<?php
require_once 'includes/config.php';

http_response_code(404);
$page_title = "Page non trouvée";
include 'includes/header.php';
?>

<div style="min-height: 70vh; display: flex; align-items: center; justify-content: center;">
    <div style="text-align: center; padding: 40px;">
        <div style="font-size: 8rem; color: #e0e0e0; margin-bottom: 20px;">
            404
        </div>
        
        <h1 style="margin-bottom: 20px; color: #2c3e50;">Page non trouvée</h1>
        
        <p style="color: #7f8c8d; margin-bottom: 30px; max-width: 500px;">
            La page que vous recherchez n'existe pas ou a été déplacée.
        </p>
        
        <div style="display: flex; gap: 15px; justify-content: center;">
            <a href="index.php" style="background: #3498db; color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none;">
                <i class="fas fa-home"></i> Retour à l'accueil
            </a>
            <a href="blog.php" style="background: #f8f9fa; color: #2c3e50; padding: 12px 30px; border-radius: 6px; text-decoration: none; border: 1px solid #ddd;">
                <i class="fas fa-newspaper"></i> Voir le blog
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>