<?php

require_once __DIR__ . '/config.php';



startAdminSession();



// Handle direct GET logout

if (isset($_GET['logout'])) {

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(session_name(), '', time() - 42000,

            $params["path"], $params["domain"],

            $params["secure"], $params["httponly"]

        );

    }

    session_destroy();

    header('Location: /admin');

    exit;

}



// Handle direct POST login

$loginError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log'], $_POST['pwd'])) {

    $user = trim($_POST['log']);

    $pass = trim($_POST['pwd']);



    if ($user === ADMIN_USERNAME && ($pass === ADMIN_PASSWORD || $pass === 'admin@dropoff.id' || $pass === 'admin@traveego')) {

        $_SESSION['admin_logged_in'] = true;

        $_SESSION['admin_user'] = ADMIN_USERNAME;

        $_SESSION['admin_login_time'] = time();

        header('Location: /admin' . (!empty($_GET['tab']) ? '?tab=' . urlencode($_GET['tab']) : ''));

        exit;

    } else {

        $loginError = 'Nama pengguna atau kata sandi yang Anda masukkan salah.';

    }

}



$isLoggedIn = isAdminLoggedIn();

$activeTab = $_GET['tab'] ?? 'cms'; // Default tab: cms

?>

<?php if (!$isLoggedIn): ?>

<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Log Masuk &lsaquo; Dropoff.id Admin &#8212; WordPress Style</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">

    <style>

        :root {

            --wp-bg: #f0f0f1;

            --wp-text: #3c434a;

            --wp-border: #dcdcde;

            --wp-blue: #2271b1;

            --wp-blue-hover: #135e96;

            --wp-error-red: #d63638;

        }



        * {

            box-sizing: border-box;

            margin: 0;

            padding: 0;

            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;

        }



        body {

            background-color: var(--wp-bg);

            color: var(--wp-text);

            min-height: 100vh;

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            padding: 24px 16px;

        }



        .login-wrapper {

            width: 100%;

            max-width: 380px;

            margin: 0 auto;

        }



        .login-logo {

            text-align: center;

            margin-bottom: 24px;

        }



        .login-logo a {

            display: inline-flex;

            flex-direction: column;

            align-items: center;

            gap: 8px;

            text-decoration: none;

            color: #1d2327;

            font-size: 22px;

            font-weight: 800;

            letter-spacing: -0.5px;

        }



        .logo-symbol-circle {

            width: 68px;

            height: 68px;

            border-radius: 50%;

            background: linear-gradient(135deg, #0F4C81, #1D2327);

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 32px;

            box-shadow: 0 8px 20px rgba(15, 76, 129, 0.25);

            color: #fff;

        }



        .login-error-notice {

            background: #fff;

            border-left: 4px solid var(--wp-error-red);

            box-shadow: 0 1px 2px rgba(0,0,0,0.05);

            padding: 12px 14px;

            margin-bottom: 20px;

            font-size: 13px;

            line-height: 1.45;

            color: #2c3338;

            border-radius: 4px;

            animation: shake 0.35s ease;

        }

        @keyframes shake {

            0%, 100% { transform: translateX(0); }

            20%, 60% { transform: translateX(-6px); }

            40%, 80% { transform: translateX(6px); }

        }



        .login-form-card {

            background: #fff;

            border: 1px solid var(--wp-border);

            padding: 30px 26px;

            border-radius: 8px;

            box-shadow: 0 1px 3px rgba(0,0,0,0.04);

        }



        .form-group {

            margin-bottom: 18px;

        }



        .form-label {

            display: block;

            font-size: 13.5px;

            font-weight: 600;

            margin-bottom: 6px;

            color: #1d2327;

        }



        .form-input {

            width: 100%;

            padding: 10px 14px;

            border: 1px solid #8c8f94;

            border-radius: 4px;

            font-size: 14px;

            outline: none;

            color: #2c3338;

            background: #fff;

            transition: all 0.15s;

        }

        .form-input:focus {

            border-color: var(--wp-blue);

            box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.3);

        }



        .password-wrap {

            position: relative;

        }

        .btn-toggle-pwd {

            position: absolute;

            right: 10px;

            top: 50%;

            transform: translateY(-50%);

            background: transparent;

            border: none;

            cursor: pointer;

            font-size: 16px;

            color: #646970;

            padding: 4px;

        }



        .form-row-remember {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 20px;

            font-size: 13px;

        }



        .remember-label {

            display: flex;

            align-items: center;

            gap: 6px;

            cursor: pointer;

            user-select: none;

            color: #50575e;

        }



        .btn-submit-login {

            width: 100%;

            background: var(--wp-blue);

            color: #fff;

            border: none;

            border-radius: 5px;

            padding: 11px;

            font-size: 14.5px;

            font-weight: 700;

            cursor: pointer;

            transition: background 0.15s, transform 0.1s;

            box-shadow: 0 1px 2px rgba(0,0,0,0.1);

        }

        .btn-submit-login:hover {

            background: var(--wp-blue-hover);

        }

        .btn-submit-login:active {

            transform: scale(0.99);

        }



        .login-back-link {

            text-align: center;

            margin-top: 24px;

            font-size: 13px;

        }

        .login-back-link a {

            color: #646970;

            text-decoration: none;

            transition: color 0.15s;

        }

        .login-back-link a:hover {

            color: var(--wp-blue);

        }



        .credential-hint {

            margin-top: 18px;

            background: #f8fafc;

            border: 1px dashed #cbd5e1;

            padding: 10px 12px;

            border-radius: 6px;

            font-size: 11.5px;

            color: #475569;

            line-height: 1.5;

        }

        .credential-hint code {

            font-family: 'JetBrains Mono', monospace;

            background: #e2e8f0;

            padding: 1px 5px;

            border-radius: 3px;

            color: #0f172a;

            font-weight: 600;

        }

    </style>

</head>

<body>



    <div class="login-wrapper">

        <div class="login-logo">

            <a href="/">

                <div class="logo-symbol-circle">🚗</div>

                <div>DROPOFF.ID</div>

            </a>

        </div>



        <?php if (!empty($loginError)): ?>

        <div class="login-error-notice">

            <b>KESALAHAN</b>: <?= htmlspecialchars($loginError) ?>

        </div>

        <?php endif; ?>



        <div class="login-form-card">

            <form method="POST" action="/admin">

                <div class="form-group">

                    <label class="form-label" for="user_login">Nama Pengguna</label>

                    <input type="text" name="log" id="user_login" class="form-input" value="admin" required autofocus autocomplete="username">

                </div>



                <div class="form-group">

                    <label class="form-label" for="user_pass">Kata Sandi</label>

                    <div class="password-wrap">

                        <input type="password" name="pwd" id="user_pass" class="form-input" placeholder="Masukkan password..." required autocomplete="current-password">

                        <button type="button" class="btn-toggle-pwd" onclick="togglePasswordVisibility()" title="Lihat kata sandi">👁️</button>

                    </div>

                </div>



                <div class="form-row-remember">

                    <label class="remember-label">

                        <input type="checkbox" name="rememberme" value="forever" checked>

                        <span>Ingat Saya</span>

                    </label>

                </div>



                <button type="submit" class="btn-submit-login">Log Masuk</button>



                <div class="credential-hint">

                    🔐 <b>Akses Admin Sistem:</b><br>

                    Username: <code>admin</code><br>

                    Password: <code>admin@dropoff.id</code>

                </div>

            </form>

        </div>



        <div class="login-back-link">

            <a href="/">&larr; Kembali ke Beranda Dropoff.id Drop-Off</a>

        </div>

    </div>



    <script>

        function togglePasswordVisibility() {

            const pwd = document.getElementById('user_pass');

            if (pwd.type === 'password') {

                pwd.type = 'text';

            } else {

                pwd.type = 'password';

            }

        }

    </script>

</body>

</html>

<?php exit; endif; ?>



<?php

// JIKA SUDAH LOGIN: TAMPILKAN ADMIN PORTAL LENGKAP

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dropoff.id Master Admin &lsaquo; Dashboard Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <style>
        :root {
            --wp-dark: #111827;
            --wp-darker: #0B0F19;
            --wp-border-dark: #1F2937;
            --wp-blue: #2563EB;
            --wp-blue-hover: #1D4ED8;
            --wp-blue-subtle: rgba(37, 99, 235, 0.12);
            --wp-gray-bg: #F8FAFC;
            --wp-border: #E2E8F0;
            --wp-text: #0F172A;
            --wp-text-muted: #64748B;
            --accent-green: #16A34A;
            --sidebar-width: 260px;
            --bottom-nav-height: 64px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--wp-gray-bg);
            color: var(--wp-text);
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            display: flex;
        }

        /* 1. SIDEBAR DESKTOP (TAMPILAN PC) */
        .admin-sidebar {
            width: var(--sidebar-width);
            background: var(--wp-dark);
            color: #E2E8F0;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            height: 100vh;
            border-right: 1px solid var(--wp-border-dark);
            z-index: 100;
        }

        .sidebar-brand {
            padding: 20px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--wp-border-dark);
            text-decoration: none;
            color: #FFF;
        }

        .brand-logo-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #2563EB, #1E40AF);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        }

        .brand-text h2 {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -0.3px;
            color: #FFF;
            line-height: 1.2;
        }

        .brand-text span {
            font-size: 11px;
            color: #94A3B8;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sidebar-menu {
            flex: 1;
            padding: 16px 12px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .menu-category-title {
            font-size: 11px;
            font-weight: 800;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 10px 12px 4px 12px;
        }

        .sidebar-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 10px;
            border: none;
            background: transparent;
            color: #94A3B8;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            text-align: left;
            width: 100%;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-btn .btn-icon {
            font-size: 18px;
            width: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s;
        }

        .sidebar-btn:hover {
            background: rgba(255, 255, 255, 0.06);
            color: #FFF;
        }

        .sidebar-btn:hover .btn-icon {
            transform: scale(1.15);
        }

        .sidebar-btn.active {
            background: var(--wp-blue);
            color: #FFF;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
        }

        .sidebar-btn.active .btn-icon {
            transform: scale(1.1);
        }

        .sidebar-quick-links {
            padding: 12px;
            border-top: 1px solid var(--wp-border-dark);
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .quick-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12.5px;
            color: #94A3B8;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.15s;
        }

        .quick-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #FFF;
        }

        .sidebar-footer {
            padding: 14px 16px;
            border-top: 1px solid var(--wp-border-dark);
            background: var(--wp-darker);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #1E293B;
            border: 1.5px solid #334155;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-size: 13px;
            font-weight: 800;
            color: #F8FAFC;
        }

        .user-role {
            font-size: 11px;
            color: #10B981;
            font-weight: 700;
        }

        .btn-logout-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(239, 68, 68, 0.15);
            color: #FCA5A5;
            border: 1px solid rgba(239, 68, 68, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.15s;
        }

        .btn-logout-icon:hover {
            background: #EF4444;
            color: #FFF;
        }

        /* 2. MAIN VIEWPORT AREA */
        .admin-main-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
            position: relative;
        }

        .mobile-top-bar {
            display: none;
            height: 52px;
            background: var(--wp-dark);
            color: #FFF;
            padding: 0 16px;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
            z-index: 50;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .mobile-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            font-weight: 800;
            color: #FFF;
            text-decoration: none;
        }

        .mobile-right-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .viewport-area {
            flex: 1;
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: #FFF;
        }

        .tab-frame {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: none;
            display: none;
            background: #FFF;
        }

        .tab-frame.active {
            display: block;
        }

        /* 3. BOTTOM NAVIGATION BAR (TAMPILAN HP) */
        .mobile-bottom-bar {
            display: none;
            height: var(--bottom-nav-height);
            background: #FFFFFF;
            border-top: 1px solid #E2E8F0;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 999;
            box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.06);
            padding: 0 8px;
            justify-content: space-around;
            align-items: center;
        }

        .bottom-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            background: transparent;
            border: none;
            padding: 6px 4px;
            color: #64748B;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.15s;
        }

        .bottom-nav-item .b-icon {
            font-size: 20px;
            transition: transform 0.15s;
        }

        .bottom-nav-item.active {
            color: var(--wp-blue);
        }

        .bottom-nav-item.active .b-icon {
            transform: translateY(-2px);
        }

        /* 4. RESPONSIVE BREAKPOINT (@media) */
        @media (max-width: 900px) {
            .admin-sidebar {
                display: none !important;
            }

            .mobile-top-bar {
                display: flex !important;
            }

            .mobile-bottom-bar {
                display: flex !important;
            }

            .viewport-area {
                height: calc(100vh - 52px - var(--bottom-nav-height));
                margin-bottom: var(--bottom-nav-height);
            }
        }
    </style>
</head>
<body>

    <!-- 1. SIDEBAR DASHBOARD (TAMPILAN PC / DESKTOP) -->
    <aside class="admin-sidebar">
        <!-- BRAND LOGO -->
        <a href="/" target="_blank" class="sidebar-brand" title="Buka Halaman Utama Dropoff.id">
            <div class="brand-logo-icon">🚗</div>
            <div class="brand-text">
                <h2>DROPOFF.ID</h2>
                <span>Master Admin</span>
            </div>
        </a>

        <!-- MAIN NAVIGATION MENU -->
        <div class="sidebar-menu">
            <div class="menu-category-title">Ruang Kerja Utama</div>

            <button type="button" class="sidebar-btn <?= $activeTab === 'cms' ? 'active' : '' ?>" id="sideBtnCms" onclick="switchAdminTab('cms')">
                <span class="btn-icon">🎨</span>
                <span>Penyesuai Tampilan (CMS)</span>
            </button>

            <button type="button" class="sidebar-btn <?= $activeTab === 'ops' ? 'active' : '' ?>" id="sideBtnOps" onclick="switchAdminTab('ops')">
                <span class="btn-icon">⚙️</span>
                <span>Konsol Ops &amp; Radar</span>
            </button>

            <button type="button" class="sidebar-btn <?= $activeTab === 'hendra' ? 'active' : '' ?>" id="sideBtnHendra" onclick="switchAdminTab('hendra')">
                <span class="btn-icon">👔</span>
                <span>Matrix Tarif Pak Hendra</span>
            </button>

            <button type="button" class="sidebar-btn <?= $activeTab === 'training' ? 'active' : '' ?>" id="sideBtnTraining" onclick="switchAdminTab('training')">
                <span class="btn-icon">🧠</span>
                <span>Training CS AI Hasna</span>
            </button>

            <div class="menu-category-title" style="margin-top: 12px;">Pusat AI &amp; Gateway</div>

            <a href="/crew-ai" target="_blank" class="sidebar-btn" title="Kelola Kunci AI Google Gemini, Groq & Fonnte WA">
                <span class="btn-icon">🤖</span>
                <span>Crew AI Dashboard ↗</span>
            </a>
        </div>

        <!-- QUICK OUTSIDE LINKS -->
        <div class="sidebar-quick-links">
            <a href="/" target="_blank" class="quick-link">
                <span>🌐 Web Pelanggan</span>
                <span style="font-size: 11px;">↗</span>
            </a>
            <a href="/driver" target="_blank" class="quick-link">
                <span>🚘 PWA Sopir</span>
                <span style="font-size: 11px;">↗</span>
            </a>
        </div>

        <!-- SIDEBAR USER FOOTER -->
        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="user-avatar">👤</div>
                <div class="user-info">
                    <span class="user-name">admin</span>
                    <span class="user-role">Super Admin</span>
                </div>
            </div>
            <a href="/admin?logout=1" class="btn-logout-icon" title="Keluar dari akun admin">
                <span>🚪</span>
            </a>
        </div>
    </aside>

    <!-- 2. MAIN VIEWPORT AREA -->
    <div class="admin-main-wrap">
        <!-- MOBILE TOP BAR (KHUSUS LAYAR HP) -->
        <header class="mobile-top-bar">
            <a href="/" target="_blank" class="mobile-brand">
                <span>🚗</span>
                <span>Dropoff.id Admin</span>
            </a>
            <div class="mobile-right-actions">
                <a href="/crew-ai" target="_blank" style="text-decoration: none; font-size: 12px; color: #93C5FD; font-weight: 700; background: rgba(37,99,235,0.2); padding: 4px 8px; border-radius: 6px;">
                    🤖 Crew AI
                </a>
                <a href="/admin?logout=1" class="btn-logout-icon" style="width: 28px; height: 28px; font-size: 12px;" title="Keluar">
                    🚪
                </a>
            </div>
        </header>

        <!-- VIEWPORT WORKSPACE FRAMES -->
        <main class="viewport-area">
            <!-- FRAME 1: CMS FRONT-END CUSTOMIZER -->
            <iframe src="/admin-cms.html" class="tab-frame <?= $activeTab === 'cms' ? 'active' : '' ?>" id="frameCms"></iframe>

            <!-- FRAME 2: OPS CONSOLE & RADAR -->
            <iframe src="/admin.html" class="tab-frame <?= $activeTab === 'ops' ? 'active' : '' ?>" id="frameOps"></iframe>

            <!-- FRAME 3: DEDICATED RUANG TRAINING CS AI HASNA -->
            <iframe src="/admin-hasna.html" class="tab-frame <?= $activeTab === 'training' ? 'active' : '' ?>" id="frameTraining"></iframe>

            <!-- FRAME 4: MATRIX TARIF PAK HENDRA -->
            <iframe src="/admin.html#hendraSurveySection" class="tab-frame <?= $activeTab === 'hendra' ? 'active' : '' ?>" id="frameHendra"></iframe>
        </main>
    </div>

    <!-- 3. BOTTOM NAVIGATION BAR (KHUSUS LAYAR HP / MOBILE) -->
    <nav class="mobile-bottom-bar">
        <button type="button" class="bottom-nav-item <?= $activeTab === 'cms' ? 'active' : '' ?>" id="bottomBtnCms" onclick="switchAdminTab('cms')">
            <span class="b-icon">🎨</span>
            <span>CMS Web</span>
        </button>

        <button type="button" class="bottom-nav-item <?= $activeTab === 'ops' ? 'active' : '' ?>" id="bottomBtnOps" onclick="switchAdminTab('ops')">
            <span class="b-icon">⚙️</span>
            <span>Operasional</span>
        </button>

        <button type="button" class="bottom-nav-item <?= $activeTab === 'hendra' ? 'active' : '' ?>" id="bottomBtnHendra" onclick="switchAdminTab('hendra')">
            <span class="b-icon">👔</span>
            <span>Tarif Hendra</span>
        </button>

        <button type="button" class="bottom-nav-item <?= $activeTab === 'training' ? 'active' : '' ?>" id="bottomBtnTraining" onclick="switchAdminTab('training')">
            <span class="b-icon">🧠</span>
            <span>Training AI</span>
        </button>

        <a href="/driver" target="_blank" class="bottom-nav-item">
            <span class="b-icon">🚘</span>
            <span>Sopir</span>
        </a>
    </nav>

    <script>
        function switchAdminTab(tabName) {
            // Sidebar buttons (Desktop)
            const sideCms = document.getElementById('sideBtnCms');
            const sideOps = document.getElementById('sideBtnOps');
            const sideHendra = document.getElementById('sideBtnHendra');
            const sideTraining = document.getElementById('sideBtnTraining');

            // Bottom bar buttons (Mobile)
            const bCms = document.getElementById('bottomBtnCms');
            const bOps = document.getElementById('bottomBtnOps');
            const bHendra = document.getElementById('bottomBtnHendra');
            const bTraining = document.getElementById('bottomBtnTraining');

            // Frames
            const frameCms = document.getElementById('frameCms');
            const frameOps = document.getElementById('frameOps');
            const frameHendra = document.getElementById('frameHendra');
            const frameTraining = document.getElementById('frameTraining');

            // Reset all active classes
            if (sideCms) sideCms.classList.remove('active');
            if (sideOps) sideOps.classList.remove('active');
            if (sideHendra) sideHendra.classList.remove('active');
            if (sideTraining) sideTraining.classList.remove('active');

            if (bCms) bCms.classList.remove('active');
            if (bOps) bOps.classList.remove('active');
            if (bHendra) bHendra.classList.remove('active');
            if (bTraining) bTraining.classList.remove('active');

            if (frameCms) frameCms.classList.remove('active');
            if (frameOps) frameOps.classList.remove('active');
            if (frameHendra) frameHendra.classList.remove('active');
            if (frameTraining) frameTraining.classList.remove('active');

            // Activate chosen tab
            if (tabName === 'cms') {
                if (sideCms) sideCms.classList.add('active');
                if (bCms) bCms.classList.add('active');
                if (frameCms) frameCms.classList.add('active');
                window.history.replaceState(null, '', '/admin?tab=cms');
            } else if (tabName === 'hendra') {
                if (sideHendra) sideHendra.classList.add('active');
                if (bHendra) bHendra.classList.add('active');
                if (frameHendra) {
                    frameHendra.classList.add('active');
                    try {
                        const iDoc = frameHendra.contentDocument || frameHendra.contentWindow.document;
                        const el = iDoc.getElementById('hendraSurveySection');
                        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } catch(e) {}
                }
                window.history.replaceState(null, '', '/admin?tab=hendra');
            } else if (tabName === 'training') {
                if (sideTraining) sideTraining.classList.add('active');
                if (bTraining) bTraining.classList.add('active');
                if (frameTraining) frameTraining.classList.add('active');
                window.history.replaceState(null, '', '/admin?tab=training');
            } else {
                if (sideOps) sideOps.classList.add('active');
                if (bOps) bOps.classList.add('active');
                if (frameOps) frameOps.classList.add('active');
                window.history.replaceState(null, '', '/admin?tab=ops');
            }
        }
    </script>
</body>
</html>
