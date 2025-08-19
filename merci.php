<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=sql106.iceiy.com;dbname=icei_39016282_digitek_empire;charset=utf8mb4", "icei_39016282", "yVsf9qzAw1ag");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    echo "Erreur de connexion à la base de données.";
    exit;
}

// Récupération du token depuis l'URL
$token = $_GET['token'] ?? null;

if (!$token) {
    echo "<h2 style='text-align:center;margin-top:50px;color:red;'>Token manquant.</h2>";
    exit;
}

// Récupérer la vente existante
$stmt = $pdo->prepare("
    SELECT v.*, p.nom, p.description, p.image_url, p.fichier_path, p.prix_promo, p.prix_original
    FROM ventes v
    JOIN produits p ON v.produit_id = p.id
    WHERE v.tokenPay COLLATE utf8mb4_general_ci = ?
");
$stmt->execute([$token]);
$vente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vente) {
    echo "<h2 style='text-align:center;margin-top:50px;color:red;'>Aucune commande trouvée.</h2>";
    exit;
}

// Met à jour le statut à "paid" si ce n’est pas déjà fait
if ($vente['statut'] !== 'paid') {
    $update = $pdo->prepare("UPDATE ventes SET statut = 'paid' WHERE id = ?");
    $update->execute([$vente['id']]);
    $vente['statut'] = 'paid';
}

// Données de la commande (à utiliser ensuite dans la page)
$numeroCommande = 'EE-' . date('Ymd') . '-' . ($vente['id'] ?? rand(10000, 99999));
$dateAchat = date('d/m/Y à H:i', strtotime($vente['created_at']));
$prixPaye = $vente['prix_promo'] ?: $vente['prix_original'];
$lienTelechargement = "telecharger.php?id={$vente['produit_id']}&token=" . urlencode($token);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merci pour votre achat | DIGITEK EMPIRE</title>
    <!-- Inclure Tailwind CSS et Font Awesome via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

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
                },
            },
            darkMode: 'class',
        }

        // Le mode clair est activé par défaut
        document.documentElement.classList.remove('dark');

        // Fonction de basculement manuel du thème
        function toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
        }

        // Animation au chargement
        window.onload = function() {
            const elements = document.querySelectorAll('.fade-in-up');
            elements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('show');
                }, 200 * index);
            });
            
            // Lancer la confetti après le chargement
            launchProfessionalConfetti();
        }
    </script>
    
    <style>
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
            opacity: 0;
            transform: translateY(20px);
        }
        
        .fade-in-up.show {
            animation: fadeInUp 0.5s ease forwards;
        }
        
        /* Effet 3D subtil sur les cartes */
        .card-3d-effect {
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-3d-effect:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        /* Animation du bouton de téléchargement */
        @keyframes pulse-border {
            0% {
                box-shadow: 0 0 0 0 rgba(93, 92, 222, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(93, 92, 222, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(93, 92, 222, 0);
            }
        }
        
        .pulse-border {
            animation: pulse-border 2s infinite;
        }
        
        /* Effet de brillance sur les boutons */
        .shimmer {
            position: relative;
            overflow: hidden;
        }
        
        .shimmer::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to right,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.3) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            transform: rotate(30deg);
            animation: shimmer 2s infinite;
        }
        
        /* Style pour le dark mode */
        .dark body {
            background-color: #181818;
            color: #f3f4f6;
        }
        
        .dark .bg-white {
            background-color: #242526;
        }
        
        .dark .text-gray-800 {
            color: #f3f4f6;
        }
        
        .dark .text-gray-600, .dark .text-gray-500 {
            color: #9ca3af;
        }
        
        .dark .bg-gray-50 {
            background-color: #323232;
        }
        
        .dark .border-gray-100 {
            border-color: #3e3e3e;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans leading-normal tracking-normal text-gray-800 transition-colors duration-300 dark:bg-gray-900">

    <!-- Barre de navigation fixe -->
    <nav class="fixed top-0 w-full bg-white shadow-md z-50 dark:bg-gray-800">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="index.php" class="flex items-center space-x-2 group">
                <div class="relative">
                    <i class="fas fa-digital-tachograph text-primary text-2xl transition-transform duration-300 group-hover:scale-110"></i>
                    <span class="absolute -top-1 -right-1 w-2 h-2 bg-primary rounded-full animate-ping"></span>
                </div>
                <span class="font-bold text-xl bg-clip-text text-transparent bg-gradient-to-r from-primary to-secondary">DIGITEK EMPIRE</span>
            </a>
            <div class="flex items-center space-x-4">
                <button onclick="toggleDarkMode()" class="p-2 rounded-full hover:bg-gray-200 transition-colors duration-300 dark:hover:bg-gray-700">
                    <i class="fas fa-moon text-gray-600 dark:hidden"></i>
                    <i class="fas fa-sun hidden dark:block text-yellow-300"></i>
                </button>
                <a href="index.php" class="bg-primary hover:bg-secondary text-white px-4 py-2 rounded-lg transition-colors duration-300 flex items-center shadow-md">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <span class="hidden sm:inline">Retour à la boutique</span>
                    <span class="sm:hidden">Boutique</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Espace pour la barre de navigation fixe -->
    <div class="h-16"></div>

    <!-- En-tête avec message de confirmation -->
    <header class="relative bg-gradient-to-r from-primary to-secondary text-white py-16 mb-6 overflow-hidden fade-in-up">
        <div class="absolute inset-0 bg-black opacity-10"></div>
        
        <!-- Éléments décoratifs digitaux -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-1/4 left-1/4 w-16 h-16 border-2 border-white/20 rounded-full animate-spin-slow opacity-50"></div>
            <div class="absolute bottom-1/3 right-1/5 w-20 h-20 border-2 border-white/30 rounded-lg rotate-12 opacity-40"></div>
            <div class="absolute top-1/3 right-1/4 w-12 h-12 bg-white/10 rounded-full"></div>
            <!-- Élément techno -->
            <div class="absolute bottom-1/4 left-1/3 opacity-30">
                <i class="fas fa-microchip text-4xl text-white/60"></i>
            </div>
        </div>
        
        <div class="container mx-auto text-center relative z-10 px-4">
            <div class="inline-block mb-6">
                <div class="w-20 h-20 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center animate-float">
                    <i class="fas fa-check-circle text-green-300 text-4xl"></i>
                </div>
            </div>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Merci pour votre achat !</h1>
            <p class="text-xl text-gray-100 max-w-2xl mx-auto">Votre commande a été confirmée et est prête à être téléchargée.</p>
            
            <!-- Badge commande confirmée -->
            <div class="mt-6 inline-block">
                <div class="px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full text-sm font-medium animate-pulse-slow flex items-center">
                    <i class="fas fa-bolt text-yellow-300 mr-2"></i>
                    <span>Commande #<?= htmlspecialchars($numeroCommande) ?></span>
                </div>
            </div>
        </div>
        <div class="absolute -bottom-10 left-0 w-full h-20 bg-white dark:bg-gray-800 rounded-t-[50%] z-0"></div>
    </header>

    <!-- Détails de la commande -->
    <section class="py-8 fade-in-up">
        <div class="container mx-auto px-4 md:px-6">
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl overflow-hidden md:flex card-3d-effect">
                <!-- Image du produit -->
                <div class="md:w-1/3 overflow-hidden">
                    <div class="relative group h-80 md:h-full">
                        <div class="absolute inset-0 bg-gradient-to-tr from-primary/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-10 pointer-events-none"></div>
                        <?php if (!empty($vente['image_url'])): ?>
                            <img class="w-full h-full object-cover transition-transform duration-700 ease-in-out group-hover:scale-105" src="<?= htmlspecialchars($vente['image_url']) ?>" alt="<?= htmlspecialchars($vente['nom']) ?>">
                        <?php else: ?>
                            <div class="w-full h-full bg-gray-100 dark:bg-gray-700 flex flex-col items-center justify-center">
                                <i class="fas fa-shopping-bag text-gray-300 dark:text-gray-500 text-5xl mb-3"></i>
                                <p class="text-gray-400 text-sm">Image non disponible</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Détails et téléchargement -->
                <div class="md:w-2/3 p-6 md:p-8 flex flex-col justify-between">
                    <!-- Section d'information -->
                    <div>
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Détails de votre commande</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 flex items-center">
                                    <i class="far fa-calendar-alt mr-2 text-primary"></i> 
                                    Achetée le <?= htmlspecialchars($dateAchat) ?>
                                </p>
                            </div>
                            <div class="py-2 px-4 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-lg inline-flex items-center">
                                <i class="fas fa-check-circle mr-2"></i>
                                <span>Paiement confirmé</span>
                            </div>
                        </div>
                        
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <h3 class="text-xl font-semibold text-primary flex items-center">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <span class="relative">
                                        <?= htmlspecialchars($vente['nom']) ?>
                                        <span class="absolute -bottom-1 left-0 right-0 h-0.5 bg-primary/30 rounded-full"></span>
                                    </span>
                                </h3>
                                <div class="text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border-l-2 border-primary">
                                    <?= nl2br(htmlspecialchars($vente['description'] ?? 'Aucune description disponible')) ?>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Prix payé</span>
                                    <div class="text-2xl font-bold text-primary"><?= number_format($prixPaye, 0, ',', ' ') ?> F CFA</div>
                                </div>
                                <div class="flex items-center space-x-2 text-green-600 dark:text-green-400">
                                    <i class="fas fa-shield-alt"></i>
                                    <span class="text-sm">Achat sécurisé</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Téléchargement -->
                    <div class="mt-8">
                        <a href="<?= $lienTelechargement ?>" 
                           class="group relative w-full bg-primary hover:bg-secondary text-white font-bold py-4 px-8 rounded-lg transition-all duration-300 transform hover:scale-105 hover:shadow-lg text-center flex items-center justify-center pulse-border shimmer">
                          <span class="absolute inset-0 w-full h-full transition-all duration-300 scale-0 group-hover:scale-100 group-hover:bg-white/10 rounded-lg"></span>
                          <i class="fas fa-download mr-3 text-xl"></i>
                          <span class="text-lg">Télécharger votre produit</span>
                        </a>
                        
                        <!-- Instructions -->
                        <div class="mt-6 bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg border-l-4 border-blue-500">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                                <div>
                                    <h4 class="font-medium text-blue-800 dark:text-blue-300">Instructions d'accès</h4>
                                    <p class="text-sm text-blue-700 dark:text-blue-400 mt-1">Cliquez sur le bouton ci-dessus pour accéder à votre produit.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section d'assistance -->
    <section class="py-10 bg-white dark:bg-gray-800 fade-in-up">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto bg-gradient-to-r from-primary/10 to-secondary/10 rounded-2xl p-6 md:p-8">
                <div class="flex flex-col md:flex-row items-center">
                    <div class="mb-6 md:mb-0 md:mr-8">
                        <div class="w-24 h-24 bg-white dark:bg-gray-700 rounded-full flex items-center justify-center shadow-lg">
                            <i class="fas fa-headset text-primary text-4xl"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800 dark:text-white mb-2">Besoin d'aide ?</h3>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">Si vous avez des questions concernant votre achat ou si vous rencontrez des difficultés, notre équipe d'assistance est à votre disposition.</p>
                        <div class="flex flex-wrap gap-3">
                            <a href="mailto:contactdigitekempire@gmail.com" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 text-primary rounded-lg shadow hover:shadow-md transition-all duration-300">
                                <i class="far fa-envelope mr-2"></i>
                                contactdigitekempire@gmail.com
                            </a>
                            <a href="https://wa.me/22956308400" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 text-green-600 dark:text-green-400 rounded-lg shadow hover:shadow-md transition-all duration-300">
                                <i class="fab fa-whatsapp mr-2"></i>
                                +229 56 30 84 00
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pied de page -->
    <footer class="bg-gradient-to-r from-gray-800 to-gray-900 text-white py-12 fade-in-up">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="text-center md:text-left mb-6 md:mb-0">
                    <div class="flex items-center justify-center md:justify-start">
                        <i class="fas fa-digital-tachograph text-primary text-3xl mr-2"></i>
                        <h2 class="text-2xl font-bold">DIGITEK EMPIRE</h2>
                    </div>
                    <p class="text-gray-400 mt-2">Solutions digitales premium pour votre réussite</p>
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
    // Fonction de salve professionnelle
    function launchProfessionalConfetti() {
        const duration = 3 * 1000; // durée totale 3 secondes
        const animationEnd = Date.now() + duration;
        const defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 1000 };

        function randomInRange(min, max) {
            return Math.random() * (max - min) + min;
        }

        const interval = setInterval(function () {
            const timeLeft = animationEnd - Date.now();

            if (timeLeft <= 0) {
                return clearInterval(interval);
            }

            const particleCount = 50 * (timeLeft / duration);
            // deux explosions opposées (gauche/droite)
            confetti(Object.assign({}, defaults, {
                particleCount,
                origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 }
            }));
            confetti(Object.assign({}, defaults, {
                particleCount,
                origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 }
            }));
        }, 250);
    }
</script>
</body>
</html>