import argparse
import sys
import time
from pathlib import Path

import joblib
import numpy as np
import pymysql
from sklearn.ensemble import IsolationForest

BANCO = dict(host="localhost", user="root", password="", database="vegames", charset="utf8mb4")

ARQUIVO_MODELO = Path(__file__).with_name("modelo_vibracao.joblib")

MINIMO_LEITURAS = 30


def conectar():
    return pymysql.connect(**BANCO, autocommit=True)


def treinar(minutos, maquina_id):
    con = conectar()
    cur = con.cursor()
    cur.execute(
        "SELECT rms_g, pico_g FROM leituras_vibracao "
        "WHERE maquina_id = %s AND registrado_em >= NOW() - INTERVAL %s MINUTE",
        (maquina_id, minutos),
    )
    leituras = np.array(cur.fetchall(), dtype=float)
    con.close()

    if len(leituras) < MINIMO_LEITURAS:
        sys.exit(f"Só tem {len(leituras)} leituras. Deixe a esteira rodando mais um pouco.")

    modelo = IsolationForest(n_estimators=100, contamination=0.02, random_state=42)
    modelo.fit(leituras)

    joblib.dump(modelo, ARQUIVO_MODELO)
    print(f"Modelo treinado com {len(leituras)} leituras e salvo em {ARQUIVO_MODELO.name}")


def pontuar(maquina_id):
    if not ARQUIVO_MODELO.exists():
        sys.exit("Ainda não existe modelo. Rode primeiro: python ml/anomalia.py treinar")
    modelo = joblib.load(ARQUIVO_MODELO)

    con = conectar()
    cur = con.cursor()

    cur.execute(
        "SELECT id, rms_g, pico_g FROM leituras_vibracao "
        "WHERE maquina_id = %s AND anomalia IS NULL ORDER BY id",
        (maquina_id,),
    )
    linhas = cur.fetchall()
    if not linhas:
        con.close()
        return

    ids = [int(l[0]) for l in linhas]
    valores = np.array([[float(l[1]), float(l[2])] for l in linhas])

    notas = modelo.decision_function(valores)
    previsoes = modelo.predict(valores)

    for i in range(len(ids)):
        anomalia = 1 if previsoes[i] == -1 else 0
        cur.execute(
            "UPDATE leituras_vibracao SET score_anomalia = %s, anomalia = %s WHERE id = %s",
            (float(notas[i]), anomalia, ids[i]),
        )
        resultado = "ANOMALIA" if anomalia else "normal"
        print(f"leitura {ids[i]}: rms={linhas[i][1]} pico={linhas[i][2]} nota={notas[i]:+.3f} -> {resultado}")

    con.close()


def main():
    parser = argparse.ArgumentParser(description="Detecção de anomalia de vibração (IsolationForest)")
    parser.add_argument("comando", choices=["treinar", "pontuar"])
    parser.add_argument("--minutos", type=int, default=5, help="minutos de dados para treinar")
    parser.add_argument("--maquina", type=int, default=1, help="id da máquina")
    parser.add_argument("--loop", action="store_true", help="repetir a cada 3 segundos")
    args = parser.parse_args()

    if args.comando == "treinar":
        treinar(args.minutos, args.maquina)
        return

    pontuar(args.maquina)
    while args.loop:
        time.sleep(3)
        pontuar(args.maquina)


if __name__ == "__main__":
    main()
