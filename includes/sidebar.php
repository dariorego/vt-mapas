<?php
/**
 * Componente Sidebar
 * Incluir em todas as páginas do sistema
 */

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* Sidebar Styles */
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 260px;
        height: 100vh;
        background: linear-gradient(180deg, #1F6F50 0%, #16523c 100%);
        color: white;
        box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        display: flex;
        flex-direction: column;
    }

    .sidebar-header {
        padding: 20px 15px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        text-align: center;
    }

    .sidebar-header h1 {
        font-size: 1.1rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin: 0;
    }

    .sidebar-header p {
        font-size: 0.75rem;
        opacity: 0.7;
        margin-top: 5px;
    }

    .sidebar-nav {
        flex: 1;
        padding: 15px 0;
    }

    .nav-section-title {
        padding: 8px 15px;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.5;
    }

    .sidebar .nav-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 15px;
        color: white;
        text-decoration: none;
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
        font-size: 0.9rem;
    }

    .sidebar .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
        border-left-color: white;
    }

    .sidebar .nav-link.active {
        background-color: rgba(255, 255, 255, 0.15);
        border-left-color: #fff;
    }

    .sidebar .nav-link .icon {
        font-size: 1.2rem;
        width: 25px;
        text-align: center;
    }

    .sidebar-footer {
        padding: 12px 15px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        font-size: 0.75rem;
        opacity: 0.6;
        text-align: center;
    }

    /* Page content with sidebar */
    .page-with-sidebar {
        margin-left: 260px;
    }

    /* Mobile Menu Toggle */
    .menu-toggle {
        display: none;
        position: fixed;
        top: 10px;
        left: 10px;
        z-index: 1100;
        background-color: #1F6F50;
        color: white;
        border: none;
        padding: 10px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 1.1rem;
    }

    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .sidebar.open {
            transform: translateX(0);
        }

        .page-with-sidebar {
            margin-left: 0;
        }

        .menu-toggle {
            display: block;
        }
    }
</style>

<!-- Mobile Menu Toggle -->
<button class="menu-toggle" onclick="toggleSidebar()">☰</button>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h1>🚚 Victor Transportes</h1>
        <p>Sistema de Gestão</p>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Menu</div>
        <a href="index.php" class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
            <span class="icon">🏠</span>
            <span>Início</span>
        </a>
        <a href="gerarrota.php" class="nav-link <?php echo $currentPage === 'gerarrota.php' ? 'active' : ''; ?>">
            <span class="icon">🗺️</span>
            <span>Gerar Rota</span>
        </a>
        <a href="validafornecedor.php"
            class="nav-link <?php echo $currentPage === 'validafornecedor.php' ? 'active' : ''; ?>">
            <span class="icon">📦</span>
            <span>Validar Fornecedor</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        © 2026 Victor Transportes
    </div>
</aside>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
    }

    document.addEventListener('click', function (e) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.querySelector('.menu-toggle');

        if (window.innerWidth <= 768 && sidebar && toggle) {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        }
    });
</script>