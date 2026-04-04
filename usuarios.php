<?php
/**
 * CRUD de Usuários do Sistema
 * Victor Transportes - Sistema de Gestão
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Apenas admins podem gerenciar usuários
if (empty($_SESSION['user_is_admin'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'config.php';
require_once 'Database.php';

$db = new Database();
$currentPage = 'usuarios.php';

// AJAX: Listar usuários
if (isset($_GET['ajax']) && $_GET['ajax'] === 'list') {
    header('Content-Type: application/json');
    try {
        $search  = $_GET['search'] ?? '';
        $sortCol = $_GET['sort'] ?? 'name';
        $sortDir = strtoupper($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        $allowedCols = ['login', 'name', 'email', 'active', 'priv_admin'];
        if (!in_array($sortCol, $allowedCols)) $sortCol = 'name';

        $limit  = max(1, min(200, (int)($_GET['limit']  ?? 25)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $params = [];
        $where  = "FROM sec_users WHERE 1=1";

        if (!empty($search)) {
            $where .= " AND (name LIKE :search OR login LIKE :search OR email LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $total = (int) $db->queryOne("SELECT COUNT(*) as n {$where}", $params)['n'];

        $sql = "SELECT login, name, email, active, priv_admin, novo_sistema, mfa
                {$where} ORDER BY {$sortCol} {$sortDir} LIMIT {$limit} OFFSET {$offset}";

        $usuarios = $db->query($sql, $params);
        echo json_encode(['success' => true, 'data' => $usuarios, 'total' => $total]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Buscar usuário por login
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get') {
    header('Content-Type: application/json');
    try {
        $login = trim($_GET['login'] ?? '');
        if (empty($login)) throw new Exception('Login inválido');

        $usuario = $db->queryOne(
            "SELECT login, name, email, active, priv_admin, novo_sistema, mfa
             FROM sec_users WHERE login = ?",
            [$login]
        );
        if (!$usuario) throw new Exception('Usuário não encontrado');

        echo json_encode(['success' => true, 'data' => $usuario]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Criar usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'create') {
    header('Content-Type: application/json');
    try {
        $login      = trim($_POST['login'] ?? '');
        $name       = trim($_POST['name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $senha      = trim($_POST['senha'] ?? '');
        $active     = isset($_POST['active']) ? 1 : 0;
        $priv_admin = isset($_POST['priv_admin']) ? 1 : 0;
        $novo_sistema = isset($_POST['novo_sistema']) ? 1 : 0;

        if (empty($login)) throw new Exception('Login é obrigatório');
        if (empty($name))  throw new Exception('Nome é obrigatório');
        if (empty($senha)) throw new Exception('Senha é obrigatória para novo usuário');

        // Verifica se login já existe
        $existe = $db->queryOne("SELECT login FROM sec_users WHERE login = ?", [$login]);
        if ($existe) throw new Exception('Login já está em uso');

        $senhaHash = md5($senha);

        $db->execute(
            "INSERT INTO sec_users (login, pswd, name, email, active, priv_admin, novo_sistema)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$login, $senhaHash, $name, $email, $active, $priv_admin, $novo_sistema]
        );

        echo json_encode(['success' => true, 'message' => 'Usuário criado com sucesso!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Atualizar usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'update') {
    header('Content-Type: application/json');
    try {
        $login      = trim($_POST['login'] ?? '');
        $name       = trim($_POST['name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $senha      = trim($_POST['senha'] ?? '');
        $active     = isset($_POST['active']) ? 1 : 0;
        $priv_admin = isset($_POST['priv_admin']) ? 1 : 0;
        $novo_sistema = isset($_POST['novo_sistema']) ? 1 : 0;

        if (empty($login)) throw new Exception('Login inválido');
        if (empty($name))  throw new Exception('Nome é obrigatório');

        if (!empty($senha)) {
            $senhaHash = md5($senha);
            $db->execute(
                "UPDATE sec_users SET name=?, email=?, pswd=?, active=?, priv_admin=?, novo_sistema=? WHERE login=?",
                [$name, $email, $senhaHash, $active, $priv_admin, $novo_sistema, $login]
            );
        } else {
            $db->execute(
                "UPDATE sec_users SET name=?, email=?, active=?, priv_admin=?, novo_sistema=? WHERE login=?",
                [$name, $email, $active, $priv_admin, $novo_sistema, $login]
            );
        }

        echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Inativar usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'delete') {
    header('Content-Type: application/json');
    try {
        $login = trim($_POST['login'] ?? '');
        if (empty($login)) throw new Exception('Login inválido');

        // Impede inativar o próprio usuário
        if ($login === $_SESSION['user_login']) {
            throw new Exception('Você não pode inativar seu próprio usuário');
        }

        $db->execute("UPDATE sec_users SET active = 0 WHERE login = ?", [$login]);
        echo json_encode(['success' => true, 'message' => 'Usuário inativado com sucesso!']);
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
    <title>Usuários - <?php echo EMPRESA_NOME; ?></title>
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

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        .main-content { padding: 20px; width: 100%; }

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

        .btn-primary  { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-light); }
        .btn-secondary { background: #e9ecef; color: var(--text); }
        .btn-danger   { background: var(--danger); color: white; }
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; }

        .filter-bar {
            background: var(--card);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
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
            box-shadow: 0 0 0 3px rgba(31,111,84,0.1);
        }

        .table-container {
            background: var(--card);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; }

        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border); }

        th { background: var(--primary); color: white; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; cursor: pointer; user-select: none; transition: background 0.2s; }
        th:hover { background: var(--primary-light); }
        th.sorted { background: var(--primary-light); }
        th .sort-icon { margin-left: 6px; opacity: 0.5; }
        th.sorted .sort-icon { opacity: 1; }

        tr:hover { background: #f8f9fa; }

        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-success  { background: #d1fae5; color: #065f46; }
        .badge-danger   { background: #fee2e2; color: #991b1b; }
        .badge-warning  { background: #fef3c7; color: #92400e; }
        .badge-info     { background: #dbeafe; color: #1e40af; }

        .actions { display: flex; gap: 8px; }

        .action-btn {
            width: 32px; height: 32px;
            border: none; border-radius: 6px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s;
            font-size: 1rem;
        }

        .action-btn.edit   { background: #dbeafe; color: #1d4ed8; }
        .action-btn.edit:hover { background: #bfdbfe; }
        .action-btn.delete { background: #fee2e2; color: #dc2626; }
        .action-btn.delete:hover { background: #fecaca; }

        /* Modal */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            display: none; align-items: center; justify-content: center;
            z-index: 3000; padding: 20px;
        }
        .modal-overlay.active { display: flex; }

        .modal {
            background: white; border-radius: 16px;
            width: 100%; max-width: 520px; max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
        }
        .modal-header h2 { font-size: 1.2rem; font-weight: 600; }
        .modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted); padding: 4px; }

        .modal-body { padding: 24px; }

        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 0.9rem; }
        .form-group input, .form-group select {
            width: 100%; padding: 10px 14px;
            border: 1px solid var(--border); border-radius: 8px; font-size: 0.9rem;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none; border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(31,111,84,0.1);
        }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        .form-check {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px;
            border: 1px solid var(--border); border-radius: 8px;
            cursor: pointer;
        }
        .form-check input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; accent-color: var(--primary); }
        .form-check label { cursor: pointer; font-size: 0.9rem; font-weight: 500; margin: 0; }

        .form-checks { display: flex; flex-direction: column; gap: 8px; margin-bottom: 16px; }

        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
            display: flex; justify-content: flex-end; gap: 12px;
        }

        /* Toast */
        .toast {
            position: fixed; bottom: 24px; right: 24px;
            padding: 14px 20px; border-radius: 10px; color: white; font-weight: 500;
            z-index: 4000; transform: translateY(100px); opacity: 0; transition: all 0.3s ease;
        }
        .toast.show { transform: translateY(0); opacity: 1; }
        .toast.success { background: var(--success); }
        .toast.error   { background: var(--danger); }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
        .empty-state .icon { font-size: 3rem; margin-bottom: 16px; }
        .loading { text-align: center; padding: 40px; color: var(--text-muted); }

        .hint { font-size: 0.8rem; color: var(--text-muted); margin-top: 4px; }

        @media (max-width: 768px) {
            .main-content { padding: 16px; }
            .page-header { flex-direction: column; align-items: flex-start; }
            .filter-bar { flex-direction: column; }
            .filter-bar input { width: 100%; }
            .table-container { overflow-x: auto; }
            table { min-width: 600px; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content page-with-sidebar">
        <div class="page-header">
            <h1>👤 Usuários do Sistema</h1>
            <button class="btn btn-primary" onclick="openModal()">
                ➕ Novo Usuário
            </button>
        </div>

        <div class="filter-bar">
            <input type="text" id="searchInput" placeholder="🔍 Buscar por nome, login ou e-mail..." oninput="debounceSearch()">
            <select id="pageSizeSelect" onchange="onPageSizeChange()" style="padding:8px 10px;border:1px solid #ddd;border-radius:8px;font-size:0.9rem;">
                <option value="10">10 / pág</option>
                <option value="25" selected>25 / pág</option>
                <option value="50">50 / pág</option>
                <option value="100">100 / pág</option>
            </select>
            <button class="btn btn-secondary" onclick="loadUsuarios()">🔄 Atualizar</button>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th data-sort="login" onclick="sortBy('login')" style="width:120px;">
                            Login <span class="sort-icon">↕</span>
                        </th>
                        <th data-sort="name" onclick="sortBy('name')">
                            Nome <span class="sort-icon">↕</span>
                        </th>
                        <th data-sort="email" onclick="sortBy('email')">
                            E-mail <span class="sort-icon">↕</span>
                        </th>
                        <th data-sort="priv_admin" onclick="sortBy('priv_admin')" style="width:110px;">
                            Perfil <span class="sort-icon">↕</span>
                        </th>
                        <th style="width:110px;">Novo Sist.</th>
                        <th data-sort="active" onclick="sortBy('active')" style="width:100px;">
                            Situação <span class="sort-icon">↕</span>
                        </th>
                        <th style="width:90px;">Ações</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <tr><td colspan="7" class="loading">🔄 Carregando...</td></tr>
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
                <h2 id="modalTitle">Novo Usuário</h2>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <form id="usuarioForm" onsubmit="saveUsuario(event)">
                <div class="modal-body">
                    <input type="hidden" id="usuarioLogin" name="login">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="login_input">Login *</label>
                            <input type="text" id="login_input" name="login_display" required placeholder="login.usuario">
                            <p class="hint">Único, sem espaços</p>
                        </div>
                        <div class="form-group">
                            <label for="senha">Senha</label>
                            <input type="password" id="senha" name="senha" placeholder="Deixe vazio para manter">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="name">Nome completo *</label>
                        <input type="text" id="name" name="name" required placeholder="Nome do usuário">
                    </div>

                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" placeholder="usuario@email.com">
                    </div>

                    <div class="form-checks">
                        <label class="form-check">
                            <input type="checkbox" id="active" name="active" value="1" checked>
                            <label for="active">Usuário ativo</label>
                        </label>
                        <label class="form-check">
                            <input type="checkbox" id="priv_admin" name="priv_admin" value="1">
                            <label for="priv_admin">Administrador</label>
                        </label>
                        <label class="form-check">
                            <input type="checkbox" id="novo_sistema" name="novo_sistema" value="1" checked>
                            <label for="novo_sistema">Acesso ao novo sistema</label>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary">💾 Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Confirmar Inativação -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal" style="max-width:400px;">
            <div class="modal-header">
                <h2>Confirmar Inativação</h2>
                <button class="modal-close" onclick="closeDeleteModal()">×</button>
            </div>
            <div class="modal-body">
                <p>Deseja realmente inativar o usuário <strong id="deleteName"></strong>?</p>
                <p style="margin-top:8px;font-size:0.85rem;color:#6B7280;">O usuário perderá o acesso ao sistema.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
                <button class="btn btn-danger" onclick="confirmDelete()">🗑️ Inativar</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <script>
        var VT_PRIMARY = '<?php echo EMPRESA_COR_PRIMARIA; ?>';
        var VT_TEXTO   = '<?php echo EMPRESA_COR_TEXTO; ?>';
        let currentSort = 'name';
        let currentDir  = 'ASC';
        let searchTimeout = null;
        let deleteLogin = null;
        let isEditing = false;

        // Paginação
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

            bar.style.display = 'flex';
            info.textContent  = `${from}–${to} de ${totalItems} registros`;

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
            syncUrl();
            loadUsuarios();
        }

        function onPageSizeChange() {
            pageSize    = parseInt(document.getElementById('pageSizeSelect').value);
            currentPage = 1;
            syncUrl();
            loadUsuarios();
        }

        document.addEventListener('DOMContentLoaded', () => { readUrlParams(); loadUsuarios(); });

        async function loadUsuarios() {
            const search = document.getElementById('searchInput').value;
            const tbody  = document.getElementById('tableBody');
            tbody.innerHTML = '<tr><td colspan="7" class="loading">🔄 Carregando...</td></tr>';

            const offset = (currentPage - 1) * pageSize;
            try {
                const url = `usuarios.php?ajax=list&search=${encodeURIComponent(search)}&sort=${currentSort}&dir=${currentDir}&limit=${pageSize}&offset=${offset}`;
                const res  = await fetch(url);
                const data = await res.json();

                if (!data.success) throw new Error(data.error);

                totalItems = data.total;
                renderPagination();
                syncUrl();

                if (!data.data.length) {
                    tbody.innerHTML = `
                        <tr><td colspan="7">
                            <div class="empty-state">
                                <div class="icon">👤</div>
                                <p>Nenhum usuário encontrado</p>
                            </div>
                        </td></tr>`;
                    return;
                }

                tbody.innerHTML = data.data.map(u => `
                    <tr>
                        <td><code style="font-size:0.85rem;">${escapeHtml(u.login)}</code></td>
                        <td><strong>${escapeHtml(u.name)}</strong></td>
                        <td style="color:#6B7280;font-size:0.9rem;">${escapeHtml(u.email || '-')}</td>
                        <td>
                            <span class="badge ${u.priv_admin == 1 ? 'badge-warning' : 'badge-info'}">
                                ${u.priv_admin == 1 ? '⭐ Admin' : '👤 Usuário'}
                            </span>
                        </td>
                        <td>
                            <span class="badge ${u.novo_sistema == 1 ? 'badge-success' : 'badge-danger'}">
                                ${u.novo_sistema == 1 ? 'Sim' : 'Não'}
                            </span>
                        </td>
                        <td>
                            <span class="badge ${u.active == 1 ? 'badge-success' : 'badge-danger'}">
                                ${u.active == 1 ? 'Ativo' : 'Inativo'}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <button class="action-btn edit" onclick="editUsuario('${escapeHtml(u.login)}')" title="Editar">✏️</button>
                                <button class="action-btn delete" onclick="openDeleteModal('${escapeHtml(u.login)}', '${escapeHtml(u.name)}')" title="Inativar">🗑️</button>
                            </div>
                        </td>
                    </tr>
                `).join('');

                updateSortHeaders();
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="7" class="empty-state">❌ Erro ao carregar dados</td></tr>`;
                showToast(e.message, 'error');
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
            loadUsuarios();
        }

        function updateSortHeaders() {
            document.querySelectorAll('th[data-sort]').forEach(th => {
                th.classList.remove('sorted');
                const icon = th.querySelector('.sort-icon');
                if (icon) icon.textContent = '↕';
            });
            const activeTh = document.querySelector(`th[data-sort="${currentSort}"]`);
            if (activeTh) {
                activeTh.classList.add('sorted');
                const icon = activeTh.querySelector('.sort-icon');
                if (icon) icon.textContent = currentDir === 'ASC' ? '↑' : '↓';
            }
        }

        function debounceSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => { currentPage = 1; loadUsuarios(); }, 300);
        }

        // Modal
        function openModal() {
            isEditing = false;
            document.getElementById('modal').classList.add('active');
            document.getElementById('modalTitle').textContent = 'Novo Usuário';
            document.getElementById('usuarioForm').reset();
            document.getElementById('usuarioLogin').value = '';
            document.getElementById('login_input').disabled = false;
            document.getElementById('active').checked = true;
            document.getElementById('novo_sistema').checked = true;
            document.getElementById('priv_admin').checked = false;
            document.getElementById('senha').placeholder = 'Obrigatória para novo usuário';
        }

        function closeModal() {
            document.getElementById('modal').classList.remove('active');
        }

        async function editUsuario(login) {
            try {
                const res  = await fetch(`usuarios.php?ajax=get&login=${encodeURIComponent(login)}`);
                const data = await res.json();
                if (!data.success) throw new Error(data.error);

                const u = data.data;
                isEditing = true;

                document.getElementById('usuarioLogin').value  = u.login;
                document.getElementById('login_input').value   = u.login;
                document.getElementById('login_input').disabled = true;
                document.getElementById('name').value          = u.name;
                document.getElementById('email').value         = u.email || '';
                document.getElementById('senha').value         = '';
                document.getElementById('senha').placeholder   = 'Deixe vazio para manter';
                document.getElementById('active').checked      = u.active == 1;
                document.getElementById('priv_admin').checked  = u.priv_admin == 1;
                document.getElementById('novo_sistema').checked = u.novo_sistema == 1;

                document.getElementById('modalTitle').textContent = 'Editar Usuário';
                document.getElementById('modal').classList.add('active');
            } catch (e) {
                showToast(e.message, 'error');
            }
        }

        async function saveUsuario(e) {
            e.preventDefault();
            const formData = new FormData();
            const login = isEditing
                ? document.getElementById('usuarioLogin').value
                : document.getElementById('login_input').value.trim();

            formData.append('ajax', isEditing ? 'update' : 'create');
            formData.append('login', login);
            formData.append('name',  document.getElementById('name').value);
            formData.append('email', document.getElementById('email').value);
            formData.append('senha', document.getElementById('senha').value);
            if (document.getElementById('active').checked)      formData.append('active', '1');
            if (document.getElementById('priv_admin').checked)  formData.append('priv_admin', '1');
            if (document.getElementById('novo_sistema').checked) formData.append('novo_sistema', '1');

            try {
                const res  = await fetch('usuarios.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) throw new Error(data.error);
                showToast(data.message, 'success');
                closeModal();
                loadUsuarios();
            } catch (e) {
                showToast(e.message, 'error');
            }
        }

        function openDeleteModal(login, nome) {
            deleteLogin = login;
            document.getElementById('deleteName').textContent = nome;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            deleteLogin = null;
        }

        async function confirmDelete() {
            if (!deleteLogin) return;
            try {
                const formData = new FormData();
                formData.append('ajax', 'delete');
                formData.append('login', deleteLogin);

                const res  = await fetch('usuarios.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) throw new Error(data.error);
                showToast(data.message, 'success');
                closeDeleteModal();
                loadUsuarios();
            } catch (e) {
                showToast(e.message, 'error');
            }
        }

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast ${type} show`;
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>

</html>
