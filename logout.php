<?php
/**
 * Logout - Encerra a sessão
 */

session_start();

// Limpa sessão
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Redireciona para login
header('Location: login.php');
exit;
