<?php
/**
 * View: Operations Dashboard & Executive Command Center (Pro Edition)
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

$currency      = esc_html( get_option( 'ifs_pms_currency', 'BDT' ) );
$today_ymd     = current_time( 'Y-m-d' );
$base_dash_url = admin_url( 'admin.php?page=ifs-pms' );

// 2. Financial Telemetry (Aligned to Local WordPress Timezone)
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
    $status_badge = 'background: rgba(239, 68, 68, 0.12); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.25);';
    $bar_gradient = 'linear-gradient(90deg, #f59e0b 0%, #ef4444 100%)';
} elseif ( $capacity_pct >= 55 ) {
    $status_label = __( 'Moderate Traffic', 'ozone-skypool' );
    $status_badge = 'background: rgba(245, 158, 11, 0.12); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.25);';
    $bar_gradient = 'linear-gradient(90deg, #0284c7 0%, #f59e0b 100%)';
} else {
    $status_label = __( 'Optimal Space', 'ozone-skypool' );
    $status_badge = 'background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.25);';
    $bar_gradient = 'linear-gradient(90deg, #0284c7 0%, #10b981 100%)';
}

// 4. Live Admissions Stream (Latest 6 Records)
$recent_admissions = $wpdb->get_results(
    "SELECT t.ticket_code, t.amount, t.status, t.sold_at, t.scanned_at, c.name AS customer_name 
     FROM {$t_tick} t 
     LEFT JOIN {$t_cust} c ON t.customer_id = c.id 
     ORDER BY t.id DESC 
     LIMIT 6"
);
?>

<style>
    /* ==========================================================================
       PREMIUM COMMAND CENTER DASHBOARD STYLES (V2)
       ========================================================================== */
    .oz-dash-stack {
        display: flex;
        flex-direction: column;
        gap: 24px;
        width: 100%;
        box-sizing: border-box;
    }

    /* Hero Header Clock & Gate Telemetry Bar */
    .oz-telemetry-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--ifs-surface, #ffffff);
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 24px;
        padding: 24px 32px;
        box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.07);
        box-sizing: border-box;
        flex-wrap: wrap;
        gap: 20px;
    }

    .oz-clock-wrap {
        display: flex;
        align-items: center;
        gap: 18px;
    }

    .oz-clock-icon {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        background: linear-gradient(135deg, rgba(2, 132, 199, 0.12) 0%, rgba(2, 132, 199, 0.04) 100%);
        border: 1px solid rgba(2, 132, 199, 0.2);
        color: var(--ifs-accent, #0284c7);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
    }

    .oz-clock-digits {
        font-family: var(--ifs-font-mono, monospace);
        font-size: 28px;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.1;
        color: var(--ifs-text-primary, #0f172a);
        font-variant-numeric: tabular-nums;
    }

    .oz-clock-date {
        font-size: 13px;
        color: var(--ifs-text-tertiary, #64748b);
        margin-top: 4px;
    }

    .oz-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        border-radius: 24px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    /* Live Occupancy Gauge Card */
    .oz-gauge-card {
        background: var(--ifs-surface, #ffffff);
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 24px;
        padding: 24px 32px;
        box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.07);
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 32px;
        align-items: center;
        box-sizing: border-box;
    }

    @media (max-width: 900px) {
        .oz-gauge-card {
            grid-template-columns: 1fr;
            gap: 20px;
        }
    }

    .oz-gauge-icon-box {
        width: 58px;
        height: 58px;
        border-radius: 16px;
        background: linear-gradient(135deg, rgba(2, 132, 199, 0.14) 0%, rgba(56, 189, 248, 0.05) 100%);
        border: 1px solid rgba(2, 132, 199, 0.25);
        color: var(--ifs-accent, #0284c7);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
    }

    .oz-gauge-progress-track {
        height: 12px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        margin-top: 10px;
        position: relative;
    }

    .oz-gauge-progress-bar {
        height: 100%;
        border-radius: 8px;
        transition: width 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* Fast Operation Tiles */
    .oz-action-deck {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 18px;
    }

    .oz-action-card {
        background: var(--ifs-surface, #ffffff);
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 20px;
        padding: 20px 24px;
        text-decoration: none !important;
        display: flex;
        align-items: center;
        gap: 18px;
        box-shadow: 0 10px 25px -10px rgba(15, 23, 42, 0.04);
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .oz-action-card:hover {
        transform: translateY(-3px);
        border-color: var(--ifs-border-focus, #0284c7);
        box-shadow: 0 16px 32px -8px rgba(2, 132, 199, 0.12);
    }

    .oz-action-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .oz-action-title {
        font-size: 14.5px;
        font-weight: 800;
        color: var(--ifs-text-primary, #0f172a);
        margin: 0;
    }

    .oz-action-sub {
        font-size: 12px;
        color: var(--ifs-text-tertiary, #64748b);
        margin-top: 4px;
    }

    /* KPI Metrics Matrix */
    .oz-kpi-quad {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
    }

    .oz-kpi-brick {
        background: var(--ifs-surface, #ffffff);
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 15px 30px -10px rgba(15, 23, 42, 0.05);
        position: relative;
        overflow: hidden;
    }

    .oz-kpi-brick::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: transparent;
    }

    .oz-kpi-brick.inflow::before { background: var(--ifs-success, #10b981); }
    .oz-kpi-brick.admit::before  { background: var(--ifs-accent, #0284c7); }
    .oz-kpi-brick.turnover::before { background: #c084fc; }
    .oz-kpi-brick.outflow::before { background: var(--ifs-danger, #ef4444); }

    .oz-kpi-meta-tag {
        font-size: 11.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--ifs-text-tertiary, #64748b);
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .oz-kpi-val {
        font-family: var(--ifs-font-mono, monospace);
        font-size: 28px;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.15;
    }

    .oz-kpi-foot {
        font-size: 12.5px;
        color: var(--ifs-text-secondary, #475569);
        margin-top: 10px;
    }

    /* Activity Stream & Financial Split */
    .oz-activity-grid {
        display: grid;
        grid-template-columns: 1.55fr 1fr;
        gap: 24px;
        align-items: start;
    }

    @media (max-width: 1080px) {
        .oz-activity-grid {
            grid-template-columns: 1fr;
        }
    }

    .oz-panel-card {
        background: var(--ifs-surface, #ffffff);
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 24px;
        box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.07);
        box-sizing: border-box;
        overflow: hidden;
    }

    .oz-panel-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 28px;
        border-bottom: 1px solid #f1f5f9;
        background: linear-gradient(to bottom, #fafbfd, #f8fafc);
    }

    .oz-panel-header-title {
        margin: 0;
        font-size: 16px;
        font-weight: 800;
        color: var(--ifs-text-primary, #0f172a);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .oz-feed-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 28px;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s ease;
    }

    .oz-feed-item:hover {
        background: #f8fafc;
    }

    .oz-feed-item:last-child {
        border-bottom: none;
    }

    .oz-feed-guest {
        font-size: 14px;
        font-weight: 700;
        color: var(--ifs-text-primary, #0f172a);
    }

    .oz-feed-meta {
        font-size: 12px;
        color: var(--ifs-accent, #0284c7);
        margin-top: 3px;
    }

    .oz-finance-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 28px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
        color: var(--ifs-text-secondary, #475569);
    }

    .oz-finance-row:last-child {
        border-bottom: none;
    }

    .oz-finance-total {
        background: #f8fafc;
        font-weight: 800;
        border-top: 2px solid #cbd5e1;
        padding: 20px 28px;
    }
</style>

<div class="oz-dash-stack">
    <!-- Live Telemetry & Station Header -->
    <div class="oz-telemetry-header">
        <div class="oz-clock-wrap">
            <div class="oz-clock-icon">
                <i class="fa-regular fa-clock"></i>
            </div>
            <div>
                <div class="oz-clock-digits" id="ozLiveClock">--:--:-- --</div>
                <div class="oz-clock-date" id="ozLiveDate"><?php echo esc_html( current_time( 'l, F j, Y' ) ); ?></div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 24px;">
            <div style="text-align: right;">
                <div style="font-size: 14px; font-weight: 800; color: var(--ifs-text-primary, #0f172a);"><?php echo esc_html( wp_get_current_user()->display_name ); ?></div>
                <div style="font-size: 12px; color: var(--ifs-text-tertiary, #64748b);"><?php esc_html_e( 'Terminal Operator • Desk Active', 'ozone-skypool' ); ?></div>
            </div>
            <div class="oz-status-pill" style="background: rgba(16, 185, 129, 0.1); color: var(--ifs-success, #10b981); border: 1px solid rgba(16, 185, 129, 0.25);">
                <span class="ifs-pms-pulse-dot"></span> <?php esc_html_e( 'Turnstiles Synchronized', 'ozone-skypool' ); ?>
            </div>
        </div>
    </div>

    <!-- Live Rooftop Capacity Gauge -->
    <div class="oz-gauge-card">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div class="oz-gauge-icon-box">
                <i class="fa-solid fa-person-swimming"></i>
            </div>
            <div>
                <div style="font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--ifs-text-tertiary, #64748b);">
                    <?php esc_html_e( 'Rooftop Skypool Occupancy', 'ozone-skypool' ); ?>
                </div>
                <div style="font-family: var(--ifs-font-mono); font-size: 30px; font-weight: 800; color: var(--ifs-text-primary, #0f172a); line-height: 1.15;">
                    <?php echo esc_html( (string) $current_swimmers ); ?>
                    <span style="font-size: 14.5px; font-weight: 600; color: var(--ifs-text-tertiary, #64748b);">/ <?php echo esc_html( (string) $max_pool_capacity ); ?> <?php esc_html_e( 'max swimmers', 'ozone-skypool' ); ?></span>
                </div>
            </div>
        </div>

        <div style="width: 100%;">
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12.5px; font-weight: 700;">
                <span style="color: var(--ifs-text-secondary, #475569);"><?php esc_html_e( 'Water & Deck Utilization', 'ozone-skypool' ); ?></span>
                <span class="ifs-pms-mono" style="color: var(--ifs-text-primary, #0f172a); font-weight: 800;">
                    <?php echo esc_html( (string) $capacity_pct ); ?>%
                </span>
            </div>
            <div class="oz-gauge-progress-track">
                <div class="oz-gauge-progress-bar" style="width: <?php echo esc_attr( (string) $capacity_pct ); ?>%; background: <?php echo esc_attr( $bar_gradient ); ?>;"></div>
            </div>
        </div>

        <div>
            <span class="oz-status-pill" style="<?php echo esc_attr( $status_badge ); ?>">
                <?php echo esc_html( $status_label ); ?>
            </span>
        </div>
    </div>

    <!-- Quick Operational Action Tiles -->
    <div class="oz-action-deck">
        <a href="<?php echo esc_url( $base_dash_url . '&view=tickets' ); ?>" class="oz-action-card">
            <div class="oz-action-icon" style="background: rgba(2, 132, 199, 0.12); color: var(--ifs-accent, #0284c7);">
                <i class="fa-solid fa-ticket"></i>
            </div>
            <div>
                <div class="oz-action-title"><?php esc_html_e( 'Sell Ticket Pass', 'ozone-skypool' ); ?></div>
                <div class="oz-action-sub"><?php esc_html_e( 'Single, Combo & VIP passes', 'ozone-skypool' ); ?></div>
            </div>
        </a>

        <a href="<?php echo esc_url( $base_dash_url . '&view=scanner' ); ?>" class="oz-action-card">
            <div class="oz-action-icon" style="background: rgba(16, 185, 129, 0.12); color: var(--ifs-success, #10b981);">
                <i class="fa-solid fa-qrcode"></i>
            </div>
            <div>
                <div class="oz-action-title"><?php esc_html_e( 'Gate Turnstile Scan', 'ozone-skypool' ); ?></div>
                <div class="oz-action-sub"><?php esc_html_e( 'Check-in admission QR codes', 'ozone-skypool' ); ?></div>
            </div>
        </a>

        <a href="<?php echo esc_url( $base_dash_url . '&view=membership' ); ?>" class="oz-action-card">
            <div class="oz-action-icon" style="background: rgba(192, 132, 252, 0.12); color: #c084fc;">
                <i class="fa-solid fa-id-card"></i>
            </div>
            <div>
                <div class="oz-action-title"><?php esc_html_e( 'Enroll Members', 'ozone-skypool' ); ?></div>
                <div class="oz-action-sub"><?php esc_html_e( 'Monthly & Season Passes', 'ozone-skypool' ); ?></div>
            </div>
        </a>

        <a href="<?php echo esc_url( $base_dash_url . '&view=expenses' ); ?>" class="oz-action-card">
            <div class="oz-action-icon" style="background: rgba(239, 68, 68, 0.12); color: var(--ifs-danger, #ef4444);">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <div>
                <div class="oz-action-title"><?php esc_html_e( 'Record Outflow', 'ozone-skypool' ); ?></div>
                <div class="oz-action-sub"><?php esc_html_e( 'F&B, Fuel, Chemicals & Bills', 'ozone-skypool' ); ?></div>
            </div>
        </a>
    </div>

    <!-- Executive KPI Summary Matrix -->
    <div class="oz-kpi-quad">
        <div class="oz-kpi-brick inflow">
            <div class="oz-kpi-meta-tag">
                <span><?php esc_html_e( 'Shift Gross Receipts', 'ozone-skypool' ); ?></span>
                <i class="fa-solid fa-arrow-trend-up" style="color: var(--ifs-success, #10b981);"></i>
            </div>
            <div class="oz-kpi-val" style="color: var(--ifs-success, #10b981);">
                <?php echo esc_html( $currency . ' ' . number_format_i18n( $today_revenue, 2 ) ); ?>
            </div>
            <div class="oz-kpi-foot">
                <?php esc_html_e( 'Net Take-Home:', 'ozone-skypool' ); ?> 
                <strong style="color: <?php echo $today_net_margin >= 0 ? 'var(--ifs-success, #10b981)' : 'var(--ifs-danger, #ef4444)'; ?>;" class="ifs-pms-mono">
                    <?php echo esc_html( $currency . ' ' . number_format_i18n( $today_net_margin, 2 ) ); ?>
                </strong>
            </div>
        </div>

        <div class="oz-kpi-brick admit">
            <div class="oz-kpi-meta-tag">
                <span><?php esc_html_e( 'Passes Issued Today', 'ozone-skypool' ); ?></span>
                <i class="fa-solid fa-ticket" style="color: var(--ifs-accent, #0284c7);"></i>
            </div>
            <div class="oz-kpi-val" style="color: var(--ifs-text-primary, #0f172a);">
                <?php echo esc_html( number_format_i18n( $today_tickets_issued ) ); ?>
            </div>
            <div class="oz-kpi-foot">
                <?php esc_html_e( 'Tickets sold during current session', 'ozone-skypool' ); ?>
            </div>
        </div>

        <div class="oz-kpi-brick turnover">
            <div class="oz-kpi-meta-tag">
                <span><?php esc_html_e( 'Gate Turnstile Verified', 'ozone-skypool' ); ?></span>
                <i class="fa-solid fa-qrcode" style="color: #c084fc;"></i>
            </div>
            <div class="oz-kpi-val" style="color: #c084fc;">
                <?php echo esc_html( number_format_i18n( $today_admitted ) ); ?>
            </div>
            <div class="oz-kpi-foot">
                <?php esc_html_e( 'Verification Conversion:', 'ozone-skypool' ); ?> 
                <strong style="color: var(--ifs-text-primary, #0f172a);" class="ifs-pms-mono"><?php echo esc_html( (string) $entry_turnover ); ?>%</strong>
            </div>
        </div>

        <div class="oz-kpi-brick outflow">
            <div class="oz-kpi-meta-tag">
                <span><?php esc_html_e( 'Operating Outflows', 'ozone-skypool' ); ?></span>
                <i class="fa-solid fa-arrow-trend-down" style="color: var(--ifs-danger, #ef4444);"></i>
            </div>
            <div class="oz-kpi-val" style="color: var(--ifs-danger, #ef4444);">
                <?php echo esc_html( $currency . ' ' . number_format_i18n( $today_expenses, 2 ) ); ?>
            </div>
            <div class="oz-kpi-foot">
                <?php esc_html_e( 'Logged disbursements & supplies', 'ozone-skypool' ); ?>
            </div>
        </div>
    </div>

    <!-- Live Stream & Shift Ledger Split -->
    <div class="oz-activity-grid">
        <!-- Live Admissions Stream -->
        <div class="oz-panel-card">
            <div class="oz-panel-top">
                <h3 class="oz-panel-header-title">
                    <i class="fa-solid fa-bolt" style="color: var(--ifs-accent, #0284c7);"></i>
                    <?php esc_html_e( 'Live Turnstile Activity Stream', 'ozone-skypool' ); ?>
                </h3>
                <a href="<?php echo esc_url( $base_dash_url . '&view=history' ); ?>" style="color: var(--ifs-accent, #0284c7); font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                    <?php esc_html_e( 'Full Pass Ledger', 'ozone-skypool' ); ?> &rarr;
                </a>
            </div>

            <?php if ( ! empty( $recent_admissions ) ) : ?>
                <?php foreach ( $recent_admissions as $act ) : 
                    $is_valid = ( $act->status === 'Valid' );
                    $badge_style = $is_valid 
                        ? 'background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.25);' 
                        : ( $act->status === 'Used' 
                            ? 'background: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.25);' 
                            : 'background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.25);' );
                ?>
                    <div class="oz-feed-item">
                        <div>
                            <div class="oz-feed-guest">
                                <?php echo esc_html( ! empty( $act->customer_name ) ? $act->customer_name : __( 'Walk-in Guest', 'ozone-skypool' ) ); ?>
                            </div>
                            <div class="oz-feed-meta ifs-pms-mono">
                                <?php echo esc_html( $act->ticket_code ); ?> &bull; <?php echo esc_html( number_format_i18n( $act->amount, 2 ) . ' ' . $currency ); ?>
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <span class="oz-status-pill" style="<?php echo esc_attr( $badge_style ); ?> padding: 4px 10px; font-size: 11px;">
                                <?php echo esc_html( $act->status ); ?>
                            </span>
                            <div style="font-size: 11.5px; color: var(--ifs-text-tertiary, #64748b); margin-top: 5px;" class="ifs-pms-mono">
                                <?php echo esc_html( $act->status === 'Used' ? ( $act->scanned_at ?: $act->sold_at ) : $act->sold_at ); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div style="text-align: center; color: var(--ifs-text-tertiary, #64748b); padding: 56px 20px;">
                    <i class="fa-solid fa-ticket" style="font-size: 32px; margin-bottom: 12px; opacity: 0.3; display: block;"></i>
                    <?php esc_html_e( 'No tickets issued or scanned yet today.', 'ozone-skypool' ); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Shift Financial Performance Balance Card -->
        <div class="oz-panel-card">
            <div class="oz-panel-top">
                <h3 class="oz-panel-header-title">
                    <i class="fa-solid fa-scale-balanced" style="color: var(--ifs-text-secondary, #475569);"></i>
                    <?php esc_html_e( 'Shift Balance Breakdown', 'ozone-skypool' ); ?>
                </h3>
                <span class="oz-status-pill" style="background: rgba(16, 185, 129, 0.1); color: var(--ifs-success, #10b981); border: 1px solid rgba(16, 185, 129, 0.2); font-size: 11px; padding: 4px 10px;">
                    <?php esc_html_e( 'Audited', 'ozone-skypool' ); ?>
                </span>
            </div>

            <div class="oz-finance-row">
                <span style="display: flex; align-items: center; gap: 12px; color: var(--ifs-text-primary, #0f172a);">
                    <i class="fa-solid fa-ticket" style="color: var(--ifs-accent, #0284c7);"></i>
                    <?php esc_html_e( 'Day Admission Tickets', 'ozone-skypool' ); ?>
                </span>
                <strong class="ifs-pms-mono" style="color: var(--ifs-text-primary, #0f172a);">
                    <?php echo esc_html( $currency . ' ' . number_format_i18n( $today_tickets_rev, 2 ) ); ?>
                </strong>
            </div>

            <div class="oz-finance-row">
                <span style="display: flex; align-items: center; gap: 12px; color: var(--ifs-text-primary, #0f172a);">
                    <i class="fa-solid fa-id-card" style="color: #c084fc;"></i>
                    <?php esc_html_e( 'Pass Subscriptions', 'ozone-skypool' ); ?>
                </span>
                <strong class="ifs-pms-mono" style="color: var(--ifs-text-primary, #0f172a);">
                    <?php echo esc_html( $currency . ' ' . number_format_i18n( $today_members_rev, 2 ) ); ?>
                </strong>
            </div>

            <div class="oz-finance-row">
                <span style="display: flex; align-items: center; gap: 12px; color: var(--ifs-text-primary, #0f172a);">
                    <i class="fa-solid fa-receipt" style="color: var(--ifs-danger, #ef4444);"></i>
                    <?php esc_html_e( 'Operational Outflows', 'ozone-skypool' ); ?>
                </span>
                <strong class="ifs-pms-mono" style="color: var(--ifs-danger, #ef4444);">
                    -<?php echo esc_html( $currency . ' ' . number_format_i18n( $today_expenses, 2 ) ); ?>
                </strong>
            </div>

            <div class="oz-finance-row oz-finance-total">
                <span style="font-size: 15px; color: var(--ifs-text-primary, #0f172a);"><?php esc_html_e( 'Daily Cash Margin', 'ozone-skypool' ); ?></span>
                <strong class="ifs-pms-mono" style="font-size: 19px; color: <?php echo $today_net_margin >= 0 ? 'var(--ifs-success, #10b981)' : 'var(--ifs-danger, #ef4444)'; ?>;">
                    <?php echo esc_html( $currency . ' ' . number_format_i18n( $today_net_margin, 2 ) ); ?>
                </strong>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    function ozUpdateClock() {
        const now = new Date();
        let hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        const hoursStr = String(hours).padStart(2, '0');
        
        const timeStr = hoursStr + ':' + minutes + ':' + seconds + ' ' + ampm;
        const clockEl = document.getElementById('ozLiveClock');
        if (clockEl) {
            clockEl.textContent = timeStr;
        }

        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const dateStr = now.toLocaleDateString(undefined, options);
        const dateEl = document.getElementById('ozLiveDate');
        if (dateEl) {
            dateEl.textContent = dateStr;
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            ozUpdateClock();
            setInterval(ozUpdateClock, 1000);
        });
    } else {
        ozUpdateClock();
        setInterval(ozUpdateClock, 1000);
    }
})();
</script>