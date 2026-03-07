<?php
/**
 * Ranking de Clientes e Fornecedores
 * Victor Transportes - Sistema de Gestão
 * 
 * Relatórios de ranking mostrando os clientes e fornecedores
 * que mais utilizam a plataforma, com filtros por período.
 */
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'config.php';
require_once 'Database.php';
$db = new Database();
$currentPage = 'ranking.php';

// AJAX: Ranking de Clientes
if (isset($_GET['ajax']) && $_GET['ajax'] === 'ranking_clientes') {
    header('Content-Type: application/json');
    try {
        $dataInicio = $_GET['data_inicio'] ?? '';
        $dataFim = $_GET['data_fim'] ?? '';
        $params = [];
        
        $sql = "SELECT 
                    c.id,
                    c.nome,
                    c.fone,
                    c.endereco,
                    c.situacao,
                    COUNT(r.id) as total_pedidos,
                    SUM(COALESCE(r.total, 0)) as valor_total,
                    SUM(COALESCE(r.pacote_qde, 0)) as total_pacotes,
                    MAX(v.data_viagem) as ultimo_uso,
                    MIN(v.data_viagem) as primeiro_uso
                FROM prod_vt.cliente c
                INNER JOIN prod_vt.remessa r ON r.cliente_id = c.id
                INNER JOIN prod_vt.viagem v ON v.id = r.viagem_id
                WHERE c.id NOT IN (120, 197)";
        
        if (!empty($dataInicio)) {
            $sql .= " AND DATE(v.data_viagem) >= :data_inicio";
            $params[':data_inicio'] = $dataInicio;
        }
        if (!empty($dataFim)) {
            $sql .= " AND DATE(v.data_viagem) <= :data_fim";
            $params[':data_fim'] = $dataFim;
        }
        
        $sql .= " GROUP BY c.id, c.nome, c.fone, c.endereco, c.situacao
                   ORDER BY total_pedidos DESC
                   LIMIT 100";
        
        $clientes = $db->query($sql, $params);
        
        // Totais gerais
        $sqlTotal = "SELECT 
                        COUNT(DISTINCT c.id) as total_clientes,
                        COUNT(r.id) as total_pedidos,
                        SUM(COALESCE(r.total, 0)) as valor_total
                     FROM prod_vt.cliente c
                     INNER JOIN prod_vt.remessa r ON r.cliente_id = c.id
                     INNER JOIN prod_vt.viagem v ON v.id = r.viagem_id
                     WHERE c.id NOT IN (120, 197)";
        
        $paramsTotal = [];
        if (!empty($dataInicio)) {
            $sqlTotal .= " AND DATE(v.data_viagem) >= :data_inicio";
            $paramsTotal[':data_inicio'] = $dataInicio;
        }
        if (!empty($dataFim)) {
            $sqlTotal .= " AND DATE(v.data_viagem) <= :data_fim";
            $paramsTotal[':data_fim'] = $dataFim;
        }
        
        $totais = $db->queryOne($sqlTotal, $paramsTotal);
        
        echo json_encode(['success' => true, 'data' => $clientes, 'totais' => $totais]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Ranking de Fornecedores
if (isset($_GET['ajax']) && $_GET['ajax'] === 'ranking_fornecedores') {
    header('Content-Type: application/json');
    try {
        $dataInicio = $_GET['data_inicio'] ?? '';
        $dataFim = $_GET['data_fim'] ?? '';
        $params = [];
        
        $sql = "SELECT 
                    f.id,
                    f.descricao as nome,
                    COUNT(rv.id) as total_entregas,
                    SUM(COALESCE(rv.qde, 0)) as total_quantidade,
                    COUNT(DISTINCT rv.cliente_id) as total_clientes_atendidos,
                    MAX(v.data_viagem) as ultimo_uso,
                    MIN(v.data_viagem) as primeiro_uso,
                    SUM(CASE WHEN rv.remessa_situacao_id = 6 THEN 1 ELSE 0 END) as total_entregues,
                    SUM(CASE WHEN rv.remessa_situacao_id = 1 THEN 1 ELSE 0 END) as total_pendentes
                FROM prod_vt.fornecedor f
                INNER JOIN prod_vt.remessa_valor rv ON rv.fornecedor_id = f.id
                INNER JOIN prod_vt.viagem v ON v.id = rv.remessa_viagem_id
                WHERE 1=1";
        
        if (!empty($dataInicio)) {
            $sql .= " AND DATE(v.data_viagem) >= :data_inicio";
            $params[':data_inicio'] = $dataInicio;
        }
        if (!empty($dataFim)) {
            $sql .= " AND DATE(v.data_viagem) <= :data_fim";
            $params[':data_fim'] = $dataFim;
        }
        
        $sql .= " GROUP BY f.id, f.descricao
                   ORDER BY total_entregas DESC
                   LIMIT 100";
        
        $fornecedores = $db->query($sql, $params);
        
        // Totais gerais
        $sqlTotal = "SELECT 
                        COUNT(DISTINCT f.id) as total_fornecedores,
                        COUNT(rv.id) as total_entregas,
                        SUM(COALESCE(rv.qde, 0)) as total_quantidade
                     FROM prod_vt.fornecedor f
                     INNER JOIN prod_vt.remessa_valor rv ON rv.fornecedor_id = f.id
                     INNER JOIN prod_vt.viagem v ON v.id = rv.remessa_viagem_id
                     WHERE 1=1";
        
        $paramsTotal = [];
        if (!empty($dataInicio)) {
            $sqlTotal .= " AND DATE(v.data_viagem) >= :data_inicio";
            $paramsTotal[':data_inicio'] = $dataInicio;
        }
        if (!empty($dataFim)) {
            $sqlTotal .= " AND DATE(v.data_viagem) <= :data_fim";
            $paramsTotal[':data_fim'] = $dataFim;
        }
        
        $totais = $db->queryOne($sqlTotal, $paramsTotal);
        
        echo json_encode(['success' => true, 'data' => $fornecedores, 'totais' => $totais]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking - Victor Transportes</title>
    <style>
        :root {
            --primary: #1F6F54; --primary-light: #2F8F6B; --primary-bg: #E8F4EF;
            --secondary: #3B82F6; --success: #22C55E; --warning: #F59E0B; --danger: #EF4444;
            --bg: #F6F8F9; --card: #ffffff; --text: #1F2933; --text-muted: #6B7280; --border: #E5E7EB;
            --gold: #F59E0B; --silver: #94A3B8; --bronze: #D97706;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
        .main-content { padding:20px; width:100%; }

        /* Header */
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px; }
        .page-header h1 { font-size:1.5rem; font-weight:600; color:var(--primary); display:flex; align-items:center; gap:10px; }

        /* Tabs */
        .tabs { display:flex; gap:0; margin-bottom:24px; background:var(--card); border-radius:12px; padding:6px; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
        .tab-btn { flex:1; padding:14px 20px; border:none; background:transparent; font-size:0.95rem; font-weight:600; color:var(--text-muted); cursor:pointer; border-radius:8px; transition:all 0.3s ease; display:flex; align-items:center; justify-content:center; gap:10px; position:relative; }
        .tab-btn:hover { background:#f3f4f6; color:var(--text); }
        .tab-btn.active { background:var(--primary); color:white; box-shadow:0 4px 12px rgba(31,111,84,0.3); }
        .tab-btn .tab-icon { font-size:1.3rem; }
        .tab-btn .tab-count { background:rgba(255,255,255,0.2); padding:2px 10px; border-radius:12px; font-size:0.75rem; }
        .tab-btn.active .tab-count { background:rgba(255,255,255,0.25); }

        /* Filter Bar */
        .filter-card { background:var(--card); border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
        .filter-title { font-size:0.8rem; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:var(--text-muted); margin-bottom:12px; display:flex; align-items:center; gap:8px; }
        .filter-row { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
        .filter-group { display:flex; flex-direction:column; gap:4px; }
        .filter-group label { font-size:0.75rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; }
        .filter-group input, .filter-group select { padding:10px 14px; border:1px solid var(--border); border-radius:8px; font-size:0.9rem; min-width:150px; }
        .filter-group input:focus, .filter-group select:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(31,111,84,0.1); }

        /* Quick filter presets */
        .preset-btns { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
        .preset-btn { padding:8px 16px; border:2px solid var(--border); background:white; border-radius:20px; font-size:0.82rem; font-weight:600; color:var(--text-muted); cursor:pointer; transition:all 0.2s; }
        .preset-btn:hover { border-color:var(--primary); color:var(--primary); }
        .preset-btn.active { background:var(--primary); border-color:var(--primary); color:white; }

        /* Buttons */
        .btn { padding:10px 20px; border:none; border-radius:8px; font-size:0.9rem; font-weight:600; cursor:pointer; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
        .btn-primary { background:var(--primary); color:white; }
        .btn-primary:hover { background:var(--primary-light); }
        .btn-secondary { background:#e9ecef; color:var(--text); }

        /* Stats */
        .stats-row { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:16px; margin-bottom:24px; }
        .stat-card { background:var(--card); border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.05); border-left:4px solid var(--primary); text-align:center; }
        .stat-card .stat-value { font-size:1.8rem; font-weight:800; color:var(--primary); margin-bottom:4px; }
        .stat-card .stat-label { font-size:0.75rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; }
        .stat-card.gold { border-left-color:var(--gold); }
        .stat-card.gold .stat-value { color:var(--gold); }
        .stat-card.blue { border-left-color:var(--secondary); }
        .stat-card.blue .stat-value { color:var(--secondary); }

        /* Table */
        .table-container { background:var(--card); border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); overflow:hidden; }
        table { width:100%; border-collapse:collapse; }
        th,td { padding:12px 14px; text-align:left; border-bottom:1px solid var(--border); }
        th { background:var(--primary); color:white; font-weight:600; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.5px; cursor:pointer; user-select:none; }
        th:hover { background:var(--primary-light); }
        tr:hover { background:#f8f9fa; }
        tr:nth-child(even) { background:#fafbfc; }
        tr:nth-child(even):hover { background:#f0f1f2; }

        /* Sort indicator */
        th .sort-arrow { margin-left:4px; font-size:0.65rem; opacity:0.4; transition:opacity 0.2s; }
        th.sorted .sort-arrow { opacity:1; }
        th.sorted-asc .sort-arrow::after { content:'▲'; }
        th.sorted-desc .sort-arrow::after { content:'▼'; }
        th:not(.sorted) .sort-arrow::after { content:'⇅'; }

        /* Rank Medals */
        .rank-medal { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:50%; font-weight:800; font-size:0.9rem; }
        .rank-medal.gold { background:linear-gradient(135deg, #F59E0B, #D97706); color:white; box-shadow:0 2px 8px rgba(245,158,11,0.4); }
        .rank-medal.silver { background:linear-gradient(135deg, #94A3B8, #64748B); color:white; box-shadow:0 2px 8px rgba(148,163,184,0.4); }
        .rank-medal.bronze { background:linear-gradient(135deg, #D97706, #B45309); color:white; box-shadow:0 2px 8px rgba(217,119,6,0.4); }
        .rank-medal.normal { background:var(--primary-bg); color:var(--primary); font-size:0.8rem; }

        /* Progress bar */
        .progress-bar { width:100%; height:8px; background:#e5e7eb; border-radius:4px; overflow:hidden; }
        .progress-fill { height:100%; border-radius:4px; transition:width 0.5s ease; }
        .progress-fill.green { background:linear-gradient(90deg, var(--primary), var(--success)); }
        .progress-fill.blue { background:linear-gradient(90deg, var(--secondary), #818cf8); }

        /* Client/Supplier name */
        .entity-name { font-weight:600; color:var(--text); font-size:0.92rem; }
        .entity-sub { font-size:0.78rem; color:var(--text-muted); margin-top:2px; }
        .entity-phone { display:inline-flex; align-items:center; gap:4px; font-size:0.82rem; color:var(--text-muted); }

        /* Date badge */
        .date-badge { display:inline-block; padding:4px 10px; border-radius:6px; font-size:0.75rem; font-weight:600; }
        .date-badge.recent { background:#dcfce7; color:#166534; }
        .date-badge.old { background:#fee2e2; color:#991b1b; }
        .date-badge.medium { background:#fef3c7; color:#92400e; }

        /* Tooltip */
        .info-tooltip { position:relative; cursor:help; }
        .info-tooltip:hover::after { content:attr(data-tip); position:absolute; bottom:100%; left:50%; transform:translateX(-50%); padding:6px 10px; background:#1f2937; color:white; border-radius:6px; font-size:0.72rem; white-space:nowrap; z-index:100; }

        /* Empty & Loading */
        .empty-state { text-align:center; padding:60px 20px; color:var(--text-muted); }
        .empty-state .icon { font-size:3rem; margin-bottom:16px; }
        .loading { text-align:center; padding:40px; color:var(--text-muted); }

        /* Tab content */
        .tab-content { display:none; }
        .tab-content.active { display:block; }

        /* Toast */
        .toast { position:fixed; bottom:24px; right:24px; padding:14px 20px; border-radius:10px; color:white; font-weight:500; z-index:4000; transform:translateY(100px); opacity:0; transition:all 0.3s ease; }
        .toast.show { transform:translateY(0); opacity:1; }
        .toast.success { background:var(--success); }
        .toast.error { background:var(--danger); }

        /* Animations */
        @keyframes fadeInUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        .animate-row { animation:fadeInUp 0.3s ease forwards; }

        /* WhatsApp link */
        .whatsapp-link { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:50%; background:#25d366; color:white; text-decoration:none; font-size:0.9rem; transition:all 0.2s; }
        .whatsapp-link:hover { background:#128c7e; transform:scale(1.1); }

        /* Export button */
        .export-btn { display:flex; align-items:center; gap:8px; }

        @media (max-width:768px) {
            .main-content { padding:16px; }
            .page-header { flex-direction:column; align-items:flex-start; }
            .filter-row { flex-direction:column; }
            .filter-group { width:100%; }
            .filter-group input, .filter-group select { min-width:auto; width:100%; }
            .table-container { overflow-x:auto; }
            table { min-width:800px; }
            .stats-row { grid-template-columns:repeat(2, 1fr); }
            .tabs { flex-direction:column; }
            .tab-btn { justify-content:flex-start; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content page-with-sidebar">
        <div class="page-header">
            <h1>🏆 Ranking de Utilização</h1>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('clientes')" id="tabClientes">
                <span class="tab-icon">👥</span>
                <span>Top Clientes</span>
                <span class="tab-count" id="tabCountClientes">0</span>
            </button>
            <button class="tab-btn" onclick="switchTab('fornecedores')" id="tabFornecedores">
                <span class="tab-icon">🏭</span>
                <span>Top Fornecedores</span>
                <span class="tab-count" id="tabCountFornecedores">0</span>
            </button>
        </div>

        <!-- Filter Card -->
        <div class="filter-card">
            <div class="filter-title">📅 Filtrar por Período</div>
            <div class="preset-btns">
                <button class="preset-btn active" onclick="setPreset('este_ano')">📆 Este Ano</button>
                <button class="preset-btn" onclick="setPreset('ano_passado')">📅 Ano Passado</button>
                <button class="preset-btn" onclick="setPreset('este_mes')">🗓️ Este Mês</button>
                <button class="preset-btn" onclick="setPreset('mes_passado')">📋 Mês Passado</button>
                <button class="preset-btn" onclick="setPreset('personalizado')">🔧 Personalizado</button>
            </div>
            <div class="filter-row" id="customDateRow">
                <div class="filter-group">
                    <label>Mês/Ano Início</label>
                    <input type="month" id="filterMesInicio" onchange="applyCustomFilter()">
                </div>
                <div class="filter-group">
                    <label>Mês/Ano Fim</label>
                    <input type="month" id="filterMesFim" onchange="applyCustomFilter()">
                </div>
                <button class="btn btn-primary" onclick="applyCustomFilter()">🔍 Filtrar</button>
                <button class="btn btn-secondary" onclick="setPreset('este_ano')">🔄 Limpar</button>
            </div>
        </div>

        <!-- ===== TAB CLIENTES ===== -->
        <div class="tab-content active" id="contentClientes">
            <div class="stats-row" id="statsClientes">
                <div class="stat-card">
                    <div class="stat-value" id="statTotalClientes">-</div>
                    <div class="stat-label">Clientes Ativos</div>
                </div>
                <div class="stat-card gold">
                    <div class="stat-value" id="statTotalPedidos">-</div>
                    <div class="stat-label">Total de Pedidos</div>
                </div>
                <div class="stat-card blue">
                    <div class="stat-value" id="statValorTotal">-</div>
                    <div class="stat-label">Valor Total (R$)</div>
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead><tr>
                        <th style="width:60px;text-align:center">#</th>
                        <th data-sort="nome" data-type="string">CLIENTE <span class="sort-arrow"></span></th>
                        <th data-sort="total_pedidos" data-type="number" style="width:100px;text-align:center">PEDIDOS <span class="sort-arrow"></span></th>
                        <th data-sort="total_pacotes" data-type="number" style="width:100px;text-align:center">PACOTES <span class="sort-arrow"></span></th>
                        <th data-sort="valor_total" data-type="number" style="width:130px;text-align:right">VALOR (R$) <span class="sort-arrow"></span></th>
                        <th style="width:140px;text-align:center">%</th>
                        <th data-sort="ultimo_uso" data-type="date" style="width:120px;text-align:center">ÚLTIMO USO <span class="sort-arrow"></span></th>
                        <th style="width:60px;text-align:center">ZAP</th>
                    </tr></thead>
                    <tbody id="tableClientes"><tr><td colspan="8" class="loading">🔄 Carregando...</td></tr></tbody>
                </table>
            </div>
        </div>

        <!-- ===== TAB FORNECEDORES ===== -->
        <div class="tab-content" id="contentFornecedores">
            <div class="stats-row" id="statsFornecedores">
                <div class="stat-card">
                    <div class="stat-value" id="statTotalFornecedores">-</div>
                    <div class="stat-label">Fornecedores Ativos</div>
                </div>
                <div class="stat-card gold">
                    <div class="stat-value" id="statTotalEntregas">-</div>
                    <div class="stat-label">Total de Entregas</div>
                </div>
                <div class="stat-card blue">
                    <div class="stat-value" id="statTotalQde">-</div>
                    <div class="stat-label">Total Quantidade</div>
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead><tr>
                        <th style="width:60px;text-align:center">#</th>
                        <th data-sort="nome" data-type="string">FORNECEDOR <span class="sort-arrow"></span></th>
                        <th data-sort="total_entregas" data-type="number" style="width:100px;text-align:center">ENTREGAS <span class="sort-arrow"></span></th>
                        <th data-sort="total_quantidade" data-type="number" style="width:100px;text-align:center">QUANTIDADE <span class="sort-arrow"></span></th>
                        <th data-sort="total_clientes_atendidos" data-type="number" style="width:100px;text-align:center">CLIENTES <span class="sort-arrow"></span></th>
                        <th style="width:140px;text-align:center">%</th>
                        <th data-sort="total_entregues" data-type="number" style="width:110px;text-align:center">ENTREGUES <span class="sort-arrow"></span></th>
                        <th data-sort="ultimo_uso" data-type="date" style="width:120px;text-align:center">ÚLTIMO USO <span class="sort-arrow"></span></th>
                    </tr></thead>
                    <tbody id="tableFornecedores"><tr><td colspan="8" class="loading">🔄 Carregando...</td></tr></tbody>
                </table>
            </div>
        </div>
    </main>

    <div class="toast" id="toast"></div>

    <script>
    let currentTab = 'clientes';
    let currentPreset = 'este_ano';
    let filterDataInicio = '', filterDataFim = '';
    let cachedClientes = [];
    let cachedFornecedores = [];
    let sortState = { clientes: { col: null, dir: 'desc' }, fornecedores: { col: null, dir: 'desc' } };

    document.addEventListener('DOMContentLoaded', () => {
        setPreset('este_ano');
        initSortableHeaders();
    });

    // ===== PRESETS =====
    function setPreset(preset) {
        currentPreset = preset;
        const now = new Date();
        const year = now.getFullYear();
        const month = now.getMonth(); // 0-indexed

        switch(preset) {
            case 'este_ano':
                filterDataInicio = `${year}-01-01`;
                filterDataFim = `${year}-12-31`;
                break;
            case 'ano_passado':
                filterDataInicio = `${year-1}-01-01`;
                filterDataFim = `${year-1}-12-31`;
                break;
            case 'este_mes':
                filterDataInicio = `${year}-${String(month+1).padStart(2,'0')}-01`;
                filterDataFim = getLastDayOfMonth(year, month);
                break;
            case 'mes_passado':
                const prevMonth = month === 0 ? 11 : month - 1;
                const prevYear = month === 0 ? year - 1 : year;
                filterDataInicio = `${prevYear}-${String(prevMonth+1).padStart(2,'0')}-01`;
                filterDataFim = getLastDayOfMonth(prevYear, prevMonth);
                break;
            case 'personalizado':
                // Show custom inputs, don't reload yet
                break;
        }

        // Update preset buttons
        document.querySelectorAll('.preset-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelector(`.preset-btn[onclick="setPreset('${preset}')"]`).classList.add('active');

        // Show/hide custom date row
        const customRow = document.getElementById('customDateRow');
        if (preset === 'personalizado') {
            customRow.style.display = 'flex';
            return; // Don't reload until user picks dates
        } else {
            customRow.style.display = 'none';
        }

        loadData();
    }

    function applyCustomFilter() {
        const mesInicio = document.getElementById('filterMesInicio').value;
        const mesFim = document.getElementById('filterMesFim').value;
        if (!mesInicio || !mesFim) {
            showToast('Selecione o mês/ano de início e fim', 'error');
            return;
        }
        const [yi, mi] = mesInicio.split('-').map(Number);
        const [yf, mf] = mesFim.split('-').map(Number);
        filterDataInicio = `${yi}-${String(mi).padStart(2,'0')}-01`;
        filterDataFim = getLastDayOfMonth(yf, mf - 1);
        loadData();
    }

    function getLastDayOfMonth(year, month) {
        const d = new Date(year, month + 1, 0);
        return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    }

    // ===== TABS =====
    function switchTab(tab) {
        currentTab = tab;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById(`tab${cap(tab)}`).classList.add('active');
        document.getElementById(`content${cap(tab)}`).classList.add('active');
    }

    function cap(s) { return s.charAt(0).toUpperCase() + s.slice(1); }

    // ===== LOAD DATA =====
    function loadData() {
        loadRankingClientes();
        loadRankingFornecedores();
    }

    async function loadRankingClientes() {
        const tbody = document.getElementById('tableClientes');
        tbody.innerHTML = '<tr><td colspan="8" class="loading">🔄 Carregando ranking de clientes...</td></tr>';
        try {
            const url = `ranking.php?ajax=ranking_clientes&data_inicio=${filterDataInicio}&data_fim=${filterDataFim}`;
            const res = await fetch(url);
            const data = await res.json();
            if (!data.success) throw new Error(data.error);

            const items = data.data;
            const totais = data.totais;

            // Update stats
            document.getElementById('statTotalClientes').textContent = totais.total_clientes || 0;
            document.getElementById('statTotalPedidos').textContent = formatNumber(totais.total_pedidos || 0);
            document.getElementById('statValorTotal').textContent = formatCurrency(totais.valor_total || 0);
            document.getElementById('tabCountClientes').textContent = items.length;

            cachedClientes = items;
            renderClientes(items);

        } catch(e) {
            tbody.innerHTML = `<tr><td colspan="8" class="empty-state">❌ ${esc(e.message)}</td></tr>`;
            showToast(e.message, 'error');
        }
    }

    function renderClientes(items) {
        const tbody = document.getElementById('tableClientes');
        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state"><div class="icon">📊</div><p>Nenhum dado encontrado para o período selecionado</p></div></td></tr>';
            return;
        }
        const maxPedidos = Math.max(...items.map(c => parseInt(c.total_pedidos) || 0), 1);

        tbody.innerHTML = items.map((c, i) => {
            const pct = ((c.total_pedidos / maxPedidos) * 100).toFixed(1);
            const medalClass = i === 0 ? 'gold' : i === 1 ? 'silver' : i === 2 ? 'bronze' : 'normal';
            const foneClean = (c.fone || '').replace(/\D/g, '');
            const hasPhone = foneClean.length >= 10;
            const dateBadge = getDateBadge(c.ultimo_uso);

            return `<tr class="animate-row" style="animation-delay:${i*30}ms">
                <td style="text-align:center"><span class="rank-medal ${medalClass}">${i+1}</span></td>
                <td>
                    <div class="entity-name">${esc(c.nome)}</div>
                    <div class="entity-sub">${esc(c.endereco || '')}</div>
                </td>
                <td style="text-align:center"><strong>${formatNumber(c.total_pedidos)}</strong></td>
                <td style="text-align:center">${formatNumber(c.total_pacotes)}</td>
                <td style="text-align:right"><strong>${formatCurrency(c.valor_total)}</strong></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="progress-bar"><div class="progress-fill green" style="width:${pct}%"></div></div>
                        <span style="font-size:0.75rem;font-weight:600;color:var(--text-muted);min-width:40px">${pct}%</span>
                    </div>
                </td>
                <td style="text-align:center"><span class="date-badge ${dateBadge.cls}">${dateBadge.text}</span></td>
                <td style="text-align:center">
                    ${hasPhone
                        ? `<a href="https://wa.me/55${foneClean}" target="_blank" class="whatsapp-link" title="${esc(c.fone)}">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                           </a>`
                        : '<span style="color:#ccc">—</span>'
                    }
                </td>
            </tr>`;
        }).join('');
    }

    async function loadRankingFornecedores() {
        const tbody = document.getElementById('tableFornecedores');
        tbody.innerHTML = '<tr><td colspan="8" class="loading">🔄 Carregando ranking de fornecedores...</td></tr>';
        try {
            const url = `ranking.php?ajax=ranking_fornecedores&data_inicio=${filterDataInicio}&data_fim=${filterDataFim}`;
            const res = await fetch(url);
            const data = await res.json();
            if (!data.success) throw new Error(data.error);

            const items = data.data;
            const totais = data.totais;

            // Update stats
            document.getElementById('statTotalFornecedores').textContent = totais.total_fornecedores || 0;
            document.getElementById('statTotalEntregas').textContent = formatNumber(totais.total_entregas || 0);
            document.getElementById('statTotalQde').textContent = formatNumber(totais.total_quantidade || 0);
            document.getElementById('tabCountFornecedores').textContent = items.length;

            cachedFornecedores = items;
            renderFornecedores(items);

        } catch(e) {
            tbody.innerHTML = `<tr><td colspan="8" class="empty-state">❌ ${esc(e.message)}</td></tr>`;
            showToast(e.message, 'error');
        }
    }

    function renderFornecedores(items) {
        const tbody = document.getElementById('tableFornecedores');
        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state"><div class="icon">📊</div><p>Nenhum dado encontrado para o período selecionado</p></div></td></tr>';
            return;
        }
        const maxEntregas = Math.max(...items.map(f => parseInt(f.total_entregas) || 0), 1);

        tbody.innerHTML = items.map((f, i) => {
            const pct = ((f.total_entregas / maxEntregas) * 100).toFixed(1);
            const medalClass = i === 0 ? 'gold' : i === 1 ? 'silver' : i === 2 ? 'bronze' : 'normal';
            const dateBadge = getDateBadge(f.ultimo_uso);
            const entreguesPct = f.total_entregas > 0 ? Math.round((f.total_entregues / f.total_entregas) * 100) : 0;

            return `<tr class="animate-row" style="animation-delay:${i*30}ms">
                <td style="text-align:center"><span class="rank-medal ${medalClass}">${i+1}</span></td>
                <td>
                    <div class="entity-name">${esc(f.nome)}</div>
                    <div class="entity-sub">${f.total_clientes_atendidos} clientes atendidos</div>
                </td>
                <td style="text-align:center"><strong>${formatNumber(f.total_entregas)}</strong></td>
                <td style="text-align:center">${formatNumber(f.total_quantidade)}</td>
                <td style="text-align:center">${formatNumber(f.total_clientes_atendidos)}</td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div class="progress-bar"><div class="progress-fill blue" style="width:${pct}%"></div></div>
                        <span style="font-size:0.75rem;font-weight:600;color:var(--text-muted);min-width:40px">${pct}%</span>
                    </div>
                </td>
                <td style="text-align:center">
                    <span style="font-size:0.8rem; font-weight:600; color:${entreguesPct >= 80 ? 'var(--success)' : entreguesPct >= 50 ? 'var(--warning)' : 'var(--danger)'}">
                        ${entreguesPct}% <span style="font-weight:400;color:var(--text-muted)">(${f.total_entregues}/${f.total_entregas})</span>
                    </span>
                </td>
                <td style="text-align:center"><span class="date-badge ${dateBadge.cls}">${dateBadge.text}</span></td>
            </tr>`;
        }).join('');
    }

    // ===== UTILS =====
    function getDateBadge(dateStr) {
        if (!dateStr) return { text: 'N/A', cls: 'old' };
        const d = new Date(dateStr);
        const now = new Date();
        const diffDays = Math.floor((now - d) / (1000 * 60 * 60 * 24));
        const formatted = `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`;
        
        if (diffDays <= 30) return { text: formatted, cls: 'recent' };
        if (diffDays <= 90) return { text: formatted, cls: 'medium' };
        return { text: formatted, cls: 'old' };
    }

    function formatNumber(n) {
        return parseInt(n || 0).toLocaleString('pt-BR');
    }

    function formatCurrency(v) {
        return parseFloat(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

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

    // ===== SORTING =====
    function initSortableHeaders() {
        document.querySelectorAll('th[data-sort]').forEach(th => {
            th.addEventListener('click', () => {
                const col = th.dataset.sort;
                const type = th.dataset.type || 'string';
                const table = th.closest('.tab-content, .table-container');
                const tabId = th.closest('.tab-content')?.id;
                const tabKey = tabId === 'contentClientes' ? 'clientes' : 'fornecedores';

                // Toggle direction
                if (sortState[tabKey].col === col) {
                    sortState[tabKey].dir = sortState[tabKey].dir === 'asc' ? 'desc' : 'asc';
                } else {
                    sortState[tabKey].col = col;
                    sortState[tabKey].dir = 'asc';
                }

                // Update header styles
                th.closest('thead').querySelectorAll('th').forEach(h => {
                    h.classList.remove('sorted', 'sorted-asc', 'sorted-desc');
                });
                th.classList.add('sorted', `sorted-${sortState[tabKey].dir}`);

                // Sort cached data
                const data = tabKey === 'clientes' ? [...cachedClientes] : [...cachedFornecedores];
                const dir = sortState[tabKey].dir === 'asc' ? 1 : -1;

                data.sort((a, b) => {
                    let valA = a[col], valB = b[col];
                    if (type === 'number') {
                        return (parseFloat(valA || 0) - parseFloat(valB || 0)) * dir;
                    } else if (type === 'date') {
                        return ((new Date(valA || 0)) - (new Date(valB || 0))) * dir;
                    } else {
                        return (valA || '').toString().localeCompare((valB || '').toString(), 'pt-BR', { sensitivity: 'base' }) * dir;
                    }
                });

                if (tabKey === 'clientes') renderClientes(data);
                else renderFornecedores(data);
            });
        });
    }
    </script>
</body>
</html>
