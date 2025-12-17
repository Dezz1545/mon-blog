    </main>
    
    <footer style="background: #2c3e50; color: white; padding: 40px 0; margin-top: 60px;">
        <div class="container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px;">
            <div>
                <h3 style="margin-bottom: 20px; color: #ecf0f1;">
                    <i class="fas fa-blog"></i> <?php echo SITE_NAME; ?>
                </h3>
                <p style="color: #bdc3c7; line-height: 1.6;">
                    Un blog moderne créé avec PHP natif et MySQL. 
                    Partagez vos idées, découvrez de nouveaux articles.
                </p>
                <div style="margin-top: 15px; display: flex; gap: 15px;">
                    <a href="#" style="color: white; font-size: 1.2rem;"><i class="fab fa-facebook"></i></a>
                    <a href="#" style="color: white; font-size: 1.2rem;"><i class="fab fa-twitter"></i></a>
                    <a href="#" style="color: white; font-size: 1.2rem;"><i class="fab fa-linkedin"></i></a>
                    <a href="#" style="color: white; font-size: 1.2rem;"><i class="fab fa-github"></i></a>
                </div>
            </div>
            
            <div>
                <h3 style="margin-bottom: 20px; color: #ecf0f1;">
                    <i class="fas fa-link"></i> Navigation
                </h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 10px;">
                        <a href="<?php echo SITE_URL; ?>/index.php" 
                           style="color: #bdc3c7; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: color 0.3s;"
                           onmouseover="this.style.color='white'"
                           onmouseout="this.style.color='#bdc3c7'">
                            <i class="fas fa-home"></i> Accueil
                        </a>
                    </li>
                    <li style="margin-bottom: 10px;">
                        <a href="<?php echo SITE_URL; ?>/blog.php" 
                           style="color: #bdc3c7; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: color 0.3s;"
                           onmouseover="this.style.color='white'"
                           onmouseout="this.style.color='#bdc3c7'">
                            <i class="fas fa-newspaper"></i> Blog
                        </a>
                    </li>
                    <li style="margin-bottom: 10px;">
                        <a href="<?php echo SITE_URL; ?>/contact.php" 
                           style="color: #bdc3c7; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: color 0.3s;"
                           onmouseover="this.style.color='white'"
                           onmouseout="this.style.color='#bdc3c7'">
                            <i class="fas fa-envelope"></i> Contact
                        </a>
                    </li>
                    <?php if(isLoggedIn()): ?>
                    <?php else: ?>
                    <li style="margin-bottom: 10px;">
                        <a href="<?php echo SITE_URL; ?>/login.php" 
                           style="color: #bdc3c7; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: color 0.3s;"
                           onmouseover="this.style.color='white'"
                           onmouseout="this.style.color='#bdc3c7'">
                            <i class="fas fa-sign-in-alt"></i> Connexion
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div>
                <h3 style="margin-bottom: 20px; color: #ecf0f1;">
                    <i class="fas fa-info-circle"></i> Informations
                </h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 10px; color: #bdc3c7; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-map-marker-alt"></i> CORTE, France
                    </li>
                    <li style="margin-bottom: 10px; color: #bdc3c7; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-envelope"></i> contact@<?php echo strtolower(str_replace(' ', '', SITE_NAME)); ?>.com
                    </li>
                    <li style="margin-bottom: 10px; color: #bdc3c7; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-clock"></i> Lundi - Vendredi, 9h-18h
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="container" style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #34495e;">
            <p style="color: #95a5a6; margin: 0; font-size: 0.9rem;">
                &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. 
                <span style="margin-left: 10px;">Développé </i> en PHP/MySQL</span>
            </p>
        </div>
    </footer>
    
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>