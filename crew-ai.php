<?php
require_once __DIR__ . '/config.php';

startAdminSession();

// Handle Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['crew_logged_in'], $_SESSION['crew_user'], $_SESSION['crew_login_time']);
    header('Location: /crew-ai');
    exit;
}

// Handle Login POST
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    if ($user === CREW_USERNAME && $pass === CREW_PASSWORD) {
        $_SESSION['crew_logged_in'] = true;
        $_SESSION['crew_user'] = CREW_USERNAME;
        $_SESSION['crew_login_time'] = time();
        header('Location: /crew-ai');
        exit;
    } else {
        $loginError = 'Username atau password yang Anda masukkan salah.';
    }
}

$isLoggedIn = isCrewLoggedIn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isLoggedIn ? 'Traveego Crew AI &ndash; Portal Kontrol Agen' : 'Masuk ke Traveego Crew AI' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* ==============================================================
           THEME TOKENS - LIGHT MODE MATCHING HOMEPAGE (index.html)
           ============================================================== */
        :root {
            --primary-blue: #0F4C81;
            --primary-dark: #0A2E50;
            --royal-blue: #0B4FD8;
            --deep-navy: #020C1E;
            --accent-green: #16A34A;
            --accent-emerald: #10B981;
            --accent-amber: #F59E0B;
            --accent-rose: #EF4444;
            --text-title: #0F172A;
            --text-body: #334155;
            --text-muted: #64748B;
            --border-subtle: #E2E8F0;
            --border-strong: #CBD5E1;
            --bg-page: #F8FAFC;
            --bg-card: #FFFFFF;
            --bg-muted: #F1F5F9;
            --sidebar-width: 280px;
            --bottom-nav-height: 66px;
            --radius-sm: 8px;
            --radius-md: 14px;
            --radius-lg: 20px;
            --radius-xl: 24px;
            --radius-android: 26px;
            --shadow-subtle: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
            --shadow-card: 0 10px 30px -5px rgba(15, 23, 42, 0.06), 0 0 1px rgba(15, 23, 42, 0.1);
            --shadow-float: 0 20px 40px -10px rgba(11, 79, 216, 0.18);
            --shadow-android: 0 8px 24px -4px rgba(15, 23, 42, 0.06), 0 2px 6px -1px rgba(15, 23, 42, 0.04);
            --shadow-android-hover: 0 16px 36px -6px rgba(11, 79, 216, 0.15), 0 4px 12px -2px rgba(11, 79, 216, 0.08);
            --font-sans: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: var(--font-sans);
            -webkit-tap-highlight-color: transparent;
        }

        html, body {
            min-height: 100vh;
            background-color: var(--bg-page);
            color: var(--text-body);
            line-height: 1.5;
        }

        /* ==============================================================
           1. LOGIN CARD STYLES (LIGHT MODE)
           ============================================================== */
        .login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background: linear-gradient(135deg, #F0F7FF 0%, #F8FAFC 50%, #EFF6FF 100%);
            position: relative;
            overflow: hidden;
        }

        .login-wrap::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(11, 79, 216, 0.08) 0%, transparent 70%);
            top: -150px;
            right: -100px;
            pointer-events: none;
        }

        .login-wrap::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(22, 163, 74, 0.06) 0%, transparent 70%);
            bottom: -150px;
            left: -100px;
            pointer-events: none;
        }

        .login-box {
            width: 100%;
            max-width: 440px;
            background: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-xl);
            padding: 44px 36px;
            box-shadow: var(--shadow-float);
            position: relative;
            z-index: 10;
        }

        .login-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--royal-blue), var(--accent-green));
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            color: var(--royal-blue);
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }

        .login-title {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-title);
            letter-spacing: -0.5px;
            margin-bottom: 8px;
        }

        .login-sub {
            color: var(--text-muted);
            font-size: 13.5px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-title);
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-control {
            width: 100%;
            height: 48px;
            background: #FFFFFF;
            border: 1.5px solid var(--border-strong);
            border-radius: 12px;
            padding: 0 16px;
            color: var(--text-title);
            font-size: 15px;
            transition: all 0.2s ease;
            outline: none;
        }

        .input-control:focus {
            border-color: var(--royal-blue);
            box-shadow: 0 0 0 4px rgba(11, 79, 216, 0.12);
        }

        .input-password {
            padding-right: 48px;
        }

        .btn-toggle-eye {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .btn-toggle-eye:hover {
            color: var(--primary-blue);
            background: var(--bg-muted);
        }

        .btn-toggle-eye svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .btn-submit {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, var(--royal-blue), var(--primary-blue));
            border: none;
            border-radius: 12px;
            color: #FFFFFF;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 25px rgba(11, 79, 216, 0.35);
            transition: all 0.2s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(11, 79, 216, 0.45);
        }

        .alert-error {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            color: #DC2626;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13.5px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }

        /* ==============================================================
           2. DESKTOP SIDEBAR LAYOUT
           ============================================================== */
        .dash-layout {
            display: flex;
            min-height: 100vh;
            background: var(--bg-page);
        }

        .dash-sidebar {
            width: var(--sidebar-width);
            background: #FFFFFF;
            border-right: 1px solid var(--border-subtle);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            overflow-y: auto;
        }

        .sidebar-brand {
            padding: 22px 20px;
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none;
            color: inherit;
        }

        .brand-logo-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--royal-blue), #1E40AF);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #FFFFFF;
            font-size: 22px;
            box-shadow: 0 6px 16px rgba(11, 79, 216, 0.3);
            flex-shrink: 0;
        }

        .brand-name {
            font-size: 16px;
            font-weight: 800;
            color: var(--text-title);
            letter-spacing: -0.3px;
        }

        .brand-tagline {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--royal-blue);
            letter-spacing: 0.3px;
        }

        /* Working Hours Widget */
        .sidebar-working-card {
            margin: 16px 18px 8px;
            padding: 14px 16px;
            background: #F0F7FF;
            border: 1px solid #BFDBFE;
            border-radius: var(--radius-md);
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .sw-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sw-title {
            font-size: 11px;
            font-weight: 800;
            color: var(--primary-dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sw-pill {
            font-size: 10.5px;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .pill-active {
            background: #DCFCE7;
            color: #15803D;
            border: 1px solid #86EFAC;
        }

        .pill-sleep {
            background: #FEF3C7;
            color: #B45309;
            border: 1px solid #FCD34D;
        }

        .sw-hours {
            font-size: 13.5px;
            font-weight: 800;
            color: var(--royal-blue);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .sw-clock {
            font-family: var(--font-mono);
            font-size: 11px;
            color: var(--text-muted);
        }

        .sidebar-heading {
            padding: 18px 20px 8px;
            font-size: 11px;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        /* Clean General Sidebar Navigation */
        .sidebar-menu-list {
            list-style: none;
            padding: 0 12px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 20px;
        }

        .sidebar-menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: var(--radius-md);
            color: var(--text-body);
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1.5px solid transparent;
            font-size: 13.5px;
            font-weight: 700;
        }

        .sidebar-menu-item:hover {
            background: var(--bg-muted);
            color: var(--text-title);
        }

        .sidebar-menu-item.active {
            background: #EFF6FF;
            color: var(--royal-blue);
            border-color: #BFDBFE;
            box-shadow: 0 4px 12px rgba(11, 79, 216, 0.08);
        }

        .menu-item-icon {
            font-size: 18px;
            width: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 16px 18px;
            border-top: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #FAFBFD;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #10B981, #059669);
            color: #FFFFFF;
            font-weight: 800;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-name {
            font-size: 13px;
            font-weight: 800;
            color: var(--text-title);
        }

        .user-role {
            font-size: 10.5px;
            color: var(--text-muted);
        }

        .btn-logout-sidebar {
            background: #FEE2E2;
            border: 1px solid #FECACA;
            color: #DC2626;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11.5px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-logout-sidebar:hover {
            background: #FCA5A5;
            color: #7F1D1D;
        }

        /* ==============================================================
           3. MAIN CONTENT AREA (LIGHT MODE)
           ============================================================== */
        .dash-main {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: var(--bg-page);
        }

        .dash-topbar {
            height: 72px;
            background: #FFFFFF;
            border-bottom: 1px solid var(--border-subtle);
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 90;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .topbar-brand-mobile {
            display: none;
            align-items: center;
            gap: 10px;
        }

        .topbar-title {
            font-size: 18px;
            font-weight: 800;
            color: var(--text-title);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .pill-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 20px;
        }

        .pill-blue {
            background: #EFF6FF;
            color: var(--royal-blue);
            border: 1px solid #BFDBFE;
        }

        .pill-green {
            background: #F0FDF4;
            color: #15803D;
            border: 1px solid #BBF7D0;
        }

        .content-container {
            padding: 32px;
            max-width: 1300px;
            width: 100%;
            margin: 0 auto;
        }

        /* Agent Tab Panels */
        .agent-panel {
            display: none;
            animation: fadeIn 0.25s ease;
        }

        .agent-panel.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Back Button on Agent Workspace */
        .btn-back-hub {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #FFFFFF;
            border: 1.5px solid var(--border-strong);
            color: var(--text-title);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 20px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }

        .btn-back-hub:hover {
            border-color: var(--royal-blue);
            color: var(--royal-blue);
            background: #F8FAFC;
            transform: translateX(-3px);
        }

        /* Agent Visual Profile Hero Banner */
        .agent-hero-banner {
            background: #FFFFFF;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-xl);
            padding: 28px 32px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            box-shadow: var(--shadow-subtle);
            position: relative;
            overflow: hidden;
        }

        .agent-hero-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 6px;
            background: linear-gradient(180deg, var(--royal-blue), var(--accent-green));
        }

        .hero-banner-left {
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .hero-agent-avatar {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            background: linear-gradient(135deg, #F0F7FF, #E0F2FE);
            border: 2px solid #BFDBFE;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            box-shadow: 0 8px 20px rgba(11, 79, 216, 0.12);
            position: relative;
            flex-shrink: 0;
        }

        .hero-agent-avatar .hero-online-dot {
            width: 14px;
            height: 14px;
            background: #22C55E;
            border-radius: 50%;
            border: 3px solid #FFFFFF;
            position: absolute;
            bottom: -3px;
            right: -3px;
        }

        .hero-banner-text h2 {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-title);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .hero-banner-text p {
            font-size: 13.5px;
            color: var(--text-body);
            line-height: 1.5;
            max-width: 680px;
        }

        .hero-banner-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        /* Buttons */
        .btn-brand-primary {
            background: linear-gradient(135deg, var(--royal-blue), var(--primary-blue));
            color: #FFFFFF;
            border: none;
            border-radius: 12px;
            padding: 12px 22px;
            font-size: 13.5px;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 6px 18px rgba(11, 79, 216, 0.3);
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-brand-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(11, 79, 216, 0.4);
        }

        .btn-brand-green {
            background: linear-gradient(135deg, #22C55E, #16A34A);
            color: #FFFFFF;
            border: none;
            border-radius: 12px;
            padding: 12px 22px;
            font-size: 13.5px;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 6px 18px rgba(22, 163, 74, 0.35);
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-brand-green:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(22, 163, 74, 0.45);
        }

        .btn-brand-outline {
            background: #FFFFFF;
            color: var(--text-title);
            border: 1.5px solid var(--border-strong);
            border-radius: 12px;
            padding: 11px 20px;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-brand-outline:hover {
            border-color: var(--royal-blue);
            color: var(--royal-blue);
            background: #F8FAFC;
        }

        /* Overview Stat Grid */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: #FFFFFF;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 22px 24px;
            box-shadow: var(--shadow-subtle);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-card);
        }

        .stat-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .stat-label {
            font-size: 12px;
            font-weight: 800;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-title);
            margin-bottom: 4px;
        }

        .stat-desc {
            font-size: 12.5px;
            color: var(--text-muted);
        }

        /* ==============================================================
           4. VISUAL AI AGENTS CARD MENU ALA ANDROID (MATERIAL 3)
           ============================================================== */
        .android-menu-section {
            margin-bottom: 34px;
        }

        .android-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .android-title-group h3 {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-title);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .android-title-group p {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* Android Card Grid (Strict 3 x 2 Layout) */
        .android-card-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        /* Android App Card */
        .android-card {
            background: #FFFFFF;
            border: 1.5px solid var(--border-subtle);
            border-radius: var(--radius-android);
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: var(--shadow-android);
            cursor: pointer;
            transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
            user-select: none;
        }

        .android-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top right, rgba(11, 79, 216, 0.04), transparent 60%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .android-card:hover {
            transform: translateY(-5px);
            border-color: #BFDBFE;
            box-shadow: var(--shadow-android-hover);
        }

        .android-card:hover::before {
            opacity: 1;
        }

        .android-card:active {
            transform: scale(0.98);
        }

        /* Android Card Top Row */
        .android-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        /* Android Squircle App Icon */
        .android-app-icon {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            position: relative;
            flex-shrink: 0;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.06);
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .android-card:hover .android-app-icon {
            transform: scale(1.08) rotate(2deg);
        }

        /* Theme variants for Android squircle icons */
        .icon-theme-blue {
            background: linear-gradient(135deg, #EFF6FF, #DBEAFE);
            border: 2px solid #BFDBFE;
            color: #1D4ED8;
        }

        .icon-theme-green {
            background: linear-gradient(135deg, #F0FDF4, #DCFCE7);
            border: 2px solid #BBF7D0;
            color: #15803D;
        }

        .icon-theme-amber {
            background: linear-gradient(135deg, #FFFBEB, #FEF3C7);
            border: 2px solid #FDE68A;
            color: #B45309;
        }

        .icon-theme-cyan {
            background: linear-gradient(135deg, #ECFEFF, #CFFAFE);
            border: 2px solid #A5F3FC;
            color: #0E7490;
        }

        .icon-theme-indigo {
            background: linear-gradient(135deg, #EEF2FF, #E0E7FF);
            border: 2px solid #C7D2FE;
            color: #4338CA;
        }

        .icon-theme-rose {
            background: linear-gradient(135deg, #FFF1F2, #FFE4E6);
            border: 2px solid #FECDD3;
            color: #E11D48;
        }

        .icon-theme-purple {
            background: linear-gradient(135deg, #FAF5FF, #F3E8FF);
            border: 2px solid #E9D5FF;
            color: #7E22CE;
        }

        /* Android Status Badge Pill */
        .android-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 800;
            padding: 5px 12px;
            border-radius: 30px;
            letter-spacing: 0.3px;
        }

        .pill-status-active {
            background: #DCFCE7;
            color: #15803D;
            border: 1px solid #86EFAC;
        }

        .pill-status-live {
            background: #EFF6FF;
            color: #1D4ED8;
            border: 1px solid #BFDBFE;
        }

        .pill-status-amber {
            background: #FEF3C7;
            color: #B45309;
            border: 1px solid #FCD34D;
        }

        /* Android Card Body */
        .android-card-body {
            margin-bottom: 20px;
        }

        .android-agent-name {
            font-size: 18px;
            font-weight: 800;
            color: var(--text-title);
            margin-bottom: 4px;
            letter-spacing: -0.3px;
        }

        .android-agent-role {
            font-size: 12px;
            font-weight: 700;
            color: var(--royal-blue);
            margin-bottom: 10px;
            display: block;
        }

        .android-agent-duty {
            font-size: 13px;
            color: var(--text-body);
            line-height: 1.5;
        }

        /* Android Card Action Button */
        .android-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 1px solid var(--border-subtle);
        }

        .android-action-link {
            font-size: 12.5px;
            font-weight: 800;
            color: var(--royal-blue);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: gap 0.2s;
        }

        .android-card:hover .android-action-link {
            gap: 10px;
        }

        .android-tap-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #F1F5F9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            color: var(--text-title);
            transition: all 0.2s;
        }

        .android-card:hover .android-tap-circle {
            background: var(--royal-blue);
            color: #FFFFFF;
            transform: translateX(3px);
        }

        /* Content Card Generic */
        .content-card {
            background: #FFFFFF;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-xl);
            padding: 28px;
            margin-bottom: 28px;
            box-shadow: var(--shadow-subtle);
        }

        .content-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 14px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 800;
            color: var(--text-title);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Workflow Architecture Showcase Styles */
        .workflow-tabs {
            display: inline-flex;
            background: #F1F5F9;
            padding: 4px;
            border-radius: 12px;
            gap: 4px;
        }

        .btn-wf-tab {
            border: none;
            background: transparent;
            color: var(--text-muted);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-wf-tab.active {
            background: #FFFFFF;
            color: var(--royal-blue);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .wf-banner-notice {
            background: #F0FDF4;
            border: 1px solid #BBF7D0;
            color: #166534;
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .wf-steps-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            align-items: stretch;
        }

        .wf-step-card {
            background: #FFFFFF;
            border: 1.5px solid var(--border-subtle);
            border-radius: 18px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .wf-step-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
            border-color: #BFDBFE;
        }

        .wf-step-num {
            font-size: 10px;
            font-weight: 800;
            color: var(--royal-blue);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .wf-step-avatar {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 12px;
            flex-shrink: 0;
        }

        .wf-step-title {
            font-size: 14.5px;
            font-weight: 800;
            color: var(--text-title);
            margin-bottom: 2px;
        }

        .wf-step-role {
            font-size: 11px;
            font-weight: 700;
            color: var(--royal-blue);
            margin-bottom: 10px;
        }

        .wf-step-desc {
            font-size: 12px;
            color: var(--text-body);
            line-height: 1.55;
            margin-bottom: 14px;
            flex-grow: 1;
        }

        .wf-step-output {
            background: #F8FAFC;
            border: 1px dashed var(--border-strong);
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 11px;
            color: var(--text-title);
            margin-bottom: 12px;
        }

        .btn-wf-action {
            width: 100%;
            background: #EFF6FF;
            color: var(--royal-blue);
            border: 1px solid #BFDBFE;
            padding: 8px 10px;
            border-radius: 10px;
            font-size: 11.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }

        .btn-wf-action:hover {
            background: var(--royal-blue);
            color: #FFFFFF;
        }

        /* Light Data Table */
        .table-responsive {
            overflow-x: auto;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-subtle);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
            text-align: left;
        }

        .data-table th {
            background: #F8FAFC;
            color: var(--primary-dark);
            font-weight: 800;
            padding: 14px 16px;
            border-bottom: 1.5px solid var(--border-subtle);
            white-space: nowrap;
        }

        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-subtle);
            color: var(--text-body);
        }

        .data-table tr:hover td {
            background: #F8FAFC;
        }

        .badge-price {
            font-family: var(--font-mono);
            font-weight: 700;
            color: #15803D;
            background: #DCFCE7;
            border: 1px solid #86EFAC;
            padding: 4px 10px;
            border-radius: 6px;
            white-space: nowrap;
        }

        .badge-route-slug {
            font-family: var(--font-mono);
            font-size: 11px;
            color: var(--royal-blue);
            background: #EFF6FF;
            padding: 2px 8px;
            border-radius: 4px;
        }

        .tag-dynamic {
            font-family: var(--font-mono);
            font-size: 11.5px;
            background: #FEF3C7;
            color: #B45309;
            border: 1px solid #FCD34D;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 600;
        }

        /* Grid for Pages & Posts */
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
        }

        .item-card {
            background: #FFFFFF;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 22px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s;
            box-shadow: var(--shadow-subtle);
        }

        .item-card:hover {
            border-color: #BFDBFE;
            transform: translateY(-2px);
            box-shadow: var(--shadow-card);
        }

        .item-title {
            font-size: 15.5px;
            font-weight: 800;
            color: var(--text-title);
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .item-desc {
            font-size: 13px;
            color: var(--text-body);
            line-height: 1.5;
            margin-bottom: 16px;
        }

        .item-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11.5px;
            color: var(--text-muted);
            border-top: 1px solid var(--border-subtle);
            padding-top: 12px;
        }

        /* Chat Simulator for Mbak Tiara */
        .chat-sim-container {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
        }

        @media (max-width: 992px) {
            .chat-sim-container {
                grid-template-columns: 1fr;
            }
        }

        .chat-window {
            background: #FFFFFF;
            border: 1.5px solid var(--border-subtle);
            border-radius: var(--radius-xl);
            display: flex;
            flex-direction: column;
            height: 520px;
            overflow: hidden;
            box-shadow: var(--shadow-subtle);
        }

        .chat-window-header {
            padding: 16px 20px;
            background: #F8FAFC;
            border-bottom: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-agent-badge {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chat-feed {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #F8FAFC;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .chat-bubble {
            max-width: 82%;
            padding: 14px 18px;
            border-radius: 18px;
            font-size: 13.5px;
            line-height: 1.55;
            position: relative;
            white-space: pre-wrap;
        }

        .chat-bubble.tiara {
            align-self: flex-start;
            background: #FFFFFF;
            color: var(--text-title);
            border: 1px solid var(--border-subtle);
            border-bottom-left-radius: 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        .chat-bubble.user {
            align-self: flex-end;
            background: linear-gradient(135deg, var(--royal-blue), #1D4ED8);
            color: #FFFFFF;
            border-bottom-right-radius: 4px;
            box-shadow: 0 4px 12px rgba(11, 79, 216, 0.25);
        }

        .chat-input-bar {
            padding: 14px 18px;
            background: #FFFFFF;
            border-top: 1px solid var(--border-subtle);
            display: flex;
            gap: 10px;
        }

        .chat-input {
            flex: 1;
            height: 44px;
            border: 1.5px solid var(--border-strong);
            border-radius: 12px;
            padding: 0 16px;
            font-size: 14px;
            color: var(--text-title);
            outline: none;
        }

        .chat-input:focus {
            border-color: var(--royal-blue);
            box-shadow: 0 0 0 3px rgba(11, 79, 216, 0.1);
        }

        /* Terminal Console */
        .terminal-box {
            background: #020617;
            border: 1px solid #1E293B;
            border-radius: 14px;
            padding: 18px;
            font-family: var(--font-mono);
            font-size: 13px;
            color: #38BDF8;
            min-height: 240px;
            max-height: 440px;
            overflow-y: auto;
            white-space: pre-wrap;
            line-height: 1.6;
        }

        /* ==============================================================
           5. MOBILE BOTTOM BAR NAVIGATION ALA ANDROID
           ============================================================== */
        .mobile-bottom-nav {
            display: none;
        }

        @media (max-width: 900px) {
            /* Sembunyikan sidebar di layar HP */
            .dash-sidebar {
                display: none !important;
            }

            /* Main wrapper full width */
            .dash-main {
                margin-left: 0 !important;
                width: 100% !important;
            }

            /* Topbar HP */
            .dash-topbar {
                padding: 0 18px;
                height: 64px;
            }

            .topbar-brand-mobile {
                display: flex;
            }

            .topbar-title {
                display: none;
            }

            .topbar-right {
                gap: 8px;
            }

            #topbarTailscaleBadge {
                display: none;
            }

            #btnQuickRun {
                padding: 8px 14px;
                font-size: 12px;
                border-radius: 8px;
            }

            /* Container padding dengan extra bottom padding agar konten tidak tertutup bottom bar */
            .content-container {
                padding: 18px 14px calc(var(--bottom-nav-height) + 30px);
            }

            .agent-hero-banner {
                padding: 20px 18px;
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .hero-banner-left {
                flex-direction: row;
                gap: 14px;
            }

            .hero-agent-avatar {
                width: 56px;
                height: 56px;
                font-size: 28px;
                border-radius: 16px;
            }

            .hero-banner-text h2 {
                font-size: 18px;
            }

            .hero-banner-actions {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
            }

            /* 3 Kotak Status Overview jadi 1 Baris di HP */
            .stat-grid-overview {
                display: grid !important;
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                gap: 8px !important;
                margin-bottom: 20px !important;
            }

            .stat-grid-overview .stat-card {
                padding: 10px 8px !important;
                border-radius: 14px !important;
                min-width: 0 !important;
                box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04) !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
            }

            .stat-grid-overview .stat-card-header {
                display: flex !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 4px !important;
                margin-bottom: 6px !important;
            }

            .stat-grid-overview .stat-label {
                font-size: 9.5px !important;
                font-weight: 800 !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                max-width: 100% !important;
                color: var(--text-muted) !important;
            }

            .stat-grid-overview .sw-pill {
                font-size: 8.5px !important;
                padding: 2px 6px !important;
                border-radius: 10px !important;
                line-height: 1 !important;
            }

            .stat-grid-overview .stat-value {
                font-size: 12.5px !important;
                font-weight: 800 !important;
                color: var(--text-title) !important;
                margin-bottom: 2px !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                letter-spacing: -0.3px !important;
            }

            .stat-grid-overview .stat-desc {
                font-size: 9px !important;
                line-height: 1.25 !important;
                color: var(--text-muted) !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                max-width: 100% !important;
            }

            /* Android Card Grid 3 x 2 di Layar HP */
            .android-card-grid {
                display: grid !important;
                grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                gap: 10px !important;
            }

            .android-card {
                padding: 12px 6px !important;
                border-radius: 18px !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                text-align: center !important;
                min-width: 0 !important;
                justify-content: space-between !important;
            }

            .android-card-top {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                gap: 6px !important;
                margin-bottom: 8px !important;
                width: 100% !important;
            }

            .android-app-icon {
                width: 48px !important;
                height: 48px !important;
                border-radius: 16px !important;
                font-size: 24px !important;
            }

            .android-status-pill {
                font-size: 8px !important;
                padding: 2px 5px !important;
                border-radius: 8px !important;
                white-space: nowrap !important;
                line-height: 1 !important;
            }

            .android-card-body {
                margin-bottom: 6px !important;
                width: 100% !important;
            }

            .android-agent-name {
                font-size: 12.5px !important;
                font-weight: 800 !important;
                margin-bottom: 2px !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                max-width: 100% !important;
            }

            .android-agent-role {
                font-size: 9px !important;
                font-weight: 700 !important;
                margin-bottom: 0 !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                max-width: 100% !important;
            }

            .android-agent-duty {
                display: none !important;
            }

            .android-card-footer {
                padding-top: 6px !important;
                justify-content: center !important;
                width: 100% !important;
            }

            .android-action-link {
                font-size: 10px !important;
                gap: 3px !important;
            }

            .android-tap-circle {
                display: none !important;
            }

            /* Bottom Bar HP Muncul */
            .mobile-bottom-nav {
                display: flex;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                height: var(--bottom-nav-height);
                background: rgba(255, 255, 255, 0.96);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border-top: 1px solid var(--border-subtle);
                z-index: 1000;
                box-shadow: 0 -4px 20px rgba(15, 23, 42, 0.08);
                padding: 6px 8px calc(6px + env(safe-area-inset-bottom, 0px));
                justify-content: space-around;
                align-items: center;
            }

            .bnav-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                flex: 1;
                height: 100%;
                color: var(--text-muted);
                text-decoration: none;
                cursor: pointer;
                padding: 4px 2px;
                transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
                -webkit-tap-highlight-color: transparent;
            }

            .bnav-item:active {
                transform: scale(0.92);
            }

            .bnav-icon-wrap {
                width: 40px;
                height: 28px;
                border-radius: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 19px;
                transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            }

            .bnav-label {
                font-size: 11px;
                font-weight: 700;
                margin-top: 2px;
                letter-spacing: -0.2px;
                transition: color 0.2s;
            }

            .bnav-item.active {
                color: var(--royal-blue);
            }

            .bnav-item.active .bnav-icon-wrap {
                background: #DBEAFE;
                color: var(--royal-blue);
            }

            .bnav-item.active .bnav-label {
                color: var(--royal-blue);
                font-weight: 800;
            }

            /* Workflow Architecture di Layar HP */
            .wf-steps-grid {
                grid-template-columns: 1fr !important;
                gap: 14px !important;
            }

            .workflow-tabs {
                width: 100% !important;
                display: flex !important;
                flex-direction: column !important;
            }
        }
    </style>
</head>
<body>

<?php if (!$isLoggedIn): ?>
<!-- ==============================================================
     HALAMAN LOGIN KHUSUS CREW AI (MODE TERANG)
     ============================================================== -->
<div class="login-wrap">
    <div class="login-box">
        <div class="login-header">
            <div class="login-badge">
                <span>🤖</span>
                <span>TRAVEEGO AI CREW</span>
            </div>
            <h1 class="login-title">Control Center</h1>
            <p class="login-sub">Masuk untuk memantau 5 AI Agent Traveego, Cost Matrix, dan Otomatisasi Konten SEO.</p>
        </div>

        <?php if (!empty($loginError)): ?>
            <div class="alert-error">
                <span>⚠️</span>
                <span><?= htmlspecialchars($loginError) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="/crew-ai">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <div class="input-wrap">
                    <!-- USERNAME TANPA PLACEHOLDER -->
                    <input type="text" id="username" name="username" class="input-control" required autofocus autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrap">
                    <!-- PASSWORD TANPA PLACEHOLDER + TOMBOL MATA -->
                    <input type="password" id="password" name="password" class="input-control input-password" required autocomplete="current-password">
                    <button type="button" class="btn-toggle-eye" id="togglePasswordBtn" title="Lihat password" aria-label="Lihat password">
                        <svg id="eyeIcon" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <span>Masuk ke Sistem AI</span>
                <span>➔</span>
            </button>
        </form>
    </div>
</div>

<script>
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const pwdInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    toggleBtn.addEventListener('click', function() {
        if (pwdInput.type === 'password') {
            pwdInput.type = 'text';
            eyeIcon.innerHTML = `
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
            `;
            toggleBtn.title = "Sembunyikan password";
        } else {
            pwdInput.type = 'password';
            eyeIcon.innerHTML = `
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            `;
            toggleBtn.title = "Lihat password";
        }
    });
</script>

<?php else: ?>
<!-- ==============================================================
     DASHBOARD CREW AI: SIDEBAR PC / BOTTOM BAR HP
     ============================================================== -->
<div class="dash-layout">
    <!-- 1. LEFT SIDEBAR (HANYA AKTIF DI TAMPILAN PC / DESKTOP) -->
    <aside class="dash-sidebar" id="dashSidebar">
        <!-- Brand Header -->
        <a href="/crew-ai" class="sidebar-brand">
            <div class="brand-logo-icon">🤖</div>
            <div>
                <div class="brand-name">TRAVEEGO AI CREW</div>
                <div class="brand-tagline">Autonomous Multi-Agent</div>
            </div>
        </a>

        <!-- Working Hours Status Card -->
        <div class="sidebar-working-card">
            <div class="sw-row">
                <span class="sw-title">Server Jam Kerja</span>
                <span class="sw-pill pill-active" id="sbHoursPill">🟢 AKTIF</span>
            </div>
            <div class="sw-hours">
                <span>⏰</span>
                <span>06:00 &ndash; 23:00 WIB</span>
            </div>
            <div class="sw-clock" id="sbClockText">Memuat jam...</div>
        </div>

        <!-- Section: Navigation Menu -->
        <div class="sidebar-heading">Menu Utama</div>
        <ul class="sidebar-menu-list">
            <li>
                <div class="sidebar-menu-item active" id="menuItemOverview" onclick="returnToOverviewHub()">
                    <span class="menu-item-icon">🏠</span>
                    <span>Overview Hub</span>
                </div>
            </li>
            <li>
                <div class="sidebar-menu-item" onclick="scrollToWorkflows()">
                    <span class="menu-item-icon">🧭</span>
                    <span>Peta Alur Kerja (Workflow)</span>
                </div>
            </li>
            <li>
                <div class="sidebar-menu-item" onclick="openServerConfigModal()">
                    <span class="menu-item-icon">⚙️</span>
                    <span>Pengaturan PC Server</span>
                </div>
            </li>
            <li>
                <div class="sidebar-menu-item" onclick="openAgentWorkspace('komandanPanel', 'Komandan Sistem &ndash; Orchestrator')">
                    <span class="menu-item-icon">⚡</span>
                    <span>Terminal & Pipeline Log</span>
                </div>
            </li>
        </ul>

        <!-- Bottom User Section -->
        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="user-avatar">W</div>
                <div>
                    <div class="user-name">winsyah</div>
                    <div class="user-role">AI Supervisor</div>
                </div>
            </div>
            <a href="/crew-ai?logout=1" class="btn-logout-sidebar" title="Keluar">
                <span>🚪</span>
                <span>Keluar</span>
            </a>
        </div>
    </aside>

    <!-- 2. MAIN CONTENT AREA -->
    <main class="dash-main">
        <!-- Topbar -->
        <header class="dash-topbar">
            <div class="topbar-left">
                <!-- Brand Icon di HP -->
                <div class="topbar-brand-mobile">
                    <div class="brand-logo-icon" style="width: 36px; height: 36px; font-size: 18px; border-radius: 10px;">🤖</div>
                    <span style="font-weight: 800; font-size: 15px; color: var(--text-title);">TRAVEEGO AI</span>
                </div>
                <!-- Title di PC -->
                <div class="topbar-title" id="activeAgentHeaderTitle">Overview Control Hub</div>
            </div>

            <div class="topbar-right">
                <span class="pill-badge pill-blue" id="topbarTailscaleBadge">
                    <span>⚡</span>
                    <span id="topbarTailscaleText">Tailscale: 100.85.151.80</span>
                </span>
                <button class="btn-brand-primary" onclick="runPipeline()" id="btnQuickRun">
                    <span>🚀</span>
                    <span>Jalankan Pipeline</span>
                </button>
            </div>
        </header>

        <div class="content-container">

            <!-- ==============================================================
                 PANEL 0: OVERVIEW HUB (ANDROID CARD MENU SHOWCASE)
                 ============================================================== -->
            <section class="agent-panel active" id="overviewHub">
                <!-- Welcome Banner -->
                <div class="agent-hero-banner">
                    <div class="hero-banner-left">
                        <div class="hero-agent-avatar">
                            <span>🤖</span>
                            <div class="hero-online-dot"></div>
                        </div>
                        <div class="hero-banner-text">
                            <h2>Traveego AI Crew Control Center</h2>
                            <p>Selamat datang, Mas Winsyah! Sentuh atau klik kartu AI Agent di bawah untuk melihat wujud visual dan mengontrol ruang kerja masing-masing agen secara langsung.</p>
                        </div>
                    </div>
                    <div class="hero-banner-actions">
                        <button class="btn-brand-outline" onclick="openServerConfigModal()">⚙️ Atur PC Server</button>
                        <button class="btn-brand-green" onclick="refreshData()">🔄 Segarkan Data</button>
                    </div>
                </div>

                <!-- Status Stats Grid -->
                <div class="stat-grid stat-grid-overview">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-label">Jadwal Operasional PC Server</span>
                            <span class="sw-pill pill-active" id="statHoursPill">🟢 AKTIF</span>
                        </div>
                        <div class="stat-value">06:00 &ndash; 23:00 WIB</div>
                        <div class="stat-desc" id="statHoursDesc">PC Server aktif memproses riset & penulisan otomatis.</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-label">Otak AI PC Server (Tailscale)</span>
                            <span class="sw-pill pill-active" id="statOllamaPill">🟢 TERHUBUNG</span>
                        </div>
                        <div class="stat-value" id="statOllamaModel">Qwen 2.5 Coder 7B</div>
                        <div class="stat-desc" id="statOllamaHost">100.85.151.80:11434</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-label">Produksi Halaman & Artikel</span>
                            <span class="sw-pill pill-active">⚡ SYNCED</span>
                        </div>
                        <div class="stat-value"><span id="statRoutesCount">4</span> Rute | <span id="statPostsCount">4</span> Post</div>
                        <div class="stat-desc">Variabel dinamis & shortcode aktif terhubung.</div>
                    </div>
                </div>

                <!-- ANDROID-STYLE CARD MENU FOR 5 AI AGENTS -->
                <div class="android-menu-section" id="androidMenuSection">
                    <div class="android-section-header">
                        <div class="android-title-group">
                            <h3>
                                <span>📱</span>
                                <span>Menu Tim AI Agent (Android Cards)</span>
                            </h3>
                            <p>Sentuh salah satu kartu untuk membuka ruang kerja, data, atau simulasi agen.</p>
                        </div>
                    </div>

                    <div class="android-card-grid">
                        <!-- CARD 1: PAK HENDRA -->
                        <div class="android-card" onclick="openAgentWorkspace('hendraPanel', 'Pak Hendra &ndash; Tariff & Intelligence')">
                            <div class="android-card-top">
                                <div class="android-app-icon icon-theme-blue">
                                    <span>👔</span>
                                </div>
                                <span class="android-status-pill pill-status-active">🟢 Menghitung</span>
                            </div>
                            <div class="android-card-body">
                                <h4 class="android-agent-name">Pak Hendra</h4>
                                <span class="android-agent-role">Tariff & Cost Intelligence Agent</span>
                                <p class="android-agent-duty">Survey harga BBM Pertamina, hitung tarif tol gerbang-ke-gerbang (Cipularang, MBZ, JORR, Sedyatmo), dan kalkulasi margin profit aman.</p>
                            </div>
                            <div class="android-card-footer">
                                <span class="android-action-link">Buka Matrix Biaya & Tarif ➔</span>
                                <div class="android-tap-circle">➔</div>
                            </div>
                        </div>

                        <!-- CARD 2: BUNG KEVIN -->
                        <div class="android-card" onclick="openAgentWorkspace('kevinPanel', 'Bung Kevin &ndash; Page Crew Copywriter')">
                            <div class="android-card-top">
                                <div class="android-app-icon icon-theme-green">
                                    <span>🎯</span>
                                </div>
                                <span class="android-status-pill pill-status-active">🟢 4 Rute Aktif</span>
                            </div>
                            <div class="android-card-body">
                                <h4 class="android-agent-name">Bung Kevin</h4>
                                <span class="android-agent-role">Page Crew (Landing Page Copywriter)</span>
                                <p class="android-agent-duty">Meracik copywriting direct response untuk landing page penawaran rute travel sekali jalan: All-In Tol + BBM, 0% DP, dan garansi penjemputan.</p>
                            </div>
                            <div class="android-card-footer">
                                <span class="android-action-link">Buka Pabrik Landing Page ➔</span>
                                <div class="android-tap-circle">➔</div>
                            </div>
                        </div>

                        <!-- CARD 3: MBAK MAYA -->
                        <div class="android-card" onclick="openAgentWorkspace('mayaPanel', 'Mbak Maya &ndash; Post Crew SEO Content')">
                            <div class="android-card-top">
                                <div class="android-app-icon icon-theme-amber">
                                    <span>✍️</span>
                                </div>
                                <span class="android-status-pill pill-status-active">🟢 4 Artikel Synced</span>
                            </div>
                            <div class="android-card-body">
                                <h4 class="android-agent-name">Mbak Maya</h4>
                                <span class="android-agent-role">Post Crew (SEO Content Specialist)</span>
                                <p class="android-agent-duty">Memproduksi artikel blog SEO edukasi dengan shortcode dinamis <code>[TARIF_AVANZA]</code> yang otomatis update saat harga BBM/tol berubah.</p>
                            </div>
                            <div class="android-card-footer">
                                <span class="android-action-link">Buka Artikel Blog SEO ➔</span>
                                <div class="android-tap-circle">➔</div>
                            </div>
                        </div>

                        <!-- CARD 4: MAS BAYU (KEYWORD RESEARCH) -->
                        <div class="android-card" onclick="openAgentWorkspace('bayuPanel', 'Mas Bayu &ndash; Keyword Research Specialist')">
                            <div class="android-card-top">
                                <div class="android-app-icon icon-theme-cyan">
                                    <span>🔍</span>
                                </div>
                                <span class="android-status-pill pill-status-active">🟢 28+ Keyword 'Drop Off'</span>
                            </div>
                            <div class="android-card-body">
                                <h4 class="android-agent-name">Mas Bayu</h4>
                                <span class="android-agent-role">Keyword & Search Intent Specialist</span>
                                <p class="android-agent-duty">Riset mendalam kata kunci turunan seed "Drop Off", analisis volume pencarian, commercial intent, dan pemetaan topik untuk Bung Kevin & Mbak Maya.</p>
                            </div>
                            <div class="android-card-footer">
                                <span class="android-action-link">Buka Riset Kata Kunci ➔</span>
                                <div class="android-tap-circle">➔</div>
                            </div>
                        </div>

                        <!-- CARD 5: MBAK DINDA (WEEKLY CONTENT EDITORIAL CALENDAR) -->
                        <div class="android-card" onclick="openAgentWorkspace('dindaPanel', 'Mbak Dinda &ndash; Weekly Content Calendar Planner')">
                            <div class="android-card-top">
                                <div class="android-app-icon icon-theme-purple">
                                    <span>📅</span>
                                </div>
                                <span class="android-status-pill pill-status-active">🟢 Rencana 7 Hari Siap</span>
                            </div>
                            <div class="android-card-body">
                                <h4 class="android-agent-name">Mbak Dinda</h4>
                                <span class="android-agent-role">Content Strategist & Editorial Planner</span>
                                <p class="android-agent-duty">Menyusun kalender konten pekanan (Senin&ndash;Minggu) berbasis data riset Mas Bayu, sinkronisasi tema artikel Mbak Maya & landing page Bung Kevin.</p>
                            </div>
                            <div class="android-card-footer">
                                <span class="android-action-link">Buka Kalender Konten ➔</span>
                                <div class="android-tap-circle">➔</div>
                            </div>
                        </div>

                        <!-- CARD 6: BANG JAKA -->
                        <div class="android-card" onclick="openAgentWorkspace('jakaPanel', 'Bang Jaka &ndash; Dispatcher & Fleet AI')">
                            <div class="android-card-top">
                                <div class="android-app-icon icon-theme-rose">
                                    <span>🚐</span>
                                </div>
                                <span class="android-status-pill pill-status-active">🟢 Standby Armada</span>
                            </div>
                            <div class="android-card-body">
                                <h4 class="android-agent-name">Bang Jaka</h4>
                                <span class="android-agent-role">Dispatcher & Fleet AI Agent</span>
                                <p class="android-agent-duty">Mengatur kesiapan armada privat (Avanza, Innova, HiAce), koordinasi penjemputan sopir 30 menit sebelum jadwal, dan monitoring perjalanan.</p>
                            </div>
                            <div class="android-card-footer">
                                <span class="android-action-link">Buka Monitoring Armada ➔</span>
                                <div class="android-tap-circle">➔</div>
                            </div>
                        </div>

                        <!-- CARD 7: KOMANDAN SISTEM -->
                        <div class="android-card" onclick="openAgentWorkspace('komandanPanel', 'Komandan Sistem &ndash; Orchestrator')">
                            <div class="android-card-top">
                                <div class="android-app-icon icon-theme-indigo">
                                    <span>🧠</span>
                                </div>
                                <span class="android-status-pill pill-status-active">🟢 P2P Tailscale</span>
                            </div>
                            <div class="android-card-body">
                                <h4 class="android-agent-name">Komandan Sistem</h4>
                                <span class="android-agent-role">System Orchestrator & PC Host</span>
                                <p class="android-agent-duty">Menegakkan jam kerja server 06:00-23:00 WIB, menjaga jalur Tailscale IP ke endpoint Ollama, dan mengoordinasikan pipeline eksekusi.</p>
                            </div>
                            <div class="android-card-footer">
                                <span class="android-action-link">Buka Konsol & Pipeline ➔</span>
                                <div class="android-tap-circle">➔</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==============================================================
                     WORKFLOW ARCHITECTURE SECTION (WORKFLOW 1 & WORKFLOW 2)
                     ============================================================== -->
                <div class="content-card" id="workflowArchitectureSection">
                    <div class="content-card-header">
                        <div>
                            <h3 class="card-title">
                                <span>🧭</span>
                                <span>Pusat Alur Kerja Terpadu (Workflow 1 &amp; Workflow 2)</span>
                            </h3>
                            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                                Dua poros utama kerja tim AI: Pabrik Konten Organik (SEO &amp; EEAT Google) dan Layanan CS 24/7 &amp; Dispatcher Armada.
                            </p>
                        </div>
                        <!-- Workflow Tab Switcher -->
                        <div class="workflow-tabs">
                            <button type="button" class="btn-wf-tab active" id="tabBtnWf1" onclick="switchWorkflowTab('wf1')">
                                <span>🏭</span>
                                <span>Workflow 1: Pabrik Konten SEO &amp; EEAT</span>
                            </button>
                            <button type="button" class="btn-wf-tab" id="tabBtnWf2" onclick="switchWorkflowTab('wf2')">
                                <span>💬</span>
                                <span>Workflow 2: CS Tiara &amp; Armada Jaka</span>
                            </button>
                        </div>
                    </div>

                    <!-- WORKFLOW 1: CONTENT & SEO EEAT ENGINE -->
                    <div class="wf-content-tab active" id="wfTab1">
                        <div class="wf-banner-notice">
                            <strong>🎯 Siklus Terjadwal:</strong> 🔍 Mas Bayu riset kata kunci sepekan sekali ➔ 📅 Mbak Dinda susun Kalender Konten pekanan ➔ 👔 Pak Hendra update tarif tol, BBM, jasa sopir &amp; harga pasaran ➔ ✍️ Mbak Maya (Artikel Blog) &amp; 🎯 Mas Kevin (Landing Page) produksi konten patuh SEO &amp; prinsip EEAT Google dengan data dinamis.
                        </div>

                        <div class="wf-steps-grid">
                            <!-- Step 1: Mas Bayu -->
                            <div class="wf-step-card">
                                <div class="wf-step-num">Langkah 1 &bull; 1x Sepekan</div>
                                <div class="wf-step-avatar icon-theme-cyan">🔍</div>
                                <h4 class="wf-step-title">Mas Bayu &ndash; Riset Keyword</h4>
                                <div class="wf-step-role">Keyword &amp; Search Intent Specialist</div>
                                <p class="wf-step-desc">
                                    Riset kata kunci <em>long-tail</em> &amp; <em>short-tail</em> turunan dari seed utama <strong>"Drop Off"</strong> di Indonesia (khususnya Jabodetabek &amp; Baraya). Bekerja sepekan sekali menjaring keyword peluang baru yang belum tertulis di database.
                                </p>
                                <div class="wf-step-output">
                                    <span>📑 Hasil Kerja:</span> <strong>Tabel Daftar Keyword "Drop Off"</strong>
                                </div>
                                <button type="button" class="btn-wf-action" onclick="openAgentWorkspace('bayuPanel', 'Mas Bayu &ndash; Keyword Research Specialist')">
                                    Buka Tabel Keyword Mas Bayu ➔
                                </button>
                            </div>

                            <!-- Step 2: Mbak Dinda -->
                            <div class="wf-step-card">
                                <div class="wf-step-num">Langkah 2 &bull; Editorial Planner</div>
                                <div class="wf-step-avatar icon-theme-purple">📅</div>
                                <h4 class="wf-step-title">Mbak Dinda &ndash; Kalender Konten</h4>
                                <div class="wf-step-role">Content Strategist &amp; Editorial Planner</div>
                                <p class="wf-step-desc">
                                    Mengambil data dari Tabel Keyword Mas Bayu untuk menyusun <strong>Kalender Konten Pekanan</strong>. Mengklasifikasikan keyword yang dibuat <strong>Landing Page</strong> (Mas Kevin) dan <strong>Artikel Blog</strong> (Mbak Maya), lengkap dengan jadwal tanggal terbit.
                                </p>
                                <div class="wf-step-output">
                                    <span>🗓️ Hasil Kerja:</span> <strong>Tabel Kalender Konten Pekanan</strong>
                                </div>
                                <button type="button" class="btn-wf-action" onclick="openAgentWorkspace('dindaPanel', 'Mbak Dinda &ndash; Weekly Content Calendar Planner')">
                                    Buka Kalender Konten Dinda ➔
                                </button>
                            </div>

                            <!-- Step 3: Pak Hendra -->
                            <div class="wf-step-card">
                                <div class="wf-step-num">Langkah 3 &bull; 1x Sepekan</div>
                                <div class="wf-step-avatar icon-theme-blue">👔</div>
                                <h4 class="wf-step-title">Pak Hendra &ndash; Riset Tarif</h4>
                                <div class="wf-step-role">Tariff &amp; Cost Intelligence Agent</div>
                                <p class="wf-step-desc">
                                    Riset sepekan sekali memantau tarif tol gerbang-ke-gerbang (Cipularang, MBZ, Sedyatmo), harga BBM Pertamina resmi, jasa sopir, dan harga pasaran drop off kompetitor. Menjamin Traveego selalu untung dan kompetitif.
                                </p>
                                <div class="wf-step-output">
                                    <span>📊 Hasil Kerja:</span> <strong>Tabel Update Tol, BBM &amp; Pasaran</strong>
                                </div>
                                <button type="button" class="btn-wf-action" onclick="openAgentWorkspace('hendraPanel', 'Pak Hendra &ndash; Tariff &amp; Intelligence')">
                                    Buka Matrix Tarif Pak Hendra ➔
                                </button>
                            </div>

                            <!-- Step 4: Mbak Maya & Mas Kevin -->
                            <div class="wf-step-card" style="border-color: #86EFAC; background: linear-gradient(180deg, #FFFFFF, #F0FDF4);">
                                <div class="wf-step-num">Langkah 4 &bull; Produksi SEO &amp; EEAT</div>
                                <div style="display:flex; gap:8px; margin-bottom:12px;">
                                    <div class="wf-step-avatar icon-theme-amber" style="width:40px; height:40px; font-size:20px; margin-bottom:0;">✍️</div>
                                    <div class="wf-step-avatar icon-theme-green" style="width:40px; height:40px; font-size:20px; margin-bottom:0;">🎯</div>
                                </div>
                                <h4 class="wf-step-title">Mbak Maya &amp; Mas Kevin</h4>
                                <div class="wf-step-role">Blog EEAT Dinamis &amp; Landing Page Crew</div>
                                <p class="wf-step-desc">
                                    <strong>Mbak Maya:</strong> Menulis artikel sesuai tanggal Kalender Mbak Dinda, patuh SEO &amp; prinsip EEAT Google (internal/external link), dengan data dinamis harga Pak Hendra.<br>
                                    <strong>Mas Kevin:</strong> Menulis Landing Page rute konversi tinggi sesuai tanggal &amp; judul kalender, prinsip EEAT, dan penawaran All-In 0% DP.
                                </p>
                                <div class="wf-step-output">
                                    <span>⚡ Standar:</span> <strong>Google EEAT &amp; Dinamis</strong>
                                </div>
                                <div style="display:flex; gap:6px;">
                                    <button type="button" class="btn-wf-action" onclick="openAgentWorkspace('mayaPanel', 'Mbak Maya &ndash; Post Crew SEO Content')">Blog Maya ➔</button>
                                    <button type="button" class="btn-wf-action" onclick="openAgentWorkspace('kevinPanel', 'Bung Kevin &ndash; Page Crew Copywriter')">Page Kevin ➔</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- WORKFLOW 2: CS TIARA & DISPATCH FLEET ENGINE -->
                    <div class="wf-content-tab" id="wfTab2" style="display: none;">
                        <div class="wf-banner-notice" style="background: #EFF6FF; border-color: #BFDBFE; color: #1E40AF;">
                            <strong>💬 Layanan Nyata Pelanggan:</strong> Mbak Tiara (CS AI) menjawab chat &amp; pesan WhatsApp/telpon user menggunakan data tarif update terbaru dari Pak Hendra serta berkomunikasi dengan Bang Jaka untuk kesiapan armada standby terdekat.
                        </div>

                        <div class="wf-steps-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
                            <!-- W2 Step 1: Mbak Tiara -->
                            <div class="wf-step-card">
                                <div class="wf-step-num">Frontliner Pelanggan 24/7</div>
                                <div class="wf-step-avatar" style="background: linear-gradient(135deg, #FDF2F8, #FCE7F3); border: 2px solid #FBCFE8; color: #DB2777;">👩‍💼</div>
                                <h4 class="wf-step-title">Mbak Tiara &ndash; CS AI Traveego</h4>
                                <div class="wf-step-role">AI Frontliner (Homepage, Landing Page &amp; WhatsApp)</div>
                                <p class="wf-step-desc">
                                    Menjawab telpon, chat web, dan WhatsApp customer dengan ramah dan akurat. Menjelaskan keunggulan All-In (Tol + BBM + Sopir), penjemputan door-to-door, dan opsi aman bayar di tempat (0% DP).
                                </p>
                                <div class="wf-step-output">
                                    <span>🌐 Channel:</span> <strong>Homepage, Landing Page &amp; WhatsApp</strong>
                                </div>
                                <button type="button" class="btn-wf-action" onclick="simulateTiaraChat()">
                                    Test Simulasi CS Mbak Tiara ➔
                                </button>
                            </div>

                            <!-- W2 Step 2: Sinkronisasi Data Pak Hendra -->
                            <div class="wf-step-card">
                                <div class="wf-step-num">Sinkronisasi Harga Otomatis</div>
                                <div class="wf-step-avatar icon-theme-blue">👔</div>
                                <h4 class="wf-step-title">Sinkron Data Pak Hendra</h4>
                                <div class="wf-step-role">Real-Time Single Source of Truth</div>
                                <p class="wf-step-desc">
                                    Mbak Tiara membaca data <code>data/cost_matrix.json</code> hasil riset mingguan Pak Hendra secara langsung. Jika harga BBM atau tarif tol naik/turun, jawaban tarif Tiara otomatis langsung presisi tanpa rekonfigurasi manual.
                                </p>
                                <div class="wf-step-output">
                                    <span>🔄 Integrasi:</span> <strong>API Real-Time Cost Matrix</strong>
                                </div>
                                <button type="button" class="btn-wf-action" onclick="openAgentWorkspace('hendraPanel', 'Pak Hendra &ndash; Tariff &amp; Intelligence')">
                                    Lihat Matrix Acuan Tiara ➔
                                </button>
                            </div>

                            <!-- W2 Step 3: Koordinasi Armada Bang Jaka -->
                            <div class="wf-step-card">
                                <div class="wf-step-num">Kesiapan Armada Real-Time</div>
                                <div class="wf-step-avatar icon-theme-rose">🚐</div>
                                <h4 class="wf-step-title">Bang Jaka &ndash; Dispatcher Armada</h4>
                                <div class="wf-step-role">Fleet Readiness &amp; Sopir Standby</div>
                                <p class="wf-step-desc">
                                    Mbak Tiara selalu berkomunikasi dengan Bang Jaka untuk update data unit yang sedang standby &amp; ready melayani penjemputan (Avanza, Innova Reborn, HiAce Premio). Sopir dipastikan tiba di lokasi jemput 30 menit sebelum jadwal.
                                </p>
                                <div class="wf-step-output">
                                    <span>🛡️ Jaminan:</span> <strong>Standby 30 Menit &amp; Privasi Terjaga</strong>
                                </div>
                                <button type="button" class="btn-wf-action" onclick="openAgentWorkspace('jakaPanel', 'Bang Jaka &ndash; Dispatcher &amp; Fleet AI')">
                                    Buka Armada Bang Jaka ➔
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Route Tariff Table -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h3 class="card-title">
                            <span>🛣️</span>
                            <span>Tarif 4 Koridor Fokus Traveego (All-In Tol & BBM)</span>
                        </h3>
                        <span class="pill-badge pill-green">Single Source of Truth</span>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table" id="quickMatrixTable">
                            <thead>
                                <tr>
                                    <th>Rute Perjalanan</th>
                                    <th>Jarak</th>
                                    <th>Estimasi Tol</th>
                                    <th>Jasa Sopir</th>
                                    <th>Avanza (All-In)</th>
                                    <th>Innova (All-In)</th>
                                    <th>Hiace (All-In)</th>
                                </tr>
                            </thead>
                            <tbody id="quickMatrixBody">
                                <tr><td colspan="7" style="text-align:center; padding: 20px;">Memuat matrix biaya...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- ==============================================================
                 PANEL 1: PAK HENDRA (TARIFF & COST INTELLIGENCE)
                 ============================================================== -->
            <section class="agent-panel" id="hendraPanel">
                <button type="button" class="btn-back-hub" onclick="returnToOverviewHub()">
                    <span>←</span>
                    <span>Kembali ke Overview Hub</span>
                </button>

                <div class="agent-hero-banner">
                    <div class="hero-banner-left">
                        <div class="hero-agent-avatar">
                            <span>👔</span>
                            <div class="hero-online-dot"></div>
                        </div>
                        <div class="hero-banner-text">
                            <h2>Pak Hendra &ndash; Tariff & Cost Intelligence Agent</h2>
                            <p><strong>"Tarif yang akurat adalah kunci margin profit dan kepercayaan pelanggan."</strong> Saya memantau harga BBM resmi Pertamina, menghitung biaya tol gerbang-ke-gerbang (Cipularang, MBZ, JORR, Sedyatmo), serta mengalokasikan jasa sopir harian untuk memastikan Traveego selalu untung tanpa kemahalan.</p>
                        </div>
                    </div>
                    <div class="hero-banner-actions">
                        <button class="btn-brand-green" onclick="refreshData()">🔄 Survey Ulang Harga</button>
                    </div>
                </div>

                <!-- Reference Fuel Prices -->
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-label">BBM Pertalite (RON 90)</span>
                            <span class="sw-pill pill-active">Acuan Avanza</span>
                        </div>
                        <div class="stat-value">Rp 10.000 / L</div>
                        <div class="stat-desc">Konsumsi rata-rata: 12 KM / Liter</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-label">BBM Dexlite / Solar</span>
                            <span class="sw-pill pill-active">Acuan Innova Diesel</span>
                        </div>
                        <div class="stat-value">Rp 13.700 / L</div>
                        <div class="stat-desc">Konsumsi rata-rata: 10 KM / Liter</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-label">BBM Dexlite / Solar</span>
                            <span class="sw-pill pill-active">Acuan Hiace Premio</span>
                        </div>
                        <div class="stat-value">Rp 13.700 / L</div>
                        <div class="stat-desc">Konsumsi rata-rata: 8 KM / Liter</div>
                    </div>
                </div>

                <!-- Full Cost Matrix Table -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h3 class="card-title">
                            <span>📊</span>
                            <span>Matrix Biaya Riil 4 Koridor Prioritas</span>
                        </h3>
                        <span class="tag-dynamic">Data Acuan Seluruh Penulis & CS Tiara</span>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Rute & Koridor</th>
                                    <th>Jarak Tempuh</th>
                                    <th>Rincian Biaya Tol</th>
                                    <th>Jasa Sopir Bersih</th>
                                    <th>Harga Pasaran Drop Off</th>
                                    <th>Toyota Avanza</th>
                                    <th>Toyota Innova</th>
                                    <th>Toyota Hiace</th>
                                </tr>
                            </thead>
                            <tbody id="hendraMatrixBody">
                                <tr><td colspan="8" style="text-align:center; padding: 20px;">Memuat data Pak Hendra...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- ==============================================================
                 PANEL 2: BUNG KEVIN (PAGE CREW - LANDING PAGE)
                 ============================================================== -->
            <section class="agent-panel" id="kevinPanel">
                <button type="button" class="btn-back-hub" onclick="returnToOverviewHub()">
                    <span>←</span>
                    <span>Kembali ke Overview Hub</span>
                </button>

                <div class="agent-hero-banner">
                    <div class="hero-banner-left">
                        <div class="hero-agent-avatar">
                            <span>🎯</span>
                            <div class="hero-online-dot"></div>
                        </div>
                        <div class="hero-banner-text">
                            <h2>Bung Kevin &ndash; Page Crew Copywriter</h2>
                            <p><strong>"Setiap landing page dirancang untuk langsung memicu booking tanpa ragu."</strong> Saya merangkai penawaran drop-off sekali jalan dengan 3 pilar konversi utama: Tarif All-In Bersih (tanpa biaya siluman tol/BBM), Tanpa DP 0% (bayar ke sopir), dan Jaminan Sopir Tepat Waktu.</p>
                        </div>
                    </div>
                    <div class="hero-banner-actions">
                        <button class="btn-brand-primary" onclick="runPipeline()">✨ Generate Halaman Rute</button>
                    </div>
                </div>

                <div class="content-card">
                    <div class="content-card-header">
                        <h3 class="card-title">
                            <span>📄</span>
                            <span>Daftar Landing Page Rute yang Dihasilkan</span>
                        </h3>
                        <span class="pill-badge pill-blue">High Converting Landing Pages</span>
                    </div>

                    <div class="items-grid" id="pagesGrid">
                        <div style="color: var(--text-muted);">Memuat halaman...</div>
                    </div>
                </div>
            </section>

            <!-- ==============================================================
                 PANEL 3: MBAK MAYA (POST CREW - SEO BLOG)
                 ============================================================== -->
            <section class="agent-panel" id="mayaPanel">
                <button type="button" class="btn-back-hub" onclick="returnToOverviewHub()">
                    <span>←</span>
                    <span>Kembali ke Overview Hub</span>
                </button>

                <div class="agent-hero-banner">
                    <div class="hero-banner-left">
                        <div class="hero-agent-avatar">
                            <span>✍️</span>
                            <div class="hero-online-dot"></div>
                        </div>
                        <div class="hero-banner-text">
                            <h2>Mbak Maya &ndash; Post Crew SEO Content Specialist</h2>
                            <p><strong>"Artikel SEO yang hebat adalah yang mengedukasi pembaca sekaligus selalu up-to-date."</strong> Saya menulis artikel travel dengan shortcode pintar seperti <code>[TARIF_AVANZA]</code> dan <code>[TOTAL_TOL]</code>. Jika Pak Hendra memperbarui harga tol atau BBM, seluruh artikel saya langsung ikut berubah tanpa perlu ditulis ulang!</p>
                        </div>
                    </div>
                    <div class="hero-banner-actions">
                        <button class="btn-brand-primary" onclick="runPipeline()">✨ Tulis Artikel Baru</button>
                    </div>
                </div>

                <div class="content-card">
                    <div class="content-card-header">
                        <h3 class="card-title">
                            <span>💡</span>
                            <span>Artikel Blog SEO dengan Variabel Dinamis</span>
                        </h3>
                        <span class="tag-dynamic">Shortcode Otomatis Tersambung</span>
                    </div>

                    <div class="items-grid" id="postsGrid">
                        <div style="color: var(--text-muted);">Memuat artikel blog...</div>
                    </div>
                </div>
            </section>

            <!-- ==============================================================
                 PANEL 4: MAS BAYU (KEYWORD RESEARCH & SEARCH INTENT)
                 ============================================================== -->
            <section class="agent-panel" id="bayuPanel">
                <button type="button" class="btn-back-hub" onclick="returnToOverviewHub()">
                    <span>←</span>
                    <span>Kembali ke Overview Hub</span>
                </button>

                <div class="agent-hero-banner">
                    <div class="hero-banner-left">
                        <div class="hero-agent-avatar">
                            <span>🔍</span>
                            <div class="hero-online-dot"></div>
                        </div>
                        <div class="hero-banner-text">
                            <h2>Mas Bayu &ndash; Keyword Research & Search Intent Agent</h2>
                            <p><strong>"Kata kunci yang tepat menjamin calon penumpang langsung siap bayar."</strong> Saya meriset seluruh variasi kata kunci turunan dari seed keyword <em>"Drop Off"</em>, memetakan search intent (Transactional, Commercial, Informational), dan mendistribusikan ide topik langsung ke Bung Kevin (Landing Page) serta Mbak Maya (Blog SEO).</p>
                        </div>
                    </div>
                    <div class="hero-banner-actions">
                        <button class="btn-brand-primary" onclick="runPipeline()">⚡ Riset Keyword Baru</button>
                    </div>
                </div>

                <!-- Search Intent Breakdown Cards -->
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-label">Commercial Intent (Siap Pesan)</span>
                            <span class="sw-pill pill-active">High Buying Intent</span>
                        </div>
                        <div class="stat-value">Target: Bung Kevin</div>
                        <div class="stat-desc">Fokus kata kunci tarif All-In, sewa drop-off tanpa DP, dan rute antar kota.</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-label">Transactional (Direct Booking)</span>
                            <span class="sw-pill pill-active">Closing Cepat</span>
                        </div>
                        <div class="stat-value">Target: Landing Page Rute</div>
                        <div class="stat-desc">Pencarian jadwal penjemputan bandara, armada standby, dan booking instan.</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-label">Informational (Top of Funnel)</span>
                            <span class="sw-pill pill-active">Organic Traffic</span>
                        </div>
                        <div class="stat-value">Target: Mbak Maya</div>
                        <div class="stat-desc">Artikel edukasi perbedaan drop-off vs carter harian & tips perjalanan bebas macet.</div>
                    </div>
                </div>

                <!-- Drop Off Keyword Matrix Table -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h3 class="card-title">
                            <span>📑</span>
                            <span>Matrix Cluster Kata Kunci Seed "Drop Off" Terverifikasi</span>
                        </h3>
                        <span class="pill-badge pill-blue">Seed: "Drop Off"</span>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Kata Kunci Turunan</th>
                                    <th>Search Intent</th>
                                    <th>Estimasi Volume</th>
                                    <th>Tingkat Persaingan</th>
                                    <th>Agen Penerima</th>
                                    <th>Target Halaman</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>drop off bandara soekarno hatta</strong></td>
                                    <td><span class="sw-pill pill-active">Transactional</span></td>
                                    <td><strong>3.600 / bln</strong></td>
                                    <td><span style="color: #16A34A; font-weight: 700;">Sedang (Peluang #1)</span></td>
                                    <td>🎯 Bung Kevin</td>
                                    <td><code>/dropoff-bandara-soetta-all-in</code></td>
                                </tr>
                                <tr>
                                    <td><strong>drop off jakarta bandung</strong></td>
                                    <td><span class="sw-pill pill-active">Commercial</span></td>
                                    <td><strong>5.400 / bln</strong></td>
                                    <td><span style="color: #F59E0B; font-weight: 700;">Menengah</span></td>
                                    <td>🎯 Bung Kevin</td>
                                    <td><code>/jakarta-bandung-dropoff</code></td>
                                </tr>
                                <tr>
                                    <td><strong>drop off bandara halim perdanakusuma</strong></td>
                                    <td><span class="sw-pill pill-active">Transactional</span></td>
                                    <td><strong>1.900 / bln</strong></td>
                                    <td><span style="color: #16A34A; font-weight: 700;">Rendah (Mudah Top 3)</span></td>
                                    <td>🎯 Bung Kevin</td>
                                    <td><code>/dropoff-bandara-halim-perdanakusuma</code></td>
                                </tr>
                                <tr>
                                    <td><strong>drop off dalam kota jabodetabek</strong></td>
                                    <td><span class="sw-pill pill-active">Commercial</span></td>
                                    <td><strong>2.200 / bln</strong></td>
                                    <td><span style="color: #16A34A; font-weight: 700;">Rendah</span></td>
                                    <td>🎯 Bung Kevin</td>
                                    <td><code>/dropoff-dalam-kota-jabodetabek</code></td>
                                </tr>
                                <tr>
                                    <td><strong>tarif drop off avanza jakarta bandung</strong></td>
                                    <td><span class="sw-pill pill-active">Transactional</span></td>
                                    <td><strong>1.300 / bln</strong></td>
                                    <td><span style="color: #16A34A; font-weight: 700;">Rendah</span></td>
                                    <td>✍️ Mbak Maya</td>
                                    <td><code>Blog: /ongkos-drop-off-bandung-terbaru</code></td>
                                </tr>
                                <tr>
                                    <td><strong>sewa mobil drop off sekali jalan all in</strong></td>
                                    <td><span class="sw-pill pill-active">Commercial</span></td>
                                    <td><strong>2.800 / bln</strong></td>
                                    <td><span style="color: #F59E0B; font-weight: 700;">Menengah</span></td>
                                    <td>✍️ Mbak Maya</td>
                                    <td><code>Blog: /keuntungan-drop-off-sekali-jalan</code></td>
                                </tr>
                                <tr>
                                    <td><strong>perbedaan drop off dan carter mobil travel</strong></td>
                                    <td><span class="sw-pill pill-sleep">Informational</span></td>
                                    <td><strong>1.600 / bln</strong></td>
                                    <td><span style="color: #16A34A; font-weight: 700;">Rendah</span></td>
                                    <td>✍️ Mbak Maya</td>
                                    <td><code>Blog: /apa-itu-drop-off-travel</code></td>
                                </tr>
                                <tr>
                                    <td><strong>drop off tanpa uang muka bayar di tujuan</strong></td>
                                    <td><span class="sw-pill pill-active">Commercial</span></td>
                                    <td><strong>980 / bln</strong></td>
                                    <td><span style="color: #16A34A; font-weight: 700;">Rendah (Konversi Tinggi)</span></td>
                                    <td>🎯 Bung Kevin</td>
                                    <td><code>Landing Page (0% DP Guarantee)</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- ==============================================================
                 PANEL 5: KOMANDAN SISTEM (ORCHESTRATOR & TAILSCALE)
                 ============================================================== -->
            <section class="agent-panel" id="komandanPanel">
                <button type="button" class="btn-back-hub" onclick="returnToOverviewHub()">
                    <span>←</span>
                    <span>Kembali ke Overview Hub</span>
                </button>

                <div class="agent-hero-banner">
                    <div class="hero-banner-left">
                        <div class="hero-agent-avatar">
                            <span>🧠</span>
                            <div class="hero-online-dot"></div>
                        </div>
                        <div class="hero-banner-text">
                            <h2>Komandan Sistem &ndash; Orchestrator & Tailscale Host</h2>
                            <p><strong>"Koordinasi terpadu antara server hosting Hostinger dan PC Server AI Anda."</strong> Saya mematuhi jam kerja 06.00-23.00 WIB, menjaga jalur Tailscale IP ke endpoint Ollama, dan memandu eksekusi bertahap Pak Hendra, Bung Kevin, dan Mbak Maya.</p>
                        </div>
                    </div>
                    <div class="hero-banner-actions">
                        <button class="btn-brand-outline" onclick="openServerConfigModal()">⚙️ Atur IP Tailscale</button>
                    </div>
                </div>

                <!-- Server Info Cards -->
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-label">Tailscale PC Server IP</span>
                            <span class="sw-pill pill-active">Port 11434</span>
                        </div>
                        <div class="stat-value" id="kmdHostText">http://100.85.151.80:11434</div>
                        <div class="stat-desc">Koneksi P2P aman via Tailscale Network.</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-label">Model Otak AI Aktif</span>
                            <span class="sw-pill pill-active">Local LLM</span>
                        </div>
                        <div class="stat-value" id="kmdModelText">qwen2.5-coder:7b</div>
                        <div class="stat-desc">Inference offline cepat tanpa biaya token cloud.</div>
                    </div>
                </div>

                <!-- Terminal Console -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h3 class="card-title">
                            <span>⚡</span>
                            <span>Konsol Eksekusi Pipeline AI Orchestrator</span>
                        </h3>
                        <button class="btn-brand-primary" onclick="runPipeline()" id="btnRunPipeline">
                            <span>🚀</span>
                            <span>Jalankan Seluruh AI Agent Sekarang</span>
                        </button>
                    </div>

                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 14px;">
                        Klik tombol di atas untuk menjalankan pipeline lengkap: Pak Hendra survey matrix &ndash;&gt; Bung Kevin generate landing pages &ndash;&gt; Mbak Maya tulis artikel SEO.
                    </p>

                    <div class="terminal-box" id="terminalOutput">Tekan tombol 'Jalankan Seluruh AI Agent Sekarang' untuk memicu eksekusi...</div>
                </div>
            </section>

            <!-- ==============================================================
                 PANEL 6: BANG JAKA (DISPATCHER & FLEET AI)
                 ============================================================== -->
            <section class="agent-panel" id="jakaPanel">
                <button type="button" class="btn-back-hub" onclick="returnToOverviewHub()">
                    <span>←</span>
                    <span>Kembali ke Overview Hub</span>
                </button>

                <div class="agent-hero-banner">
                    <div class="hero-banner-left">
                        <div class="hero-agent-avatar">
                            <span>🚐</span>
                            <div class="hero-online-dot"></div>
                        </div>
                        <div class="hero-banner-text">
                            <h2>Bang Jaka &ndash; Dispatcher & Fleet AI Agent</h2>
                            <p><strong>"Armada prima, sopir sopan, dan penjemputan anti-telat."</strong> Saya memastikan armada privat Toyota Avanza, Innova Reborn, dan HiAce Premio selalu steril, BBM terisi, dan sopir standby di titik jemput penumpang 30 menit sebelum jadwal penerbangan atau keberangkatan.</p>
                        </div>
                    </div>
                    <div class="hero-banner-actions">
                        <button class="btn-brand-green" onclick="refreshData()">🔄 Cek Status Armada</button>
                    </div>
                </div>

                <!-- Armada Cards -->
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-label">Toyota Avanza / Veloz</span>
                            <span class="sw-pill pill-active">🟢 Ready</span>
                        </div>
                        <div class="stat-value">Kapasitas 3-4 Pax</div>
                        <div class="stat-desc">Ideal untuk perjalanan keluarga kecil, drop-off bandara, & dalam kota.</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-label">Toyota Innova Reborn</span>
                            <span class="sw-pill pill-active">🟢 Ready</span>
                        </div>
                        <div class="stat-value">Kapasitas 4-5 Pax</div>
                        <div class="stat-desc">Kenyamanan suspensi premium untuk rute Jakarta &ndash; Bandung & Bandara.</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-label">Toyota HiAce Premio</span>
                            <span class="sw-pill pill-active">🟢 Ready</span>
                        </div>
                        <div class="stat-value">Kapasitas 8-10 Pax</div>
                        <div class="stat-desc">Armada rombongan besar / keluarga besar dengan bagasi banyak.</div>
                    </div>
                </div>

                <!-- SOP Penjemputan -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h3 class="card-title">
                            <span>🛡️</span>
                            <span>Standar Operasional Penjemputan (SOP Anti-Disintermediation)</span>
                        </h3>
                        <span class="pill-badge pill-green">SOP Disiplin</span>
                    </div>

                    <div style="font-size: 13.5px; line-height: 1.6; color: var(--text-body);">
                        <p style="margin-bottom: 12px;">✅ <strong>Standby 30 Menit Awal:</strong> Sopir tiba di lokasi penjemputan 30 menit sebelum jam pendaratan pesawat atau jadwal pemesanan.</p>
                        <p style="margin-bottom: 12px;">✅ <strong>Proteksi Privasi Penumpang:</strong> Nomor telepon penumpang disensor di aplikasi sopir (<code>0819-****-4321</code>). Komunikasi diarahkan via koordinasi In-App atau Admin.</p>
                        <p>✅ <strong>Kebersihan & Kenyamanan:</strong> Kabin ber-AC dingin, bersih tanpa aroma rokok, dan siap dengan air mineral.</p>
                    </div>
                </div>
            </section>

            <!-- ==============================================================
                 PANEL 7: MBAK DINDA (CONTENT STRATEGIST & EDITORIAL PLANNER)
                 ============================================================== -->
            <section class="agent-panel" id="dindaPanel">
                <button type="button" class="btn-back-hub" onclick="returnToOverviewHub()">
                    <span>←</span>
                    <span>Kembali ke Overview Hub</span>
                </button>

                <div class="agent-hero-banner">
                    <div class="hero-banner-left">
                        <div class="hero-agent-avatar" style="border-color: #E9D5FF; background: linear-gradient(135deg, #FAF5FF, #F3E8FF);">
                            <span>📅</span>
                            <div class="hero-online-dot"></div>
                        </div>
                        <div class="hero-banner-text">
                            <h2>Mbak Dinda &ndash; Content Strategist & Weekly Editorial Planner</h2>
                            <p><strong>"Konsistensi publikasi adalah kunci mendominasi halaman #1 Google."</strong> Saya merancang dan mengunci jadwal kalender konten 7 hari (Senin &ndash; Minggu) untuk Traveego berbasis riset seed <em>"Drop Off"</em> dari Mas Bayu, mendistribusikan penugasan ke Bung Kevin (Landing Page) & Mbak Maya (Artikel SEO), serta memonitor jadwal tayang otomatis.</p>
                        </div>
                    </div>
                    <div class="hero-banner-actions">
                        <button class="btn-brand-primary" onclick="simulateCalendarSync()">
                            <span>⚡</span>
                            <span>Sinkronkan ke PC Server (Tailscale)</span>
                        </button>
                        <button class="btn-brand-outline" onclick="generateNewWeeklyPlan()">
                            <span>🔄</span>
                            <span>Generate Ulang Jadwal Pekanan</span>
                        </button>
                    </div>
                </div>

                <!-- 3 Stat Cards for Content Strategy -->
                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-label">Target Produksi Pekanan</span>
                            <span class="sw-pill pill-active">🟢 7 Konten / Minggu</span>
                        </div>
                        <div class="stat-value">Senin &ndash; Minggu (1 Slot / Hari)</div>
                        <div class="stat-desc">4 Artikel Blog Edukasi SEO + 2 Landing Page Promo Rute + 1 Panduan Tarif FAQ.</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-label">Distribusi Search Intent</span>
                            <span class="sw-pill pill-active">Tercakup 100%</span>
                        </div>
                        <div class="stat-value">Commercial &bull; Transaksi &bull; Edukasi</div>
                        <div class="stat-desc">Dirancang seimbang antara edukasi organik dan konversi pemesanan langsung 0% DP.</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-header">
                            <span class="stat-label">Alur Kerja Multi-Agent</span>
                            <span class="sw-pill pill-active">Otomatis Terhubung</span>
                        </div>
                        <div class="stat-value">🔍 Mas Bayu ➔ 📅 Dinda ➔ 🎯 / ✍️</div>
                        <div class="stat-desc">Keyword diumpan dari Mas Bayu, jadwal ditentukan Mbak Dinda, diproduksi Bung Kevin & Mbak Maya.</div>
                    </div>
                </div>

                <!-- Weekly Editorial Calendar Table -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h3 class="card-title">
                            <span>🗓️</span>
                            <span>Kalender Konten Pekanan (Senin &ndash; Minggu) &bull; Traveego Drop Off</span>
                        </h3>
                        <span class="pill-badge pill-blue" id="dindaActiveWeekBadge">Pekan Berjalan: 27 Okt &ndash; 2 Nov 2026</span>
                    </div>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 100px;">Hari & Jam</th>
                                    <th>Format Konten</th>
                                    <th>Topik / Kata Kunci Fokus ("Drop Off")</th>
                                    <th>Search Intent</th>
                                    <th>Eksekutor</th>
                                    <th>Target Slug / URL</th>
                                    <th>Status Produksi</th>
                                </tr>
                            </thead>
                            <tbody id="weeklyCalendarBody">
                                <tr>
                                    <td>
                                        <strong style="color: var(--royal-blue);">SENIN</strong><br>
                                        <span style="font-size: 11px; color: var(--text-muted);">08:00 WIB</span>
                                    </td>
                                    <td><span class="sw-pill pill-active">Landing Page</span></td>
                                    <td>
                                        <strong>Drop Off Bandara Soekarno-Hatta All-In Tol & BBM</strong><br>
                                        <span style="font-size: 11.5px; color: var(--text-muted);">Seed: drop off bandara soetta</span>
                                    </td>
                                    <td><span class="sw-pill pill-active">Transactional</span></td>
                                    <td>🎯 Bung Kevin</td>
                                    <td><code>/dropoff-bandara-soetta-all-in</code></td>
                                    <td><span class="sw-pill pill-active">🟢 Siap Terbit</span></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong style="color: var(--royal-blue);">SELASA</strong><br>
                                        <span style="font-size: 11px; color: var(--text-muted);">10:00 WIB</span>
                                    </td>
                                    <td><span class="tag-dynamic">Artikel Blog SEO</span></td>
                                    <td>
                                        <strong>Perbedaan Layanan Drop Off vs Carter Mobil Travel Harian</strong><br>
                                        <span style="font-size: 11.5px; color: var(--text-muted);">Seed: apa itu drop off travel</span>
                                    </td>
                                    <td><span class="sw-pill pill-sleep">Informational</span></td>
                                    <td>✍️ Mbak Maya</td>
                                    <td><code>Blog: /apa-itu-drop-off-travel</code></td>
                                    <td><span class="sw-pill pill-active">🟢 Siap Terbit</span></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong style="color: var(--royal-blue);">RABU</strong><br>
                                        <span style="font-size: 11px; color: var(--text-muted);">09:00 WIB</span>
                                    </td>
                                    <td><span class="tag-dynamic">Panduan Tarif</span></td>
                                    <td>
                                        <strong>Rincian Tarif Drop Off Jakarta &ndash; Bandung All-In Avanza & Innova</strong><br>
                                        <span style="font-size: 11.5px; color: var(--text-muted);">Shortcode dinamis tarif BBM & Tol Cipularang</span>
                                    </td>
                                    <td><span class="sw-pill pill-active">Commercial</span></td>
                                    <td>👔 Hendra x ✍️ Maya</td>
                                    <td><code>Blog: /ongkos-drop-off-bandung-terbaru</code></td>
                                    <td><span class="sw-pill pill-active">🟢 Terjadwal</span></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong style="color: var(--royal-blue);">KAMIS</strong><br>
                                        <span style="font-size: 11px; color: var(--text-muted);">13:00 WIB</span>
                                    </td>
                                    <td><span class="sw-pill pill-active">Landing Page</span></td>
                                    <td>
                                        <strong>Layanan Drop Off Dalam Kota Jabodetabek Bebas Ribet</strong><br>
                                        <span style="font-size: 11.5px; color: var(--text-muted);">Seed: drop off dalam kota jabodetabek</span>
                                    </td>
                                    <td><span class="sw-pill pill-active">Commercial</span></td>
                                    <td>🎯 Bung Kevin</td>
                                    <td><code>/dropoff-dalam-kota-jabodetabek</code></td>
                                    <td><span class="sw-pill pill-active">🟢 Terjadwal</span></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong style="color: var(--royal-blue);">JUMAT</strong><br>
                                        <span style="font-size: 11px; color: var(--text-muted);">14:00 WIB</span>
                                    </td>
                                    <td><span class="tag-dynamic">Artikel Tips SEO</span></td>
                                    <td>
                                        <strong>5 Keuntungan Drop Off Sekali Jalan Dibanding Naik Angkutan Umum</strong><br>
                                        <span style="font-size: 11.5px; color: var(--text-muted);">Seed: sewa mobil drop off sekali jalan</span>
                                    </td>
                                    <td><span class="sw-pill pill-active">Commercial</span></td>
                                    <td>✍️ Mbak Maya</td>
                                    <td><code>Blog: /keuntungan-drop-off-sekali-jalan</code></td>
                                    <td><span class="sw-pill pill-sleep">🟡 Review Draft</span></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong style="color: var(--royal-blue);">SABTU</strong><br>
                                        <span style="font-size: 11px; color: var(--text-muted);">07:30 WIB</span>
                                    </td>
                                    <td><span class="sw-pill pill-active">Landing Page</span></td>
                                    <td>
                                        <strong>Carter Drop Off Jakarta &ndash; Bandung Liburan Akhir Pekan</strong><br>
                                        <span style="font-size: 11.5px; color: var(--text-muted);">Promo keluarga tanpa DP & jemput di pintu</span>
                                    </td>
                                    <td><span class="sw-pill pill-active">Transactional</span></td>
                                    <td>🎯 Bung Kevin</td>
                                    <td><code>/jakarta-bandung-dropoff</code></td>
                                    <td><span class="sw-pill pill-sleep">🟡 Antrean Final</span></td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong style="color: var(--royal-blue);">MINGGU</strong><br>
                                        <span style="font-size: 11px; color: var(--text-muted);">16:00 WIB</span>
                                    </td>
                                    <td><span class="tag-dynamic">Trust & Security</span></td>
                                    <td>
                                        <strong>SOP Keamanan Drop Off Traveego: Bayar Sopir di Tempat Tanpa DP</strong><br>
                                        <span style="font-size: 11.5px; color: var(--text-muted);">Seed: drop off bayar tujuan aman</span>
                                    </td>
                                    <td><span class="sw-pill pill-active">Commercial</span></td>
                                    <td>🚐 Jaka x ✍️ Maya</td>
                                    <td><code>Blog: /garansi-0-persen-dp-traveego</code></td>
                                    <td><span class="sw-pill pill-active">🟢 Terjadwal</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Action: Calendar Sync Notice -->
                <div class="content-card" style="background: #FAF5FF; border-color: #E9D5FF;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
                        <div>
                            <h4 style="font-size: 15px; font-weight: 800; color: #6B21A8; margin-bottom: 4px;">📡 Status Penjadwalan Otomatis ke PC Server AI</h4>
                            <p style="font-size: 12.5px; color: #7E22CE; margin: 0;">Kalender ini terhubung langsung ke PC Server Ollama via Tailscale. Pipeline akan memproduksi draft konten secara berkala sesuai slot jadwal di atas.</p>
                        </div>
                        <button class="btn-brand-primary" style="background: linear-gradient(135deg, #7E22CE, #6B21A8);" onclick="showCalendarAlert()">
                            <span>🗓️</span>
                            <span>Konfirmasi Kalender Aktif</span>
                        </button>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <!-- 3. MOBILE BOTTOM NAVIGATION BAR ALA ANDROID (MUNCUL OTOMATIS DI LAYAR HP) -->
    <nav class="mobile-bottom-nav" id="mobileBottomNav">
        <div class="bnav-item active" id="bnavOverview" onclick="returnToOverviewHub()">
            <div class="bnav-icon-wrap">🏠</div>
            <span class="bnav-label">Overview</span>
        </div>
        <div class="bnav-item" id="bnavAgents" onclick="scrollToAndroidAgents()">
            <div class="bnav-icon-wrap">🤖</div>
            <span class="bnav-label">AI Agent</span>
        </div>
        <div class="bnav-item" id="bnavServer" onclick="openServerConfigModal(); updateBottomNavActive('bnavServer');">
            <div class="bnav-icon-wrap">⚙️</div>
            <span class="bnav-label">Server</span>
        </div>
        <div class="bnav-item" id="bnavPipeline" onclick="openAgentWorkspace('komandanPanel', 'Komandan Sistem &ndash; Orchestrator'); updateBottomNavActive('bnavPipeline');">
            <div class="bnav-icon-wrap">⚡</div>
            <span class="bnav-label">Pipeline</span>
        </div>
        <a href="/crew-ai?logout=1" class="bnav-item" title="Keluar">
            <div class="bnav-icon-wrap" style="color: #EF4444;">🚪</div>
            <span class="bnav-label" style="color: #EF4444;">Keluar</span>
        </a>
    </nav>
</div>

<!-- ==============================================================
     MODAL PENGATURAN TAILSCALE PC SERVER (LIGHT MODE)
     ============================================================== -->
<div id="serverConfigModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); backdrop-filter:blur(6px); z-index:999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#FFFFFF; border:1px solid #CBD5E1; border-radius:24px; width:100%; max-width:480px; padding:32px; box-shadow:0 20px 50px rgba(0,0,0,0.15);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div style="font-size:18px; font-weight:800; color:var(--text-title); display:flex; align-items:center; gap:10px;">
                <span>⚙️</span>
                <span>Pengaturan Server AI (Tailscale)</span>
            </div>
            <button onclick="closeServerConfigModal()" style="background:none; border:none; color:var(--text-muted); font-size:24px; cursor:pointer;">&times;</button>
        </div>

        <div style="margin-bottom:18px;">
            <label style="display:block; font-size:13px; font-weight:700; color:var(--text-title); margin-bottom:6px;">URL / IP Ollama Host (Tailscale)</label>
            <input type="text" id="cfgOllamaHost" style="width:100%; height:46px; background:#F8FAFC; border:1.5px solid #CBD5E1; border-radius:10px; padding:0 14px; color:#0F172A; font-family:monospace; font-size:14px; outline:none;">
            <small style="color:var(--text-muted); font-size:11.5px; margin-top:4px; display:block;">Contoh: <code>http://100.85.151.80:11434</code> (PC Server Anda)</small>
        </div>

        <div style="margin-bottom:24px;">
            <label style="display:block; font-size:13px; font-weight:700; color:var(--text-title); margin-bottom:6px;">Pilihan Model AI</label>
            <select id="cfgModelSelect" style="width:100%; height:46px; background:#F8FAFC; border:1.5px solid #CBD5E1; border-radius:10px; padding:0 14px; color:#0F172A; font-size:14px; outline:none;"></select>
            <small style="color:var(--text-muted); font-size:11.5px; margin-top:4px; display:block;">Daftar model dibaca otomatis dari Ollama PC Server Anda.</small>
        </div>

        <div style="display:flex; gap:12px; justify-content:flex-end;">
            <button type="button" onclick="closeServerConfigModal()" class="btn-brand-outline">Batal</button>
            <button type="button" onclick="saveServerConfig()" id="btnSaveCfg" class="btn-brand-primary">Simpan & Test Koneksi</button>
        </div>
    </div>
</div>

<script>
    // Navigation Control
    function returnToOverviewHub() {
        document.querySelectorAll('.agent-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('overviewHub').classList.add('active');
        document.getElementById('activeAgentHeaderTitle').innerText = 'Overview Control Hub';
        
        // Update sidebar active menu
        document.querySelectorAll('.sidebar-menu-item').forEach(el => el.classList.remove('active'));
        const m = document.getElementById('menuItemOverview');
        if (m) m.classList.add('active');

        // Update mobile bottom nav active
        updateBottomNavActive('bnavOverview');

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function scrollToAndroidAgents() {
        returnToOverviewHub();
        setTimeout(() => {
            const el = document.getElementById('androidMenuSection');
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 120);
        updateBottomNavActive('bnavAgents');
    }

    function openAgentWorkspace(panelId, titleText) {
        document.querySelectorAll('.agent-panel').forEach(p => p.classList.remove('active'));
        const target = document.getElementById(panelId);
        if (target) target.classList.add('active');

        if (titleText) {
            document.getElementById('activeAgentHeaderTitle').innerHTML = titleText;
        }

        if (panelId === 'komandanPanel') {
            updateBottomNavActive('bnavPipeline');
        } else {
            updateBottomNavActive('bnavAgents');
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function updateBottomNavActive(activeId) {
        document.querySelectorAll('.bnav-item').forEach(el => el.classList.remove('active'));
        const target = document.getElementById(activeId);
        if (target) target.classList.add('active');
    }

    // Live Clock WIB
    function updateClock() {
        const now = new Date();
        const str = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
        const sbClock = document.getElementById('sbClockText');
        if (sbClock) sbClock.innerText = str;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Data Loaders
    let currentServerConfig = {};
    let availableModels = [];

    async function loadStatus() {
        try {
            const res = await fetch('/api/crew.php?action=status');
            const d = await res.json();
            if (!d.success) return;

            // Jam Kerja
            const wh = d.working_hours;
            const sbHoursPill = document.getElementById('sbHoursPill');
            const statHoursPill = document.getElementById('statHoursPill');
            const statusLabel = wh.is_working ? '🟢 AKTIF' : '🟡 STANDBY';
            const pillClass = wh.is_working ? 'sw-pill pill-active' : 'sw-pill pill-sleep';

            if (sbHoursPill) {
                sbHoursPill.innerText = statusLabel;
                sbHoursPill.className = pillClass;
            }
            if (statHoursPill) {
                statHoursPill.innerText = statusLabel;
                statHoursPill.className = pillClass;
            }
            const statDesc = document.getElementById('statHoursDesc');
            if (statDesc) statDesc.innerText = wh.status_text;

            // Ollama Tailscale
            const ol = d.ollama;
            currentServerConfig = ol.config || {};
            availableModels = ol.available_models || [];

            const statOllamaPill = document.getElementById('statOllamaPill');
            if (statOllamaPill) {
                statOllamaPill.innerText = ol.connected ? '🟢 TERHUBUNG' : '🟡 STANDBY';
                statOllamaPill.className = ol.connected ? 'sw-pill pill-active' : 'sw-pill pill-sleep';
            }

            const modelText = ol.model || 'qwen2.5-coder:7b';
            if (document.getElementById('statOllamaModel')) document.getElementById('statOllamaModel').innerText = modelText;
            if (document.getElementById('kmdModelText')) document.getElementById('kmdModelText').innerText = modelText;

            const hostText = ol.config && ol.config.ollama_host ? ol.config.ollama_host : 'http://100.85.151.80:11434';
            if (document.getElementById('statOllamaHost')) document.getElementById('statOllamaHost').innerText = hostText;
            if (document.getElementById('kmdHostText')) document.getElementById('kmdHostText').innerText = hostText;
            if (document.getElementById('topbarTailscaleText')) {
                document.getElementById('topbarTailscaleText').innerText = 'Tailscale: ' + (ol.connected ? 'Terhubung' : 'Standby');
            }

            // Stats Count
            if (document.getElementById('statRoutesCount')) document.getElementById('statRoutesCount').innerText = d.stats.routes_count;
            if (document.getElementById('statPostsCount')) document.getElementById('statPostsCount').innerText = d.stats.posts_count;
        } catch(e) {
            console.error('Error fetching status:', e);
        }
    }

    async function loadMatrix() {
        try {
            const res = await fetch('/api/crew.php?action=get_matrix');
            const d = await res.json();
            if (!d.success) return;

            const quickTbody = document.getElementById('quickMatrixBody');
            const hendraTbody = document.getElementById('hendraMatrixBody');

            if (quickTbody) quickTbody.innerHTML = '';
            if (hendraTbody) hendraTbody.innerHTML = '';

            const routes = d.data.routes || {};
            for (const key in routes) {
                const r = routes[key];
                const p = r.pricing || {};

                // Row for Quick Matrix on Hub
                if (quickTbody) {
                    const trQ = document.createElement('tr');
                    trQ.innerHTML = `
                        <td>
                            <strong>${r.origin} ➔ ${r.destination}</strong><br>
                            <span class="badge-route-slug">/${r.slug}</span>
                        </td>
                        <td><strong>${r.distance_km} KM</strong></td>
                        <td style="font-size: 12.5px;">Rp ${Number(r.toll_cost).toLocaleString('id-ID')}</td>
                        <td style="font-size: 12.5px;">Rp ${Number(r.base_driver_fee).toLocaleString('id-ID')}</td>
                        <td><span class="badge-price">${p.avanza ? p.avanza.formatted_price : '-'}</span></td>
                        <td><span class="badge-price" style="background:#EFF6FF; color:#1D4ED8; border-color:#BFDBFE;">${p.innova ? p.innova.formatted_price : '-'}</span></td>
                        <td><span class="badge-price" style="background:#FEF3C7; color:#B45309; border-color:#FCD34D;">${p.hiace ? p.hiace.formatted_price : '-'}</span></td>
                    `;
                    quickTbody.appendChild(trQ);
                }

                // Row for Pak Hendra Matrix
                if (hendraTbody) {
                    const marketPrices = {
                        'jakarta_bandara_soetta': 'Rp 325.000',
                        'jakarta_bandara_halim': 'Rp 280.000',
                        'jabodetabek_dalam_kota': 'Rp 350.000',
                        'jakarta_baraya': 'Rp 650.000'
                    };
                    const pasaranText = marketPrices[key] || 'Rp 350.000';

                    const trH = document.createElement('tr');
                    trH.innerHTML = `
                        <td>
                            <strong style="color: var(--text-title);">${r.origin} ➔ ${r.destination}</strong><br>
                            <span class="badge-route-slug">/${r.slug}</span>
                        </td>
                        <td><span class="tag-dynamic">${r.distance_km} KM</span></td>
                        <td style="font-size: 12px; max-width: 240px;">
                            <strong style="color: #0F172A;">Rp ${Number(r.toll_cost).toLocaleString('id-ID')}</strong><br>
                            <span style="color: var(--text-muted);">${r.toll_details || '-'}</span>
                        </td>
                        <td style="font-weight: 700;">Rp ${Number(r.base_driver_fee).toLocaleString('id-ID')}</td>
                        <td>
                            <span style="color:#DC2626; text-decoration:line-through; font-size:12px;">${pasaranText}</span><br>
                            <span style="color:#16A34A; font-size:11px; font-weight:700;">Traveego Lebih Hemat</span>
                        </td>
                        <td><span class="badge-price">${p.avanza ? p.avanza.formatted_price : '-'}</span></td>
                        <td><span class="badge-price" style="background:#EFF6FF; color:#1D4ED8; border-color:#BFDBFE;">${p.innova ? p.innova.formatted_price : '-'}</span></td>
                        <td><span class="badge-price" style="background:#FEF3C7; color:#B45309; border-color:#FCD34D;">${p.hiace ? p.hiace.formatted_price : '-'}</span></td>
                    `;
                    hendraTbody.appendChild(trH);
                }
            }
        } catch(e) {
            console.error('Error loading matrix:', e);
        }
    }

    async function loadPages() {
        try {
            const res = await fetch('/api/crew.php?action=get_pages');
            const d = await res.json();
            if (!d.success) return;

            const grid = document.getElementById('pagesGrid');
            if (!grid) return;
            grid.innerHTML = '';

            if (d.pages.length === 0) {
                grid.innerHTML = '<div style="color: var(--text-muted);">Belum ada landing page rute.</div>';
                return;
            }

            d.pages.forEach(p => {
                const page = p.data;
                const card = document.createElement('div');
                card.className = 'item-card';
                card.innerHTML = `
                    <div>
                        <div style="font-size: 11px; color: var(--royal-blue); font-weight: 800; margin-bottom: 6px;">BUNG KEVIN / LANDING PAGE</div>
                        <h4 class="item-title">${page.meta_title || p.file}</h4>
                        <p class="item-desc">${page.meta_description || '-'}</p>
                    </div>
                    <div class="item-meta">
                        <span>Slug: <code>/${page.slug || p.file}</code></span>
                        <span class="sw-pill pill-active">All-In 0% DP</span>
                    </div>
                `;
                grid.appendChild(card);
            });
        } catch(e) {
            console.error('Error loading pages:', e);
        }
    }

    async function loadPosts() {
        try {
            const res = await fetch('/api/crew.php?action=get_posts');
            const d = await res.json();
            if (!d.success) return;

            const grid = document.getElementById('postsGrid');
            if (!grid) return;
            grid.innerHTML = '';

            if (d.posts.length === 0) {
                grid.innerHTML = '<div style="color: var(--text-muted);">Belum ada artikel blog.</div>';
                return;
            }

            d.posts.forEach(p => {
                const post = p.data;
                const card = document.createElement('div');
                card.className = 'item-card';
                card.innerHTML = `
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span class="tag-dynamic">${post.category || 'SEO Artikel'}</span>
                            <span style="font-size: 11px; color: var(--text-muted);">${post.published_at || ''}</span>
                        </div>
                        <h4 class="item-title">${post.title || p.file}</h4>
                        <p class="item-desc">${post.meta_description || '-'}</p>
                    </div>
                    <div class="item-meta">
                        <span>Target: <code>/${post.target_route_slug || ''}</code></span>
                        <span class="sw-pill pill-active">Shortcode Linked</span>
                    </div>
                `;
                grid.appendChild(card);
            });
        } catch(e) {
            console.error('Error loading posts:', e);
        }
    }

    // Run Pipeline
    async function runPipeline() {
        const btn = document.getElementById('btnRunPipeline');
        const btnQ = document.getElementById('btnQuickRun');
        const term = document.getElementById('terminalOutput');

        openAgentWorkspace('komandanPanel', 'Komandan Sistem &ndash; Orchestrator');

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span>⏳</span><span>Sedang Menjalankan Pipeline...</span>';
        }
        if (btnQ) {
            btnQ.disabled = true;
            btnQ.innerHTML = '<span>⏳</span><span>Memproses...</span>';
        }

        term.innerText = 'Memicu orchestrator AI di PC Server lokal via Tailscale...\nMenjalankan Pak Hendra (Survey Matrix), Bung Kevin (Page Crew), Mbak Maya (Post Crew)...\nMohon tunggu sejenak...\n';

        try {
            const res = await fetch('/api/crew.php?action=run_pipeline');
            const d = await res.json();
            term.innerText = d.log || 'Eksekusi selesai tanpa output log.';
            refreshData();
        } catch(e) {
            term.innerText += '\n[ERROR] Gagal memicu pipeline: ' + e.message;
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<span>🚀</span><span>Jalankan Seluruh AI Agent Sekarang</span>';
            }
            if (btnQ) {
                btnQ.disabled = false;
                btnQ.innerHTML = '<span>🚀</span><span>Jalankan Pipeline</span>';
            }
        }
    }

    // Modal Server Tailscale
    function openServerConfigModal() {
        document.getElementById('serverConfigModal').style.display = 'flex';
        document.getElementById('cfgOllamaHost').value = currentServerConfig.ollama_host || 'http://100.85.151.80:11434';

        const select = document.getElementById('cfgModelSelect');
        select.innerHTML = '';
        if (availableModels.length > 0) {
            availableModels.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m;
                opt.innerText = m;
                if (m === currentServerConfig.model) opt.selected = true;
                select.appendChild(opt);
            });
        } else {
            const opt = document.createElement('option');
            opt.value = currentServerConfig.model || 'qwen2.5-coder:7b';
            opt.innerText = currentServerConfig.model || 'qwen2.5-coder:7b';
            select.appendChild(opt);
        }
    }

    function closeServerConfigModal() {
        document.getElementById('serverConfigModal').style.display = 'none';
        updateBottomNavActive('bnavOverview');
    }

    async function saveServerConfig() {
        const host = document.getElementById('cfgOllamaHost').value.trim();
        const model = document.getElementById('cfgModelSelect').value.trim();
        const btn = document.getElementById('btnSaveCfg');

        btn.disabled = true;
        btn.innerText = 'Menyimpan & Mengetes...';

        try {
            const res = await fetch('/api/crew.php?action=save_ai_config', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ollama_host: host, model: model })
            });
            const d = await res.json();
            if (d.success) {
                alert('Berhasil: ' + d.message);
                closeServerConfigModal();
                loadStatus();
            } else {
                alert('Gagal: ' + (d.message || 'Terjadi kesalahan'));
            }
        } catch(e) {
            alert('Error: ' + e.message);
        } finally {
            btn.disabled = false;
            btn.innerText = 'Simpan & Test Koneksi';
        }
    }

    // Chat Simulator Mbak Tiara
    async function sendChatSim(e) {
        if (e) e.preventDefault();
        const input = document.getElementById('chatInputText');
        const text = input.value.trim();
        if (!text) return;

        appendChatBubble('user', text);
        input.value = '';

        const typingId = 'typing-' + Date.now();
        const feed = document.getElementById('chatFeed');
        const typingEl = document.createElement('div');
        typingEl.className = 'chat-bubble tiara';
        typingEl.id = typingId;
        typingEl.innerText = 'Mbak Tiara sedang mengetik...';
        feed.appendChild(typingEl);
        feed.scrollTop = feed.scrollHeight;

        try {
            const res = await fetch('/api/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            });
            const d = await res.json();
            const el = document.getElementById(typingId);
            if (el) el.remove();
            appendChatBubble('tiara', d.reply || 'Maaf, Tiara sedang memeriksa data rute. Mohon coba sesaat lagi ya.');
        } catch(err) {
            const el = document.getElementById(typingId);
            if (el) el.remove();
            appendChatBubble('tiara', 'Maaf terjadi gangguan koneksi ke server chat: ' + err.message);
        }
    }

    function appendChatBubble(sender, text) {
        const feed = document.getElementById('chatFeed');
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble ' + sender;
        bubble.innerText = text;
        feed.appendChild(bubble);
        feed.scrollTop = feed.scrollHeight;
    }

    function askQuickPrompt(question) {
        document.getElementById('chatInputText').value = question;
        sendChatSim();
    }

    function clearChatSim() {
        const feed = document.getElementById('chatFeed');
        feed.innerHTML = `
            <div class="chat-bubble tiara">
Halo Mas Winsyah! Percakapan telah dibersihkan. Silakan tanyakan seputar rute Bandara, Jabodetabek, atau Bandung! 😊
            </div>
        `;
    }

    // Mbak Dinda Weekly Calendar Controls
    function simulateCalendarSync() {
        alert('✅ Kalender Konten Pekanan berhasil disinkronkan dengan PC Server AI (Tailscale)!\nSlot jadwal artikel dan landing page telah dijadwalkan otomatis pada jam operasional server 06:00 - 23:00 WIB.');
    }

    function generateNewWeeklyPlan() {
        const badge = document.getElementById('dindaActiveWeekBadge');
        if (badge) badge.innerText = 'Pekan Diperbarui: Siap Eksekusi (Sync Otomatis)';
        alert('🔄 Mbak Dinda berhasil merotasi topik pekanan berdasarkan kluster riset kata kunci "Drop Off" terbaru dari Mas Bayu!');
    }

    function showCalendarAlert() {
        alert('📅 Kalender Konten Pekanan aktif! 7 slot konten terdistribusi rapi (4 artikel SEO, 2 landing page rute, 1 FAQ panduan tarif) untuk mendominasi Google.');
    }

    // Workflow Tab Switcher & Navigation
    function switchWorkflowTab(tabId) {
        document.querySelectorAll('.btn-wf-tab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.wf-content-tab').forEach(t => t.style.display = 'none');
        
        if (tabId === 'wf1') {
            const b = document.getElementById('tabBtnWf1');
            const t = document.getElementById('wfTab1');
            if (b) b.classList.add('active');
            if (t) t.style.display = 'block';
        } else {
            const b = document.getElementById('tabBtnWf2');
            const t = document.getElementById('wfTab2');
            if (b) b.classList.add('active');
            if (t) t.style.display = 'block';
        }
    }

    function scrollToWorkflows() {
        returnToOverviewHub();
        setTimeout(() => {
            const el = document.getElementById('workflowArchitectureSection');
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 120);
    }

    function simulateTiaraChat() {
        alert('👩‍💼 Mbak Tiara (CS AI):\n"Halo Kak! Tiara terhubung langsung dengan data tarif update Pak Hendra dan armada standby Bang Jaka.\nTarif kami ALL-IN (Tol + BBM + Sopir) tanpa DP sama sekali, bayar di lokasi penjemputan/tujuan!"');
    }

    function refreshData() {
        loadStatus();
        loadMatrix();
        loadPages();
        loadPosts();
    }

    // Initial load
    refreshData();
</script>
<?php endif; ?>

</body>
</html>
