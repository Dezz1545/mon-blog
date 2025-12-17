<?php
class Newsletter {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance();
    }
    
    // S'abonner à la newsletter
    public function subscribe($email) {
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Email invalide'];
        }
        
        // Vérifier si l'email est déjà inscrit
        if($this->isSubscribed($email)) {
            return ['success' => false, 'error' => 'Cet email est déjà inscrit à la newsletter'];
        }
        
        try {
            $id = Database::insert('newsletter', [
                'email' => $email,
                'subscribed_at' => date('Y-m-d H:i:s'),
                'is_active' => 1
            ]);
            
            return [
                'success' => true,
                'subscription_id' => $id,
                'message' => 'Inscription à la newsletter réussie !'
            ];
        } catch(PDOException $e) {
            return ['success' => false, 'error' => 'Erreur: ' . $e->getMessage()];
        }
    }
    
    // Se désabonner
    public function unsubscribe($email) {
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        return Database::update('newsletter', ['is_active' => 0], 'email = ?', [$email]);
    }
    
    // Vérifier si un email est inscrit
    public function isSubscribed($email) {
        $result = Database::fetch(
            "SELECT id FROM newsletter WHERE email = ? AND is_active = 1",
            [$email]
        );
        return $result !== false;
    }
    
    // Récupérer tous les abonnés
    public function getSubscribers($limit = 100, $offset = 0, $active_only = true) {
        $where = $active_only ? "WHERE is_active = 1" : "";
        
        return Database::fetchAll("
            SELECT * FROM newsletter 
            $where
            ORDER BY subscribed_at DESC 
            LIMIT ? OFFSET ?
        ", [$limit, $offset]);
    }
    
    // Compter les abonnés
    public function countSubscribers($active_only = true) {
        $where = $active_only ? "WHERE is_active = 1" : "";
        
        $result = Database::fetch("SELECT COUNT(*) as count FROM newsletter $where");
        return $result['count'];
    }
    
    // Exporter les emails
    public function exportEmails($active_only = true) {
        $where = $active_only ? "WHERE is_active = 1" : "";
        
        $subscribers = Database::fetchAll("
            SELECT email, subscribed_at 
            FROM newsletter 
            $where
            ORDER BY subscribed_at DESC
        ");
        
        return $subscribers;
    }
    
    // Envoyer une newsletter (simulation)
    public function sendNewsletter($subject, $content) {
        $subscribers = $this->getSubscribers(0, 0, true);
        $sent_count = 0;
        
        // En production, vous utiliseriez une bibliothèque d'emails
        // comme PHPMailer ou SwiftMailer
        foreach($subscribers as $subscriber) {
            // Simulation d'envoi d'email
            // mail($subscriber['email'], $subject, $content);
            $sent_count++;
        }
        
        return [
            'success' => true,
            'sent_count' => $sent_count,
            'message' => "Newsletter envoyée à $sent_count abonnés"
        ];
    }
}
?>