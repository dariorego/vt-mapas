<?php
/**
 * Classe RouteOptimizer
 * 
 * Otimiza rotas de entrega calculando distâncias entre coordenadas geográficas
 * e determinando a melhor ordem de visita usando o algoritmo Nearest Neighbor.
 */

class RouteOptimizer
{
    private $db;
    private $startingPoint;
    private $deliveryPoints;
    private $optimizedRoute;
    private $totalDistance;
    private $estimatedTime;

    /**
     * Construtor
     * 
     * @param Database $db Instância da classe Database
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->optimizedRoute = [];
        $this->totalDistance = 0;
        $this->estimatedTime = 0;
    }

    /**
     * Calcula a distância entre dois pontos geográficos usando a fórmula de Haversine
     * 
     * @param float $lat1 Latitude do ponto 1
     * @param float $lon1 Longitude do ponto 1
     * @param float $lat2 Latitude do ponto 2
     * @param float $lon2 Longitude do ponto 2
     * @return float Distância em quilômetros
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        // Converte graus para radianos
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        // Diferenças
        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;

        // Fórmula de Haversine
        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($lat1Rad) * cos($lat2Rad) *
            sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = EARTH_RADIUS_KM * $c;

        return round($distance, 2);
    }

    /**
     * Busca o ponto de partida (cliente inicial)
     * 
     * @param int $clientId ID do cliente de partida
     * @return array|false Dados do cliente ou false
     */
    public function fetchStartingPoint($clientId = null)
    {
        if ($clientId === null) {
            $clientId = STARTING_CLIENT_ID;
        }

        $query = "SELECT c.id, c.nome, c.coordenadas, c.longitude, c.latitude 
                  FROM cliente c 
                  WHERE c.id = :client_id";

        $this->startingPoint = $this->db->queryOne($query, ['client_id' => $clientId]);

        if (!$this->startingPoint) {
            throw new Exception("Ponto de partida (cliente ID: {$clientId}) não encontrado!");
        }

        debug_log("Ponto de partida carregado:", $this->startingPoint);
        return $this->startingPoint;
    }

    /**
     * Busca as viagens disponíveis para seleção
     * 
     * @return array Lista de viagens
     */
    public function fetchAvailableTrips()
    {
        $query = "SELECT
                    r.viagem_id,
                    m.nome as motorista_nome,
                    r.data_remessa
                  FROM remessa r
                  LEFT JOIN motorista m ON m.id = r.motorista_id 
                  WHERE r.viagem_id IS NOT NULL AND r.viagem_id > 0
                  GROUP BY 
                    r.viagem_id,
                    r.motorista_id, 
                    m.nome,
                    r.data_remessa
                  ORDER BY r.data_remessa DESC
                  LIMIT 50";

        return $this->db->query($query);
    }
    public function fetchDeliveryPoints($viagemId)
    {
        $query = "SELECT 
                    r.id, 
                    r.cliente_id,
                    c.nome as cliente_nome,
                    r.remessa_situacao_id, 
                    rs.descricao as situacao_descricao, 
                    r.motorista_id, 
                    m.nome as motorista_nome,
                    m.latitude as motorista_lat,
                    m.longitude as motorista_lon,
                    r.data_remessa,  
                    r.coordenadas, 
                    r.latitude, 
                    r.longitude, 
                    r.ordem, 
                    r.ordem_auto, 
                    r.distancia, 
                    r.tempo,
                    r.descricao
                  FROM remessa r
                  LEFT JOIN cliente c ON c.id = r.cliente_id
                  LEFT JOIN remessa_situacao rs ON rs.id = r.remessa_situacao_id
                  LEFT JOIN motorista m ON m.id = r.motorista_id 
                  WHERE r.viagem_id = :viagem_id
                  AND r.latitude IS NOT NULL AND r.longitude IS NOT NULL";

        $this->deliveryPoints = $this->db->query($query, [
            'viagem_id' => $viagemId
        ]);

        if (empty($this->deliveryPoints)) {
            debug_log("Nenhum ponto encontrado para viagem_id {$viagemId}.");
            throw new Exception("Nenhum ponto de entrega encontrado para a viagem ID {$viagemId}!");
        }

        debug_log("Total de pontos de entrega carregados: " . count($this->deliveryPoints));
        return $this->deliveryPoints;
    }

    /**
     * Otimiza a rota usando o algoritmo Nearest Neighbor com Ponto Final Otimizado
     * 
     * O algoritmo define:
     * 1. Ponto Inicial (Fixo)
     * 2. Ponto Final (Entrega mais próxima da casa do motorista)
     * 3. Ordena os demais pontos usando Nearest Neighbor entre o início e o ponto final
     * 
     * @return array Rota otimizada com ordem de entrega
     */
    public function optimizeRoute()
    {
        if (empty($this->startingPoint) || empty($this->deliveryPoints)) {
            throw new Exception("Ponto de partida ou pontos de entrega não carregados!");
        }

        $unvisited = $this->deliveryPoints;
        $route = [];

        // 1. Identificar o ponto de destino final (Mais próximo do motorista)
        // Assume-se que todos os pontos da mesma viagem têm o mesmo motorista
        $firstPoint = reset($unvisited);
        $driverLat = $firstPoint['motorista_lat'] ?? null;
        $driverLon = $firstPoint['motorista_lon'] ?? null;

        $finalPointIndex = null;

        if ($driverLat && $driverLon) {
            $minDistToDriver = PHP_FLOAT_MAX;

            foreach ($unvisited as $index => $point) {
                $dist = $this->calculateDistance($point['latitude'], $point['longitude'], $driverLat, $driverLon);
                if ($dist < $minDistToDriver) {
                    $minDistToDriver = $dist;
                    $finalPointIndex = $index;
                }
            }
            debug_log("Ponto final definido (Mais próximo do motorista): ID {$unvisited[$finalPointIndex]['id']} a {$minDistToDriver} km do motorista.");
        }

        // Se encontrou um ponto final, remove ele da lista de 'visitáveis agora' para adicionar só no final
        $finalPoint = null;
        if ($finalPointIndex !== null) {
            $finalPoint = $unvisited[$finalPointIndex];
            array_splice($unvisited, $finalPointIndex, 1); // Remove da lista temporária
        }

        // 2. Executar Nearest Neighbor padrão nos pontos restantes
        $currentPoint = [
            'latitude' => $this->startingPoint['latitude'],
            'longitude' => $this->startingPoint['longitude']
        ];
        $this->totalDistance = 0;
        $ordem = 1;

        debug_log("Iniciando otimização...");

        while (!empty($unvisited)) {
            $nearestIndex = null;
            $nearestDistance = PHP_FLOAT_MAX;

            foreach ($unvisited as $index => $point) {
                $distance = $this->calculateDistance(
                    $currentPoint['latitude'],
                    $currentPoint['longitude'],
                    $point['latitude'],
                    $point['longitude']
                );

                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearestIndex = $index;
                }
            }

            $nearestPoint = $unvisited[$nearestIndex];
            $nearestPoint['ordem'] = $ordem;
            $nearestPoint['distancia'] = $nearestDistance;

            $route[] = $nearestPoint;
            $this->totalDistance += $nearestDistance;

            $currentPoint = [
                'latitude' => $nearestPoint['latitude'],
                'longitude' => $nearestPoint['longitude']
            ];

            array_splice($unvisited, $nearestIndex, 1);
            $ordem++;
        }

        // 3. Adicionar o ponto final (se existir)
        if ($finalPoint) {
            $distToFinal = $this->calculateDistance(
                $currentPoint['latitude'],
                $currentPoint['longitude'],
                $finalPoint['latitude'],
                $finalPoint['longitude']
            );

            $finalPoint['ordem'] = $ordem;
            $finalPoint['distancia'] = $distToFinal;

            $route[] = $finalPoint;
            $this->totalDistance += $distToFinal;
        }

        $this->optimizedRoute = $route;
        $this->estimatedTime = round(($this->totalDistance / AVG_SPEED_KMH) * 60);

        debug_log("Otimização concluída!");
        debug_log("Distância total: {$this->totalDistance} km");

        return $this->optimizedRoute;
    }

    /**
     * Atualiza o banco de dados com a ordem otimizada
     * 
     * @return int Número de registros atualizados
     */
    public function updateDatabase()
    {
        if (empty($this->optimizedRoute)) {
            throw new Exception("Nenhuma rota otimizada para atualizar!");
        }

        $this->db->beginTransaction();

        try {
            $updated = 0;

            foreach ($this->optimizedRoute as $point) {
                $query = "UPDATE remessa 
                          SET ordem_auto = :ordem, 
                              distancia = :distancia,
                              tempo = :tempo
                          WHERE id = :id";

                $params = [
                    'ordem' => $point['ordem'],
                    'distancia' => $point['distancia'],
                    'tempo' => $this->estimatedTime,
                    'id' => $point['id']
                ];

                $this->db->execute($query, $params);
                $updated++;
            }

            $this->db->commit();
            debug_log("Banco de dados atualizado com sucesso! {$updated} registros atualizados.");

            return $updated;
        } catch (Exception $e) {
            $this->db->rollback();
            debug_log("Erro ao atualizar banco de dados: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retorna a rota otimizada
     * 
     * @return array
     */
    public function getOptimizedRoute()
    {
        return $this->optimizedRoute;
    }

    /**
     * Retorna a distância total da rota
     * 
     * @return float
     */
    public function getTotalDistance()
    {
        return $this->totalDistance;
    }

    /**
     * Retorna o tempo estimado da rota
     * 
     * @return int Tempo em minutos
     */
    public function getEstimatedTime()
    {
        return $this->estimatedTime;
    }

    /**
     * Retorna o ponto de partida
     * 
     * @return array
     */
    public function getStartingPoint()
    {
        return $this->startingPoint;
    }

    /**
     * Valida a rota copiando os valores de ordem_auto para ordem
     * 
     * @param int $viagemId ID da viagem
     * @return int Número de registros atualizados
     */
    public function validateRoute($viagemId)
    {
        $query = "UPDATE remessa 
                  SET ordem = ordem_auto 
                  WHERE viagem_id = :viagem_id 
                  AND ordem_auto IS NOT NULL";

        $this->db->execute($query, ['viagem_id' => $viagemId]);

        // Busca quantos registros foram afetados
        $countQuery = "SELECT COUNT(*) as total 
                       FROM remessa 
                       WHERE viagem_id = :viagem_id 
                       AND ordem = ordem_auto";

        $result = $this->db->queryOne($countQuery, ['viagem_id' => $viagemId]);

        debug_log("Rota validada! {$result['total']} registros atualizados para viagem {$viagemId}.");

        return $result['total'] ?? 0;
    }

    /**
     * Exibe um resumo da rota otimizada
     */
    public function displayRouteSummary()
    {
        if (empty($this->optimizedRoute)) {
            echo "Nenhuma rota otimizada disponível.\n";
            return;
        }

        echo "\n" . str_repeat("=", 80) . "\n";
        echo "RESUMO DA ROTA OTIMIZADA\n";
        echo str_repeat("=", 80) . "\n\n";

        echo "Ponto de Partida: {$this->startingPoint['nome']}\n";
        echo "Coordenadas: {$this->startingPoint['latitude']}, {$this->startingPoint['longitude']}\n\n";

        echo "Ordem de Entrega:\n";
        echo str_repeat("-", 80) . "\n";

        foreach ($this->optimizedRoute as $point) {
            echo sprintf(
                "%2d. ID: %-5s | Cliente: %-5s | Distância: %6.2f km | Descrição: %s\n",
                $point['ordem'],
                $point['id'],
                $point['cliente_id'],
                $point['distancia'],
                substr($point['descricao'] ?? 'N/A', 0, 30)
            );
        }

        echo str_repeat("-", 80) . "\n";
        echo "\nDistância Total: {$this->totalDistance} km\n";
        echo "Tempo Estimado: {$this->estimatedTime} minutos (" . round($this->estimatedTime / 60, 1) . " horas)\n";
        echo str_repeat("=", 80) . "\n\n";
    }
}
