<?php
$host = 'sql106.iceiy.com';
$dbname = 'icei_39016282_digitek_empire';
$username = 'icei_39016282';
$password = 'yVsf9qzAw1ag';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4"); // IMPORTANT pour que les emojis fonctionnent
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
