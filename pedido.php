<?php
/**
 * CRUD de Pedidos (Remessa)
 * Victor Transportes - Sistema de Gestão
 */
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'config.php';
require_once 'Database.php';
$db = new Database();
$currentPage = 'pedido.php';

// AJAX: Listar pedidos
if (isset($_GET['ajax']) && $_GET['ajax'] === 'list') {
    header('Content-Type: application/json');
    try {
        $search = $_GET['search'] ?? '';
        $sortCol = $_GET['sort'] ?? 'remessa_id';
        $sortDir = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $allowedCols = ['remessa_id','data_viagem','cliente_nome','remessa_pacote_qde','remessa_total','remessa_situacao_descricao','remessa_ordem'];
        if (!in_array($sortCol, $allowedCols)) $sortCol = 'remessa_id';

        $limit  = max(1, min(200, (int) ($_GET['limit']  ?? 25)));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));

        $params = [];
        $where  = "FROM v_remessa WHERE remessa_cliente_id NOT IN (120,197)";

        if (!empty($search)) {
            $where .= " AND (cliente_nome LIKE :s1 OR CAST(remessa_id AS CHAR) LIKE :s2 OR motorista_nome LIKE :s3)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
        }

        $total = (int) $db->queryOne("SELECT COUNT(*) as n {$where}", $params)['n'];

        $sql = "SELECT remessa_id, remessa_descricao, remessa_pacote_qde, remessa_pacote_valor,
                remessa_fardo_qde, remessa_fardo_valor, remessa_forma_pagamento_id,
                remessa_cliente_id, remessa_remessa_situacao_id, remessa_motorista_id,
                remessa_data_remessa, remessa_outros_valores, remessa_total,
                cliente_nome, cliente_fone, cliente_endereco, cliente_coordenadas,
                motorista_nome, motorista_fone, carro_descricao,
                forma_pgto_descricao, remessa_situacao_descricao,
                viagem_id, data_viagem, cliente_cidade_id,
                viagem_remessa_situacao_id, remessa_ordem
                {$where} ORDER BY {$sortCol} {$sortDir} LIMIT {$limit} OFFSET {$offset}";
        $pedidos = $db->query($sql, $params);
        echo json_encode(['success' => true, 'data' => $pedidos, 'total' => $total]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Buscar pedido por ID
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get') {
    header('Content-Type: application/json');
    try {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) throw new Exception('ID inválido');
        $pedido = $db->queryOne("SELECT remessa_id, remessa_descricao, remessa_pacote_qde, remessa_pacote_valor,
            remessa_fardo_qde, remessa_fardo_valor, remessa_forma_pagamento_id,
            remessa_cliente_id, remessa_remessa_situacao_id, remessa_motorista_id,
            remessa_data_remessa, remessa_outros_valores, remessa_total,
            cliente_nome, cliente_fone, cliente_endereco, cliente_coordenadas,
            motorista_nome, forma_pgto_descricao, remessa_situacao_descricao,
            viagem_id, data_viagem, remessa_ordem
            FROM v_remessa WHERE remessa_id = ?", [$id]);
        if (!$pedido) throw new Exception('Pedido não encontrado');
        // Get coordinates from remessa table
        $coords = $db->queryOne("SELECT latitude, longitude, coordenadas FROM remessa WHERE id = ?", [$id]);
        if ($coords) { $pedido['latitude'] = $coords['latitude']; $pedido['longitude'] = $coords['longitude']; $pedido['coordenadas'] = $coords['coordenadas']; }
        echo json_encode(['success' => true, 'data' => $pedido]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Listar viagens para select
if (isset($_GET['ajax']) && $_GET['ajax'] === 'viagens') {
    header('Content-Type: application/json');
    try {
        $viagens = $db->query("SELECT v.id, v.data_viagem, m.nome as motorista_nome, c.descricao as carro_descricao
            FROM viagem v LEFT JOIN prod_vt.motorista m ON m.id = v.motorista_id
            LEFT JOIN carro c ON c.id = m.carro_id
            ORDER BY v.data_viagem DESC LIMIT 100");
        echo json_encode(['success' => true, 'data' => $viagens]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Listar clientes para select
if (isset($_GET['ajax']) && $_GET['ajax'] === 'clientes') {
    header('Content-Type: application/json');
    try {
        $search = $_GET['search'] ?? '';
        $params = [];
        $sql = "SELECT id, nome, fone, endereco, coordenadas, latitude, longitude FROM cliente WHERE id NOT IN (120,197)";
        if (!empty($search)) {
            $sql .= " AND (nome LIKE :s1 OR CAST(id AS CHAR) LIKE :s2)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
        }
        $sql .= " ORDER BY nome LIMIT 50";
        $clientes = $db->query($sql, $params);
        echo json_encode(['success' => true, 'data' => $clientes]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Listar situações
if (isset($_GET['ajax']) && $_GET['ajax'] === 'situacoes') {
    header('Content-Type: application/json');
    try {
        $sit = $db->query("SELECT id, descricao FROM remessa_situacao ORDER BY descricao");
        echo json_encode(['success' => true, 'data' => $sit]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Listar formas de pagamento
if (isset($_GET['ajax']) && $_GET['ajax'] === 'formas_pgto') {
    header('Content-Type: application/json');
    try {
        $fp = $db->query("SELECT id, descricao FROM forma_pgto ORDER BY descricao");
        echo json_encode(['success' => true, 'data' => $fp]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Listar motoristas
if (isset($_GET['ajax']) && $_GET['ajax'] === 'motoristas') {
    header('Content-Type: application/json');
    try {
        $mot = $db->query("SELECT id, nome FROM prod_vt.motorista WHERE situacao = 'a' ORDER BY nome");
        echo json_encode(['success' => true, 'data' => $mot]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Criar pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'create') {
    header('Content-Type: application/json');
    try {
        $viagem_id = intval($_POST['viagem_id'] ?? 0);
        $cliente_id = intval($_POST['cliente_id'] ?? 0);
        $motorista_id = intval($_POST['motorista_id'] ?? 0);
        $remessa_situacao_id = intval($_POST['remessa_situacao_id'] ?? 0);
        $forma_pagamento_id = intval($_POST['forma_pagamento_id'] ?? 0);
        $descricao = trim($_POST['descricao'] ?? '');
        $data_remessa = trim($_POST['data_remessa'] ?? date('Y-m-d'));
        $pacote_qde = intval($_POST['pacote_qde'] ?? 0);
        $pacote_valor = floatval($_POST['pacote_valor'] ?? 0);
        $total = floatval($_POST['total'] ?? 0);
        $outros_valores = floatval($_POST['outros_valores'] ?? 0);
        $latitude = trim($_POST['latitude'] ?? '') !== '' ? floatval($_POST['latitude']) : null;
        $longitude = trim($_POST['longitude'] ?? '') !== '' ? floatval($_POST['longitude']) : null;
        $coordenadas = trim($_POST['coordenadas'] ?? '');

        if (!$viagem_id) throw new Exception('Viagem é obrigatória');
        if (!$cliente_id) throw new Exception('Cliente é obrigatório');

        // Get motorista from viagem if not provided
        if (!$motorista_id) {
            $v = $db->queryOne("SELECT motorista_id FROM viagem WHERE id = ?", [$viagem_id]);
            if ($v) $motorista_id = $v['motorista_id'];
        }

        $sql = "INSERT INTO remessa (viagem_id, cliente_id, motorista_id, remessa_situacao_id, forma_pagamento_id,
                descricao, data_remessa, pacote_qde, pacote_valor, total, outros_valores, latitude, longitude, coordenadas)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $db->execute($sql, [$viagem_id, $cliente_id, $motorista_id, $remessa_situacao_id ?: null,
            $forma_pagamento_id ?: null, $descricao, $data_remessa, $pacote_qde, $pacote_valor,
            $total, $outros_valores, $latitude, $longitude, $coordenadas]);
        $newId = $db->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Pedido criado com sucesso!', 'id' => $newId]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Atualizar pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'update') {
    header('Content-Type: application/json');
    try {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) throw new Exception('ID inválido');
        $viagem_id = intval($_POST['viagem_id'] ?? 0);
        $cliente_id = intval($_POST['cliente_id'] ?? 0);
        $motorista_id = intval($_POST['motorista_id'] ?? 0);
        $remessa_situacao_id = intval($_POST['remessa_situacao_id'] ?? 0);
        $forma_pagamento_id = intval($_POST['forma_pagamento_id'] ?? 0);
        $descricao = trim($_POST['descricao'] ?? '');
        $data_remessa = trim($_POST['data_remessa'] ?? '');
        $pacote_qde = intval($_POST['pacote_qde'] ?? 0);
        $pacote_valor = floatval($_POST['pacote_valor'] ?? 0);
        $total = floatval($_POST['total'] ?? 0);
        $outros_valores = floatval($_POST['outros_valores'] ?? 0);
        $latitude = trim($_POST['latitude'] ?? '') !== '' ? floatval($_POST['latitude']) : null;
        $longitude = trim($_POST['longitude'] ?? '') !== '' ? floatval($_POST['longitude']) : null;
        $coordenadas = trim($_POST['coordenadas'] ?? '');

        if (!$viagem_id) throw new Exception('Viagem é obrigatória');
        if (!$cliente_id) throw new Exception('Cliente é obrigatório');

        $sql = "UPDATE remessa SET viagem_id=?, cliente_id=?, motorista_id=?, remessa_situacao_id=?,
                forma_pagamento_id=?, descricao=?, data_remessa=?, pacote_qde=?, pacote_valor=?,
                total=?, outros_valores=?, latitude=?, longitude=?, coordenadas=? WHERE id=?";
        $db->execute($sql, [$viagem_id, $cliente_id, $motorista_id, $remessa_situacao_id ?: null,
            $forma_pagamento_id ?: null, $descricao, $data_remessa, $pacote_qde, $pacote_valor,
            $total, $outros_valores, $latitude, $longitude, $coordenadas, $id]);
        echo json_encode(['success' => true, 'message' => 'Pedido atualizado com sucesso!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Deletar pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'delete') {
    header('Content-Type: application/json');
    try {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) throw new Exception('ID inválido');
        $db->execute("DELETE FROM remessa WHERE id = ?", [$id]);
        echo json_encode(['success' => true, 'message' => 'Pedido excluído com sucesso!']);
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
    <title>Pedidos - Victor Transportes</title>
    <style>
        :root {
            --primary: #1F6F54; --primary-light: #2F8F6B; --primary-bg: #E8F4EF;
            --secondary: #3B82F6; --success: #22C55E; --warning: #F59E0B; --danger: #EF4444;
            --bg: #F6F8F9; --card: #ffffff; --text: #1F2933; --text-muted: #6B7280; --border: #E5E7EB;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
        .main-content { padding:20px; width:100%; }

        /* Header */
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px; }
        .page-header h1 { font-size:1.5rem; font-weight:600; color:var(--primary); display:flex; align-items:center; gap:10px; }

        /* Buttons */
        .btn { padding:10px 20px; border:none; border-radius:8px; font-size:0.9rem; font-weight:600; cursor:pointer; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
        .btn-primary { background:var(--primary); color:white; }
        .btn-primary:hover { background:var(--primary-light); }
        .btn-secondary { background:#e9ecef; color:var(--text); }
        .btn-danger { background:var(--danger); color:white; }
        .btn-sm { padding:6px 12px; font-size:0.8rem; }
        .btn-outline { background:transparent; border:2px solid var(--primary); color:var(--primary); }
        .btn-outline:hover { background:var(--primary); color:white; }

        /* Filter Bar */
        .filter-bar { background:var(--card); padding:16px; border-radius:12px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.05); display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
        .filter-bar input { flex:1; min-width:200px; padding:10px 14px; border:1px solid var(--border); border-radius:8px; font-size:0.9rem; }
        .filter-bar input:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(31,111,84,0.1); }

        /* Table */
        .table-container { background:var(--card); border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); overflow:hidden; }
        table { width:100%; border-collapse:collapse; }
        th,td { padding:12px 14px; text-align:left; border-bottom:1px solid var(--border); }
        th { background:var(--primary); color:white; font-weight:600; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.5px; cursor:pointer; user-select:none; transition:background 0.2s; }
        th:hover { background:var(--primary-light); }
        th.sorted { background:var(--primary-light); }
        th .sort-icon { margin-left:4px; opacity:0.7; }
        th.sorted .sort-icon { opacity:1; }
        tr:hover { background:#f8f9fa; }

        /* Badges */
        .badge { display:inline-block; padding:4px 10px; border-radius:20px; font-size:0.72rem; font-weight:600; }
        .badge-info { background:#dbeafe; color:#1e40af; }
        .badge-success { background:#d1fae5; color:#065f46; }
        .badge-warning { background:#fef3c7; color:#92400e; }
        .pedido-badge { display:inline-flex; align-items:center; justify-content:center; min-width:28px; height:24px; padding:0 8px; border-radius:12px; font-size:0.8rem; font-weight:700; background:var(--primary-bg); color:var(--primary); }

        /* Actions */
        .actions { display:flex; gap:6px; }
        .action-btn { width:30px; height:30px; border:none; border-radius:6px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.2s; font-size:0.9rem; }
        .action-btn.edit { background:#dbeafe; color:#1d4ed8; }
        .action-btn.edit:hover { background:#bfdbfe; }
        .action-btn.delete { background:#fee2e2; color:#dc2626; }
        .action-btn.delete:hover { background:#fecaca; }

        /* Form View */
        .form-view { display:none; }
        .form-view.active { display:block; }
        .grid-view.hidden { display:none; }
        .form-card { background:var(--card); border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); margin-bottom:20px; overflow:hidden; }
        .form-card-header { background:var(--primary); color:white; padding:14px 24px; font-weight:600; font-size:1rem; text-align:center; }
        .form-card-toolbar { display:flex; justify-content:center; gap:12px; padding:16px 24px; border-bottom:1px solid var(--border); flex-wrap:wrap; }
        .form-card-toolbar .right { position:absolute; right:24px; }
        .form-card-body { padding:24px; }

        .section-title { font-size:0.85rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted); margin:20px 0 12px; padding-bottom:6px; border-bottom:1px solid var(--border); }
        .section-title:first-child { margin-top:0; }

        .form-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(250px,1fr)); gap:16px; }
        .form-group { margin-bottom:0; }
        .form-group label { display:block; margin-bottom:4px; font-weight:500; font-size:0.82rem; color:var(--text-muted); text-transform:uppercase; }
        .form-group label .req { color:var(--danger); }
        .form-group input, .form-group select { width:100%; padding:9px 12px; border:1px solid var(--border); border-radius:8px; font-size:0.9rem; }
        .form-group input:focus, .form-group select:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(31,111,84,0.1); }
        .form-group .input-row { display:flex; gap:8px; align-items:center; }
        .form-group .input-row input { flex:1; }

        .required-note { color:var(--danger); font-size:0.8rem; font-weight:600; margin-top:16px; }

        /* Toast */
        .toast { position:fixed; bottom:24px; right:24px; padding:14px 20px; border-radius:10px; color:white; font-weight:500; z-index:4000; transform:translateY(100px); opacity:0; transition:all 0.3s ease; }
        .toast.show { transform:translateY(0); opacity:1; }
        .toast.success { background:var(--success); }
        .toast.error { background:var(--danger); }

        .empty-state { text-align:center; padding:60px 20px; color:var(--text-muted); }
        .empty-state .icon { font-size:3rem; margin-bottom:16px; }
        .loading { text-align:center; padding:40px; color:var(--text-muted); }

        /* Delete Modal */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center; z-index:3000; padding:20px; }
        .modal-overlay.active { display:flex; }
        .modal { background:white; border-radius:16px; width:100%; max-width:400px; box-shadow:0 20px 60px rgba(0,0,0,0.2); }
        .modal-header { padding:20px 24px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; }
        .modal-header h2 { font-size:1.2rem; font-weight:600; }
        .modal-close { background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted); }
        .modal-body { padding:24px; }
        .modal-footer { padding:16px 24px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:12px; }

        /* Client search */
        .client-search-results { position:absolute; z-index:100; background:white; border:1px solid var(--border); border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.15); max-height:200px; overflow-y:auto; width:100%; display:none; }
        .client-search-results.active { display:block; }
        .client-search-item { padding:8px 12px; cursor:pointer; font-size:0.85rem; border-bottom:1px solid #f0f0f0; }
        .client-search-item:hover { background:var(--primary-bg); }
        .client-search-item .name { font-weight:600; }
        .client-search-item .detail { font-size:0.75rem; color:var(--text-muted); }

        @media (max-width:768px) {
            .main-content { padding:16px; }
            .page-header { flex-direction:column; align-items:flex-start; }
            .filter-bar { flex-direction:column; }
            .table-container { overflow-x:auto; }
            table { min-width:800px; }
            .form-grid { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content page-with-sidebar">
        <!-- GRID VIEW -->
        <div class="grid-view" id="gridView">
            <div class="page-header">
                <h1>📦 Relatório - Pedidos</h1>
                <button class="btn btn-primary" onclick="showForm()">➕ Novo Pedido</button>
            </div>
            <div class="filter-bar">
                <input type="text" id="searchInput" placeholder="🔍 Buscar por cliente, código ou motorista..." oninput="debounceSearch()">
                <select id="pageSizeSelect" onchange="onPageSizeChange()" style="padding:10px 14px;border:1px solid var(--border);border-radius:8px;font-size:0.9rem;">
                    <option value="10">10 / pág</option>
                    <option value="25" selected>25 / pág</option>
                    <option value="50">50 / pág</option>
                    <option value="100">100 / pág</option>
                </select>
                <button class="btn btn-secondary" onclick="loadPedidos()">🔄 Atualizar</button>
            </div>
            <div class="table-container">
                <table>
                    <thead><tr>
                        <th data-sort="remessa_id" onclick="sortBy('remessa_id')" style="width:70px">CÓD. <span class="sort-icon">↕</span></th>
                        <th>Cliente/Cidade</th>
                        <th style="width:120px">Contato</th>
                        <th data-sort="remessa_pacote_qde" onclick="sortBy('remessa_pacote_qde')" style="width:100px">Qde Pacotes <span class="sort-icon">↕</span></th>
                        <th data-sort="remessa_total" onclick="sortBy('remessa_total')" style="width:110px">Valor Total <span class="sort-icon">↕</span></th>
                        <th data-sort="remessa_situacao_descricao" onclick="sortBy('remessa_situacao_descricao')">Situação Pedido <span class="sort-icon">↕</span></th>
                        <th style="width:80px">Ações</th>
                    </tr></thead>
                    <tbody id="tableBody"><tr><td colspan="7" class="loading">🔄 Carregando...</td></tr></tbody>
                </table>
            </div>
            <div id="paginationBar" style="display:none; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; padding:12px 4px;">
                <span id="paginationInfo" style="font-size:0.85rem; color:#666;"></span>
                <div id="paginationControls" style="display:flex; gap:4px; flex-wrap:wrap;"></div>
            </div>
        </div>

        <!-- FORM VIEW -->
        <div class="form-view" id="formView">
            <div class="form-card">
                <div class="form-card-header" id="formHeader">CADASTRAR/ATUALIZAR - PEDIDO</div>
                <div class="form-card-toolbar" style="position:relative;">
                    <button class="btn btn-primary" onclick="savePedido()">💾 Salvar</button>
                    <button class="btn btn-danger" id="btnExcluir" onclick="openDeleteFromForm()" style="display:none">🗑️ Excluir</button>
                    <div style="position:absolute;right:24px;display:flex;gap:8px;">
                        <button class="btn btn-secondary" onclick="showGrid()">← Voltar</button>
                    </div>
                </div>
                <div class="form-card-body">
                    <input type="hidden" id="pedidoId">

                    <div class="section-title">Informações Gerais</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Viagem <span class="req">*</span></label>
                            <select id="f_viagem_id" onchange="onViagemChange()"><option value="">Selecione...</option></select>
                        </div>
                        <div class="form-group">
                            <label>Motorista <span class="req">*</span></label>
                            <select id="f_motorista_id"><option value="">Selecione...</option></select>
                        </div>
                    </div>
                    <div class="form-grid" style="margin-top:12px;">
                        <div class="form-group" style="position:relative;">
                            <label>Cliente <span class="req">*</span></label>
                            <div class="input-row">
                                <input type="hidden" id="f_cliente_id">
                                <input type="text" id="f_cliente_search" placeholder="Buscar cliente..." oninput="searchClientes()" onfocus="searchClientes()" autocomplete="off">
                                <button type="button" class="btn btn-sm btn-outline" onclick="searchClientes()">🔍 Buscar</button>
                            </div>
                            <div class="client-search-results" id="clienteResults"></div>
                        </div>
                        <div class="form-group">
                            <label>Descrição</label>
                            <input type="text" id="f_descricao">
                        </div>
                    </div>
                    <div class="form-grid" style="margin-top:12px;">
                        <div class="form-group">
                            <label>Situação <span class="req">*</span></label>
                            <select id="f_situacao_id"><option value="">Selecione...</option></select>
                        </div>
                        <div class="form-group">
                            <label>Forma Pgto <span class="req">*</span></label>
                            <select id="f_forma_pgto_id"><option value="">Selecione...</option></select>
                        </div>
                    </div>

                    <div class="section-title">Datas</div>
                    <div class="form-grid">
                        <div class="form-group"><label>Data</label><input type="date" id="f_data_remessa"></div>
                    </div>

                    <div class="section-title">Valores Totais</div>
                    <div class="form-grid">
                        <div class="form-group"><label>Qde Pacote</label><input type="number" id="f_pacote_qde" min="0" value="0"></div>
                        <div class="form-group"><label>Valor Pacote</label><input type="number" id="f_pacote_valor" step="0.01" min="0" value="0"></div>
                        <div class="form-group"><label>Total</label><input type="number" id="f_total" step="0.01" min="0" value="0"></div>
                        <div class="form-group"><label>Outros Valores</label><input type="number" id="f_outros_valores" step="0.01" min="0" value="0"></div>
                    </div>

                    <div class="section-title">Coordenadas</div>
                    <div class="form-grid">
                        <div class="form-group"><label>Coordenadas</label><input type="text" id="f_coordenadas" placeholder="https://maps.google.com/..."></div>
                        <div class="form-group"><label>Latitude</label><input type="text" id="f_latitude" placeholder="-8.034988"></div>
                        <div class="form-group"><label>Longitude</label><input type="text" id="f_longitude" placeholder="-34.974216"></div>
                        <div class="form-group"><label>Mapa</label><a href="#" id="f_mapa_link" target="_blank" class="btn btn-sm btn-outline" style="margin-top:4px">Google Maps</a></div>
                    </div>

                    <p class="required-note">* Campos obrigatórios</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Delete Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <div class="modal-header"><h2>Confirmar Exclusão</h2><button class="modal-close" onclick="closeDeleteModal()">×</button></div>
            <div class="modal-body"><p>Deseja realmente excluir o pedido <strong id="deleteInfo"></strong>?</p></div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
                <button class="btn btn-danger" onclick="confirmDelete()">🗑️ Excluir</button>
            </div>
        </div>
    </div>
    <div class="toast" id="toast"></div>

    <script>
    let currentSort = 'remessa_id', currentDir = 'DESC', searchTimeout = null, deleteId = null;
    let viagensCache=[], motoristasCache=[], situacoesCache=[], formasPgtoCache=[];

    // ── Paginação ─────────────────────────────────────────────────────────────
    let currentPage = 1, pageSize = 25, totalItems = 0;
    function readUrlParams() {
        const p = new URLSearchParams(location.search);
        currentPage = Math.max(1, parseInt(p.get('page') || '1'));
        pageSize    = [10,25,50,100].includes(parseInt(p.get('size'))) ? parseInt(p.get('size')) : 25;
        const sel = document.getElementById('pageSizeSelect');
        if (sel) sel.value = pageSize;
    }
    function syncUrl() {
        const p = new URLSearchParams(location.search);
        p.set('page', currentPage); p.set('size', pageSize);
        history.replaceState(null, '', '?' + p.toString());
    }
    function renderPagination() {
        const bar = document.getElementById('paginationBar');
        const info = document.getElementById('paginationInfo');
        const ctrl = document.getElementById('paginationControls');
        if (!totalItems) { bar.style.display = 'none'; return; }
        const totalPages = Math.ceil(totalItems / pageSize);
        const from = Math.min((currentPage-1)*pageSize+1, totalItems);
        const to   = Math.min(currentPage*pageSize, totalItems);
        bar.style.display = 'flex';
        info.textContent  = `${from}–${to} de ${totalItems} registros`;
        const bs = (active) => `style="padding:6px 10px;border:1px solid ${active?'#1F6F54':'#ddd'};border-radius:6px;background:${active?'#1F6F54':'#fff'};color:${active?'#fff':'#333'};font-size:0.82rem;cursor:pointer;font-weight:${active?'700':'400'};"`;
        let btns = `<button ${bs(false)} onclick="goPage(1)" ${currentPage===1?'disabled':''}>«</button>`;
        btns    += `<button ${bs(false)} onclick="goPage(${currentPage-1})" ${currentPage===1?'disabled':''}>‹</button>`;
        for (let i=Math.max(1,currentPage-2); i<=Math.min(totalPages,currentPage+2); i++)
            btns += `<button ${bs(i===currentPage)} onclick="goPage(${i})">${i}</button>`;
        btns += `<button ${bs(false)} onclick="goPage(${currentPage+1})" ${currentPage===totalPages?'disabled':''}>›</button>`;
        btns += `<button ${bs(false)} onclick="goPage(${totalPages})" ${currentPage===totalPages?'disabled':''}>»</button>`;
        ctrl.innerHTML = btns;
    }
    function goPage(p) {
        const tp = Math.ceil(totalItems/pageSize);
        p = Math.max(1, Math.min(tp, p));
        if (p === currentPage) return;
        currentPage = p; syncUrl(); loadPedidos();
    }
    function onPageSizeChange() {
        pageSize = parseInt(document.getElementById('pageSizeSelect').value);
        currentPage = 1; syncUrl(); loadPedidos();
    }
    // ─────────────────────────────────────────────────────────────────────────

    document.addEventListener('DOMContentLoaded', () => { readUrlParams(); loadPedidos(); loadFormData(); });
    document.addEventListener('click', e => { if (!e.target.closest('.form-group')) document.getElementById('clienteResults').classList.remove('active'); });

    async function loadFormData() {
        try {
            const [vRes,mRes,sRes,fRes] = await Promise.all([
                fetch('pedido.php?ajax=viagens'), fetch('pedido.php?ajax=motoristas'),
                fetch('pedido.php?ajax=situacoes'), fetch('pedido.php?ajax=formas_pgto')
            ]);
            const [vD,mD,sD,fD] = await Promise.all([vRes.json(),mRes.json(),sRes.json(),fRes.json()]);
            if(vD.success) { viagensCache=vD.data; populateViagens(); }
            if(mD.success) { motoristasCache=mD.data; populateSelect('f_motorista_id',mD.data,'id','nome'); }
            if(sD.success) { situacoesCache=sD.data; populateSelect('f_situacao_id',sD.data,'id','descricao'); }
            if(fD.success) { formasPgtoCache=fD.data; populateSelect('f_forma_pgto_id',fD.data,'id','descricao'); }
        } catch(e) { console.error(e); }
    }

    function populateViagens() {
        const sel = document.getElementById('f_viagem_id');
        sel.innerHTML = '<option value="">Selecione a viagem...</option>' +
            viagensCache.map(v => {
                const dt = v.data_viagem ? formatDate(v.data_viagem) : '';
                return `<option value="${v.id}">D: ${v.id}-${dt} | M: -${esc(v.motorista_nome||'')}- | C: ${esc(v.carro_descricao||'')}</option>`;
            }).join('');
    }

    function populateSelect(id, data, valKey, lblKey) {
        const sel = document.getElementById(id);
        sel.innerHTML = '<option value="">Selecione...</option>' + data.map(d => `<option value="${d[valKey]}">${esc(d[lblKey])}</option>`).join('');
    }

    function onViagemChange() {
        const vid = document.getElementById('f_viagem_id').value;
        const v = viagensCache.find(x => x.id == vid);
        if (v && v.motorista_nome) {
            const m = motoristasCache.find(x => x.nome === v.motorista_nome);
            if (m) document.getElementById('f_motorista_id').value = m.id;
        }
    }

    async function searchClientes() {
        const q = document.getElementById('f_cliente_search').value;
        if (q.length < 2) { document.getElementById('clienteResults').classList.remove('active'); return; }
        try {
            const res = await fetch(`pedido.php?ajax=clientes&search=${encodeURIComponent(q)}`);
            const data = await res.json();
            if (!data.success) return;
            const el = document.getElementById('clienteResults');
            el.innerHTML = data.data.map(c => `<div class="client-search-item" onclick="selectCliente(${c.id},'${esc(c.nome)}','${c.latitude||''}','${c.longitude||''}','${esc(c.coordenadas||'')}')">
                <div class="name">${esc(c.nome)}</div><div class="detail">${esc(c.fone||'')} - ${esc(c.endereco||'')}</div></div>`).join('');
            el.classList.add('active');
        } catch(e) { console.error(e); }
    }

    function selectCliente(id, nome, lat, lng, coords) {
        document.getElementById('f_cliente_id').value = id;
        document.getElementById('f_cliente_search').value = nome;
        document.getElementById('clienteResults').classList.remove('active');
        if (lat) document.getElementById('f_latitude').value = lat;
        if (lng) document.getElementById('f_longitude').value = lng;
        if (coords) document.getElementById('f_coordenadas').value = coords;
        updateMapLink();
    }

    function updateMapLink() {
        const lat = document.getElementById('f_latitude').value;
        const lng = document.getElementById('f_longitude').value;
        const link = document.getElementById('f_mapa_link');
        if (lat && lng) { link.href = `https://maps.google.com/?q=${lat},${lng}`; link.style.pointerEvents='auto'; }
        else { link.href='#'; link.style.pointerEvents='none'; }
    }

    async function loadPedidos() {
        const search = document.getElementById('searchInput').value;
        const tbody  = document.getElementById('tableBody');
        tbody.innerHTML = '<tr><td colspan="7" class="loading">🔄 Carregando...</td></tr>';
        const offset = (currentPage - 1) * pageSize;
        try {
            const url = `pedido.php?ajax=list&search=${encodeURIComponent(search)}&sort=${currentSort}&dir=${currentDir}&limit=${pageSize}&offset=${offset}`;
            const res  = await fetch(url);
            const data = await res.json();
            if (!data.success) throw new Error(data.error);

            totalItems = data.total;
            renderPagination();
            syncUrl();

            if (!data.data.length) { tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state"><div class="icon">📦</div><p>Nenhum pedido encontrado</p></div></td></tr>'; return; }
            tbody.innerHTML = data.data.map(p => `<tr>
                <td><strong>${p.remessa_id}</strong></td>
                <td><strong>${esc(p.cliente_nome)}</strong><br><span style="font-size:0.8rem;color:var(--text-muted)">${esc(p.cliente_endereco||'')}</span></td>
                <td>${esc(p.cliente_fone||'-')}</td>
                <td><span class="pedido-badge">${p.remessa_pacote_qde||0}</span></td>
                <td><strong>${formatMoney(p.remessa_total)}</strong></td>
                <td><span class="badge badge-info">${esc(p.remessa_situacao_descricao||'')}</span></td>
                <td><div class="actions">
                    <button class="action-btn edit" onclick="editPedido(${p.remessa_id})" title="Editar">✏️</button>
                    <button class="action-btn delete" onclick="openDeleteModal(${p.remessa_id},'#${p.remessa_id} - ${esc(p.cliente_nome)}')" title="Excluir">🗑️</button>
                </div></td>
            </tr>`).join('');
            updateSortHeaders();
        } catch(e) { tbody.innerHTML = '<tr><td colspan="7" class="empty-state">❌ Erro ao carregar</td></tr>'; showToast(e.message,'error'); }
    }

    function sortBy(col) { if(currentSort===col) currentDir=currentDir==='ASC'?'DESC':'ASC'; else { currentSort=col; currentDir='DESC'; } currentPage=1; loadPedidos(); }
    function updateSortHeaders() { document.querySelectorAll('th[data-sort]').forEach(th => { th.classList.remove('sorted'); const i=th.querySelector('.sort-icon'); if(i) i.textContent='↕'; }); const a=document.querySelector(`th[data-sort="${currentSort}"]`); if(a){a.classList.add('sorted'); const i=a.querySelector('.sort-icon'); if(i) i.textContent=currentDir==='ASC'?'↑':'↓'; } }
    function debounceSearch() { clearTimeout(searchTimeout); searchTimeout=setTimeout(()=>{currentPage=1;loadPedidos();},300); }

    function showForm(id=null) {
        document.getElementById('gridView').classList.add('hidden');
        document.getElementById('formView').classList.add('active');
        resetForm();
        if (!id) {
            document.getElementById('formHeader').textContent = 'CADASTRAR - PEDIDO';
            document.getElementById('btnExcluir').style.display = 'none';
            document.getElementById('f_data_remessa').value = new Date().toISOString().split('T')[0];
        }
    }
    function showGrid() {
        document.getElementById('formView').classList.remove('active');
        document.getElementById('gridView').classList.remove('hidden');
        loadPedidos();
    }
    function resetForm() {
        document.getElementById('pedidoId').value='';
        ['f_viagem_id','f_motorista_id','f_situacao_id','f_forma_pgto_id'].forEach(id=>document.getElementById(id).value='');
        ['f_cliente_id'].forEach(id=>document.getElementById(id).value='');
        ['f_cliente_search','f_descricao','f_data_remessa','f_coordenadas','f_latitude','f_longitude'].forEach(id=>document.getElementById(id).value='');
        ['f_pacote_qde','f_pacote_valor','f_total','f_outros_valores'].forEach(id=>document.getElementById(id).value='0');
        updateMapLink();
    }

    async function editPedido(id) {
        try {
            const res = await fetch(`pedido.php?ajax=get&id=${id}`);
            const data = await res.json();
            if (!data.success) throw new Error(data.error);
            const p = data.data;
            showForm(id);
            document.getElementById('formHeader').textContent = 'CADASTRAR/ATUALIZAR - PEDIDO';
            document.getElementById('btnExcluir').style.display = '';
            document.getElementById('pedidoId').value = p.remessa_id;
            document.getElementById('f_viagem_id').value = p.viagem_id || '';
            document.getElementById('f_motorista_id').value = p.remessa_motorista_id || '';
            document.getElementById('f_cliente_id').value = p.remessa_cliente_id || '';
            document.getElementById('f_cliente_search').value = p.cliente_nome || '';
            document.getElementById('f_descricao').value = p.remessa_descricao || '';
            document.getElementById('f_situacao_id').value = p.remessa_remessa_situacao_id || '';
            document.getElementById('f_forma_pgto_id').value = p.remessa_forma_pagamento_id || '';
            document.getElementById('f_data_remessa').value = p.remessa_data_remessa || '';
            document.getElementById('f_pacote_qde').value = p.remessa_pacote_qde || 0;
            document.getElementById('f_pacote_valor').value = p.remessa_pacote_valor || 0;
            document.getElementById('f_total').value = p.remessa_total || 0;
            document.getElementById('f_outros_valores').value = p.remessa_outros_valores || 0;
            document.getElementById('f_latitude').value = p.latitude || '';
            document.getElementById('f_longitude').value = p.longitude || '';
            document.getElementById('f_coordenadas').value = p.coordenadas || '';
            updateMapLink();
        } catch(e) { showToast(e.message,'error'); }
    }

    async function savePedido() {
        const fd = new FormData();
        const id = document.getElementById('pedidoId').value;
        fd.append('ajax', id ? 'update' : 'create');
        if (id) fd.append('id', id);
        fd.append('viagem_id', document.getElementById('f_viagem_id').value);
        fd.append('cliente_id', document.getElementById('f_cliente_id').value);
        fd.append('motorista_id', document.getElementById('f_motorista_id').value);
        fd.append('remessa_situacao_id', document.getElementById('f_situacao_id').value);
        fd.append('forma_pagamento_id', document.getElementById('f_forma_pgto_id').value);
        fd.append('descricao', document.getElementById('f_descricao').value);
        fd.append('data_remessa', document.getElementById('f_data_remessa').value);
        fd.append('pacote_qde', document.getElementById('f_pacote_qde').value);
        fd.append('pacote_valor', document.getElementById('f_pacote_valor').value);
        fd.append('total', document.getElementById('f_total').value);
        fd.append('outros_valores', document.getElementById('f_outros_valores').value);
        fd.append('latitude', document.getElementById('f_latitude').value);
        fd.append('longitude', document.getElementById('f_longitude').value);
        fd.append('coordenadas', document.getElementById('f_coordenadas').value);

        try {
            const res = await fetch('pedido.php', {method:'POST', body:fd});
            const data = await res.json();
            if (!data.success) throw new Error(data.error);
            showToast(data.message,'success');
            if (!id && data.id) { document.getElementById('pedidoId').value = data.id; document.getElementById('btnExcluir').style.display=''; document.getElementById('formHeader').textContent='CADASTRAR/ATUALIZAR - PEDIDO'; }
        } catch(e) { showToast(e.message,'error'); }
    }

    function openDeleteModal(id, info) { deleteId=id; document.getElementById('deleteInfo').textContent=info; document.getElementById('deleteModal').classList.add('active'); }
    function openDeleteFromForm() { const id=document.getElementById('pedidoId').value; if(id) openDeleteModal(id,'#'+id); }
    function closeDeleteModal() { document.getElementById('deleteModal').classList.remove('active'); deleteId=null; }
    async function confirmDelete() {
        if (!deleteId) return;
        try {
            const fd=new FormData(); fd.append('ajax','delete'); fd.append('id',deleteId);
            const res=await fetch('pedido.php',{method:'POST',body:fd});
            const data=await res.json();
            if(!data.success) throw new Error(data.error);
            showToast(data.message,'success'); closeDeleteModal();
            if(document.getElementById('formView').classList.contains('active')) showGrid(); else loadPedidos();
        } catch(e) { showToast(e.message,'error'); }
    }

    function showToast(msg,type='success') { const t=document.getElementById('toast'); t.textContent=msg; t.className=`toast ${type} show`; setTimeout(()=>t.classList.remove('show'),3000); }
    function esc(t) { if(!t) return ''; const d=document.createElement('div'); d.textContent=t; return d.innerHTML; }
    function formatDate(s) { if(!s) return ''; const p=s.split('-'); return p.length===3?`${p[2]}/${p[1]}/${p[0]}`:s; }
    function formatMoney(v) { return parseFloat(v||0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'}); }
    </script>
</body>
</html>
