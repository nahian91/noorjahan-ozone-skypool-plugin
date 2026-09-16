<?php
/**
 * View: Financial Performance Reports & Profit-and-Loss Audit Statement (Executive Edition v2)
 *
 * @package Ozone_Skypool_OS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$t_tick    = $wpdb->prefix . 'ifs_pms_tickets';
$t_members = $wpdb->prefix . 'ifs_pms_memberships';
$t_expense = $wpdb->prefix . 'ifs_pms_expenses';
$currency  = esc_html( get_option( 'ifs_pms_currency', 'BDT' ) );
$b_name    = esc_html( get_option( 'ifs_pms_business_name', 'Ozone Restaurant & Skypool' ) );

// 1. Sanitize & Normalize Period Selection
$current_year_int  = (int) current_time( 'Y' );
$current_month_int = (int) current_time( 'm' );

$selected_month = isset( $_GET['report_month'] ) ? absint( $_GET['report_month'] ) : $current_month_int;
$selected_year  = isset( $_GET['report_year'] ) ? absint( $_GET['report_year'] ) : $current_year_int;

if ( $selected_month < 1 || $selected_month > 12 ) {
    $selected_month = $current_month_int;
}
if ( $selected_year < 2022 || $selected_year > 2035 ) {
    $selected_year = $current_year_int;
}

// 2. High-Performance Date Range Boundaries
$start_date_str  = sprintf( '%04d-%02d-01 00:00:00', $selected_year, $selected_month );
$last_day_int    = (int) gmdate( 't', strtotime( $start_date_str ) );
$end_date_str    = sprintf( '%04d-%02d-%02d 23:59:59', $selected_year, $selected_month, $last_day_int );
$date_only_end   = sprintf( '%04d-%02d-%02d', $selected_year, $selected_month, $last_day_int );
$date_only_start = sprintf( '%04d-%02d-01', $selected_year, $selected_month );

// 3. Telemetry Queries
$ticket_rev = (float) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM {$t_tick} 
         WHERE sold_at >= %s AND sold_at <= %s AND status != 'Cancelled'",
        $start_date_str,
        $end_date_str
    )
);

$member_rev = (float) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM {$t_members} 
         WHERE created_at >= %s AND created_at <= %s",
        $start_date_str,
        $end_date_str
    )
);

$total_inflow = $ticket_rev + $member_rev;

$total_outflow = (float) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM {$t_expense} 
         WHERE expense_date >= %s AND expense_date <= %s",
        $date_only_start,
        $date_only_end
    )
);

$net_profit = $total_inflow - $total_outflow;
$margin_pct = $total_inflow > 0 ? round( ( $net_profit / $total_inflow ) * 100, 1 ) : 0.0;

$cat_breakdown = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT category, SUM(amount) AS total 
         FROM {$t_expense} 
         WHERE expense_date >= %s AND expense_date <= %s 
         GROUP BY category 
         ORDER BY total DESC",
        $date_only_start,
        $date_only_end
    )
);

$month_timestamp = mktime( 0, 0, 0, $selected_month, 1, $selected_year );
$month_label     = date_i18n( 'F Y', $month_timestamp );
?>

<style>
    /* ==========================================================================
       PRO EXECUTIVE FINANCIAL REPORT SUITE (ULTRA-PREMIUM UI/UX V2)
       ========================================================================== */
    .oz-report-wrapper {
        display: flex;
        flex-direction: column;
        gap: 28px;
        width: 100%;
        box-sizing: border-box;
    }

    /* Executive Glassmorphic Toolbar */
    .oz-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 250, 252, 0.8) 100%);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 24px;
        padding: 24px 32px;
        box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.07);
        box-sizing: border-box;
        flex-wrap: wrap;
        gap: 20px;
    }

    .oz-header-tag {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 11.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--ifs-success, #10b981);
        background: rgba(16, 185, 129, 0.08);
        padding: 5px 14px;
        border-radius: 24px;
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .oz-header-title {
        margin: 8px 0 0 0;
        font-size: 22px;
        font-weight: 800;
        letter-spacing: -0.025em;
        color: var(--ifs-text-primary, #0f172a);
    }

    .oz-controls-group {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .oz-filter-pill {
        display: flex;
        align-items: center;
        background: #f8fafc;
        border: 1.5px solid #cbd5e1;
        border-radius: 14px;
        padding: 4px;
        gap: 4px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    }

    #wpcontent .oz-filter-pill select {
        background: transparent !important;
        border: none !important;
        font-size: 13.5px !important;
        font-weight: 700 !important;
        color: var(--ifs-text-primary, #0f172a) !important;
        padding: 8px 32px 8px 14px !important;
        height: 38px !important;
        cursor: pointer;
        box-shadow: none !important;
    }

    /* KPI Cards Grid */
    .oz-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .oz-kpi-card {
        background: var(--ifs-surface, #ffffff);
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 24px;
        padding: 26px;
        box-shadow: 0 15px 30px -10px rgba(15, 23, 42, 0.05);
        position: relative;
        overflow: hidden;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .oz-kpi-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 22px 40px -12px rgba(15, 23, 42, 0.09);
        border-color: #cbd5e1;
    }

    .oz-kpi-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
    }

    .oz-kpi-label {
        font-size: 11.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--ifs-text-tertiary, #64748b);
    }

    .oz-kpi-icon-bubble {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }

    .oz-kpi-value {
        font-size: 28px;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.1;
        font-variant-numeric: tabular-nums;
    }

    .oz-kpi-sub {
        font-size: 12.5px;
        color: var(--ifs-text-secondary, #475569);
        margin-top: 10px;
    }

    /* Statement Panels */
    .oz-statement-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }

    @media (max-width: 1120px) {
        .oz-statement-grid {
            grid-template-columns: 1fr;
        }
    }

    .oz-panel {
        background: var(--ifs-surface, #ffffff);
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 24px;
        box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.07);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .oz-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 24px 32px;
        border-bottom: 1px solid #f1f5f9;
        background: linear-gradient(to bottom, #fafbfd, #f8fafc);
    }

    .oz-panel-title {
        margin: 0;
        font-size: 16.5px;
        font-weight: 800;
        letter-spacing: -0.01em;
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--ifs-text-primary, #0f172a);
    }

    .oz-ledger-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 32px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
        transition: background 0.15s ease;
    }

    .oz-ledger-row:hover {
        background: #f8fafc;
    }

    .oz-ledger-row:last-child {
        border-bottom: none;
    }

    .oz-ledger-total {
        background: #f8fafc;
        font-weight: 800;
        border-top: 2px solid #cbd5e1;
        padding: 20px 32px;
    }

    .oz-bar-bg {
        width: 110px;
        height: 8px;
        background: #e2e8f0;
        border-radius: 4px;
        overflow: hidden;
    }

    .oz-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #f43f5e 0%, #e11d48 100%);
        border-radius: 4px;
        transition: width 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* Enhanced Button Styles Inside Toolbar */
    #wpcontent .oz-report-wrapper .ifs-pms-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
        font-weight: 700 !important;
        cursor: pointer !important;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
        box-sizing: border-box !important;
    }

    #wpcontent .oz-report-wrapper .ifs-pms-btn-primary {
        height: 42px !important;
        padding: 0 20px !important;
        border-radius: 12px !important;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
        color: #ffffff !important;
        border: 1px solid rgba(2, 132, 199, 0.8) !important;
        box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3) !important;
    }
    #wpcontent .oz-report-wrapper .ifs-pms-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(2, 132, 199, 0.45) !important;
    }

    #wpcontent .oz-report-wrapper .ifs-pms-btn-secondary {
        height: 42px !important;
        padding: 0 18px !important;
        border-radius: 12px !important;
        background: #ffffff !important;
        color: var(--ifs-text-secondary, #475569) !important;
        border: 1.5px solid #cbd5e1 !important;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03) !important;
    }
    #wpcontent .oz-report-wrapper .ifs-pms-btn-secondary:hover {
        background: #f8fafc !important;
        border-color: #94a3b8 !important;
        color: #0f172a !important;
        transform: translateY(-2px);
    }

    /* Print Formatting */
    @media print {
        .oz-toolbar form,
        .oz-controls-group button,
        .ifs-pms-aside {
            display: none !important;
        }
        .ifs-pms-canvas {
            padding: 0 !important;
            height: auto !important;
            overflow: visible !important;
            background: #ffffff !important;
        }
        .oz-panel, .oz-toolbar, .oz-kpi-card {
            box-shadow: none !important;
            border: 1px solid #cbd5e1 !important;
            background: #ffffff !important;
        }
    }
</style>

<div class="oz-report-wrapper">
    <!-- Top Executive Controls Toolbar -->
    <div class="oz-toolbar">
        <div>
            <div class="oz-header-tag">
                <span class="ifs-pms-pulse-dot"></span> <?php esc_html_e( 'Audited Financial Statement', 'ozone-skypool' ); ?>
            </div>
            <h2 class="oz-header-title">
                <?php echo esc_html( $b_name ); ?> &bull; <?php echo esc_html( $month_label ); ?>
            </h2>
        </div>

        <div class="oz-controls-group">
            <form method="GET" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display: flex; gap: 10px; align-items: center; margin: 0;">
                <input type="hidden" name="page" value="ifs-pms">
                <input type="hidden" name="view" value="reports">

                <div class="oz-filter-pill">
                    <select name="report_month">
                        <?php for ( $m = 1; $m <= 12; $m++ ) : ?>
                            <option value="<?php echo esc_attr( (string) $m ); ?>" <?php selected( $selected_month, $m ); ?>>
                                <?php echo esc_html( date_i18n( 'M', mktime( 0, 0, 0, $m, 1 ) ) ); ?>
                            </option>
                        <?php endfor; ?>
                    </select>

                    <select name="report_year" class="ifs-pms-mono">
                        <?php for ( $y = $current_year_int - 4; $y <= $current_year_int + 2; $y++ ) : ?>
                            <option value="<?php echo esc_attr( (string) $y ); ?>" <?php selected( $selected_year, $y ); ?>>
                                <?php echo esc_html( (string) $y ); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <button type="submit" class="ifs-pms-btn ifs-pms-btn-primary">
                    <i class="fa-solid fa-arrows-rotate"></i> <?php esc_html_e( 'Update', 'ozone-skypool' ); ?>
                </button>
            </form>

            <button type="button" class="ifs-pms-btn ifs-pms-btn-secondary" onclick="window.print()">
                <i class="fa-solid fa-print"></i> <?php esc_html_e( 'Print', 'ozone-skypool' ); ?>
            </button>
            <button type="button" class="ifs-pms-btn ifs-pms-btn-secondary" onclick="ifsPmsDownloadLedgerCsv()">
                <i class="fa-solid fa-file-csv"></i> <?php esc_html_e( 'Export CSV', 'ozone-skypool' ); ?>
            </button>
        </div>
    </div>

    <!-- Executive KPI Grid -->
    <div class="oz-kpi-grid">
        <!-- Inflow -->
        <div class="oz-kpi-card">
            <div class="oz-kpi-top">
                <span class="oz-kpi-label"><?php esc_html_e( 'Gross Operating Inflow', 'ozone-skypool' ); ?></span>
                <div class="oz-kpi-icon-bubble" style="background: rgba(16, 185, 129, 0.1); color: var(--ifs-success, #10b981);">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </div>
            </div>
            <div class="oz-kpi-value ifs-pms-mono" style="color: var(--ifs-success, #10b981);">
                <?php echo esc_html( $currency . ' ' . number_format_i18n( $total_inflow, 2 ) ); ?>
            </div>
            <div class="oz-kpi-sub">
                <?php printf( esc_html__( 'Passes: %s &bull; Members: %s', 'ozone-skypool' ), esc_html( number_format_i18n( $ticket_rev, 0 ) ), esc_html( number_format_i18n( $member_rev, 0 ) ) ); ?>
            </div>
        </div>

        <!-- Outflow -->
        <div class="oz-kpi-card">
            <div class="oz-kpi-top">
                <span class="oz-kpi-label"><?php esc_html_e( 'Operating Outflows', 'ozone-skypool' ); ?></span>
                <div class="oz-kpi-icon-bubble" style="background: rgba(239, 68, 68, 0.1); color: var(--ifs-danger, #ef4444);">
                    <i class="fa-solid fa-arrow-trend-down"></i>
                </div>
            </div>
            <div class="oz-kpi-value ifs-pms-mono" style="color: var(--ifs-danger, #ef4444);">
                <?php echo esc_html( $currency . ' ' . number_format_i18n( $total_outflow, 2 ) ); ?>
            </div>
            <div class="oz-kpi-sub">
                <?php esc_html_e( 'F&B, chemical maintenance & overheads', 'ozone-skypool' ); ?>
            </div>
        </div>

        <!-- Net Take-Home -->
        <div class="oz-kpi-card">
            <div class="oz-kpi-top">
                <span class="oz-kpi-label"><?php esc_html_e( 'Net Operating Margin', 'ozone-skypool' ); ?></span>
                <div class="oz-kpi-icon-bubble" style="background: rgba(2, 132, 199, 0.1); color: var(--ifs-primary, #0284c7);">
                    <i class="fa-solid fa-scale-balanced"></i>
                </div>
            </div>
            <div class="oz-kpi-value ifs-pms-mono" style="color: <?php echo ( $net_profit >= 0 ) ? 'var(--ifs-text-primary, #0f172a)' : 'var(--ifs-danger, #ef4444)'; ?>;">
                <?php echo esc_html( $currency . ' ' . number_format_i18n( $net_profit, 2 ) ); ?>
            </div>
            <div class="oz-kpi-sub">
                <strong style="color: <?php echo $net_profit >= 0 ? 'var(--ifs-success, #10b981)' : 'var(--ifs-danger, #ef4444)'; ?>;">
                    <?php echo esc_html( $net_profit >= 0 ? __( 'Net Surplus Available', 'ozone-skypool' ) : __( 'Operating Deficit Recorded', 'ozone-skypool' ) ); ?>
                </strong>
            </div>
        </div>

        <!-- Margin Yield -->
        <div class="oz-kpi-card">
            <div class="oz-kpi-top">
                <span class="oz-kpi-label"><?php esc_html_e( 'Profit Margin Yield', 'ozone-skypool' ); ?></span>
                <div class="oz-kpi-icon-bubble" style="background: rgba(192, 132, 252, 0.1); color: #c084fc;">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
            </div>
            <div class="oz-kpi-value ifs-pms-mono" style="color: #c084fc;">
                <?php echo esc_html( (string) $margin_pct ); ?>%
            </div>
            <div class="oz-kpi-sub">
                <?php esc_html_e( 'Operational cash conversion rate', 'ozone-skypool' ); ?>
            </div>
        </div>
    </div>

    <!-- Detailed Ledger Statements -->
    <div class="oz-statement-grid">
        <!-- Inflows Statement -->
        <div class="oz-panel">
            <div class="oz-panel-head">
                <h3 class="oz-panel-title">
                    <i class="fa-solid fa-circle-arrow-down" style="color: var(--ifs-success, #10b981);"></i>
                    <?php esc_html_e( 'Revenues & Collections Statement', 'ozone-skypool' ); ?>
                </h3>
                <span class="ifs-pms-badge ifs-pms-badge-success"><?php esc_html_e( 'Credit (+)', 'ozone-skypool' ); ?></span>
            </div>

            <div class="oz-ledger-row">
                <span style="display: flex; align-items: center; gap: 12px; color: var(--ifs-text-primary, #0f172a); font-weight: 600;">
                    <i class="fa-solid fa-ticket" style="color: var(--ifs-accent, #0284c7);"></i>
                    <?php esc_html_e( 'Day Passes & Rooftop Combos', 'ozone-skypool' ); ?>
                </span>
                <strong class="ifs-pms-mono" style="color: var(--ifs-text-primary, #0f172a); font-size: 14.5px;">
                    <?php echo esc_html( $currency . ' ' . number_format_i18n( $ticket_rev, 2 ) ); ?>
                </strong>
            </div>

            <div class="oz-ledger-row">
                <span style="display: flex; align-items: center; gap: 12px; color: var(--ifs-text-primary, #0f172a); font-weight: 600;">
                    <i class="fa-solid fa-id-card" style="color: #c084fc;"></i>
                    <?php esc_html_e( 'Monthly & Season Memberships', 'ozone-skypool' ); ?>
                </span>
                <strong class="ifs-pms-mono" style="color: var(--ifs-text-primary, #0f172a); font-size: 14.5px;">
                    <?php echo esc_html( $currency . ' ' . number_format_i18n( $member_rev, 2 ) ); ?>
                </strong>
            </div>

            <div class="oz-ledger-row oz-ledger-total">
                <span style="font-size: 15px; color: var(--ifs-text-primary, #0f172a);"><?php esc_html_e( 'Gross Operating Receipts', 'ozone-skypool' ); ?></span>
                <strong class="ifs-pms-mono" style="color: var(--ifs-success, #10b981); font-size: 18px;">
                    <?php echo esc_html( $currency . ' ' . number_format_i18n( $total_inflow, 2 ) ); ?>
                </strong>
            </div>
        </div>

        <!-- Outflows Statement -->
        <div class="oz-panel">
            <div class="oz-panel-head">
                <h3 class="oz-panel-title">
                    <i class="fa-solid fa-circle-arrow-up" style="color: var(--ifs-danger, #ef4444);"></i>
                    <?php esc_html_e( 'Operating Disbursements Statement', 'ozone-skypool' ); ?>
                </h3>
                <span class="ifs-pms-badge ifs-pms-badge-danger"><?php esc_html_e( 'Debit (-)', 'ozone-skypool' ); ?></span>
            </div>

            <?php if ( ! empty( $cat_breakdown ) ) : ?>
                <?php foreach ( $cat_breakdown as $c ) :
                    $cat_pct = $total_outflow > 0 ? (int) round( ( (float) $c->total / $total_outflow ) * 100 ) : 0;
                ?>
                    <div class="oz-ledger-row">
                        <span style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                            <span style="font-weight: 600; color: var(--ifs-text-primary, #0f172a);"><?php echo esc_html( $c->category ); ?></span>
                            <span style="display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 12px; font-weight: 700; color: var(--ifs-text-tertiary);" class="ifs-pms-mono"><?php echo esc_html( (string) $cat_pct ); ?>%</span>
                                <div class="oz-bar-bg">
                                    <div class="oz-bar-fill" style="width: <?php echo esc_attr( (string) $cat_pct ); ?>%;"></div>
                                </div>
                            </span>
                        </span>
                        <strong class="ifs-pms-mono" style="color: var(--ifs-danger, #ef4444); margin-left: 20px; white-space: nowrap; font-size: 14.5px;">
                            -<?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $c->total, 2 ) ); ?>
                        </strong>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="oz-ledger-row" style="color: var(--ifs-text-tertiary, #64748b); justify-content: center; padding: 42px 20px;">
                    <?php esc_html_e( 'No facility expenditures recorded for this period.', 'ozone-skypool' ); ?>
                </div>
            <?php endif; ?>

            <div class="oz-ledger-row oz-ledger-total">
                <span style="font-size: 15px; color: var(--ifs-text-primary, #0f172a);"><?php esc_html_e( 'Total Outflow Deductions', 'ozone-skypool' ); ?></span>
                <strong class="ifs-pms-mono" style="color: var(--ifs-danger, #ef4444); font-size: 18px;">
                    -<?php echo esc_html( $currency . ' ' . number_format_i18n( $total_outflow, 2 ) ); ?>
                </strong>
            </div>
        </div>
    </div>
</div>

<script>
function ifsPmsDownloadLedgerCsv() {
    if (typeof ifs_pms_export_ledger === 'function') {
        ifs_pms_export_ledger();
    } else if (typeof ifsPmsConfig !== 'undefined') {
        window.location.href = ifsPmsConfig.ajax_url + '?action=ifs_pms_export_csv_action&security=' + ifsPmsConfig.nonce;
    }
}
</script>