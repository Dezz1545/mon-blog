<?php
require_once 'includes/config.php';

echo "<h1>Test de connexion à la base de données</h1>";

try {
    // Tester la connexion
    $stmt = $pdo->query("SELECT DATABASE() as db");
    $result = $stmt->fetch();
    echo "✅ Base connectée : <strong>" . $result['db'] . "</strong><br><br>";
    
    // Lister les tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📋 Tables trouvées (" . count($tables) . ") :<br>";
    echo "<ul>";
    foreach($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Tester la table users
    echo "<br>👥 Test table users :<br>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch();
    echo "Nombre d'utilisateurs : " . $userCount['count'];
    
    // Si 0 utilisateurs, créer l'admin
    if($userCount['count'] == 0) {
        echo "<br><br>⚠️ Aucun utilisateur trouvé. Création de l'admin...";
        $password = password_hash('Admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute(['admin', 'admin@blog.com', $password]);
        echo "<br>✅ Admin créé ! (admin@blog.com / Admin123)";
    }
    
} catch(PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?>