<?php
/**
 * Script de migration pour modifier la colonne statut de ENUM à VARCHAR
 * 
 * Usage: php backend/scripts/migrate_statut.php
 * 
 * Assurez-vous d'avoir configuré vos paramètres de connexion dans .env
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

// Récupérer les paramètres de connexion
$dbHost = $_ENV['DATABASE_URL'] ?? 'mysql://root:@127.0.0.1:3306/Al3DPrint';
preg_match('/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/', $dbHost, $matches);

if (empty($matches)) {
    die("❌ Erreur: Impossible de parser DATABASE_URL. Vérifiez votre fichier .env\n");
}

$dbUser = $matches[1] ?: 'root';
$dbPass = $matches[2] ?: '';
$dbHost = $matches[3] ?: '127.0.0.1';
$dbPort = $matches[4] ?: '3306';
$dbName = $matches[5] ?: 'Al3DPrint';

echo "🔄 Migration de la colonne statut de la table commandes...\n";
echo "📊 Base de données: {$dbName}\n\n";

try {
    // Connexion à la base de données
    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    echo "✅ Connexion à la base de données réussie\n\n";

    // Étape 1: Mettre à jour les valeurs existantes
    echo "📝 Mise à jour des valeurs existantes...\n";
    
    $updates = [
        "UPDATE commandes SET statut = 'En attente' WHERE statut IN ('en attente', 'En attente')" => 'En attente',
        "UPDATE commandes SET statut = 'En cours' WHERE statut IN ('en fabrication', 'En fabrication')" => 'En cours',
        "UPDATE commandes SET statut = 'Expédié' WHERE statut IN ('expédiée', 'Expédiée', 'expédié', 'Expédié')" => 'Expédié',
        "UPDATE commandes SET statut = 'Terminé' WHERE statut IN ('livrée', 'Livrée', 'terminé', 'Terminé')" => 'Terminé',
    ];

    foreach ($updates as $sql => $newValue) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $count = $stmt->rowCount();
        if ($count > 0) {
            echo "  ✓ {$count} commande(s) mise(s) à jour vers '{$newValue}'\n";
        }
    }

    echo "\n";

    // Étape 2: Modifier la structure de la colonne
    echo "🔧 Modification de la structure de la colonne statut (ENUM → VARCHAR)...\n";
    
    $pdo->exec("ALTER TABLE commandes MODIFY COLUMN statut VARCHAR(20) DEFAULT 'En attente'");
    
    echo "✅ Colonne statut modifiée avec succès\n\n";

    // Vérification
    echo "🔍 Vérification de la structure...\n";
    $stmt = $pdo->query("DESCRIBE commandes");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'statut') {
            echo "  ✓ Type: {$column['Type']}\n";
            echo "  ✓ Default: {$column['Default']}\n";
            break;
        }
    }

    echo "\n✅ Migration terminée avec succès !\n";
    echo "🎉 Vous pouvez maintenant utiliser les statuts: 'En attente', 'En cours', 'Expédié', 'Terminé'\n";

} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

