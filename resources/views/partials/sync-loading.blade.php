{{-- ═══════════════════════════════════════════════════════════
     CFIP INTELLIGENCE UNIT — SYNC LOADING OVERLAY
     Always rendered so the manual refresh button (syncRefreshBtn)
     works regardless of the auto-sync setting.
     Auto-trigger only fires when an admin enables it in Settings.
     ═══════════════════════════════════════════════════════════ --}}
@php
    $settingsPath    = storage_path('app/app_settings.json');
    $appSettings     = file_exists($settingsPath)
                         ? (json_decode(file_get_contents($settingsPath), true) ?? [])
                         : [];
    $autoSyncEnabled = (bool) ($appSettings['auto_sync'] ?? false);
@endphp

<style>
/* ── Sync refresh button (added to topbars) ────────────── */
.sync-btn-wrap {
    position: relative;
    display: inline-flex;
    flex-shrink: 0;
}
.sync-cd-pip {
    position: absolute;
    bottom: -5px;
    right: -5px;
    background: #f7b84f;
    color: #78350f;
    font-size: 8px;
    font-weight: 700;
    padding: 1px 4px;
    border-radius: 10px;
    line-height: 1.4;
    white-space: nowrap;
    display: none;
    pointer-events: none;
}
.sync-cd-pip.visible { display: block; }
.sync-refresh-icon   { transition: transform .3s; }
.spin-slow           { animation: spinSlow 1.1s linear infinite; }
@keyframes spinSlow { to { transform: rotate(360deg); } }

/* ── Overlay backdrop ──────────────────────────────────── */
#cfipSyncOverlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(4, 8, 22, 0.97);
    align-items: center;
    justify-content: center;
    font-family: 'Courier New', Courier, monospace;
}
#cfipSyncOverlay.active { display: flex; }

/* ── Main panel ────────────────────────────────────────── */
.inv-panel {
    width: 580px;
    max-width: 95vw;
    background: #080f23;
    border: 1px solid #00d4aa;
    border-radius: 4px;
    box-shadow: 0 0 40px rgba(0,212,170,.2), 0 0 80px rgba(0,212,170,.05);
    overflow: hidden;
    animation: invFadeIn .35s ease;
}
@keyframes invFadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Header ────────────────────────────────────────────── */
.inv-header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 22px;
    background: #0d1526;
    border-bottom: 1px solid #162040;
}
.inv-logo-wrap {
    width: 40px;
    height: 40px;
    border: 1px solid #00d4aa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    animation: invPulse 2.5s ease-in-out infinite;
}
@keyframes invPulse {
    0%,100% { box-shadow: 0 0 6px rgba(0,212,170,.4); }
    50%      { box-shadow: 0 0 18px rgba(0,212,170,.8); }
}
.inv-logo-wrap svg { color: #00d4aa; }
.inv-title-block { flex: 1; }
.inv-agency  { font-size: 11px; font-weight: 700; letter-spacing: .12em; color: #00d4aa; text-transform: uppercase; }
.inv-case-id { font-size: 10px; color: #4a9eff; letter-spacing: .06em; margin-top: 2px; }
.inv-classified {
    background: #b91c1c; color: #fff;
    font-size: 9px; font-weight: 700; letter-spacing: .12em;
    padding: 3px 8px; border-radius: 2px; text-transform: uppercase; flex-shrink: 0;
}

/* ── Body ──────────────────────────────────────────────── */
.inv-body { padding: 20px 22px; }

.inv-current-step {
    font-size: 12px; color: #f7b84f;
    letter-spacing: .06em; text-transform: uppercase;
    min-height: 18px; margin-bottom: 12px;
    display: flex; align-items: center; gap: 8px;
}
.inv-cursor {
    display: inline-block; width: 8px; height: 13px;
    background: #f7b84f; animation: blink .7s step-start infinite; flex-shrink: 0;
}
@keyframes blink { 50% { opacity: 0; } }

.inv-track {
    background: #111d36; border: 1px solid #162040;
    border-radius: 2px; height: 8px; margin-bottom: 6px; overflow: hidden;
}
.inv-bar {
    height: 100%; width: 0%;
    background: linear-gradient(90deg, #00d4aa, #4a9eff);
    transition: width .6s cubic-bezier(.4,0,.2,1);
    border-radius: 2px;
}
.inv-progress-meta {
    display: flex; justify-content: space-between;
    font-size: 10px; color: #3a5080; letter-spacing: .04em; margin-bottom: 18px;
}

.inv-log-header {
    font-size: 9px; font-weight: 700; letter-spacing: .14em;
    color: #2a4070; text-transform: uppercase;
    border-top: 1px solid #111d36; padding-top: 14px; margin-bottom: 10px;
}
.inv-steps { display: flex; flex-direction: column; gap: 4px; }

.inv-step {
    display: flex; align-items: center; gap: 10px;
    padding: 5px 0; border-bottom: 1px solid #0d1526;
}
.inv-step-icon {
    width: 16px; height: 16px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
/* pending */
.inv-step.pending .inv-step-icon::before { content: '○'; font-size: 12px; color: #2a4070; }
.inv-step.pending .inv-step-num          { color: #1e3055; }
.inv-step.pending .inv-step-label        { color: #2a4070; }
.inv-step.pending .inv-step-badge        { color: #1e3055; background: #080f23; border-color: #111d36; }
/* active */
.inv-step.active .inv-step-icon { animation: spin 1.2s linear infinite; }
.inv-step.active .inv-step-icon::before { content: '◌'; font-size: 12px; color: #f7b84f; }
@keyframes spin { to { transform: rotate(360deg); } }
.inv-step.active .inv-step-num   { color: #f7b84f; }
.inv-step.active .inv-step-label { color: #e0c070; }
.inv-step.active .inv-step-badge { color: #f7b84f; background: rgba(247,184,79,.08); border-color: rgba(247,184,79,.25); }
/* done */
.inv-step.done .inv-step-icon::before { content: '✓'; font-size: 11px; color: #00d4aa; font-weight: 700; }
.inv-step.done .inv-step-num          { color: #00d4aa; }
.inv-step.done .inv-step-label        { color: #00a882; }
.inv-step.done .inv-step-badge        { color: #00d4aa; background: rgba(0,212,170,.08); border-color: rgba(0,212,170,.25); }

.inv-step-num   { font-size: 9px; letter-spacing: .1em; width: 50px; flex-shrink: 0; transition: color .3s; }
.inv-step-label { flex: 1; font-size: 11px; transition: color .3s; }
.inv-step-badge {
    font-size: 9px; letter-spacing: .08em;
    padding: 2px 7px; border-radius: 2px; border: 1px solid;
    flex-shrink: 0; text-transform: uppercase; transition: all .3s;
}

/* ── Footer ────────────────────────────────────────────── */
.inv-footer {
    padding: 12px 22px; background: #0d1526;
    border-top: 1px solid #111d36;
    display: flex; align-items: center; justify-content: space-between;
}
.inv-scanline { font-size: 10px; color: #2a4070; letter-spacing: .06em; }
.inv-standby  { font-size: 10px; color: #2a4070; letter-spacing: .08em; text-transform: uppercase; }

/* ── Error bar ─────────────────────────────────────────── */
.inv-error-bar {
    display: none; margin: 0 22px 16px;
    padding: 10px 14px;
    background: rgba(185,28,28,.12); border: 1px solid rgba(185,28,28,.4);
    border-radius: 2px; color: #f87171; font-size: 11px;
    align-items: center; justify-content: space-between; gap: 12px;
}
.inv-error-bar.visible { display: flex; }
.inv-skip-btn {
    background: none; border: 1px solid #f87171; color: #f87171;
    border-radius: 2px; padding: 3px 10px;
    font-size: 10px; font-family: inherit; cursor: pointer;
    letter-spacing: .06em; text-transform: uppercase; white-space: nowrap;
    transition: background .2s;
}
.inv-skip-btn:hover { background: rgba(248,113,113,.15); }

/* ── Done flash ────────────────────────────────────────── */
.inv-done-flash {
    text-align: center; padding: 8px 22px 0;
    font-size: 11px; color: #00d4aa;
    letter-spacing: .1em; text-transform: uppercase;
    display: none; animation: invFadeIn .4s ease;
}
.inv-done-flash.visible { display: block; }
</style>

{{-- ── OVERLAY MARKUP ───────────────────────────────────────── --}}
<div id="cfipSyncOverlay">
    <div class="inv-panel">

        <div class="inv-header">
            <div class="inv-logo-wrap">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
            </div>
            <div class="inv-title-block">
                <div class="inv-agency">CFIP Intelligence Unit</div>
                <div class="inv-case-id">CASE FILE #SYS-DATASYNC &nbsp;·&nbsp; {{ now()->format('Y-m-d') }}</div>
            </div>
            <div class="inv-classified">Classified</div>
        </div>

        <div class="inv-body">
            <div class="inv-current-step">
                <span class="inv-cursor"></span>
                <span id="invCurrentStep">Initialising investigation...</span>
            </div>
            <div class="inv-track">
                <div class="inv-bar" id="invBar"></div>
            </div>
            <div class="inv-progress-meta">
                <span id="invStepCount">0 / 8 steps secured</span>
                <span id="invPct">0%</span>
            </div>
            <div class="inv-log-header">Evidence Log</div>
            <div class="inv-steps" id="invStepsList"></div>
            <div class="inv-done-flash" id="invDoneFlash">
                ✓ All evidence secured — loading latest data...
            </div>
        </div>

        <div class="inv-error-bar" id="invErrorBar">
            <span id="invErrorMsg">Sync encountered an issue.</span>
            <button class="inv-skip-btn" onclick="cfipDismissSync()">View cached data</button>
        </div>

        <div class="inv-footer">
            <span class="inv-scanline" id="invScanline">▓▓▓▓▓░░░░░░░░░░░░░░░</span>
            <span class="inv-standby">Intelligence gathering in progress...</span>
        </div>

    </div>
</div>

<script>
(function () {
    'use strict';

    /* ── Constants ────────────────────────────────────────── */
    var AUTO_SYNC    = {{ $autoSyncEnabled ? 'true' : 'false' }};
    var COOLDOWN_MS  = 5 * 60 * 1000;   // 5 minutes between syncs
    var POLL_MS      = 2000;
    var STEP_TOTAL   = 8;

    var STEP_LABELS = [
        'Gathering agent records',
        'Scanning department databases',
        'Cross-referencing cohort data',
        'Pulling enrollment manifests',
        'Cataloguing training modules',
        'Extracting content intelligence',
        'Compiling learner performance records',
        'Finalising evidence mapping',
    ];

    var SCANLINE_FRAMES = [
        '▓▓▓▓▓░░░░░░░░░░░░░░░','░▓▓▓▓▓░░░░░░░░░░░░░░','░░▓▓▓▓▓░░░░░░░░░░░░░',
        '░░░▓▓▓▓▓░░░░░░░░░░░░','░░░░▓▓▓▓▓░░░░░░░░░░░','░░░░░▓▓▓▓▓░░░░░░░░░░',
        '░░░░░░▓▓▓▓▓░░░░░░░░░','░░░░░░░▓▓▓▓▓░░░░░░░░','░░░░░░░░▓▓▓▓▓░░░░░░░',
        '░░░░░░░░░▓▓▓▓▓░░░░░░','░░░░░░░░░░▓▓▓▓▓░░░░░','░░░░░░░░░░░▓▓▓▓▓░░░░',
        '░░░░░░░░░░░░▓▓▓▓▓░░░','░░░░░░░░░░░░░▓▓▓▓▓░░','░░░░░░░░░░░░░░▓▓▓▓▓░',
        '░░░░░░░░░░░░░░░▓▓▓▓▓',
    ];

    /* ── DOM refs ─────────────────────────────────────────── */
    var overlay       = document.getElementById('cfipSyncOverlay');
    var barEl         = document.getElementById('invBar');
    var stepCountEl   = document.getElementById('invStepCount');
    var pctEl         = document.getElementById('invPct');
    var currentStepEl = document.getElementById('invCurrentStep');
    var stepsList     = document.getElementById('invStepsList');
    var errorBar      = document.getElementById('invErrorBar');
    var errorMsg      = document.getElementById('invErrorMsg');
    var doneFlash     = document.getElementById('invDoneFlash');
    var scanlineEl    = document.getElementById('invScanline');

    /* ── State ────────────────────────────────────────────── */
    var isSyncing  = false;
    var dismissed  = false;
    var pollTimer  = null;
    var scanTimer  = null;
    var cdTimer    = null;   // cooldown badge refresh
    var scanIdx    = 0;
    var stepsBuilt = false;

    /* ── Cooldown helpers ─────────────────────────────────── */
    function cooldownSecs() {
        var last = parseInt(sessionStorage.getItem('cfip_last_sync') || '0', 10);
        return Math.max(0, Math.ceil((COOLDOWN_MS - (Date.now() - last)) / 1000));
    }
    window.cfipCooldownSecs = cooldownSecs;

    function fmtCd(secs) {
        if (secs <= 0) return '';
        return secs >= 60 ? Math.ceil(secs / 60) + 'm' : secs + 's';
    }

    /* ── Refresh-button state ─────────────────────────────── */
    function updateBtn() {
        var btn  = document.getElementById('syncRefreshBtn');
        var pip  = document.getElementById('syncCdPip');
        var icon = btn && btn.querySelector('.sync-refresh-icon');
        if (!btn) return;

        if (isSyncing) {
            btn.disabled = true;
            btn.title    = 'Sync in progress…';
            if (icon) icon.classList.add('spin-slow');
            if (pip)  { pip.textContent = ''; pip.classList.remove('visible'); }
        } else {
            btn.disabled = false;
            if (icon) icon.classList.remove('spin-slow');
            var rem = cooldownSecs();
            if (rem > 0) {
                var label = fmtCd(rem);
                if (pip)  { pip.textContent = label; pip.classList.add('visible'); }
                btn.title = 'Data was recently synced — available again in ' + label + '.';
            } else {
                if (pip)  { pip.textContent = ''; pip.classList.remove('visible'); }
                btn.title = 'Refresh data from iSpring';
            }
        }
    }

    function startCdTicker() {
        clearInterval(cdTimer);
        cdTimer = setInterval(updateBtn, 10000); // refresh badge every 10s
    }

    /* ── Overlay show / hide ──────────────────────────────── */
    function showOverlay() {
        isSyncing = true;
        dismissed = false;
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        updateBtn();
    }

    function hideOverlay() {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    /* ── Build step rows ──────────────────────────────────── */
    function buildStepList() {
        if (stepsBuilt) {
            // Reset all steps to pending for a fresh run
            for (var j = 0; j < STEP_LABELS.length; j++) {
                var r = document.getElementById('invStep' + j);
                if (r) { r.className = 'inv-step pending'; r.querySelector('.inv-step-badge').textContent = 'Pending'; }
            }
            return;
        }
        stepsBuilt = true;
        STEP_LABELS.forEach(function (label, i) {
            var row = document.createElement('div');
            row.className = 'inv-step pending';
            row.id = 'invStep' + i;
            row.innerHTML =
                '<div class="inv-step-icon"></div>' +
                '<span class="inv-step-num">STEP ' + (i < 9 ? '0' : '') + (i + 1) + '</span>' +
                '<span class="inv-step-label">' + label + '</span>' +
                '<span class="inv-step-badge">Pending</span>';
            stepsList.appendChild(row);
        });
    }

    /* ── Scanline ─────────────────────────────────────────── */
    function startScanline() {
        scanTimer = setInterval(function () {
            scanlineEl.textContent = SCANLINE_FRAMES[scanIdx % SCANLINE_FRAMES.length];
            scanIdx++;
        }, 120);
    }
    function stopScanline() {
        clearInterval(scanTimer);
        scanlineEl.textContent = '▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓';
    }

    /* ── Reset panel for a fresh run ─────────────────────── */
    function resetPanel() {
        barEl.style.width = '0%';
        stepCountEl.textContent = '0 / ' + STEP_TOTAL + ' steps secured';
        pctEl.textContent = '0%';
        currentStepEl.textContent = 'Initialising investigation...';
        doneFlash.classList.remove('visible');
        errorBar.classList.remove('visible');
    }

    /* ── Trigger sync on server ───────────────────────────── */
    function triggerSyncInternal() {
        showOverlay();
        buildStepList();
        resetPanel();
        startScanline();

        fetch('{{ route("api.refresh") }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.cooldown) {
                // Server says we're in cooldown — just poll existing status
                startPolling();
                return;
            }
            startPolling();
        })
        .catch(function () {
            isSyncing = false;
            hideOverlay();
            updateBtn();
        });
    }

    function startPolling() {
        clearInterval(pollTimer);
        pollTimer = setInterval(pollStatus, POLL_MS);
    }

    /* ── Poll ─────────────────────────────────────────────── */
    function pollStatus() {
        fetch('{{ route("api.sync.status") }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) { applyStatus(data); })
        .catch(function () { /* network blip */ });
    }

    /* ── Apply status ─────────────────────────────────────── */
    function applyStatus(data) {
        var done  = parseInt(data.steps_done  || 0, 10);
        var total = parseInt(data.steps_total || STEP_TOTAL, 10);
        var pct   = total > 0 ? Math.round((done / total) * 100) : 0;

        barEl.style.width = pct + '%';
        stepCountEl.textContent = done + ' / ' + total + ' steps secured';
        pctEl.textContent = pct + '%';
        if (data.step) currentStepEl.textContent = data.step;

        for (var i = 0; i < STEP_LABELS.length; i++) {
            var row   = document.getElementById('invStep' + i);
            if (!row) continue;
            var badge = row.querySelector('.inv-step-badge');
            if (i < done) {
                row.className = 'inv-step done';
                badge.textContent = 'Secured';
            } else if (i === done && data.status !== 'done') {
                row.className = 'inv-step active';
                badge.textContent = 'In Progress';
            } else {
                row.className = 'inv-step pending';
                badge.textContent = 'Pending';
            }
        }

        if (data.status === 'done')   { onSyncDone(total); }
        if (data.status === 'failed') { onSyncFailed(data.error || 'Sync failed.'); }
    }

    /* ── Completion ───────────────────────────────────────── */
    function onSyncDone(total) {
        if (dismissed) return;
        clearInterval(pollTimer);
        stopScanline();

        for (var i = 0; i < STEP_LABELS.length; i++) {
            var row = document.getElementById('invStep' + i);
            if (row) { row.className = 'inv-step done'; row.querySelector('.inv-step-badge').textContent = 'Secured'; }
        }
        barEl.style.width = '100%';
        stepCountEl.textContent = total + ' / ' + total + ' steps secured';
        pctEl.textContent = '100%';
        currentStepEl.textContent = 'Investigation complete.';
        doneFlash.classList.add('visible');

        isSyncing = false;
        sessionStorage.setItem('cfip_last_sync', Date.now().toString());
        updateBtn();

        // Set flag so the imminent reload doesn't re-trigger auto-sync
        sessionStorage.setItem('cfip_sync_done', '1');

        setTimeout(function () {
            window.location.reload();
        }, 1800);
    }

    /* ── Failure ──────────────────────────────────────────── */
    function onSyncFailed(msg) {
        if (dismissed) return;
        clearInterval(pollTimer);
        stopScanline();
        isSyncing = false;
        updateBtn();
        errorMsg.textContent = msg;
        errorBar.classList.add('visible');
        currentStepEl.textContent = 'Sync interrupted — see error below.';
    }

    /* ── Public API ───────────────────────────────────────── */

    // Called by the ↻ refresh button in any topbar
    window.cfipRequestSync = function () {
        if (isSyncing) return;
        var rem = cooldownSecs();
        if (rem > 0) {
            // Shake the button to signal cooldown
            var btn = document.getElementById('syncRefreshBtn');
            if (btn) {
                btn.style.transition = 'transform .1s';
                btn.style.transform  = 'translateX(3px)';
                setTimeout(function () { btn.style.transform = 'translateX(-3px)'; }, 100);
                setTimeout(function () { btn.style.transform = ''; }, 200);
            }
            return;
        }
        triggerSyncInternal();
    };

    window.cfipDismissSync = function () {
        dismissed = true;
        isSyncing = false;
        clearInterval(pollTimer);
        clearInterval(scanTimer);
        hideOverlay();
        updateBtn();
    };

    /* ── Auto-trigger on page load (admin setting) ────────── */
    function init() {
        // Always wire up button state on load
        updateBtn();
        startCdTicker();

        if (!AUTO_SYNC) return;

        // Skip if this is the reload triggered after a completed sync
        if (sessionStorage.getItem('cfip_sync_done')) {
            sessionStorage.removeItem('cfip_sync_done');
            return;
        }

        // Skip if synced recently
        if (cooldownSecs() > 0) return;

        triggerSyncInternal();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
</script>
