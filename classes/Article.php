<?php
class Article {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance();
    }
    
    // Créer un article
    public function create($data, $image = null) {
        // Validation
        $errors = $this->validateArticleData($data);
        if(!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Préparer les données
        $article_data = [
            'title' => $data['title'],
            'slug' => $this->createSlug($data['title']),
            'content' => $data['content'],
            'excerpt' => $data['excerpt'] ?? substr(strip_tags($data['content']), 0, 150) . '...',
            'image' => $image,
            'category_id' => $data['category_id'],
            'user_id' => $data['user_id'],
            'published' => $data['published'] ?? 1,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $id = Database::insert('articles', $article_data);
            
            return [
                'success' => true,
                'article_id' => $id,
                'message' => 'Article créé avec succès'
            ];
        } catch(PDOException $e) {
            return ['success' => false, 'errors' => ['Erreur: ' . $e->getMessage()]];
        }
    }
    
    // Mettre à jour un article
    public function update($id, $data, $image = null) {
        $article = $this->getArticle($id);
        if(!$article) {
            return ['success' => false, 'errors' => ['Article non trouvé']];
        }
        
        // Validation
        $errors = $this->validateArticleData($data, $id);
        if(!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Préparer les données
        $update_data = [
            'title' => $data['title'],
            'slug' => $this->createSlug($data['title']),
            'content' => $data['content'],
            'excerpt' => $data['excerpt'] ?? substr(strip_tags($data['content']), 0, 150) . '...',
            'category_id' => $data['category_id'],
            'published' => $data['published'] ?? 1,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if($image !== null) {
            $update_data['image'] = $image;
        }
        
        try {
            Database::update('articles', $update_data, 'id = ?', [$id]);
            
            return [
                'success' => true,
                'message' => 'Article mis à jour avec succès'
            ];
        } catch(PDOException $e) {
            return ['success' => false, 'errors' => ['Erreur: ' . $e->getMessage()]];
        }
    }
    
    // Récupérer un article
    public function getArticle($id) {
        return Database::fetch("
            SELECT a.*, c.name as category_name, u.username 
            FROM articles a 
            LEFT JOIN categories c ON a.category_id = c.id 
            LEFT JOIN users u ON a.user_id = u.id 
            WHERE a.id = ?
        ", [$id]);
    }
    
    // Récupérer les derniers articles
    public function getLatest($limit = 10, $published_only = true) {
        $where = $published_only ? "WHERE a.published = 1" : "";
        
        return Database::fetchAll("
            SELECT a.*, c.name as category_name, u.username 
            FROM articles a 
            LEFT JOIN categories c ON a.category_id = c.id 
            LEFT JOIN users u ON a.user_id = u.id 
            $where
            ORDER BY a.created_at DESC 
            LIMIT ?
        ", [$limit]);
    }
    
    // Récupérer les articles populaires
    public function getPopular($limit = 10, $published_only = true) {
        $where = $published_only ? "WHERE a.published = 1" : "";
        
        return Database::fetchAll("
            SELECT a.*, COUNT(al.id) as like_count, c.name as category_name
            FROM articles a 
            LEFT JOIN article_likes al ON a.id = al.article_id 
            LEFT JOIN categories c ON a.category_id = c.id 
            $where
            GROUP BY a.id 
            ORDER BY like_count DESC 
            LIMIT ?
        ", [$limit]);
    }
    
    // Récupérer les articles par catégorie
    public function getByCategory($category_id, $limit = 10, $published_only = true) {
        $where = $published_only ? "AND a.published = 1" : "";
        
        return Database::fetchAll("
            SELECT a.*, c.name as category_name, u.username 
            FROM articles a 
            LEFT JOIN categories c ON a.category_id = c.id 
            LEFT JOIN users u ON a.user_id = u.id 
            WHERE a.category_id = ? $where
            ORDER BY a.created_at DESC 
            LIMIT ?
        ", [$category_id, $limit]);
    }
    
    // Rechercher des articles
    public function search($query, $category_id = null, $limit = 20) {
        $where = "WHERE a.published = 1 AND (a.title LIKE ? OR a.content LIKE ? OR a.excerpt LIKE ?)";
        $params = ["%$query%", "%$query%", "%$query%"];
        
        if($category_id) {
            $where .= " AND a.category_id = ?";
            $params[] = $category_id;
        }
        
        return Database::fetchAll("
            SELECT a.*, c.name as category_name, u.username 
            FROM articles a 
            LEFT JOIN categories c ON a.category_id = c.id 
            LEFT JOIN users u ON a.user_id = u.id 
            $where
            ORDER BY a.created_at DESC 
            LIMIT ?
        ", array_merge($params, [$limit]));
    }
    
    // Pagination
    public function paginate($page = 1, $per_page = 10, $published_only = true) {
        $offset = ($page - 1) * $per_page;
        $where = $published_only ? "WHERE a.published = 1" : "";
        
        // Total articles
        $total = Database::fetch("SELECT COUNT(*) as total FROM articles a $where")['total'];
        
        // Articles de la page
        $articles = Database::fetchAll("
            SELECT a.*, c.name as category_name, u.username 
            FROM articles a 
            LEFT JOIN categories c ON a.category_id = c.id 
            LEFT JOIN users u ON a.user_id = u.id 
            $where
            ORDER BY a.created_at DESC 
            LIMIT ? OFFSET ?
        ", [$per_page, $offset]);
        
        return [
            'articles' => $articles,
            'total' => $total,
            'pages' => ceil($total / $per_page),
            'current' => $page
        ];
    }
    
    // Supprimer un article
    public function delete($id) {
        // Supprimer d'abord les likes et commentaires
        Database::delete('article_likes', 'article_id = ?', [$id]);
        Database::delete('comments', 'article_id = ?', [$id]);
        
        // Puis supprimer l'article
        return Database::delete('articles', 'id = ?', [$id]);
    }
    
    // Ajouter un like
    public function addLike($article_id, $user_id) {
        try {
            Database::insert('article_likes', [
                'article_id' => $article_id,
                'user_id' => $user_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return true;
        } catch(PDOException $e) {
            // Ignorer si déjà liké (contrainte UNIQUE)
            return false;
        }
    }
    
    // Supprimer un like
    public function removeLike($article_id, $user_id) {
        return Database::delete('article_likes', 'article_id = ? AND user_id = ?', [$article_id, $user_id]);
    }
    
    // Vérifier si l'utilisateur a liké
    public function hasLiked($article_id, $user_id) {
        $result = Database::fetch(
            "SELECT id FROM article_likes WHERE article_id = ? AND user_id = ?",
            [$article_id, $user_id]
        );
        return $result !== false;
    }
    
    // Nombre de likes
    public function getLikeCount($article_id) {
        $result = Database::fetch(
            "SELECT COUNT(*) as count FROM article_likes WHERE article_id = ?",
            [$article_id]
        );
        return $result['count'] ?? 0;
    }
    
    // Méthodes privées
    private function validateArticleData($data, $exclude_id = null) {
        $errors = [];
        
        if(empty($data['title']) || strlen($data['title']) < 5) {
            $errors[] = "Le titre doit contenir au moins 5 caractères";
        }
        
        if(empty($data['content']) || strlen($data['content']) < 50) {
            $errors[] = "Le contenu doit contenir au moins 50 caractères";
        }
        
        if(empty($data['category_id']) || !is_numeric($data['category_id'])) {
            $errors[] = "Veuillez sélectionner une catégorie";
        }
        
        return $errors;
    }
    
    private function createSlug($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        
        if(empty($text)) {
            $text = 'article-' . time();
        }
        
        // Vérifier l'unicité du slug
        $counter = 1;
        $original_slug = $text;
        
        while($this->slugExists($text)) {
            $text = $original_slug . '-' . $counter;
            $counter++;
        }
        
        return $text;
    }
    
    private function slugExists($slug) {
        $result = Database::fetch("SELECT id FROM articles WHERE slug = ?", [$slug]);
        return $result !== false;
    }
}
?>