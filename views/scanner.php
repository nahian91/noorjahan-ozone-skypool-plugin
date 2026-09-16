<?php
/**
 * View: Gate Turnstile Control & QR Validator Console (Executive Edition)
 *
 * @package Ozone_Skypool_OS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$currency = esc_html( get_option( 'ifs_pms_currency', 'BDT' ) );
?>

<style>
    /* ==========================================================================
       EXECUTIVE TURNSTILE GATE & SCANNER CONSOLE
       ========================================================================== */
    .oz-scanner-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(360px, 0.85fr);
        gap: 26px;
        align-items: start;
        box-sizing: border-box;
        width: 100%;
    }

    @media (max-width: 1200px) {
        .oz-scanner-layout {
            grid-template-columns: 1fr;
        }
    }

    .oz-panel-card {
        background: var(--ifs-surface, #ffffff);
        border: 1px solid var(--ifs-border-subtle, #e2e8f0);
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.03);
        box-sizing: border-box;
        overflow: hidden;
    }

    .oz-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 22px 28px;
        border-bottom: 1px solid var(--ifs-border-subtle, #e2e8f0);
        background: var(--ifs-surface-hover, #fafbfd);
    }

    .oz-panel-title {
        margin: 0;
        font-size: 16px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--ifs-text-primary, #0f172a);
    }

    .oz-console-body {
        padding: 28px;
        display: flex;
        flex-direction: column;
        gap: 22px;
        box-sizing: border-box;
    }

    /* Electronic Turnstile Relay Card */
    .oz-barrier-relay-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--ifs-surface-hover, #f8fafc);
        border: 1.5px solid var(--ifs-border-subtle, #e2e8f0);
        border-radius: 16px;
        padding: 16px 20px;
        transition: all 0.3s ease;
    }

    .oz-relay-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 11.5px;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        padding: 7px 16px;
        border-radius: 9999px;
        transition: all 0.25s ease;
    }

    .oz-relay-status-pill.closed {
        background: var(--ifs-danger-soft, rgba(239, 68, 68, 0.12));
        color: var(--ifs-danger, #ef4444);
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .oz-relay-status-pill.open {
        background: var(--ifs-success-soft, rgba(16, 185, 129, 0.12));
        color: var(--ifs-success, #10b981);
        border: 1px solid rgba(16, 185, 129, 0.35);
        box-shadow: 0 0 16px rgba(16, 185, 129, 0.35);
    }

    /* Optical Viewfinder HUD */
    .oz-scanner-viewport {
        position: relative;
        width: 100%;
        max-width: 480px;
        height: 290px;
        margin: 0 auto;
        border-radius: 18px;
        background: #020617;
        border: 2px solid var(--ifs-border-subtle, #e2e8f0);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        box-shadow: inset 0 0 40px rgba(0, 0, 0, 0.8), 0 10px 28px rgba(15, 23, 42, 0.08);
    }

    .oz-scanner-video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: none;
    }

    .oz-hud-reticle {
        position: absolute;
        width: 28px;
        height: 28px;
        border-color: var(--ifs-accent, #38bdf8);
        border-style: solid;
        z-index: 6;
        pointer-events: none;
        transition: all 0.2s ease;
    }

    .oz-hud-reticle.top-left { top: 16px; left: 16px; border-width: 3px 0 0 3px; border-top-left-radius: 8px; }
    .oz-hud-reticle.top-right { top: 16px; right: 16px; border-width: 3px 3px 0 0; border-top-right-radius: 8px; }
    .oz-hud-reticle.bottom-left { bottom: 16px; left: 16px; border-width: 0 0 3px 3px; border-bottom-left-radius: 8px; }
    .oz-hud-reticle.bottom-right { bottom: 16px; right: 16px; border-width: 0 3px 3px 0; border-bottom-right-radius: 8px; }

    .oz-laser-beam {
        position: absolute;
        left: 6%;
        right: 6%;
        height: 2px;
        background: linear-gradient(90deg, transparent, var(--ifs-accent, #38bdf8), transparent);
        box-shadow: 0 0 18px var(--ifs-accent, #38bdf8), 0 0 8px #ffffff;
        animation: ozLaserSweep 2.2s ease-in-out infinite alternate;
        z-index: 5;
        pointer-events: none;
    }

    @keyframes ozLaserSweep {
        0% { top: 14%; opacity: 0.2; }
        50% { opacity: 1; }
        100% { top: 86%; opacity: 0.2; }
    }

    /* Form Fields & High-Visibility Token Input */
    .oz-field-group {
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    .oz-field-label {
        font-size: 12.5px;
        font-weight: 700;
        color: var(--ifs-text-secondary, #334155);
        margin-bottom: 8px;
        display: block;
    }

    #wpcontent .oz-scanner-layout input[type="text"] {
        display: block !important;
        width: 100% !important;
        background: var(--ifs-surface, #ffffff) !important;
        border: 2px solid var(--ifs-border-strong, #cbd5e1) !important;
        border-radius: 14px !important;
        padding: 12px 52px 12px 18px !important;
        font-size: 19px !important;
        font-weight: 800 !important;
        letter-spacing: 2px !important;
        text-transform: uppercase !important;
        text-align: center !important;
        color: var(--ifs-text-primary, #0f172a) !important;
        height: 54px !important;
        box-sizing: border-box !important;
        transition: border-color 0.15s ease, box-shadow 0.15s ease !important;
    }

    #wpcontent .oz-scanner-layout input[type="text"]:focus {
        border-color: var(--ifs-border-focus, #0284c7) !important;
        box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.18) !important;
        outline: none !important;
    }

    /* Dedicated Status Banner */
    .oz-scanner-status-bar {
        padding: 16px 20px;
        border-radius: 14px;
        font-weight: 700;
        font-size: 13.5px;
        text-align: center;
        background: var(--ifs-surface-hover, #f8fafc);
        border: 1px solid var(--ifs-border-subtle, #e2e8f0);
        color: var(--ifs-text-secondary, #475569);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* Verification Telemetry Stream Cards */
    .oz-telemetry-result-card {
        background: var(--ifs-surface-hover, #f8fafc);
        border: 1.5px solid var(--ifs-border-subtle, #e2e8f0);
        border-radius: 16px;
        padding: 20px 22px;
        margin-bottom: 22px;
        box-sizing: border-box;
        transition: border-color 0.2s ease, background 0.2s ease;
    }

    .oz-feed-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 18px;
        background: var(--ifs-surface-hover, #f8fafc);
        border: 1px solid var(--ifs-border-subtle, #e2e8f0);
        border-radius: 12px;
        margin-bottom: 10px;
        transition: background 0.15s ease, transform 0.15s ease;
    }

    .oz-feed-item:hover {
        background: var(--ifs-surface-active, #f1f5f9);
        transform: translateY(-1px);
    }

    /* Force-Override for Button System Inside Scanner View */
    #wpcontent .oz-scanner-layout .oz-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
        font-family: var(--ifs-font-sans, inherit) !important;
        font-weight: 700 !important;
        text-decoration: none !important;
        cursor: pointer !important;
        box-sizing: border-box !important;
        outline: none !important;
        transition: all 0.18s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }

    #wpcontent .oz-scanner-layout .oz-btn-primary.oz-btn-lg {
        height: 52px !important;
        padding: 0 24px !important;
        font-size: 14.5px !important;
        font-weight: 800 !important;
        border-radius: 12px !important;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
        color: #ffffff !important;
        border: 1px solid rgba(2, 132, 199, 0.8) !important;
        box-shadow: 0 4px 16px rgba(2, 132, 199, 0.35) !important;
    }

    #wpcontent .oz-scanner-layout .oz-btn-primary.oz-btn-lg:hover {
        background: linear-gradient(135deg, #0369a1 0%, #075985 100%) !important;
        box-shadow: 0 6px 22px rgba(2, 132, 199, 0.45) !important;
        transform: translateY(-1px);
    }

    #wpcontent .oz-scanner-layout .oz-btn-secondary {
        height: 44px !important;
        padding: 0 18px !important;
        font-size: 13px !important;
        font-weight: 700 !important;
        border-radius: 12px !important;
        background: var(--ifs-surface, #ffffff) !important;
        color: var(--ifs-text-secondary, #475569) !important;
        border: 1.5px solid var(--ifs-border-strong, #cbd5e1) !important;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04) !important;
    }

    #wpcontent .oz-scanner-layout .oz-btn-secondary:hover {
        background: var(--ifs-surface-hover, #f8fafc) !important;
        border-color: var(--ifs-border-focus, #0284c7) !important;
        color: var(--ifs-accent, #0284c7) !important;
        transform: translateY(-1px);
    }
</style>

<div class="oz-scanner-layout">
    <!-- LEFT: Main Validator Console -->
    <div class="oz-panel-card">
        <div class="oz-panel-head">
            <h3 class="oz-panel-title">
                <i class="fa-solid fa-turnstile" style="color: var(--ifs-accent, #0284c7);"></i>
                <?php esc_html_e( 'Gate Turnstile Validation Console', 'ozone-skypool' ); ?>
            </h3>
            <span class="ifs-pms-badge ifs-pms-badge-success">
                <span class="ifs-pms-pulse-dot"></span> <?php esc_html_e( 'Relay Port Online', 'ozone-skypool' ); ?>
            </span>
        </div>

        <div class="oz-console-body">
            <!-- Hardware Barrier Simulation Status -->
            <div class="oz-barrier-relay-bar">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="width: 44px; height: 44px; border-radius: 12px; background: var(--ifs-primary-soft, rgba(2, 132, 199, 0.1)); color: var(--ifs-accent, #0284c7); display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="fa-solid fa-microchip"></i>
                    </div>
                    <div>
                        <div style="font-size: 14px; font-weight: 800; color: var(--ifs-text-primary, #0f172a);"><?php esc_html_e( 'Electronic Gate Barrier #01', 'ozone-skypool' ); ?></div>
                        <div style="font-size: 11.5px; color: var(--ifs-text-tertiary, #64748b); margin-top: 2px;"><?php esc_html_e( 'Auto-drop turnstile arm • 4000ms actuation pulse', 'ozone-skypool' ); ?></div>
                    </div>
                </div>

                <div class="oz-relay-status-pill closed" id="ozTurnstileStatePill">
                    <i class="fa-solid fa-lock"></i> <span id="ozTurnstileStateTxt"><?php esc_html_e( 'BARRIER LOCKED', 'ozone-skypool' ); ?></span>
                </div>
            </div>

            <!-- Optical HUD Viewport -->
            <div class="oz-scanner-viewport" id="ozScannerViewport">
                <span class="oz-hud-reticle top-left"></span>
                <span class="oz-hud-reticle top-right"></span>
                <span class="oz-hud-reticle bottom-left"></span>
                <span class="oz-hud-reticle bottom-right"></span>
                <div class="oz-laser-beam"></div>

                <video id="ozScannerVideo" class="oz-scanner-video" playsinline muted></video>

                <div id="ozScannerPlaceholder" style="text-align: center; color: #64748b; padding: 20px;">
                    <i class="fa-solid fa-qrcode" style="font-size: 68px; opacity: 0.35; margin-bottom: 14px; display: block;"></i>
                    <div style="font-size: 11.5px; font-weight: 800; letter-spacing: 0.8px; text-transform: uppercase;">
                        <?php esc_html_e( 'Camera Idle • Ready for Laser Gun Input', 'ozone-skypool' ); ?>
                    </div>
                </div>
            </div>

            <!-- Optical Controls -->
            <div style="display: flex; gap: 12px;">
                <button type="button" class="oz-btn oz-btn-secondary" style="flex: 1.4;" id="ozToggleCamBtn" onclick="ozToggleCamera()">
                    <i class="fa-solid fa-camera"></i> <span id="ozCamBtnLabel"><?php esc_html_e( 'Activate WebCam Scanner', 'ozone-skypool' ); ?></span>
                </button>
                <button type="button" class="oz-btn oz-btn-secondary" style="flex: 1;" onclick="ozResetScannerConsole()">
                    <i class="fa-solid fa-arrows-rotate"></i> <?php esc_html_e( 'Reset Standby', 'ozone-skypool' ); ?>
                </button>
            </div>

            <!-- Pass Barcode Identifier Input -->
            <div class="oz-field-group">
                <label class="oz-field-label" for="ozScanManualInput">
                    <?php esc_html_e( 'Pass Barcode / QR Identifier Token', 'ozone-skypool' ); ?>
                </label>
                <div style="position: relative;">
                    <input type="text" id="ozScanManualInput" class="ifs-pms-mono" placeholder="OZONE-TKT-XXXXXX" autofocus autocomplete="off">
                    <i class="fa-solid fa-barcode" style="position: absolute; right: 18px; top: 50%; transform: translateY(-50%); font-size: 22px; color: var(--ifs-text-tertiary, #94a3b8); pointer-events: none;"></i>
                </div>
                <div style="font-size: 11.5px; color: var(--ifs-text-tertiary, #64748b); margin-top: 6px; display: flex; justify-content: space-between;">
                    <span><?php esc_html_e( 'Handheld USB scanners operate automatically upon focus.', 'ozone-skypool' ); ?></span>
                    <kbd style="font-family: var(--ifs-font-mono); background: var(--ifs-surface-hover); border: 1px solid var(--ifs-border-subtle); padding: 1px 6px; border-radius: 4px; font-weight: 700;">Enter</kbd>
                </div>
            </div>

            <button type="button" id="ozAuthorizeBtn" class="oz-btn oz-btn-primary oz-btn-lg" style="width: 100%;" onclick="ozExecuteVerification()">
                <i class="fa-solid fa-shield-check"></i> <?php esc_html_e( 'Verify & Actuate Turnstile Barrier', 'ozone-skypool' ); ?>
            </button>

            <div id="ozScannerStatusBar" class="oz-scanner-status-bar">
                <i class="fa-solid fa-satellite-dish"></i> <?php esc_html_e( 'Terminal Standby • Present admission pass to scanner', 'ozone-skypool' ); ?>
            </div>
        </div>
    </div>

    <!-- RIGHT: Verification Telemetry Stream -->
    <div class="oz-panel-card">
        <div class="oz-panel-head">
            <h3 class="oz-panel-title">
                <i class="fa-solid fa-clock-rotate-left" style="color: var(--ifs-text-secondary, #475569);"></i>
                <?php esc_html_e( 'Live Validation Feed', 'ozone-skypool' ); ?>
            </h3>
            <span class="ifs-pms-badge" style="background: var(--ifs-surface-hover); color: var(--ifs-text-secondary); border: 1px solid var(--ifs-border-subtle);">
                <?php esc_html_e( 'Current Shift', 'ozone-skypool' ); ?>
            </span>
        </div>

        <div class="oz-console-body">
            <!-- Latest Scan Result Telemetry Box -->
            <div id="ozResultTelemetryBox" class="oz-telemetry-result-card" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--ifs-border-subtle, #e2e8f0);">
                    <strong id="ozResultPatron" style="font-size: 15px; color: var(--ifs-text-primary, #0f172a);">Patron Name</strong>
                    <span id="ozResultBadge" class="ifs-pms-badge ifs-pms-badge-success"><?php esc_html_e( 'Admitted', 'ozone-skypool' ); ?></span>
                </div>
                <div style="font-size: 12.5px; line-height: 1.8; color: var(--ifs-text-secondary, #475569);" class="ifs-pms-mono">
                    <div><?php esc_html_e( 'Token UID:', 'ozone-skypool' ); ?> <strong id="ozResultCode" style="color: var(--ifs-text-primary, #0f172a); font-weight: 800;">-</strong></div>
                    <div><?php esc_html_e( 'Decision Time:', 'ozone-skypool' ); ?> <span id="ozResultTimestamp">-</span></div>
                    <div id="ozResultMessageRow" style="margin-top: 4px; font-weight: 700;">-</div>
                </div>
            </div>

            <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: var(--ifs-text-tertiary, #64748b); letter-spacing: 0.08em; margin-bottom: 6px;">
                <?php esc_html_e( 'Recent Turnstile Admittance Log', 'ozone-skypool' ); ?>
            </div>

            <div id="ozScanSessionLogContainer">
                <div style="text-align: center; color: var(--ifs-text-tertiary, #64748b); padding: 48px 20px; font-size: 13px;" id="ozScanNoHistory">
                    <i class="fa-solid fa-fingerprint" style="font-size: 36px; opacity: 0.35; margin-bottom: 12px; display: block;"></i>
                    <?php esc_html_e( 'No tickets scanned during this session yet.', 'ozone-skypool' ); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    let videoStream     = null;
    let cameraActive    = false;
    let barcodeDetector = null;
    let scanInterval    = null;
    let isProcessing    = false;
    let barrierTimer    = null;

    if ('BarcodeDetector' in window) {
        try {
            barcodeDetector = new BarcodeDetector({ formats: ['qr_code', 'code_128', 'code_39'] });
        } catch(e) {
            barcodeDetector = null;
        }
    }

    function getAjaxParams() {
        if (typeof ifsPmsConfig !== 'undefined') {
            return {
                url: ifsPmsConfig.ajax_url,
                nonce: ifsPmsConfig.nonce
            };
        }
        return {
            url: typeof ajaxurl !== 'undefined' ? ajaxurl : '',
            nonce: ''
        };
    }

    window.ozExecuteVerification = function() {
        if (isProcessing) return;

        const inputEl  = document.getElementById('ozScanManualInput');
        const codeVal  = inputEl ? inputEl.value.trim().toUpperCase() : '';
        const statusEl = document.getElementById('ozScannerStatusBar');
        const authBtn  = document.getElementById('ozAuthorizeBtn');
        const params   = getAjaxParams();

        if (!codeVal) {
            if (inputEl) inputEl.focus();
            return;
        }

        isProcessing = true;
        if (authBtn) authBtn.disabled = true;

        statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <?php echo esc_js( __( 'Contacting Turnstile Controller...', 'ozone-skypool' ) ); ?>';
        statusEl.style.color = 'var(--ifs-text-secondary, #475569)';
        statusEl.style.background = 'var(--ifs-surface-hover, #f8fafc)';
        statusEl.style.borderColor = 'var(--ifs-border-subtle, #e2e8f0)';

        const postData = new URLSearchParams();
        postData.append('action', 'ifs_pms_verify_pass_action');
        postData.append('ticket_code', codeVal);
        postData.append('security', params.nonce);

        fetch(params.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: postData.toString()
        })
        .then(response => response.json())
        .then(res => {
            const timeString = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });

            if (res && res.success) {
                if (typeof ifs_pms_audio !== 'undefined' && ifs_pms_audio.playSuccess) {
                    ifs_pms_audio.playSuccess();
                }

                statusEl.innerHTML = '<i class="fa-solid fa-circle-check"></i> <?php echo esc_js( __( 'ACCESS GRANTED • TURNSTILE UNLOCKED', 'ozone-skypool' ) ); ?>';
                statusEl.style.background = 'var(--ifs-success-soft, rgba(16, 185, 129, 0.12))';
                statusEl.style.color = 'var(--ifs-success, #10b981)';
                statusEl.style.borderColor = 'rgba(16, 185, 129, 0.4)';

                triggerBarrierRelay(true);
                displayTelemetry(codeVal, res.data.customer_name || 'Walk-in Guest', timeString, true, res.data.message);
                logSessionEntry(codeVal, timeString, true);
            } else {
                if (typeof ifs_pms_audio !== 'undefined' && ifs_pms_audio.playError) {
                    ifs_pms_audio.playError();
                }

                const errorMsg = (res && res.data && res.data.message) ? res.data.message : <?php echo wp_json_encode( __( 'Invalid or Expired Pass ID', 'ozone-skypool' ) ); ?>;
                statusEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> ' + escapeHtml(errorMsg);
                statusEl.style.background = 'var(--ifs-danger-soft, rgba(239, 68, 68, 0.12))';
                statusEl.style.color = 'var(--ifs-danger, #ef4444)';
                statusEl.style.borderColor = 'rgba(239, 68, 68, 0.4)';

                triggerBarrierRelay(false);
                displayTelemetry(codeVal, <?php echo wp_json_encode( __( 'Access Denied', 'ozone-skypool' ) ); ?>, timeString, false, errorMsg);
                logSessionEntry(codeVal, timeString, false);
            }
        })
        .catch(err => {
            statusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> <?php echo esc_js( __( 'Gate Communication Error', 'ozone-skypool' ) ); ?>';
            statusEl.style.background = 'var(--ifs-danger-soft, rgba(239, 68, 68, 0.12))';
            statusEl.style.color = 'var(--ifs-danger, #ef4444)';
        })
        .finally(() => {
            isProcessing = false;
            if (authBtn) authBtn.disabled = false;
            if (inputEl) {
                inputEl.value = '';
                inputEl.focus();
            }
        });
    };

    function triggerBarrierRelay(open) {
        const pill = document.getElementById('ozTurnstileStatePill');
        const txt  = document.getElementById('ozTurnstileStateTxt');
        if (!pill || !txt) return;

        clearTimeout(barrierTimer);

        if (open) {
            pill.className = 'oz-relay-status-pill open';
            txt.innerHTML  = '<i class="fa-solid fa-lock-open"></i> <?php echo esc_js( __( 'BARRIER OPEN • ENTERING', 'ozone-skypool' ) ); ?>';

            barrierTimer = setTimeout(() => {
                pill.className = 'oz-relay-status-pill closed';
                txt.innerHTML  = '<i class="fa-solid fa-lock"></i> <?php echo esc_js( __( 'BARRIER LOCKED', 'ozone-skypool' ) ); ?>';
            }, 4000);
        } else {
            pill.className = 'oz-relay-status-pill closed';
            txt.innerHTML  = '<i class="fa-solid fa-ban"></i> <?php echo esc_js( __( 'ENTRY REJECTED', 'ozone-skypool' ) ); ?>';

            barrierTimer = setTimeout(() => {
                txt.innerHTML = '<i class="fa-solid fa-lock"></i> <?php echo esc_js( __( 'BARRIER LOCKED', 'ozone-skypool' ) ); ?>';
            }, 2500);
        }
    }

    function displayTelemetry(code, patron, time, isValid, msg) {
        const card     = document.getElementById('ozResultTelemetryBox');
        const badge    = document.getElementById('ozResultBadge');
        const codeEl   = document.getElementById('ozResultCode');
        const patronEl = document.getElementById('ozResultPatron');
        const timeEl   = document.getElementById('ozResultTimestamp');
        const msgEl    = document.getElementById('ozResultMessageRow');

        if (!card) return;
        card.style.display = 'block';

        codeEl.textContent   = code;
        patronEl.textContent = patron;
        timeEl.textContent   = time;
        msgEl.textContent    = msg;

        if (isValid) {
            card.style.borderColor = 'rgba(16, 185, 129, 0.35)';
            card.style.background  = 'var(--ifs-success-soft, rgba(16, 185, 129, 0.08))';
            badge.className        = 'ifs-pms-badge ifs-pms-badge-success';
            badge.textContent      = <?php echo wp_json_encode( __( 'Pass Approved', 'ozone-skypool' ) ); ?>;
            msgEl.style.color      = 'var(--ifs-success, #10b981)';
        } else {
            card.style.borderColor = 'rgba(239, 68, 68, 0.35)';
            card.style.background  = 'var(--ifs-danger-soft, rgba(239, 68, 68, 0.08))';
            badge.className        = 'ifs-pms-badge ifs-pms-badge-danger';
            badge.textContent      = <?php echo wp_json_encode( __( 'Denied', 'ozone-skypool' ) ); ?>;
            msgEl.style.color      = 'var(--ifs-danger, #ef4444)';
        }
    }

    function logSessionEntry(code, time, isValid) {
        const emptyState = document.getElementById('ozScanNoHistory');
        if (emptyState) emptyState.remove();

        const container = document.getElementById('ozScanSessionLogContainer');
        if (!container) return;

        const row = document.createElement('div');
        row.className = 'oz-feed-item';

        const badgeHtml = isValid 
            ? '<span class="ifs-pms-badge ifs-pms-badge-success"><?php echo esc_js( __( 'Admitted', 'ozone-skypool' ) ); ?></span>'
            : '<span class="ifs-pms-badge ifs-pms-badge-danger"><?php echo esc_js( __( 'Denied', 'ozone-skypool' ) ); ?></span>';

        row.innerHTML = `
            <div>
                <strong class="ifs-pms-mono" style="color: var(--ifs-text-primary, #0f172a); font-size: 13.5px;">${escapeHtml(code)}</strong>
                <div style="font-size: 11px; color: var(--ifs-text-tertiary, #64748b); font-family: var(--ifs-font-mono); margin-top: 3px;">${escapeHtml(time)}</div>
            </div>
            <div>${badgeHtml}</div>
        `;

        container.insertBefore(row, container.firstChild);
        while (container.children.length > 7) {
            container.removeChild(container.lastChild);
        }
    }

    window.ozResetScannerConsole = function() {
        const input = document.getElementById('ozScanManualInput');
        if (input) {
            input.value = '';
            input.focus();
        }
        const statusEl = document.getElementById('ozScannerStatusBar');
        if (statusEl) {
            statusEl.innerHTML = '<i class="fa-solid fa-satellite-dish"></i> <?php echo esc_js( __( 'Terminal Standby • Present admission pass to scanner', 'ozone-skypool' ) ); ?>';
            statusEl.style.color = 'var(--ifs-text-secondary, #475569)';
            statusEl.style.background = 'var(--ifs-surface-hover, #f8fafc)';
            statusEl.style.borderColor = 'var(--ifs-border-subtle, #e2e8f0)';
        }
    };

    window.ozToggleCamera = async function() {
        const video       = document.getElementById('ozScannerVideo');
        const placeholder = document.getElementById('ozScannerPlaceholder');
        const label       = document.getElementById('ozCamBtnLabel');

        if (!cameraActive) {
            try {
                videoStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                video.srcObject = videoStream;
                video.style.display = 'block';
                if (placeholder) placeholder.style.display = 'none';
                await video.play();

                cameraActive = true;
                if (label) label.textContent = <?php echo wp_json_encode( __( 'Turn Off Camera', 'ozone-skypool' ) ); ?>;

                if (barcodeDetector) {
                    scanInterval = setInterval(async () => {
                        if (isProcessing) return;
                        if (video.readyState === video.HAVE_ENOUGH_DATA) {
                            try {
                                const codes = await barcodeDetector.detect(video);
                                if (codes.length > 0 && codes[0].rawValue) {
                                    const input = document.getElementById('ozScanManualInput');
                                    if (input) input.value = codes[0].rawValue;
                                    window.ozExecuteVerification();
                                }
                            } catch (detectErr) {}
                        }
                    }, 280);
                }
            } catch (err) {
                alert(<?php echo wp_json_encode( __( 'Camera stream unavailable. Verify browser video permissions or use an external laser scanner.', 'ozone-skypool' ) ); ?>);
            }
        } else {
            if (videoStream) {
                videoStream.getTracks().forEach(t => t.stop());
            }
            clearInterval(scanInterval);
            video.style.display = 'none';
            if (placeholder) placeholder.style.display = 'block';
            if (label) label.textContent = <?php echo wp_json_encode( __( 'Activate WebCam Scanner', 'ozone-skypool' ) ); ?>;
            cameraActive = false;
        }
    };

    function escapeHtml(str) {
        return String(str).replace(/[&<>"'`=\/]/g, s => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;', '/': '&#x2F;', '`': '&#x60;', '=': '&#x3D;'
        }[s]));
    }

    function initConsole() {
        const input = document.getElementById('ozScanManualInput');
        if (input) {
            input.focus();
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    window.ozExecuteVerification();
                }
            });
        }

        window.addEventListener('beforeunload', function() {
            if (videoStream) {
                videoStream.getTracks().forEach(t => t.stop());
            }
            clearInterval(scanInterval);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initConsole);
    } else {
        initConsole();
    }
})();
</script>