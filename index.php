<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Otimizador de Rotas de Entrega - Victor Transportes</title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
     integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
     crossorigin=""/>
     
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
                                <tr>
                                    <td><strong style="color: var(--primary-color); font-size: 1.1em;"><?php echo $point['ordem']; ?></strong></td>
                                    <td>
                                        <strong><?php echo $point['cliente_nome'] ?? 'CLIENTE ' . $point['cliente_id']; ?></strong>
                                    </td>
                                    <td><?php echo $point['distancia']; ?> km</td>
                                    <td><?php echo round(($point['distancia'] / 40) * 60); ?> min</td>
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
                    const startIcon = L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/markers/marker-icon-2x-red.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    });

                    const deliveryIcon = L.divIcon({
                        className: 'custom-div-icon',
                        html: "<div style='background-color:#1F6F50; color:white; border-radius:50%; width:25px; height:25px; display:flex; justify-content:center; align-items:center; font-weight:bold; border:2px solid white; box-shadow:0 2px 4px rgba(0,0,0,0.3);'>%ORD%</div>",
                        iconSize: [30, 42],
                        iconAnchor: [15, 42]
                    });

                    // Marcador Inicial
                    L.marker([startPoint.latitude, startPoint.longitude], {icon: startIcon})
                     .addTo(map)
                     .bindPopup(`<b>${startPoint.nome}</b><br>Ponto de Partida`);

                    const latlngs = [[startPoint.latitude, startPoint.longitude]];

                    // Marcadores da Rota
                    routePoints.forEach(point => {
                        const lat = parseFloat(point.latitude);
                        const lng = parseFloat(point.longitude);
                        
                        // Cria ícone numerado
                        const iconHtml = deliveryIcon.options.html.replace('%ORD%', point.ordem);
                        const numIcon = L.divIcon({
                            className: 'custom-marker',
                            html: iconHtml,
                            iconSize: [30, 30],
                            iconAnchor: [15, 15]
                        });

                        L.marker([lat, lng], {icon: numIcon})
                         .addTo(map)
                         .bindPopup(`
                            <b>${point.ordem}. ${point.cliente_nome || 'Cliente ' + point.cliente_id}</b><br>
                            ${point.situacao_descricao || ''}<br>
                            Distância: ${point.distancia} km
                         `);
                         
                        latlngs.push([lat, lng]);
                    });

                    const polyline = L.polyline(latlngs, {color: '#1F6F50', weight: 4, opacity: 0.8}).addTo(map);
                    map.fitBounds(polyline.getBounds(), {padding: [50, 50]});
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