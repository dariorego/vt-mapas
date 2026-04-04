<?php
/**
 * Victor Transportes - Container Principal com Abas
 * Sistema de Gestão
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';
$isAdmin = !empty($_SESSION['user_is_admin']);
$userName = $_SESSION['user_name'] ?? 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo EMPRESA_NOME; ?> - Sistema de Gestão</title>
    <style>
        :root {
            --primary: <?php echo EMPRESA_COR_PRIMARIA; ?>;
            --primary-dark: <?php echo EMPRESA_COR_SECUNDARIA; ?>;
            --primary-light: <?php echo EMPRESA_COR_PRIMARIA; ?>;
            --bg: #f4f7f6;
            --card: #ffffff;
            --text: #1F2933;
            --text-muted: #6B7280;
            --border: #E5E7EB;
            --sidebar-width: 240px;
            --sidebar-collapsed: 60px;
            --tab-height: 38px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            overflow: hidden;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            display: flex;
            flex-direction: column;
            transition: width 0.3s ease;
            flex-shrink: 0;
            position: relative;
            z-index: 100;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed);
        }

        .sidebar-header {
            padding: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 60px;
        }

        .sidebar-logo {
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .sidebar-brand {
            overflow: hidden;
            white-space: nowrap;
            transition: opacity 0.3s;
        }

        .sidebar.collapsed .sidebar-brand {
            opacity: 0;
            width: 0;
        }

        .sidebar-brand h1 {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .sidebar-brand p {
            font-size: 0.65rem;
            opacity: 0.7;
        }

        /* Toggle */
        .sidebar-toggle {
            position: absolute;
            top: 20px;
            right: -12px;
            width: 24px;
            height: 24px;
            background: white;
            border: 2px solid var(--primary);
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            z-index: 101;
            transition: all 0.2s;
        }

        .sidebar-toggle:hover {
            background: var(--primary);
        }

        .sidebar-toggle:hover svg {
            stroke: white;
        }

        .sidebar-toggle svg {
            width: 12px;
            height: 12px;
            stroke: var(--primary);
            stroke-width: 2.5;
            fill: none;
            transition: transform 0.3s, stroke 0.2s;
        }

        .sidebar.collapsed .sidebar-toggle svg {
            transform: rotate(180deg);
        }

        /* Navigation */
        .sidebar-nav {
            flex: 1;
            padding: 12px 0;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar-nav::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
        }

        .nav-section {
            padding: 8px 16px;
            font-size: 0.6rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.5;
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar.collapsed .nav-section {
            opacity: 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            color: white;
            text-decoration: none;
            cursor: pointer;
            border-left: 3px solid transparent;
            transition: all 0.2s;
            position: relative;
            font-size: 0.88rem;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: rgba(255, 255, 255, 0.5);
        }

        .nav-item.tab-open {
            background: rgba(255, 255, 255, 0.08);
        }

        .nav-item.tab-active {
            background: rgba(255, 255, 255, 0.15);
            border-left-color: #fff;
        }

        .nav-item .icon {
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
        }

        .nav-item .label {
            white-space: nowrap;
            overflow: hidden;
            transition: opacity 0.3s;
        }

        .sidebar.collapsed .nav-item .label {
            opacity: 0;
        }

        /* Tooltip */
        .nav-item .tooltip {
            position: absolute;
            left: calc(var(--sidebar-collapsed) + 8px);
            background: #1a1a2e;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transform: translateX(-5px);
            transition: all 0.2s;
            z-index: 200;
            pointer-events: none;
        }

        .nav-item .tooltip::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 50%;
            transform: translateY(-50%);
            border: 6px solid transparent;
            border-right-color: #1a1a2e;
        }

        .sidebar.collapsed .nav-item:hover .tooltip {
            opacity: 1;
            visibility: visible;
            transform: translateX(0);
        }

        /* Submenu */
        .nav-submenu-toggle {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            color: white;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            border-left: 3px solid transparent;
            transition: all 0.2s;
            position: relative;
            font-size: 0.88rem;
        }

        .nav-submenu-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-submenu-toggle .icon {
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
        }

        .nav-submenu-toggle .label {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            transition: opacity 0.3s;
        }

        .sidebar.collapsed .nav-submenu-toggle .label,
        .sidebar.collapsed .nav-submenu-toggle .chevron {
            opacity: 0;
        }

        .nav-submenu-toggle .chevron {
            width: 14px;
            height: 14px;
            stroke: white;
            fill: none;
            transition: transform 0.2s;
        }

        .nav-submenu-toggle.open .chevron {
            transform: rotate(90deg);
        }

        .nav-submenu-toggle .tooltip {
            position: absolute;
            left: calc(var(--sidebar-collapsed) + 8px);
            background: #1a1a2e;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transform: translateX(-5px);
            transition: all 0.2s;
            z-index: 200;
            pointer-events: none;
        }

        .sidebar.collapsed .nav-submenu-toggle:hover .tooltip {
            opacity: 1;
            visibility: visible;
            transform: translateX(0);
        }

        .nav-submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: rgba(0, 0, 0, 0.1);
        }

        .nav-submenu.open {
            max-height: 300px;
        }

        .nav-submenu .nav-item {
            padding-left: 44px;
            font-size: 0.82rem;
        }

        .sidebar.collapsed .nav-submenu {
            display: none;
        }

        /* User */
        .sidebar-user {
            padding: 12px 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 28px;
            height: 28px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .user-info {
            overflow: hidden;
            transition: opacity 0.3s;
        }

        .sidebar.collapsed .user-info {
            opacity: 0;
        }

        .user-name {
            font-size: 0.8rem;
            font-weight: 600;
        }

        .user-role {
            font-size: 0.65rem;
            opacity: 0.7;
        }

        .sidebar-footer {
            padding: 10px 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.65rem;
            opacity: 0.5;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
        }

        .sidebar.collapsed .sidebar-footer {
            opacity: 0;
        }

        /* ===== MAIN AREA ===== */
        .main-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-width: 0;
        }

        /* ===== TABS BAR ===== */
        .tabs-bar {
            height: var(--tab-height);
            min-height: var(--tab-height);
            background: #dee2e6;
            display: flex;
            align-items: flex-end;
            padding: 0 4px;
            gap: 1px;
            border-bottom: 1px solid #c9cdd2;
            overflow-x: auto;
            overflow-y: hidden;
            flex-shrink: 0;
            scrollbar-width: none;
        }

        .tabs-bar::-webkit-scrollbar {
            height: 0;
        }

        .tab {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 10px 7px 12px;
            background: #c8cdd2;
            border-radius: 8px 8px 0 0;
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--text-muted);
            cursor: pointer;
            white-space: nowrap;
            max-width: 190px;
            min-width: 100px;
            transition: all 0.15s;
            border: 1px solid #b8bdc3;
            border-bottom: none;
            position: relative;
            user-select: none;
        }

        .tab:hover {
            background: #d5d9de;
        }

        .tab.active {
            background: white;
            color: var(--text);
            font-weight: 600;
            border-color: #c9cdd2;
            z-index: 1;
        }

        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: white;
        }

        .tab .tab-icon {
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .tab .tab-title {
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }

        .tab .tab-close {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            line-height: 1;
            color: #999;
            transition: all 0.15s;
            flex-shrink: 0;
            opacity: 0;
        }

        .tab:hover .tab-close,
        .tab.active .tab-close {
            opacity: 1;
        }

        .tab .tab-close:hover {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }

        /* ===== CONTENT AREA ===== */
        .content-area {
            flex: 1;
            background: white;
            position: relative;
            overflow: hidden;
        }

        .tab-content {
            position: absolute;
            inset: 0;
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .tab-content iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Empty State */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-muted);
            text-align: center;
            padding: 40px;
        }

        .empty-state .es-icon {
            font-size: 4rem;
            margin-bottom: 16px;
            opacity: 0.4;
        }

        .empty-state h2 {
            font-size: 1.2rem;
            margin-bottom: 8px;
            color: var(--text);
        }

        .empty-state p {
            font-size: 0.9rem;
        }

        /* ===== MOBILE ===== */
        .mobile-header {
            display: none;
            height: 48px;
            background: var(--primary);
            color: white;
            align-items: center;
            padding: 0 12px;
            gap: 12px;
            flex-shrink: 0;
        }

        .mobile-header button {
            background: none;
            border: none;
            color: white;
            font-size: 1.3rem;
            cursor: pointer;
            padding: 8px;
        }

        .mobile-header span {
            font-weight: 600;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 99;
        }

        .sidebar-overlay.active {
            display: block;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                z-index: 1000;
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
            .sidebar.collapsed .nav-item .label,
            .sidebar.collapsed .nav-section,
            .sidebar.collapsed .sidebar-footer,
            .sidebar.collapsed .user-info,
            .sidebar.collapsed .nav-submenu-toggle .label,
            .sidebar.collapsed .nav-submenu-toggle .chevron {
                opacity: 1;
            }

            .mobile-header {
                display: flex !important;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeMobileSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <button class="sidebar-toggle" id="sidebarToggle" title="Recolher/Expandir">
            <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </button>

        <div class="sidebar-header">
            <span class="sidebar-logo">🚚</span>
            <div class="sidebar-brand">
                <h1><?php echo EMPRESA_NOME; ?></h1>
                <p>Sistema de Gestão</p>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">Menu</div>

            <div class="nav-item" data-page="dashboard.php" data-title="Início" data-icon="🏠">
                <span class="icon">🏠</span>
                <span class="label">Início</span>
                <span class="tooltip">Início</span>
            </div>

            <!-- Cadastros Submenu -->
            <button class="nav-submenu-toggle" data-submenu="cadastrosSubmenu">
                <span class="icon">📋</span>
                <span class="label">Cadastros</span>
                <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
                <span class="tooltip">Cadastros</span>
            </button>
            <div class="nav-submenu" id="cadastrosSubmenu">
                <div class="nav-item" data-page="motorista.php" data-title="Motoristas" data-icon="🚗">
                    <span class="icon">🚗</span>
                    <span class="label">Motoristas</span>
                </div>
                <div class="nav-item" data-page="carro.php" data-title="Carros" data-icon="🚛">
                    <span class="icon">🚛</span>
                    <span class="label">Carros</span>
                </div>
                <div class="nav-item" data-page="cliente.php" data-title="Clientes" data-icon="👥">
                    <span class="icon">👥</span>
                    <span class="label">Clientes</span>
                </div>
                <div class="nav-item" data-page="fornecedor.php" data-title="Fornecedores" data-icon="🏭">
                    <span class="icon">🏭</span>
                    <span class="label">Fornecedores</span>
                </div>
            </div>

            <!-- Cadastros Gerais Submenu -->
            <button class="nav-submenu-toggle" data-submenu="cadastrosGeraisSubmenu">
                <span class="icon">🗂️</span>
                <span class="label">Cadastros Gerais</span>
                <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
                <span class="tooltip">Cadastros Gerais</span>
            </button>
            <div class="nav-submenu" id="cadastrosGeraisSubmenu">
                <div class="nav-item" data-page="remessa_situacao.php" data-title="Situação Remessa" data-icon="📋">
                    <span class="icon">📋</span>
                    <span class="label">Situação Remessa</span>
                </div>
                <div class="nav-item" data-page="cidade.php" data-title="Cidades" data-icon="🏙️">
                    <span class="icon">🏙️</span>
                    <span class="label">Cidades</span>
                </div>
                <div class="nav-item" data-page="forma_pagamento.php" data-title="Forma de Pagamento" data-icon="💳">
                    <span class="icon">💳</span>
                    <span class="label">Forma de Pagamento</span>
                </div>
            </div>

            <!-- Viagens Submenu -->
            <button class="nav-submenu-toggle" data-submenu="viagensSubmenu">
                <span class="icon">🚐</span>
                <span class="label">Viagens</span>
                <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
                <span class="tooltip">Viagens</span>
            </button>
            <div class="nav-submenu" id="viagensSubmenu">
                <div class="nav-item" data-page="viagem.php" data-title="Relação de Viagem" data-icon="📋">
                    <span class="icon">📋</span>
                    <span class="label">Relação de Viagem</span>
                </div>
                <div class="nav-item" data-page="pedido.php" data-title="Pedidos" data-icon="📦">
                    <span class="icon">📦</span>
                    <span class="label">Pedidos</span>
                </div>
                <div class="nav-item" data-page="gerarrota.php" data-title="Gerar Rota" data-icon="🗺️">
                    <span class="icon">🗺️</span>
                    <span class="label">Gerar Rota</span>
                </div>
            </div>

            <!-- Fornecedor Submenu -->
            <button class="nav-submenu-toggle" data-submenu="fornecedorSubmenu">
                <span class="icon">🏭</span>
                <span class="label">Fornecedor</span>
                <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
                <span class="tooltip">Fornecedor</span>
            </button>
            <div class="nav-submenu" id="fornecedorSubmenu">
                <div class="nav-item" data-page="validafornecedor.php" data-title="Validar Fornecedor" data-icon="📦">
                    <span class="icon">📦</span>
                    <span class="label">Validar Fornecedor</span>
                </div>
            </div>

            <!-- Relatórios Submenu -->
            <button class="nav-submenu-toggle" data-submenu="relatoriosSubmenu">
                <span class="icon">📊</span>
                <span class="label">Relatórios</span>
                <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
                <span class="tooltip">Relatórios</span>
            </button>
            <div class="nav-submenu" id="relatoriosSubmenu">
                <div class="nav-item" data-page="ranking.php" data-title="Ranking" data-icon="🏆">
                    <span class="icon">🏆</span>
                    <span class="label">Ranking</span>
                </div>
            </div>

            <div class="nav-item" data-page="configuracoes.php" data-title="Configurações" data-icon="⚙️">
                <span class="icon">⚙️</span>
                <span class="label">Configurações</span>
                <span class="tooltip">Configurações</span>
            </div>

            <?php if ($isAdmin): ?>
                <div class="nav-item" data-page="sobre.php" data-title="Sobre" data-icon="ℹ️">
                    <span class="icon">ℹ️</span>
                    <span class="label">Sobre</span>
                    <span class="tooltip">Sobre</span>
                </div>
            <?php endif; ?>

            <div class="nav-section">Conta</div>

            <a href="logout.php" class="nav-item" style="color: white; text-decoration: none;">
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
            © 2026 <?php echo EMPRESA_NOME; ?>
        </div>
    </aside>

    <!-- Main Area -->
    <div class="main-area">
        <!-- Mobile Header -->
        <div class="mobile-header">
            <button onclick="openMobileSidebar()">☰</button>
            <span><?php echo EMPRESA_NOME; ?></span>
        </div>

        <!-- Tabs Bar -->
        <div class="tabs-bar" id="tabsBar"></div>

        <!-- Content Area -->
        <div class="content-area" id="contentArea">
            <div class="empty-state" id="emptyState">
                <div class="es-icon">📂</div>
                <h2>Nenhuma aba aberta</h2>
                <p>Clique em um item do menu para abrir uma nova aba</p>
            </div>
        </div>
    </div>

    <script>
        // ===== TAB STATE =====
        const tabs = [];
        let activeTabId = null;

        // DOM
        const sidebar = document.getElementById('sidebar');
        const tabsBar = document.getElementById('tabsBar');
        const contentArea = document.getElementById('contentArea');
        const emptyState = document.getElementById('emptyState');

        // ===== SIDEBAR TOGGLE =====
        const SIDEBAR_KEY = 'vt_app_sidebar_collapsed';
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem(SIDEBAR_KEY, sidebar.classList.contains('collapsed'));
        });

        if (localStorage.getItem(SIDEBAR_KEY) === 'true') {
            sidebar.classList.add('collapsed');
        }

        // ===== SUBMENU TOGGLES =====
        document.querySelectorAll('.nav-submenu-toggle').forEach(toggle => {
            const submenuId = toggle.dataset.submenu;
            const submenu = document.getElementById(submenuId);
            if (!submenu) return;

            const storageKey = 'vt_app_sub_' + submenuId;
            if (localStorage.getItem(storageKey) === 'true') {
                toggle.classList.add('open');
                submenu.classList.add('open');
            }

            toggle.addEventListener('click', () => {
                const isOpen = toggle.classList.toggle('open');
                submenu.classList.toggle('open', isOpen);
                localStorage.setItem(storageKey, isOpen);
            });
        });

        // ===== NAV ITEM CLICK =====
        document.querySelectorAll('.nav-item[data-page]').forEach(item => {
            item.addEventListener('click', () => {
                const page = item.dataset.page;
                const title = item.dataset.title;
                const icon = item.dataset.icon;
                openTab(page, title, icon);
                closeMobileSidebar();
            });
        });

        // ===== OPEN TAB =====
        function openTab(page, title, icon) {
            let tab = tabs.find(t => t.page === page);

            if (!tab) {
                const id = 'tab-' + Date.now();
                tab = { id, page, title, icon };
                tabs.push(tab);

                // Create tab element
                const tabEl = document.createElement('div');
                tabEl.className = 'tab';
                tabEl.dataset.id = id;
                tabEl.dataset.page = page;
                tabEl.innerHTML = `
                    <span class="tab-icon">${icon}</span>
                    <span class="tab-title">${title}</span>
                    <span class="tab-close" onclick="event.stopPropagation(); closeTab('${id}')">×</span>
                `;
                tabEl.addEventListener('click', () => activateTab(id));

                // Middle click to close
                tabEl.addEventListener('mousedown', (e) => {
                    if (e.button === 1) {
                        e.preventDefault();
                        closeTab(id);
                    }
                });

                tabsBar.appendChild(tabEl);

                // Create content iframe
                const content = document.createElement('div');
                content.className = 'tab-content';
                content.dataset.id = id;
                const separator = page.includes('?') ? '&' : '?';
                content.innerHTML = `<iframe src="${page}${separator}notabs=1"></iframe>`;
                contentArea.appendChild(content);
            }

            activateTab(tab.id);
            saveTabs();
        }

        // ===== ACTIVATE TAB =====
        function activateTab(id) {
            activeTabId = id;

            // Update tab elements
            document.querySelectorAll('.tab').forEach(t => {
                t.classList.toggle('active', t.dataset.id === id);
            });

            // Update content
            document.querySelectorAll('.tab-content').forEach(c => {
                c.classList.toggle('active', c.dataset.id === id);
            });

            // Hide empty state
            emptyState.style.display = 'none';

            // Update sidebar highlights
            updateSidebarHighlights();

            // Scroll active tab into view
            const activeTab = document.querySelector(`.tab[data-id="${id}"]`);
            if (activeTab) {
                activeTab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
            }

            saveTabs();
        }

        // ===== CLOSE TAB =====
        function closeTab(id) {
            const index = tabs.findIndex(t => t.id === id);
            if (index === -1) return;

            tabs.splice(index, 1);

            document.querySelector(`.tab[data-id="${id}"]`)?.remove();
            document.querySelector(`.tab-content[data-id="${id}"]`)?.remove();

            if (activeTabId === id) {
                if (tabs.length > 0) {
                    const newIndex = Math.min(index, tabs.length - 1);
                    activateTab(tabs[newIndex].id);
                } else {
                    activeTabId = null;
                    emptyState.style.display = 'flex';
                    updateSidebarHighlights();
                }
            }

            saveTabs();
        }

        // ===== SIDEBAR HIGHLIGHTS =====
        function updateSidebarHighlights() {
            const openPages = tabs.map(t => t.page);
            const activeTab = tabs.find(t => t.id === activeTabId);
            const activePage = activeTab ? activeTab.page : null;

            document.querySelectorAll('.nav-item[data-page]').forEach(item => {
                const page = item.dataset.page;
                item.classList.remove('tab-open', 'tab-active');
                if (page === activePage) {
                    item.classList.add('tab-active');
                } else if (openPages.includes(page)) {
                    item.classList.add('tab-open');
                }
            });
        }

        // ===== PERSIST TABS =====
        function saveTabs() {
            const state = {
                tabs: tabs.map(t => ({ page: t.page, title: t.title, icon: t.icon })),
                activePage: tabs.find(t => t.id === activeTabId)?.page || null
            };
            localStorage.setItem('vt_app_tabs', JSON.stringify(state));
        }

        function loadTabs() {
            try {
                const saved = JSON.parse(localStorage.getItem('vt_app_tabs'));
                if (saved && saved.tabs && saved.tabs.length > 0) {
                    saved.tabs.forEach(t => openTab(t.page, t.title, t.icon));
                    if (saved.activePage) {
                        const tab = tabs.find(t => t.page === saved.activePage);
                        if (tab) activateTab(tab.id);
                    }
                    return true;
                }
            } catch (e) { }
            return false;
        }

        // ===== MOBILE SIDEBAR =====
        function openMobileSidebar() {
            sidebar.classList.add('mobile-open');
            document.getElementById('sidebarOverlay').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeMobileSidebar() {
            sidebar.classList.remove('mobile-open');
            document.getElementById('sidebarOverlay').classList.remove('active');
            document.body.style.overflow = '';
        }

        // Keyboard: Escape closes mobile sidebar
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeMobileSidebar();
        });

        // ===== INIT =====
        const restored = loadTabs();
        if (!restored) {
            openTab('dashboard.php', 'Início', '🏠');
        }
    </script>
</body>

</html>
