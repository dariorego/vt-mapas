<?php
/**
 * Configurações - Victor Transportes
 * Gerencia cards da tela inicial e preferências do sistema
 */
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'config.php';
$currentPage = 'configuracoes.php';

// Arquivo de configuração
$configFile = __DIR__ . '/data/dashboard_cards.json';

// Cards disponíveis no sistema
$allCards = [
    'gerar_rota' => [
        'titulo' => 'Gerar Rota',
        'descricao' => 'Visualize entregas no mapa e otimize a sequência de visitas para economizar tempo e combustível.',
        'icone' => '🗺️',
        'link' => 'gerarrota.php',
        'cor' => '#3B82F6',
        'ordem' => 1,
        'admin_only' => false
    ],
    'validar_fornecedor' => [
        'titulo' => 'Validar Fornecedor',
        'descricao' => 'Controle o recebimento de pacotes, visualize pendências e atualize status em tempo real.',
        'icone' => '📦',
        'link' => 'validafornecedor.php',
        'cor' => '#F59E0B',
        'ordem' => 2,
        'admin_only' => false
    ],
    'motoristas' => [
        'titulo' => 'Motoristas',
        'descricao' => 'Cadastro e gestão completa dos motoristas da frota.',
        'icone' => '🚗',
        'link' => 'motorista.php',
        'cor' => '#22C55E',
        'ordem' => 3,
        'admin_only' => false
    ],
    'clientes' => [
        'titulo' => 'Clientes',
        'descricao' => 'Gerencie o cadastro de clientes, contatos e localizações.',
        'icone' => '👥',
        'link' => 'cliente.php',
        'cor' => '#8B5CF6',
        'ordem' => 4,
        'admin_only' => false
    ],
    'viagens' => [
        'titulo' => 'Relação de Viagem',
        'descricao' => 'Acompanhe viagens, atribua motoristas e controle entregas.',
        'icone' => '🚐',
        'link' => 'viagem.php',
        'cor' => '#EC4899',
        'ordem' => 5,
        'admin_only' => false
    ],
    'pedidos' => [
        'titulo' => 'Pedidos',
        'descricao' => 'Visualize e gerencie todos os pedidos e remessas do sistema.',
        'icone' => '📋',
        'link' => 'pedido.php',
        'cor' => '#14B8A6',
        'ordem' => 6,
        'admin_only' => false
    ],
    'ranking' => [
        'titulo' => 'Ranking',
        'descricao' => 'Relatório de ranking dos clientes e fornecedores que mais utilizam a plataforma.',
        'icone' => '🏆',
        'link' => 'ranking.php',
        'cor' => '#F59E0B',
        'ordem' => 7,
        'admin_only' => false
    ],
    'otimizar_rotas' => [
        'titulo' => 'Otimizar Rotas',
        'descricao' => 'Otimize automaticamente a ordem das entregas para reduzir distância percorrida.',
        'icone' => '🛣️',
        'link' => 'optimize_routes.php',
        'cor' => '#6366F1',
        'ordem' => 8,
        'admin_only' => false
    ],
    'sobre' => [
        'titulo' => 'Sobre o Sistema',
        'descricao' => 'Visualize informações técnicas, versão do banco de dados e detalhes do release.',
        'icone' => 'ℹ️',
        'link' => 'sobre.php',
        'cor' => '#64748B',
        'ordem' => 9,
        'admin_only' => true
    ]
];

// Helper: load saved config
function loadCardConfig($configFile) {
    if (file_exists($configFile)) {
        $json = file_get_contents($configFile);
        return json_decode($json, true) ?: [];
    }
    return [];
}

// Helper: save config
function saveCardConfig($configFile, $config) {
    $dir = dirname($configFile);
    if (!is_dir($dir)) { mkdir($dir, 0755, true); }
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// AJAX: Salvar configuração
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'save_cards') {
    header('Content-Type: application/json');
    try {
        $cards = json_decode($_POST['cards'] ?? '{}', true);
        if ($cards === null) throw new Exception('Dados inválidos');
        saveCardConfig($configFile, $cards);
        echo json_encode(['success' => true, 'message' => 'Configuração salva com sucesso!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Carregar configuração
if (isset($_GET['ajax']) && $_GET['ajax'] === 'load_cards') {
    header('Content-Type: application/json');
    try {
        $saved = loadCardConfig($configFile);
        echo json_encode(['success' => true, 'data' => $saved, 'all_cards' => $allCards]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Carregar config salva para preencher estado inicial
$savedConfig = loadCardConfig($configFile);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Victor Transportes</title>
    <style>
        :root {
            --primary: #1F6F54; --primary-light: #2F8F6B; --primary-bg: #E8F4EF;
            --secondary: #3B82F6; --success: #22C55E; --warning: #F59E0B; --danger: #EF4444;
            --bg: #F6F8F9; --card: #ffffff; --text: #1F2933; --text-muted: #6B7280; --border: #E5E7EB;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
        .main-content { padding:24px; width:100%; }

        /* Page header */
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; flex-wrap:wrap; gap:16px; }
        .page-header h1 { font-size:1.5rem; font-weight:600; color:var(--primary); }
        .page-header-sub { font-size:0.9rem; color:var(--text-muted); margin-top:4px; }

        /* Section */
        .config-section { background:var(--card); border-radius:16px; padding:28px; box-shadow:0 2px 12px rgba(0,0,0,0.06); margin-bottom:24px; }
        .section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:16px; border-bottom:1px solid var(--border); }
        .section-title { font-size:1.15rem; font-weight:600; color:var(--text); display:flex; align-items:center; gap:10px; }
        .section-desc { font-size:0.85rem; color:var(--text-muted); margin-top:6px; }

        /* Toggle all */
        .toggle-all-btn { padding:8px 16px; border:2px solid var(--border); background:white; border-radius:8px; font-size:0.82rem; font-weight:600; color:var(--text-muted); cursor:pointer; transition:all 0.2s; }
        .toggle-all-btn:hover { border-color:var(--primary); color:var(--primary); }

        /* Cards Grid */
        .cards-config-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(320px, 1fr)); gap:16px; }

        /* Card Config Item */
        .card-config { background:var(--bg); border:2px solid var(--border); border-radius:14px; padding:20px; transition:all 0.3s ease; position:relative; cursor:grab; }
        .card-config:active { cursor:grabbing; }
        .card-config.enabled { border-color:var(--primary); background:var(--primary-bg); }
        .card-config.dragging { opacity:0.5; transform:scale(0.95); }
        .card-config.drag-over { border-color:var(--secondary); border-style:dashed; }

        .card-config-header { display:flex; align-items:center; gap:14px; margin-bottom:12px; }
        .card-config-icon { width:52px; height:52px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.6rem; background:white; box-shadow:0 2px 8px rgba(0,0,0,0.08); flex-shrink:0; }
        .card-config-info { flex:1; min-width:0; }
        .card-config-title { font-weight:600; font-size:1rem; color:var(--text); margin-bottom:2px; }
        .card-config-link { font-size:0.75rem; color:var(--text-muted); }

        .card-config-desc { font-size:0.85rem; color:var(--text-muted); line-height:1.5; margin-bottom:16px; }

        .card-config-footer { display:flex; justify-content:space-between; align-items:center; }

        /* Custom Toggle Switch */
        .toggle-container { display:flex; align-items:center; gap:10px; }
        .toggle-label { font-size:0.82rem; font-weight:600; }
        .toggle-label.on { color:var(--success); }
        .toggle-label.off { color:var(--text-muted); }

        .toggle-switch { position:relative; width:52px; height:28px; cursor:pointer; }
        .toggle-switch input { opacity:0; width:0; height:0; }
        .toggle-slider { position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background:#cbd5e1; border-radius:28px; transition:0.3s; }
        .toggle-slider:before { position:absolute; content:""; height:22px; width:22px; left:3px; bottom:3px; background:white; border-radius:50%; transition:0.3s; box-shadow:0 2px 4px rgba(0,0,0,0.2); }
        .toggle-switch input:checked + .toggle-slider { background:var(--success); }
        .toggle-switch input:checked + .toggle-slider:before { transform:translateX(24px); }

        /* Order badge */
        .order-badge { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:50%; background:var(--primary); color:white; font-size:0.75rem; font-weight:700; }
        .card-config:not(.enabled) .order-badge { background:#cbd5e1; }

        /* Admin badge */
        .admin-badge { display:inline-block; padding:2px 10px; border-radius:10px; font-size:0.7rem; font-weight:600; background:#fef3c7; color:#92400e; }

        /* Drag handle */
        .drag-handle { color:#cbd5e1; font-size:1.2rem; cursor:grab; padding:4px; line-height:1; }
        .card-config.enabled .drag-handle { color:var(--primary); }

        /* Save bar */
        .save-bar { position:sticky; bottom:20px; background:var(--card); border-radius:14px; padding:16px 24px; box-shadow:0 -4px 20px rgba(0,0,0,0.1); display:flex; justify-content:space-between; align-items:center; z-index:100; border:1px solid var(--border); }
        .save-bar.hidden { display:none; }
        .save-info { font-size:0.9rem; color:var(--text-muted); display:flex; align-items:center; gap:8px; }
        .save-info .dot { width:8px; height:8px; border-radius:50%; background:var(--warning); animation:pulse 1.5s infinite; }
        @keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:0.4; } }

        .btn { padding:12px 24px; border:none; border-radius:10px; font-size:0.95rem; font-weight:600; cursor:pointer; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
        .btn-primary { background:var(--primary); color:white; }
        .btn-primary:hover { background:var(--primary-light); transform:translateY(-1px); box-shadow:0 4px 12px rgba(31,111,84,0.3); }
        .btn-secondary { background:#e9ecef; color:var(--text); }
        .btn-danger { background:var(--danger); color:white; }

        /* Preview */
        .preview-section { margin-top:32px; }
        .preview-title { font-size:1rem; font-weight:600; color:var(--text-muted); margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        .preview-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:16px; }
        .preview-card { background:white; border-radius:14px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.05); text-align:center; border:1px solid #f0f0f0; transition:all 0.3s; }
        .preview-card:hover { transform:translateY(-3px); box-shadow:0 6px 16px rgba(0,0,0,0.1); }
        .preview-icon { font-size:2.2rem; margin-bottom:10px; width:60px; height:60px; display:flex; align-items:center; justify-content:center; border-radius:50%; background:#f8f9fa; margin:0 auto 12px; }
        .preview-name { font-weight:600; font-size:0.95rem; color:var(--secondary); margin-bottom:4px; }
        .preview-desc { font-size:0.8rem; color:#888; line-height:1.4; }

        /* Toast */
        .toast { position:fixed; bottom:24px; right:24px; padding:14px 20px; border-radius:12px; color:white; font-weight:500; z-index:4000; transform:translateY(100px); opacity:0; transition:all 0.3s ease; }
        .toast.show { transform:translateY(0); opacity:1; }
        .toast.success { background:var(--success); }
        .toast.error { background:var(--danger); }

        /* Animations */
        @keyframes fadeInUp { from { opacity:0; transform:translateY(15px); } to { opacity:1; transform:translateY(0); } }
        .animate-in { animation:fadeInUp 0.3s ease forwards; }

        @media (max-width:768px) {
            .main-content { padding:16px; }
            .cards-config-grid { grid-template-columns:1fr; }
            .preview-grid { grid-template-columns:repeat(2, 1fr); }
            .save-bar { flex-direction:column; gap:12px; text-align:center; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content page-with-sidebar">
        <div class="page-header">
            <div>
                <h1>⚙️ Configurações</h1>
                <p class="page-header-sub">Gerencie os cards da tela inicial — marque, desmarque e reordene.</p>
            </div>
        </div>

        <!-- Config Section -->
        <div class="config-section">
            <div class="section-header">
                <div>
                    <div class="section-title">🎯 Cards da Tela Inicial</div>
                    <div class="section-desc">Ative ou desative os cards que aparecem na dashboard. Arraste para reordenar.</div>
                </div>
                <div style="display:flex;gap:8px;">
                    <button class="toggle-all-btn" onclick="toggleAll(true)">✅ Marcar Todos</button>
                    <button class="toggle-all-btn" onclick="toggleAll(false)">⬜ Desmarcar Todos</button>
                </div>
            </div>

            <div class="cards-config-grid" id="cardsGrid">
                <!-- Rendered by JS -->
            </div>
        </div>

        <!-- Preview -->
        <div class="config-section preview-section">
            <div class="preview-title">👁️ Prévia da Tela Inicial</div>
            <div class="preview-grid" id="previewGrid">
                <!-- Rendered by JS -->
            </div>
            <div id="previewEmpty" style="display:none;text-align:center;padding:30px;color:var(--text-muted);">
                <div style="font-size:2rem;margin-bottom:8px;">🫥</div>
                <p>Nenhum card selecionado. Marque ao menos um card acima.</p>
            </div>
        </div>

        <!-- Save Bar -->
        <div class="save-bar hidden" id="saveBar">
            <div class="save-info">
                <span class="dot"></span>
                <span>Você tem alterações não salvas</span>
            </div>
            <div style="display:flex;gap:10px;">
                <button class="btn btn-secondary" onclick="resetChanges()">↩️ Desfazer</button>
                <button class="btn btn-primary" onclick="saveConfig()" id="saveBtn">💾 Salvar Configuração</button>
            </div>
        </div>
    </main>

    <div class="toast" id="toast"></div>

    <script>
    // All available cards from PHP
    const allCards = <?php echo json_encode($allCards, JSON_UNESCAPED_UNICODE); ?>;
    const savedConfig = <?php echo json_encode($savedConfig ?: new stdClass(), JSON_UNESCAPED_UNICODE); ?>;

    let currentConfig = {}; // { cardKey: { enabled: bool, ordem: int, ... } }
    let originalConfig = {};
    let hasChanges = false;

    document.addEventListener('DOMContentLoaded', () => {
        initConfig();
        renderCards();
        renderPreview();
    });

    function initConfig() {
        // Merge allCards with savedConfig
        let ordem = 1;
        const keys = Object.keys(allCards);

        // If we have saved ordering, use it; otherwise keep default order
        if (savedConfig && Object.keys(savedConfig).length > 0) {
            // Sort keys by saved order
            keys.sort((a, b) => {
                const oa = savedConfig[a]?.ordem ?? allCards[a].ordem;
                const ob = savedConfig[b]?.ordem ?? allCards[b].ordem;
                return oa - ob;
            });
        }

        keys.forEach((key, i) => {
            const card = allCards[key];
            const saved = savedConfig[key] || {};
            currentConfig[key] = {
                enabled: saved.enabled !== undefined ? saved.enabled : (key === 'gerar_rota' || key === 'validar_fornecedor' || key === 'sobre'),
                ordem: saved.ordem !== undefined ? saved.ordem : card.ordem,
                titulo: saved.titulo || card.titulo,
                descricao: saved.descricao || card.descricao,
                icone: card.icone,
                link: card.link,
                cor: saved.cor || card.cor,
                admin_only: card.admin_only
            };
        });

        originalConfig = JSON.parse(JSON.stringify(currentConfig));
    }

    function renderCards() {
        const grid = document.getElementById('cardsGrid');
        const sortedKeys = getSortedKeys();

        grid.innerHTML = sortedKeys.map((key, idx) => {
            const card = allCards[key];
            const cfg = currentConfig[key];
            const isEnabled = cfg.enabled;

            return `<div class="card-config ${isEnabled ? 'enabled' : ''} animate-in" 
                         data-key="${key}" draggable="true" style="animation-delay:${idx*50}ms"
                         ondragstart="onDragStart(event)" ondragover="onDragOver(event)" 
                         ondrop="onDrop(event)" ondragend="onDragEnd(event)"
                         ondragenter="onDragEnter(event)" ondragleave="onDragLeave(event)">
                <div class="card-config-header">
                    <span class="drag-handle" title="Arraste para reordenar">⠿</span>
                    <div class="card-config-icon" style="border:2px solid ${cfg.cor}20">
                        ${card.icone}
                    </div>
                    <div class="card-config-info">
                        <div class="card-config-title">${esc(cfg.titulo)}</div>
                        <div class="card-config-link">→ ${card.link}</div>
                    </div>
                    <span class="order-badge">${idx + 1}</span>
                </div>
                <div class="card-config-desc">${esc(cfg.descricao)}</div>
                <div class="card-config-footer">
                    <div class="toggle-container">
                        <label class="toggle-switch">
                            <input type="checkbox" ${isEnabled ? 'checked' : ''} onchange="toggleCard('${key}', this.checked)">
                            <span class="toggle-slider"></span>
                        </label>
                        <span class="toggle-label ${isEnabled ? 'on' : 'off'}">${isEnabled ? 'Ativo' : 'Inativo'}</span>
                    </div>
                    ${card.admin_only ? '<span class="admin-badge">🔒 Admin</span>' : ''}
                </div>
            </div>`;
        }).join('');
    }

    function renderPreview() {
        const grid = document.getElementById('previewGrid');
        const empty = document.getElementById('previewEmpty');
        const enabledKeys = getSortedKeys().filter(k => currentConfig[k].enabled);

        if (enabledKeys.length === 0) {
            grid.style.display = 'none';
            empty.style.display = 'block';
            return;
        }

        grid.style.display = '';
        empty.style.display = 'none';

        grid.innerHTML = enabledKeys.map((key, i) => {
            const cfg = currentConfig[key];
            return `<div class="preview-card animate-in" style="animation-delay:${i*40}ms">
                <div class="preview-icon">${cfg.icone}</div>
                <div class="preview-name">${esc(cfg.titulo)}</div>
                <div class="preview-desc">${esc(cfg.descricao)}</div>
            </div>`;
        }).join('');
    }

    function getSortedKeys() {
        return Object.keys(currentConfig).sort((a, b) => currentConfig[a].ordem - currentConfig[b].ordem);
    }

    function toggleCard(key, enabled) {
        currentConfig[key].enabled = enabled;
        checkChanges();
        renderCards();
        renderPreview();
    }

    function toggleAll(enabled) {
        Object.keys(currentConfig).forEach(k => currentConfig[k].enabled = enabled);
        checkChanges();
        renderCards();
        renderPreview();
    }

    function checkChanges() {
        hasChanges = JSON.stringify(currentConfig) !== JSON.stringify(originalConfig);
        document.getElementById('saveBar').classList.toggle('hidden', !hasChanges);
    }

    function resetChanges() {
        currentConfig = JSON.parse(JSON.stringify(originalConfig));
        hasChanges = false;
        document.getElementById('saveBar').classList.add('hidden');
        renderCards();
        renderPreview();
        showToast('Alterações desfeitas', 'success');
    }

    async function saveConfig() {
        const btn = document.getElementById('saveBtn');
        btn.disabled = true;
        btn.innerHTML = '⏳ Salvando...';

        try {
            const form = new FormData();
            form.append('ajax', 'save_cards');
            form.append('cards', JSON.stringify(currentConfig));

            const res = await fetch('configuracoes.php', { method: 'POST', body: form });
            const data = await res.json();

            if (!data.success) throw new Error(data.error);

            originalConfig = JSON.parse(JSON.stringify(currentConfig));
            hasChanges = false;
            document.getElementById('saveBar').classList.add('hidden');
            showToast('✅ Configuração salva com sucesso!', 'success');
        } catch(e) {
            showToast('❌ ' + e.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '💾 Salvar Configuração';
        }
    }

    // ===== DRAG & DROP =====
    let draggedKey = null;

    function onDragStart(e) {
        draggedKey = e.currentTarget.dataset.key;
        e.currentTarget.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    }

    function onDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }

    function onDragEnter(e) {
        e.preventDefault();
        const target = e.currentTarget;
        if (target.dataset.key !== draggedKey) {
            target.classList.add('drag-over');
        }
    }

    function onDragLeave(e) {
        e.currentTarget.classList.remove('drag-over');
    }

    function onDrop(e) {
        e.preventDefault();
        const targetKey = e.currentTarget.dataset.key;
        e.currentTarget.classList.remove('drag-over');

        if (draggedKey && targetKey && draggedKey !== targetKey) {
            // Swap orders
            const tmpOrdem = currentConfig[draggedKey].ordem;
            currentConfig[draggedKey].ordem = currentConfig[targetKey].ordem;
            currentConfig[targetKey].ordem = tmpOrdem;

            // Re-normalize orders
            const sorted = getSortedKeys();
            sorted.forEach((k, i) => currentConfig[k].ordem = i + 1);

            checkChanges();
            renderCards();
            renderPreview();
        }
    }

    function onDragEnd(e) {
        draggedKey = null;
        document.querySelectorAll('.card-config').forEach(c => {
            c.classList.remove('dragging', 'drag-over');
        });
    }

    // ===== UTILS =====
    function showToast(msg, type='success') {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className = `toast ${type} show`;
        setTimeout(() => t.classList.remove('show'), 3000);
    }

    function esc(t) {
        if (!t) return '';
        const d = document.createElement('div');
        d.textContent = t;
        return d.innerHTML;
    }
    </script>
</body>
</html>
