<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

// Connexion à la base de données
$databaseUrl = $_ENV['DATABASE_URL'] ?? 'sqlite:///' . __DIR__ . '/../var/data.db';

// Parser l'URL de la base de données
$parsedUrl = parse_url($databaseUrl);

try {
    if (isset($parsedUrl['scheme']) && $parsedUrl['scheme'] === 'mysql') {
        // MySQL
        $host = $parsedUrl['host'] ?? '127.0.0.1';
        $port = $parsedUrl['port'] ?? 3306;
        $user = $parsedUrl['user'] ?? 'root';
        $pass = $parsedUrl['pass'] ?? '';
        $dbname = trim($parsedUrl['path'] ?? 'al3dprint', '/');
        
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Vérifier si les colonnes existent déjà
        $stmt = $pdo->query("SHOW COLUMNS FROM clients LIKE 'reset_token'");
        $resetTokenExists = $stmt->rowCount() > 0;
        
        $stmt = $pdo->query("SHOW COLUMNS FROM clients LIKE 'reset_token_expires_at'");
        $resetTokenExpiresAtExists = $stmt->rowCount() > 0;
        
        // Ajouter reset_token si nécessaire
        if (!$resetTokenExists) {
            $pdo->exec("ALTER TABLE clients ADD COLUMN reset_token VARCHAR(255) NULL");
            echo "✓ Colonne reset_token ajoutée\n";
        } else {
            echo "✓ Colonne reset_token existe déjà\n";
        }
        
        // Ajouter reset_token_expires_at si nécessaire
        if (!$resetTokenExpiresAtExists) {
            $pdo->exec("ALTER TABLE clients ADD COLUMN reset_token_expires_at DATETIME NULL");
            echo "✓ Colonne reset_token_expires_at ajoutée\n";
        } else {
            echo "✓ Colonne reset_token_expires_at existe déjà\n";
        }
        
        // Vérifier si l'index existe
        $stmt = $pdo->query("SHOW INDEX FROM clients WHERE Key_name = 'idx_reset_token'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("CREATE INDEX idx_reset_token ON clients(reset_token)");
            echo "✓ Index idx_reset_token créé\n";
        } else {
            echo "✓ Index idx_reset_token existe déjà\n";
        }
        
    } elseif (isset($parsedUrl['scheme']) && $parsedUrl['scheme'] === 'sqlite') {
        // SQLite
        $dbPath = substr($databaseUrl, 9);
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Vérifier si les colonnes existent déjà
        $stmt = $pdo->query("PRAGMA table_info(clients)");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = array_column($columns, 'name');
        
        // Ajouter reset_token si nécessaire
        if (!in_array('reset_token', $columnNames)) {
            $pdo->exec("ALTER TABLE clients ADD COLUMN reset_token VARCHAR(255) NULL");
            echo "✓ Colonne reset_token ajoutée\n";
        } else {
            echo "✓ Colonne reset_token existe déjà\n";
        }
        
        // Ajouter reset_token_expires_at si nécessaire
        if (!in_array('reset_token_expires_at', $columnNames)) {
            $pdo->exec("ALTER TABLE clients ADD COLUMN reset_token_expires_at DATETIME NULL");
            echo "✓ Colonne reset_token_expires_at ajoutée\n";
        } else {
            echo "✓ Colonne reset_token_expires_at existe déjà\n";
        }
        
        // Vérifier si l'index existe
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='index' AND name='idx_reset_token'");
        if ($stmt->fetch() === false) {
            $pdo->exec("CREATE INDEX idx_reset_token ON clients(reset_token)");
            echo "✓ Index idx_reset_token créé\n";
        } else {
            echo "✓ Index idx_reset_token existe déjà\n";
        }
    } else {
        die("Type de base de données non supporté: " . ($parsedUrl['scheme'] ?? 'inconnu') . "\n");
    }

    echo "\n✅ Migration terminée avec succès !\n";

} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

