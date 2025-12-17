<?php
/**
 * Fichier d'autoload pour toutes les classes
 * Inclure ce fichier une fois pour charger toutes les classes
 */

// Vérifier que le dossier classes existe
if(!is_dir(__DIR__)) {
    die('❌ Erreur : Le dossier classes n\'existe pas');
}

// Liste des classes à charger
$classes = [
    'Database'    => 'Database.php',
    'User'        => 'User.php',
    'Article'     => 'Article.php',
    'Category'    => 'Category.php',
    'Comment'     => 'Comment.php',
    'Newsletter'  => 'Newsletter.php'
];

// Charger chaque classe
foreach($classes as $className => $fileName) {
    $filePath = __DIR__ . '/' . $fileName;
    
    if(file_exists($filePath)) {
        require_once $filePath;
    } else {
        // Log l'erreur mais continue (pour ne pas bloquer le site)
        error_log("⚠️ Classe $className non trouvée : $filePath");
    }
}

// Vérifier que Database est bien chargé
if(!class_exists('Database')) {
    die('❌ Erreur critique : La classe Database n\'a pas pu être chargée');
}

// Message de debug (à désactiver en production)
// echo "<!-- ✅ Toutes les classes sont chargées -->";
?>