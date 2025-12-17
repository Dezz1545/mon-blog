<?php
require_once '../includes/config.php';
require_once '../classes/Database.php';
require_once '../classes/Comment.php';

// Vérifier l'authentification et les permissions
if(!isLoggedIn() || !isAdmin()) {
    $_SESSION['message'] = "Accès refusé. Admin requis.";
    $_SESSION['message_type'] = "error";
    header('Location: ../login.php');
    exit();
}

$commentClass = new Comment();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Traitement selon l'action
if(isset($_GET['approve'])) {
    $comment_id = (int)$_GET['approve'];
    $commentClass->approve($comment_id);
    $_SESSION['message'] = "Commentaire approuvé";
    $_SESSION['message_type'] = "success";
    header('Location: comments.php');
    exit();
}

if(isset($_GET['disapprove'])) {
    $comment_id = (int)$_GET['disapprove'];
    $commentClass->disapprove($comment_id);
    $_SESSION['message'] = "Commentaire désapprouvé";
    $_SESSION['message_type'] = "success";
    header('Location: comments.php');
    exit();
}

if(isset($_GET['delete'])) {
    $comment_id = (int)$_GET['delete'];
    if(isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
        $commentClass->delete($comment_id);
        $_SESSION['message'] = "Commentaire supprimé";
        $_SESSION['message_type'] = "success";
        header('Location: comments.php');
        exit();
    } else {
        $action = 'delete';
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;

// Construire les paramètres selon le filtre
$approved = null;
if($filter == 'approved') {
    $approved = 1;
} elseif($filter == 'pending') {
    $approved = 0;
}

// Récupérer les commentaires
$comments = $commentClass->getAll($per_page, ($page - 1) * $per_page, $approved);

// Compter le total
$total_comments = $commentClass->countAll();
$total_pages = ceil($total_comments / $per_page);

$pending_count = $commentClass->countPending();

$page_title = "Gestion des commentaires";
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
                        <i class="fas fa-comments"></i> Gestion des commentaires
                    </h1>
                    <p style="color: #7f8c8d; margin: 0;">
                        Modérez les commentaires des articles
                    </p>
                </div>
                
                <?php if($pending_count > 0): ?>
                <div style="background: #e74c3c; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold;">
                    <?php echo $pending_count; ?> en attente
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Messages -->
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
        
        <!-- Filtres -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <div style="display: flex; gap: 15px;">
                <a href="comments.php?filter=all" 
                   style="padding: 10px 20px; border-radius: 20px; text-decoration: none; 
                          background: <?php echo $filter == 'all' ? '#3498db' : '#f8f9fa'; ?>; 
                          color: <?php echo $filter == 'all' ? 'white' : '#7f8c8d'; ?>;">
                    Tous (<?php echo $total_comments; ?>)
                </a>
                <a href="comments.php?filter=pending" 
                   style="padding: 10px 20px; border-radius: 20px; text-decoration: none; 
                          background: <?php echo $filter == 'pending' ? '#e74c3c' : '#f8f9fa'; ?>; 
                          color: <?php echo $filter == 'pending' ? 'white' : '#7f8c8d'; ?>;">
                    En attente (<?php echo $pending_count; ?>)
                </a>
                <a href="comments.php?filter=approved" 
                   style="padding: 10px 20px; border-radius: 20px; text-decoration: none; 
                          background: <?php echo $filter == 'approved' ? '#2ecc71' : '#f8f9fa'; ?>; 
                          color: <?php echo $filter == 'approved' ? 'white' : '#7f8c8d'; ?>;">
                    Approuvés
                </a>
            </div>
        </div>
        
        <?php if($action == 'delete' && isset($_GET['delete'])): ?>
        <!-- Confirmation de suppression -->
        <div style="max-width: 600px; margin: 0 auto;">
            <div style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center;">
                <div style="width: 80px; height: 80px; background: #fdedec; color: #e74c3c; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem;"></i>
                </div>
                
                <h2 style="margin-bottom: 15px; color: #2c3e50;">Confirmer la suppression</h2>
                <p style="color: #7f8c8d; margin-bottom: 25px;">
                    Êtes-vous sûr de vouloir supprimer ce commentaire ?<br>
                    Cette action est irréversible.
                </p>
                
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <a href="comments.php" 
                       style="padding: 12px 30px; border: 1px solid #ddd; border-radius: 6px; text-decoration: none; color: #7f8c8d;">
                        Annuler
                    </a>
                    <a href="comments.php?delete=<?php echo (int)$_GET['delete']; ?>&confirm=yes" 
                       style="background: #e74c3c; color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none;">
                        Oui, supprimer
                    </a>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Liste des commentaires -->
        <?php if(empty($comments)): ?>
        <div style="background: white; padding: 50px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center;">
            <i class="fas fa-comment-slash" style="font-size: 3rem; color: #ddd; margin-bottom: 15px;"></i>
            <h3 style="color: #95a5a6; margin-bottom: 10px;">Aucun commentaire</h3>
            <p style="color: #bdc3c7;">Aucun commentaire ne correspond à ce filtre</p>
        </div>
        <?php else: ?>
        <div style="background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <div style="padding: 20px; border-bottom: 1px solid #eee; background: #f8f9fa;">
                <strong><?php echo count($comments); ?></strong> commentaire<?php echo count($comments) > 1 ? 's' : ''; ?> 
                <?php echo $filter == 'pending' ? 'en attente' : ($filter == 'approved' ? 'approuvés' : 'au total'); ?>
            </div>
            
            <div style="display: grid; gap: 0;">
                <?php foreach($comments as $comment): ?>
                <div style="padding: 20px; border-bottom: 1px solid #f0f0f0;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                        <div>
                            <div style="font-weight: 500; color: #2c3e50; margin-bottom: 5px;">
                                <?php echo htmlspecialchars($comment['username']); ?>
                                <span style="font-size: 0.85rem; color: #7f8c8d;">(<?php echo $comment['email']; ?>)</span>
                            </div>
                            <div style="font-size: 0.85rem; color: #7f8c8d;">
                                Sur l'article : 
                                <a href="../article.php?id=<?php echo $comment['article_id']; ?>" 
                                   style="color: #3498db; text-decoration: none;">
                                    <?php echo htmlspecialchars($comment['article_title']); ?>
                                </a>
                                • <?php echo date('d/m/Y à H:i', strtotime($comment['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                            <?php if(!$comment['is_approved']): ?>
                            <a href="comments.php?approve=<?php echo $comment['id']; ?>" 
                               style="color: #2ecc71; text-decoration: none;" title="Approuver">
                                <i class="fas fa-check"></i>
                            </a>
                            <?php else: ?>
                            <a href="comments.php?disapprove=<?php echo $comment['id']; ?>" 
                               style="color: #f39c12; text-decoration: none;" title="Désapprouver">
                                <i class="fas fa-times"></i>
                            </a>
                            <?php endif; ?>
                            <a href="comments.php?delete=<?php echo $comment['id']; ?>" 
                               onclick="return confirm('Supprimer ce commentaire ?')"
                               style="color: #e74c3c; text-decoration: none;" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 6px;">
                        <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                    </div>
                    
                    <?php if(!$comment['is_approved']): ?>
                    <div style="margin-top: 10px;">
                        <span style="background: #fff3cd; color: #856404; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem;">
                            <i class="fas fa-clock"></i> En attente de modération
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <div style="padding: 20px; border-top: 1px solid #eee; text-align: center;">
                <div style="display: inline-flex; gap: 5px;">
                    <?php if($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?><?php echo $filter != 'all' ? '&filter='.$filter : ''; ?>" 
                       style="padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; color: #7f8c8d; border-radius: 4px;">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo $filter != 'all' ? '&filter='.$filter : ''; ?>" 
                       style="padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; color: <?php echo $i == $page ? 'white' : '#7f8c8d'; ?>; background: <?php echo $i == $page ? '#3498db' : 'white'; ?>; border-radius: 4px;">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?><?php echo $filter != 'all' ? '&filter='.$filter : ''; ?>" 
                       style="padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; color: #7f8c8d; border-radius: 4px;">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer-dashboard.php'; ?>