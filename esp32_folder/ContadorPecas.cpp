#include "ContadorPecas.h"
#include "config.h"

static unsigned long ultimaLeituraMs = 0;
static unsigned long ultimaContagemMs = 0;
static bool bloqueado = false;
static unsigned long total = 0;

static bool lerLaser() {
    bool nivelAlto = digitalRead(PIN_LASER) == HIGH;
    return LASER_BLOQUEADO_EM_NIVEL_ALTO ? nivelAlto : !nivelAlto;
}

void contadorPecasSetup() {
    pinMode(PIN_LASER, INPUT);
}

bool contadorPecasAtualizar() {
    unsigned long agora = millis();
    if (agora - ultimaLeituraMs < LASER_INTERVALO_LEITURA_MS) {
        return false;
    }
    ultimaLeituraMs = agora;

    bool bloqueadoAgora = lerLaser();

    bool novaPeca = false;
    if (bloqueado && !bloqueadoAgora && (agora - ultimaContagemMs) > PECA_DEBOUNCE_MS) {
        total++;
        ultimaContagemMs = agora;
        novaPeca = true;
    }

    bloqueado = bloqueadoAgora;
    return novaPeca;
}

unsigned long contadorPecasTotal() {
    return total;
}

bool contadorPecasBloqueado() {
    return bloqueado;
}
