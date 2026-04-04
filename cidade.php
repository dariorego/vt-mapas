<?php
/**
 * CRUD de Cidades
 * <?php echo EMPRESA_NOME; ?> - Sistema de Gestão
 */
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'config.php';
require_once 'Database.php';
$db = new Database();
$currentPage = 'cidade.php';

// AJAX: Listar
if (isset($_GET['ajax']) && $_GET['ajax'] === 'list') {
    header('Content-Type: application/json');
    try {
        $search   = $_GET['search'] ?? '';
        $sortCol  = $_GET['sort']   ?? 'descricao';
        $sortDir  = strtoupper($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $allowed  = ['id', 'descricao', 'situacao', 'grupo'];
        if (!in_array($sortCol, $allowed)) $sortCol = 'descricao';

        $limit    = max(1, min(200, (int)($_GET['limit']  ?? 25)));
        $offset   = max(0, (int)($_GET['offset'] ?? 0));
        $situacao = $_GET['situacao'] ?? '';

        $params = [];
        $where  = "FROM cidade WHERE 1=1";

        if (!empty($search)) {
            $where .= " AND (descricao LIKE :s1 OR CAST(id AS CHAR) LIKE :s2)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
        }
        if ($situacao === 'Ativo') {
            $where .= " AND (situacao IN ('A','a','1','Ativo') OR situacao IS NULL OR situacao = '')";
        } elseif ($situacao === 'Inativo') {
            $where .= " AND situacao IN ('I','i','0','Inativo')";
        }

        $total = (int)$db->queryOne("SELECT COUNT(*) as n {$where}", $params)['n'];
        $rows  = $db->query("SELECT id, descricao, situacao, grupo, latitude, logitude {$where} ORDER BY {$sortCol} {$sortDir} LIMIT {$limit} OFFSET {$offset}", $params);

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
        $id = intval($_GET['id'] ?? 0);
        if (!$id) throw new Exception('ID inválido');
        $row = $db->queryOne("SELECT * FROM cidade WHERE id = ?", [$id]);
        if (!$row) throw new Exception('Cidade não encontrada');
        echo json_encode(['success' => true, 'data' => $row]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Salvar (criar/editar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'save') {
    header('Content-Type: application/json');
    try {
        $id        = intval($_POST['id'] ?? 0);
        $descricao = trim($_POST['descricao'] ?? '');
        $situacao  = $_POST['situacao'] ?? 'A';
        $grupo     = !empty($_POST['grupo']) ? intval($_POST['grupo']) : null;
        $latitude  = trim($_POST['latitude'] ?? '') ?: null;
        $longitude = trim($_POST['longitude'] ?? '') ?: null;

        if (empty($descricao)) throw new Exception('Descrição obrigatória');

        if ($id) {
            $db->execute(
                "UPDATE cidade SET descricao=:d, situacao=:s, grupo=:g, latitude=:lat, logitude=:lng WHERE id=:id",
                [':d'=>$descricao, ':s'=>$situacao, ':g'=>$grupo, ':lat'=>$latitude, ':lng'=>$longitude, ':id'=>$id]
            );
            echo json_encode(['success' => true, 'message' => 'Cidade atualizada']);
        } else {
            $db->execute(
                "INSERT INTO cidade (descricao, situacao, grupo, latitude, logitude) VALUES (:d,:s,:g,:lat,:lng)",
                [':d'=>$descricao, ':s'=>$situacao, ':g'=>$grupo, ':lat'=>$latitude, ':lng'=>$longitude]
            );
            echo json_encode(['success' => true, 'message' => 'Cidade criada']);
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
        // Verificar se há clientes vinculados
        $uso = $db->queryOne("SELECT COUNT(*) as n FROM cliente WHERE cidade_id = ?", [$id]);
        if ($uso && $uso['n'] > 0) throw new Exception("Não é possível excluir: {$uso['n']} cliente(s) vinculado(s)");
        $db->execute("DELETE FROM cidade WHERE id = ?", [$id]);
        echo json_encode(['success' => true, 'message' => 'Cidade excluída']);
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
    <title>Cidades - <?php echo EMPRESA_NOME; ?></title>
    <style>
        :root {
            --primary: <?php echo EMPRESA_COR_PRIMARIA; ?>;
            --primary-light: <?php echo EMPRESA_COR_PRIMARIA; ?>;
            --primary-dark: <?php echo EMPRESA_COR_SECUNDARIA; ?>;
            --primary-bg: <?php echo EMPRESA_COR_PRIMARIA; ?>1a;
            --success: #22C55E;
            --danger: #EF4444;
            --warning: #F59E0B;
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
        .btn-primary { background:var(--primary); color:white; }
        .btn-primary:hover { opacity:0.9; }
        .btn-secondary { background:#e9ecef; color:var(--text); }
        .btn-danger { background:var(--danger); color:white; }
        .btn-sm { padding:6px 12px; font-size:0.8rem; }

        .toolbar { background:var(--card); padding:14px 16px; border-radius:12px; margin-bottom:16px; box-shadow:0 2px 8px rgba(0,0,0,0.05); display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
        .filter-group { display:flex; flex-direction:column; gap:4px; }
        .filter-group label { font-size:0.78rem; font-weight:600; color:var(--text-muted); text-transform:uppercase; }
        .filter-group input, .filter-group select { padding:8px 12px; border:1px solid var(--border); border-radius:8px; font-size:0.9rem; min-width:180px; }
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
        .action-btn.edit:hover { background:#DBEAFE; }
        .action-btn.delete { background:#FEF2F2; color:#DC2626; }
        .action-btn.delete:hover { background:#FEE2E2; }

        .pagination { display:flex; justify-content:space-between; align-items:center; padding:14px 16px; background:var(--card); border-radius:0 0 12px 12px; border-top:1px solid var(--border); margin-top:-1px; flex-wrap:wrap; gap:12px; }
        .pagination-info { font-size:0.85rem; color:var(--text-muted); }
        .pagination-controls { display:flex; gap:4px; }
        .page-btn { padding:6px 12px; border:1px solid var(--border); background:white; border-radius:6px; font-size:0.85rem; cursor:pointer; transition:all 0.15s; color:var(--text); }
        .page-btn:hover:not(:disabled) { background:var(--primary-bg); border-color:var(--primary); }
        .page-btn.active { background:var(--primary); color:white; border-color:var(--primary); }
        .page-btn:disabled { opacity:0.4; cursor:default; }

        .loading { text-align:center; padding:40px; color:var(--text-muted); }
        .empty-state { text-align:center; padding:48px; color:var(--text-muted); }
        .empty-state .icon { font-size:2.5rem; margin-bottom:12px; }
        .sort-icon { font-size:0.7rem; opacity:0.7; }

        /* Modal */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center; }
        .modal-overlay.open { display:flex; }
        .modal { background:white; border-radius:16px; width:100%; max-width:540px; box-shadow:0 20px 60px rgba(0,0,0,0.2); max-height:90vh; overflow-y:auto; }
        #cityMap { width:100%; height:220px; border-radius:10px; border:1px solid var(--border); margin-top:4px; background:#e8f0f7; display:none; }
        .map-placeholder { width:100%; height:220px; border-radius:10px; border:1px solid var(--border); background:#f3f4f6; display:flex; align-items:center; justify-content:center; color:var(--text-muted); font-size:0.85rem; margin-top:4px; }
        .modal-header { padding:20px 24px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; }
        .modal-header h2 { font-size:1.1rem; font-weight:600; }
        .modal-close { background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted); }
        .modal-body { padding:24px; }
        .modal-footer { padding:16px 24px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:10px; }
        .form-group { margin-bottom:16px; }
        .form-group label { display:block; margin-bottom:6px; font-weight:500; font-size:0.9rem; }
        .form-group input, .form-group select { width:100%; padding:10px 14px; border:1px solid var(--border); border-radius:8px; font-size:0.9rem; }
        .form-group input:focus, .form-group select:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px var(--primary-bg); }
        .form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }

        /* Toast */
        .toast { position:fixed; bottom:24px; right:24px; padding:14px 20px; border-radius:10px; color:white; font-size:0.9rem; font-weight:500; z-index:9999; opacity:0; transition:opacity 0.3s; pointer-events:none; }
        .toast.show { opacity:1; }
        .toast.success { background:#22C55E; }
        .toast.error   { background:#EF4444; }

        /* Delete modal */
        .delete-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1100; justify-content:center; align-items:center; }
        .delete-overlay.open { display:flex; }
        .delete-modal { background:white; border-radius:16px; width:100%; max-width:400px; padding:28px; box-shadow:0 20px 60px rgba(0,0,0,0.2); }
        .delete-modal h3 { font-size:1.1rem; margin-bottom:12px; }
        .delete-modal p  { color:var(--text-muted); font-size:0.9rem; margin-bottom:20px; }
        .delete-modal .actions { justify-content:flex-end; }

        @media (max-width:768px) { .form-row { grid-template-columns:1fr; } .filter-group input, .filter-group select { min-width:140px; } }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<main class="main-content page-with-sidebar">
    <div class="page-header">
        <h1>🏙️ Cidades</h1>
        <button class="btn btn-primary" onclick="openModal()">➕ Nova Cidade</button>
    </div>

    <div class="toolbar">
        <div class="filter-group">
            <label>Busca</label>
            <input type="text" id="searchInput" placeholder="🔍 Nome ou código..." oninput="debounceSearch()">
        </div>
        <div class="filter-group">
            <label>Situação</label>
            <select id="filterSituacao" onchange="goPage(1)">
                <option value="">Todas</option>
                <option value="Ativo">Ativo</option>
                <option value="Inativo">Inativo</option>
            </select>
        </div>
        <div class="filter-group">
            <label>Por página</label>
            <select id="perPageSelect" onchange="goPage(1)">
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>
        <div class="filter-group" style="justify-content:flex-end;flex-direction:row;gap:8px;margin-top:auto;">
            <button class="btn btn-secondary btn-sm" onclick="clearFilters()">🧹 Limpar</button>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th data-sort="id" onclick="sortBy('id')" style="width:80px">CÓD. <span class="sort-icon">↕</span></th>
                    <th data-sort="descricao" onclick="sortBy('descricao')">DESCRIÇÃO <span class="sort-icon">↕</span></th>
                    <th data-sort="grupo" onclick="sortBy('grupo')" style="width:100px">GRUPO <span class="sort-icon">↕</span></th>
                    <th style="width:90px">SITUAÇÃO</th>
                    <th style="width:110px">AÇÕES</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <tr><td colspan="5" class="loading">🔄 Carregando...</td></tr>
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
            <h2 id="modalTitle">Nova Cidade</h2>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="cityId">
            <div class="form-group">
                <label>Descrição *</label>
                <input type="text" id="cityDescricao" placeholder="Nome da cidade">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Grupo</label>
                    <input type="number" id="cityGrupo" placeholder="Ex: 1">
                </div>
                <div class="form-group">
                    <label>Situação</label>
                    <select id="citySituacao">
                        <option value="A">Ativo</option>
                        <option value="I">Inativo</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Latitude</label>
                    <input type="text" id="cityLatitude" placeholder="-8.0631" oninput="updateMap()">
                </div>
                <div class="form-group">
                    <label>Longitude</label>
                    <input type="text" id="cityLongitude" placeholder="-34.8716" oninput="updateMap()">
                </div>
            </div>
            <div class="form-group" id="mapContainer" style="display:none">
                <label>Localização no Mapa</label>
                <iframe id="cityMap" frameborder="0" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe>
                <div class="map-placeholder" id="mapPlaceholder">📍 Informe latitude e longitude para ver o mapa</div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
            <button class="btn btn-primary" onclick="saveCity()">💾 Salvar</button>
        </div>
    </div>
</div>

<!-- Modal Excluir -->
<div class="delete-overlay" id="deleteModal">
    <div class="delete-modal">
        <h3>🗑️ Excluir Cidade</h3>
        <p>Deseja excluir <strong id="deleteInfo"></strong>? Esta ação não pode ser desfeita.</p>
        <div class="actions">
            <button class="btn btn-secondary" onclick="closeDelete()">Cancelar</button>
            <button class="btn btn-danger" onclick="confirmDelete()">Excluir</button>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>

<script>
    let currentSort = 'descricao';
    let currentDir  = 'ASC';
    let currentPage = 1;
    let searchTimeout = null;
    let deleteId = null;

    document.addEventListener('DOMContentLoaded', () => loadCidades());

    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => { currentPage = 1; loadCidades(); }, 400);
    }

    function goPage(p) { currentPage = p; loadCidades(); }

    function sortBy(col) {
        if (currentSort === col) currentDir = currentDir === 'ASC' ? 'DESC' : 'ASC';
        else { currentSort = col; currentDir = 'ASC'; }
        currentPage = 1;
        loadCidades();
    }

    function clearFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('filterSituacao').value = '';
        document.getElementById('perPageSelect').value = '25';
        currentPage = 1;
        loadCidades();
    }

    async function loadCidades() {
        const search   = document.getElementById('searchInput').value;
        const situacao = document.getElementById('filterSituacao').value;
        const limit    = parseInt(document.getElementById('perPageSelect').value);
        const offset   = (currentPage - 1) * limit;
        const tbody    = document.getElementById('tableBody');
        tbody.innerHTML = '<tr><td colspan="5" class="loading">🔄 Carregando...</td></tr>';

        try {
            const url = `cidade.php?ajax=list&search=${encodeURIComponent(search)}&situacao=${encodeURIComponent(situacao)}&sort=${currentSort}&dir=${currentDir}&limit=${limit}&offset=${offset}`;
            const res  = await fetch(url);
            const data = await res.json();
            if (!data.success) throw new Error(data.error);

            if (data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5"><div class="empty-state"><div class="icon">🏙️</div><p>Nenhuma cidade encontrada</p></div></td></tr>';
            } else {
                tbody.innerHTML = data.data.map(c => {
                    const sit = (c.situacao || '').toUpperCase();
                    const ativo = sit === 'A' || sit === '1' || sit === '' || c.situacao === null;
                    return `<tr>
                        <td><strong>${c.id}</strong></td>
                        <td>${esc(c.descricao || '')}</td>
                        <td>${c.grupo ?? '-'}</td>
                        <td><span class="badge ${ativo ? 'badge-success' : 'badge-danger'}">${ativo ? 'Ativo' : 'Inativo'}</span></td>
                        <td class="actions">
                            <button class="action-btn edit" onclick="editCity(${c.id})" title="Editar">✏️</button>
                            <button class="action-btn delete" onclick="openDelete(${c.id}, '${esc(c.descricao || '')}')" title="Excluir">🗑️</button>
                        </td>
                    </tr>`;
                }).join('');
            }

            renderPagination(data.total, limit);
            updateSortHeaders();
        } catch(e) {
            tbody.innerHTML = `<tr><td colspan="5" class="loading">❌ Erro ao carregar</td></tr>`;
            showToast(e.message, 'error');
        }
    }

    function renderPagination(total, limit) {
        const totalPages = Math.max(1, Math.ceil(total / limit));
        const start = (currentPage - 1) * limit + 1;
        const end   = Math.min(currentPage * limit, total);
        document.getElementById('paginationInfo').textContent = total > 0 ? `${start}–${end} de ${total} registros` : '0 registros';

        let html = '';
        const btn = (p, label, disabled, active) =>
            `<button class="page-btn${active?' active':''}" onclick="goPage(${p})" ${disabled?'disabled':''}>${label}</button>`;
        html += btn(1, '«', currentPage===1, false);
        html += btn(currentPage-1, '‹', currentPage===1, false);
        for (let i = Math.max(1, currentPage-2); i <= Math.min(totalPages, currentPage+2); i++)
            html += btn(i, i, false, i===currentPage);
        html += btn(currentPage+1, '›', currentPage===totalPages, false);
        html += btn(totalPages, '»', currentPage===totalPages, false);
        document.getElementById('paginationControls').innerHTML = html;
    }

    function updateSortHeaders() {
        document.querySelectorAll('th[data-sort]').forEach(th => {
            const s = th.querySelector('.sort-icon');
            if (!s) return;
            s.textContent = th.dataset.sort === currentSort ? (currentDir === 'ASC' ? '↑' : '↓') : '↕';
        });
    }

    // Modal
    const MAPS_KEY = '<?php echo GOOGLE_MAPS_API_KEY; ?>';

    function updateMap() {
        const lat = parseFloat(document.getElementById('cityLatitude').value);
        const lng = parseFloat(document.getElementById('cityLongitude').value);
        const map      = document.getElementById('cityMap');
        const placeholder = document.getElementById('mapPlaceholder');
        const container   = document.getElementById('mapContainer');
        container.style.display = 'block';

        if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
            map.src = `https://www.google.com/maps/embed/v1/place?key=${MAPS_KEY}&q=${lat},${lng}&zoom=13`;
            map.style.display = 'block';
            placeholder.style.display = 'none';
        } else {
            map.style.display = 'none';
            placeholder.style.display = 'flex';
        }
    }

    function openModal(id) {
        document.getElementById('cityId').value        = '';
        document.getElementById('cityDescricao').value = '';
        document.getElementById('cityGrupo').value     = '';
        document.getElementById('citySituacao').value  = 'A';
        document.getElementById('cityLatitude').value  = '';
        document.getElementById('cityLongitude').value = '';
        document.getElementById('mapContainer').style.display = 'none';
        document.getElementById('cityMap').src = '';
        document.getElementById('modalTitle').textContent = 'Nova Cidade';
        document.getElementById('modal').classList.add('open');
        setTimeout(() => document.getElementById('cityDescricao').focus(), 100);
    }

    function closeModal() { document.getElementById('modal').classList.remove('open'); }

    async function editCity(id) {
        try {
            const res  = await fetch(`cidade.php?ajax=get&id=${id}`);
            const data = await res.json();
            if (!data.success) throw new Error(data.error);
            const c = data.data;
            document.getElementById('cityId').value        = c.id;
            document.getElementById('cityDescricao').value = c.descricao || '';
            document.getElementById('cityGrupo').value     = c.grupo || '';
            document.getElementById('citySituacao').value  = (c.situacao || 'A').toUpperCase() === 'I' ? 'I' : 'A';
            document.getElementById('cityLatitude').value  = c.latitude || '';
            document.getElementById('cityLongitude').value = c.logitude || '';
            document.getElementById('modalTitle').textContent = 'Editar Cidade';
            document.getElementById('modal').classList.add('open');
            updateMap();
        } catch(e) { showToast(e.message, 'error'); }
    }

    async function saveCity() {
        const id        = document.getElementById('cityId').value;
        const descricao = document.getElementById('cityDescricao').value.trim();
        if (!descricao) { showToast('Informe a descrição', 'error'); return; }

        const body = new FormData();
        body.append('ajax', 'save');
        body.append('id',        id);
        body.append('descricao', descricao);
        body.append('situacao',  document.getElementById('citySituacao').value);
        body.append('grupo',     document.getElementById('cityGrupo').value);
        body.append('latitude',  document.getElementById('cityLatitude').value);
        body.append('longitude', document.getElementById('cityLongitude').value);

        try {
            const res  = await fetch('cidade.php', { method:'POST', body });
            const data = await res.json();
            if (!data.success) throw new Error(data.error);
            showToast(data.message, 'success');
            closeModal();
            loadCidades();
        } catch(e) { showToast(e.message, 'error'); }
    }

    // Delete
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
            const res  = await fetch('cidade.php', { method:'POST', body });
            const data = await res.json();
            if (!data.success) throw new Error(data.error);
            showToast(data.message, 'success');
            closeDelete();
            loadCidades();
        } catch(e) { showToast(e.message, 'error'); }
    }

    // Toast
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
