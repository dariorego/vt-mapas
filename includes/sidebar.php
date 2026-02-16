<?php
/**
 * Componente Sidebar Unificado
 * Victor Transportes - Sistema de Gestão
 * 
 * USO: <?php include 'includes/sidebar.php'; ?>
 * Requer: Variável $currentPage deve estar definida antes do include
 */

// Se está dentro do sistema de abas, não renderiza o sidebar
if (isset($_GET['notabs']) && $_GET['notabs'] === '1') {
    // Apenas adiciona estilo para página sem sidebar
    echo '<style>
        .page-with-sidebar { 
            margin-left: 0 !important; 
            width: 100% !important;
            padding-top: 0 !important;
        }
    </style>';
    return;
}

// Detecta a página atual se não foi definida
if (!isset($currentPage)) {
    $currentPage = basename($_SERVER['PHP_SELF']);
}

// Verifica se é admin para mostrar certas opções
$isAdmin = !empty($_SESSION['user_is_admin']);
$userName = $_SESSION['user_name'] ?? 'Usuário';

// Define títulos das páginas
$pageTitles = [
    'index.php' => '🏠 Início',
    'gerarrota.php' => '🗺️ Gerar Rota',
    'validafornecedor.php' => '📦 Validar Fornecedor',
    'optimize_routes.php' => '🛣️ Otimizar Rotas',
    'motorista.php' => '🚗 Motoristas',
    'cliente.php' => '👥 Clientes',
    'viagem.php' => '🚐 Relação de Viagem',
    'pedido.php' => '📦 Pedidos',
    'ranking.php' => '🏆 Ranking',
    'configuracoes.php' => '⚙️ Configurações',
    'sobre.php' => 'ℹ️ Sobre'
];
$pageTitle = $pageTitles[$currentPage] ?? '🚚 Victor Transportes';

// Verifica se está em uma página de cadastros para manter submenu aberto
$isCadastrosPage = in_array($currentPage, ['motorista.php', 'cliente.php']);
// Verifica se está em uma página de viagens para manter submenu aberto
$isViagensPage = in_array($currentPage, ['viagem.php', 'pedido.php', 'gerarrota.php']);
// Verifica se está em uma página de fornecedor para manter submenu aberto
$isFornecedorPage = in_array($currentPage, ['validafornecedor.php']);
// Verifica se está em uma página de relatórios para manter submenu aberto
$isRelatoriosPage = in_array($currentPage, ['ranking.php']);
?>
<style>
    :root {
        --sidebar-width: 260px;
        --sidebar-collapsed-width: 72px;
        --sidebar-bg-start: #1F6F50;
        --sidebar-bg-end: #16523c;
        --sidebar-transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Mobile Header */
    .mobile-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 56px;
        background: var(--sidebar-bg-start);
        color: white;
        display: flex;
        align-items: center;
        padding: 0 12px;
        z-index: 2000;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .mobile-header .menu-btn {
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

    .mobile-header .menu-btn:active {
        background: rgba(255, 255, 255, 0.1);
    }

    .mobile-header .header-title {
        flex: 1;
        font-size: 1.1rem;
        font-weight: 600;
        margin-left: 8px;
    }

    /* Sidebar Overlay */
    .sidebar-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 2100;
    }

    .sidebar-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    /* Sidebar Base */
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: var(--sidebar-width);
        height: 100vh;
        background: linear-gradient(180deg, var(--sidebar-bg-start) 0%, var(--sidebar-bg-end) 100%);
        color: white;
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
        z-index: 2200;
        display: flex;
        flex-direction: column;
        transition: width var(--sidebar-transition);
        overflow: hidden;
    }

    /* Collapsed State */
    .sidebar.collapsed {
        width: var(--sidebar-collapsed-width);
    }

    /* Header */
    .sidebar-header {
        padding: 20px 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 80px;
    }

    .sidebar-logo {
        font-size: 1.8rem;
        flex-shrink: 0;
    }

    .sidebar-brand {
        overflow: hidden;
        white-space: nowrap;
        transition: opacity var(--sidebar-transition), width var(--sidebar-transition);
    }

    .sidebar.collapsed .sidebar-brand {
        opacity: 0;
        width: 0;
    }

    .sidebar-brand h1 {
        font-size: 1rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0;
    }

    .sidebar-brand p {
        font-size: 0.7rem;
        opacity: 0.7;
        margin-top: 2px;
    }

    /* Toggle Button */
    .sidebar-toggle {
        position: absolute;
        top: 28px;
        right: -14px;
        width: 28px;
        height: 28px;
        background: #fff;
        border: 2px solid var(--sidebar-bg-start);
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        z-index: 2201;
        transition: transform var(--sidebar-transition), background var(--sidebar-transition);
        padding: 0;
    }

    .sidebar-toggle:hover {
        background: var(--sidebar-bg-start);
    }

    .sidebar-toggle:hover .toggle-icon {
        stroke: #fff;
    }

    .toggle-icon {
        width: 14px;
        height: 14px;
        stroke: var(--sidebar-bg-start);
        stroke-width: 2.5;
        fill: none;
        transition: transform var(--sidebar-transition), stroke var(--sidebar-transition);
    }

    .sidebar.collapsed .toggle-icon {
        transform: rotate(180deg);
    }

    /* Navigation */
    .sidebar-nav {
        flex: 1;
        padding: 16px 0;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .nav-section-title {
        padding: 8px 20px;
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        opacity: 0.5;
        white-space: nowrap;
        transition: opacity var(--sidebar-transition);
    }

    .sidebar.collapsed .nav-section-title {
        opacity: 0;
    }

    /* Nav Links */
    .sidebar .nav-link {
        position: relative;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 20px;
        color: white;
        text-decoration: none;
        border-left: 3px solid transparent;
        font-size: 0.9rem;
        transition: all var(--sidebar-transition);
        overflow: hidden;
    }

    .sidebar .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
        border-left-color: rgba(255, 255, 255, 0.5);
    }

    .sidebar .nav-link.active {
        background-color: rgba(255, 255, 255, 0.15);
        border-left-color: #fff;
    }

    .sidebar .nav-link .icon {
        font-size: 1.3rem;
        width: 28px;
        text-align: center;
        flex-shrink: 0;
    }

    .sidebar .nav-link .label {
        white-space: nowrap;
        transition: opacity var(--sidebar-transition);
    }

    .sidebar.collapsed .nav-link .label {
        opacity: 0;
    }

    /* Tooltips */
    .sidebar .nav-link .tooltip {
        position: absolute;
        left: calc(var(--sidebar-collapsed-width) + 10px);
        background: #1a1a2e;
        color: #fff;
        padding: 8px 14px;
        border-radius: 6px;
        font-size: 0.8rem;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transform: translateX(-10px);
        transition: all 0.2s ease;
        pointer-events: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        z-index: 2202;
    }

    .sidebar .nav-link .tooltip::before {
        content: '';
        position: absolute;
        left: -6px;
        top: 50%;
        transform: translateY(-50%);
        border: 6px solid transparent;
        border-right-color: #1a1a2e;
    }

    .sidebar.collapsed .nav-link:hover .tooltip {
        opacity: 1;
        visibility: visible;
        transform: translateX(0);
    }

    /* Submenu Styles */
    .nav-submenu-toggle {
        position: relative;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px 20px;
        color: white;
        text-decoration: none;
        border-left: 3px solid transparent;
        font-size: 0.9rem;
        transition: all var(--sidebar-transition);
        overflow: hidden;
        cursor: pointer;
        background: none;
        border: none;
        border-left: 3px solid transparent;
        width: 100%;
        text-align: left;
    }

    .nav-submenu-toggle:hover {
        background-color: rgba(255, 255, 255, 0.1);
        border-left-color: rgba(255, 255, 255, 0.5);
    }

    .nav-submenu-toggle.active {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .nav-submenu-toggle .icon {
        font-size: 1.3rem;
        width: 28px;
        text-align: center;
        flex-shrink: 0;
    }

    .nav-submenu-toggle .label {
        flex: 1;
        white-space: nowrap;
        transition: opacity var(--sidebar-transition);
    }

    .sidebar.collapsed .nav-submenu-toggle .label {
        opacity: 0;
    }

    .nav-submenu-toggle .chevron {
        width: 16px;
        height: 16px;
        transition: transform 0.2s ease;
        opacity: 0.7;
    }

    .sidebar.collapsed .nav-submenu-toggle .chevron {
        display: none;
    }

    .nav-submenu-toggle.open .chevron {
        transform: rotate(90deg);
    }

    .nav-submenu {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
        background: rgba(0, 0, 0, 0.1);
    }

    .nav-submenu.open {
        max-height: 200px;
    }

    .nav-submenu .nav-link {
        padding-left: 48px;
        font-size: 0.85rem;
    }

    .nav-submenu .nav-link .icon {
        font-size: 1.1rem;
        width: 24px;
    }

    .sidebar.collapsed .nav-submenu {
        display: none;
    }

    /* Tooltip for submenu when collapsed */
    .nav-submenu-toggle .tooltip {
        position: absolute;
        left: calc(var(--sidebar-collapsed-width) + 10px);
        background: #1a1a2e;
        color: #fff;
        padding: 8px 14px;
        border-radius: 6px;
        font-size: 0.8rem;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transform: translateX(-10px);
        transition: all 0.2s ease;
        pointer-events: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        z-index: 2202;
    }

    .nav-submenu-toggle .tooltip::before {
        content: '';
        position: absolute;
        left: -6px;
        top: 50%;
        transform: translateY(-50%);
        border: 6px solid transparent;
        border-right-color: #1a1a2e;
    }

    .sidebar.collapsed .nav-submenu-toggle:hover .tooltip {
        opacity: 1;
        visibility: visible;
        transform: translateX(0);
    }

    /* User info */
    .sidebar-user {
        padding: 12px 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all var(--sidebar-transition);
    }

    .sidebar-user .user-avatar {
        width: 32px;
        height: 32px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .sidebar-user .user-info {
        overflow: hidden;
        white-space: nowrap;
        transition: opacity var(--sidebar-transition);
    }

    .sidebar.collapsed .sidebar-user .user-info {
        opacity: 0;
    }

    .sidebar-user .user-name {
        font-size: 0.85rem;
        font-weight: 600;
    }

    .sidebar-user .user-role {
        font-size: 0.7rem;
        opacity: 0.7;
    }

    /* Footer */
    .sidebar-footer {
        padding: 14px 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 0.7rem;
        opacity: 0.5;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        transition: opacity var(--sidebar-transition);
    }

    .sidebar.collapsed .sidebar-footer {
        opacity: 0;
    }

    /* Page Content Adjustment */
    .page-with-sidebar {
        margin-left: var(--sidebar-width);
        transition: margin-left var(--sidebar-transition);
        padding-top: 0;
        min-height: 100vh;
        width: calc(100vw - var(--sidebar-width));
    }

    body.sidebar-collapsed .page-with-sidebar {
        margin-left: var(--sidebar-collapsed-width);
        width: calc(100vw - var(--sidebar-collapsed-width));
    }

    /* Desktop - hide mobile header, show sidebar */
    @media (min-width: 769px) {
        .mobile-header {
            display: none;
        }

        .page-with-sidebar {
            padding-top: 0;
        }
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            width: var(--sidebar-width) !important;
        }

        .sidebar.mobile-open {
            transform: translateX(0);
        }

        .sidebar-toggle {
            display: none;
        }

        .sidebar.collapsed .sidebar-brand,
        .sidebar.collapsed .nav-link .label,
        .sidebar.collapsed .nav-section-title,
        .sidebar.collapsed .sidebar-footer,
        .sidebar.collapsed .sidebar-user .user-info {
            opacity: 1;
        }

        .page-with-sidebar {
            margin-left: 0 !important;
            padding-top: 68px;
            width: 100% !important;
            min-height: calc(100vh - 68px);
        }
    }
</style>

<!-- Mobile Header -->
<header class="mobile-header">
    <button class="menu-btn" id="mobileMenuBtn">☰</button>
    <span class="header-title"><?php echo $pageTitle; ?></span>
</header>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <!-- Toggle Button (Desktop) -->
    <button class="sidebar-toggle" id="sidebarToggle" title="Recolher/Expandir menu">
        <svg class="toggle-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
            stroke-linecap="round" stroke-linejoin="round">
            <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
    </button>

    <div class="sidebar-header">
        <span class="sidebar-logo">🚚</span>
        <div class="sidebar-brand">
            <h1>Victor Transportes</h1>
            <p>Sistema de Gestão</p>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Menu</div>

        <a href="index.php" class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
            <span class="icon">🏠</span>
            <span class="label">Início</span>
            <span class="tooltip">Início</span>
        </a>

        <!-- Cadastros Submenu -->
        <button class="nav-submenu-toggle <?php echo $isCadastrosPage ? 'open active' : ''; ?>" id="cadastrosToggle">
            <span class="icon">📋</span>
            <span class="label">Cadastros</span>
            <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
            <span class="tooltip">Cadastros</span>
        </button>
        <div class="nav-submenu <?php echo $isCadastrosPage ? 'open' : ''; ?>" id="cadastrosSubmenu">
            <a href="motorista.php" class="nav-link <?php echo $currentPage === 'motorista.php' ? 'active' : ''; ?>">
                <span class="icon">🚗</span>
                <span class="label">Motoristas</span>
                <span class="tooltip">Motoristas</span>
            </a>
            <a href="cliente.php" class="nav-link <?php echo $currentPage === 'cliente.php' ? 'active' : ''; ?>">
                <span class="icon">👥</span>
                <span class="label">Clientes</span>
                <span class="tooltip">Clientes</span>
            </a>
        </div>

        <!-- Viagens Submenu -->
        <button class="nav-submenu-toggle <?php echo $isViagensPage ? 'open active' : ''; ?>" id="viagensToggle">
            <span class="icon">🚐</span>
            <span class="label">Viagens</span>
            <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
            <span class="tooltip">Viagens</span>
        </button>
        <div class="nav-submenu <?php echo $isViagensPage ? 'open' : ''; ?>" id="viagensSubmenu">
            <a href="viagem.php" class="nav-link <?php echo $currentPage === 'viagem.php' ? 'active' : ''; ?>">
                <span class="icon">📋</span>
                <span class="label">Relação de Viagem</span>
                <span class="tooltip">Relação de Viagem</span>
            </a>
            <a href="pedido.php" class="nav-link <?php echo $currentPage === 'pedido.php' ? 'active' : ''; ?>">
                <span class="icon">📦</span>
                <span class="label">Pedidos</span>
                <span class="tooltip">Pedidos</span>
            </a>
            <a href="gerarrota.php" class="nav-link <?php echo $currentPage === 'gerarrota.php' ? 'active' : ''; ?>">
                <span class="icon">🗺️</span>
                <span class="label">Gerar Rota</span>
                <span class="tooltip">Gerar Rota</span>
            </a>
        </div>

        <!-- Fornecedor Submenu -->
        <button class="nav-submenu-toggle <?php echo $isFornecedorPage ? 'open active' : ''; ?>" id="fornecedorToggle">
            <span class="icon">🏭</span>
            <span class="label">Fornecedor</span>
            <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
            <span class="tooltip">Fornecedor</span>
        </button>
        <div class="nav-submenu <?php echo $isFornecedorPage ? 'open' : ''; ?>" id="fornecedorSubmenu">
            <a href="validafornecedor.php" class="nav-link <?php echo $currentPage === 'validafornecedor.php' ? 'active' : ''; ?>">
                <span class="icon">📦</span>
                <span class="label">Validar Fornecedor</span>
                <span class="tooltip">Validar Fornecedor</span>
            </a>
        </div>

        <!-- Relatórios Submenu -->
        <button class="nav-submenu-toggle <?php echo $isRelatoriosPage ? 'open active' : ''; ?>" id="relatoriosToggle">
            <span class="icon">📊</span>
            <span class="label">Relatórios</span>
            <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
            <span class="tooltip">Relatórios</span>
        </button>
        <div class="nav-submenu <?php echo $isRelatoriosPage ? 'open' : ''; ?>" id="relatoriosSubmenu">
            <a href="ranking.php" class="nav-link <?php echo $currentPage === 'ranking.php' ? 'active' : ''; ?>">
                <span class="icon">🏆</span>
                <span class="label">Ranking</span>
                <span class="tooltip">Ranking</span>
            </a>
        </div>

        <a href="configuracoes.php" class="nav-link <?php echo $currentPage === 'configuracoes.php' ? 'active' : ''; ?>">
            <span class="icon">⚙️</span>
            <span class="label">Configurações</span>
            <span class="tooltip">Configurações</span>
        </a>

        <?php if ($isAdmin): ?>
            <a href="sobre.php" class="nav-link <?php echo $currentPage === 'sobre.php' ? 'active' : ''; ?>">
                <span class="icon">ℹ️</span>
                <span class="label">Sobre</span>
                <span class="tooltip">Sobre</span>
            </a>
        <?php endif; ?>

        <div class="nav-section-title">Conta</div>

        <a href="logout.php" class="nav-link">
            <span class="icon">🚪</span>
            <span class="label">Sair</span>
            <span class="tooltip">Sair</span>
        </a>
    </nav>

    <div class="sidebar-user">
        <div class="user-avatar">👤</div>
        <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars($userName); ?></div>
            <div class="user-role"><?php echo $isAdmin ? 'Administrador' : 'Usuário'; ?></div>
        </div>
    </div>

    <div class="sidebar-footer">
        © 2026 Victor Transportes
    </div>
</aside>

<script src="includes/sidebar.js"></script>