<?php
require_once 'includes/config.php';

$message = '';
$error = '';
$show_reset_form = false;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['request_reset'])) {
        // Demande de réinitialisation
        $email = sanitize($_POST['email']);
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if($user) {
            // Générer un token sécurisé
            $token = bin2hex(random_bytes(50));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, token_expiry = ? WHERE id = ?");
            $stmt->execute([$token, $expiry, $user['id']]);
            
            // En production, vous enverriez un email ici
            $reset_link = SITE_URL . "/forgot-password.php?token=$token";
            
            $message = "Un lien de réinitialisation a été généré. En production, il serait envoyé à votre email.";
            $message .= "<br><br><strong>Lien de test :</strong> <a href='$reset_link'>$reset_link</a>";
            
        } else {
            $error = "Aucun compte actif trouvé avec cet email.";
        }
        
    } elseif(isset($_POST['reset_password'])) {
        // Réinitialisation du mot de passe
        $token = sanitize($_POST['token']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Contraintes mot de passe
        if(strlen($password) < 8) {
            $error = "Le mot de passe doit contenir au moins 8 caractères";
        } elseif(!preg_match('/[A-Z]/', $password)) {
            $error = "Le mot de passe doit contenir au moins une majuscule";
        } elseif(!preg_match('/[0-9]/', $password)) {
            $error = "Le mot de passe doit contenir au moins un chiffre";
        } elseif($password !== $confirm_password) {
            $error = "Les mots de passe ne correspondent pas";
        } else {
            // Vérifier le token
            $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND token_expiry > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if($user) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL WHERE id = ?");
                $stmt->execute([$hashed_password, $user['id']]);
                
                $_SESSION['message'] = "Mot de passe réinitialisé avec succès !";
                $_SESSION['message_type'] = "success";
                header('Location: login.php');
                exit();
            } else {
                $error = "Token invalide ou expiré.";
            }
        }
    }
}

// Vérifier si un token est présent dans l'URL
if(isset($_GET['token'])) {
    $token = sanitize($_GET['token']);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND token_expiry > NOW()");
    $stmt->execute([$token]);
    
    if($stmt->fetch()) {
        $show_reset_form = true;
    } else {
        $error = "Token invalide ou expiré.";
    }
}

$page_title = "Mot de passe oublié";
include 'includes/header.php';
?>

<section style="padding: 80px 0; background: #f8f9fa; min-height: 70vh;">
    <div class="container">
        <div style="max-width: 500px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 40px;">
                <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                    <i class="fas fa-key" style="font-size: 2rem;"></i>
                </div>
                <h1 style="margin-bottom: 10px;">Mot de passe oublié</h1>
                <p style="color: #666;">Réinitialisez votre mot de passe en quelques étapes</p>
            </div>
            
            <?php if($message): ?>
            <div style="background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin-bottom: 30px; text-align: center;">
                <i class="fas fa-check-circle" style="font-size: 1.5rem; margin-bottom: 10px; display: block;"></i>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>
            
            <?php if($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 30px;">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if(!$show_reset_form): ?>
            <!-- Formulaire de demande de réinitialisation -->
            <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
                <h2 style="margin-bottom: 20px; font-size: 1.3rem;">Étape 1 : Entrez votre email</h2>
                <p style="color: #666; margin-bottom: 30px;">
                    Nous vous enverrons un lien pour réinitialiser votre mot de passe.
                </p>
                
                <form method="POST">
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #555;">
                            <i class="fas fa-envelope"></i> Adresse email *
                        </label>
                        <input type="email" name="email" required
                               style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                    </div>
                    
                    <button type="submit" name="request_reset"
                            style="width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border: none; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer;">
                        <i class="fas fa-paper-plane"></i> Envoyer le lien de réinitialisation
                    </button>
                </form>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center;">
                    <a href="login.php" style="color: #667eea; text-decoration: none;">
                        <i class="fas fa-arrow-left"></i> Retour à la connexion
                    </a>
                </div>
            </div>
            <?php else: ?>
            <!-- Formulaire de réinitialisation -->
            <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
                <h2 style="margin-bottom: 20px; font-size: 1.3rem;">Étape 2 : Nouveau mot de passe</h2>
                <p style="color: #666; margin-bottom: 30px;">
                    Choisissez un nouveau mot de passe sécurisé.
                </p>
                
                <form method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #555;">
                            <i class="fas fa-lock"></i> Nouveau mot de passe *
                        </label>
                        <input type="password" name="password" required minlength="8"
                               style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                        <small style="color: #666; display: block; margin-top: 5px;">
                            <i class="fas fa-info-circle"></i> 8 caractères minimum, 1 majuscule, 1 chiffre
                        </small>
                    </div>
                    
                    <div style="margin-bottom: 30px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #555;">
                            <i class="fas fa-lock"></i> Confirmer le mot de passe *
                        </label>
                        <input type="password" name="confirm_password" required minlength="8"
                               style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                    </div>
                    
                    <button type="submit" name="reset_password"
                            style="width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border: none; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer;">
                        <i class="fas fa-check"></i> Réinitialiser le mot de passe
                    </button>
                </form>
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 30px; text-align: center; color: #666; font-size: 0.9rem;">
                <p>Besoin d'aide ? <a href="contact.php" style="color: #667eea;">Contactez-nous</a></p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>