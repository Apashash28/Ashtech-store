<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
session_destroy(); // Détruire toutes les sessions
header('Location: admin_login.php'); // Rediriger vers la page de connexion après la déconnexion
exit();
