<?php
require_once '../includes/config.php';
require_once '../classes/Database.php';
require_once '../classes/Article.php';
require_once '../classes/Category.php';

// Vérifier l'authentification et les permissions
if(!isLoggedIn() || (!isAdmin() && $_SESSION['role'] !== 'author')) {
    $_SESSION['message'] = "Accès refusé. Permissions insuffisantes.";
    $_SESSION['message_type'] = "error";
    header('Location: ../login.php');
    exit();
}

$articleClass = new Article();
$categoryClass = new Category();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$message_type = '';

// Traitement selon l'action
switch($action) {
    case 'create':
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'title' => sanitize($_POST['title']),
                'content' => $_POST['content'],
                'excerpt' => sanitize($_POST['excerpt']),
                'category_id' => (int)$_POST['category_id'],
                'user_id' => $_SESSION['user_id'],
                'published' => isset($_POST['published']) ? 1 : 0
            ];
            
            // Gestion de l'upload d'image
            $image = null;
            if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $image_name = uploadImage($_FILES['image'], 'articles');
                if($image_name) {
                    $image = $image_name;
                }
            }
            
            $result = $articleClass->create($data, $image);
            
            if($result['success']) {
                $_SESSION['message'] = "Article créé avec succès !";
                $_SESSION['message_type'] = "success";
                header('Location: articles.php');
                exit();
            } else {
                $message = implode('<br>', $result['errors']);
                $message_type = "error";
            }
        }
        
        $categories = $categoryClass->getAll();
        $page_title = "Créer un article";
        break;
        
    case 'edit':
        if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            header('Location: articles.php');
            exit();
        }
        
        $article_id = (int)$_GET['id'];
        $article = $articleClass->getArticle($article_id);
        
        // Vérifier les permissions (admin ou auteur de l'article)
        if(!$article || (!isAdmin() && $article['user_id'] != $_SESSION['user_id'])) {
            $_SESSION['message'] = "Article non trouvé ou permissions insuffisantes";
            $_SESSION['message_type'] = "error";
            header('Location: articles.php');
            exit();
        }
        
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'title' => sanitize($_POST['title']),
                'content' => $_POST['content'],
                'excerpt' => sanitize($_POST['excerpt']),
                'category_id' => (int)$_POST['category_id'],
                'published' => isset($_POST['published']) ? 1 : 0
            ];
            
            // Gestion de l'upload d'image
            $image = null;
            if(isset($_POST['remove_image']) && $_POST['remove_image'] == 1) {
                $image = '';
            } elseif(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $image_name = uploadImage($_FILES['image'], 'articles');
                if($image_name) {
                    $image = $image_name;
                }
            }
            
            $result = $articleClass->update($article_id, $data, $image);
            
            if($result['success']) {
                $_SESSION['message'] = "Article mis à jour avec succès !";
                $_SESSION['message_type'] = "success";
                header('Location: articles.php');
                exit();
            } else {
                $message = implode('<br>', $result['errors']);
                $message_type = "error";
            }
        }
        
        $categories = $categoryClass->getAll();
        $page_title = "Éditer l'article";
        break;
        
    case 'delete':
        if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            header('Location: articles.php');
            exit();
        }
        
        $article_id = (int)$_GET['id'];
        $article = $articleClass->getArticle($article_id);
        
        // Vérifier les permissions
        if(!$article || (!isAdmin() && $article['user_id'] != $_SESSION['user_id'])) {
            $_SESSION['message'] = "Permissions insuffisantes";
            $_SESSION['message_type'] = "error";
            header('Location: articles.php');
            exit();
        }
        
        if(isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
            $articleClass->delete($article_id);
            $_SESSION['message'] = "Article supprimé avec succès";
            $_SESSION['message_type'] = "success";
            header('Location: articles.php');
            exit();
        }
        
        $page_title = "Confirmer la suppression";
        break;
        
    case 'list':
    default:
        // Pagination
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $per_page = 10;
        
        // Filtres
        $category_filter = isset($_GET['category']) ? (int)$_GET['category'] : null;
        $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
        $search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
        
        // Construire la requête
        $where = [];
        $params = [];
        
        if($category_filter) {
            $where[] = "a.category_id = ?";
            $params[] = $category_filter;
        }
        
        if($status_filter == 'published') {
            $where[] = "a.published = 1";
        } elseif($status_filter == 'draft') {
            $where[] = "a.published = 0";
        }
        
        if(!empty($search)) {
            $where[] = "(a.title LIKE ? OR a.content LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        // Pour les auteurs, ne voir que leurs articles
        if(!isAdmin()) {
            $where[] = "a.user_id = ?";
            $params[] = $_SESSION['user_id'];
        }
        
        $where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        // Compter le total
        $total_result = Database::fetch(
            "SELECT COUNT(*) as total FROM articles a $where_sql",
            $params
        );
        $total_articles = $total_result['total'];
        $total_pages = ceil($total_articles / $per_page);
        
        // Récupérer les articles
        $offset = ($page - 1) * $per_page;
        $params[] = $per_page;
        $params[] = $offset;
        
        $articles = Database::fetchAll("
            SELECT a.*, c.name as category_name, u.username 
            FROM articles a 
            LEFT JOIN categories c ON a.category_id = c.id 
            LEFT JOIN users u ON a.user_id = u.id 
            $where_sql
            ORDER BY a.created_at DESC 
            LIMIT ? OFFSET ?
        ", $params);
        
        $categories = $categoryClass->getAll();
        $page_title = "Gestion des articles";
        break;
}

// Fonction d'upload d'image
function uploadImage($file, $type = 'articles') {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if(!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    if($file['size'] > $max_size) {
        return false;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $upload_dir = "../uploads/$type/";
    
    // Créer le dossier si nécessaire
    if(!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $destination = $upload_dir . $filename;
    
    if(move_uploaded_file($file['tmp_name'], $destination)) {
        return $filename;
    }
    
    return false;
}

include '../includes/header.php';
?>

<div style="min-height: 100vh; background: #f8f9fa;">
    <!-- Sidebar (identique à dashboard/index.php) -->
    <?php include 'sidebar.php'; ?>
    
    <!-- Contenu principal -->
    <div style="margin-left: 250px; padding: 30px;">
        <!-- En-tête -->
        <div style="margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="margin: 0 0 10px; color: #2c3e50;">
                        <i class="fas fa-newspaper"></i> <?php echo $page_title; ?>
                    </h1>
                    <p style="color: #7f8c8d; margin: 0;">
                        <?php echo $action == 'list' ? "Gérez vos articles publiés et brouillons" : ""; ?>
                    </p>
                </div>
                
                <?php if($action == 'list'): ?>
                <a href="articles.php?action=create" 
                   style="background: #3498db; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-plus"></i> Nouvel article
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if($message): ?>
        <div style="padding: 15px; background: <?php echo $message_type == 'error' ? '#f8d7da' : '#d4edda'; ?>; 
                    color: <?php echo $message_type == 'error' ? '#721c24' : '#155724'; ?>; 
                    border-radius: 6px; margin-bottom: 30px;">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['message'])): ?>
        <div style="padding: 15px; background: <?php echo $_SESSION['message_type'] == 'error' ? '#f8d7da' : '#d4edda'; ?>; 
                    color: <?php echo $_SESSION['message_type'] == 'error' ? '#721c24' : '#155724'; ?>; 
                    border-radius: 6px; margin-bottom: 30px;">
            <?php 
            echo $_SESSION['message'];
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        </div>
        <?php endif; ?>
        
        <!-- Contenu selon l'action -->
        <?php if($action == 'create' || $action == 'edit'): ?>
        <!-- Formulaire création/édition -->
        <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <form method="POST" enctype="multipart/form-data">
                <div style="display: grid; gap: 25px;">
                    <!-- Titre -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #2c3e50;">
                            Titre *
                        </label>
                        <input type="text" name="title" required 
                               value="<?php echo isset($article) ? htmlspecialchars($article['title']) : ''; ?>"
                               style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                    </div>
                    
                    <!-- Catégorie et image -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #2c3e50;">
                                Catégorie *
                            </label>
                            <select name="category_id" required 
                                    style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                                <option value="">Sélectionnez une catégorie</option>
                                <?php foreach($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"
                                    <?php echo (isset($article) && $article['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo $category['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #2c3e50;">
                                Image (optionnel)
                            </label>
                            <input type="file" name="image" accept="image/*"
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                            
                            <?php if(isset($article) && $article['image']): ?>
                            <div style="margin-top: 10px;">
                                <img src="../uploads/articles/<?php echo $article['image']; ?>" 
                                     alt="Image actuelle" 
                                     style="max-width: 150px; border-radius: 4px;">
                                <div style="margin-top: 10px;">
                                    <label style="display: inline-flex; align-items: center; gap: 5px;">
                                        <input type="checkbox" name="remove_image" value="1">
                                        Supprimer l'image actuelle
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Extrait -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #2c3e50;">
                            Extrait
                        </label>
                        <textarea name="excerpt" rows="3"
                                  placeholder="Résumé de l'article (optionnel)"
                                  style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; resize: vertical;"><?php echo isset($article) ? htmlspecialchars($article['excerpt']) : ''; ?></textarea>
                        <small style="color: #7f8c8d;">Ce texte s'affichera dans la liste des articles</small>
                    </div>
                    
                    <!-- Contenu -->
                    <div>
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #2c3e50;">
                            Contenu *
                        </label>
                        <textarea name="content" id="editor" rows="15" required
                                  style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; resize: vertical; font-family: monospace;"><?php echo isset($article) ? htmlspecialchars($article['content']) : ''; ?></textarea>
                    </div>
                    
                    <!-- Options -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 20px; border-top: 1px solid #eee;">
                        <label style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" name="published" value="1" 
                                   <?php echo (!isset($article) || $article['published']) ? 'checked' : ''; ?>>
                            <span>Publier immédiatement</span>
                        </label>
                        
                        <div style="display: flex; gap: 10px;">
                            <a href="articles.php" 
                               style="padding: 12px 25px; border: 1px solid #ddd; border-radius: 6px; text-decoration: none; color: #7f8c8d;">
                                Annuler
                            </a>
                            <button type="submit" 
                                    style="background: #3498db; color: white; padding: 12px 30px; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer;">
                                <?php echo $action == 'create' ? 'Créer l\'article' : 'Mettre à jour'; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Éditeur simple -->
        <script>
        document.getElementById('editor').addEventListener('keydown', function(e) {
            if(e.key === 'Tab') {
                e.preventDefault();
                var start = this.selectionStart;
                var end = this.selectionEnd;
                
                // Insert tab
                this.value = this.value.substring(0, start) + '    ' + this.value.substring(end);
                
                // Place cursor after tab
                this.selectionStart = this.selectionEnd = start + 4;
            }
        });
        </script>
        
        <?php elseif($action == 'delete' && isset($article)): ?>
        <!-- Confirmation de suppression -->
        <div style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center;">
            <div style="width: 80px; height: 80px; background: #fdedec; color: #e74c3c; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 2rem;"></i>
            </div>
            
            <h2 style="margin-bottom: 15px; color: #2c3e50;">Confirmer la suppression</h2>
            <p style="color: #7f8c8d; margin-bottom: 25px; max-width: 500px; margin-left: auto; margin-right: auto;">
                Êtes-vous sûr de vouloir supprimer l'article "<strong><?php echo htmlspecialchars($article['title']); ?></strong>" ?<br>
                Cette action est irréversible et supprimera également tous les commentaires associés.
            </p>
            
            <div style="display: flex; gap: 15px; justify-content: center;">
                <a href="articles.php" 
                   style="padding: 12px 30px; border: 1px solid #ddd; border-radius: 6px; text-decoration: none; color: #7f8c8d;">
                    Annuler
                </a>
                <a href="articles.php?action=delete&id=<?php echo $article_id; ?>&confirm=yes" 
                   style="background: #e74c3c; color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none;">
                    Oui, supprimer
                </a>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Liste des articles -->
        
        <!-- Filtres -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form method="GET" style="display: grid; grid-template-columns: 1fr auto auto auto; gap: 15px;">
                <input type="hidden" name="action" value="list">
                
                <div>
                    <input type="text" name="search" 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                           placeholder="Rechercher un article..."
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95rem;">
                </div>
                
                <div>
                    <select name="category" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95rem;">
                        <option value="">Toutes catégories</option>
                        <?php foreach($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                            <?php echo isset($_GET['category']) && $_GET['category'] == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo $category['name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95rem;">
                        <option value="">Tous les statuts</option>
                        <option value="published" <?php echo isset($_GET['status']) && $_GET['status'] == 'published' ? 'selected' : ''; ?>>
                            Publiés
                        </option>
                        <option value="draft" <?php echo isset($_GET['status']) && $_GET['status'] == 'draft' ? 'selected' : ''; ?>>
                            Brouillons
                        </option>
                    </select>
                </div>
                
                <div>
                    <button type="submit" 
                            style="background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-size: 0.95rem; cursor: pointer;">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                    <a href="articles.php" 
                       style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; text-decoration: none; color: #7f8c8d; margin-left: 10px;">
                        Réinitialiser
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Résultats -->
        <div style="background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <?php if(empty($articles)): ?>
            <div style="padding: 50px; text-align: center; color: #95a5a6;">
                <i class="fas fa-newspaper" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                <p style="margin: 0;">Aucun article trouvé</p>
            </div>
            <?php else: ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Titre</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Catégorie</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Auteur</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Date</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Statut</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($articles as $article): ?>
                    <tr style="border-bottom: 1px solid #f0f0f0;">
                        <td style="padding: 15px;">
                            <a href="../article.php?id=<?php echo $article['id']; ?>" 
                               style="color: #3498db; text-decoration: none; font-weight: 500;">
                                <?php echo htmlspecialchars(substr($article['title'], 0, 50)); ?>
                                <?php echo strlen($article['title']) > 50 ? '...' : ''; ?>
                            </a>
                        </td>
                        <td style="padding: 15px; color: #7f8c8d;">
                            <?php echo $article['category_name']; ?>
                        </td>
                        <td style="padding: 15px; color: #7f8c8d;">
                            <?php echo $article['username']; ?>
                        </td>
                        <td style="padding: 15px; color: #7f8c8d;">
                            <?php echo date('d/m/Y', strtotime($article['created_at'])); ?>
                        </td>
                        <td style="padding: 15px;">
                            <?php if($article['published']): ?>
                            <span style="background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem;">
                                <i class="fas fa-check-circle"></i> Publié
                            </span>
                            <?php else: ?>
                            <span style="background: #fff3cd; color: #856404; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem;">
                                <i class="fas fa-clock"></i> Brouillon
                            </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px;">
                            <div style="display: flex; gap: 10px;">
                                <a href="articles.php?action=edit&id=<?php echo $article['id']; ?>" 
                                   style="color: #3498db; text-decoration: none;" title="Éditer">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="../article.php?id=<?php echo $article['id']; ?>" 
                                   style="color: #2ecc71; text-decoration: none;" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="articles.php?action=delete&id=<?php echo $article['id']; ?>" 
                                   onclick="return confirm('Supprimer cet article ?')"
                                   style="color: #e74c3c; text-decoration: none;" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <div style="padding: 20px; border-top: 1px solid #eee; text-align: center;">
                <div style="display: inline-flex; gap: 5px;">
                    <?php if($page > 1): ?>
                    <a href="?action=list&page=<?php echo $page-1; ?><?php echo isset($_GET['category']) ? '&category='.$_GET['category'] : ''; ?><?php echo isset($_GET['status']) ? '&status='.$_GET['status'] : ''; ?><?php echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : ''; ?>" 
                       style="padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; color: #7f8c8d; border-radius: 4px;">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?action=list&page=<?php echo $i; ?><?php echo isset($_GET['category']) ? '&category='.$_GET['category'] : ''; ?><?php echo isset($_GET['status']) ? '&status='.$_GET['status'] : ''; ?><?php echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : ''; ?>" 
                       style="padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; color: <?php echo $i == $page ? 'white' : '#7f8c8d'; ?>; background: <?php echo $i == $page ? '#3498db' : 'white'; ?>; border-radius: 4px;">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_pages): ?>
                    <a href="?action=list&page=<?php echo $page+1; ?><?php echo isset($_GET['category']) ? '&category='.$_GET['category'] : ''; ?><?php echo isset($_GET['status']) ? '&status='.$_GET['status'] : ''; ?><?php echo isset($_GET['search']) ? '&search='.urlencode($_GET['search']) : ''; ?>" 
                       style="padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; color: #7f8c8d; border-radius: 4px;">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                
                <p style="margin-top: 10px; color: #7f8c8d; font-size: 0.9rem;">
                    Page <?php echo $page; ?> sur <?php echo $total_pages; ?> 
                    (<?php echo $total_articles; ?> article<?php echo $total_articles > 1 ? 's' : ''; ?>)
                </p>
            </div>
            <?php endif; ?>
            
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer-dashboard.php'; ?>