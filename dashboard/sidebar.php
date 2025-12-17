<div style="position: fixed; left: 0; top: 0; bottom: 0; width: 250px; background: #2c3e50; color: white; padding: 20px 0; overflow-y: auto; z-index: 1000;">
    <div style="padding: 0 20px 30px; border-bottom: 1px solid #34495e;">
        <h2 style="margin: 0; font-size: 1.5rem;">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </h2>
        <p style="color: #bdc3c7; margin: 5px 0 0; font-size: 0.9rem;">
            <?php echo $_SESSION['username']; ?> (<?php echo $_SESSION['role']; ?>)
        </p>
    </div>
    
    <nav style="margin-top: 30px;">
        <ul style="list-style: none; padding: 0; margin: 0;">
            <li style="margin-bottom: 5px;">
                <a href="index.php" 
                   style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: white; text-decoration: none; 
                          background: <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? '#34495e' : 'transparent'; ?>; 
                          border-left: 4px solid <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? '#3498db' : 'transparent'; ?>;">
                    <i class="fas fa-tachometer-alt"></i> Tableau de bord
                </a>
            </li>
            <li style="margin-bottom: 5px;">
                <a href="articles.php" 
                   style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: #bdc3c7; text-decoration: none; 
                          background: <?php echo basename($_SERVER['PHP_SELF']) == 'articles.php' ? '#34495e' : 'transparent'; ?>; 
                          border-left: 4px solid <?php echo basename($_SERVER['PHP_SELF']) == 'articles.php' ? '#3498db' : 'transparent'; ?>;">
                    <i class="fas fa-newspaper"></i> Articles
                </a>
            </li>
            <li style="margin-bottom: 5px;">
                <a href="categories.php" 
                   style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: #bdc3c7; text-decoration: none; 
                          background: <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? '#34495e' : 'transparent'; ?>; 
                          border-left: 4px solid <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? '#3498db' : 'transparent'; ?>;">
                    <i class="fas fa-folder"></i> Catégories
                </a>
            </li>
            <li style="margin-bottom: 5px;">
                <a href="comments.php" 
                   style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: #bdc3c7; text-decoration: none; 
                          background: <?php echo basename($_SERVER['PHP_SELF']) == 'comments.php' ? '#34495e' : 'transparent'; ?>; 
                          border-left: 4px solid <?php echo basename($_SERVER['PHP_SELF']) == 'comments.php' ? '#3498db' : 'transparent'; ?>;">
                    <i class="fas fa-comments"></i> Commentaires
                    <?php 
                    $pending_count = Database::fetch("SELECT COUNT(*) as count FROM comments WHERE is_approved = 0")['count'];
                    if($pending_count > 0): ?>
                    <span style="background: #e74c3c; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem;">
                        <?php echo $pending_count; ?>
                    </span>
                    <?php endif; ?>
                </a>
            </li>
            <li style="margin-bottom: 5px;">
                <a href="users.php" 
                   style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: #bdc3c7; text-decoration: none; 
                          background: <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? '#34495e' : 'transparent'; ?>; 
                          border-left: 4px solid <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? '#3498db' : 'transparent'; ?>;">
                    <i class="fas fa-users"></i> Utilisateurs
                </a>
            </li>
            <li style="margin-bottom: 5px;">
                <a href="contacts.php" 
                   style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: #bdc3c7; text-decoration: none; 
                          background: <?php echo basename($_SERVER['PHP_SELF']) == 'contacts.php' ? '#34495e' : 'transparent'; ?>; 
                          border-left: 4px solid <?php echo basename($_SERVER['PHP_SELF']) == 'contacts.php' ? '#3498db' : 'transparent'; ?>;">
                    <i class="fas fa-envelope"></i> Messages
                </a>
            </li>
            <li style="margin-bottom: 5px;">
                <a href="newsletter.php" 
                   style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: #bdc3c7; text-decoration: none; 
                          background: <?php echo basename($_SERVER['PHP_SELF']) == 'newsletter.php' ? '#34495e' : 'transparent'; ?>; 
                          border-left: 4px solid <?php echo basename($_SERVER['PHP_SELF']) == 'newsletter.php' ? '#3498db' : 'transparent'; ?>;">
                    <i class="fas fa-mail-bulk"></i> Newsletter
                </a>
            </li>
            <li style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #34495e;">
                <a href="../index.php" 
                   style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: #bdc3c7; text-decoration: none;">
                    <i class="fas fa-globe"></i> Voir le site
                </a>
            </li>
            <li>
                <a href="../logout.php" 
                   style="display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: #e74c3c; text-decoration: none;">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </li>
        </ul>
    </nav>
</div>