<?php
/**
 * View: Staff Management & Unified Operator Salary Provisioning (Executive UX Edition)
 *
 * @package Ozone_Skypool_OS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$t_tick   = $wpdb->prefix . 'ifs_pms_tickets';
$t_salary = $wpdb->prefix . 'ifs_pms_salaries';
$currency = esc_html( get_option( 'ifs_pms_currency', 'BDT' ) );
$base_url = admin_url( 'admin.php?page=ifs-pms&view=staff' );
$is_admin = current_user_can( 'manage_options' );

// Active Tab Router ('add' or 'list')
$active_tab = isset( $_GET['tab'] ) && $_GET['tab'] === 'list' ? 'list' : 'add';

// Fetch WordPress Users with salaries joined
$staff_users = get_users( array(
    'role__in' => array( 'administrator', 'ozone_cashier', 'editor' ),
    'orderby'  => 'display_name',
) );

// Index salary records by user_id
$salaries_raw = $wpdb->get_results( "SELECT * FROM {$t_salary}", OBJECT_K );
$salaries_by_user = array();
foreach ( $salaries_raw as $sal ) {
    $salaries_by_user[ $sal->user_id ] = $sal;
}
?>

<style>
    /* ==========================================================================
       UNIFIED STAFF & SALARY PROVISIONING SUITE (EXECUTIVE UX)
       ========================================================================== */
    .oz-staff-wrapper {
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

    .oz-subnav-btn:hover { color: var(--ifs-text-primary, #0f172a); background: rgba(255, 255, 255, 0.5); }
    .oz-subnav-btn.active {
        background: var(--ifs-surface, #ffffff) !important;
        color: var(--ifs-accent, #0284c7) !important;
        box-shadow: 0 8px 24px rgba(2, 132, 199, 0.15), 0 2px 6px rgba(0, 0, 0, 0.04);
        transform: translateY(-1px);
    }

    .oz-tab-pane { display: none; }
    .oz-tab-pane.active {
        display: block;
        animation: ozFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes ozFadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

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
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--ifs-text-primary, #0f172a);
    }

    .oz-form-box-centered { max-width: 720px; margin: 0 auto; }
    .oz-form-stack {
        padding: 36px;
        display: flex;
        flex-direction: column;
        gap: 22px;
        box-sizing: border-box;
    }

    .oz-field-group {
        display: flex;
        flex-direction: column;
        width: 100%;
        margin: 0;
    }

    .oz-field-label {
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    #wpcontent .oz-staff-wrapper input[type="text"],
    #wpcontent .oz-staff-wrapper input[type="email"],
    #wpcontent .oz-staff-wrapper input[type="password"],
    #wpcontent .oz-staff-wrapper input[type="number"],
    #wpcontent .oz-staff-wrapper input[type="date"],
    #wpcontent .oz-staff-wrapper select,
    #wpcontent .oz-modal-card input,
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

    #wpcontent .oz-staff-wrapper input:focus,
    #wpcontent .oz-staff-wrapper select:focus,
    #wpcontent .oz-modal-card input:focus,
    #wpcontent .oz-modal-card select:focus {
        border-color: var(--ifs-border-focus, #0284c7) !important;
        box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.12), 0 4px 12px rgba(2, 132, 199, 0.08) !important;
        outline: none !important;
    }

    .oz-search-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 22px 32px;
        border-bottom: 1px solid #f1f5f9;
        gap: 16px;
        flex-wrap: wrap;
        background: #ffffff;
    }

    .oz-search-container {
        position: relative;
        flex: 1;
        max-width: 380px;
        min-width: 240px;
    }

    .oz-search-container i.search-icon {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 14px;
        pointer-events: none;
    }

    #wpcontent .oz-search-container input {
        padding-left: 44px !important;
        height: 46px !important;
        border-radius: 14px !important;
    }

    .oz-table-wrap { width: 100%; overflow-x: auto; }
    .oz-table { width: 100%; border-collapse: collapse; font-size: 14px; text-align: left; }
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
    .oz-table tr:hover td { background: #f8fafc; }

    .oz-staff-avatar-sm {
        width: 42px; height: 42px; border-radius: 12px;
        background: linear-gradient(135deg, rgba(2, 132, 199, 0.12) 0%, rgba(2, 132, 199, 0.04) 100%);
        border: 1px solid rgba(2, 132, 199, 0.2);
        color: var(--ifs-accent, #0284c7); font-weight: 800; font-size: 15px;
        display: inline-flex; align-items: center; justify-content: center; margin-right: 16px; flex-shrink: 0;
    }

    .oz-modal-overlay {
        display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center;
    }
    .oz-modal-card {
        background: #ffffff; border: 1.5px solid #cbd5e1;
        border-radius: 24px; width: 580px; max-width: 95vw; padding: 36px; box-sizing: border-box;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
        animation: ozModalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes ozModalPop {
        from { opacity: 0; transform: scale(0.95) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    /* Harmonious Action Buttons */
    #wpcontent .oz-staff-wrapper .oz-btn {
        display: inline-flex !important; align-items: center !important; justify-content: center !important;
        gap: 8px !important; font-weight: 700 !important; cursor: pointer !important; box-sizing: border-box !important; text-decoration: none !important;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    #wpcontent .oz-staff-wrapper .oz-btn-primary.oz-btn-lg {
        height: 54px !important; padding: 0 32px !important; font-size: 15px !important; font-weight: 800 !important; border-radius: 16px !important;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important; color: #ffffff !important; border: 1px solid rgba(2,132,199,0.8)!important;
        box-shadow: 0 8px 24px rgba(2, 132, 199, 0.45) !important;
    }
    #wpcontent .oz-staff-wrapper .oz-btn-primary.oz-btn-lg:hover { transform: translateY(-2px); box-shadow: 0 12px 30px rgba(2, 132, 199, 0.55) !important; }

    /* Table Action Buttons (View, Edit, Delete) */
    .oz-table .oz-btn-sm {
        height: 36px !important; padding: 0 14px !important; font-size: 12.5px !important; border-radius: 10px !important;
        font-weight: 700 !important; display: inline-flex !important; align-items: center !important; gap: 6px !important; box-shadow: 0 2px 4px rgba(15, 23, 42, 0.03);
    }
    .oz-table .oz-btn-sm:hover { transform: translateY(-2px); }

    .oz-table .oz-btn-view {
        background: rgba(16, 185, 129, 0.08) !important; color: #059669 !important; border: 1.5px solid rgba(16, 185, 129, 0.25) !important;
    }
    .oz-table .oz-btn-view:hover { background: #10b981 !important; color: #ffffff !important; border-color: #10b981 !important; box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35) !important; }

    .oz-table .oz-btn-edit {
        background: rgba(2, 132, 199, 0.08) !important; color: #0284c7 !important; border: 1.5px solid rgba(2, 132, 199, 0.25) !important;
    }
    .oz-table .oz-btn-edit:hover { background: #0284c7 !important; color: #ffffff !important; border-color: #0284c7 !important; box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35) !important; }

    .oz-table .oz-btn-delete {
        background: rgba(244, 63, 94, 0.08) !important; color: #f43f5e !important; border: 1.5px solid rgba(244, 63, 94, 0.25) !important;
    }
    .oz-table .oz-btn-delete:hover { background: #f43f5e !important; color: #ffffff !important; border-color: #f43f5e !important; box-shadow: 0 6px 16px rgba(244, 63, 94, 0.35) !important; }
</style>

<div class="oz-staff-wrapper">
    <!-- Sub Navigation Tab Bar -->
    <div class="oz-subnav-bar" role="tablist">
        <button type="button" class="oz-subnav-btn <?php echo ( $active_tab === 'add' ) ? 'active' : ''; ?>" onclick="ozSwitchStaffTab('add', this)">
            <i class="fa-solid fa-user-plus"></i> <?php esc_html_e( 'Add Staff & Salary', 'ozone-skypool' ); ?>
        </button>
        <button type="button" class="oz-subnav-btn <?php echo ( $active_tab === 'list' ) ? 'active' : ''; ?>" onclick="ozSwitchStaffTab('list', this)">
            <i class="fa-solid fa-users"></i> <?php esc_html_e( 'All Staff Directory', 'ozone-skypool' ); ?>
        </button>
    </div>

    <!-- TAB 1: Add Staff & Salary Terminal -->
    <div id="ozStaffPaneAdd" class="oz-tab-pane <?php echo ( $active_tab === 'add' ) ? 'active' : ''; ?>">
        <div class="oz-form-box-centered">
            <div class="oz-panel-card">
                <div class="oz-panel-head">
                    <h3 class="oz-panel-title">
                        <i class="fa-solid fa-user-plus" style="color: var(--ifs-accent, #0284c7);"></i>
                        <?php esc_html_e( 'Provision Operator & Compensation', 'ozone-skypool' ); ?>
                    </h3>
                    <span class="ifs-pms-badge ifs-pms-badge-success"><?php esc_html_e( 'RBAC & Payroll', 'ozone-skypool' ); ?></span>
                </div>

                <form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=staff' ) ); ?>">
                    <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                    <input type="hidden" name="ifs_pms_action" value="create_staff">

                    <div class="oz-form-stack">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">
                            <div class="oz-field-group">
                                <label class="oz-field-label"><?php esc_html_e( 'Username', 'ozone-skypool' ); ?> *</label>
                                <input type="text" name="user_login" required placeholder="e.g. cashier_tanvir" autocomplete="off">
                            </div>
                            <div class="oz-field-group">
                                <label class="oz-field-label"><?php esc_html_e( 'Display / Full Name', 'ozone-skypool' ); ?> *</label>
                                <input type="text" name="display_name" required placeholder="e.g. Tanvir Ahmed" autocomplete="off">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">
                            <div class="oz-field-group">
                                <label class="oz-field-label"><?php esc_html_e( 'Email Address', 'ozone-skypool' ); ?> *</label>
                                <input type="email" name="user_email" required placeholder="cashier@ozoneskypool.com" autocomplete="off">
                            </div>
                            <div class="oz-field-group">
                                <label class="oz-field-label"><?php esc_html_e( 'Secure Password', 'ozone-skypool' ); ?> *</label>
                                <input type="password" name="user_pass" required placeholder="••••••••••••" autocomplete="new-password">
                            </div>
                        </div>

                        <div class="oz-field-group">
                            <label class="oz-field-label"><?php esc_html_e( 'Assigned Terminal Role', 'ozone-skypool' ); ?> *</label>
                            <select name="user_role">
                                <option value="ozone_cashier"><?php esc_html_e( 'Ozone Cashier (POS, Scanner & Members)', 'ozone-skypool' ); ?></option>
                                <option value="administrator"><?php esc_html_e( 'Administrator / Facility Manager (Full Access)', 'ozone-skypool' ); ?></option>
                            </select>
                        </div>

                        <hr style="border: 0; border-top: 1px solid #f1f5f9; margin: 6px 0;">

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">
                            <div class="oz-field-group">
                                <label class="oz-field-label"><?php printf( esc_html__( 'Base Salary (%s)', 'ozone-skypool' ), esc_html( $currency ) ); ?></label>
                                <input type="number" step="0.01" name="base_salary" class="ifs-pms-mono" placeholder="25000.00" value="0.00">
                            </div>
                            <div class="oz-field-group">
                                <label class="oz-field-label"><?php printf( esc_html__( 'Allowance (%s)', 'ozone-skypool' ), esc_html( $currency ) ); ?></label>
                                <input type="number" step="0.01" name="allowance" class="ifs-pms-mono" placeholder="3000.00" value="0.00">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">
                            <div class="oz-field-group">
                                <label class="oz-field-label"><?php esc_html_e( 'Pay Frequency', 'ozone-skypool' ); ?></label>
                                <select name="pay_frequency">
                                    <option value="Monthly"><?php esc_html_e( 'Monthly', 'ozone-skypool' ); ?></option>
                                    <option value="Weekly"><?php esc_html_e( 'Weekly', 'ozone-skypool' ); ?></option>
                                    <option value="Daily"><?php esc_html_e( 'Daily Shift', 'ozone-skypool' ); ?></option>
                                </select>
                            </div>
                            <div class="oz-field-group">
                                <label class="oz-field-label"><?php esc_html_e( 'Effective Date', 'ozone-skypool' ); ?></label>
                                <input type="date" name="effective_date" class="ifs-pms-mono" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
                            </div>
                        </div>

                        <div style="margin-top: 10px;">
                            <button type="submit" class="oz-btn oz-btn-primary oz-btn-lg" style="width: 100%;">
                                <i class="fa-solid fa-user-check"></i> <?php esc_html_e( 'Create Operator & Compensation', 'ozone-skypool' ); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB 2: All Staff Directory -->
    <div id="ozStaffPaneList" class="oz-tab-pane <?php echo ( $active_tab === 'list' ) ? 'active' : ''; ?>">
        <div class="oz-panel-card">
            <div class="oz-panel-head">
                <h3 class="oz-panel-title">
                    <i class="fa-solid fa-user-shield" style="color: var(--ifs-text-secondary, #475569);"></i>
                    <?php esc_html_e( 'Active Operators Directory & Payroll Ledger', 'ozone-skypool' ); ?>
                </h3>
                <span class="ifs-pms-badge" style="background: #f1f5f9; color: var(--ifs-text-secondary); border: 1px solid #cbd5e1; font-weight: 700; padding: 6px 12px; border-radius: 8px;">
                    <?php echo count( $staff_users ); ?> <?php esc_html_e( 'Registered Operators', 'ozone-skypool' ); ?>
                </span>
            </div>

            <div class="oz-search-bar">
                <div class="oz-search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="ozStaffSearchInput" placeholder="<?php esc_attr_e( 'Filter by name, username, or email...', 'ozone-skypool' ); ?>" oninput="ozFilterStaffDirectory()" autocomplete="off">
                </div>
                <div style="font-size: 12.5px; font-weight: 700; color: #64748b;">
                    <?php esc_html_e( 'RBAC & Compensation Management', 'ozone-skypool' ); ?>
                </div>
            </div>

            <div class="oz-table-wrap">
                <table class="oz-table" id="ozStaffDirectoryTable">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Operator Profile', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Username', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Role', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Salary & Payout', 'ozone-skypool' ); ?></th>
                            <th style="text-align: right;"><?php esc_html_e( 'Actions', 'ozone-skypool' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $staff_users ) ) : ?>
                            <?php foreach ( $staff_users as $u ) : 
                                $initial   = mb_strtoupper( mb_substr( $u->display_name, 0, 1 ) );
                                $role_name = in_array( 'administrator', $u->roles, true ) ? __( 'Facility Manager', 'ozone-skypool' ) : __( 'Cashier Operator', 'ozone-skypool' );
                                $sal       = isset( $salaries_by_user[ $u->ID ] ) ? $salaries_by_user[ $u->ID ] : null;
                                $base      = $sal ? (float) $sal->base_salary : 0.00;
                                $allow     = $sal ? (float) $sal->allowance : 0.00;
                                $payout    = $base + $allow;
                            ?>
                                <tr class="oz-staff-record-row">
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            <span class="oz-staff-avatar-sm"><?php echo esc_html( $initial ); ?></span>
                                            <div>
                                                <strong style="color: #0f172a; font-size: 14px;"><?php echo esc_html( $u->display_name ); ?></strong>
                                                <div style="font-size: 11.5px; color: #64748b;"><?php echo esc_html( $u->user_email ); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="ifs-pms-mono" style="font-weight: 700; color: #0284c7;">
                                        <?php echo esc_html( $u->user_login ); ?>
                                    </td>
                                    <td>
                                        <span class="ifs-pms-badge <?php echo in_array( 'administrator', $u->roles, true ) ? 'ifs-pms-badge-success' : 'ifs-pms-badge-warning'; ?>">
                                            <?php echo esc_html( $role_name ); ?>
                                        </span>
                                    </td>
                                    <td class="ifs-pms-mono">
                                        <?php if ( $sal ) : ?>
                                            <strong style="color: #10b981;"><?php echo esc_html( $currency . ' ' . number_format_i18n( $payout, 2 ) ); ?></strong>
                                            <div style="font-size: 11px; color: #64748b;"><?php echo esc_html( $sal->pay_frequency ); ?></div>
                                        <?php else : ?>
                                            <span style="color: #94a3b8; font-style: italic; font-size: 12.5px;"><?php esc_html_e( 'Not set', 'ozone-skypool' ); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <!-- View Button: Emerald Green Harmony -->
                                        <button type="button" class="oz-btn oz-btn-sm oz-btn-view" onclick='ozOpenViewStaffModal(<?php echo wp_json_encode( array(
                                            'name'  => $u->display_name,
                                            'login' => $u->user_login,
                                            'email' => $u->user_email,
                                            'role'  => $role_name,
                                            'base'  => $base,
                                            'allow' => $allow,
                                            'freq'  => $sal ? $sal->pay_frequency : 'Monthly',
                                        ) ); ?>)'>
                                            <i class="fa-solid fa-eye"></i> <?php esc_html_e( 'View', 'ozone-skypool' ); ?>
                                        </button>

                                        <!-- Edit Button: Sky Blue Harmony -->
                                        <button type="button" class="oz-btn oz-btn-sm oz-btn-edit" onclick='ozOpenEditStaffModal(<?php echo wp_json_encode( array(
                                            'id'        => $u->ID,
                                            'name'      => $u->display_name,
                                            'email'     => $u->user_email,
                                            'role'      => in_array( 'administrator', $u->roles, true ) ? 'administrator' : 'ozone_cashier',
                                            'base'      => $base,
                                            'allow'     => $allow,
                                            'freq'      => $sal ? $sal->pay_frequency : 'Monthly',
                                            'eff_date'  => $sal ? $sal->effective_date : current_time( 'Y-m-d' ),
                                        ) ); ?>)'>
                                            <i class="fa-solid fa-pen-to-square"></i> <?php esc_html_e( 'Edit', 'ozone-skypool' ); ?>
                                        </button>

                                        <!-- Delete Button: Rose Red Harmony -->
                                        <?php if ( $is_admin && $u->ID !== get_current_user_id() ) : ?>
                                            <form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=staff' ) ); ?>" style="display: inline-block; margin: 0;" onsubmit="return confirm(<?php echo wp_json_encode( __( 'Permanently revoke and delete this operator account?', 'ozone-skypool' ) ); ?>);">
                                                <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                                                <input type="hidden" name="ifs_pms_action" value="delete_staff">
                                                <input type="hidden" name="user_id" value="<?php echo esc_attr( $u->ID ); ?>">
                                                <button type="submit" class="oz-btn oz-btn-sm oz-btn-delete" title="<?php esc_attr_e( 'Revoke Account', 'ozone-skypool' ); ?>">
                                                    <i class="fa-solid fa-trash-can"></i> <?php esc_html_e( 'Delete', 'ozone-skypool' ); ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: View Staff Details -->
<div id="ozViewStaffModal" class="oz-modal-overlay">
    <div class="oz-modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: 17px; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-user-shield" style="color: var(--ifs-accent, #0284c7);"></i>
                <?php esc_html_e( 'Operator Profile & Compensation', 'ozone-skypool' ); ?>
            </h3>
            <button type="button" style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 18px; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center;" onclick="ozCloseViewStaffModal()">&times;</button>
        </div>

        <div style="display: flex; flex-direction: column; gap: 16px; font-size: 14px;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Display Name:', 'ozone-skypool' ); ?></span>
                <strong style="color: #0f172a;" id="ozViewStaffName">--</strong>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Username:', 'ozone-skypool' ); ?></span>
                <strong class="ifs-pms-mono" style="color: #0284c7;" id="ozViewStaffLogin">--</strong>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Email Address:', 'ozone-skypool' ); ?></span>
                <span class="ifs-pms-mono" style="color: #475569;" id="ozViewStaffEmail">--</span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Terminal Permission:', 'ozone-skypool' ); ?></span>
                <span id="ozViewStaffRoleBadge" class="ifs-pms-badge ifs-pms-badge-success">--</span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Base Salary:', 'ozone-skypool' ); ?></span>
                <span class="ifs-pms-mono" style="font-weight: 700;" id="ozViewStaffBase">--</span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Allowance:', 'ozone-skypool' ); ?></span>
                <span class="ifs-pms-mono" style="font-weight: 700;" id="ozViewStaffAllow">--</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Pay Frequency:', 'ozone-skypool' ); ?></span>
                <span class="ifs-pms-badge" id="ozViewStaffFreq">--</span>
            </div>
        </div>

        <div style="margin-top: 28px;">
            <button type="button" class="oz-btn oz-btn-secondary" style="width: 100%; height: 48px; border-radius: 12px;" onclick="ozCloseViewStaffModal()">
                <?php esc_html_e( 'Close Profile', 'ozone-skypool' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- MODAL: Edit Staff Account & Salary -->
<div id="ozEditStaffModal" class="oz-modal-overlay">
    <div class="oz-modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: 17px; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-pen-to-square" style="color: var(--ifs-accent, #0284c7);"></i>
                <?php esc_html_e( 'Edit Operator Profile & Compensation', 'ozone-skypool' ); ?>
            </h3>
            <button type="button" style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 18px; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center;" onclick="ozCloseEditStaffModal()">&times;</button>
        </div>

        <form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=staff&tab=list' ) ); ?>">
            <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
            <input type="hidden" name="ifs_pms_action" value="edit_staff">
            <input type="hidden" name="user_id" id="ozEditStaffId">

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php esc_html_e( 'Display Name', 'ozone-skypool' ); ?> *</label>
                        <input type="text" name="display_name" id="ozEditStaffName" required>
                    </div>
                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php esc_html_e( 'Email Address', 'ozone-skypool' ); ?> *</label>
                        <input type="email" name="user_email" id="ozEditStaffEmail" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php esc_html_e( 'Terminal Role', 'ozone-skypool' ); ?> *</label>
                        <select name="user_role" id="ozEditStaffRole">
                            <option value="ozone_cashier"><?php esc_html_e( 'Ozone Cashier', 'ozone-skypool' ); ?></option>
                            <option value="administrator"><?php esc_html_e( 'Facility Manager', 'ozone-skypool' ); ?></option>
                        </select>
                    </div>
                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php esc_html_e( 'New Password', 'ozone-skypool' ); ?></label>
                        <input type="password" name="user_pass" placeholder="Leave blank if unchanged">
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid #f1f5f9; margin: 4px 0;">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php esc_html_e( 'Base Salary', 'ozone-skypool' ); ?></label>
                        <input type="number" step="0.01" name="base_salary" id="ozEditStaffBase" class="ifs-pms-mono" required>
                    </div>
                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php esc_html_e( 'Allowance', 'ozone-skypool' ); ?></label>
                        <input type="number" step="0.01" name="allowance" id="ozEditStaffAllow" class="ifs-pms-mono" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php esc_html_e( 'Pay Frequency', 'ozone-skypool' ); ?></label>
                        <select name="pay_frequency" id="ozEditStaffFreq">
                            <option value="Monthly"><?php esc_html_e( 'Monthly', 'ozone-skypool' ); ?></option>
                            <option value="Weekly"><?php esc_html_e( 'Weekly', 'ozone-skypool' ); ?></option>
                            <option value="Daily"><?php esc_html_e( 'Daily Shift', 'ozone-skypool' ); ?></option>
                        </select>
                    </div>
                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php esc_html_e( 'Effective Date', 'ozone-skypool' ); ?></label>
                        <input type="date" name="effective_date" id="ozEditStaffDate" class="ifs-pms-mono" required>
                    </div>
                </div>

                <div style="display: flex; gap: 14px; margin-top: 14px;">
                    <button type="submit" class="oz-btn oz-btn-primary" style="flex: 2; height: 50px; border-radius: 14px; font-weight: 800; font-size: 14.5px;"><?php esc_html_e( 'Save Changes', 'ozone-skypool' ); ?></button>
                    <button type="button" class="oz-btn oz-btn-secondary" style="flex: 1; height: 50px; border-radius: 14px; font-weight: 700;" onclick="ozCloseEditStaffModal()"><?php esc_html_e( 'Cancel', 'ozone-skypool' ); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function ozSwitchStaffTab(tabKey, btn) {
    document.querySelectorAll('.oz-subnav-btn').forEach(b => b.classList.remove('active'));
    if(btn) btn.classList.add('active');

    document.getElementById('ozStaffPaneAdd').classList.remove('active');
    document.getElementById('ozStaffPaneList').classList.remove('active');

    if (tabKey === 'add') document.getElementById('ozStaffPaneAdd').classList.add('active');
    else if (tabKey === 'list') document.getElementById('ozStaffPaneList').classList.add('active');

    if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.set('tab', tabKey);
        window.history.replaceState({}, '', url);
    }
}

function ozOpenViewStaffModal(data) {
    document.getElementById('ozViewStaffName').textContent = data.name;
    document.getElementById('ozViewStaffLogin').textContent = data.login;
    document.getElementById('ozViewStaffEmail').textContent = data.email;
    document.getElementById('ozViewStaffRoleBadge').textContent = data.role;
    document.getElementById('ozViewStaffBase').textContent = data.base.toFixed(2) + ' <?php echo esc_js( $currency ); ?>';
    document.getElementById('ozViewStaffAllow').textContent = data.allow.toFixed(2) + ' <?php echo esc_js( $currency ); ?>';
    document.getElementById('ozViewStaffFreq').textContent = data.freq;
    document.getElementById('ozViewStaffModal').style.display = 'flex';
}
function ozCloseViewStaffModal() {
    document.getElementById('ozViewStaffModal').style.display = 'none';
}

function ozOpenEditStaffModal(data) {
    document.getElementById('ozEditStaffId').value = data.id;
    document.getElementById('ozEditStaffName').value = data.name;
    document.getElementById('ozEditStaffEmail').value = data.email;
    document.getElementById('ozEditStaffRole').value = data.role;
    document.getElementById('ozEditStaffBase').value = data.base.toFixed(2);
    document.getElementById('ozEditStaffAllow').value = data.allow.toFixed(2);
    document.getElementById('ozEditStaffFreq').value = data.freq;
    document.getElementById('ozEditStaffDate').value = data.eff_date;
    document.getElementById('ozEditStaffModal').style.display = 'flex';
}
function ozCloseEditStaffModal() {
    document.getElementById('ozEditStaffModal').style.display = 'none';
}

function ozFilterStaffDirectory() {
    const input = document.getElementById('ozStaffSearchInput');
    const filter = input ? input.value.toLowerCase().trim() : '';
    const rows = document.querySelectorAll('.oz-staff-record-row');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = (!filter || text.includes(filter)) ? '' : 'none';
    });
}
</script>