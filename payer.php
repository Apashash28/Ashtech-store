<?php
// payer.php -- Crée un paiement via l'API Money Fusion ou affiche un formulaire client

try {
    $pdo = new PDO(
        "mysql:host=sql106.iceiy.com;dbname=icei_39016282_digitek_empire;charset=utf8mb4",
        "icei_39016282",
        "yVsf9qzAw1ag",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die("Erreur de connexion à la base de données.");
}

$produit_id = $_GET['id'] ?? null;
if (!$produit_id) {
    die("Produit non spécifié.");
}

$stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
$stmt->execute([$produit_id]);
$produit = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$produit) {
    die("Produit introuvable.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Nettoyage et encodage UTF-8 des données client
    $nom_client = trim($_POST['nom'] ?? 'Client');
    $numero = trim($_POST['numero'] ?? '01010101');
    $email = trim($_POST['email'] ?? 'client@example.com');

    $nom_client = mb_convert_encoding($nom_client, 'UTF-8', 'auto');
    $numero = mb_convert_encoding($numero, 'UTF-8', 'auto');
    $email = mb_convert_encoding($email, 'UTF-8', 'auto');

    $prix = $produit['prix_promo'] ?: $produit['prix_original'];
    $nom_produit = mb_convert_encoding($produit['nom'], 'UTF-8', 'auto');

    $return_url = "https://digitekempire.iceiy.com/merci.php";
    $webhook_url = "https://digitekempire.iceiy.com/webhook.php";

    $data = [
        "totalPrice" => (int)$prix,
        "article" => [$nom_produit => (int)$prix],
        "personal_Info" => [
            "produit_id" => (int)$produit_id,
            "email" => $email
        ],
        "numeroSend" => $numero,
        "nomclient" => $nom_client,
        "return_url" => $return_url,
        "webhook_url" => $webhook_url
    ];

    $json_data = json_encode($data, JSON_UNESCAPED_UNICODE);

    $ch = curl_init("https://www.pay.moneyfusion.net/DIGITEK_EMPIRE/6086524cc0531dc1/pay/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json; charset=utf-8"]);
    $response = curl_exec($ch);
    curl_close($ch);

    $res = json_decode($response, true);

    if (isset($res['statut']) && $res['statut'] && isset($res['url']) && isset($res['token'])) {
        $tokenPay = $res['token'];

        $stmt = $pdo->prepare("INSERT INTO ventes (produit_id, nom_client, numero_client, email, montant, statut, tokenPay, created_at)
                               VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $produit_id,
            $nom_client,
            $numero,
            $email,
            $prix,
            'en_attente',
            $tokenPay
        ]);

        header("Location: " . $res['url']);
        exit;
    } else {
        $erreur_paiement = "Échec du paiement. Réponse API : <pre>" . htmlspecialchars($response, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</pre>";
    }
}
?>


<!DOCTYPE html>
<html lang="fr" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finaliser votre achat | DIGITEK EMPIRE</title>
    <!-- Script immédiat pour forcer le mode clair -->
    <script>
        // Forcer immédiatement le mode clair
        document.documentElement.classList.add('light');
        document.documentElement.classList.remove('dark');
    </script>
    <!-- Inclure Tailwind CSS et Font Awesome via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Favicon de base (PNG) -->
    <link rel="icon" type="image/png" href="img.png">
    
    <!-- Pour les appareils Apple (iPhone, iPad) -->
    <link rel="apple-touch-icon" href="img.png">
    
    <!-- Pour éviter les problèmes de cache -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#5D5CDE',
                        secondary: '#6366F1',
                        accent: '#4F46E5',
                        dark: '#1F2937',
                        light: '#F9FAFB'
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'bounce-slow': 'bounce 2s infinite',
                        'float': 'float 6s ease-in-out infinite',
                        'shimmer': 'shimmer 2s linear infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' },
                        },
                        shimmer: {
                            '0%': { backgroundPosition: '-200% 0' },
                            '100%': { backgroundPosition: '200% 0' },
                        }
                    },
                    boxShadow: {
                        'soft': '0 5px 15px rgba(0, 0, 0, 0.05)',
                        'medium': '0 8px 30px rgba(0, 0, 0, 0.12)',
                    }
                },
                darkMode: 'class', // Configurer Tailwind pour utiliser class au lieu de media
            },
        }

        // Fonction de basculement du thème - uniquement pour le bouton
        function toggleDarkMode() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                document.documentElement.classList.add('light');
            } else {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('light');
            }
        }
        
        // Forcer le mode clair au chargement
        window.onload = function() {
            document.documentElement.classList.add('light');
            document.documentElement.classList.remove('dark');
        }
    </script>
    <style>
        /* S'assurer que le mode clair est toujours prioritaire */
        html:not(.dark) {
            background-color: #FFFFFF;
            color: #1F2937;
        }
        
        /* Animation pour les éléments en entrée */
        .fade-in {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }
        
        .fade-in.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Effet 3D sur les cartes */
        .card-3d-effect {
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-3d-effect:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .dark .card-3d-effect {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }
        
        .dark .card-3d-effect:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }
        
        /* Animation du bouton */
        @keyframes pulse-border {
            0% {
                box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(79, 70, 229, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(79, 70, 229, 0);
            }
        }
        
        .pulse-border {
            animation: pulse-border 2s infinite;
        }
        
        /* Style pour les inputs */
        .input-style {
            transition: all 0.3s ease;
        }
        
        .input-style:focus {
            border-color: #5D5CDE;
            box-shadow: 0 0 0 3px rgba(93, 92, 222, 0.2);
        }
        
        /* Amélioration de la responsivité sur petits écrans */
        @media (max-width: 640px) {
            .payment-badge {
                flex-basis: calc(50% - 0.5rem);
            }
        }
        
        /* Animation au chargement */
        @keyframes fadeInUp {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.5s ease forwards;
        }
    </style>
</head>
<body class="bg-white font-sans leading-normal tracking-normal text-gray-800 transition-colors duration-300 min-h-screen">

    <!-- Barre de navigation fixe -->
    <nav class="fixed top-0 w-full bg-white shadow-sm z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="index.php" class="flex items-center space-x-2 group">
                <div class="relative">
                    <i class="fas fa-digital-tachograph text-primary text-2xl transition-transform duration-300 group-hover:scale-110"></i>
                </div>
                <span class="font-bold text-xl text-gray-800">DIGITEK EMPIRE</span>
            </a>
            <div class="flex items-center space-x-4">
                <button onclick="toggleDarkMode()" class="p-2 rounded-full hover:bg-gray-200 transition-colors duration-300">
                    <i class="fas fa-moon text-gray-600"></i>
                    <i class="fas fa-sun hidden text-yellow-300"></i>
                </button>
                <a href="index.php" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition-colors duration-300 flex items-center shadow-sm">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <span class="hidden sm:inline">Retour à la boutique</span>
                    <span class="sm:hidden">Boutique</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Espace pour la barre de navigation fixe -->
    <div class="h-16"></div>

    <!-- En-tête du paiement -->
    <header class="relative bg-gradient-to-r from-primary to-accent text-white py-12 mb-10 overflow-hidden fade-in">
        <div class="container mx-auto text-center relative z-10 px-4">
            <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold mb-4">Finaliser votre commande</h1>
            <p class="text-xl text-white/90 max-w-2xl mx-auto">Un dernier pas vers l'excellence numérique</p>
            
            <!-- Badge produit digital -->
            <div class="mt-6 inline-block">
                <div class="px-4 py-2 bg-white/10 rounded-full text-sm font-medium flex items-center">
                    <i class="fas fa-lock text-yellow-300 mr-2"></i>
                    <span>Paiement Sécurisé</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Contenu principal -->
    <div class="container mx-auto px-4 mb-16">
        <div class="max-w-4xl mx-auto">
            <!-- Grille principale -->
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Résumé de la commande -->
                <div class="w-full lg:w-5/12 order-1">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden fade-in">
                        <div class="bg-gradient-to-r from-primary to-accent text-white p-4">
                            <h2 class="text-xl font-bold flex items-center">
                                <i class="fas fa-shopping-cart mr-2"></i>
                                Résumé de votre commande
                            </h2>
                        </div>
                        
                        <div class="p-5 space-y-4">
                            <div class="flex items-center space-x-4">
                                <?php if (!empty($produit['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($produit['image_url']) ?>" alt="<?= htmlspecialchars($produit['nom']) ?>" class="w-16 h-16 object-cover rounded-lg border">
                                <?php else: ?>
                                    <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-shopping-bag text-gray-400 text-xl"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div>
                                    <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($produit['nom']) ?></h3>
                                    <p class="text-sm text-gray-500">
                                        <?= !empty($produit['reference']) ? 'Réf: ' . htmlspecialchars($produit['reference']) : 'Produit digital premium' ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Prix du produit</span>
                                    <span class="font-medium">
                                        <?= number_format(($produit['prix_original'] ?? 0), 0, ',', ' ') ?> F CFA
                                    </span>
                                </div>
                                
                                <?php if (!empty($produit['prix_promo']) && $produit['prix_promo'] < ($produit['prix_original'] ?? 0)): ?>
                                    <div class="flex justify-between text-sm mt-1">
                                        <span class="text-gray-600">Réduction</span>
                                        <span class="font-medium text-green-600">
                                            -<?= number_format(($produit['prix_original'] - $produit['prix_promo']), 0, ',', ' ') ?> F CFA
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-4 pb-1">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-gray-800">Total</span>
                                    <span class="font-bold text-xl text-primary">
                                        <?= number_format(($produit['prix_promo'] ?: $produit['prix_original']), 0, ',', ' ') ?> F CFA
                                    </span>
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 rounded-lg p-4 text-sm">
                                <div class="flex items-start space-x-2">
                                    <i class="fas fa-info-circle text-primary mt-1"></i>
                                    <p class="text-gray-600">Après votre paiement, vous recevrez un accès immédiat à votre produit digital premium.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section des méthodes de paiement pour mobile -->
                    <div class="mt-6 bg-white rounded-xl shadow-md p-5 fade-in lg:hidden">
                        <h3 class="font-bold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-shield-alt text-primary mr-2"></i>
                            Paiement 100% Sécurisé
                        </h3>
                        
                        <div class="flex flex-wrap gap-3 mt-3">
                            <div class="payment-badge bg-gray-100 p-2 rounded-md flex items-center justify-center">
                                <i class="fas fa-mobile-alt text-gray-600 mr-2"></i>
                                <span class="text-sm font-medium">Mobile Money</span>
                            </div>
                            <div class="payment-badge bg-gray-100 p-2 rounded-md flex items-center justify-center">
                                <i class="fas fa-credit-card text-gray-600 mr-2"></i>
                                <span class="text-sm font-medium">Carte Bancaire</span>
                            </div>
                            <div class="payment-badge bg-gray-100 p-2 rounded-md flex items-center justify-center">
                                <i class="fas fa-lock text-gray-600 mr-2"></i>
                                <span class="text-sm font-medium">Paiement Sécurisé</span>
                            </div>
                        </div>
                        
                        <div class="mt-5 flex items-center justify-center">
                            <div class="w-full max-w-xs mx-auto flex items-center space-x-2 text-sm text-gray-500">
                                <i class="fas fa-shield-alt text-green-600"></i>
                                <span>Paiement sécurisé par Money Fusion</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Formulaire de paiement -->
                <div class="w-full lg:w-7/12 order-2 mt-8 lg:mt-0">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden fade-in">
                        <div class="bg-gradient-to-r from-primary to-accent text-white p-4">
                            <h2 class="text-xl font-bold flex items-center">
                                <i class="fas fa-user-circle mr-2"></i>
                                Vos informations
                            </h2>
                        </div>
                        
                        <?php if (!empty($erreur_paiement)): ?>
                            <div class="bg-red-100 text-red-700 p-4 border-l-4 border-red-500">
                                <?= $erreur_paiement ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="p-6">
                            <form method="POST" class="space-y-5">
                                <div>
                                    <label for="nom" class="block text-sm font-medium text-gray-700 mb-1">Nom complet</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-user text-gray-400"></i>
                                        </div>
                                        <input type="text" id="nom" name="nom" required 
                                            class="w-full pl-10 pr-3 py-3 rounded-lg border border-gray-300 bg-white text-gray-800 text-base focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all duration-300 input-style"
                                            placeholder="Votre nom et prénom">
                                    </div>
                                </div>
                                
                                <div>
    <label for="numero" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
        Numéro WhatsApp <span class="text-red-500">*</span>
    </label>
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i class="fab fa-whatsapp text-green-500"></i>
        </div>
        <input type="tel" id="numero" name="numero" required
            pattern="^\+\d{6,15}$"
            title="Veuillez entrer un numéro avec l'indicatif (ex: +229xxxxxxxx)"
            class="w-full pl-10 pr-3 py-3 rounded-lg border border-gray-300 bg-white text-gray-800 text-base focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all duration-300 input-style"
            placeholder="+229XXXXXXXX">
    </div>
    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400 flex flex-wrap items-start leading-relaxed">
    <i class="fas fa-info-circle mr-1 mt-0.5 text-blue-500 shrink-0"></i>
    <span>
        Entrez votre numéro <strong>WhatsApp actif</strong> avec l’indicatif international.<br class="sm:hidden" />
        Exemple : <strong>+229xxxxxxxx</strong>
    </span>
</p>

</div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Adresse email</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-envelope text-gray-400"></i>
                                        </div>
                                        <input type="email" id="email" name="email" required 
                                            class="w-full pl-10 pr-3 py-3 rounded-lg border border-gray-300 bg-white text-gray-800 text-base focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all duration-300 input-style"
                                            placeholder="Votre adresse email">
                                    </div>
                                </div>
                                
                                <div class="pt-3">
                                    <button type="submit" 
                                        class="w-full bg-primary hover:bg-accent text-white font-bold py-3.5 px-6 rounded-lg transition-all duration-300 transform hover:-translate-y-1 hover:shadow-md pulse-border flex items-center justify-center text-lg">
                                        <i class="fas fa-lock mr-2"></i>
                                        Payer <?= number_format(($produit['prix_promo'] ?: $produit['prix_original']), 0, ',', ' ') ?> F CFA
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Section des méthodes de paiement pour desktop -->
                    <div class="mt-6 bg-white rounded-xl shadow-md p-5 fade-in hidden lg:block">
                        <h3 class="font-bold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-shield-alt text-primary mr-2"></i>
                            Paiement 100% Sécurisé
                        </h3>
                        
                        <div class="flex flex-wrap gap-3 mt-3">
                            <div class="bg-gray-100 p-2 rounded-md flex items-center justify-center">
                                <i class="fas fa-mobile-alt text-gray-600 mr-2"></i>
                                <span class="text-sm font-medium">Mobile Money</span>
                            </div>
                            <div class="bg-gray-100 p-2 rounded-md flex items-center justify-center">
                                <i class="fas fa-credit-card text-gray-600 mr-2"></i>
                                <span class="text-sm font-medium">Carte Bancaire</span>
                            </div>
                            <div class="bg-gray-100 p-2 rounded-md flex items-center justify-center">
                                <i class="fas fa-lock text-gray-600 mr-2"></i>
                                <span class="text-sm font-medium">Paiement Sécurisé</span>
                            </div>
                        </div>
                        
                        <div class="mt-5 flex items-center justify-center">
                            <div class="w-full max-w-xs mx-auto flex items-center space-x-2 text-sm text-gray-500">
                                <i class="fas fa-shield-alt text-green-600"></i>
                                <span>Paiement sécurisé par Money Fusion</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Engagements client -->
                    <div class="mt-6 bg-white rounded-xl shadow-md p-5 fade-in">
                        <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-check-circle text-primary mr-2"></i>
                            Nos engagements
                        </h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                                    <i class="fas fa-bolt"></i>
                                </div>
                                <p class="text-sm text-gray-600">Accès immédiat après paiement</p>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                                    <i class="fas fa-headset"></i>
                                </div>
                                <p class="text-sm text-gray-600">Support technique 24/7</p>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                                    <i class="fas fa-sync"></i>
                                </div>
                                <p class="text-sm text-gray-600">Mises à jour gratuites incluses</p>
                            </div>
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <p class="text-sm text-gray-600">Satisfaction garantie ou remboursé</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pied de page -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="text-center md:text-left mb-6 md:mb-0">
                    <div class="flex items-center justify-center md:justify-start">
                        <i class="fas fa-digital-tachograph text-white text-2xl mr-2"></i>
                        <h2 class="text-2xl font-bold">DIGITEK EMPIRE</h2>
                    </div>
                    <p class="text-gray-400 mt-2">Solutions digitales premium pour votre réussite</p>
                </div>
                
                <div class="flex space-x-4">
                    <a href="https://www.facebook.com/profile.php?id=61573555000061" class="group">
                        <div class="bg-gray-700 w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300 group-hover:bg-blue-600">
                            <i class="fab fa-facebook-f text-white"></i>
                        </div>
                    </a>
                    <a href="https://www.instagram.com/0eliteempire0" class="group">
                        <div class="bg-gray-700 w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300 group-hover:bg-pink-600">
                            <i class="fab fa-instagram text-white"></i>
                        </div>
                    </a>
                    <a href="https://wa.me/22961517802" class="group">
                        <div class="bg-gray-700 w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300 group-hover:bg-green-500">
                            <i class="fab fa-whatsapp text-white"></i>
                        </div>
                    </a>
                </div>
            </div>
            
            <hr class="border-gray-700 my-6">
            
            <div class="text-center text-gray-400 text-sm">
                <p>&copy; 2025 DIGITEK EMPIRE. Tous droits réservés.</p>
                <p class="mt-2">Conçu pour offrir la meilleure expérience utilisateur</p>
            </div>
        </div>
    </footer>

    <script>
        // Forcer le mode clair IMMÉDIATEMENT
        document.documentElement.classList.add('light');
        document.documentElement.classList.remove('dark');
        
        // Animation des éléments au chargement
        document.addEventListener('DOMContentLoaded', function() {
            // Forcer à nouveau le mode clair
            document.documentElement.classList.add('light');
            document.documentElement.classList.remove('dark');
            
            // Animation des éléments
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('is-visible');
                }, 100 * index);
            });
        });
        
        // Forcer encore une fois après le chargement complet
        window.onload = function() {
            document.documentElement.classList.add('light');
            document.documentElement.classList.remove('dark');
        }
        
        // En cas de changement de visibilité de la page
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                document.documentElement.classList.add('light');
                document.documentElement.classList.remove('dark');
            }
        });
    </script>
</body>
</html>