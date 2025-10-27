<?php 
require_once __DIR__ . '/includes/session.php';
require_guest(); 
?>
<!DOCTYPE html>
<html lang="<?= get_language() ?>" dir="<?= get_direction() ?>">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Streamify Pro - Sign In</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <script src="assets/js/i18n.js"></script>
    <style>
        body.login-page {
            min-height: 100vh;
            background: url('assets/img/background.png') center/cover no-repeat fixed;
            position: relative;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }

        /* Dark overlay */
        body.login-page::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(10, 14, 20, 0.92) 0%, rgba(26, 29, 36, 0.88) 50%, rgba(35, 39, 47, 0.9) 100%);
            z-index: 0;
        }

        /* Animated background particles */
        body.login-page::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background:
                radial-gradient(circle at 20% 30%, rgba(99, 102, 241, 0.06) 0%, transparent 40%),
                radial-gradient(circle at 80% 70%, rgba(139, 92, 246, 0.06) 0%, transparent 40%),
                radial-gradient(circle at 50% 50%, rgba(99, 102, 241, 0.04) 0%, transparent 50%);
            animation: floatParticles 25s ease-in-out infinite;
            z-index: 0;
        }

        @keyframes floatParticles {

            0%,
            100% {
                transform: translate(0, 0) rotate(0deg);
            }

            33% {
                transform: translate(30px, -30px) rotate(120deg);
            }

            66% {
                transform: translate(-20px, 20px) rotate(240deg);
            }
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: stretch;
            position: relative;
            z-index: 1;
            flex-direction: row;
        }


        /* Left Side - Login Form */
        .login-section {
            flex: 0 0 42%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 2rem;
        }

        .login-card {
            width: 100%;
            max-width: 480px;
            background: rgba(35, 39, 47, 0.75);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 32px;
            padding: 2.5rem;
            box-shadow:
                0 25px 80px rgba(0, 0, 0, 0.6),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
            position: relative;
            margin: auto 0;
        }

        /* Glowing border effect */
        .login-card::before {
            content: '';
            position: absolute;
            inset: -2px;
            background: linear-gradient(135deg,
                    rgba(99, 102, 241, 0.3) 0%,
                    rgba(139, 92, 246, 0.3) 50%,
                    rgba(99, 102, 241, 0.3) 100%);
            border-radius: 32px;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .login-card:hover::before {
            opacity: 0.6;
        }

        .logo-wrapper {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-wrapper img {
            width: 70px;
            height: 70px;
            margin-bottom: 1rem;
            filter: drop-shadow(0 0 20px rgba(99, 102, 241, 0.4));
        }

        .brand-title {
            font-size: 1.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .brand-subtitle {
            font-size: 0.95rem;
            color: #8b92a6;
            margin-bottom: 2rem;
        }

        .welcome-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .welcome-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #e8eaed;
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            font-size: 0.9rem;
            color: #8b92a6;
        }

        .form-label-text {
            font-size: 0.85rem;
            font-weight: 600;
            color: #b4b8c5;
            margin-bottom: 0.5rem;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            background: rgba(15, 20, 25, 0.5);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 14px;
            color: #e8eaed;
            padding: 0.875rem 1.125rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(20, 25, 32, 0.8);
            border-color: #6366f1;
            color: #e8eaed;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15), 0 0 20px rgba(99, 102, 241, 0.2);
            outline: none;
            transform: translateY(-1px);
        }

        .form-control::placeholder {
            color: #5a5f6f;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 14px;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            margin-top: 1.5rem;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.35);
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #7c8ff5 0%, #8557b0 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(99, 102, 241, 0.45);
        }

        .toggle-auth {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: #8b92a6;
        }

        .toggle-auth button {
            background: none;
            border: none;
            color: #8b5cf6;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
            margin-left: 0.5rem;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .toggle-auth button:hover {
            color: #a78bfa;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            border-radius: 12px;
            padding: 0.875rem;
            margin-top: 1rem;
        }

        /* Right Side - Feature Showcase */
        .feature-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4rem 3rem;
            position: relative;
        }

        .feature-showcase {
            width: 100%;
            max-width: 580px;
            min-height: 560px;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: rgba(35, 39, 47, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 32px;
            padding: 2.75rem 2.5rem;
            box-shadow:
                0 25px 80px rgba(0, 0, 0, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        /* Glowing border effect for feature showcase */
        .feature-showcase::before {
            content: '';
            position: absolute;
            inset: -2px;
            background: linear-gradient(135deg,
                    rgba(99, 102, 241, 0.3) 0%,
                    rgba(139, 92, 246, 0.3) 50%,
                    rgba(99, 102, 241, 0.3) 100%);
            border-radius: 32px;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .feature-showcase:hover::before {
            opacity: 0.5;
        }

        .feature-slide {
            display: none;
            text-align: center;
            animation: slideIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
        }

        .feature-slide.active {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(30px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        .feature-visual {
            width: 110px;
            height: 110px;
            margin: 0 auto 1.75rem;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(139, 92, 246, 0.15) 100%);
            border: 3px solid rgba(99, 102, 241, 0.3);
            border-radius: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.2);
            flex-shrink: 0;
        }

        .feature-visual::before {
            content: '';
            position: absolute;
            inset: -20px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 0.5;
            }

            50% {
                transform: scale(1.2);
                opacity: 0.8;
            }
        }

        .feature-visual i {
            font-size: 3rem;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            z-index: 1;
        }

        .feature-title {
            font-size: 2rem;
            font-weight: 800;
            color: #e8eaed;
            margin-bottom: 0.875rem;
            line-height: 1.2;
        }

        .feature-description {
            font-size: 1rem;
            color: #b4b8c5;
            line-height: 1.6;
            margin-bottom: 2rem;
            max-width: 95%;
        }

        .carousel-nav {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.75rem;
            margin: 1.75rem 0 1.5rem 0;
            width: 100%;
        }

        .nav-arrow {
            width: 46px;
            height: 46px;
            background: rgba(99, 102, 241, 0.12);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #8b5cf6;
            flex-shrink: 0;
        }

        .nav-arrow:hover {
            background: rgba(99, 102, 241, 0.25);
            border-color: #6366f1;
            transform: scale(1.1);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
        }

        .carousel-dots {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(139, 92, 246, 0.3);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .dot.active {
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            width: 40px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(139, 92, 246, 0.5);
        }

        .cta-section {
            text-align: center;
            margin-top: 1.25rem;
            width: 100%;
        }

        .cta-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: #8b92a6;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 0.875rem;
        }

        .cta-button {
            padding: 0.875rem 2.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 14px;
            color: white;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cta-button:hover {
            background: linear-gradient(135deg, #7c8ff5 0%, #8557b0 100%);
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.5);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .feature-showcase {
                max-width: 500px;
                min-height: 550px;
                padding: 2.5rem 2rem;
            }
        }

        @media (max-width: 1024px) {
            .login-section {
                flex: 0 0 45%;
            }

            .feature-showcase {
                max-width: 450px;
                min-height: 500px;
                padding: 2rem 1.5rem;
            }

            .feature-title {
                font-size: 1.85rem;
            }

            .feature-description {
                font-size: 1rem;
            }
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }

            .login-section {
                flex: 0 0 auto;
                padding: 2rem 1.5rem;
                order: 1;
            }

            .feature-section {
                flex: 0 0 auto;
                padding: 2rem 1.5rem;
                order: 2;
            }

            .login-card {
                padding: 2rem 1.5rem;
            }

            .feature-showcase {
                max-width: 100%;
                min-height: auto;
                padding: 2rem 1.5rem;
            }

            .feature-title {
                font-size: 1.5rem;
            }

            .feature-description {
                font-size: 0.95rem;
            }

            .welcome-title {
                font-size: 1.35rem;
            }

            .brand-title {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 1.75rem 1.25rem;
            }

            .logo-wrapper img {
                width: 55px;
                height: 55px;
            }

            .brand-title {
                font-size: 1.35rem;
            }

            .feature-section {
                padding: 1.5rem 1rem;
            }

            .feature-showcase {
                padding: 1.75rem 1.25rem;
            }

            .feature-visual {
                width: 90px;
                height: 90px;
                margin-bottom: 1.25rem;
            }

            .feature-visual i {
                font-size: 2.5rem;
            }

            .feature-title {
                font-size: 1.35rem;
            }

            .feature-description {
                font-size: 0.875rem;
            }

            .carousel-nav {
                gap: 1.25rem;
            }

            .nav-arrow {
                width: 40px;
                height: 40px;
            }

            .cta-button {
                padding: 0.75rem 2rem;
                font-size: 0.875rem;
            }
        }

        /* Language Selector Styles */
        .language-selector {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            z-index: 1000;
        }

        [dir="rtl"] .language-selector {
            right: auto;
            left: 1.5rem;
        }

        .language-dropdown {
            position: relative;
        }

        .language-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1rem;
            background: rgba(35, 39, 47, 0.75);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 12px;
            color: #e8eaed;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .language-btn:hover {
            background: rgba(45, 49, 57, 0.85);
            border-color: #6366f1;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.3);
        }

        .language-btn i {
            font-size: 1rem;
            color: #8b5cf6;
        }

        .language-flag {
            font-size: 1.25rem;
            line-height: 1;
        }

        .language-menu {
            position: absolute;
            top: calc(100% + 0.5rem);
            right: 0;
            min-width: 200px;
            background: rgba(35, 39, 47, 0.95);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 12px;
            padding: 0.5rem;
            display: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            animation: slideDown 0.2s ease;
        }

        [dir="rtl"] .language-menu {
            right: auto;
            left: 0;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .language-menu.show {
            display: block;
        }

        .language-option {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 0.875rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #e8eaed;
            text-decoration: none;
        }

        .language-option:hover {
            background: rgba(99, 102, 241, 0.15);
            color: #e8eaed;
        }

        .language-option.active {
            background: rgba(99, 102, 241, 0.25);
            color: #a78bfa;
        }

        .language-option .language-flag {
            font-size: 1.5rem;
        }

        .language-info {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .language-name {
            font-weight: 600;
            font-size: 0.875rem;
        }

        .language-native {
            font-size: 0.75rem;
            color: #8b92a6;
        }

        .language-option.active .language-native {
            color: #a78bfa;
        }

        .language-check {
            color: #8b5cf6;
            font-size: 1rem;
            display: none;
        }

        .language-option.active .language-check {
            display: block;
        }

        /* RTL Adjustments */
        [dir="rtl"] .toggle-auth button {
            margin-left: 0;
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            .language-selector {
                top: 1rem;
                right: 1rem;
            }

            [dir="rtl"] .language-selector {
                right: auto;
                left: 1rem;
            }
        }
    </style>
</head>

<body class="login-page">
    <!-- Language Selector -->
    <div class="language-selector">
        <div class="language-dropdown">
            <button class="language-btn" id="languageBtn">
                <i class="bi bi-globe"></i>
                <span class="language-flag" id="currentFlag">🇺🇸</span>
                <span id="currentLangName">English</span>
                <i class="bi bi-chevron-down" style="font-size: 0.75rem;"></i>
            </button>
            <div class="language-menu" id="languageMenu">
                <a href="set_language.php?lang=en" class="language-option active" data-lang="en" data-flag="🇺🇸" data-name="English" data-native="English">
                    <span class="language-flag">🇺🇸</span>
                    <div class="language-info">
                        <span class="language-name">English</span>
                        <span class="language-native">English</span>
                    </div>
                    <i class="bi bi-check-circle-fill language-check"></i>
                </a>
                <a href="set_language.php?lang=ar" class="language-option" data-lang="ar" data-flag="🇸🇦" data-name="Arabic" data-native="العربية">
                    <span class="language-flag">🇸🇦</span>
                    <div class="language-info">
                        <span class="language-name">Arabic</span>
                        <span class="language-native">العربية</span>
                    </div>
                    <i class="bi bi-check-circle-fill language-check"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="login-container">
        <!-- Left Side - Login Form -->
        <div class="login-section">
            <div class="login-card">
                <div class="logo-wrapper">
                    <img src="assets/img/logo1.png" alt="Streamify Pro">
                    <h1 class="brand-title" data-i18n="common.appName">Streamify Pro</h1>
                    <p class="brand-subtitle" data-i18n="auth.ultimateHub">Your Ultimate Entertainment Hub</p>
                </div>

                <div class="welcome-section">
                    <h2 class="welcome-title" data-i18n="auth.welcomeBack">Welcome Back</h2>
                    <p class="welcome-subtitle" data-i18n="auth.continueJourney">Continue your streaming journey</p>
                </div>

                <div id="authAlert" class="alert-danger d-none" role="alert"></div>

                <form id="authForm" data-mode="login">
                    <!-- Login Mode -->
                    <div id="group-usernameOrEmail">
                        <label class="form-label-text" data-i18n="auth.username">Username</label>
                        <div class="form-group">
                            <input type="text" class="form-control" id="usernameOrEmail" name="username" data-i18n-placeholder="auth.enterUsername" placeholder="Enter your username" required />
                        </div>
                    </div>

                    <!-- Signup Mode -->
                    <div id="group-username" class="d-none">
                        <label class="form-label-text" data-i18n="auth.username">Username</label>
                        <div class="form-group">
                            <input type="text" class="form-control" id="signupUsername" name="signup_username" data-i18n-placeholder="auth.chooseUsername" placeholder="Choose a username" />
                        </div>
                    </div>

                    <div id="group-email" class="d-none">
                        <label class="form-label-text" data-i18n="auth.email">Email</label>
                        <div class="form-group">
                            <input type="email" class="form-control" id="signupEmail" name="email" data-i18n-placeholder="auth.enterEmail" placeholder="your@email.com" />
                        </div>
                    </div>

                    <label class="form-label-text" data-i18n="auth.password">Password</label>
                    <div class="form-group">
                        <input type="password" class="form-control" id="password" name="password" data-i18n-placeholder="auth.enterPassword" placeholder="••••••••••" required />
                    </div>

                    <button id="submitBtn" class="btn-login" type="submit">
                        <span data-i18n="auth.signIn">Sign In</span>
                    </button>
                </form>

                <div class="toggle-auth">
                    <span id="toggleText" data-i18n="auth.newToStreamify">New to Streamify?</span>
                    <button id="toggleBtn" data-i18n="auth.createAccount">Create Account</button>
                </div>
            </div>
        </div>

        <!-- Right Side - Feature Showcase -->
        <div class="feature-section">
            <div class="feature-showcase">
                <!-- Slide 4 - Kids -->
                <div class="feature-slide" data-slide="3">
                    <div class="feature-visual">
                        <i class="bi bi-balloon-heart-fill"></i>
                    </div>
                    <h2 class="feature-title" data-i18n="auth.feature4Title">Kids Safe Zone</h2>
                    <p class="feature-description" data-i18n="auth.feature4Description">Curated educational content for children. Parental controls and age-appropriate entertainment.</p>
                </div>
                <!-- Slide 3 - Fitness -->
                <div class="feature-slide" data-slide="2">
                    <div class="feature-visual">
                        <i class="bi bi-heart-pulse-fill"></i>
                    </div>
                    <h2 class="feature-title" data-i18n="auth.feature3Title">Live Fitness Classes</h2>
                    <p class="feature-description" data-i18n="auth.feature3Description">Join expert-led workouts from yoga to HIIT. Real-time coaching and personalized training programs.</p>
                </div>

                <!-- Slide 1 - Streaming -->
                <div class="feature-slide active" data-slide="0">
                    <div class="feature-visual">
                        <i class="bi bi-play-circle-fill"></i>
                    </div>
                    <h2 class="feature-title" data-i18n="auth.feature1Title">10,000+ Movies & Shows</h2>
                    <p class="feature-description" data-i18n="auth.feature1Description">Unlimited streaming of blockbusters, series, and exclusive originals. 4K Ultra HD quality with no ads.</p>
                </div>

                <!-- Slide 2 - Gaming -->
                <div class="feature-slide" data-slide="1">
                    <div class="feature-visual">
                        <i class="bi bi-joystick"></i>
                    </div>
                    <h2 class="feature-title" data-i18n="auth.feature2Title">Browser Gaming</h2>
                    <p class="feature-description" data-i18n="auth.feature2Description">Play premium HTML5 games instantly. No downloads, no waiting. From puzzles to action adventures.</p>
                </div>




                <!-- Carousel Navigation -->
                <div class="carousel-nav">
                    <div class="nav-arrow" id="prevBtn">
                        <i class="bi bi-chevron-left"></i>
                    </div>
                    <div class="carousel-dots">
                        <span class="dot active" data-slide="0"></span>
                        <span class="dot" data-slide="1"></span>
                        <span class="dot" data-slide="2"></span>
                        <span class="dot" data-slide="3"></span>
                    </div>
                    <div class="nav-arrow" id="nextBtn">
                        <i class="bi bi-chevron-right"></i>
                    </div>
                </div>

                <!-- CTA Section -->
                <div class="cta-section">
                    <p class="cta-title" data-i18n="auth.startPremiumJourney">Start Your Premium Journey</p>
                    <button class="cta-button" onclick="document.getElementById('toggleBtn').click()">
                        <span data-i18n="auth.joinNow">Join Now</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function request(url, data) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok || json.error) throw new Error(json.error || 'Request failed');
            return json;
        }

        function showError(msg) {
            const el = document.getElementById('authAlert');
            el.textContent = msg;
            el.classList.remove('d-none');
        }

        function clearError() {
            document.getElementById('authAlert').classList.add('d-none');
        }

        const form = document.getElementById('authForm');
        const toggleBtn = document.getElementById('toggleBtn');
        const toggleText = document.getElementById('toggleText');
        const submitBtn = document.getElementById('submitBtn');
        const welcomeTitle = document.querySelector('.welcome-title');
        const welcomeSubtitle = document.querySelector('.welcome-subtitle');

        function setMode(mode) {
            form.dataset.mode = mode; // 'login' or 'signup'
            const isSignup = mode === 'signup';
            document.getElementById('group-usernameOrEmail').classList.toggle('d-none', isSignup);
            document.getElementById('group-username').classList.toggle('d-none', !isSignup);
            document.getElementById('group-email').classList.toggle('d-none', !isSignup);
            // Toggle required attributes to avoid validation on hidden fields
            document.getElementById('usernameOrEmail').required = !isSignup;
            document.getElementById('signupUsername').required = isSignup;
            document.getElementById('signupEmail').required = isSignup;
            document.getElementById('password').required = true;
            
            // Use i18n for dynamic text
            if (window.i18n && window.i18n.t) {
                submitBtn.innerHTML = isSignup ? 
                    '<span data-i18n="auth.createAccount">' + window.i18n.t('auth.createAccount') + '</span>' : 
                    '<span data-i18n="auth.signIn">' + window.i18n.t('auth.signIn') + '</span>';
                toggleText.textContent = isSignup ? window.i18n.t('auth.alreadyHaveAccount') : window.i18n.t('auth.newToStreamify');
                toggleText.setAttribute('data-i18n', isSignup ? 'auth.alreadyHaveAccount' : 'auth.newToStreamify');
                toggleBtn.textContent = isSignup ? window.i18n.t('auth.signIn') : window.i18n.t('auth.createAccount');
                toggleBtn.setAttribute('data-i18n', isSignup ? 'auth.signIn' : 'auth.createAccount');
                welcomeTitle.textContent = isSignup ? window.i18n.t('auth.joinStreamify') : window.i18n.t('auth.welcomeBack');
                welcomeTitle.setAttribute('data-i18n', isSignup ? 'auth.joinStreamify' : 'auth.welcomeBack');
                welcomeSubtitle.textContent = isSignup ? window.i18n.t('auth.startJourney') : window.i18n.t('auth.continueJourney');
                welcomeSubtitle.setAttribute('data-i18n', isSignup ? 'auth.startJourney' : 'auth.continueJourney');
            } else {
                // Fallback to English if i18n is not loaded
                submitBtn.innerHTML = isSignup ? '<span>Create Account</span>' : '<span>Sign In</span>';
                toggleText.textContent = isSignup ? 'Already have an account?' : 'New to Streamify?';
                toggleBtn.textContent = isSignup ? 'Sign In' : 'Create Account';
                welcomeTitle.textContent = isSignup ? 'Join Streamify Pro' : 'Welcome Back';
                welcomeSubtitle.textContent = isSignup ? 'Start your entertainment journey today' : 'Continue your streaming journey';
            }
        }

        toggleBtn.addEventListener('click', () => {
            setMode(form.dataset.mode === 'login' ? 'signup' : 'login');
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearError();
            const mode = form.dataset.mode;
            try {
                if (mode === 'login') {
                    const username = document.getElementById('usernameOrEmail').value.trim();
                    const password = document.getElementById('password').value;
                    const res = await request('includes/auth.php?action=login', {
                        username,
                        password
                    });
                    localStorage.setItem('Streamify Pro_user', JSON.stringify(res.user));
                    window.location.href = 'home.php';
                } else {
                    const username = document.getElementById('signupUsername').value.trim();
                    const email = document.getElementById('signupEmail').value.trim();
                    const password = document.getElementById('password').value;
                    await request('includes/auth.php?action=signup', {
                        username,
                        email,
                        password
                    });
                    const res = await request('includes/auth.php?action=login', {
                        username,
                        password
                    });
                    localStorage.setItem('Streamify Pro_user', JSON.stringify(res.user));
                    window.location.href = 'home.php';
                }
            } catch (err) {
                showError(err.message);
            }
        });

        // Language Selector - Simple dropdown toggle and display
        const languageBtn = document.getElementById('languageBtn');
        const languageMenu = document.getElementById('languageMenu');
        const languageOptions = document.querySelectorAll('.language-option');
        const currentLang = document.documentElement.getAttribute('lang') || 'en';

        // Set initial state based on current language
        const activeOption = document.querySelector(`.language-option[data-lang="${currentLang}"]`);
        if (activeOption) {
            languageOptions.forEach(opt => opt.classList.remove('active'));
            activeOption.classList.add('active');
            const flag = activeOption.getAttribute('data-flag');
            const name = activeOption.getAttribute('data-name');
            document.getElementById('currentFlag').textContent = flag;
            document.getElementById('currentLangName').textContent = name;
        }

        // Toggle language menu
        languageBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            languageMenu.classList.toggle('show');
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!languageBtn.contains(e.target) && !languageMenu.contains(e.target)) {
                languageMenu.classList.remove('show');
            }
        });

        // default state
        setMode('login');

        // Feature Carousel Functionality
        let currentSlide = 0;
        const totalSlides = 4;
        const slides = document.querySelectorAll('.feature-slide');
        const dots = document.querySelectorAll('.dot');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        function showSlide(index) {
            // Wrap around
            if (index >= totalSlides) {
                currentSlide = 0;
            } else if (index < 0) {
                currentSlide = totalSlides - 1;
            } else {
                currentSlide = index;
            }

            // Update slides
            slides.forEach((slide, i) => {
                if (i === currentSlide) {
                    slide.classList.add('active');
                } else {
                    slide.classList.remove('active');
                }
            });

            // Update dots
            dots.forEach((dot, i) => {
                if (i === currentSlide) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        }

        // Navigation arrows
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                showSlide(currentSlide - 1);
                stopAutoPlay();
                startAutoPlay();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                showSlide(currentSlide + 1);
                stopAutoPlay();
                startAutoPlay();
            });
        }

        // Indicator dots
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
                stopAutoPlay();
                startAutoPlay();
            });
        });

        // Auto-play
        let autoPlayInterval;

        function startAutoPlay() {
            autoPlayInterval = setInterval(() => {
                showSlide(currentSlide + 1);
            }, 4500);
        }

        function stopAutoPlay() {
            if (autoPlayInterval) {
                clearInterval(autoPlayInterval);
            }
        }

        startAutoPlay();

        // Pause on hover
        const showcaseElement = document.querySelector('.feature-showcase');
        if (showcaseElement) {
            showcaseElement.addEventListener('mouseenter', stopAutoPlay);
            showcaseElement.addEventListener('mouseleave', startAutoPlay);
        }

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                showSlide(currentSlide - 1);
                stopAutoPlay();
                startAutoPlay();
            } else if (e.key === 'ArrowRight') {
                showSlide(currentSlide + 1);
                stopAutoPlay();
                startAutoPlay();
            }
        });

        // Touch swipe support
        let touchStartX = 0;
        let touchEndX = 0;

        if (showcaseElement) {
            showcaseElement.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
            });

            showcaseElement.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
            });
        }

        function handleSwipe() {
            if (touchEndX < touchStartX - 50) {
                showSlide(currentSlide + 1);
                stopAutoPlay();
                startAutoPlay();
            }
            if (touchEndX > touchStartX + 50) {
                showSlide(currentSlide - 1);
                stopAutoPlay();
                startAutoPlay();
            }
        }
    </script>
</body>

</html>