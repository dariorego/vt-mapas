<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
// Só admin pode acessar
if (empty($_SESSION['user_is_admin'])) {
    header('Location: index.php');
    exit;
}

require_once 'config.php';
require_once 'Database.php';

// Informações do sistema
$releaseVersion = '1.0.0';
$releaseDate = '2026-01-15';
$appName = 'LogApp - Sistema de Gestão Logística';

// Busca info do banco
$dbInfo = [];
try {
    $db = new Database();
    $result = $db->query("SELECT DATABASE() as db_name, VERSION() as db_version");
    $dbInfo = $result[0] ?? [];
} catch (Exception $e) {
    $dbInfo = ['db_name' => 'Erro ao conectar', 'db_version' => 'N/D'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sobre - Victor Transportes</title>
    <style>
        :root {
            --primary: #1F6F50;
            --primary-dark: #16523c;
            --secondary: #2c3e50;
            --bg: #f4f7f6;
            --card: #ffffff;
            --text: #333;
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
            -webkit-tap-highlight-color: transparent;
        }

        .mobile-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 56px;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            padding: 0 12px;
            z-index: 2000;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        /* ... */

        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2100;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            max-width: 85vw;
            height: 100vh;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            z-index: 2200;
            transform: translateX(-100%);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
        }

        .sidebar.open {
            transform: translateX(0);
        }

        .sidebar-header {
            padding: 20px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .sidebar-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.8rem;
            margin-top: 4px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 12px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            color: white;
            text-decoration: none;
            font-size: 1rem;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }

        .nav-item:active,
        .nav-item.active {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: white;
        }

        .nav-item .icon {
            font-size: 1.3rem;
            width: 28px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 12px 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.75rem;
            text-align: center;
        }

        .main {
            padding: 68px 16px 20px 16px;
            max-width: 600px;
            margin: 0 auto;
        }

        .about-card {
            background: var(--card);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 16px;
        }

        .about-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .about-header .logo {
            font-size: 4rem;
            margin-bottom: 10px;
        }

        .about-header h1 {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .about-header .version {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-top: 8px;
        }

        .info-section {
            padding: 20px;
        }

        .info-section h3 {
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #999;
            margin-bottom: 12px;
            letter-spacing: 1px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-item .label {
            color: #666;
            font-size: 0.9rem;
        }

        .info-item .value {
            font-weight: 600;
            color: var(--secondary);
            font-size: 0.9rem;
            text-align: right;
        }

        .info-item .value.highlight {
            color: var(--primary);
        }

        .copyright {
            text-align: center;
            padding: 20px;
            color: #999;
            font-size: 0.8rem;
        }

        .tech-badge {
            display: inline-block;
            background: #e9ecef;
            color: var(--secondary);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            margin: 2px;
        }
    </style>
</head>

<body>
    <header class="mobile-header">
        <button class="menu-btn" onclick="toggleMenu()">☰</button>
        <span class="header-title">ℹ️ Sobre</span>
    </header>

    <div class="sidebar-overlay" id="overlay" onclick="closeMenu()"></div>
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>🚚 Victor Transportes</h2>
            <p>Sistema de Gestão</p>
        </div>
        <div class="sidebar-nav">
            <a href="index.php" class="nav-item"><span class="icon">🏠</span> Início</a>
            <a href="gerarrota.php" class="nav-item"><span class="icon">🗺️</span> Gerar Rota</a>
            <a href="validafornecedor.php" class="nav-item"><span class="icon">📦</span> Validar Fornecedor</a>
            <a href="sobre.php" class="nav-item active"><span class="icon">ℹ️</span> Sobre</a>
            <a href="logout.php" class="nav-item"><span class="icon">🚪</span> Sair</a>
        </div>
        <div class="sidebar-footer">© 2026 Victor Transportes</div>
    </nav>

    <main class="main">
        <div class="about-card">
            <div class="about-header">
                <div class="logo">🚚</div>
                <h1>
                    <?php echo $appName; ?>
                </h1>
                <p>Victor Transportes</p>
                <span class="version">v
                    <?php echo $releaseVersion; ?>
                </span>
            </div>

            <div class="info-section">
                <h3>📋 Informações do Sistema</h3>
                <div class="info-item">
                    <span class="label">Versão (Release)</span>
                    <span class="value highlight">
                        <?php echo $releaseVersion; ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="label">Data de Lançamento</span>
                    <span class="value">
                        <?php echo date('d/m/Y', strtotime($releaseDate)); ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="label">Usuário Logado</span>
                    <span class="value">
                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'N/D'); ?>
                    </span>
                </div>
            </div>

            <div class="info-section" style="border-top: 1px solid #eee;">
                <h3>🗄️ Banco de Dados</h3>
                <div class="info-item">
                    <span class="label">Nome do Banco</span>
                    <span class="value highlight">
                        <?php echo htmlspecialchars($dbInfo['db_name'] ?? 'N/D'); ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="label">Versão MySQL</span>
                    <span class="value">
                        <?php echo htmlspecialchars($dbInfo['db_version'] ?? 'N/D'); ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="label">Host</span>
                    <span class="value">
                        <?php echo defined('DB_HOST') ? DB_HOST : 'N/D'; ?>
                    </span>
                </div>
            </div>

            <div class="info-section" style="border-top: 1px solid #eee;">
                <h3>🛠️ Tecnologias</h3>
                <div style="padding: 10px 0;">
                    <span class="tech-badge">PHP
                        <?php echo phpversion(); ?>
                    </span>
                    <span class="tech-badge">MySQL</span>
                    <span class="tech-badge">Leaflet.js</span>
                    <span class="tech-badge">OpenStreetMap</span>
                </div>
            </div>

            <div class="copyright">
                <p>© 2026 Victor Transportes</p>
                <p>Todos os direitos reservados</p>
            </div>
        </div>
    </main>

    <script>
        function toggleMenu() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('overlay').classList.toggle('active');
        }
        function closeMenu() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('overlay').classList.remove('active');
        }
    </script>
</body>

</html>