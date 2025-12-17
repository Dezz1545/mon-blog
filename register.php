<?php
require_once 'includes/config.php';

$errors = [];
$success = false;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if(empty($username) || strlen($username) < 3) {
        $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères";
    }
    
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide";
    }
    
    // Contraintes mot de passe
    if(strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
    }
    if(!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une majuscule";
    }
    if(!preg_match('/[0-9]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un chiffre";
    }
    if($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }
    
    // Vérifier si email existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if($stmt->fetch()) {
        $errors[] = "Cet email est déjà utilisé";
    }
    
    // Vérifier si username existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if($stmt->fetch()) {
        $errors[] = "Ce nom d'utilisateur est déjà pris";
    }
    
    // Si pas d'erreurs, créer l'utilisateur
    if(empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password, role) 
                VALUES (?, ?, ?, 'user')
            ");
            $stmt->execute([$username, $email, $hashed_password]);
            
            $success = true;
            $_SESSION['message'] = "Compte créé avec succès ! Vous pouvez vous connecter.";
            $_SESSION['message_type'] = "success";
            
            // Rediriger vers login après 3 secondes
            header("refresh:3;url=login.php");
            
        } catch(PDOException $e) {
            $errors[] = "Erreur lors de la création du compte : " . $e->getMessage();
        }
    }
}

$page_title = "Inscription";
include 'includes/header.php';
?>

<div style="max-width: 500px; margin: 60px auto; padding: 40px; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
    <h2 style="text-align: center; margin-bottom: 30px; color: #333;">Créer un compte</h2>
    
    <?php if($success): ?>
    <div style="background: #d4edda; color: #155724; padding: 20px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
        <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
        <h3 style="margin-bottom: 10px;">✅ Compte créé avec succès !</h3>
        <p>Redirection vers la page de connexion dans 3 secondes...</p>
        <p><a href="login.php" style="color: #155724; font-weight: bold;">Cliquez ici si la redirection ne fonctionne pas</a></p>
    </div>
    <?php endif; ?>
    
    <?php if(!empty($errors)): ?>
    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        <h4 style="margin-bottom: 10px;"><i class="fas fa-exclamation-triangle"></i> Erreurs :</h4>
        <ul style="margin-left: 20px;">
            <?php foreach($errors as $error): ?>
            <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if(!$success): ?>
    <form method="POST">
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: bold;">
                <i class="fas fa-user"></i> Nom d'utilisateur *
            </label>
            <input type="text" name="username" required minlength="3"
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
            <small style="color: #666;">Minimum 3 caractères</small>
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: bold;">
                <i class="fas fa-envelope"></i> Email *
            </label>
            <input type="email" name="email" required
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: bold;">
                <i class="fas fa-lock"></i> Mot de passe *
            </label>
            <input type="password" name="password" required minlength="8"
                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
            <small style="color: #666;">
                <i class="fas fa-info-circle"></i> 8 caractères minimum, 1 majuscule, 1 chiffre
            </small>
        </div>
        
        <div style="margin-bottom: 30px;">
            <label style="display: block; margin-bottom: 8px; font-weight: bold;">
                <i class="fas fa-lock"></i> Confirmer le mot de passe *
            </label>
            <input type="password" name="confirm_password" required minlength="8"
                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 1rem;">
        </div>
        
        <button type="submit" 
                style="width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border: none; border-radius: 4px; font-size: 1rem; font-weight: bold; cursor: pointer;">
            <i class="fas fa-user-plus"></i> Créer mon compte
        </button>
    </form>
    
    <div style="margin-top: 30px; text-align: center; padding-top: 20px; border-top: 1px solid #eee;">
        <p>Déjà un compte ?</p>
        <a href="login.php" 
           style="display: inline-block; background: #f0f0f0; color: #333; padding: 10px 20px; border-radius: 4px; text-decoration: none; margin-top: 10px;">
            <i class="fas fa-sign-in-alt"></i> Se connecter
        </a>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>