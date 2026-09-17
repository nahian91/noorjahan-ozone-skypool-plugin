/**
 * Ozone Skypool OS - Terminal Client Engine & Dashboard Clock
 *
 * @package Ozone_Skypool_OS
 */

(function (window, document, $) {
    'use strict';

    // Root Configuration Helper
    const config = window.ifsPmsConfig || {
        ajax_url: window.ajaxurl || '',
        nonce: '',
        currency: 'BDT',
        last_ticket: null,
        i18n: {
            packageTitle: 'Package title',
            ageCategory: 'Age/category',
            itemName: 'Item name',
            mediaTitle: 'Select Venue Logo',
            mediaBtn: 'Use this logo',
            mediaAlert: 'WordPress Media Uploader is loading or unavailable. Please refresh the page.'
        }
    };

    /**
     * 1. Web Audio API Acoustic Feedback
     */
    const ifsPmsAudio = {
        ctx: null,

        init: function () {
            if (!this.ctx && (window.AudioContext || window.webkitAudioContext)) {
                this.ctx = new (window.AudioContext || window.webkitAudioContext)();
            }
        },

        playSuccess: function () {
            this.init();
            if (!this.ctx) return;

            if (this.ctx.state === 'suspended') {
                this.ctx.resume();
            }

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
            if (!this.ctx) return;

            if (this.ctx.state === 'suspended') {
                this.ctx.resume();
            }

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

    /**
     * 2. Security & Sanitization Utilities
     */
    function escapeHtml(string) {
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

    /**
     * 3. Theme Management Engine
     */
    function applyTheme(theme) {
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

    function initTheme() {
        const storedTheme = localStorage.getItem('ifs_pms_theme');
        const defaultDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const currentTheme = storedTheme ? storedTheme : (defaultDark ? 'dark' : 'light');
        applyTheme(currentTheme);
    }

    /**
     * 4. Thermal Slip Rendering Modal
     */
    function showReceipt(ticket) {
        if (!ticket) return;

        const slipCode = document.getElementById('ifs-pms-slip-code');
        const slipMeta = document.getElementById('ifs-pms-slip-meta');
        const qrContainer = document.getElementById('ifs-pms-thermal-qr');
        const modal = document.getElementById('ifs-pms-thermal-modal');

        if (slipCode) {
            slipCode.innerText = ticket.code || '';
        }

        if (slipMeta) {
            let metaHtml =
                '<strong>Patron:</strong> ' + escapeHtml(ticket.name || 'Walk-in Guest') + '<br>' +
                '<strong>Phone:</strong> ' + escapeHtml(ticket.phone || '-') + '<br>';

            if (ticket.room) {
                metaHtml += '<strong>Hotel Room #:</strong> ' + escapeHtml(ticket.room) + '<br>';
            }

            metaHtml +=
                '<strong>Amount:</strong> ' + escapeHtml(ticket.amount || '0.00') + ' ' + escapeHtml(config.currency) + '<br>' +
                '<strong>Cashier:</strong> ' + escapeHtml(ticket.staff || '-') + '<br>' +
                '<strong>Timestamp:</strong> ' + escapeHtml(ticket.date || '-');

            slipMeta.innerHTML = metaHtml;
        }

        if (qrContainer && typeof window.QRCode !== 'undefined') {
            qrContainer.innerHTML = '';
            new window.QRCode(qrContainer, {
                text: ticket.code || '',
                width: 110,
                height: 110,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: window.QRCode.CorrectLevel.M
            });
        }

        if (modal) {
            modal.style.display = 'flex';
        }
    }

    function closeReceipt() {
        const modal = document.getElementById('ifs-pms-thermal-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function exportLedger() {
        if (config.ajax_url && config.nonce) {
            window.location.href = config.ajax_url + '?action=ifs_pms_export_csv_action&security=' + config.nonce;
        }
    }

    /**
     * 5. Settings Tab Routing
     */
    function switchSettingsTab(tabKey, btn) {
        const buttons = document.querySelectorAll('.ifs-pms-subnav-btn');
        const panePos = document.getElementById('ifsPmsSettingsPanePos');
        const paneGeneral = document.getElementById('ifsPmsSettingsPaneGeneral');
        const activeTabInput = document.getElementById('ifsPmsActiveTabInput');

        buttons.forEach(b => b.classList.remove('active'));
        if (btn) {
            btn.classList.add('active');
        }

        if (panePos) panePos.classList.remove('active');
        if (paneGeneral) paneGeneral.classList.remove('active');

        if (tabKey === 'pos' && panePos) {
            panePos.classList.add('active');
        } else if (paneGeneral) {
            paneGeneral.classList.add('active');
        }

        if (activeTabInput) {
            activeTabInput.value = tabKey;
        }

        if (window.history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.set('tab', tabKey);
            window.history.replaceState({}, '', url);
        }
    }

    /**
     * 6. Financial Reports Sub-Tab & Filter Routing
     */
    function switchSubTab(subTabKey) {
        const subTabBtns = document.querySelectorAll('.ifs-pms-sub-tab-btn');
        const incomeContent = document.getElementById('ifsPmsIncomeTabContent');
        const expenseContent = document.getElementById('ifsPmsExpenseTabContent');
        const subTabInput = document.getElementById('ifsPmsSubTabInput');

        subTabBtns.forEach(b => b.classList.remove('active'));
        if (incomeContent) incomeContent.classList.remove('active');
        if (expenseContent) expenseContent.classList.remove('active');

        if (subTabInput) {
            subTabInput.value = subTabKey;
        }

        if (subTabKey === 'income') {
            if (subTabBtns[0]) subTabBtns[0].classList.add('active');
            if (incomeContent) incomeContent.classList.add('active');
        } else {
            if (subTabBtns[1]) subTabBtns[1].classList.add('active');
            if (expenseContent) expenseContent.classList.add('active');
        }
    }

    function applyIncomeFilter(filterVal) {
        const form = document.getElementById('ifsPmsReportGlobalForm');
        if (form) {
            const input = form.querySelector('input[name="income_filter"]');
            if (input) input.value = filterVal;
            form.submit();
        }
    }

    function applyExpenseFilter(catVal) {
        const form = document.getElementById('ifsPmsReportGlobalForm');
        if (form) {
            const input = form.querySelector('input[name="expense_cat"]');
            if (input) input.value = catVal;
            form.submit();
        }
    }

    /**
     * 7. Expense Management Utilities
     */
    function switchExpenseTab(tabKey, btn) {
        document.querySelectorAll('.ifs-pms-subnav-btn').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');

        const paneAdd = document.getElementById('ifsPmsExpensePaneAdd');
        const paneList = document.getElementById('ifsPmsExpensePaneList');

        if (paneAdd) paneAdd.classList.remove('active');
        if (paneList) paneList.classList.remove('active');

        if (tabKey === 'add' && paneAdd) {
            paneAdd.classList.add('active');
        } else if (paneList) {
            paneList.classList.add('active');
        }

        if (window.history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.set('tab', tabKey);
            window.history.replaceState({}, '', url);
        }
    }

    function setExpensePreset(title, cat) {
        const titleInput  = document.getElementById('ifsExpTitle');
        const catSelect   = document.getElementById('ifsExpCat');
        const amountInput = document.getElementById('ifsExpAmount');

        if (titleInput) titleInput.value = title;
        if (catSelect)  catSelect.value = cat;
        if (amountInput) amountInput.focus();
    }

    function openViewExpenseModal(data) {
        const titleEl = document.getElementById('ifsPmsViewExpTitle');
        const catEl   = document.getElementById('ifsPmsViewExpCat');
        const amtEl   = document.getElementById('ifsPmsViewExpAmount');
        const dateEl  = document.getElementById('ifsPmsViewExpDate');
        const byEl    = document.getElementById('ifsPmsViewExpBy');
        const modal   = document.getElementById('ifsPmsViewExpenseModal');

        if (titleEl) titleEl.textContent = data.title;
        if (catEl)   catEl.textContent = data.category;
        if (amtEl)   amtEl.textContent = '-' + data.amount + ' ' + config.currency;
        if (dateEl)  dateEl.textContent = data.date;
        if (byEl)    byEl.textContent = data.by;

        if (modal) modal.style.display = 'flex';
    }

    function closeViewExpenseModal() {
        const modal = document.getElementById('ifsPmsViewExpenseModal');
        if (modal) modal.style.display = 'none';
    }

    function openEditExpenseModal(data) {
        const idEl  = document.getElementById('ifsPmsEditExpId');
        const titleEl = document.getElementById('ifsPmsEditExpTitle');
        const catEl = document.getElementById('ifsPmsEditExpCat');
        const amtEl = document.getElementById('ifsPmsEditExpAmount');
        const dateEl = document.getElementById('ifsPmsEditExpDate');
        const modal = document.getElementById('ifsPmsEditExpenseModal');

        if (idEl) idEl.value = data.id;
        if (titleEl) titleEl.value = data.title;
        if (catEl) catEl.value = data.category;
        if (amtEl) amtEl.value = parseFloat(data.amount).toFixed(2);
        if (dateEl) dateEl.value = data.date;

        if (modal) modal.style.display = 'flex';
    }

    function closeEditExpenseModal() {
        const modal = document.getElementById('ifsPmsEditExpenseModal');
        if (modal) modal.style.display = 'none';
    }

    /**
     * 8. Dynamic Repeater Table Rows
     */
    function addPricingTierRow() {
        const tableBody = document.querySelector('#ifsPmsPricingTierTable tbody');
        if (!tableBody) return;

        const emptyRow = tableBody.querySelector('.ifs-pms-empty-row');
        if (emptyRow) {
            emptyRow.remove();
        }

        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="text" name="ifs_pricing_tier_name[]" required placeholder="${escapeHtml(config.i18n.packageTitle)}"></td>
            <td><input type="text" name="ifs_pricing_tier_age[]" required placeholder="${escapeHtml(config.i18n.ageCategory)}"></td>
            <td><input type="number" step="0.01" name="ifs_pricing_tier_price[]" required class="ifs-pms-mono" placeholder="0.00"></td>
            <td class="ifs-pms-td-action">
                <button type="button" class="ifs-pms-btn ifs-pms-btn-danger ifs-pms-btn-sm" onclick="ifsPms.removeRow(this)">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    }

    function addAddonRow() {
        const tableBody = document.querySelector('#ifsPmsAmenityAddonTable tbody');
        if (!tableBody) return;

        const emptyRow = tableBody.querySelector('.ifs-pms-empty-addon-row');
        if (emptyRow) {
            emptyRow.remove();
        }

        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="text" name="ifs_addon_name[]" required placeholder="${escapeHtml(config.i18n.itemName)}"></td>
            <td><input type="number" step="0.01" name="ifs_addon_price[]" required class="ifs-pms-mono" placeholder="0.00"></td>
            <td class="ifs-pms-td-action">
                <button type="button" class="ifs-pms-btn ifs-pms-btn-danger ifs-pms-btn-sm" onclick="ifsPms.removeRow(this)">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    }

    function removeRow(btn) {
        if (!btn) return;
        const tr = btn.closest('tr');
        if (tr) {
            tr.remove();
        }
    }

    /**
     * 9. WordPress Media Uploader
     */
    function openMediaUploader() {
        if (typeof window.wp === 'undefined' || !window.wp.media) {
            alert(config.i18n.mediaAlert);
            return;
        }

        const mediaUploader = window.wp.media({
            title: config.i18n.mediaTitle,
            button: { text: config.i18n.mediaBtn },
            multiple: false
        });

        mediaUploader.on('select', function () {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            const logoField = document.getElementById('ifsPmsLogoUrl');
            if (logoField && attachment && attachment.url) {
                logoField.value = attachment.url;
            }
        });

        mediaUploader.open();
    }

    /**
     * 10. Public API Namespace Registration
     */
    window.ifsPms = {
        audio: ifsPmsAudio,
        applyTheme: applyTheme,
        initTheme: initTheme,
        showReceipt: showReceipt,
        closeReceipt: closeReceipt,
        exportLedger: exportLedger,
        switchSettingsTab: switchSettingsTab,
        switchSubTab: switchSubTab,
        applyIncomeFilter: applyIncomeFilter,
        applyExpenseFilter: applyExpenseFilter,
        switchExpenseTab: switchExpenseTab,
        setExpensePreset: setExpensePreset,
        openViewExpenseModal: openViewExpenseModal,
        closeViewExpenseModal: closeViewExpenseModal,
        openEditExpenseModal: openEditExpenseModal,
        closeEditExpenseModal: closeEditExpenseModal,
        addPricingTierRow: addPricingTierRow,
        addAddonRow: addAddonRow,
        removeRow: removeRow,
        openMediaUploader: openMediaUploader,
        escapeHtml: escapeHtml
    };

    // Backward-Compatibility Aliases
    window.ifs_pms_audio = ifsPmsAudio;
    window.ifs_pms_show_receipt = showReceipt;
    window.ifs_pms_close_receipt = closeReceipt;
    window.ifs_pms_export_ledger = exportLedger;
    window.ifsPmsSwitchSettingsTab = switchSettingsTab;
    window.ifsPmsSwitchSubTab = switchSubTab;
    window.ifsPmsApplyIncomeFilter = applyIncomeFilter;
    window.ifsPmsApplyExpenseFilter = applyExpenseFilter;
    window.ifsPmsSwitchExpenseTab = switchExpenseTab;
    window.ifsPmsSetExpensePreset = setExpensePreset;
    window.ifsPmsOpenViewExpenseModal = openViewExpenseModal;
    window.ifsPmsCloseViewExpenseModal = closeViewExpenseModal;
    window.ifsPmsOpenEditExpenseModal = openEditExpenseModal;
    window.ifsPmsCloseEditExpenseModal = closeEditExpenseModal;
    window.ifsPmsAddPricingTierRow = addPricingTierRow;
    window.ifsPmsAddAddonRow = addAddonRow;
    window.ifsPmsRemoveRow = removeRow;
    window.ifsPmsOpenMediaUploader = openMediaUploader;

    /**
     * 11. Runtime Bootstrap
     */
    $(document).ready(function () {
        initTheme();

        if (config.last_ticket) {
            showReceipt(config.last_ticket);
        }

        // Amenities Repeater Visibility Toggle
        const $amenCheckbox = $('input[name="ifs_pms_enable_amenities"]');
        const $amenBox = $('#ifsPmsAmenitiesRepeaterBox');
        if ($amenCheckbox.length && $amenBox.length) {
            $amenCheckbox.on('change', function () {
                if (this.checked) {
                    $amenBox.addClass('is-visible');
                } else {
                    $amenBox.removeClass('is-visible');
                }
            });
        }
    });

})(window, document, jQuery);

/**
 * Ozone Skypool OS - Dashboard Clock & Countdown Engine
 */
(function (window, document) {
    'use strict';

    const closingTimeString = window.ifsPmsDashboardConfig ? window.ifsPmsDashboardConfig.closing_time : '23:00';

    function ifsPmsUpdateClockAndCountdown() {
        const now = new Date();
        let hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        const hoursStr = String(hours).padStart(2, '0');
        
        const timeStr = hoursStr + ':' + minutes + ':' + seconds + ' ' + ampm;
        const clockEl = document.getElementById('ifsPmsLiveClock');
        if (clockEl) {
            clockEl.textContent = timeStr;
        }

        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const dateStr = now.toLocaleDateString(undefined, options);
        const dateEl = document.getElementById('ifsPmsLiveDate');
        if (dateEl) {
            dateEl.textContent = dateStr;
        }

        // Calculate Time Remaining until Closing Time
        const countdownEl = document.getElementById('ifsPmsCountdownText');
        const containerEl = document.getElementById('ifsPmsClosingCountdown');
        if (countdownEl && closingTimeString) {
            const parts = closingTimeString.split(':');
            const closeHour = parseInt(parts[0], 10);
            const closeMin = parseInt(parts[1] || 0, 10);

            const closeDate = new Date(now);
            closeDate.setHours(closeHour, closeMin, 0, 0);

            const diffMs = closeDate - now;
            if (diffMs > 0) {
                const diffHrs = Math.floor(diffMs / (1000 * 60 * 60));
                const diffMins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
                const diffSecs = Math.floor((diffMs % (1000 * 60)) / 1000);

                countdownEl.textContent = diffHrs + 'h ' + diffMins + 'm ' + diffSecs + 's to close';
                if (containerEl) containerEl.style.display = 'inline-flex';
            } else {
                if (containerEl) containerEl.style.display = 'none';
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            ifsPmsUpdateClockAndCountdown();
            setInterval(ifsPmsUpdateClockAndCountdown, 1000);
        });
    } else {
        ifsPmsUpdateClockAndCountdown();
        setInterval(ifsPmsUpdateClockAndCountdown, 1000);
    }

})(window, document);