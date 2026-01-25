<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';
require_once 'Database.php';
require_once 'RouteOptimizer.php';

// AJAX: Busca pontos da viagem (sem otimizar)
if (isset($_GET['ajax']) && $_GET['ajax'] === 'pontos') {
    header('Content-Type: application/json');
    try {
        $viagemId = $_GET['viagem_id'] ?? null;
        if (!$viagemId)
            throw new Exception('Viagem não informada');

        $db = new Database();
        $optimizer = new RouteOptimizer($db);
        $startPoint = $optimizer->fetchStartingPoint();
        $points = $optimizer->fetchDeliveryPoints($viagemId);

        // Pega info do motorista do primeiro ponto
        $firstPoint = reset($points);
        $motorista = $firstPoint['motorista_nome'] ?? 'Não definido';
        $dataRemessa = $firstPoint['data_remessa'] ?? null;

        // Verifica se os pontos já têm ordem definida (ordem > 0)
        $hasOrder = false;
        foreach ($points as $p) {
            if (!empty($p['ordem']) && $p['ordem'] > 0) {
                $hasOrder = true;
                break;
            }
        }

        // Se tem ordem, ordena os pontos pela ordem
        if ($hasOrder) {
            usort($points, function ($a, $b) {
                return ($a['ordem'] ?? 999) - ($b['ordem'] ?? 999);
            });
        }

        echo json_encode([
            'success' => true,
            'startPoint' => $startPoint,
            'points' => $points,
            'motorista' => $motorista,
            'data' => $dataRemessa,
            'total' => count($points),
            'hasOrder' => $hasOrder
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Otimiza rota
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['optimize'])) {
    header('Content-Type: application/json');
    try {
        $db = new Database();
        $optimizer = new RouteOptimizer($db);
        $startPoint = $optimizer->fetchStartingPoint();
        $optimizer->fetchDeliveryPoints($_POST['viagem_id']);
        $route = $optimizer->optimizeRoute();
        $optimizer->updateDatabase();

        $firstPoint = reset($route);

        echo json_encode([
            'success' => true,
            'startPoint' => $startPoint,
            'route' => $route,
            'motorista' => $firstPoint['motorista_nome'] ?? 'Não definido',
            'totalDistance' => $optimizer->getTotalDistance(),
            'estimatedTime' => $optimizer->getEstimatedTime()
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Valida rota
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_route'])) {
    header('Content-Type: application/json');
    try {
        if (empty($_POST['viagem_id']))
            throw new Exception('Viagem não informada');

        $db = new Database();
        $optimizer = new RouteOptimizer($db);
        $updated = $optimizer->validateRoute($_POST['viagem_id']);

        echo json_encode(['success' => true, 'updated' => $updated]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Salvar nova ordem após drag-and-drop
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_order'])) {
    header('Content-Type: application/json');
    try {
        $orders = json_decode($_POST['orders'], true);
        if (empty($orders)) {
            throw new Exception('Nenhuma ordem informada');
        }

        $db = new Database();
        $db->beginTransaction();

        foreach ($orders as $item) {
            $sql = "UPDATE remessa SET ordem_auto = :ordem WHERE id = :id";
            $db->execute($sql, [
                'ordem' => $item['ordem'],
                'id' => $item['id']
            ]);
        }

        $db->commit();
        echo json_encode(['success' => true, 'updated' => count($orders)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

$trips = [];
try {
    $dbInit = new Database();
    $optInit = new RouteOptimizer($dbInit);
    $trips = $optInit->fetchAvailableTrips();
} catch (Exception $e) {
}
$currentPage = 'gerarrota.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerar Rota - Victor Transportes</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>

    <style>
        :root {
            --primary-color: #1F6F50;
            --primary-dark: #16523c;
            --secondary-color: #2c3e50;
            --background-color: #f4f7f6;
            --card-background: #ffffff;
            --text-color: #333;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
        }

        /* Remove page-specific mobile header since sidebar provides it */
        .container {

            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .search-card {
            background-color: var(--card-background);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .search-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--secondary-color);
            font-size: 0.85rem;
        }

        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: var(--primary-dark);
        }

        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .btn-secondary {
            background-color: var(--secondary-color);
        }

        .info-panel {
            background-color: var(--card-background);
            padding: 12px 16px;
            margin-bottom: 16px;
            border-left: 4px solid var(--primary-color);
            border-radius: 6px;
            display: none;
        }

        .info-panel.visible {
            display: block;
        }

        .info-panel h3 {
            margin: 0;
            font-size: 1rem;
            color: var(--primary-color);
        }

        .info-panel p {
            margin: 4px 0 0;
            font-size: 0.9rem;
            color: #666;
        }

        #map {
            height: 400px;
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 16px;
        }

        .table-container {
            background-color: var(--card-background);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
            display: none;
        }

        .table-container.visible {
            display: block;
        }

        .table-header {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 15px;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        th,
        td {
            padding: 10px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f9fa;
            color: var(--secondary-color);
            border-bottom: 2px solid #ddd;
            font-weight: 700;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        tr.sortable-ghost {
            background-color: #e8f5e9;
            opacity: 0.8;
        }

        tbody tr {
            cursor: grab;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }

            .form-group {
                width: 100%;
                min-width: auto;
            }

            #map {
                height: 300px;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="container page-with-sidebar">
        <div class="search-card">
            <form class="search-form" id="routeForm">
                <div class="form-group">
                    <label for="viagem_id">SELECIONE A VIAGEM</label>
                    <select id="viagem_id" name="viagem_id" required>
                        <option value="">-- Selecione --</option>
                        <?php foreach ($trips as $trip): ?>
                            <option value="<?php echo $trip['viagem_id']; ?>">
                                <?php echo "{$trip['viagem_id']} - " . date('d/m/Y', strtotime($trip['data_remessa'])) . " - " . strtoupper($trip['motorista_nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" id="btnCarregarRota" disabled>🗺️ Gerar Rota</button>
                <button type="button" id="btnValidarRota" class="btn-secondary" disabled>✅ Validar Rota</button>
            </form>
        </div>

        <div class="info-panel" id="infoPanel">
            <h3 id="infoMotorista">Motorista</h3>
            <p id="infoDetalhes">Detalhes da viagem</p>
        </div>

        <div id="alertContainer"></div>

        <div id="map"></div>

        <div class="table-container" id="tableContainer">
            <div class="table-header">ROTEIRO DE ENTREGA</div>
            <table>
                <thead>
                    <tr>
                        <th width="50">SEQ</th>
                        <th>CLIENTE</th>
                        <th>DISTÂNCIA</th>
                        <th>TEMPO</th>
                        <th>SITUAÇÃO</th>
                    </tr>
                </thead>
                <tbody id="tableBody"></tbody>
            </table>
        </div>
    </div>

    <script>
        // Estado global
        let map;
        let markers = [];
        let polyline = null;
        let startPoint = null;
        let currentPoints = [];
        let isRouteOptimized = false;
        let currentViagemId = null;

        // Inicializa mapa
        function initMap() {
            map = L.map('map').setView([-8.05, -34.9], 11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);
        }

        // Limpa mapa
        function clearMap() {
            markers.forEach(m => map.removeLayer(m));
            markers = [];
            if (polyline) {
                map.removeLayer(polyline);
                polyline = null;
            }
        }

        // Ícones
        const startIcon = L.divIcon({
            className: 'custom-flag-icon',
            html: '<div style="font-size: 32px; filter: drop-shadow(2px 4px 6px black);">🚩</div>',
            iconSize: [40, 40], iconAnchor: [5, 40], popupAnchor: [10, -35]
        });

        const endIcon = L.divIcon({
            className: 'custom-flag-icon',
            html: '<div style="font-size: 32px; filter: drop-shadow(2px 4px 6px black);">🏁</div>',
            iconSize: [40, 40], iconAnchor: [5, 40], popupAnchor: [10, -35]
        });

        function createNumberIcon(num) {
            return L.divIcon({
                className: 'custom-marker',
                html: `<div style='background-color:#1F6F50; color:white; border-radius:50%; width:28px; height:28px; display:flex; justify-content:center; align-items:center; font-weight:bold; border:2px solid white; box-shadow:0 2px 4px rgba(0,0,0,0.3);'>${num}</div>`,
                iconSize: [28, 28], iconAnchor: [14, 14]
            });
        }

        // Mostra pontos sem rota (apenas marcadores)
        function showPointsOnMap(data) {
            clearMap();
            startPoint = data.startPoint;
            currentPoints = data.points;
            isRouteOptimized = false;

            // Marcador do ponto de partida
            const startMarker = L.marker([startPoint.latitude, startPoint.longitude], { icon: startIcon })
                .addTo(map)
                .bindPopup(`<b>${startPoint.nome}</b><br>🚩 Ponto de Partida`);
            markers.push(startMarker);

            const bounds = [[startPoint.latitude, startPoint.longitude]];

            // Marcadores dos pontos de entrega (pontos vermelhos simples)
            currentPoints.forEach((point, index) => {
                const lat = parseFloat(point.latitude);
                const lon = parseFloat(point.longitude);

                const pointIcon = L.divIcon({
                    className: 'point-marker',
                    html: `<div style='background-color:#dc3545; border-radius:50%; width:14px; height:14px; border:2px solid white; box-shadow:0 2px 4px rgba(0,0,0,0.3);'></div>`,
                    iconSize: [14, 14], iconAnchor: [7, 7]
                });

                const marker = L.marker([lat, lon], { icon: pointIcon })
                    .addTo(map)
                    .bindPopup(`<b>${point.cliente_nome || 'Cliente ' + point.cliente_id}</b><br>${point.situacao_descricao || ''}`);
                markers.push(marker);
                bounds.push([lat, lon]);
            });

            map.fitBounds(bounds, { padding: [50, 50] });

            // Atualiza info
            document.getElementById('infoMotorista').textContent = `Motorista: ${data.motorista.toUpperCase()}`;
            document.getElementById('infoDetalhes').textContent =
                `Viagem #${currentViagemId} | Data: ${data.data ? new Date(data.data).toLocaleDateString('pt-BR') : 'N/D'} | ${data.total} pontos de entrega`;
            document.getElementById('infoPanel').classList.add('visible');
            document.getElementById('tableContainer').classList.remove('visible');
        }

        // Mostra rota já existente no banco (ordem já definida)
        function showExistingRoute(data) {
            clearMap();
            startPoint = data.startPoint;
            currentPoints = data.points;
            isRouteOptimized = true;

            // Marcador de partida
            const startMarker = L.marker([startPoint.latitude, startPoint.longitude], { icon: startIcon })
                .addTo(map)
                .bindPopup(`<b>${startPoint.nome}</b><br>🚩 Ponto de Partida`);
            markers.push(startMarker);

            const latlngs = [[startPoint.latitude, startPoint.longitude]];

            // Marcadores numerados conforme ordem do banco
            currentPoints.forEach((point, index) => {
                const lat = parseFloat(point.latitude);
                const lon = parseFloat(point.longitude);
                const isLast = index === currentPoints.length - 1;
                const ordem = point.ordem || (index + 1);

                const icon = isLast ? endIcon : createNumberIcon(ordem);
                let popup = `<b>${ordem}. ${point.cliente_nome || 'Cliente'}</b><br>`;
                if (isLast) popup += `🏁 <b>Última Entrega</b><br>`;
                popup += `${point.situacao_descricao || ''}`;

                const marker = L.marker([lat, lon], { icon })
                    .addTo(map)
                    .bindPopup(popup);
                markers.push(marker);
                latlngs.push([lat, lon]);
            });

            // Linha da rota
            polyline = L.polyline(latlngs, { color: '#1F6F50', weight: 4, opacity: 0.8 }).addTo(map);
            map.fitBounds(polyline.getBounds(), { padding: [50, 50] });

            // Atualiza info
            document.getElementById('infoMotorista').textContent = `Motorista: ${data.motorista.toUpperCase()}`;
            document.getElementById('infoDetalhes').textContent =
                `Viagem #${currentViagemId} | Data: ${data.data ? new Date(data.data).toLocaleDateString('pt-BR') : 'N/D'} | ${data.total} pontos | ✅ Ordem já definida`;
            document.getElementById('infoPanel').classList.add('visible');

            // Preenche tabela
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';
            currentPoints.forEach((point, index) => {
                const ordem = point.ordem || (index + 1);
                const tr = document.createElement('tr');
                tr.dataset.id = point.id;
                tr.dataset.lat = point.latitude;
                tr.dataset.lon = point.longitude;
                tr.dataset.client = point.cliente_nome || '';
                tr.dataset.status = point.situacao_descricao || '';
                tr.innerHTML = `
                    <td class="seq-num"><strong style="color: var(--primary-color);">${ordem}</strong></td>
                    <td><strong>${point.cliente_nome || 'Cliente ' + point.cliente_id}</strong></td>
                    <td class="dist-cell">-</td>
                    <td class="time-cell">-</td>
                    <td><span class="badge badge-info">${(point.situacao_descricao || 'PENDENTE').toUpperCase()}</span></td>
                `;
                tbody.appendChild(tr);
            });
            document.getElementById('tableContainer').classList.add('visible');

            // Habilita drag-and-drop
            new Sortable(tbody, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: recalculateRoute
            });

            // Mantém botão de carregar rota habilitado para re-otimizar se quiser
            document.getElementById('btnCarregarRota').disabled = false;
            document.getElementById('btnValidarRota').disabled = false;
        }

        // Mostra rota otimizada
        function showOptimizedRoute(data) {
            clearMap();
            startPoint = data.startPoint;
            currentPoints = data.route;
            isRouteOptimized = true;

            // Marcador de partida
            const startMarker = L.marker([startPoint.latitude, startPoint.longitude], { icon: startIcon })
                .addTo(map)
                .bindPopup(`<b>${startPoint.nome}</b><br>🚩 Ponto de Partida`);
            markers.push(startMarker);

            const latlngs = [[startPoint.latitude, startPoint.longitude]];

            // Marcadores numerados
            currentPoints.forEach((point, index) => {
                const lat = parseFloat(point.latitude);
                const lon = parseFloat(point.longitude);
                const isLast = index === currentPoints.length - 1;

                const icon = isLast ? endIcon : createNumberIcon(point.ordem);
                let popup = `<b>${point.ordem}. ${point.cliente_nome || 'Cliente'}</b><br>`;
                if (isLast) popup += `🏁 <b>Última Entrega</b><br>`;
                popup += `${point.situacao_descricao || ''}<br>Distância: ${point.distancia} km`;

                const marker = L.marker([lat, lon], { icon })
                    .addTo(map)
                    .bindPopup(popup);
                markers.push(marker);
                latlngs.push([lat, lon]);
            });

            // Linha da rota
            polyline = L.polyline(latlngs, { color: '#1F6F50', weight: 4, opacity: 0.8 }).addTo(map);
            map.fitBounds(polyline.getBounds(), { padding: [50, 50] });

            // Atualiza info
            document.getElementById('infoMotorista').textContent = `Motorista: ${data.motorista.toUpperCase()}`;
            document.getElementById('infoDetalhes').textContent =
                `Viagem #${currentViagemId} | Distância: ${data.totalDistance} km | Tempo: ${Math.floor(data.estimatedTime / 60)}h${data.estimatedTime % 60}min`;
            document.getElementById('infoPanel').classList.add('visible');

            // Preenche tabela
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = '';
            currentPoints.forEach(point => {
                const tr = document.createElement('tr');
                tr.dataset.id = point.id;
                tr.dataset.lat = point.latitude;
                tr.dataset.lon = point.longitude;
                tr.dataset.client = point.cliente_nome || '';
                tr.dataset.status = point.situacao_descricao || '';
                tr.innerHTML = `
                    <td class="seq-num"><strong style="color: var(--primary-color);">${point.ordem}</strong></td>
                    <td><strong>${point.cliente_nome || 'Cliente ' + point.cliente_id}</strong></td>
                    <td class="dist-cell">${point.distancia} km</td>
                    <td class="time-cell">${Math.round((point.distancia / 40) * 60)} min</td>
                    <td><span class="badge badge-info">${(point.situacao_descricao || 'PENDENTE').toUpperCase()}</span></td>
                `;
                tbody.appendChild(tr);
            });
            document.getElementById('tableContainer').classList.add('visible');

            // Habilita drag-and-drop
            new Sortable(tbody, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: recalculateRoute
            });
        }

        // Recalcula após drag-and-drop e salva no banco
        async function recalculateRoute() {
            const tbody = document.getElementById('tableBody');
            const rows = tbody.querySelectorAll('tr');
            const newLatLngs = [[startPoint.latitude, startPoint.longitude]];

            let currentLat = parseFloat(startPoint.latitude);
            let currentLon = parseFloat(startPoint.longitude);

            clearMap();

            // Start marker
            const startMarker = L.marker([startPoint.latitude, startPoint.longitude], { icon: startIcon })
                .addTo(map).bindPopup(`<b>${startPoint.nome}</b><br>🚩 Partida`);
            markers.push(startMarker);

            // Coleta as novas ordens
            const orders = [];

            rows.forEach((row, index) => {
                const lat = parseFloat(row.dataset.lat);
                const lon = parseFloat(row.dataset.lon);
                const seqNum = index + 1;
                const dist = calculateDistance(currentLat, currentLon, lat, lon);
                const isLast = index === rows.length - 1;

                row.querySelector('.seq-num strong').textContent = seqNum;
                row.querySelector('.dist-cell').textContent = dist.toFixed(2) + ' km';
                row.querySelector('.time-cell').textContent = Math.round((dist / 40) * 60) + ' min';

                const icon = isLast ? endIcon : createNumberIcon(seqNum);
                const marker = L.marker([lat, lon], { icon }).addTo(map)
                    .bindPopup(`<b>${seqNum}. ${row.dataset.client}</b>`);
                markers.push(marker);

                currentLat = lat;
                currentLon = lon;
                newLatLngs.push([lat, lon]);

                // Adiciona à lista de ordens para salvar
                orders.push({ id: row.dataset.id, ordem: seqNum });
            });

            polyline = L.polyline(newLatLngs, { color: '#1F6F50', weight: 4, opacity: 0.8 }).addTo(map);

            // Salva a nova ordem no banco
            try {
                const formData = new FormData();
                formData.append('save_order', '1');
                formData.append('orders', JSON.stringify(orders));

                const res = await fetch('gerarrota.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    showAlert(`✅ Ordem salva automaticamente (${data.updated} pontos)`, 'success');
                } else {
                    showAlert('⚠️ Erro ao salvar ordem: ' + data.error, 'danger');
                }
            } catch (e) {
                showAlert('⚠️ Erro ao salvar ordem', 'danger');
            }
        }

        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        function showAlert(message, type = 'info') {
            document.getElementById('alertContainer').innerHTML =
                `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => document.getElementById('alertContainer').innerHTML = '', 5000);
        }

        // Event Listeners
        document.getElementById('viagem_id').addEventListener('change', async function () {
            const viagemId = this.value;
            document.getElementById('btnCarregarRota').disabled = !viagemId;
            document.getElementById('btnValidarRota').disabled = true;

            if (!viagemId) {
                clearMap();
                document.getElementById('infoPanel').classList.remove('visible');
                document.getElementById('tableContainer').classList.remove('visible');
                return;
            }

            currentViagemId = viagemId;

            try {
                const res = await fetch(`gerarrota.php?ajax=pontos&viagem_id=${viagemId}`);
                const data = await res.json();
                if (data.success) {
                    if (data.hasOrder) {
                        // Se já tem ordem definida, mostra a rota com a ordem do banco
                        showExistingRoute(data);
                    } else {
                        // Se não tem ordem, mostra apenas os pontos
                        showPointsOnMap(data);
                    }
                } else {
                    showAlert(data.error, 'danger');
                }
            } catch (e) {
                showAlert('Erro ao carregar pontos', 'danger');
            }
        });

        document.getElementById('btnCarregarRota').addEventListener('click', async function () {
            if (!currentViagemId) return;

            // Confirma se deseja regenerar a rota
            if (!confirm('⚠️ A rota será recalculada e a ordem atual será substituída.\n\nDeseja continuar?')) {
                return;
            }

            this.disabled = true;
            this.textContent = '⏳ Gerando...';

            try {
                const formData = new FormData();
                formData.append('optimize', '1');
                formData.append('viagem_id', currentViagemId);

                const res = await fetch('gerarrota.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    showOptimizedRoute(data);
                    document.getElementById('btnValidarRota').disabled = false;
                    showAlert('✅ Rota otimizada com sucesso!', 'success');
                } else {
                    showAlert(data.error, 'danger');
                }
            } catch (e) {
                showAlert('Erro ao otimizar rota', 'danger');
            }

            this.disabled = false;
            this.textContent = '🗺️ Gerar Rota';
        });

        document.getElementById('btnValidarRota').addEventListener('click', async function () {
            if (!currentViagemId) return;

            if (!confirm('Confirma validação da rota? Os valores de ordem_auto serão copiados para ordem.')) return;

            try {
                const formData = new FormData();
                formData.append('validate_route', '1');
                formData.append('viagem_id', currentViagemId);

                const res = await fetch('gerarrota.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    showAlert(`✅ Rota validada! ${data.updated} registros atualizados.`, 'success');
                } else {
                    showAlert(data.error, 'danger');
                }
            } catch (e) {
                showAlert('Erro ao validar rota', 'danger');
            }
        });



        // Inicializa
        initMap();
    </script>


</body>

</html>