<?php
require_once 'includes/config.php';

// Vérifier si un ID d'article est fourni
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Article non trouvé";
    $_SESSION['message_type'] = "error";
    header('Location: blog.php');
    exit();
}

$article_id = (int)$_GET['id'];

// Récupérer l'article avec ses informations
$stmt = $pdo->prepare("
    SELECT a.*, c.name as category_name, u.username, u.avatar,
           COUNT(DISTINCT al.id) as like_count,
           COUNT(DISTINCT com.id) as comment_count
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id 
    LEFT JOIN users u ON a.user_id = u.id 
    LEFT JOIN article_likes al ON a.id = al.article_id 
    LEFT JOIN comments com ON a.id = com.article_id AND com.is_approved = 1
    WHERE a.id = ? AND a.published = 1
    GROUP BY a.id
");

$stmt->execute([$article_id]);
$article = $stmt->fetch();

// Si article non trouvé
if(!$article) {
    $_SESSION['message'] = "Article non trouvé ou non publié";
    $_SESSION['message_type'] = "error";
    header('Location: blog.php');
    exit();
}

// Incrémenter le compteur de vues
$pdo->prepare("UPDATE articles SET views = views + 1 WHERE id = ?")->execute([$article_id]);

// Gérer l'ajout de like
if(isset($_POST['like']) && isLoggedIn()) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO article_likes (article_id, user_id) VALUES (?, ?)");
        $stmt->execute([$article_id, $_SESSION['user_id']]);
        $_SESSION['message'] = "Merci pour votre like !";
        $_SESSION['message_type'] = "success";
        header("Location: article.php?id=$article_id");
        exit();
    } catch(PDOException $e) {
        // Ignorer l'erreur si déjà liké
    }
}

// Gérer l'ajout de commentaire
if(isset($_POST['add_comment']) && isLoggedIn()) {
    $comment = sanitize($_POST['comment']);
    
    if(!empty($comment)) {
        $stmt = $pdo->prepare("
            INSERT INTO comments (article_id, user_id, content, is_approved) 
            VALUES (?, ?, ?, ?)
        ");
        // Les commentaires sont approuvés automatiquement si l'utilisateur est admin/author
        $is_approved = isAdmin() || $_SESSION['role'] === 'author' ? 1 : 0;
        $stmt->execute([$article_id, $_SESSION['user_id'], $comment, $is_approved]);
        
        $_SESSION['message'] = $is_approved 
            ? "Votre commentaire a été publié !" 
            : "Votre commentaire est en attente de modération.";
        $_SESSION['message_type'] = "success";
        header("Location: article.php?id=$article_id");
        exit();
    }
}

// Récupérer les commentaires approuvés
$stmt = $pdo->prepare("
    SELECT c.*, u.username, u.avatar 
    FROM comments c 
    LEFT JOIN users u ON c.user_id = u.id 
    WHERE c.article_id = ? AND c.is_approved = 1 
    ORDER BY c.created_at DESC
");
$stmt->execute([$article_id]);
$comments = $stmt->fetchAll();

// Vérifier si l'utilisateur connecté a déjà liké cet article
$user_has_liked = false;
if(isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT id FROM article_likes WHERE article_id = ? AND user_id = ?");
    $stmt->execute([$article_id, $_SESSION['user_id']]);
    $user_has_liked = $stmt->fetch() ? true : false;
}

$page_title = $article['title'];
include 'includes/header.php';
?>

<section style="padding: 40px 0; background: #f8f9fa; min-height: 80vh;">
    <div class="container">
        <!-- Navigation -->
        <div style="margin-bottom: 30px;">
            <a href="blog.php" style="color: #667eea; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Retour au blog
            </a>
            <span style="margin: 0 10px; color: #ccc;">/</span>
            <a href="blog.php?category=<?php echo $article['category_id']; ?>" style="color: #667eea; text-decoration: none;">
                <?php echo $article['category_name']; ?>
            </a>
        </div>
        
        <!-- Article principal -->
        <article style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
            <!-- Image de l'article -->
            <div style="height: 400px; overflow: hidden; position: relative;">
                <?php if($article['image']): ?>
                <img src="uploads/articles/<?php echo $article['image']; ?>" 
                     alt="<?php echo $article['title']; ?>" 
                     style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <i class="fas fa-newspaper" style="font-size: 6rem; opacity: 0.3;"></i>
                </div>
                <?php endif; ?>
                
                <!-- Badge catégorie -->
                <div style="position: absolute; top: 20px; left: 20px;">
                    <span style="background: rgba(255,255,255,0.9); color: #333; padding: 8px 16px; border-radius: 20px; font-weight: bold;">
                        <?php echo $article['category_name']; ?>
                    </span>
                </div>
            </div>
            
            <!-- Contenu de l'article -->
            <div style="padding: 50px;">
                <!-- Métadonnées -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <?php if($article['avatar']): ?>
                            <img src="uploads/avatars/<?php echo $article['avatar']; ?>" 
                                 alt="<?php echo $article['username']; ?>" 
                                 style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                            <i class="fas fa-user" style="color: #666; font-size: 1.2rem;"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div style="font-weight: bold; font-size: 1.1rem;"><?php echo $article['username']; ?></div>
                            <div style="color: #666; font-size: 0.9rem;">
                                <i class="far fa-calendar"></i> Publié le <?php echo date('d/m/Y à H:i', strtotime($article['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 20px; color: #666;">
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #333;"><?php echo $article['views']; ?></div>
                            <div style="font-size: 0.9rem;">Vues</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #333;"><?php echo $article['like_count']; ?></div>
                            <div style="font-size: 0.9rem;">Likes</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #333;"><?php echo $article['comment_count']; ?></div>
                            <div style="font-size: 0.9rem;">Commentaires</div>
                        </div>
                    </div>
                </div>
                
                <!-- Titre -->
                <h1 style="font-size: 2.5rem; margin-bottom: 30px; line-height: 1.3;">
                    <?php echo $article['title']; ?>
                </h1>
                
                <!-- Extrait -->
                <?php if($article['excerpt']): ?>
                <div style="background: #f8f9fa; padding: 20px; border-left: 4px solid #667eea; margin-bottom: 30px; border-radius: 4px;">
                    <p style="font-size: 1.1rem; font-style: italic; color: #555; margin: 0;">
                        <?php echo $article['excerpt']; ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <!-- Contenu -->
                <div style="font-size: 1.1rem; line-height: 1.8; color: #333;">
                    <?php echo nl2br(htmlspecialchars_decode($article['content'])); ?>
                </div>
                
                <!-- Actions -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 50px; padding-top: 30px; border-top: 1px solid #eee;">
                    <!-- Bouton Like -->
                    <form method="POST" style="margin: 0;">
                        <?php if(isLoggedIn()): ?>
                            <button type="submit" name="like" 
                                    style="background: <?php echo $user_has_liked ? '#e74c3c' : '#667eea'; ?>; 
                                           color: white; padding: 12px 30px; border: none; border-radius: 30px; 
                                           cursor: pointer; font-size: 1rem; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-heart"></i>
                                <?php echo $user_has_liked ? 'Vous aimez déjà' : 'J\'aime cet article'; ?>
                                (<?php echo $article['like_count']; ?>)
                            </button>
                        <?php else: ?>
                            <a href="login.php" 
                               style="background: #667eea; color: white; padding: 12px 30px; border-radius: 30px; 
                                      text-decoration: none; display: inline-flex; align-items: center; gap: 10px;">
                                <i class="fas fa-heart"></i>
                                Connectez-vous pour liker
                                (<?php echo $article['like_count']; ?>)
                            </a>
                        <?php endif; ?>
                    </form>
                    
                    <!-- Partage -->
                    <div style="display: flex; gap: 10px;">
                        <span style="color: #666;">Partager :</span>
                        <a href="#" style="color: #3b5998;"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" style="color: #1da1f2;"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" style="color: #0077b5;"><i class="fab fa-linkedin fa-lg"></i></a>
                        <a href="#" style="color: #333;"><i class="fab fa-github fa-lg"></i></a>
                    </div>
                </div>
            </div>
        </article>
        
        <!-- Section commentaires -->
        <div style="margin-top: 50px;">
            <h2 style="margin-bottom: 30px; font-size: 1.8rem;">
                <i class="far fa-comments"></i> Commentaires (<?php echo count($comments); ?>)
            </h2>
            
            <!-- Formulaire d'ajout de commentaire -->
            <?php if(isLoggedIn()): ?>
            <div style="background: white; padding: 30px; border-radius: 8px; margin-bottom: 40px; box-shadow: 0 3px 10px rgba(0,0,0,0.05);">
                <h3 style="margin-bottom: 20px;">Ajouter un commentaire</h3>
                <form method="POST">
                    <textarea name="comment" 
                              placeholder="Votre commentaire..." 
                              rows="4"
                              style="width: 100%; padding: 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; margin-bottom: 15px; resize: vertical;"></textarea>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <small style="color: #666;">
                            Votre commentaire sera <?php echo (isAdmin() || $_SESSION['role'] === 'author') ? 'publié immédiatement' : 'soumis à modération'; ?>
                        </small>
                        <button type="submit" name="add_comment"
                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; border: none; border-radius: 30px; cursor: pointer; font-weight: bold;">
                            <i class="fas fa-paper-plane"></i> Publier le commentaire
                        </button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div style="background: #f8f9fa; padding: 30px; border-radius: 8px; text-align: center; margin-bottom: 40px;">
                <p style="margin-bottom: 20px;">Connectez-vous pour ajouter un commentaire</p>
                <a href="login.php" 
                   style="background: #667eea; color: white; padding: 12px 30px; border-radius: 30px; text-decoration: none; display: inline-block;">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </a>
            </div>
            <?php endif; ?>
            
            <!-- Liste des commentaires -->
            <?php if(empty($comments)): ?>
            <div style="text-align: center; padding: 40px; background: white; border-radius: 8px;">
                <i class="far fa-comment-dots" style="font-size: 3rem; color: #ddd; margin-bottom: 20px;"></i>
                <h3 style="color: #666; margin-bottom: 10px;">Aucun commentaire pour le moment</h3>
                <p style="color: #999;">Soyez le premier à commenter cet article !</p>
            </div>
            <?php else: ?>
            <div style="display: grid; gap: 20px;">
                <?php foreach($comments as $comment): ?>
                <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                        <div style="width: 40px; height: 40px; background: #f0f0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <?php if($comment['avatar']): ?>
                            <img src="uploads/avatars/<?php echo $comment['avatar']; ?>" 
                                 alt="<?php echo $comment['username']; ?>" 
                                 style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                            <i class="fas fa-user" style="color: #666;"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div style="font-weight: bold;"><?php echo $comment['username']; ?></div>
                            <div style="color: #666; font-size: 0.9rem;">
                                <i class="far fa-clock"></i> <?php echo date('d/m/Y à H:i', strtotime($comment['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div style="color: #333; line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                    </div>
                    
                    <?php if(isAdmin()): ?>
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; text-align: right;">
                        <a href="dashboard/comments.php?action=delete&id=<?php echo $comment['id']; ?>" 
                           style="color: #e74c3c; text-decoration: none; font-size: 0.9rem;">
                            <i class="fas fa-trash"></i> Supprimer
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Articles similaires -->
        <?php
        $stmt = $pdo->prepare("
            SELECT a.*, c.name as category_name 
            FROM articles a 
            LEFT JOIN categories c ON a.category_id = c.id 
            WHERE a.category_id = ? AND a.id != ? AND a.published = 1 
            ORDER BY a.created_at DESC 
            LIMIT 3
        ");
        $stmt->execute([$article['category_id'], $article_id]);
        $related_articles = $stmt->fetchAll();
        
        if(!empty($related_articles)):
        ?>
        <div style="margin-top: 80px;">
            <h2 style="margin-bottom: 30px; font-size: 1.8rem;">
                <i class="fas fa-newspaper"></i> Articles similaires
            </h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px;">
                <?php foreach($related_articles as $related): ?>
                <div style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 3px 10px rgba(0,0,0,0.08);">
                    <div style="height: 180px; overflow: hidden;">
                        <?php if($related['image']): ?>
                        <img src="uploads/articles/<?php echo $related['image']; ?>" 
                             alt="<?php echo $related['title']; ?>" 
                             style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                        <?php endif; ?>
                    </div>
                    
                    <div style="padding: 20px;">
                        <span style="background: #f0f0f0; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; color: #666;">
                            <?php echo $related['category_name']; ?>
                        </span>
                        
                        <h3 style="margin: 15px 0 10px; font-size: 1.1rem;">
                            <a href="article.php?id=<?php echo $related['id']; ?>" 
                               style="color: #333; text-decoration: none; display: block;">
                                <?php echo $related['title']; ?>
                            </a>
                        </h3>
                        
                        <div style="color: #666; font-size: 0.9rem; display: flex; justify-content: space-between; align-items: center;">
                            <span>
                                <i class="far fa-calendar"></i> <?php echo date('d/m/Y', strtotime($related['created_at'])); ?>
                            </span>
                            <a href="article.php?id=<?php echo $related['id']; ?>" 
                               style="color: #667eea; text-decoration: none; font-weight: bold; font-size: 0.9rem;">
                                Lire <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>