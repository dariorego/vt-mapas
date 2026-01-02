<?php
/**
 * Script Principal de Otimização de Rotas
 * 
 * Este script pode ser executado via linha de comando ou incluído em outros arquivos.
 */

require_once 'config.php';
require_once 'Database.php';
require_once 'RouteOptimizer.php';

// Verifica se está rodando via CLI
$isCli = (php_sapi_name() === 'cli');

try {
    if ($isCli) {
        echo "Iniciando processo de otimização de rotas...\n";
    }

    // Instancia a conexão com o banco
    $db = new Database();

    // Instancia o otimizador
    $optimizer = new RouteOptimizer($db);

    // 1. Busca o ponto de partida (Cliente 120)
    $optimizer->fetchStartingPoint();

    // Parâmetros de busca (podem vir de argumentos CLI ou usar padrão)
    $viagemId = 0; // Padrão

    if ($isCli) {
        // Argumentos: php optimize_routes.php <viagem_id>
        if (isset($argv[1]))
            $viagemId = $argv[1];

        if (empty($viagemId)) {
            die("ERRO: Informe o ID da viagem. Exemplo: php optimize_routes.php 123\n");
        }

        echo "Buscando entregas para Viagem ID: {$viagemId}...\n";
    }

    // 2. Busca os pontos de entrega
    $optimizer->fetchDeliveryPoints($viagemId);

    // 3. Executa a otimização
    if ($isCli)
        echo "Calculando melhor rota...\n";
    $route = $optimizer->optimizeRoute();

    // 4. Exibe o resumo
    if ($isCli) {
        $optimizer->displayRouteSummary();
        echo "Atualizando banco de dados...\n";
    }

    // 5. Atualiza o banco de dados
    $updatedCount = $optimizer->updateDatabase();

    if ($isCli) {
        echo "Processo concluído com sucesso!\n";
    }

} catch (Exception $e) {
    if ($isCli) {
        echo "ERRO CRÍTICO: " . $e->getMessage() . "\n";
    } else {
        // Se for web, relança para ser tratado pelo index.php ou exibe JSON
        throw $e;
    }
}
