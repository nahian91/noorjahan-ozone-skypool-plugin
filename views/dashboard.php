<?php
/**
 * View: Operations Dashboard & Executive Command Center (Enterprise Edition v7 - 5 Stream Limit & Zero Inline CSS)
 *
 * @package Ozone_Skypool_OS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

// 1. Establish Table References & Parameters
$t_tick    = $wpdb->prefix . 'ifs_pms_tickets';
$t_cust    = $wpdb->prefix . 'ifs_pms_customers';
$t_members = $wpdb->prefix . 'ifs_pms_memberships';
$t_expense = $wpdb->prefix . 'ifs_pms_expenses';

$currency      = esc_html( (string) get_option( 'ifs_pms_currency', 'BDT' ) );
$today_ymd     = current_time( 'Y-m-d' );
$base_dash_url = admin_url( 'admin.php?page=ifs-pms' );

// Enqueue standalone dashboard stylesheet and script
wp_enqueue_style( 'ifs-pms-dashboard-css', IFS_PMS_URL . 'assets/css/dashboard.css', array(), IFS_PMS_VERSION );
wp_enqueue_script( 'ifs-pms-dashboard-js', IFS_PMS_URL . 'assets/js/dashboard.js', array(), IFS_PMS_VERSION, true );

// 2. Financial Telemetry
$today_tickets_rev = (float) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM {$t_tick} WHERE DATE(sold_at) = %s AND status != 'Cancelled'",
        $today_ymd
    )
);

$today_members_rev = (float) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM {$t_members} WHERE DATE(created_at) = %s",
        $today_ymd
    )
);

$today_revenue = $today_tickets_rev + $today_members_rev;

$today_tickets_issued = (int) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$t_tick} WHERE DATE(sold_at) = %s",
        $today_ymd
    )
);

$today_admitted = (int) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$t_tick} WHERE DATE(scanned_at) = %s AND status = 'Used'",
        $today_ymd
    )
);

$today_expenses = (float) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM {$t_expense} WHERE expense_date = %s",
        $today_ymd
    )
);

$today_net_margin = $today_revenue - $today_expenses;
$entry_turnover   = $today_tickets_issued > 0 ? (int) round( ( $today_admitted / $today_tickets_issued ) * 100 ) : 0;

// 3. Pool Capacity Metrics & Dynamics
$max_pool_capacity = (int) get_option( 'ifs_pms_max_capacity', 80 );
if ( $max_pool_capacity <= 0 ) {
    $max_pool_capacity = 80;
}

$current_swimmers = max( 0, $today_admitted );
$capacity_pct     = min( 100, (int) round( ( $current_swimmers / $max_pool_capacity ) * 100 ) );

if ( $capacity_pct >= 85 ) {
    $status_label = __( 'Near Peak Capacity', 'ozone-skypool' );
    $status_class = 'ifs-pms-status-red';
    $bar_gradient = 'linear-gradient(90deg, #f59e0b 0%, #ef4444 100%)';
} elseif ( $capacity_pct >= 55 ) {
    $status_label = __( 'Moderate Traffic', 'ozone-skypool' );
    $status_class = 'ifs-pms-status-amber';
    $bar_gradient = 'linear-gradient(90deg, #0284c7 0%, #f59e0b 100%)';
} else {
    $status_label = __( 'Optimal Space', 'ozone-skypool' );
    $status_class = 'ifs-pms-status-green';
    $bar_gradient = 'linear-gradient(90deg, #0284c7 0%, #10b981 100%)';
}

// 4. Operating Hours & Closing Time Calculation
$weekly_schedule  = get_option( 'ifs_pms_weekly_schedule', array() );
$day_key          = strtolower( current_time( 'l' ) );
$today_sched      = $weekly_schedule[ $day_key ] ?? array();
$closing_time_str = $today_sched['close'] ?? '23:00';

// Pass closing time safely to client engine
wp_localize_script( 'ifs-pms-dashboard-js', 'ifsPmsDashboardConfig', array(
    'closing_time' => $closing_time_str,
) );

// 5. Live Admissions Stream (Strictly Latest 5 Records)
$recent_admissions = $wpdb->get_results(
    "SELECT t.ticket_code, t.amount, t.status, t.sold_at, t.scanned_at, c.name AS customer_name 
     FROM {$t_tick} t 
     LEFT JOIN {$t_cust} c ON t.customer_id = c.id 
     ORDER BY t.id DESC 
     LIMIT 5"
);
?>

<div class="ifs-pms-dash-stack">
    <!-- Live Telemetry & Station Header -->
    <div class="ifs-pms-telemetry-header">
        <div class="ifs-pms-clock-wrap">
            <div class="ifs-pms-clock-icon">
                <i class="fa-regular fa-clock"></i>
            </div>
            <div>
                <div class="ifs-pms-clock-digits" id="ifsPmsLiveClock">--:--:-- --</div>
                <div class="ifs-pms-clock-date" id="ifsPmsLiveDate"><?php echo esc_html( current_time( 'l, F j, Y' ) ); ?></div>
                <div class="ifs-pms-closing-countdown" id="ifsPmsClosingCountdown">
                    <i class="fa-solid fa-hourglass-half"></i> <span id="ifsPmsCountdownText">--</span>
                </div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 24px;">
            <div class="ifs-pms-operator-meta">
                <div class="ifs-pms-operator-name"><?php echo esc_html( wp_get_current_user()->display_name ); ?></div>
                <div class="ifs-pms-operator-role"><?php esc_html_e( 'Terminal Operator • Desk Active', 'ozone-skypool' ); ?></div>
            </div>
            <div class="ifs-pms-status-pill ifs-pms-status-green">
                <span class="ifs-pms-pulse-dot"></span> <?php esc_html_e( 'Turnstiles Synchronized', 'ozone-skypool' ); ?>
            </div>
        </div>
    </div>

    <!-- Live Rooftop Capacity Gauge -->
    <div class="ifs-pms-gauge-card">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div class="ifs-pms-gauge-icon-box">
                <i class="fa-solid fa-person-swimming"></i>
            </div>
            <div>
                <div class="ifs-pms-gauge-title">
                    <?php esc_html_e( 'Rooftop Skypool Occupancy', 'ozone-skypool' ); ?>
                </div>
                <div class="ifs-pms-gauge-numbers">
                    <span class="ifs-pms-mono"><?php echo esc_html( (string) $current_swimmers ); ?></span>
                    <span class="ifs-pms-gauge-subtitle">/ <span class="ifs-pms-mono"><?php echo esc_html( (string) $max_pool_capacity ); ?></span> <?php esc_html_e( 'max swimmers', 'ozone-skypool' ); ?></span>
                </div>
            </div>
        </div>

        <div class="ifs-pms-gauge-progress-wrap">
            <div class="ifs-pms-gauge-progress-meta">
                <span class="ifs-pms-gauge-progress-label"><?php esc_html_e( 'Water & Deck Utilization', 'ozone-skypool' ); ?></span>
                <span class="ifs-pms-mono ifs-pms-gauge-progress-val">
                    <?php echo esc_html( (string) $capacity_pct ); ?>%
                </span>
            </div>
            <div class="ifs-pms-gauge-progress-track">
                <div class="ifs-pms-gauge-progress-bar" style="width: <?php echo esc_attr( (string) $capacity_pct ); ?>%; background: <?php echo esc_attr( $bar_gradient ); ?>;"></div>
            </div>
        </div>

        <div>
            <span class="ifs-pms-status-pill <?php echo esc_attr( $status_class ); ?>">
                <?php echo esc_html( $status_label ); ?>
            </span>
        </div>
    </div>

    <!-- Quick Operational Action Tiles -->
    <div class="ifs-pms-action-deck">
        <a href="<?php echo esc_url( $base_dash_url . '&view=tickets' ); ?>" class="ifs-pms-action-card">
            <div class="ifs-pms-action-icon ifs-pms-action-icon-blue">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <div>
                <div class="ifs-pms-action-title"><?php esc_html_e( 'Sell Ticket Pass', 'ozone-skypool' ); ?></div>
                <div class="ifs-pms-action-sub"><?php esc_html_e( 'Single, Combo & VIP passes', 'ozone-skypool' ); ?></div>
            </div>
        </a>

        <a href="<?php echo esc_url( $base_dash_url . '&view=scanner' ); ?>" class="ifs-pms-action-card">
            <div class="ifs-pms-action-icon ifs-pms-action-icon-green">
                <i class="fa-solid fa-qrcode"></i>
            </div>
            <div>
                <div class="ifs-pms-action-title"><?php esc_html_e( 'Gate Turnstile Scan', 'ozone-skypool' ); ?></div>
                <div class="ifs-pms-action-sub"><?php esc_html_e( 'Check-in admission QR codes', 'ozone-skypool' ); ?></div>
            </div>
        </a>

        <a href="<?php echo esc_url( $base_dash_url . '&view=membership' ); ?>" class="ifs-pms-action-card">
            <div class="ifs-pms-action-icon ifs-pms-action-icon-purple">
                <i class="fa-solid fa-id-card"></i>
            </div>
            <div>
                <div class="ifs-pms-action-title"><?php esc_html_e( 'Enroll Members', 'ozone-skypool' ); ?></div>
                <div class="ifs-pms-action-sub"><?php esc_html_e( 'Monthly & Season Passes', 'ozone-skypool' ); ?></div>
            </div>
        </a>

        <a href="<?php echo esc_url( $base_dash_url . '&view=expenses' ); ?>" class="ifs-pms-action-card">
            <div class="ifs-pms-action-icon ifs-pms-action-icon-red">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <div>
                <div class="ifs-pms-action-title"><?php esc_html_e( 'Record Outflow', 'ozone-skypool' ); ?></div>
                <div class="ifs-pms-action-sub"><?php esc_html_e( 'Salary, Utility, Fuel & Bills', 'ozone-skypool' ); ?></div>
            </div>
        </a>
    </div>

    <!-- Executive KPI Summary Matrix -->
    <div class="ifs-pms-kpi-quad">
        <div class="ifs-pms-kpi-brick ifs-pms-kpi-brick-inflow">
            <div class="ifs-pms-kpi-meta-tag">
                <span><?php esc_html_e( 'Shift Gross Receipts', 'ozone-skypool' ); ?></span>
                <i class="fa-solid fa-arrow-trend-up" style="color: #10b981;"></i>
            </div>
            <div class="ifs-pms-kpi-val ifs-pms-mono" style="color: #10b981;">
                <?php echo esc_html( $currency . ' ' . number_format_i18n( $today_revenue, 2 ) ); ?>
            </div>
            <div class="ifs-pms-kpi-foot">
                <?php esc_html_e( 'Net Take-Home:', 'ozone-skypool' ); ?> 
                <strong style="color: <?php echo $today_net_margin >= 0 ? '#10b981' : '#ef4444'; ?>;" class="ifs-pms-mono">
                    <?php echo esc_html( $currency . ' ' . number_format_i18n( $today_net_margin, 2 ) ); ?>
                </strong>
            </div>
        </div>

        <div class="ifs-pms-kpi-brick ifs-pms-kpi-brick-admit">
            <div class="ifs-pms-kpi-meta-tag">
                <span><?php esc_html_e( 'Passes Issued Today', 'ozone-skypool' ); ?></span>
                <i class="fa-solid fa-ticket" style="color: #0284c7;"></i>
            </div>
            <div class="ifs-pms-kpi-val ifs-pms-mono" style="color: #0f172a;">
                <?php echo esc_html( number_format_i18n( $today_tickets_issued ) ); ?>
            </div>
            <div class="ifs-pms-kpi-foot">
                <?php esc_html_e( 'Tickets sold during current session', 'ozone-skypool' ); ?>
            </div>
        </div>

        <div class="ifs-pms-kpi-brick ifs-pms-kpi-brick-turnover">
            <div class="ifs-pms-kpi-meta-tag">
                <span><?php esc_html_e( 'Gate Turnstile Verified', 'ozone-skypool' ); ?></span>
                <i class="fa-solid fa-qrcode" style="color: #c084fc;"></i>
            </div>
            <div class="ifs-pms-kpi-val ifs-pms-mono" style="color: #c084fc;">
                <?php echo esc_html( number_format_i18n( $today_admitted ) ); ?>
            </div>
            <div class="ifs-pms-kpi-foot">
                <?php esc_html_e( 'Verification Conversion:', 'ozone-skypool' ); ?> 
                <strong style="color: #0f172a;" class="ifs-pms-mono"><?php echo esc_html( (string) $entry_turnover ); ?>%</strong>
            </div>
        </div>

        <div class="ifs-pms-kpi-brick ifs-pms-kpi-brick-outflow">
            <div class="ifs-pms-kpi-meta-tag">
                <span><?php esc_html_e( 'Operating Outflows', 'ozone-skypool' ); ?></span>
                <i class="fa-solid fa-arrow-trend-down" style="color: #ef4444;"></i>
            </div>
            <div class="ifs-pms-kpi-val ifs-pms-mono" style="color: #ef4444;">
                <?php echo esc_html( $currency . ' ' . number_format_i18n( $today_expenses, 2 ) ); ?>
            </div>
            <div class="ifs-pms-kpi-foot">
                <?php esc_html_e( 'Logged disbursements & supplies', 'ozone-skypool' ); ?>
            </div>
        </div>
    </div>

    <!-- Live Stream & Shift Ledger Split -->
    <div class="ifs-pms-activity-grid">
        <!-- Live Admissions Stream (Latest 5) -->
        <div class="ifs-pms-panel-card">
            <div class="ifs-pms-panel-top">
                <h3 class="ifs-pms-panel-header-title">
                    <i class="fa-solid fa-bolt" style="color: #0284c7;"></i>
                    <?php esc_html_e( 'Live Turnstile Activity Stream (Latest 5)', 'ozone-skypool' ); ?>
                </h3>
                <a href="<?php echo esc_url( $base_dash_url . '&view=history' ); ?>" style="color: #0284c7; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                    <?php esc_html_e( 'Full Pass Ledger', 'ozone-skypool' ); ?> &rarr;
                </a>
            </div>

            <?php if ( ! empty( $recent_admissions ) ) : ?>
                <?php foreach ( $recent_admissions as $act ) : 
                    $is_valid = ( $act->status === 'Valid' );
                    $badge_class = $is_valid 
                        ? 'ifs-pms-status-green' 
                        : ( $act->status === 'Used' 
                            ? 'ifs-pms-status-amber' 
                            : 'ifs-pms-status-red' );
                ?>
                    <div class="ifs-pms-feed-item">
                        <div>
                            <div class="ifs-pms-feed-guest">
                                <?php echo esc_html( ! empty( $act->customer_name ) ? $act->customer_name : __( 'Walk-in Guest', 'ozone-skypool' ) ); ?>
                            </div>
                            <div class="ifs-pms-feed-meta ifs-pms-mono">
                                <?php echo esc_html( $act->ticket_code ); ?> &bull; <?php echo esc_html( number_format_i18n( $act->amount, 2 ) . ' ' . $currency ); ?>
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <span class="ifs-pms-status-pill <?php echo esc_attr( $badge_class ); ?>" style="padding: 4px 10px; font-size: 11px;">
                                <?php echo esc_html( $act->status ); ?>
                            </span>
                            <div style="font-size: 11.5px; color: #64748b; margin-top: 5px;" class="ifs-pms-mono">
                                <?php echo esc_html( $act->status === 'Used' ? ( $act->scanned_at ?: $act->sold_at ) : $act->sold_at ); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div style="text-align: center; color: #64748b; padding: 56px 20px;">
                    <i class="fa-solid fa-ticket" style="font-size: 32px; margin-bottom: 12px; opacity: 0.3; display: block;"></i>
                    <?php esc_html_e( 'No tickets issued or scanned yet today.', 'ozone-skypool' ); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Shift Financial Performance Balance Card -->
        <div class="ifs-pms-panel-card">
            <div class="ifs-pms-panel-top">
                <h3 class="ifs-pms-panel-header-title">
                    <i class="fa-solid fa-scale-balanced" style="color: #475569;"></i>
                    <?php esc_html_e( 'Shift Balance Breakdown', 'ozone-skypool' ); ?>
                </h3>
                <span class="ifs-pms-status-pill ifs-pms-status-green" style="font-size: 11px; padding: 4px 10px;">
                    <?php esc_html_e( 'Audited', 'ozone-skypool' ); ?>
                </span>
            </div>

            <div class="ifs-pms-finance-row">
                <span style="display: flex; align-items: center; gap: 12px; color: #0f172a;">
                    <i class="fa-solid fa-ticket" style="color: #0284c7;"></i>
                    <?php esc_html_e( 'Day Admission Tickets', 'ozone-skypool' ); ?>
                </span>
                <strong class="ifs-pms-mono" style="color: #0f172a;">
                    <?php echo esc_html( $currency . ' ' . number_format_i18n( $today_tickets_rev, 2 ) ); ?>
                </strong>
            </div>

            <div class="ifs-pms-finance-row">
                <span style="display: flex; align-items: center; gap: 12px; color: #0f172a;">
                    <i class="fa-solid fa-id-card" style="color: #c084fc;"></i>
                    <?php esc_html_e( 'Pass Subscriptions', 'ozone-skypool' ); ?>
                </span>
                <strong class="ifs-pms-mono" style="color: #0f172a;">
                    <?php echo esc_html( $currency . ' ' . number_format_i18n( $today_members_rev, 2 ) ); ?>
                </strong>
            </div>

            <div class="ifs-pms-finance-row">
                <span style="display: flex; align-items: center; gap: 12px; color: #0f172a;">
                    <i class="fa-solid fa-receipt" style="color: #ef4444;"></i>
                    <?php esc_html_e( 'Operational Outflows', 'ozone-skypool' ); ?>
                </span>
                <strong class="ifs-pms-mono" style="color: #ef4444;">
                    -<?php echo esc_html( $currency . ' ' . number_format_i18n( $today_expenses, 2 ) ); ?>
                </strong>
            </div>

            <div class="ifs-pms-finance-row ifs-pms-finance-total">
                <span style="font-size: 15px; color: #0f172a;"><?php esc_html_e( 'Daily Cash Margin', 'ozone-skypool' ); ?></span>
                <strong class="ifs-pms-mono" style="font-size: 19px; color: <?php echo $today_net_margin >= 0 ? '#10b981' : '#ef4444'; ?>;">
                    <?php echo esc_html( $currency . ' ' . number_format_i18n( $today_net_margin, 2 ) ); ?>
                </strong>
            </div>
        </div>
    </div>
</div>