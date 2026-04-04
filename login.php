<?php
/**
 * Página de Login
 */

require_once 'config.php';
require_once 'Database.php';

session_start();

// Se já está logado, redireciona
if (isset($_SESSION['user_id'])) {
    header('Location: app.php');
    exit;
}

$erro = '';
$login = '';

// Processa login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $erro = 'Preencha todos os campos';
    } else {
        try {
            $db = new Database();

            $sql = "SELECT login, pswd, name, email, active, priv_admin, picture, novo_sistema 
                    FROM sec_users 
                    WHERE login = :login 
                    LIMIT 1";

            $users = $db->query($sql, [':login' => $login]);

            if (empty($users)) {
                $erro = 'Usuário não encontrado';
            } else {
                $user = $users[0];

                if (!$user['novo_sistema']) {
                    $erro = 'Sem permissão para este sistema';
                } elseif (!$user['active']) {
                    $erro = 'Usuário inativo';
                } else {
                    // Verifica senha (MD5 para sistemas legados)
                    $passwordHash = md5($password);
                    $senhaValida = ($user['pswd'] === $passwordHash) || password_verify($password, $user['pswd']);

                    if (!$senhaValida) {
                        $erro = 'Senha incorreta';
                    } else {
                        // Login OK - cria sessão
                        $_SESSION['user_id'] = $user['login'];
                        $_SESSION['user_login'] = $user['login'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_picture'] = $user['picture'];
                        $_SESSION['user_is_admin'] = (bool) $user['priv_admin'];
                        $_SESSION['login_time'] = time();

                        header('Location: app.php');
                        exit;
                    }
                }
            }
        } catch (Exception $e) {
            $erro = 'Erro ao conectar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo EMPRESA_NOME; ?></title>
    <style>
        :root {
            --primary: <?php echo EMPRESA_COR_PRIMARIA; ?>;
            --primary-dark: <?php echo EMPRESA_COR_SECUNDARIA; ?>;
            --bg: #f4f7f6;
            --card: #ffffff;
            --text: #333;
            --error: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: var(--card);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .login-header .icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }

        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .login-header p {
            opacity: 0.8;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .login-form {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(31, 111, 80, 0.1);
        }

        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-wrapper input {
            padding-right: 45px;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            transition: opacity 0.3s;
            opacity: 0.6;
            color: var(--text);
        }

        .toggle-password:hover {
            opacity: 1;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(31, 111, 80, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-msg {
            background: #fff5f5;
            color: var(--error);
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            border-left: 4px solid var(--error);
        }

        .footer-text {
            text-align: center;
            color: #999;
            font-size: 0.8rem;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <div class="icon">🚚</div>
            <h1><?php echo EMPRESA_NOME; ?></h1>
            <p>Sistema de Gestão Logística</p>
        </div>

        <form class="login-form" method="POST" action="">
            <?php if ($erro): ?>
                <div class="error-msg">⚠️
                    <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="login">Usuário</label>
                <input type="text" id="login" name="login" value="<?php echo htmlspecialchars($login); ?>"
                    placeholder="Digite seu usuário" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="Digite sua senha" required>
                    <button type="button" class="toggle-password" id="togglePassword" aria-label="Mostrar senha" title="Mostrar senha">
                        👁️
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">Entrar</button>

            <p class="footer-text">© 2026 <?php echo EMPRESA_NOME; ?></p>
        </form>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            this.textContent = type === 'password' ? '👁️' : '🙈';
        });
    </script>
</body>

</html>