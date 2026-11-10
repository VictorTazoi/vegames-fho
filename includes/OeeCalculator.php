<?php
    require_once __DIR__ . '/../config/database.php';

    class OeeCalculator
    {
        private PDO $pdo;

        public function __construct()
        {
            $this->pdo = db();
        }

        private function condicaoTurno(string $coluna, ?int $turnoId, array &$params, string $sufixo): ?string
        {
            if (!$turnoId) {
                return null;
            }
            $stmt = $this->pdo->prepare('SELECT hora_inicio, hora_fim FROM turnos WHERE id = :id');
            $stmt->execute(['id' => $turnoId]);
            $turno = $stmt->fetch();
            if (!$turno) {
                return null;
            }

            $params["ti{$sufixo}"] = $turno['hora_inicio'];
            $params["tf{$sufixo}"] = $turno['hora_fim'];

            if ($turno['hora_inicio'] < $turno['hora_fim']) {
                return "TIME({$coluna}) BETWEEN :ti{$sufixo} AND :tf{$sufixo}";
            }
            return "(TIME({$coluna}) >= :ti{$sufixo} OR TIME({$coluna}) <= :tf{$sufixo})";
        }

        private function janelaOrdem(?int $ordemId): ?array
        {
            if (!$ordemId) {
                return null;
            }
            $stmt = $this->pdo->prepare('SELECT maquina_id, inicio, fim FROM ordens_producao WHERE id = :id');
            $stmt->execute(['id' => $ordemId]);
            $ordem = $stmt->fetch();
            return $ordem ?: null;
        }

        private function condicoes(array $filtros, string $colunaTempo, string $sufixo): array
        {
            $where = [];
            $params = [];

            if (!empty($filtros['maquina_id'])) {
                $where[] = 'maquina_id = :maquina_id' . $sufixo;
                $params['maquina_id' . $sufixo] = $filtros['maquina_id'];
            }
            if (!empty($filtros['data_inicio'])) {
                $where[] = "{$colunaTempo} >= :data_inicio{$sufixo}";
                $params['data_inicio' . $sufixo] = $filtros['data_inicio'] . ' 00:00:00';
            }
            if (!empty($filtros['data_fim'])) {
                $where[] = "{$colunaTempo} <= :data_fim{$sufixo}";
                $params['data_fim' . $sufixo] = $filtros['data_fim'] . ' 23:59:59';
            }

            $condTurno = $this->condicaoTurno($colunaTempo, $filtros['turno_id'] ?? null, $params, $sufixo);
            if ($condTurno) {
                $where[] = $condTurno;
            }

            $ordem = $this->janelaOrdem($filtros['ordem_id'] ?? null);
            if ($ordem) {
                $where[] = 'maquina_id = :ordem_maquina' . $sufixo;
                $params['ordem_maquina' . $sufixo] = $ordem['maquina_id'];
                $where[] = "{$colunaTempo} >= :ordem_inicio{$sufixo}";
                $params['ordem_inicio' . $sufixo] = $ordem['inicio'];
                if ($ordem['fim']) {
                    $where[] = "{$colunaTempo} <= :ordem_fim{$sufixo}";
                    $params['ordem_fim' . $sufixo] = $ordem['fim'];
                }
            }

            return [$where, $params];
        }

        public function calcular(array $filtros): array
        {
            $pdo = $this->pdo;

            [$whereProd, $paramsProd] = $this->condicoes($filtros, 'registrado_em', '_p');
            $sql = 'SELECT COALESCE(SUM(pecas_contadas),0) AS total FROM contagem_producao';
            if ($whereProd) {
                $sql .= ' WHERE ' . implode(' AND ', $whereProd);
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($paramsProd);
            $produzidas = (int) $stmt->fetchColumn();

            [$whereRef, $paramsRef] = $this->condicoes($filtros, 'registrado_em', '_r');
            $sql = 'SELECT COALESCE(SUM(quantidade),0) AS total FROM refugo';
            if ($whereRef) {
                $sql .= ' WHERE ' . implode(' AND ', $whereRef);
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($paramsRef);
            $rejeitadas = (int) $stmt->fetchColumn();

            $totalProduzido = $produzidas + $rejeitadas;

            [$wherePr, $paramsPr] = $this->condicoes($filtros, 'inicio', '_pr');
            $sql = "SELECT COALESCE(SUM(TIMESTAMPDIFF(SECOND, inicio, COALESCE(fim, NOW()))),0) AS total_seg,
                        COUNT(*) AS qtd_paradas
                    FROM paradas";
            if ($wherePr) {
                $sql .= ' WHERE ' . implode(' AND ', $wherePr);
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($paramsPr);
            $paradaRow = $stmt->fetch();
            $tempoParadoSeg = (int) $paradaRow['total_seg'];
            $qtdParadas = (int) $paradaRow['qtd_paradas'];

            [$whereOp, $paramsOp] = $this->condicoesOrdens($filtros);
            $sql = "SELECT COALESCE(SUM(tempo_planejado_seg),0) AS tempo_planejado,
                        COALESCE(AVG(tempo_ciclo_ideal_seg), 5) AS ciclo_ideal
                    FROM ordens_producao";
            if ($whereOp) {
                $sql .= ' WHERE ' . implode(' AND ', $whereOp);
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($paramsOp);
            $opRow = $stmt->fetch();
            $cicloIdealSeg = (float) $opRow['ciclo_ideal'];

            if (!empty($filtros['ordem_id'])) {
                $tempoPlanejadoSeg = (int) $opRow['tempo_planejado'];
            } else {
                $tempoPlanejadoSeg = $this->tempoPlanejadoPorPeriodo($filtros);
            }

            $tempoOperandoSeg = max(0, $tempoPlanejadoSeg - $tempoParadoSeg);

            $disponibilidade = $tempoPlanejadoSeg > 0
                ? min(1, $tempoOperandoSeg / $tempoPlanejadoSeg)
                : 0;

            $performance = $tempoOperandoSeg > 0
                ? ($totalProduzido * $cicloIdealSeg) / $tempoOperandoSeg
                : 0;

            $qualidade = $totalProduzido > 0
                ? $produzidas / $totalProduzido
                : 0;

            $oee = $disponibilidade * $performance * $qualidade;

            return [
                'produzidas' => $produzidas,
                'rejeitadas' => $rejeitadas,
                'total_produzido' => $totalProduzido,
                'tempo_parado_seg' => $tempoParadoSeg,
                'qtd_paradas' => $qtdParadas,
                'tempo_planejado_seg' => $tempoPlanejadoSeg,
                'tempo_operando_seg' => $tempoOperandoSeg,
                'disponibilidade' => round($disponibilidade * 100, 1),
                'performance' => round($performance * 100, 1),
                'qualidade' => round($qualidade * 100, 1),
                'oee' => round($oee * 100, 1),
            ];
        }

        public static function classificarOee(float $oeePercentual): string
        {
            if ($oeePercentual >= 85) {
                return 'Classe mundial';
            }
            if ($oeePercentual >= 60) {
                return 'Aceitável (abaixo do padrão classe mundial de 85%)';
            }
            if ($oeePercentual >= 40) {
                return 'Baixo';
            }
            return 'Crítico';
        }

        private function condicoesOrdens(array $filtros): array
        {
            $where = [];
            $params = [];

            if (!empty($filtros['maquina_id'])) {
                $where[] = 'maquina_id = :maquina_id';
                $params['maquina_id'] = $filtros['maquina_id'];
            }
            if (!empty($filtros['turno_id'])) {
                $where[] = 'turno_id = :turno_id';
                $params['turno_id'] = $filtros['turno_id'];
            }
            if (!empty($filtros['ordem_id'])) {
                $where[] = 'id = :ordem_id';
                $params['ordem_id'] = $filtros['ordem_id'];
            }
            if (!empty($filtros['data_inicio'])) {
                $where[] = 'inicio >= :data_inicio';
                $params['data_inicio'] = $filtros['data_inicio'] . ' 00:00:00';
            }
            if (!empty($filtros['data_fim'])) {
                $where[] = 'inicio <= :data_fim';
                $params['data_fim'] = $filtros['data_fim'] . ' 23:59:59';
            }

            return [$where, $params];
        }

        private function tempoPlanejadoPorPeriodo(array $filtros): int
        {
            $inicio = !empty($filtros['data_inicio']) ? new DateTime($filtros['data_inicio']) : new DateTime('today');
            $fim = !empty($filtros['data_fim']) ? new DateTime($filtros['data_fim']) : new DateTime('today');
            $dias = max(1, $inicio->diff($fim)->days + 1);

            return $dias * 8 * 3600;
        }

        public function calcularParaOrdem(int $ordemId): ?array
        {
            $stmt = $this->pdo->prepare('SELECT maquina_id FROM ordens_producao WHERE id = :id');
            $stmt->execute(['id' => $ordemId]);
            $maquinaId = $stmt->fetchColumn();
            if (!$maquinaId) {
                return null;
            }

            return $this->calcular(['ordem_id' => $ordemId, 'maquina_id' => $maquinaId]);
        }

        public function salvarSnapshotOrdem(int $ordemId): void
        {
            $stmt = $this->pdo->prepare('SELECT maquina_id FROM ordens_producao WHERE id = :id');
            $stmt->execute(['id' => $ordemId]);
            $maquinaId = $stmt->fetchColumn();
            if (!$maquinaId) {
                return;
            }

            $dados = $this->calcularParaOrdem($ordemId);
            if (!$dados) {
                return;
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO oee_resultados
                    (ordem_id, maquina_id, produzidas, rejeitadas, tempo_parado_seg,
                    tempo_planejado_seg, tempo_operando_seg, disponibilidade, performance, qualidade, oee)
                VALUES
                    (:ordem_id, :maquina_id, :produzidas, :rejeitadas, :tempo_parado_seg,
                    :tempo_planejado_seg, :tempo_operando_seg, :disponibilidade, :performance, :qualidade, :oee)
                ON DUPLICATE KEY UPDATE
                    produzidas = VALUES(produzidas), rejeitadas = VALUES(rejeitadas),
                    tempo_parado_seg = VALUES(tempo_parado_seg), tempo_planejado_seg = VALUES(tempo_planejado_seg),
                    tempo_operando_seg = VALUES(tempo_operando_seg), disponibilidade = VALUES(disponibilidade),
                    performance = VALUES(performance), qualidade = VALUES(qualidade), oee = VALUES(oee)'
            );
            $stmt->execute([
                'ordem_id' => $ordemId,
                'maquina_id' => $maquinaId,
                'produzidas' => $dados['produzidas'],
                'rejeitadas' => $dados['rejeitadas'],
                'tempo_parado_seg' => $dados['tempo_parado_seg'],
                'tempo_planejado_seg' => $dados['tempo_planejado_seg'],
                'tempo_operando_seg' => $dados['tempo_operando_seg'],
                'disponibilidade' => $dados['disponibilidade'],
                'performance' => $dados['performance'],
                'qualidade' => $dados['qualidade'],
                'oee' => $dados['oee'],
            ]);
        }

        public function calcularPorMaquina(array $filtrosBase): array
        {
            $pdo = $this->pdo;
            $maquinas = $pdo->query('SELECT id, nome, localizacao, status FROM maquinas ORDER BY nome')->fetchAll();

            $resultado = [];
            foreach ($maquinas as $maquina) {
                $filtros = $filtrosBase;
                $filtros['maquina_id'] = $maquina['id'];
                $dados = $this->calcular($filtros);
                $dados['maquina_id'] = (int) $maquina['id'];
                $dados['nome'] = $maquina['nome'];
                $dados['localizacao'] = $maquina['localizacao'];
                $dados['status_maquina'] = $maquina['status'];
                $resultado[] = $dados;
            }

            return $resultado;
        }
    }
