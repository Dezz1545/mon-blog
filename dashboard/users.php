<?php
require_once '../includes/config.php';
require_once '../classes/Database.php';
require_once '../classes/User.php';

// Vérifier l'authentification et les permissions
if(!isLoggedIn() || !isAdmin()) {
    $_SESSION['message'] = "Accès refusé. Admin requis.";
    $_SESSION['message_type'] = "error";
    header('Location: ../login.php');
    exit();
}

$userClass = new User();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Traitement selon l'action
switch($action) {
    case 'create':
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = sanitize($_POST['username']);
            $email = sanitize($_POST['email']);
            $password = $_POST['password'];
            $role = sanitize($_POST['role']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            $result = $userClass->register($username, $email, $password, $role);
            
            if($result['success']) {
                // Activer/désactiver si nécessaire
                if(!$is_active) {
                    $userClass->deactivateUser($result['user_id']);
                }
                
                $_SESSION['message'] = "Utilisateur créé avec succès !";
                $_SESSION['message_type'] = "success";
                header('Location: users.php');
                exit();
            } else {
                $message = implode('<br>', $result['errors']);
                $message_type = "error";
            }
        }
        
        $page_title = "Créer un utilisateur";
        break;
        
    case 'edit':
        if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            header('Location: users.php');
            exit();
        }
        
        $user_id = (int)$_GET['id'];
        $user = $userClass->getUser($user_id);
        
        if(!$user) {
            $_SESSION['message'] = "Utilisateur non trouvé";
            $_SESSION['message_type'] = "error";
            header('Location: users.php');
            exit();
        }
        
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $username = sanitize($_POST['username']);
            $email = sanitize($_POST['email']);
            $role = sanitize($_POST['role']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Préparer les données de mise à jour
            $update_data = [
                'username' => $username,
                'email' => $email,
                'role' => $role
            ];
            
            // Mettre à jour le mot de passe si fourni
            if(!empty($_POST['password'])) {
                $password_errors = $userClass->validatePassword($_POST['password']);
                if(empty($password_errors)) {
                    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $update_data['password'] = $hashed_password;
                } else {
                    $message = implode('<br>', $password_errors);
                    $message_type = "error";
                }
            }
            
            if(!isset($message)) {
                $userClass->updateProfile($user_id, $update_data);
                
                // Activer/désactiver
                if($is_active) {
                    $userClass->activateUser($user_id);
                } else {
                    $userClass->deactivateUser($user_id);
                }
                
                $_SESSION['message'] = "Utilisateur mis à jour avec succès !";
                $_SESSION['message_type'] = "success";
                header('Location: users.php');
                exit();
            }
        }
        
        $page_title = "Éditer l'utilisateur";
        break;
        
    case 'toggle_active':
        if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            header('Location: users.php');
            exit();
        }
        
        $user_id = (int)$_GET['id'];
        $user = $userClass->getUser($user_id);
        
        if($user) {
            if($user['is_active']) {
                $userClass->deactivateUser($user_id);
                $_SESSION['message'] = "Utilisateur désactivé";
            } else {
                $userClass->activateUser($user_id);
                $_SESSION['message'] = "Utilisateur activé";
            }
            $_SESSION['message_type'] = "success";
        }
        
        header('Location: users.php');
        exit();
        
    case 'delete':
        if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            header('Location: users.php');
            exit();
        }
        
        $user_id = (int)$_GET['id'];
        $user = $userClass->getUser($user_id);
        
        // Ne pas permettre de se supprimer soi-même
        if($user && $user['id'] != $_SESSION['user_id']) {
            if(isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
                Database::delete('users', 'id = ?', [$user_id]);
                $_SESSION['message'] = "Utilisateur supprimé";
                $_SESSION['message_type'] = "success";
                header('Location: users.php');
                exit();
            }
        } else {
            $_SESSION['message'] = "Vous ne pouvez pas supprimer votre propre compte";
            $_SESSION['message_type'] = "error";
            header('Location: users.php');
            exit();
        }
        
        $page_title = "Confirmer la suppression";
        break;
        
    case 'list':
    default:
        // Pagination
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $per_page = 20;
        
        // Récupérer les utilisateurs
        $users = $userClass->getAllUsers($per_page, ($page - 1) * $per_page);
        $total_users = $userClass->countUsers();
        $total_pages = ceil($total_users / $per_page);
        
        $page_title = "Gestion des utilisateurs";
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
                        <i class="fas fa-users"></i> <?php echo $page_title; ?>
                    </h1>
                    <p style="color: #7f8c8d; margin: 0;">
                        <?php echo $action == 'list' ? "Gérez les utilisateurs du site" : ""; ?>
                    </p>
                </div>
                
                <?php if($action == 'list') { ?>
                <a href="users.php?action=create" 
                   style="background: #3498db; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-plus"></i> Nouvel utilisateur
                </a>
                <?php } ?>
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
        <?php if($action == 'create' || $action == 'edit'): ?>
        <!-- Formulaire création/édition -->
        <div style="max-width: 600px; margin: 0 auto;">
            <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <form method="POST">
                    <div style="display: grid; gap: 20px;">
                        <!-- Username -->
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #2c3e50;">
                                Nom d'utilisateur *
                            </label>
                            <input type="text" name="username" required minlength="3"
                                   value="<?php echo isset($user) ? htmlspecialchars($user['username']) : ''; ?>"
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                        </div>
                        
                        <!-- Email -->
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #2c3e50;">
                                Email *
                            </label>
                            <input type="email" name="email" required
                                   value="<?php echo isset($user) ? htmlspecialchars($user['email']) : ''; ?>"
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                        </div>
                        
                        <!-- Mot de passe -->
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #2c3e50;">
                                Mot de passe <?php echo $action == 'create' ? '*' : '(laisser vide pour ne pas changer)'; ?>
                            </label>
                            <input type="password" name="password" 
                                   <?php echo $action == 'create' ? 'required' : ''; ?>
                                   minlength="8"
                                   style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                            <small style="color: #7f8c8d; display: block; margin-top: 5px;">
                                8 caractères minimum, 1 majuscule, 1 chiffre
                            </small>
                        </div>
                        
                        <!-- Rôle -->
                        <div>
                            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #2c3e50;">
                                Rôle *
                            </label>
                            <select name="role" required
                                    style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem;">
                                <option value="user" <?php echo (isset($user) && $user['role'] == 'user') ? 'selected' : ''; ?>>Utilisateur</option>
                                <option value="author" <?php echo (isset($user) && $user['role'] == 'author') ? 'selected' : ''; ?>>Auteur</option>
                                <option value="admin" <?php echo (isset($user) && $user['role'] == 'admin') ? 'selected' : ''; ?>>Administrateur</option>
                            </select>
                        </div>
                        
                        <!-- Statut -->
                        <div>
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="is_active" value="1" 
                                       <?php echo (!isset($user) || $user['is_active']) ? 'checked' : ''; ?>>
                                <span>Compte actif</span>
                            </label>
                        </div>
                        
                        <!-- Boutons -->
                        <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 20px; border-top: 1px solid #eee;">
                            <a href="users.php" 
                               style="padding: 12px 25px; border: 1px solid #ddd; border-radius: 6px; text-decoration: none; color: #7f8c8d;">
                                Annuler
                            </a>
                            <button type="submit" 
                                    style="background: #3498db; color: white; padding: 12px 30px; border: none; border-radius: 6px; font-size: 1rem; cursor: pointer;">
                                <?php echo $action == 'create' ? 'Créer l\'utilisateur' : 'Mettre à jour'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php elseif($action == 'delete' && isset($user)): ?>
        <!-- Confirmation de suppression -->
        <div style="max-width: 600px; margin: 0 auto;">
            <div style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center;">
                <div style="width: 80px; height: 80px; background: #fdedec; color: #e74c3c; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem;"></i>
                </div>
                
                <h2 style="margin-bottom: 15px; color: #2c3e50;">Confirmer la suppression</h2>
                <p style="color: #7f8c8d; margin-bottom: 25px;">
                    Êtes-vous sûr de vouloir supprimer l'utilisateur "<strong><?php echo htmlspecialchars($user['username']); ?></strong>" ?<br>
                    Cette action est irréversible.
                </p>
                
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <a href="users.php" 
                       style="padding: 12px 30px; border: 1px solid #ddd; border-radius: 6px; text-decoration: none; color: #7f8c8d;">
                        Annuler
                    </a>
                    <a href="users.php?action=delete&id=<?php echo $user_id; ?>&confirm=yes" 
                       style="background: #e74c3c; color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none;">
                        Oui, supprimer
                    </a>
                </div>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Liste des utilisateurs -->
        <?php if(empty($users)): ?>
        <div style="background: white; padding: 50px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center;">
            <i class="fas fa-users-slash" style="font-size: 3rem; color: #ddd; margin-bottom: 15px;"></i>
            <h3 style="color: #95a5a6; margin-bottom: 10px;">Aucun utilisateur</h3>
            <p style="color: #bdc3c7;">Commencez par créer votre premier utilisateur</p>
        </div>
        <?php else: ?>
        <div style="background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Utilisateur</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Email</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Rôle</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Statut</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Date</th>
                        <th style="padding: 15px; text-align: left; border-bottom: 1px solid #eee; color: #2c3e50;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $usr) { ?>
                    <tr style="border-bottom: 1px solid #f0f0f0;">
                        <td style="padding: 15px;">
                            <div style="font-weight: 500; color: #2c3e50;"><?php echo htmlspecialchars($usr['username']); ?></div>
                            <?php if($usr['id'] == $_SESSION['user_id']): ?>
                            <span style="font-size: 0.85rem; color: #3498db;">(Vous)</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px; color: #7f8c8d;"><?php echo $usr['email']; ?></td>
                        <td style="padding: 15px;">
                            <span style="background: <?php echo $usr['role'] == 'admin' ? '#e74c3c' : ($usr['role'] == 'author' ? '#3498db' : '#95a5a6'); ?>; 
                                  color: white; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem;">
                                <?php echo $usr['role']; ?>
                            </span>
                        </td>
                        <td style="padding: 15px;">
                            <?php if($usr['is_active']): ?>
                            <a href="users.php?action=toggle_active&id=<?php echo $usr['id']; ?>" 
                               style="background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem; text-decoration: none;">
                                <i class="fas fa-check-circle"></i> Actif
                            </a>
                            <?php else: ?>
                            <a href="users.php?action=toggle_active&id=<?php echo $usr['id']; ?>" 
                               style="background: #f8d7da; color: #721c24; padding: 4px 10px; border-radius: 12px; font-size: 0.85rem; text-decoration: none;">
                                <i class="fas fa-times-circle"></i> Inactif
                            </a>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px; color: #7f8c8d;">
                            <?php echo date('d/m/Y', strtotime($usr['created_at'])); ?>
                        </td>
                        <td style="padding: 15px;">
                            <div style="display: flex; gap: 10px;">
                                <a href="users.php?action=edit&id=<?php echo $usr['id']; ?>" 
                                   style="color: #3498db; text-decoration: none;" title="Éditer">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if($usr['id'] != $_SESSION['user_id']): ?>
                                <a href="users.php?action=delete&id=<?php echo $usr['id']; ?>" 
                                   onclick="return confirm('Supprimer cet utilisateur ?')"
                                   style="color: #e74c3c; text-decoration: none;" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php } ?>
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
                    
                    <?php foreach($users as $usr) { ?>
                    <a href="?page=<?php echo $i; ?>" 
                       style="padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; color: <?php echo $i == $page ? 'white' : '#7f8c8d'; ?>; background: <?php echo $i == $page ? '#3498db' : 'white'; ?>; border-radius: 4px;">
                        <?php echo $i; ?>
                    </a>
                    <?php } ?>
                    
                    <?php if($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?>" 
                       style="padding: 8px 12px; border: 1px solid #ddd; text-decoration: none; color: #7f8c8d; border-radius: 4px;">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                
                <p style="margin-top: 10px; color: #7f8c8d; font-size: 0.9rem;">
                    Page <?php echo $page; ?> sur <?php echo $total_pages; ?> 
                    (<?php echo $total_users; ?> utilisateur<?php echo $total_users > 1 ? 's' : ''; ?>)
                </p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>