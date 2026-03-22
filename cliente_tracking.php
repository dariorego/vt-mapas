<?php
session_start();
require_once 'config.php';
require_once 'Database.php';

// ── Normaliza telefone (remove tudo que não é dígito) ────────────────────────
function normalizeFone(string $fone): string
{
    return preg_replace('/\D/', '', $fone);
}

// ── Logout ────────────────────────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: cliente_tracking.php');
    exit;
}

$error   = '';
$cliente = null;
$remessa = null;
$status  = null; // 'em_rota' | 'proximo' | 'em_breve' | 'entregue' | 'nao_encontrado'

// ── Login ─────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fone'])) {
    $foneInput = normalizeFone(trim($_POST['fone']));

    if (strlen($foneInput) < 8) {
        $error = 'Informe um telefone válido.';
    } else {
        try {
            $db = new Database();

            // Busca cliente pelo telefone (compara sem formatação)
            $clientes = $db->query(
                "SELECT id, nome, fone FROM cliente WHERE situacao = 'a' AND fone IS NOT NULL AND fone != ''"
            );

            $found = null;
            foreach ($clientes as $c) {
                if (normalizeFone($c['fone']) === $foneInput) {
                    $found = $c;
                    break;
                }
            }

            if (!$found) {
                $error = 'Telefone não encontrado. Verifique o número informado.';
            } else {
                $_SESSION['tracking_cliente_id']   = $found['id'];
                $_SESSION['tracking_cliente_nome']  = $found['nome'];
                header('Location: cliente_tracking.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Erro ao consultar. Tente novamente.';
        }
    }
}

// ── Carrega dados se logado ───────────────────────────────────────────────────
if (isset($_SESSION['tracking_cliente_id'])) {
    try {
        $db        = new Database();
        $clienteId = (int) $_SESSION['tracking_cliente_id'];

        // Última viagem que contém esse cliente
        $ultimaViagem = $db->queryOne(
            "SELECT viagem_id, MAX(data_remessa) as data_remessa
             FROM remessa
             WHERE cliente_id = :cid AND viagem_id > 0
             GROUP BY viagem_id
             ORDER BY MAX(data_remessa) DESC, viagem_id DESC
             LIMIT 1",
            ['cid' => $clienteId]
        );

        if (!$ultimaViagem) {
            $status = 'nao_encontrado';
        } else {
            $viagemId = $ultimaViagem['viagem_id'];

            // Remessa do cliente nessa viagem
            $remessa = $db->queryOne(
                "SELECT r.id, r.ordem, r.pacote_qde, r.fardo_qde, r.total,
                        r.remessa_situacao_id, rs.descricao AS situacao_descricao,
                        r.forma_pagamento_id, fp.descricao AS forma_pagamento,
                        r.data_remessa, r.data_hora_entrega, r.viagem_id,
                        r.descricao AS obs
                 FROM remessa r
                 LEFT JOIN remessa_situacao rs ON rs.id = r.remessa_situacao_id
                 LEFT JOIN forma_pagamento  fp ON fp.id = r.forma_pagamento_id
                 WHERE r.cliente_id = :cid AND r.viagem_id = :vid
                 LIMIT 1",
                ['cid' => $clienteId, 'vid' => $viagemId]
            );

            if (!$remessa) {
                $status = 'nao_encontrado';
            } elseif ($remessa['remessa_situacao_id'] == 3) {
                // situacao_id 3 = Entregue
                $status = 'entregue';
            } else {
                // Conta quantas entregas NÃO entregues estão antes do cliente (ordem menor)
                $ordemCliente = (int) $remessa['ordem'];

                $pendentesAntes = $db->queryOne(
                    "SELECT COUNT(*) AS total
                     FROM remessa
                     WHERE viagem_id = :vid
                       AND ordem > 0
                       AND ordem < :ordem
                       AND remessa_situacao_id != 3",
                    ['vid' => $viagemId, 'ordem' => $ordemCliente]
                );

                $pendentes = (int) ($pendentesAntes['total'] ?? 999);

                if ($pendentes == 0) {
                    $status = 'em_breve';
                } elseif ($pendentes <= 2) {
                    $status = 'proximo';
                } else {
                    $status = 'em_rota';
                }
            }
        }

        $cliente = ['nome' => $_SESSION['tracking_cliente_nome']];

    } catch (Exception $e) {
        $status = 'nao_encontrado';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Rastreamento de Entrega — <?php echo htmlspecialchars(EMPRESA_NOME); ?></title>
    <style>
        :root {
            --green:  <?php echo EMPRESA_COR_PRIMARIA; ?>;
            --green2: <?php echo EMPRESA_COR_SECUNDARIA; ?>;
            --yellow: #f0a500;
            --blue:   #1a73e8;
            --red:    #e53935;
            --bg:     #f4f7f6;
            --card:   #ffffff;
            --text:   #333;
            --muted:  #666;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* ── Header ─────────────────────────────────────── */
        .header {
            width: 100%;
            background: linear-gradient(135deg, var(--green), var(--green2));
            color: <?php echo EMPRESA_COR_TEXTO; ?>;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: space-between;
        }
        .header-brand { display: flex; align-items: center; gap: 10px; }
        .header-logo { width: 36px; height: 36px; object-fit: contain; border-radius: 6px; }
        .header h1 { font-size: 1.05rem; font-weight: 700; line-height: 1.2; }
        .header h1 small { display: block; font-size: 0.7rem; font-weight: 400; opacity: 0.85; }
        .header .logout {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            border: 1px solid rgba(255,255,255,0.5);
            padding: 4px 10px;
            border-radius: 20px;
        }

        /* ── Conteúdo ────────────────────────────────────── */
        .content {
            width: 100%;
            max-width: 480px;
            padding: 20px 16px 40px;
        }

        /* ── Card de login ───────────────────────────────── */
        .login-card {
            background: var(--card);
            border-radius: 16px;
            padding: 32px 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            text-align: center;
        }
        .login-card .icon { font-size: 3rem; margin-bottom: 12px; }
        .login-card h2 { font-size: 1.3rem; margin-bottom: 6px; color: var(--green); }
        .login-card p  { font-size: 0.9rem; color: var(--muted); margin-bottom: 24px; }

        .login-card input[type="tel"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 1.1rem;
            text-align: center;
            letter-spacing: 2px;
            outline: none;
            transition: border-color 0.2s;
        }
        .login-card input[type="tel"]:focus { border-color: var(--green); }

        .btn-login {
            width: 100%;
            margin-top: 16px;
            padding: 14px;
            background: var(--green);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
        }
        .btn-login:active { opacity: 0.85; }

        .error-msg {
            margin-top: 14px;
            padding: 10px 14px;
            background: #fde8e8;
            color: var(--red);
            border-radius: 8px;
            font-size: 0.88rem;
        }

        /* ── Boas-vindas ─────────────────────────────────── */
        .welcome {
            font-size: 0.95rem;
            color: var(--muted);
            margin-bottom: 20px;
        }
        .welcome strong { color: var(--text); }

        /* ── Card de status ──────────────────────────────── */
        .status-card {
            background: var(--card);
            border-radius: 16px;
            padding: 24px 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 16px;
            text-align: center;
        }

        .status-icon { font-size: 3.5rem; margin-bottom: 10px; }

        .status-badge {
            display: inline-block;
            padding: 6px 18px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 14px;
        }
        .badge-rota    { background: #e8f5e9; color: var(--green); }
        .badge-proximo { background: #fff8e1; color: var(--yellow); }
        .badge-breve   { background: #fff3e0; color: #e65100; }
        .badge-entregue{ background: #e3f2fd; color: var(--blue); }
        .badge-erro    { background: #fde8e8; color: var(--red); }

        .status-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .status-msg {
            font-size: 0.92rem;
            color: var(--muted);
            line-height: 1.5;
        }

        /* ── Barra de progresso ──────────────────────────── */
        .progress-steps {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0;
            margin: 20px 0 4px;
        }
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }
        .step-dot {
            width: 28px; height: 28px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            background: #e0e0e0;
            color: #999;
            position: relative;
            z-index: 1;
        }
        .step-dot.done  { background: var(--green); color: #fff; }
        .step-dot.active{ background: var(--yellow); color: #fff; box-shadow: 0 0 0 4px rgba(240,165,0,0.2); }
        .step-label {
            font-size: 0.65rem;
            color: var(--muted);
            margin-top: 5px;
            text-align: center;
        }
        .step-line {
            flex: 1;
            height: 3px;
            background: #e0e0e0;
            margin-top: -14px;
        }
        .step-line.done { background: var(--green); }

        /* ── Card de detalhes ────────────────────────────── */
        .detail-card {
            background: var(--card);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .detail-card h3 {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--muted);
            margin-bottom: 16px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label {
            font-size: 0.85rem;
            color: var(--muted);
        }
        .detail-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text);
            text-align: right;
        }
        .detail-value.highlight {
            color: var(--green);
            font-size: 1.1rem;
        }

        /* ── Sem viagem ──────────────────────────────────── */
        .empty-card {
            background: var(--card);
            border-radius: 16px;
            padding: 40px 20px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .empty-card .icon { font-size: 3rem; margin-bottom: 12px; }
        .empty-card p { color: var(--muted); font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="header">
    <div class="header-brand">
        <?php if (EMPRESA_LOGO): ?>
            <img src="<?php echo htmlspecialchars(EMPRESA_LOGO); ?>" alt="Logo" class="header-logo">
        <?php else: ?>
            <span style="font-size:1.8rem;">📦</span>
        <?php endif; ?>
        <h1>
            <?php echo htmlspecialchars(EMPRESA_NOME); ?>
            <small>Rastreamento de Entrega</small>
        </h1>
    </div>
    <?php if (isset($_SESSION['tracking_cliente_id'])): ?>
        <a href="?logout=1" class="logout">Sair</a>
    <?php endif; ?>
</div>

<div class="content">

<?php if (!isset($_SESSION['tracking_cliente_id'])): ?>
    <!-- ── Tela de Login ───────────────────────────────── -->
    <div class="login-card">
        <div class="icon">📱</div>
        <h2>Acompanhe sua entrega</h2>
        <p>Informe o telefone cadastrado no seu pedido para ver o status da entrega.</p>

        <form method="POST">
            <input type="tel" name="fone" placeholder="(00) 00000-0000"
                   autofocus inputmode="numeric" maxlength="20">
            <button type="submit" class="btn-login">Consultar</button>
        </form>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
    </div>

<?php elseif ($status === 'nao_encontrado'): ?>
    <!-- ── Sem entrega ────────────────────────────────── -->
    <p class="welcome">Olá, <strong><?php echo htmlspecialchars($cliente['nome']); ?></strong>!</p>
    <div class="empty-card">
        <div class="icon">🔍</div>
        <p>Não encontramos nenhuma entrega ativa para o seu cadastro no momento.</p>
    </div>

<?php else: ?>
    <!-- ── Tela de Status ─────────────────────────────── -->
    <p class="welcome">Olá, <strong><?php echo htmlspecialchars($cliente['nome']); ?></strong>!</p>

    <?php
        // Configuração visual por status
        $cfg = match($status) {
            'em_rota'  => [
                'icon'   => '🚚',
                'badge'  => 'badge-rota',
                'label'  => 'Em Rota',
                'title'  => 'Sua entrega está a caminho',
                'msg'    => 'O entregador está realizando as entregas do dia. Acompanhe aqui quando sua vez estiver próxima.',
                'step'   => 1,
            ],
            'proximo'  => [
                'icon'   => '📍',
                'badge'  => 'badge-proximo',
                'label'  => 'Próxima Entrega',
                'title'  => 'Está entre as próximas 3 entregas!',
                'msg'    => 'Fique atento! O entregador estará em breve na sua localização.',
                'step'   => 2,
            ],
            'em_breve' => [
                'icon'   => '🔔',
                'badge'  => 'badge-breve',
                'label'  => 'Chegando Agora',
                'title'  => 'Prepare-se para receber!',
                'msg'    => 'Você é a próxima entrega. Fique disponível para receber o entregador.',
                'step'   => 2,
            ],
            'entregue' => [
                'icon'   => '✅',
                'badge'  => 'badge-entregue',
                'label'  => 'Entregue',
                'title'  => 'Entrega realizada com sucesso',
                'msg'    => 'Seu pedido foi entregue. Obrigado pela preferência!',
                'step'   => 3,
            ],
            default => [
                'icon'   => '❓',
                'badge'  => 'badge-erro',
                'label'  => 'Sem informação',
                'title'  => 'Status não disponível',
                'msg'    => '',
                'step'   => 0,
            ],
        };

        $step = $cfg['step'];
    ?>

    <!-- Card de status -->
    <div class="status-card">
        <div class="status-icon"><?php echo $cfg['icon']; ?></div>
        <div class="status-badge <?php echo $cfg['badge']; ?>"><?php echo $cfg['label']; ?></div>
        <div class="status-title"><?php echo $cfg['title']; ?></div>
        <?php if ($cfg['msg']): ?>
            <div class="status-msg"><?php echo $cfg['msg']; ?></div>
        <?php endif; ?>

        <!-- Barra de progresso -->
        <div class="progress-steps" style="margin-top: 24px;">
            <div class="step">
                <div class="step-dot <?php echo $step >= 1 ? 'done' : ''; ?>">🚚</div>
                <div class="step-label">Em rota</div>
            </div>
            <div class="step-line <?php echo $step >= 2 ? 'done' : ''; ?>"></div>
            <div class="step">
                <div class="step-dot <?php echo $step === 2 ? 'active' : ($step > 2 ? 'done' : ''); ?>">📍</div>
                <div class="step-label">Próximo</div>
            </div>
            <div class="step-line <?php echo $step >= 3 ? 'done' : ''; ?>"></div>
            <div class="step">
                <div class="step-dot <?php echo $step >= 3 ? 'done' : ''; ?>">✅</div>
                <div class="step-label">Entregue</div>
            </div>
        </div>
    </div>

    <?php if ($remessa): ?>
    <!-- Card de detalhes da entrega -->
    <div class="detail-card">
        <h3>Detalhes da Entrega</h3>

        <?php if (!empty($remessa['total']) && $remessa['total'] > 0): ?>
        <div class="detail-row">
            <span class="detail-label">Valor total</span>
            <span class="detail-value highlight">R$ <?php echo number_format($remessa['total'], 2, ',', '.'); ?></span>
        </div>
        <?php endif; ?>

        <?php if (!empty($remessa['forma_pagamento'])): ?>
        <div class="detail-row">
            <span class="detail-label">Forma de pagamento</span>
            <span class="detail-value"><?php echo htmlspecialchars($remessa['forma_pagamento']); ?></span>
        </div>
        <?php endif; ?>

        <?php
            $pacotes = (int)($remessa['pacote_qde'] ?? 0);
            $fardos  = (int)($remessa['fardo_qde']  ?? 0);
        ?>
        <?php if ($pacotes > 0): ?>
        <div class="detail-row">
            <span class="detail-label">Pacotes</span>
            <span class="detail-value"><?php echo $pacotes; ?> <?php echo $pacotes == 1 ? 'pacote' : 'pacotes'; ?></span>
        </div>
        <?php endif; ?>

        <?php if ($fardos > 0): ?>
        <div class="detail-row">
            <span class="detail-label">Fardos</span>
            <span class="detail-value"><?php echo $fardos; ?> <?php echo $fardos == 1 ? 'fardo' : 'fardos'; ?></span>
        </div>
        <?php endif; ?>

        <?php if (!empty($remessa['data_remessa'])): ?>
        <div class="detail-row">
            <span class="detail-label">Data da remessa</span>
            <span class="detail-value"><?php echo date('d/m/Y', strtotime($remessa['data_remessa'])); ?></span>
        </div>
        <?php endif; ?>

        <?php if ($status === 'entregue' && !empty($remessa['data_hora_entrega'])): ?>
        <div class="detail-row">
            <span class="detail-label">Entregue em</span>
            <span class="detail-value"><?php echo date('d/m/Y H:i', strtotime($remessa['data_hora_entrega'])); ?></span>
        </div>
        <?php endif; ?>

        <?php if (!empty($remessa['obs'])): ?>
        <div class="detail-row">
            <span class="detail-label">Observação</span>
            <span class="detail-value" style="font-weight:400; color: var(--muted); font-size:0.85rem;"><?php echo htmlspecialchars($remessa['obs']); ?></span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Auto-refresh quando não entregue -->
    <?php if ($status !== 'entregue'): ?>
    <p style="text-align:center; color: var(--muted); font-size: 0.75rem; margin-top: 20px;">
        Atualiza automaticamente a cada 2 minutos
    </p>
    <script>
        setTimeout(() => location.reload(), 120000);
    </script>
    <?php endif; ?>

<?php endif; ?>

</div>

<div style="text-align:center; padding: 20px 16px 40px; color: #999; font-size: 0.78rem;">
    <?php echo htmlspecialchars(EMPRESA_NOME); ?>
    <?php if (EMPRESA_TELEFONE): ?>
        &nbsp;·&nbsp;
        <a href="tel:<?php echo preg_replace('/\D/','',(string)EMPRESA_TELEFONE); ?>"
           style="color:#999; text-decoration:none;">
            <?php echo htmlspecialchars(EMPRESA_TELEFONE); ?>
        </a>
    <?php endif; ?>
    <?php if (EMPRESA_WHATSAPP): ?>
        &nbsp;·&nbsp;
        <a href="https://wa.me/<?php echo htmlspecialchars(EMPRESA_WHATSAPP); ?>"
           target="_blank" style="color:#25D366; text-decoration:none; font-weight:600;">
            WhatsApp
        </a>
    <?php endif; ?>
</div>

</body>
</html>
