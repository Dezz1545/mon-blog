<?php
/**
 * Fichier d'authentification et autorisations
 */

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Vérifier si l'utilisateur est administrateur
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Vérifier si l'utilisateur est auteur
function isAuthor() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'author' || $_SESSION['role'] === 'admin');
}

// Vérifier si l'utilisateur peut éditer un article
function canEditArticle($article_user_id) {
    if(!isLoggedIn()) return false;
    
    // Admin peut tout éditer
    if(isAdmin()) return true;
    
    // Auteur peut éditer ses propres articles
    if(isAuthor() && $_SESSION['user_id'] == $article_user_id) return true;
    
    return false;
}

// Rediriger si non connecté
function requireLogin() {
    if(!isLoggedIn()) {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        $_SESSION['message'] = "Veuillez vous connecter pour accéder à cette page";
        $_SESSION['message_type'] = "error";
        header('Location: login.php');
        exit();
    }
}

// Rediriger si non admin
function requireAdmin() {
    requireLogin();
    
    if(!isAdmin()) {
        $_SESSION['message'] = "Accès refusé. Droits administrateur requis";
        $_SESSION['message_type'] = "error";
        header('Location: index.php');
        exit();
    }
}

// Rediriger si non auteur ou admin
function requireAuthor() {
    requireLogin();
    
    if(!isAuthor()) {
        $_SESSION['message'] = "Accès refusé. Vous devez être auteur ou administrateur";
        $_SESSION['message_type'] = "error";
        header('Location: index.php');
        exit();
    }
}

// Générer un token CSRF
function generateCSRFToken() {
    if(!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Vérifier un token CSRF
function verifyCSRFToken($token) {
    if(!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Valider les données de formulaire
function validateFormData($data, $rules) {
    $errors = [];
    
    foreach($rules as $field => $rule) {
        $value = isset($data[$field]) ? trim($data[$field]) : '';
        
        // Vérifier si le champ est requis
        if(isset($rule['required']) && $rule['required'] && empty($value)) {
            $errors[$field] = $rule['message'] ?? "Le champ $field est requis";
            continue;
        }
        
        // Vérifier la longueur minimale
        if(isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
            $errors[$field] = $rule['message'] ?? "Le champ $field doit contenir au moins {$rule['min_length']} caractères";
        }
        
        // Vérifier la longueur maximale
        if(isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
            $errors[$field] = $rule['message'] ?? "Le champ $field ne doit pas dépasser {$rule['max_length']} caractères";
        }
        
        // Vérifier le format email
        if(isset($rule['email']) && $rule['email'] && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[$field] = $rule['message'] ?? "L'email $field est invalide";
        }
        
        // Vérifier le format numérique
        if(isset($rule['numeric']) && $rule['numeric'] && !is_numeric($value)) {
            $errors[$field] = $rule['message'] ?? "Le champ $field doit être un nombre";
        }
        
        // Validation personnalisée
        if(isset($rule['custom']) && is_callable($rule['custom'])) {
            $customError = $rule['custom']($value);
            if($customError !== true) {
                $errors[$field] = $customError;
            }
        }
    }
    
    return $errors;
}
?>