#include <WiFi.h>
#include "config.h"
#include "ComunicacaoMQTT.h"
#include "ContadorPecas.h"
#include "BotaoPeca.h"
#include "Motor.h"
#include "SensorVibracao.h"

static char topicoProducao[48];
static char topicoVibracao[48];
static char topicoHeartbeat[48];
static char mensagem[160];
static unsigned long ultimoLogMs = 0;
static unsigned long ultimoHeartbeatMs = 0;

void setup() {
    Serial.begin(115200);
    delay(200);
    Serial.println("\n[VegaMES] Iniciando...");

    snprintf(topicoProducao, sizeof(topicoProducao), "vegames/maquina/%d/producao", MAQUINA_ID);
    snprintf(topicoVibracao, sizeof(topicoVibracao), "vegames/maquina/%d/vibracao", MAQUINA_ID);

    snprintf(topicoHeartbeat, sizeof(topicoHeartbeat), "vegames/maquina/%d/heartbeat", MAQUINA_ID);

    contadorPecasSetup();
    botaoPecaSetup();
    motorSetup();
    vibracaoSetup();
    mqttSetup();

    Serial.println("[VegaMES] Pronto.");
}

void loop() {
    mqttLoop();

    if (contadorPecasAtualizar()) {
        snprintf(mensagem, sizeof(mensagem), "{\"quantidade\":1}");
        mqttPublicar(topicoProducao, mensagem);
    }

    if (botaoPecaAtualizar()) {
        snprintf(mensagem, sizeof(mensagem), "{\"quantidade\":1}");
        mqttPublicar(topicoProducao, mensagem);
    }

    if (vibracaoAtualizar()) {
        snprintf(mensagem, sizeof(mensagem), "{\"rms_g\":%.3f,\"pico_g\":%.3f}",
                 vibracaoUltimoRmsG(), vibracaoUltimoPicoG());
        mqttPublicar(topicoVibracao, mensagem);
    }

    if (millis() - ultimoHeartbeatMs > HEARTBEAT_INTERVALO_MS) {
        ultimoHeartbeatMs = millis();
        mqttPublicar(topicoHeartbeat, "{\"vivo\":1}");
    }

    unsigned long agora = millis();
    if (agora - ultimoLogMs > 5000) {
        ultimoLogMs = agora;
        Serial.printf("[Status] mqtt=%s laser=%s pecas_laser=%lu pecas_botao=%lu aparelhos_na_rede=%d\n",
                      mqttConectado() ? "conectado" : "SEM CONEXAO",
                      contadorPecasBloqueado() ? "bloqueado" : "livre",
                      contadorPecasTotal(),
                      botaoPecaTotal(),
                      WiFi.softAPgetStationNum());
    }
}
