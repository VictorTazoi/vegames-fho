#include "SensorVibracao.h"
#include "config.h"
#include <Wire.h>
#include <math.h>

static const float UNIDADES_POR_G = 4096.0f;

static unsigned long ultimaAmostraMs = 0;
static unsigned long inicioJanelaMs = 0;
static unsigned long amostras = 0;
static double somaQuadrados = 0.0;
static float pico = 0.0f;

static float ultimoRms = 0.0f;
static float ultimoPico = 0.0f;

static void escreverRegistrador(uint8_t registrador, uint8_t valor) {
    Wire.beginTransmission(MPU6050_ENDERECO);
    Wire.write(registrador);
    Wire.write(valor);
    Wire.endTransmission();
}

static void configurarMpu() {
    escreverRegistrador(0x6B, 0x00);
    delay(50);
    escreverRegistrador(0x1C, 0x10);
}

static bool lerAceleracao(int16_t &ax, int16_t &ay, int16_t &az) {
    Wire.beginTransmission(MPU6050_ENDERECO);
    Wire.write(0x3B);
    if (Wire.endTransmission(false) != 0) {
        return false;
    }
    if (Wire.requestFrom((int) MPU6050_ENDERECO, 6, true) != 6) {
        return false;
    }

    ax = (int16_t) ((Wire.read() << 8) | Wire.read());
    ay = (int16_t) ((Wire.read() << 8) | Wire.read());
    az = (int16_t) ((Wire.read() << 8) | Wire.read());
    return true;
}

static void comecarNovaJanela(unsigned long agora) {
    inicioJanelaMs = agora;
    amostras = 0;
    somaQuadrados = 0.0;
    pico = 0.0f;
}

void vibracaoSetup() {
    Wire.begin(PIN_I2C_SDA, PIN_I2C_SCL);
    configurarMpu();
    comecarNovaJanela(millis());
}

bool vibracaoAtualizar() {
    unsigned long agora = millis();
    const unsigned long intervaloMs = 1000UL / VIBRACAO_AMOSTRAS_HZ;

    if (agora - ultimaAmostraMs >= intervaloMs) {
        ultimaAmostraMs = agora;

        int16_t ax, ay, az;
        if (lerAceleracao(ax, ay, az)) {
            float gx = ax / UNIDADES_POR_G;
            float gy = ay / UNIDADES_POR_G;
            float gz = az / UNIDADES_POR_G;
            float magnitude = sqrtf(gx * gx + gy * gy + gz * gz);

            somaQuadrados += (double) (magnitude * magnitude);
            if (magnitude > pico) {
                pico = magnitude;
            }
            amostras++;
        }
    }

    if (agora - inicioJanelaMs >= VIBRACAO_JANELA_MS && amostras > 0) {
        ultimoRms = sqrtf((float) (somaQuadrados / amostras));
        ultimoPico = pico;
        comecarNovaJanela(agora);
        return true;
    }

    return false;
}

float vibracaoUltimoRmsG() {
    return ultimoRms;
}

float vibracaoUltimoPicoG() {
    return ultimoPico;
}
