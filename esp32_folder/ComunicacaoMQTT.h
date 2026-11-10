#pragma once
#include <Arduino.h>

void mqttSetup();
void mqttLoop();
bool mqttPublicar(const char *topico, const char *payloadJson);
bool mqttConectado();
