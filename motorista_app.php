<?php
/**
 * App do Motorista - Mobile First
 * Victor Transportes - Módulo de Entregas
 */

session_start();
if (!isset($_SESSION['motorista_id'])) {
    header('Location: motorista_login.php');
    exit;
}

require_once 'config.php';
require_once 'Database.php';

$db           = new Database();
$motoristaId  = (int) $_SESSION['motorista_id'];
$motoristaNome = $_SESSION['motorista_nome'] ?? 'Motorista';

// ─── AJAX: Listar viagens do motorista ───────────────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'viagens') {
    header('Content-Type: application/json');
    try {
        $viagens = $db->query(
            "SELECT v.id, DATE_FORMAT(v.data_viagem, '%d/%m/%y') AS data_fmt
             FROM viagem v
             WHERE v.motorista_id = ?
               AND v.data_viagem >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
             ORDER BY v.data_viagem DESC
             LIMIT 30",
            [$motoristaId]
        );
        echo json_encode(['success' => true, 'data' => $viagens]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── AJAX: Listar pedidos de uma viagem ──────────────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'pedidos') {
    header('Content-Type: application/json');
    try {
        $viagemId = (int) ($_GET['viagem_id'] ?? 0);
        if (!$viagemId) throw new Exception('Viagem inválida');

        $pedidos = $db->query(
            "SELECT
                r.id                AS remessa_id,
                r.ordem             AS ordem,
                r.descricao,
                c.nome              AS cliente_nome,
                ci.descricao        AS cidade_nome,
                NULL                AS cidade_sigla,
                r.pacote_qde,
                r.total,
                r.latitude,
                r.longitude,
                r.coordenadas,
                r.remessa_situacao_id,
                rs.descricao        AS situacao_descricao,
                r.forma_pagamento_id,
                fp.descricao        AS forma_pgto_descricao
             FROM remessa r
             LEFT JOIN cliente      c  ON c.id  = r.cliente_id
             LEFT JOIN cidade       ci ON ci.id = c.cidade_id
             LEFT JOIN remessa_situacao rs ON rs.id = r.remessa_situacao_id
             LEFT JOIN forma_pagamento fp ON fp.id = r.forma_pagamento_id
             WHERE r.viagem_id = ?
               AND r.cliente_id NOT IN (120, 197)
             ORDER BY r.ordem ASC, r.id ASC",
            [$viagemId]
        );

        // Totalizador
        $total    = count($pedidos);
        $entregues = 0;
        $totalDinheiro = 0.0;

        foreach ($pedidos as $p) {
            $entregue = stripos($p['situacao_descricao'] ?? '', 'ntreg') !== false
                     || stripos($p['situacao_descricao'] ?? '', 'inaliz') !== false;
            if ($entregue) {
                $entregues++;
                // Acumula dinheiro (pagamentos em espécie)
                if (stripos($p['forma_pgto_descricao'] ?? '', 'dinheiro') !== false
                 || stripos($p['forma_pgto_descricao'] ?? '', 'spécie') !== false
                 || stripos($p['forma_pgto_descricao'] ?? '', 'especie') !== false) {
                    $totalDinheiro += (float) $p['total'];
                }
            }
        }

        echo json_encode([
            'success'        => true,
            'data'           => $pedidos,
            'total'          => $total,
            'entregues'      => $entregues,
            'a_entregar'     => $total - $entregues,
            'total_dinheiro' => $totalDinheiro,
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── AJAX: Formas de pagamento ────────────────────────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'formas_pgto') {
    header('Content-Type: application/json');
    try {
        $fps = $db->query("SELECT id, descricao FROM forma_pagamento ORDER BY descricao");
        echo json_encode(['success' => true, 'data' => $fps]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── AJAX: Confirmar entrega ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'confirmar') {
    header('Content-Type: application/json');
    try {
        $remessaId     = (int) ($_POST['remessa_id']      ?? 0);
        $formaPgtoId   = (int) ($_POST['forma_pgto_id']   ?? 0);

        if (!$remessaId)   throw new Exception('Remessa inválida');
        if (!$formaPgtoId) throw new Exception('Selecione a forma de pagamento');

        // Garante que a remessa pertence ao motorista logado
        $remessa = $db->queryOne(
            "SELECT r.id FROM remessa r
             JOIN viagem v ON v.id = r.viagem_id
             WHERE r.id = ? AND v.motorista_id = ?",
            [$remessaId, $motoristaId]
        );
        if (!$remessa) throw new Exception('Acesso negado');

        // Busca o status "entregue"
        $statusEntregue = $db->queryOne(
            "SELECT id FROM remessa_situacao
             WHERE LOWER(descricao) LIKE '%ntreg%' OR LOWER(descricao) LIKE '%inaliz%'
             ORDER BY id ASC LIMIT 1"
        );
        if (!$statusEntregue) throw new Exception('Status de entrega não encontrado');

        $db->execute(
            "UPDATE remessa
             SET remessa_situacao_id = ?, forma_pagamento_id = ?
             WHERE id = ?",
            [$statusEntregue['id'], $formaPgtoId, $remessaId]
        );

        echo json_encode(['success' => true, 'message' => 'Entrega confirmada!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── Logout ───────────────────────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    unset($_SESSION['motorista_id'], $_SESSION['motorista_nome']);
    header('Location: motorista_login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Entregas - <?php echo EMPRESA_NOME; ?></title>
    <style>
        :root {
            --primary: <?php echo EMPRESA_COR_PRIMARIA; ?>;
            --primary-dark: <?php echo EMPRESA_COR_SECUNDARIA; ?>;
            --primary-light: <?php echo EMPRESA_COR_PRIMARIA; ?>;
            --primary-bg:   #E8F4EF;
            --success:      #22C55E;
            --danger:       #EF4444;
            --warning:      #F59E0B;
            --bg:           #F0F4F2;
            --card:         #ffffff;
            --text:         #1F2933;
            --muted:        #6B7280;
            --border:       #E5E7EB;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding-bottom: 80px;
        }

        /* ── HEADER ── */
        .app-header {
            background: var(--primary);
            color: #fff;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .header-left { display: flex; align-items: center; gap: 10px; }
        .header-icon { font-size: 1.6rem; }
        .header-title { font-size: 1rem; font-weight: 700; }
        .header-sub   { font-size: 0.75rem; opacity: 0.8; }

        .btn-logout {
            background: rgba(255,255,255,0.15);
            border: none;
            color: #fff;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
        }

        /* ── SCREENS ── */
        .screen { display: none; padding: 16px; }
        .screen.active { display: block; }

        /* ── VIAGEM SELECTOR ── */
        .select-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .select-label {
            font-size: 1rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 16px;
        }

        .viagem-list {
            background: var(--card);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.07);
        }

        .viagem-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 18px;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: background 0.15s;
        }

        .viagem-item:last-child { border-bottom: none; }
        .viagem-item:active { background: var(--primary-bg); }

        .viagem-item .vi-id {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
        }

        .viagem-item .vi-date {
            font-size: 0.9rem;
            color: var(--muted);
        }

        .vi-arrow { color: var(--muted); font-size: 1rem; }

        /* ── TOTAL DINHEIRO ── */
        .total-dinheiro {
            background: var(--primary-bg);
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 12px;
            font-size: 0.95rem;
            color: var(--primary-dark);
        }

        .total-dinheiro strong { font-size: 1.1rem; }

        /* ── COLLAPSIBLE SECTION ── */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            background: var(--card);
            border-radius: 12px;
            margin-bottom: 8px;
            cursor: pointer;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            user-select: none;
        }

        .section-header.blue  { background: #DBEAFE; }
        .section-header.green { background: var(--primary-bg); }

        .section-title {
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--text);
        }

        .section-header.blue  .section-title { color: #1D4ED8; }
        .section-header.green .section-title { color: var(--primary); }

        .chevron { font-size: 0.9rem; transition: transform 0.2s; }
        .chevron.open { transform: rotate(180deg); }

        .section-body { display: none; margin-bottom: 12px; }
        .section-body.open { display: block; }

        /* ── TOTALIZADOR CARDS ── */
        .totalizador-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            padding: 4px 0 8px;
        }

        .tot-card {
            background: var(--card);
            border-radius: 12px;
            padding: 16px 8px;
            text-align: center;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }

        .tot-value {
            display: inline-block;
            background: var(--primary);
            color: #fff;
            font-size: 1.4rem;
            font-weight: 800;
            width: 52px;
            height: 52px;
            line-height: 52px;
            border-radius: 12px;
            margin-bottom: 6px;
        }

        .tot-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--muted);
            letter-spacing: 0.4px;
        }

        /* ── PEDIDO CARD ── */
        .pedido-card {
            background: var(--card);
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 10px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.07);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
            transition: box-shadow 0.15s;
        }

        .pedido-card.entregue {
            background: #F0FDF4;
            border-left: 4px solid var(--success);
        }

        .pedido-card:active { box-shadow: 0 0 0 2px var(--primary); }

        .pedido-info { flex: 1; min-width: 0; }

        .pedido-header {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text);
            line-height: 1.3;
        }

        .pedido-cidade {
            font-size: 0.82rem;
            color: var(--muted);
            margin-top: 2px;
        }

        .pedido-qde {
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--primary);
            margin-top: 4px;
        }

        .pedido-situacao {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--muted);
            margin-top: 3px;
        }

        .pedido-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: center;
            flex-shrink: 0;
        }

        .btn-map {
            background: none;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
            line-height: 1;
            padding: 2px;
        }

        .btn-confirmar {
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 0.72rem;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-confirmar.entregue {
            background: #dcfce7;
            color: #166534;
        }

        /* ── MODAL ── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            display: none;
            align-items: flex-end;
            justify-content: center;
            z-index: 500;
        }

        .modal-overlay.active { display: flex; }

        .modal {
            background: #fff;
            border-radius: 20px 20px 0 0;
            width: 100%;
            max-width: 480px;
            padding: 24px 24px 40px;
            animation: slideUp 0.25s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(100%); }
            to   { transform: translateY(0); }
        }

        .modal-handle {
            width: 40px;
            height: 4px;
            background: var(--border);
            border-radius: 2px;
            margin: 0 auto 20px;
        }

        .modal-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .modal-subtitle {
            font-size: 0.85rem;
            color: var(--muted);
            margin-bottom: 20px;
        }

        .map-btns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }

        .map-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            transition: opacity 0.15s;
        }

        .map-btn:active { opacity: 0.7; }
        .map-btn.google { background: #E8F0FE; color: #1A73E8; }
        .map-btn.waze   { background: #EEF5FE; color: #33CCFF; }

        .form-label {
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: var(--muted);
            margin-bottom: 8px;
            display: block;
        }

        .fp-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 20px;
        }

        .fp-option {
            padding: 12px 10px;
            border: 2px solid var(--border);
            border-radius: 10px;
            text-align: center;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
            background: var(--card);
            color: var(--text);
        }

        .fp-option:active, .fp-option.selected {
            border-color: var(--primary);
            background: var(--primary-bg);
            color: var(--primary);
        }

        .btn-confirmar-entrega {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-confirmar-entrega:active { background: var(--primary-dark); }
        .btn-confirmar-entrega:disabled { background: var(--muted); cursor: not-allowed; }

        /* ── MAPA OPÇÕES MODAL ── */
        .mapa-info {
            background: var(--primary-bg);
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 16px;
            font-size: 0.85rem;
            color: var(--primary-dark);
        }

        /* ── EMPTY ── */
        .empty {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted);
        }

        .empty .icon { font-size: 2.5rem; margin-bottom: 10px; }

        /* ── LOADING ── */
        .loading {
            text-align: center;
            padding: 40px;
            color: var(--muted);
            font-size: 0.9rem;
        }

        /* ── BACK BUTTON ── */
        .btn-back {
            display: flex;
            align-items: center;
            gap: 6px;
            background: none;
            border: none;
            color: var(--primary);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
            margin-bottom: 16px;
        }

        /* ── TOAST ── */
        .toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: var(--text);
            color: #fff;
            padding: 12px 24px;
            border-radius: 24px;
            font-size: 0.9rem;
            font-weight: 600;
            z-index: 1000;
            opacity: 0;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        .toast.success { background: var(--primary); }
        .toast.error   { background: var(--danger); }

        /* ── VIAGEM ATIVA ── */
        .viagem-ativa-bar {
            background: var(--primary-dark);
            color: #fff;
            padding: 8px 16px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .viagem-ativa-bar span { font-weight: 700; }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="app-header">
        <div class="header-left">
            <div class="header-icon">🚚</div>
            <div>
                <div class="header-title"><?= htmlspecialchars($motoristaNome) ?></div>
                <div class="header-sub">Módulo de Entregas</div>
            </div>
        </div>
        <a href="?logout=1" class="btn-logout" onclick="return confirm('Sair do sistema?')">Sair</a>
    </header>

    <!-- VIAGEM ATIVA BAR (oculto até selecionar viagem) -->
    <div class="viagem-ativa-bar" id="viagemAtivaBar" style="display:none">
        <span id="viagemAtivaLabel">Viagem #---</span>
        <button style="background:rgba(255,255,255,0.2);border:none;color:#fff;padding:4px 10px;border-radius:6px;font-size:0.78rem;font-weight:700;cursor:pointer"
                onclick="voltarViagens()">Trocar</button>
    </div>

    <!-- TELA 1: SELEÇÃO DE VIAGEM -->
    <div class="screen active" id="screenViagens">
        <div class="select-title">Data Viagem</div>
        <div class="select-label">Selecione a Viagem</div>
        <div id="viagemListContainer">
            <div class="loading">Carregando viagens...</div>
        </div>
    </div>

    <!-- TELA 2: DASHBOARD DA VIAGEM -->
    <div class="screen" id="screenDashboard">
        <button class="btn-back" onclick="voltarViagens()">← Viagens</button>

        <!-- Total Dinheiro -->
        <div class="total-dinheiro" id="totalDinheiroCard" style="display:none">
            Valor Total em Dinheiro: <strong id="totalDinheiro">R$ 0,00</strong>
        </div>

        <!-- TOTALIZADOR -->
        <div class="section-header green" onclick="toggleSection('totalizador')">
            <span class="section-title">Totalizador</span>
            <span class="chevron open" id="chevronTotalizador">∧</span>
        </div>
        <div class="section-body open" id="totalizador">
            <div class="totalizador-grid">
                <div class="tot-card">
                    <div class="tot-value" id="totTotal">0</div>
                    <div class="tot-label">Total</div>
                </div>
                <div class="tot-card">
                    <div class="tot-value" id="totEntregues">0</div>
                    <div class="tot-label">Entregues</div>
                </div>
                <div class="tot-card">
                    <div class="tot-value" id="totAEntregar">0</div>
                    <div class="tot-label">A Entregar</div>
                </div>
            </div>
        </div>

        <!-- ENTREGAS (pendentes) -->
        <div class="section-header" onclick="toggleSection('entregas')">
            <span class="section-title">Entregas</span>
            <span class="chevron open" id="chevronEntregas">∧</span>
        </div>
        <div class="section-body open" id="entregas">
            <div id="listaEntregas"><div class="loading">Carregando...</div></div>
        </div>

        <!-- PEDIDOS FINALIZADOS -->
        <div class="section-header blue" onclick="toggleSection('finalizados')">
            <span class="section-title">Pedidos Finalizados</span>
            <span class="chevron open" id="chevronFinalizados">∧</span>
        </div>
        <div class="section-body open" id="finalizados">
            <div id="listaFinalizados"><div class="loading">Carregando...</div></div>
        </div>
    </div>

    <!-- MODAL: CONFIRMAR ENTREGA -->
    <div class="modal-overlay" id="modalConfirmar">
        <div class="modal">
            <div class="modal-handle"></div>
            <div class="modal-title" id="modalClienteNome">Cliente</div>
            <div class="modal-subtitle" id="modalCidadeNome"></div>

            <!-- Botões de mapa -->
            <div class="map-btns" id="mapBtns">
                <a href="#" id="linkGoogleMaps" class="map-btn google" target="_blank" rel="noopener">
                    🗺️ Google Maps
                </a>
                <a href="#" id="linkWaze" class="map-btn waze" target="_blank" rel="noopener">
                    🚗 Waze
                </a>
            </div>

            <!-- Forma de pagamento -->
            <label class="form-label">Forma de Pagamento</label>
            <div class="fp-grid" id="fpGrid">
                <div class="loading">Carregando...</div>
            </div>

            <button class="btn-confirmar-entrega" id="btnConfirmarEntrega" disabled onclick="confirmarEntrega()">
                ✅ Confirmar Entrega
            </button>
        </div>
    </div>

    <!-- TOAST -->
    <div class="toast" id="toast"></div>

    <script>
    // ─── Estado ──────────────────────────────────────────────────────────────
    let viagemAtualId   = null;
    let remessaAtualId  = null;
    let formaPgtoSel    = null;
    let formasPgto      = [];
    let pedidosCache    = [];

    // ─── Init ─────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', carregarViagens);

    // ─── Viagens ──────────────────────────────────────────────────────────────
    async function carregarViagens() {
        const container = document.getElementById('viagemListContainer');
        container.innerHTML = '<div class="loading">🔄 Carregando viagens...</div>';
        try {
            const res  = await fetch('motorista_app.php?ajax=viagens');
            const data = await res.json();
            if (!data.success) throw new Error(data.error);

            if (!data.data.length) {
                container.innerHTML = '<div class="empty"><div class="icon">📦</div><p>Nenhuma viagem encontrada</p></div>';
                return;
            }

            const ul = document.createElement('div');
            ul.className = 'viagem-list';
            ul.innerHTML = data.data.map(v => `
                <div class="viagem-item" onclick="selecionarViagem(${v.id}, '${escHtml(v.data_fmt)}')">
                    <div>
                        <div class="vi-id">${v.id}</div>
                        <div class="vi-date">${v.data_fmt}</div>
                    </div>
                    <span class="vi-arrow">›</span>
                </div>
            `).join('');
            container.innerHTML = '';
            container.appendChild(ul);
        } catch (e) {
            container.innerHTML = `<div class="empty"><div class="icon">❌</div><p>${e.message}</p></div>`;
        }
    }

    async function selecionarViagem(id, dataFmt) {
        viagemAtualId = id;
        document.getElementById('viagemAtivaBar').style.display = 'flex';
        document.getElementById('viagemAtivaLabel').textContent  = `Viagem ${id} — ${dataFmt}`;
        showScreen('screenDashboard');
        await carregarPedidos(id);
        await carregarFormasPgto();
    }

    function voltarViagens() {
        viagemAtualId = null;
        document.getElementById('viagemAtivaBar').style.display = 'none';
        showScreen('screenViagens');
    }

    // ─── Pedidos ──────────────────────────────────────────────────────────────
    async function carregarPedidos(viagemId) {
        document.getElementById('listaEntregas').innerHTML   = '<div class="loading">🔄 Carregando...</div>';
        document.getElementById('listaFinalizados').innerHTML = '<div class="loading">🔄 Carregando...</div>';
        try {
            const res  = await fetch(`motorista_app.php?ajax=pedidos&viagem_id=${viagemId}`);
            const data = await res.json();
            if (!data.success) throw new Error(data.error);

            pedidosCache = data.data;

            // Totalizador
            document.getElementById('totTotal').textContent     = data.total;
            document.getElementById('totEntregues').textContent = data.entregues;
            document.getElementById('totAEntregar').textContent = data.a_entregar;

            // Total dinheiro
            if (data.total_dinheiro > 0) {
                document.getElementById('totalDinheiroCard').style.display = '';
                document.getElementById('totalDinheiro').textContent =
                    'R$ ' + parseFloat(data.total_dinheiro).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
            }

            // Separa pendentes / finalizados
            const pendentes   = [];
            const finalizados = [];
            data.data.forEach(p => {
                const entregue = /ntreg|inaliz/i.test(p.situacao_descricao || '');
                if (entregue) finalizados.push(p);
                else          pendentes.push(p);
            });

            renderPedidos('listaEntregas',    pendentes,   false);
            renderPedidos('listaFinalizados', finalizados, true);
        } catch (e) {
            document.getElementById('listaEntregas').innerHTML   = `<div class="empty">❌ ${e.message}</div>`;
            document.getElementById('listaFinalizados').innerHTML = '';
        }
    }

    function renderPedidos(containerId, lista, entregue) {
        const el = document.getElementById(containerId);
        if (!lista.length) {
            el.innerHTML = `<div class="empty"><div class="icon">${entregue ? '✅' : '📦'}</div><p>${entregue ? 'Nenhuma entrega finalizada' : 'Sem pendências'}</p></div>`;
            return;
        }
        el.innerHTML = lista.map((p, idx) => {
            const seq        = (p.ordem > 0 ? p.ordem : (idx + 1));
            const cityLabel  = p.cidade_sigla ? ` ${p.cidade_sigla}` : '';
            const cityFull   = p.cidade_nome || '';
            const qdeValor   = `Qde/Valor: ${p.pacote_qde} - R$ ${parseFloat(p.total || 0).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
            const temCoord   = !!(p.latitude || p.coordenadas);
            const fpLabel    = entregue && p.forma_pgto_descricao ? `💳 ${p.forma_pgto_descricao}` : '';

            return `
            <div class="pedido-card ${entregue ? 'entregue' : ''}" onclick="abrirModal(${p.remessa_id})">
                <div class="pedido-info">
                    <div class="pedido-header">${seq}: ${p.remessa_id} - ${escHtml(p.cliente_nome || '')}${cityLabel}</div>
                    ${cityFull ? `<div class="pedido-cidade">${escHtml(cityFull)}</div>` : ''}
                    ${p.descricao ? `<div class="pedido-cidade">${escHtml(p.descricao)}</div>` : ''}
                    <div class="pedido-qde">${qdeValor}</div>
                    ${fpLabel ? `<div class="pedido-situacao">${fpLabel}</div>` : ''}
                </div>
                <div class="pedido-actions">
                    <button class="btn-map" onclick="event.stopPropagation(); abrirMapa(${p.remessa_id})"
                            title="Abrir no mapa" ${!temCoord ? 'style="opacity:0.3"' : ''}>🗺️</button>
                    <button class="btn-confirmar ${entregue ? 'entregue' : ''}"
                            onclick="event.stopPropagation(); abrirModal(${p.remessa_id})">
                        ${entregue ? '✅ OK' : 'Entregar'}
                    </button>
                </div>
            </div>`;
        }).join('');
    }

    // ─── Modal Confirmação ────────────────────────────────────────────────────
    async function abrirModal(remessaId) {
        const p = pedidosCache.find(x => x.remessa_id == remessaId);
        if (!p) return;

        remessaAtualId = remessaId;
        formaPgtoSel   = p.forma_pagamento_id || null;

        document.getElementById('modalClienteNome').textContent = `${p.remessa_id} - ${p.cliente_nome || ''}`;
        document.getElementById('modalCidadeNome').textContent  = `${p.cidade_nome || ''} ${p.descricao ? '| ' + p.descricao : ''}`.trim();

        // Links de mapa
        const lat  = p.latitude;
        const lng  = p.longitude;
        const temCoord = !!(lat && lng);

        document.getElementById('linkGoogleMaps').href = temCoord
            ? `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`
            : '#';
        document.getElementById('linkWaze').href = temCoord
            ? `https://waze.com/ul?ll=${lat},${lng}&navigate=yes`
            : '#';

        if (!temCoord) {
            document.getElementById('mapBtns').style.opacity = '0.4';
            document.getElementById('mapBtns').style.pointerEvents = 'none';
        } else {
            document.getElementById('mapBtns').style.opacity = '';
            document.getElementById('mapBtns').style.pointerEvents = '';
        }

        // Formas de pagamento
        renderFormasPgto(formaPgtoSel);

        document.getElementById('modalConfirmar').classList.add('active');
        document.getElementById('btnConfirmarEntrega').disabled = !formaPgtoSel;
    }

    function fecharModal() {
        document.getElementById('modalConfirmar').classList.remove('active');
        remessaAtualId = null;
        formaPgtoSel   = null;
    }

    // Fechar modal ao clicar fora
    document.getElementById('modalConfirmar').addEventListener('click', function(e) {
        if (e.target === this) fecharModal();
    });

    // ─── Formas de Pagamento ──────────────────────────────────────────────────
    async function carregarFormasPgto() {
        if (formasPgto.length) return;
        try {
            const res  = await fetch('motorista_app.php?ajax=formas_pgto');
            const data = await res.json();
            if (data.success) formasPgto = data.data;
        } catch (_) {}
    }

    function renderFormasPgto(selectedId) {
        const grid = document.getElementById('fpGrid');
        if (!formasPgto.length) {
            grid.innerHTML = '<div class="loading">Carregando...</div>';
            return;
        }
        grid.innerHTML = formasPgto.map(fp => `
            <div class="fp-option ${fp.id == selectedId ? 'selected' : ''}"
                 onclick="selecionarFp(${fp.id}, this)">
                ${escHtml(fp.descricao)}
            </div>
        `).join('');
    }

    function selecionarFp(id, el) {
        formaPgtoSel = id;
        document.querySelectorAll('.fp-option').forEach(o => o.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('btnConfirmarEntrega').disabled = false;
    }

    // ─── Confirmar Entrega ────────────────────────────────────────────────────
    async function confirmarEntrega() {
        if (!remessaAtualId || !formaPgtoSel) return;

        const btn = document.getElementById('btnConfirmarEntrega');
        btn.disabled = true;
        btn.textContent = 'Confirmando...';

        try {
            const fd = new FormData();
            fd.append('ajax',          'confirmar');
            fd.append('remessa_id',    remessaAtualId);
            fd.append('forma_pgto_id', formaPgtoSel);

            const res  = await fetch('motorista_app.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (!data.success) throw new Error(data.error);

            showToast('✅ Entrega confirmada!', 'success');
            fecharModal();
            await carregarPedidos(viagemAtualId);
        } catch (e) {
            showToast(e.message, 'error');
            btn.disabled = false;
            btn.textContent = '✅ Confirmar Entrega';
        }
    }

    // ─── Abrir mapa diretamente ───────────────────────────────────────────────
    function abrirMapa(remessaId) {
        const p = pedidosCache.find(x => x.remessa_id == remessaId);
        if (!p || !p.latitude) { showToast('Sem coordenadas disponíveis', 'error'); return; }
        window.open(`https://www.google.com/maps/dir/?api=1&destination=${p.latitude},${p.longitude}`, '_blank');
    }

    // ─── Sections Collapsible ─────────────────────────────────────────────────
    function toggleSection(id) {
        const body    = document.getElementById(id);
        const chevron = document.getElementById('chevron' + id.charAt(0).toUpperCase() + id.slice(1));
        body.classList.toggle('open');
        if (chevron) chevron.classList.toggle('open');
    }

    // ─── Navegação de telas ───────────────────────────────────────────────────
    function showScreen(id) {
        document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
        document.getElementById(id).classList.add('active');
    }

    // ─── Toast ────────────────────────────────────────────────────────────────
    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        t.textContent = msg;
        t.className = `toast ${type} show`;
        setTimeout(() => t.classList.remove('show'), 3000);
    }

    // ─── Escape HTML ──────────────────────────────────────────────────────────
    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
    </script>
</body>
</html>
