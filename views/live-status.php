<?php
/**
 * View: Live Pool Floor Status & Active Swimmer Telemetry (Live Surcharge Edition)
 *
 * @package Ozone_Skypool_OS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$t_tick = $wpdb->prefix . 'ifs_pms_tickets';
$t_cust = $wpdb->prefix . 'ifs_pms_customers';

$wp_now_dt    = current_datetime(); 
$today_ymd    = $wp_now_dt->format( 'Y-m-d' );
$server_epoch = $wp_now_dt->getTimestamp();
$currency     = esc_html( (string) get_option( 'ifs_pms_currency', 'BDT' ) );

$max_capacity = (int) get_option( 'ifs_pms_max_capacity', 80 );
if ( $max_capacity <= 0 ) {
    $max_capacity = 80;
}

// Handle manual checkout/exit action
if ( isset( $_POST['ifs_pms_action'] ) && $_POST['ifs_pms_action'] === 'checkout_swimmer' ) {
    check_admin_referer( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' );
    $checkout_id = absint( $_POST['ticket_id'] ?? 0 );
    if ( $checkout_id > 0 ) {
        $wpdb->update(
            $t_tick,
            array( 'status' => 'Completed' ),
            array( 'id' => $checkout_id ),
            array( '%s' ),
            array( '%d' )
        );
        echo '<div class="notice notice-success is-dismissible oz-notice-spacing"><p>' . esc_html__( 'Patron checked out from pool deck successfully.', 'swimming-pool-manager' ) . '</p></div>';
    }
}

// Fetch active admissions for today
$active_swimmers = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT t.id, t.ticket_code, t.customer_id, t.duration_hours, t.amount, t.sold_at, t.scanned_at
         FROM {$t_tick} t
         WHERE t.status = 'Used'
           AND (
               DATE(COALESCE(NULLIF(t.scanned_at, '0000-00-00 00:00:00'), t.sold_at)) = %s
           )
         ORDER BY COALESCE(NULLIF(t.scanned_at, '0000-00-00 00:00:00'), t.sold_at) DESC",
        $today_ymd
    )
);

$current_count = count( $active_swimmers );
$capacity_pct  = min( 100, (int) round( ( $current_count / $max_capacity ) * 100 ) );

if ( $capacity_pct >= 85 ) {
    $status_label = __( 'Critical / Peak', 'swimming-pool-manager' );
    $status_badge = 'ifs-pms-badge-danger';
    $bar_gradient = 'linear-gradient(90deg, #f59e0b 0%, #ef4444 100%)';
} elseif ( $capacity_pct >= 55 ) {
    $status_label = __( 'Moderate Flow', 'swimming-pool-manager' );
    $status_badge = 'ifs-pms-badge-warning';
    $bar_gradient = 'linear-gradient(90deg, #0284c7 0%, #f59e0b 100%)';
} else {
    $status_label = __( 'Optimal Space', 'swimming-pool-manager' );
    $status_badge = 'ifs-pms-badge-success';
    $bar_gradient = 'linear-gradient(90deg, #0284c7 0%, #10b981 100%)';
}
?>

<div class="oz-live-status-stack">
    <!-- Top Gauge & Live Occupancy -->
    <div class="oz-status-hero">
        <div class="oz-status-hero-left">
            <div class="oz-status-orb">
                <i class="fa-solid fa-person-swimming"></i>
            </div>
            <div>
                <div class="oz-status-title-tag"><?php esc_html_e( 'Live In-Pool Census', 'swimming-pool-manager' ); ?></div>
                <div class="oz-status-count">
                    <span class="ifs-pms-mono" id="ozLiveCensusCount"><?php echo esc_html( (string) $current_count ); ?></span>
                    <span class="oz-status-sub">/ <span class="ifs-pms-mono" id="ozLiveCensusCapacity"><?php echo esc_html( (string) $max_capacity ); ?></span> <?php esc_html_e( 'Capacity', 'swimming-pool-manager' ); ?></span>
                </div>
            </div>
        </div>

        <div>
            <div class="oz-progress-label-row">
                <span class="oz-progress-title"><?php esc_html_e( 'Surface Occupancy', 'swimming-pool-manager' ); ?></span>
                <span class="ifs-pms-mono oz-progress-pct" id="ozLiveCapacityPctText"><?php echo esc_html( (string) $capacity_pct ); ?>%</span>
            </div>
            <div class="oz-progress-track">
                <div class="oz-progress-fill" id="ozLiveCapacityBar" style="width: <?php echo esc_attr( (string) $capacity_pct ); ?>%; background: <?php echo esc_attr( $bar_gradient ); ?>;"></div>
            </div>
        </div>

        <div>
            <span class="ifs-pms-badge <?php echo esc_attr( $status_badge ); ?> oz-status-pill-padded" id="ozLiveStatusPill">
                <span class="ifs-pms-pulse-dot"></span> <span id="ozLiveStatusText"><?php echo esc_html( $status_label ); ?></span>
            </span>
        </div>
    </div>

    <!-- Active Swimmers Registry -->
    <div class="oz-telemetry-panel">
        <div class="oz-telemetry-head">
            <h3 class="oz-telemetry-title">
                <i class="fa-solid fa-tower-broadcast oz-telemetry-icon"></i>
                <?php esc_html_e( 'Active Pool Floor Patrons & Duration Watchdog', 'swimming-pool-manager' ); ?>
            </h3>
            <div class="oz-live-clock-pill">
                <i class="fa-regular fa-clock"></i>
                <span class="ifs-pms-mono" id="ozWatcherClock">--:--:-- --</span>
            </div>
        </div>

        <div class="oz-table-wrap">
            <table class="oz-table" id="ozActiveSwimmersTable">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Pass Code', 'swimming-pool-manager' ); ?></th>
                        <th><?php esc_html_e( 'Entry Time', 'swimming-pool-manager' ); ?></th>
                        <th><?php esc_html_e( 'Duration', 'swimming-pool-manager' ); ?></th>
                        <th><?php esc_html_e( 'Target Exit', 'swimming-pool-manager' ); ?></th>
                        <th><?php esc_html_e( 'Remaining / Extra & Surcharge', 'swimming-pool-manager' ); ?></th>
                        <th class="oz-th-right"><?php esc_html_e( 'Action', 'swimming-pool-manager' ); ?></th>
                    </tr>
                </thead>
                <tbody id="ozActiveSwimmersTbody">
                    <?php if ( ! empty( $active_swimmers ) ) : ?>
                        <?php foreach ( $active_swimmers as $swimmer ) :
                            $hours  = max( 1, (int) $swimmer->duration_hours );
                            $amount = max( 0.00, (float) $swimmer->amount );
                            $hourly_rate = $amount > 0 ? ( $amount / $hours ) : 100.00;
                            
                            $entry_raw = ( ! empty( $swimmer->scanned_at ) && $swimmer->scanned_at !== '0000-00-00 00:00:00' ) 
                                ? $swimmer->scanned_at 
                                : $swimmer->sold_at;

                            $entry_dt = date_create_immutable_from_format( 'Y-m-d H:i:s', $entry_raw, wp_timezone() );
                            if ( ! $entry_dt ) {
                                $entry_dt = $wp_now_dt;
                            }
                            
                            $entry_stamp = $entry_dt->getTimestamp();
                            $exit_stamp  = $entry_stamp + ( $hours * 3600 );
                            
                            $entry_formatted = $entry_dt->format( 'h:i:s A' );
                            $exit_formatted  = wp_date( 'h:i:s A', $exit_stamp, wp_timezone() );
                        ?>
                            <tr class="oz-swimmer-row" 
                                id="ozRow-<?php echo esc_attr( (string) $swimmer->id ); ?>"
                                data-id="<?php echo esc_attr( (string) $swimmer->id ); ?>"
                                data-hourly-rate="<?php echo esc_attr( (string) $hourly_rate ); ?>"
                                data-exit-timestamp="<?php echo esc_attr( (string) $exit_stamp ); ?>"
                                data-entry-timestamp="<?php echo esc_attr( (string) $entry_stamp ); ?>">
                                
                                <td class="ifs-pms-mono oz-text-blue oz-text-weight-heavy">
                                    <?php echo esc_html( $swimmer->ticket_code ); ?>
                                </td>

                                <td class="ifs-pms-mono oz-text-dark oz-text-weight-bold">
                                    <?php echo esc_html( $entry_formatted ); ?>
                                </td>

                                <td class="ifs-pms-mono">
                                    <span class="ifs-pms-badge oz-badge-soft-blue">
                                        <?php echo esc_html( (string) $hours ); ?> <?php echo esc_html( _n( 'Hr', 'Hrs', $hours, 'swimming-pool-manager' ) ); ?>
                                    </span>
                                </td>

                                <td class="ifs-pms-mono oz-text-sub oz-text-weight-heavy">
                                    <?php echo esc_html( $exit_formatted ); ?>
                                </td>

                                <td>
                                    <span class="oz-row-clock-pill oz-clock-safe oz-timer-display" id="ozTimer-<?php echo esc_attr( (string) $swimmer->id ); ?>">
                                        <i class="fa-regular fa-clock"></i>
                                        <span class="oz-clock-digits">00:00:00 left</span>
                                    </span>
                                </td>

                                <td class="oz-td-right">
                                    <button type="button" class="oz-checkout-btn" onclick="ozCheckoutSwimmer(<?php echo esc_attr( (string) $swimmer->id ); ?>, this)">
                                        <i class="fa-solid fa-person-walking-arrow-right"></i> <?php esc_html_e( 'Check Out', 'swimming-pool-manager' ); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr id="ozEmptyNoticeRow">
                            <td colspan="6" class="oz-empty-state">
                                <i class="fa-solid fa-water-ladder oz-empty-icon"></i>
                                <?php esc_html_e( 'No swimmers currently registered inside the pool.', 'swimming-pool-manager' ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function($) {
    'use strict';

    let serverStartEpoch = <?php echo (int) $server_epoch; ?>;
    let clientMountTime  = Date.now();
    let currencySymbol   = <?php echo wp_json_encode( $currency ); ?>;
    let isPolling        = false;

    const txtNoSwimmers = <?php echo wp_json_encode( __( 'No swimmers currently registered inside the pool.', 'swimming-pool-manager' ) ); ?>;
    const txtCheckoutBtn = <?php echo wp_json_encode( __( 'Check Out', 'swimming-pool-manager' ) ); ?>;
    const txtConfirmCheckout = <?php echo wp_json_encode( __( 'Check out patron from pool deck?', 'swimming-pool-manager' ) ); ?>;

    function getSyncedEpoch() {
        const elapsedSeconds = Math.floor((Date.now() - clientMountTime) / 1000);
        return serverStartEpoch + elapsedSeconds;
    }

    function formatDigitalClock(totalSeconds) {
        const hrs  = Math.floor(totalSeconds / 3600);
        const mins = Math.floor((totalSeconds % 3600) / 60);
        const secs = totalSeconds % 60;

        return String(hrs).padStart(2, '0') + ':' + 
               String(mins).padStart(2, '0') + ':' + 
               String(secs).padStart(2, '0');
    }

    function renderTimeBadge(rowEl, badgeEl, diff) {
        const hourlyRate = parseFloat(rowEl.getAttribute('data-hourly-rate')) || 100.00;

        if (diff > 0) {
            const timeDigits = formatDigitalClock(diff);

            if (diff <= 900) {
                badgeEl.className = 'oz-row-clock-pill oz-clock-warning oz-timer-display';
                badgeEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> <span class="oz-clock-digits">' + timeDigits + ' left</span>';
            } else {
                badgeEl.className = 'oz-row-clock-pill oz-clock-safe oz-timer-display';
                badgeEl.innerHTML = '<i class="fa-regular fa-clock"></i> <span class="oz-clock-digits">' + timeDigits + ' left</span>';
            }
        } else {
            const overstaySeconds = Math.abs(diff);
            const timeDigits = formatDigitalClock(overstaySeconds);
            
            const extraHours = overstaySeconds / 3600;
            const surchargeAmount = (extraHours * hourlyRate).toFixed(2);

            badgeEl.className = 'oz-row-clock-pill oz-clock-overstay oz-timer-display';
            badgeEl.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> ' +
                                '<span class="oz-clock-digits">+' + timeDigits + ' extra</span>' +
                                '<span class="oz-surcharge-text">Fee: +' + surchargeAmount + ' ' + currencySymbol + '</span>';
        }
    }

    function runLiveClockAndCounters() {
        const currentEpoch = getSyncedEpoch();
        const serverDate   = new Date(currentEpoch * 1000);

        let hours = serverDate.getUTCHours();
        const minutes = String(serverDate.getUTCMinutes()).padStart(2, '0');
        const seconds = String(serverDate.getUTCSeconds()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        const clockStr = String(hours).padStart(2, '0') + ':' + minutes + ':' + seconds + ' ' + ampm;
        
        const clockEl = document.getElementById('ozWatcherClock');
        if (clockEl) {
            clockEl.textContent = clockStr;
        }

        const rows = document.querySelectorAll('.oz-swimmer-row');
        rows.forEach(function(row) {
            const exitStamp = parseInt(row.getAttribute('data-exit-timestamp'), 10);
            const badgeEl = row.querySelector('.oz-timer-display');
            if (!badgeEl || isNaN(exitStamp)) return;

            const diff = exitStamp - currentEpoch;
            renderTimeBadge(row, badgeEl, diff);
        });
    }

    function pollPoolFloorData() {
        if (isPolling) return;
        isPolling = true;

        $.ajax({
            url: window.ajaxurl || '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ifs_pms_get_live_telemetry_action',
                security: '<?php echo wp_create_nonce( 'ifs_pms_security_token' ); ?>'
            },
            success: function(res) {
                isPolling = false;
                if (!res || !res.success || !res.data) return;

                const data = res.data;

                if (data.server_epoch) {
                    serverStartEpoch = data.server_epoch;
                    clientMountTime  = Date.now();
                }

                $('#ozLiveCensusCount').text(data.current_count);
                $('#ozLiveCensusCapacity').text(data.max_capacity);
                $('#ozLiveCapacityPctText').text(data.capacity_pct + '%');
                $('#ozLiveCapacityBar').css({
                    'width': data.capacity_pct + '%',
                    'background': data.bar_gradient
                });
                $('#ozLiveStatusPill').attr('class', 'ifs-pms-badge ' + data.status_badge + ' oz-status-pill-padded');
                $('#ozLiveStatusText').text(data.status_label);

                const tbody = $('#ozActiveSwimmersTbody');
                if (!data.swimmers || data.swimmers.length === 0) {
                    tbody.html(
                        '<tr id="ozEmptyNoticeRow">' +
                            '<td colspan="6" class="oz-empty-state">' +
                                '<i class="fa-solid fa-water-ladder oz-empty-icon-large"></i>' +
                                txtNoSwimmers +
                            '</td>' +
                        '</tr>'
                    );
                    return;
                }

                $('#ozEmptyNoticeRow').remove();

                const existingRowIds = [];
                $('.oz-swimmer-row').each(function() {
                    existingRowIds.push(parseInt($(this).attr('data-id'), 10));
                });

                const incomingIds = data.swimmers.map(s => parseInt(s.id, 10));

                existingRowIds.forEach(function(id) {
                    if (!incomingIds.includes(id)) {
                        $('#ozRow-' + id).fadeOut(250, function() { $(this).remove(); });
                    }
                });

                data.swimmers.forEach(function(swimmer) {
                    const rowEl = $('#ozRow-' + swimmer.id);
                    if (rowEl.length === 0) {
                        const newRowHtml = 
                            '<tr class="oz-swimmer-row" id="ozRow-' + swimmer.id + '" data-id="' + swimmer.id + '" data-hourly-rate="' + swimmer.hourly_rate + '" data-exit-timestamp="' + swimmer.exit_stamp + '" data-entry-timestamp="' + swimmer.entry_stamp + '">' +
                                '<td class="ifs-pms-mono oz-text-blue oz-text-weight-heavy">' + swimmer.ticket_code + '</td>' +
                                '<td class="ifs-pms-mono oz-text-dark oz-text-weight-bold">' + swimmer.entry_formatted + '</td>' +
                                '<td class="ifs-pms-mono"><span class="ifs-pms-badge oz-badge-soft-blue">' + swimmer.duration_hours + (swimmer.duration_hours > 1 ? ' Hrs' : ' Hr') + '</span></td>' +
                                '<td class="ifs-pms-mono oz-text-sub oz-text-weight-heavy">' + swimmer.exit_formatted + '</td>' +
                                '<td><span class="oz-row-clock-pill oz-clock-safe oz-timer-display" id="ozTimer-' + swimmer.id + '"><i class="fa-regular fa-clock"></i> <span class="oz-clock-digits">00:00:00 left</span></span></td>' +
                                '<td class="oz-td-right"><button type="button" class="oz-checkout-btn" onclick="ozCheckoutSwimmer(' + swimmer.id + ', this)"><i class="fa-solid fa-person-walking-arrow-right"></i> ' + txtCheckoutBtn + '</button></td>' +
                            '</tr>';
                        tbody.prepend($(newRowHtml).hide().fadeIn(300));
                    } else {
                        rowEl.attr('data-exit-timestamp', swimmer.exit_stamp);
                        rowEl.attr('data-entry-timestamp', swimmer.entry_stamp);
                        rowEl.attr('data-hourly-rate', swimmer.hourly_rate);
                    }
                });

                runLiveClockAndCounters();
            },
            error: function() {
                isPolling = false;
            }
        });
    }

    window.ozCheckoutSwimmer = function(ticketId, btn) {
        if (!confirm(txtConfirmCheckout)) {
            return;
        }

        const $btn = $(btn);
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i>');

        $.ajax({
            url: window.ajaxurl || '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ifs_pms_checkout_swimmer_ajax',
                ticket_id: ticketId,
                security: '<?php echo wp_create_nonce( 'ifs_pms_security_token' ); ?>'
            },
            success: function(res) {
                if (res && res.success) {
                    $('#ozRow-' + ticketId).fadeOut(300, function() {
                        $(this).remove();
                        if ($('.oz-swimmer-row').length === 0) {
                            $('#ozActiveSwimmersTbody').html(
                                '<tr id="ozEmptyNoticeRow">' +
                                    '<td colspan="6" class="oz-empty-state">' +
                                        '<i class="fa-solid fa-water-ladder oz-empty-icon-large"></i>' +
                                        txtNoSwimmers +
                                    '</td>' +
                                '</tr>'
                            );
                        }
                    });
                    pollPoolFloorData();
                } else {
                    alert((res && res.data && res.data.message) ? res.data.message : 'Checkout failed');
                    $btn.prop('disabled', false).html('<i class="fa-solid fa-person-walking-arrow-right"></i> ' + txtCheckoutBtn);
                }
            },
            error: function() {
                alert('Connection error during checkout.');
                $btn.prop('disabled', false).html('<i class="fa-solid fa-person-walking-arrow-right"></i> ' + txtCheckoutBtn);
            }
        });
    };

    $(document).ready(function() {
        runLiveClockAndCounters();
        setInterval(runLiveClockAndCounters, 1000);
        setInterval(pollPoolFloorData, 5000);
    });

})(jQuery);
</script>