<?php
/**
 * CRUD de Viagens
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
$currentPage = 'viagem.php';

// AJAX: Listar viagens
if (isset($_GET['ajax']) && $_GET['ajax'] === 'list') {
    header('Content-Type: application/json');
    try {
        $search = $_GET['search'] ?? '';
        $sortCol = $_GET['sort'] ?? 'data_viagem';
        $sortDir = strtoupper($_GET['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        // Validar coluna de ordenação
        $allowedCols = ['id', 'data_viagem', 'motorista_nome', 'situacao_nome', 'qde_pedido'];
        if (!in_array($sortCol, $allowedCols))
            $sortCol = 'data_viagem';

        // Map sort columns to actual SQL columns
        $sortMap = [
            'id' => 'v.id',
            'data_viagem' => 'v.data_viagem',
            'motorista_nome' => 'm.nome',
            'situacao_nome' => 'rs.descricao',
            'qde_pedido' => 'qde_pedido'
        ];
        $sqlSort = $sortMap[$sortCol] ?? 'v.data_viagem';

        $params = [];
        $sql = "SELECT
                    v.id as id,
                    v.data_viagem as data_viagem,
                    v.motorista_id as motorista_id,
                    v.remessa_situacao_id as remessa_situacao_id,
                    COALESCE(m.nome, 'N/A') as motorista_nome,
                    COALESCE(rs.descricao, 'N/A') as situacao_nome,
                    (SELECT COUNT(*) FROM remessa r WHERE r.viagem_id = v.id) as qde_pedido
                FROM viagem v
                LEFT JOIN prod_vt.motorista m ON m.id = v.motorista_id
                LEFT JOIN remessa_situacao rs ON rs.id = v.remessa_situacao_id
                WHERE 1=1";

        if (!empty($search)) {
            $sql .= " AND (m.nome LIKE :search OR CAST(v.id AS CHAR) LIKE :search2)";
            $params[':search'] = "%{$search}%";
            $params[':search2'] = "%{$search}%";
        }

        $sql .= " ORDER BY {$sqlSort} {$sortDir}";

        $viagens = $db->query($sql, $params);
        echo json_encode(['success' => true, 'data' => $viagens]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Buscar viagem por ID
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get') {
    header('Content-Type: application/json');
    try {
        $id = intval($_GET['id'] ?? 0);
        if (!$id)
            throw new Exception('ID inválido');

        $viagem = $db->queryOne("SELECT v.id, v.data_viagem, v.motorista_id, v.remessa_situacao_id
                                 FROM viagem v WHERE v.id = ?", [$id]);
        if (!$viagem)
            throw new Exception('Viagem não encontrada');

        echo json_encode(['success' => true, 'data' => $viagem]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Listar motoristas para select
if (isset($_GET['ajax']) && $_GET['ajax'] === 'motoristas') {
    header('Content-Type: application/json');
    try {
        $motoristas = $db->query("SELECT id, nome FROM prod_vt.motorista WHERE situacao = 'a' ORDER BY nome");
        echo json_encode(['success' => true, 'data' => $motoristas]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Listar situações para select
if (isset($_GET['ajax']) && $_GET['ajax'] === 'situacoes') {
    header('Content-Type: application/json');
    try {
        $situacoes = $db->query("SELECT id, descricao FROM remessa_situacao ORDER BY descricao");
        echo json_encode(['success' => true, 'data' => $situacoes]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Criar viagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'create') {
    header('Content-Type: application/json');
    try {
        $data_viagem = trim($_POST['data_viagem'] ?? '');
        $motorista_id = intval($_POST['motorista_id'] ?? 0);
        $remessa_situacao_id = intval($_POST['remessa_situacao_id'] ?? 0);

        if (empty($data_viagem))
            throw new Exception('Data da viagem é obrigatória');
        if (!$motorista_id)
            throw new Exception('Motorista é obrigatório');

        $sql = "INSERT INTO viagem (data_viagem, motorista_id, remessa_situacao_id) VALUES (?, ?, ?)";
        $db->execute($sql, [$data_viagem, $motorista_id, $remessa_situacao_id ?: null]);
        $newId = $db->lastInsertId();

        echo json_encode(['success' => true, 'message' => 'Viagem criada com sucesso!', 'id' => $newId]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Atualizar viagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'update') {
    header('Content-Type: application/json');
    try {
        $id = intval($_POST['id'] ?? 0);
        if (!$id)
            throw new Exception('ID inválido');

        $data_viagem = trim($_POST['data_viagem'] ?? '');
        $motorista_id = intval($_POST['motorista_id'] ?? 0);
        $remessa_situacao_id = intval($_POST['remessa_situacao_id'] ?? 0);

        if (empty($data_viagem))
            throw new Exception('Data da viagem é obrigatória');
        if (!$motorista_id)
            throw new Exception('Motorista é obrigatório');

        $sql = "UPDATE viagem SET data_viagem = ?, motorista_id = ?, remessa_situacao_id = ? WHERE id = ?";
        $db->execute($sql, [$data_viagem, $motorista_id, $remessa_situacao_id ?: null, $id]);

        echo json_encode(['success' => true, 'message' => 'Viagem atualizada com sucesso!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Deletar viagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'delete') {
    header('Content-Type: application/json');
    try {
        $id = intval($_POST['id'] ?? 0);
        if (!$id)
            throw new Exception('ID inválido');

        // Check if viagem has remessas
        $count = $db->queryOne("SELECT COUNT(*) as total FROM remessa WHERE viagem_id = ?", [$id]);
        if ($count && $count['total'] > 0) {
            throw new Exception('Não é possível excluir esta viagem pois existem ' . $count['total'] . ' pedido(s) vinculado(s).');
        }

        $db->execute("DELETE FROM viagem WHERE id = ?", [$id]);

        echo json_encode(['success' => true, 'message' => 'Viagem excluída com sucesso!']);
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
    <title>Viagens - Victor Transportes</title>
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

        /* Stats Bar */
        .stats-bar {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: var(--card);
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 180px;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        .stat-icon.total {
            background: var(--primary-bg);
            color: var(--primary);
        }

        .stat-icon.pedidos {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .stat-icon.hoje {
            background: #fef3c7;
            color: #92400e;
        }

        .stat-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text);
        }

        .stat-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
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

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-secondary {
            background: #f3f4f6;
            color: #4b5563;
        }

        /* Pedido count badge */
        .pedido-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 28px;
            padding: 0 10px;
            border-radius: 14px;
            font-size: 0.85rem;
            font-weight: 700;
            background: var(--primary-bg);
            color: var(--primary);
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

        .action-btn.view {
            background: #dcfce7;
            color: #166534;
        }

        .action-btn.view:hover {
            background: #bbf7d0;
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
                min-width: 700px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .stats-bar {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content page-with-sidebar">
        <div class="page-header">
            <h1>🚐 Relação de Viagens</h1>
            <button class="btn btn-primary" onclick="openModal()">
                ➕ Nova Viagem
            </button>
        </div>

        <!-- Stats -->
        <div class="stats-bar" id="statsBar">
            <div class="stat-card">
                <div class="stat-icon total">🚐</div>
                <div>
                    <div class="stat-value" id="statTotal">-</div>
                    <div class="stat-label">Total Viagens</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pedidos">📦</div>
                <div>
                    <div class="stat-value" id="statPedidos">-</div>
                    <div class="stat-label">Total Pedidos</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon hoje">📅</div>
                <div>
                    <div class="stat-value" id="statHoje">-</div>
                    <div class="stat-label">Viagens Hoje</div>
                </div>
            </div>
        </div>

        <div class="filter-bar">
            <input type="text" id="searchInput" placeholder="🔍 Buscar por motorista ou código..." oninput="debounceSearch()">
            <button class="btn btn-secondary" onclick="loadViagens()">🔄 Atualizar</button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th data-sort="id" onclick="sortBy('id')" style="width: 80px;">
                            CÓD. <span class="sort-icon">↕</span>
                        </th>
                        <th data-sort="data_viagem" onclick="sortBy('data_viagem')" style="width: 140px;">
                            Data Viagem <span class="sort-icon">↕</span>
                        </th>
                        <th data-sort="motorista_nome" onclick="sortBy('motorista_nome')">
                            Motorista <span class="sort-icon">↕</span>
                        </th>
                        <th data-sort="situacao_nome" onclick="sortBy('situacao_nome')">
                            Situação Viagem <span class="sort-icon">↕</span>
                        </th>
                        <th data-sort="qde_pedido" onclick="sortBy('qde_pedido')" style="width: 120px;">
                            Qde Pedido <span class="sort-icon">↕</span>
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
                <h2 id="modalTitle">Nova Viagem</h2>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <form id="viagemForm" onsubmit="saveViagem(event)">
                <div class="modal-body">
                    <input type="hidden" id="viagemId" name="id">

                    <div class="form-group">
                        <label for="data_viagem">Data da Viagem *</label>
                        <input type="date" id="data_viagem" name="data_viagem" required>
                    </div>

                    <div class="form-group">
                        <label for="motorista_id">Motorista *</label>
                        <select id="motorista_id" name="motorista_id" required>
                            <option value="">Selecione o motorista...</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="remessa_situacao_id">Situação da Viagem</label>
                        <select id="remessa_situacao_id" name="remessa_situacao_id">
                            <option value="">Selecione a situação...</option>
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
                <p>Deseja realmente excluir a viagem <strong id="deleteInfo"></strong>?</p>
                <p style="margin-top: 8px; font-size: 0.85rem; color: var(--text-muted);">
                    ⚠️ Viagens com pedidos vinculados não podem ser excluídas.
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
                <button class="btn btn-danger" onclick="confirmDelete()">🗑️ Excluir</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <script>
        // Estado
        let currentSort = 'data_viagem';
        let currentDir = 'DESC';
        let searchTimeout = null;
        let deleteId = null;
        let motoristasCache = [];
        let situacoesCache = [];

        // Carrega dados na inicialização
        document.addEventListener('DOMContentLoaded', () => {
            loadViagens();
            loadSelectData();
        });

        // Carregar dados para selects
        async function loadSelectData() {
            try {
                const [motRes, sitRes] = await Promise.all([
                    fetch('viagem.php?ajax=motoristas'),
                    fetch('viagem.php?ajax=situacoes')
                ]);
                const motData = await motRes.json();
                const sitData = await sitRes.json();

                if (motData.success) {
                    motoristasCache = motData.data;
                    const select = document.getElementById('motorista_id');
                    select.innerHTML = '<option value="">Selecione o motorista...</option>' +
                        motData.data.map(m => `<option value="${m.id}">${escapeHtml(m.nome)}</option>`).join('');
                }

                if (sitData.success) {
                    situacoesCache = sitData.data;
                    const select = document.getElementById('remessa_situacao_id');
                    select.innerHTML = '<option value="">Selecione a situação...</option>' +
                        sitData.data.map(s => `<option value="${s.id}">${escapeHtml(s.descricao)}</option>`).join('');
                }
            } catch (e) {
                console.error('Erro ao carregar dados dos selects:', e);
            }
        }

        // Carregar viagens
        async function loadViagens() {
            const search = document.getElementById('searchInput').value;
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '<tr><td colspan="6" class="loading">🔄 Carregando...</td></tr>';

            try {
                const url = `viagem.php?ajax=list&search=${encodeURIComponent(search)}&sort=${currentSort}&dir=${currentDir}`;
                const res = await fetch(url);
                const data = await res.json();

                if (!data.success) throw new Error(data.error);

                // Update stats
                updateStats(data.data);

                if (data.data.length === 0) {
                    tbody.innerHTML = `
                        <tr><td colspan="6">
                            <div class="empty-state">
                                <div class="icon">🚐</div>
                                <p>Nenhuma viagem encontrada</p>
                            </div>
                        </td></tr>`;
                    return;
                }

                tbody.innerHTML = data.data.map(v => `
                    <tr>
                        <td><strong>${v.id}</strong></td>
                        <td>${formatDate(v.data_viagem)}</td>
                        <td><strong>${escapeHtml(v.motorista_nome)}</strong></td>
                        <td>
                            <span class="badge badge-info">
                                ${escapeHtml(v.situacao_nome)}
                            </span>
                        </td>
                        <td>
                            <span class="pedido-badge">${v.qde_pedido}</span>
                        </td>
                        <td>
                            <div class="actions">
                                <button class="action-btn edit" onclick="editViagem(${v.id})" title="Editar">✏️</button>
                                <button class="action-btn delete" onclick="openDeleteModal(${v.id}, '${v.id} - ${formatDate(v.data_viagem)}')" title="Excluir">🗑️</button>
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

        // Update stats
        function updateStats(data) {
            const today = new Date().toISOString().split('T')[0];
            const totalViagens = data.length;
            const totalPedidos = data.reduce((sum, v) => sum + parseInt(v.qde_pedido || 0), 0);
            const viagensHoje = data.filter(v => v.data_viagem === today).length;

            document.getElementById('statTotal').textContent = totalViagens;
            document.getElementById('statPedidos').textContent = totalPedidos;
            document.getElementById('statHoje').textContent = viagensHoje;
        }

        // Format date
        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const parts = dateStr.split('-');
            if (parts.length === 3) {
                return `${parts[2]}/${parts[1]}/${parts[0]}`;
            }
            return dateStr;
        }

        // Ordenação
        function sortBy(col) {
            if (currentSort === col) {
                currentDir = currentDir === 'ASC' ? 'DESC' : 'ASC';
            } else {
                currentSort = col;
                currentDir = col === 'data_viagem' ? 'DESC' : 'ASC';
            }
            loadViagens();
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
            searchTimeout = setTimeout(loadViagens, 300);
        }

        // Modal
        function openModal(id = null) {
            document.getElementById('modal').classList.add('active');
            document.getElementById('modalTitle').textContent = id ? 'Editar Viagem' : 'Nova Viagem';
            document.getElementById('viagemForm').reset();
            document.getElementById('viagemId').value = '';

            // Set today's date as default for new viagem
            if (!id) {
                document.getElementById('data_viagem').value = new Date().toISOString().split('T')[0];
            }
        }

        function closeModal() {
            document.getElementById('modal').classList.remove('active');
        }

        // Editar
        async function editViagem(id) {
            try {
                const res = await fetch(`viagem.php?ajax=get&id=${id}`);
                const data = await res.json();

                if (!data.success) throw new Error(data.error);

                const v = data.data;
                document.getElementById('viagemId').value = v.id;
                document.getElementById('data_viagem').value = v.data_viagem || '';
                document.getElementById('motorista_id').value = v.motorista_id || '';
                document.getElementById('remessa_situacao_id').value = v.remessa_situacao_id || '';

                document.getElementById('modalTitle').textContent = 'Editar Viagem';
                document.getElementById('modal').classList.add('active');
            } catch (e) {
                showToast(e.message, 'error');
            }
        }

        // Salvar
        async function saveViagem(e) {
            e.preventDefault();
            const form = document.getElementById('viagemForm');
            const formData = new FormData(form);
            const isEdit = !!formData.get('id');
            formData.append('ajax', isEdit ? 'update' : 'create');

            try {
                const res = await fetch('viagem.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (!data.success) throw new Error(data.error);

                showToast(data.message, 'success');
                closeModal();
                loadViagens();
            } catch (e) {
                showToast(e.message, 'error');
            }
        }

        // Delete modal
        function openDeleteModal(id, info) {
            deleteId = id;
            document.getElementById('deleteInfo').textContent = info;
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

                const res = await fetch('viagem.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (!data.success) throw new Error(data.error);

                showToast(data.message, 'success');
                closeDeleteModal();
                loadViagens();
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
    </script>
</body>

</html>
