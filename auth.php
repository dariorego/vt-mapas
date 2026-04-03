<?php
/**
 * Sistema de Autenticação
 * 
 * Funções para login, logout e verificação de sessão.
 */

session_start();

require_once 'config.php';
require_once 'Database.php';

/**
 * Verifica se o usuário está autenticado
 */
function isAuthenticated(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Redireciona para login se não autenticado
 */
function requireAuth(): void
{
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Retorna dados do usuário logado
 */
function getCurrentUser(): ?array
{
    if (!isAuthenticated()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'login' => $_SESSION['user_login'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'picture' => $_SESSION['user_picture'] ?? null,
        'is_admin' => $_SESSION['user_is_admin'] ?? false
    ];
}

/**
 * Tenta autenticar o usuário
 */
function authenticate(string $login, string $password): array
{
    $db = new Database();

    // Busca usuário pelo login
    $sql = "SELECT login, pswd, name, email, active, activation_code, priv_admin, mfa, picture, novo_sistema 
            FROM sec_users 
            WHERE login = :login 
            LIMIT 1";

    $users = $db->query($sql, [':login' => $login]);

    if (empty($users)) {
        return ['success' => false, 'error' => 'Usuário não encontrado'];
    }

    $user = $users[0];

    // Verifica se tem acesso ao novo sistema
    if (!$user['novo_sistema']) {
        return ['success' => false, 'error' => 'Usuário sem permissão para este sistema'];
    }

    // Verifica se está ativo
    if (!$user['active']) {
        return ['success' => false, 'error' => 'Usuário inativo'];
    }

    // Verifica senha (assumindo MD5 - comum em sistemas legados)
    // Se a senha estiver em outro formato, ajustar aqui
    $passwordHash = md5($password);

    if ($user['pswd'] !== $passwordHash) {
        // Tenta também com password_verify para senhas mais seguras
        if (!password_verify($password, $user['pswd'])) {
            return ['success' => false, 'error' => 'Senha incorreta'];
        }
    }

    // Login bem sucedido - cria sessão
    $_SESSION['user_id'] = $user['login'];
    $_SESSION['user_login'] = $user['login'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_picture'] = $user['picture'];
    $_SESSION['user_is_admin'] = (bool) $user['priv_admin'];
    $_SESSION['login_time'] = time();

    return ['success' => true, 'user' => getCurrentUser()];
}

/**
 * Encerra a sessão do usuário
 */
function logout(): void
{
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
}
