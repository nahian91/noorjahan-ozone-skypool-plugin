<?php
/**
 * View: Gate Turnstile Control & QR Validator Console (Executive Telemetry Edition v10 - Rich Telemetry & Auto 4-Digit Padding)
 *
 * @package Ozone_Skypool_OS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$currency = esc_html( get_option( 'ifs_pms_currency', 'BDT' ) );

// Generate today's base prefix (e.g., OZONE-SEP-17-)
$current_mon_prefix = 'OZONE-' . strtoupper( current_time( 'M' ) ) . '-' . current_time( 'd' ) . '-';
?>

<style>
    /* ==========================================================================
       EXECUTIVE TURNSTILE GATE & SCANNER CONSOLE
       ========================================================================== */
    .oz-scanner-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(400px, 0.95fr);
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
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        box-sizing: border-box;
        overflow: hidden;
    }

    .oz-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 22px 28px;
        border-bottom: 1px solid #f1f5f9;
        background: linear-gradient(to bottom, #fafbfd, #f8fafc);
    }

    .oz-panel-title {
        margin: 0;
        font-size: 16px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #0f172a;
    }

    .oz-console-body {
        padding: 28px;
        display: flex;
        flex-direction: column;
        gap: 22px;
        box-sizing: border-box;
    }

    /* Electronic Turnstile Relay Bar */
    .oz-barrier-relay-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 18px;
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
        padding: 8px 18px;
        border-radius: 9999px;
        transition: all 0.25s ease;
    }

    .oz-relay-status-pill.closed {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .oz-relay-status-pill.open {
        background: rgba(16, 185, 129, 0.12);
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.4);
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.35);
    }

    /* Optical Viewfinder HUD */
    .oz-scanner-viewport {
        position: relative;
        width: 100%;
        max-width: 500px;
        height: 270px;
        margin: 0 auto;
        border-radius: 20px;
        background: #020617;
        border: 2px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        box-shadow: inset 0 0 50px rgba(0, 0, 0, 0.85);
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
        border-color: #0284c7;
        border-style: solid;
        z-index: 6;
        pointer-events: none;
    }

    .oz-hud-reticle.top-left { top: 16px; left: 16px; border-width: 3px 0 0 3px; border-top-left-radius: 8px; }
    .oz-hud-reticle.top-right { top: 16px; right: 16px; border-width: 3px 3px 0 0; border-top-right-radius: 8px; }
    .oz-hud-reticle.bottom-left { bottom: 16px; left: 16px; border-width: 0 0 3px 3px; border-bottom-left-radius: 8px; }
    .oz-hud-reticle.bottom-right { bottom: 16px; right: 16px; border-width: 0 3px 3px 0; border-bottom-right-radius: 8px; }

    .oz-laser-beam {
        position: absolute;
        left: 8%;
        right: 8%;
        height: 2px;
        background: linear-gradient(90deg, transparent, #0284c7, #38bdf8, transparent);
        box-shadow: 0 0 16px #0284c7, 0 0 6px #ffffff;
        animation: ozLaserSweep 2.2s ease-in-out infinite alternate;
        z-index: 5;
        pointer-events: none;
    }

    @keyframes ozLaserSweep {
        0% { top: 14%; opacity: 0.2; }
        50% { opacity: 1; }
        100% { top: 86%; opacity: 0.2; }
    }

    .oz-field-group {
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    .oz-field-label {
        font-size: 12.5px;
        font-weight: 700;
        color: #475569;
        margin-bottom: 8px;
        display: block;
    }

    /* Split Input Group (Prefix + Serial Number) */
    .oz-split-token-group {
        display: flex;
        align-items: center;
        width: 100%;
        background: #ffffff;
        border: 2px solid #cbd5e1;
        border-radius: 14px;
        overflow: hidden;
        transition: border-color 0.2s, box-shadow 0.2s;
        box-sizing: border-box;
    }

    .oz-split-token-group:focus-within {
        border-color: #0284c7;
        box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.15);
    }

    .oz-token-prefix-addon {
        background: #f1f5f9;
        color: #0284c7;
        font-weight: 800;
        font-size: 15px;
        padding: 0 14px;
        height: 52px;
        display: flex;
        align-items: center;
        border-right: 1.5px solid #cbd5e1;
        user-select: none;
        letter-spacing: 0.8px;
        white-space: nowrap;
    }

    #wpcontent .oz-scanner-layout input[type="text"].oz-token-number-input {
        flex: 1 !important;
        display: block !important;
        width: 100% !important;
        background: #ffffff !important;
        border: none !important;
        border-radius: 0 !important;
        padding: 0 16px !important;
        font-size: 20px !important;
        font-weight: 800 !important;
        letter-spacing: 2px !important;
        text-transform: uppercase !important;
        text-align: left !important;
        color: #0f172a !important;
        height: 52px !important;
        box-shadow: none !important;
        outline: none !important;
    }

    .oz-scanner-status-bar {
        padding: 16px 20px;
        border-radius: 14px;
        font-weight: 700;
        font-size: 13.5px;
        text-align: center;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        color: #475569;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.25s ease;
    }

    /* Detailed Telemetry HUD */
    .oz-telemetry-result-card {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 20px;
        padding: 22px;
        box-sizing: border-box;
        margin-bottom: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    }

    .oz-telemetry-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin: 14px 0;
        font-size: 12px;
    }

    .oz-telemetry-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 8px 12px;
    }

    .oz-telemetry-item span {
        display: block;
        font-size: 10px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .oz-telemetry-item strong {
        font-size: 12.5px;
        color: #0f172a;
    }

    .oz-telemetry-item-wide {
        grid-column: span 2;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 12px;
    }

    .oz-telemetry-item-wide span {
        display: block;
        font-size: 10px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        margin-bottom: 2px;
    }

    .oz-feed-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 8px;
        transition: all 0.15s ease;
    }

    .oz-feed-item:hover {
        background: #f1f5f9;
        transform: translateY(-1px);
    }

    #wpcontent .oz-scanner-layout .oz-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
        font-weight: 700 !important;
        cursor: pointer !important;
        border-radius: 12px !important;
        transition: all 0.2s ease !important;
    }

    #wpcontent .oz-scanner-layout .oz-btn-primary.oz-btn-lg {
        height: 52px !important;
        font-size: 15px !important;
        font-weight: 800 !important;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
        color: #ffffff !important;
        border: 1px solid rgba(2, 132, 199, 0.8) !important;
        box-shadow: 0 4px 16px rgba(2, 132, 199, 0.35) !important;
    }

    #wpcontent .oz-scanner-layout .oz-btn-primary.oz-btn-lg:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 22px rgba(2, 132, 199, 0.45) !important;
    }

    #wpcontent .oz-scanner-layout .oz-btn-secondary {
        height: 44px !important;
        padding: 0 16px !important;
        font-size: 13px !important;
        background: #ffffff !important;
        color: #475569 !important;
        border: 1.5px solid #cbd5e1 !important;
    }

    #wpcontent .oz-scanner-layout .oz-btn-secondary:hover {
        background: #f8fafc !important;
        border-color: #0284c7 !important;
        color: #0284c7 !important;
    }
</style>

<div class="oz-scanner-layout">
    <!-- LEFT: Main Validator Console -->
    <div class="oz-panel-card">
        <div class="oz-panel-head">
            <h3 class="oz-panel-title">
                <i class="fa-solid fa-turnstile" style="color: #0284c7;"></i>
                <?php esc_html_e( 'Gate Turnstile Validation Console', 'ozone-skypool' ); ?>
            </h3>
            <span class="ifs-pms-badge ifs-pms-badge-success">
                <span class="ifs-pms-pulse-dot"></span> <?php esc_html_e( 'Relay Port Online', 'ozone-skypool' ); ?>
            </span>
        </div>

        <div class="oz-console-body">
            <!-- Electronic Barrier Status Bar with Live Countdown -->
            <div class="oz-barrier-relay-bar">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <div style="width: 42px; height: 42px; border-radius: 12px; background: rgba(2, 132, 199, 0.1); color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                        <i class="fa-solid fa-microchip"></i>
                    </div>
                    <div>
                        <div style="font-size: 14px; font-weight: 800; color: #0f172a;"><?php esc_html_e( 'Electronic Gate Barrier #01', 'ozone-skypool' ); ?></div>
                        <div style="font-size: 11.5px; color: #64748b; margin-top: 1px;" id="ozBarrierSub">
                            <?php esc_html_e( '4000ms actuation pulse • Auto-relock armed', 'ozone-skypool' ); ?>
                        </div>
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
                    <i class="fa-solid fa-qrcode" style="font-size: 64px; opacity: 0.3; margin-bottom: 12px; display: block;"></i>
                    <div style="font-size: 11px; font-weight: 800; letter-spacing: 0.8px; text-transform: uppercase;">
                        <?php esc_html_e( 'Ready for USB Scanner Gun or WebCam', 'ozone-skypool' ); ?>
                    </div>
                </div>
            </div>

            <!-- Scanner Camera Controls -->
            <div style="display: flex; gap: 12px;">
                <button type="button" class="oz-btn oz-btn-secondary" style="flex: 1.4;" id="ozToggleCamBtn" onclick="ozToggleCamera()">
                    <i class="fa-solid fa-camera"></i> <span id="ozCamBtnLabel"><?php esc_html_e( 'Activate WebCam Scanner', 'ozone-skypool' ); ?></span>
                </button>
                <button type="button" class="oz-btn oz-btn-secondary" style="flex: 1;" onclick="ozResetScannerConsole()">
                    <i class="fa-solid fa-arrows-rotate"></i> <?php esc_html_e( 'Clear Standby', 'ozone-skypool' ); ?>
                </button>
            </div>

            <!-- Auto-Prefixed Barcode Token Input -->
            <div class="oz-field-group">
                <label class="oz-field-label" for="ozScanSerialInput">
                    <?php esc_html_e( 'Pass Barcode / Turnstile Token UID', 'ozone-skypool' ); ?>
                </label>
                
                <div class="oz-split-token-group">
                    <span class="oz-token-prefix-addon ifs-pms-mono" id="ozDailyPrefixSpan"><?php echo esc_html( $current_mon_prefix ); ?></span>
                    <input type="text" id="ozScanSerialInput" class="oz-token-number-input ifs-pms-mono" placeholder="0005" autofocus autocomplete="off">
                </div>

                <div style="font-size: 11.5px; color: #64748b; margin-top: 6px; display: flex; justify-content: space-between;">
                    <span><?php esc_html_e( 'Type number (e.g. 5 pads to 0005) or scan entire barcode.', 'ozone-skypool' ); ?></span>
                    <kbd style="font-family: var(--ifs-font-mono); background: #f1f5f9; border: 1px solid #cbd5e1; padding: 1px 6px; border-radius: 4px; font-weight: 700;">Enter</kbd>
                </div>
            </div>

            <button type="button" id="ozAuthorizeBtn" class="oz-btn oz-btn-primary oz-btn-lg" style="width: 100%;" onclick="ozExecuteVerification()">
                <i class="fa-solid fa-shield-check"></i> <?php esc_html_e( 'Verify & Actuate Turnstile Barrier', 'ozone-skypool' ); ?>
            </button>

            <!-- Dynamic Feedback Bar -->
            <div id="ozScannerStatusBar" class="oz-scanner-status-bar">
                <i class="fa-solid fa-satellite-dish"></i> <?php esc_html_e( 'Terminal Standby • Present pass to scanner', 'ozone-skypool' ); ?>
            </div>
        </div>
    </div>

    <!-- RIGHT: Complete Verification Telemetry Stream -->
    <div class="oz-panel-card">
        <div class="oz-panel-head">
            <h3 class="oz-panel-title">
                <i class="fa-solid fa-clock-rotate-left" style="color: #475569;"></i>
                <?php esc_html_e( 'Turnstile Telemetry Stream', 'ozone-skypool' ); ?>
            </h3>
            <span class="ifs-pms-badge" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;">
                <?php esc_html_e( 'Active Shift', 'ozone-skypool' ); ?>
            </span>
        </div>

        <div class="oz-console-body">
            <!-- Full Info Real-Time Telemetry Card -->
            <div id="ozResultTelemetryBox" class="oz-telemetry-result-card" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 12px; border-bottom: 1px dashed #e2e8f0;">
                    <div>
                        <strong id="ozResultPatron" style="font-size: 16px; color: #0f172a; display: block; font-weight: 800;">Patron Name</strong>
                        <div style="display: flex; gap: 6px; align-items: center; margin-top: 4px;">
                            <span id="ozResultClassificationBadge" class="ifs-pms-badge">General Customer</span>
                            <span id="ozResultPhone" style="font-size: 11px; color: #64748b; font-family: var(--ifs-font-mono);">017XXXXXXXX</span>
                        </div>
                    </div>
                    <span id="ozResultBadge" class="ifs-pms-badge ifs-pms-badge-success"><?php esc_html_e( 'Admitted', 'ozone-skypool' ); ?></span>
                </div>

                <!-- Comprehensive 4-Column Metric Grid -->
                <div class="oz-telemetry-grid ifs-pms-mono">
                    <div class="oz-telemetry-item">
                        <span><?php esc_html_e( 'Token Code', 'ozone-skypool' ); ?></span>
                        <strong id="ozResultCode" style="color: #0284c7; font-size: 13px;">-</strong>
                    </div>

                    <div class="oz-telemetry-item" id="ozResultRoomWrap" style="display: none;">
                        <span><?php esc_html_e( 'Hotel Room #', 'ozone-skypool' ); ?></span>
                        <strong id="ozResultRoom" style="color: #0284c7; font-size: 13px;">-</strong>
                    </div>

                    <div class="oz-telemetry-item">
                        <span><?php esc_html_e( 'Duration', 'ozone-skypool' ); ?></span>
                        <strong id="ozResultDuration">-</strong>
                    </div>

                    <div class="oz-telemetry-item">
                        <span><?php esc_html_e( 'Valid Until', 'ozone-skypool' ); ?></span>
                        <strong id="ozResultValidUntil" style="color: #10b981;">-</strong>
                    </div>

                    <div class="oz-telemetry-item">
                        <span><?php esc_html_e( 'Amount Paid', 'ozone-skypool' ); ?></span>
                        <strong id="ozResultAmount" style="color: #0f172a;">-</strong>
                    </div>

                    <div class="oz-telemetry-item">
                        <span><?php esc_html_e( 'Tender Method', 'ozone-skypool' ); ?></span>
                        <strong id="ozResultPayment">-</strong>
                    </div>

                    <div class="oz-telemetry-item-wide">
                        <span><?php esc_html_e( 'Enrolled Package Items', 'ozone-skypool' ); ?></span>
                        <strong id="ozResultPackage" style="color: #0f172a; font-family: var(--ifs-font-sans); font-size: 12px; line-height: 1.4;">-</strong>
                    </div>
                </div>

                <div id="ozResultMessageRow" style="margin-top: 10px; font-size: 12px; font-weight: 700; padding: 6px 10px; border-radius: 8px;">-</div>
            </div>

            <!-- Recent Scan Feed -->
            <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.08em; margin-bottom: 4px;">
                <?php esc_html_e( 'Recent Turnstile Admittances', 'ozone-skypool' ); ?>
            </div>

            <div id="ozScanSessionLogContainer">
                <div style="text-align: center; color: #94a3b8; padding: 48px 20px; font-size: 13px;" id="ozScanNoHistory">
                    <i class="fa-solid fa-fingerprint" style="font-size: 36px; opacity: 0.35; margin-bottom: 12px; display: block;"></i>
                    <?php esc_html_e( 'No tickets scanned in this session yet.', 'ozone-skypool' ); ?>
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
    let countdownTimer  = null;

    const basePrefix  = <?php echo wp_json_encode( $current_mon_prefix ); ?>;
    const currencySym = <?php echo wp_json_encode( $currency ); ?>;

    const audioCtx = (typeof window.AudioContext !== 'undefined' || typeof window.webkitAudioContext !== 'undefined')
        ? new (window.AudioContext || window.webkitAudioContext)()
        : null;

    function playBeep(frequency, duration, type = 'sine') {
        if (!audioCtx) return;
        try {
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.type = type;
            osc.frequency.value = frequency;
            gain.gain.setValueAtTime(0.12, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start();
            osc.stop(audioCtx.currentTime + duration);
        } catch (e) {}
    }

    function soundAccessGranted() {
        playBeep(880, 0.12);
        setTimeout(() => playBeep(1320, 0.18), 120);
    }

    function soundAccessDenied() {
        playBeep(260, 0.25, 'sawtooth');
        setTimeout(() => playBeep(220, 0.35, 'sawtooth'), 180);
    }

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

    // Automatic zero-padding: "5" -> "0005", "42" -> "0042", handles full barcode dumps
    function constructFullToken(rawVal) {
        let clean = rawVal.trim().toUpperCase();
        if (!clean) return '';

        // If the scanner/cashier provided the entire barcode
        if (clean.startsWith('OZONE-')) {
            return clean;
        }

        // If user input is pure digits (e.g. 5, 24, 100), pad left to 4 digits
        if (/^\d+$/.test(clean)) {
            clean = clean.padStart(4, '0');
        }

        return basePrefix + clean;
    }

    window.ozExecuteVerification = function(customCode = null) {
        if (isProcessing) return;

        const inputEl = document.getElementById('ozScanSerialInput');
        let rawVal    = customCode ? customCode : (inputEl ? inputEl.value : '');
        let codeVal   = constructFullToken(rawVal);

        const statusEl = document.getElementById('ozScannerStatusBar');
        const authBtn  = document.getElementById('ozAuthorizeBtn');
        const params   = getAjaxParams();

        if (!codeVal || codeVal === basePrefix) {
            if (inputEl) inputEl.focus();
            return;
        }

        // Update the visible input to reflect the padded 4 digits
        if (inputEl && /^\d+$/.test(rawVal.trim())) {
            inputEl.value = rawVal.trim().padStart(4, '0');
        }

        isProcessing = true;
        if (authBtn) authBtn.disabled = true;

        statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <?php echo esc_js( __( 'Contacting Turnstile Controller...', 'ozone-skypool' ) ); ?>';
        statusEl.style.color = '#475569';
        statusEl.style.background = '#f8fafc';
        statusEl.style.borderColor = '#e2e8f0';

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
                soundAccessGranted();

                statusEl.innerHTML = '<i class="fa-solid fa-circle-check"></i> <?php echo esc_js( __( 'ACCESS GRANTED • TURNSTILE UNLOCKED', 'ozone-skypool' ) ); ?>';
                statusEl.style.background = 'rgba(16, 185, 129, 0.12)';
                statusEl.style.color = '#10b981';
                statusEl.style.borderColor = 'rgba(16, 185, 129, 0.4)';

                triggerBarrierRelay(true);
                displayTelemetry(res.data, true);
                logSessionEntry(codeVal, timeString, true, res.data.customer_name);
            } else {
                soundAccessDenied();

                const errorMsg = (res && res.data && res.data.message) ? res.data.message : <?php echo wp_json_encode( __( 'Invalid or Expired Pass ID', 'ozone-skypool' ) ); ?>;
                statusEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> ' + escapeHtml(errorMsg);
                statusEl.style.background = 'rgba(239, 68, 68, 0.12)';
                statusEl.style.color = '#ef4444';
                statusEl.style.borderColor = 'rgba(239, 68, 68, 0.4)';

                triggerBarrierRelay(false);
                displayTelemetry({
                    ticket_code: codeVal,
                    customer_name: <?php echo wp_json_encode( __( 'Access Rejected', 'ozone-skypool' ) ); ?>,
                    customer_phone: '-',
                    package: '-',
                    amount: '0.00',
                    payment_method: '-',
                    duration_hours: 0,
                    valid_until: '-',
                    message: errorMsg,
                    scanned_at: timeString
                }, false);
                logSessionEntry(codeVal, timeString, false, 'Invalid Token');
            }
        })
        .catch(err => {
            statusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> <?php echo esc_js( __( 'Gate Controller Timeout', 'ozone-skypool' ) ); ?>';
            statusEl.style.background = 'rgba(239, 68, 68, 0.12)';
            statusEl.style.color = '#ef4444';
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
        const sub  = document.getElementById('ozBarrierSub');
        if (!pill || !txt) return;

        clearTimeout(barrierTimer);
        clearInterval(countdownTimer);

        if (open) {
            pill.className = 'oz-relay-status-pill open';
            let secondsLeft = 4;
            txt.innerHTML  = `<i class="fa-solid fa-lock-open"></i> PASS OK (${secondsLeft}s)`;

            countdownTimer = setInterval(() => {
                secondsLeft--;
                if (secondsLeft > 0) {
                    txt.innerHTML = `<i class="fa-solid fa-lock-open"></i> PASS OK (${secondsLeft}s)`;
                } else {
                    clearInterval(countdownTimer);
                }
            }, 1000);

            barrierTimer = setTimeout(() => {
                pill.className = 'oz-relay-status-pill closed';
                txt.innerHTML  = '<i class="fa-solid fa-lock"></i> <?php echo esc_js( __( 'BARRIER LOCKED', 'ozone-skypool' ) ); ?>';
                if (sub) sub.textContent = <?php echo wp_json_encode( __( '4000ms actuation pulse • Auto-relock armed', 'ozone-skypool' ) ); ?>;
            }, 4000);
        } else {
            pill.className = 'oz-relay-status-pill closed';
            txt.innerHTML  = '<i class="fa-solid fa-ban"></i> <?php echo esc_js( __( 'ENTRY REJECTED', 'ozone-skypool' ) ); ?>';

            barrierTimer = setTimeout(() => {
                txt.innerHTML = '<i class="fa-solid fa-lock"></i> <?php echo esc_js( __( 'BARRIER LOCKED', 'ozone-skypool' ) ); ?>';
            }, 2500);
        }
    }

    function displayTelemetry(data, isValid) {
        const card        = document.getElementById('ozResultTelemetryBox');
        const badge       = document.getElementById('ozResultBadge');
        const patronEl    = document.getElementById('ozResultPatron');
        const phoneEl     = document.getElementById('ozResultPhone');
        const classBadge  = document.getElementById('ozResultClassificationBadge');
        const codeEl      = document.getElementById('ozResultCode');
        const roomWrap    = document.getElementById('ozResultRoomWrap');
        const roomEl      = document.getElementById('ozResultRoom');
        const durationEl  = document.getElementById('ozResultDuration');
        const validUntil  = document.getElementById('ozResultValidUntil');
        const amountEl    = document.getElementById('ozResultAmount');
        const paymentEl   = document.getElementById('ozResultPayment');
        const packageEl   = document.getElementById('ozResultPackage');
        const msgEl       = document.getElementById('ozResultMessageRow');

        if (!card) return;
        card.style.display = 'block';

        codeEl.textContent   = data.ticket_code || '-';
        patronEl.textContent = data.customer_name || 'Walk-in Guest';
        phoneEl.textContent  = data.customer_phone || '-';
        msgEl.textContent    = data.message || '';

        if (isValid) {
            card.style.borderColor = 'rgba(16, 185, 129, 0.4)';
            card.style.background  = 'rgba(16, 185, 129, 0.02)';
            badge.className        = 'ifs-pms-badge ifs-pms-badge-success';
            badge.textContent      = <?php echo wp_json_encode( __( 'Pass Approved', 'ozone-skypool' ) ); ?>;
            msgEl.style.color      = '#10b981';
            msgEl.style.background = 'rgba(16, 185, 129, 0.08)';

            if (data.guest_type === 'room_guest') {
                classBadge.textContent = 'Hotel Room Guest';
                classBadge.className   = 'ifs-pms-badge ifs-pms-badge-primary';
                if (data.room_no && roomWrap) {
                    roomWrap.style.display = 'block';
                    roomEl.textContent     = data.room_no;
                }
            } else {
                classBadge.textContent = 'General Customer';
                classBadge.className   = 'ifs-pms-badge';
                if (roomWrap) roomWrap.style.display = 'none';
            }

            durationEl.textContent = (data.duration_hours || 1) + ' Hours';
            validUntil.textContent = data.valid_until || '-';
            amountEl.textContent   = (data.amount || '0.00') + ' ' + currencySym;
            paymentEl.textContent  = data.payment_method || 'Cash';
            packageEl.textContent  = data.package || 'Standard Swim Pass';
        } else {
            card.style.borderColor = 'rgba(239, 68, 68, 0.4)';
            card.style.background  = 'rgba(239, 68, 68, 0.02)';
            badge.className        = 'ifs-pms-badge ifs-pms-badge-danger';
            badge.textContent      = <?php echo wp_json_encode( __( 'Denied', 'ozone-skypool' ) ); ?>;
            msgEl.style.color      = '#ef4444';
            msgEl.style.background = 'rgba(239, 68, 68, 0.08)';
            classBadge.textContent = 'Verification Failed';
            classBadge.className   = 'ifs-pms-badge ifs-pms-badge-danger';

            if (roomWrap) roomWrap.style.display = 'none';
            durationEl.textContent = '-';
            validUntil.textContent = '-';
            amountEl.textContent   = '-';
            paymentEl.textContent  = '-';
            packageEl.textContent  = '-';
        }
    }

    function logSessionEntry(code, time, isValid, name) {
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
                <strong class="ifs-pms-mono" style="color: #0f172a; font-size: 13px;">${escapeHtml(code)}</strong>
                <div style="font-size: 11px; color: #64748b; margin-top: 1px;">${escapeHtml(name || 'Guest')} &bull; ${escapeHtml(time)}</div>
            </div>
            <div>${badgeHtml}</div>
        `;

        container.insertBefore(row, container.firstChild);
        while (container.children.length > 7) {
            container.removeChild(container.lastChild);
        }
    }

    window.ozResetScannerConsole = function() {
        const input = document.getElementById('ozScanSerialInput');
        if (input) {
            input.value = '';
            input.focus();
        }
        const statusEl = document.getElementById('ozScannerStatusBar');
        if (statusEl) {
            statusEl.innerHTML = '<i class="fa-solid fa-satellite-dish"></i> <?php echo esc_js( __( 'Terminal Standby • Present pass to scanner', 'ozone-skypool' ) ); ?>';
            statusEl.style.color = '#475569';
            statusEl.style.background = '#f8fafc';
            statusEl.style.borderColor = '#e2e8f0';
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
                                    window.ozExecuteVerification(codes[0].rawValue);
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
        const input = document.getElementById('ozScanSerialInput');
        if (input) {
            input.focus();
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    window.ozExecuteVerification();
                }
            });

            // If a USB laser scanner dumps the entire token into this input
            input.addEventListener('paste', function(e) {
                setTimeout(() => {
                    let pasteVal = input.value.trim().toUpperCase();
                    if (pasteVal.startsWith(basePrefix)) {
                        input.value = pasteVal.replace(basePrefix, '');
                    }
                    window.ozExecuteVerification();
                }, 50);
            });
        }

        window.addEventListener('beforeunload', function() {
            if (videoStream) {
                videoStream.getTracks().forEach(t => t.stop());
            }
            clearInterval(scanInterval);
            clearTimeout(barrierTimer);
            clearInterval(countdownTimer);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initConsole);
    } else {
        initConsole();
    }
})();
</script>