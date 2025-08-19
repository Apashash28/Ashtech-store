<?php
// Afficher les erreurs pour faciliter le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Vérifier si l'utilisateur est déjà connecté
// Si l'administrateur est déjà connecté, rediriger vers le tableau de bord
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] == true) {
    // On redirige vers le dashboard si l'utilisateur est déjà connecté
    header('Location: dashboard.php');  // Change ici si le chemin est différent
    exit(); // Ne pas oublier le exit() après la redirection pour arrêter l'exécution du script
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Informations de connexion sécurisées (à remplacer par des valeurs sécurisées)
    $admin_username = 'Yannick';
    $admin_password = 'Yannick2009@##'; // Remplacer par un mot de passe sécurisé

    // Vérifier si le nom d'utilisateur et le mot de passe sont corrects
    if ($_POST['username'] == $admin_username && $_POST['password'] == $admin_password) {
        // Si l'authentification réussie, créer une session et rediriger vers le tableau de bord
        $_SESSION['admin_logged_in'] = true;
        header('Location: dashboard.php');  // Change ici si le chemin est différent
        exit();
    } else {
        // Si les identifiants sont incorrects, afficher un message d'erreur
        $error = 'Nom d\'utilisateur ou mot de passe incorrect.';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.5/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Favicon de base (PNG) -->
    <link rel="icon" type="image/png" href="img.png">
    
    <!-- Pour les appareils Apple (iPhone, iPad) -->
    <link rel="apple-touch-icon" href="img.png">
    
    <!-- Pour éviter les problèmes de cache -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    
    <style>
        :root {
            --primary-color: #5D5CDE;
            --primary-light: #7e7df1;
            --primary-dark: #4a49b8;
            --secondary-color: #FC5A5A;
            --accent-color: #F8C630;
            --text-color: #2D3748;
            --text-light: #718096;
            --background-color: #F7FAFC;
            --card-bg: rgba(255, 255, 255, 0.85);
            --shadow-color: rgba(0, 0, 0, 0.1);
            --input-bg: rgba(247, 250, 252, 0.8);
            --success-color: #48BB78;
        }
        
        .dark {
            --primary-color: #6D6CEE;
            --primary-light: #8a8af3;
            --primary-dark: #5150c7;
            --secondary-color: #FC5A5A;
            --accent-color: #F8C630;
            --text-color: #F7FAFC;
            --text-light: #CBD5E0;
            --background-color: #171923;
            --card-bg: rgba(26, 32, 44, 0.85);
            --shadow-color: rgba(0, 0, 0, 0.25);
            --input-bg: rgba(45, 55, 72, 0.8);
            --success-color: #48BB78;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Background with animated shapes */
        .bg-shapes {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
            overflow: hidden;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(40px);
            opacity: 0.6;
            animation: float 15s infinite ease-in-out;
        }
        
        .shape-1 {
            background: var(--primary-light);
            width: 300px;
            height: 300px;
            top: -100px;
            right: -80px;
            animation-delay: 0s;
        }
        
        .shape-2 {
            background: var(--secondary-color);
            width: 250px;
            height: 250px;
            bottom: -70px;
            left: -100px;
            animation-delay: -5s;
        }
        
        .shape-3 {
            background: var(--accent-color);
            width: 200px;
            height: 200px;
            bottom: 40%;
            right: 10%;
            animation-delay: -10s;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) scale(1);
            }
            50% {
                transform: translateY(-20px) scale(1.05);
            }
        }
        
        /* Glassmorphism login card */
        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
            position: relative;
            z-index: 10;
            perspective: 1000px;
        }
        
        .login-card {
            background-color: var(--card-bg);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 
                0 10px 25px -5px var(--shadow-color),
                0 10px 10px -5px var(--shadow-color),
                0 0 0 1px rgba(255, 255, 255, 0.1) inset;
            transition: transform 0.6s cubic-bezier(0.34, 1.56, 0.64, 1), 
                        box-shadow 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            transform-style: preserve-3d;
            animation: cardAppear 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            opacity: 0;
            transform: translateY(30px);
        }
        
        @keyframes cardAppear {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 20px 30px -10px var(--shadow-color),
                0 15px 15px -5px var(--shadow-color),
                0 0 0 1px rgba(255, 255, 255, 0.15) inset;
        }
        
        /* Logo styling */
        .logo-container {
            position: relative;
            width: 85px;
            height: 85px;
            margin: 0 auto 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logo-bg {
            position: absolute;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border-radius: 18px;
            transform: rotate(0deg);
            transition: transform 0.5s ease;
            box-shadow: 
                0 10px 20px rgba(93, 92, 222, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.15) inset;
        }
        
        .logo-container:hover .logo-bg {
            transform: rotate(45deg);
        }
        
        .logo-text {
            color: white;
            font-size: 36px;
            font-weight: 700;
            z-index: 1;
            transform: rotate(0deg);
            transition: transform 0.5s ease 0.1s;
        }
        
        .logo-container:hover .logo-text {
            transform: rotate(-45deg);
        }
        
        .logo-shine {
            position: absolute;
            top: -20%;
            left: -20%;
            width: 140%;
            height: 140%;
            background: radial-gradient(circle at center, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0) 70%);
            opacity: 0;
            transition: opacity 0.5s ease;
            pointer-events: none;
            z-index: 2;
        }
        
        .logo-container:hover .logo-shine {
            opacity: 0.3;
            animation: shineSweep 1.5s ease-in-out;
        }
        
        @keyframes shineSweep {
            0% {
                transform: scale(0) translateY(0%) translateX(0%);
                opacity: 0;
            }
            50% {
                opacity: 0.3;
            }
            100% {
                transform: scale(1) translateY(-20%) translateX(20%);
                opacity: 0;
            }
        }
        
        /* Form styling */
        .form-title {
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-align: center;
            background: linear-gradient(120deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
        }
        
        .form-subtitle {
            text-align: center;
            color: var(--text-light);
            margin-bottom: 2rem;
            font-weight: 400;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .input-label {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 0.875rem;
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 0 0.25rem;
        }
        
        .input-field {
            width: 100%;
            padding: 1rem;
            font-size: 1rem;
            background-color: var(--input-bg);
            color: var(--text-color);
            border: 2px solid transparent;
            border-radius: 12px;
            outline: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .input-field:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(93, 92, 222, 0.15);
        }
        
        .input-field:focus + .input-label,
        .input-field:not(:placeholder-shown) + .input-label {
            top: 0;
            left: 0.75rem;
            transform: translateY(-50%) scale(0.8);
            background-color: var(--card-bg);
        }
        
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            z-index: 10;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
        }
        
        /* Button styling */
        .btn-login {
            width: 100%;
            padding: 1rem;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 15px -3px rgba(93, 92, 222, 0.25);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 20px -5px rgba(93, 92, 222, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 5px 10px -3px rgba(93, 92, 222, 0.25);
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        /* Loading spinner */
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
            margin-right: 0.5rem;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Error message */
        .error-message {
            background-color: rgba(252, 90, 90, 0.1);
            border-left: 4px solid var(--secondary-color);
            color: var(--secondary-color);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            animation: shake 0.8s cubic-bezier(.36,.07,.19,.97) both, fadeIn 0.3s ease forwards;
            transform: translateZ(0);
            position: relative;
            opacity: 0;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            color: var(--text-light);
            font-size: 0.75rem;
        }
        
        /* Dark mode toggle */
        .theme-toggle {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--card-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 100;
            border: none;
            transition: all 0.3s ease;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }
        
        /* Decorative elements */
        .decorative-dots {
            position: absolute;
            width: 120px;
            height: 120px;
            z-index: -1;
            opacity: 0.5;
        }
        
        .dots-top-right {
            top: 5%;
            right: 5%;
            animation: drift 20s infinite alternate ease-in-out;
        }
        
        .dots-bottom-left {
            bottom: 5%;
            left: 5%;
            animation: drift 15s infinite alternate-reverse ease-in-out;
        }
        
        @keyframes drift {
            0% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(10px, -10px) rotate(5deg); }
            100% { transform: translate(-10px, 10px) rotate(-5deg); }
        }

        /* Success ripple effect for button click */
        .ripple {
            position: absolute;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.4);
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }

        @keyframes ripple {
            to {
                transform: scale(2.5);
                opacity: 0;
            }
        }
        
        /* Responsive adjustments */
        @media (max-width: 640px) {
            .login-container {
                padding: 1rem;
            }
            
            .login-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    
    <div class="decorative-dots dots-top-right">
        <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="dots-pattern" width="10" height="10" patternUnits="userSpaceOnUse">
                    <circle fill="var(--primary-color)" cx="5" cy="5" r="1.5"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#dots-pattern)"/>
        </svg>
    </div>
    
    <div class="decorative-dots dots-bottom-left">
        <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="dots-pattern2" width="10" height="10" patternUnits="userSpaceOnUse">
                    <circle fill="var(--accent-color)" cx="5" cy="5" r="1.5"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#dots-pattern2)"/>
        </svg>
    </div>
    
    <button id="themeToggle" class="theme-toggle" aria-label="Changer de thème">
        <svg xmlns="http://www.w3.org/2000/svg" class="dark-icon w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        <svg xmlns="http://www.w3.org/2000/svg" class="light-icon w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
    </button>
    
    <div class="login-container">
        <div class="login-card">
            <div class="logo-container">
                <div class="logo-bg"></div>
                <span class="logo-text">A</span>
                <div class="logo-shine"></div>
            </div>
            
            <h1 class="form-title text-2xl md:text-3xl">Connexion Admin</h1>
            <p class="form-subtitle">Accédez à votre espace d'administration</p>
            
            <!-- Afficher l'erreur si elle existe -->
            <?php if (isset($error)) { echo "<div id='errorAlert' class='error-message'>$error</div>"; } ?>
            
            <!-- Formulaire de connexion -->
            <form id="loginForm" method="POST">
                <div class="input-group">
                    <input type="text" id="username" name="username" class="input-field" placeholder=" " required autocomplete="username">
                    <label for="username" class="input-label">Nom d'utilisateur</label>
                </div>
                
                <div class="input-group password-container">
                    <input type="password" id="password" name="password" class="input-field" placeholder=" " required autocomplete="current-password">
                    <label for="password" class="input-label">Mot de passe</label>
                    <button type="button" id="togglePassword" class="password-toggle" aria-label="Afficher/masquer le mot de passe">
                        <svg xmlns="http://www.w3.org/2000/svg" class="eye-open h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" class="eye-closed h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                    </button>
                </div>
                
                <button type="submit" id="loginButton" class="btn-login">
                    <div class="spinner" id="loadingSpinner"></div>
                    <span id="buttonText">Se connecter</span>
                </button>
            </form>
            
            <div class="login-footer">
                <p>© <?php echo date('Y'); ?> Panneau d'Administration. Tous droits réservés.</p>
            </div>
        </div>
    </div>

    <script>
        // Détection du mode sombre
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
            toggleThemeIcons();
        }
        
        // Écouteur pour les changements de préférence de thème du système
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
            if (event.matches) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            toggleThemeIcons();
        });
        
        // Toggle pour le mode sombre
        document.getElementById('themeToggle').addEventListener('click', function() {
            document.documentElement.classList.toggle('dark');
            toggleThemeIcons();
        });
        
        function toggleThemeIcons() {
            const isDark = document.documentElement.classList.contains('dark');
            document.querySelector('.dark-icon').classList.toggle('hidden', isDark);
            document.querySelector('.light-icon').classList.toggle('hidden', !isDark);
        }
        
        // Toggle pour afficher/masquer le mot de passe
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeOpen = document.querySelector('.eye-open');
            const eyeClosed = document.querySelector('.eye-closed');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
            }
        });
        
        // Effet de ripple pour le bouton
        document.getElementById('loginButton').addEventListener('click', function(e) {
            // Créer l'élément de ripple
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            this.appendChild(ripple);
            
            // Positionner le ripple là où l'utilisateur a cliqué
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = `${size}px`;
            ripple.style.left = `${e.clientX - rect.left - size/2}px`;
            ripple.style.top = `${e.clientY - rect.top - size/2}px`;
            
            // Nettoyer après l'animation
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
        
        // Animation de chargement lors de la soumission du formulaire
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            document.getElementById('loadingSpinner').style.display = 'block';
            document.getElementById('buttonText').style.marginLeft = '8px';
            
            // Désactiver le bouton pour éviter les soumissions multiples
            document.getElementById('loginButton').disabled = true;
        });
        
        // Fermer l'alerte d'erreur après 5 secondes
        const errorAlert = document.getElementById('errorAlert');
        if (errorAlert) {
            setTimeout(() => {
                errorAlert.style.opacity = '0';
                errorAlert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => {
                    errorAlert.style.display = 'none';
                }, 500);
            }, 5000);
        }
        
        // Animation interactive du logo au chargement
        window.addEventListener('load', function() {
            const logo = document.querySelector('.logo-bg');
            logo.style.transform = 'rotate(360deg)';
            setTimeout(() => {
                logo.style.transform = 'rotate(0deg)';
            }, 1000);
        });
        
        // Animation des champs de saisie lors du focus
        const inputs = document.querySelectorAll('.input-field');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-3px)';
                this.parentElement.style.transition = 'transform 0.3s ease';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>