<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/auth.php';

    exigirLoginApi();
    session_write_close();

    set_time_limit(0);
    ignore_user_abort(false);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');

    $pdo = db();

    $tabelasInsert = [
        'contagem_producao' => 'producao',
        'refugo' => 'refugo',
    ];

    $tabelasUpdate = [
        'paradas' => 'parada',
        'ordens_producao' => 'ordem',
    ];

    function assinaturaInsert(PDO $pdo, string $tabela): int
    {
        return (int) $pdo->query("SELECT COALESCE(MAX(id),0) FROM {$tabela}")->fetchColumn();
    }

    function assinaturaUpdate(PDO $pdo, string $tabela): int
    {
        return (int) $pdo->query("SELECT COALESCE(MAX(UNIX_TIMESTAMP(atualizado_em)),0) FROM {$tabela}")->fetchColumn();
    }

    $ultimos = [];
    foreach ($tabelasInsert as $tabela => $tipo) {
        $ultimos[$tabela] = assinaturaInsert($pdo, $tabela);
    }
    foreach ($tabelasUpdate as $tabela => $tipo) {
        $ultimos[$tabela] = assinaturaUpdate($pdo, $tabela);
    }

    $inicio = time();

    while (true) {
        if (connection_aborted()) {
            break;
        }

        if (time() - $inicio > 280) {
            break;
        }

        $houveEvento = false;
        foreach ($tabelasInsert as $tabela => $tipo) {
            $atual = assinaturaInsert($pdo, $tabela);
            if ($atual > $ultimos[$tabela]) {
                $ultimos[$tabela] = $atual;
                $houveEvento = true;
                echo "event: {$tipo}\n";
                echo 'data: ' . json_encode(['tabela' => $tabela, 'ultimo_id' => $atual]) . "\n\n";
            }
        }
        foreach ($tabelasUpdate as $tabela => $tipo) {
            $atual = assinaturaUpdate($pdo, $tabela);
            if ($atual !== $ultimos[$tabela]) {
                $ultimos[$tabela] = $atual;
                $houveEvento = true;
                echo "event: {$tipo}\n";
                echo 'data: ' . json_encode(['tabela' => $tabela]) . "\n\n";
            }
        }

        if (!$houveEvento) {
            echo ": ping\n\n";
        }

        flush();
        usleep(500000);
    }
