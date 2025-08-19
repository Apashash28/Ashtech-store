<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure le fichier de configuration depuis le dossier 'includes'
require_once __DIR__ . '/../includes/config.php';  // Adapter le chemin ici

session_start();

// Vérifie si l'admin est connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

// Récupérer le nom d'utilisateur s'il existe
$username = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Administrateur';


// Message de notification
$notification = '';
$notificationType = '';

// Supprimer un produit si l'ID est passé dans l'URL
if (isset($_GET['id']) && isset($_GET['action']) && $_GET['action'] == 'delete') {
    $id = $_GET['id'];

    try {
        // Requête pour supprimer le produit
        $query = $pdo->prepare("DELETE FROM produits WHERE id = ?");
        $query->execute([$id]);
        
        $notification = "Le produit a été supprimé avec succès.";
        $notificationType = "success";
    } catch (PDOException $e) {
        $notification = "Erreur lors de la suppression : " . $e->getMessage();
        $notificationType = "error";
    }
}

// Récupérer les catégories pour le filtre
$categoriesQuery = $pdo->query("SELECT id, nom FROM categories ORDER BY nom");
$categories = $categoriesQuery->fetchAll(PDO::FETCH_ASSOC);

// Paramètres de filtrage et pagination
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';

// Construction de la requête avec filtres
$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(p.nom LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($categoryFilter)) {
    $whereClauses[] = "p.category_id = ?";
    $params[] = $categoryFilter;
}

$whereSQL = '';
if (!empty($whereClauses)) {
    $whereSQL = "WHERE " . implode(' AND ', $whereClauses);
}

// Compter le nombre total de produits (pour pagination)
$countSQL = "SELECT COUNT(*) FROM produits p $whereSQL";
$countStmt = $pdo->prepare($countSQL);
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Récupérer les produits avec filtre et pagination
$offset = ($currentPage - 1) * $perPage;

$sql = "SELECT p.*, c.nom AS nom_categorie
        FROM produits p
        LEFT JOIN categories c ON p.category_id = c.id
        $whereSQL
        ORDER BY p.date_ajout DESC
        LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des produits | Administration</title>
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
                        <a href="produits.php" class="flex items-center p-3.5 text-gray-800 dark:text-white bg-primary-50 dark:bg-primary-900/30 rounded-xl border-l-4 border-primary-500 font-medium group transition-all duration-200">
                            <div class="w-5 h-5 flex items-center justify-center text-primary-500 dark:text-primary-400 group-hover:scale-110 transition-transform">
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
                        <a href="modifier_produit.php" class="flex items-center p-3.5 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-800 dark:hover:text-white rounded-xl transition-colors group duration-200">
                            <div class="w-5 h-5 flex items-center justify-center text-gray-500 dark:text-gray-400 group-hover:text-primary-500 dark:group-hover:text-primary-400 group-hover:scale-110 transition-all">
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
                            <h1 class="text-xl font-bold text-gray-800 dark:text-white ml-8">Gestion des produits</h1>
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
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Gestion des produits</h1>
                            <p class="mt-1 text-gray-600 dark:text-gray-400">Consultez, modifiez ou supprimez des produits</p>
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
                    
                    <!-- Products Container -->
                    <div class="mt-6 fade-in fade-in-delay-1">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-card border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <!-- Actions et filtres -->
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                    <div class="flex-1">
                                        <form action="" method="GET" class="flex flex-col sm:flex-row gap-3">
                                            <div class="relative flex-1">
                                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Rechercher un produit..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-base bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500">
                                                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                            
                                            <div class="w-full sm:w-48">
                                                <select name="category" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-base bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500/50 focus:border-primary-500">
                                                    <option value="">Toutes les catégories</option>
                                                    <?php foreach ($categories as $cat): ?>
                                                        <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($cat['nom']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <?php if (!empty($search) || !empty($categoryFilter)): ?>
                                                <a href="produits.php" class="px-4 py-2 text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-center">
                                                    <i class="fas fa-times mr-1"></i> Réinitialiser
                                                </a>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                    
                                    <div>
                                        <a href="ajouter_produit.php" class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition-colors">
                                            <i class="fas fa-plus mr-2"></i> Ajouter un produit
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Vue pour grands écrans (tableau) et petits écrans (cartes) -->
                            <div>
                                <?php if (count($produits) > 0): ?>
                                    <!-- Vue tableau pour desktop -->
                                    <div class="hidden lg:block overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead class="bg-gray-50 dark:bg-gray-700">
                                                <tr>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Image</th>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Produit</th>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Catégorie</th>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prix</th>
                                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                <?php foreach ($produits as $produit): ?>
                                                    <?php 
                                                    // Vérifier si la promotion est active
                                                    $promoActive = false;
                                                    if (!empty($produit['prix_promo']) && $produit['prix_promo'] < $produit['prix_original']) {
                                                        if (!empty($produit['promo_fin'])) {
                                                            $dateFin = new DateTime($produit['promo_fin']);
                                                            $now = new DateTime();
                                                            $promoActive = $dateFin > $now;
                                                        } else {
                                                            $promoActive = true;
                                                        }
                                                    }
                                                    
                                                    // Formater la date d'ajout
                                                    $dateAjout = !empty($produit['date_ajout']) ? (new DateTime($produit['date_ajout']))->format('d/m/Y') : '-';
                                                    
                                                    // Formater la date de fin de promo
                                                    $dateFinPromo = !empty($produit['promo_fin']) ? (new DateTime($produit['promo_fin']))->format('d/m/Y') : '-';
                                                    ?>
                                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                                        <td class="px-4 py-3 whitespace-nowrap">
                                                            <div class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                                                <?php if (!empty($produit['image_url'])): ?>
                                                                    <img src="../<?= htmlspecialchars($produit['image_url']) ?>" alt="<?= htmlspecialchars($produit['nom']) ?>" class="w-full h-full object-cover">

                                                                <?php else: ?>
                                                                    <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                                                                        <i class="fas fa-image"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            <div>
                                                                <div class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($produit['nom']) ?></div>
                                                                <div class="text-xs text-gray-500 dark:text-gray-400 max-w-xs truncate"><?= htmlspecialchars($produit['description']) ?></div>
                                                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">ID: <?= $produit['id'] ?> • Ajouté le <?= $dateAjout ?></div>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap">
                                                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                                                <?= htmlspecialchars($produit['nom_categorie'] ?? 'Aucune') ?>
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap">
                                                            <div>
                                                                <?php if ($promoActive): ?>
                                                                    <div class="text-xs line-through text-gray-500 dark:text-gray-400"><?= number_format($produit['prix_original'], 0, ',', ' ') ?> F CFA</div>
                                                                    <div class="text-sm font-medium text-green-600 dark:text-green-400"><?= number_format($produit['prix_promo'], 0, ',', ' ') ?> F CFA</div>
                                                                <?php else: ?>
                                                                    <div class="text-sm font-medium text-gray-800 dark:text-white"><?= number_format($produit['prix_original'], 0, ',', ' ') ?> F CFA</div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap">
                                                            <?php if ($promoActive): ?>
                                                                <div class="flex items-center">
                                                                    <div class="h-2.5 w-2.5 rounded-full bg-green-500 mr-2"></div>
                                                                    <span class="text-sm text-gray-800 dark:text-white">En promotion</span>
                                                                </div>
                                                                <?php if (!empty($produit['promo_fin'])): ?>
                                                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">jusqu'au <?= $dateFinPromo ?></div>
                                                                <?php endif; ?>
                                                            <?php elseif (!empty($produit['prix_promo']) && $produit['prix_promo'] < $produit['prix_original'] && !empty($produit['promo_fin'])): ?>
                                                                <div class="flex items-center">
                                                                    <div class="h-2.5 w-2.5 rounded-full bg-red-500 mr-2"></div>
                                                                    <span class="text-sm text-gray-800 dark:text-white">Promo expirée</span>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="flex items-center">
                                                                    <div class="h-2.5 w-2.5 rounded-full bg-blue-500 mr-2"></div>
                                                                    <span class="text-sm text-gray-800 dark:text-white">Prix standard</span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap text-right">
                                                            <div class="flex justify-end space-x-2">
                                                                <a href="modifier_produit.php?id=<?= $produit['id'] ?>" class="p-2 bg-gray-100 dark:bg-gray-700 text-primary-500 hover:text-white hover:bg-primary-500 rounded-lg transition-colors" title="Modifier">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="?action=delete&id=<?= $produit['id'] ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')" class="p-2 bg-gray-100 dark:bg-gray-700 text-red-600 hover:text-white hover:bg-red-600 dark:text-red-500 dark:hover:bg-red-600 dark:hover:text-white rounded-lg transition-colors" title="Supprimer">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Vue carte pour mobile et tablette -->
<div class="grid grid-cols-2 gap-3 p-3 lg:hidden">
    <?php foreach ($produits as $produit): ?>
        <?php 
        // Vérifier si la promotion est active
        $promoActive = false;
        if (!empty($produit['prix_promo']) && $produit['prix_promo'] < $produit['prix_original']) {
            if (!empty($produit['promo_fin'])) {
                $dateFin = new DateTime($produit['promo_fin']);
                $now = new DateTime();
                $promoActive = $dateFin > $now;
            } else {
                $promoActive = true;
            }
        }
        
        // Formater la date d'ajout
        $dateAjout = !empty($produit['date_ajout']) ? (new DateTime($produit['date_ajout']))->format('d/m/Y') : '-';
        
        // Formater la date de fin de promo
        $dateFinPromo = !empty($produit['promo_fin']) ? (new DateTime($produit['promo_fin']))->format('d/m/Y') : '-';
        ?>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-card border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
            <!-- En-tête avec image -->
            <div class="relative h-32 bg-gray-100 dark:bg-gray-700">
                <?php if (!empty($produit['image_url'])): ?>
                    <img src="../<?= htmlspecialchars($produit['image_url']) ?>" alt="<?= htmlspecialchars($produit['nom']) ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500">
                        <i class="fas fa-image text-xl"></i>
                    </div>
                <?php endif; ?>

                <?php if ($promoActive): ?>
                    <div class="absolute top-0 right-0 bg-red-500 text-white text-xs font-semibold px-2 py-0.5 m-2 rounded-full">
                        Promotion
                    </div>
                <?php endif; ?>

                <div class="absolute bottom-0 left-0 w-full px-2 py-1 bg-gradient-to-t from-black/70 to-transparent">
                    <span class="inline-block px-1.5 py-0.5 text-xs rounded-full bg-white/20 text-white backdrop-blur-sm select-none">
                        <?= htmlspecialchars($produit['nom_categorie'] ?? 'Aucune') ?>
                    </span>
                </div>
            </div>

            <!-- Infos produit -->
            <div class="p-3">
                <div class="flex justify-between items-center">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white line-clamp-1 max-w-[75%]"><?= htmlspecialchars($produit['nom']) ?></h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-1 select-none">ID: <?= $produit['id'] ?></span>
                </div>

                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400 line-clamp-2"><?= htmlspecialchars($produit['description']) ?></p>

                <div class="mt-2 flex justify-between items-end">
                    <div>
                        <?php if ($promoActive): ?>
                            <div class="text-[11px] line-through text-gray-500 dark:text-gray-400"><?= number_format($produit['prix_original'], 0, ',', ' ') ?> F CFA</div>
                            <div class="text-sm font-semibold text-green-600 dark:text-green-400"><?= number_format($produit['prix_promo'], 0, ',', ' ') ?> F CFA</div>
                            <?php if (!empty($produit['promo_fin'])): ?>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">jusqu'au <?= $dateFinPromo ?></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-sm font-semibold text-gray-800 dark:text-white"><?= number_format($produit['prix_original'], 0, ',', ' ') ?> F CFA</div>
                        <?php endif; ?>
                    </div>

                    <div class="text-[10px] text-gray-500 dark:text-gray-400 ml-2 select-none">
                        Ajouté le <?= $dateAjout ?>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="border-t border-gray-200 dark:border-gray-700 p-2 flex justify-between items-center bg-gray-50 dark:bg-gray-800">
                <div>
                    <?php if ($promoActive): ?>
                        <div class="flex items-center text-[10px]">
                            <div class="h-2 w-2 rounded-full bg-green-500 mr-2"></div>
                            <span class="text-gray-600 dark:text-gray-400 select-none">En promotion</span>
                        </div>
                    <?php elseif (!empty($produit['prix_promo']) && $produit['prix_promo'] < $produit['prix_original'] && !empty($produit['promo_fin'])): ?>
                        <div class="flex items-center text-[10px]">
                            <div class="h-2 w-2 rounded-full bg-red-500 mr-2"></div>
                            <span class="text-gray-600 dark:text-gray-400 select-none">Promo expirée</span>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center text-[10px]">
                            <div class="h-2 w-2 rounded-full bg-blue-500 mr-2"></div>
                            <span class="text-gray-600 dark:text-gray-400 select-none">Prix standard</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flex space-x-1">
                    <a href="modifier_produit.php?id=<?= $produit['id'] ?>" class="p-1 bg-gray-200 dark:bg-gray-700 text-primary-500 hover:text-white hover:bg-primary-500 rounded-lg transition-colors" title="Modifier">
                        <i class="fas fa-edit text-sm"></i>
                    </a>
                    <a href="?action=delete&id=<?= $produit['id'] ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')" class="p-1 bg-gray-200 dark:bg-gray-700 text-red-600 hover:text-white hover:bg-red-600 dark:text-red-500 dark:hover:bg-red-600 dark:hover:text-white rounded-lg transition-colors" title="Supprimer">
                        <i class="fas fa-trash-alt text-sm"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

                                    <!-- Pagination -->
                                    <?php if ($totalPages > 1): ?>
                                        <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 px-4 py-3">
                                            <div class="flex-1 flex justify-between sm:hidden">
                                                <?php if ($currentPage > 1): ?>
                                                    <a href="?page=<?= $currentPage - 1 ?>&search=<?= urlencode($search) ?>&category=<?= $categoryFilter ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                        Précédent
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($currentPage < $totalPages): ?>
                                                    <a href="?page=<?= $currentPage + 1 ?>&search=<?= urlencode($search) ?>&category=<?= $categoryFilter ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                        Suivant
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                                <div>
                                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                                        Affichage de <span class="font-medium"><?= ($currentPage - 1) * $perPage + 1 ?></span> à 
                                                        <span class="font-medium"><?= min($currentPage * $perPage, $totalProducts) ?></span> sur 
                                                        <span class="font-medium"><?= $totalProducts ?></span> résultats
                                                    </p>
                                                </div>
                                                <div>
                                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                                        <?php if ($currentPage > 1): ?>
                                                            <a href="?page=<?= $currentPage - 1 ?>&search=<?= urlencode($search) ?>&category=<?= $categoryFilter ?>" 
                                                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                                <i class="fas fa-chevron-left"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php 
                                                        $startPage = max(1, $currentPage - 2);
                                                        $endPage = min($totalPages, $startPage + 4);
                                                        
                                                        for ($i = $startPage; $i <= $endPage; $i++): 
                                                        ?>
                                                            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= $categoryFilter ?>" 
                                                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 
                                                                    <?= $i === $currentPage ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-500 dark:text-primary-400 z-10 border-primary-500/30 dark:border-primary-500/50' : 'bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600' ?> text-sm font-medium">
                                                                <?= $i ?>
                                                            </a>
                                                        <?php endfor; ?>
                                                        
                                                        <?php if ($currentPage < $totalPages): ?>
                                                            <a href="?page=<?= $currentPage + 1 ?>&search=<?= urlencode($search) ?>&category=<?= $categoryFilter ?>" 
                                                               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-600">
                                                                <i class="fas fa-chevron-right"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </nav>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                <?php else: ?>
                                    <div class="text-center py-12">
                                        <div class="mb-4 text-gray-400 dark:text-gray-500">
                                            <i class="fas fa-box-open fa-4x"></i>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Aucun produit trouvé</h3>
                                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                            <?php if (!empty($search) || !empty($categoryFilter)): ?>
                                                Aucun produit ne correspond à vos critères de recherche.
                                                <a href="produits.php" class="text-primary-500 hover:underline">Réinitialiser les filtres</a>
                                            <?php else: ?>
                                                Commencez par ajouter un produit à votre catalogue.
                                            <?php endif; ?>
                                        </p>
                                        <div class="mt-6">
                                            <a href="ajouter_produit.php" class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition-colors">
                                                <i class="fas fa-plus mr-2"></i> Ajouter un produit
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
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
    document.addEventListener('DOMContentLoaded', function() {
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
    });
    </script>
</body>
</html>