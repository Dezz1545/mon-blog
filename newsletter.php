<?php

// NOUVEAU (2 lignes) :
require_once 'includes/config.php'; // ← Chargera automatiquement toutes les classes
$newsletterClass = new Newsletter();
require_once 'includes/config.php';
require_once 'classes/Newsletter.php';

$newsletterClass = new Newsletter();
$message = '';
$message_type = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['subscribe'])) {
        $email = sanitize($_POST['email']);
        
        $result = $newsletterClass->subscribe($email);
        
        if($result['success']) {
            $message = $result['message'];
            $message_type = 'success';
        } else {
            $message = $result['error'];
            $message_type = 'error';
        }
    }
}

$page_title = "Newsletter";
include 'includes/header.php';
?>

<section style="padding: 80px 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
    <div class="container">
        <h1 style="text-align: center; margin-bottom: 10px; font-size: 2.5rem;">Newsletter</h1>
        <p style="text-align: center; font-size: 1.1rem; max-width: 600px; margin: 0 auto 40px;">
            Restez informé de nos dernières publications et actualités
        </p>
    </div>
</section>

<section style="padding: 60px 0; background: #f8f9fa;">
    <div class="container">
        <div style="max-width: 600px; margin: 0 auto;">
            <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
                <div style="text-align: center; margin-bottom: 30px;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                        <i class="fas fa-newspaper" style="font-size: 2rem;"></i>
                    </div>
                    <h2 style="margin-bottom: 10px; color: #2c3e50;">Abonnez-vous à notre newsletter</h2>
                    <p style="color: #7f8c8d;">
                        Recevez nos derniers articles directement dans votre boîte mail.
                        Pas de spam, désabonnez-vous à tout moment.
                    </p>
                </div>
                
                <?php if($message): ?>
                <div style="padding: 15px; background: <?php echo $message_type == 'error' ? '#f8d7da' : '#d4edda'; ?>; 
                            color: <?php echo $message_type == 'error' ? '#721c24' : '#155724'; ?>; 
                            border-radius: 6px; margin-bottom: 30px; text-align: center;">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                        <input type="email" name="email" required
                               placeholder="Votre adresse email"
                               style="flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                        <button type="submit" name="subscribe"
                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; border: none; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer;">
                            <i class="fas fa-paper-plane"></i> S'abonner
                        </button>
                    </div>
                </form>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                    <h3 style="margin-bottom: 15px; color: #2c3e50; font-size: 1.1rem;">
                        <i class="fas fa-info-circle"></i> Ce que vous recevrez :
                    </h3>
                    <ul style="color: #7f8c8d; padding-left: 20px;">
                        <li>Nos derniers articles et tutoriels</li>
                        <li>Des astuces et conseils exclusifs</li>
                        <li>Les annonces importantes du blog</li>
                        <li>Maximum 1 email par semaine</li>
                    </ul>
                </div>
            </div>
            
            <div style="margin-top: 30px; text-align: center; color: #7f8c8d;">
                <p>Déjà abonné et souhaitez vous désinscrire ? <a href="mailto:contact@mon-blog.com" style="color: #667eea;">Contactez-nous</a></p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>