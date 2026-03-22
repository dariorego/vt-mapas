<?php
/**
 * Classe RouteOptimizer
 *
 * Otimiza rotas de entrega usando a Google Directions API.
 * Fallback automático para o algoritmo Nearest Neighbor (Haversine)
 * caso a chave não esteja configurada ou a API retorne erro.
 */

class RouteOptimizer
{
    private $db;
    private $startingPoint;
    private $deliveryPoints;
    private $optimizedRoute;
    private $totalDistance;
    private $estimatedTime;
    private $encodedPolyline;   // polyline encodada retornada pelo Google
    private $viagemId;          // armazenada para update no banco

    public function __construct(Database $db)
    {
        $this->db              = $db;
        $this->optimizedRoute  = [];
        $this->totalDistance   = 0;
        $this->estimatedTime   = 0;
        $this->encodedPolyline = null;
    }

    // ─── Haversine (mantido como fallback) ────────────────────────────────────

    public function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $lat1Rad  = deg2rad($lat1);
        $lon1Rad  = deg2rad($lon1);
        $lat2Rad  = deg2rad($lat2);
        $lon2Rad  = deg2rad($lon2);
        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;
        $a        = sin($deltaLat / 2) ** 2
                  + cos($lat1Rad) * cos($lat2Rad) * sin($deltaLon / 2) ** 2;
        return round(EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }

    // ─── Busca de dados ────────────────────────────────────────────────────────

    public function fetchStartingPoint($clientId = null)
    {
        if ($clientId === null) $clientId = STARTING_CLIENT_ID;

        $this->startingPoint = $this->db->queryOne(
            "SELECT c.id, c.nome, c.coordenadas, c.longitude, c.latitude
             FROM cliente c WHERE c.id = :client_id",
            ['client_id' => $clientId]
        );

        if (!$this->startingPoint)
            throw new Exception("Ponto de partida (cliente ID: {$clientId}) não encontrado!");

        debug_log("Ponto de partida carregado:", $this->startingPoint);
        return $this->startingPoint;
    }

    public function fetchAvailableTrips()
    {
        return $this->db->query(
            "SELECT r.viagem_id, m.nome as motorista_nome, r.data_remessa
             FROM remessa r
             LEFT JOIN motorista m ON m.id = r.motorista_id
             WHERE r.viagem_id IS NOT NULL AND r.viagem_id > 0
             GROUP BY r.viagem_id, r.motorista_id, m.nome, r.data_remessa
             ORDER BY r.data_remessa DESC LIMIT 50"
        );
    }

    public function fetchDeliveryPoints($viagemId)
    {
        $this->viagemId = $viagemId;

        $this->deliveryPoints = $this->db->query(
            "SELECT r.id, r.cliente_id,
                    c.nome as cliente_nome,
                    r.remessa_situacao_id,
                    rs.descricao as situacao_descricao,
                    r.motorista_id,
                    m.nome as motorista_nome,
                    m.latitude as motorista_lat,
                    m.longitude as motorista_lon,
                    r.data_remessa, r.coordenadas,
                    r.latitude, r.longitude,
                    r.ordem, r.ordem_auto, r.distancia, r.tempo, r.descricao
             FROM remessa r
             LEFT JOIN cliente c ON c.id = r.cliente_id
             LEFT JOIN remessa_situacao rs ON rs.id = r.remessa_situacao_id
             LEFT JOIN motorista m ON m.id = r.motorista_id
             WHERE r.viagem_id = :viagem_id
               AND r.latitude IS NOT NULL AND r.longitude IS NOT NULL",
            ['viagem_id' => $viagemId]
        );

        if (empty($this->deliveryPoints))
            throw new Exception("Nenhum ponto de entrega encontrado para a viagem ID {$viagemId}!");

        debug_log("Total de pontos carregados: " . count($this->deliveryPoints));
        return $this->deliveryPoints;
    }

    // ─── Otimização principal ─────────────────────────────────────────────────

    /**
     * Otimiza a rota.
     * Usa Google Directions API se GOOGLE_MAPS_API_KEY estiver definida.
     * Caso contrário usa Nearest Neighbor (Haversine).
     */
    public function optimizeRoute()
    {
        if (empty($this->startingPoint) || empty($this->deliveryPoints))
            throw new Exception("Ponto de partida ou pontos de entrega não carregados!");

        if (!empty(GOOGLE_MAPS_API_KEY)) {
            try {
                return $this->optimizeRouteGoogle();
            } catch (Exception $e) {
                debug_log("Erro na API Google, usando fallback local: " . $e->getMessage());
                // fallback sem polyline
            }
        }

        return $this->optimizeRouteLocal();
    }

    // ─── Google Directions API ────────────────────────────────────────────────

    private function optimizeRouteGoogle()
    {
        $points = $this->deliveryPoints;

        // 1. Identifica o ponto final (mais próximo do motorista)
        $firstPoint   = reset($points);
        $driverLat    = $firstPoint['motorista_lat'] ?? null;
        $driverLon    = $firstPoint['motorista_lon'] ?? null;
        $finalIdx     = null;
        $finalPoint   = null;

        if ($driverLat && $driverLon) {
            $minDist = PHP_FLOAT_MAX;
            foreach ($points as $i => $p) {
                $d = $this->calculateDistance($p['latitude'], $p['longitude'], $driverLat, $driverLon);
                if ($d < $minDist) { $minDist = $d; $finalIdx = $i; }
            }
        }

        if ($finalIdx !== null) {
            $finalPoint = $points[$finalIdx];
            array_splice($points, $finalIdx, 1);
        }

        // Destino: ponto final (ou último ponto se não há coordenada do motorista)
        $destination = $finalPoint ?? array_pop($points);

        // 2. Chama a API
        $route = $this->callDirectionsAPI(
            $this->startingPoint,
            $points,          // waypoints intermediários (serão reordenados pelo Google)
            $destination
        );

        // 3. Reconstrói a rota com a ordem retornada pelo Google
        $waypointOrder = $route['waypoint_order'] ?? range(0, count($points) - 1);
        $legs          = $route['legs'];

        $optimizedPoints = [];
        foreach ($waypointOrder as $i => $originalIdx) {
            $optimizedPoints[] = $points[$originalIdx];
        }
        $optimizedPoints[] = $destination;

        // 4. Monta $this->optimizedRoute com distância e tempo reais por trecho
        $this->optimizedRoute  = [];
        $this->totalDistance   = 0;
        $this->estimatedTime   = 0;

        foreach ($optimizedPoints as $idx => $point) {
            $leg = $legs[$idx] ?? null;

            $distKm  = $leg ? round($leg['distance']['value'] / 1000, 2) : 0;
            $timeMin = $leg ? round($leg['duration']['value'] / 60)       : 0;

            $point['ordem']    = $idx + 1;
            $point['distancia'] = $distKm;
            $point['tempo']     = $timeMin;

            $this->optimizedRoute[] = $point;
            $this->totalDistance   += $distKm;
            $this->estimatedTime   += $timeMin;
        }

        // 5. Guarda a polyline encodada do trajeto completo
        $this->encodedPolyline = $route['overview_polyline']['points'] ?? null;

        debug_log("Google API: {$this->totalDistance} km | {$this->estimatedTime} min");
        return $this->optimizedRoute;
    }

    /**
     * Chama a Google Directions API e retorna routes[0].
     */
    private function callDirectionsAPI(array $origin, array $waypoints, array $destination): array
    {
        $waypointStr = '';
        if (!empty($waypoints)) {
            $parts = array_map(fn($p) => "{$p['latitude']},{$p['longitude']}", $waypoints);
            $waypointStr = 'optimize:true|' . implode('|', $parts);
        }

        $params = [
            'origin'      => "{$origin['latitude']},{$origin['longitude']}",
            'destination' => "{$destination['latitude']},{$destination['longitude']}",
            'language'    => 'pt-BR',
            'key'         => GOOGLE_MAPS_API_KEY,
        ];

        if ($waypointStr) $params['waypoints'] = $waypointStr;

        $url = 'https://maps.googleapis.com/maps/api/directions/json?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $curlErr  = curl_error($ch);

        if ($curlErr)
            throw new Exception("cURL erro ao chamar Google Directions: {$curlErr}");

        $json = json_decode($response, true);
        if (!$json)
            throw new Exception("Resposta inválida da Google Directions API");

        if ($json['status'] !== 'OK') {
            $msg = $json['error_message'] ?? $json['status'];
            throw new Exception("Google Directions API: {$msg}");
        }

        return $json['routes'][0];
    }

    // ─── Fallback: Nearest Neighbor (Haversine) ───────────────────────────────

    private function optimizeRouteLocal()
    {
        $unvisited  = $this->deliveryPoints;
        $route      = [];
        $firstPoint = reset($unvisited);
        $driverLat  = $firstPoint['motorista_lat'] ?? null;
        $driverLon  = $firstPoint['motorista_lon'] ?? null;
        $finalIdx   = null;

        if ($driverLat && $driverLon) {
            $minDist = PHP_FLOAT_MAX;
            foreach ($unvisited as $i => $p) {
                $d = $this->calculateDistance($p['latitude'], $p['longitude'], $driverLat, $driverLon);
                if ($d < $minDist) { $minDist = $d; $finalIdx = $i; }
            }
        }

        $finalPoint = null;
        if ($finalIdx !== null) {
            $finalPoint = $unvisited[$finalIdx];
            array_splice($unvisited, $finalIdx, 1);
        }

        $currentPoint       = ['latitude' => $this->startingPoint['latitude'], 'longitude' => $this->startingPoint['longitude']];
        $this->totalDistance = 0;
        $ordem               = 1;

        while (!empty($unvisited)) {
            $nearestIdx  = null;
            $nearestDist = PHP_FLOAT_MAX;

            foreach ($unvisited as $i => $p) {
                $d = $this->calculateDistance($currentPoint['latitude'], $currentPoint['longitude'], $p['latitude'], $p['longitude']);
                if ($d < $nearestDist) { $nearestDist = $d; $nearestIdx = $i; }
            }

            $nearest             = $unvisited[$nearestIdx];
            $nearest['ordem']    = $ordem;
            $nearest['distancia'] = $nearestDist;
            $nearest['tempo']     = round(($nearestDist / AVG_SPEED_KMH) * 60);

            $route[]             = $nearest;
            $this->totalDistance += $nearestDist;
            $currentPoint        = ['latitude' => $nearest['latitude'], 'longitude' => $nearest['longitude']];
            array_splice($unvisited, $nearestIdx, 1);
            $ordem++;
        }

        if ($finalPoint) {
            $d                   = $this->calculateDistance($currentPoint['latitude'], $currentPoint['longitude'], $finalPoint['latitude'], $finalPoint['longitude']);
            $finalPoint['ordem']    = $ordem;
            $finalPoint['distancia'] = $d;
            $finalPoint['tempo']     = round(($d / AVG_SPEED_KMH) * 60);
            $route[]             = $finalPoint;
            $this->totalDistance += $d;
        }

        $this->optimizedRoute  = $route;
        $this->estimatedTime   = round(($this->totalDistance / AVG_SPEED_KMH) * 60);
        $this->encodedPolyline = null;

        debug_log("Fallback local: {$this->totalDistance} km | {$this->estimatedTime} min");
        return $this->optimizedRoute;
    }

    // ─── Banco de dados ────────────────────────────────────────────────────────

    private function ensureViagemColumns()
    {
        $columns = [
            'polyline_encoded'   => 'TEXT NULL',
            'distancia_total_km' => 'DECIMAL(8,2) NULL',
            'tempo_total_min'    => 'INT NULL',
        ];

        foreach ($columns as $col => $def) {
            $exists = $this->db->queryOne(
                "SELECT COUNT(*) as cnt
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME   = 'viagem'
                   AND COLUMN_NAME  = :col",
                ['col' => $col]
            );
            if (empty($exists['cnt'])) {
                $this->db->execute("ALTER TABLE viagem ADD COLUMN {$col} {$def}");
            }
        }
    }

    public function updateDatabase()
    {
        if (empty($this->optimizedRoute))
            throw new Exception("Nenhuma rota otimizada para atualizar!");

        // Garante colunas extras na tabela viagem (fora da transação para evitar DDL lock)
        $this->ensureViagemColumns();

        $this->db->beginTransaction();
        try {
            $updated = 0;
            foreach ($this->optimizedRoute as $point) {
                $this->db->execute(
                    "UPDATE remessa SET ordem_auto = :ordem, distancia = :distancia, tempo = :tempo WHERE id = :id",
                    ['ordem' => $point['ordem'], 'distancia' => $point['distancia'], 'tempo' => $point['tempo'], 'id' => $point['id']]
                );
                $updated++;
            }

            // Atualiza viagem com polyline e totais
            if ($this->viagemId) {
                $this->db->execute(
                    "UPDATE viagem
                     SET polyline_encoded    = :polyline,
                         distancia_total_km  = :distancia,
                         tempo_total_min     = :tempo
                     WHERE id = :id",
                    [
                        'polyline'  => $this->encodedPolyline,
                        'distancia' => round($this->totalDistance, 2),
                        'tempo'     => $this->estimatedTime,
                        'id'        => $this->viagemId,
                    ]
                );
            }

            $this->db->commit();
            debug_log("Banco atualizado: {$updated} remessas + viagem #{$this->viagemId}");
            return $updated;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    // ─── Validação ─────────────────────────────────────────────────────────────

    public function validateRoute($viagemId)
    {
        $this->db->execute(
            "UPDATE remessa SET ordem = ordem_auto WHERE viagem_id = :viagem_id AND ordem_auto IS NOT NULL",
            ['viagem_id' => $viagemId]
        );
        $result = $this->db->queryOne(
            "SELECT COUNT(*) as total FROM remessa WHERE viagem_id = :viagem_id AND ordem = ordem_auto",
            ['viagem_id' => $viagemId]
        );
        debug_log("Rota validada! {$result['total']} registros — viagem {$viagemId}.");
        return $result['total'] ?? 0;
    }

    // ─── Getters ──────────────────────────────────────────────────────────────

    public function getOptimizedRoute()   { return $this->optimizedRoute; }
    public function getTotalDistance()    { return $this->totalDistance; }
    public function getEstimatedTime()    { return $this->estimatedTime; }
    public function getStartingPoint()    { return $this->startingPoint; }
    public function getEncodedPolyline()  { return $this->encodedPolyline; }

    // ─── Exibição CLI ─────────────────────────────────────────────────────────

    public function displayRouteSummary()
    {
        if (empty($this->optimizedRoute)) { echo "Nenhuma rota otimizada disponível.\n"; return; }

        $via = !empty(GOOGLE_MAPS_API_KEY) && $this->encodedPolyline ? 'Google Directions API' : 'Nearest Neighbor (local)';
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "RESUMO DA ROTA — {$via}\n";
        echo str_repeat("=", 80) . "\n\n";
        echo "Partida: {$this->startingPoint['nome']} ({$this->startingPoint['latitude']}, {$this->startingPoint['longitude']})\n\n";

        foreach ($this->optimizedRoute as $p) {
            printf("%2d. ID: %-5s | Cliente: %-5s | %.2f km | %d min | %s\n",
                $p['ordem'], $p['id'], $p['cliente_id'], $p['distancia'], $p['tempo'],
                substr($p['descricao'] ?? 'N/A', 0, 30));
        }

        echo str_repeat("-", 80) . "\n";
        echo "Distância Total: {$this->totalDistance} km\n";
        echo "Tempo Total: {$this->estimatedTime} min (" . round($this->estimatedTime / 60, 1) . " h)\n";
        echo str_repeat("=", 80) . "\n\n";
    }
}
