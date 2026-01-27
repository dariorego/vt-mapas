<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userName = $_SESSION['user_name'] ?? 'Usuário';
$isAdmin = !empty($_SESSION['user_is_admin']);
$currentPage = 'index.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Victor Transportes - Sistema de Gestão</title>
    <style>
        :root {
            --primary: #1F6F54;
            --primary-light: #2F8F6B;
            --primary-bg: #E8F4EF;
            --secondary: #3B82F6;
            --success: #22C55E;
            --warning: #F59E0B;
            --danger: #EF4444;
            --bg: #F6F8F9;
            --card: #ffffff;
            --text: #1F2933;
            --text-muted: #6B7280;
            --border: #E5E7EB;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            -webkit-tap-highlight-color: transparent;
        }

        /* Main Content */
        .main-content {
            padding: 20px;
            width: 100%;
            min-height: 100vh;
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            border-radius: 16px;
            padding: 28px;
            margin-bottom: 28px;
            box-shadow: 0 4px 20px rgba(31, 111, 84, 0.15);
        }

        .welcome-card h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .welcome-card p {
            opacity: 0.95;
            font-size: 0.95rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .action-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            border: 1px solid #f0f0f0;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .card-icon {
            font-size: 3rem;
            margin-bottom: 16px;
            background: #f8f9fa;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: var(--primary);
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--secondary);
        }

        .card-desc {
            font-size: 0.9rem;
            color: #666;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 16px;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content page-with-sidebar">
        <div class="welcome-card">
            <h2>Olá, <?php echo htmlspecialchars($userName); ?>! 👋</h2>
            <p>Bem-vindo ao sistema de gestão logística. O que você deseja fazer hoje?</p>
        </div>

        <div class="dashboard-grid">
            <a href="gerarrota.php" class="action-card">
                <div class="card-icon">🗺️</div>
                <h3 class="card-title">Gerar Rota</h3>
                <p class="card-desc">Visualize entregas no mapa e otimize a sequência de visitas para economizar tempo e
                    combustível.</p>
            </a>

            <a href="validafornecedor.php" class="action-card">
                <div class="card-icon">📦</div>
                <h3 class="card-title">Validar Fornecedor</h3>
                <p class="card-desc">Controle o recebimento de pacotes, visualize pendências e atualize status em tempo
                    real.</p>
            </a>

            <?php if ($isAdmin): ?>
                <a href="sobre.php" class="action-card">
                    <div class="card-icon">ℹ️</div>
                    <h3 class="card-title">Sobre o Sistema</h3>
                    <p class="card-desc">Visualize informações técnicas, versão do banco de dados e detalhes do release.</p>
                </a>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>