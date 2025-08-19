<?php
// ⚙️ Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=sql106.iceiy.com;dbname=icei_39016282_digitek_empire;charset=utf8mb4", "icei_39016282", "yVsf9qzAw1ag");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    echo "Erreur de connexion à la base de données.";
    exit;
}

// 🔐 Récupération sécurisée des paramètres GET
$produit_id = $_GET['id'] ?? '';
$token = $_GET['token'] ?? '';

if (empty($produit_id) || empty($token)) {
    http_response_code(400);
    echo "Paramètres manquants.";
    exit;
}

// 🔍 Vérifie si la vente existe avec ce produit et ce token
$stmt = $pdo->prepare("
    SELECT v.*, p.fichier_path 
    FROM ventes v 
    JOIN produits p ON v.produit_id = p.id 
    WHERE p.id = ? AND v.tokenPay COLLATE utf8mb4_general_ci = ?
");
$stmt->execute([$produit_id, $token]);
$vente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vente) {
    http_response_code(403);
    echo "Accès interdit. Ce téléchargement n'est pas autorisé.";
    exit;
}

// 📁 Détermination du fichier à servir
if (!empty($vente['fichier_path'])) {
    $fichierRelatif = $vente['fichier_path'];
} else {
    // Recherche dans dossier fichiers avec plusieurs extensions possibles
    $dossier = __DIR__ . '/fichiers/';
    $base = "produit-$produit_id";
    $extensions = ['pdf', 'zip', 'rar', 'mp3', 'mp4', 'jpg', 'png', 'txt', 'docx']; // adapter selon besoin

    $fichierRelatif = null;
    foreach ($extensions as $ext) {
        $fichierTest = $dossier . $base . '.' . $ext;
        if (file_exists($fichierTest)) {
            $fichierRelatif = 'fichiers/' . $base . '.' . $ext;
            break;
        }
    }

    if (!$fichierRelatif) {
        http_response_code(404);
        echo "Fichier introuvable.";
        exit;
    }
}

$fichierComplet = __DIR__ . '/' . ltrim($fichierRelatif, '/');

if (!file_exists($fichierComplet)) {
    http_response_code(404);
    echo "Fichier introuvable.";
    exit;
}

// 🛡️ Détection MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $fichierComplet);
finfo_close($finfo);

// Par sécurité, si MIME non détecté, on force octet-stream
if (!$mimeType) {
    $mimeType = "application/octet-stream";
}

// ✅ Envoi sécurisé du fichier
header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . basename($fichierComplet) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($fichierComplet));

flush();
readfile($fichierComplet);
exit;
