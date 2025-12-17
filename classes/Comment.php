<?php
class Comment {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance();
    }
    
    // Ajouter un commentaire
    public function add($article_id, $user_id, $content) {
        if(empty($content) || strlen($content) < 3) {
            return ['success' => false, 'error' => 'Le commentaire doit contenir au moins 3 caractères'];
        }
        
        // Vérifier si l'article existe et est publié
        $article = Database::fetch("SELECT id FROM articles WHERE id = ? AND published = 1", [$article_id]);
        if(!$article) {
            return ['success' => false, 'error' => 'Article non trouvé'];
        }
        
        // Déterminer si le commentaire est approuvé automatiquement
        $user = Database::fetch("SELECT role FROM users WHERE id = ?", [$user_id]);
        $is_approved = ($user['role'] === 'admin' || $user['role'] === 'author') ? 1 : 0;
        
        try {
            $id = Database::insert('comments', [
                'article_id' => $article_id,
                'user_id' => $user_id,
                'content' => $content,
                'is_approved' => $is_approved,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            return [
                'success' => true,
                'comment_id' => $id,
                'message' => $is_approved ? 
                    'Commentaire publié avec succès' : 
                    'Commentaire soumis à modération'
            ];
        } catch(PDOException $e) {
            return ['success' => false, 'error' => 'Erreur: ' . $e->getMessage()];
        }
    }
    
    // Récupérer les commentaires d'un article
    public function getByArticle($article_id, $approved_only = true) {
        $where = $approved_only ? "AND c.is_approved = 1" : "";
        
        return Database::fetchAll("
            SELECT c.*, u.username, u.avatar 
            FROM comments c 
            LEFT JOIN users u ON c.user_id = u.id 
            WHERE c.article_id = ? $where
            ORDER BY c.created_at DESC
        ", [$article_id]);
    }
    
    // Récupérer tous les commentaires (pour admin)
    public function getAll($limit = 50, $offset = 0, $approved = null) {
        $where = "";
        $params = [];
        
        if($approved !== null) {
            $where = "WHERE c.is_approved = ?";
            $params[] = $approved;
        }
        
        $params[] = $limit;
        $params[] = $offset;
        
        return Database::fetchAll("
            SELECT c.*, u.username, u.email, a.title as article_title, a.slug as article_slug 
            FROM comments c 
            LEFT JOIN users u ON c.user_id = u.id 
            LEFT JOIN articles a ON c.article_id = a.id 
            $where
            ORDER BY c.created_at DESC 
            LIMIT ? OFFSET ?
        ", $params);
    }
    
    // Approuver un commentaire
    public function approve($id) {
        return Database::update('comments', ['is_approved' => 1], 'id = ?', [$id]);
    }
    
    // Désapprouver un commentaire
    public function disapprove($id) {
        return Database::update('comments', ['is_approved' => 0], 'id = ?', [$id]);
    }
    
    // Supprimer un commentaire
    public function delete($id) {
        return Database::delete('comments', 'id = ?', [$id]);
    }
    
    // Récupérer un commentaire
    public function getComment($id) {
        return Database::fetch("
            SELECT c.*, u.username, a.title as article_title 
            FROM comments c 
            LEFT JOIN users u ON c.user_id = u.id 
            LEFT JOIN articles a ON c.article_id = a.id 
            WHERE c.id = ?
        ", [$id]);
    }
    
    // Compter les commentaires en attente
    public function countPending() {
        $result = Database::fetch("SELECT COUNT(*) as count FROM comments WHERE is_approved = 0");
        return $result['count'];
    }
    
    // Compter tous les commentaires
    public function countAll() {
        $result = Database::fetch("SELECT COUNT(*) as count FROM comments");
        return $result['count'];
    }
}
?>