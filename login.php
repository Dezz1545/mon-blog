<?php
require_once 'includes/config.php';

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    // Vérifier l'utilisateur
    $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        $_SESSION['message'] = "Connexion réussie !";
        $_SESSION['message_type'] = "success";
        
        // Rediriger vers la page précédente ou l'accueil
        $redirect = isset($_SESSION['redirect_to']) ? $_SESSION['redirect_to'] : 'index.php';
        unset($_SESSION['redirect_to']);
        redirect($redirect);
    } else {
        $error = "Email ou mot de passe incorrect";
    }
}

$page_title = "Connexion";
include 'includes/header.php';
?>

<div style="max-width: 400px; margin: 60px auto; padding: 40px; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h2 style="text-align: center; margin-bottom: 30px; color: #333;">Connexion</h2>
    
    <?php if($error): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <form method="POST">
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: bold;">Email</label>
            <input type="email" name="email" required 
                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: bold;">Mot de passe</label>
            <input type="password" name="password" required 
                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
        </div>
        
        <button type="submit" 
                style="width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border: none; border-radius: 4px; font-size: 1rem; font-weight: bold; cursor: pointer;">
            <i class="fas fa-sign-in-alt"></i> Se connecter
        </button>
    </form>
    
    <div style="margin-top: 30px; text-align: center;">
        <p style="margin-bottom: 10px;">Pas encore de compte ?</p>
        <a href="register.php" 
           style="display: inline-block; background: #f0f0f0; color: #333; padding: 10px 20px; border-radius: 4px; text-decoration: none;">
            Créer un compte
        </a>
        <a href="forgot-password.php" 
           style="display: block; margin-top: 15px; color: #667eea; text-decoration: none;">
            <i class="fas fa-key"></i> Mot de passe oublié ?
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>