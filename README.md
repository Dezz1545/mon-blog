# 🚀 Blog PHP Natif - Système de Gestion de Contenu

Un système de blog complet développé en PHP natif sans framework, avec dashboard administrateur, gestion d'articles, commentaires, utilisateurs et newsletter.

<img width="1919" height="927" alt="image" src="https://github.com/user-attachments/assets/d275389d-1f3a-41b2-8f6d-842f0ebf174e" />

*Capture d'écran du dashboard administrateur*

## ✨ Fonctionnalités

### 🎯 Frontend Public
- ✅ **Page d'accueil** avec articles récents et populaires
- ✅ **Système de blog** avec filtres (catégorie, popularité, recherche)
- ✅ **Articles détaillés** avec commentaires et likes
- ✅ **Inscription/Connexion** avec contraintes mot de passe
- ✅ **Formulaire de contact** avec validation
- ✅ **Newsletter** avec inscription/désinscription
- ✅ **Design responsive** (mobile, tablette, desktop)

### 🛠️ Dashboard Administrateur
- ✅ **Tableau de bord** avec statistiques
- ✅ **Gestion CRUD articles** (création, édition, suppression)
- ✅ **Gestion catégories** et sous-catégories
- ✅ **Modération commentaires** (approuver/rejeter)
- ✅ **Gestion utilisateurs** avec rôles (admin/author/user)
- ✅ **Visualisation messages contact**
- ✅ **Gestion newsletter** (export CSV, envoi)

### 🔐 Sécurité & Administration
- ✅ **Système d'authentification** sécurisé (bcrypt)
- ✅ **Rôles utilisateurs** (Admin, Auteur, Utilisateur)
- ✅ **Protection CSRF** sur les formulaires
- ✅ **Validation données** côté serveur et client
- ✅ **Upload sécurisé** d'images
- ✅ **Récupération mot de passe** par email


## 🚀 Installation

### Prérequis
- Serveur web (Apache/Nginx)
- PHP 8.0+ avec extensions PDO, MySQLi
- MySQL 5.7+ ou MariaDB 10.2+
- Composer (optionnel)

### Étapes d'installation

1. **Cloner le dépôt**

git clone https://github.com/votre-utilisateur/mon-blog.git
cd mon-blog

2. **Créer la base de données
CREATE DATABASE blog_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

3. **Importer la structure (fichier database.sql fourni)
USE blog_db;
SOURCE chemin/vers/database.sql;

4. **Config
# Copier le fichier de configuration
cp includes/config.example.php includes/config.php

# Éditer les paramètres
nano includes/config.php

Test connexion base de données
http://localhost/mon-blog/test-db.php

Lien au site :
http://localhost/mon-blog/

Première connexion admin
Par défaut, un compte admin est créé :

Email : landry@gmail.com

Mot de passe : Landry123@


Première connexion User1
un compte User1 est créé pour les test :

Email : User1@gmail.com

Mot de passe : User1234@

