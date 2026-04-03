<?php
/**
 * Login do Motorista
 * Victor Transportes - Módulo Mobile
 */

session_start();
require_once 'config.php';
require_once 'Database.php';

// Se já está logado como motorista, redireciona
if (isset($_SESSION['motorista_id'])) {
    header('Location: motorista_app.php');
    exit;
}

$erro = '';
$usuario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha   = $_POST['senha'] ?? '';

    if (empty($usuario) || empty($senha)) {
        $erro = 'Preencha todos os campos';
    } else {
        try {
            $db = new Database();

            // Autentica na tabela de acesso do motorista
            $user = $db->queryOne(
                "SELECT login, pswd, name, active
                 FROM sec_users_motorista
                 WHERE login = ? AND active = 'Y'
                 LIMIT 1",
                [$usuario]
            );

            if (!$user) {
                $erro = 'Usuário não encontrado ou inativo';
            } elseif (md5($senha) !== $user['pswd'] && !password_verify($senha, $user['pswd'])) {
                $erro = 'Senha incorreta';
            } else {
                // Busca motorista ativo pelo login
                $motorista = $db->queryOne(
                    "SELECT id, nome FROM motorista WHERE usuario = ? AND situacao = 'a' LIMIT 1",
                    [$usuario]
                );

                if (!$motorista) {
                    $erro = 'Motorista inativo. Entre em contato com o administrador.';
                } else {
                    $_SESSION['motorista_id']   = $motorista['id'];
                    $_SESSION['motorista_nome'] = $motorista['nome'];
                    header('Location: motorista_app.php');
                    exit;
                }
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Motorista - Victor Transportes</title>
    <style>
        :root {
            --primary: <?php echo EMPRESA_COR_PRIMARIA; ?>;
            --primary-dark: <?php echo EMPRESA_COR_SECUNDARIA; ?>;
            --primary-light: <?php echo EMPRESA_COR_PRIMARIA; ?>;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(160deg, var(--primary) 0%, var(--primary-dark) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: #fff;
            border-radius: 20px;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            padding: 36px 30px 28px;
            text-align: center;
        }

        .logo {
            width: 72px;
            height: 72px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin: 0 auto 14px;
        }

        .card-header h1 { font-size: 1.4rem; font-weight: 700; }
        .card-header p  { opacity: 0.8; font-size: 0.9rem; margin-top: 4px; }

        .card-body { padding: 28px 28px 32px; }

        .error-box {
            background: #fef2f2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .form-group { margin-bottom: 18px; }

        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: #555;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .input-wrap { position: relative; }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.2s;
            -webkit-appearance: none;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(31,111,84,0.12);
        }

        .toggle-pwd {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: #9ca3af;
            padding: 4px;
            line-height: 1;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 6px;
            transition: background 0.2s, transform 0.1s;
            letter-spacing: 0.3px;
        }

        .btn-login:active { background: var(--primary-dark); transform: scale(0.98); }

        .footer { text-align: center; color: #bbb; font-size: 0.78rem; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <div class="logo">🚚</div>
            <h1>Victor Transportes</h1>
            <p>Área do Motorista</p>
        </div>
        <div class="card-body">
            <?php if ($erro): ?>
                <div class="error-box">⚠️ <?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="usuario">Usuário</label>
                    <input type="text" id="usuario" name="usuario"
                           value="<?= htmlspecialchars($usuario) ?>"
                           placeholder="Seu usuário" autocomplete="username"
                           autocapitalize="none" required autofocus>
                </div>

                <div class="form-group">
                    <label for="senha">Senha</label>
                    <div class="input-wrap">
                        <input type="password" id="senha" name="senha"
                               placeholder="Sua senha" autocomplete="current-password" required>
                        <button type="button" class="toggle-pwd" id="togglePwd" aria-label="Mostrar senha">👁️</button>
                    </div>
                </div>

                <button type="submit" class="btn-login">Entrar</button>
            </form>

            <p class="footer">© 2026 Victor Transportes</p>
        </div>
    </div>

    <script>
        document.getElementById('togglePwd').addEventListener('click', function () {
            const input = document.getElementById('senha');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            this.textContent = isPassword ? '🙈' : '👁️';
        });
    </script>
</body>
</html>
