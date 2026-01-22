<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userName = $_SESSION['user_name'] ?? 'Usuário';

// Verifica se é admin para mostrar certas opções
$isAdmin = !empty($_SESSION['user_is_admin']);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Victor Transportes - Sistema de Gestão</title>
    <style>
        :root {
            /* Nova paleta - mais elegante e menos saturada */
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

        /* Mobile Header */
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

        .menu-btn {
            width: 44px;
            height: 44px;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .menu-btn:active {
            background: rgba(255, 255, 255, 0.1);
        }

        .header-title {
            flex: 1;
            font-size: 1.1rem;
            font-weight: 600;
            margin-left: 8px;
        }

        /* Sidebar Overlay */
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
            transition: all 0.3s ease;
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Sidebar - Novo Design em Duas Colunas */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 240px;
            max-width: 85vw;
            height: 100vh;
            background: #ffffff;
            z-index: 2200;
            transform: translateX(-100%);
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.15);
        }

        .sidebar.open {
            transform: translateX(0);
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            background: var(--primary);
        }

        .sidebar-header-icon {
            width: 60px;
            min-width: 60px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.1);
        }

        .sidebar-header-icon span {
            color: white;
            font-size: 1.3rem;
        }

        .sidebar-header-title {
            flex: 1;
            padding: 0 16px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
        }

        .nav-item {
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.2s;
            border-bottom: 1px solid #f0f0f0;
        }

        .nav-item .icon-col {
            width: 60px;
            min-width: 60px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-right: 1px solid #e9ecef;
        }

        .nav-item .icon-col span {
            font-size: 1.3rem;
            color: #6c757d;
        }

        .nav-item .label-col {
            flex: 1;
            padding: 0 16px;
            font-size: 0.95rem;
            color: #495057;
            font-weight: 500;
        }

        .nav-item:hover .icon-col {
            background: var(--primary-bg);
        }

        .nav-item:hover .label-col {
            color: var(--primary);
        }

        .nav-item.active .icon-col {
            background: var(--primary);
        }

        .nav-item.active .icon-col span {
            color: white;
        }

        .nav-item.active .label-col {
            background: var(--primary);
            color: white;
        }

        .sidebar-footer {
            display: flex;
            align-items: center;
            border-top: 1px solid #e9ecef;
        }

        .sidebar-footer .icon-col {
            width: 60px;
            min-width: 60px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-right: 1px solid #e9ecef;
        }

        .sidebar-footer .icon-col span {
            font-size: 1.3rem;
            color: #6c757d;
        }

        .sidebar-footer .label-col {
            flex: 1;
            padding: 8px 16px;
            font-size: 0.75rem;
            color: #868e96;
        }

        /* Main Content */
        .main-content {
            padding: 76px 20px 20px 20px;
            max-width: 1200px;
            margin: 0 auto;
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
    </style>
</head>

<body>
    <!-- Mobile Header -->
    <header class="mobile-header">
        <button class="menu-btn" onclick="toggleMenu()">☰</button>
        <span class="header-title">🏠 Início</span>
    </header>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="overlay" onclick="closeMenu()"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-header-icon">
                <span>☰</span>
            </div>
            <div class="sidebar-header-title">Menu</div>
        </div>
        <div class="sidebar-nav">
            <a href="index.php" class="nav-item active">
                <div class="icon-col"><span>🏠</span></div>
                <div class="label-col">Início</div>
            </a>
            <a href="gerarrota.php" class="nav-item">
                <div class="icon-col"><span>🗺️</span></div>
                <div class="label-col">Gerar Rota</div>
            </a>
            <a href="validafornecedor.php" class="nav-item">
                <div class="icon-col"><span>📦</span></div>
                <div class="label-col">Validar Fornecedor</div>
            </a>
            <?php if ($isAdmin): ?>
                <a href="sobre.php" class="nav-item">
                    <div class="icon-col"><span>ℹ️</span></div>
                    <div class="label-col">Sobre</div>
                </a>
            <?php endif; ?>
            <a href="logout.php" class="nav-item">
                <div class="icon-col"><span>🚪</span></div>
                <div class="label-col">Sair</div>
            </a>
        </div>
        <div class="sidebar-footer">
            <div class="icon-col"><span>👤</span></div>
            <div class="label-col">
                <?php echo htmlspecialchars($userName); ?><br>
                © 2026 Victor Transportes
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
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