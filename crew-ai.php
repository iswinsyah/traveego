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
        :root {
            --bg-dark: #070B14;
            --bg-card: rgba(15, 23, 42, 0.75);
            --bg-card-hover: rgba(30, 41, 59, 0.85);
            --border-color: rgba(255, 255, 255, 0.12);
            --border-glow: rgba(59, 130, 246, 0.4);
            --text-main: #F8FAFC;
            --text-muted: #94A3B8;
            --accent-blue: #3B82F6;
            --accent-cyan: #06B6D4;
            --accent-emerald: #10B981;
            --accent-amber: #F59E0B;
            --accent-rose: #F43F5E;
            --font-sans: 'Plus Jakarta Sans', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-sans);
            background: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-image: 
                radial-gradient(circle at 15% 20%, rgba(59, 130, 246, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 85% 80%, rgba(6, 182, 212, 0.12) 0%, transparent 40%);
            background-attachment: fixed;
        }

        /* ==============================================================
           1. LOGIN CARD STYLES
           ============================================================== */
        .login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-box {
            width: 100%;
            max-width: 440px;
            background: var(--bg-card);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 40px 32px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.7), 0 0 30px rgba(59, 130, 246, 0.15);
            position: relative;
            overflow: hidden;
        }

        .login-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent-blue), var(--accent-cyan));
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.35);
            color: #93C5FD;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }

        .login-title {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #FFFFFF, #93C5FD);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
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
            color: #CBD5E1;
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
            background: rgba(15, 23, 42, 0.9);
            border: 1.5px solid rgba(255, 255, 255, 0.14);
            border-radius: 12px;
            padding: 0 16px;
            color: #FFFFFF;
            font-family: inherit;
            font-size: 15px;
            transition: all 0.2s ease;
            outline: none;
        }

        .input-control:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
            background: rgba(15, 23, 42, 1);
        }

        /* Password input specific with eye button */
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
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .btn-toggle-eye:hover {
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.08);
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
            background: linear-gradient(135deg, var(--accent-blue), #2563EB);
            border: none;
            border-radius: 12px;
            color: #FFFFFF;
            font-size: 15px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.5);
            transition: all 0.2s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #2563EB, #1D4ED8);
            transform: translateY(-1px);
            box-shadow: 0 14px 28px -5px rgba(37, 99, 235, 0.6);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .alert-error {
            background: rgba(244, 63, 94, 0.15);
            border: 1px solid rgba(244, 63, 94, 0.35);
            color: #FDA4AF;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13.5px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ==============================================================
           2. DASHBOARD STYLES (LOGGED IN)
           ============================================================== */
        .dash-nav {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dash-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }

        .dash-logo-badge {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-cyan));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.4);
        }

        .dash-title {
            font-size: 17px;
            font-weight: 800;
            letter-spacing: -0.3px;
        }

        .dash-sub {
            font-size: 11.5px;
            color: var(--text-muted);
        }

        .dash-nav-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .live-clock-badge {
            font-family: var(--font-mono);
            font-size: 12.5px;
            color: #93C5FD;
            background: rgba(59, 130, 246, 0.12);
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 6px 14px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-logout {
            background: rgba(244, 63, 94, 0.12);
            border: 1px solid rgba(244, 63, 94, 0.3);
            color: #FDA4AF;
            padding: 7px 16px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-logout:hover {
            background: rgba(244, 63, 94, 0.25);
            color: #FFFFFF;
        }

        /* DASHBOARD BODY */
        .dash-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 28px 20px 80px;
            width: 100%;
        }

        /* STATUS WIDGETS */
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .status-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(12px);
        }

        .status-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .status-label {
            font-size: 12.5px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11.5px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .indicator-green {
            background: rgba(16, 185, 129, 0.15);
            color: #6EE7B7;
            border: 1px solid rgba(16, 185, 129, 0.35);
        }

        .indicator-amber {
            background: rgba(245, 158, 11, 0.15);
            color: #FCD34D;
            border: 1px solid rgba(245, 158, 11, 0.35);
        }

        .status-main-text {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .status-desc {
            font-size: 12.5px;
            color: var(--text-muted);
            line-height: 1.4;
        }

        /* TAB CONTROLS */
        .tab-nav {
            display: flex;
            gap: 10px;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 28px;
            overflow-x: auto;
            padding-bottom: 4px;
        }

        .tab-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            font-family: inherit;
            font-size: 14.5px;
            font-weight: 700;
            padding: 12px 18px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .tab-btn:hover {
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.05);
        }

        .tab-btn.active {
            color: #FFFFFF;
            background: rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(59, 130, 246, 0.35);
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.2);
        }

        .tab-panel {
            display: none;
        }

        .tab-panel.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* SECTION BOX */
        .section-box {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 26px;
            margin-bottom: 24px;
            backdrop-filter: blur(16px);
        }

        .section-box-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 14px;
        }

        .box-title {
            font-size: 18px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-action {
            background: linear-gradient(135deg, var(--accent-blue), #1D4ED8);
            border: none;
            border-radius: 10px;
            color: #FFFFFF;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            padding: 10px 18px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
        }

        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.45);
        }

        /* TABLE */
        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }

        .data-table th {
            background: rgba(15, 23, 42, 0.9);
            color: #94A3B8;
            font-weight: 700;
            text-align: left;
            padding: 12px 14px;
            border-bottom: 1.5px solid var(--border-color);
            white-space: nowrap;
        }

        .data-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            color: #E2E8F0;
        }

        .data-table tr:hover td {
            background: rgba(255, 255, 255, 0.03);
        }

        .badge-price {
            font-family: var(--font-mono);
            font-weight: 700;
            color: #6EE7B7;
            background: rgba(16, 185, 129, 0.12);
            padding: 4px 8px;
            border-radius: 6px;
            white-space: nowrap;
        }

        .tag-dynamic {
            font-family: var(--font-mono);
            font-size: 12px;
            background: rgba(245, 158, 11, 0.15);
            color: #FCD34D;
            border: 1px solid rgba(245, 158, 11, 0.3);
            padding: 2px 6px;
            border-radius: 4px;
        }

        /* CARDS GRID FOR POSTS / PAGES */
        .item-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 18px;
        }

        .item-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px;
            transition: all 0.25s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .item-card:hover {
            border-color: var(--border-glow);
            transform: translateY(-2px);
            background: rgba(15, 23, 42, 0.85);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
        }

        .item-title {
            font-size: 15.5px;
            font-weight: 700;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .item-desc {
            font-size: 12.5px;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 16px;
        }

        .item-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11.5px;
            color: var(--text-muted);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 12px;
        }

        /* TERMINAL CONSOLE */
        .terminal-box {
            background: #020617;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 16px;
            font-family: var(--font-mono);
            font-size: 12.5px;
            color: #38BDF8;
            min-height: 220px;
            max-height: 420px;
            overflow-y: auto;
            white-space: pre-wrap;
            line-height: 1.6;
        }
    </style>
</head>
<body>

<?php if (!$isLoggedIn): ?>
<!-- ==============================================================
     HALAMAN LOGIN KHUSUS CREW AI (/crew-ai)
     ============================================================== -->
<div class="login-wrap">
    <div class="login-box">
        <div class="login-header">
            <div class="login-badge">
                <span>🤖</span>
                <span>TRAVEEGO AI CREW</span>
            </div>
            <h1 class="login-title">Control Center</h1>
            <p class="login-sub">Masuk untuk mengelola Tim AI Agent, Cost Matrix, dan Otomatisasi Konten SEO.</p>
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
                    <!-- KOLOM USERNAME TANPA PLACEHOLDER -->
                    <input type="text" id="username" name="username" class="input-control" required autofocus autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrap">
                    <!-- KOLOM PASSWORD TANPA PLACEHOLDER + TOMBOL MATA -->
                    <input type="password" id="password" name="password" class="input-control input-password" required autocomplete="current-password">
                    <button type="button" class="btn-toggle-eye" id="togglePasswordBtn" title="Lihat password" aria-label="Lihat password">
                        <!-- Icon Eye Open -->
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
    // Fitur toggle lihat password (tanda mata)
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const pwdInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    toggleBtn.addEventListener('click', function() {
        if (pwdInput.type === 'password') {
            pwdInput.type = 'text';
            // Ubah icon ke mata tertutup (slashed eye)
            eyeIcon.innerHTML = `
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
            `;
            toggleBtn.title = "Sembunyikan password";
        } else {
            pwdInput.type = 'password';
            // Kembalikan ke icon mata terbuka
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
     DASHBOARD UTAMA CREW AI (SETELAH LOGIN)
     ============================================================== -->
<nav class="dash-nav">
    <a href="/crew-ai" class="dash-brand">
        <div class="dash-logo-badge">🤖</div>
        <div>
            <div class="dash-title">TRAVEEGO CREW AI</div>
            <div class="dash-sub">Multi-Agent Content & Pricing Engine</div>
        </div>
    </a>

    <div class="dash-nav-actions">
        <div class="live-clock-badge" id="liveClockBadge">
            <span>⏱️</span>
            <span id="liveClockText">--:--:-- WIB</span>
        </div>
        <a href="/crew-ai?logout=1" class="btn-logout">
            <span>🚪</span>
            <span>Keluar</span>
        </a>
    </div>
</nav>

<div class="dash-container">
    <!-- STATUS WIDGETS -->
    <div class="status-grid">
        <!-- 1. Status Jam Kerja Server -->
        <div class="status-card">
            <div class="status-card-header">
                <span class="status-label">Jam Operasional Server</span>
                <span class="status-indicator indicator-green" id="serverHoursIndicator">🟢 AKTIF</span>
            </div>
            <div class="status-main-text" id="serverHoursMain">06:00 &ndash; 23:00 WIB</div>
            <div class="status-desc" id="serverHoursDesc">PC Server aktif memproses riset & penulisan otomatis.</div>
        </div>

        <!-- 2. Status Otak AI PC Server (Tailscale) -->
        <div class="status-card">
            <div class="status-card-header">
                <span class="status-label">Otak AI Server (Tailscale)</span>
                <span class="status-indicator indicator-amber" id="ollamaIndicator">🟡 CEK KONEKSI</span>
            </div>
            <div class="status-main-text" id="ollamaMain">Qwen 2.5 Coder 7B</div>
            <div class="status-desc" id="ollamaDesc">Memeriksa Tailscale IP (100.85.151.80:11434)...</div>
            <div style="margin-top: 10px; display: flex; gap: 8px;">
                <button type="button" onclick="openServerConfigModal()" style="background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3); color: #93C5FD; font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 6px; cursor: pointer;">⚙️ Atur IP Tailscale / Model</button>
            </div>
        </div>

        <!-- 3. Rute & Konten Dihasilkan -->
        <div class="status-card">
            <div class="status-card-header">
                <span class="status-label">Produksi AI Crew</span>
                <span class="status-indicator indicator-green">⚡ SYNCED</span>
            </div>
            <div class="status-main-text"><span id="statRoutes">5</span> Rute | <span id="statPosts">2</span> Post</div>
            <div class="status-desc">Variabel dinamis & shortcode aktif terhubung.</div>
        </div>
    </div>

    <!-- TAB NAVIGATION -->
    <div class="tab-nav">
        <button class="tab-btn active" onclick="switchTab('matrixTab', this)">
            <span>📊</span>
            <span>Cost & Tariff Matrix (Intelligence)</span>
        </button>
        <button class="tab-btn" onclick="switchTab('pagesTab', this)">
            <span>📄</span>
            <span>Page Crew (Landing Page Rute)</span>
        </button>
        <button class="tab-btn" onclick="switchTab('postsTab', this)">
            <span>✍️</span>
            <span>Post Crew (Artikel Blog SEO)</span>
        </button>
        <button class="tab-btn" onclick="switchTab('terminalTab', this)">
            <span>⚡</span>
            <span>Pipeline Runner & Console</span>
        </button>
    </div>

    <!-- TAB 1: COST MATRIX -->
    <div class="tab-panel active" id="matrixTab">
        <div class="section-box">
            <div class="section-box-header">
                <div class="box-title">
                    <span>⛽</span>
                    <span>Single Source of Truth &ndash; Matrix Biaya Operasional</span>
                </div>
                <button class="btn-action" onclick="refreshData()">
                    <span>🔄</span>
                    <span>Segarkan Data</span>
                </button>
            </div>

            <p style="font-size: 13.5px; color: var(--text-muted); margin-bottom: 20px; line-height: 1.5;">
                Data ini menjadi acuan mutlak bagi seluruh Tim Penulis dan CS AI Tiara. Jika ada perubahan harga BBM atau tarif tol, angka di seluruh artikel dan halaman akan otomatis ikut berubah tanpa perlu ditulis ulang.
            </p>

            <div class="table-responsive">
                <table class="data-table" id="matrixTable">
                    <thead>
                        <tr>
                            <th>Rute Perjalanan</th>
                            <th>Jarak</th>
                            <th>Rincian Tol</th>
                            <th>Jasa Sopir</th>
                            <th>Tarif Avanza</th>
                            <th>Tarif Innova</th>
                            <th>Tarif Hiace</th>
                        </tr>
                    </thead>
                    <tbody id="matrixTableBody">
                        <tr><td colspan="7" style="text-align:center; color: var(--text-muted);">Memuat matrix biaya...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: PAGE CREW -->
    <div class="tab-panel" id="pagesTab">
        <div class="section-box">
            <div class="section-box-header">
                <div class="box-title">
                    <span>📄</span>
                    <span>Landing Page Penjualan yang Dihasilkan (Page Crew)</span>
                </div>
                <button class="btn-action" onclick="runPipeline()">
                    <span>✨</span>
                    <span>Generate Ulang Halaman</span>
                </button>
            </div>
            <div class="item-grid" id="pagesGrid">
                <div style="color: var(--text-muted);">Memuat daftar halaman...</div>
            </div>
        </div>
    </div>

    <!-- TAB 3: POST CREW -->
    <div class="tab-panel" id="postsTab">
        <div class="section-box">
            <div class="section-box-header">
                <div class="box-title">
                    <span>✍️</span>
                    <span>Artikel Blog SEO & Variabel Dinamis (Post Crew)</span>
                </div>
                <button class="btn-action" onclick="runPipeline()">
                    <span>✨</span>
                    <span>Tulis Artikel Baru</span>
                </button>
            </div>
            <div class="item-grid" id="postsGrid">
                <div style="color: var(--text-muted);">Memuat artikel blog...</div>
            </div>
        </div>
    </div>

    <!-- TAB 4: TERMINAL CONSOLE -->
    <div class="tab-panel" id="terminalTab">
        <div class="section-box">
            <div class="section-box-header">
                <div class="box-title">
                    <span>⚡</span>
                    <span>Terminal Console AI Orchestrator</span>
                </div>
                <button class="btn-action" onclick="runPipeline()" id="btnRunPipeline">
                    <span>🚀</span>
                    <span>Jalankan Seluruh AI Agent Sekarang</span>
                </button>
            </div>

            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 14px;">
                Memicu pipeline AI lokal untuk menghitung ulang biaya, memproduksi halaman rute, dan menulis artikel baru.
            </p>

            <div class="terminal-box" id="terminalOutput">Tekan tombol 'Jalankan Seluruh AI Agent Sekarang' untuk memulai eksekusi...</div>
        </div>
    </div>
</div>

<script>
    // Tab switching
    function switchTab(tabId, btn) {
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        btn.classList.add('active');
    }

    // Live Clock WIB
    function updateClock() {
        const now = new Date();
        const str = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
        document.getElementById('liveClockText').innerText = str;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Fetch and populate status
    async function loadStatus() {
        try {
            const res = await fetch('/api/crew.php?action=status');
            const d = await res.json();
            if (!d.success) return;

            // Jam Kerja
            const wh = d.working_hours;
            const hoursInd = document.getElementById('serverHoursIndicator');
            hoursInd.innerText = wh.is_working ? '🟢 AKTIF' : '🟡 ISTIRAHAT';
            hoursInd.className = 'status-indicator ' + (wh.is_working ? 'indicator-green' : 'indicator-amber');
            document.getElementById('serverHoursDesc').innerText = wh.status_text;

            // Ollama status
            const ol = d.ollama;
            const olInd = document.getElementById('ollamaIndicator');
            olInd.innerText = ol.connected ? '🟢 TERHUBUNG' : '🟡 STANDBY';
            olInd.className = 'status-indicator ' + (ol.connected ? 'indicator-green' : 'indicator-amber');
            document.getElementById('ollamaDesc').innerText = ol.message;

            // Stats
            document.getElementById('statRoutes').innerText = d.stats.routes_count;
            document.getElementById('statPosts').innerText = d.stats.posts_count;
        } catch(e) {
            console.error('Error fetching status:', e);
        }
    }

    // Fetch and populate matrix
    async function loadMatrix() {
        try {
            const res = await fetch('/api/crew.php?action=get_matrix');
            const d = await res.json();
            if (!d.success) return;

            const tbody = document.getElementById('matrixTableBody');
            tbody.innerHTML = '';

            const routes = d.data.routes || {};
            for (const key in routes) {
                const r = routes[key];
                const p = r.pricing || {};
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <strong>${r.origin} ➔ ${r.destination}</strong><br>
                        <small style="color: var(--text-muted); font-size: 11px;">/${r.slug}</small>
                    </td>
                    <td><span class="badge-price" style="background: rgba(59,130,246,0.15); color: #93C5FD;">${r.distance_km} KM</span></td>
                    <td style="font-size: 12px; color: var(--text-muted); max-width: 220px;">
                        <strong>Rp ${Number(r.toll_cost).toLocaleString('id-ID')}</strong><br>
                        ${r.toll_details || '-'}
                    </td>
                    <td>Rp ${Number(r.base_driver_fee).toLocaleString('id-ID')}</td>
                    <td><span class="badge-price">${p.avanza ? p.avanza.formatted_price : '-'}</span></td>
                    <td><span class="badge-price" style="color: #93C5FD; background: rgba(59,130,246,0.15);">${p.innova ? p.innova.formatted_price : '-'}</span></td>
                    <td><span class="badge-price" style="color: #FCD34D; background: rgba(245,158,11,0.15);">${p.hiace ? p.hiace.formatted_price : '-'}</span></td>
                `;
                tbody.appendChild(tr);
            }
        } catch(e) {
            console.error('Error loading matrix:', e);
        }
    }

    // Fetch and populate Pages
    async function loadPages() {
        try {
            const res = await fetch('/api/crew.php?action=get_pages');
            const d = await res.json();
            if (!d.success) return;

            const grid = document.getElementById('pagesGrid');
            grid.innerHTML = '';

            if (d.pages.length === 0) {
                grid.innerHTML = '<div style="color: var(--text-muted);">Belum ada halaman rute.</div>';
                return;
            }

            d.pages.forEach(p => {
                const page = p.data;
                const card = document.createElement('div');
                card.className = 'item-card';
                card.innerHTML = `
                    <div>
                        <div style="font-size: 11px; color: #93C5FD; font-weight: 700; margin-bottom: 6px;">PAGE CREW / LANDING PAGE</div>
                        <h3 class="item-title">${page.meta_title || p.file}</h3>
                        <p class="item-desc">${page.meta_description || '-'}</p>
                    </div>
                    <div class="item-meta">
                        <span>Slug: <code>/${page.slug || p.file}</code></span>
                        <span style="color: #6EE7B7; font-weight: 700;">All-In 0% DP</span>
                    </div>
                `;
                grid.appendChild(card);
            });
        } catch(e) {
            console.error('Error loading pages:', e);
        }
    }

    // Fetch and populate Posts
    async function loadPosts() {
        try {
            const res = await fetch('/api/crew.php?action=get_posts');
            const d = await res.json();
            if (!d.success) return;

            const grid = document.getElementById('postsGrid');
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
                        <h3 class="item-title">${post.title || p.file}</h3>
                        <p class="item-desc">${post.meta_description || '-'}</p>
                    </div>
                    <div class="item-meta">
                        <span>Target: <code>/${post.target_route_slug || ''}</code></span>
                        <span style="color: #FCD34D;">Placeholder Otomatis</span>
                    </div>
                `;
                grid.appendChild(card);
            });
        } catch(e) {
            console.error('Error loading posts:', e);
        }
    }

    // Run AI Pipeline
    async function runPipeline() {
        const btn = document.getElementById('btnRunPipeline');
        const term = document.getElementById('terminalOutput');
        
        switchTab('terminalTab', document.querySelectorAll('.tab-btn')[3]);

        btn.disabled = true;
        btn.innerHTML = '<span>⏳</span><span>Sedang Menjalankan Agent...</span>';
        term.innerText = 'Memicu orchestrator AI di PC Server lokal...\nMohon tunggu sejenak...\n';

        try {
            const res = await fetch('/api/crew.php?action=run_pipeline');
            const d = await res.json();
            term.innerText = d.log || 'Eksekusi selesai tanpa output log.';
            refreshData();
        } catch(e) {
            term.innerText += '\n[ERROR] Gagal memicu pipeline: ' + e.message;
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span>🚀</span><span>Jalankan Seluruh AI Agent Sekarang</span>';
        }
    }

    // Modal Pengaturan Server Tailscale
    let currentServerConfig = {};
    let availableModels = [];

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

    // Updated loadStatus
    async function loadStatus() {
        try {
            const res = await fetch('/api/crew.php?action=status');
            const d = await res.json();
            if (!d.success) return;

            // Jam Kerja
            const wh = d.working_hours;
            const hoursInd = document.getElementById('serverHoursIndicator');
            hoursInd.innerText = wh.is_working ? '🟢 AKTIF' : '🟡 ISTIRAHAT';
            hoursInd.className = 'status-indicator ' + (wh.is_working ? 'indicator-green' : 'indicator-amber');
            document.getElementById('serverHoursDesc').innerText = wh.status_text;

            // Ollama status via Tailscale
            const ol = d.ollama;
            currentServerConfig = ol.config || {};
            availableModels = ol.available_models || [];

            const olInd = document.getElementById('ollamaIndicator');
            olInd.innerText = ol.connected ? '🟢 TERHUBUNG' : '🟡 STANDBY';
            olInd.className = 'status-indicator ' + (ol.connected ? 'indicator-green' : 'indicator-amber');
            
            document.getElementById('ollamaMain').innerText = ol.model || 'qwen2.5-coder:7b';
            document.getElementById('ollamaDesc').innerText = ol.message;

            // Stats
            document.getElementById('statRoutes').innerText = d.stats.routes_count;
            document.getElementById('statPosts').innerText = d.stats.posts_count;
        } catch(e) {
            console.error('Error fetching status:', e);
        }
    }

    function refreshData() {
        loadStatus();
        loadMatrix();
        loadPages();
        loadPosts();
    }

    // Init
    refreshData();
</script>

<!-- MODAL PENGATURAN TAILSCALE SERVER -->
<div id="serverConfigModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); backdrop-filter:blur(10px); z-index:999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#0F172A; border:1px solid rgba(255,255,255,0.15); border-radius:20px; width:100%; max-width:480px; padding:28px; box-shadow:0 20px 50px rgba(0,0,0,0.6);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <div style="font-size:17px; font-weight:800; display:flex; align-items:center; gap:8px;">
                <span>⚙️</span>
                <span>Pengaturan Server AI (Tailscale)</span>
            </div>
            <button onclick="closeServerConfigModal()" style="background:none; border:none; color:#94A3B8; font-size:24px; cursor:pointer;">&times;</button>
        </div>

        <div style="margin-bottom:18px;">
            <label style="display:block; font-size:12.5px; font-weight:700; color:#93C5FD; margin-bottom:6px;">URL / IP Ollama Host (Tailscale)</label>
            <input type="text" id="cfgOllamaHost" style="width:100%; height:44px; background:#070B14; border:1px solid rgba(255,255,255,0.15); border-radius:10px; padding:0 14px; color:#fff; font-family:monospace; font-size:14px;">
            <small style="color:#64748B; font-size:11px; margin-top:4px; display:block;">Contoh: <code>http://100.85.151.80:11434</code> (PC Server Anda)</small>
        </div>

        <div style="margin-bottom:22px;">
            <label style="display:block; font-size:12.5px; font-weight:700; color:#93C5FD; margin-bottom:6px;">Pilihan Model AI</label>
            <select id="cfgModelSelect" style="width:100%; height:44px; background:#070B14; border:1px solid rgba(255,255,255,0.15); border-radius:10px; padding:0 14px; color:#fff; font-size:14px;"></select>
            <small style="color:#64748B; font-size:11px; margin-top:4px; display:block;">Daftar model diambil otomatis dari endpoint <code>/api/tags</code> server Ollama Anda.</small>
        </div>

        <div style="display:flex; gap:12px; justify-content:flex-end;">
            <button type="button" onclick="closeServerConfigModal()" style="background:rgba(255,255,255,0.08); border:none; color:#fff; padding:10px 18px; border-radius:10px; font-weight:700; cursor:pointer;">Batal</button>
            <button type="button" onclick="saveServerConfig()" id="btnSaveCfg" style="background:linear-gradient(135deg, #3B82F6, #1D4ED8); border:none; color:#fff; padding:10px 20px; border-radius:10px; font-weight:700; cursor:pointer;">Simpan & Test Koneksi</button>
        </div>
    </div>
</div>
<?php endif; ?>

</body>
</html>
