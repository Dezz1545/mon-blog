<?php
class User {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance();
    }
    
    // Inscription
    public function register($username, $email, $password, $role = 'user') {
        // Validation
        $errors = $this->validateUserData($username, $email, $password);
        if(!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Vérifier si email existe
        if($this->emailExists($email)) {
            return ['success' => false, 'errors' => ['Cet email est déjà utilisé']];
        }
        
        // Vérifier si username existe
        if($this->usernameExists($username)) {
            return ['success' => false, 'errors' => ['Ce nom d\'utilisateur est déjà pris']];
        }
        
        // Créer l'utilisateur
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $data = [
            'username' => $username,
            'email' => $email,
            'password' => $hashed_password,
            'role' => $role,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $id = Database::insert('users', $data);
            
            return [
                'success' => true,
                'user_id' => $id,
                'message' => 'Compte créé avec succès'
            ];
        } catch(PDOException $e) {
            return ['success' => false, 'errors' => ['Erreur: ' . $e->getMessage()]];
        }
    }
    
    // Connexion
    public function login($email, $password) {
        $user = Database::fetch(
            "SELECT id, username, email, password, role FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Mettre à jour la dernière connexion
            Database::update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
            
            return true;
        }
        return false;
    }
    
    // Récupérer un utilisateur par ID
    public function getUser($id) {
        return Database::fetch("SELECT * FROM users WHERE id = ?", [$id]);
    }
    
    // Mettre à jour le profil
    public function updateProfile($id, $data) {
        // Ne pas permettre la modification du rôle via cette méthode
        if(isset($data['role'])) unset($data['role']);
        
        return Database::update('users', $data, 'id = ?', [$id]);
    }
    
    // Changer le mot de passe
    public function changePassword($id, $current_password, $new_password) {
        $user = $this->getUser($id);
        
        if(!password_verify($current_password, $user['password'])) {
            return ['success' => false, 'error' => 'Mot de passe actuel incorrect'];
        }
        
        // Validation du nouveau mot de passe
        $errors = $this->validatePassword($new_password);
        if(!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        Database::update('users', ['password' => $hashed_password], 'id = ?', [$id]);
        
        return ['success' => true, 'message' => 'Mot de passe changé avec succès'];
    }
    
    // Demande de réinitialisation
    public function requestPasswordReset($email) {
        $user = Database::fetch("SELECT id FROM users WHERE email = ? AND is_active = 1", [$email]);
        
        if(!$user) return false;
        
        $token = bin2hex(random_bytes(50));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        Database::update('users', 
            ['reset_token' => $token, 'token_expiry' => $expiry], 
            'id = ?', 
            [$user['id']]
        );
        
        return $token;
    }
    
    // Réinitialiser le mot de passe
    public function resetPassword($token, $new_password) {
        $user = Database::fetch(
            "SELECT id FROM users WHERE reset_token = ? AND token_expiry > NOW()",
            [$token]
        );
        
        if(!$user) {
            return ['success' => false, 'error' => 'Token invalide ou expiré'];
        }
        
        // Validation du mot de passe
        $errors = $this->validatePassword($new_password);
        if(!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        Database::update('users', 
            ['password' => $hashed_password, 'reset_token' => null, 'token_expiry' => null], 
            'id = ?', 
            [$user['id']]
        );
        
        return ['success' => true, 'message' => 'Mot de passe réinitialisé avec succès'];
    }
    
    // Récupérer tous les utilisateurs
    public function getAllUsers($limit = 50, $offset = 0) {
        return Database::fetchAll(
            "SELECT * FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    }
    
    // Compter les utilisateurs
    public function countUsers() {
        return Database::fetch("SELECT COUNT(*) as count FROM users")['count'];
    }
    
    // Désactiver un utilisateur
    public function deactivateUser($id) {
        return Database::update('users', ['is_active' => 0], 'id = ?', [$id]);
    }
    
    // Activer un utilisateur
    public function activateUser($id) {
        return Database::update('users', ['is_active' => 1], 'id = ?', [$id]);
    }
    
    // Changer le rôle
    public function changeRole($id, $role) {
        $allowed_roles = ['admin', 'author', 'user'];
        if(!in_array($role, $allowed_roles)) {
            return false;
        }
        
        return Database::update('users', ['role' => $role], 'id = ?', [$id]);
    }
    
    // Méthodes privées
    private function validateUserData($username, $email, $password) {
        $errors = [];
        
        if(strlen($username) < 3) {
            $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères";
        }
        
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email invalide";
        }
        
        $password_errors = $this->validatePassword($password);
        $errors = array_merge($errors, $password_errors);
        
        return $errors;
    }
    
    private function validatePassword($password) {
        $errors = [];
        
        if(strlen($password) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
        }
        
        if(!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une majuscule";
        }
        
        if(!preg_match('/[0-9]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un chiffre";
        }
        
        return $errors;
    }
    
    private function emailExists($email) {
        $result = Database::fetch("SELECT id FROM users WHERE email = ?", [$email]);
        return $result !== false;
    }
    
    private function usernameExists($username) {
        $result = Database::fetch("SELECT id FROM users WHERE username = ?", [$username]);
        return $result !== false;
    }
}
?>