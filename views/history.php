<?php
/**
 * View: Staff Sales Auditing & Cashier Shift Ledger (Executive Edition)
 *
 * @package Ozone_Skypool_OS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$t_tick   = $wpdb->prefix . 'ifs_pms_tickets';
$currency = esc_html( get_option( 'ifs_pms_currency', 'BDT' ) );

// 1. Validate and Sanitize Date Input (Y-m-d format)
$raw_date = isset( $_GET['shift_date'] ) ? sanitize_text_field( wp_unslash( $_GET['shift_date'] ) ) : '';
if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw_date ) ) {
    $shift_date = current_time( 'Y-m-d' );
} else {
    $shift_date = $raw_date;
}

// 2. Fetch Sales Grouped by Cashier
$staff_sales = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT sold_by, COUNT(*) AS count, SUM(amount) AS total 
         FROM {$t_tick} 
         WHERE DATE(sold_at) = %s AND status != 'Cancelled' 
         GROUP BY sold_by 
         ORDER BY total DESC",
        $shift_date
    )
);

// 3. Optimized Scan Aggregation
$staff_scans_raw = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT scanned_by, COUNT(*) AS scan_count 
         FROM {$t_tick} 
         WHERE DATE(scanned_at) = %s AND status = 'Used' AND scanned_by IS NOT NULL 
         GROUP BY scanned_by",
        $shift_date
    ),
    OBJECT_K
);

// 4. Calculate Shift Totals
$shift_total_rev    = 0.0;
$shift_total_tkts   = 0;
$top_cashier_name   = '—';
$top_cashier_intake = 0.0;

if ( ! empty( $staff_sales ) ) {
    $top_cashier_name   = $staff_sales[0]->sold_by;
    $top_cashier_intake = (float) $staff_sales[0]->total;

    foreach ( $staff_sales as $st ) {
        $shift_total_rev  += (float) $st->total;
        $shift_total_tkts += (int) $st->count;
    }
}

$shift_total_scans = (int) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$t_tick} 
         WHERE DATE(scanned_at) = %s AND status = 'Used'",
        $shift_date
    )
);

$date_label = date_i18n( 'l, F j, Y', strtotime( $shift_date ) );
?>

<style>
    /* ==========================================================================
       EXECUTIVE STAFF AUDIT & CASHIER SHIFT LEDGER
       ========================================================================== */
    .oz-staff-wrapper {
        display: flex;
        flex-direction: column;
        gap: 24px;
        width: 100%;
        box-sizing: border-box;
    }

    /* KPI Cards Grid */
    .oz-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
    }

    .oz-kpi-card {
        background: var(--ifs-surface, #ffffff);
        border: 1px solid var(--ifs-border-subtle, #e2e8f0);
        border-radius: 16px;
        padding: 22px 24px;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.02);
        position: relative;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .oz-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    .oz-kpi-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .oz-kpi-label {
        font-size: 11.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--ifs-text-tertiary, #64748b);
    }

    .oz-kpi-icon-bubble {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
    }

    .oz-kpi-val {
        font-size: 26px;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.1;
    }

    .oz-kpi-sub {
        font-size: 12px;
        color: var(--ifs-text-secondary, #475569);
        margin-top: 8px;
    }

    /* Main Table Panel */
    .oz-panel-card {
        background: var(--ifs-surface, #ffffff);
        border: 1px solid var(--ifs-border-subtle, #e2e8f0);
        border-radius: 18px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.03);
        box-sizing: border-box;
        overflow: hidden;
    }

    .oz-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 28px;
        border-bottom: 1px solid var(--ifs-border-subtle, #e2e8f0);
        background: var(--ifs-surface-hover, #fafbfd);
        flex-wrap: wrap;
        gap: 16px;
    }

    .oz-panel-title {
        margin: 0;
        font-size: 16px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--ifs-text-primary, #0f172a);
    }

    .oz-staff-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 28px;
        border-bottom: 1px solid var(--ifs-border-subtle, #e2e8f0);
        gap: 16px;
        flex-wrap: wrap;
        background: var(--ifs-surface, #ffffff);
    }

    .oz-search-container {
        position: relative;
        flex: 1;
        max-width: 320px;
        min-width: 220px;
    }

    .oz-search-container i.search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--ifs-text-tertiary, #94a3b8);
        font-size: 13px;
        pointer-events: none;
    }

    #wpcontent .oz-staff-toolbar input[type="text"] {
        padding-left: 38px !important;
        height: 42px !important;
        border-radius: 10px !important;
        border: 1.5px solid var(--ifs-border-strong, #cbd5e1) !important;
    }

    #wpcontent .oz-staff-toolbar input[type="date"] {
        height: 42px !important;
        border-radius: 10px !important;
        border: 1.5px solid var(--ifs-border-strong, #cbd5e1) !important;
        padding: 6px 12px !important;
    }

    /* Avatar & Progress System */
    .oz-staff-avatar-sm {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: var(--ifs-surface-hover, #f1f5f9);
        border: 1px solid var(--ifs-border-strong, #cbd5e1);
        color: var(--ifs-accent, #0284c7);
        font-weight: 800;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 14px;
        flex-shrink: 0;
    }

    .oz-progress-track {
        width: 100%;
        max-width: 120px;
        height: 6px;
        background: var(--ifs-surface-hover, #f1f5f9);
        border: 1px solid var(--ifs-border-subtle, #e2e8f0);
        border-radius: 4px;
        overflow: hidden;
        margin-top: 6px;
    }

    .oz-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #0284c7 0%, #38bdf8 100%);
        border-radius: 4px;
    }

    /* Table Container */
    .oz-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .oz-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        text-align: left;
    }

    .oz-table th {
        background: var(--ifs-surface-hover, #fafbfd);
        color: var(--ifs-text-tertiary, #64748b);
        font-weight: 700;
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        padding: 14px 24px;
        border-bottom: 1px solid var(--ifs-border-subtle, #e2e8f0);
    }

    .oz-table td {
        padding: 16px 24px;
        border-bottom: 1px solid var(--ifs-border-subtle, #f1f5f9);
        color: var(--ifs-text-primary, #0f172a);
        vertical-align: middle;
    }

    .oz-table tr:hover td {
        background: var(--ifs-surface-hover, #f8fafc);
    }

    /* Button System Overrides */
    #wpcontent .oz-staff-wrapper .oz-btn {
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

    #wpcontent .oz-staff-wrapper .oz-btn-primary {
        height: 42px !important;
        padding: 0 18px !important;
        font-size: 13px !important;
        border-radius: 10px !important;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
        color: #ffffff !important;
        border: 1px solid rgba(2, 132, 199, 0.8) !important;
    }

    #wpcontent .oz-staff-wrapper .oz-btn-secondary {
        height: 42px !important;
        padding: 0 16px !important;
        font-size: 13px !important;
        border-radius: 10px !important;
        background: var(--ifs-surface, #ffffff) !important;
        color: var(--ifs-text-secondary, #475569) !important;
        border: 1.5px solid var(--ifs-border-strong, #cbd5e1) !important;
    }

    #wpcontent .oz-staff-wrapper .oz-btn-secondary.active {
        background: var(--ifs-primary-soft, rgba(2, 132, 199, 0.08)) !important;
        color: var(--ifs-accent, #0284c7) !important;
        border-color: var(--ifs-accent, #0284c7) !important;
    }

    /* Print Viewport */
    @media print {
        .oz-staff-toolbar,
        .oz-panel-head button,
        .ifs-pms-aside {
            display: none !important;
        }
        .ifs-pms-canvas {
            padding: 0 !important;
            height: auto !important;
            overflow: visible !important;
            background: #ffffff !important;
        }
        .oz-panel-card,
        .oz-kpi-card {
            box-shadow: none !important;
            border: 1px solid #cbd5e1 !important;
            background: #ffffff !important;
        }
    }
</style>

<div class="oz-staff-wrapper">
    <!-- Shift KPI Summary Matrix -->
    <div class="oz-kpi-grid">
        <!-- Inflow -->
        <div class="oz-kpi-card">
            <div class="oz-kpi-top">
                <span class="oz-kpi-label"><?php esc_html_e( 'Shift Gross Intake', 'swimming-pool-manager' ); ?></span>
                <div class="oz-kpi-icon-bubble" style="background: rgba(16, 185, 129, 0.1); color: var(--ifs-success, #10b981);">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
            </div>
            <div class="oz-kpi-val ifs-pms-mono" style="color: var(--ifs-success, #10b981);">
                <?php echo esc_html( $currency . ' ' . number_format_i18n( $shift_total_rev, 2 ) ); ?>
            </div>
            <div class="oz-kpi-sub"><?php esc_html_e( 'Sum of register tickets for date', 'swimming-pool-manager' ); ?></div>
        </div>

        <!-- Volume Issued -->
        <div class="oz-kpi-card">
            <div class="oz-kpi-top">
                <span class="oz-kpi-label"><?php esc_html_e( 'Passes Issued', 'swimming-pool-manager' ); ?></span>
                <div class="oz-kpi-icon-bubble" style="background: rgba(2, 132, 199, 0.1); color: var(--ifs-accent, #0284c7);">
                    <i class="fa-solid fa-ticket"></i>
                </div>
            </div>
            <div class="oz-kpi-val ifs-pms-mono" style="color: var(--ifs-text-primary, #0f172a);">
                <?php echo esc_html( number_format_i18n( $shift_total_tkts ) ); ?>
            </div>
            <div class="oz-kpi-sub"><?php esc_html_e( 'Single, Combo & VIP tickets sold', 'swimming-pool-manager' ); ?></div>
        </div>

        <!-- Shift Star Performer -->
        <div class="oz-kpi-card">
            <div class="oz-kpi-top">
                <span class="oz-kpi-label"><?php esc_html_e( 'Leading Register Cashier', 'swimming-pool-manager' ); ?></span>
                <div class="oz-kpi-icon-bubble" style="background: rgba(245, 158, 11, 0.1); color: var(--ifs-warning, #f59e0b);">
                    <i class="fa-solid fa-trophy"></i>
                </div>
            </div>
            <div class="oz-kpi-val" style="font-size: 20px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--ifs-text-primary, #0f172a);">
                <?php echo esc_html( $top_cashier_name ); ?>
            </div>
            <div class="oz-kpi-sub ifs-pms-mono">
                <?php echo esc_html( $currency . ' ' . number_format_i18n( $top_cashier_intake, 2 ) ); ?> <?php esc_html_e( 'collected', 'swimming-pool-manager' ); ?>
            </div>
        </div>

        <!-- Scans Audited -->
        <div class="oz-kpi-card">
            <div class="oz-kpi-top">
                <span class="oz-kpi-label"><?php esc_html_e( 'Turnstile Verifications', 'swimming-pool-manager' ); ?></span>
                <div class="oz-kpi-icon-bubble" style="background: rgba(192, 132, 252, 0.1); color: #c084fc;">
                    <i class="fa-solid fa-turnstile"></i>
                </div>
            </div>
            <div class="oz-kpi-val ifs-pms-mono" style="color: #c084fc;">
                <?php echo esc_html( number_format_i18n( $shift_total_scans ) ); ?>
            </div>
            <div class="oz-kpi-sub"><?php esc_html_e( 'Passes admitted through gates', 'swimming-pool-manager' ); ?></div>
        </div>
    </div>

    <!-- Shift Audit Ledger Table Panel -->
    <div class="oz-panel-card">
        <div class="oz-panel-head">
            <div>
                <h3 class="oz-panel-title">
                    <i class="fa-solid fa-user-shield" style="color: var(--ifs-accent, #0284c7);"></i>
                    <?php esc_html_e( 'Cashier Shift Reconciliations', 'swimming-pool-manager' ); ?>
                </h3>
                <span style="font-size: 11.5px; color: var(--ifs-text-tertiary, #64748b); margin-top: 3px; display: block;">
                    <?php esc_html_e( 'Shift Date: ', 'swimming-pool-manager' ); ?><?php echo esc_html( $date_label ); ?>
                </span>
            </div>

            <button type="button" class="oz-btn oz-btn-secondary" onclick="window.print()">
                <i class="fa-solid fa-print"></i> <?php esc_html_e( 'Print Shift Slip', 'swimming-pool-manager' ); ?>
            </button>
        </div>

        <!-- Search & Shift Date Navigator -->
        <div class="oz-staff-toolbar">
            <div class="oz-search-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="ozStaffSearchInput" placeholder="<?php esc_attr_e( 'Search register operator...', 'swimming-pool-manager' ); ?>" oninput="ozFilterStaffTable()" autocomplete="off">
            </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=staff&shift_date=' . current_time( 'Y-m-d' ) ) ); ?>" 
                   class="oz-btn oz-btn-secondary <?php echo ( $shift_date === current_time( 'Y-m-d' ) ) ? 'active' : ''; ?>">
                    <?php esc_html_e( 'Today', 'swimming-pool-manager' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=staff&shift_date=' . gmdate( 'Y-m-d', strtotime( '-1 day', strtotime( current_time( 'Y-m-d' ) ) ) ) ) ); ?>" 
                   class="oz-btn oz-btn-secondary <?php echo ( $shift_date === gmdate( 'Y-m-d', strtotime( '-1 day', strtotime( current_time( 'Y-m-d' ) ) ) ) ) ? 'active' : ''; ?>">
                    <?php esc_html_e( 'Yesterday', 'swimming-pool-manager' ); ?>
                </a>

                <form method="GET" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display: flex; align-items: center; gap: 8px; margin: 0;">
                    <input type="hidden" name="page" value="ifs-pms">
                    <input type="hidden" name="view" value="staff">
                    <input type="date" name="shift_date" class="ifs-pms-mono" value="<?php echo esc_attr( $shift_date ); ?>">
                    <button type="submit" class="oz-btn oz-btn-primary">
                        <i class="fa-solid fa-arrows-rotate"></i> <?php esc_html_e( 'Audit', 'swimming-pool-manager' ); ?>
                    </button>
                </form>
            </div>
        </div>

        <div class="oz-table-wrap">
            <table class="oz-table" id="ozStaffSalesTable">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Cashier Operator', 'swimming-pool-manager' ); ?></th>
                        <th><?php esc_html_e( 'Tickets Issued', 'swimming-pool-manager' ); ?></th>
                        <th><?php esc_html_e( 'Shift Share', 'swimming-pool-manager' ); ?></th>
                        <th><?php esc_html_e( 'Gross Tendered', 'swimming-pool-manager' ); ?></th>
                        <th><?php esc_html_e( 'Turnstile Verifications', 'swimming-pool-manager' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $staff_sales ) ) : ?>
                        <?php foreach ( $staff_sales as $st ) :
                            $cashier_name = ! empty( $st->sold_by ) ? $st->sold_by : __( 'Terminal Unassigned', 'swimming-pool-manager' );
                            $scans_count  = isset( $staff_scans_raw[ $st->sold_by ] ) ? (int) $staff_scans_raw[ $st->sold_by ]->scan_count : 0;
                            $pct_share    = $shift_total_rev > 0 ? (int) round( ( (float) $st->total / $shift_total_rev ) * 100 ) : 0;
                            $initial      = mb_strtoupper( mb_substr( $cashier_name, 0, 1 ) );
                        ?>
                            <tr class="oz-staff-data-row">
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <span class="oz-staff-avatar-sm"><?php echo esc_html( $initial ); ?></span>
                                        <div>
                                            <strong class="oz-staff-label" style="font-size: 13.5px; color: var(--ifs-text-primary, #0f172a);"><?php echo esc_html( $cashier_name ); ?></strong>
                                            <div style="font-size: 11.5px; color: var(--ifs-text-tertiary, #94a3b8);"><?php esc_html_e( 'Terminal Operator', 'swimming-pool-manager' ); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong class="ifs-pms-mono" style="font-size: 14px; color: var(--ifs-text-primary, #0f172a);"><?php echo esc_html( number_format_i18n( $st->count ) ); ?></strong>
                                    <span style="color: var(--ifs-text-tertiary, #94a3b8); font-size: 12px; margin-left: 3px;"><?php esc_html_e( 'passes', 'swimming-pool-manager' ); ?></span>
                                </td>
                                <td>
                                    <div style="font-size: 11.5px; font-weight: 800; color: var(--ifs-text-tertiary, #64748b);" class="ifs-pms-mono"><?php echo esc_html( (string) $pct_share ); ?>%</div>
                                    <div class="oz-progress-track">
                                        <div class="oz-progress-bar" style="width: <?php echo esc_attr( (string) $pct_share ); ?>%;"></div>
                                    </div>
                                </td>
                                <td style="color: var(--ifs-success, #10b981); font-weight: 800; font-size: 14.5px;" class="ifs-pms-mono">
                                    <?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $st->total, 2 ) ); ?>
                                </td>
                                <td>
                                    <span class="ifs-pms-badge" style="background: var(--ifs-primary-soft, rgba(2, 132, 199, 0.08)); color: var(--ifs-accent, #0284c7); border: 1px solid rgba(2, 132, 199, 0.25);">
                                        <i class="fa-solid fa-qrcode" style="margin-right: 4px;"></i> <?php echo esc_html( number_format_i18n( $scans_count ) ); ?> <?php esc_html_e( 'verified', 'swimming-pool-manager' ); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 48px 16px; color: var(--ifs-text-tertiary, #94a3b8);">
                                <i class="fa-solid fa-user-xmark" style="font-size: 36px; opacity: 0.35; margin-bottom: 12px; display: block;"></i>
                                <?php printf( esc_html__( 'No cashier transactions logged for date: %s', 'swimming-pool-manager' ), esc_html( $shift_date ) ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if ( ! empty( $staff_sales ) ) : ?>
                    <tfoot>
                        <tr style="background: var(--ifs-surface-hover, #f8fafc); font-weight: 800; border-top: 2px solid var(--ifs-border-strong, #cbd5e1);">
                            <td style="color: var(--ifs-text-primary, #0f172a);"><?php esc_html_e( 'Shift Aggregate Sum', 'swimming-pool-manager' ); ?></td>
                            <td class="ifs-pms-mono" style="color: var(--ifs-text-primary, #0f172a);">
                                <?php echo esc_html( number_format_i18n( $shift_total_tkts ) ); ?> <?php esc_html_e( 'passes', 'swimming-pool-manager' ); ?>
                            </td>
                            <td style="color: var(--ifs-text-tertiary, #94a3b8);" class="ifs-pms-mono">100%</td>
                            <td class="ifs-pms-mono" style="color: var(--ifs-success, #10b981); font-size: 15px;">
                                <?php echo esc_html( $currency . ' ' . number_format_i18n( $shift_total_rev, 2 ) ); ?>
                            </td>
                            <td class="ifs-pms-mono" style="color: var(--ifs-accent, #0284c7);">
                                <?php echo esc_html( number_format_i18n( $shift_total_scans ) ); ?> <?php esc_html_e( 'verified', 'swimming-pool-manager' ); ?>
                            </td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    window.ozFilterStaffTable = function() {
        const searchInput = document.getElementById('ozStaffSearchInput');
        const filterStr   = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const rows        = document.querySelectorAll('.oz-staff-data-row');

        rows.forEach(row => {
            const labelEl = row.querySelector('.oz-staff-label');
            const nameTxt = labelEl ? labelEl.textContent.toLowerCase() : '';
            if (!filterStr || nameTxt.includes(filterStr)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    };
})();
</script>