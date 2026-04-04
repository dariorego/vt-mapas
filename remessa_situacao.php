<?php
/**
 * CRUD de Situação de Remessa
 */
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'config.php';
require_once 'Database.php';
$db = new Database();
$currentPage = 'remessa_situacao.php';

// AJAX: Listar
if (isset($_GET['ajax']) && $_GET['ajax'] === 'list') {
    header('Content-Type: application/json');
    try {
        $search   = $_GET['search'] ?? '';
        $sortCol  = $_GET['sort']   ?? 'id';
        $sortDir  = strtoupper($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $allowed  = ['id', 'descricao', 'situacao'];
        if (!in_array($sortCol, $allowed)) $sortCol = 'id';

        $limit  = max(1, min(200, (int)($_GET['limit']  ?? 25)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $params = [];
        $where  = "FROM remessa_situacao WHERE 1=1";
        if (!empty($search)) {
            $where .= " AND (descricao LIKE :s1 OR CAST(id AS CHAR) LIKE :s2)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
        }

        $total = (int)$db->queryOne("SELECT COUNT(*) as n {$where}", $params)['n'];
        $rows  = $db->query("SELECT id, descricao, situacao {$where} ORDER BY {$sortCol} {$sortDir} LIMIT {$limit} OFFSET {$offset}", $params);

        echo json_encode(['success' => true, 'data' => $rows, 'total' => $total]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Buscar por ID
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get') {
    header('Content-Type: application/json');
    try {
        $id  = intval($_GET['id'] ?? 0);
        if (!$id) throw new Exception('ID inválido');
        $row = $db->queryOne("SELECT id, descricao, situacao FROM remessa_situacao WHERE id = ?", [$id]);
        if (!$row) throw new Exception('Situação não encontrada');
        echo json_encode(['success' => true, 'data' => $row]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Salvar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'save') {
    header('Content-Type: application/json');
    try {
        $id        = intval($_POST['id'] ?? 0);
        $descricao = trim($_POST['descricao'] ?? '');
        $situacao  = $_POST['situacao'] ?? 'a';

        if (empty($descricao)) throw new Exception('Descrição obrigatória');

        if ($id) {
            $db->execute(
                "UPDATE remessa_situacao SET descricao=:d, situacao=:s WHERE id=:id",
                [':d' => $descricao, ':s' => $situacao, ':id' => $id]
            );
            echo json_encode(['success' => true, 'message' => 'Situação atualizada']);
        } else {
            $db->execute(
                "INSERT INTO remessa_situacao (descricao, situacao) VALUES (:d, :s)",
                [':d' => $descricao, ':s' => $situacao]
            );
            echo json_encode(['success' => true, 'message' => 'Situação criada']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Deletar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'delete') {
    header('Content-Type: application/json');
    try {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) throw new Exception('ID inválido');
        $uso = $db->queryOne("SELECT COUNT(*) as n FROM remessa WHERE remessa_situacao_id = ?", [$id]);
        if ($uso && $uso['n'] > 0) throw new Exception("Não é possível excluir: {$uso['n']} remessa(s) vinculada(s)");
        $db->execute("DELETE FROM remessa_situacao WHERE id = ?", [$id]);
        echo json_encode(['success' => true, 'message' => 'Situação excluída']);
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
    <title>Situação Remessa - <?php echo EMPRESA_NOME; ?></title>
    <style>
        :root {
            --primary: <?php echo EMPRESA_COR_PRIMARIA; ?>;
            --primary-dark: <?php echo EMPRESA_COR_SECUNDARIA; ?>;
            --primary-bg: <?php echo EMPRESA_COR_PRIMARIA; ?>1a;
            --success: #22C55E;
            --danger: #EF4444;
            --bg: #F6F8F9;
            --card: #ffffff;
            --text: #1F2933;
            --text-muted: #6B7280;
            --border: #E5E7EB;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
        .main-content { padding:20px; width:100%; }
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px; }
        .page-header h1 { font-size:1.5rem; font-weight:600; color:var(--primary); display:flex; align-items:center; gap:10px; }
        .btn { padding:10px 20px; border:none; border-radius:8px; font-size:0.9rem; font-weight:600; cursor:pointer; transition:all 0.2s; display:inline-flex; align-items:center; gap:8px; }
        .btn-primary  { background:var(--primary); color:white; }
        .btn-primary:hover { opacity:0.9; }
        .btn-secondary { background:#e9ecef; color:var(--text); }
        .btn-danger    { background:var(--danger); color:white; }
        .btn-sm        { padding:6px 12px; font-size:0.8rem; }

        .toolbar { background:var(--card); padding:14px 16px; border-radius:12px; margin-bottom:16px; box-shadow:0 2px 8px rgba(0,0,0,0.05); display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
        .filter-group { display:flex; flex-direction:column; gap:4px; }
        .filter-group label { font-size:0.78rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; }
        .filter-group input, .filter-group select { padding:8px 12px; border:1px solid var(--border); border-radius:8px; font-size:0.9rem; min-width:200px; }
        .filter-group input:focus, .filter-group select:focus { outline:none; border-color:var(--primary); }

        .table-container { background:var(--card); border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); overflow:hidden; }
        table { width:100%; border-collapse:collapse; }
        th { background:var(--primary); color:white; padding:12px 16px; text-align:left; font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; cursor:pointer; user-select:none; }
        th:hover { opacity:0.9; }
        td { padding:12px 16px; border-bottom:1px solid var(--border); font-size:0.9rem; vertical-align:middle; }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#f9fafb; }

        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:0.75rem; font-weight:600; }
        .badge-success { background:#dcfce7; color:#166534; }
        .badge-danger  { background:#fee2e2; color:#991b1b; }

        .actions { display:flex; gap:6px; }
        .action-btn { border:none; border-radius:6px; padding:6px 10px; cursor:pointer; font-size:0.82rem; transition:all 0.15s; }
        .action-btn.edit   { background:#EFF6FF; color:#1D4ED8; }
        .action-btn.edit:hover   { background:#DBEAFE; }
        .action-btn.delete { background:#FEF2F2; color:#DC2626; }
        .action-btn.delete:hover { background:#FEE2E2; }

        .pagination { display:flex; justify-content:space-between; align-items:center; padding:14px 16px; background:var(--card); border-radius:0 0 12px 12px; border-top:1px solid var(--border); margin-top:-1px; flex-wrap:wrap; gap:12px; }
        .pagination-info { font-size:0.85rem; color:var(--text-muted); }
        .pagination-controls { display:flex; gap:4px; }
        .page-btn { padding:6px 12px; border:1px solid var(--border); background:white; border-radius:6px; font-size:0.85rem; cursor:pointer; color:var(--text); }
        .page-btn:hover:not(:disabled) { background:var(--primary-bg); border-color:var(--primary); }
        .page-btn.active { background:var(--primary); color:white; border-color:var(--primary); }
        .page-btn:disabled { opacity:0.4; cursor:default; }

        .loading { text-align:center; padding:40px; color:var(--text-muted); }
        .empty-state { text-align:center; padding:48px; color:var(--text-muted); }
        .empty-state .icon { font-size:2.5rem; margin-bottom:12px; }
        .sort-icon { font-size:0.7rem; opacity:0.7; }

        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center; }
        .modal-overlay.open { display:flex; }
        .modal { background:white; border-radius:16px; width:100%; max-width:440px; box-shadow:0 20px 60px rgba(0,0,0,0.2); }
        .modal-header { padding:20px 24px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; }
        .modal-header h2 { font-size:1.1rem; font-weight:600; }
        .modal-close { background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted); }
        .modal-body { padding:24px; }
        .modal-footer { padding:16px 24px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:10px; }
        .form-group { margin-bottom:16px; }
        .form-group label { display:block; margin-bottom:6px; font-weight:500; font-size:0.9rem; }
        .form-group input, .form-group select { width:100%; padding:10px 14px; border:1px solid var(--border); border-radius:8px; font-size:0.9rem; }
        .form-group input:focus, .form-group select:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px var(--primary-bg); }

        .delete-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1100; justify-content:center; align-items:center; }
        .delete-overlay.open { display:flex; }
        .delete-modal { background:white; border-radius:16px; width:100%; max-width:400px; padding:28px; box-shadow:0 20px 60px rgba(0,0,0,0.2); }
        .delete-modal h3 { font-size:1.1rem; margin-bottom:12px; }
        .delete-modal p  { color:var(--text-muted); font-size:0.9rem; margin-bottom:20px; }
        .delete-modal .del-actions { display:flex; gap:10px; justify-content:flex-end; }

        .toast { position:fixed; bottom:24px; right:24px; padding:14px 20px; border-radius:10px; color:white; font-size:0.9rem; font-weight:500; z-index:9999; opacity:0; transition:opacity 0.3s; pointer-events:none; }
        .toast.show { opacity:1; }
        .toast.success { background:#22C55E; }
        .toast.error   { background:#EF4444; }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<main class="main-content page-with-sidebar">
    <div class="page-header">
        <h1>📋 Situação de Remessa</h1>
        <button class="btn btn-primary" onclick="openModal()">➕ Nova Situação</button>
    </div>

    <div class="toolbar">
        <div class="filter-group">
            <label>Busca</label>
            <input type="text" id="searchInput" placeholder="🔍 Descrição ou código..." oninput="debounceSearch()">
        </div>
        <div class="filter-group">
            <label>Por página</label>
            <select id="perPageSelect" onchange="goPage(1)">
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
        <div class="filter-group" style="flex-direction:row;align-items:flex-end;">
            <button class="btn btn-secondary btn-sm" onclick="clearFilters()">🧹 Limpar</button>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th data-sort="id" onclick="sortBy('id')" style="width:80px">CÓD. <span class="sort-icon">↕</span></th>
                    <th data-sort="descricao" onclick="sortBy('descricao')">DESCRIÇÃO <span class="sort-icon">↕</span></th>
                    <th style="width:110px">SITUAÇÃO</th>
                    <th style="width:110px">AÇÕES</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <tr><td colspan="4" class="loading">🔄 Carregando...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="pagination" id="paginationBar">
        <div class="pagination-info" id="paginationInfo"></div>
        <div class="pagination-controls" id="paginationControls"></div>
    </div>
</main>

<!-- Modal Criar/Editar -->
<div class="modal-overlay" id="modal">
    <div class="modal">
        <div class="modal-header">
            <h2 id="modalTitle">Nova Situação</h2>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="recId">
            <div class="form-group">
                <label>Descrição *</label>
                <input type="text" id="recDescricao" placeholder="Ex: Entregue">
            </div>
            <div class="form-group">
                <label>Situação</label>
                <select id="recSituacao">
                    <option value="a">Ativo</option>
                    <option value="i">Inativo</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="saveRecord()">💾 Salvar</button>
        </div>
    </div>
</div>

<!-- Modal Excluir -->
<div class="delete-overlay" id="deleteModal">
    <div class="delete-modal">
        <h3>🗑️ Excluir Situação</h3>
        <p>Deseja excluir <strong id="deleteInfo"></strong>? Esta ação não pode ser desfeita.</p>
        <div class="del-actions">
            <button class="btn btn-secondary" onclick="closeDelete()">Cancelar</button>
            <button class="btn btn-danger" onclick="confirmDelete()">Excluir</button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
    let currentSort = 'id', currentDir = 'ASC', currentPage = 1, searchTimeout = null, deleteId = null;

    document.addEventListener('DOMContentLoaded', () => loadData());

    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => { currentPage = 1; loadData(); }, 400);
    }

    function goPage(p) { currentPage = p; loadData(); }

    function sortBy(col) {
        currentDir = currentSort === col ? (currentDir === 'ASC' ? 'DESC' : 'ASC') : 'ASC';
        currentSort = col;
        currentPage = 1;
        loadData();
    }

    function clearFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('perPageSelect').value = '25';
        currentPage = 1;
        loadData();
    }

    async function loadData() {
        const search = document.getElementById('searchInput').value;
        const limit  = parseInt(document.getElementById('perPageSelect').value);
        const offset = (currentPage - 1) * limit;
        const tbody  = document.getElementById('tableBody');
        tbody.innerHTML = '<tr><td colspan="4" class="loading">🔄 Carregando...</td></tr>';

        try {
            const res  = await fetch(`remessa_situacao.php?ajax=list&search=${encodeURIComponent(search)}&sort=${currentSort}&dir=${currentDir}&limit=${limit}&offset=${offset}`);
            const data = await res.json();
            if (!data.success) throw new Error(data.error);

            if (!data.data.length) {
                tbody.innerHTML = '<tr><td colspan="4"><div class="empty-state"><div class="icon">📋</div><p>Nenhuma situação encontrada</p></div></td></tr>';
            } else {
                tbody.innerHTML = data.data.map(r => {
                    const ativo = (r.situacao || '').toLowerCase() !== 'i';
                    return `<tr>
                        <td><strong>${r.id}</strong></td>
                        <td>${esc(r.descricao || '')}</td>
                        <td><span class="badge ${ativo ? 'badge-success' : 'badge-danger'}">${ativo ? 'Ativo' : 'Inativo'}</span></td>
                        <td class="actions">
                            <button class="action-btn edit"   onclick="editRecord(${r.id})" title="Editar">✏️</button>
                            <button class="action-btn delete" onclick="openDelete(${r.id}, '${esc(r.descricao || '')}')" title="Excluir">🗑️</button>
                        </td>
                    </tr>`;
                }).join('');
            }

            renderPagination(data.total, limit);
            updateSortHeaders();
        } catch(e) {
            tbody.innerHTML = '<tr><td colspan="4" class="loading">❌ Erro ao carregar</td></tr>';
            showToast(e.message, 'error');
        }
    }

    function renderPagination(total, limit) {
        const totalPages = Math.max(1, Math.ceil(total / limit));
        const start = (currentPage - 1) * limit + 1;
        const end   = Math.min(currentPage * limit, total);
        document.getElementById('paginationInfo').textContent = total > 0 ? `${start}–${end} de ${total} registros` : '0 registros';

        const btn = (p, label, disabled, active) =>
            `<button class="page-btn${active?' active':''}" onclick="goPage(${p})" ${disabled?'disabled':''}>${label}</button>`;
        let html = btn(1,'«',currentPage===1,false) + btn(currentPage-1,'‹',currentPage===1,false);
        for (let i = Math.max(1,currentPage-2); i <= Math.min(totalPages,currentPage+2); i++)
            html += btn(i, i, false, i===currentPage);
        html += btn(currentPage+1,'›',currentPage===totalPages,false) + btn(totalPages,'»',currentPage===totalPages,false);
        document.getElementById('paginationControls').innerHTML = html;
    }

    function updateSortHeaders() {
        document.querySelectorAll('th[data-sort]').forEach(th => {
            const s = th.querySelector('.sort-icon');
            if (s) s.textContent = th.dataset.sort === currentSort ? (currentDir==='ASC'?'↑':'↓') : '↕';
        });
    }

    function openModal() {
        document.getElementById('recId').value        = '';
        document.getElementById('recDescricao').value = '';
        document.getElementById('recSituacao').value  = 'a';
        document.getElementById('modalTitle').textContent = 'Nova Situação';
        document.getElementById('modal').classList.add('open');
        setTimeout(() => document.getElementById('recDescricao').focus(), 100);
    }

    function closeModal() { document.getElementById('modal').classList.remove('open'); }

    async function editRecord(id) {
        try {
            const res  = await fetch(`remessa_situacao.php?ajax=get&id=${id}`);
            const data = await res.json();
            if (!data.success) throw new Error(data.error);
            document.getElementById('recId').value        = data.data.id;
            document.getElementById('recDescricao').value = data.data.descricao || '';
            document.getElementById('recSituacao').value  = (data.data.situacao || 'a').toLowerCase() === 'i' ? 'i' : 'a';
            document.getElementById('modalTitle').textContent = 'Editar Situação';
            document.getElementById('modal').classList.add('open');
        } catch(e) { showToast(e.message, 'error'); }
    }

    async function saveRecord() {
        const descricao = document.getElementById('recDescricao').value.trim();
        if (!descricao) { showToast('Informe a descrição', 'error'); return; }

        const body = new FormData();
        body.append('ajax',      'save');
        body.append('id',        document.getElementById('recId').value);
        body.append('descricao', descricao);
        body.append('situacao',  document.getElementById('recSituacao').value);

        try {
            const res  = await fetch('remessa_situacao.php', { method:'POST', body });
            const data = await res.json();
            if (!data.success) throw new Error(data.error);
            showToast(data.message, 'success');
            closeModal();
            loadData();
        } catch(e) { showToast(e.message, 'error'); }
    }

    function openDelete(id, nome) {
        deleteId = id;
        document.getElementById('deleteInfo').textContent = nome;
        document.getElementById('deleteModal').classList.add('open');
    }
    function closeDelete() { document.getElementById('deleteModal').classList.remove('open'); deleteId = null; }

    async function confirmDelete() {
        if (!deleteId) return;
        const body = new FormData();
        body.append('ajax', 'delete');
        body.append('id', deleteId);
        try {
            const res  = await fetch('remessa_situacao.php', { method:'POST', body });
            const data = await res.json();
            if (!data.success) throw new Error(data.error);
            showToast(data.message, 'success');
            closeDelete();
            loadData();
        } catch(e) { showToast(e.message, 'error'); }
    }

    function showToast(msg, type='success') {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className = `toast ${type} show`;
        setTimeout(() => t.classList.remove('show'), 3500);
    }

    function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
</script>
</body>
</html>
