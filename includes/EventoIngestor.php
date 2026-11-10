<?php
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/OeeCalculator.php';

    class EventoIngestor
    {
        private PDO $pdo;

        public function __construct()
        {
            $this->pdo = db();
        }

        private function maquinaExiste(int $maquinaId): bool
        {
            $stmt = $this->pdo->prepare('SELECT id FROM maquinas WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $maquinaId]);
            return (bool) $stmt->fetchColumn();
        }

        public function processarMensagem(string $topico, array $payload): array
        {
            if (!preg_match('#^vegames/maquina/(\d+)/(producao|refugo|parada|vibracao)$#', $topico, $m)) {
                throw new InvalidArgumentException('Tópico inválido: ' . $topico);
            }
            $maquinaId = (int) $m[1];
            $canal = $m[2];

            if (!$this->maquinaExiste($maquinaId)) {
                throw new InvalidArgumentException('Máquina desconhecida: ' . $maquinaId);
            }

            return match ($canal) {
                'producao' => $this->registrarProducao($maquinaId, $payload),
                'refugo' => $this->registrarRefugo($maquinaId, $payload),
                'parada' => $this->registrarParada($maquinaId, $payload),
                'vibracao' => $this->registrarVibracao($maquinaId, $payload),
            };
        }

        public function registrarProducao(int $maquinaId, array $payload): array
        {
            $quantidade = max(1, (int) ($payload['quantidade'] ?? 1));
            $sensorId = $payload['sensor_id'] ?? null;

            if ($sensorId === null) {
                $stmt = $this->pdo->prepare(
                    "SELECT id FROM sensores WHERE maquina_id = :maquina_id AND tipo = 'laser' LIMIT 1"
                );
                $stmt->execute(['maquina_id' => $maquinaId]);
                $sensorId = $stmt->fetchColumn() ?: null;
            }
            if (!$sensorId) {
                throw new InvalidArgumentException('Esta máquina não tem sensor laser cadastrado.');
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO contagem_producao (maquina_id, sensor_id, registrado_em, pecas_contadas)
                VALUES (:maquina_id, :sensor_id, NOW(3), :quantidade)'
            );
            $stmt->execute([
                'maquina_id' => $maquinaId,
                'sensor_id' => $sensorId,
                'quantidade' => $quantidade,
            ]);
            $contagemId = (int) $this->pdo->lastInsertId();

            $this->verificarMetaDaOrdem($maquinaId);

            return ['contagem_id' => $contagemId, 'quantidade' => $quantidade];
        }

        private function verificarMetaDaOrdem(int $maquinaId): void
        {
            $stmt = $this->pdo->prepare(
                "SELECT id, meta_producao, inicio FROM ordens_producao
                WHERE maquina_id = :maquina_id AND status = 'em_andamento' LIMIT 1"
            );
            $stmt->execute(['maquina_id' => $maquinaId]);
            $ordem = $stmt->fetch();
            if (!$ordem) {
                return;
            }

            $stmtProduzidas = $this->pdo->prepare(
                'SELECT COALESCE(SUM(pecas_contadas),0) FROM contagem_producao
                WHERE maquina_id = :maquina_id AND registrado_em >= :inicio'
            );
            $stmtProduzidas->execute(['maquina_id' => $maquinaId, 'inicio' => $ordem['inicio']]);
            $produzidas = (int) $stmtProduzidas->fetchColumn();

            if ($produzidas >= (int) $ordem['meta_producao']) {
                $this->pdo->prepare(
                    "UPDATE ordens_producao SET status = 'finalizada', fim = NOW(3) WHERE id = :id"
                )->execute(['id' => $ordem['id']]);

                (new OeeCalculator())->salvarSnapshotOrdem((int) $ordem['id']);
            }
        }

        public function registrarRefugo(int $maquinaId, array $payload): array
        {
            $quantidade = max(1, (int) ($payload['quantidade'] ?? 1));
            $motivo = $payload['motivo'] ?? null;

            $stmt = $this->pdo->prepare(
                'INSERT INTO refugo (maquina_id, registrado_em, quantidade, motivo)
                VALUES (:maquina_id, NOW(), :quantidade, :motivo)'
            );
            $stmt->execute([
                'maquina_id' => $maquinaId,
                'quantidade' => $quantidade,
                'motivo' => $motivo,
            ]);

            return ['refugo_id' => (int) $this->pdo->lastInsertId(), 'quantidade' => $quantidade];
        }

        public function registrarVibracao(int $maquinaId, array $payload): array
        {
            $rms = $payload['rms_g'] ?? null;
            $pico = $payload['pico_g'] ?? null;
            if (!is_numeric($rms) || !is_numeric($pico)) {
                throw new InvalidArgumentException('A vibração precisa de "rms_g" e "pico_g" numéricos.');
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO leituras_vibracao (maquina_id, registrado_em, rms_g, pico_g)
                VALUES (:maquina_id, NOW(3), :rms_g, :pico_g)'
            );
            $stmt->execute([
                'maquina_id' => $maquinaId,
                'rms_g' => (float) $rms,
                'pico_g' => (float) $pico,
            ]);

            return ['vibracao_id' => (int) $this->pdo->lastInsertId()];
        }

        public function registrarParada(int $maquinaId, array $payload): array
        {
            $acao = $payload['acao'] ?? null;

            if ($acao === 'inicio') {
                $stmt = $this->pdo->prepare(
                    'SELECT id FROM paradas WHERE maquina_id = :maquina_id AND fim IS NULL LIMIT 1'
                );
                $stmt->execute(['maquina_id' => $maquinaId]);
                if ($stmt->fetchColumn()) {
                    return ['aviso' => 'Já existe uma parada em aberto para esta máquina.'];
                }

                $tipo = $payload['tipo'] ?? 'nao_programada';
                if (!in_array($tipo, ['programada', 'nao_programada', 'manutencao'], true)) {
                    $tipo = 'nao_programada';
                }

                $stmt = $this->pdo->prepare(
                    'INSERT INTO paradas (maquina_id, tipo, motivo, inicio)
                    VALUES (:maquina_id, :tipo, :motivo, NOW())'
                );
                $stmt->execute([
                    'maquina_id' => $maquinaId,
                    'tipo' => $tipo,
                    'motivo' => $payload['motivo'] ?? null,
                ]);

                $this->pdo->prepare('UPDATE maquinas SET status = ? WHERE id = ?')->execute(['parada', $maquinaId]);

                return ['parada_id' => (int) $this->pdo->lastInsertId(), 'acao' => 'inicio'];
            }

            if ($acao === 'fim') {
                $stmt = $this->pdo->prepare(
                    'SELECT id FROM paradas WHERE maquina_id = :maquina_id AND fim IS NULL
                    ORDER BY inicio DESC LIMIT 1'
                );
                $stmt->execute(['maquina_id' => $maquinaId]);
                $paradaId = $stmt->fetchColumn();

                if (!$paradaId) {
                    return ['aviso' => 'Não há parada em aberto para esta máquina.'];
                }

                $update = $this->pdo->prepare(
                    'UPDATE paradas
                    SET fim = NOW(),
                        motivo = COALESCE(:motivo, motivo)
                    WHERE id = :id'
                );
                $update->execute([
                    'motivo' => $payload['motivo'] ?? null,
                    'id' => $paradaId,
                ]);

                $this->pdo->prepare('UPDATE maquinas SET status = ? WHERE id = ?')->execute(['operando', $maquinaId]);

                return ['parada_id' => (int) $paradaId, 'acao' => 'fim'];
            }

            throw new InvalidArgumentException('O campo "acao" deve ser "inicio" ou "fim".');
        }
    }
