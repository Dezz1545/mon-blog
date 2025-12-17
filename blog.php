<?php
require_once 'includes/config.php';

// Récupérer les paramètres de filtrage
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'recent'; // recent ou popular
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10; // 10 articles par page

// Calculer l'offset pour la pagination
$offset = ($page - 1) * $per_page;

// Construire la requête SQL de base
$sql = "
    SELECT a.*, c.name as category_name, u.username,
           COUNT(al.id) as like_count
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id 
    LEFT JOIN users u ON a.user_id = u.id 
    LEFT JOIN article_likes al ON a.id = al.article_id 
    WHERE a.published = 1 
";

$params = [];
$where_conditions = [];

// Ajouter les filtres
if(!empty($search)) {
    $where_conditions[] = "(a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if($category_id) {
    $where_conditions[] = "a.category_id = ?";
    $params[] = $category_id;
}

if(!empty($where_conditions)) {
    $sql .= " AND " . implode(" AND ", $where_conditions);
}

// Grouper pour le COUNT des likes
$sql .= " GROUP BY a.id ";

// Trier selon le filtre
if($filter === 'popular') {
    $sql .= " ORDER BY like_count DESC, a.created_at DESC ";
} else {
    $sql .= " ORDER BY a.created_at DESC ";
}

// Requête pour le nombre total d'articles
$count_sql = "SELECT COUNT(DISTINCT a.id) as total FROM articles a 
              LEFT JOIN categories c ON a.category_id = c.id 
              WHERE a.published = 1 ";
if(!empty($where_conditions)) {
    $count_sql .= " AND " . implode(" AND ", $where_conditions);
}

// Exécuter la requête de comptage
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_result = $count_stmt->fetch();
$total_articles = $total_result['total'];
$total_pages = ceil($total_articles / $per_page);

// Ajouter la pagination à la requête principale
$sql .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

// Exécuter la requête principale
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll();

// Récupérer toutes les catégories pour le filtre
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$page_title = "Blog";
include 'includes/header.php';
?>

<section style="padding: 40px 0; background: #f8f9fa;">
    <div class="container">
        <h1 style="text-align: center; margin-bottom: 40px; font-size: 2.5rem;">Blog</h1>
        
        <!-- Filtres et recherche -->
        <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 40px;">
            <form method="GET" style="display: grid; grid-template-columns: 1fr auto auto auto; gap: 15px; align-items: end;">
                <!-- Recherche -->
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Recherche</label>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Rechercher un article..." 
                           style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <!-- Filtre par catégorie -->
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Catégorie</label>
                    <select name="category" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Toutes les catégories</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                            <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo $cat['name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Filtre par popularité/récence -->
                <div>
                    <label style="display: block; margin-bottom: 8px; font-weight: bold;">Trier par</label>
                    <select name="filter" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="recent" <?php echo $filter === 'recent' ? 'selected' : ''; ?>>Plus récents</option>
                        <option value="popular" <?php echo $filter === 'popular' ? 'selected' : ''; ?>>Plus populaires</option>
                    </select>
                </div>
                
                <!-- Bouton -->
                <div>
                    <button type="submit" 
                            style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; white-space: nowrap;">
                        <i class="fas fa-filter"></i> Appliquer les filtres
                    </button>
                </div>
            </form>
            
            <?php if(!empty($search) || $category_id): ?>
            <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 4px;">
                <p style="margin: 0;">
                    <i class="fas fa-info-circle"></i> 
                    <?php echo $total_articles; ?> article(s) trouvé(s)
                    <?php if(!empty($search)): ?>
                    pour "<strong><?php echo htmlspecialchars($search); ?></strong>"
                    <?php endif; ?>
                    <?php if($category_id): ?>
                    dans la catégorie "<strong><?php echo $categories[array_search($category_id, array_column($categories, 'id'))]['name']; ?></strong>"
                    <?php endif; ?>
                    <a href="blog.php" style="margin-left: 15px; color: #667eea;">
                        <i class="fas fa-times"></i> Effacer les filtres
                    </a>
                </p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Liste des articles -->
        <?php if(empty($articles)): ?>
        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 8px;">
            <i class="fas fa-newspaper" style="font-size: 4rem; color: #ddd; margin-bottom: 20px;"></i>
            <h3 style="margin-bottom: 15px;">Aucun article trouvé</h3>
            <p style="color: #666; margin-bottom: 30px;">
                <?php if(!empty($search) || $category_id): ?>
                Essayez avec d'autres critères de recherche.
                <?php else: ?>
                Aucun article n'a été publié pour le moment.
                <?php endif; ?>
            </p>
            <a href="blog.php" style="background: #667eea; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none;">
                Voir tous les articles
            </a>
        </div>
        <?php else: ?>
        <div style="display: grid; gap: 30px; margin-bottom: 50px;">
            <?php foreach($articles as $article): ?>
            <article style="display: grid; grid-template-columns: 300px 1fr; gap: 30px; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <!-- Image -->
                <div style="height: 250px; overflow: hidden;">
                    <?php if($article['image']): ?>
                    <img src="uploads/articles/<?php echo $article['image']; ?>" 
                         alt="<?php echo $article['title']; ?>" 
                         style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <i class="fas fa-newspaper" style="font-size: 4rem;"></i>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Contenu -->
                <div style="padding: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <span style="background: #e0e0e0; padding: 4px 12px; border-radius: 20px; font-size: 0.9rem;">
                            <?php echo $article['category_name']; ?>
                        </span>
                        <div style="display: flex; gap: 15px; color: #666; font-size: 0.9rem;">
                            <span><i class="far fa-calendar"></i> <?php echo date('d/m/Y', strtotime($article['created_at'])); ?></span>
                            <span><i class="far fa-heart"></i> <?php echo $article['like_count']; ?> likes</span>
                            <span><i class="far fa-eye"></i> <?php echo $article['views']; ?> vues</span>
                        </div>
                    </div>
                    
                    <h2 style="margin-bottom: 15px; font-size: 1.5rem;">
                        <a href="article.php?id=<?php echo $article['id']; ?>" 
                           style="color: #333; text-decoration: none;">
                            <?php echo $article['title']; ?>
                        </a>
                    </h2>
                    
                    <p style="color: #666; margin-bottom: 20px; line-height: 1.6;">
                        <?php echo $article['excerpt']; ?>
                    </p>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 40px; height: 40px; background: #f0f0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user" style="color: #666;"></i>
                            </div>
                            <div>
                                <div style="font-weight: bold;"><?php echo $article['username']; ?></div>
                                <small style="color: #666;">Auteur</small>
                            </div>
                        </div>
                        
                        <a href="article.php?id=<?php echo $article['id']; ?>" 
                           style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 10px 25px; border-radius: 4px; text-decoration: none; font-weight: bold;">
                            Lire l'article <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
        <div style="text-align: center; margin-top: 40px;">
            <div style="display: inline-flex; gap: 5px; flex-wrap: wrap; justify-content: center;">
                <!-- Page précédente -->
                <?php if($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?><?php echo $category_id ? '&category='.$category_id : ''; ?><?php echo !empty($search) ? '&q='.urlencode($search) : ''; ?><?php echo $filter !== 'recent' ? '&filter='.$filter : ''; ?>" 
                   style="padding: 10px 15px; border: 1px solid #ddd; text-decoration: none; color: #333; border-radius: 4px;">
                    <i class="fas fa-chevron-left"></i> Précédent
                </a>
                <?php endif; ?>
                
                <!-- Pages numérotées -->
                <?php 
                $start = max(1, $page - 2);
                $end = min($total_pages, $page + 2);
                
                for($i = $start; $i <= $end; $i++): 
                ?>
                <a href="?page=<?php echo $i; ?><?php echo $category_id ? '&category='.$category_id : ''; ?><?php echo !empty($search) ? '&q='.urlencode($search) : ''; ?><?php echo $filter !== 'recent' ? '&filter='.$filter : ''; ?>" 
                   style="padding: 10px 15px; border: 1px solid #ddd; text-decoration: none; color: #333; border-radius: 4px; <?php echo $i == $page ? 'background: #667eea; color: white; border-color: #667eea;' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <!-- Page suivante -->
                <?php if($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?><?php echo $category_id ? '&category='.$category_id : ''; ?><?php echo !empty($search) ? '&q='.urlencode($search) : ''; ?><?php echo $filter !== 'recent' ? '&filter='.$filter : ''; ?>" 
                   style="padding: 10px 15px; border: 1px solid #ddd; text-decoration: none; color: #333; border-radius: 4px;">
                    Suivant <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            
            <p style="margin-top: 15px; color: #666;">
                Page <?php echo $page; ?> sur <?php echo $total_pages; ?> 
                (<?php echo $total_articles; ?> article(s) au total)
            </p>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>