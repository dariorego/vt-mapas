<?php
/**
 * Runner de Migrations
 *
 * Executa arquivos .sql em ordem sequencial.
 * Registra cada migration aplicada na tabela _migrations para não re-executar.
 *
 * Uso via CLI:
 *   php migrations/migrate.php
 *
 * Uso via Docker:
 *   docker exec vt-mapas-app php /var/www/html/migrations/migrate.php
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Database.php';

$db  = new Database();
$pdo = (new ReflectionClass($db))->getMethod('connect')->invoke($db);

// ── Garante tabela de controle ────────────────────────────────────────────────
$pdo->exec("
    CREATE TABLE IF NOT EXISTS _migrations (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        filename   VARCHAR(255) NOT NULL UNIQUE,
        applied_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── Lista migrations já aplicadas ─────────────────────────────────────────────
$applied = [];
foreach ($pdo->query("SELECT filename FROM _migrations ORDER BY filename") as $row) {
    $applied[] = $row['filename'];
}

// ── Lê arquivos .sql da pasta migrations/ em ordem ────────────────────────────
$dir   = __DIR__;
$files = glob($dir . '/*.sql');
sort($files);

if (empty($files)) {
    echo "Nenhum arquivo .sql encontrado em migrations/\n";
    exit(0);
}

$ran = 0;
foreach ($files as $file) {
    $name = basename($file);

    if (in_array($name, $applied)) {
        echo "[SKIP] {$name} (já aplicada)\n";
        continue;
    }

    $sql = trim(file_get_contents($file));
    if (empty($sql)) {
        echo "[SKIP] {$name} (arquivo vazio)\n";
        continue;
    }

    echo "[RUN]  {$name} ... ";

    try {
        $pdo->exec($sql);
        $stmt = $pdo->prepare("INSERT INTO _migrations (filename) VALUES (?)");
        $stmt->execute([$name]);
        echo "OK\n";
        $ran++;
    } catch (PDOException $e) {
        // 1060 = Duplicate column name — estrutura já existe, marca como aplicada
        if ($e->getCode() === '42S21' || str_contains($e->getMessage(), 'Duplicate column')) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO _migrations (filename) VALUES (?)");
            $stmt->execute([$name]);
            echo "JÁ EXISTE (marcada como aplicada)\n";
            $ran++;
        } else {
            echo "ERRO\n";
            echo "       " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}

if ($ran === 0) {
    echo "Nenhuma migration nova para aplicar.\n";
} else {
    echo "\n{$ran} migration(s) aplicada(s) com sucesso.\n";
}
