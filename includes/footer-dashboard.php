    </main>
    
    <!-- Footer pour dashboard -->
    <footer style="background: #34495e; color: white; padding: 20px 0; margin-top: 40px; margin-left: 250px;">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; padding: 0 30px;">
            <div style="color: #bdc3c7; font-size: 0.9rem;">
                <p style="margin: 0;">
                    <i class="fas fa-tachometer-alt"></i> Dashboard <?php echo SITE_NAME; ?> 
                    • <?php echo $_SESSION['username']; ?> (<?php echo $_SESSION['role']; ?>)
                    • <?php echo date('d/m/Y H:i'); ?>
                </p>
            </div>
            
            <div style="display: flex; gap: 20px;">
                <a href="<?php echo SITE_URL; ?>/index.php" 
                   style="color: #bdc3c7; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; gap: 5px;"
                   onmouseover="this.style.color='white'"
                   onmouseout="this.style.color='#bdc3c7'">
                    <i class="fas fa-external-link-alt"></i> Voir le site
                </a>
                
                <span style="color: #7f8c8d;">|</span>
                
                <a href="<?php echo SITE_URL; ?>/dashboard/" 
                   style="color: #bdc3c7; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; gap: 5px;"
                   onmouseover="this.style.color='white'"
                   onmouseout="this.style.color='#bdc3c7'">
                    <i class="fas fa-tachometer-alt"></i> Tableau de bord
                </a>
                
                <span style="color: #7f8c8d;">|</span>
                
                <a href="<?php echo SITE_URL; ?>/logout.php" 
                   style="color: #e74c3c; text-decoration: none; font-size: 0.9rem; display: flex; align-items: center; gap: 5px;"
                   onmouseover="this.style.color='#c0392b'"
                   onmouseout="this.style.color='#e74c3c'">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
    </footer>
    
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <script>
    // Scripts spécifiques au dashboard
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-refresh des notifications (optionnel)
        setInterval(() => {
            fetch('<?php echo SITE_URL; ?>/dashboard/check-notifications.php')
                .then(response => response.json())
                .then(data => {
                    if(data.pending_comments > 0) {
                        const badge = document.querySelector('.pending-comments-badge');
                        if(badge) {
                            badge.textContent = data.pending_comments;
                            badge.style.display = 'inline-block';
                        }
                    }
                });
        }, 30000); // Toutes les 30 secondes
        
        // Confirmation pour les actions critiques
        document.querySelectorAll('a[href*="delete"], a[href*="remove"]').forEach(link => {
            link.addEventListener('click', function(e) {
                if(!confirm('Êtes-vous sûr de vouloir effectuer cette action ?')) {
                    e.preventDefault();
                }
            });
        });
    });
    </script>
</body>
</html>