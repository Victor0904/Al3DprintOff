<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

$databaseUrl = $_ENV['DATABASE_URL'] ?? 'sqlite:///' . __DIR__ . '/../var/data.db';
$parsedUrl = parse_url($databaseUrl);

try {
    if (isset($parsedUrl['scheme']) && $parsedUrl['scheme'] === 'mysql') {
        $host = $parsedUrl['host'] ?? '127.0.0.1';
        $port = $parsedUrl['port'] ?? 3306;
        $user = $parsedUrl['user'] ?? 'root';
        $pass = $parsedUrl['pass'] ?? '';
        $dbname = trim($parsedUrl['path'] ?? 'al3dprint', '/');

        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->query("SHOW COLUMNS FROM clients LIKE 'type_client'");
        if ($stmt->rowCount() > 0) {
            echo "✓ La colonne type_client existe déjà.\n";
            exit(0);
        }

        $pdo->exec("ALTER TABLE clients ADD COLUMN type_client VARCHAR(20) NOT NULL DEFAULT 'particulier'");
        echo "✓ Colonne type_client ajoutée avec succès.\n";
        exit(0);
    }

    // SQLite
    $path = $parsedUrl['path'] ?? __DIR__ . '/../var/data.db';
    $pdo = new PDO('sqlite:' . $path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("PRAGMA table_info(clients)");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
    if (in_array('type_client', $columns)) {
        echo "✓ La colonne type_client existe déjà.\n";
        exit(0);
    }

    $pdo->exec("ALTER TABLE clients ADD COLUMN type_client VARCHAR(20) NOT NULL DEFAULT 'particulier'");
    echo "✓ Colonne type_client ajoutée avec succès.\n";
} catch (Throwable $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
