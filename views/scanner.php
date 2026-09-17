<?php
/**
 * View: Gate Turnstile Control & QR Validator Console (Live Telemetry Preview Edition)
 *
 * @package Ozone_Skypool_OS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enqueue external scanner console stylesheet
if ( defined( 'IFS_PMS_URL' ) && defined( 'IFS_PMS_VERSION' ) ) {
    wp_enqueue_style(
        'oz-scanner-console-css',
        IFS_PMS_URL . 'assets/css/scanner-console.css',
        array(),
        IFS_PMS_VERSION
    );
}

$currency = esc_html( get_option( 'ifs_pms_currency', 'BDT' ) );
$current_operator = wp_get_current_user()->display_name;

// Generate today's base prefix (e.g., OZONE-SEP-17-)
$current_mon_prefix = 'OZONE-' . strtoupper( current_time( 'M' ) ) . '-' . current_time( 'd' ) . '-';
?>

<div class="oz-scanner-layout">
    <!-- LEFT: Main Validator Console -->
    <div class="oz-panel-card">
        <div class="oz-panel-head">
            <h3 class="oz-panel-title">
                <i class="fa-solid fa-turnstile" style="color: #0284c7;"></i>
                <?php esc_html_e( 'Gate Turnstile Validation Console', 'swimming-pool-manager' ); ?>
            </h3>
            <span class="ifs-pms-badge ifs-pms-badge-success">
                <span class="ifs-pms-pulse-dot"></span> <?php esc_html_e( 'Relay Port Online', 'swimming-pool-manager' ); ?>
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
                        <div style="font-size: 14px; font-weight: 800; color: #0f172a;"><?php esc_html_e( 'Electronic Gate Barrier #01', 'swimming-pool-manager' ); ?></div>
                        <div style="font-size: 11.5px; color: #64748b; margin-top: 1px;" id="ozBarrierSub">
                            <?php esc_html_e( '4000ms actuation pulse • Auto-relock armed', 'swimming-pool-manager' ); ?>
                        </div>
                    </div>
                </div>

                <div class="oz-relay-status-pill closed" id="ozTurnstileStatePill">
                    <i class="fa-solid fa-lock"></i> <span id="ozTurnstileStateTxt"><?php esc_html_e( 'BARRIER LOCKED', 'swimming-pool-manager' ); ?></span>
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
                        <?php esc_html_e( 'Ready for USB Scanner Gun or WebCam', 'swimming-pool-manager' ); ?>
                    </div>
                </div>
            </div>

            <!-- Scanner Camera Controls -->
            <div style="display: flex; gap: 12px;">
                <button type="button" class="oz-btn oz-btn-secondary" style="flex: 1.4;" id="ozToggleCamBtn" onclick="ozToggleCamera()">
                    <i class="fa-solid fa-camera"></i> <span id="ozCamBtnLabel"><?php esc_html_e( 'Activate WebCam Scanner', 'swimming-pool-manager' ); ?></span>
                </button>
                <button type="button" class="oz-btn oz-btn-secondary" style="flex: 1;" onclick="ozResetScannerConsole()">
                    <i class="fa-solid fa-arrows-rotate"></i> <?php esc_html_e( 'Clear Standby', 'swimming-pool-manager' ); ?>
                </button>
            </div>

            <!-- Auto-Prefixed Barcode Token Input -->
            <div class="oz-field-group">
                <label class="oz-field-label" for="ozScanSerialInput">
                    <?php esc_html_e( 'Pass Barcode / Turnstile Token UID', 'swimming-pool-manager' ); ?>
                </label>
                
                <div class="oz-split-token-group">
                    <span class="oz-token-prefix-addon ifs-pms-mono" id="ozDailyPrefixSpan"><?php echo esc_html( $current_mon_prefix ); ?></span>
                    <input type="text" id="ozScanSerialInput" class="oz-token-number-input ifs-pms-mono" placeholder="0005" autofocus autocomplete="off">
                </div>

                <div style="font-size: 11.5px; color: #64748b; margin-top: 6px; display: flex; justify-content: space-between;">
                    <span><?php esc_html_e( 'Type digits or scan barcode. Auto-triggers verification.', 'swimming-pool-manager' ); ?></span>
                    <kbd style="font-family: var(--ifs-font-mono, monospace); background: #f1f5f9; border: 1px solid #cbd5e1; padding: 1px 6px; border-radius: 4px; font-weight: 700;">Auto</kbd>
                </div>
            </div>

            <button type="button" id="ozAuthorizeBtn" class="oz-btn oz-btn-primary oz-btn-lg" style="width: 100%;" onclick="ozExecuteVerification()">
                <i class="fa-solid fa-shield-check"></i> <?php esc_html_e( 'Verify & Actuate Turnstile Barrier', 'swimming-pool-manager' ); ?>
            </button>

            <!-- Dynamic Feedback Bar -->
            <div id="ozScannerStatusBar" class="oz-scanner-status-bar">
                <i class="fa-solid fa-satellite-dish"></i> <?php esc_html_e( 'Terminal Standby • Present pass to scanner', 'swimming-pool-manager' ); ?>
            </div>
        </div>
    </div>

    <!-- RIGHT: Complete Verification Telemetry Stream (Full Dossier) -->
    <div class="oz-panel-card">
        <div class="oz-panel-head">
            <h3 class="oz-panel-title">
                <i class="fa-solid fa-radar" style="color: #0284c7;"></i>
                <?php esc_html_e( 'Turnstile Telemetry Stream', 'swimming-pool-manager' ); ?>
            </h3>
            <span class="ifs-pms-badge" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;">
                <i class="fa-solid fa-user-shield" style="color: #0284c7;"></i> <?php echo esc_html( $current_operator ); ?>
            </span>
        </div>

        <div class="oz-console-body">
            <!-- Full Info Real-Time Telemetry Card -->
            <div id="ozResultTelemetryBox" class="oz-telemetry-result-card" style="border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 20px; background: #ffffff;">
                
                <!-- 1. Patron Dossier Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 14px; border-bottom: 1.5px dashed #e2e8f0;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div id="ozResultAvatar" style="width: 48px; height: 48px; border-radius: 14px; background: #f1f5f9; color: #64748b; font-weight: 800; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div>
                            <strong id="ozResultPatron" style="font-size: 17px; color: #0f172a; display: block; font-weight: 800;">
                                <?php esc_html_e( 'Awaiting Pass Scan...', 'swimming-pool-manager' ); ?>
                            </strong>
                            <div style="display: flex; gap: 8px; align-items: center; margin-top: 3px;">
                                <span id="ozResultClassificationBadge" class="ifs-pms-badge" style="background: #f1f5f9; color: #64748b; font-size: 11px;">
                                    <?php esc_html_e( 'Idle Standby', 'swimming-pool-manager' ); ?>
                                </span>
                                <span id="ozResultPhone" class="ifs-pms-mono" style="font-size: 11.5px; color: #64748b;">—</span>
                            </div>
                        </div>
                    </div>
                    <span id="ozResultBadge" class="ifs-pms-badge" style="background: #f1f5f9; color: #64748b; font-size: 12px; padding: 6px 14px; border-radius: 20px;">
                        <?php esc_html_e( 'Standby', 'swimming-pool-manager' ); ?>
                    </span>
                </div>

                <!-- 2. Full 8-Point Telemetry Metric Matrix -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-top: 16px;">
                    <div class="oz-telemetry-item" style="background: #f8fafc; padding: 10px 14px; border-radius: 10px; border: 1px solid #f1f5f9;">
                        <span style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; display: block;">
                            <i class="fa-solid fa-fingerprint" style="color: #0284c7;"></i> <?php esc_html_e( 'Token Code', 'swimming-pool-manager' ); ?>
                        </span>
                        <strong id="ozResultCode" class="ifs-pms-mono" style="color: #0284c7; font-size: 13.5px; margin-top: 2px; display: block;">—</strong>
                    </div>

                    <div class="oz-telemetry-item" style="background: #f8fafc; padding: 10px 14px; border-radius: 10px; border: 1px solid #f1f5f9;">
                        <span style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; display: block;">
                            <i class="fa-solid fa-door-closed" style="color: #0284c7;"></i> <?php esc_html_e( 'Barrier Gate Port', 'swimming-pool-manager' ); ?>
                        </span>
                        <strong id="ozResultGatePort" style="color: #0f172a; font-size: 13px; margin-top: 2px; display: block;">
                            <?php esc_html_e( 'Turnstile Barrier #01', 'swimming-pool-manager' ); ?>
                        </strong>
                    </div>

                    <div class="oz-telemetry-item" id="ozResultRoomWrap" style="display: none; background: rgba(2, 132, 199, 0.05); padding: 10px 14px; border-radius: 10px; border: 1px solid rgba(2, 132, 199, 0.2);">
                        <span style="font-size: 10px; font-weight: 700; color: #0284c7; text-transform: uppercase; letter-spacing: 0.5px; display: block;">
                            <i class="fa-solid fa-hotel"></i> <?php esc_html_e( 'Hotel Room Number', 'swimming-pool-manager' ); ?>
                        </span>
                        <strong id="ozResultRoom" class="ifs-pms-mono" style="color: #0284c7; font-size: 13.5px; margin-top: 2px; display: block;">—</strong>
                    </div>

                    <div class="oz-telemetry-item" style="background: #f8fafc; padding: 10px 14px; border-radius: 10px; border: 1px solid #f1f5f9;">
                        <span style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; display: block;">
                            <i class="fa-solid fa-hourglass-start" style="color: #0284c7;"></i> <?php esc_html_e( 'Booked Session', 'swimming-pool-manager' ); ?>
                        </span>
                        <strong id="ozResultDuration" class="ifs-pms-mono" style="color: #0f172a; font-size: 13px; margin-top: 2px; display: block;">—</strong>
                    </div>

                    <div class="oz-telemetry-item" style="background: #f8fafc; padding: 10px 14px; border-radius: 10px; border: 1px solid #f1f5f9;">
                        <span style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; display: block;">
                            <i class="fa-solid fa-clock" style="color: #10b981;"></i> <?php esc_html_e( 'Valid Until (Exit Window)', 'swimming-pool-manager' ); ?>
                        </span>
                        <strong id="ozResultValidUntil" class="ifs-pms-mono" style="color: #10b981; font-size: 13.5px; margin-top: 2px; display: block;">—</strong>
                    </div>

                    <div class="oz-telemetry-item" style="background: #f8fafc; padding: 10px 14px; border-radius: 10px; border: 1px solid #f1f5f9;">
                        <span style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; display: block;">
                            <i class="fa-solid fa-money-bill-wave" style="color: #10b981;"></i> <?php esc_html_e( 'Total Amount Settled', 'swimming-pool-manager' ); ?>
                        </span>
                        <strong id="ozResultAmount" class="ifs-pms-mono" style="color: #0f172a; font-size: 13.5px; margin-top: 2px; display: block;">—</strong>
                    </div>

                    <div class="oz-telemetry-item" style="background: #f8fafc; padding: 10px 14px; border-radius: 10px; border: 1px solid #f1f5f9;">
                        <span style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; display: block;">
                            <i class="fa-solid fa-credit-card" style="color: #0284c7;"></i> <?php esc_html_e( 'Payment Tender', 'swimming-pool-manager' ); ?>
                        </span>
                        <strong id="ozResultPayment" style="color: #0f172a; font-size: 13px; margin-top: 2px; display: block;">—</strong>
                    </div>

                    <div class="oz-telemetry-item" style="background: #f8fafc; padding: 10px 14px; border-radius: 10px; border: 1px solid #f1f5f9;">
                        <span style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; display: block;">
                            <i class="fa-solid fa-user-check" style="color: #0284c7;"></i> <?php esc_html_e( 'Desk Issuing Staff', 'swimming-pool-manager' ); ?>
                        </span>
                        <strong id="ozResultSoldBy" style="color: #475569; font-size: 12.5px; margin-top: 2px; display: block;">—</strong>
                    </div>

                    <div class="oz-telemetry-item" style="background: #f8fafc; padding: 10px 14px; border-radius: 10px; border: 1px solid #f1f5f9;">
                        <span style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; display: block;">
                            <i class="fa-solid fa-shield-halved" style="color: #10b981;"></i> <?php esc_html_e( 'Verifying Operator', 'swimming-pool-manager' ); ?>
                        </span>
                        <strong id="ozResultScannedBy" style="color: #475569; font-size: 12.5px; margin-top: 2px; display: block;">—</strong>
                    </div>
                </div>

                <!-- 3. Enrolled Packages & Inclusions Full Width Block -->
                <div style="margin-top: 12px; background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 10px; padding: 10px 14px;">
                    <span style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 2px;">
                        <i class="fa-solid fa-layer-group" style="color: #0284c7;"></i> <?php esc_html_e( 'Enrolled Package Inclusions & Amenities', 'swimming-pool-manager' ); ?>
                    </span>
                    <strong id="ozResultPackage" style="color: #0f172a; font-size: 12.5px; line-height: 1.4; display: block; font-weight: 700;">
                        <?php esc_html_e( 'No active scan telemetry loaded.', 'swimming-pool-manager' ); ?>
                    </strong>
                </div>

                <!-- 4. Security & Actuator Output Log -->
                <div id="ozResultMessageRow" style="margin-top: 14px; font-size: 12px; font-weight: 700; padding: 8px 14px; border-radius: 8px; color: #64748b; background: #f1f5f9; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-satellite-dish"></i> 
                    <span id="ozResultMessageTxt"><?php esc_html_e( 'Ready to authorize entry gates.', 'swimming-pool-manager' ); ?></span>
                </div>
            </div>

            <!-- Recent Turnstile Admittance Feed -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin: 20px 0 8px 0;">
                <div style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.08em;">
                    <i class="fa-solid fa-list-check" style="color: #0284c7;"></i> <?php esc_html_e( 'Shift Admittance Ledger Stream', 'swimming-pool-manager' ); ?>
                </div>
                <span style="font-size: 11px; color: #94a3b8; font-weight: 600;"><?php esc_html_e( 'Auto-synchronized', 'swimming-pool-manager' ); ?></span>
            </div>

            <div id="ozScanSessionLogContainer" style="display: flex; flex-direction: column; gap: 8px;">
                <div style="text-align: center; color: #94a3b8; padding: 36px 20px; font-size: 13px;" id="ozScanNoHistory">
                    <i class="fa-solid fa-fingerprint" style="font-size: 36px; opacity: 0.35; margin-bottom: 10px; display: block;"></i>
                    <?php esc_html_e( 'No tickets scanned in this session yet.', 'swimming-pool-manager' ); ?>
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
    let typingTimer     = null;

    const basePrefix      = <?php echo wp_json_encode( $current_mon_prefix ); ?>;
    const currencySym     = <?php echo wp_json_encode( $currency ); ?>;
    const currentOperator = <?php echo wp_json_encode( $current_operator ); ?>;

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

    function constructFullToken(rawVal) {
        let clean = rawVal.trim().toUpperCase();
        if (!clean) return '';

        if (clean.startsWith('OZONE-') || clean.startsWith('OZ-')) {
            return clean;
        }

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

        if (inputEl && /^\d+$/.test(rawVal.trim())) {
            inputEl.value = rawVal.trim().padStart(4, '0');
        }

        isProcessing = true;
        if (authBtn) authBtn.disabled = true;

        statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <?php echo esc_js( __( 'Contacting Turnstile Controller...', 'swimming-pool-manager' ) ); ?>';
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

                statusEl.innerHTML = '<i class="fa-solid fa-circle-check"></i> <?php echo esc_js( __( 'ACCESS GRANTED • TURNSTILE UNLOCKED', 'swimming-pool-manager' ) ); ?>';
                statusEl.style.background = 'rgba(16, 185, 129, 0.12)';
                statusEl.style.color = '#10b981';
                statusEl.style.borderColor = 'rgba(16, 185, 129, 0.4)';

                triggerBarrierRelay(true);
                displayTelemetry(res.data, true);
                logSessionEntry(codeVal, timeString, true, res.data.customer_name, res.data.guest_type, res.data.room_no);
            } else {
                soundAccessDenied();

                const errorMsg = (res && res.data && res.data.message) ? res.data.message : <?php echo wp_json_encode( __( 'Invalid or Expired Pass ID', 'swimming-pool-manager' ) ); ?>;
                statusEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> ' + escapeHtml(errorMsg);
                statusEl.style.background = 'rgba(239, 68, 68, 0.12)';
                statusEl.style.color = '#ef4444';
                statusEl.style.borderColor = 'rgba(239, 68, 68, 0.4)';

                triggerBarrierRelay(false);
                displayTelemetry({
                    ticket_code: codeVal,
                    customer_name: <?php echo wp_json_encode( __( 'Access Rejected', 'swimming-pool-manager' ) ); ?>,
                    customer_phone: '-',
                    guest_type: 'customer',
                    room_no: '',
                    package: <?php echo wp_json_encode( __( 'Verification Failed or Voided', 'swimming-pool-manager' ) ); ?>,
                    amount: '0.00',
                    payment_method: '-',
                    duration_hours: 0,
                    valid_until: '-',
                    sold_by: '-',
                    scanned_by: currentOperator,
                    message: errorMsg,
                    scanned_at: timeString
                }, false);
                logSessionEntry(codeVal, timeString, false, 'Invalid / Void Pass', 'customer', '');
            }
        })
        .catch(err => {
            statusEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> <?php echo esc_js( __( 'Gate Controller Timeout', 'swimming-pool-manager' ) ); ?>';
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
                txt.innerHTML  = '<i class="fa-solid fa-lock"></i> <?php echo esc_js( __( 'BARRIER LOCKED', 'swimming-pool-manager' ) ); ?>';
                if (sub) sub.textContent = <?php echo wp_json_encode( __( '4000ms actuation pulse • Auto-relock armed', 'swimming-pool-manager' ) ); ?>;
            }, 4000);
        } else {
            pill.className = 'oz-relay-status-pill closed';
            txt.innerHTML  = '<i class="fa-solid fa-ban"></i> <?php echo esc_js( __( 'ENTRY REJECTED', 'swimming-pool-manager' ) ); ?>';

            barrierTimer = setTimeout(() => {
                txt.innerHTML = '<i class="fa-solid fa-lock"></i> <?php echo esc_js( __( 'BARRIER LOCKED', 'swimming-pool-manager' ) ); ?>';
            }, 2500);
        }
    }

    // Complete Telemetry Population
    function displayTelemetry(data, isValid) {
        const card        = document.getElementById('ozResultTelemetryBox');
        const avatar      = document.getElementById('ozResultAvatar');
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
        const soldByEl    = document.getElementById('ozResultSoldBy');
        const scannedByEl = document.getElementById('ozResultScannedBy');
        const packageEl   = document.getElementById('ozResultPackage');
        const msgTxt      = document.getElementById('ozResultMessageTxt');
        const msgRow      = document.getElementById('ozResultMessageRow');

        if (!card) return;

        codeEl.textContent     = data.ticket_code || '-';
        patronEl.textContent   = data.customer_name || 'Walk-in Guest';
        phoneEl.textContent    = data.customer_phone || '-';
        msgTxt.textContent     = data.message || '';
        soldByEl.textContent   = data.sold_by || 'Front Desk Staff';
        scannedByEl.textContent= data.scanned_by || currentOperator;

        if (avatar) {
            avatar.textContent = (data.customer_name && data.customer_name.length > 0) ? data.customer_name.charAt(0).toUpperCase() : 'G';
        }

        if (isValid) {
            card.style.borderColor = 'rgba(16, 185, 129, 0.4)';
            card.style.background  = 'rgba(16, 185, 129, 0.02)';
            avatar.style.background= 'rgba(16, 185, 129, 0.15)';
            avatar.style.color     = '#10b981';

            badge.className        = 'ifs-pms-badge ifs-pms-badge-success';
            badge.textContent      = <?php echo wp_json_encode( __( 'Pass Authorized', 'swimming-pool-manager' ) ); ?>;
            
            msgRow.style.color     = '#10b981';
            msgRow.style.background= 'rgba(16, 185, 129, 0.08)';

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

            durationEl.textContent = (data.duration_hours || 1) + ' ' + ((data.duration_hours > 1) ? 'Hours' : 'Hour');
            validUntil.textContent = data.valid_until || '-';
            amountEl.textContent   = (data.amount || '0.00') + ' ' + currencySym;
            paymentEl.textContent  = data.payment_method || 'Cash';
            packageEl.textContent  = data.package || 'Standard Swim Pass';
        } else {
            card.style.borderColor = 'rgba(239, 68, 68, 0.4)';
            card.style.background  = 'rgba(239, 68, 68, 0.02)';
            avatar.style.background= 'rgba(239, 68, 68, 0.15)';
            avatar.style.color     = '#ef4444';

            badge.className        = 'ifs-pms-badge ifs-pms-badge-danger';
            badge.textContent      = <?php echo wp_json_encode( __( 'Access Denied', 'swimming-pool-manager' ) ); ?>;
            
            msgRow.style.color     = '#ef4444';
            msgRow.style.background= 'rgba(239, 68, 68, 0.08)';
            
            classBadge.textContent = 'Validation Rejected';
            classBadge.className   = 'ifs-pms-badge ifs-pms-badge-danger';

            if (roomWrap) roomWrap.style.display = 'none';
            durationEl.textContent = '-';
            validUntil.textContent = '-';
            amountEl.textContent   = '-';
            paymentEl.textContent  = '-';
            packageEl.textContent  = '-';
        }
    }

    // Hydrated Multi-Attribute Session Feed Entry
    function logSessionEntry(code, time, isValid, name, guestType, roomNo) {
        const emptyState = document.getElementById('ozScanNoHistory');
        if (emptyState) emptyState.remove();

        const container = document.getElementById('ozScanSessionLogContainer');
        if (!container) return;

        const row = document.createElement('div');
        row.style.background = '#ffffff';
        row.style.border = isValid ? '1px solid #e2e8f0' : '1px solid rgba(239, 68, 68, 0.2)';
        row.style.borderRadius = '10px';
        row.style.padding = '10px 14px';
        row.style.display = 'flex';
        row.style.justifyContent = 'space-between';
        row.style.alignItems = 'center';

        const badgeHtml = isValid 
            ? '<span class="ifs-pms-badge ifs-pms-badge-success" style="font-size: 11px;"><i class="fa-solid fa-check"></i> <?php echo esc_js( __( 'Admitted', 'swimming-pool-manager' ) ); ?></span>'
            : '<span class="ifs-pms-badge ifs-pms-badge-danger" style="font-size: 11px;"><i class="fa-solid fa-xmark"></i> <?php echo esc_js( __( 'Denied', 'swimming-pool-manager' ) ); ?></span>';

        const roomTag = (guestType === 'room_guest' && roomNo) ? `<span style="color:#0284c7; font-weight:700;">[Room ${escapeHtml(roomNo)}]</span>` : '';

        row.innerHTML = `
            <div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <strong class="ifs-pms-mono" style="color: #0284c7; font-size: 13px;">${escapeHtml(code)}</strong>
                    ${roomTag}
                </div>
                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                    <strong>${escapeHtml(name || 'Walk-in Guest')}</strong> &bull; <span class="ifs-pms-mono">${escapeHtml(time)}</span>
                </div>
            </div>
            <div>${badgeHtml}</div>
        `;

        container.insertBefore(row, container.firstChild);
        while (container.children.length > 8) {
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
            statusEl.innerHTML = '<i class="fa-solid fa-satellite-dish"></i> <?php echo esc_js( __( 'Terminal Standby • Present pass to scanner', 'swimming-pool-manager' ) ); ?>';
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
                if (label) label.textContent = <?php echo wp_json_encode( __( 'Turn Off Camera', 'swimming-pool-manager' ) ); ?>;

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
                alert(<?php echo wp_json_encode( __( 'Camera stream unavailable. Verify browser video permissions or use an external laser scanner.', 'swimming-pool-manager' ) ); ?>);
            }
        } else {
            if (videoStream) {
                videoStream.getTracks().forEach(t => t.stop());
            }
            clearInterval(scanInterval);
            video.style.display = 'none';
            if (placeholder) placeholder.style.display = 'block';
            if (label) label.textContent = <?php echo wp_json_encode( __( 'Activate WebCam Scanner', 'swimming-pool-manager' ) ); ?>;
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

            input.addEventListener('input', function() {
                const val = input.value.trim();
                clearTimeout(typingTimer);

                if (val.length >= 4 || val.toUpperCase().startsWith('OZONE-') || val.toUpperCase().startsWith('OZ-')) {
                    typingTimer = setTimeout(() => {
                        window.ozExecuteVerification();
                    }, 200);
                }
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    window.ozExecuteVerification();
                }
            });

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
            clearTimeout(typingTimer);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initConsole);
    } else {
        initConsole();
    }
})();
</script>