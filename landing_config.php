<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';

$dataFile = __DIR__ . '/data/landing.json';

function loadLanding(string $file): array {
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (is_array($data)) return $data;
    }
    return [];
}

// ── AJAX: salvar ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $data = loadLanding($dataFile);

    try {
        switch ($_POST['action']) {

            case 'save_hero':
                $data['hero'] = [
                    'badge'     => trim($_POST['badge']     ?? ''),
                    'titulo'    => trim($_POST['titulo']    ?? ''),
                    'subtitulo' => trim($_POST['subtitulo'] ?? ''),
                ];
                break;

            case 'save_servicos':
                $servicos = json_decode($_POST['servicos'] ?? '[]', true);
                if (!is_array($servicos)) throw new Exception('Dados inválidos');
                $data['servicos'] = array_map(fn($s) => [
                    'icone'   => trim($s['icone']   ?? '📦'),
                    'titulo'  => trim($s['titulo']  ?? ''),
                    'descricao'=> trim($s['descricao'] ?? ''),
                    'ativo'   => (bool)($s['ativo'] ?? true),
                ], $servicos);
                break;

            case 'save_cidades':
                $cidades = json_decode($_POST['cidades'] ?? '[]', true);
                if (!is_array($cidades)) throw new Exception('Dados inválidos');
                $data['cidades'] = array_values(array_filter(array_map('trim', $cidades)));
                break;

            case 'save_contato':
                $data['contato'] = [
                    'titulo'    => trim($_POST['titulo']    ?? ''),
                    'subtitulo' => trim($_POST['subtitulo'] ?? ''),
                    'email'     => trim($_POST['email']     ?? ''),
                    'endereco'  => trim($_POST['endereco']  ?? ''),
                ];
                break;

            default:
                throw new Exception('Ação desconhecida');
        }

        file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

$landing     = loadLanding($dataFile);
$hero        = $landing['hero']     ?? [];
$servicos    = $landing['servicos'] ?? [];
$cidades     = $landing['cidades']  ?? [];
$contato     = $landing['contato']  ?? [];
$currentPage = 'landing_config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Landing Page</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    <style>
        :root {
            --primary: <?php echo EMPRESA_COR_PRIMARIA; ?>;
            --primary-d: <?php echo EMPRESA_COR_SECUNDARIA; ?>;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',sans-serif; background:#f4f7f6; color:#333; }

        .container { max-width: 860px; margin: 0 auto; padding: 24px 16px 60px; }

        h1 { font-size:1.4rem; font-weight:800; margin-bottom:6px; color:#1a1a2e; }
        .page-sub { color:#666; font-size:0.9rem; margin-bottom:28px; }
        .page-sub a { color: var(--primary); text-decoration:none; }

        /* ── Card ───────────────────────────────────── */
        .card {
            background:#fff; border-radius:14px;
            padding:24px; margin-bottom:20px;
            box-shadow:0 2px 12px rgba(0,0,0,0.06);
        }
        .card-header {
            display:flex; align-items:center; justify-content:space-between;
            margin-bottom:18px;
        }
        .card-header h2 { font-size:1rem; font-weight:700; color:#1a1a2e; }
        .card-header span { font-size:1.3rem; }

        /* ── Form ───────────────────────────────────── */
        .form-row { margin-bottom:14px; }
        label { display:block; font-size:0.78rem; font-weight:600; color:#555; margin-bottom:5px; text-transform:uppercase; letter-spacing:.5px; }
        input[type=text], textarea {
            width:100%; padding:10px 12px;
            border:1.5px solid #ddd; border-radius:8px;
            font-size:0.92rem; font-family:inherit;
            transition:border-color .2s; outline:none;
        }
        input[type=text]:focus, textarea:focus { border-color:var(--primary); }
        textarea { resize:vertical; min-height:72px; }

        .btn-save {
            background:var(--primary); color:#fff;
            border:none; padding:10px 22px;
            border-radius:8px; font-size:0.88rem;
            font-weight:700; cursor:pointer;
            transition:background .2s;
        }
        .btn-save:hover { background:var(--primary-d); }

        /* ── Serviços ───────────────────────────────── */
        .servico-item {
            border:1.5px solid #eee; border-radius:10px;
            padding:14px; margin-bottom:10px;
            display:grid; grid-template-columns:36px 1fr auto;
            gap:12px; align-items:start;
            background:#fafafa; cursor:grab;
        }
        .servico-item:active { cursor:grabbing; }
        .sortable-ghost { opacity:.4; }
        .drag-handle { font-size:1.2rem; color:#bbb; padding-top:2px; cursor:grab; }
        .servico-fields { display:flex; flex-direction:column; gap:8px; }
        .servico-row { display:flex; gap:8px; }
        .servico-row input[type=text] { flex:1; }
        .servico-icon-input { width:60px !important; text-align:center; font-size:1.2rem; }
        .btn-remove {
            background:#fee2e2; border:none; color:#dc2626;
            border-radius:6px; padding:6px 10px;
            cursor:pointer; font-size:0.8rem; white-space:nowrap;
            align-self:start; margin-top:2px;
        }
        .btn-remove:hover { background:#fca5a5; }
        .btn-add {
            border:2px dashed #ccc; background:transparent;
            color:#666; border-radius:8px;
            padding:10px; width:100%; cursor:pointer;
            font-size:0.88rem; margin-top:6px;
            transition:border-color .2s, color .2s;
        }
        .btn-add:hover { border-color:var(--primary); color:var(--primary); }
        .toggle-ativo { display:flex; align-items:center; gap:6px; font-size:0.78rem; color:#555; }
        .toggle-ativo input { width:auto; }

        /* ── Cidades ────────────────────────────────── */
        .cidades-wrap { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:14px; }
        .cidade-tag {
            display:flex; align-items:center; gap:6px;
            background:#f0faf5; border:1.5px solid #c6eadb;
            border-radius:24px; padding:5px 10px 5px 14px;
            font-size:0.85rem; color:#1a5c3a;
        }
        .cidade-tag button {
            background:none; border:none; cursor:pointer;
            color:#888; font-size:1rem; line-height:1;
            padding:0 2px;
        }
        .cidade-tag button:hover { color:#dc2626; }
        .cidade-add-row { display:flex; gap:8px; }
        .cidade-add-row input { flex:1; }

        /* ── Toast ──────────────────────────────────── */
        #toast {
            position:fixed; bottom:24px; right:24px;
            background:#1a1a2e; color:#fff;
            padding:12px 20px; border-radius:10px;
            font-size:0.88rem; opacity:0;
            transition:opacity .3s; pointer-events:none;
            z-index:999;
        }
        #toast.show { opacity:1; }
        #toast.error { background:#dc2626; }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>

<div class="container page-with-sidebar">
    <h1>🌐 Configurar Landing Page</h1>
    <p class="page-sub">
        Edite o conteúdo exibido em
        <a href="/" target="_blank">vt.logapp.com.br</a> para visitantes.
    </p>

    <!-- ── Hero ──────────────────────────────────────────────── -->
    <div class="card">
        <div class="card-header">
            <h2>Cabeçalho (Hero)</h2>
            <span>🦸</span>
        </div>
        <div class="form-row">
            <label>Badge (texto pequeno acima do título)</label>
            <input type="text" id="hero_badge" value="<?php echo htmlspecialchars($hero['badge'] ?? ''); ?>">
        </div>
        <div class="form-row">
            <label>Título principal</label>
            <input type="text" id="hero_titulo" value="<?php echo htmlspecialchars($hero['titulo'] ?? ''); ?>">
        </div>
        <div class="form-row">
            <label>Subtítulo / Descrição</label>
            <textarea id="hero_subtitulo"><?php echo htmlspecialchars($hero['subtitulo'] ?? ''); ?></textarea>
        </div>
        <button class="btn-save" onclick="saveHero()">Salvar Hero</button>
    </div>

    <!-- ── Serviços ───────────────────────────────────────────── -->
    <div class="card">
        <div class="card-header">
            <h2>O que fazemos — Serviços</h2>
            <span>📦</span>
        </div>
        <div id="servicosList">
            <?php foreach ($servicos as $i => $s): ?>
            <div class="servico-item" data-idx="<?php echo $i; ?>">
                <div class="drag-handle">⠿</div>
                <div class="servico-fields">
                    <div class="servico-row">
                        <input type="text" class="servico-icon-input" placeholder="📦" value="<?php echo htmlspecialchars($s['icone'] ?? '📦'); ?>">
                        <input type="text" placeholder="Título" value="<?php echo htmlspecialchars($s['titulo'] ?? ''); ?>">
                    </div>
                    <textarea placeholder="Descrição"><?php echo htmlspecialchars($s['descricao'] ?? ''); ?></textarea>
                    <label class="toggle-ativo">
                        <input type="checkbox" <?php echo ($s['ativo'] ?? true) ? 'checked' : ''; ?>> Ativo (exibir na página)
                    </label>
                </div>
                <button class="btn-remove" onclick="removeServico(this)">✕ Remover</button>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="btn-add" onclick="addServico()">+ Adicionar serviço</button>
        <br><br>
        <button class="btn-save" onclick="saveServicos()">Salvar Serviços</button>
    </div>

    <!-- ── Cidades ────────────────────────────────────────────── -->
    <div class="card">
        <div class="card-header">
            <h2>Área de Atuação — Cidades</h2>
            <span>📍</span>
        </div>
        <div class="cidades-wrap" id="cidadesWrap">
            <?php foreach ($cidades as $cidade): ?>
            <div class="cidade-tag">
                <?php echo htmlspecialchars($cidade); ?>
                <button onclick="removeCidade(this)" title="Remover">×</button>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="cidade-add-row">
            <input type="text" id="novaCidade" placeholder="Nome da cidade" onkeydown="if(event.key==='Enter') addCidade()">
            <button class="btn-save" onclick="addCidade()">Adicionar</button>
        </div>
        <br>
        <button class="btn-save" onclick="saveCidades()">Salvar Cidades</button>
    </div>

    <!-- ── Contato ────────────────────────────────────────────── -->
    <div class="card">
        <div class="card-header">
            <h2>Fale Conosco — Contato</h2>
            <span>💬</span>
        </div>
        <p style="font-size:0.82rem;color:#888;margin-bottom:14px;">
            Telefone e WhatsApp são configurados em <strong>config.php</strong>.
        </p>
        <div class="form-row">
            <label>Título da seção</label>
            <input type="text" id="contato_titulo" value="<?php echo htmlspecialchars($contato['titulo'] ?? 'Fale conosco'); ?>">
        </div>
        <div class="form-row">
            <label>Subtítulo</label>
            <textarea id="contato_subtitulo"><?php echo htmlspecialchars($contato['subtitulo'] ?? ''); ?></textarea>
        </div>
        <div class="form-row">
            <label>E-mail</label>
            <input type="text" id="contato_email" placeholder="contato@empresa.com.br" value="<?php echo htmlspecialchars($contato['email'] ?? ''); ?>">
        </div>
        <div class="form-row">
            <label>Endereço</label>
            <input type="text" id="contato_endereco" placeholder="Rua Exemplo, 123 — Recife, PE" value="<?php echo htmlspecialchars($contato['endereco'] ?? ''); ?>">
        </div>
        <button class="btn-save" onclick="saveContato()">Salvar Contato</button>
    </div>
</div>

<div id="toast"></div>

<script>
    // ── Toast ─────────────────────────────────────────────────
    function toast(msg, error = false) {
        const el = document.getElementById('toast');
        el.textContent = msg;
        el.className = 'show' + (error ? ' error' : '');
        setTimeout(() => el.className = '', 3000);
    }

    async function post(data) {
        const fd = new FormData();
        for (const [k, v] of Object.entries(data)) fd.append(k, v);
        const r = await fetch('landing_config.php', { method: 'POST', body: fd });
        return r.json();
    }

    // ── Hero ──────────────────────────────────────────────────
    async function saveHero() {
        const res = await post({
            action:    'save_hero',
            badge:     document.getElementById('hero_badge').value,
            titulo:    document.getElementById('hero_titulo').value,
            subtitulo: document.getElementById('hero_subtitulo').value,
        });
        res.success ? toast('✅ Hero salvo!') : toast('❌ ' + res.error, true);
    }

    // ── Serviços ──────────────────────────────────────────────
    new Sortable(document.getElementById('servicosList'), {
        animation: 150, handle: '.drag-handle', ghostClass: 'sortable-ghost'
    });

    function addServico() {
        const list = document.getElementById('servicosList');
        const div = document.createElement('div');
        div.className = 'servico-item';
        div.innerHTML = `
            <div class="drag-handle">⠿</div>
            <div class="servico-fields">
                <div class="servico-row">
                    <input type="text" class="servico-icon-input" placeholder="📦" value="📦">
                    <input type="text" placeholder="Título">
                </div>
                <textarea placeholder="Descrição"></textarea>
                <label class="toggle-ativo">
                    <input type="checkbox" checked> Ativo (exibir na página)
                </label>
            </div>
            <button class="btn-remove" onclick="removeServico(this)">✕ Remover</button>
        `;
        list.appendChild(div);
        div.querySelector('input[placeholder="Título"]').focus();
    }

    function removeServico(btn) {
        if (document.querySelectorAll('.servico-item').length <= 1) {
            toast('Deve existir pelo menos 1 serviço.', true); return;
        }
        btn.closest('.servico-item').remove();
    }

    async function saveServicos() {
        const items = [...document.querySelectorAll('.servico-item')].map(el => {
            const inputs  = el.querySelectorAll('input[type=text]');
            const ta      = el.querySelector('textarea');
            const ativo   = el.querySelector('input[type=checkbox]').checked;
            return { icone: inputs[0].value, titulo: inputs[1].value, descricao: ta.value, ativo };
        });
        const res = await post({ action: 'save_servicos', servicos: JSON.stringify(items) });
        res.success ? toast('✅ Serviços salvos!') : toast('❌ ' + res.error, true);
    }

    // ── Cidades ───────────────────────────────────────────────
    function addCidade() {
        const input = document.getElementById('novaCidade');
        const nome = input.value.trim();
        if (!nome) return;

        const wrap = document.getElementById('cidadesWrap');
        const div = document.createElement('div');
        div.className = 'cidade-tag';
        div.innerHTML = `${nome} <button onclick="removeCidade(this)" title="Remover">×</button>`;
        wrap.appendChild(div);
        input.value = '';
        input.focus();
    }

    function removeCidade(btn) {
        btn.closest('.cidade-tag').remove();
    }

    async function saveCidades() {
        const cidades = [...document.querySelectorAll('.cidade-tag')].map(el =>
            el.childNodes[0].textContent.trim()
        );
        const res = await post({ action: 'save_cidades', cidades: JSON.stringify(cidades) });
        res.success ? toast('✅ Cidades salvas!') : toast('❌ ' + res.error, true);
    }

    // ── Contato ───────────────────────────────────────────────
    async function saveContato() {
        const res = await post({
            action:    'save_contato',
            titulo:    document.getElementById('contato_titulo').value,
            subtitulo: document.getElementById('contato_subtitulo').value,
            email:     document.getElementById('contato_email').value,
            endereco:  document.getElementById('contato_endereco').value,
        });
        res.success ? toast('✅ Contato salvo!') : toast('❌ ' + res.error, true);
    }
</script>
</body>
</html>
