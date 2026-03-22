<?php
/**
 * CRUD de Fornecedores
 * Victor Transportes - Sistema de Gestão
 */
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'config.php';
require_once 'Database.php';
$db = new Database();
$currentPage = 'fornecedor.php';

// AJAX: Listar fornecedores
if (isset($_GET['ajax']) && $_GET['ajax'] === 'list') {
    header('Content-Type: application/json');
    try {
        $search = $_GET['search'] ?? '';
        $sortCol = $_GET['sort'] ?? 'descricao';
        $sortDir = strtoupper($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $allowedCols = ['id', 'descricao', 'contato_nome', 'contato_fone', 'situacao', 'endereco'];
        if (!in_array($sortCol, $allowedCols)) $sortCol = 'descricao';

        $limit    = max(1, min(200, (int) ($_GET['limit']    ?? 25)));
        $offset   = max(0, (int) ($_GET['offset']   ?? 0));
        $situacao = $_GET['situacao'] ?? '';

        $params = [];
        $where  = "FROM prod_vt.fornecedor WHERE 1=1";

        if (!empty($search)) {
            $where .= " AND (descricao LIKE :s1 OR contato_nome LIKE :s2 OR contato_fone LIKE :s3 OR endereco LIKE :s4 OR CAST(id AS CHAR) LIKE :s5)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
            $params[':s4'] = "%{$search}%";
            $params[':s5'] = "%{$search}%";
        }
        if (!empty($situacao)) {
            $where .= " AND (situacao = :sit OR (situacao IS NULL AND :sit2 = 'Ativo'))";
            $params[':sit']  = $situacao;
            $params[':sit2'] = $situacao;
        }

        $total = (int) $db->queryOne("SELECT COUNT(*) as n {$where}", $params)['n'];

        $sql = "SELECT id, descricao, contato_nome, contato_fone, situacao, endereco
                {$where} ORDER BY {$sortCol} {$sortDir} LIMIT {$limit} OFFSET {$offset}";
        $fornecedores = $db->query($sql, $params);
        echo json_encode(['success' => true, 'data' => $fornecedores, 'total' => $total]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Buscar fornecedor por ID
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get') {
    header('Content-Type: application/json');
    try {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) throw new Exception('ID inválido');
        $fornecedor = $db->queryOne("SELECT id, descricao, contato_nome, contato_fone, situacao, endereco
                                     FROM prod_vt.fornecedor WHERE id = ?", [$id]);
        if (!$fornecedor) throw new Exception('Fornecedor não encontrado');
        echo json_encode(['success' => true, 'data' => $fornecedor]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Criar fornecedor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'create') {
    header('Content-Type: application/json');
    try {
        $descricao = trim($_POST['descricao'] ?? '');
        $contato_nome = trim($_POST['contato_nome'] ?? '');
        $contato_fone = trim($_POST['contato_fone'] ?? '');
        $situacao = trim($_POST['situacao'] ?? 'Ativo');
        $endereco = trim($_POST['endereco'] ?? '');

        if (empty($descricao)) throw new Exception('Estabelecimento é obrigatório');

        $sql = "INSERT INTO prod_vt.fornecedor (descricao, contato_nome, contato_fone, situacao, endereco)
                VALUES (?, ?, ?, ?, ?)";
        $db->execute($sql, [$descricao, $contato_nome, $contato_fone, $situacao, $endereco]);
        $newId = $db->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Fornecedor criado com sucesso!', 'id' => $newId]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Atualizar fornecedor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'update') {
    header('Content-Type: application/json');
    try {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) throw new Exception('ID inválido');

        $descricao = trim($_POST['descricao'] ?? '');
        $contato_nome = trim($_POST['contato_nome'] ?? '');
        $contato_fone = trim($_POST['contato_fone'] ?? '');
        $situacao = trim($_POST['situacao'] ?? 'Ativo');
        $endereco = trim($_POST['endereco'] ?? '');

        if (empty($descricao)) throw new Exception('Estabelecimento é obrigatório');

        $sql = "UPDATE prod_vt.fornecedor SET descricao=?, contato_nome=?, contato_fone=?, situacao=?, endereco=? WHERE id=?";
        $db->execute($sql, [$descricao, $contato_nome, $contato_fone, $situacao, $endereco, $id]);
        echo json_encode(['success' => true, 'message' => 'Fornecedor atualizado com sucesso!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Deletar fornecedor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'delete') {
    header('Content-Type: application/json');
    try {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) throw new Exception('ID inválido');
        // Soft delete - muda situação para Inativo
        $db->execute("UPDATE prod_vt.fornecedor SET situacao = 'Inativo' WHERE id = ?", [$id]);
        echo json_encode(['success' => true, 'message' => 'Fornecedor inativado com sucesso!']);
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
    <title>Fornecedores - Victor Transportes</title>
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
        .btn { padding:10px 20px; border:none; border-radius:8px; font-size:0.9rem; font-weight:600; cursor:pointer; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; text-decoration:none; }
        .btn-primary { background:var(--primary); color:white; }
        .btn-primary:hover { background:var(--primary-light); }
        .btn-secondary { background:#e9ecef; color:var(--text); }
        .btn-danger { background:var(--danger); color:white; }
        .btn-sm { padding:6px 12px; font-size:0.8rem; }

        /* Filter Bar */
        .filter-bar { background:var(--card); padding:16px; border-radius:12px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.05); display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
        .filter-bar input, .filter-bar select { flex:1; min-width:200px; padding:10px 14px; border:1px solid var(--border); border-radius:8px; font-size:0.9rem; }
        .filter-bar input:focus, .filter-bar select:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(31,111,84,0.1); }
        .filter-bar select { flex:0; min-width:140px; }
        .counter-badge { background:var(--primary-bg); color:var(--primary); padding:6px 14px; border-radius:20px; font-size:0.85rem; font-weight:600; white-space:nowrap; }

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
        tr:nth-child(even) { background:#fafbfc; }
        tr:nth-child(even):hover { background:#f0f1f2; }

        /* Badges */
        .badge { display:inline-block; padding:4px 10px; border-radius:20px; font-size:0.72rem; font-weight:600; }
        .badge-success { background:#d1fae5; color:#065f46; }
        .badge-danger { background:#fee2e2; color:#991b1b; }

        /* Actions */
        .actions { display:flex; gap:6px; align-items:center; }
        .action-btn { width:32px; height:32px; border:none; border-radius:6px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.2s; font-size:1rem; }
        .action-btn.edit { background:#dbeafe; color:#1d4ed8; }
        .action-btn.edit:hover { background:#bfdbfe; }
        .action-btn.delete { background:#fee2e2; color:#dc2626; }
        .action-btn.delete:hover { background:#fecaca; }

        /* Form View */
        .form-view { display:none; }
        .form-view.active { display:block; }
        .grid-view.hidden { display:none; }
        .form-card { background:var(--card); border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); margin-bottom:20px; overflow:hidden; }
        .form-card-header { background:var(--primary); color:white; padding:14px 24px; font-weight:600; font-size:1rem; text-transform:uppercase; letter-spacing:0.5px; }
        .form-card-toolbar { display:flex; justify-content:center; gap:12px; padding:16px 24px; border-bottom:1px solid var(--border); flex-wrap:wrap; position:relative; }
        .form-card-body { padding:24px; }
        .form-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(250px,1fr)); gap:16px; }
        .form-group { margin-bottom:0; }
        .form-group label { display:block; margin-bottom:4px; font-weight:500; font-size:0.82rem; color:var(--text-muted); text-transform:uppercase; }
        .form-group label .req { color:var(--danger); }
        .form-group input, .form-group select { width:100%; padding:9px 12px; border:1px solid var(--border); border-radius:8px; font-size:0.9rem; }
        .form-group input:focus, .form-group select:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(31,111,84,0.1); }
        .form-group .situacao-radios { display:flex; gap:16px; padding:8px 0; }
        .form-group .situacao-radios label { display:flex; align-items:center; gap:6px; cursor:pointer; font-size:0.9rem; text-transform:none; color:var(--text); }
        .required-note { color:var(--danger); font-size:0.8rem; font-weight:600; margin-top:16px; }
        .form-date { text-align:center; color:var(--text-muted); font-size:0.85rem; margin-top:12px; }

        /* Row number */
        .row-num { display:inline-flex; align-items:center; justify-content:center; min-width:28px; height:24px; padding:0 6px; border-radius:12px; font-size:0.75rem; font-weight:700; background:var(--primary-bg); color:var(--primary); }

        /* Modal */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center; z-index:3000; padding:20px; }
        .modal-overlay.active { display:flex; }
        .modal { background:white; border-radius:16px; width:100%; max-width:400px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.2); }
        .modal-header { padding:20px 24px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; }
        .modal-header h2 { font-size:1.2rem; font-weight:600; }
        .modal-close { background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted); }
        .modal-body { padding:24px; }
        .modal-footer { padding:16px 24px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:12px; }

        /* Toast */
        .toast { position:fixed; bottom:24px; right:24px; padding:14px 20px; border-radius:10px; color:white; font-weight:500; z-index:4000; transform:translateY(100px); opacity:0; transition:all 0.3s ease; }
        .toast.show { transform:translateY(0); opacity:1; }
        .toast.success { background:var(--success); }
        .toast.error { background:var(--danger); }

        .empty-state { text-align:center; padding:60px 20px; color:var(--text-muted); }
        .empty-state .icon { font-size:3rem; margin-bottom:16px; }
        .loading { text-align:center; padding:40px; color:var(--text-muted); }

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
                <h1>🏭 Relatório de Fornecedores</h1>
                <button class="btn btn-primary" onclick="showForm()">➕ Novo</button>
            </div>
            <div class="filter-bar">
                <input type="text" id="searchInput" placeholder="🔍 Busca Rápida..." oninput="debounceSearch()">
                <select id="filterSituacao" onchange="onFilterChange()">
                    <option value="">Todos</option>
                    <option value="Ativo" selected>Ativo</option>
                    <option value="Inativo">Inativo</option>
                </select>
                <select id="pageSizeSelect" onchange="onPageSizeChange()">
                    <option value="10">10 / pág</option>
                    <option value="25" selected>25 / pág</option>
                    <option value="50">50 / pág</option>
                    <option value="100">100 / pág</option>
                </select>
                <button class="btn btn-secondary" onclick="loadFornecedores()">🔄</button>
                <span class="counter-badge" id="counterBadge">0 registros</span>
            </div>
            <div class="table-container">
                <table>
                    <thead><tr>
                        <th data-sort="id" onclick="sortBy('id')" style="width:70px">CÓD. <span class="sort-icon">↕</span></th>
                        <th data-sort="descricao" onclick="sortBy('descricao')">ESTABELECIMENTO <span class="sort-icon">↕</span></th>
                        <th data-sort="contato_nome" onclick="sortBy('contato_nome')">NOME CONTATO <span class="sort-icon">↕</span></th>
                        <th data-sort="contato_fone" onclick="sortBy('contato_fone')" style="width:140px">FONE <span class="sort-icon">↕</span></th>
                        <th data-sort="situacao" onclick="sortBy('situacao')" style="width:100px">SITUAÇÃO <span class="sort-icon">↕</span></th>
                        <th style="width:100px;text-align:center">AÇÃO</th>
                    </tr></thead>
                    <tbody id="tableBody"><tr><td colspan="6" class="loading">🔄 Carregando...</td></tr></tbody>
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
                <div class="form-card-header" id="formHeader">CADASTRAR/ATUALIZAR - FORNECEDOR</div>
                <div class="form-card-toolbar">
                    <button class="btn btn-primary" id="btnIncluir" onclick="saveFornecedor()">➕ Incluir</button>
                    <button class="btn btn-danger" id="btnCancelar" onclick="showGrid()">🚫 Cancelar</button>
                    <div style="position:absolute;right:24px;display:flex;gap:8px;">
                        <button class="btn btn-secondary btn-sm" onclick="resetFormAndNew()">🔄</button>
                    </div>
                </div>
                <div class="form-card-body">
                    <input type="hidden" id="fornecedorId">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>ESTABELECIMENTO <span class="req">*</span></label>
                            <input type="text" id="f_descricao" placeholder="">
                        </div>
                        <div class="form-group">
                            <label>NOME CONTATO</label>
                            <input type="text" id="f_contato_nome" placeholder="">
                        </div>
                    </div>

                    <div class="form-grid" style="margin-top:12px;">
                        <div class="form-group">
                            <label>FONE</label>
                            <input type="text" id="f_contato_fone" placeholder="">
                        </div>
                        <div class="form-group">
                            <label>SITUAÇÃO</label>
                            <div class="situacao-radios">
                                <label><input type="radio" name="f_situacao" value="Ativo" checked> Ativo</label>
                                <label><input type="radio" name="f_situacao" value="Inativo"> Inativo</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid" style="margin-top:12px;">
                        <div class="form-group">
                            <label>ENDEREÇO</label>
                            <input type="text" id="f_endereco" placeholder="">
                        </div>
                    </div>

                    <div class="form-date" id="formDate"></div>

                    <p class="required-note">* Campos obrigatórios</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Delete Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <div class="modal-header"><h2>Confirmar Exclusão</h2><button class="modal-close" onclick="closeDeleteModal()">×</button></div>
            <div class="modal-body"><p>Deseja realmente inativar o fornecedor <strong id="deleteInfo"></strong>?</p></div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
                <button class="btn btn-danger" onclick="confirmDelete()">🗑️ Inativar</button>
            </div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script>
    let currentSort = 'descricao', currentDir = 'ASC', searchTimeout = null, deleteId = null;

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
        currentPage = p; syncUrl(); loadFornecedores();
    }
    function onPageSizeChange() {
        pageSize = parseInt(document.getElementById('pageSizeSelect').value);
        currentPage = 1; syncUrl(); loadFornecedores();
    }
    function onFilterChange() { currentPage = 1; loadFornecedores(); }
    // ─────────────────────────────────────────────────────────────────────────

    document.addEventListener('DOMContentLoaded', () => { readUrlParams(); loadFornecedores(); setFormDate(); });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeDeleteModal();
    });

    function setFormDate() {
        const d = new Date();
        document.getElementById('formDate').textContent =
            `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`;
    }

    async function loadFornecedores() {
        const search    = document.getElementById('searchInput').value;
        const sitFilter = document.getElementById('filterSituacao').value;
        const tbody     = document.getElementById('tableBody');
        tbody.innerHTML = '<tr><td colspan="6" class="loading">🔄 Carregando...</td></tr>';
        const offset = (currentPage - 1) * pageSize;
        try {
            const url = `fornecedor.php?ajax=list&search=${encodeURIComponent(search)}&sort=${currentSort}&dir=${currentDir}&situacao=${encodeURIComponent(sitFilter)}&limit=${pageSize}&offset=${offset}`;
            const res  = await fetch(url);
            const data = await res.json();
            if (!data.success) throw new Error(data.error);

            totalItems = data.total;
            document.getElementById('counterBadge').textContent = `${totalItems} registros`;
            renderPagination();
            syncUrl();

            if (!data.data.length) {
                tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="icon">🏭</div><p>Nenhum fornecedor encontrado</p></div></td></tr>';
                return;
            }

            tbody.innerHTML = data.data.map((f) => {
                let sit = (f.situacao || '').toLowerCase();
                const isAtivo = sit === 'a' || sit === 'ativo' || sit === '';
                return `<tr>
                    <td>${f.id}</td>
                    <td><strong>${esc(f.descricao || '')}</strong></td>
                    <td>${esc(f.contato_nome || '')}</td>
                    <td>${esc(f.contato_fone || '')}</td>
                    <td><span class="badge ${isAtivo ? 'badge-success' : 'badge-danger'}">${isAtivo ? 'Ativo' : 'Inativo'}</span></td>
                    <td class="actions" style="justify-content:center">
                        <button class="action-btn edit" onclick="editFornecedor(${f.id})" title="Editar">✏️</button>
                        <button class="action-btn delete" onclick="openDeleteModal(${f.id}, '${esc(f.descricao || '')}')" title="Inativar">🗑️</button>
                    </td>
                </tr>`;
            }).join('');
            updateSortHeaders();
        } catch(e) {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">❌ Erro ao carregar</td></tr>';
            showToast(e.message,'error');
        }
    }

    function sortBy(col) {
        if(currentSort===col) currentDir=currentDir==='ASC'?'DESC':'ASC';
        else { currentSort=col; currentDir='ASC'; }
        currentPage = 1;
        loadFornecedores();
    }
    function updateSortHeaders() {
        document.querySelectorAll('th[data-sort]').forEach(th => {
            th.classList.remove('sorted');
            const i=th.querySelector('.sort-icon');
            if(i) i.textContent='↕';
        });
        const a=document.querySelector(`th[data-sort="${currentSort}"]`);
        if(a){ a.classList.add('sorted'); const i=a.querySelector('.sort-icon'); if(i) i.textContent=currentDir==='ASC'?'↑':'↓'; }
    }
    function debounceSearch() { clearTimeout(searchTimeout); searchTimeout=setTimeout(()=>{currentPage=1;loadFornecedores();},300); }

    // ===== FORM FUNCTIONS =====
    function showForm(id=null) {
        document.getElementById('gridView').classList.add('hidden');
        document.getElementById('formView').classList.add('active');
        resetForm();
        if (!id) {
            document.getElementById('formHeader').textContent = 'CADASTRAR/ATUALIZAR - FORNECEDOR';
            document.getElementById('btnIncluir').textContent = '➕ Incluir';
        }
    }

    function showGrid() {
        document.getElementById('formView').classList.remove('active');
        document.getElementById('gridView').classList.remove('hidden');
        loadFornecedores();
    }

    function resetForm() {
        document.getElementById('fornecedorId').value = '';
        ['f_descricao','f_contato_nome','f_contato_fone','f_endereco'].forEach(id => document.getElementById(id).value='');
        document.querySelector('input[name="f_situacao"][value="Ativo"]').checked = true;
    }

    function resetFormAndNew() {
        resetForm();
        document.getElementById('formHeader').textContent = 'CADASTRAR/ATUALIZAR - FORNECEDOR';
        document.getElementById('btnIncluir').textContent = '➕ Incluir';
    }

    async function editFornecedor(id) {
        try {
            const res = await fetch(`fornecedor.php?ajax=get&id=${id}`);
            const data = await res.json();
            if (!data.success) throw new Error(data.error);
            const f = data.data;
            showForm(id);
            document.getElementById('formHeader').textContent = 'CADASTRAR/ATUALIZAR - FORNECEDOR';
            document.getElementById('btnIncluir').textContent = '💾 Atualizar';
            document.getElementById('fornecedorId').value = f.id;
            document.getElementById('f_descricao').value = f.descricao || '';
            document.getElementById('f_contato_nome').value = f.contato_nome || '';
            document.getElementById('f_contato_fone').value = f.contato_fone || '';
            document.getElementById('f_endereco').value = f.endereco || '';
            let sit = (f.situacao || '').toLowerCase();
            if (sit === 'a' || sit === 'ativo' || sit === '') sit = 'Ativo';
            else if (sit === 'i' || sit === 'inativo') sit = 'Inativo';
            else sit = 'Ativo';
            const radio = document.querySelector(`input[name="f_situacao"][value="${sit}"]`);
            if (radio) radio.checked = true;
        } catch(e) { showToast(e.message,'error'); }
    }

    async function saveFornecedor() {
        const fd = new FormData();
        const id = document.getElementById('fornecedorId').value;
        fd.append('ajax', id ? 'update' : 'create');
        if (id) fd.append('id', id);
        fd.append('descricao', document.getElementById('f_descricao').value);
        fd.append('contato_nome', document.getElementById('f_contato_nome').value);
        fd.append('contato_fone', document.getElementById('f_contato_fone').value);
        fd.append('endereco', document.getElementById('f_endereco').value);
        fd.append('situacao', document.querySelector('input[name="f_situacao"]:checked').value);

        try {
            const res = await fetch('fornecedor.php', {method:'POST', body:fd});
            const data = await res.json();
            if (!data.success) throw new Error(data.error);
            showToast(data.message,'success');
            if (!id && data.id) {
                document.getElementById('fornecedorId').value = data.id;
                document.getElementById('btnIncluir').textContent = '💾 Atualizar';
            }
        } catch(e) { showToast(e.message,'error'); }
    }

    // ===== DELETE =====
    function openDeleteModal(id, info) { deleteId=id; document.getElementById('deleteInfo').textContent=info; document.getElementById('deleteModal').classList.add('active'); }
    function closeDeleteModal() { document.getElementById('deleteModal').classList.remove('active'); deleteId=null; }
    async function confirmDelete() {
        if (!deleteId) return;
        try {
            const fd=new FormData(); fd.append('ajax','delete'); fd.append('id',deleteId);
            const res=await fetch('fornecedor.php',{method:'POST',body:fd});
            const data=await res.json();
            if(!data.success) throw new Error(data.error);
            showToast(data.message,'success'); closeDeleteModal(); loadFornecedores();
        } catch(e) { showToast(e.message,'error'); }
    }

    // ===== UTILS =====
    function showToast(msg,type='success') { const t=document.getElementById('toast'); t.textContent=msg; t.className=`toast ${type} show`; setTimeout(()=>t.classList.remove('show'),3000); }
    function esc(t) { if(!t) return ''; const d=document.createElement('div'); d.textContent=t; return d.innerHTML; }
    </script>
</body>
</html>
