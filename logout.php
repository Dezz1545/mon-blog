<?php
require_once 'includes/config.php';

// Détruire la session
session_destroy();

// Rediriger vers l'accueil
$_SESSION['message'] = "Vous avez été déconnecté avec succès.";
$_SESSION['message_type'] = "success";

redirect('index.php');
?>