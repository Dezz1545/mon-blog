<?php
// ============================================
// AUTOLOAD DES CLASSES
// ============================================

// Chemin vers le fichier d'autoload
$autoloadPath = __DIR__ . '/../classes/autoload.php';

if(file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    // Fallback : charger les classes manuellement
    $classesPath = __DIR__ . '/../classes/';
    $requiredClasses = [
        'Database.php',
        'User.php', 
        'Article.php',
        'Category.php',
        'Comment.php',
        'Newsletter.php'
    ];
    
    foreach($requiredClasses as $classFile) {
        $fullPath = $classesPath . $classFile;
        if(file_exists($fullPath)) {
            require_once $fullPath;
        }
    }
}
session_start();

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'blog_db');
define('DB_USER', 'root');
define('DB_PASS', '');  // Laissez vide si pas de mot de passe

// Configuration du site
define('SITE_NAME', 'Mon Blog PHP');
define('SITE_URL', 'http://localhost/mon-blog');
define('SITE_PATH', __DIR__ . '/../');

// Connexion PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Fonction pour nettoyer les données
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Vérifier si l'utilisateur est admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirection
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Pour le développement - afficher les erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>

