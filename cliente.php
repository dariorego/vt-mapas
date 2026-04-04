<?php
/**
 * CRUD de Clientes
 * Victor Transportes - Sistema de Gestão
 */
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'config.php';
require_once 'Database.php';
$db = new Database();
$currentPage = 'cliente.php';

// AJAX: Listar clientes
if (isset($_GET['ajax']) && $_GET['ajax'] === 'list') {
    header('Content-Type: application/json');
    try {
        $search = $_GET['search'] ?? '';
        $sortCol = $_GET['sort'] ?? 'nome';
        $sortDir = strtoupper($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $allowedCols = ['id', 'nome', 'fone', 'endereco', 'situacao'];
        if (!in_array($sortCol, $allowedCols)) $sortCol = 'nome';

        $limit    = max(1, min(200, (int) ($_GET['limit']    ?? 25)));
        $offset   = max(0, (int) ($_GET['offset']   ?? 0));
        $situacao = $_GET['situacao'] ?? '';

        $params = [];
        $where  = "FROM cliente c LEFT JOIN cidade ci ON ci.id = c.cidade_id WHERE 1=1";

        if (!empty($search)) {
            $where .= " AND (c.nome LIKE :s1 OR c.fone LIKE :s2 OR c.endereco LIKE :s3 OR CAST(c.id AS CHAR) LIKE :s4)";
            $params[':s1'] = "%{$search}%";
            $params[':s2'] = "%{$search}%";
            $params[':s3'] = "%{$search}%";
            $params[':s4'] = "%{$search}%";
        }
        if (!empty($situacao)) {
            $where .= " AND c.situacao = :sit";
            $params[':sit'] = $situacao;
        }

        $total = (int) $db->queryOne("SELECT COUNT(*) as n {$where}", $params)['n'];

        $sql = "SELECT c.id, c.nome, c.fone, c.endereco, c.coordenadas, c.situacao, c.cidade_id,
                       c.complemento, c.latitude, c.longitude, ci.descricao as cidade_descricao
                {$where} ORDER BY c.{$sortCol} {$sortDir} LIMIT {$limit} OFFSET {$offset}";
        $clientes = $db->query($sql, $params);
        echo json_encode(['success' => true, 'data' => $clientes, 'total' => $total]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Buscar cliente por ID
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get') {
    header('Content-Type: application/json');
    try {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) throw new Exception('ID inválido');
        $cliente = $db->queryOne("SELECT c.id, c.nome, c.fone, c.endereco, c.coordenadas, c.situacao,
                                         c.cidade_id, c.complemento, c.latitude, c.longitude,
                                         ci.descricao as cidade_descricao
                                  FROM cliente c
                                  LEFT JOIN cidade ci ON ci.id = c.cidade_id
                                  WHERE c.id = ?", [$id]);
        if (!$cliente) throw new Exception('Cliente não encontrado');
        echo json_encode(['success' => true, 'data' => $cliente]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Listar cidades para select
if (isset($_GET['ajax']) && $_GET['ajax'] === 'cidades') {
    header('Content-Type: application/json');
    try {
        $cidades = $db->query("SELECT id, descricao FROM cidade ORDER BY descricao");
        echo json_encode(['success' => true, 'data' => $cidades]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Criar cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'create') {
    header('Content-Type: application/json');
    try {
        $nome = trim($_POST['nome'] ?? '');
        $fone = trim($_POST['fone'] ?? '');
        $endereco = trim($_POST['endereco'] ?? '');
        $complemento = trim($_POST['complemento'] ?? '');
        $coordenadas = trim($_POST['coordenadas'] ?? '');
        $situacao = trim($_POST['situacao'] ?? 'a');
        $cidade_id = intval($_POST['cidade_id'] ?? 0);
        $latitude = trim($_POST['latitude'] ?? '') !== '' ? floatval($_POST['latitude']) : null;
        $longitude = trim($_POST['longitude'] ?? '') !== '' ? floatval($_POST['longitude']) : null;

        if (empty($nome)) throw new Exception('Nome é obrigatório');

        $sql = "INSERT INTO cliente (nome, fone, endereco, complemento, coordenadas, situacao, cidade_id, latitude, longitude)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $db->execute($sql, [$nome, $fone, $endereco, $complemento, $coordenadas, $situacao,
            $cidade_id ?: null, $latitude, $longitude]);
        $newId = $db->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Cliente criado com sucesso!', 'id' => $newId]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Atualizar cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'update') {
    header('Content-Type: application/json');
    try {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) throw new Exception('ID inválido');

        $nome = trim($_POST['nome'] ?? '');
        $fone = trim($_POST['fone'] ?? '');
        $endereco = trim($_POST['endereco'] ?? '');
        $complemento = trim($_POST['complemento'] ?? '');
        $coordenadas = trim($_POST['coordenadas'] ?? '');
        $situacao = trim($_POST['situacao'] ?? 'a');
        $cidade_id = intval($_POST['cidade_id'] ?? 0);
        $latitude = trim($_POST['latitude'] ?? '') !== '' ? floatval($_POST['latitude']) : null;
        $longitude = trim($_POST['longitude'] ?? '') !== '' ? floatval($_POST['longitude']) : null;

        if (empty($nome)) throw new Exception('Nome é obrigatório');

        $sql = "UPDATE cliente SET nome=?, fone=?, endereco=?, complemento=?, coordenadas=?,
                situacao=?, cidade_id=?, latitude=?, longitude=? WHERE id=?";
        $db->execute($sql, [$nome, $fone, $endereco, $complemento, $coordenadas, $situacao,
            $cidade_id ?: null, $latitude, $longitude, $id]);
        echo json_encode(['success' => true, 'message' => 'Cliente atualizado com sucesso!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Deletar cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'delete') {
    header('Content-Type: application/json');
    try {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) throw new Exception('ID inválido');
        // Soft delete - muda situação para inativo
        $db->execute("UPDATE cliente SET situacao = 'i' WHERE id = ?", [$id]);
        echo json_encode(['success' => true, 'message' => 'Cliente inativado com sucesso!']);
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
    <title>Clientes - <?php echo EMPRESA_NOME; ?></title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        :root {
            --primary: <?php echo EMPRESA_COR_PRIMARIA; ?>; --primary-light: <?php echo EMPRESA_COR_PRIMARIA; ?>; --primary-bg: <?php echo EMPRESA_COR_PRIMARIA; ?>1a;
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
        .btn-outline { background:transparent; border:2px solid var(--primary); color:var(--primary); }
        .btn-outline:hover { background:var(--primary); color:white; }

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
        .action-btn.map { background:#dcfce7; color:#166534; }
        .action-btn.map:hover { background:#bbf7d0; }
        .action-btn.map.disabled { background:#f3f4f6; color:#9ca3af; cursor:not-allowed; }
        .action-btn.whatsapp { background:#dcfce7; color:#25d366; }
        .action-btn.whatsapp:hover { background:#bbf7d0; }
        .action-btn.whatsapp.disabled { background:#f3f4f6; color:#9ca3af; cursor:not-allowed; }

        /* Map Button inline */
        .map-link { display:inline-flex; align-items:center; gap:4px; background:var(--primary-bg); color:var(--primary); padding:4px 10px; border-radius:6px; font-size:0.78rem; font-weight:600; cursor:pointer; border:none; transition:all 0.2s; text-decoration:none; }
        .map-link:hover { background:var(--primary); color:white; }
        .map-link.disabled { background:#f3f4f6; color:#9ca3af; cursor:not-allowed; pointer-events:none; }

        /* WhatsApp link */
        .whatsapp-link { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:50%; background:#25d366; color:white; text-decoration:none; font-size:1.1rem; transition:all 0.2s; }
        .whatsapp-link:hover { background:#128c7e; transform:scale(1.1); }
        .whatsapp-link.disabled { background:#ccc; cursor:not-allowed; pointer-events:none; }

        /* Form View */
        .form-view { display:none; }
        .form-view.active { display:block; }
        .grid-view.hidden { display:none; }
        .form-card { background:var(--card); border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.05); margin-bottom:20px; overflow:hidden; }
        .form-card-header { background:var(--primary); color:white; padding:14px 24px; font-weight:600; font-size:1rem; text-align:center; }
        .form-card-toolbar { display:flex; justify-content:center; gap:12px; padding:16px 24px; border-bottom:1px solid var(--border); flex-wrap:wrap; position:relative; }
        .form-card-body { padding:24px; }
        .section-title { font-size:0.85rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--text-muted); margin:20px 0 12px; padding-bottom:6px; border-bottom:1px solid var(--border); }
        .section-title:first-child { margin-top:0; }
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

        /* Modal */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.5); display:none; align-items:center; justify-content:center; z-index:3000; padding:20px; }
        .modal-overlay.active { display:flex; }
        .modal { background:white; border-radius:16px; width:100%; max-width:400px; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,0.2); }
        .modal.modal-lg { max-width:700px; }
        .modal-header { padding:20px 24px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center; }
        .modal-header h2 { font-size:1.2rem; font-weight:600; }
        .modal-close { background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted); }
        .modal-body { padding:24px; }
        .modal-footer { padding:16px 24px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:12px; }

        /* Map Container */
        #mapContainer { height:400px; width:100%; border-radius:8px; z-index:1; }

        /* Toast */
        .toast { position:fixed; bottom:24px; right:24px; padding:14px 20px; border-radius:10px; color:white; font-weight:500; z-index:4000; transform:translateY(100px); opacity:0; transition:all 0.3s ease; }
        .toast.show { transform:translateY(0); opacity:1; }
        .toast.success { background:var(--success); }
        .toast.error { background:var(--danger); }

        .empty-state { text-align:center; padding:60px 20px; color:var(--text-muted); }
        .empty-state .icon { font-size:3rem; margin-bottom:16px; }
        .loading { text-align:center; padding:40px; color:var(--text-muted); }

        /* Row number */
        .row-num { display:inline-flex; align-items:center; justify-content:center; min-width:28px; height:24px; padding:0 6px; border-radius:12px; font-size:0.75rem; font-weight:700; background:var(--primary-bg); color:var(--primary); }

        @media (max-width:768px) {
            .main-content { padding:16px; }
            .page-header { flex-direction:column; align-items:flex-start; }
            .filter-bar { flex-direction:column; }
            .table-container { overflow-x:auto; }
            table { min-width:900px; }
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
                <h1>👥 Relatório de Clientes</h1>
                <button class="btn btn-primary" onclick="showForm()">➕ Novo</button>
            </div>
            <div class="filter-bar">
                <input type="text" id="searchInput" placeholder="🔍 Busca Rápida..." oninput="debounceSearch()">
                <select id="filterSituacao" onchange="onFilterChange()">
                    <option value="">Todos</option>
                    <option value="a" selected>Ativo</option>
                    <option value="i">Inativo</option>
                </select>
                <select id="pageSizeSelect" onchange="onPageSizeChange()">
                    <option value="10">10 / pág</option>
                    <option value="25" selected>25 / pág</option>
                    <option value="50">50 / pág</option>
                    <option value="100">100 / pág</option>
                </select>
                <button class="btn btn-secondary" onclick="loadClientes()">🔄</button>
                <span class="counter-badge" id="counterBadge">0 registros</span>
            </div>
            <div class="table-container">
                <table>
                    <thead><tr>
                        <th style="width:40px"></th>
                        <th style="width:50px">#</th>
                        <th data-sort="nome" onclick="sortBy('nome')">NOME SOBRENOME <span class="sort-icon">↕</span></th>
                        <th data-sort="fone" onclick="sortBy('fone')" style="width:140px">TELEFONE <span class="sort-icon">↕</span></th>
                        <th style="width:130px">MAPA</th>
                        <th style="width:80px">WHATSAPP</th>
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
                <div class="form-card-header" id="formHeader">[05] - CADASTRAR/ATUALIZAR - CLIENTE</div>
                <div class="form-card-toolbar">
                    <button class="btn btn-primary" id="btnIncluir" onclick="saveCliente()">➕ Incluir</button>
                    <button class="btn btn-danger" id="btnCancelar" onclick="showGrid()">🚫 Cancelar</button>
                    <div style="position:absolute;right:24px;display:flex;gap:8px;">
                        <button class="btn btn-secondary btn-sm" onclick="resetFormAndNew()">🔄</button>
                    </div>
                </div>
                <div class="form-card-body">
                    <input type="hidden" id="clienteId">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>NOME SOBRENOME <span class="req">*</span></label>
                            <input type="text" id="f_nome" placeholder="">
                        </div>
                        <div class="form-group">
                            <label>CIDADE</label>
                            <select id="f_cidade_id"><option value="">Selecione</option></select>
                        </div>
                    </div>

                    <div class="form-grid" style="margin-top:12px;">
                        <div class="form-group">
                            <label>TELEFONE</label>
                            <input type="text" id="f_fone" placeholder="">
                        </div>
                        <div class="form-group">
                            <label>ENDEREÇO</label>
                            <input type="text" id="f_endereco" placeholder="">
                        </div>
                    </div>

                    <div class="form-grid" style="margin-top:12px;">
                        <div class="form-group">
                            <label>Complemento</label>
                            <input type="text" id="f_complemento" placeholder="">
                        </div>
                        <div class="form-group">
                            <label>COORDENADAS GOOGLE</label>
                            <input type="text" id="f_coordenadas" placeholder="https://maps.google.com/..." oninput="updateFormMapLink()">
                        </div>
                    </div>

                    <div class="form-grid" style="margin-top:12px;">
                        <div class="form-group">
                            <label>SITUAÇÃO</label>
                            <div class="situacao-radios">
                                <label><input type="radio" name="f_situacao" value="a" checked> Ativo</label>
                                <label><input type="radio" name="f_situacao" value="i"> Inativo</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>MAPA</label>
                            <a href="#" id="f_mapa_link" target="_blank" class="btn btn-sm btn-outline" style="margin-top:4px" onclick="openFormMap(event)">🗺️ Google Maps</a>
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
            <div class="modal-body"><p>Deseja realmente inativar o cliente <strong id="deleteInfo"></strong>?</p></div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
                <button class="btn btn-danger" onclick="confirmDelete()">🗑️ Inativar</button>
            </div>
        </div>
    </div>

    <!-- Map Modal -->
    <div class="modal-overlay" id="mapModal">
        <div class="modal modal-lg">
            <div class="modal-header">
                <h2 id="mapModalTitle">🗺️ Localização</h2>
                <div style="display:flex; gap:8px; align-items:center;">
                    <span style="font-size:0.8rem; color:var(--text-muted);">Fechar ou tecla Esc</span>
                    <button class="modal-close" onclick="closeMapModal()">×</button>
                </div>
            </div>
            <div class="modal-body" style="padding:16px;">
                <div id="mapContainer"></div>
            </div>
            <div class="modal-footer">
                <a href="#" id="mapExternalLink" target="_blank" class="btn btn-outline btn-sm">🔗 Ver mapa ampliado</a>
                <button class="btn btn-secondary" onclick="closeMapModal()">Fechar</button>
            </div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script>
    var VT_PRIMARY = '<?php echo EMPRESA_COR_PRIMARIA; ?>';
    var VT_TEXTO   = '<?php echo EMPRESA_COR_TEXTO; ?>';
    let currentSort = 'nome', currentDir = 'ASC', searchTimeout = null, deleteId = null;
    let cidadesCache = [];
    let mapInstance = null, mapMarker = null;

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
        const bs = (active) => `style="padding:6px 10px;border:1px solid ${active?VT_PRIMARY:'#ddd'};border-radius:6px;background:${active?VT_PRIMARY:'#fff'};color:${active?VT_TEXTO:'#333'};font-size:0.82rem;cursor:pointer;font-weight:${active?'700':'400'};"`;
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
        currentPage = p; syncUrl(); loadClientes();
    }
    function onPageSizeChange() {
        pageSize = parseInt(document.getElementById('pageSizeSelect').value);
        currentPage = 1; syncUrl(); loadClientes();
    }
    function onFilterChange() { currentPage = 1; loadClientes(); }
    // ─────────────────────────────────────────────────────────────────────────

    document.addEventListener('DOMContentLoaded', () => { readUrlParams(); loadClientes(); loadCidades(); setFormDate(); });

    // Fechar mapa com Esc
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeMapModal();
    });

    function setFormDate() {
        const d = new Date();
        document.getElementById('formDate').textContent =
            `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`;
    }

    async function loadCidades() {
        try {
            const res = await fetch('cliente.php?ajax=cidades');
            const data = await res.json();
            if (data.success) {
                cidadesCache = data.data;
                const sel = document.getElementById('f_cidade_id');
                sel.innerHTML = '<option value="">Selecione</option>' +
                    data.data.map(c => `<option value="${c.id}">${esc(c.descricao)}</option>`).join('');
            }
        } catch(e) { console.error(e); }
    }

    async function loadClientes() {
        const search    = document.getElementById('searchInput').value;
        const sitFilter = document.getElementById('filterSituacao').value;
        const tbody     = document.getElementById('tableBody');
        tbody.innerHTML = '<tr><td colspan="6" class="loading">🔄 Carregando...</td></tr>';
        const offset = (currentPage - 1) * pageSize;
        try {
            const url = `cliente.php?ajax=list&search=${encodeURIComponent(search)}&sort=${currentSort}&dir=${currentDir}&situacao=${sitFilter}&limit=${pageSize}&offset=${offset}`;
            const res  = await fetch(url);
            const data = await res.json();
            if (!data.success) throw new Error(data.error);

            totalItems = data.total;
            document.getElementById('counterBadge').textContent = `${totalItems} registros`;
            renderPagination();
            syncUrl();

            if (!data.data.length) {
                tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="icon">👥</div><p>Nenhum cliente encontrado</p></div></td></tr>';
                return;
            }

            tbody.innerHTML = data.data.map((c, i) => {
                const foneClean = (c.fone || '').replace(/\D/g, '');
                const hasCoords = c.coordenadas || (c.latitude && c.longitude);
                const hasPhone = foneClean.length >= 10;

                return `<tr>
                    <td><button class="action-btn edit" onclick="editCliente(${c.id})" title="Editar">✏️</button></td>
                    <td><span class="row-num">${i+1}</span></td>
                    <td><strong>${esc(c.nome)}</strong></td>
                    <td>${esc(c.fone || '')}</td>
                    <td>
                        <button class="map-link ${hasCoords ? '' : 'disabled'}"
                            onclick="${hasCoords ? `openMapFromCoords('${esc(c.coordenadas||'')}', ${c.latitude||'null'}, ${c.longitude||'null'}, '${esc(c.nome)}')` : 'void(0)'}"
                            title="${hasCoords ? 'Ver no mapa' : 'Sem coordenadas'}">
                            🗺️ Google Maps
                        </button>
                    </td>
                    <td style="text-align:center;">
                        ${hasPhone
                            ? `<a href="https://wa.me/55${foneClean}" target="_blank" class="whatsapp-link" title="Abrir WhatsApp">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                               </a>`
                            : `<span class="whatsapp-link disabled" title="Sem telefone">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                               </span>`
                        }
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
        loadClientes();
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
    function debounceSearch() { clearTimeout(searchTimeout); searchTimeout=setTimeout(()=>{currentPage=1;loadClientes();},300); }

    // ===== MAP FUNCTIONS =====
    function parseCoordinatesFromString(coordStr) {
        if (!coordStr) return null;
        // Try to extract lat,lng from various formats
        // Format: "lat,lng" or Google Maps URL
        let match;
        // Try direct lat,lng
        match = coordStr.match(/(-?\d+\.?\d*)\s*,\s*(-?\d+\.?\d*)/);
        if (match) return { lat: parseFloat(match[1]), lng: parseFloat(match[2]) };
        // Try Google Maps URL with @lat,lng
        match = coordStr.match(/@(-?\d+\.?\d*),(-?\d+\.?\d*)/);
        if (match) return { lat: parseFloat(match[1]), lng: parseFloat(match[2]) };
        // Try Google Maps URL with q=lat,lng
        match = coordStr.match(/[?&]q=(-?\d+\.?\d*),(-?\d+\.?\d*)/);
        if (match) return { lat: parseFloat(match[1]), lng: parseFloat(match[2]) };
        return null;
    }

    function openMapFromCoords(coordStr, lat, lng, nome) {
        let coords = null;
        if (lat && lng && lat !== 'null' && lng !== 'null') {
            coords = { lat: parseFloat(lat), lng: parseFloat(lng) };
        }
        if (!coords) {
            coords = parseCoordinatesFromString(coordStr);
        }
        if (!coords) {
            // Open Google Maps search with name
            window.open(`https://maps.google.com/maps?q=${encodeURIComponent(nome)}`, '_blank');
            return;
        }
        openMapModal(coords.lat, coords.lng, nome);
    }

    function openMapModal(lat, lng, nome) {
        document.getElementById('mapModalTitle').textContent = `🗺️ ${nome}`;
        document.getElementById('mapExternalLink').href = `https://maps.google.com/?q=${lat},${lng}`;
        document.getElementById('mapModal').classList.add('active');

        setTimeout(() => {
            if (mapInstance) {
                mapInstance.remove();
                mapInstance = null;
            }
            mapInstance = L.map('mapContainer').setView([lat, lng], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(mapInstance);
            L.marker([lat, lng]).addTo(mapInstance)
                .bindPopup(`<strong>${nome}</strong>`)
                .openPopup();
        }, 200);
    }

    function closeMapModal() {
        document.getElementById('mapModal').classList.remove('active');
        if (mapInstance) {
            mapInstance.remove();
            mapInstance = null;
        }
    }

    // ===== FORM FUNCTIONS =====
    function showForm(id=null) {
        document.getElementById('gridView').classList.add('hidden');
        document.getElementById('formView').classList.add('active');
        resetForm();
        if (!id) {
            document.getElementById('formHeader').textContent = '[05] - CADASTRAR/ATUALIZAR - CLIENTE';
            document.getElementById('btnIncluir').textContent = '➕ Incluir';
        }
    }

    function showGrid() {
        document.getElementById('formView').classList.remove('active');
        document.getElementById('gridView').classList.remove('hidden');
        loadClientes();
    }

    function resetForm() {
        document.getElementById('clienteId').value = '';
        ['f_nome','f_fone','f_endereco','f_complemento','f_coordenadas'].forEach(id => document.getElementById(id).value='');
        document.getElementById('f_cidade_id').value = '';
        document.querySelector('input[name="f_situacao"][value="a"]').checked = true;
        updateFormMapLink();
    }

    function resetFormAndNew() {
        resetForm();
        document.getElementById('formHeader').textContent = '[05] - CADASTRAR/ATUALIZAR - CLIENTE';
        document.getElementById('btnIncluir').textContent = '➕ Incluir';
    }

    function updateFormMapLink() {
        const coords = document.getElementById('f_coordenadas').value;
        const link = document.getElementById('f_mapa_link');
        const parsed = parseCoordinatesFromString(coords);
        if (parsed) {
            link.href = `https://maps.google.com/?q=${parsed.lat},${parsed.lng}`;
            link.style.pointerEvents = 'auto';
            link.style.opacity = '1';
        } else if (coords && coords.startsWith('http')) {
            link.href = coords;
            link.style.pointerEvents = 'auto';
            link.style.opacity = '1';
        } else {
            link.href = '#';
            link.style.pointerEvents = 'none';
            link.style.opacity = '0.5';
        }
    }

    function openFormMap(e) {
        e.preventDefault();
        const coords = document.getElementById('f_coordenadas').value;
        const parsed = parseCoordinatesFromString(coords);
        if (parsed) {
            openMapModal(parsed.lat, parsed.lng, document.getElementById('f_nome').value || 'Cliente');
        } else if (coords && coords.startsWith('http')) {
            window.open(coords, '_blank');
        }
    }

    async function editCliente(id) {
        try {
            const res = await fetch(`cliente.php?ajax=get&id=${id}`);
            const data = await res.json();
            if (!data.success) throw new Error(data.error);
            const c = data.data;
            showForm(id);
            document.getElementById('formHeader').textContent = '[05] - CADASTRAR/ATUALIZAR - CLIENTE';
            document.getElementById('btnIncluir').textContent = '💾 Atualizar';
            document.getElementById('clienteId').value = c.id;
            document.getElementById('f_nome').value = c.nome || '';
            document.getElementById('f_fone').value = c.fone || '';
            document.getElementById('f_endereco').value = c.endereco || '';
            document.getElementById('f_complemento').value = c.complemento || '';
            document.getElementById('f_coordenadas').value = c.coordenadas || '';
            document.getElementById('f_cidade_id').value = c.cidade_id || '';
            const sit = c.situacao || 'a';
            const radio = document.querySelector(`input[name="f_situacao"][value="${sit}"]`);
            if (radio) radio.checked = true;
            updateFormMapLink();
        } catch(e) { showToast(e.message,'error'); }
    }

    async function saveCliente() {
        const fd = new FormData();
        const id = document.getElementById('clienteId').value;
        fd.append('ajax', id ? 'update' : 'create');
        if (id) fd.append('id', id);
        fd.append('nome', document.getElementById('f_nome').value);
        fd.append('fone', document.getElementById('f_fone').value);
        fd.append('endereco', document.getElementById('f_endereco').value);
        fd.append('complemento', document.getElementById('f_complemento').value);
        fd.append('coordenadas', document.getElementById('f_coordenadas').value);
        fd.append('cidade_id', document.getElementById('f_cidade_id').value);
        fd.append('situacao', document.querySelector('input[name="f_situacao"]:checked').value);

        // Try to extract lat/lng from coordenadas
        const coords = document.getElementById('f_coordenadas').value;
        const parsed = parseCoordinatesFromString(coords);
        if (parsed) {
            fd.append('latitude', parsed.lat);
            fd.append('longitude', parsed.lng);
        }

        try {
            const res = await fetch('cliente.php', {method:'POST', body:fd});
            const data = await res.json();
            if (!data.success) throw new Error(data.error);
            showToast(data.message,'success');
            if (!id && data.id) {
                document.getElementById('clienteId').value = data.id;
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
            const res=await fetch('cliente.php',{method:'POST',body:fd});
            const data=await res.json();
            if(!data.success) throw new Error(data.error);
            showToast(data.message,'success'); closeDeleteModal(); loadClientes();
        } catch(e) { showToast(e.message,'error'); }
    }

    // ===== UTILS =====
    function showToast(msg,type='success') { const t=document.getElementById('toast'); t.textContent=msg; t.className=`toast ${type} show`; setTimeout(()=>t.classList.remove('show'),3000); }
    function esc(t) { if(!t) return ''; const d=document.createElement('div'); d.textContent=t; return d.innerHTML; }
    </script>
</body>
</html>
