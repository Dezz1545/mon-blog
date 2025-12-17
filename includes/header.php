<?php
if(!isset($page_title)) {
    $page_title = SITE_NAME;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Styles temporaires */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        
        /* Header */
        header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem 0; }
        .header-content { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.8rem; font-weight: bold; }
        .logo a { color: white; text-decoration: none; }
        
        /* Navigation */
        .nav-links { display: flex; gap: 25px; align-items: center; }
        .nav-links a { color: white; text-decoration: none; padding: 8px 15px; border-radius: 4px; transition: background 0.3s; }
        .nav-links a:hover { background: rgba(255,255,255,0.1); }
        .nav-links .btn-login { background: #4CAF50; }
        
        /* Messages flash */
        .alert { padding: 15px; margin: 20px 0; border-radius: 4px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <header>
        <div class="container header-content">
            <div class="logo">
                <a href="<?php echo SITE_URL; ?>/index.php"><?php echo SITE_NAME; ?></a>
            </div>
            
            <nav class="nav-links">
                <a href="<?php echo SITE_URL; ?>/index.php"><i class="fas fa-home"></i> Accueil</a>
                <a href="<?php echo SITE_URL; ?>/blog.php"><i class="fas fa-blog"></i> Blog</a>
                <a href="<?php echo SITE_URL; ?>/contact.php"><i class="fas fa-envelope"></i> Contact</a>
                
                <?php if(isLoggedIn()): ?>
                    <?php if(isAdmin()): ?>
                        <a href="<?php echo SITE_URL; ?>/dashboard/"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <?php endif; ?>
                    <a href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                    <span style="background: white; color: #333; padding: 5px 10px; border-radius: 20px;">
                        <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                    </span>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/login.php" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Connexion
                    </a>
                    <a href="<?php echo SITE_URL; ?>/register.php">
                        <i class="fas fa-user-plus"></i> Inscription
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    
    <main class="container">
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'success'; ?>">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>