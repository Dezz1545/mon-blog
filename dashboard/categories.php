<?php
require_once '../includes/config.php';
require_once '../classes/Database.php';
require_once '../classes/Category.php';

// Vérifier l'authentification et les permissions
if(!isLoggedIn() || !isAdmin()) {
    $_SESSION['message'] = "Accès refusé. Admin requis.";
    $_SESSION['message_type'] = "error";
    header('Location: ../login.php');
    exit();
}

$categoryClass = new Category();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$message_type = '';

// Traitement selon l'action
switch($action) {
    case 'create':
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            
            $result = $categoryClass->create($name, $description);
            
            if($result['success']) {
                $_SESSION['message'] = "Catégorie créée avec succès !";
                $_SESSION['message_type'] = "success";
                header('Location: categories.php');
                exit();
            } else {
                $message = $result['error'];
                $message_type = "error";
            }
        }
        
        $page_title = "Créer une catégorie";
        break;
        
    case 'edit':
        if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            header('Location: categories.php');
            exit();
        }
        
        $category_id = (int)$_GET['id'];
        $category = $categoryClass->getCategory($category_id);
        
        if(!$category) {
            $_SESSION['message'] = "Catégorie non trouvée";
            $_SESSION['message_type'] = "error";
            header('Location: categories.php');
            exit();
        }
        
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            
            $result = $categoryClass->update($category_id, $name, $description);
            
            if($result['success']) {
                $_SESSION['message'] = "Catégorie mise à jour avec succès !";
                $_SESSION['message_type'] = "success";
                header('Location: categories.php');
                exit();
            } else {
                $message = $result['error'];
                $message_type = "error";
            }
        }
        
        $page_title = "Éditer la catégorie";
        break;
        
    case 'delete':
        if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            header('Location: categories.php');
            exit();
        }
        
        $category_id = (int)$_GET['id'];
        $category = $categoryClass->getCategory($category_id);
        
        if(!$category) {
            $_SESSION['message'] = "Catégorie non trouvée";
            $_SESSION['message_type'] = "error";
            header('Location: categories.php');
            exit();
        }
        
        if(isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
            $result = $categoryClass->delete($category_id);
            
            if($result['success']) {
                $_SESSION['message'] = $result['message'];
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = $result['error'];
                $_SESSION['message_type'] = "error";
            }
            
            header('Location: categories.php');
            exit();
        }
        
        $page_title = "Confirmer la suppression";
        break;
        
    case 'list':
    default:
        $categories = $categoryClass->getAllWithCount();
        $page_title = "Gestion des catégories";
        break;
}

include '../includes/header.php';
?>

<div style="min-height: 100vh; background: #f8f9fa;">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    
    <!-- Contenu principal -->
    <div style="margin-left: 250px; padding: 30px;">
        <!-- En-tête -->
        <div style="margin-bottom: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="margin: 0 0 10px; color: #2c3e50;">
                        <i class="fas fa-folder"></i> <?php echo $page_title; ?>
                    </h1>
                    <p style="color: #7f8c8d; margin: 0;">
                        <?php echo $action == 'list' ? "Gérez les catégories de votre blog" : ""; ?>
                    </p>
                </div>
                
                <?php if($action == 'list'): ?>
                <a href="categories.php?action=create" 
                   style="background: #2ecc71; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-plus"></i> Nouvelle catégorie
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
        <div style="max-width: 600px; margin: 0 auto;">
            <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <form method="POST">
                    <div style="display: grid; gap: 20px;">
                        <!-- Nom -->
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #2c3e50;">
                                Nom de la catégorie *
                            </label>
                            <input type="text" name="name" required 
                                   value="<?php echo isset($category) ? htmlspecialchars($category['name']) : ''; ?>"
                                   placeholder="Ex: Technologie, Programmation, Design..."
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                            <small style="color: #7f8c8d; display: block; margin-top: 5px;">
                                Minimum 3 caractères
                            </small>
                        </div>
                        
                        <!-- Description -->
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #2c3e50;">
                                Description (optionnel)
                            </label>
                            <textarea name="description" rows="4"
                                      placeholder="Description de la catégorie..."
                                      style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; resize: vertical;"><?php echo isset($category) ? htmlspecialchars($category['description']) : ''; ?></textarea>
                        </div>
                        
                        <!-- Boutons -->
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 20px; border-top: 1px solid #eee;">
                            <a href="categories.php" 
                               style="padding: 12px 25px; border: 1px solid #ddd; border-radius: 6px; text-decoration: none; color: #7f8c8d;">
                                Annuler
                            </a>
                            <button type="submit" 
                                    style="background: #2ecc71; color: white; padding: 12px 30px; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer;">
                                <?php echo $action == 'create' ? 'Créer la catégorie' : 'Mettre à jour'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php elseif($action == 'delete' && isset($category)): ?>
        <!-- Confirmation de suppression -->
        <div style="max-width: 600px; margin: 0 auto;">
            <div style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center;">
                <div style="width: 80px; height: 80px; background: #fdedec; color: #e74c3c; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem;"></i>
                </div>
                
                <h2 style="margin-bottom: 15px; color: #2c3e50;">Confirmer la suppression</h2>
                <p style="color: #7f8c8d; margin-bottom: 25px;">
                    Êtes-vous sûr de vouloir supprimer la catégorie "<strong><?php echo htmlspecialchars($category['name']); ?></strong>" ?<br>
                    <?php
                    $article_count = Database::fetch("SELECT COUNT(*) as count FROM articles WHERE category_id = ?", [$category_id])['count'];
                    if($article_count > 0): ?>
                    <span style="color: #e74c3c; font-weight: bold;">
                        Attention : Cette catégorie contient <?php echo $article_count; ?> article<?php echo $article_count > 1 ? 's' : ''; ?> !
                    </span>
                    <?php endif; ?>
                </p>
                
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <a href="categories.php" 
                       style="padding: 12px 30px; border: 1px solid #ddd; border-radius: 6px; text-decoration: none; color: #7f8c8d;">
                        Annuler
                    </a>
                    <a href="categories.php?action=delete&id=<?php echo $category_id; ?>&confirm=yes" 
                       style="background: #e74c3c; color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none;">
                        Oui, supprimer
                    </a>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Liste des catégories -->
        <?php if(empty($categories)): ?>
        <div style="background: white; padding: 50px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center;">
            <i class="fas fa-folder" style="font-size: 3rem; color: #ddd; margin-bottom: 15px;"></i>
            <h3 style="color: #95a5a6; margin-bottom: 10px;">Aucune catégorie</h3>
            <p style="color: #bdc3c7;">Commencez par créer votre première catégorie</p>
        </div>
        <?php else: ?>
        <div style="background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Nom</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Description</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Articles</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Créée le</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($categories as $cat): ?>
                    <tr style="border-bottom: 1px solid #f0f0f0;">
                        <td style="padding: 15px;">
                            <div style="font-weight: 500; color: #2c3e50;"><?php echo htmlspecialchars($cat['name']); ?></div>
                            <div style="font-size: 0.85rem; color: #7f8c8d;">/<?php echo $cat['slug']; ?></div>
                        </td>
                        <td style="padding: 15px; color: #7f8c8d;">
                            <?php echo $cat['description'] ? htmlspecialchars(substr($cat['description'], 0, 100)) . (strlen($cat['description']) > 100 ? '...' : '') : '<span style="color: #bdc3c7;">-</span>'; ?>
                        </td>
                        <td style="padding: 15px;">
                            <span style="background: #e0e0e0; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem;">
                                <?php echo $cat['article_count']; ?>
                            </span>
                        </td>
                        <td style="padding: 15px; color: #7f8c8d;">
                            <?php echo date('d/m/Y', strtotime($cat['created_at'])); ?>
                        </td>
                        <td style="padding: 15px;">
                            <div style="display: flex; gap: 10px;">
                                <a href="categories.php?action=edit&id=<?php echo $cat['id']; ?>" 
                                   style="color: #3498db; text-decoration: none;" title="Éditer">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="categories.php?action=delete&id=<?php echo $cat['id']; ?>" 
                                   onclick="return confirm('Supprimer cette catégorie ?')"
                                   style="color: #e74c3c; text-decoration: none;" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer-dashboard.php'; ?>