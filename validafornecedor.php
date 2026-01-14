<?php
/**
 * Validação de Fornecedores
 * 
 * Aplicação otimizada para dispositivos móveis.
 */

require_once 'config.php';
require_once 'Database.php';

$db = new Database();
$fornecedores = [];
$resultados = [];
$filtroDataInicio = $_POST['data_inicio'] ?? $_GET['data_inicio'] ?? '';
$filtroDataFim = $_POST['data_fim'] ?? $_GET['data_fim'] ?? '';
$filtroFornecedor = $_POST['fornecedor_id'] ?? '';

// AJAX handlers
if (isset($_POST['ajax']) && $_POST['ajax'] === 'atualizar_status') {
    header('Content-Type: application/json');
    try {
        $ids = $_POST['ids'] ?? [];
        if (empty($ids))
            throw new Exception('Nenhum registro selecionado.');
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE prod_vt.remessa_valor SET remessa_situacao_id = 6 WHERE id IN ($placeholders) AND remessa_situacao_id = 1";
        $updated = $db->execute($sql, $ids);
        echo json_encode(['success' => true, 'message' => "$updated atualizado(s)!", 'updated' => $updated]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if (isset($_POST['ajax']) && $_POST['ajax'] === 'desfazer_entrega') {
    header('Content-Type: application/json');
    try {
        $ids = $_POST['ids'] ?? [];
        if (empty($ids))
            throw new Exception('Nenhum registro selecionado.');
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE prod_vt.remessa_valor SET remessa_situacao_id = 1 WHERE id IN ($placeholders) AND remessa_situacao_id = 6";
        $updated = $db->execute($sql, $ids);
        echo json_encode(['success' => true, 'message' => "$updated revertido(s)!", 'updated' => $updated]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'fornecedores') {
    header('Content-Type: application/json');
    try {
        $params = [];
        $sql = "SELECT DISTINCT f.id, f.descricao FROM prod_vt.remessa_valor rv
                LEFT JOIN prod_vt.fornecedor f ON f.id = rv.fornecedor_id 
                LEFT JOIN prod_vt.viagem v ON v.id = rv.remessa_viagem_id WHERE f.id IS NOT NULL";
        if (!empty($_GET['data_inicio'])) {
            $sql .= " AND DATE(v.data_viagem) >= :data_inicio";
            $params[':data_inicio'] = $_GET['data_inicio'];
        }
        if (!empty($_GET['data_fim'])) {
            $sql .= " AND DATE(v.data_viagem) <= :data_fim";
            $params[':data_fim'] = $_GET['data_fim'];
        }
        $sql .= " ORDER BY f.descricao";
        $fornecedores = $db->query($sql, $params);
        echo json_encode(['success' => true, 'data' => $fornecedores]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

try {
    $fornecedores = $db->query("SELECT id, descricao FROM prod_vt.fornecedor ORDER BY descricao");
} catch (Exception $e) {
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filtrar'])) {
    try {
        $params = [];
        $whereConditions = [];
        $sql = "SELECT rv.id, rv.valor, rv.remessa_id, r.viagem_id, v.data_viagem, rv.remessa_viagem_id, rv.qde, rv.cliente_id, c.nome AS cliente_nome, rv.fornecedor_id, f.descricao AS fornecedor_descricao, rv.remessa_situacao_id, rs.descricao AS situacao_descricao
                FROM prod_vt.remessa_valor rv
                LEFT JOIN prod_vt.remessa r ON r.id = rv.remessa_id 
                LEFT JOIN prod_vt.cliente c ON c.id = rv.cliente_id 
                LEFT JOIN prod_vt.fornecedor f ON f.id = rv.fornecedor_id 
                LEFT JOIN prod_vt.remessa_situacao rs ON rs.id = rv.remessa_situacao_id 
                LEFT JOIN prod_vt.viagem v ON v.id = rv.remessa_viagem_id";

        if (!empty($filtroDataInicio)) {
            $whereConditions[] = "DATE(v.data_viagem) >= :data_inicio";
            $params[':data_inicio'] = $filtroDataInicio;
        }
        if (!empty($filtroDataFim)) {
            $whereConditions[] = "DATE(v.data_viagem) <= :data_fim";
            $params[':data_fim'] = $filtroDataFim;
        }
        if (!empty($filtroFornecedor)) {
            $whereConditions[] = "rv.fornecedor_id = :fornecedor_id";
            $params[':fornecedor_id'] = $filtroFornecedor;
        }
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }
        $sql .= " ORDER BY v.data_viagem DESC, f.descricao, c.nome";
        $resultados = $db->query($sql, $params);
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title>Validar Fornecedor</title>
    <style>
        :root {
            --primary: #1F6F50;
            --primary-dark: #16523c;
            --secondary: #2c3e50;
            --bg: #f4f7f6;
            --card: #ffffff;
            --text: #333;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            font-size: 16px;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            -webkit-tap-highlight-color: transparent;
        }

        /* Mobile Header */
        .mobile-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 56px;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            padding: 0 12px;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .menu-btn {
            width: 44px;
            height: 44px;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }

        .menu-btn:active {
            background: rgba(255, 255, 255, 0.1);
        }

        .header-title {
            flex: 1;
            font-size: 1.1rem;
            font-weight: 600;
            margin-left: 8px;
        }

        /* Sidebar Overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 200;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }

        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            max-width: 85vw;
            height: 100vh;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            z-index: 300;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .sidebar.open {
            transform: translateX(0);
        }

        .sidebar-header {
            padding: 20px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
        }

        .sidebar-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.8rem;
            margin-top: 4px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 12px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            color: white;
            text-decoration: none;
            font-size: 1rem;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }

        .nav-item:active,
        .nav-item.active {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: white;
        }

        .nav-item .icon {
            font-size: 1.3rem;
            width: 28px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 12px 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.75rem;
            text-align: center;
        }

        /* Main Content */
        .main {
            padding: 68px 12px 80px 12px;
        }

        /* Cards & Form */
        .card {
            background: var(--card);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .form-row {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }

        .form-group {
            flex: 1;
        }

        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            -webkit-appearance: none;
        }

        .form-group select {
            background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 8L1 3h10z'/%3E%3C/svg%3E") no-repeat right 12px center;
        }

        .btn-row {
            display: flex;
            gap: 8px;
        }

        .btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:active {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: #e9ecef;
            color: var(--secondary);
        }

        /* Stats */
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-bottom: 12px;
        }

        .stat {
            background: var(--card);
            border-radius: 10px;
            padding: 12px 8px;
            text-align: center;
            border-left: 3px solid var(--primary);
        }

        .stat .num {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat .lbl {
            font-size: 0.7rem;
            color: #666;
            margin-top: 2px;
        }

        /* Action Bar */
        .action-bar {
            background: var(--primary);
            color: white;
            padding: 10px 12px;
            border-radius: 10px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .action-bar .count {
            font-size: 0.85rem;
            opacity: 0.9;
        }

        .action-btns {
            display: flex;
            gap: 6px;
        }

        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
        }

        .action-btn:disabled {
            opacity: 0.5;
        }

        .btn-mark {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .btn-confirm {
            background: var(--success);
            color: white;
        }

        /* List Items */
        .list {
            background: var(--card);
            border-radius: 12px;
            overflow: hidden;
        }

        .list-item {
            padding: 14px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .list-item.selected {
            background: #e8f5e9;
        }

        .list-item .check {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }

        .list-item .check input {
            width: 22px;
            height: 22px;
            cursor: pointer;
        }

        .list-item .info {
            flex: 1;
            min-width: 0;
        }

        .list-item .client {
            font-weight: 600;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .list-item .meta {
            font-size: 0.8rem;
            color: #666;
            margin-top: 2px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .list-item .actions {
            flex-shrink: 0;
        }

        .item-btn {
            width: 44px;
            height: 44px;
            border: none;
            border-radius: 10px;
            font-size: 1.3rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .item-btn.confirm {
            background: #d4edda;
            color: var(--success);
        }

        .item-btn.confirm:active {
            background: var(--success);
            color: white;
        }

        .item-btn.undo {
            background: #fff3cd;
            color: #856404;
        }

        .item-btn.undo:active {
            background: var(--warning);
            color: white;
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-done {
            background: #d4edda;
            color: #155724;
        }

        /* Alert */
        .alert {
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 12px;
            font-size: 0.9rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 400;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            background: white;
            border-radius: 16px;
            padding: 24px;
            width: 100%;
            max-width: 340px;
            text-align: center;
        }

        .modal h3 {
            font-size: 1.2rem;
            margin-bottom: 12px;
        }

        .modal p {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 20px;
        }

        .modal-btns {
            display: flex;
            gap: 10px;
        }

        .modal-btns .btn {
            flex: 1;
        }

        /* Empty State */
        .empty {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .empty .icon {
            font-size: 3rem;
            margin-bottom: 12px;
        }

        /* Loading */
        .loading {
            font-size: 0.8rem;
            color: #666;
            margin-top: 4px;
            display: none;
        }

        .loading.active {
            display: block;
        }

        /* Hide desktop sidebar include */
        .page-with-sidebar {
            margin-left: 0 !important;
        }
    </style>
</head>

<body>
    <!-- Mobile Header -->
    <header class="mobile-header">
        <button class="menu-btn" onclick="toggleMenu()">☰</button>
        <span class="header-title">📦 Validar Fornecedor</span>
    </header>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="overlay" onclick="closeMenu()"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>🚚 Victor Transportes</h2>
            <p>Sistema de Gestão</p>
        </div>
        <div class="sidebar-nav">
            <a href="index.php" class="nav-item">
                <span class="icon">🏠</span> Início
            </a>
            <a href="gerarrota.php" class="nav-item">
                <span class="icon">🗺️</span> Gerar Rota
            </a>
            <a href="validafornecedor.php" class="nav-item active">
                <span class="icon">📦</span> Validar Fornecedor
            </a>
        </div>
        <div class="sidebar-footer">© 2026 Victor Transportes</div>
    </nav>

    <!-- Main Content -->
    <main class="main">
        <!-- Filter Form -->
        <div class="card">
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label>Data Início</label>
                        <input type="date" name="data_inicio" id="data_inicio"
                            value="<?php echo htmlspecialchars($filtroDataInicio); ?>">
                    </div>
                    <div class="form-group">
                        <label>Data Fim</label>
                        <input type="date" name="data_fim" id="data_fim"
                            value="<?php echo htmlspecialchars($filtroDataFim); ?>">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 12px;">
                    <label>Fornecedor</label>
                    <select name="fornecedor_id" id="fornecedor_id">
                        <option value="">Todos os Fornecedores</option>
                        <?php foreach ($fornecedores as $f): ?>
                            <option value="<?php echo $f['id']; ?>" <?php echo ($filtroFornecedor == $f['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($f['descricao']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="loading" id="loading">🔄 Carregando...</div>
                </div>
                <div class="btn-row">
                    <button type="submit" name="filtrar" class="btn btn-primary">Filtrar</button>
                    <a href="validafornecedor.php" style="flex:1; text-decoration:none;">
                        <button type="button" class="btn btn-secondary" style="width:100%;">Limpar</button>
                    </a>
                </div>
            </form>
        </div>

        <!-- Alert -->
        <div class="alert alert-success" id="alertSuccess" style="display:none;"></div>
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <?php if (!empty($resultados)): ?>
            <?php
            $total = count($resultados);
            $totalQde = array_sum(array_column($resultados, 'qde'));
            $pendentes = count(array_filter($resultados, fn($r) => $r['remessa_situacao_id'] == 1));
            ?>

            <!-- Stats -->
            <div class="stats">
                <div class="stat">
                    <div class="num"><?php echo $total; ?></div>
                    <div class="lbl">Total</div>
                </div>
                <div class="stat">
                    <div class="num"><?php echo $pendentes; ?></div>
                    <div class="lbl">Pendentes</div>
                </div>
                <div class="stat">
                    <div class="num"><?php echo $totalQde; ?></div>
                    <div class="lbl">Qtde</div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="action-bar">
                <span class="count" id="selectedCount">0 selecionado(s)</span>
                <div class="action-btns">
                    <button class="action-btn btn-mark" id="btnToggle" onclick="toggleAll()">☑️</button>
                    <button class="action-btn btn-confirm" id="btnConfirm" disabled onclick="confirmarLote()">✅
                        Confirmar</button>
                </div>
            </div>

            <!-- List -->
            <div class="list">
                <?php foreach ($resultados as $row): ?>
                    <?php
                    $isPendente = ($row['remessa_situacao_id'] == 1);
                    $isEntregue = ($row['remessa_situacao_id'] == 6);
                    ?>
                    <div class="list-item" data-id="<?php echo $row['id']; ?>"
                        data-status="<?php echo $row['remessa_situacao_id']; ?>">
                        <div class="check">
                            <?php if ($isPendente): ?>
                                <input type="checkbox" class="row-check" value="<?php echo $row['id']; ?>" onchange="updateCount()">
                            <?php endif; ?>
                        </div>
                        <div class="info">
                            <div class="client"><?php echo htmlspecialchars($row['cliente_nome'] ?? 'N/D'); ?></div>
                            <div class="meta">
                                <span><?php echo $row['data_viagem'] ? date('d/m', strtotime($row['data_viagem'])) : '-'; ?></span>
                                <span>Qtd: <?php echo $row['qde']; ?></span>
                                <span class="badge <?php echo $isPendente ? 'badge-pending' : 'badge-done'; ?>">
                                    <?php echo $isPendente ? 'Pendente' : 'Entregue'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="actions">
                            <?php if ($isPendente): ?>
                                <button class="item-btn confirm" onclick="confirmarUm(<?php echo $row['id']; ?>)"
                                    title="Confirmar">✓</button>
                            <?php elseif ($isEntregue): ?>
                                <button class="item-btn undo" onclick="desfazerUm(<?php echo $row['id']; ?>)"
                                    title="Desfazer">↩</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filtrar'])): ?>
            <div class="empty">
                <div class="icon">📭</div>
                <p>Nenhum registro encontrado</p>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="empty">
                    <div class="icon">📦</div>
                    <p>Selecione as datas e/ou fornecedor para buscar as entregas</p>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modal -->
    <div class="modal-overlay" id="modal">
        <div class="modal">
            <h3 id="modalTitle">Confirmar?</h3>
            <p id="modalMsg">Deseja confirmar esta ação?</p>
            <div class="modal-btns">
                <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
                <button class="btn btn-primary" id="modalConfirm" onclick="executarAcao()">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
        let idsAcao = [];
        let tipoAcao = 'confirmar';

        function toggleMenu() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('overlay').classList.toggle('active');
        }
        function closeMenu() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('overlay').classList.remove('active');
        }

        function updateCount() {
            const checked = document.querySelectorAll('.row-check:checked');
            document.getElementById('selectedCount').textContent = checked.length + ' selecionado(s)';
            document.getElementById('btnConfirm').disabled = checked.length === 0;
            document.querySelectorAll('.list-item').forEach(item => {
                const cb = item.querySelector('.row-check');
                item.classList.toggle('selected', cb && cb.checked);
            });
        }

        function toggleAll() {
            const cbs = document.querySelectorAll('.row-check');
            const allChecked = Array.from(cbs).every(cb => cb.checked);
            cbs.forEach(cb => cb.checked = !allChecked);
            updateCount();
        }

        function openModal(ids, tipo) {
            idsAcao = ids;
            tipoAcao = tipo;
            document.getElementById('modalTitle').textContent = tipo === 'confirmar' ? '✅ Confirmar Entrega' : '↩️ Desfazer Entrega';
            document.getElementById('modalMsg').textContent = ids.length + ' registro(s) serão ' + (tipo === 'confirmar' ? 'confirmados' : 'revertidos');
            document.getElementById('modalConfirm').className = 'btn ' + (tipo === 'confirmar' ? 'btn-primary' : 'btn-secondary');
            document.getElementById('modal').classList.add('active');
        }
        function closeModal() {
            document.getElementById('modal').classList.remove('active');
            idsAcao = [];
        }

        function confirmarUm(id) { openModal([id], 'confirmar'); }
        function desfazerUm(id) { openModal([id], 'desfazer'); }
        function confirmarLote() {
            const ids = Array.from(document.querySelectorAll('.row-check:checked')).map(cb => cb.value);
            if (ids.length > 0) openModal(ids, 'confirmar');
        }

        async function executarAcao() {
            if (idsAcao.length === 0) return;
            const formData = new FormData();
            formData.append('ajax', tipoAcao === 'confirmar' ? 'atualizar_status' : 'desfazer_entrega');
            idsAcao.forEach(id => formData.append('ids[]', id));

            try {
                const res = await fetch('validafornecedor.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('alertSuccess').textContent = (tipoAcao === 'confirmar' ? '✅ ' : '↩️ ') + data.message;
                    document.getElementById('alertSuccess').style.display = 'block';

                    idsAcao.forEach(id => {
                        const item = document.querySelector(`.list-item[data-id="${id}"]`);
                        if (item) {
                            const badge = item.querySelector('.badge');
                            const actions = item.querySelector('.actions');
                            const check = item.querySelector('.check');

                            if (tipoAcao === 'confirmar') {
                                item.dataset.status = '6';
                                if (badge) { badge.className = 'badge badge-done'; badge.textContent = 'Entregue'; }
                                if (actions) actions.innerHTML = `<button class="item-btn undo" onclick="desfazerUm(${id})">↩</button>`;
                                if (check) check.innerHTML = '';
                            } else {
                                item.dataset.status = '1';
                                if (badge) { badge.className = 'badge badge-pending'; badge.textContent = 'Pendente'; }
                                if (actions) actions.innerHTML = `<button class="item-btn confirm" onclick="confirmarUm(${id})">✓</button>`;
                                if (check) check.innerHTML = `<input type="checkbox" class="row-check" value="${id}" onchange="updateCount()">`;
                            }
                            item.classList.remove('selected');
                        }
                    });
                    updateCount();
                    setTimeout(() => document.getElementById('alertSuccess').style.display = 'none', 4000);
                } else {
                    alert('Erro: ' + data.error);
                }
            } catch (e) {
                alert('Erro de conexão');
            }
            closeModal();
        }

        // Fornecedores dinâmicos
        const dataInicio = document.getElementById('data_inicio');
        const dataFim = document.getElementById('data_fim');
        const fornecedorSelect = document.getElementById('fornecedor_id');
        const loading = document.getElementById('loading');

        async function buscarFornecedores() {
            if (!dataInicio.value && !dataFim.value) return;
            loading.classList.add('active');
            try {
                const params = new URLSearchParams({ ajax: 'fornecedores', data_inicio: dataInicio.value, data_fim: dataFim.value });
                const res = await fetch(`validafornecedor.php?${params}`);
                const data = await res.json();
                if (data.success) {
                    const current = fornecedorSelect.value;
                    fornecedorSelect.innerHTML = '<option value="">Todos os Fornecedores</option>';
                    data.data.forEach(f => {
                        const opt = document.createElement('option');
                        opt.value = f.id;
                        opt.textContent = f.descricao;
                        if (f.id == current) opt.selected = true;
                        fornecedorSelect.appendChild(opt);
                    });
                }
            } catch (e) { }
            loading.classList.remove('active');
        }

        dataInicio.addEventListener('change', buscarFornecedores);
        dataFim.addEventListener('change', buscarFornecedores);
        if (dataInicio.value || dataFim.value) buscarFornecedores();
    </script>
</body>

</html>