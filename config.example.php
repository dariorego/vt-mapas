<?php
/**
 * Configuração de Exemplo
 * 
 * Renomeie este arquivo para config.php e configure suas credenciais.
 */

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'nome_do_banco');
define('DB_USER', 'usuario');
define('DB_PASS', 'senha');
define('DB_CHARSET', 'utf8mb4');

// ID do Cliente de Partida (Ponto Fixo)
define('STARTING_CLIENT_ID', 120);

// Configurações de Otimização de Rota
define('EARTH_RADIUS_KM', 6371);
define('AVG_SPEED_KMH', 40);

// Configurações de Timezone
date_default_timezone_set('America/Sao_Paulo');

// Modo de Debug
define('DEBUG_MODE', false);

function debug_log($message, $data = null)
{
    if (DEBUG_MODE) {
        echo "[DEBUG] " . date('Y-m-d H:i:s') . " - " . $message;
        if ($data !== null) {
            echo "\n";
            print_r($data);
        }
        echo "\n";
    }
}
