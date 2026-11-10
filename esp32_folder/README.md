# Firmware do ESP32

Código que roda no ESP32 da maquete. Cada sensor tem seu próprio arquivo.

| Arquivo | O que faz |
|---|---|
| `esp32_folder.ino` | Programa principal: chama os outros arquivos e publica as mensagens |
| `config.h` | Pinos, tempos e rede |
| `segredos.h` | Senhas (não vai para o GitHub; modelo em `segredos.example.h`) |
| `ContadorPecas` | Conta as peças com o receptor de laser |
| `BotaoPeca` | Botão que soma +1 peça produzida a cada aperto |
| `Motor` | Deixa o relé do motor ligado quando o ESP32 inicia (não há controle depois) |
| `SensorVibracao` | Lê o MPU6050 e calcula RMS e pico a cada 2 s |
| `ComunicacaoMQTT` | Cria a rede Wi-Fi e envia as mensagens |

## Ligações

| Item | Pino |
|---|---|
| Receptor do laser | D27 |
| Botão (soma peça) | D32 |
| Relé do motor | D25 |
| MPU6050 SDA / SCL | D21 / D22 |

Os LEDs estão soldados em D34 e D35, mas o código não usa.

## Rede

O ESP32 cria a rede Wi-Fi "VegaMES" (IP 192.168.4.1). O notebook entra nela e roda o
Mosquitto. O ESP32 publica nestes tópicos (o número é o `MAQUINA_ID` do `config.h`):

```
vegames/maquina/1/producao   {"quantidade":1}
vegames/maquina/1/vibracao   {"rms_g":0.123,"pico_g":0.456}
vegames/maquina/1/heartbeat  {"vivo":1}
```

## Para gravar na placa

1. Copie `segredos.example.h` para `segredos.h` e preencha.
2. Na Arduino IDE, instale o suporte à placa ESP32 e a biblioteca **PubSubClient**.
3. Abra o `esp32_folder.ino`, escolha a placa e a porta COM e grave.
4. Abra o Monitor Serial (115200). A cada 5 segundos aparece uma linha `[Status]`.

## Se algo não funcionar

- **Conta peças ao contrário** (conta quando não tem peça): troque `LASER_BLOQUEADO_EM_NIVEL_ALTO` no `config.h`.
- **Motor desligado ao iniciar**: troque `MOTOR_NO_CONTATO_NF` (depende de o fio estar no NC ou no NO do relé) ou `RELE_ATIVO_EM_NIVEL_BAIXO`.
- **Não envia mensagens**: veja se o notebook aparece em "aparelhos conectados" no Monitor Serial
  e se o IP do notebook é o mesmo de `MQTT_HOST`.
