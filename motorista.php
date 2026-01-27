<?php
/**
 * CRUD de Motoristas
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
$currentPage = 'motorista.php';

// AJAX: Listar motoristas
if (isset($_GET['ajax']) && $_GET['ajax'] === 'list') {
    header('Content-Type: application/json');
    try {
        $search = $_GET['search'] ?? '';
        $sortCol = $_GET['sort'] ?? 'nome';
        $sortDir = strtoupper($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        // Validar coluna de ordenação
        $allowedCols = ['id', 'nome', 'fone', 'situacao', 'usuario'];
        if (!in_array($sortCol, $allowedCols))
            $sortCol = 'nome';

        $params = [];
        $sql = "SELECT id, nome, situacao, fone, carro_id, usuario, latitude, longitude 
                FROM prod_vt.motorista WHERE 1=1";

        if (!empty($search)) {
            $sql .= " AND nome LIKE :search";
            $params[':search'] = "%{$search}%";
        }

        $sql .= " ORDER BY {$sortCol} {$sortDir}";

        $motoristas = $db->query($sql, $params);
        echo json_encode(['success' => true, 'data' => $motoristas]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Buscar motorista por ID
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get') {
    header('Content-Type: application/json');
    try {
        $id = intval($_GET['id'] ?? 0);
        if (!$id)
            throw new Exception('ID inválido');

        $motorista = $db->queryOne("SELECT id, nome, situacao, fone, carro_id, usuario 
                                    FROM prod_vt.motorista WHERE id = ?", [$id]);
        if (!$motorista)
            throw new Exception('Motorista não encontrado');

        echo json_encode(['success' => true, 'data' => $motorista]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Criar motorista
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'create') {
    header('Content-Type: application/json');
    try {
        $nome = trim($_POST['nome'] ?? '');
        $fone = trim($_POST['fone'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $senha = trim($_POST['senha'] ?? '');
        $situacao = trim($_POST['situacao'] ?? 'a');

        if (empty($nome))
            throw new Exception('Nome é obrigatório');

        $latitude = trim($_POST['latitude'] ?? '');
        $longitude = trim($_POST['longitude'] ?? '');
        $latitude = $latitude !== '' ? floatval($latitude) : null;
        $longitude = $longitude !== '' ? floatval($longitude) : null;

        $sql = "INSERT INTO prod_vt.motorista (nome, fone, usuario, senha, situacao, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $senhaHash = !empty($senha) ? password_hash($senha, PASSWORD_DEFAULT) : '';

        $db->execute($sql, [$nome, $fone, $usuario, $senhaHash, $situacao, $latitude, $longitude]);
        $newId = $db->lastInsertId();

        echo json_encode(['success' => true, 'message' => 'Motorista criado com sucesso!', 'id' => $newId]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Atualizar motorista
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'update') {
    header('Content-Type: application/json');
    try {
        $id = intval($_POST['id'] ?? 0);
        if (!$id)
            throw new Exception('ID inválido');

        $nome = trim($_POST['nome'] ?? '');
        $fone = trim($_POST['fone'] ?? '');
        $usuario = trim($_POST['usuario'] ?? '');
        $senha = trim($_POST['senha'] ?? '');
        $situacao = trim($_POST['situacao'] ?? 'a');

        if (empty($nome))
            throw new Exception('Nome é obrigatório');

        $latitude = trim($_POST['latitude'] ?? '');
        $longitude = trim($_POST['longitude'] ?? '');
        $latitude = $latitude !== '' ? floatval($latitude) : null;
        $longitude = $longitude !== '' ? floatval($longitude) : null;

        if (!empty($senha)) {
            $sql = "UPDATE prod_vt.motorista SET nome = ?, fone = ?, usuario = ?, senha = ?, situacao = ?, latitude = ?, longitude = ? WHERE id = ?";
            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
            $db->execute($sql, [$nome, $fone, $usuario, $senhaHash, $situacao, $latitude, $longitude, $id]);
        } else {
            $sql = "UPDATE prod_vt.motorista SET nome = ?, fone = ?, usuario = ?, situacao = ?, latitude = ?, longitude = ? WHERE id = ?";
            $db->execute($sql, [$nome, $fone, $usuario, $situacao, $latitude, $longitude, $id]);
        }

        echo json_encode(['success' => true, 'message' => 'Motorista atualizado com sucesso!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Deletar motorista (soft delete - mudar situação)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'delete') {
    header('Content-Type: application/json');
    try {
        $id = intval($_POST['id'] ?? 0);
        if (!$id)
            throw new Exception('ID inválido');

        // Soft delete - apenas inativa
        $db->execute("UPDATE prod_vt.motorista SET situacao = 'i' WHERE id = ?", [$id]);

        echo json_encode(['success' => true, 'message' => 'Motorista inativado com sucesso!']);
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
    <title>Motoristas - Victor Transportes</title>
    <style>
        :root {
            --primary: #1F6F54;
            --primary-light: #2F8F6B;
            --primary-bg: #E8F4EF;
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

        /* Main Container */
        .main-content {
            padding: 20px;
            width: 100%;
        }

        /* Page Header */
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
        }

        .btn-secondary {
            background: #e9ecef;
            color: var(--text);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.8rem;
        }

        /* Filter Bar */
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

        /* Table Container */
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

        th {
            background: #f8f9fa;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            cursor: pointer;
            user-select: none;
            transition: background 0.2s;
        }

        th:hover {
            background: var(--primary-bg);
        }

        th.sorted {
            background: var(--primary-bg);
            color: var(--primary);
        }

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

        /* Status Badge */
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

        /* Actions */
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

        .action-btn.map {
            background: #dcfce7;
            color: #166534;
        }

        .action-btn.map:hover {
            background: #bbf7d0;
        }

        .action-btn.map.disabled {
            background: #f3f4f6;
            color: #9ca3af;
            cursor: not-allowed;
        }

        /* Modal */
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        /* Toast */
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

        .toast.success {
            background: var(--success);
        }

        .toast.error {
            background: var(--danger);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-state .icon {
            font-size: 3rem;
            margin-bottom: 16px;
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 16px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .filter-bar {
                flex-direction: column;
            }

            .filter-bar input {
                width: 100%;
            }

            .table-container {
                overflow-x: auto;
            }

            table {
                min-width: 600px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }

        /* Map Modal */
        #mapContainer {
            height: 400px;
            width: 100%;
            border-radius: 8px;
            z-index: 1;
        }
    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content page-with-sidebar">
        <div class="page-header">
            <h1>🚗 Motoristas</h1>
            <button class="btn btn-primary" onclick="openModal()">
                ➕ Novo Motorista
            </button>
        </div>

        <div class="filter-bar">
            <input type="text" id="searchInput" placeholder="🔍 Buscar por nome..." oninput="debounceSearch()">
            <button class="btn btn-secondary" onclick="loadMotoristas()">🔄 Atualizar</button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th data-sort="id" onclick="sortBy('id')" style="width: 60px;">
                            ID <span class="sort-icon">↕</span>
                        </th>
                        <th data-sort="nome" onclick="sortBy('nome')">
                            Nome <span class="sort-icon">↕</span>
                        </th>
                        <th data-sort="fone" onclick="sortBy('fone')" style="width: 140px;">
                            Telefone <span class="sort-icon">↕</span>
                        </th>
                        <th data-sort="usuario" onclick="sortBy('usuario')" style="width: 120px;">
                            Usuário <span class="sort-icon">↕</span>
                        </th>
                        <th data-sort="situacao" onclick="sortBy('situacao')" style="width: 100px;">
                            Situação <span class="sort-icon">↕</span>
                        </th>
                        <th style="width: 100px;">Ações</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr>
                        <td colspan="6" class="loading">🔄 Carregando...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Modal Criar/Editar -->
    <div class="modal-overlay" id="modal">
        <div class="modal">
            <div class="modal-header">
                <h2 id="modalTitle">Novo Motorista</h2>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <form id="motoristaForm" onsubmit="saveMotorista(event)">
                <div class="modal-body">
                    <input type="hidden" id="motoristaId" name="id">

                    <div class="form-group">
                        <label for="nome">Nome *</label>
                        <input type="text" id="nome" name="nome" required placeholder="Nome completo do motorista">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="fone">Telefone</label>
                            <input type="text" id="fone" name="fone" placeholder="(00) 00000-0000">
                        </div>
                        <div class="form-group">
                            <label for="situacao">Situação</label>
                            <select id="situacao" name="situacao">
                                <option value="a">Ativo</option>
                                <option value="i">Inativo</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="usuario">Usuário</label>
                            <input type="text" id="usuario" name="usuario" placeholder="Login do motorista">
                        </div>
                        <div class="form-group">
                            <label for="senha">Senha</label>
                            <input type="password" id="senha" name="senha" placeholder="Deixe vazio para manter">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="latitude">Latitude</label>
                            <input type="text" id="latitude" name="latitude" placeholder="-7.985626">
                        </div>
                        <div class="form-group">
                            <label for="longitude">Longitude</label>
                            <input type="text" id="longitude" name="longitude" placeholder="-35.049240">
                        </div>
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
                <p>Deseja realmente inativar o motorista <strong id="deleteName"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
                <button class="btn btn-danger" onclick="confirmDelete()">🗑️ Inativar</button>
            </div>
        </div>
    </div>

    <!-- Modal Mapa -->
    <div class="modal-overlay" id="mapModal">
        <div class="modal" style="max-width: 600px;">
            <div class="modal-header">
                <h2 id="mapModalTitle">🗺️ Localização</h2>
                <button class="modal-close" onclick="closeMapModal()">×</button>
            </div>
            <div class="modal-body" style="padding: 16px;">
                <div id="mapContainer"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeMapModal()">Fechar</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <script>
        // Estado
        let currentSort = 'nome';
        let currentDir = 'ASC';
        let searchTimeout = null;
        let deleteId = null;
        let mapInstance = null;

        // Carrega dados na inicialização
        document.addEventListener('DOMContentLoaded', loadMotoristas);

        // Carregar motoristas
        async function loadMotoristas() {
            const search = document.getElementById('searchInput').value;
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '<tr><td colspan="6" class="loading">🔄 Carregando...</td></tr>';

            try {
                const url = `motorista.php?ajax=list&search=${encodeURIComponent(search)}&sort=${currentSort}&dir=${currentDir}`;
                const res = await fetch(url);
                const data = await res.json();

                if (!data.success) throw new Error(data.error);

                if (data.data.length === 0) {
                    tbody.innerHTML = `
                        <tr><td colspan="6">
                            <div class="empty-state">
                                <div class="icon">🚗</div>
                                <p>Nenhum motorista encontrado</p>
                            </div>
                        </td></tr>`;
                    return;
                }

                tbody.innerHTML = data.data.map(m => `
                    <tr>
                        <td>${m.id}</td>
                        <td><strong>${escapeHtml(m.nome)}</strong></td>
                        <td>${escapeHtml(m.fone || '-')}</td>
                        <td>${escapeHtml(m.usuario || '-')}</td>
                        <td>
                            <span class="badge ${m.situacao === 'a' ? 'badge-success' : 'badge-danger'}">
                                ${m.situacao === 'a' ? 'Ativo' : 'Inativo'}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <button class="action-btn map ${m.latitude && m.longitude ? '' : 'disabled'}" 
                                    onclick="${m.latitude && m.longitude ? `openMapModal(${m.latitude}, ${m.longitude}, '${escapeHtml(m.nome)}')` : 'void(0)'}" 
                                    title="${m.latitude && m.longitude ? 'Ver no mapa' : 'Sem localização'}">🗺️</button>
                                <button class="action-btn edit" onclick="editMotorista(${m.id})" title="Editar">✏️</button>
                                <button class="action-btn delete" onclick="openDeleteModal(${m.id}, '${escapeHtml(m.nome)}')" title="Inativar">🗑️</button>
                            </div>
                        </td>
                    </tr>
                `).join('');

                updateSortHeaders();
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="6" class="empty-state">❌ Erro ao carregar dados</td></tr>`;
                showToast(e.message, 'error');
            }
        }

        // Ordenação
        function sortBy(col) {
            if (currentSort === col) {
                currentDir = currentDir === 'ASC' ? 'DESC' : 'ASC';
            } else {
                currentSort = col;
                currentDir = 'ASC';
            }
            loadMotoristas();
        }

        function updateSortHeaders() {
            document.querySelectorAll('th[data-sort]').forEach(th => {
                th.classList.remove('sorted');
                const icon = th.querySelector('.sort-icon');
                icon.textContent = '↕';
            });
            const activeTh = document.querySelector(`th[data-sort="${currentSort}"]`);
            if (activeTh) {
                activeTh.classList.add('sorted');
                activeTh.querySelector('.sort-icon').textContent = currentDir === 'ASC' ? '↑' : '↓';
            }
        }

        // Debounce search
        function debounceSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(loadMotoristas, 300);
        }

        // Modal
        function openModal(id = null) {
            document.getElementById('modal').classList.add('active');
            document.getElementById('modalTitle').textContent = id ? 'Editar Motorista' : 'Novo Motorista';
            document.getElementById('motoristaForm').reset();
            document.getElementById('motoristaId').value = '';
        }

        function closeModal() {
            document.getElementById('modal').classList.remove('active');
        }

        // Editar
        async function editMotorista(id) {
            try {
                const res = await fetch(`motorista.php?ajax=get&id=${id}`);
                const data = await res.json();

                if (!data.success) throw new Error(data.error);

                const m = data.data;
                document.getElementById('motoristaId').value = m.id;
                document.getElementById('nome').value = m.nome;
                document.getElementById('fone').value = m.fone || '';
                document.getElementById('usuario').value = m.usuario || '';
                document.getElementById('situacao').value = m.situacao;
                document.getElementById('latitude').value = m.latitude || '';
                document.getElementById('longitude').value = m.longitude || '';
                document.getElementById('senha').value = '';
                document.getElementById('senha').placeholder = 'Deixe vazio para manter';

                document.getElementById('modalTitle').textContent = 'Editar Motorista';
                document.getElementById('modal').classList.add('active');
            } catch (e) {
                showToast(e.message, 'error');
            }
        }

        // Salvar
        async function saveMotorista(e) {
            e.preventDefault();
            const form = document.getElementById('motoristaForm');
            const formData = new FormData(form);
            const isEdit = !!formData.get('id');
            formData.append('ajax', isEdit ? 'update' : 'create');

            try {
                const res = await fetch('motorista.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (!data.success) throw new Error(data.error);

                showToast(data.message, 'success');
                closeModal();
                loadMotoristas();
            } catch (e) {
                showToast(e.message, 'error');
            }
        }

        // Delete modal
        function openDeleteModal(id, nome) {
            deleteId = id;
            document.getElementById('deleteName').textContent = nome;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteId = null;
        }

        async function confirmDelete() {
            if (!deleteId) return;

            try {
                const formData = new FormData();
                formData.append('ajax', 'delete');
                formData.append('id', deleteId);

                const res = await fetch('motorista.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (!data.success) throw new Error(data.error);

                showToast(data.message, 'success');
                closeDeleteModal();
                loadMotoristas();
            } catch (e) {
                showToast(e.message, 'error');
            }
        }

        // Toast
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast ${type} show`;
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        // Escape HTML
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Map Modal
        function openMapModal(lat, lng, nome) {
            document.getElementById('mapModalTitle').textContent = `🗺️ ${nome}`;
            document.getElementById('mapModal').classList.add('active');
            
            // Aguarda o modal abrir para inicializar o mapa
            setTimeout(() => {
                if (mapInstance) {
                    mapInstance.remove();
                }
                
                mapInstance = L.map('mapContainer').setView([lat, lng], 15);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap'
                }).addTo(mapInstance);
                
                L.marker([lat, lng]).addTo(mapInstance)
                    .bindPopup(`<b>${nome}</b><br>Lat: ${lat}<br>Lng: ${lng}`)
                    .openPopup();
            }, 100);
        }

        function closeMapModal() {
            document.getElementById('mapModal').classList.remove('active');
            if (mapInstance) {
                mapInstance.remove();
                mapInstance = null;
            }
        }
    </script>
</body>

</html>