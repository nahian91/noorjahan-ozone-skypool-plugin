<?php
/**
 * View: Operating Expenses & Outflow Registry (Dual Tab Executive Edition)
 *
 * @package Ozone_Skypool_OS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$t_expense = $wpdb->prefix . 'ifs_pms_expenses';
$currency  = esc_html( get_option( 'ifs_pms_currency', 'BDT' ) );
$base_url  = admin_url( 'admin.php?page=ifs-pms' );
$is_admin  = current_user_can( 'manage_options' ) || current_user_can( 'ozone_manage_settings' );

// 1. Timezone-aligned date boundaries
$today_ymd   = current_time( 'Y-m-d' );
$month_start = current_time( 'Y-m-01' );
$month_end   = current_time( 'Y-m-t' );

// Active Tab Router
$active_tab = isset( $_GET['tab'] ) && $_GET['tab'] === 'list' ? 'list' : 'add';

// 2. Financial Metrics (Indexed Range Queries)
$exp_today = (float) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM {$t_expense} WHERE expense_date = %s",
        $today_ymd
    )
);

$exp_month = (float) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COALESCE(SUM(amount), 0) FROM {$t_expense} WHERE expense_date >= %s AND expense_date <= %s",
        $month_start,
        $month_end
    )
);

$top_cat = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT category 
         FROM {$t_expense} 
         WHERE expense_date >= %s AND expense_date <= %s 
         GROUP BY category 
         ORDER BY SUM(amount) DESC 
         LIMIT 1",
        $month_start,
        $month_end
    )
);
$top_cat = $top_cat ? $top_cat : __( 'Operations & F&B', 'ozone-skypool' );

// 3. Fetch Outflows
$expenses = $wpdb->get_results(
    "SELECT * FROM {$t_expense} ORDER BY id DESC LIMIT 250"
);
?>

<style>
    /* ==========================================================================
       EXECUTIVE EXPENSES & OUTFLOW REGISTRY SUITE (V2)
       ========================================================================== */
    .oz-expense-wrapper {
        display: flex;
        flex-direction: column;
        gap: 28px;
        width: 100%;
        box-sizing: border-box;
    }

    /* Sub Navigation Tabs */
    .oz-subnav-bar {
        display: flex;
        width: 100%;
        gap: 10px;
        background: linear-gradient(135deg, rgba(241, 245, 249, 0.8) 0%, rgba(226, 232, 240, 0.6) 100%);
        backdrop-filter: blur(12px);
        padding: 8px;
        border-radius: 16px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-sizing: border-box;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    .oz-subnav-btn {
        flex: 1 1 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 14px 24px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        background: transparent;
        color: var(--ifs-text-secondary, #475569);
        border: none !important;
        outline: none;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        text-align: center;
        white-space: nowrap;
    }

    .oz-subnav-btn:hover {
        color: var(--ifs-text-primary, #0f172a);
        background: rgba(255, 255, 255, 0.5);
    }

    .oz-subnav-btn.active {
        background: var(--ifs-surface, #ffffff) !important;
        color: var(--ifs-accent, #0284c7) !important;
        box-shadow: 0 8px 24px rgba(2, 132, 199, 0.15), 0 2px 6px rgba(0, 0, 0, 0.04);
        transform: translateY(-1px);
    }

    .oz-tab-pane {
        display: none;
    }

    .oz-tab-pane.active {
        display: block;
        animation: ozFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes ozFadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* KPI Summary Cards */
    .oz-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 20px;
    }

    .oz-kpi-card {
        background: var(--ifs-surface, #ffffff);
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 15px 30px -10px rgba(15, 23, 42, 0.05);
        position: relative;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .oz-kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 20px 35px -10px rgba(15, 23, 42, 0.08);
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
        letter-spacing: 0.08em;
        color: var(--ifs-text-tertiary, #64748b);
    }

    .oz-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .oz-kpi-val {
        font-size: 28px;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.1;
    }

    .oz-kpi-sub {
        font-size: 12.5px;
        color: var(--ifs-text-secondary, #475569);
        margin-top: 10px;
    }

    /* Card Panels */
    .oz-panel-card {
        background: var(--ifs-surface, #ffffff);
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 24px;
        box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.07);
        box-sizing: border-box;
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
        font-size: 17px;
        font-weight: 800;
        letter-spacing: -0.01em;
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--ifs-text-primary, #0f172a);
    }

    /* Single / Add Form Layout */
    .oz-form-box-centered {
        max-width: 720px;
        margin: 0 auto;
    }

    .oz-form-stack {
        display: flex;
        flex-direction: column;
        gap: 22px;
        padding: 36px;
        box-sizing: border-box;
    }

    .oz-form-group {
        display: flex;
        flex-direction: column;
        width: 100%;
        margin: 0;
    }

    .oz-label {
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        margin-bottom: 8px;
        display: block;
    }

    #wpcontent .oz-expense-wrapper input[type="text"],
    #wpcontent .oz-expense-wrapper input[type="number"],
    #wpcontent .oz-expense-wrapper input[type="date"],
    #wpcontent .oz-expense-wrapper select,
    #wpcontent .oz-modal-card input[type="text"],
    #wpcontent .oz-modal-card input[type="number"],
    #wpcontent .oz-modal-card input[type="date"],
    #wpcontent .oz-modal-card select {
        display: block !important;
        width: 100% !important;
        background: #ffffff !important;
        border: 1.5px solid #cbd5e1 !important;
        border-radius: 14px !important;
        padding: 12px 18px !important;
        font-size: 14.5px !important;
        font-family: var(--ifs-font-sans, inherit) !important;
        color: var(--ifs-text-primary, #0f172a) !important;
        height: 50px !important;
        box-sizing: border-box !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    #wpcontent .oz-expense-wrapper input:focus,
    #wpcontent .oz-expense-wrapper select:focus,
    #wpcontent .oz-modal-card input:focus,
    #wpcontent .oz-modal-card select:focus {
        border-color: var(--ifs-border-focus, #0284c7) !important;
        box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.12), 0 4px 12px rgba(2, 132, 199, 0.08) !important;
        outline: none !important;
    }

    /* Preset Quick-Fill Chips */
    .oz-chips-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
    }

    .oz-chip {
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        color: #475569;
        border-radius: 8px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.15s ease;
        user-select: none;
    }

    .oz-chip:hover {
        background: rgba(2, 132, 199, 0.1);
        border-color: #0284c7;
        color: #0284c7;
        transform: translateY(-1px);
    }

    /* All Expenses Table & Filter Toolbar */
    .oz-search-bar {
        display: flex;
        gap: 16px;
        padding: 22px 32px;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
        align-items: center;
        background: #ffffff;
    }

    .oz-search-box {
        position: relative;
        flex: 1;
        min-width: 260px;
    }

    .oz-search-box i.search-icon {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 14px;
        pointer-events: none;
    }

    #wpcontent .oz-search-box input {
        padding-left: 44px !important;
        padding-right: 36px !important;
        height: 46px !important;
        border-radius: 14px !important;
    }

    .oz-search-clear {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        display: none;
        padding: 4px;
    }

    .oz-search-clear:hover {
        color: #0f172a;
    }

    #wpcontent .oz-search-bar select {
        width: auto !important;
        min-width: 200px !important;
        height: 46px !important;
        border-radius: 14px !important;
    }

    /* Table Architecture */
    .oz-table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .oz-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
        text-align: left;
    }

    .oz-table th {
        background: #f8fafc;
        color: #64748b;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 18px 24px;
        border-bottom: 1px solid #e2e8f0;
    }

    .oz-table td {
        padding: 20px 24px;
        border-bottom: 1px solid #f1f5f9;
        color: #0f172a;
        vertical-align: middle;
    }

    .oz-table tr:hover td {
        background: #f8fafc;
    }

    .oz-cat-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
    }

    /* Modal Styling */
    .oz-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(8px);
        z-index: 99999;
        align-items: center;
        justify-content: center;
    }

    .oz-modal-card {
        background: #ffffff;
        border: 1.5px solid #cbd5e1;
        border-radius: 24px;
        width: 540px;
        max-width: 95vw;
        padding: 36px;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
        box-sizing: border-box;
        animation: ozModalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes ozModalPop {
        from { opacity: 0; transform: scale(0.95) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    /* Harmonious Action Button System */
    #wpcontent .oz-expense-wrapper .oz-btn {
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
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }

    #wpcontent .oz-expense-wrapper .oz-btn-primary.oz-btn-lg {
        height: 54px !important;
        padding: 0 32px !important;
        font-size: 15px !important;
        font-weight: 800 !important;
        border-radius: 16px !important;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
        color: #ffffff !important;
        border: 1px solid rgba(2, 132, 199, 0.8) !important;
        box-shadow: 0 8px 24px rgba(2, 132, 199, 0.45) !important;
    }

    #wpcontent .oz-expense-wrapper .oz-btn-primary.oz-btn-lg:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 30px rgba(2, 132, 199, 0.55) !important;
    }

    /* Table Action Buttons (View, Edit, Delete) */
    .oz-table .oz-btn-sm {
        height: 36px !important;
        padding: 0 14px !important;
        font-size: 12.5px !important;
        border-radius: 10px !important;
        font-weight: 700 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        box-shadow: 0 2px 4px rgba(15, 23, 42, 0.03);
    }

    .oz-table .oz-btn-sm:hover {
        transform: translateY(-2px);
    }

    .oz-table .oz-btn-view {
        background: rgba(16, 185, 129, 0.08) !important;
        color: #059669 !important;
        border: 1.5px solid rgba(16, 185, 129, 0.25) !important;
    }
    .oz-table .oz-btn-view:hover {
        background: #10b981 !important;
        color: #ffffff !important;
        border-color: #10b981 !important;
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35) !important;
    }

    .oz-table .oz-btn-edit {
        background: rgba(2, 132, 199, 0.08) !important;
        color: #0284c7 !important;
        border: 1.5px solid rgba(2, 132, 199, 0.25) !important;
    }
    .oz-table .oz-btn-edit:hover {
        background: #0284c7 !important;
        color: #ffffff !important;
        border-color: #0284c7 !important;
        box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35) !important;
    }

    .oz-table .oz-btn-delete {
        background: rgba(244, 63, 94, 0.08) !important;
        color: #f43f5e !important;
        border: 1.5px solid rgba(244, 63, 94, 0.25) !important;
    }
    .oz-table .oz-btn-delete:hover {
        background: #f43f5e !important;
        color: #ffffff !important;
        border-color: #f43f5e !important;
        box-shadow: 0 6px 16px rgba(244, 63, 94, 0.35) !important;
    }
</style>

<div class="oz-expense-wrapper">
    <!-- Top KPI Metrics -->
    <div class="oz-kpi-grid">
        <div class="oz-kpi-card">
            <div class="oz-kpi-top">
                <span class="oz-kpi-label"><?php esc_html_e( 'Today\'s Operational Outflow', 'ozone-skypool' ); ?></span>
                <div class="oz-kpi-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--ifs-danger, #ef4444);">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
            </div>
            <div class="oz-kpi-val ifs-pms-mono" style="color: var(--ifs-danger, #ef4444);">
                <?php echo esc_html( $currency . ' ' . number_format_i18n( $exp_today, 2 ) ); ?>
            </div>
            <div class="oz-kpi-sub"><?php esc_html_e( 'Disbursements posted for current day', 'ozone-skypool' ); ?></div>
        </div>

        <div class="oz-kpi-card">
            <div class="oz-kpi-top">
                <span class="oz-kpi-label"><?php esc_html_e( 'Month-To-Date Cost', 'ozone-skypool' ); ?></span>
                <div class="oz-kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--ifs-warning, #f59e0b);">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
            </div>
            <div class="oz-kpi-val ifs-pms-mono" style="color: var(--ifs-warning, #f59e0b);">
                <?php echo esc_html( $currency . ' ' . number_format_i18n( $exp_month, 2 ) ); ?>
            </div>
            <div class="oz-kpi-sub"><?php esc_html_e( 'Current monthly overhead total', 'ozone-skypool' ); ?></div>
        </div>

        <div class="oz-kpi-card">
            <div class="oz-kpi-top">
                <span class="oz-kpi-label"><?php esc_html_e( 'Major Cost Sector', 'ozone-skypool' ); ?></span>
                <div class="oz-kpi-icon" style="background: rgba(192, 132, 252, 0.1); color: #c084fc;">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
            </div>
            <div class="oz-kpi-val" style="font-size: 22px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: var(--ifs-text-primary, #0f172a);">
                <?php echo esc_html( $top_cat ); ?>
            </div>
            <div class="oz-kpi-sub"><?php esc_html_e( 'Highest monthly expense allocation', 'ozone-skypool' ); ?></div>
        </div>
    </div>

    <!-- Sub Navigation Tabs -->
    <div class="oz-subnav-bar" role="tablist">
        <button type="button" class="oz-subnav-btn <?php echo ( $active_tab === 'add' ) ? 'active' : ''; ?>" id="ozExpenseTabBtnAdd" onclick="ozSwitchExpenseTab('add', this)">
            <i class="fa-solid fa-circle-plus"></i> <?php esc_html_e( 'Add Expense', 'ozone-skypool' ); ?>
        </button>
        <button type="button" class="oz-subnav-btn <?php echo ( $active_tab === 'list' ) ? 'active' : ''; ?>" id="ozExpenseTabBtnList" onclick="ozSwitchExpenseTab('list', this)">
            <i class="fa-solid fa-receipt"></i> <?php esc_html_e( 'All Expenses', 'ozone-skypool' ); ?>
        </button>
    </div>

    <!-- TAB 1: Add Expense Form -->
    <div id="ozExpensePaneAdd" class="oz-tab-pane <?php echo ( $active_tab === 'add' ) ? 'active' : ''; ?>">
        <div class="oz-form-box-centered">
            <div class="oz-panel-card">
                <div class="oz-panel-head">
                    <h3 class="oz-panel-title">
                        <i class="fa-solid fa-circle-plus" style="color: var(--ifs-accent, #0284c7);"></i>
                        <?php esc_html_e( 'Record Facility Outflow', 'ozone-skypool' ); ?>
                    </h3>
                    <span class="ifs-pms-badge ifs-pms-badge-danger"><?php esc_html_e( 'Debit (-)', 'ozone-skypool' ); ?></span>
                </div>

                <form method="POST" action="<?php echo esc_url( $base_url . '&view=expenses' ); ?>" id="ifsPmsExpenseForm">
                    <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                    <input type="hidden" name="ifs_pms_action" value="add_expense">

                    <div class="oz-form-stack">
                        <div class="oz-form-group">
                            <label class="oz-label" for="ifsExpTitle"><?php esc_html_e( 'Outflow Purpose / Description', 'ozone-skypool' ); ?> *</label>
                            <input type="text" name="expense_title" id="ifsExpTitle" required placeholder="<?php esc_attr_e( 'e.g. Liquid Chlorine 50L Drum', 'ozone-skypool' ); ?>" autocomplete="off">
                            <div class="oz-chips-wrap">
                                <span class="oz-chip" onclick="ozSetExpensePreset('Liquid Chlorine 50L Drum', 'Water Treatment')"><?php esc_html_e( 'Chlorine Drum', 'ozone-skypool' ); ?></span>
                                <span class="oz-chip" onclick="ozSetExpensePreset('Rooftop BBQ Charcoal & Fuel', 'Restaurant & F&B')"><?php esc_html_e( 'BBQ Charcoal', 'ozone-skypool' ); ?></span>
                                <span class="oz-chip" onclick="ozSetExpensePreset('Fresh Mocktail Fruit Purees', 'Restaurant & F&B')"><?php esc_html_e( 'Bar Syrups', 'ozone-skypool' ); ?></span>
                                <span class="oz-chip" onclick="ozSetExpensePreset('Tower Backup Generator Diesel', 'Electricity / Utilities')"><?php esc_html_e( 'Diesel Gen', 'ozone-skypool' ); ?></span>
                                <span class="oz-chip" onclick="ozSetExpensePreset('Deck Staff Meal Allowance', 'Staff Expenses')"><?php esc_html_e( 'Staff Meals', 'ozone-skypool' ); ?></span>
                                <span class="oz-chip" onclick="ozSetExpensePreset('Pool Vacuum Filter Cartridge', 'Pump Maintenance')"><?php esc_html_e( 'Filter Cartridge', 'ozone-skypool' ); ?></span>
                            </div>
                        </div>

                        <div class="oz-form-group">
                            <label class="oz-label" for="ifsExpCat"><?php esc_html_e( 'Operational Sector', 'ozone-skypool' ); ?> *</label>
                            <select name="expense_cat" id="ifsExpCat">
                                <option value="Water Treatment"><?php esc_html_e( 'Water Treatment & Chemicals', 'ozone-skypool' ); ?></option>
                                <option value="Restaurant & F&B"><?php esc_html_e( 'Restaurant, F&B & Bar Inventory', 'ozone-skypool' ); ?></option>
                                <option value="Electricity / Utilities"><?php esc_html_e( 'Electricity, Power & Utilities', 'ozone-skypool' ); ?></option>
                                <option value="Pump Maintenance"><?php esc_html_e( 'Pump, Filter & Mechanical Maintenance', 'ozone-skypool' ); ?></option>
                                <option value="Staff Expenses"><?php esc_html_e( 'Staff Payroll, Uniforms & Meals', 'ozone-skypool' ); ?></option>
                                <option value="General Facility"><?php esc_html_e( 'Rooftop Deck & General Maintenance', 'ozone-skypool' ); ?></option>
                                <option value="Other Costs"><?php esc_html_e( 'Miscellaneous & Operating Overheads', 'ozone-skypool' ); ?></option>
                            </select>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">
                            <div class="oz-form-group">
                                <label class="oz-label" for="ifsExpAmount"><?php printf( esc_html__( 'Amount Outflow (%s)', 'ozone-skypool' ), esc_html( $currency ) ); ?> *</label>
                                <input type="number" step="0.01" name="expense_amount" id="ifsExpAmount" class="ifs-pms-mono" required placeholder="0.00" min="0.01">
                            </div>

                            <div class="oz-form-group">
                                <label class="oz-label" for="ifsExpDate"><?php esc_html_e( 'Disbursement Date', 'ozone-skypool' ); ?> *</label>
                                <input type="date" name="expense_date" id="ifsExpDate" class="ifs-pms-mono" value="<?php echo esc_attr( $today_ymd ); ?>" required>
                            </div>
                        </div>

                        <div style="margin-top: 8px;">
                            <button type="submit" class="oz-btn oz-btn-primary oz-btn-lg" style="width: 100%;">
                                <i class="fa-solid fa-file-circle-check"></i> <?php esc_html_e( 'Post Expense Record', 'ozone-skypool' ); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB 2: All Expenses Registry -->
    <div id="ozExpensePaneList" class="oz-tab-pane <?php echo ( $active_tab === 'list' ) ? 'active' : ''; ?>">
        <div class="oz-panel-card">
            <div class="oz-panel-head">
                <h3 class="oz-panel-title">
                    <i class="fa-solid fa-receipt" style="color: var(--ifs-text-secondary, #475569);"></i>
                    <?php esc_html_e( 'Expense Audit Registry', 'ozone-skypool' ); ?>
                </h3>
                <span class="ifs-pms-badge" style="background: #f1f5f9; color: var(--ifs-text-secondary); border: 1px solid #cbd5e1; font-weight: 700; padding: 6px 12px; border-radius: 8px;">
                    <?php echo count( $expenses ); ?> <?php esc_html_e( 'Postings Recorded', 'ozone-skypool' ); ?>
                </span>
            </div>

            <!-- Instant Search & Category Filter Toolbar -->
            <div class="oz-search-bar">
                <div class="oz-search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="ozExpenseSearchInput" placeholder="<?php esc_attr_e( 'Search by item description, sector, or poster...', 'ozone-skypool' ); ?>" oninput="ozFilterExpenseRegistry()" autocomplete="off">
                    <button type="button" class="oz-search-clear" id="ozExpenseSearchClear" onclick="ozClearExpenseSearch()">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <select id="ozExpenseCatSelect" onchange="ozFilterExpenseRegistry()">
                    <option value="ALL"><?php esc_html_e( 'All Sectors', 'ozone-skypool' ); ?></option>
                    <option value="Water Treatment"><?php esc_html_e( 'Water Treatment', 'ozone-skypool' ); ?></option>
                    <option value="Restaurant & F&B"><?php esc_html_e( 'Restaurant & F&B', 'ozone-skypool' ); ?></option>
                    <option value="Electricity / Utilities"><?php esc_html_e( 'Electricity / Utilities', 'ozone-skypool' ); ?></option>
                    <option value="Pump Maintenance"><?php esc_html_e( 'Pump Maintenance', 'ozone-skypool' ); ?></option>
                    <option value="Staff Expenses"><?php esc_html_e( 'Staff Expenses', 'ozone-skypool' ); ?></option>
                    <option value="General Facility"><?php esc_html_e( 'General Facility', 'ozone-skypool' ); ?></option>
                    <option value="Other Costs"><?php esc_html_e( 'Other Costs', 'ozone-skypool' ); ?></option>
                </select>
            </div>

            <div class="oz-table-wrap">
                <table class="oz-table" id="ozExpenseTable">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Description', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Sector', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Amount', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Logged By', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Date', 'ozone-skypool' ); ?></th>
                            <th style="text-align: right;"><?php esc_html_e( 'Actions', 'ozone-skypool' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $expenses ) ) : ?>
                            <?php foreach ( $expenses as $e ) : ?>
                                <tr class="oz-expense-data-row" data-cat="<?php echo esc_attr( $e->category ); ?>">
                                    <td>
                                        <strong style="color: #0f172a; font-weight: 700;"><?php echo esc_html( $e->title ); ?></strong>
                                    </td>
                                    <td>
                                        <span class="oz-cat-badge">
                                            <?php echo esc_html( $e->category ); ?>
                                        </span>
                                    </td>
                                    <td style="color: var(--ifs-danger, #ef4444); font-weight: 800;" class="ifs-pms-mono">
                                        -<?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $e->amount, 2 ) ); ?>
                                    </td>
                                    <td style="color: #64748b; font-size: 13px;">
                                        <?php echo esc_html( ! empty( $e->added_by ) ? $e->added_by : __( 'System', 'ozone-skypool' ) ); ?>
                                    </td>
                                    <td style="font-size: 12.5px; color: #475569;" class="ifs-pms-mono">
                                        <?php echo esc_html( $e->expense_date ); ?>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <!-- View Modal: Emerald Green Harmony -->
                                        <button type="button" class="oz-btn oz-btn-sm oz-btn-view" onclick='ozOpenViewExpenseModal(<?php echo wp_json_encode( array(
                                            'id'       => $e->id,
                                            'title'    => $e->title,
                                            'category' => $e->category,
                                            'amount'   => number_format_i18n( (float) $e->amount, 2 ),
                                            'date'     => $e->expense_date,
                                            'by'       => ! empty( $e->added_by ) ? $e->added_by : __( 'System', 'ozone-skypool' ),
                                        ) ); ?>)'>
                                            <i class="fa-solid fa-eye"></i> <?php esc_html_e( 'View', 'ozone-skypool' ); ?>
                                        </button>

                                        <!-- Edit Modal: Sky Blue Harmony -->
                                        <button type="button" class="oz-btn oz-btn-sm oz-btn-edit" onclick='ozOpenEditExpenseModal(<?php echo wp_json_encode( array(
                                            'id'       => $e->id,
                                            'title'    => $e->title,
                                            'category' => $e->category,
                                            'amount'   => $e->amount,
                                            'date'     => $e->expense_date,
                                        ) ); ?>)'>
                                            <i class="fa-solid fa-pen-to-square"></i> <?php esc_html_e( 'Edit', 'ozone-skypool' ); ?>
                                        </button>

                                        <!-- Delete Action: Rose Red Harmony -->
                                        <?php if ( $is_admin ) : ?>
                                            <form method="POST" action="<?php echo esc_url( $base_url . '&view=expenses' ); ?>" style="display: inline-block; margin: 0;" onsubmit="return confirm(<?php echo wp_json_encode( __( 'Delete this expense posting permanently?', 'ozone-skypool' ) ); ?>);">
                                                <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                                                <input type="hidden" name="ifs_pms_action" value="delete_expense">
                                                <input type="hidden" name="expense_id" value="<?php echo esc_attr( $e->id ); ?>">
                                                <button type="submit" class="oz-btn oz-btn-sm oz-btn-delete" title="<?php esc_attr_e( 'Delete Permanently', 'ozone-skypool' ); ?>">
                                                    <i class="fa-solid fa-trash-can"></i> <?php esc_html_e( 'Delete', 'ozone-skypool' ); ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr id="ozExpenseEmptyRow">
                                <td colspan="6" style="text-align: center; padding: 56px 16px; color: #94a3b8;">
                                    <i class="fa-solid fa-receipt" style="font-size: 36px; opacity: 0.35; margin-bottom: 14px; display: block;"></i>
                                    <?php esc_html_e( 'No operational expenditures recorded.', 'ozone-skypool' ); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: View Expense Details -->
<div id="ozViewExpenseModal" class="oz-modal-overlay">
    <div class="oz-modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: 17px; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-receipt" style="color: var(--ifs-accent, #0284c7);"></i>
                <?php esc_html_e( 'Expense Details', 'ozone-skypool' ); ?>
            </h3>
            <button type="button" style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 18px; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center;" onclick="ozCloseViewExpenseModal()">&times;</button>
        </div>

        <div style="display: flex; flex-direction: column; gap: 16px; font-size: 14px;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Description:', 'ozone-skypool' ); ?></span>
                <strong style="color: #0f172a;" id="ozViewExpTitle">--</strong>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Sector Category:', 'ozone-skypool' ); ?></span>
                <span class="oz-cat-badge" id="ozViewExpCat">--</span>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Amount Outflow:', 'ozone-skypool' ); ?></span>
                <strong style="color: var(--ifs-danger); font-size: 17px;" class="ifs-pms-mono" id="ozViewExpAmount">--</strong>
            </div>

            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Payment Date:', 'ozone-skypool' ); ?></span>
                <span class="ifs-pms-mono" id="ozViewExpDate">--</span>
            </div>

            <div style="display: flex; justify-content: space-between;">
                <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Logged By Operator:', 'ozone-skypool' ); ?></span>
                <span style="color: #475569;" id="ozViewExpBy">--</span>
            </div>
        </div>

        <div style="margin-top: 28px;">
            <button type="button" class="oz-btn oz-btn-secondary" style="width: 100%; height: 48px; border-radius: 12px;" onclick="ozCloseViewExpenseModal()">
                <?php esc_html_e( 'Close Slip', 'ozone-skypool' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- MODAL: Edit Expense Record -->
<div id="ozEditExpenseModal" class="oz-modal-overlay">
    <div class="oz-modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: 17px; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-pen-to-square" style="color: var(--ifs-accent, #0284c7);"></i>
                <?php esc_html_e( 'Edit Expense Entry', 'ozone-skypool' ); ?>
            </h3>
            <button type="button" style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 18px; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center;" onclick="ozCloseEditExpenseModal()">&times;</button>
        </div>

        <form method="POST" action="<?php echo esc_url( $base_url . '&view=expenses' ); ?>" id="ozEditExpenseForm">
            <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
            <input type="hidden" name="ifs_pms_action" value="edit_expense">
            <input type="hidden" name="expense_id" id="ozEditExpId" value="">

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div class="oz-form-group">
                    <label class="oz-label"><?php esc_html_e( 'Outflow Description', 'ozone-skypool' ); ?> *</label>
                    <input type="text" name="expense_title" id="ozEditExpTitle" required>
                </div>

                <div class="oz-form-group">
                    <label class="oz-label"><?php esc_html_e( 'Operational Sector', 'ozone-skypool' ); ?> *</label>
                    <select name="expense_cat" id="ozEditExpCat">
                        <option value="Water Treatment"><?php esc_html_e( 'Water Treatment & Chemicals', 'ozone-skypool' ); ?></option>
                        <option value="Restaurant & F&B"><?php esc_html_e( 'Restaurant, F&B & Bar Inventory', 'ozone-skypool' ); ?></option>
                        <option value="Electricity / Utilities"><?php esc_html_e( 'Electricity, Power & Utilities', 'ozone-skypool' ); ?></option>
                        <option value="Pump Maintenance"><?php esc_html_e( 'Pump, Filter & Mechanical Maintenance', 'ozone-skypool' ); ?></option>
                        <option value="Staff Expenses"><?php esc_html_e( 'Staff Payroll, Uniforms & Meals', 'ozone-skypool' ); ?></option>
                        <option value="General Facility"><?php esc_html_e( 'Rooftop Deck & General Maintenance', 'ozone-skypool' ); ?></option>
                        <option value="Other Costs"><?php esc_html_e( 'Miscellaneous & Operating Overheads', 'ozone-skypool' ); ?></option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="oz-form-group">
                        <label class="oz-label"><?php printf( esc_html__( 'Amount (%s)', 'ozone-skypool' ), esc_html( $currency ) ); ?> *</label>
                        <input type="number" step="0.01" name="expense_amount" id="ozEditExpAmount" class="ifs-pms-mono" required min="0.01">
                    </div>

                    <div class="oz-form-group">
                        <label class="oz-label"><?php esc_html_e( 'Date', 'ozone-skypool' ); ?> *</label>
                        <input type="date" name="expense_date" id="ozEditExpDate" class="ifs-pms-mono" required>
                    </div>
                </div>

                <div style="display: flex; gap: 14px; margin-top: 14px;">
                    <button type="submit" class="oz-btn oz-btn-primary" style="flex: 2; height: 50px; border-radius: 14px; font-weight: 800; font-size: 14.5px;">
                        <i class="fa-solid fa-floppy-disk"></i> <?php esc_html_e( 'Save Changes', 'ozone-skypool' ); ?>
                    </button>
                    <button type="button" class="oz-btn oz-btn-secondary" style="flex: 1; height: 50px; border-radius: 14px; font-weight: 700;" onclick="ozCloseEditExpenseModal()">
                        <?php esc_html_e( 'Cancel', 'ozone-skypool' ); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    // Tab Switching Router
    window.ozSwitchExpenseTab = function(tabKey, btn) {
        document.querySelectorAll('.oz-subnav-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        document.getElementById('ozExpensePaneAdd').classList.remove('active');
        document.getElementById('ozExpensePaneList').classList.remove('active');

        if (tabKey === 'add') {
            document.getElementById('ozExpensePaneAdd').classList.add('active');
        } else {
            document.getElementById('ozExpensePaneList').classList.add('active');
        }

        if (window.history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.set('tab', tabKey);
            window.history.replaceState({}, '', url);
        }
    };

    // Quick Fill Presets
    window.ozSetExpensePreset = function(title, cat) {
        const titleInput  = document.getElementById('ifsExpTitle');
        const catSelect   = document.getElementById('ifsExpCat');
        const amountInput = document.getElementById('ifsExpAmount');

        if (titleInput) titleInput.value = title;
        if (catSelect)  catSelect.value = cat;
        if (amountInput) amountInput.focus();
    };

    // View Modal Handlers
    window.ozOpenViewExpenseModal = function(data) {
        document.getElementById('ozViewExpTitle').textContent  = data.title;
        document.getElementById('ozViewExpCat').textContent    = data.category;
        document.getElementById('ozViewExpAmount').textContent = '-' + data.amount + ' <?php echo esc_js( $currency ); ?>';
        document.getElementById('ozViewExpDate').textContent   = data.date;
        document.getElementById('ozViewExpBy').textContent     = data.by;

        document.getElementById('ozViewExpenseModal').style.display = 'flex';
    };

    window.ozCloseViewExpenseModal = function() {
        document.getElementById('ozViewExpenseModal').style.display = 'none';
    };

    // Edit Modal Handlers
    window.ozOpenEditExpenseModal = function(data) {
        document.getElementById('ozEditExpId').value     = data.id;
        document.getElementById('ozEditExpTitle').value  = data.title;
        document.getElementById('ozEditExpCat').value    = data.category;
        document.getElementById('ozEditExpAmount').value = parseFloat(data.amount).toFixed(2);
        document.getElementById('ozEditExpDate').value   = data.date;

        document.getElementById('ozEditExpenseModal').style.display = 'flex';
    };

    window.ozCloseEditExpenseModal = function() {
        document.getElementById('ozEditExpenseModal').style.display = 'none';
    };

    // Live Instant Filter
    window.ozFilterExpenseRegistry = function() {
        const searchInput = document.getElementById('ozExpenseSearchInput');
        const clearBtn    = document.getElementById('ozExpenseSearchClear');
        const catSelect   = document.getElementById('ozExpenseCatSelect');
        const searchVal   = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const catVal      = catSelect ? catSelect.value : 'ALL';
        const rows        = document.querySelectorAll('.oz-expense-data-row');

        if (clearBtn) {
            clearBtn.style.display = searchVal ? 'block' : 'none';
        }

        rows.forEach(row => {
            const rowCat  = row.getAttribute('data-cat');
            const rowText = row.textContent.toLowerCase();

            const matchesCat    = (catVal === 'ALL' || rowCat === catVal);
            const matchesSearch = (!searchVal || rowText.includes(searchVal));

            row.style.display = (matchesCat && matchesSearch) ? '' : 'none';
        });
    };

    window.ozClearExpenseSearch = function() {
        const input = document.getElementById('ozExpenseSearchInput');
        if (input) {
            input.value = '';
            window.ozFilterExpenseRegistry();
            input.focus();
        }
    };
})();
</script>