<?php
/**
 * Validação de Fornecedores
 * 
 * Visualiza entregas agrupadas por cliente com fornecedores em grid.
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';
require_once 'Database.php';

$db = new Database();
$fornecedores = [];
$clientes = [];
$resultados = [];
// Calcula segunda-feira e sábado da semana corrente como padrão
$hoje = new DateTime();
$diaSemana = (int)$hoje->format('N'); // 1 = Segunda, 7 = Domingo
$segundaFeira = (clone $hoje)->modify('-' . ($diaSemana - 1) . ' days');
$sabado = (clone $segundaFeira)->modify('+5 days');

$filtroDataInicio = $_POST['data_inicio'] ?? $_GET['data_inicio'] ?? $segundaFeira->format('Y-m-d');
$filtroDataFim = $_POST['data_fim'] ?? $_GET['data_fim'] ?? $sabado->format('Y-m-d');
$filtroFornecedor = $_POST['fornecedor_id'] ?? '';
$filtroCliente = $_POST['cliente_id'] ?? '';
$filtroStatus = $_POST['status_entrega'] ?? '';

// AJAX: Atualiza status (1 -> 6)
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
        echo json_encode(['success' => true, 'message' => "$updated confirmado(s)!", 'updated' => $updated]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Desfaz (6 -> 1)
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

// AJAX: Busca fornecedores por data
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

// AJAX: Busca clientes por data
if (isset($_GET['ajax']) && $_GET['ajax'] === 'clientes') {
    header('Content-Type: application/json');
    try {
        $params = [];
        $sql = "SELECT DISTINCT c.id, c.nome, c.fone, 
                CONCAT(c.nome, COALESCE(CONCAT(' - ', c.fone), '')) as nome_telefone
                FROM prod_vt.remessa_valor rv
                LEFT JOIN prod_vt.cliente c ON c.id = rv.cliente_id 
                LEFT JOIN prod_vt.viagem v ON v.id = rv.remessa_viagem_id WHERE c.id IS NOT NULL";
        if (!empty($_GET['data_inicio'])) {
            $sql .= " AND DATE(v.data_viagem) >= :data_inicio";
            $params[':data_inicio'] = $_GET['data_inicio'];
        }
        if (!empty($_GET['data_fim'])) {
            $sql .= " AND DATE(v.data_viagem) <= :data_fim";
            $params[':data_fim'] = $_GET['data_fim'];
        }
        $sql .= " ORDER BY c.nome";
        $clientes = $db->query($sql, $params);
        echo json_encode(['success' => true, 'data' => $clientes]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Carrega listas iniciais
try {
    $fornecedores = $db->query("SELECT id, descricao FROM prod_vt.fornecedor ORDER BY descricao");
    $clientes = $db->query("SELECT id, nome, fone, CONCAT(nome, COALESCE(CONCAT(' - ', fone), '')) as nome_telefone FROM prod_vt.cliente ORDER BY nome");
} catch (Exception $e) {
}

// Processa filtro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filtrar'])) {
    try {
        $params = [];
        $whereConditions = [];
        $sql = "SELECT rv.id, rv.qde, rv.cliente_id, c.nome AS cliente_nome, c.fone AS cliente_telefone,
                rv.fornecedor_id, f.descricao AS fornecedor_descricao, rv.remessa_situacao_id, 
                rs.descricao AS situacao_descricao, v.data_viagem
                FROM prod_vt.remessa_valor rv
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
        if (!empty($filtroCliente)) {
            $whereConditions[] = "rv.cliente_id = :cliente_id";
            $params[':cliente_id'] = $filtroCliente;
        }
        if (!empty($filtroStatus)) {
            if ($filtroStatus === 'entregue') {
                $whereConditions[] = "rv.remessa_situacao_id = 6";
            } elseif ($filtroStatus === 'nao_entregue') {
                $whereConditions[] = "rv.remessa_situacao_id = 1";
            }
        }
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }
        $sql .= " ORDER BY c.nome, f.descricao";
        $resultados = $db->query($sql, $params);
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

// Agrupa resultados por cliente
$clientesAgrupados = [];
foreach ($resultados as $row) {
    $clienteId = $row['cliente_id'];
    if (!isset($clientesAgrupados[$clienteId])) {
        $clientesAgrupados[$clienteId] = [
            'nome' => $row['cliente_nome'] ?? 'N/D',
            'telefone' => $row['cliente_telefone'] ?? '',
            'fornecedores' => []
        ];
    }
    $clientesAgrupados[$clienteId]['fornecedores'][] = [
        'id' => $row['id'],
        'nome' => $row['fornecedor_descricao'] ?? 'N/D',
        'qde' => $row['qde'],
        'situacao_id' => $row['remessa_situacao_id'],
        'situacao' => $row['situacao_descricao']
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>Validar Fornecedor</title>
    <?php $currentPage = 'validafornecedor.php'; ?>
    <style>
        :root {
            /* Nova paleta - mais elegante e menos saturada */
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
            -webkit-tap-highlight-color: transparent;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Main */
        .main {
            padding: 20px 12px 20px 12px;
        }

        /* Filter Card */
        .filter-card {
            background: var(--card);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .filter-row {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
        }

        .filter-group {
            flex: 1;
        }

        .filter-group label {
            display: block;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--secondary);
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.9rem;
            -webkit-appearance: none;
        }

        /* Autocomplete */
        .autocomplete-wrapper {
            position: relative;
        }

        .autocomplete-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 100;
            display: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .autocomplete-list.active {
            display: block;
        }

        .autocomplete-item {
            padding: 10px 12px;
            cursor: pointer;
            font-size: 0.85rem;
            border-bottom: 1px solid #eee;
        }

        .autocomplete-item:last-child {
            border-bottom: none;
        }

        .autocomplete-item:hover,
        .autocomplete-item.selected {
            background: var(--bg);
        }

        .autocomplete-item .highlight {
            background: #fff3cd;
            font-weight: 600;
        }

        .autocomplete-more {
            padding: 8px 12px;
            text-align: center;
            color: #666;
            font-size: 0.75rem;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            font-style: italic;
        }

        .autocomplete-count {
            padding: 6px 12px;
            background: var(--primary-bg);
            color: var(--primary);
            font-size: 0.7rem;
            font-weight: 600;
            border-bottom: 1px solid var(--border);
        }

        .btn-row {
            display: flex;
            gap: 8px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-secondary {
            background: #e9ecef;
            color: var(--secondary);
        }

        /* Stats */
        .stats {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }

        .stat {
            flex: 1;
            background: var(--card);
            border-radius: 10px;
            padding: 10px 8px;
            text-align: center;
            border-left: 3px solid var(--primary);
        }

        .stat .num {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat .lbl {
            font-size: 0.65rem;
            color: #666;
        }

        /* Client Card - Design Clean */
        .client-card {
            background: var(--card);
            border-radius: 12px;
            margin-bottom: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            border-left: 4px solid var(--primary-light);
        }

        .client-header {
            background: #ffffff;
            color: var(--text);
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
        }

        .client-name {
            font-weight: 600;
            font-size: 1rem;
            color: var(--text);
        }

        .client-phone {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .client-phone a {
            color: var(--primary);
            text-decoration: none;
        }

        /* Fornecedor Grid */
        .fornecedor-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1px;
            background: #eee;
        }

        .fornecedor-item {
            background: white;
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .fornecedor-item.pendente {
            border-left: 3px solid var(--warning);
        }

        .fornecedor-item.entregue {
            border-left: 3px solid var(--success);
            background: #f8fff8;
        }

        .fornecedor-nome {
            font-weight: 600;
            font-size: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .fornecedor-qde {
            font-size: 0.8rem;
            color: #666;
        }

        .fornecedor-qde strong {
            color: var(--primary);
        }

        .fornecedor-action {
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Toggle Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
            flex-shrink: 0;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #dc3545;
            transition: 0.3s;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
        }

        input:checked+.slider {
            background-color: var(--success);
        }

        input:checked+.slider:before {
            transform: translateX(24px);
        }

        .slider.round {
            border-radius: 26px;
        }

        .slider.round:before {
            border-radius: 50%;
        }

        .status-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: #666;
        }

        .status-label.entregue {
            color: var(--success);
        }

        .status-label.pendente {
            color: #dc3545;
        }

        /* Alert */
        .alert {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 12px;
            font-size: 0.9rem;
            text-align: center;
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

        /* Empty */
        .empty {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .empty .icon {
            font-size: 3rem;
            margin-bottom: 12px;
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

        .loading {
            font-size: 0.75rem;
            color: #666;
            margin-top: 3px;
            display: none;
        }

        .loading.active {
            display: block;
        }

        /* Responsive - 3 cols em telas maiores */
        @media (min-width: 500px) {
            .fornecedor-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 768px) {
            .fornecedor-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Status Filter Toggle Buttons */
        .status-toggle-group {
            display: flex;
            gap: 0;
            background: #f1f3f5;
            border-radius: 10px;
            padding: 4px;
            width: 100%;
        }

        .status-toggle {
            flex: 1;
            position: relative;
        }

        .status-toggle input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .status-toggle-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #666;
            cursor: pointer;
            transition: all 0.25s ease;
            background: transparent;
            border: none;
            width: 100%;
            text-align: center;
        }

        .status-toggle-btn .icon {
            font-size: 1rem;
            line-height: 1;
        }

        .status-toggle input:checked + .status-toggle-btn {
            background: white;
            color: var(--text);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            font-weight: 600;
        }

        .status-toggle.todos input:checked + .status-toggle-btn {
            background: var(--primary);
            color: white;
        }

        .status-toggle.entregue input:checked + .status-toggle-btn {
            background: var(--success);
            color: white;
        }

        .status-toggle.nao-entregue input:checked + .status-toggle-btn {
            background: var(--warning);
            color: white;
        }

        .status-toggle-btn:hover {
            background: rgba(0, 0, 0, 0.05);
        }

        .status-toggle input:checked + .status-toggle-btn:hover {
            filter: brightness(1.05);
        }

        @media (max-width: 400px) {
            .status-toggle-btn {
                padding: 8px 6px;
                font-size: 0.75rem;
            }
            .status-toggle-btn .icon {
                font-size: 0.9rem;
            }
            .status-toggle-btn .text {
                display: none;
            }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main page-with-sidebar">
        <div class="page-header">
            <h1>✅ Validar Fornecedor</h1>
        </div>
        <div class="filter-card">
            <form method="POST" action="" id="filterForm">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Data Início *</label>
                        <input type="date" name="data_inicio" id="data_inicio" required
                            value="<?php echo htmlspecialchars($filtroDataInicio); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Data Fim *</label>
                        <input type="date" name="data_fim" id="data_fim" required
                            value="<?php echo htmlspecialchars($filtroDataFim); ?>">
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Fornecedor</label>
                        <div class="autocomplete-wrapper">
                            <input type="text" id="fornecedor_search" placeholder="Digite para buscar..."
                                autocomplete="off">
                            <input type="hidden" name="fornecedor_id" id="fornecedor_id"
                                value="<?php echo htmlspecialchars($filtroFornecedor); ?>">
                            <div class="autocomplete-list" id="fornecedor_list"></div>
                        </div>
                        <div class="loading" id="loadingF">🔄</div>
                    </div>
                    <div class="filter-group">
                        <label>Cliente</label>
                        <div class="autocomplete-wrapper">
                            <input type="text" id="cliente_search" placeholder="Digite para buscar..."
                                autocomplete="off">
                            <input type="hidden" name="cliente_id" id="cliente_id"
                                value="<?php echo htmlspecialchars($filtroCliente); ?>">
                            <div class="autocomplete-list" id="cliente_list"></div>
                        </div>
                        <div class="loading" id="loadingC">🔄</div>
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-group" style="flex: none; width: 100%;">
                        <label>Status de Entrega</label>
                        <div class="status-toggle-group">
                            <label class="status-toggle todos">
                                <input type="radio" name="status_entrega" value="" <?php echo empty($filtroStatus) ? 'checked' : ''; ?>>
                                <span class="status-toggle-btn">
                                    <span class="icon">📋</span>
                                    <span class="text">Todos</span>
                                </span>
                            </label>
                            <label class="status-toggle entregue">
                                <input type="radio" name="status_entrega" value="entregue" <?php echo $filtroStatus === 'entregue' ? 'checked' : ''; ?>>
                                <span class="status-toggle-btn">
                                    <span class="icon">✅</span>
                                    <span class="text">Entregue</span>
                                </span>
                            </label>
                            <label class="status-toggle nao-entregue">
                                <input type="radio" name="status_entrega" value="nao_entregue" <?php echo $filtroStatus === 'nao_entregue' ? 'checked' : ''; ?>>
                                <span class="status-toggle-btn">
                                    <span class="icon">⏳</span>
                                    <span class="text">Não Entregue</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="btn-row">
                    <button type="submit" name="filtrar" class="btn btn-primary">Filtrar</button>
                    <a href="validafornecedor.php" style="flex:1; text-decoration:none;">
                        <button type="button" class="btn btn-secondary" style="width:100%;">Limpar</button>
                    </a>
                </div>
            </form>
        </div>

        <div class="alert alert-success" id="alertSuccess" style="display:none;"></div>
        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <?php if (!empty($clientesAgrupados)): ?>
            <?php
            $totalClientes = count($clientesAgrupados);
            $totalFornecedores = count($resultados);
            $totalPendentes = count(array_filter($resultados, fn($r) => $r['remessa_situacao_id'] == 1));
            ?>
            <div class="stats">
                <div class="stat">
                    <div class="num"><?php echo $totalClientes; ?></div>
                    <div class="lbl">Clientes</div>
                </div>
                <div class="stat">
                    <div class="num"><?php echo $totalFornecedores; ?></div>
                    <div class="lbl">Itens</div>
                </div>
                <div class="stat">
                    <div class="num"><?php echo $totalPendentes; ?></div>
                    <div class="lbl">Pendentes</div>
                </div>
                <div class="stat" style="cursor:pointer; background:var(--primary); border-left-color:#fff;"
                    onclick="generatePDF()">
                    <div class="num" style="color:#fff; font-size:1.1rem;">📄</div>
                    <div class="lbl" style="color:#fff;">Baixar PDF</div>
                </div>
            </div>

            <?php foreach ($clientesAgrupados as $clienteId => $cliente): ?>
                <div class="client-card">
                    <div class="client-header">
                        <div class="client-name"><?php echo htmlspecialchars($cliente['nome']); ?></div>
                        <?php if (!empty($cliente['telefone'])): ?>
                            <div class="client-phone">
                                <a href="tel:<?php echo preg_replace('/\D/', '', $cliente['telefone']); ?>">
                                    📞 <?php echo htmlspecialchars($cliente['telefone']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="fornecedor-grid">
                        <?php foreach ($cliente['fornecedores'] as $forn): ?>
                            <?php
                            $isPendente = ($forn['situacao_id'] == 1);
                            $isEntregue = ($forn['situacao_id'] == 6);
                            ?>
                            <div class="fornecedor-item <?php echo $isPendente ? 'pendente' : ($isEntregue ? 'entregue' : ''); ?>"
                                data-id="<?php echo $forn['id']; ?>" data-status="<?php echo $forn['situacao_id']; ?>">
                                <div class="fornecedor-nome" title="<?php echo htmlspecialchars($forn['nome']); ?>">
                                    <?php echo htmlspecialchars($forn['nome']); ?>
                                </div>
                                <div class="fornecedor-qde">Qtd: <strong><?php echo $forn['qde']; ?></strong></div>
                                <div class="fornecedor-action">
                                    <label class="switch">
                                        <input type="checkbox" onchange="toggleStatus(this, <?php echo $forn['id']; ?>)" <?php echo $isEntregue ? 'checked' : ''; ?>>
                                        <span class="slider round"></span>
                                    </label>
                                    <span class="status-label <?php echo $isEntregue ? 'entregue' : 'pendente'; ?>">
                                        <?php echo $isEntregue ? 'Entregue' : 'Não Entregue'; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filtrar'])): ?>
            <div class="empty">
                <div class="icon">📭</div>
                <p>Nenhum registro encontrado</p>
            </div>
        <?php else: ?>
            <div class="filter-card">
                <div class="empty">
                    <div class="icon">📦</div>
                    <p>Selecione os filtros para buscar</p>
                </div>
            </div>
        <?php endif; ?>
    </main>

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
        let idAcao = null;
        let tipoAcao = 'confirmar';

        // PDF Generation
        function generatePDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            const dataInicio = document.getElementById('data_inicio').value || 'N/D';
            const dataFim = document.getElementById('data_fim').value || 'N/D';
            const today = new Date().toLocaleDateString('pt-BR');

            // Configurações
            const pageWidth = 210;
            const marginLeft = 12;
            const marginRight = 12;
            const contentWidth = pageWidth - marginLeft - marginRight;
            const pageHeight = 297;
            const marginBottom = 20;
            
            // Colunas da tabela
            const colStatus = marginLeft;
            const colFornecedor = marginLeft + 12;
            const colQtd = pageWidth - marginRight - 15;
            
            // Header
            doc.setFillColor(31, 111, 80);
            doc.rect(0, 0, pageWidth, 35, 'F');
            
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(18);
            doc.setFont('helvetica', 'bold');
            doc.text('Validação de Fornecedores', pageWidth / 2, 15, { align: 'center' });

            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text(`Período: ${formatDate(dataInicio)} a ${formatDate(dataFim)}`, pageWidth / 2, 23, { align: 'center' });
            doc.text(`Gerado em: ${today}`, pageWidth / 2, 30, { align: 'center' });
            doc.setTextColor(0, 0, 0);

            let y = 45;

            // Get all client cards
            const clientCards = document.querySelectorAll('.client-card');

            clientCards.forEach((card, cardIndex) => {
                const clientName = card.querySelector('.client-name')?.textContent?.trim() || 'N/D';
                let clientPhone = card.querySelector('.client-phone a')?.textContent?.trim() || '';
                clientPhone = clientPhone.replace(/[^\d\s\-\(\)]/g, '').trim();

                const fornecedores = card.querySelectorAll('.fornecedor-item');
                const totalItems = fornecedores.length;
                
                // Calcula altura necessária para este cliente (header + itens + espaço)
                const clientHeight = 12 + (totalItems * 8) + 6;
                
                // Verifica se precisa de nova página
                if (y + clientHeight > pageHeight - marginBottom) {
                    doc.addPage();
                    y = 20;
                }

                // ===== HEADER DO CLIENTE =====
                doc.setFillColor(31, 111, 80);
                doc.rect(marginLeft, y, contentWidth, 10, 'F');
                doc.setTextColor(255, 255, 255);
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(10);
                doc.text(clientName + (clientPhone ? `  •  ${clientPhone}` : ''), marginLeft + 4, y + 7);
                doc.setTextColor(0, 0, 0);
                
                y += 12;

                // ===== CABEÇALHO DA TABELA =====
                doc.setFillColor(245, 247, 250);
                doc.rect(marginLeft, y, contentWidth, 7, 'F');
                doc.setDrawColor(200, 200, 200);
                doc.line(marginLeft, y, marginLeft + contentWidth, y);
                doc.line(marginLeft, y + 7, marginLeft + contentWidth, y + 7);
                
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(7);
                doc.setTextColor(100, 100, 100);
                doc.text('', colStatus + 2, y + 5);
                doc.text('FORNECEDOR', colFornecedor, y + 5);
                doc.text('QTD', colQtd + 10, y + 5, { align: 'right' });
                doc.setTextColor(0, 0, 0);
                
                y += 8;

                // ===== LINHAS DOS FORNECEDORES =====
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(9);

                fornecedores.forEach((forn, fornIndex) => {
                    // Nova página se necessário
                    if (y > pageHeight - marginBottom) {
                        doc.addPage();
                        y = 20;
                    }

                    const nome = forn.querySelector('.fornecedor-nome')?.textContent?.trim() || 'N/D';
                    const qdeText = forn.querySelector('.fornecedor-qde')?.textContent?.trim() || '';
                    const qde = qdeText.replace(/[^\d]/g, '') || '0';
                    const isEntregue = forn.classList.contains('entregue');
                    
                    // Linha de fundo alternada
                    if (fornIndex % 2 === 0) {
                        doc.setFillColor(252, 252, 252);
                        doc.rect(marginLeft, y - 1, contentWidth, 7, 'F');
                    }
                    
                    // Checkbox de status
                    doc.setDrawColor(180, 180, 180);
                    doc.rect(colStatus, y, 4, 4);
                    if (isEntregue) {
                        doc.setFillColor(34, 197, 94);
                        doc.rect(colStatus + 0.5, y + 0.5, 3, 3, 'F');
                    }
                    
                    // Nome do fornecedor
                    doc.setTextColor(30, 30, 30);
                    doc.setFont('helvetica', 'normal');
                    
                    // Trunca nome se muito longo
                    let displayNome = nome;
                    const maxNomeWidth = colQtd - colFornecedor - 10;
                    while (doc.getTextWidth(displayNome) > maxNomeWidth && displayNome.length > 10) {
                        displayNome = displayNome.slice(0, -1);
                    }
                    if (displayNome !== nome) displayNome += '...';
                    
                    doc.text(displayNome, colFornecedor, y + 3);
                    
                    // Linha pontilhada
                    doc.setDrawColor(200, 200, 200);
                    doc.setLineDashPattern([1, 1], 0);
                    const nomeWidth = doc.getTextWidth(displayNome);
                    const lineStart = colFornecedor + nomeWidth + 2;
                    const lineEnd = colQtd - 2;
                    if (lineEnd > lineStart) {
                        doc.line(lineStart, y + 2, lineEnd, y + 2);
                    }
                    doc.setLineDashPattern([], 0);
                    
                    // Quantidade alinhada à direita
                    doc.setFont('helvetica', 'bold');
                    doc.setTextColor(31, 111, 80);
                    doc.text(qde, colQtd + 10, y + 3, { align: 'right' });
                    doc.setTextColor(0, 0, 0);
                    
                    y += 7;
                });
                
                // Linha inferior da tabela
                doc.setDrawColor(200, 200, 200);
                doc.line(marginLeft, y, marginLeft + contentWidth, y);
                
                y += 10;
            });

            // ===== RODAPÉ COM ESTATÍSTICAS =====
            const stats = document.querySelectorAll('.stat .num');
            if (stats.length >= 3) {
                // Adiciona rodapé em todas as páginas
                const totalPages = doc.internal.getNumberOfPages();
                for (let i = 1; i <= totalPages; i++) {
                    doc.setPage(i);
                    const footerY = pageHeight - 10;
                    
                    // Linha separadora
                    doc.setDrawColor(31, 111, 80);
                    doc.setLineWidth(0.5);
                    doc.line(marginLeft, footerY - 5, pageWidth - marginRight, footerY - 5);
                    doc.setLineWidth(0.2);
                    
                    // Texto do rodapé
                    doc.setFontSize(8);
                    doc.setFont('helvetica', 'normal');
                    doc.setTextColor(100, 100, 100);
                    doc.text(`Página ${i} de ${totalPages}`, marginLeft, footerY);
                    
                    doc.setFont('helvetica', 'bold');
                    doc.setTextColor(31, 111, 80);
                    doc.text(
                        `Clientes: ${stats[0].textContent}  |  Itens: ${stats[1].textContent}  |  Pendentes: ${stats[2].textContent}`,
                        pageWidth - marginRight,
                        footerY,
                        { align: 'right' }
                    );
                }
            }

            doc.save(`validacao_fornecedores_${dataInicio}_${dataFim}.pdf`);
        }
        
        // Função auxiliar para formatar data
        function formatDate(dateStr) {
            if (!dateStr || dateStr === 'N/D') return dateStr;
            const parts = dateStr.split('-');
            if (parts.length === 3) {
                return `${parts[2]}/${parts[1]}/${parts[0]}`;
            }
            return dateStr;
        }

        function toggleMenu() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('overlay').classList.toggle('active');
        }
        function closeMenu() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('overlay').classList.remove('active');
        }

        function confirmar(id) {
            idAcao = id;
            tipoAcao = 'confirmar';
            document.getElementById('modalTitle').textContent = '✅ Confirmar Recebimento';
            document.getElementById('modalMsg').textContent = 'Marcar este item como recebido?';
            document.getElementById('modal').classList.add('active');
        }

        function desfazer(id) {
            idAcao = id;
            tipoAcao = 'desfazer';
            document.getElementById('modalTitle').textContent = '↩️ Desfazer';
            document.getElementById('modalMsg').textContent = 'Reverter para pendente?';
            document.getElementById('modal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('modal').classList.remove('active');
            idAcao = null;
        }

        async function executarAcao() {
            if (!idAcao) return;
            const formData = new FormData();
            formData.append('ajax', tipoAcao === 'confirmar' ? 'atualizar_status' : 'desfazer_entrega');
            formData.append('ids[]', idAcao);

            try {
                const res = await fetch('validafornecedor.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('alertSuccess').textContent = (tipoAcao === 'confirmar' ? '✅ ' : '↩️ ') + data.message;
                    document.getElementById('alertSuccess').style.display = 'block';
                    setTimeout(() => document.getElementById('alertSuccess').style.display = 'none', 3000);
                } else {
                    alert('Erro: ' + data.error);
                }
            } catch (e) {
                alert('Erro de conexão');
            }
            closeModal();
        }

        async function toggleStatus(checkbox, id) {
            const isChecked = checkbox.checked;
            const action = isChecked ? 'atualizar_status' : 'desfazer_entrega';
            const formData = new FormData();
            formData.append('ajax', action);
            formData.append('ids[]', id);

            const item = checkbox.closest('.fornecedor-item');
            const label = item.querySelector('.status-label');

            try {
                const res = await fetch('validafornecedor.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('alertSuccess').textContent = (isChecked ? '✅ ' : '↩️ ') + data.message;
                    document.getElementById('alertSuccess').style.display = 'block';

                    if (isChecked) {
                        item.classList.remove('pendente');
                        item.classList.add('entregue');
                        item.dataset.status = '6';
                        label.textContent = 'Entregue';
                        label.classList.remove('pendente');
                        label.classList.add('entregue');
                    } else {
                        item.classList.remove('entregue');
                        item.classList.add('pendente');
                        item.dataset.status = '1';
                        label.textContent = 'Não Entregue';
                        label.classList.remove('entregue');
                        label.classList.add('pendente');
                    }
                    setTimeout(() => document.getElementById('alertSuccess').style.display = 'none', 3000);
                } else {
                    checkbox.checked = !isChecked;
                    alert('Erro: ' + data.error);
                }
            } catch (e) {
                checkbox.checked = !isChecked;
                alert('Erro de conexão');
            }
        }

        // Autocomplete functionality
        const fornecedores = <?php echo json_encode($fornecedores); ?>;
        const clientes = <?php echo json_encode($clientes); ?>;

        function setupAutocomplete(inputId, listId, hiddenId, items, labelKey) {
            const input = document.getElementById(inputId);
            const list = document.getElementById(listId);
            const hidden = document.getElementById(hiddenId);

            // Set initial value if exists
            const currentVal = hidden.value;
            if (currentVal) {
                const found = items.find(item => item.id == currentVal);
                if (found) input.value = found[labelKey];
            }

            input.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                hidden.value = ''; // Clear selection when typing

                if (query.length === 0) {
                    list.classList.remove('active');
                    return;
                }

                const allMatches = items.filter(item => {
                    const label = (item[labelKey] || '').toString().toLowerCase();
                    return label.includes(query);
                });
                
                const totalMatches = allMatches.length;
                const displayLimit = 50;
                const filtered = allMatches.slice(0, displayLimit);

                if (filtered.length === 0) {
                    list.innerHTML = '<div class="autocomplete-item" style="color:#999;">Nenhum resultado encontrado</div>';
                } else {
                    let html = '';
                    
                    // Adiciona contador de resultados
                    if (totalMatches > 1) {
                        html += `<div class="autocomplete-count">${totalMatches} ${totalMatches === 1 ? 'resultado' : 'resultados'} encontrado${totalMatches === 1 ? '' : 's'}</div>`;
                    }
                    
                    html += filtered.map(item => {
                        const label = item[labelKey];
                        const highlighted = label.replace(
                            new RegExp(`(${query})`, 'gi'),
                            '<span class="highlight">$1</span>'
                        );
                        return `<div class="autocomplete-item" data-id="${item.id}" data-label="${label}">${highlighted}</div>`;
                    }).join('');
                    
                    // Indicador de mais resultados
                    if (totalMatches > displayLimit) {
                        html += `<div class="autocomplete-more">+ ${totalMatches - displayLimit} resultados não exibidos. Digite mais para refinar.</div>`;
                    }
                    
                    list.innerHTML = html;
                }
                list.classList.add('active');
            });

            input.addEventListener('focus', function () {
                if (this.value.length > 0) {
                    this.dispatchEvent(new Event('input'));
                }
            });

            list.addEventListener('click', function (e) {
                const item = e.target.closest('.autocomplete-item');
                if (item && item.dataset.id) {
                    hidden.value = item.dataset.id;
                    input.value = item.dataset.label;
                    list.classList.remove('active');
                }
            });

            input.addEventListener('blur', function () {
                setTimeout(() => list.classList.remove('active'), 200);
            });

            // Clear button functionality - if user clears input, clear hidden too
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    list.classList.remove('active');
                }
            });
        }

        setupAutocomplete('fornecedor_search', 'fornecedor_list', 'fornecedor_id', fornecedores, 'descricao');
        setupAutocomplete('cliente_search', 'cliente_list', 'cliente_id', clientes, 'nome_telefone');

        // Auto-submit on status change
        document.querySelectorAll('input[name="status_entrega"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Verifica se as datas estão preenchidas
                const dataInicio = document.getElementById('data_inicio').value;
                const dataFim = document.getElementById('data_fim').value;
                
                if (dataInicio && dataFim) {
                    // Adiciona um campo hidden para indicar que é um filtro
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'filtrar';
                    hiddenInput.value = '1';
                    document.getElementById('filterForm').appendChild(hiddenInput);
                    
                    // Submete o formulário
                    document.getElementById('filterForm').submit();
                } else {
                    alert('Por favor, preencha as datas de início e fim antes de filtrar.');
                }
            });
        });
    </script>
</body>

</html>