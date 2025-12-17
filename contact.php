<?php
require_once 'includes/config.php';

$errors = [];
$success = false;

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject']);
    $message = sanitize($_POST['message']);
    
    // Validation
    if(empty($name) || strlen($name) < 2) {
        $errors[] = "Le nom doit contenir au moins 2 caractères";
    }
    
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide";
    }
    
    if(empty($message) || strlen($message) < 10) {
        $errors[] = "Le message doit contenir au moins 10 caractères";
    }
    
    if(empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO contacts (name, email, subject, message) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $email, $subject, $message]);
            
            $success = true;
            
            // Vous pouvez ajouter ici l'envoi d'email
            // mail('admin@votresite.com', "Nouveau contact: $subject", $message);
            
        } catch(PDOException $e) {
            $errors[] = "Erreur lors de l'envoi du message : " . $e->getMessage();
        }
    }
}

$page_title = "Contact";
include 'includes/header.php';
?>

<section style="padding: 60px 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
    <div class="container">
        <h1 style="text-align: center; margin-bottom: 10px; font-size: 2.5rem;">Contactez-nous</h1>
        <p style="text-align: center; font-size: 1.1rem; max-width: 600px; margin: 0 auto 40px;">
            Une question, une suggestion ou besoin d'aide ? N'hésitez pas à nous contacter.
        </p>
    </div>
</section>

<section style="padding: 60px 0; background: #f8f9fa;">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px;">
            <!-- Formulaire de contact -->
            <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
                <h2 style="margin-bottom: 30px; color: #333;">Envoyez-nous un message</h2>
                
                <?php if($success): ?>
                <div style="background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin-bottom: 30px; text-align: center;">
                    <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 15px; display: block;"></i>
                    <h3 style="margin-bottom: 10px;">✅ Message envoyé avec succès !</h3>
                    <p>Nous vous répondrons dans les plus brefs délais.</p>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($errors)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 30px;">
                    <h4 style="margin-bottom: 10px;"><i class="fas fa-exclamation-triangle"></i> Erreurs :</h4>
                    <ul style="margin-left: 20px;">
                        <?php foreach($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if(!$success): ?>
                <form method="POST">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #555;">
                            <i class="fas fa-user"></i> Nom complet *
                        </label>
                        <input type="text" name="name" required minlength="2"
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                               style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border 0.3s;"
                               onfocus="this.style.borderColor='#667eea';" 
                               onblur="this.style.borderColor='#ddd';">
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #555;">
                            <i class="fas fa-envelope"></i> Email *
                        </label>
                        <input type="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border 0.3s;"
                               onfocus="this.style.borderColor='#667eea';" 
                               onblur="this.style.borderColor='#ddd';">
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #555;">
                            <i class="fas fa-tag"></i> Sujet
                        </label>
                        <input type="text" name="subject"
                               value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>"
                               placeholder="Sujet de votre message (optionnel)"
                               style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; transition: border 0.3s;"
                               onfocus="this.style.borderColor='#667eea';" 
                               onblur="this.style.borderColor='#ddd';">
                    </div>
                    
                    <div style="margin-bottom: 30px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: bold; color: #555;">
                            <i class="fas fa-comment"></i> Message *
                        </label>
                        <textarea name="message" required minlength="10" rows="6"
                                  placeholder="Votre message..."
                                  style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 1rem; resize: vertical; transition: border 0.3s;"
                                  onfocus="this.style.borderColor='#667eea';" 
                                  onblur="this.style.borderColor='#ddd';"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        <small style="color: #666; display: block; margin-top: 5px;">
                            Minimum 10 caractères
                        </small>
                    </div>
                    
                    <button type="submit" 
                            style="width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border: none; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer; transition: transform 0.2s;"
                            onmouseover="this.style.transform='translateY(-2px)';"
                            onmouseout="this.style.transform='translateY(0)';">
                        <i class="fas fa-paper-plane"></i> Envoyer le message
                    </button>
                </form>
                <?php endif; ?>
            </div>
            
            <!-- Informations de contact -->
            <div>
                <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin-bottom: 30px;">
                    <h3 style="margin-bottom: 25px; color: #333; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-info-circle" style="color: #667eea;"></i> Informations de contact
                    </h3>
                    
                    <div style="display: grid; gap: 25px;">
                        <div style="display: flex; align-items: flex-start; gap: 15px;">
                            <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <h4 style="margin-bottom: 5px; color: #333;">Adresse</h4>
                                <p style="color: #666; margin: 0;">123 Rue du Blog<br>75000 Paris, France</p>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: flex-start; gap: 15px;">
                            <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <h4 style="margin-bottom: 5px; color: #333;">Téléphone</h4>
                                <p style="color: #666; margin: 0;">+33 1 23 45 67 89<br>Lundi - Vendredi, 9h-18h</p>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: flex-start; gap: 15px;">
                            <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <h4 style="margin-bottom: 5px; color: #333;">Email</h4>
                                <p style="color: #666; margin: 0;">contact@mon-blog.com<br>support@mon-blog.com</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="background: white; padding: 40px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
                    <h3 style="margin-bottom: 25px; color: #333; display: flex; align-items: center; gap: 10px;">
                        <i class="far fa-clock" style="color: #667eea;"></i> Horaires d'ouverture
                    </h3>
                    
                    <div style="display: grid; gap: 15px;">
                        <?php
                        $hours = [
                            ['Lundi', '9h00 - 18h00'],
                            ['Mardi', '9h00 - 18h00'],
                            ['Mercredi', '9h00 - 18h00'],
                            ['Jeudi', '9h00 - 18h00'],
                            ['Vendredi', '9h00 - 17h00'],
                            ['Samedi', '10h00 - 14h00'],
                            ['Dimanche', 'Fermé']
                        ];
                        
                        foreach($hours as $hour):
                        ?>
                        <div style="display: flex; justify-content: space-between; padding-bottom: 10px; border-bottom: 1px solid #f0f0f0;">
                            <span style="font-weight: 500; color: #333;"><?php echo $hour[0]; ?></span>
                            <span style="color: #666;"><?php echo $hour[1]; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Carte (optionnelle) -->
<div style="height: 400px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666;">
    <div style="text-align: center;">
        <i class="fas fa-map" style="font-size: 4rem; margin-bottom: 20px; opacity: 0.3;"></i>
        <p>Carte Google Maps ici<br><small>(Intégration optionnelle)</small></p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>