<?php
require_once 'includes/config.php';

// Récupérer les 3 derniers articles
$stmt = $pdo->query("
    SELECT a.*, c.name as category_name, u.username 
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id 
    LEFT JOIN users u ON a.user_id = u.id 
    WHERE a.published = 1 
    ORDER BY a.created_at DESC 
    LIMIT 3
");
$latestArticles = $stmt->fetchAll();

// Récupérer les articles les plus likés
$stmt = $pdo->query("
    SELECT a.*, COUNT(al.id) as like_count, c.name as category_name
    FROM articles a 
    LEFT JOIN article_likes al ON a.id = al.article_id 
    LEFT JOIN categories c ON a.category_id = c.id 
    WHERE a.published = 1 
    GROUP BY a.id 
    ORDER BY like_count DESC 
    LIMIT 5
");
$popularArticles = $stmt->fetchAll();

// Récupérer les catégories avec 6 derniers articles
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$categoryArticles = [];
foreach($categories as $category) {
    $stmt = $pdo->prepare("
        SELECT a.* 
        FROM articles a 
        WHERE a.category_id = ? AND a.published = 1 
        ORDER BY a.created_at DESC 
        LIMIT 6
    ");
    $stmt->execute([$category['id']]);
    $categoryArticles[$category['id']] = $stmt->fetchAll();
}

$page_title = "Accueil";
include 'includes/header.php';
?>

<section style="background: #f8f9fa; padding: 60px 0; text-align: center; margin-bottom: 40px;">
    <h1 style="font-size: 2.5rem; margin-bottom: 20px;">Bienvenue sur <?php echo SITE_NAME; ?></h1>
    <p style="font-size: 1.2rem; max-width: 800px; margin: 0 auto;">Découvrez nos derniers articles sur la technologie, la programmation et le design.</p>
    
    <!-- Barre de recherche -->
    <form action="blog.php" method="GET" style="margin-top: 30px; max-width: 600px; margin-left: auto; margin-right: auto;">
        <div style="display: flex; gap: 10px;">
            <input type="text" name="q" placeholder="Rechercher un article..." 
                   style="flex: 1; padding: 12px; border: 2px solid #ddd; border-radius: 4px;">
            <button type="submit" style="background: #667eea; color: white; border: none; padding: 12px 30px; border-radius: 4px; cursor: pointer;">
                <i class="fas fa-search"></i> Rechercher
            </button>
        </div>
    </form>
</section>

<!-- 3 derniers articles -->
<section style="margin-bottom: 60px;">
    <h2 style="text-align: center; margin-bottom: 40px; font-size: 2rem;">Derniers articles</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px;">
        <?php foreach($latestArticles as $article): ?>
        <article style="border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; transition: transform 0.3s, box-shadow 0.3s;">
            <div style="height: 200px; background: #f0f0f0; overflow: hidden;">
                <?php if($article['image']): ?>
                <img src="uploads/articles/<?php echo $article['image']; ?>" alt="<?php echo $article['title']; ?>" 
                     style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <i class="fas fa-newspaper" style="font-size: 3rem;"></i>
                </div>
                <?php endif; ?>
            </div>
            
            <div style="padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <span style="background: #e0e0e0; padding: 4px 12px; border-radius: 20px; font-size: 0.9rem;">
                        <?php echo $article['category_name']; ?>
                    </span>
                    <span style="color: #666; font-size: 0.9rem;">
                        <i class="far fa-calendar"></i> <?php echo date('d/m/Y', strtotime($article['created_at'])); ?>
                    </span>
                </div>
                
                <h3 style="margin-bottom: 10px; font-size: 1.3rem;">
                    <a href="article.php?id=<?php echo $article['id']; ?>" 
                       style="color: #333; text-decoration: none;">
                        <?php echo $article['title']; ?>
                    </a>
                </h3>
                
                <p style="color: #666; margin-bottom: 15px;">
                    <?php echo substr(strip_tags($article['content']), 0, 120) . '...'; ?>
                </p>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                    <span style="color: #666;">
                        <i class="fas fa-user"></i> <?php echo $article['username']; ?>
                    </span>
                    <a href="article.php?id=<?php echo $article['id']; ?>" 
                       style="color: #667eea; text-decoration: none; font-weight: bold;">
                        Lire la suite <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>

<!-- Articles populaires -->
<section style="margin-bottom: 60px;">
    <h2 style="text-align: center; margin-bottom: 40px; font-size: 2rem;">Articles populaires</h2>
    
    <div style="background: white; border-radius: 8px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <div style="display: grid; gap: 20px;">
            <?php foreach($popularArticles as $index => $article): ?>
            <div style="display: flex; align-items: center; padding: 15px; border-bottom: 1px solid #f0f0f0; transition: background 0.3s;">
                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%; margin-right: 15px; font-weight: bold;">
                    <?php echo $index + 1; ?>
                </div>
                <div style="flex: 1;">
                    <h4 style="margin-bottom: 5px;">
                        <a href="article.php?id=<?php echo $article['id']; ?>" style="color: #333; text-decoration: none;">
                            <?php echo $article['title']; ?>
                        </a>
                    </h4>
                    <div style="display: flex; gap: 15px; font-size: 0.9rem; color: #666;">
                        <span><i class="far fa-heart"></i> <?php echo $article['like_count']; ?> likes</span>
                        <span><i class="far fa-folder"></i> <?php echo $article['category_name']; ?></span>
                    </div>
                </div>
                <div style="color: #667eea; font-weight: bold;">
                    <?php echo $article['like_count']; ?> <i class="fas fa-heart" style="color: #e74c3c;"></i>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Catégories -->
<section style="margin-bottom: 60px;">
    <h2 style="text-align: center; margin-bottom: 40px; font-size: 2rem;">Catégories</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
        <?php foreach($categories as $category): 
            $articles = $categoryArticles[$category['id']] ?? [];
        ?>
        <div style="background: white; border-radius: 8px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <h3 style="margin-bottom: 20px; color: #667eea; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-folder"></i> <?php echo $category['name']; ?>
            </h3>
            
            <?php if(!empty($articles)): ?>
            <ul style="list-style: none; margin-bottom: 20px;">
                <?php foreach($articles as $article): ?>
                <li style="padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                    <a href="article.php?id=<?php echo $article['id']; ?>" 
                       style="color: #333; text-decoration: none; display: block;">
                        <i class="fas fa-newspaper" style="color: #667eea; margin-right: 10px;"></i>
                        <?php echo $article['title']; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
            <p style="color: #666; text-align: center; padding: 20px 0;">
                Aucun article dans cette catégorie pour le moment.
            </p>
            <?php endif; ?>
            
            <a href="blog.php?category=<?php echo $category['id']; ?>" 
               style="display: inline-block; background: #667eea; color: white; padding: 8px 20px; border-radius: 4px; text-decoration: none;">
                Voir tous <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Newsletter -->
<section style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 60px 0; border-radius: 8px; text-align: center;">
    <div style="max-width: 600px; margin: 0 auto;">
        <h2 style="font-size: 2rem; margin-bottom: 20px;">Restez informé</h2>
        <p style="margin-bottom: 30px; font-size: 1.1rem;">
            Abonnez-vous à notre newsletter pour recevoir les derniers articles directement dans votre boîte mail.
        </p>
        
        <form method="POST" action="newsletter.php" style="display: flex; gap: 10px;">
            <input type="email" name="email" placeholder="Votre adresse email" required 
                   style="flex: 1; padding: 15px; border: none; border-radius: 4px; font-size: 1rem;">
            <button type="submit" style="background: white; color: #667eea; border: none; padding: 15px 30px; border-radius: 4px; font-weight: bold; cursor: pointer;">
                <i class="fas fa-paper-plane"></i> S'abonner
            </button>
        </form>
        
        <p style="margin-top: 15px; font-size: 0.9rem; opacity: 0.8;">
            Pas de spam. Désabonnez-vous à tout moment.
        </p>
    </div>
</section>

<?php include 'includes/footer.php'; ?>