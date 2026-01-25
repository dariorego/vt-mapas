/**
 * Sidebar Retrátil - JavaScript
 * Victor Transportes - Sistema de Gestão
 */

(function () {
    'use strict';

    // Estado do sidebar (persistido no localStorage)
    const STORAGE_KEY = 'vt_sidebar_collapsed';
    let sidebarExpanded = localStorage.getItem(STORAGE_KEY) !== 'true';

    /**
     * Inicializa o sidebar
     */
    function initSidebar() {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');
        const overlay = document.getElementById('sidebarOverlay');
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');

        if (!sidebar) return;

        // Aplica estado salvo (com pequeno delay para evitar flash)
        requestAnimationFrame(() => {
            if (!sidebarExpanded) {
                sidebar.classList.add('collapsed');
                document.body.classList.add('sidebar-collapsed');
            }
        });

        // Toggle desktop
        if (toggle) {
            toggle.addEventListener('click', function (e) {
                e.stopPropagation();
                toggleSidebar();
            });
        }

        // Toggle mobile
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                toggleMobileSidebar();
            });
        }

        // Overlay click fecha sidebar mobile
        if (overlay) {
            overlay.addEventListener('click', closeMobileSidebar);
        }

        // Fecha sidebar mobile ao clicar fora
        document.addEventListener('click', function (e) {
            if (window.innerWidth <= 768) {
                if (sidebar && !sidebar.contains(e.target) &&
                    mobileMenuBtn && !mobileMenuBtn.contains(e.target)) {
                    closeMobileSidebar();
                }
            }
        });

        // Escuta resize para ajustar comportamento
        window.addEventListener('resize', handleResize);

        // Keyboard accessibility - Escape fecha mobile
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && window.innerWidth <= 768) {
                closeMobileSidebar();
            }
        });

        // Submenu toggles
        initSubmenus();
    }

    /**
     * Inicializa os submenus colapsáveis
     */
    function initSubmenus() {
        const SUBMENU_KEY = 'vt_submenu_cadastros';
        const toggle = document.getElementById('cadastrosToggle');
        const submenu = document.getElementById('cadastrosSubmenu');

        if (!toggle || !submenu) return;

        // Aplica estado salvo (se não está em página de cadastros)
        const savedState = localStorage.getItem(SUBMENU_KEY);
        const isOnCadastrosPage = toggle.classList.contains('open');

        if (!isOnCadastrosPage && savedState === 'true') {
            toggle.classList.add('open');
            submenu.classList.add('open');
        }

        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            const isOpen = toggle.classList.toggle('open');
            submenu.classList.toggle('open', isOpen);
            localStorage.setItem(SUBMENU_KEY, isOpen.toString());
        });
    }

    /**
     * Toggle do sidebar (desktop)
     */
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;

        sidebarExpanded = !sidebarExpanded;

        if (sidebarExpanded) {
            sidebar.classList.remove('collapsed');
            document.body.classList.remove('sidebar-collapsed');
            localStorage.setItem(STORAGE_KEY, 'false');
        } else {
            sidebar.classList.add('collapsed');
            document.body.classList.add('sidebar-collapsed');
            localStorage.setItem(STORAGE_KEY, 'true');
        }
    }

    /**
     * Toggle do sidebar (mobile)
     */
    function toggleMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (!sidebar) return;

        const isOpen = sidebar.classList.toggle('mobile-open');

        if (overlay) {
            overlay.classList.toggle('active', isOpen);
        }

        // Prevent body scroll when mobile menu is open
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }

    /**
     * Fecha sidebar mobile
     */
    function closeMobileSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (sidebar) {
            sidebar.classList.remove('mobile-open');
        }
        if (overlay) {
            overlay.classList.remove('active');
        }
        document.body.style.overflow = '';
    }

    /**
     * Ajusta comportamento no resize
     */
    function handleResize() {
        if (window.innerWidth > 768) {
            closeMobileSidebar();
        }
    }

    // Expõe funções globalmente
    window.VTSidebar = {
        toggle: toggleSidebar,
        toggleMobile: toggleMobileSidebar,
        closeMobile: closeMobileSidebar,
        isExpanded: function () { return sidebarExpanded; }
    };

    // Inicializa quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebar);
    } else {
        initSidebar();
    }
})();
