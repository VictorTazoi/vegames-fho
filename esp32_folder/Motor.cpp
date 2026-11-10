#include "Motor.h"
#include "config.h"

void motorSetup() {
    pinMode(PIN_RELE_MOTOR, OUTPUT);

    bool acionarRele = MOTOR_NO_CONTATO_NF ? false : true;
    bool nivel = RELE_ATIVO_EM_NIVEL_BAIXO ? !acionarRele : acionarRele;
    digitalWrite(PIN_RELE_MOTOR, nivel ? HIGH : LOW);
}
