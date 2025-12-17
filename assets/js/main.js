/* exported formatDate, truncateText, copyToClipboard, toggleDarkMode */
/**
 * Script principal du blog
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ===== MENU MOBILE =====
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');
    
    if(mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            if(navLinks) {
                navLinks.classList.toggle('show');
            }
        });
    }
    
    // ===== FORM VALIDATION =====
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Validation basique des champs requis
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if(!field.value.trim()) {
                    isValid = false;
                    highlightError(field);
                } else {
                    removeHighlight(field);
                }
            });
            
            // Validation spécifique pour les emails
            const emailFields = form.querySelectorAll('input[type="email"]');
            emailFields.forEach(field => {
                if(field.value && !isValidEmail(field.value)) {
                    isValid = false;
                    highlightError(field, 'Email invalide');
                }
            });
            
            // Validation des mots de passe
            const passwordFields = form.querySelectorAll('input[type="password"]');
            passwordFields.forEach(field => {
                if(field.value && field.value.length < 8) {
                    isValid = false;
                    highlightError(field, 'Minimum 8 caractères');
                }
            });
            
            if(!isValid) {
                e.preventDefault();
                showNotification('Veuillez corriger les erreurs dans le formulaire', 'error');
            }
        });
    });
    
    // ===== LIKE SYSTEM =====
    const likeButtons = document.querySelectorAll('.like-btn');
    
    likeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            /* eslint-disable-next-line no-unused-vars */
            const articleId = this.dataset.articleId;
            const isLiked = this.classList.contains('liked');
            
            // Animation visuelle
            if(!isLiked) {
                this.innerHTML = '<i class="fas fa-heart"></i> J\'aime';
                this.classList.add('liked');
                this.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    this.style.transform = 'scale(1)';
                }, 300);
            } else {
                this.innerHTML = '<i class="far fa-heart"></i> Like';
                this.classList.remove('liked');
            }
            
            // Envoi AJAX (optionnel)
            // toggleLike(articleId, !isLiked);
        });
    });
    
    // ===== COMMENT SYSTEM =====
    const commentForm = document.getElementById('comment-form');
    
    if(commentForm) {
        commentForm.addEventListener('submit', function(e) {
            const commentText = this.querySelector('textarea').value;
            
            if(commentText.length < 3) {
                e.preventDefault();
                showNotification('Le commentaire doit contenir au moins 3 caractères', 'error');
                return;
            }
            
            // Afficher un indicateur de chargement
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publication...';
            submitBtn.disabled = true;
        });
    }
    
    // ===== SEARCH SUGGESTIONS =====
    const searchInput = document.getElementById('search-input');
    
    if(searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            if(this.value.length > 2) {
                searchTimeout = setTimeout(() => {
                    fetchSearchSuggestions(this.value);
                }, 500);
            }
        });
    }
    
    // ===== IMAGE PREVIEW =====
    const imageInputs = document.querySelectorAll('input[type="file"][accept^="image"]');
    
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = this.files[0];
            const previewId = this.dataset.preview;
            
            if(file && previewId) {
                const preview = document.getElementById(previewId);
                if(preview) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    
                    reader.readAsDataURL(file);
                }
            }
        });
    });
    
    // ===== PASSWORD STRENGTH =====
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(input => {
        const strengthMeter = document.getElementById('password-strength');
        
        if(strengthMeter) {
            input.addEventListener('input', function() {
                const strength = calculatePasswordStrength(this.value);
                updateStrengthMeter(strengthMeter, strength);
            });
        }
    });
    
    // ===== FUNCTIONS =====
    
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    function highlightError(field, message = 'Ce champ est requis') {
        field.style.borderColor = '#e74c3c';
        
        // Créer ou mettre à jour le message d'erreur
        let errorMsg = field.nextElementSibling;
        if(!errorMsg || !errorMsg.classList.contains('error-message')) {
            errorMsg = document.createElement('div');
            errorMsg.className = 'error-message';
            field.parentNode.insertBefore(errorMsg, field.nextSibling);
        }
        
        errorMsg.textContent = message;
        errorMsg.style.color = '#e74c3c';
        errorMsg.style.fontSize = '0.85rem';
        errorMsg.style.marginTop = '5px';
    }
    
    function removeHighlight(field) {
        field.style.borderColor = '';
        
        // Supprimer le message d'erreur
        const errorMsg = field.nextElementSibling;
        if(errorMsg && errorMsg.classList.contains('error-message')) {
            errorMsg.remove();
        }
    }
    
    function showNotification(message, type = 'info') {
        // Créer la notification
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        `;
        
        // Styles
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.padding = '15px 20px';
        notification.style.borderRadius = '6px';
        notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        notification.style.zIndex = '9999';
        notification.style.display = 'flex';
        notification.style.alignItems = 'center';
        notification.style.justifyContent = 'space-between';
        notification.style.minWidth = '300px';
        notification.style.maxWidth = '400px';
        
        // Couleurs selon le type
        switch(type) {
            case 'success':
                notification.style.background = '#d4edda';
                notification.style.color = '#155724';
                notification.style.border = '1px solid #c3e6cb';
                break;
            case 'error':
                notification.style.background = '#f8d7da';
                notification.style.color = '#721c24';
                notification.style.border = '1px solid #f5c6cb';
                break;
            case 'warning':
                notification.style.background = '#fff3cd';
                notification.style.color = '#856404';
                notification.style.border = '1px solid #ffeaa7';
                break;
            default:
                notification.style.background = '#d1ecf1';
                notification.style.color = '#0c5460';
                notification.style.border = '1px solid #bee5eb';
        }
        
        // Bouton de fermeture
        const closeBtn = notification.querySelector('.notification-close');
        if(closeBtn) {
            closeBtn.style.background = 'transparent';
            closeBtn.style.border = 'none';
            closeBtn.style.fontSize = '1.5rem';
            closeBtn.style.cursor = 'pointer';
            closeBtn.style.marginLeft = '10px';
            
            closeBtn.addEventListener('click', function() {
                notification.remove();
            });
        }
        
        // Ajouter au DOM
        document.body.appendChild(notification);
        
        // Supprimer automatiquement après 5 secondes
        setTimeout(() => {
            if(notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
    
    function fetchSearchSuggestions(query) {
        // À implémenter avec AJAX
        console.log('Recherche de:', query);
    }
    
    function calculatePasswordStrength(password) {
        let strength = 0;
        
        if(password.length >= 8) strength++;
        if(/[A-Z]/.test(password)) strength++;
        if(/[a-z]/.test(password)) strength++;
        if(/[0-9]/.test(password)) strength++;
        if(/[^A-Za-z0-9]/.test(password)) strength++;
        
        return strength;
    }
    
    function updateStrengthMeter(meter, strength) {
        meter.style.width = (strength * 20) + '%';
        
        switch(strength) {
            case 0:
            case 1:
                meter.style.backgroundColor = '#e74c3c';
                break;
            case 2:
                meter.style.backgroundColor = '#e67e22';
                break;
            case 3:
                meter.style.backgroundColor = '#f1c40f';
                break;
            case 4:
                meter.style.backgroundColor = '#2ecc71';
                break;
            case 5:
                meter.style.backgroundColor = '#27ae60';
                break;
        }
    }
    
    // ===== INITIALISATIONS =====
    
    // Activer les tooltips Bootstrap si présents
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if(tooltips.length > 0 && typeof bootstrap !== 'undefined') {
        tooltips.forEach(tooltip => {
            new bootstrap.Tooltip(tooltip);
        });
    }
    
    // Gérer les confirmations de suppression
    const deleteLinks = document.querySelectorAll('a[onclick*="confirm"]');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if(!confirm(this.getAttribute('data-confirm') || 'Êtes-vous sûr ?')) {
                e.preventDefault();
            }
        });
    });
    
    // ===== ANIMATIONS =====
    
    // Animation au scroll
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('.animate-on-scroll');
        
        elements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if(elementTop < windowHeight - 100) {
                element.classList.add('animated');
            }
        });
    };
    
    window.addEventListener('scroll', animateOnScroll);
    animateOnScroll(); // Exécuter au chargement
    
    // ===== CHARTS (optionnel) =====
    if(typeof Chart !== 'undefined') {
        const chartElements = document.querySelectorAll('.chart-container');
        
        chartElements.forEach(container => {
            const ctx = container.querySelector('canvas');
            if(ctx) {
                const chartType = container.dataset.chartType || 'bar';
                const chartData = JSON.parse(container.dataset.chartData || '{}');
                
                new Chart(ctx, {
                    type: chartType,
                    data: chartData,
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        }
                    }
                });
            }
        });
    }
});

// ===== FONCTIONS GLOBALES =====

/**
 * Formatte une date
 */
function formatDate(dateString, format = 'fr-FR') {
    const date = new Date(dateString);
    return date.toLocaleDateString(format, {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

/**
 * Tronque un texte
 */
function truncateText(text, maxLength = 100) {
    if(text.length <= maxLength) return text;
    return text.substr(0, maxLength) + '...';
}

/**
 * Copie du texte dans le presse-papier
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text)
        .then(() => {
            console.log('Texte copié:', text);
        })
        .catch(err => {
            console.error('Erreur de copie:', err);
        });
}

/**
 * Basculer le mode sombre
 */
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
}

// Expose functions to global scope for inline usage
if (typeof window !== 'undefined') {
    window.formatDate = formatDate;
    window.truncateText = truncateText;
    window.copyToClipboard = copyToClipboard;
    window.toggleDarkMode = toggleDarkMode;
}

// Vérifier le mode sombre au chargement
if(localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
}