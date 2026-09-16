/**
 * Ozone Skypool OS - Terminal Client Engine
 */

// 1. Web Audio API Acoustic Feedback
const ifs_pms_audio = {
    ctx: null,
    init: function () {
        if (!this.ctx) {
            this.ctx = new (window.AudioContext || window.webkitAudioContext)();
        }
    },
    playSuccess: function () {
        this.init();
        const now = this.ctx.currentTime;
        const osc = this.ctx.createOscillator();
        const gain = this.ctx.createGain();
        osc.connect(gain);
        gain.connect(this.ctx.destination);
        osc.frequency.setValueAtTime(587.33, now);
        osc.frequency.setValueAtTime(880, now + 0.1);
        gain.gain.setValueAtTime(0.2, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
        osc.start(now);
        osc.stop(now + 0.35);
    },
    playError: function () {
        this.init();
        const now = this.ctx.currentTime;
        const osc = this.ctx.createOscillator();
        const gain = this.ctx.createGain();
        osc.connect(gain);
        gain.connect(this.ctx.destination);
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(150, now);
        osc.frequency.setValueAtTime(110, now + 0.15);
        gain.gain.setValueAtTime(0.3, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.4);
        osc.start(now);
        osc.stop(now + 0.4);
    }
};

// 2. Theme Switching Engine
function ifsPmsInitTheme() {
    const storedTheme = localStorage.getItem('ifs_pms_theme');
    const defaultDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const currentTheme = storedTheme ? storedTheme : (defaultDark ? 'dark' : 'light');
    ifsPmsApplyTheme(currentTheme);
}

function ifsPmsApplyTheme(theme) {
    const shell = document.getElementById('ifsPmsAppShell');
    const lightBtn = document.getElementById('ifsThemeLightBtn');
    const darkBtn = document.getElementById('ifsThemeDarkBtn');

    if (theme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        if (shell) shell.setAttribute('data-theme', 'dark');
        if (darkBtn) darkBtn.classList.add('active');
        if (lightBtn) lightBtn.classList.remove('active');
    } else {
        document.documentElement.removeAttribute('data-theme');
        if (shell) shell.removeAttribute('data-theme');
        if (lightBtn) lightBtn.classList.add('active');
        if (darkBtn) darkBtn.classList.remove('active');
    }
    localStorage.setItem('ifs_pms_theme', theme);
}

function ifsPmsSetTheme(theme) {
    ifsPmsApplyTheme(theme);
}

// 3. Thermal Receipt Rendering & Escape Utilities
function ifs_pms_show_receipt(t) {
    if (!t) return;
    const slipCode = document.getElementById('ifs-pms-slip-code');
    const slipMeta = document.getElementById('ifs-pms-slip-meta');
    const qrContainer = document.getElementById('ifs-pms-thermal-qr');
    const modal = document.getElementById('ifs-pms-thermal-modal');

    if (slipCode) slipCode.innerText = t.code;
    if (slipMeta) {
        slipMeta.innerHTML =
            '<strong>Patron:</strong> ' + ifsPmsEscapeHtml(t.name) + '<br>' +
            '<strong>Phone:</strong> ' + ifsPmsEscapeHtml(t.phone) + '<br>' +
            '<strong>Amount:</strong> ' + ifsPmsEscapeHtml(t.amount) + ' ' + (typeof ifsPmsConfig !== 'undefined' ? ifsPmsConfig.currency : 'BDT') + '<br>' +
            '<strong>Cashier:</strong> ' + ifsPmsEscapeHtml(t.staff) + '<br>' +
            '<strong>Timestamp:</strong> ' + ifsPmsEscapeHtml(t.date);
    }

    if (qrContainer && typeof QRCode !== 'undefined') {
        qrContainer.innerHTML = '';
        new QRCode(qrContainer, {
            text: t.code,
            width: 110,
            height: 110,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
    }

    if (modal) modal.style.display = 'flex';
}

function ifs_pms_close_receipt() {
    const modal = document.getElementById('ifs-pms-thermal-modal');
    if (modal) modal.style.display = 'none';
}

function ifsPmsEscapeHtml(string) {
    return String(string).replace(/[&<>"'`=\/]/g, function (s) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            '/': '&#x2F;',
            '`': '&#x60;',
            '=': '&#x3D;'
        }[s];
    });
}

function ifs_pms_export_ledger() {
    if (typeof ifsPmsConfig !== 'undefined') {
        window.location.href = ifsPmsConfig.ajax_url + '?action=ifs_pms_export_csv_action&security=' + ifsPmsConfig.nonce;
    }
}

// 4. Initial DOM Setup
document.addEventListener('DOMContentLoaded', function () {
    ifsPmsInitTheme();
    if (typeof ifsPmsConfig !== 'undefined' && ifsPmsConfig.last_ticket) {
        ifs_pms_show_receipt(ifsPmsConfig.last_ticket);
    }
});