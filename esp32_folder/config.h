#pragma once
#include "segredos.h"

#define AP_IP_1 192
#define AP_IP_2 168
#define AP_IP_3 4
#define AP_IP_4 1
#define WIFI_CANAL        6
#define WIFI_MAX_CLIENTES 2

#define MQTT_HOST      "192.168.4.2"
#define MQTT_PORT      1883
#define MQTT_CLIENT_ID "vegames_gt01"

#define MAQUINA_ID 1

#define PIN_LASER      27
#define PIN_BOTAO      32
#define PIN_RELE_MOTOR 25
#define PIN_I2C_SDA    21
#define PIN_I2C_SCL    22
#define PIN_LED_STATUS 2

#define LASER_BLOQUEADO_EM_NIVEL_ALTO true

#define RELE_ATIVO_EM_NIVEL_BAIXO false
#define MOTOR_NO_CONTATO_NF       true

#define LASER_INTERVALO_LEITURA_MS 5
#define PECA_DEBOUNCE_MS           120
#define BOTAO_DEBOUNCE_MS          300
#define HEARTBEAT_INTERVALO_MS     10000

#define MPU6050_ENDERECO     0x68
#define VIBRACAO_AMOSTRAS_HZ 100
#define VIBRACAO_JANELA_MS   2000
