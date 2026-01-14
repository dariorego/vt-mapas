<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Victor Transportes - Sistema de Gestão</title>

    <style>
        :root {
            --primary-color: #1F6F50;
            --primary-dark: #16523c;
            --secondary-color: #2c3e50;
            --background-color: #f4f7f6;
            --card-background: #ffffff;
            --text-color: #333;
            --sidebar-width: 260px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }

        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .sidebar-header h1 {
            font-size: 1.3rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .sidebar-header p {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-top: 5px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
        }

        .nav-section {
            margin-bottom: 20px;
        }

        .nav-section-title {
            padding: 10px 20px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.5;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: white;
        }

        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.15);
            border-left-color: #fff;
        }

        .nav-link .icon {
            font-size: 1.3rem;
            width: 30px;
            text-align: center;
        }

        .nav-link .label {
            font-size: 0.95rem;
            font-weight: 500;
        }

        .nav-link .badge {
            margin-left: auto;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.75rem;
        }

        .sidebar-footer {
            padding: 15px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.8rem;
            opacity: 0.6;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 30px;
        }

        .welcome-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 12px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(31, 111, 80, 0.3);
        }

        .welcome-section h2 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .welcome-section p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .feature-card {
            background-color: var(--card-background);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
            border: 2px solid transparent;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }

        .feature-card .card-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 1.3rem;
            color: var(--secondary-color);
            margin-bottom: 10px;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .feature-card .card-action {
            margin-top: 20px;
            color: var(--primary-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Quick Stats */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-item {
            background-color: var(--card-background);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .stat-item .stat-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .stat-item .stat-label {
            font-size: 0.85rem;
            color: #666;
        }

        /* Mobile Menu Toggle */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
                padding-top: 70px;
            }

            .menu-toggle {
                display: block;
            }

            .welcome-section {
                padding: 25px;
            }

            .welcome-section h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- Mobile Menu Toggle -->
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h1>🚚 Victor Transportes</h1>
            <p>Sistema de Gestão Logística</p>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Menu Principal</div>
                <a href="index.php" class="nav-link active">
                    <span class="icon">🏠</span>
                    <span class="label">Início</span>
                </a>
                <a href="gerarrota.php" class="nav-link">
                    <span class="icon">🗺️</span>
                    <span class="label">Gerar Rota</span>
                </a>
                <a href="validafornecedor.php" class="nav-link">
                    <span class="icon">📦</span>
                    <span class="label">Validar Fornecedor</span>
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Relatórios</div>
                <a href="#" class="nav-link" style="opacity: 0.5; cursor: not-allowed;">
                    <span class="icon">📊</span>
                    <span class="label">Estatísticas</span>
                    <span class="badge">Em breve</span>
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            © 2026 Victor Transportes
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <section class="welcome-section">
            <h2>Bem-vindo ao Sistema de Gestão Logística - Logapp</h2>
            <p>Gerencie suas rotas de entrega e valide fornecedores de forma simples e eficiente.</p>
        </section>

        <div class="quick-stats">
            <div class="stat-item">
                <div class="stat-icon">🗺️</div>
                <div class="stat-label">Otimização de Rotas</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">📦</div>
                <div class="stat-label">Controle de Entregas</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">✅</div>
                <div class="stat-label">Validação Rápida</div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">📈</div>
                <div class="stat-label">Acompanhamento</div>
            </div>
        </div>

        <div class="cards-grid">
            <a href="gerarrota.php" class="feature-card">
                <div class="card-icon">🗺️</div>
                <h3>Gerar Rota</h3>
                <p>Otimize as rotas de entrega com visualização no mapa. Organize a sequência de entregas de forma
                    eficiente.</p>
                <div class="card-action">
                    Acessar →
                </div>
            </a>

            <a href="validafornecedor.php" class="feature-card">
                <div class="card-icon">📦</div>
                <h3>Validar Fornecedor</h3>
                <p>Controle o status das entregas dos fornecedores. Confirme ou reverta entregas com facilidade.</p>
                <div class="card-action">
                    Acessar →
                </div>
            </a>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.menu-toggle');

            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    </script>
</body>

</html>