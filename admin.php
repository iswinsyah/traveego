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

    if ($user === ADMIN_USERNAME && $pass === ADMIN_PASSWORD) {
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
    <title>Log Masuk &lsaquo; Traveego Admin &#8212; WordPress Style</title>
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
                <div>TRAVEEGO</div>
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
                    Password: <code>admin@traveego</code>
                </div>
            </form>
        </div>

        <div class="login-back-link">
            <a href="/">&larr; Kembali ke Beranda Traveego Drop-Off</a>
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveego Master Admin &lsaquo; WordPress Style Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --wp-dark: #1d2327;
            --wp-darker: #101517;
            --wp-blue: #2271b1;
            --wp-blue-hover: #135e96;
            --wp-gray-bg: #f0f0f1;
            --wp-border: #dcdcde;
            --wp-text: #2c3338;
            --wp-text-muted: #646970;
            --accent-green: #16A34A;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: var(--wp-gray-bg);
            color: var(--wp-text);
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* 1. TOP MASTER ADMIN BAR */
        .wp-master-bar {
            background: var(--wp-dark);
            color: #c3c4c7;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            font-size: 13px;
            z-index: 1000;
            flex-shrink: 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        .bar-left, .bar-center, .bar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .wp-brand-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #fff;
            text-decoration: none;
            font-weight: 800;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .wp-brand-icon {
            background: var(--wp-blue);
            color: #fff;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 900;
            font-size: 14px;
        }

        /* TAB NAVIGATION PILLS */
        .nav-tabs-pill {
            display: flex;
            background: rgba(0,0,0,0.4);
            border-radius: 6px;
            padding: 3px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .btn-tab-pill {
            background: transparent;
            border: none;
            color: #94a3b8;
            padding: 5px 14px;
            border-radius: 4px;
            font-size: 12.5px;
            cursor: pointer;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-tab-pill:hover {
            color: #fff;
        }
        .btn-tab-pill.active {
            background: var(--wp-blue);
            color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        .bar-link {
            color: #c3c4c7;
            text-decoration: none;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s;
        }
        .bar-link:hover {
            color: #fff;
            background: rgba(255,255,255,0.1);
        }

        .user-tag {
            font-size: 12px;
            color: #e2e8f0;
            padding: 4px 8px;
            background: rgba(255,255,255,0.06);
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-logout {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.35);
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.15s;
        }
        .btn-logout:hover {
            background: #ef4444;
            color: #fff;
        }

        /* 2. TAB VIEW CONTAINERS */
        .admin-viewport {
            flex: 1;
            height: calc(100vh - 48px);
            position: relative;
            overflow: hidden;
        }

        .tab-frame {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: none;
            display: none;
            background: #fff;
        }
        .tab-frame.active {
            display: block;
        }
    </style>
</head>
<body>

    <!-- 1. TOP MASTER WORDPRESS BAR -->
    <header class="wp-master-bar">
        <div class="bar-left">
            <a href="/" target="_blank" class="wp-brand-badge" title="Lihat Website Depan">
                <div class="wp-brand-icon">W</div>
                <span>Traveego Admin</span>
            </a>

            <!-- TAB CONTROLLERS -->
            <div class="nav-tabs-pill">
                <button type="button" class="btn-tab-pill <?= $activeTab === 'cms' ? 'active' : '' ?>" id="tabBtnCms" onclick="switchAdminTab('cms')">
                    <span>🎨 Penyesuai Tampilan (CMS ala WP)</span>
                </button>
                <button type="button" class="btn-tab-pill <?= $activeTab === 'ops' ? 'active' : '' ?>" id="tabBtnOps" onclick="switchAdminTab('ops')">
                    <span>⚙️ Konsol Operasional &amp; Radar</span>
                </button>
                <button type="button" class="btn-tab-pill <?= $activeTab === 'training' ? 'active' : '' ?>" id="tabBtnTraining" onclick="switchAdminTab('training')" title="Buka Ruang Training CS AI Mbak Hasna">
                    <span>🧠 Training CS AI Hasna</span>
                </button>
            </div>
        </div>

        <div class="bar-center">
            <a href="/" target="_blank" class="bar-link">
                <span>🌐 Web Pelanggan ↗</span>
            </a>
            <a href="/driver" target="_blank" class="bar-link">
                <span>🚘 PWA Sopir ↗</span>
            </a>
        </div>

        <div class="bar-right">
            <div class="user-tag">
                <span>👤</span>
                <span><b>admin</b></span>
            </div>
            <a href="/admin?logout=1" class="btn-logout" title="Keluar dari akun admin">
                <span>🚪 Keluar</span>
            </a>
        </div>
    </header>

    <!-- 2. WORKSPACE FRAMES -->
    <main class="admin-viewport">
        <!-- FRAME 1: CMS FRONT-END CUSTOMIZER -->
        <iframe src="/admin-cms.html" class="tab-frame <?= $activeTab === 'cms' ? 'active' : '' ?>" id="frameCms"></iframe>

        <!-- FRAME 2: OPS CONSOLE & RADAR -->
        <iframe src="/admin.html" class="tab-frame <?= $activeTab === 'ops' ? 'active' : '' ?>" id="frameOps"></iframe>

        <!-- FRAME 3: DEDICATED RUANG TRAINING CS AI HASNA -->
        <iframe src="/admin-hasna.html" class="tab-frame <?= $activeTab === 'training' ? 'active' : '' ?>" id="frameTraining"></iframe>
    </main>

    <script>
        function switchAdminTab(tabName) {
            const btnCms = document.getElementById('tabBtnCms');
            const btnOps = document.getElementById('tabBtnOps');
            const btnTraining = document.getElementById('tabBtnTraining');
            const frameCms = document.getElementById('frameCms');
            const frameOps = document.getElementById('frameOps');
            const frameTraining = document.getElementById('frameTraining');

            btnCms.classList.remove('active');
            btnOps.classList.remove('active');
            btnTraining.classList.remove('active');
            frameCms.classList.remove('active');
            frameOps.classList.remove('active');
            frameTraining.classList.remove('active');

            if (tabName === 'cms') {
                btnCms.classList.add('active');
                frameCms.classList.add('active');
                window.history.replaceState(null, '', '/admin?tab=cms');
            } else if (tabName === 'training') {
                btnTraining.classList.add('active');
                frameTraining.classList.add('active');
                window.history.replaceState(null, '', '/admin?tab=training');
            } else {
                btnOps.classList.add('active');
                frameOps.classList.add('active');
                window.history.replaceState(null, '', '/admin?tab=ops');
            }
        }
    </script>
</body>
</html>
