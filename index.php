<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Otimizador de Rotas de Entrega - Victor Transportes</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

    <!-- SortableJS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
     
    <style>
        :root {
            --primary-color: #1F6F50; /* Baseado na imagem: verde escuro */
            --secondary-color: #2c3e50;
            --accent-color: #2F6F5E;
            --background-color: #f4f7f6;
            --card-background: #ffffff;
            --text-color: #333;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
        }

        .header-bar {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header-bar h1 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .search-card {
            background-color: var(--card-background);
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            align-items: flex-end;
            gap: 15px;
        }

        .form-group {
            flex-grow: 1;
            max-width: 300px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        input[type="number"] {
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
            background-color: #16523c;
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
            height: 500px;
            width: 100%;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .table-container {
            background-color: var(--card-background);
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
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

        th, td {
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
        
        /* Drag and Drop Styles */
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
        
        .badge-success { background-color: #d4edda; color: #155724; }
        .badge-warning { background-color: #fff3cd; color: #856404; }
        .badge-info { background-color: #d1ecf1; color: #0c5460; }

        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success { background-color: #d4edda; color: #155724; }
        .alert-danger { background-color: #f8d7da; color: #721c24; }

    </style>
</head>
<body>
    <div class="header-bar">
        <h1>Plataforma de Gestão Logística - Victor Transportes</h1>
    </div>

    <div class="container">
        <!-- Search -->
        <?php
        // Carrega viagens disponíveis para o dropdown
        require_once 'config.php';
        require_once 'Database.php';
        require_once 'RouteOptimizer.php';

        $trips = [];
        try {
            $dbInit = new Database();
            $optInit = new RouteOptimizer($dbInit);
            $trips = $optInit->fetchAvailableTrips();
        } catch (Exception $e) {
            // Silencioso na inicialização
        }
        ?>

        <!-- Search -->
        <div class="search-card">
            <div class="form-group">
                <form method="POST" action="" style="display: flex; gap: 10px; width: 100%; align-items: flex-end;">
                    <div style="flex-grow: 1;">
                        <label for="viagem_id">SELECIONE A VIAGEM</label>
                        <select id="viagem_id" name="viagem_id" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;" required>
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
                </form>
            </div>
        </div>

        <?php
        if (isset($_POST['optimize'])) {
            require_once 'config.php';
            require_once 'Database.php';
            require_once 'RouteOptimizer.php';

            try {
                $db = new Database();
                $optimizer = new RouteOptimizer($db);

                // Busca dados
                $startPoint = $optimizer->fetchStartingPoint();
                $deliveryPoints = $optimizer->fetchDeliveryPoints($_POST['viagem_id']);

                // Otimiza
                $route = $optimizer->optimizeRoute();
                $optimizer->updateDatabase(); // Atualiza ordem no banco
        
                // Pega dados extras do primeiro ponto para o cabeçalho (motorista é o mesmo pra viagem)
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
                                <strong>Distância Total:</strong> <?php echo $summary['distance']; ?> km | 
                                <strong>Tempo Estimado:</strong> <?php echo gmdate("H:i", $summary['time'] * 60); ?> h
                            </p>
                        </div>

                        <div class="layout-grid">
                            <!-- Mapa Full Width -->
                            <div id="map"></div>
                    
                            <!-- Tabela Footer -->
                            <div class="table-container">
                                <div class="table-header">ROTEIRO DE ENTREGA</div>
                                <table>
                                    <thead>
                                        <tr>
                                            <th width="50">SEQ</th>
                                            <th>CLIENTE</th>
                                            <th>DISTÂNCIA (KM)</th>
                                            <th>TEMPO ESTIMADO</th>
                                            <th>SITUAÇÃO</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($route as $point): ?>
                                            <tr data-id="<?php echo $point['id']; ?>" 
                                                data-lat="<?php echo $point['latitude']; ?>" 
                                                data-lon="<?php echo $point['longitude']; ?>"
                                                data-client="<?php echo htmlspecialchars($point['cliente_nome'] ?? 'CLIENTE ' . $point['cliente_id']); ?>"
                                                data-status="<?php echo htmlspecialchars($point['situacao_descricao'] ?? 'PENDENTE'); ?>">
                                                <td class="seq-num"><strong style="color: var(--primary-color); font-size: 1.1em;"><?php echo $point['ordem']; ?></strong></td>
                                                <td>
                                                    <strong><?php echo $point['cliente_nome'] ?? 'CLIENTE ' . $point['cliente_id']; ?></strong>
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

                        <!-- Leaflet JS -->
                        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
                         integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
                         crossorigin=""></script>
                 
                        <script>
                            const startPoint = <?php echo json_encode($startPoint); ?>;
                            const routePoints = <?php echo json_encode($route); ?>;
                    
                            const map = L.map('map').setView([startPoint.latitude, startPoint.longitude], 12);

                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                maxZoom: 19,
                                attribution: '© OpenStreetMap'
                            }).addTo(map);

                            // Ícones
                            // Ícone Bandeira Início 🚩
                            const startIcon = L.divIcon({
                                className: 'custom-flag-icon',
                                html: '<div style="font-size: 32px; filter: drop-shadow(2px 4px 6px black);">🚩</div>',
                                iconSize: [40, 40],
                                iconAnchor: [5, 40],
                                popupAnchor: [10, -35]
                            });

                            // Ícone Bandeira Fim (Chegada) 🏁
                            const endIcon = L.divIcon({
                                className: 'custom-flag-icon',
                                html: '<div style="font-size: 32px; filter: drop-shadow(2px 4px 6px black);">🏁</div>',
                                iconSize: [40, 40],
                                iconAnchor: [5, 40],
                                popupAnchor: [10, -35]
                            });

                            // Ícone Numérico Padrão
                            const deliveryIcon = L.divIcon({
                                className: 'custom-div-icon',
                                html: "<div style='background-color:#1F6F50; color:white; border-radius:50%; width:25px; height:25px; display:flex; justify-content:center; align-items:center; font-weight:bold; border:2px solid white; box-shadow:0 2px 4px rgba(0,0,0,0.3);'>%ORD%</div>",
                                iconSize: [30, 42],
                                iconAnchor: [15, 42]
                            });

                            // Marcador Inicial
                            L.marker([startPoint.latitude, startPoint.longitude], {icon: startIcon})
                             .addTo(map)
                             .bindPopup(`<b>${startPoint.nome}</b><br>🚩 Ponto de Partida`);

                            const latlngs = [[startPoint.latitude, startPoint.longitude]];

                            // Marcadores da Rota
                            routePoints.forEach((point, index) => {
                                const lat = parseFloat(point.latitude);
                                const lng = parseFloat(point.longitude);
                                const isLast = index === routePoints.length - 1;
                        
                                let iconToUse;

                                if (isLast) {
                                    iconToUse = endIcon;
                                } else {
                                    // Cria ícone numerado
                                    const iconHtml = deliveryIcon.options.html.replace('%ORD%', point.ordem);
                                    iconToUse = L.divIcon({
                                        className: 'custom-marker',
                                        html: iconHtml,
                                        iconSize: [30, 30],
                                        iconAnchor: [15, 15]
                                    });
                                }

                                let popupContent = `<b>${point.ordem}. ${point.cliente_nome || 'Cliente ' + point.cliente_id}</b><br>`;
                                if (isLast) popupContent += `🏁 <b>Última Entrega (Próx. ao Motorista)</b><br>`;
                                popupContent += `${point.situacao_descricao || ''}<br>`;
                                popupContent += `Distância: ${point.distancia} km`;

                                L.marker([lat, lng], {icon: iconToUse})
                                 .addTo(map)
                                 .bindPopup(popupContent);
                         
                                latlngs.push([lat, lng]);
                            });

                            const polyline = L.polyline(latlngs, {color: '#1F6F50', weight: 4, opacity: 0.8}).addTo(map);
                            map.fitBounds(polyline.getBounds(), {padding: [50, 50]});
                    
                            // --- DRAG AND DROP LOGIC & RECALCULATION ---

                            // Referência Global
                            let currentPolyline = polyline;
                            let currentMarkers = []; 

                            // Função para limpar marcadores antigos da rota (não o Start/End fixos se precisar, mas aqui limpamos todos menos o start fixo)
                            // Na verdade, vamos limpar tudo e redesenhar, é mais seguro.
                            // Porém, StartIcon é fixo.
                    
                            // Vamos guardar referências aos markers criados no loop inicial? 
                            // Melhor: O PHP gerou o JS inicial. Vamos sobrescrever essa lógica com uma função reutilizável.
                    
                            // 1. Setup Table Sortable
                            const tbody = document.querySelector('table tbody');
                            new Sortable(tbody, {
                                animation: 150,
                                ghostClass: 'sortable-ghost',
                                onEnd: function (evt) {
                                   recalculateAndRedraw();
                                }
                            });

                            // 2. Função de Cálculo de Distância (Haversine)
                            function calculateDistance(lat1, lon1, lat2, lon2) {
                                const R = 6371; // km
                                const dLat = (lat2 - lat1) * Math.PI / 180;
                                const dLon = (lon2 - lon1) * Math.PI / 180;
                                const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                                          Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
                                          Math.sin(dLon/2) * Math.sin(dLon/2);
                                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                                return R * c;
                            }

                            // 3. Função Principal de Recálculo
                            function recalculateAndRedraw() {
                                const rows = Array.from(tbody.querySelectorAll('tr'));
                        
                                // Estado Inicial
                                let currentLat = parseFloat(startPoint.latitude);
                                let currentLon = parseFloat(startPoint.longitude);
                                let totalDist = 0;
                        
                                // Arrays para o Mapa
                                const newLatLngs = [[currentLat, currentLon]];
                                const mapPoints = []; // Para recriar markers

                                rows.forEach((row, index) => {
                                    const lat = parseFloat(row.dataset.lat);
                                    const lon = parseFloat(row.dataset.lon);
                             
                                    // Nova Distância deste trecho
                                    const dist = calculateDistance(currentLat, currentLon, lat, lon);
                                    totalDist += dist;
                            
                                    // Atualiza UI da Tabela
                                    const seqNum = index + 1;
                                    row.querySelector('.seq-num strong').textContent = seqNum;
                                    row.querySelector('.dist-cell').textContent = dist.toFixed(2) + ' km';
                                    row.querySelector('.time-cell').textContent = Math.round((dist / 40) * 60) + ' min';

                                    // Prepara dados para o mapa
                                    mapPoints.push({
                                        lat: lat,
                                        lon: lon,
                                        ordem: seqNum,
                                        cliente: row.dataset.client,
                                        status: row.dataset.status,
                                        distAcc: totalDist
                                    });

                                    // Atualiza Current para o próximo loop
                                    currentLat = lat;
                                    currentLon = lon;
                                    newLatLngs.push([lat, lon]);
                                });

                                // --- ATUALIZA O MAPA ---
                        
                                // Remove layers antigos (exceto o tile layer)
                                map.eachLayer((layer) => {
                                    if (layer instanceof L.Marker || layer instanceof L.Polyline) {
                                        // Opcional: Manter o marcador 'Start' original se ele não estiver na lista de removíveis
                                        // Mas vamos redesenhar o Start também para garantir consistência ou filtrar.
                                        // Para simplificar: removemos tudo e redesenhamos o start fixo + rota.
                                        map.removeLayer(layer);
                                    }
                                });

                                // Redesenha Start
                                 L.marker([startPoint.latitude, startPoint.longitude], {icon: startIcon})
                                  .addTo(map)
                                  .bindPopup(`<b>${startPoint.nome}</b><br>🚩 Ponto de Partida`);

                                // Redesenha Marcadores da Rota
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
                                    // popupContent += `Distância Acumulada: ${p.distAcc.toFixed(2)} km`; // Opcional

                                    L.marker([p.lat, p.lon], {icon: iconToUse})
                                     .addTo(map)
                                     .bindPopup(popupContent);
                                });

                                // Redesenha Linha
                                currentPolyline = L.polyline(newLatLngs, {color: '#1F6F50', weight: 4, opacity: 0.8}).addTo(map);
                        
                                // Atualiza Cabeçalho de Totais (Opcional, mas bom para UX)
                                // Precisaríamos pegar os elementos do DOM do cabeçalho info-panel...
                                // Mas por enquanto, a tabela e mapa já dão o feedback visual.
                            }
                        </script>

                        <?php
            } catch (Exception $e) {
                echo '<div class="alert alert-danger" style="margin-top:20px;">' . $e->getMessage() . '</div>';
            }
        }
        ?>
    </div>
</body>
</html>