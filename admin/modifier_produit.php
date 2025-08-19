<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../includes/config.php';

// Vérifie si l'admin est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

$username = $_SESSION['admin_username'] ?? 'Administrateur';
$notification = '';
$notificationType = '';

// Mise à jour du produit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nom = trim($_POST['nom'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $prix_original = floatval($_POST['prix_original'] ?? 0);
    $prix_promo = isset($_POST['prix_promo']) && $_POST['prix_promo'] !== '' ? floatval($_POST['prix_promo']) : null;
    $promo_fin = !empty($_POST['promo_fin']) ? $_POST['promo_fin'] : null;
    $caracteristiques = trim($_POST['caracteristiques'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // Récupère l'image actuelle
    $stmt = $pdo->prepare("SELECT image_url FROM produits WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $produitActuel = $stmt->fetch(PDO::FETCH_ASSOC);
    $image_url = $produitActuel['image_url'] ?? '';

    // Gestion de l'upload d'image
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $maxSize = 2 * 1024 * 1024;
        $uploadDir = __DIR__ . '/../images/';
        $fileTmp = $_FILES['image_file']['tmp_name'];
        $fileSize = $_FILES['image_file']['size'];
        $fileType = mime_content_type($fileTmp);
        $fileExt = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));

        if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
            $newFileName = uniqid() . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmp, $destination)) {
                $image_url = 'images/' . $newFileName;
            } else {
                $notification = "Erreur lors du téléversement de l'image.";
                $notificationType = 'error';
            }
        } else {
            $notification = "Format ou taille de fichier non valide (max 2 Mo, JPG/PNG/GIF/WEBP).";
            $notificationType = 'error';
        }
    }

    // Validation
    if (!$id || !$nom || !$description || !$category_id || !$prix_original || !$image_url) {
        $notification = "Certains champs obligatoires sont manquants.";
        $notificationType = 'error';
    } else {
        try {
            $sql = "UPDATE produits SET 
                        nom = :nom, 
                        description = :description, 
                        category_id = :category_id, 
                        prix_original = :prix_original, 
                        prix_promo = :prix_promo, 
                        promo_fin = :promo_fin,
                        image_url = :image_url,
                        caracteristiques = :caracteristiques,
                        is_featured = :is_featured
                    WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nom' => $nom,
                ':description' => $description,
                ':category_id' => $category_id,
                ':prix_original' => $prix_original,
                ':prix_promo' => $prix_promo,
                ':promo_fin' => $promo_fin,
                ':image_url' => $image_url,
                ':caracteristiques' => $caracteristiques,
                ':is_featured' => $is_featured,
                ':id' => $id
            ]);

            $notification = "Le produit a été mis à jour avec succès.";
            $notificationType = 'success';
        } catch (PDOException $e) {
            $notification = "Erreur lors de la mise à jour : " . $e->getMessage();
            $notificationType = 'error';
        }
    }
}

// Récupération du produit pour affichage
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produit) {
        header("Location: produits.php");
        exit;
    }
} else {
    header("Location: produits.php");
    exit;
}

// Récupération des catégories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY nom");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nom de la catégorie actuelle
$categoryName = "";
if (!empty($produit['category_id'])) {
    $stmtCat = $pdo->prepare("SELECT nom FROM categories WHERE id = ?");
    $stmtCat->execute([$produit['category_id']]);
    $category = $stmtCat->fetch(PDO::FETCH_ASSOC);
    $categoryName = $category['nom'] ?? "";
}
?>





<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un produit | Administration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Favicon de base (PNG) -->
    <link rel="icon" type="image/png" href="img.png">
    
    <!-- Pour les appareils Apple (iPhone, iPad) -->
    <link rel="apple-touch-icon" href="img.png">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Pour éviter les problèmes de cache -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <script>
        // Support du mode sombre
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
            if (event.matches) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        });

        // Configuration de Tailwind pour le mode sombre
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#f5f5ff',
                            100: '#e8e8ff',
                            200: '#d1d1fe',
                            300: '#b3b2fd',
                            400: '#908ffb',
                            500: '#5D5CDE',
                            600: '#5553d2',
                            700: '#4744b8',
                            800: '#3c3997',
                            900: '#352f76',
                            950: '#201c47',
                        },
                        secondary: '#6366F1'
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'bounce-slow': 'bounce 2s ease-in-out infinite',
                        'fade-in': 'fadeIn 0.5s ease-out forwards',
                        'slide-up': 'slideUp 0.5s ease-out forwards',
                        'scale-in': 'scaleIn 0.3s ease-out forwards',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        scaleIn: {
                            '0%': { transform: 'scale(0.9)', opacity: '0' },
                            '100%': { transform: 'scale(1)', opacity: '1' },
                        },
                    },
                    boxShadow: {
                        'card': '0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03)',
                        'card-hover': '0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.03)',
                        'glow': '0 0 15px rgba(93, 92, 222, 0.5)',
                    }
                }
            }
        }
    </script>
    <style>
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .dark ::-webkit-scrollbar-track {
            background: #1f2937;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #d1d1fe;
            border-radius: 4px;
        }
        
        .dark ::-webkit-scrollbar-thumb {
            background: #4744b8;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #5D5CDE;
        }
        
        .dark ::-webkit-scrollbar-thumb:hover {
            background: #5553d2;
        }
        
        /* Card animations */
        .card-stats {
            transition: all 0.3s ease;
        }
        
        .card-stats:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .card-stats .icon-wrapper {
            transition: all 0.3s ease;
        }
        
        .card-stats:hover .icon-wrapper {
            transform: scale(1.1);
        }
        
        /* Main content transition */
        .main-content-transition {
            transition: margin-left 0.3s ease-in-out;
        }
        
        @media (max-width: 768px) {
            .main-content-transition {
                margin-left: 0 !important;
            }
        }
        
        /* Fade in animation for elements */
        .fade-in {
            opacity: 0;
            animation: fadeIn 0.6s ease forwards;
        }
        
        .fade-in-delay-1 {
            animation-delay: 0.1s;
        }
        
        .fade-in-delay-2 {
            animation-delay: 0.2s;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Gradient backgrounds */
        .bg-gradient-primary {
            background: linear-gradient(135deg, #5D5CDE 0%, #6366F1 100%);
        }
        
        .dark .bg-gradient-primary {
            background: linear-gradient(135deg, #4744b8 0%, #5553d2 100%);
        }
        
        /* File input styling */
        input[type="file"]::file-selector-button {
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.4rem;
            background-color: #f5f5ff;
            color: #5D5CDE;
            margin-right: 1rem;
            transition: all 0.2s ease;
        }
        
        .dark input[type="file"]::file-selector-button {
            background-color: rgba(93, 92, 222, 0.2);
            color: #b3b2fd;
        }
        
        input[type="file"]::file-selector-button:hover {
            background-color: #e8e8ff;
        }
        
        .dark input[type="file"]::file-selector-button:hover {
            background-color: rgba(93, 92, 222, 0.3);
        }
        
        /* Image preview animation */
        .image-preview-anim {
            transition: all 0.3s ease;
        }
        
        .image-preview-anim:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        /* Form section transition */
        .form-section {
            transition: all 0.3s ease;
        }
        
        .form-section:hover {
            background-color: rgba(245, 245, 255, 0.3);
        }
        
        .dark .form-section:hover {
            background-color: rgba(93, 92, 222, 0.05);
        }
        
        /* Focus animation for inputs */
        input:focus, textarea:focus, select:focus {
            box-shadow: 0 0 0 3px rgba(93, 92, 222, 0.2);
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen font-sans flex flex-col">
    <!-- Mobile Hamburger Menu Button -->
    <button id="hamburger-button" class="md:hidden fixed top-4 left-4 z-50 p-2.5 rounded-xl bg-primary-500 text-white shadow-lg hover:bg-primary-600 transition-all duration-300 ease-in-out">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Overlay for mobile sidebar -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black opacity-0 pointer-events-none md:hidden transition-opacity duration-300 z-30"></div>

    <div class="flex flex-col md:flex-row min-h-screen">
        <!-- Sidebar Navigation -->
        <aside id="sidebar" class="fixed md:sticky top-0 left-0 w-72 md:w-72 bg-white dark:bg-gray-800 shadow-lg h-screen overflow-y-auto transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-40">
            <div class="bg-gradient-primary p-6 text-white">
                <div class="flex items-center mb-4">
                    <div class="flex items-center">
                        <div class="w-12 h-12 rounded-full overflow-hidden shadow-md border-2 border-white/30">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/aa/Mangekyou_Sharingan_Sasuke_%28Eternal%29.svg/2048px-Mangekyou_Sharingan_Sasuke_%28Eternal%29.svg.png" alt="Photo de profil" class="w-full h-full object-cover">
                        </div>
                        <div class="ml-4">
                            <h2 class="text-xl font-bold">DIGITEK EMPIRE</h2>
                            <div class="flex items-center mt-1">
                                <span class="relative inline-block w-2 h-2 bg-green-500 rounded-full mr-2">
                                    <span class="absolute inset-0 bg-green-500 rounded-full animate-ping opacity-75"></span>
                                </span>
                                <p class="text-sm opacity-90">Administration</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-white/10 p-3 rounded-lg text-sm backdrop-blur-sm">
                    <p class="text-white/90">Bonjour, <span class="font-semibold"><?php echo htmlspecialchars($username); ?></span></p>
                    <p class="text-xs mt-1 text-white/70">Dernière connexion: <?php echo date('d/m/Y H:i'); ?></p>
                </div>
            </div>
            
            <nav class="p-4">
                <div class="text-xs uppercase text-gray-500 dark:text-gray-400 font-semibold tracking-wider mb-4 pl-4">Menu principal</div>
                <ul class="space-y-2">
                    <li>
                        <a href="dashboard.php" class="flex items-center p-3.5 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-800 dark:hover:text-white rounded-xl transition-colors group duration-200">
                            <div class="w-5 h-5 flex items-center justify-center text-gray-500 dark:text-gray-400 group-hover:text-primary-500 dark:group-hover:text-primary-400 group-hover:scale-110 transition-all">
                                <i class="fas fa-tachometer-alt"></i>
                            </div>
                            <span class="ml-3">Tableau de bord</span>
                        </a>
                    </li>
                    <li>
                        <a href="produits.php" class="flex items-center p-3.5 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-800 dark:hover:text-white rounded-xl transition-colors group duration-200">
                            <div class="w-5 h-5 flex items-center justify-center text-gray-500 dark:text-gray-400 group-hover:text-primary-500 dark:group-hover:text-primary-400 group-hover:scale-110 transition-all">
                                <i class="fas fa-box"></i>
                            </div>
                            <span class="ml-3">Voir les produits</span>
                        </a>
                    </li>
                    <li>
                        <a href="ajouter_produit.php" class="flex items-center p-3.5 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-800 dark:hover:text-white rounded-xl transition-colors group duration-200">
                            <div class="w-5 h-5 flex items-center justify-center text-gray-500 dark:text-gray-400 group-hover:text-primary-500 dark:group-hover:text-primary-400 group-hover:scale-110 transition-all">
                                <i class="fas fa-plus"></i>
                            </div>
                            <span class="ml-3">Ajouter un produit</span>
                        </a>
                    </li>
                    <li>
                        <a href="modifier_produit.php" class="flex items-center p-3.5 text-gray-800 dark:text-white bg-primary-50 dark:bg-primary-900/30 rounded-xl border-l-4 border-primary-500 font-medium group transition-all duration-200">
                            <div class="w-5 h-5 flex items-center justify-center text-primary-500 dark:text-primary-400 group-hover:scale-110 transition-transform">
                                <i class="fas fa-edit"></i>
                            </div>
                            <span class="ml-3">Modifier un produit</span>
                        </a>
                    </li>
                    <li class="border-t dark:border-gray-700 my-4 pt-4">
                        <a href="logout.php" class="flex items-center p-3.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-xl transition-colors group duration-200">
                            <div class="w-5 h-5 flex items-center justify-center group-hover:scale-110 transition-transform">
                                <i class="fas fa-sign-out-alt"></i>
                            </div>
                            <span class="ml-3">Se déconnecter</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="p-4 mt-auto border-t dark:border-gray-700">
                <div class="bg-primary-50 dark:bg-gray-700 p-4 rounded-xl relative overflow-hidden">
                    <div class="relative z-10">
                        <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">Besoin d'aide ?</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Notre support technique est disponible 24/7.</p>
                        <a href="#" class="text-sm text-primary-600 dark:text-primary-400 hover:underline inline-flex items-center">
                            Contacter le support
                            <i class="fas fa-arrow-right ml-1 text-xs"></i>
                        </a>
                    </div>
                    <div class="absolute bottom-0 right-0 opacity-10">
                        <i class="fas fa-headset text-5xl text-primary-500"></i>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 main-content-transition md:ml-0">
            <!-- Header -->
            <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center py-4 md:justify-end md:space-x-10">
                        <div class="flex items-center md:hidden">
                            <h1 class="text-xl font-bold text-gray-800 dark:text-white ml-8">Modifier un produit</h1>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <div class="relative">
                                <button class="p-2 bg-gray-100 dark:bg-gray-700 rounded-full text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                    <i class="fas fa-bell"></i>
                                </button>
                                <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
                            </div>
                            
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-full bg-gradient-primary flex items-center justify-center text-white">
                                    <span class="font-medium text-sm">
                                        <?php 
                                            // Get first letter of username
                                            echo substr(htmlspecialchars($username), 0, 1); 
                                        ?>
                                    </span>
                                </div>
                                <span class="hidden md:inline-block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <?php echo htmlspecialchars($username); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Dashboard Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="mb-8 fade-in">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Modifier un produit</h1>
                            <p class="mt-1 text-gray-600 dark:text-gray-400">Modifiez un produit existant et prévisualisez son apparence</p>
                        </div>
                    </div>
                    
                    <?php if (!empty($notification)): ?>
                        <div class="mt-6 p-4 rounded-xl border <?php echo $notificationType === 'success' ? 'bg-green-50 border-green-200 text-green-700 dark:bg-green-900/30 dark:border-green-700 dark:text-green-400' : 'bg-red-50 border-red-200 text-red-700 dark:bg-red-900/30 dark:border-red-700 dark:text-red-400'; ?>">
                            <div class="flex items-center">
                                <i class="fas <?php echo $notificationType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-3"></i>
                                <?php echo $notification; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Form & Preview Grid -->
                    <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Formulaire de modification -->
                        <div class="lg:col-span-2 fade-in fade-in-delay-1">
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-card border border-gray-100 dark:border-gray-700 overflow-hidden transition-all duration-300 hover:shadow-card-hover">
                                <div class="p-4 md:p-5 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/70">
                                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white flex items-center">
                                        <i class="fas fa-edit mr-2 text-primary-500"></i>
                                        Informations du produit
                                    </h2>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Remplissez tous les champs requis (*)</p>
                                </div>
                                
                                <form action="modifier_produit.php" method="POST" enctype="multipart/form-data" id="productForm" class="p-5 md:p-6 space-y-7">
                                    <input type="hidden" name="id" value="<?= $produit['id'] ?>">
                                    
                                    <!-- Section: Informations générales -->
                                    <div class="space-y-5">
                                        <h3 class="text-lg font-medium text-gray-800 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">
                                            <i class="fas fa-info-circle text-primary-400 mr-2"></i>Informations générales
                                        </h3>
                                        
                                        <div class="grid grid-cols-1 gap-5">
                                            <div>
                                                <label for="nom" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Nom du produit <span class="text-red-500">*</span>
                                                </label>
                                                <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($produit['nom']) ?>" required
                                                    class="w-full px-4 py-3 text-base rounded-lg border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-colors duration-200 placeholder-gray-400">
                                            </div>
                                            
                                            <div>
                                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Description <span class="text-red-500">*</span>
                                                </label>
                                                <textarea id="description" name="description" rows="4" required
                                                    class="w-full px-4 py-3 text-base rounded-lg border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-colors duration-200"><?= htmlspecialchars($produit['description']) ?></textarea>
                                            </div>
                                            
                                            <div>
                                                <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Catégorie <span class="text-red-500">*</span>
                                                </label>
                                                <div class="relative">
                                                    <select id="category_id" name="category_id" required
                                                        class="appearance-none w-full pl-4 pr-10 py-3 text-base rounded-lg border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-colors duration-200">
                                                        <?php foreach ($categories as $cat): ?>
                                                            <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $produit['category_id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($cat['nom']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-700 dark:text-gray-300">
                                                        <i class="fas fa-chevron-down text-gray-400"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Section: Prix et promotion -->
                                    <div class="space-y-5">
                                        <h3 class="text-lg font-medium text-gray-800 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">
                                            <i class="fas fa-tag text-primary-400 mr-2"></i>Prix et promotion
                                        </h3>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                            <div>
                                                <label for="prix_original" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Prix original (F CFA) <span class="text-red-500">*</span>
                                                </label>
                                                <div class="relative rounded-lg">
                                                    <input type="number" id="prix_original" name="prix_original" value="<?= $produit['prix_original'] ?>" 
                                                        step="1" min="0" required
                                                        class="w-full pl-4 pr-20 py-3 text-base rounded-lg border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-colors duration-200">
                                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                        <span class="text-gray-500 dark:text-gray-400 sm:text-sm">F CFA</span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <label for="prix_promo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Prix promotionnel (F CFA)
                                                </label>
                                                <div class="relative rounded-lg">
                                                    <input type="number" id="prix_promo" name="prix_promo" value="<?= $produit['prix_promo'] ?>" 
                                                        step="1" min="0"
                                                        class="w-full pl-4 pr-20 py-3 text-base rounded-lg border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-colors duration-200">
                                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                        <span class="text-gray-500 dark:text-gray-400 sm:text-sm">F CFA</span>
                                                    </div>
                                                </div>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Laissez vide si le produit n'est pas en promotion</p>
                                            </div>
                                            
                                            <div>
                                                <label for="promo_fin" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Date de fin de promotion
                                                </label>
                                                <input type="datetime-local" id="promo_fin" name="promo_fin" 
                                                    value="<?= $produit['promo_fin'] ? date('Y-m-d\TH:i', strtotime($produit['promo_fin'])) : '' ?>"
                                                    class="w-full px-4 py-3 text-base rounded-lg border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-colors duration-200">
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Applicable uniquement si un prix promotionnel est défini</p>
                                            </div>
                                            
                                            <div class="flex items-center space-x-3">
                                                <div class="flex h-full items-end pb-6">
                                                    <label class="inline-flex items-center cursor-pointer">
                                                        <input type="checkbox" id="is_featured" name="is_featured" value="1" <?= $produit['is_featured'] ? 'checked' : '' ?>
                                                            class="sr-only peer">
                                                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-500/50 dark:peer-focus:ring-primary-400/50 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-500"></div>
                                                        <span class="ms-3 text-sm font-medium text-gray-800 dark:text-gray-300">Produit phare</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Section: Image et média -->
                                    <div class="space-y-5">
                                        <h3 class="text-lg font-medium text-gray-800 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">
                                            <i class="fas fa-image text-primary-400 mr-2"></i>Image et média
                                        </h3>
                                        
                                        <div class="grid grid-cols-1 gap-5">
                                            <!-- Image actuelle -->
                                            <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Image actuelle du produit</label>
                                                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4">
                                                    <img src="../<?= htmlspecialchars($produit['image_url']) ?>" alt="Image actuelle"
                                                        class="w-40 h-40 object-cover rounded-lg shadow-md border border-gray-300 dark:border-gray-600 transition-transform hover:scale-105 cursor-pointer">
                                                    <div class="text-center sm:text-left">
                                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Nom du fichier: <span class="font-medium"><?= basename($produit['image_url']) ?></span></p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-500">Cliquez sur l'image pour l'agrandir</p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Upload nouvelle image -->
                                            <div>
                                                <label for="image_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Téléverser une nouvelle image
                                                </label>
                                                <div class="flex items-center justify-center w-full">
                                                    <label for="image_file" class="flex flex-col items-center justify-center w-full h-28 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                                            <i class="fas fa-cloud-upload-alt mb-2 text-xl text-gray-500 dark:text-gray-400"></i>
                                                            <p class="mb-1 text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Cliquez pour téléverser</span> ou glissez-déposez</p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-500">JPG, PNG, GIF ou WEBP (MAX. 2 Mo)</p>
                                                        </div>
                                                        <input id="image_file" name="image_file" type="file" class="hidden" accept="image/png, image/jpeg, image/webp, image/gif" />
                                                    </label>
                                                </div>
                                                <div id="file-name-display" class="mt-2 text-sm text-gray-600 dark:text-gray-400 hidden">
                                                    <span class="font-medium">Fichier sélectionné:</span> <span id="selected-file-name"></span>
                                                </div>
                                            </div>
                                            
                                            <!-- Fichier à livrer -->
                                            <div>
                                                <label for="fichier" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    Fichier à livrer après paiement
                                                </label>
                                                <?php if (!empty($produit['fichier_path'])): ?>
                                                    <div class="mb-3 p-3 rounded-lg bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-700/50 flex items-center">
                                                        <i class="fas fa-file-alt text-primary-500 mr-2"></i>
                                                        <p class="text-sm text-gray-700 dark:text-gray-300">
                                                            Fichier actuel : <span class="font-medium"><?= basename($produit['fichier_path']) ?></span>
                                                        </p>
                                                    </div>
                                                <?php endif; ?>
                                                <input type="file" id="fichier" name="fichier" accept=".zip,.pdf,.rar,.docx,.xlsx,.mp4,.mp3,.txt,.jpg,.png"
                                                    class="block w-full text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-white dark:bg-gray-700 focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary-50 file:text-primary-700 dark:file:bg-primary-900/30 dark:file:text-primary-400 hover:file:bg-primary-100 dark:hover:file:bg-primary-900/50 file:transition-colors">
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Laissez vide pour conserver le fichier actuel. Formats acceptés : zip, pdf, docx, mp4, etc.</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Section: Caractéristiques -->
                                    <div class="space-y-5">
                                        <h3 class="text-lg font-medium text-gray-800 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">
                                            <i class="fas fa-list-ul text-primary-400 mr-2"></i>Caractéristiques
                                        </h3>
                                        
                                        <div>
                                            <label for="caracteristiques" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Liste des caractéristiques <span class="text-red-500">*</span>
                                            </label>
                                            <textarea id="caracteristiques" name="caracteristiques" rows="4" required
                                                class="w-full px-4 py-3 text-base rounded-lg border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white bg-white dark:bg-gray-700 focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500 transition-colors duration-200"
                                                placeholder="Listez les caractéristiques principales du produit"><?= htmlspecialchars($produit['caracteristiques']) ?></textarea>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Séparez chaque caractéristique par un retour à la ligne</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Boutons d'action -->
                                    <div class="flex flex-col sm:flex-row justify-between pt-5 border-t border-gray-200 dark:border-gray-700 gap-3 sm:gap-4">
                                        <a href="produits.php"
                                            class="flex-1 sm:flex-none sm:w-auto px-6 py-3 text-center font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all">
                                            <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
                                        </a>
                                        <button type="submit"
                                            class="flex-1 sm:flex-none sm:w-auto px-8 py-3 bg-primary-500 hover:bg-primary-600 focus:ring-primary-500/50 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all flex items-center justify-center">
                                            <i class="fas fa-save mr-2"></i>Enregistrer les modifications
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Prévisualisation du produit -->
                        <div class="fade-in fade-in-delay-2">
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-card border border-gray-100 dark:border-gray-700 overflow-hidden transition-all duration-300 hover:shadow-card-hover sticky top-24">
                                <div class="p-4 md:p-5 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/70">
                                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white flex items-center">
                                        <i class="fas fa-eye mr-2 text-primary-500"></i>
                                        Aperçu du produit
                                    </h2>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Visualisation en temps réel</p>
                                </div>
                                
                                <div class="p-5">
                                    <div class="bg-white dark:bg-gray-900 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 shadow">
                                        <!-- Image du produit -->
                                        <div class="relative aspect-video bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                            <img id="preview_image" src="../<?= htmlspecialchars($produit['image_url']) ?>" alt="<?= htmlspecialchars($produit['nom']) ?>" class="w-full h-full object-cover">
                                            <div id="image_placeholder" class="absolute inset-0 flex items-center justify-center <?= empty($produit['image_url']) ? '' : 'hidden' ?>">
                                                <div class="text-gray-400 dark:text-gray-600">
                                                    <i class="fas fa-image text-4xl mb-2"></i>
                                                    <p class="text-sm">Image non disponible</p>
                                                </div>
                                            </div>
                                            <!-- Badge promo -->
                                            <div id="preview_promo_badge" class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded <?= ($produit['prix_promo'] && $produit['prix_promo'] < $produit['prix_original']) ? '' : 'hidden' ?>">
                                                PROMO
                                            </div>
                                        </div>
                                        
                                        <!-- Contenu du produit -->
                                        <div class="p-4">
                                            <div class="mb-2">
                                                <span id="preview_category" class="inline-block bg-primary-100 dark:bg-primary-900/30 text-primary-800 dark:text-primary-300 text-xs px-2 py-1 rounded">
                                                    <?= htmlspecialchars($categoryName) ?>
                                                </span>
                                            </div>
                                            <h3 id="preview_nom" class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                                <?= htmlspecialchars($produit['nom']) ?>
                                            </h3>
                                            <p id="preview_description" class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                                                <?= htmlspecialchars($produit['description']) ?>
                                            </p>
                                            
                                            <!-- Prix -->
                                            <div class="flex items-center mb-4">
                                                <span id="preview_prix_original" class="<?= ($produit['prix_promo'] && $produit['prix_promo'] < $produit['prix_original']) ? 'line-through text-gray-500 dark:text-gray-400 text-base' : 'text-xl font-bold text-gray-800 dark:text-white' ?>">
                                                    <?= number_format($produit['prix_original'], 0, ',', ' ') ?> F CFA
                                                </span>
                                                
                                                <span id="preview_prix_promo" class="ml-2 text-xl font-bold text-red-600 dark:text-red-500 <?= ($produit['prix_promo'] && $produit['prix_promo'] < $produit['prix_original']) ? '' : 'hidden' ?>">
                                                    <?= $produit['prix_promo'] ? number_format($produit['prix_promo'], 0, ',', ' ') . ' F CFA' : '' ?>
                                                </span>
                                            </div>
                                            
                                            <!-- Date promo -->
                                            <p id="preview_date_promo" class="text-xs text-gray-500 dark:text-gray-400 mb-3 <?= ($produit['prix_promo'] && $produit['promo_fin']) ? '' : 'hidden' ?>">
                                                Promotion jusqu'au <?= $produit['promo_fin'] ? date('d/m/Y', strtotime($produit['promo_fin'])) : '' ?>
                                            </p>
                                            
                                            <!-- Caractéristiques -->
                                            <div id="preview_caracteristiques" class="mt-4 bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Caractéristiques principales :</h4>
                                                <ul class="text-xs space-y-1 pl-5 list-disc">
                                                    <?php
                                                    $caracteristiques = explode("\n", $produit['caracteristiques']);
                                                    foreach($caracteristiques as $caracteristique):
                                                        if(trim($caracteristique) !== ''):
                                                    ?>
                                                        <li class="text-gray-600 dark:text-gray-400"><?= htmlspecialchars(trim($caracteristique)) ?></li>
                                                    <?php
                                                        endif;
                                                    endforeach;
                                                    ?>
                                                </ul>
                                            </div>
                                            
                                            <!-- Bouton d'action -->
                                            <div class="mt-4">
                                                <button class="w-full py-2 bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-lg transition-colors">
                                                    <i class="fas fa-shopping-cart mr-2"></i>Ajouter au panier
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-6 mt-auto">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Panneau d'administration DIGITEK EMPIRE &copy; <?php echo date('Y'); ?></p>
                    <div class="flex space-x-4 mt-4 md:mt-0">
                        <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-primary-500 dark:hover:text-primary-400">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-primary-500 dark:hover:text-primary-400">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-primary-500 dark:hover:text-primary-400">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
            </footer>
        </main>
    </div>
    
    <script>
    // Mise à jour de l'aperçu en temps réel
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('productForm');
        
        // Éléments du formulaire
        const nomInput = document.getElementById('nom');
        const descriptionInput = document.getElementById('description');
        const categorySelect = document.getElementById('category_id');
        const prixOriginalInput = document.getElementById('prix_original');
        const prixPromoInput = document.getElementById('prix_promo');
        const promoFinInput = document.getElementById('promo_fin');
        const imageFileInput = document.getElementById('image_file');
        const caracteristiquesInput = document.getElementById('caracteristiques');
        const isFeaturedInput = document.getElementById('is_featured');
        
        // Éléments d'aperçu
        const previewNom = document.getElementById('preview_nom');
        const previewDescription = document.getElementById('preview_description');
        const previewCategory = document.getElementById('preview_category');
        const previewPrixOriginal = document.getElementById('preview_prix_original');
        const previewPrixPromo = document.getElementById('preview_prix_promo');
        const previewPromoBadge = document.getElementById('preview_promo_badge');
        const previewDatePromo = document.getElementById('preview_date_promo');
        const previewImage = document.getElementById('preview_image');
        const imagePlaceholder = document.getElementById('image_placeholder');
        const previewCaracteristiques = document.getElementById('preview_caracteristiques').querySelector('ul');
        const fileNameDisplay = document.getElementById('file-name-display');
        const selectedFileName = document.getElementById('selected-file-name');
        
        // Afficher le nom du fichier image sélectionné
        imageFileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const fileName = this.files[0].name;
                selectedFileName.textContent = fileName;
                fileNameDisplay.classList.remove('hidden');
                
                // Prévisualiser l'image sélectionnée
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewImage.classList.remove('hidden');
                    imagePlaceholder.classList.add('hidden');
                };
                reader.readAsDataURL(this.files[0]);
            } else {
                fileNameDisplay.classList.add('hidden');
            }
        });
        
        // Fonction de mise à jour de l'aperçu
        function updatePreview() {
            // Mise à jour du nom et de la description
            previewNom.textContent = nomInput.value || 'Nom du produit';
            previewDescription.textContent = descriptionInput.value || 'Description du produit';
            
            // Mise à jour de la catégorie
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            previewCategory.textContent = selectedOption ? selectedOption.textContent : 'Catégorie';
            
            // Mise à jour des prix
            const prixOriginal = parseFloat(prixOriginalInput.value) || 0;
            const prixPromo = parseFloat(prixPromoInput.value) || 0;
            
            // Formater les prix avec des espaces comme séparateurs de milliers
            const formatterPrix = (prix) => {
                return prix.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ") + " F CFA";
            };
            
            previewPrixOriginal.textContent = formatterPrix(prixOriginal);
            
            // Gestion de la promotion
            if (prixPromo && prixPromo < prixOriginal) {
                previewPrixPromo.textContent = formatterPrix(prixPromo);
                previewPrixPromo.classList.remove('hidden');
                previewPromoBadge.classList.remove('hidden');
                previewPrixOriginal.classList.add('line-through', 'text-gray-500', 'dark:text-gray-400', 'text-base');
                previewPrixOriginal.classList.remove('text-xl', 'font-bold', 'text-gray-800', 'dark:text-white');
                
                // Date de fin de promotion
                if (promoFinInput.value) {
                    const date = new Date(promoFinInput.value);
                    const formattedDate = date.toLocaleDateString('fr-FR');
                    previewDatePromo.textContent = `Promotion jusqu'au ${formattedDate}`;
                    previewDatePromo.classList.remove('hidden');
                } else {
                    previewDatePromo.classList.add('hidden');
                }
            } else {
                previewPrixPromo.classList.add('hidden');
                previewPromoBadge.classList.add('hidden');
                previewDatePromo.classList.add('hidden');
                previewPrixOriginal.classList.remove('line-through', 'text-gray-500', 'dark:text-gray-400', 'text-base');
                previewPrixOriginal.classList.add('text-xl', 'font-bold', 'text-gray-800', 'dark:text-white');
            }
            
            // Mise à jour des caractéristiques
            if (caracteristiquesInput.value) {
                // Diviser les caractéristiques par ligne
                const caracteristiques = caracteristiquesInput.value.split('\n').filter(item => item.trim() !== '');
                
                // Vider la liste
                previewCaracteristiques.innerHTML = '';
                
                // Ajouter chaque caractéristique à la liste
                caracteristiques.forEach(carac => {
                    const li = document.createElement('li');
                    li.textContent = carac;
                    li.classList.add('text-gray-600', 'dark:text-gray-400');
                    previewCaracteristiques.appendChild(li);
                });
            } else {
                previewCaracteristiques.innerHTML = '<li class="text-gray-600 dark:text-gray-400">Caractéristiques du produit</li>';
            }
        }
        
        // Événements pour mettre à jour l'aperçu en temps réel
        nomInput.addEventListener('input', updatePreview);
        descriptionInput.addEventListener('input', updatePreview);
        categorySelect.addEventListener('change', updatePreview);
        prixOriginalInput.addEventListener('input', updatePreview);
        prixPromoInput.addEventListener('input', updatePreview);
        promoFinInput.addEventListener('input', updatePreview);
        caracteristiquesInput.addEventListener('input', updatePreview);
        isFeaturedInput.addEventListener('change', updatePreview);
        
        // Vérification de l'image en cas d'erreur
        previewImage.addEventListener('error', function() {
            this.classList.add('hidden');
            imagePlaceholder.classList.remove('hidden');
        });
        
        // Mise à jour initiale de l'aperçu
        updatePreview();
        
        // Animation des images au clic pour agrandir
        const productImage = document.querySelector('.image-preview-anim');
        if (productImage) {
            productImage.addEventListener('click', function() {
                // Code pour agrandir l'image ou afficher une lightbox
                alert('Fonctionnalité d\'agrandissement d\'image à implémenter');
            });
        }
        
        // Menu hamburger - Configuration
        const hamburgerButton = document.getElementById('hamburger-button');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        
        // Fonction pour ouvrir/fermer le menu
        function toggleMenu() {
            const isOpen = sidebar.classList.contains('translate-x-0');
            
            if (isOpen) {
                // Fermer le menu
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                sidebarOverlay.classList.remove('opacity-50');
                sidebarOverlay.classList.add('opacity-0');
                sidebarOverlay.classList.add('pointer-events-none');
                hamburgerButton.innerHTML = '<i class="fas fa-bars"></i>';
            } else {
                // Ouvrir le menu
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                sidebarOverlay.classList.remove('opacity-0');
                sidebarOverlay.classList.remove('pointer-events-none');
                sidebarOverlay.classList.add('opacity-50');
                hamburgerButton.innerHTML = '<i class="fas fa-times"></i>';
            }
        }
        
        // Événement de clic sur le bouton hamburger
        hamburgerButton.addEventListener('click', toggleMenu);
        
        // Fermer le menu quand on clique sur l'overlay
        sidebarOverlay.addEventListener('click', toggleMenu);
        
        // Fermer le menu quand on clique sur un lien du menu (pour mobile)
        const menuLinks = sidebar.querySelectorAll('a');
        menuLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 768) { // Only on mobile
                    toggleMenu();
                }
            });
        });
        
        // Ajuster le menu lors du redimensionnement de la fenêtre
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768) {
                // Sur les écrans moyens et grands, s'assurer que le menu est visible
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                sidebarOverlay.classList.remove('opacity-50');
                sidebarOverlay.classList.add('opacity-0');
                sidebarOverlay.classList.add('pointer-events-none');
            } else if (!sidebar.classList.contains('translate-x-0')) {
                // Sur mobile, s'assurer que le menu est caché par défaut
                sidebar.classList.add('-translate-x-full');
                sidebar.classList.remove('translate-x-0');
            }
        });
        
        // Animation des sections au survol
        const formSections = document.querySelectorAll('.form-section');
        formSections.forEach(section => {
            section.addEventListener('mouseenter', function() {
                this.classList.add('bg-opacity-30');
            });
            
            section.addEventListener('mouseleave', function() {
                this.classList.remove('bg-opacity-30');
            });
        });
    });
    </script>
</body>
</html>