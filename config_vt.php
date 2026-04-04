<?php
/**
 * Configuração do Banco de Dados e Aplicação
 * 
 * IMPORTANTE: Este arquivo contém credenciais sensíveis.
 * Não compartilhe ou faça commit deste arquivo em repositórios públicos.
 */

// Configurações do Banco de Dados
// Configurações do Banco de Dados
define('DB_HOST', getenv('DB_HOST') ?: '62.72.9.195');
#define('DB_NAME', getenv('DB_NAME') ?: 'prod_vt_dev');
define('DB_NAME', getenv('DB_NAME') ?: 'prod_br');
define('DB_USER', getenv('DB_USER') ?: 'admin');
define('DB_PASS', getenv('DB_PASS') ?: '!@#123qweQWE');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// ─── Identidade Visual da Empresa ─────────────────────────────────────────────
// Altere estas configurações para cada cliente/instalação
define('EMPRESA_NOME',        'Victor Transportes');
define('EMPRESA_SLOGAN',      'Sistema de Gestão de Entregas');
define('EMPRESA_TELEFONE',    '(81) 99999-9999');
define('EMPRESA_WHATSAPP',    '5581999999999');   // formato internacional, sem + nem espaços
define('EMPRESA_LOGO',        '');                // caminho relativo ex: 'assets/logo.png' — vazio = usa nome textual
define('EMPRESA_COR_PRIMARIA',   '#2E9D6F');      // cor principal (botões, sidebar, header)
define('EMPRESA_COR_SECUNDARIA', '#248C5A');      // cor escura (hover, gradiente)
define('EMPRESA_COR_TEXTO',      '#ffffff');      // cor do texto sobre a cor primária

// ID do Cliente de Partida (Ponto Fixo)
define('STARTING_CLIENT_ID', 120);

// ─── Google Maps API ──────────────────────────────────────────────────────────
// Gere sua chave em: https://console.cloud.google.com/apis/credentials
// Habilite: Directions API
// Restrinja a chave por IP do servidor para maior segurança
define('GOOGLE_MAPS_API_KEY', 'AIzaSyBkrKTOMhz9FEBoLN-I6pSHtU7ocMhEqWo');

// Configurações de Otimização de Rota
define('EARTH_RADIUS_KM', 6371); // Raio da Terra em quilômetros (fallback sem API)
define('AVG_SPEED_KMH', 40);     // Velocidade média km/h (fallback sem API)

// Configurações de Timezone
date_default_timezone_set('America/Sao_Paulo');

// Modo de Debug
define('DEBUG_MODE', false);

/**
 * Função auxiliar para exibir mensagens de debug
 */
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

