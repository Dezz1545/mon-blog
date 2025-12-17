<?php
/**
 * Fonctions utilitaires pour le blog
 */

/**
 * Génère un slug à partir d'un texte
 */
function generateSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    
    if(empty($text)) {
        return 'n-a';
    }
    
    return $text;
}

/**
 * Formate une date
 */
function formatDate($date, $format = 'd/m/Y') {
    return date($format, strtotime($date));
}

/**
 * Limite le texte à un certain nombre de mots
 */
function limitWords($text, $limit = 20) {
    $words = explode(' ', $text);
    if(count($words) > $limit) {
        return implode(' ', array_slice($words, 0, $limit)) . '...';
    }
    return $text;
}

/**
 * Vérifie si une image existe
 */
function imageExists($path) {
    return file_exists($path) && is_file($path);
}

/**
 * Upload sécurisé d'image
 */
function uploadImage($file, $type = 'articles') {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if(!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    if($file['size'] > $max_size) {
        return false;
    }
    
    // Vérifier que c'est bien une image
    $image_info = getimagesize($file['tmp_name']);
    if(!$image_info) {
        return false;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $upload_dir = "uploads/$type/";
    
    // Créer le dossier si nécessaire
    if(!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $destination = $upload_dir . $filename;
    
    if(move_uploaded_file($file['tmp_name'], $destination)) {
        // Redimensionner si nécessaire (optionnel)
        // resizeImage($destination, 1200, 800);
        return $filename;
    }
    
    return false;
}

/**
 * Redimensionne une image (optionnel)
 */
function resizeImage($path, $max_width, $max_height) {
    $image_info = getimagesize($path);
    if(!$image_info) return false;
    
    $type = $image_info[2];
    
    switch($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($path);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($path);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($path);
            break;
        default:
            return false;
    }
    
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Calculer les nouvelles dimensions
    $ratio = $width / $height;
    
    if($width > $max_width || $height > $max_height) {
        if($ratio > 1) {
            $new_width = $max_width;
            $new_height = $max_width / $ratio;
        } else {
            $new_height = $max_height;
            $new_width = $max_height * $ratio;
        }
        
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // Conserver la transparence pour PNG
        if($type == IMAGETYPE_PNG) {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
        }
        
        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        
        switch($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($new_image, $path, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($new_image, $path, 8);
                break;
            case IMAGETYPE_GIF:
                imagegif($new_image, $path);
                break;
        }
        
        imagedestroy($image);
        imagedestroy($new_image);
    }
    
    return true;
}

/**
 * Génère une pagination
 */
function generatePagination($current_page, $total_pages, $url, $params = []) {
    $html = '<div class="pagination">';
    
    if($current_page > 1) {
        $html .= '<a href="' . buildUrl($url, $current_page - 1, $params) . '">&laquo; Précédent</a>';
    }
    
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for($i = $start; $i <= $end; $i++) {
        $active = $i == $current_page ? ' active' : '';
        $html .= '<a href="' . buildUrl($url, $i, $params) . '" class="' . $active . '">' . $i . '</a>';
    }
    
    if($current_page < $total_pages) {
        $html .= '<a href="' . buildUrl($url, $current_page + 1, $params) . '">Suivant &raquo;</a>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Construit une URL avec paramètres
 */
function buildUrl($base_url, $page, $params = []) {
    $params['page'] = $page;
    return $base_url . '?' . http_build_query($params);
}

/**
 * Protection contre les attaques XSS
 */
function xssProtection($data) {
    if(is_array($data)) {
        return array_map('xssProtection', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Vérifie la force d'un mot de passe
 */
function checkPasswordStrength($password) {
    $strength = 0;
    
    // Longueur minimale
    if(strlen($password) >= 8) $strength++;
    
    // Contient une majuscule
    if(preg_match('/[A-Z]/', $password)) $strength++;
    
    // Contient une minuscule
    if(preg_match('/[a-z]/', $password)) $strength++;
    
    // Contient un chiffre
    if(preg_match('/[0-9]/', $password)) $strength++;
    
    // Contient un caractère spécial
    if(preg_match('/[^A-Za-z0-9]/', $password)) $strength++;
    
    return $strength;
}
?>