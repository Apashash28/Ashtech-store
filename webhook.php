<?php
// webhook.php - Reçoit les notifications de paiement de Money Fusion

try {
    // Connexion à la base de données
     $pdo = new PDO("mysql:host=sql106.iceiy.com;dbname=icei_39016282_digitek_empire;charset=utf8mb4", "icei_39016282", "yVsf9qzAw1ag");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Lire les données JSON du POST
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    // Journalisation temporaire (utile pour débogage)
    file_put_contents("webhook_log.txt", date("Y-m-d H:i:s") . " - Reçu: " . $input . PHP_EOL, FILE_APPEND);

    // Vérification du token
    if (!$data || empty($data['personal_Info']['tokenPay'])) {
        http_response_code(400);
        echo "❌ Aucun token fourni.";
        exit;
    }

    // Extraction des infos
    $token = $data['personal_Info']['tokenPay'];
    $produit_id = $data['personal_Info']['produit_id'] ?? null;
    $nom_client = $data['name'] ?? '';
    $numero_client = $data['number'] ?? '';
    $email = $data['email'] ?? '';
    $montant = $data['amount'] ?? 0;

    // Nettoyage du statut
    $statut_brut = strtolower(trim($data['status'] ?? 'en_attente'));
    $statut_brut = iconv('UTF-8', 'UTF-8//IGNORE', $statut_brut);

    // Normalisation du statut
    $statut = match ($statut_brut) {
        'payé', 'payÃ©', 'pay�', 'paid' => 'paid',
        'annulé', 'cancelled', 'canceled' => 'cancelled',
        'en_attente', 'pending' => 'en_attente',
        default => 'en_attente'
    };

    // Vérifie si la vente existe déjà
    $check = $pdo->prepare("SELECT id, statut FROM ventes WHERE tokenPay = ?");
    $check->execute([$token]);

    if ($vente = $check->fetch()) {
        // Met à jour seulement si le statut a changé (pour éviter des mises à jour inutiles)
        if ($vente['statut'] !== $statut) {
            $update = $pdo->prepare("UPDATE ventes SET statut = ?, montant = ? WHERE id = ?");
            $update->execute([$statut, $montant, $vente['id']]);
            file_put_contents("webhook_log.txt", date("Y-m-d H:i:s") . " - Mise à jour statut vente id {$vente['id']} en '$statut'" . PHP_EOL, FILE_APPEND);
        }
        http_response_code(200);
        echo "✅ Vente mise à jour avec statut: $statut";
        exit;
    }

    // Sinon : insérer une nouvelle vente
    $stmt = $pdo->prepare("
        INSERT INTO ventes (produit_id, nom_client, numero_client, email, montant, statut, tokenPay, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $produit_id,
        $nom_client,
        $numero_client,
        $email,
        $montant,
        $statut,
        $token
    ]);

    file_put_contents("webhook_log.txt", date("Y-m-d H:i:s") . " - Nouvelle vente enregistrée avec statut: $statut" . PHP_EOL, FILE_APPEND);

    http_response_code(201);
    echo "✅ Nouvelle vente enregistrée avec statut: $statut";
} catch (Exception $e) {
    file_put_contents("webhook_log.txt", date("Y-m-d H:i:s") . " - ERREUR: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    echo "❌ Erreur serveur.";
}
?>
