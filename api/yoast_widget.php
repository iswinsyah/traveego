<?php
/**
 * TRAVEEGO YOAST SEO FLOATING INDICATOR & INSPECTOR WIDGET
 * Menampilkan status SEO & Readability di setiap Halaman & Postingan
 */
if (!isset($yoastAnalysis) || empty($yoastAnalysis)) {
    return;
}

$seoScore = $yoastAnalysis['seo']['score'] ?? 0;
$seoStatus = $yoastAnalysis['seo']['status'] ?? 'ok';
$readScore = $yoastAnalysis['readability']['score'] ?? 0;
$readStatus = $yoastAnalysis['readability']['status'] ?? 'ok';
$keyphrase = $yoastAnalysis['focus_keyphrase'] ?? '';
$serp = $yoastAnalysis['serp_preview'] ?? [];
?>

<style>
/* Yoast Floating Widget CSS */
.yoast-widget-wrap {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 99999;
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
}

.yoast-pill-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #0F172A;
    color: #FFFFFF;
    padding: 10px 18px;
    border-radius: 999px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.1);
    cursor: pointer;
    font-size: 13px;
    font-weight: 700;
    transition: all 0.2s ease;
    user-select: none;
    border: none;
    outline: none;
}

.yoast-pill-btn:hover {
    transform: translateY(-2px);
    background: #1E293B;
    box-shadow: 0 14px 30px -4px rgba(15, 23, 42, 0.45);
}

.yoast-indicator-group {
    display: flex;
    align-items: center;
    gap: 6px;
}

.yoast-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}

.yoast-dot.dot-good {
    background: #10B981;
    box-shadow: 0 0 8px #10B981;
}

.yoast-dot.dot-ok {
    background: #F59E0B;
    box-shadow: 0 0 8px #F59E0B;
}

.yoast-dot.dot-poor {
    background: #EF4444;
    box-shadow: 0 0 8px #EF4444;
}

.yoast-sep {
    color: #475569;
    font-size: 12px;
}

/* Yoast Inspector Drawer */
.yoast-drawer {
    position: absolute;
    bottom: 54px;
    right: 0;
    width: 380px;
    max-width: calc(100vw - 32px);
    max-height: min(600px, 80vh);
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 20px;
    box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.25), 0 0 0 1px rgba(15, 23, 42, 0.05);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: yoastSlideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    color: #0F172A;
}

@keyframes yoastSlideUp {
    from { opacity: 0; transform: translateY(12px) scale(0.97); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.yoast-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
}

.yoast-title {
    font-size: 14px;
    font-weight: 800;
    color: #0F172A;
    display: flex;
    align-items: center;
    gap: 8px;
}

.yoast-close-btn {
    background: transparent;
    border: none;
    font-size: 16px;
    cursor: pointer;
    color: #64748B;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
}
.yoast-close-btn:hover { background: #E2E8F0; color: #0F172A; }

.yoast-focus-box {
    padding: 12px 20px;
    background: #F1F5F9;
    border-bottom: 1px solid #E2E8F0;
    font-size: 12px;
}
.yoast-focus-label { color: #64748B; font-weight: 600; margin-bottom: 2px; }
.yoast-focus-text { color: #0B4FD8; font-weight: 800; word-break: break-word; }

/* Tabs */
.yoast-tabs {
    display: flex;
    background: #FFFFFF;
    border-bottom: 1px solid #E2E8F0;
    padding: 4px 12px 0;
    gap: 4px;
}

.yoast-tab {
    flex: 1;
    padding: 10px 8px;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    font-size: 12px;
    font-weight: 700;
    color: #64748B;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.15s ease;
}

.yoast-tab.active {
    color: #0B4FD8;
    border-bottom-color: #0B4FD8;
}

.yoast-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px 20px;
    font-size: 13px;
}

.yoast-score-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
    padding-bottom: 12px;
    border-bottom: 1px dashed #E2E8F0;
}

.badge-yoast {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
}

.badge-yoast-good { background: #DCFCE7; color: #166534; }
.badge-yoast-ok { background: #FEF3C7; color: #92400E; }
.badge-yoast-poor { background: #FEE2E2; color: #991B1B; }

.yoast-checklist {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.yoast-check-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    line-height: 1.5;
}

.yoast-check-bullet {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-top: 5px;
    flex-shrink: 0;
}

.yoast-check-desc {
    color: #334155;
    font-size: 12.5px;
}
.yoast-check-desc strong {
    color: #0F172A;
}

/* Google SERP Card */
.serp-box {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 14px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.serp-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}
.serp-fav {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #0B4FD8;
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 12px;
}
.serp-brand { font-size: 12px; font-weight: 700; color: #202124; }
.serp-url { font-size: 11px; color: #4D5156; word-break: break-all; }
.serp-title {
    font-size: 15px;
    font-weight: 600;
    color: #1A0DAB;
    margin: 0 0 6px;
    line-height: 1.35;
}
.serp-desc {
    font-size: 12px;
    color: #4D5156;
    line-height: 1.5;
    margin: 0;
}

@media (max-width: 600px) {
    .yoast-widget-wrap { bottom: 16px; right: 16px; }
    .yoast-pill-btn { padding: 8px 14px; font-size: 12px; }
    .yoast-drawer { width: calc(100vw - 32px); bottom: 50px; }
}
</style>

<div class="yoast-widget-wrap" id="yoastWidget">
    <!-- Floating Pill Button -->
    <button type="button" class="yoast-pill-btn" onclick="toggleYoastInspector()" title="Klik untuk melihat Audit Yoast SEO & Keterbacaan">
        <span>🌿</span>
        <div class="yoast-indicator-group">
            <span class="yoast-dot dot-<?= $seoStatus ?>"></span>
            <span>SEO <?= $seoScore ?></span>
        </div>
        <span class="yoast-sep">|</span>
        <div class="yoast-indicator-group">
            <span class="yoast-dot dot-<?= $readStatus ?>"></span>
            <span>Baca <?= $readScore ?></span>
        </div>
        <span style="font-size: 10px; opacity: 0.7;">▲</span>
    </button>

    <!-- Slide-up Drawer Inspector -->
    <div class="yoast-drawer" id="yoastInspector" style="display: none;">
        <div class="yoast-header">
            <div class="yoast-title">
                <span>🌿</span>
                <span>Yoast SEO Inspector</span>
            </div>
            <button type="button" class="yoast-close-btn" onclick="toggleYoastInspector()" title="Tutup">✕</button>
        </div>

        <div class="yoast-focus-box">
            <div class="yoast-focus-label">Kata Kunci Fokus (Focus Keyphrase):</div>
            <div class="yoast-focus-text">"<?= htmlspecialchars($keyphrase) ?>"</div>
        </div>

        <div class="yoast-tabs">
            <button type="button" class="yoast-tab active" id="tabBtnSeo" onclick="openYoastTab('tabSeo', this)">
                <span class="yoast-dot dot-<?= $seoStatus ?>"></span>
                <span>SEO (<?= $seoScore ?>)</span>
            </button>
            <button type="button" class="yoast-tab" id="tabBtnRead" onclick="openYoastTab('tabRead', this)">
                <span class="yoast-dot dot-<?= $readStatus ?>"></span>
                <span>Keterbacaan (<?= $readScore ?>)</span>
            </button>
            <button type="button" class="yoast-tab" id="tabBtnSerp" onclick="openYoastTab('tabSerp', this)">
                <span>🔍 SERP</span>
            </button>
        </div>

        <div class="yoast-body">
            <!-- TAB 1: SEO -->
            <div id="tabSeo" class="yoast-pane">
                <div class="yoast-score-row">
                    <span class="badge-yoast badge-yoast-<?= $seoStatus ?>">
                        <?= $seoStatus === 'good' ? '🟢 Sangat Baik (Good)' : ($seoStatus === 'ok' ? '🟠 Cukup Baik (OK)' : '🔴 Perlu Perbaikan') ?>
                    </span>
                    <span style="font-size: 12px; color: #64748B; font-weight: 600;"><?= $yoastAnalysis['seo']['word_count'] ?? 0 ?> Kata</span>
                </div>
                <ul class="yoast-checklist">
                    <?php foreach (($yoastAnalysis['seo']['checks'] ?? []) as $c): ?>
                    <li class="yoast-check-item">
                        <span class="yoast-check-bullet dot-<?= $c['status'] ?>"></span>
                        <div class="yoast-check-desc">
                            <strong><?= htmlspecialchars($c['title']) ?>:</strong> <?= htmlspecialchars($c['message']) ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- TAB 2: READABILITY -->
            <div id="tabRead" class="yoast-pane" style="display: none;">
                <div class="yoast-score-row">
                    <span class="badge-yoast badge-yoast-<?= $readStatus ?>">
                        <?= $readStatus === 'good' ? '🟢 Sangat Baik (Good)' : ($readStatus === 'ok' ? '🟠 Cukup Baik (OK)' : '🔴 Perlu Perbaikan') ?>
                    </span>
                    <span style="font-size: 12px; color: #64748B; font-weight: 600;">Flesch: <?= $yoastAnalysis['readability']['flesch_score'] ?? 0 ?>/100</span>
                </div>
                <ul class="yoast-checklist">
                    <?php foreach (($yoastAnalysis['readability']['checks'] ?? []) as $c): ?>
                    <li class="yoast-check-item">
                        <span class="yoast-check-bullet dot-<?= $c['status'] ?>"></span>
                        <div class="yoast-check-desc">
                            <strong><?= htmlspecialchars($c['title']) ?>:</strong> <?= htmlspecialchars($c['message']) ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- TAB 3: SERP PREVIEW -->
            <div id="tabSerp" class="yoast-pane" style="display: none;">
                <p style="font-size: 12px; color: #64748B; margin-top: 0; margin-bottom: 12px;">Pratinjau tampilan cuplikan di Google Search:</p>
                <div class="serp-box">
                    <div class="serp-header">
                        <div class="serp-fav">T</div>
                        <div>
                            <div class="serp-brand">Traveego Indonesia</div>
                            <div class="serp-url"><?= htmlspecialchars($serp['displayed_url'] ?? '') ?></div>
                        </div>
                    </div>
                    <div class="serp-title"><?= htmlspecialchars($serp['title'] ?? '') ?></div>
                    <p class="serp-desc"><?= htmlspecialchars($serp['meta_description'] ?? '') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleYoastInspector() {
    const el = document.getElementById('yoastInspector');
    if (!el) return;
    el.style.display = (el.style.display === 'none' || el.style.display === '') ? 'flex' : 'none';
}

function openYoastTab(tabId, btn) {
    document.querySelectorAll('.yoast-pane').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.yoast-tab').forEach(b => b.classList.remove('active'));
    const target = document.getElementById(tabId);
    if (target) target.style.display = 'block';
    if (btn) btn.classList.add('active');
}
</script>
