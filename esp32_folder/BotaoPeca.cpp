#include "BotaoPeca.h"
#include "config.h"

static int botaoAnterior = HIGH;
static unsigned long ultimoAcionamentoMs = 0;
static unsigned long total = 0;

void botaoPecaSetup() {
    pinMode(PIN_BOTAO, INPUT_PULLUP);
}

bool botaoPecaAtualizar() {
    int botaoAgora = digitalRead(PIN_BOTAO);
    unsigned long agora = millis();

    bool apertou = false;
    if (botaoAnterior == HIGH && botaoAgora == LOW && (agora - ultimoAcionamentoMs) > BOTAO_DEBOUNCE_MS) {
        total++;
        ultimoAcionamentoMs = agora;
        apertou = true;
    }
    botaoAnterior = botaoAgora;
    return apertou;
}

unsigned long botaoPecaTotal() {
    return total;
}
