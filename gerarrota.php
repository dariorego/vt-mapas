<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerar Rota - Victor Transportes</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

    <!-- SortableJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>

    <style>
        :root {
            --primary-color: #1F6F50;
            --primary-dark: #16523c;
            --secondary-color: #2c3e50;
            --accent-color: #2F6F5E;
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

        /* Mobile Header */
        .mobile-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 56px;
            background: var(--primary-color);
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

        .menu-btn:hover {
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
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--primary-dark) 100%);
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

        .nav-item:hover,
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
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 68px 20px 20px 20px;
        }

        .search-card {
            background-color: var(--card-background);
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: flex-end;
            gap: 15px;
            flex-wrap: wrap;
        }

        .form-group {
            flex-grow: 1;
            min-width: 250px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 11px 24px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-transform: uppercase;
            font-weight: 600;
        }

        button:hover {
            background-color: var(--primary-dark);
        }

        .btn-secondary {
            background-color: var(--secondary-color);
        }

        .info-panel {
            background-color: var(--card-background);
            padding: 15px;
            margin-bottom: 20px;
            border-left: 5px solid var(--primary-color);
            border-radius: 4px;
        }

        .info-panel h2 {
            margin-top: 0;
            font-size: 1.1rem;
            color: var(--primary-color);
        }

        .layout-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        #map {
            height: 450px;
            width: 100%;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .table-container {
            background-color: var(--card-background);
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
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

        tr.sortable-drag {
            cursor: grabbing;
        }

        tbody tr {
            cursor: grab;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .search-card {
                flex-direction: column;
            }

            .form-group {
                width: 100%;
                min-width: auto;
            }

            #map {
                height: 350px;
            }
        }
    </style>
</head>

<body>
    <!-- Mobile Header -->
    <header class="mobile-header">
        <button class="menu-btn" onclick="toggleMenu()">☰</button>
        <span class="header-title">🗺️ Gerar Rota</span>
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
            <a href="gerarrota.php" class="nav-item active">
                <span class="icon">🗺️</span> Gerar Rota
            </a>
            <a href="validafornecedor.php" class="nav-item">
                <span class="icon">📦</span> Validar Fornecedor
            </a>
        </div>
        <div class="sidebar-footer">© 2026 Victor Transportes</div>
    </nav>

    <div class="container">
        <?php
        require_once 'config.php';
        require_once 'Database.php';
        require_once 'RouteOptimizer.php';

        $trips = [];
        try {
            $dbInit = new Database();
            $optInit = new RouteOptimizer($dbInit);
            $trips = $optInit->fetchAvailableTrips();
        } catch (Exception $e) {
            // Silencioso
        }
        ?>

        <div class="search-card">
            <div class="form-group">
                <form method="POST" action=""
                    style="display: flex; gap: 10px; width: 100%; align-items: flex-end; flex-wrap: wrap;">
                    <div style="flex-grow: 1; min-width: 250px;">
                        <label for="viagem_id">SELECIONE A VIAGEM</label>
                        <select id="viagem_id" name="viagem_id" required>
                            <option value="">-- Selecione --</option>
                            <?php foreach ($trips as $trip): ?>
                                <?php
                                $selected = (isset($_POST['viagem_id']) && $_POST['viagem_id'] == $trip['viagem_id']) ? 'selected' : '';
                                $label = "{$trip['viagem_id']} - " . date('d/m/Y', strtotime($trip['data_remessa'])) . " - " . strtoupper($trip['motorista_nome']);
                                ?>
                                <option value="<?php echo $trip['viagem_id']; ?>" <?php echo $selected; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="optimize">Carregar Rota</button>
                    <button type="submit" name="validate_route" class="btn-secondary">Rota Validada</button>
                </form>
            </div>
        </div>

        <?php
        if (isset($_POST['validate_route'])) {
            try {
                if (empty($_POST['viagem_id'])) {
                    throw new Exception("Selecione uma viagem primeiro!");
                }

                $db = new Database();
                $optimizer = new RouteOptimizer($db);
                $updated = $optimizer->validateRoute($_POST['viagem_id']);

                echo '<div class="alert alert-success">
                        ✅ Rota validada com sucesso! ' . $updated . ' registros atualizados.
                        <br><small>Os valores de ordem_auto foram copiados para o campo ordem.</small>
                      </div>';

            } catch (Exception $e) {
                echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
            }
        }
        ?>

        <?php
        if (isset($_POST['optimize'])) {
            try {
                $db = new Database();
                $optimizer = new RouteOptimizer($db);

                $startPoint = $optimizer->fetchStartingPoint();
                $deliveryPoints = $optimizer->fetchDeliveryPoints($_POST['viagem_id']);
                $route = $optimizer->optimizeRoute();
                $optimizer->updateDatabase();

                $firstPoint = reset($route);
                $motoristaNome = $firstPoint['motorista_nome'] ?? 'Não Definido';
                $dataRemessa = $firstPoint['data_remessa'] ?? date('Y-m-d');

                $summary = [
                    'distance' => $optimizer->getTotalDistance(),
                    'time' => $optimizer->getEstimatedTime(),
                    'count' => count($route)
                ];
                ?>

                <div class="info-panel">
                    <h2>Motorista: <?php echo strtoupper($motoristaNome); ?></h2>
                    <p style="margin: 5px 0 0;">
                        <strong>Viagem:</strong> #<?php echo $_POST['viagem_id']; ?> |
                        <strong>Data:</strong> <?php echo date('d/m/Y', strtotime($dataRemessa)); ?> |
                        <strong>Distância:</strong> <?php echo $summary['distance']; ?> km |
                        <strong>Tempo:</strong> <?php echo gmdate("H:i", $summary['time'] * 60); ?> h
                    </p>
                </div>

                <div class="layout-grid">
                    <div id="map"></div>

                    <div class="table-container">
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
                            <tbody>
                                <?php foreach ($route as $point): ?>
                                    <tr data-id="<?php echo $point['id']; ?>" data-lat="<?php echo $point['latitude']; ?>"
                                        data-lon="<?php echo $point['longitude']; ?>"
                                        data-client="<?php echo htmlspecialchars($point['cliente_nome'] ?? 'CLIENTE ' . $point['cliente_id']); ?>"
                                        data-status="<?php echo htmlspecialchars($point['situacao_descricao'] ?? 'PENDENTE'); ?>">
                                        <td class="seq-num"><strong
                                                style="color: var(--primary-color); font-size: 1.1em;"><?php echo $point['ordem']; ?></strong>
                                        </td>
                                        <td><strong><?php echo $point['cliente_nome'] ?? 'CLIENTE ' . $point['cliente_id']; ?></strong>
                                        </td>
                                        <td class="dist-cell"><?php echo $point['distancia']; ?> km</td>
                                        <td class="time-cell"><?php echo round(($point['distancia'] / 40) * 60); ?> min</td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo strtoupper($point['situacao_descricao'] ?? 'PENDENTE'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
                    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

                <script>
                    const startPoint = <?php echo json_encode($startPoint); ?>;
                    const routePoints = <?php echo json_encode($route); ?>;

                    const map = L.map('map').setView([startPoint.latitude, startPoint.longitude], 12);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '© OpenStreetMap'
                    }).addTo(map);

                    const startIcon = L.divIcon({
                        className: 'custom-flag-icon',
                        html: '<div style="font-size: 32px; filter: drop-shadow(2px 4px 6px black);">🚩</div>',
                        iconSize: [40, 40],
                        iconAnchor: [5, 40],
                        popupAnchor: [10, -35]
                    });

                    const endIcon = L.divIcon({
                        className: 'custom-flag-icon',
                        html: '<div style="font-size: 32px; filter: drop-shadow(2px 4px 6px black);">🏁</div>',
                        iconSize: [40, 40],
                        iconAnchor: [5, 40],
                        popupAnchor: [10, -35]
                    });

                    const deliveryIcon = L.divIcon({
                        className: 'custom-div-icon',
                        html: "<div style='background-color:#1F6F50; color:white; border-radius:50%; width:25px; height:25px; display:flex; justify-content:center; align-items:center; font-weight:bold; border:2px solid white; box-shadow:0 2px 4px rgba(0,0,0,0.3);'>%ORD%</div>",
                        iconSize: [30, 42],
                        iconAnchor: [15, 42]
                    });

                    L.marker([startPoint.latitude, startPoint.longitude], { icon: startIcon })
                        .addTo(map)
                        .bindPopup(`<b>${startPoint.nome}</b><br>🚩 Ponto de Partida`);

                    const latlngs = [[startPoint.latitude, startPoint.longitude]];

                    routePoints.forEach((point, index) => {
                        const lat = parseFloat(point.latitude);
                        const lng = parseFloat(point.longitude);
                        const isLast = index === routePoints.length - 1;

                        let iconToUse;

                        if (isLast) {
                            iconToUse = endIcon;
                        } else {
                            const iconHtml = deliveryIcon.options.html.replace('%ORD%', point.ordem);
                            iconToUse = L.divIcon({
                                className: 'custom-marker',
                                html: iconHtml,
                                iconSize: [30, 30],
                                iconAnchor: [15, 15]
                            });
                        }

                        let popupContent = `<b>${point.ordem}. ${point.cliente_nome || 'Cliente ' + point.cliente_id}</b><br>`;
                        if (isLast) popupContent += `🏁 <b>Última Entrega</b><br>`;
                        popupContent += `${point.situacao_descricao || ''}<br>`;
                        popupContent += `Distância: ${point.distancia} km`;

                        L.marker([lat, lng], { icon: iconToUse })
                            .addTo(map)
                            .bindPopup(popupContent);

                        latlngs.push([lat, lng]);
                    });

                    const polyline = L.polyline(latlngs, { color: '#1F6F50', weight: 4, opacity: 0.8 }).addTo(map);
                    map.fitBounds(polyline.getBounds(), { padding: [50, 50] });

                    let currentPolyline = polyline;

                    const tbody = document.querySelector('table tbody');
                    new Sortable(tbody, {
                        animation: 150,
                        ghostClass: 'sortable-ghost',
                        onEnd: function (evt) {
                            recalculateAndRedraw();
                        }
                    });

                    function calculateDistance(lat1, lon1, lat2, lon2) {
                        const R = 6371;
                        const dLat = (lat2 - lat1) * Math.PI / 180;
                        const dLon = (lon2 - lon1) * Math.PI / 180;
                        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                            Math.sin(dLon / 2) * Math.sin(dLon / 2);
                        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                        return R * c;
                    }

                    function recalculateAndRedraw() {
                        const rows = Array.from(tbody.querySelectorAll('tr'));

                        let currentLat = parseFloat(startPoint.latitude);
                        let currentLon = parseFloat(startPoint.longitude);
                        let totalDist = 0;

                        const newLatLngs = [[currentLat, currentLon]];
                        const mapPoints = [];

                        rows.forEach((row, index) => {
                            const lat = parseFloat(row.dataset.lat);
                            const lon = parseFloat(row.dataset.lon);

                            const dist = calculateDistance(currentLat, currentLon, lat, lon);
                            totalDist += dist;

                            const seqNum = index + 1;
                            row.querySelector('.seq-num strong').textContent = seqNum;
                            row.querySelector('.dist-cell').textContent = dist.toFixed(2) + ' km';
                            row.querySelector('.time-cell').textContent = Math.round((dist / 40) * 60) + ' min';

                            mapPoints.push({
                                lat: lat,
                                lon: lon,
                                ordem: seqNum,
                                cliente: row.dataset.client,
                                status: row.dataset.status
                            });

                            currentLat = lat;
                            currentLon = lon;
                            newLatLngs.push([lat, lon]);
                        });

                        map.eachLayer((layer) => {
                            if (layer instanceof L.Marker || layer instanceof L.Polyline) {
                                map.removeLayer(layer);
                            }
                        });

                        L.marker([startPoint.latitude, startPoint.longitude], { icon: startIcon })
                            .addTo(map)
                            .bindPopup(`<b>${startPoint.nome}</b><br>🚩 Ponto de Partida`);

                        mapPoints.forEach((p, index) => {
                            const isLast = index === mapPoints.length - 1;
                            let iconToUse;

                            if (isLast) {
                                iconToUse = endIcon;
                            } else {
                                const iconHtml = deliveryIcon.options.html.replace('%ORD%', p.ordem);
                                iconToUse = L.divIcon({
                                    className: 'custom-marker',
                                    html: iconHtml,
                                    iconSize: [30, 30],
                                    iconAnchor: [15, 15]
                                });
                            }

                            let popupContent = `<b>${p.ordem}. ${p.cliente}</b><br>`;
                            if (isLast) popupContent += `🏁 <b>Última Entrega</b><br>`;
                            popupContent += `${p.status}<br>`;

                            L.marker([p.lat, p.lon], { icon: iconToUse })
                                .addTo(map)
                                .bindPopup(popupContent);
                        });

                        currentPolyline = L.polyline(newLatLngs, { color: '#1F6F50', weight: 4, opacity: 0.8 }).addTo(map);
                    }
                </script>

                <?php
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
            }
        }
        ?>
    </div>

    <script>
        function toggleMenu() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('overlay').classList.toggle('active');
        }
        function closeMenu() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('overlay').classList.remove('active');
        }
    </script>
</body>

</html>