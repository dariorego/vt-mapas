<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'config.php';
$userName = $_SESSION['user_name'] ?? 'Usuário';
$isAdmin = !empty($_SESSION['user_is_admin']);
$currentPage = 'index.php';

// ===== Cards dinâmicos =====
$configFile = __DIR__ . '/data/dashboard_cards.json';

// Definição de todos os cards disponíveis
$allCards = [
    'gerar_rota' => [
        'titulo' => 'Gerar Rota', 'descricao' => 'Visualize entregas no mapa e otimize a sequência de visitas para economizar tempo e combustível.',
        'icone' => '🗺️', 'link' => 'gerarrota.php', 'cor' => '#3B82F6', 'ordem' => 1, 'admin_only' => false
    ],
    'validar_fornecedor' => [
        'titulo' => 'Validar Fornecedor', 'descricao' => 'Controle o recebimento de pacotes, visualize pendências e atualize status em tempo real.',
        'icone' => '📦', 'link' => 'validafornecedor.php', 'cor' => '#F59E0B', 'ordem' => 2, 'admin_only' => false
    ],
    'motoristas' => [
        'titulo' => 'Motoristas', 'descricao' => 'Cadastro e gestão completa dos motoristas da frota.',
        'icone' => '🚗', 'link' => 'motorista.php', 'cor' => '#22C55E', 'ordem' => 3, 'admin_only' => false
    ],
    'clientes' => [
        'titulo' => 'Clientes', 'descricao' => 'Gerencie o cadastro de clientes, contatos e localizações.',
        'icone' => '👥', 'link' => 'cliente.php', 'cor' => '#8B5CF6', 'ordem' => 4, 'admin_only' => false
    ],
    'carros' => [
        'titulo' => 'Carros', 'descricao' => 'Cadastro e gestão da frota de veículos.',
        'icone' => '🚛', 'link' => 'carro.php', 'cor' => '#6366F1', 'ordem' => 4.5, 'admin_only' => false
    ],
    'viagens' => [
        'titulo' => 'Relação de Viagem', 'descricao' => 'Acompanhe viagens, atribua motoristas e controle entregas.',
        'icone' => '🚐', 'link' => 'viagem.php', 'cor' => '#EC4899', 'ordem' => 5, 'admin_only' => false
    ],
    'pedidos' => [
        'titulo' => 'Pedidos', 'descricao' => 'Visualize e gerencie todos os pedidos e remessas do sistema.',
        'icone' => '📋', 'link' => 'pedido.php', 'cor' => '#14B8A6', 'ordem' => 6, 'admin_only' => false
    ],
    'ranking' => [
        'titulo' => 'Ranking', 'descricao' => 'Relatório de ranking dos clientes e fornecedores que mais utilizam a plataforma.',
        'icone' => '🏆', 'link' => 'ranking.php', 'cor' => '#F59E0B', 'ordem' => 7, 'admin_only' => false
    ],
    'otimizar_rotas' => [
        'titulo' => 'Otimizar Rotas', 'descricao' => 'Otimize automaticamente a ordem das entregas para reduzir distância percorrida.',
        'icone' => '🛣️', 'link' => 'optimize_routes.php', 'cor' => '#6366F1', 'ordem' => 8, 'admin_only' => false
    ],
    'sobre' => [
        'titulo' => 'Sobre o Sistema', 'descricao' => 'Visualize informações técnicas, versão do banco de dados e detalhes do release.',
        'icone' => 'ℹ️', 'link' => 'sobre.php', 'cor' => '#64748B', 'ordem' => 9, 'admin_only' => true
    ]
];

// Carregar config salva
$savedConfig = [];
if (file_exists($configFile)) {
    $json = file_get_contents($configFile);
    $savedConfig = json_decode($json, true) ?: [];
}

// Montar lista de cards visíveis
$visibleCards = [];
if (!empty($savedConfig)) {
    // Usar config salva
    $sorted = $savedConfig;
    uasort($sorted, function($a, $b) { return ($a['ordem'] ?? 99) - ($b['ordem'] ?? 99); });
    foreach ($sorted as $key => $cfg) {
        if (!($cfg['enabled'] ?? false)) continue;
        if (!isset($allCards[$key])) continue;
        $card = $allCards[$key];
        if ($card['admin_only'] && !$isAdmin) continue;
        $visibleCards[] = [
            'titulo' => $cfg['titulo'] ?? $card['titulo'],
            'descricao' => $cfg['descricao'] ?? $card['descricao'],
            'icone' => $card['icone'],
            'link' => $card['link'],
            'cor' => $cfg['cor'] ?? $card['cor']
        ];
    }
} else {
    // Fallback: cards originais
    $defaults = ['gerar_rota', 'validar_fornecedor', 'carros', 'sobre'];
    foreach ($defaults as $key) {
        $card = $allCards[$key];
        if ($card['admin_only'] && !$isAdmin) continue;
        $visibleCards[] = [
            'titulo' => $card['titulo'],
            'descricao' => $card['descricao'],
            'icone' => $card['icone'],
            'link' => $card['link'],
            'cor' => $card['cor']
        ];
    }
}
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
            --primary-light: <?php echo EMPRESA_COR_PRIMARIA; ?>;
            --primary-bg: <?php echo EMPRESA_COR_PRIMARIA; ?>1a;
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
            animation: fadeInUp 0.4s ease forwards;
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

        .empty-dashboard {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        .empty-dashboard .icon { font-size: 3rem; margin-bottom: 16px; }
        .empty-dashboard a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .empty-dashboard a:hover { text-decoration: underline; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
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
            <?php if (empty($visibleCards)): ?>
                <div class="empty-dashboard" style="grid-column:1/-1;">
                    <div class="icon">🫥</div>
                    <p>Nenhum card configurado para exibição.</p>
                    <p><a href="configuracoes.php">⚙️ Ir para Configurações</a> para escolher os cards.</p>
                </div>
            <?php else: ?>
                <?php foreach ($visibleCards as $i => $card): ?>
                    <a href="<?php echo htmlspecialchars($card['link']); ?>" class="action-card" style="animation-delay:<?php echo $i * 80; ?>ms">
                        <div class="card-icon"><?php echo $card['icone']; ?></div>
                        <h3 class="card-title"><?php echo htmlspecialchars($card['titulo']); ?></h3>
                        <p class="card-desc"><?php echo htmlspecialchars($card['descricao']); ?></p>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>