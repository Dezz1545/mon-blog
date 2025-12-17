<?php


require_once '../includes/config.php';
require_once '../classes/Database.php';
require_once '../classes/Newsletter.php';

// Vérifier l'authentification et les permissions
if(!isLoggedIn() || !isAdmin()) {
    $_SESSION['message'] = "Accès refusé. Admin requis.";
    $_SESSION['message_type'] = "error";
    header('Location: ../login.php');
    exit();
}

$newsletterClass = new Newsletter();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Traitement selon l'action
switch($action) {
    case 'send':
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $subject = sanitize($_POST['subject']);
            $content = $_POST['content'];
            
            if(empty($subject) || empty($content)) {
                $message = "Veuillez remplir tous les champs";
                $message_type = "error";
            } else {
                $result = $newsletterClass->sendNewsletter($subject, $content);
                
                $_SESSION['message'] = $result['message'];
                $_SESSION['message_type'] = "success";
                header('Location: newsletter.php');
                exit();
            }
        }
        
        $page_title = "Envoyer une newsletter";
        break;
        
    case 'export':
        $subscribers = $newsletterClass->exportEmails();
        
        // Générer le contenu CSV
        $csv_content = "Email,Date d'inscription\n";
        foreach($subscribers as $subscriber) {
            $csv_content .= '"' . $subscriber['email'] . '","' . $subscriber['subscribed_at'] . "\"\n";
        }
        
        // Forcer le téléchargement
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="newsletter_abonnes_' . date('Y-m-d') . '.csv"');
        echo $csv_content;
        exit();
        
    case 'unsubscribe':
        if(isset($_GET['id']) && is_numeric($_GET['id'])) {
            $subscriber_id = (int)$_GET['id'];
            $subscriber = Database::fetch("SELECT email FROM newsletter WHERE id = ?", [$subscriber_id]);
            
            if($subscriber) {
                $newsletterClass->unsubscribe($subscriber['email']);
                $_SESSION['message'] = "Abonné désinscrit";
                $_SESSION['message_type'] = "success";
            }
        }
        header('Location: newsletter.php');
        exit();
        
    case 'list':
    default:
        // Pagination
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $per_page = 50;
        
        // Récupérer les abonnés
        $subscribers = $newsletterClass->getSubscribers($per_page, ($page - 1) * $per_page);
        $total_subscribers = $newsletterClass->countSubscribers();
        $total_pages = ceil($total_subscribers / $per_page);
        
        $page_title = "Gestion newsletter";
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
                        <i class="fas fa-mail-bulk"></i> <?php echo $page_title; ?>
                    </h1>
                    <p style="color: #7f8c8d; margin: 0;">
                        Gérez votre liste d'abonnés et envoyez des newsletters
                    </p>
                </div>
                
                <?php if($action == 'list'): ?>
                <div>
                    <a href="newsletter.php?action=send" 
                       style="background: #3498db; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; margin-right: 10px;">
                        <i class="fas fa-paper-plane"></i> Envoyer
                    </a>
                    <a href="newsletter.php?action=export" 
                       style="background: #2ecc71; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-download"></i> Exporter
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if(isset($message)): ?>
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
        <?php if($action == 'send'): ?>
        <!-- Formulaire d'envoi -->
        <div style="max-width: 800px; margin: 0 auto;">
            <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="text-align: center; margin-bottom: 30px;">
                    <div style="width: 80px; height: 80px; background: #e8f4fc; color: #3498db; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                        <i class="fas fa-paper-plane" style="font-size: 2rem;"></i>
                    </div>
                    <h2 style="margin-bottom: 10px; color: #2c3e50;">Envoyer une newsletter</h2>
                    <p style="color: #7f8c8d;">Envoi simulé - En production, configurez un service d'emails</p>
                </div>
                
                <form method="POST">
                    <div style="display: grid; gap: 20px;">
                        <!-- Destinataires -->
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 6px;">
                            <div style="font-weight: 500; color: #2c3e50; margin-bottom: 10px;">
                                <i class="fas fa-users"></i> Destinataires
                            </div>
                            <div style="color: #7f8c8d;">
                                Cette newsletter sera envoyée à <strong><?php echo $total_subscribers; ?></strong> abonné<?php echo $total_subscribers > 1 ? 's' : ''; ?> actif<?php echo $total_subscribers > 1 ? 's' : ''; ?>
                            </div>
                        </div>
                        
                        <!-- Sujet -->
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #2c3e50;">
                                Sujet *
                            </label>
                            <input type="text" name="subject" required 
                                   placeholder="Sujet de votre newsletter"
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                        </div>
                        
                        <!-- Contenu -->
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #2c3e50;">
                                Contenu *
                            </label>
                            <textarea name="content" rows="15" required
                                      placeholder="Contenu de votre newsletter (HTML autorisé)"
                                      style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; font-family: monospace; resize: vertical;"></textarea>
                            <small style="color: #7f8c8d; display: block; margin-top: 5px;">
                                Vous pouvez utiliser du HTML basique pour la mise en forme
                            </small>
                        </div>
                        
                        <!-- Boutons -->
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 20px; border-top: 1px solid #eee;">
                            <a href="newsletter.php" 
                               style="padding: 12px 25px; border: 1px solid #ddd; border-radius: 6px; text-decoration: none; color: #7f8c8d;">
                                Annuler
                            </a>
                            <button type="submit" 
                                    style="background: #3498db; color: white; padding: 12px 30px; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer;">
                                <i class="fas fa-paper-plane"></i> Simuler l'envoi
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Liste des abonnés -->
        
        <!-- Statistiques -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center;">
                <div style="font-size: 2rem; font-weight: bold; color: #9b59b6; margin-bottom: 10px;">
                    <?php echo $total_subscribers; ?>
                </div>
                <div style="color: #7f8c8d;">Abonnés actifs</div>
            </div>
            
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center;">
                <div style="font-size: 2rem; font-weight: bold; color: #3498db; margin-bottom: 10px;">
                    <?php echo date('d/m/Y'); ?>
                </div>
                <div style="color: #7f8c8d;">Date du jour</div>
            </div>
            
            <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center;">
                <div style="font-size: 2rem; font-weight: bold; color: #2ecc71; margin-bottom: 10px;">
                    CSV
                </div>
                <div style="color: #7f8c8d;">Format d'export</div>
            </div>
        </div>
        
        <?php if(empty($subscribers)): ?>
        <div style="background: white; padding: 50px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center;">
            <i class="fas fa-users-slash" style="font-size: 3rem; color: #ddd; margin-bottom: 15px;"></i>
            <h3 style="color: #95a5a6; margin-bottom: 10px;">Aucun abonné</h3>
            <p style="color: #bdc3c7;">Aucun abonné à la newsletter pour le moment</p>
        </div>
        <?php else: ?>
        <div style="background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Email</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Date d'inscription</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Statut</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($subscribers as $subscriber): ?>
                    <tr style="border-bottom: 1px solid #f0f0f0;">
                        <td style="padding: 15px; color: #2c3e50;">
                            <?php echo $subscriber['email']; ?>
                        </td>
                        <td style="padding: 15px; color: #7f8c8d;">
                            <?php echo date('d/m/Y à H:i', strtotime($subscriber['subscribed_at'])); ?>
                        </td>
                        <td style="padding: 15px;">
                            <?php if($subscriber['is_active']): ?>
                            <span style="background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem;">
                                <i class="fas fa-check-circle"></i> Actif
                            </span>
                            <?php else: ?>
                            <span style="background: #f8d7da; color: #721c24; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem;">
                                <i class="fas fa-times-circle"></i> Désinscrit
                            </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px;">
                            <div style="display: flex; gap: 10px;">
                                <?php if($subscriber['is_active']): ?>
                                <a href="newsletter.php?action=unsubscribe&id=<?php echo $subscriber['id']; ?>" 
                                   onclick="return confirm('Désinscrire cet abonné ?')"
                                   style="color: #e74c3c; text-decoration: none;" title="Désinscrire">
                                    <i class="fas fa-user-minus"></i>
                                </a>
                                <?php endif; ?>
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
                    <a href="?page=<?php echo $page-1; ?>" 
                       style="padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; color: #7f8c8d; border-radius: 4px;">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" 
                       style="padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; color: <?php echo $i == $page ? 'white' : '#7f8c8d'; ?>; background: <?php echo $i == $page ? '#3498db' : 'white'; ?>; border-radius: 4px;">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?>" 
                       style="padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; color: #7f8c8d; border-radius: 4px;">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                
                <p style="margin-top: 10px; color: #7f8c8d; font-size: 0.9rem;">
                    Page <?php echo $page; ?> sur <?php echo $total_pages; ?> 
                    (<?php echo $total_subscribers; ?> abonné<?php echo $total_subscribers > 1 ? 's' : ''; ?>)
                </p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer-dashboard.php'; ?>