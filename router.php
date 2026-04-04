<?php
/**
 * Router para desenvolvimento local (php -S localhost:8000 router.php)
 * Emula as rotas do .htaccess
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');

// Mapeia rotas limpas para os arquivos PHP
$routes = [
    '/minhaentrega' => 'cliente_tracking.php',
    '/motorista'    => 'motorista_login.php',
    '/admin'        => 'login.php',
    '/usuarios'     => 'usuarios.php',
    '/cidades'      => 'cidade.php',
    ''              => 'index.php',
    '/'             => 'index.php',
];

if (isset($routes[$uri])) {
    // Inclui o arquivo correspondente mantendo o contexto
    $_SERVER['SCRIPT_NAME'] = '/' . $routes[$uri];
    require __DIR__ . '/' . $routes[$uri];
    return true;
}

// Serve arquivos estáticos normalmente (css, js, imagens, etc.)
if (is_file(__DIR__ . $uri)) {
    return false;
}

// 404 fallback
http_response_code(404);
echo '<h1>404 - Página não encontrada</h1>';
