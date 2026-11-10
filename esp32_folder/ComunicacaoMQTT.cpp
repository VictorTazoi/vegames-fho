#include "ComunicacaoMQTT.h"
#include "config.h"
#include <WiFi.h>
#include <PubSubClient.h>

static WiFiClient wifiClient;
static PubSubClient mqttClient(wifiClient);

static unsigned long ultimaTentativaMs = 0;
static const unsigned long INTERVALO_RECONEXAO_MS = 5000;
static int clientesAnteriores = -1;

static void iniciarRede() {
    IPAddress ip(AP_IP_1, AP_IP_2, AP_IP_3, AP_IP_4);
    IPAddress mascara(255, 255, 255, 0);

    WiFi.mode(WIFI_AP);
    WiFi.softAPConfig(ip, ip, mascara);
    bool ok = WiFi.softAP(WIFI_SSID, WIFI_PASSWORD, WIFI_CANAL, 0, WIFI_MAX_CLIENTES);

    if (ok) {
        Serial.printf("[WiFi] Rede \"%s\" criada. IP do ESP32: %s\n",
                      WIFI_SSID, WiFi.softAPIP().toString().c_str());
    } else {
        Serial.println("[WiFi] Não foi possível criar a rede.");
    }
}

static void atualizarLed(int clientes) {
    if (mqttClient.connected()) {
        digitalWrite(PIN_LED_STATUS, HIGH);
        return;
    }
    unsigned long periodo = (clientes > 0) ? 200 : 1000;
    digitalWrite(PIN_LED_STATUS, (millis() / periodo) % 2);
}

static void conectarMqtt() {
    if (mqttClient.connected()) {
        return;
    }
    unsigned long agora = millis();
    if (agora - ultimaTentativaMs < INTERVALO_RECONEXAO_MS) {
        return;
    }
    ultimaTentativaMs = agora;

    Serial.printf("[MQTT] Conectando em %s:%d...\n", MQTT_HOST, MQTT_PORT);
    bool conectou = mqttClient.connect(MQTT_CLIENT_ID, MQTT_USER, MQTT_PASS);

    if (conectou) {
        Serial.println("[MQTT] Conectado.");
    } else {
        Serial.printf("[MQTT] Falhou (código %d).\n", mqttClient.state());
    }
}

void mqttSetup() {
    pinMode(PIN_LED_STATUS, OUTPUT);
    iniciarRede();
    mqttClient.setServer(MQTT_HOST, MQTT_PORT);
}

void mqttLoop() {
    int clientes = WiFi.softAPgetStationNum();
    if (clientes != clientesAnteriores) {
        Serial.printf("[WiFi] Aparelhos conectados: %d\n", clientes);
        clientesAnteriores = clientes;
    }

    atualizarLed(clientes);

    if (clientes > 0) {
        conectarMqtt();
        mqttClient.loop();
    }
}

bool mqttPublicar(const char *topico, const char *payloadJson) {
    if (!mqttClient.connected()) {
        Serial.println("[MQTT] Sem conexão, mensagem descartada.");
        return false;
    }
    bool ok = mqttClient.publish(topico, payloadJson);
    Serial.printf("[MQTT] %s -> %s (%s)\n", topico, payloadJson, ok ? "ok" : "falhou");
    return ok;
}

bool mqttConectado() {
    return mqttClient.connected();
}
