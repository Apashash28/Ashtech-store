<?php
// Affichage des erreurs pour debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Vérifie si admin connecté
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

$username = $_SESSION['admin_username'] ?? 'Administrateur';

// Connexion à la base de données
$servername = "sql106.iceiy.com";
$db_username = "icei_39016282";
$db_password = "yVsf9qzAw1ag";
$dbname = "icei_39016282_digitek_empire";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Statuts valides pour les ventes réussies ---
$statuts_valides = ["payé", "paid", "success"];
 // adapter ici selon ta base

// Préparer la liste pour SQL
$statuts_sql = "'" . implode("','", $statuts_valides) . "'";

// --- Total produits ---
$sql = "SELECT COUNT(*) AS total_products FROM produits";
$result = $conn->query($sql);
$total_products = ($result) ? (int)$result->fetch_assoc()['total_products'] : 0;

// --- Produits mois précédent ---
$sql_prev_month = "
    SELECT COUNT(*) AS prev_month_products 
    FROM produits 
    WHERE date_ajout >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01') 
      AND date_ajout < DATE_FORMAT(CURDATE(), '%Y-%m-01')";
$result_prev_month = $conn->query($sql_prev_month);
$prev_month_products = ($result_prev_month) ? (int)$result_prev_month->fetch_assoc()['prev_month_products'] : 0;

// Calcul tendance produits
if ($prev_month_products > 0) {
    $trend_percentage = round((($total_products - $prev_month_products) / $prev_month_products) * 100);
    $trend_up = $trend_percentage >= 0;
    $trend_percentage = abs($trend_percentage);
} else {
    $trend_percentage = 0;
    $trend_up = true;
}

// --- Total ventes ---
$sql = "SELECT COUNT(*) AS total_ventes FROM ventes WHERE statut IN ($statuts_sql)";
$result = $conn->query($sql);
$total_ventes = ($result) ? (int)$result->fetch_assoc()['total_ventes'] : 0;

// --- Ventes mois précédent ---
$sql_prev_month_ventes = "
    SELECT COUNT(*) AS prev_month_ventes 
    FROM ventes 
    WHERE statut IN ($statuts_sql)
      AND created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01') 
      AND created_at < DATE_FORMAT(CURDATE(), '%Y-%m-01')";
$result_prev_month_ventes = $conn->query($sql_prev_month_ventes);
$prev_month_ventes = ($result_prev_month_ventes) ? (int)$result_prev_month_ventes->fetch_assoc()['prev_month_ventes'] : 0;

// Calcul tendance ventes
if ($prev_month_ventes > 0) {
    $trend_ventes_percentage = round((($total_ventes - $prev_month_ventes) / $prev_month_ventes) * 100);
    $trend_ventes_up = $trend_ventes_percentage >= 0;
    $trend_ventes_percentage = abs($trend_ventes_percentage);
} else {
    $trend_ventes_percentage = 0;
    $trend_ventes_up = true;
}

// --- Total revenus ---
$sql = "SELECT SUM(montant) AS total_revenu FROM ventes WHERE statut IN ($statuts_sql)";
$result = $conn->query($sql);
$montant = ($result) ? floatval($result->fetch_assoc()['total_revenu']) : 0;

// --- Revenus mois précédent ---
$sql_prev_month_revenu = "
    SELECT SUM(montant) AS prev_month_revenu 
    FROM ventes 
    WHERE statut IN ($statuts_sql)
      AND created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01') 
      AND created_at < DATE_FORMAT(CURDATE(), '%Y-%m-01')";
$result_prev_month_revenu = $conn->query($sql_prev_month_revenu);
$prev_month_revenu = ($result_prev_month_revenu) ? floatval($result_prev_month_revenu->fetch_assoc()['prev_month_revenu']) : 0;

// Calcul tendance revenus
if ($prev_month_revenu > 0) {
    $trend_revenu_percentage = round((($montant - $prev_month_revenu) / $prev_month_revenu) * 100);
    $trend_revenu_up = $trend_revenu_percentage >= 0;
    $trend_revenu_percentage = abs($trend_revenu_percentage);
} else {
    $trend_revenu_percentage = 0;
    $trend_revenu_up = true;
}

// --- Total clients uniques ---
$sql = "SELECT COUNT(DISTINCT email) AS total_clients FROM ventes WHERE statut IN ($statuts_sql)";
$result = $conn->query($sql);
$total_clients = ($result) ? (int)$result->fetch_assoc()['total_clients'] : 0;

// Objectif clients (pourcentage)
$objectif_clients = 200;
$pourcentage_objectif = ($objectif_clients > 0) ? min(100, ($total_clients / $objectif_clients) * 100) : 0;

// --- Fonction pour données graphique ---
function getChartData($conn, $period, $statuts_sql) {
    $data = [];
    $labels = [];

    if ($period === '7j' || $period === '7 jours') {
        // Données sur les 7 derniers jours
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as total 
                FROM ventes 
                WHERE statut IN ($statuts_sql) AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
                GROUP BY DATE(created_at) 
                ORDER BY date ASC";
        $result = $conn->query($sql);

        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('D', strtotime($date)); // ex: Mon, Tue, etc.
            $data[$date] = 0;
        }

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[$row['date']] = (int)$row['total'];
            }
        }
    } elseif ($period === '30j' || $period === '30 jours') {
        // Données par semaine sur 30 jours
        $sql = "SELECT YEARWEEK(created_at, 1) as yearweek, COUNT(*) as total 
                FROM ventes 
                WHERE statut IN ($statuts_sql) AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                GROUP BY yearweek 
                ORDER BY yearweek ASC";
        $result = $conn->query($sql);

        for ($i = 4; $i >= 0; $i--) {
            $week = date('oW', strtotime("-$i weeks")); // année + semaine ISO
            $labels[] = 'S' . substr($week, 4); // S + numéro de semaine
            $data[$week] = 0;
        }

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $data[$row['yearweek']] = (int)$row['total'];
            }
        }

        $tempLabels = [];
        $tempData = [];
        foreach ($data as $yearweek => $count) {
            $tempLabels[] = 'S' . substr($yearweek, 4);
            $tempData[] = $count;
        }
        $labels = $tempLabels;
        $data = $tempData;

    } else {
        // Données mois sur 12 mois (année)
        $sql = "SELECT MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as total 
                FROM ventes 
                WHERE statut IN ($statuts_sql) AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
                GROUP BY year, month 
                ORDER BY year ASC, month ASC";
        $result = $conn->query($sql);

        $currentMonth = (int)date('n');
        $currentYear = (int)date('Y');

        for ($i = 11; $i >= 0; $i--) {
            $month = $currentMonth - $i;
            $year = $currentYear;
            if ($month <= 0) {
                $month += 12;
                $year--;
            }
            $dateObj = DateTime::createFromFormat('!m', $month);
            $monthName = $dateObj->format('M');
            $labels[] = $monthName;
            $data["$year-$month"] = 0;
        }

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $key = $row['year'] . '-' . (int)$row['month'];
                $data[$key] = (int)$row['total'];
            }
        }

        $tempData = [];
        foreach ($labels as $monthName) {
            $tempData[] = array_shift($data);
        }
        $data = $tempData;
    }

    return [
        'labels' => $labels,
        'data' => is_array($data) ? array_values($data) : []
    ];
}

// Récupération des données pour graphiques
$chartData7j = getChartData($conn, '7j', $statuts_sql);
$chartData30j = getChartData($conn, '30j', $statuts_sql);
$chartDataAnnee = getChartData($conn, 'Année', $statuts_sql);

?>




<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord | Administration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Favicon de base (PNG) -->
    <link rel="icon" type="image/png" href="img.png">
    
    <!-- Pour les appareils Apple (iPhone, iPad) -->
    <link rel="apple-touch-icon" href="img.png">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Ajout de Chart.js pour les graphiques -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
        
        /* Progress bar animation */
        @keyframes growWidth {
            from { width: 0; }
            to { width: var(--final-width); }
        }
        
        .animate-progress-bar {
            animation: growWidth 1s ease-out forwards;
        }
        
        /* Pulse dot animation */
        .pulse-dot {
            position: relative;
        }
        
        .pulse-dot::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: inherit;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            opacity: 0.7;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                opacity: 0.7;
            }
            50% {
                transform: scale(1.5);
                opacity: 0;
            }
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
        
        .fade-in-delay-3 {
            animation-delay: 0.3s;
        }
        
        .fade-in-delay-4 {
            animation-delay: 0.4s;
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
        
        .bg-gradient-green {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
        }
        
        .bg-gradient-blue {
            background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%);
        }
        
        .bg-gradient-yellow {
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
        }
        
        .bg-gradient-purple {
            background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);
        }
        
        /* Card indicator bar */
        .card-indicator {
            position: absolute;
            left: 0;
            bottom: 0;
            height: 3px;
            width: 100%;
            background: rgba(93, 92, 222, 0.2);
            overflow: hidden;
        }
        
        .card-indicator::after {
            content: '';
            position: absolute;
            height: 100%;
            width: 40%;
            background: #5D5CDE;
            left: -40%;
            animation: slide 2s ease-in-out infinite;
        }
        
        @keyframes slide {
            0% { left: -40%; }
            100% { left: 100%; }
        }
        
        /* Live indicator dot */
        .live-indicator {
            position: relative;
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #10B981;
            border-radius: 50%;
        }
        
        .live-indicator::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background-color: #10B981;
            border-radius: 50%;
            animation: pulse 2s infinite;
            opacity: 0.8;
        }
        
        /* Table hover effect */
        .hover-row {
            transition: all 0.2s ease;
        }
        
        .hover-row:hover {
            background-color: rgba(93, 92, 222, 0.05);
        }
        
        .dark .hover-row:hover {
            background-color: rgba(93, 92, 222, 0.1);
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
                                <span class="live-indicator mr-2"></span>
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
                        <a href="dashboard.php" class="flex items-center p-3.5 text-gray-800 dark:text-white bg-primary-50 dark:bg-primary-900/30 rounded-xl border-l-4 border-primary-500 font-medium group transition-all duration-200">
                            <div class="w-5 h-5 flex items-center justify-center text-primary-500 dark:text-primary-400 group-hover:scale-110 transition-transform">
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
                            <h1 class="text-xl font-bold text-gray-800 dark:text-white ml-8">Dashboard</h1>
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
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Tableau de bord</h1>
                            <p class="mt-1 text-gray-600 dark:text-gray-400 flex items-center">
                                <span>Gérez votre site depuis cette interface</span>
                                <span class="inline-flex items-center ml-3 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    <span class="live-indicator mr-1.5"></span>
                                    En ligne
                                </span>
                            </p>
                        </div>
                        <div class="mt-4 md:mt-0">
                            <div id="refresh-button" class="inline-flex items-center px-4 py-2 bg-gradient-primary text-white rounded-lg shadow transition-all duration-200 ease-in-out cursor-pointer hover:shadow-glow">
                                <i class="fas fa-sync-alt mr-2"></i>
                                <span>Rafraîchir</span>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Overview -->
                    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- Produits -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-card hover:shadow-card-hover border border-gray-100 dark:border-gray-700 p-6 card-stats relative overflow-hidden fade-in fade-in-delay-1">
                            <div class="flex items-center">
                                <div class="icon-wrapper p-3 rounded-xl bg-gradient-primary text-white">
                                    <i class="fas fa-box text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Produits</h3>
                                    <div class="flex items-baseline">
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white" id="total-produits">
                                            <?php echo $total_products; ?>
                                        </p>
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="produits.php" class="text-sm text-primary-600 dark:text-primary-400 inline-flex items-center hover:underline group">
                                    Voir tous les produits
                                    <i class="fas fa-arrow-right ml-1 text-xs transition-transform duration-200 group-hover:translate-x-1"></i>
                                </a>
                            </div>
                            
                            <!-- Mini chart dynamique généré en fonction des données réelles -->
                            <div class="absolute bottom-0 right-0 w-24 h-16 opacity-20">
                                <svg viewBox="0 0 100 50" preserveAspectRatio="none" class="w-full h-full">
                                    <path d="M0,50 L5,45 L10,48 L15,30 L20,25 L25,35 L30,30 L35,25 L40,20 L45,15 L50,20 L55,15 L60,10 L65,15 L70,5 L75,10 L80,5 L85,0 L90,5 L95,10 L100,5" 
                                          stroke="currentColor" 
                                          stroke-width="2" 
                                          fill="none" 
                                          class="text-primary-500">
                                    </path>
                                </svg>
                            </div>
                        </div>

                        
                             <!-- Utilisateurs -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-card hover:shadow-card-hover border border-gray-100 dark:border-gray-700 p-6 card-stats relative overflow-hidden fade-in fade-in-delay-2">
                            <div class="flex items-center">
                                <div class="icon-wrapper p-3 rounded-xl bg-gradient-green text-white">
                                    <i class="fas fa-users text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">DIGITEK EMPIRE</h3>
                                    <div class="flex items-baseline">
                                        
                                        
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="#" class="text-sm text-primary-600 dark:text-primary-400 inline-flex items-center hover:underline group">
                                   
                                    
                                </a>
                            </div>
                            <!-- Mini chart -->
                            <div class="absolute bottom-0 right-0 w-24 h-16 opacity-20">
                                <svg viewBox="0 0 100 50" preserveAspectRatio="none" class="w-full h-full">
                                    <path d="M0,50 L20,50 L40,50 L60,50 L80,50 L100,50" 
                                          stroke="currentColor" 
                                          stroke-width="2" 
                                          fill="none" 
                                          class="text-green-500">
                                    </path>
                                </svg>
                            </div>
                        </div>

                        <!-- Ventes -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-card hover:shadow-card-hover border border-gray-100 dark:border-gray-700 p-6 card-stats relative overflow-hidden fade-in fade-in-delay-3">
                            <div class="flex items-center">
                                <div class="icon-wrapper p-3 rounded-xl bg-gradient-blue text-white">
                                    <i class="fas fa-shopping-cart text-xl"></i>
                                </div>
                                <div class="ml-4">
                                   <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Ventes</h3>
<div class="flex items-baseline">
    <p class="text-2xl font-bold text-gray-900 dark:text-white" id="total-ventes">
        <?= $total_ventes ?>
    </p>
    <span class="ml-2 text-xs font-medium <?= $trend_ventes_up ? 'text-green-500' : 'text-red-500' ?>">
        <i class="fas fa-<?= $trend_ventes_up ? 'arrow-up' : 'arrow-down' ?>"></i> 
        <?php echo $trend_ventes_percentage; ?>%
    </span>
</div>

                                </div>
                            </div>
                           
                            
                            <!-- Mini chart -->
                            <div class="absolute bottom-0 right-0 w-24 h-16 opacity-20">
                                <svg viewBox="0 0 100 50" preserveAspectRatio="none" class="w-full h-full">
                                    <path d="M0,45 L10,40 L20,45 L30,35 L40,30 L50,20 L60,15 L70,25 L80,15 L90,10 L100,5" 
                                          stroke="currentColor" 
                                          stroke-width="2" 
                                          fill="none" 
                                          class="text-blue-500">
                                    </path>
                                </svg>
                            </div>
                        </div>

                        <!-- Revenus -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-card hover:shadow-card-hover border border-gray-100 dark:border-gray-700 p-6 card-stats relative overflow-hidden fade-in fade-in-delay-4">
                            <div class="flex items-center">
                                <div class="icon-wrapper p-3 rounded-xl bg-gradient-yellow text-white">
                                    <i class="fas fa-coins text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400">Revenus</h3>
                                    <div class="flex items-baseline">
                                        <p class="text-2xl font-bold text-gray-900 dark:text-white" id="total-revenus">
                                            <?php echo number_format($montant, 0, ',', ' ') . ' F CFA'; ?>
                                        </p>
                                        <span class="ml-2 text-xs font-medium <?php echo $trend_revenu_up ? 'text-green-500' : 'text-red-500'; ?>">
                                            <i class="fas fa-<?php echo $trend_revenu_up ? 'arrow-up' : 'arrow-down'; ?>"></i> 
                                            <?php echo $trend_revenu_percentage; ?>%
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            
                            <!-- Mini chart -->
                            <div class="absolute bottom-0 right-0 w-24 h-16 opacity-20">
                                <svg viewBox="0 0 100 50" preserveAspectRatio="none" class="w-full h-full">
                                    <path d="M0,40 L10,35 L20,30 L30,35 L40,25 L50,20 L60,15 L70,10 L80,5 L90,10 L100,0" 
                                          stroke="currentColor" 
                                          stroke-width="2" 
                                          fill="none" 
                                          class="text-yellow-500">
                                    </path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Graphique d'aperçu des ventes -->
                    <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-card border border-gray-100 dark:border-gray-700 p-6 fade-in">
                            <div class="flex justify-between items-center mb-6">
                                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Aperçu des ventes</h2>
                                <div class="flex space-x-2">
                                    <button class="chart-period-btn active px-3 py-1 text-sm rounded-lg bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400" data-period="7j">7 jours</button>
                                    <button class="chart-period-btn px-3 py-1 text-sm rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" data-period="30j">30 jours</button>
                                    <button class="chart-period-btn px-3 py-1 text-sm rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" data-period="annee">Année</button>
                                </div>
                            </div>
                            <div class="h-64">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>
                        
                        <!-- Clients -->
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-card border border-gray-100 dark:border-gray-700 p-6 fade-in">
                            <div class="flex items-center mb-6">
                                <div class="p-3 rounded-xl bg-gradient-purple text-white mr-4">
                                    <i class="fas fa-user-check text-xl"></i>
                                </div>
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Clients</h2>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Total des clients uniques</p>
                                </div>
                            </div>
                            
                            <div class="flex flex-col items-center mb-6">
                                <div class="text-4xl font-bold text-gray-800 dark:text-white mb-2" id="total-clients">
                                    <?php echo $total_clients; ?>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 mb-4">
                                    <div class="bg-purple-600 h-2.5 rounded-full animate-progress-bar" style="--final-width: <?php echo $pourcentage_objectif; ?>%"></div>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo $total_clients; ?> sur objectif de <?php echo $objectif_clients; ?> clients
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Tableau des dernières ventes -->
                    <div class="mt-10 fade-in">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-white flex items-center">
                                Dernières ventes
                                <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                                    Récent
                                </span>
                            </h2>
                            
                        </div>
                        <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-xl shadow-card border border-gray-100 dark:border-gray-700 relative">
                            <div class="card-indicator"></div>
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                    <tr>
                                        <th class="px-6 py-3 text-left font-medium">ID</th>
                                        <th class="px-6 py-3 text-left font-medium">Nom</th>
                                        <th class="px-6 py-3 text-left font-medium">Numéro</th>
                                        <th class="px-6 py-3 text-left font-medium">Email</th>
                                        <th class="px-6 py-3 text-left font-medium">Montant</th>
                                        <th class="px-6 py-3 text-left font-medium">Statut</th>
                                        <th class="px-6 py-3 text-left font-medium">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-800 dark:text-gray-100">
                                    <?php
                                    $sql = "SELECT id, nom_client, numero_client, email, montant, statut, created_at FROM ventes ORDER BY created_at DESC LIMIT 10";
                                    $result = $conn->query($sql);
                                    if ($result && $result->num_rows > 0):
                                        while ($row = $result->fetch_assoc()):
                                    ?>
                                    <tr class="hover-row">
                                        <td class="px-6 py-4"><?php echo $row['id']; ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['nom_client']); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['numero_client']); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td class="px-6 py-4 font-medium"><?php echo number_format($row['montant'], 0, ',', ' ') . ' F CFA'; ?></td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                <?php 
                                                    echo $row['statut'] === 'success' ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' : 
                                                        ($row['statut'] === 'pending' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300' : 
                                                        'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300');
                                                ?>">
                                                <span class="w-1.5 h-1.5 rounded-full 
                                                    <?php 
                                                        echo $row['statut'] === 'success' ? 'bg-green-600 dark:bg-green-400' : 
                                                            ($row['statut'] === 'pending' ? 'bg-yellow-600 dark:bg-yellow-400' : 
                                                            'bg-red-600 dark:bg-red-400');
                                                    ?> mr-1.5"></span>
                                                <?php echo ucfirst($row['statut']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-gray-500 dark:text-gray-400"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                    </tr>
                                    <?php endwhile; else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-shopping-cart text-2xl mb-2 text-gray-300 dark:text-gray-600"></i>
                                                <p>Aucune vente récente</p>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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

    <!-- Script pour le menu hamburger et les graphiques -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configuration du menu hamburger
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
            
            // Animation des cartes de statistiques
            const cards = document.querySelectorAll('.card-stats');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.classList.add('shadow-card-hover');
                });
                card.addEventListener('mouseleave', function() {
                    this.classList.remove('shadow-card-hover');
                });
            });
            
            // Données pour le graphique - initialisées avec des données PHP réelles
            const realSalesData = {
                '7j': {
                    labels: <?php echo json_encode($chartData7j['labels']); ?>,
                    data: <?php echo json_encode($chartData7j['data']); ?>
                },
                '30j': {
                    labels: <?php echo json_encode($chartData30j['labels']); ?>,
                    data: <?php echo json_encode($chartData30j['data']); ?>
                },
                'annee': {
                    labels: <?php echo json_encode($chartDataAnnee['labels']); ?>,
                    data: <?php echo json_encode($chartDataAnnee['data']); ?>
                }
            };
            
            // Boutons de période du graphique
            const chartPeriodBtns = document.querySelectorAll('.chart-period-btn');
            chartPeriodBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Enlever la classe 'active' de tous les boutons
                    chartPeriodBtns.forEach(b => {
                        b.classList.remove('active', 'bg-primary-100', 'dark:bg-primary-900/50', 'text-primary-600', 'dark:text-primary-400');
                        b.classList.add('text-gray-500', 'dark:text-gray-400');
                    });
                    
                    // Ajouter la classe 'active' au bouton cliqué
                    this.classList.add('active', 'bg-primary-100', 'dark:bg-primary-900/50', 'text-primary-600', 'dark:text-primary-400');
                    this.classList.remove('text-gray-500', 'dark:text-gray-400');
                    
                    // Mettre à jour le graphique avec les données réelles
                    updateChartData(this.dataset.period);
                });
            });

            // Configuration du graphique de ventes
            const isDarkMode = document.documentElement.classList.contains('dark');
            const textColor = isDarkMode ? '#9ca3af' : '#6b7280';
            const borderColor = isDarkMode ? '#374151' : '#e5e7eb';
            
            // Créer le graphique initial avec des données réelles
            const salesChartCtx = document.getElementById('salesChart').getContext('2d');
            let salesChart = new Chart(salesChartCtx, {
                type: 'line',
                data: {
                    labels: realSalesData['7j'].labels,
                    datasets: [{
                        label: 'Ventes',
                        data: realSalesData['7j'].data,
                        backgroundColor: 'rgba(93, 92, 222, 0.05)',
                        borderColor: '#5D5CDE',
                        borderWidth: 2,
                        tension: 0.3,
                        pointBackgroundColor: '#5D5CDE',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: isDarkMode ? '#374151' : '#fff',
                            titleColor: isDarkMode ? '#fff' : '#000',
                            bodyColor: isDarkMode ? '#e5e7eb' : '#6b7280',
                            borderColor: isDarkMode ? '#4b5563' : '#e5e7eb',
                            borderWidth: 1,
                            padding: 10,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return 'Ventes: ' + context.raw;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                                drawBorder: false
                            },
                            ticks: {
                                color: textColor,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        y: {
                            grid: {
                                color: borderColor,
                                drawBorder: false
                            },
                            ticks: {
                                color: textColor,
                                font: {
                                    size: 11
                                },
                                stepSize: 5,
                                // Assurez-vous que le graphique commence à 0
                                beginAtZero: true
                            }
                        }
                    }
                }
            });
            
            // Fonction pour mettre à jour les données du graphique
            function updateChartData(period) {
                // Utilisation des données réelles selon la période
                salesChart.data.labels = realSalesData[period].labels;
                salesChart.data.datasets[0].data = realSalesData[period].data;
                
                // Obtenir la valeur max pour ajuster l'échelle
                const maxValue = Math.max(...realSalesData[period].data);
                
                // Ajuster l'échelle Y en fonction des données
                if (maxValue <= 10) {
                    salesChart.options.scales.y.ticks.stepSize = 1;
                } else if (maxValue <= 50) {
                    salesChart.options.scales.y.ticks.stepSize = 5;
                } else if (maxValue <= 100) {
                    salesChart.options.scales.y.ticks.stepSize = 10;
                } else {
                    salesChart.options.scales.y.ticks.stepSize = Math.ceil(maxValue / 10);
                }
                
                salesChart.update();
            }
            
            // Écouter les changements de mode sombre pour mettre à jour le graphique
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
                const isDark = event.matches;
                
                // Mettre à jour les couleurs du graphique
                salesChart.options.plugins.tooltip.backgroundColor = isDark ? '#374151' : '#fff';
                salesChart.options.plugins.tooltip.titleColor = isDark ? '#fff' : '#000';
                salesChart.options.plugins.tooltip.bodyColor = isDark ? '#e5e7eb' : '#6b7280';
                salesChart.options.plugins.tooltip.borderColor = isDark ? '#4b5563' : '#e5e7eb';
                
                salesChart.options.scales.x.ticks.color = isDark ? '#9ca3af' : '#6b7280';
                salesChart.options.scales.y.ticks.color = isDark ? '#9ca3af' : '#6b7280';
                salesChart.options.scales.y.grid.color = isDark ? '#374151' : '#e5e7eb';
                
                salesChart.update();
            });
            
            // Gestion du bouton de rafraîchissement
            document.getElementById('refresh-button').addEventListener('click', function() {
                // Ajouter une classe pour l'animation
                this.classList.add('animate-pulse');
                
                // Recharger la page après une courte animation
                setTimeout(() => {
                    location.reload();
                }, 300);
            });
        });
    </script>
</body>
</html>