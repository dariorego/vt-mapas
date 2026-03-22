<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(EMPRESA_NOME); ?> — Gestão Inteligente de Entregas</title>
    <style>
        :root {
            --primary:   <?php echo EMPRESA_COR_PRIMARIA; ?>;
            --primary-d: <?php echo EMPRESA_COR_SECUNDARIA; ?>;
            --text:      #fff;
            --dark:      #1a1a2e;
            --gray:      #f4f7f6;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            color: #333;
            overflow-x: hidden;
        }

        /* ── Nav ───────────────────────────────────────── */
        nav {
            position: fixed; top: 0; width: 100%; z-index: 100;
            background: rgba(0,0,0,0.35);
            backdrop-filter: blur(8px);
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 40px;
        }
        .nav-brand {
            display: flex; align-items: center; gap: 10px;
            color: #fff; font-size: 1.1rem; font-weight: 700;
            text-decoration: none;
        }
        .nav-brand img { width: 36px; height: 36px; object-fit: contain; border-radius: 6px; }
        .nav-links { display: flex; gap: 24px; align-items: center; }
        .nav-links a {
            color: rgba(255,255,255,0.88);
            text-decoration: none; font-size: 0.9rem;
            transition: color 0.2s;
        }
        .nav-links a:hover { color: #fff; }
        .btn-nav {
            background: var(--primary);
            color: #fff !important;
            padding: 8px 20px;
            border-radius: 24px;
            font-weight: 600 !important;
            transition: background 0.2s !important;
        }
        .btn-nav:hover { background: var(--primary-d) !important; }

        /* ── Hero ──────────────────────────────────────── */
        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--dark) 0%, #16213e 50%, var(--primary-d) 100%);
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            text-align: center;
            padding: 100px 20px 60px;
            position: relative; overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute; inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .hero-badge {
            background: rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.9);
            padding: 6px 18px; border-radius: 30px;
            font-size: 0.8rem; letter-spacing: 1px;
            text-transform: uppercase; margin-bottom: 24px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .hero h1 {
            font-size: clamp(2rem, 5vw, 3.8rem);
            font-weight: 800; color: #fff;
            line-height: 1.15; margin-bottom: 20px;
        }
        .hero h1 span { color: var(--primary); }
        .hero p {
            font-size: clamp(1rem, 2vw, 1.2rem);
            color: rgba(255,255,255,0.75);
            max-width: 560px; line-height: 1.7;
            margin-bottom: 40px;
        }
        .hero-btns { display: flex; gap: 14px; flex-wrap: wrap; justify-content: center; }
        .btn-primary {
            background: var(--primary); color: #fff;
            padding: 14px 32px; border-radius: 8px;
            text-decoration: none; font-weight: 700;
            font-size: 1rem; transition: background 0.2s, transform 0.1s;
            box-shadow: 0 4px 20px rgba(46,157,111,0.4);
        }
        .btn-primary:hover { background: var(--primary-d); transform: translateY(-2px); }
        .btn-outline {
            border: 2px solid rgba(255,255,255,0.4); color: #fff;
            padding: 14px 32px; border-radius: 8px;
            text-decoration: none; font-weight: 600;
            font-size: 1rem; transition: border-color 0.2s, transform 0.1s;
        }
        .btn-outline:hover { border-color: #fff; transform: translateY(-2px); }

        /* ── Stats ─────────────────────────────────────── */
        .stats {
            display: flex; gap: 40px; justify-content: center;
            margin-top: 60px; flex-wrap: wrap;
        }
        .stat { color: #fff; text-align: center; }
        .stat-num { font-size: 2rem; font-weight: 800; color: var(--primary); }
        .stat-label { font-size: 0.8rem; color: rgba(255,255,255,0.6); margin-top: 2px; }

        /* ── Seções ─────────────────────────────────────── */
        section { padding: 80px 20px; }
        .container { max-width: 1100px; margin: 0 auto; }
        .section-tag {
            display: inline-block; background: var(--primary);
            color: #fff; padding: 4px 14px; border-radius: 20px;
            font-size: 0.75rem; font-weight: 700;
            letter-spacing: 1px; text-transform: uppercase;
            margin-bottom: 14px;
        }
        h2 {
            font-size: clamp(1.6rem, 3vw, 2.4rem);
            font-weight: 800; margin-bottom: 14px; color: var(--dark);
        }
        .section-sub {
            color: #666; font-size: 1rem; max-width: 520px;
            line-height: 1.7; margin-bottom: 50px;
        }

        /* ── Serviços ───────────────────────────────────── */
        #servicos { background: var(--gray); }
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
        }
        .service-card {
            background: #fff; border-radius: 16px;
            padding: 28px 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .service-card:hover { transform: translateY(-4px); box-shadow: 0 8px 30px rgba(0,0,0,0.1); }
        .service-icon {
            width: 52px; height: 52px; border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--primary-d));
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 16px;
        }
        .service-card h3 { font-size: 1.05rem; font-weight: 700; margin-bottom: 8px; color: var(--dark); }
        .service-card p { font-size: 0.88rem; color: #666; line-height: 1.6; }

        /* ── Como funciona ──────────────────────────────── */
        #como-funciona { background: #fff; }
        .steps { display: flex; flex-direction: column; gap: 32px; max-width: 680px; }
        .step { display: flex; gap: 20px; align-items: flex-start; }
        .step-num {
            width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
            background: linear-gradient(135deg, var(--primary), var(--primary-d));
            color: #fff; font-weight: 800; font-size: 1rem;
            display: flex; align-items: center; justify-content: center;
        }
        .step-body h3 { font-size: 1rem; font-weight: 700; margin-bottom: 4px; color: var(--dark); }
        .step-body p  { font-size: 0.88rem; color: #666; line-height: 1.6; }

        /* ── Rastreamento CTA ───────────────────────────── */
        .track-section {
            background: linear-gradient(135deg, var(--primary), var(--primary-d));
            padding: 70px 20px; text-align: center; color: #fff;
        }
        .track-section h2 { color: #fff; margin-bottom: 12px; }
        .track-section p  { color: rgba(255,255,255,0.85); margin-bottom: 32px; font-size: 1rem; max-width: 480px; margin-left: auto; margin-right: auto; }
        .btn-white {
            background: #fff; color: var(--primary);
            padding: 14px 36px; border-radius: 8px;
            text-decoration: none; font-weight: 700;
            font-size: 1rem; display: inline-block;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .btn-white:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(0,0,0,0.2); }

        /* ── Contato ─────────────────────────────────────── */
        #contato { background: var(--gray); }
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 24px;
        }
        .contact-card {
            background: #fff; border-radius: 16px; padding: 28px 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            text-align: center;
        }
        .contact-card .icon { font-size: 2rem; margin-bottom: 12px; }
        .contact-card h4 { font-weight: 700; margin-bottom: 6px; color: var(--dark); }
        .contact-card a  { color: var(--primary); text-decoration: none; font-size: 0.95rem; }
        .contact-card a:hover { text-decoration: underline; }

        /* ── Footer ──────────────────────────────────────── */
        footer {
            background: var(--dark); color: rgba(255,255,255,0.5);
            text-align: center; padding: 28px 20px;
            font-size: 0.82rem;
        }
        footer a { color: rgba(255,255,255,0.5); text-decoration: none; }
        footer a:hover { color: #fff; }

        /* ── Responsive ──────────────────────────────────── */
        @media (max-width: 640px) {
            nav { padding: 12px 20px; }
            .nav-links .hide-mobile { display: none; }
        }
    </style>
</head>
<body>

<!-- Nav -->
<nav>
    <a class="nav-brand" href="/">
        <?php if (EMPRESA_LOGO): ?>
            <img src="<?php echo htmlspecialchars(EMPRESA_LOGO); ?>" alt="Logo">
        <?php else: ?>
            <span style="font-size:1.6rem;">🚚</span>
        <?php endif; ?>
        <?php echo htmlspecialchars(EMPRESA_NOME); ?>
    </a>
    <div class="nav-links">
        <a href="#servicos" class="hide-mobile">Serviços</a>
        <a href="#como-funciona" class="hide-mobile">Como funciona</a>
        <a href="#contato" class="hide-mobile">Contato</a>
        <a href="/minhaentrega">Rastrear entrega</a>
        <a href="/admin" class="btn-nav">Área Admin</a>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div class="hero-badge">Gestão Inteligente de Entregas</div>
    <h1>Entregas mais rápidas,<br><span>clientes mais felizes</span></h1>
    <p>Otimização de rotas, rastreamento em tempo real e gestão completa da sua operação logística em um só lugar.</p>
    <div class="hero-btns">
        <a href="/minhaentrega" class="btn-primary">📦 Rastrear minha entrega</a>
        <a href="#servicos" class="btn-outline">Conheça os serviços</a>
    </div>
    <div class="stats">
        <div class="stat">
            <div class="stat-num">100%</div>
            <div class="stat-label">Digital</div>
        </div>
        <div class="stat">
            <div class="stat-num">Google</div>
            <div class="stat-label">Maps integrado</div>
        </div>
        <div class="stat">
            <div class="stat-num">Real time</div>
            <div class="stat-label">Atualizações</div>
        </div>
    </div>
</section>

<!-- Serviços -->
<section id="servicos">
    <div class="container">
        <div class="section-tag">Serviços</div>
        <h2>Tudo que sua logística precisa</h2>
        <p class="section-sub">Da saída do fornecedor até a entrega ao cliente, com controle total em cada etapa.</p>

        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon">🗺️</div>
                <h3>Otimização de Rotas</h3>
                <p>Algoritmo integrado ao Google Maps para calcular a rota mais eficiente entre múltiplos pontos de entrega.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">📦</div>
                <h3>Rastreamento pelo Cliente</h3>
                <p>O cliente acompanha sua entrega em tempo real pelo telefone, sabendo quando ela está próxima.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">🚗</div>
                <h3>App do Motorista</h3>
                <p>Interface mobile para o motorista visualizar roteiro, confirmar entregas e registrar forma de pagamento.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">📊</div>
                <h3>Gestão Completa</h3>
                <p>Controle de pedidos, clientes, fornecedores, motoristas e viagens em um painel administrativo completo.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">🏆</div>
                <h3>Ranking de Desempenho</h3>
                <p>Acompanhe o desempenho dos motoristas com relatórios e ranking de entregas realizadas.</p>
            </div>
            <div class="service-card">
                <div class="service-icon">✅</div>
                <h3>Validação de Fornecedor</h3>
                <p>Controle o recebimento de pacotes dos fornecedores com confirmação digital e histórico completo.</p>
            </div>
        </div>
    </div>
</section>

<!-- Como funciona -->
<section id="como-funciona">
    <div class="container">
        <div class="section-tag">Como funciona</div>
        <h2>Simples e eficiente</h2>
        <p class="section-sub">Do cadastro à entrega em poucos passos.</p>

        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-body">
                    <h3>Cadastre os pedidos e monte a viagem</h3>
                    <p>Registre os pedidos, associe ao motorista e organize a viagem com todos os pontos de entrega.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-body">
                    <h3>Gere a rota otimizada</h3>
                    <p>O sistema calcula automaticamente a melhor sequência de entregas via Google Maps, economizando tempo e combustível.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-body">
                    <h3>Motorista executa pelo app</h3>
                    <p>O motorista acessa o roteiro pelo celular, confirma cada entrega e registra a forma de pagamento recebida.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">4</div>
                <div class="step-body">
                    <h3>Cliente acompanha em tempo real</h3>
                    <p>O cliente consulta o status da entrega pelo telefone e recebe aviso quando o entregador está próximo.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Rastreamento -->
<section class="track-section">
    <h2>Já tem uma entrega a caminho?</h2>
    <p>Consulte o status da sua entrega agora mesmo usando o número de telefone cadastrado.</p>
    <a href="/minhaentrega" class="btn-white">📦 Rastrear minha entrega</a>
</section>

<!-- Contato -->
<section id="contato">
    <div class="container">
        <div class="section-tag">Contato</div>
        <h2>Fale conosco</h2>
        <p class="section-sub">Entre em contato para saber mais sobre nossos serviços.</p>

        <div class="contact-grid">
            <?php if (EMPRESA_TELEFONE): ?>
            <div class="contact-card">
                <div class="icon">📞</div>
                <h4>Telefone</h4>
                <a href="tel:<?php echo preg_replace('/\D/', '', EMPRESA_TELEFONE); ?>">
                    <?php echo htmlspecialchars(EMPRESA_TELEFONE); ?>
                </a>
            </div>
            <?php endif; ?>

            <?php if (EMPRESA_WHATSAPP): ?>
            <div class="contact-card">
                <div class="icon">💬</div>
                <h4>WhatsApp</h4>
                <a href="https://wa.me/<?php echo htmlspecialchars(EMPRESA_WHATSAPP); ?>" target="_blank">
                    Enviar mensagem
                </a>
            </div>
            <?php endif; ?>

            <div class="contact-card">
                <div class="icon">🔐</div>
                <h4>Área Administrativa</h4>
                <a href="/admin">Acessar painel</a>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer>
    © <?php echo date('Y'); ?> <?php echo htmlspecialchars(EMPRESA_NOME); ?>.
    Todos os direitos reservados.
    &nbsp;·&nbsp;
    <a href="/admin">Admin</a>
    &nbsp;·&nbsp;
    <a href="/minhaentrega">Rastrear entrega</a>
</footer>

</body>
</html>
