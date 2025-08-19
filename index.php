<?php
// Forcer l'encodage de la réponse en UTF-8
header('Content-Type: text/html; charset=utf-8');

// Inclure la configuration avec le bon charset utf8mb4
require_once 'includes/config.php';

// Vérification de connexion
if (!$pdo) {
    die('Erreur de connexion à la base de données');
}

// Requête des catégories
$query = $pdo->prepare("SELECT * FROM categories ORDER BY nom ASC");
$query->execute();
$categories = $query->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DIGITEK EMPIRE</title>
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
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            primary: '#5D5CDE',
            secondary: '#F3C34D',
          },
          animation: {
            'fade-in': 'fadeIn 0.8s ease-in-out forwards',
            'slide-up': 'slideUp 0.6s ease-out forwards',
          },
          keyframes: {
            fadeIn: {
              '0%': { opacity: '0' },
              '100%': { opacity: '1' },
            },
            slideUp: {
              '0%': { transform: 'translateY(20px)', opacity: '0' },
              '100%': { transform: 'translateY(0)', opacity: '1' },
            }
          }
        }
      }
    }
  </script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    
    * {
      font-family: 'Poppins', sans-serif;
    }
    
    .line-clamp-3 {
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    
    .hero-gradient {
      background: linear-gradient(135deg, #5D5CDE 0%, #814DBA 100%);
    }
    
    .dark .hero-gradient {
      background: linear-gradient(135deg, #4240A1 0%, #63389A 100%);
    }
    
    .product-card {
      transition: all 0.3s ease;
    }
    
    .product-card:hover {
      transform: translateY(-5px);
    }
    
    .animate-delay-100 {
      animation-delay: 0.1s;
    }
    
    .animate-delay-200 {
      animation-delay: 0.2s;
    }
    
    .animate-delay-300 {
      animation-delay: 0.3s;
    }
    
    .floating-whatsapp {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 100;
      animation: bounce 2s infinite;
    }
    
    @keyframes bounce {
      0%, 100% {
        transform: translateY(0);
      }
      50% {
        transform: translateY(-10px);
      }
    }
    
    /* Intersection Observer Animation */
    .reveal {
      opacity: 0;
      transform: translateY(30px);
      transition: opacity 0.6s ease, transform 0.6s ease;
    }
    
    .reveal.active {
      opacity: 1;
      transform: translateY(0);
    }
    
    /* Dark mode specific styles */
    .dark .dark-shadow {
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
    }
    
    /* Loading animation */
    .skeleton {
      background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
      background-size: 200% 100%;
      animation: skeleton-loading 1.5s infinite;
    }
    
    .dark .skeleton {
      background: linear-gradient(90deg, #2a2a2a 25%, #383838 50%, #2a2a2a 75%);
      background-size: 200% 100%;
      animation: skeleton-loading 1.5s infinite;
    }
    
    @keyframes skeleton-loading {
      0% {
        background-position: 200% 0;
      }
      100% {
        background-position: -200% 0;
      }
    }
    /* Style de base pour les cartes */
.category-filter {
    transition: all 0.3s ease;
    background-color: white;
    color: #2d3748;
    border: 1px solid #ddd;
}

/* Style de la carte active (fond rouge et texte blanc) */
.category-filter.active {
    background-color: #f87171; /* Rouge */
    color: white;
}

/* Pour l'icône dans les catégories */
.category-filter i {
    color: #3182ce;
    transition: transform 0.2s ease;
}

/* Lorsque l'élément est actif, l'icône devient blanche */
.category-filter.active i {
    color: white;
}

/* Effet au survol */
.category-filter:hover {
    transform: scale(1.05);
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
}



  </style>
</head>
<body class="bg-gray-50 text-gray-800 dark:bg-gray-900 dark:text-gray-200 min-h-screen flex flex-col">

  <!-- Navigation -->
<nav class="bg-white dark:bg-gray-800 shadow-md dark:shadow-lg sticky top-0 z-50">
  <div class="container mx-auto px-4">
    <div class="flex justify-between items-center py-3">
      <div class="flex items-center space-x-4">
        <!-- Logo ou nom de la marque -->
        <div class="text-primary dark:text-primary font-semibold text-2xl uppercase tracking-wider hover:text-secondary transition-all duration-300">
          DIGITEK EMPIRE
        </div>
       
      </div>

      <!-- Mobile Hamburger Icon -->
      <div class="sm:hidden flex items-center">
        <button id="mobile-menu-button" class="text-gray-700 dark:text-gray-300">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>
      </div>

      <!-- Dark Mode Toggle -->
      <div class="flex items-center space-x-4">
        <button id="darkModeToggle" class="p-3 rounded-full bg-gray-800 text-white dark:bg-gray-300 dark:text-gray-900 hover:bg-gray-700 dark:hover:bg-gray-400 transition-all duration-300 ease-in-out shadow-md hover:shadow-xl focus:outline-none">
          <!-- Icône du mode sombre -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 dark:hidden transition-all duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
          </svg>

          <!-- Icône du mode clair -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden dark:block transition-all duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </button>

        <!-- Links for larger screens -->
        <div class="hidden md:flex items-center space-x-4">
          <a href="#contact" class="text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-primary dark:hover:text-primary transition">Contact</a>
          <a href="#products" class="bg-primary hover:bg-opacity-80 text-white px-4 py-2 rounded-lg text-sm font-medium transition">Nos produits</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div id="mobile-menu" class="sm:hidden hidden bg-white dark:bg-gray-800 p-4">
    <a href="#contact"  class="block bg-primary text-white py-2 px-4 rounded-lg text-center max-w-xs sm:max-w-full w-full sm:w-auto mx-auto">Contact</a><br>
   <a href="#products" class="block bg-primary text-white py-2 px-4 rounded-lg text-center max-w-xs sm:max-w-full w-full sm:w-auto mx-auto">
  Nos produits
</a>

  </div>
</nav>

<!-- JavaScript to toggle the mobile menu -->
<script>
  const menuButton = document.getElementById('mobile-menu-button');
  const mobileMenu = document.getElementById('mobile-menu');
  
  menuButton.addEventListener('click', () => {
    mobileMenu.classList.toggle('hidden');
  });
  
</script>

 <!-- Hero -->
<section class="text-white overflow-hidden relative">
  <!-- Gradient sombre pour lisibilité du texte -->
  <div class="absolute inset-0 bg-gradient-to-r from-black/20 to-black/40 z-0"></div>

   <!-- Section d'accueil professionnelle avec flou réduit -->
<div class="relative w-full h-[500px] bg-cover bg-center bg-no-repeat" style="background-image: url('./1.jpeg');">

  <!-- Overlay sombre subtil -->
  <div class="absolute inset-0 bg-black/40"></div>

  <!-- Zone de contenu avec fond flou réduit -->
  <div class="absolute inset-0 flex items-center justify-start px-4 md:px-16">
    <div class="bg-white/30 backdrop-blur-sm rounded-2xl shadow-xl p-6 md:p-10 max-w-xl text-left">
      <h1 class="text-3xl md:text-5xl font-extrabold text-gray-900 mb-4 leading-tight drop-shadow-md">
        Bienvenue chez <span class="text-secondary">DIGITEK EMPIRE</span>
      </h1>
      <p class="text-base md:text-lg text-gray-900 mb-6 drop-shadow-sm">
        Votre allié de confiance pour des produits digitaux de qualité exceptionnelle.
      </p>
      <div class="flex flex-wrap gap-4">
        <a href="#products" class="bg-secondary hover:bg-opacity-90 text-gray-900 font-semibold py-3 px-6 rounded-xl shadow hover:shadow-2xl transform hover:-translate-y-1 transition">
          <i class="fas fa-shopping-cart mr-2"></i>Explorer les produits
        </a>
        <a href="#" target="_blank" class="bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-6 rounded-xl shadow hover:shadow-2xl transform hover:-translate-y-1 transition">
          <i class="fab fa-whatsapp mr-2"></i>Nous contacter
        </a>
      </div>
    </div>
  </div>
</div>


      </div>
    </div>
  </div>
</section>



      </div>
    </div>
    
    <!-- Animated Shapes -->
    <div class="hidden md:block absolute -bottom-10 right-0 w-1/3 h-48 bg-white/10 rounded-tl-full transform rotate-6 blur-xl"></div>
    <div class="hidden md:block absolute top-10 right-10 w-20 h-20 bg-primary/30 rounded-full transform blur-xl"></div>
  </section>

  <!-- Produits -->
<section id="products" class="py-16 bg-white dark:bg-gray-800 reveal">
    <div class="container mx-auto px-4">
        
        <!-- Titre des catégories -->
        <div class="px-6 mb-8 text-center">
            <h2 class="text-3xl font-bold text-gray-800 inline-block relative pb-2 animate-fadeInUp">
                Nos catégories
                <span class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-1/3 h-1 bg-blue-500 rounded-full animate-underline"></span>
            </h2>
        </div>

        <!-- Filtres de catégories -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6 px-6 mb-10">
            <!-- Carte "Tous" -->
            <button data-filter="all"
                    class="category-filter flex flex-col items-center justify-center bg-white text-gray-800 rounded-xl p-6 shadow-md hover:shadow-2xl transition-all duration-300 text-sm font-semibold transform hover:scale-105">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-3 transform transition-all duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <span class="font-bold">Tous</span>
            </button>

            <!-- Autres catégories -->
            <?php foreach ($categories as $category): ?>
                <button data-filter="<?= htmlspecialchars($category['nom']) ?>"
                        class="category-filter flex flex-col items-center justify-center bg-white text-gray-800 rounded-xl p-6 shadow-md hover:shadow-2xl transition-all duration-300 text-sm font-semibold border border-transparent hover:bg-gray-100 transform hover:scale-105">
                    <i class="fas <?= htmlspecialchars($category['icon']) ?> text-2xl mb-3 text-blue-500 transition-all duration-200 transform hover:scale-110"></i>
                    <span class="font-semibold"><?= htmlspecialchars($category['nom']) ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Section Produits Phares -->
        <div class="mb-16">
            <div class="text-center mb-8">
                <h3 class="text-2xl font-semibold inline-block relative pb-2 text-gray-800 dark:text-white">
                    Nos Produits Phares
                    <span class="absolute bottom-0 left-0 right-0 mx-auto w-3/4 h-1 bg-blue-500 rounded-full"></span>
                </h3>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
                <?php 
                $queryFeatured = $pdo->prepare("SELECT p.*, c.nom as category_name 
                                              FROM produits p
                                              JOIN categories c ON p.category_id = c.id
                                              WHERE p.is_featured = 1
                                              ORDER BY RAND() LIMIT 4");
                $queryFeatured->execute();
                $featuredProducts = $queryFeatured->fetchAll();
                
                foreach ($featuredProducts as $produit): ?>
                <div class="product-card bg-white dark:bg-gray-700 rounded-lg overflow-hidden shadow-md transition duration-300 mobile-product-card">
                    <div class="relative">
                       
                        
                       <img src="<?= htmlspecialchars($produit['image_url'] ?: 'images/default.jpg') ?>"
                             alt="<?= htmlspecialchars($produit['nom']) ?>"
                             class="w-full h-36 sm:h-48 object-cover">
                             
                        <?php if ($produit['prix_promo'] && $produit['prix_promo'] < $produit['prix_original']): ?>
                            <div class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-lg">
                                -<?= round((($produit['prix_original'] - $produit['prix_promo']) / $produit['prix_original']) * 100) ?>%
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-3 sm:p-4">
                        <h4 class="text-sm sm:text-lg font-semibold mb-1 sm:mb-2 text-gray-800 dark:text-white line-clamp-1"><?= htmlspecialchars($produit['nom']) ?></h4>
                        <p class="text-gray-600 dark:text-gray-300 text-xs sm:text-sm mb-2 sm:mb-4 line-clamp-3"><?= htmlspecialchars($produit['description']) ?></p>
                        <div class="flex items-center justify-between mb-2 sm:mb-4">
                            <?php if ($produit['prix_promo'] && $produit['prix_promo'] < $produit['prix_original']): ?>
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400 line-through text-xs sm:text-sm">
                                        <?= number_format($produit['prix_original'], 0, ',', ' ') ?> F CFA
                                    </p>
                                    <p class="text-primary font-bold text-sm sm:text-lg">
                                        <?= number_format($produit['prix_promo'], 0, ',', ' ') ?> F CFA
                                    </p>
                                </div>
                            <?php else: ?>
                                <p class="text-primary font-bold text-sm sm:text-lg">
                                    <?= number_format($produit['prix_original'], 0, ',', ' ') ?> F CFA
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="flex space-x-2">
                            <a href="details.php?id=<?= $produit['id'] ?>" class="flex-1 text-center border border-primary text-primary dark:border-primary dark:text-primary rounded-lg py-1 sm:py-2 hover:bg-primary/10 transition text-xs sm:text-sm font-medium">
                                Détails
                            </a>
                            <a href="payer.php?id=<?= $produit['id'] ?>" class="flex-1 text-center bg-primary text-white rounded-lg py-1 sm:py-2 hover:bg-opacity-90 transition text-xs sm:text-sm font-medium">
                                 Acheter
                             </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Titre principal avant les produits -->
<div class="px-6 mb-12 text-center">
    <h2 class="text-3xl md:text-4xl font-bold text-gray-800 dark:text-white inline-block relative pb-2 animate-fadeInUp">
        Tous nos produits
        <span class="absolute bottom-0 left-1/2 transform -translate-x-1/2 w-1/4 h-1 bg-blue-500 rounded-full animate-underline"></span>
    </h2>
    
</div>
        <!-- Liste des produits par catégorie -->
        <div id="products-container">
            <?php foreach ($categories as $category): ?>
                <?php
                $query = $pdo->prepare("SELECT * FROM produits WHERE category_id = ?");
                $query->execute([$category['id']]);
                $produits = $query->fetchAll();
                ?>
                <?php if (!empty($produits)): ?>
                    <div class="product-category mb-16" data-category="<?= htmlspecialchars($category['nom']) ?>">
                        <h3 class="text-2xl font-semibold mb-6 pb-2 border-b-2 border-primary/30 dark:border-primary/50 inline-block text-gray-800 dark:text-white"><?= htmlspecialchars($category['nom']) ?></h3>
                        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
                            <?php foreach ($produits as $produit): ?>
                                <div class="product-card bg-white dark:bg-gray-700 rounded-lg overflow-hidden shadow-md transition duration-300 mobile-product-card">
                                    <div class="relative">
                                        <?php if ($produit['is_featured']): ?>
                                            <div class="absolute top-2 left-2 bg-yellow-500 text-white text-xs font-bold px-2 py-1 rounded-lg">
                                                Phare
                                            </div>
                                        <?php endif; ?>
                                        
                                        <img src="<?= htmlspecialchars($produit['image_url'] ?: 'images/default.jpg') ?>"
                                             alt="<?= htmlspecialchars($produit['nom']) ?>"
                                             class="w-full h-36 sm:h-48 object-cover">

                                        <?php if ($produit['prix_promo'] && $produit['prix_promo'] < $produit['prix_original']): ?>
                                            <div class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-lg">
                                                -<?= round((($produit['prix_original'] - $produit['prix_promo']) / $produit['prix_original']) * 100) ?>%
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="p-3 sm:p-4">
                                        <h4 class="text-sm sm:text-lg font-semibold mb-1 sm:mb-2 text-gray-800 dark:text-white line-clamp-1"><?= htmlspecialchars($produit['nom']) ?></h4>
                                        <p class="text-gray-600 dark:text-gray-300 text-xs sm:text-sm mb-2 sm:mb-4 line-clamp-3"><?= htmlspecialchars($produit['description']) ?></p>
                                        <div class="flex items-center justify-between mb-2 sm:mb-4">
                                            <?php if ($produit['prix_promo'] && $produit['prix_promo'] < $produit['prix_original']): ?>
                                                <div>
                                                    <p class="text-gray-500 dark:text-gray-400 line-through text-xs sm:text-sm">
                                                        <?= number_format($produit['prix_original'], 0, ',', ' ') ?> F CFA
                                                    </p>
                                                    <p class="text-primary font-bold text-sm sm:text-lg">
                                                        <?= number_format($produit['prix_promo'], 0, ',', ' ') ?> F CFA
                                                    </p>
                                                </div>
                                            <?php else: ?>
                                                <p class="text-primary font-bold text-sm sm:text-lg">
                                                    <?= number_format($produit['prix_original'], 0, ',', ' ') ?> F CFA
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex space-x-2">
                                            <a href="details.php?id=<?= $produit['id'] ?>" class="flex-1 text-center border border-primary text-primary dark:border-primary dark:text-primary rounded-lg py-1 sm:py-2 hover:bg-primary/10 transition text-xs sm:text-sm font-medium">
                                                Détails
                                            </a>
                                            <a href="payer.php?id=<?= $produit['id'] ?>" class="flex-1 text-center bg-primary text-white rounded-lg py-1 sm:py-2 hover:bg-opacity-90 transition text-xs sm:text-sm font-medium">
                                                   Acheter
                                             </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
  <!-- Features -->
  <section class="py-16 bg-white dark:bg-gray-800 reveal">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold text-center mb-4 text-gray-800 dark:text-white">Pourquoi nous choisir ?</h2>
      <p class="text-center text-gray-600 dark:text-gray-400 max-w-2xl mx-auto mb-12">Nous proposons des produits digitaux de qualité supérieure, conçus pour vous aider à réussir</p>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-xl shadow-md dark:shadow-xl flex flex-col items-center text-center transform transition hover:scale-105">
          <div class="bg-primary/10 dark:bg-primary/20 p-3 rounded-full mb-4">
            <i class="fas fa-award text-primary text-2xl"></i>
          </div>
          <h3 class="text-xl font-semibold mb-2">Qualité Premium</h3>
          <p class="text-gray-600 dark:text-gray-300">Tous nos produits sont élaborés avec les plus hauts standards de qualité.</p>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-xl shadow-md dark:shadow-xl flex flex-col items-center text-center transform transition hover:scale-105">
          <div class="bg-primary/10 dark:bg-primary/20 p-3 rounded-full mb-4">
            <i class="fas fa-headset text-primary text-2xl"></i>
          </div>
          <h3 class="text-xl font-semibold mb-2">Support Réactif</h3>
          <p class="text-gray-600 dark:text-gray-300">Notre équipe est disponible pour vous aider et répondre à toutes vos questions.</p>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-xl shadow-md dark:shadow-xl flex flex-col items-center text-center transform transition hover:scale-105">
          <div class="bg-primary/10 dark:bg-primary/20 p-3 rounded-full mb-4">
            <i class="fas fa-bolt text-primary text-2xl"></i>
          </div>
          <h3 class="text-xl font-semibold mb-2">Livraison Rapide</h3>
          <p class="text-gray-600 dark:text-gray-300">Accédez immédiatement à vos produits après l'achat.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact section -->
  <section id="contact" class="py-16 bg-gray-50 dark:bg-gray-900 reveal">
    <div class="container mx-auto px-4">
      <h2 class="text-3xl font-bold text-center mb-4 text-gray-800 dark:text-white">Contactez-nous</h2>
      <p class="text-center text-gray-600 dark:text-gray-400 max-w-2xl mx-auto mb-12">Nous sommes à votre disposition pour répondre à toutes vos questions</p>
      
      <div class="max-w-3xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-md dark:shadow-xl">
          <h3 class="text-xl font-semibold mb-4 text-gray-800 dark:text-white">Envoyez-nous un message</h3>
          <form>
            <div class="mb-4">
              <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2" for="name">Nom</label>
              <input type="text" id="name" class="w-full px-3 py-2 text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white" placeholder="Votre nom">
            </div>
            <div class="mb-4">
              <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2" for="email">Email</label>
              <input type="email" id="email" class="w-full px-3 py-2 text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white" placeholder="Votre email">
            </div>
            <div class="mb-4">
              <label class="block text-gray-700 dark:text-gray-300 text-sm font-medium mb-2" for="message">Message</label>
              <textarea id="message" rows="4" class="w-full px-3 py-2 text-base border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary dark:bg-gray-700 dark:text-white" placeholder="Votre message"></textarea>
            </div>
            <button type="submit" class="w-full bg-primary hover:bg-opacity-90 text-white font-medium py-2 px-4 rounded-lg transition">
              Envoyer
            </button>
          </form>
        </div>
        
        
          
          
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- WhatsApp Floating Button -->
  <a href="https://wa.me/22956308400" target="_blank" class="floating-whatsapp bg-green-500 text-white p-4 rounded-full shadow-lg hover:bg-green-600 transition">
    <i class="fab fa-whatsapp text-2xl"></i>
  </a>

  <!-- Footer -->
  <footer class="bg-gray-900 text-gray-400 pt-12 pb-8 mt-auto">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
        <div>
          <h3 class="text-white text-lg font-semibold mb-4">DIGITEK EMPIRE</h3>
          <p class="text-sm">Votre allié de confiance pour des produits digitaux de qualité exceptionnelle.</p>
        </div>
        
        <div>
          <h4 class="text-white text-base font-medium mb-4">Liens Rapides</h4>
          <ul class="space-y-2 text-sm">
            <li><a href="#" class="hover:text-primary transition">Accueil</a></li>
            <li><a href="#products" class="hover:text-primary transition">Produits</a></li>
            <li><a href="#contact" class="hover:text-primary transition">Contact</a></li>
          </ul>
        </div>
        
        <div>
          <h4 class="text-white text-base font-medium mb-4">Catégories</h4>
          <ul class="space-y-2 text-sm">
            <?php foreach (array_slice($categories, 0, 4) as $category): ?>
              <li><a href="#" class="hover:text-primary transition"><?= htmlspecialchars($category['nom']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        
        
      
      <div class="border-t border-gray-800 pt-6 flex flex-col md:flex-row justify-between items-center">
        <p class="text-sm">&copy; <?= date('Y') ?> DIGITEK EMPIRE. Tous droits réservés.</p>
        <div class="flex space-x-4 mt-4 md:mt-0">
        </div>
      </div>
    </div>
  </footer>

  <script>
    // Dark mode toggle
    document.getElementById('darkModeToggle').addEventListener('click', function() {
      document.documentElement.classList.toggle('dark');
    });
    
    // Reveal animations on scroll
    document.addEventListener('DOMContentLoaded', function() {
      const revealElements = document.querySelectorAll('.reveal');
      
      const revealOnScroll = function() {
        for (let i = 0; i < revealElements.length; i++) {
          const windowHeight = window.innerHeight;
          const elementTop = revealElements[i].getBoundingClientRect().top;
          const elementVisible = 150;
          
          if (elementTop < windowHeight - elementVisible) {
            revealElements[i].classList.add('active');
          }
        }
      };
      
      window.addEventListener('scroll', revealOnScroll);
      revealOnScroll(); // Check initial state on load
      
      // Category filtering
      const categoryFilters = document.querySelectorAll('.category-filter');
      const productCategories = document.querySelectorAll('.product-category');
      
      categoryFilters.forEach(filter => {
        filter.addEventListener('click', function() {
          const category = this.getAttribute('data-filter');
          
          // Update active filter button
          categoryFilters.forEach(btn => btn.classList.remove('active', 'bg-primary', 'text-white'));
          categoryFilters.forEach(btn => btn.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-800', 'dark:text-white'));
          this.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-800', 'dark:text-white');
          this.classList.add('active', 'bg-primary', 'text-white');
          
          // Show/hide product categories
          if (category === 'all') {
            productCategories.forEach(cat => cat.style.display = 'block');
          } else {
            productCategories.forEach(cat => {
              if (cat.getAttribute('data-category') === category) {
                cat.style.display = 'block';
              } else {
                cat.style.display = 'none';
              }
            });
          }
        });
      });
      
      // Category card clicks
      const categoryCards = document.querySelectorAll('.category-card');
      categoryCards.forEach(card => {
        card.addEventListener('click', function() {
          const category = this.getAttribute('data-category');
          const filterBtn = document.querySelector(`.category-filter[data-filter="${category}"]`);
          if (filterBtn) {
            filterBtn.click();
            document.getElementById('products').scrollIntoView({ behavior: 'smooth' });
          }
        });
      });
    });
  </script>
  <script>
  document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('.category-filter');
    const cards = document.querySelectorAll('[data-category]');

    buttons.forEach(button => {
      button.addEventListener('click', () => {
        const filter = button.dataset.filter;

        // Active class visuelle
        buttons.forEach(btn => btn.classList.remove('bg-blue-600', 'text-white'));
        buttons.forEach(btn => btn.classList.add('bg-white', 'text-gray-800'));
        button.classList.remove('bg-white', 'text-gray-800');
        button.classList.add('bg-blue-600', 'text-white');

        // Affichage filtré
        cards.forEach(card => {
          const category = card.dataset.category;
          if (filter === 'all' || filter === category) {
            card.classList.remove('hidden');
          } else {
            card.classList.add('hidden');
          }
        });
      });
    });
  });
  document.addEventListener('DOMContentLoaded', () => {
    // Sélectionner toutes les catégories
    const categoryButtons = document.querySelectorAll('.category-filter');
    
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Enlever l'état actif de toutes les catégories
            categoryButtons.forEach(btn => {
                btn.classList.remove('bg-red-600', 'text-white');
                btn.classList.add('bg-white', 'text-gray-800');
            });

            // Ajouter l'état actif à la catégorie cliquée
            this.classList.add('bg-red-600', 'text-white');
            this.classList.remove('bg-white', 'text-gray-800');
        });
    });
});

</script>
</body>
</html>