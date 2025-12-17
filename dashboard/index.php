<?php

// NOUVEAU :
require_once '../includes/config.php'; 

// Initialiser les classes
$userClass = new User();
$articleClass = new Article();
$categoryClass = new Category();
$commentClass = new Comment();
$newsletterClass = new Newsletter();
require_once '../includes/config.php';
require_once '../classes/Database.php';
require_once '../classes/User.php';
require_once '../classes/Article.php';
require_once '../classes/Category.php';
require_once '../classes/Comment.php';
require_once '../classes/Newsletter.php';

// Vérifier l'authentification et les permissions
if(!isLoggedIn() || !isAdmin()) {
    $_SESSION['message'] = "Accès refusé. Admin requis.";
    $_SESSION['message_type'] = "error";
    header('Location: ../login.php');
    exit();
}

// Initialiser les classes
$userClass = new User();
$articleClass = new Article();
$categoryClass = new Category();
$commentClass = new Comment();
$newsletterClass = new Newsletter();

// Statistiques
$totalUsers = $userClass->countUsers();
$totalArticles = $articleClass->paginate(1, 1, false)['total'];
$pendingComments = $commentClass->countPending();
$totalSubscribers = $newsletterClass->countSubscribers();

// Derniers articles
$latestArticles = $articleClass->getLatest(5, false);

// Derniers utilisateurs
$latestUsers = Database::fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

$page_title = "Tableau de bord";
include '../includes/header.php';
?>

<div style="min-height: 100vh; background: #f8f9fa;">
    <!-- Sidebar -->
    <div style="position: fixed; left: 0; top: 0; bottom: 0; width: 250px; background: #2c3e50; color: white; padding: 20px 0; overflow-y: auto;">
        <div style="padding: 0 20px 30px; border-bottom: 1px solid #34495e;">
            <h2 style="margin: 0; font-size: 1.5rem;">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </h2>
            <p style="color: #bdc3c7; margin: 5px 0 0; font-size: 0.9rem;">
                <?php echo $_SESSION['username']; ?> (<?php echo $_SESSION['role']; ?>)
            </p>
        </div>
        
        <nav style="margin-top: 30px;">
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 5px;">
                    <a href="index.php" 
                       style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: white; text-decoration: none; background: #34495e; border-left: 4px solid #3498db;">
                        <i class="fas fa-tachometer-alt"></i> Tableau de bord
                    </a>
                </li>
                <li style="margin-bottom: 5px;">
                    <a href="articles.php" 
                       style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: #bdc3c7; text-decoration: none; transition: all 0.3s;"
                       onmouseover="this.style.backgroundColor='#34495e'; this.style.color='white';"
                       onmouseout="this.style.backgroundColor='transparent'; this.style.color='#bdc3c7';">
                        <i class="fas fa-newspaper"></i> Articles
                    </a>
                </li>
                <li style="margin-bottom: 5px;">
                    <a href="categories.php" 
                       style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: #bdc3c7; text-decoration: none; transition: all 0.3s;"
                       onmouseover="this.style.backgroundColor='#34495e'; this.style.color='white';"
                       onmouseout="this.style.backgroundColor='transparent'; this.style.color='#bdc3c7';">
                        <i class="fas fa-folder"></i> Catégories
                    </a>
                </li>
                <li style="margin-bottom: 5px;">
                    <a href="comments.php" 
                       style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: #bdc3c7; text-decoration: none; transition: all 0.3s;"
                       onmouseover="this.style.backgroundColor='#34495e'; this.style.color='white';"
                       onmouseout="this.style.backgroundColor='transparent'; this.style.color='#bdc3c7';">
                        <i class="fas fa-comments"></i> Commentaires
                        <?php if($pendingComments > 0): ?>
                        <span style="background: #e74c3c; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem;">
                            <?php echo $pendingComments; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li style="margin-bottom: 5px;">
                    <a href="users.php" 
                       style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: #bdc3c7; text-decoration: none; transition: all 0.3s;"
                       onmouseover="this.style.backgroundColor='#34495e'; this.style.color='white';"
                       onmouseout="this.style.backgroundColor='transparent'; this.style.color='#bdc3c7';">
                        <i class="fas fa-users"></i> Utilisateurs
                    </a>
                </li>
                <li style="margin-bottom: 5px;">
                    <a href="contacts.php" 
                       style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: #bdc3c7; text-decoration: none; transition: all 0.3s;"
                       onmouseover="this.style.backgroundColor='#34495e'; this.style.color='white';"
                       onmouseout="this.style.backgroundColor='transparent'; this.style.color='#bdc3c7';">
                        <i class="fas fa-envelope"></i> Messages
                    </a>
                </li>
                <li style="margin-bottom: 5px;">
                    <a href="newsletter.php" 
                       style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: #bdc3c7; text-decoration: none; transition: all 0.3s;"
                       onmouseover="this.style.backgroundColor='#34495e'; this.style.color='white';"
                       onmouseout="this.style.backgroundColor='transparent'; this.style.color='#bdc3c7';">
                        <i class="fas fa-mail-bulk"></i> Newsletter
                    </a>
                </li>
                <li style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #34495e;">
                    <a href="../index.php" 
                       style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: #bdc3c7; text-decoration: none; transition: all 0.3s;"
                       onmouseover="this.style.backgroundColor='#34495e'; this.style.color='white';"
                       onmouseout="this.style.backgroundColor='transparent'; this.style.color='#bdc3c7';">
                        <i class="fas fa-globe"></i> Voir le site
                    </a>
                </li>
                <li>
                    <a href="../logout.php" 
                       style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: #e74c3c; text-decoration: none; transition: all 0.3s;"
                       onmouseover="this.style.backgroundColor='#34495e';"
                       onmouseout="this.style.backgroundColor='transparent';">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    
    <!-- Contenu principal -->
    <div style="margin-left: 250px; padding: 30px;">
        <!-- En-tête -->
        <div style="margin-bottom: 30px;">
            <h1 style="margin: 0 0 10px; color: #2c3e50;">Tableau de bord</h1>
            <p style="color: #7f8c8d; margin: 0;">Bienvenue dans l'espace d'administration</p>
        </div>
        
        <!-- Statistiques -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px;">
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-top: 4px solid #3498db;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 2rem; font-weight: bold; color: #2c3e50;"><?php echo $totalUsers; ?></div>
                        <div style="color: #7f8c8d;">Utilisateurs</div>
                    </div>
                    <div style="width: 50px; height: 50px; background: #e8f4fc; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-users" style="color: #3498db; font-size: 1.5rem;"></i>
                    </div>
                </div>
                <a href="users.php" style="display: inline-block; margin-top: 15px; color: #3498db; text-decoration: none; font-size: 0.9rem;">
                    Voir tous <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-top: 4px solid #2ecc71;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 2rem; font-weight: bold; color: #2c3e50;"><?php echo $totalArticles; ?></div>
                        <div style="color: #7f8c8d;">Articles</div>
                    </div>
                    <div style="width: 50px; height: 50px; background: #e8f8ef; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-newspaper" style="color: #2ecc71; font-size: 1.5rem;"></i>
                    </div>
                </div>
                <a href="articles.php" style="display: inline-block; margin-top: 15px; color: #2ecc71; text-decoration: none; font-size: 0.9rem;">
                    Gérer <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-top: 4px solid #e74c3c;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 2rem; font-weight: bold; color: #2c3e50;"><?php echo $pendingComments; ?></div>
                        <div style="color: #7f8c8d;">Commentaires en attente</div>
                    </div>
                    <div style="width: 50px; height: 50px; background: #fdedec; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-comments" style="color: #e74c3c; font-size: 1.5rem;"></i>
                    </div>
                </div>
                <a href="comments.php" style="display: inline-block; margin-top: 15px; color: #e74c3c; text-decoration: none; font-size: 0.9rem;">
                    Modérer <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border-top: 4px solid #9b59b6;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 2rem; font-weight: bold; color: #2c3e50;"><?php echo $totalSubscribers; ?></div>
                        <div style="color: #7f8c8d;">Abonnés newsletter</div>
                    </div>
                    <div style="width: 50px; height: 50px; background: #f4ecf7; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-mail-bulk" style="color: #9b59b6; font-size: 1.5rem;"></i>
                    </div>
                </div>
                <a href="newsletter.php" style="display: inline-block; margin-top: 15px; color: #9b59b6; text-decoration: none; font-size: 0.9rem;">
                    Gérer <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
        
        <!-- Derniers articles et utilisateurs -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px;">
            <!-- Derniers articles -->
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; color: #2c3e50;">
                        <i class="fas fa-newspaper"></i> Derniers articles
                    </h3>
                    <a href="articles.php?action=create" 
                       style="background: #3498db; color: white; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 0.9rem;">
                        <i class="fas fa-plus"></i> Nouveau
                    </a>
                </div>
                
                <?php if(empty($latestArticles)): ?>
                <p style="color: #95a5a6; text-align: center; padding: 20px 0;">Aucun article publié</p>
                <?php else: ?>
                <div style="display: grid; gap: 15px;">
                    <?php foreach($latestArticles as $article): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                        <div style="flex: 1;">
                            <div style="font-weight: 500; color: #2c3e50; margin-bottom: 5px;">
                                <a href="../article.php?id=<?php echo $article['id']; ?>" style="color: inherit; text-decoration: none;">
                                    <?php echo substr($article['title'], 0, 40); ?><?php echo strlen($article['title']) > 40 ? '...' : ''; ?>
                                </a>
                            </div>
                            <div style="display: flex; gap: 15px; font-size: 0.85rem; color: #7f8c8d;">
                                <span><?php echo date('d/m/Y', strtotime($article['created_at'])); ?></span>
                                <span>
                                    <?php if($article['published']): ?>
                                    <span style="color: #2ecc71;"><i class="fas fa-check-circle"></i> Publié</span>
                                    <?php else: ?>
                                    <span style="color: #e74c3c;"><i class="fas fa-clock"></i> Brouillon</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <a href="articles.php?action=edit&id=<?php echo $article['id']; ?>" 
                               style="color: #3498db; text-decoration: none;" title="Éditer">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="articles.php?action=delete&id=<?php echo $article['id']; ?>" 
                               onclick="return confirm('Supprimer cet article ?')"
                               style="color: #e74c3c; text-decoration: none;" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Derniers utilisateurs -->
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; color: #2c3e50;">
                        <i class="fas fa-users"></i> Derniers utilisateurs
                    </h3>
                    <a href="users.php?action=create" 
                       style="background: #3498db; color: white; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-size: 0.9rem;">
                        <i class="fas fa-plus"></i> Ajouter
                    </a>
                </div>
                
                <?php if(empty($latestUsers)): ?>
                <p style="color: #95a5a6; text-align: center; padding: 20px 0;">Aucun utilisateur</p>
                <?php else: ?>
                <div style="display: grid; gap: 15px;">
                    <?php foreach($latestUsers as $user): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                        <div style="flex: 1;">
                            <div style="font-weight: 500; color: #2c3e50; margin-bottom: 5px;">
                                <?php echo $user['username']; ?>
                                <span style="font-size: 0.85rem; background: #e0e0e0; padding: 2px 8px; border-radius: 10px; margin-left: 10px;">
                                    <?php echo $user['role']; ?>
                                </span>
                            </div>
                            <div style="font-size: 0.85rem; color: #7f8c8d;">
                                <?php echo $user['email']; ?>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <span style="font-size: 0.85rem; color: <?php echo $user['is_active'] ? '#2ecc71' : '#e74c3c'; ?>;">
                                <?php echo $user['is_active'] ? 'Actif' : 'Inactif'; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Actions rapides -->
        <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <h3 style="margin: 0 0 20px; color: #2c3e50;">
                <i class="fas fa-bolt"></i> Actions rapides
            </h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <a href="articles.php?action=create" 
                   style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; background: #e8f4fc; border-radius: 8px; text-decoration: none; color: #3498db; transition: transform 0.2s;"
                   onmouseover="this.style.transform='translateY(-5px)';"
                   onmouseout="this.style.transform='translateY(0)';">
                    <i class="fas fa-plus-circle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <span style="font-weight: 500;">Nouvel article</span>
                </a>
                
                <a href="categories.php?action=create" 
                   style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; background: #e8f8ef; border-radius: 8px; text-decoration: none; color: #2ecc71; transition: transform 0.2s;"
                   onmouseover="this.style.transform='translateY(-5px)';"
                   onmouseout="this.style.transform='translateY(0)';">
                    <i class="fas fa-folder-plus" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <span style="font-weight: 500;">Nouvelle catégorie</span>
                </a>
                
                <a href="comments.php" 
                   style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; background: #fdedec; border-radius: 8px; text-decoration: none; color: #e74c3c; transition: transform 0.2s;"
                   onmouseover="this.style.transform='translateY(-5px)';"
                   onmouseout="this.style.transform='translateY(0)';">
                    <i class="fas fa-comment-dots" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <span style="font-weight: 500;">Modérer commentaires</span>
                </a>
                
                <a href="newsletter.php" 
                   style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; background: #f4ecf7; border-radius: 8px; text-decoration: none; color: #9b59b6; transition: transform 0.2s;"
                   onmouseover="this.style.transform='translateY(-5px)';"
                   onmouseout="this.style.transform='translateY(0)';">
                    <i class="fas fa-paper-plane" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <span style="font-weight: 500;">Envoyer newsletter</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer-dashboard.php'; ?>