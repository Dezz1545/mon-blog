<?php
class Category {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance();
    }
    
    // Créer une catégorie
    public function create($name, $description = null) {
        if(empty($name) || strlen($name) < 3) {
            return ['success' => false, 'error' => 'Le nom doit contenir au moins 3 caractères'];
        }
        
        if($this->nameExists($name)) {
            return ['success' => false, 'error' => 'Cette catégorie existe déjà'];
        }
        
        $slug = $this->createSlug($name);
        
        try {
            $id = Database::insert('categories', [
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            return [
                'success' => true,
                'category_id' => $id,
                'message' => 'Catégorie créée avec succès'
            ];
        } catch(PDOException $e) {
            return ['success' => false, 'error' => 'Erreur: ' . $e->getMessage()];
        }
    }
    
    // Mettre à jour une catégorie
    public function update($id, $name, $description = null) {
        $category = $this->getCategory($id);
        if(!$category) {
            return ['success' => false, 'error' => 'Catégorie non trouvée'];
        }
        
        if(empty($name) || strlen($name) < 3) {
            return ['success' => false, 'error' => 'Le nom doit contenir au moins 3 caractères'];
        }
        
        // Vérifier si le nom existe déjà (pour une autre catégorie)
        if($name !== $category['name'] && $this->nameExists($name)) {
            return ['success' => false, 'error' => 'Cette catégorie existe déjà'];
        }
        
        $slug = $this->createSlug($name);
        
        try {
            Database::update('categories', 
                ['name' => $name, 'slug' => $slug, 'description' => $description], 
                'id = ?', 
                [$id]
            );
            
            return ['success' => true, 'message' => 'Catégorie mise à jour avec succès'];
        } catch(PDOException $e) {
            return ['success' => false, 'error' => 'Erreur: ' . $e->getMessage()];
        }
    }
    
    // Récupérer une catégorie
    public function getCategory($id) {
        return Database::fetch("SELECT * FROM categories WHERE id = ?", [$id]);
    }
    
    // Récupérer toutes les catégories
    public function getAll() {
        return Database::fetchAll("SELECT * FROM categories ORDER BY name");
    }
    
    // Récupérer les catégories avec le nombre d'articles
    public function getAllWithCount() {
        return Database::fetchAll("
            SELECT c.*, COUNT(a.id) as article_count 
            FROM categories c 
            LEFT JOIN articles a ON c.id = a.category_id AND a.published = 1 
            GROUP BY c.id 
            ORDER BY c.name
        ");
    }
    
    // Supprimer une catégorie
    public function delete($id) {
        // Vérifier si la catégorie a des articles
        $articles = Database::fetch("SELECT COUNT(*) as count FROM articles WHERE category_id = ?", [$id]);
        
        if($articles['count'] > 0) {
            return ['success' => false, 'error' => 'Impossible de supprimer : cette catégorie contient des articles'];
        }
        
        $result = Database::delete('categories', 'id = ?', [$id]);
        
        if($result) {
            return ['success' => true, 'message' => 'Catégorie supprimée avec succès'];
        } else {
            return ['success' => false, 'error' => 'Erreur lors de la suppression'];
        }
    }
    
    // Méthodes privées
    private function nameExists($name) {
        $result = Database::fetch("SELECT id FROM categories WHERE name = ?", [$name]);
        return $result !== false;
    }
    
    private function createSlug($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        
        if(empty($text)) {
            return 'categorie-' . time();
        }
        
        return $text;
    }
}
?>