<?php
/**
 * CRUD de Carros
 * Victor Transportes - Sistema de Gestão
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';
require_once 'Database.php';

$db = new Database();
$currentPage = 'carro.php';

// AJAX: Listar carros
if (isset($_GET['ajax']) && $_GET['ajax'] === 'list') {
    header('Content-Type: application/json');
    try {
        $search = $_GET['search'] ?? '';
        $sortCol = $_GET['sort'] ?? 'descricao';
        $sortDir = strtoupper($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        // Validar coluna de ordenação
        $allowedCols = ['id', 'descricao', 'placa', 'situacao'];
        if (!in_array($sortCol, $allowedCols))
            $sortCol = 'descricao';

        $limit  = max(1, min(200, (int) ($_GET['limit']  ?? 25)));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));

        $params = [];
        $where  = "FROM carro WHERE 1=1";

        if (!empty($search)) {
            $where .= " AND (descricao LIKE :search OR placa LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $totalResult = $db->queryOne("SELECT COUNT(*) as n {$where}", $params);
        $total = ($totalResult) ? (int)$totalResult['n'] : 0;

        $sql = "SELECT id, descricao, placa, situacao
                {$where} ORDER BY {$sortCol} {$sortDir} LIMIT {$limit} OFFSET {$offset}";

        $carros = $db->query($sql, $params);
        echo json_encode(['success' => true, 'data' => $carros, 'total' => $total]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Listar carros ativos (para dropdown)
if (isset($_GET['ajax']) && $_GET['ajax'] === 'list_active') {
    header('Content-Type: application/json');
    try {
        $carros = $db->query("SELECT id, descricao, placa FROM carro WHERE situacao = 'a' ORDER BY descricao ASC");
        echo json_encode(['success' => true, 'data' => $carros]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Buscar carro por ID
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get') {
    header('Content-Type: application/json');
    try {
        $id = intval($_GET['id'] ?? 0);
        if (!$id)
            throw new Exception('ID inválido');

        $carro = $db->queryOne("SELECT id, descricao, placa, situacao FROM carro WHERE id = ?", [$id]);
        if (!$carro)
            throw new Exception('Carro não encontrado');

        echo json_encode(['success' => true, 'data' => $carro]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Criar carro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'create') {
    header('Content-Type: application/json');
    try {
        $descricao = trim($_POST['descricao'] ?? '');
        $placa = trim($_POST['placa'] ?? '');
        $situacao = trim($_POST['situacao'] ?? 'a');

        if (empty($descricao))
            throw new Exception('Descrição é obrigatória');
        if (empty($placa))
            throw new Exception('Placa é obrigatória');

        $sql = "INSERT INTO carro (descricao, placa, situacao) VALUES (?, ?, ?)";
        $db->execute($sql, [$descricao, $placa, $situacao]);
        $newId = $db->lastInsertId();

        echo json_encode(['success' => true, 'message' => 'Carro criado com sucesso!', 'id' => $newId]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Atualizar carro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'update') {
    header('Content-Type: application/json');
    try {
        $id = intval($_POST['id'] ?? 0);
        if (!$id)
            throw new Exception('ID inválido');

        $descricao = trim($_POST['descricao'] ?? '');
        $placa = trim($_POST['placa'] ?? '');
        $situacao = trim($_POST['situacao'] ?? 'a');

        if (empty($descricao))
            throw new Exception('Descrição é obrigatória');
        if (empty($placa))
            throw new Exception('Placa é obrigatória');

        $sql = "UPDATE carro SET descricao = ?, placa = ?, situacao = ? WHERE id = ?";
        $db->execute($sql, [$descricao, $placa, $situacao, $id]);

        echo json_encode(['success' => true, 'message' => 'Carro atualizado com sucesso!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Deletar carro (soft delete - mudar situação)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'delete') {
    header('Content-Type: application/json');
    try {
        $id = intval($_POST['id'] ?? 0);
        if (!$id)
            throw new Exception('ID inválido');

        // Soft delete - apenas inativa
        $db->execute("UPDATE carro SET situacao = 'i' WHERE id = ?", [$id]);

        echo json_encode(['success' => true, 'message' => 'Carro inativado com sucesso!']);
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
    <title>Carros - Victor Transportes</title>
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
        }

        .main-content {
            padding: 20px;
            width: 100%;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-light);
            filter: brightness(1.1);
        }

        .btn-secondary {
            background: #e9ecef;
            color: var(--text);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .filter-bar {
            background: var(--card);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-bar input {
            flex: 1;
            min-width: 200px;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .filter-bar input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(31, 111, 84, 0.1);
        }

        .table-container {
            background: var(--card);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th { background:var(--primary); color:white; font-weight:600; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.5px; cursor:pointer; user-select:none; transition:background 0.2s; }
        th:hover { background:var(--primary-light); }
        th.sorted { background:var(--primary-light); }

        th .sort-icon {
            margin-left: 6px;
            opacity: 0.5;
        }

        th.sorted .sort-icon {
            opacity: 1;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 1rem;
        }

        .action-btn.edit {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .action-btn.edit:hover {
            background: #bfdbfe;
        }

        .action-btn.delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .action-btn.delete:hover {
            background: #fecaca;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 3000;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
            padding: 4px;
        }

        .modal-body {
            padding: 24px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(31, 111, 84, 0.1);
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 14px 20px;
            border-radius: 10px;
            color: white;
            font-weight: 500;
            z-index: 4000;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        .toast.success { background: var(--success); }
        .toast.error { background: var(--danger); }

        .loading {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }

        @media (max-width: 768px) {
            .page-header { flex-direction: column; align-items: flex-start; }
            .filter-bar { flex-direction: column; }
            .filter-bar input { width: 100%; }
            .table-container { overflow-x: auto; }
            table { min-width: 600px; }
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content page-with-sidebar">
        <div class="page-header">
            <h1>🚛 Carros</h1>
            <button class="btn btn-primary" onclick="openModal()">
                ➕ Novo Carro
            </button>
        </div>

        <div class="filter-bar">
            <input type="text" id="searchInput" placeholder="🔍 Buscar por descrição ou placa..." oninput="debounceSearch()">
            <select id="pageSizeSelect" onchange="onPageSizeChange()" style="padding:8px 10px;border:1px solid #ddd;border-radius:8px;font-size:0.9rem;">
                <option value="10">10 / pág</option>
                <option value="25" selected>25 / pág</option>
                <option value="50">50 / pág</option>
                <option value="100">100 / pág</option>
            </select>
            <button class="btn btn-secondary" onclick="loadCarros()">🔄 Atualizar</button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th data-sort="id" onclick="sortBy('id')" style="width: 80px;">ID <span class="sort-icon">↕</span></th>
                        <th data-sort="descricao" onclick="sortBy('descricao')">Descrição <span class="sort-icon">↕</span></th>
                        <th data-sort="placa" onclick="sortBy('placa')" style="width: 150px;">Placa <span class="sort-icon">↕</span></th>
                        <th data-sort="situacao" onclick="sortBy('situacao')" style="width: 120px;">Situação <span class="sort-icon">↕</span></th>
                        <th style="width: 100px;">Ações</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr><td colspan="5" class="loading">🔄 Carregando...</td></tr>
                </tbody>
            </table>
        </div>

        <div id="paginationBar" style="display:none; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; padding:12px 4px;">
            <span id="paginationInfo" style="font-size:0.85rem; color:#666;"></span>
            <div id="paginationControls" style="display:flex; gap:4px; flex-wrap:wrap;"></div>
        </div>
    </main>

    <!-- Modal Criar/Editar -->
    <div class="modal-overlay" id="modal">
        <div class="modal">
            <div class="modal-header">
                <h2 id="modalTitle">Novo Carro</h2>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <form id="carroForm" onsubmit="saveCarro(event)">
                <div class="modal-body">
                    <input type="hidden" id="carroId" name="id">

                    <div class="form-group">
                        <label for="descricao">Descrição *</label>
                        <input type="text" id="descricao" name="descricao" required placeholder="Ex: Mercedes-Benz Sprinter">
                    </div>

                    <div class="form-group">
                        <label for="placa">Placa *</label>
                        <input type="text" id="placa" name="placa" required placeholder="ABC-1234">
                    </div>

                    <div class="form-group">
                        <label for="situacao">Situação</label>
                        <select id="situacao" name="situacao">
                            <option value="a">Ativo</option>
                            <option value="i">Inativo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">💾 Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Confirmar Delete -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header">
                <h2>Confirmar Exclusão</h2>
                <button class="modal-close" onclick="closeDeleteModal()">×</button>
            </div>
            <div class="modal-body">
                <p>Deseja realmente inativar o carro <strong id="deleteDesc"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
                <button class="btn btn-danger" onclick="confirmDelete()">🗑️ Inativar</button>
            </div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script>
        var VT_PRIMARY = '<?php echo EMPRESA_COR_PRIMARIA; ?>';
        var VT_TEXTO   = '<?php echo EMPRESA_COR_TEXTO; ?>';

        let currentSort = 'descricao';
        let currentDir  = 'ASC';
        let searchTimeout = null;
        let deleteId = null;

        let currentPage = 1;
        let pageSize    = 25;
        let totalItems  = 0;

        function readUrlParams() {
            const p = new URLSearchParams(location.search);
            currentPage = Math.max(1, parseInt(p.get('page') || '1'));
            pageSize    = [10, 25, 50, 100].includes(parseInt(p.get('size'))) ? parseInt(p.get('size')) : 25;
            const sel = document.getElementById('pageSizeSelect');
            if (sel) sel.value = pageSize;
        }

        function syncUrl() {
            const p = new URLSearchParams(location.search);
            p.set('page', currentPage);
            p.set('size', pageSize);
            history.replaceState(null, '', '?' + p.toString());
        }

        function renderPagination() {
            const bar  = document.getElementById('paginationBar');
            const info = document.getElementById('paginationInfo');
            const ctrl = document.getElementById('paginationControls');
            if (!totalItems) { bar.style.display = 'none'; return; }

            const totalPages = Math.ceil(totalItems / pageSize);
            const from = Math.min((currentPage - 1) * pageSize + 1, totalItems);
            const to   = Math.min(currentPage * pageSize, totalItems);

            bar.style.display  = 'flex';
            info.textContent   = `${from}–${to} de ${totalItems} registros`;

            const btnStyle = (active) =>
                `style="padding:6px 10px;border:1px solid ${active ? VT_PRIMARY : '#ddd'};border-radius:6px;` +
                `background:${active ? VT_PRIMARY : '#fff'};color:${active ? VT_TEXTO : '#333'};` +
                `font-size:0.82rem;cursor:pointer;font-weight:${active ? '700' : '400'};"`;

            let btns = `<button ${btnStyle(false)} onclick="goPage(1)" ${currentPage===1?'disabled':''}>«</button>`;
            btns    += `<button ${btnStyle(false)} onclick="goPage(${currentPage-1})" ${currentPage===1?'disabled':''}>‹</button>`;

            const range = 2;
            for (let i = Math.max(1, currentPage - range); i <= Math.min(totalPages, currentPage + range); i++) {
                btns += `<button ${btnStyle(i===currentPage)} onclick="goPage(${i})">${i}</button>`;
            }

            btns += `<button ${btnStyle(false)} onclick="goPage(${currentPage+1})" ${currentPage===totalPages?'disabled':''}>›</button>`;
            btns += `<button ${btnStyle(false)} onclick="goPage(${totalPages})" ${currentPage===totalPages?'disabled':''}>»</button>`;
            ctrl.innerHTML = btns;
        }

        function goPage(p) {
            const totalPages = Math.ceil(totalItems / pageSize);
            p = Math.max(1, Math.min(totalPages, p));
            if (p === currentPage) return;
            currentPage = p;
            loadCarros();
        }

        function onPageSizeChange() {
            pageSize    = parseInt(document.getElementById('pageSizeSelect').value);
            currentPage = 1;
            loadCarros();
        }

        document.addEventListener('DOMContentLoaded', () => { readUrlParams(); loadCarros(); });

        async function loadCarros() {
            const search = document.getElementById('searchInput').value;
            const tbody  = document.getElementById('tableBody');
            tbody.innerHTML = '<tr><td colspan="5" class="loading">🔄 Carregando...</td></tr>';

            const offset = (currentPage - 1) * pageSize;
            try {
                const url = `carro.php?ajax=list&search=${encodeURIComponent(search)}&sort=${currentSort}&dir=${currentDir}&limit=${pageSize}&offset=${offset}`;
                const res  = await fetch(url);
                const data = await res.json();

                if (!data.success) throw new Error(data.error);

                totalItems = data.total;
                renderPagination();
                syncUrl();

                if (!data.data.length) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:#999;">Nenhum carro encontrado</td></tr>';
                    return;
                }

                tbody.innerHTML = data.data.map(c => `
                    <tr>
                        <td>${c.id}</td>
                        <td><strong>${escapeHtml(c.descricao)}</strong></td>
                        <td><code>${escapeHtml(c.placa)}</code></td>
                        <td>
                            <span class="badge ${c.situacao === 'a' ? 'badge-success' : 'badge-danger'}">
                                ${c.situacao === 'a' ? 'Ativo' : 'Inativo'}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <button class="action-btn edit" onclick="editCarro(${c.id})" title="Editar">✏️</button>
                                <button class="action-btn delete" onclick="openDeleteModal(${c.id}, '${escapeHtml(c.descricao)}')" title="Inativar">🗑️</button>
                            </div>
                        </td>
                    </tr>
                `).join('');

                updateSortHeaders();
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align:center;padding:40px;color:red;">Erro ao carregar: ${e.message}</td></tr>`;
            }
        }

        function sortBy(col) {
            if (currentSort === col) {
                currentDir = currentDir === 'ASC' ? 'DESC' : 'ASC';
            } else {
                currentSort = col;
                currentDir  = 'ASC';
            }
            currentPage = 1;
            loadCarros();
        }

        function updateSortHeaders() {
            document.querySelectorAll('th[data-sort]').forEach(th => {
                th.classList.remove('sorted');
                th.querySelector('.sort-icon').textContent = '↕';
            });
            const activeTh = document.querySelector(`th[data-sort="${currentSort}"]`);
            if (activeTh) {
                activeTh.classList.add('sorted');
                activeTh.querySelector('.sort-icon').textContent = currentDir === 'ASC' ? '↑' : '↓';
            }
        }

        function debounceSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => { currentPage = 1; loadCarros(); }, 300);
        }

        function openModal() {
            document.getElementById('modal').classList.add('active');
            document.getElementById('modalTitle').textContent = 'Novo Carro';
            document.getElementById('carroForm').reset();
            document.getElementById('carroId').value = '';
        }

        function closeModal() {
            document.getElementById('modal').classList.remove('active');
        }

        async function editCarro(id) {
            try {
                const res  = await fetch(`carro.php?ajax=get&id=${id}`);
                const data = await res.json();
                if (!data.success) throw new Error(data.error);

                const c = data.data;
                document.getElementById('carroId').value = c.id;
                document.getElementById('descricao').value = c.descricao;
                document.getElementById('placa').value = c.placa;
                document.getElementById('situacao').value = c.situacao;

                document.getElementById('modalTitle').textContent = 'Editar Carro';
                document.getElementById('modal').classList.add('active');
            } catch (e) {
                showToast(e.message, 'error');
            }
        }

        async function saveCarro(e) {
            e.preventDefault();
            const id = document.getElementById('carroId').value;
            const formData = new FormData(document.getElementById('carroForm'));
            formData.append('ajax', id ? 'update' : 'create');

            try {
                const res  = await fetch('carro.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) throw new Error(data.error);

                showToast(data.message, 'success');
                closeModal();
                loadCarros();
            } catch (e) {
                showToast(e.message, 'error');
            }
        }

        function openDeleteModal(id, desc) {
            deleteId = id;
            document.getElementById('deleteDesc').textContent = desc;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteId = null;
        }

        async function confirmDelete() {
            if (!deleteId) return;
            const formData = new FormData();
            formData.append('ajax', 'delete');
            formData.append('id', deleteId);

            try {
                const res  = await fetch('carro.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) throw new Error(data.error);

                showToast(data.message, 'success');
                closeDeleteModal();
                loadCarros();
            } catch (e) {
                showToast(e.message, 'error');
            }
        }

        function showToast(msg, type) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.className = `toast show ${type}`;
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>

</html>
