<?php
require_once '../includes/config.php';
require_once '../classes/Database.php';

// Vérifier l'authentification et les permissions
if(!isLoggedIn() || !isAdmin()) {
    $_SESSION['message'] = "Accès refusé. Admin requis.";
    $_SESSION['message_type'] = "error";
    header('Location: ../login.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;

// Filtres
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; // all, read, unread
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Construire la requête
$where = [];
$params = [];

if($filter == 'read') {
    $where[] = "is_read = 1";
} elseif($filter == 'unread') {
    $where[] = "is_read = 0";
}

if(!empty($search)) {
    $where[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Compter le total
$total_result = Database::fetch(
    "SELECT COUNT(*) as total FROM contacts $where_sql",
    $params
);
$total_contacts = $total_result['total'];
$total_pages = ceil($total_contacts / $per_page);

// Récupérer les messages
$offset = ($page - 1) * $per_page;
$params[] = $per_page;
$params[] = $offset;

$contacts = Database::fetchAll("
    SELECT * FROM contacts 
    $where_sql
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
", $params);

// Compter les messages non lus
$unread_count = Database::fetch("SELECT COUNT(*) as count FROM contacts WHERE is_read = 0")['count'];

// Marquer comme lu
if(isset($_GET['mark_as_read']) && is_numeric($_GET['mark_as_read'])) {
    $contact_id = (int)$_GET['mark_as_read'];
    Database::update('contacts', ['is_read' => 1], 'id = ?', [$contact_id]);
    $_SESSION['message'] = "Message marqué comme lu";
    $_SESSION['message_type'] = "success";
    header('Location: contacts.php');
    exit();
}

// Supprimer un message
if(isset($_GET['delete'])) {
    $contact_id = (int)$_GET['delete'];
    if(isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
        Database::delete('contacts', 'id = ?', [$contact_id]);
        $_SESSION['message'] = "Message supprimé";
        $_SESSION['message_type'] = "success";
        header('Location: contacts.php');
        exit();
    }
}

$page_title = "Messages de contact";
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
                        <i class="fas fa-envelope"></i> Messages de contact
                    </h1>
                    <p style="color: #7f8c8d; margin: 0;">
                        Gérez les messages reçus via le formulaire de contact
                    </p>
                </div>
                
                <?php if($unread_count > 0): ?>
                <div style="background: #e74c3c; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold;">
                    <?php echo $unread_count; ?> non lu<?php echo $unread_count > 1 ? 's' : ''; ?>
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
            <form method="GET" style="display: grid; grid-template-columns: 1fr auto auto; gap: 15px;">
                <div>
                    <input type="text" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Rechercher un message..."
                           style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95rem;">
                </div>
                
                <div>
                    <select name="filter" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.95rem;">
                        <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>Tous les messages</option>
                        <option value="unread" <?php echo $filter == 'unread' ? 'selected' : ''; ?>>Non lus</option>
                        <option value="read" <?php echo $filter == 'read' ? 'selected' : ''; ?>>Lus</option>
                    </select>
                </div>
                
                <div>
                    <button type="submit" 
                            style="background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-size: 0.95rem; cursor: pointer;">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                    <a href="contacts.php" 
                       style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; text-decoration: none; color: #7f8c8d; margin-left: 10px;">
                        Réinitialiser
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Liste des messages -->
        <?php if(empty($contacts)): ?>
        <div style="background: white; padding: 50px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center;">
            <i class="fas fa-inbox" style="font-size: 3rem; color: #ddd; margin-bottom: 15px;"></i>
            <h3 style="color: #95a5a6; margin-bottom: 10px;">Aucun message</h3>
            <p style="color: #bdc3c7;">Aucun message ne correspond à ce filtre</p>
        </div>
        <?php else: ?>
        <div style="display: grid; gap: 15px;">
            <?php foreach($contacts as $contact): ?>
            <div style="background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden; 
                         border-left: 4px solid <?php echo $contact['is_read'] ? '#95a5a6' : '#3498db'; ?>;">
                <div style="padding: 20px;">
                    <!-- En-tête du message -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                <div style="font-weight: 500; color: #2c3e50;">
                                    <?php echo htmlspecialchars($contact['name']); ?>
                                </div>
                                <?php if(!$contact['is_read']): ?>
                                <span style="background: #3498db; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem;">
                                    Nouveau
                                </span>
                                <?php endif; ?>
                            </div>
                            <div style="color: #7f8c8d; font-size: 0.9rem;">
                                <i class="fas fa-envelope"></i> <?php echo $contact['email']; ?>
                                • <i class="far fa-clock"></i> <?php echo date('d/m/Y à H:i', strtotime($contact['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                            <?php if(!$contact['is_read']): ?>
                            <a href="contacts.php?mark_as_read=<?php echo $contact['id']; ?>" 
                               style="color: #2ecc71; text-decoration: none;" title="Marquer comme lu">
                                <i class="fas fa-check"></i>
                            </a>
                            <?php endif; ?>
                            <a href="contacts.php?delete=<?php echo $contact['id']; ?>" 
                               onclick="return confirm('Supprimer ce message ?')"
                               style="color: #e74c3c; text-decoration: none;" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Sujet -->
                    <?php if($contact['subject']): ?>
                    <div style="margin-bottom: 10px;">
                        <strong style="color: #2c3e50;">Sujet :</strong>
                        <span style="color: #7f8c8d;"><?php echo htmlspecialchars($contact['subject']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Message -->
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-top: 10px;">
                        <?php echo nl2br(htmlspecialchars($contact['message'])); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
        <div style="margin-top: 30px; text-align: center;">
            <div style="display: inline-flex; gap: 5px;">
                <?php if($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?><?php echo $filter != 'all' ? '&filter='.$filter : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
                   style="padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; color: #7f8c8d; border-radius: 4px;">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php endif; ?>
                
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?><?php echo $filter != 'all' ? '&filter='.$filter : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
                   style="padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; color: <?php echo $i == $page ? 'white' : '#7f8c8d'; ?>; background: <?php echo $i == $page ? '#3498db' : 'white'; ?>; border-radius: 4px;">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
                
                <?php if($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?><?php echo $filter != 'all' ? '&filter='.$filter : ''; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" 
                   style="padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; color: #7f8c8d; border-radius: 4px;">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            
            <p style="margin-top: 10px; color: #7f8c8d; font-size: 0.9rem;">
                Page <?php echo $page; ?> sur <?php echo $total_pages; ?> 
                (<?php echo $total_contacts; ?> message<?php echo $total_contacts > 1 ? 's' : ''; ?>)
            </p>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer-dashboard.php'; ?>