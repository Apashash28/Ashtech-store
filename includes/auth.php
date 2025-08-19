<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] != true) {
    header('Location: admin_login.php'); // Rediriger vers la page de connexion si non connecté
    exit();
}
