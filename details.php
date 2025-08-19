<?php
require_once 'includes/config.php'; // Connexion à la base de données

// Récupérer l'id du produit à partir de l'URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Requête pour récupérer les détails du produit
    $query = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
    $query->execute([$id]);
    $produit = $query->fetch();
} else {        
    echo "Produit non trouvé.";
    exit;
}
// Récupération de l'ID produit depuis l'URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Récupération du produit
    $query = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
    $query->execute([$id]);
    $produit = $query->fetch();
} else {
    echo "Produit non trouvé.";
    exit;
}

// Soumission d’un avis
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['produit_id'])) {
    $nom = htmlspecialchars($_POST['nom_client'], ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($_POST['email_client'], ENT_QUOTES, 'UTF-8');
    $note = intval($_POST['note']);
    $commentaire = htmlspecialchars($_POST['commentaire'], ENT_QUOTES, 'UTF-8');
    $date = date('Y-m-d H:i:s');

    $sql = "INSERT INTO avis (produit_id, nom_client, email_client, note, commentaire, date_ajout)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id, $nom, $email, $note, $commentaire, $date]);

    header("Location: ".$_SERVER['REQUEST_URI']."#avis-section");
    exit;
}

// Récupération des avis existants
$stmt = $pdo->prepare("SELECT * FROM avis WHERE produit_id = ? ORDER BY date_ajout DESC");
$stmt->execute([$id]);
$avis = $stmt->fetchAll();

// Calcul de la note moyenne
$note_moyenne = 0;
$nombre_avis = count($avis);
if ($nombre_avis > 0) {
    $total_notes = array_sum(array_column($avis, 'note'));
    $note_moyenne = round($total_notes / $nombre_avis, 1);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du produit | DIGITEK EMPIRE</title>
    <!-- Inclure Tailwind CSS et Font Awesome via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    },
                    boxShadow: {
                        'soft': '0 5px 15px rgba(0, 0, 0, 0.05)',
                        'medium': '0 8px 30px rgba(0, 0, 0, 0.12)',
                    }
                },
            },
        }

        // Mode clair par défaut
        document.documentElement.classList.remove('dark');

        // Compte à rebours
        function startCountdown(endDate) {
            const countdownDate = new Date(endDate).getTime();
            
            const countdownTimer = setInterval(function() {
                const now = new Date().getTime();
                const distance = countdownDate - now;
                
                if (distance < 0) {
                    clearInterval(countdownTimer);
                    document.getElementById("countdown-container").innerHTML = 
                        `<div class="text-center p-4 bg-red-100 rounded-lg">
                            <p class="text-xl font-bold text-red-600">Promo terminée</p>
                        </div>`;
                    return;
                }
                
                // Calcul du temps restant
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                // Mise à jour des éléments du compte à rebours
                document.getElementById("countdown-days").textContent = days.toString().padStart(2, '0');
                document.getElementById("countdown-hours").textContent = hours.toString().padStart(2, '0');
                document.getElementById("countdown-minutes").textContent = minutes.toString().padStart(2, '0');
                document.getElementById("countdown-seconds").textContent = seconds.toString().padStart(2, '0');
            }, 1000);
        }

        // Fonction pour analyser et afficher les caractéristiques avec des icônes
        function parseFeatures() {
            const featuresElement = document.getElementById('caracteristiques');
            if (!featuresElement) return;
            
            const featuresText = featuresElement.textContent;
            if (!featuresText || featuresText.trim() === 'Aucune caractéristique disponible') return;
            
            // Liste d'icônes pour différents types de caractéristiques
            const featureIcons = {
                'mémoire': 'fa-memory',
                'stockage': 'fa-hdd',
                'ram': 'fa-memory',
                'processeur': 'fa-microchip',
                'écran': 'fa-desktop',
                'batterie': 'fa-battery-full',
                'caméra': 'fa-camera',
                'résolution': 'fa-expand',
                'connectivité': 'fa-wifi',
                'poids': 'fa-weight',
                'dimensions': 'fa-ruler-combined',
                'garantie': 'fa-shield-alt',
                'livraison': 'fa-truck',
                'paiement': 'fa-credit-card',
                'format': 'fa-file',
                'durée': 'fa-clock',
                'accès': 'fa-key',
                'support': 'fa-headset',
                'mise à jour': 'fa-sync',
                'compatibilité': 'fa-laptop',
                'système': 'fa-cog',
                'logiciel': 'fa-code',
                'téléchargement': 'fa-download',
                'taille': 'fa-file-archive',
                'installation': 'fa-wrench',
                'licence': 'fa-id-card',
                'version': 'fa-tag',
                'audio': 'fa-volume-up',
                'vidéo': 'fa-video',
                'streaming': 'fa-stream',
                'cloud': 'fa-cloud',
                'mobile': 'fa-mobile-alt',
                'données': 'fa-database',
                'vitesse': 'fa-tachometer-alt',
                'sécurité': 'fa-lock',
                // Icône par défaut pour les autres types
                'default': 'fa-check-circle'
            };
            
            // Diviser les caractéristiques en lignes (séparées par des sauts de ligne)
            const features = featuresText.split('\n').filter(f => f.trim() !== '');
            
            // Créer le HTML pour chaque caractéristique avec une icône appropriée
            let featuresHTML = '<div class="grid grid-cols-1 md:grid-cols-2 gap-3">';
            
            features.forEach(feature => {
                let iconClass = featureIcons.default;
                
                // Recherche d'un mot-clé dans la caractéristique pour déterminer l'icône
                for (const [keyword, icon] of Object.entries(featureIcons)) {
                    if (feature.toLowerCase().includes(keyword)) {
                        iconClass = icon;
                        break;
                    }
                }
                
                featuresHTML += `
                <div class="feature-item flex items-start space-x-3 p-3 rounded-lg transition-all duration-300 hover:bg-gray-50 border border-gray-100">
                    <div class="feature-icon flex-shrink-0 w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                        <i class="fas ${iconClass}"></i>
                    </div>
                    <div class="feature-text">
                        <p class="text-gray-700">${feature}</p>
                    </div>
                </div>`;
            });
            
            featuresHTML += '</div>';
            
            // Remplacer le contenu des caractéristiques
            featuresElement.innerHTML = featuresHTML;
        }

        // Initialisation
        window.onload = function() {
            const promoEndDate = "<?php echo $produit['promo_fin'] ?? ''; ?>";
            if (promoEndDate) {
                startCountdown(promoEndDate);
            }
            
            // Effet de zoom sur l'image au survol
            const productImage = document.getElementById('product-image');
            if (productImage) {
                productImage.addEventListener('mouseover', function() {
                    this.classList.add('scale-105');
                });
                productImage.addEventListener('mouseout', function() {
                    this.classList.remove('scale-105');
                });
            }
            
            // Analyser et afficher les caractéristiques avec des icônes
            parseFeatures();
            
            // Animation des éléments au chargement
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                setTimeout(() => {
                    element.classList.add('is-visible');
                }, 100 * index);
            });
        }
        
        // Fonction pour copier le lien du produit
        function copyProductLink() {
            navigator.clipboard.writeText(window.location.href)
                .then(function() {
                    // Afficher une notification de succès
                    const notification = document.getElementById('copyNotification');
                    notification.classList.remove('opacity-0', '-translate-y-4');
                    notification.classList.add('opacity-100', 'translate-y-0');
                    
                    // Cacher la notification après 2 secondes
                    setTimeout(() => {
                        notification.classList.add('opacity-0', '-translate-y-4');
                        notification.classList.remove('opacity-100', 'translate-y-0');
                    }, 2000);
                })
                .catch(function(err) {
                    console.error("Erreur de copie du lien : ", err);
                });
        }
    </script>
    <style>
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
        
        /* Effet hover sur les caractéristiques */
        .feature-item {
            transition: all 0.3s ease;
        }
        .feature-item:hover {
            transform: translateX(5px);
        }
        
        /* Transition des images */
        #product-image {
            transition: transform 0.5s ease;
        }
        @keyframes blink {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.3;
  }
}

.animate-blink {
  animation: blink 1s infinite;
}
@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-8px);
  }
}

.animate-float {
  animation: float 3s ease-in-out infinite;
}


    </style>
</head>
<body class="bg-white font-sans leading-normal tracking-normal text-gray-800">

    <!-- Notification de copie du lien -->
    <div id="copyNotification" class="fixed top-20 left-1/2 transform -translate-x-1/2 -translate-y-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 opacity-0 transition-all duration-300">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span>Lien copié !</span>
        </div>
    </div>

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

    <!-- En-tête du produit -->
<header class="relative bg-gradient-to-r from-primary to-accent text-white py-12 mb-10 overflow-hidden fade-in">
    <div class="container mx-auto text-center relative z-10 px-4">
        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold mb-4 animate-float">
            <?= htmlspecialchars($produit['nom'] ?? 'Produit Premium') ?>
        </h1>
        <p class="text-xl text-white/90 max-w-2xl mx-auto">Solutions digitales premium pour votre réussite</p>

        <!-- Badge produit digital -->
        <div class="mt-6 inline-block">
            <div class="px-4 py-2 bg-white/10 rounded-full text-sm font-medium flex items-center">
                <i class="fas fa-bolt text-yellow-300 mr-2"></i>
                <span>Produit Digital Premium</span>
            </div>
        </div>
    </div>
</header>


    <!-- Conteneur principal -->
    <div class="container mx-auto px-4 mb-16">
        <!-- Détails du produit -->
        <section class="mb-12 fade-in">
            <div class="bg-white shadow-md rounded-xl overflow-hidden md:flex">
                <!-- Image du produit -->
                <div class="md:w-2/5 bg-gray-50">
                    <?php if (!empty($produit['image_url'])): ?>
                        <div class="relative h-72 md:h-full overflow-hidden">
                            <img id="product-image" class="w-full h-full object-cover" src="<?= htmlspecialchars($produit['image_url']) ?>" alt="<?= htmlspecialchars($produit['nom'] ?? 'Produit') ?>">
                        </div>
                    <?php else: ?>
                        <div class="relative h-72 md:h-full overflow-hidden">
                            <img id="product-image" class="w-full h-full object-cover" src="images/default.jpg" alt="Image par défaut">
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Description et prix -->
                <div class="md:w-3/5 p-6 md:p-8 flex flex-col justify-between">
                    <!-- Section d'information -->
                    <div>
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">Détails du produit</h2>
                                <p class="text-sm text-gray-500">Spécifications complètes</p>
                            </div>
                            <?php if (!empty($produit['reference'])): ?>
                                <span class="bg-gray-100 text-gray-700 text-sm py-1 px-3 rounded-lg flex items-center">
                                    <i class="fas fa-barcode text-xs text-primary mr-1"></i>
                                    Réf: <?= htmlspecialchars($produit['reference'] ?? 'N/A') ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <h3 class="text-xl font-semibold text-primary flex items-center">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Description
                                </h3>
                                <div class="text-gray-600 bg-gray-50 p-4 rounded-lg border-l-2 border-primary">
                                    <?= nl2br(htmlspecialchars($produit['description'] ?? 'Aucune description disponible')) ?>
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <h3 class="text-xl font-semibold text-primary flex items-center">
                                    <i class="fas fa-list-ul mr-2"></i>
                                    Ce que vous obtenez
                                </h3>
                                <div id="caracteristiques" class="text-gray-600 bg-gray-50 p-4 rounded-lg border-l-2 border-primary">
                                    <?= nl2br(htmlspecialchars($produit['caracteristiques'] ?? 'Aucune caractéristique disponible')) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section prix et achat -->
                    <div class="mt-8 space-y-4">
                        <?php if (!empty($produit['prix_promo']) && $produit['prix_promo'] < ($produit['prix_original'] ?? 0)): ?>
                            <!-- Prix avec promotion -->
                            <div class="flex items-center space-x-4">
                                <div>
                                    <p class="text-lg text-gray-500 line-through"><?= number_format(($produit['prix_original'] ?? 0), 0, ',', ' ') ?> F CFA</p>
                                    <p class="text-3xl font-bold text-primary"><?= number_format(($produit['prix_promo'] ?? 0), 0, ',', ' ') ?> F CFA</p>
                                </div>
                                <div class="bg-accent text-white text-sm font-bold py-2 px-4 rounded-lg shadow-sm">
                                    <?php 
                                    $reduction = round((1 - ($produit['prix_promo'] / $produit['prix_original'])) * 100);
                                    echo "-{$reduction}%";
                                    ?>
                                </div>
                            </div>
                            
                           <!-- Compte à rebours parfaitement aligné -->
<div id="countdown-container" class="my-4 bg-red-50 p-4 rounded-lg border border-red-200">
  <p class="text-sm text-red-600 mb-2 flex items-center whitespace-nowrap overflow-hidden text-ellipsis">
    <i class="fas fa-stopwatch text-red-600 mr-2 shrink-0"></i>
    Offre spéciale, se termine dans :
</p>

    <div class="grid grid-cols-4 gap-2 sm:gap-4 max-w-full overflow-hidden">
        <div class="flex flex-col items-center">
            <div class="bg-red-100 text-red-600 w-full aspect-square flex items-center justify-center rounded-lg shadow-sm">
                <span id="countdown-days" class="text-lg sm:text-2xl font-bold">00</span>
            </div>
            <span class="text-xs mt-1 text-red-600">Jours</span>
        </div>
        <div class="flex flex-col items-center">
            <div class="bg-red-100 text-red-600 w-full aspect-square flex items-center justify-center rounded-lg shadow-sm">
                <span id="countdown-hours" class="text-lg sm:text-2xl font-bold">00</span>
            </div>
            <span class="text-xs mt-1 text-red-600">Heures</span>
        </div>
        <div class="flex flex-col items-center">
            <div class="bg-red-100 text-red-600 w-full aspect-square flex items-center justify-center rounded-lg shadow-sm">
                <span id="countdown-minutes" class="text-lg sm:text-2xl font-bold">00</span>
            </div>
            <span class="text-xs mt-1 text-red-600">Minutes</span>
        </div>
        <div class="flex flex-col items-center">
            <div id="seconds-block" class="bg-red-100 text-red-600 w-full aspect-square flex items-center justify-center rounded-lg shadow-sm transition-transform duration-300">
                <span id="countdown-seconds" class="text-lg sm:text-2xl font-bold">00</span>
            </div>
            <span class="text-xs mt-1 text-red-600">Secondes</span>
        </div>
    </div>
</div>

                        <?php else: ?>
                            <!-- Prix normal sans promotion -->
                            <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                                <p class="text-sm text-gray-500 mb-1">Prix standard</p>
                                <p class="text-3xl font-bold text-primary"><?= number_format(($produit['prix_original'] ?? 0), 0, ',', ' ') ?> F CFA</p>
                            </div>
                        <?php endif; ?>

                        <!-- Bouton d'achat -->
<div class="flex items-center space-x-4 mt-6">
    <?php
        // Détermine si le produit est gratuit
        $prix = $produit['prix_promo'] !== null ? $produit['prix_promo'] : $produit['prix_original'];
        $estGratuit = floatval($prix) <= 0;
        $urlAchat = $estGratuit ? "merci.php?id=" . $produit['id'] : "payer.php?id=" . $produit['id'];
    ?>
    <a href="<?= $urlAchat ?>" class="group relative w-full md:w-auto bg-primary hover:bg-accent text-white font-bold py-3 px-8 rounded-lg transition-all duration-300 transform hover:-translate-y-1 hover:shadow-md text-center flex items-center justify-center animate-blink">
        <i class="fas fa-shopping-cart mr-2"></i>
        <span><?= $estGratuit ? "Obtenir gratuitement" : "Acheter maintenant" ?></span>
    </a>
</div>



                               
                            </a>
                            <button class="bg-gray-200 p-3 rounded-full hover:bg-gray-300 transition-colors duration-300 group">
                                <i class="fas fa-heart text-gray-600 group-hover:text-red-500 transition-colors duration-300"></i>
                            </button>
                            <button id="copyButton" onclick="copyProductLink()" class="bg-gray-200 p-3 rounded-full hover:bg-gray-300 transition-colors duration-300 group">
                                <i class="fas fa-share-alt text-gray-600 group-hover:text-primary transition-colors duration-300"></i>
                            </button>
                        </div>
                        
                        <!-- Label de confiance -->
                        <div class="mt-4 flex items-center space-x-2 text-sm text-gray-500">
                            <i class="fas fa-shield-alt text-green-600"></i>
                            <span>Paiement sécurisé et accès instantané</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
       <section id="avis-section" class="mt-12">
    <h2 class="text-2xl font-bold text-purple-700 mb-6">Avis des clients</h2>

    <?php if ($nombre_avis > 0): ?>
        <div class="mb-8">
            <p class="text-lg font-semibold text-yellow-500">
                Note moyenne : <?= $note_moyenne ?>/5 ⭐ (<?= $nombre_avis ?> avis)
            </p>
        </div>
    <?php endif; ?>

    <div class="space-y-6">
        <?php foreach ($avis as $a): ?>
            <div class="bg-white shadow-md p-6 rounded-xl border border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="font-semibold text-base text-gray-800"><?= htmlspecialchars($a['nom_client']) ?></h4>
                    <div class="text-yellow-400 text-sm">
                        <?= str_repeat("★", $a['note']) . str_repeat("☆", 5 - $a['note']) ?>
                    </div>
                </div>
                <div class="mb-2">
                    <span class="inline-flex items-center bg-purple-100 text-purple-800 text-xs font-semibold px-3 py-1 rounded-full">
                        <i class="fas fa-check-circle mr-1"></i> Achat vérifié
                    </span>
                </div>
                <p class="text-gray-700 text-sm leading-relaxed">
    <?= nl2br($a['commentaire']) ?>
</p>

                <div class="text-xs text-gray-500 mt-2 italic">
                    <?= date('d/m/Y à H:i', strtotime($a['date_ajout'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="mt-14 bg-white p-6 rounded-xl shadow-md border border-gray-200">
    <h3 class="text-xl font-semibold text-purple-700 mb-4">Laisser un avis</h3>
    <form action="#avis-section" method="post" class="space-y-5">
        <input type="hidden" name="produit_id" value="<?= $id ?>">

        <div>
            <label class="block text-sm font-medium text-gray-700">Nom</label>
            <input type="text" name="nom_client" required
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" name="email_client" required
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Note (1 à 5)</label>
            <select name="note" required
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                <option value="5">5 - Excellent</option>
                <option value="4">4 - Très bon</option>
                <option value="3">3 - Moyen</option>
                <option value="2">2 - Passable</option>
                <option value="1">1 - Mauvais</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Commentaire</label>
            <textarea name="commentaire" rows="4" required
                class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
        </div>

        <button type="submit"
            class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2 rounded-md font-medium transition">
            Envoyer mon avis
        </button>
    </form>
</section>
<br>


        <!-- Avantages produit -->
        <section class="mb-12 fade-in">
            <h2 class="text-2xl font-bold text-center mb-8 relative">
                <span class="relative z-10">Avantages de nos produits digitaux</span>
                <span class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-24 h-1 bg-primary rounded-full"></span>
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center text-center space-y-4 transition-all duration-300 transform hover:-translate-y-2 hover:shadow-md">
                    <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                        <i class="fas fa-bolt text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">Accès Instantané</h3>
                        <p class="text-gray-600 mt-2">Téléchargement immédiat après votre achat, disponible 24/7</p>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center text-center space-y-4 transition-all duration-300 transform hover:-translate-y-2 hover:shadow-md">
                    <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                        <i class="fas fa-sync text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">Mises à jour gratuites</h3>
                        <p class="text-gray-600 mt-2">Bénéficiez des dernières fonctionnalités sans frais supplémentaires</p>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center text-center space-y-4 transition-all duration-300 transform hover:-translate-y-2 hover:shadow-md">
                    <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                        <i class="fas fa-headset text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 text-lg">Support Premium</h3>
                        <p class="text-gray-600 mt-2">Assistance technique dédiée et accès privilégié à notre équipe</p>
                    </div>
                </div>
            </div>
            
            <!-- Bannière de garantie -->
            <div class="mt-10 p-6 bg-gray-50 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden fade-in">
                <div class="relative z-10">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                        <div class="md:w-2/3">
                            <h3 class="text-2xl font-bold mb-4 text-gray-800">La Garantie DIGITEK EMPIRE</h3>
                            <p class="text-gray-600">
                                Nos produits digitaux sont développés selon les plus hauts standards de l'industrie.
                                Vous bénéficiez d'une garantie de satisfaction de 30 jours et d'un support privilégié pour une expérience sans compromis.
                            </p>
                            <div class="mt-6 flex flex-wrap gap-4">
                                <div class="flex items-center space-x-2 bg-white px-3 py-1 rounded-full border border-gray-100 shadow-sm">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <span class="text-gray-700">Satisfaction garantie</span>
                                </div>
                                <div class="flex items-center space-x-2 bg-white px-3 py-1 rounded-full border border-gray-100 shadow-sm">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <span class="text-gray-700">Support réactif</span>
                                </div>
                                <div class="flex items-center space-x-2 bg-white px-3 py-1 rounded-full border border-gray-100 shadow-sm">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <span class="text-gray-700">Mises à jour gratuites</span>
                                </div>
                            </div>
                        </div>
                        <div class="md:w-1/3 flex justify-center">
                            <div class="w-32 h-32 bg-primary/10 rounded-full flex items-center justify-center">
                                <i class="fas fa-shield-alt text-primary text-4xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ pour produits digitaux -->
        <section class="mb-12 fade-in">
            <h2 class="text-2xl font-bold text-center mb-8 relative">
                <span class="relative z-10">Questions fréquentes</span>
                <span class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-24 h-1 bg-primary rounded-full"></span>
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
                    <h3 class="font-bold text-gray-800 flex items-center text-lg">
                        <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center text-primary mr-3">
                            <i class="fas fa-question"></i>
                        </div>
                        Comment accéder à mon produit digital ?
                    </h3>
                    <p class="mt-3 text-gray-600 pl-11">
                        Après votre achat, vous recevrez immédiatement un email avec vos identifiants et instructions d'accès. 
                        Vous pourrez également retrouver votre produit dans votre espace client.
                    </p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
                    <h3 class="font-bold text-gray-800 flex items-center text-lg">
                        <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center text-primary mr-3">
                            <i class="fas fa-sync"></i>
                        </div>
                        Les mises à jour sont-elles incluses ?
                    </h3>
                    <p class="mt-3 text-gray-600 pl-11">
                        Oui ! Toutes les mises à jour sont gratuites et automatiques.
                        Vous bénéficiez toujours de la dernière version sans action de votre part.
                    </p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
                    <h3 class="font-bold text-gray-800 flex items-center text-lg">
                        <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center text-primary mr-3">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        Puis-je utiliser le produit sur plusieurs appareils ?
                    </h3>
                    <p class="mt-3 text-gray-600 pl-11">
                        Absolument ! Votre licence vous permet d'utiliser le produit sur tous vos appareils personnels
                        sans limitation de nombre.
                    </p>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300">
                    <h3 class="font-bold text-gray-800 flex items-center text-lg">
                        <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center text-primary mr-3">
                            <i class="fas fa-life-ring"></i>
                        </div>
                        Comment obtenir de l'aide si nécessaire ?
                    </h3>
                    <p class="mt-3 text-gray-600 pl-11">
                        Notre équipe de support est disponible 24/7 via email, chat et WhatsApp.
                        Un temps de réponse moyen de moins de 2 heures vous est garanti.
                    </p>
                </div>
            </div>
            
            <!-- CTA -->
            <div class="mt-12 text-center">
                <a href="payer.php?id=<?= $produit['id'] ?>" class="inline-block bg-primary hover:bg-accent text-white font-bold py-4 px-8 rounded-lg shadow-md text-center transition-all duration-300 transform hover:-translate-y-1">

                    <span class="flex items-center">
                        <i class="fas fa-shopping-cart mr-2"></i>
                        <span>ACHETER MAINTENANT</span>
                    </span>
                </a>
                <p class="mt-4 text-gray-600">Rejoignez des milliers d'utilisateurs satisfaits</p>
            </div>
        </section>
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
        // Correction du script de copie du lien
        document.addEventListener('DOMContentLoaded', function() {
            const copyButton = document.getElementById('copyButton');
            if (copyButton) {
                copyButton.addEventListener('click', copyProductLink);
            }
        });
    </script>
</body>
</html>