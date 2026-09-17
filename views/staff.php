<?php
/**
 * View: Staff Management & Unified Operator Salary Provisioning (Enterprise Edition v4 - Custom Avatar Upload & Zero Inline CSS)
 *
 * @package Ozone_Skypool_OS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$t_tick   = $wpdb->prefix . 'ifs_pms_tickets';
$t_salary = $wpdb->prefix . 'ifs_pms_salaries';
$currency = esc_html( (string) get_option( 'ifs_pms_currency', 'BDT' ) );
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
$salaries_raw     = $wpdb->get_results( "SELECT * FROM {$t_salary}", OBJECT_K );
$salaries_by_user = array();
foreach ( $salaries_raw as $sal ) {
    $salaries_by_user[ $sal->user_id ] = $sal;
}
?>

<style>
    /* ==========================================================================
       UNIFIED STAFF & SALARY PROVISIONING SUITE (ENTERPRISE UX v4)
       ========================================================================== */
    .ifs-pms-staff-wrapper {
        display: flex;
        flex-direction: column;
        gap: 28px;
        width: 100%;
        box-sizing: border-box;
    }

    /* Sub Navigation Tabs */
    .ifs-pms-subnav-bar {
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

    .ifs-pms-subnav-btn {
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
        color: #475569;
        border: none !important;
        outline: none;
        transition: all 0.25s ease;
        text-align: center;
        white-space: nowrap;
    }

    .ifs-pms-subnav-btn:hover { color: #0f172a; background: rgba(255, 255, 255, 0.5); }
    .ifs-pms-subnav-btn.active {
        background: #ffffff !important;
        color: #0284c7 !important;
        box-shadow: 0 8px 24px rgba(2, 132, 199, 0.15), 0 2px 6px rgba(0, 0, 0, 0.04);
        transform: translateY(-1px);
    }

    .ifs-pms-tab-pane { display: none; }
    .ifs-pms-tab-pane.active {
        display: block;
        animation: ifsPmsFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes ifsPmsFadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .ifs-pms-panel-card {
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 24px;
        box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.07);
        box-sizing: border-box;
        overflow: hidden;
    }

    .ifs-pms-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 24px 32px;
        border-bottom: 1px solid #f1f5f9;
        background: linear-gradient(to bottom, #fafbfd, #f8fafc);
    }

    .ifs-pms-panel-title {
        margin: 0;
        font-size: 17px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #0f172a;
    }

    .ifs-pms-form-box-centered { max-width: 720px; margin: 0 auto; }
    .ifs-pms-form-stack {
        padding: 36px;
        display: flex;
        flex-direction: column;
        gap: 22px;
        box-sizing: border-box;
    }

    .ifs-pms-field-group {
        display: flex;
        flex-direction: column;
        width: 100%;
        margin: 0;
    }

    .ifs-pms-field-label {
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    #wpcontent .ifs-pms-staff-wrapper input[type="text"],
    #wpcontent .ifs-pms-staff-wrapper input[type="email"],
    #wpcontent .ifs-pms-staff-wrapper input[type="password"],
    #wpcontent .ifs-pms-staff-wrapper input[type="number"],
    #wpcontent .ifs-pms-staff-wrapper input[type="date"],
    #wpcontent .ifs-pms-staff-wrapper select,
    #wpcontent .ifs-pms-modal-card input,
    #wpcontent .ifs-pms-modal-card select {
        display: block !important;
        width: 100% !important;
        background: #ffffff !important;
        border: 1.5px solid #cbd5e1 !important;
        border-radius: 14px !important;
        padding: 12px 18px !important;
        font-size: 14.5px !important;
        font-family: inherit !important;
        color: #0f172a !important;
        height: 50px !important;
        box-sizing: border-box !important;
        transition: all 0.25s ease !important;
    }

    #wpcontent .ifs-pms-staff-wrapper input:focus,
    #wpcontent .ifs-pms-staff-wrapper select:focus,
    #wpcontent .ifs-pms-modal-card input:focus,
    #wpcontent .ifs-pms-modal-card select:focus {
        border-color: #0284c7 !important;
        box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.12) !important;
        outline: none !important;
    }

    .ifs-pms-search-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 22px 32px;
        border-bottom: 1px solid #f1f5f9;
        gap: 16px;
        flex-wrap: wrap;
        background: #ffffff;
    }

    .ifs-pms-search-container {
        position: relative;
        flex: 1;
        max-width: 380px;
        min-width: 240px;
    }

    .ifs-pms-search-container i.search-icon {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 14px;
        pointer-events: none;
    }

    #wpcontent .ifs-pms-search-container input {
        padding-left: 44px !important;
        height: 46px !important;
        border-radius: 14px !important;
    }

    .ifs-pms-table-wrap { width: 100%; overflow-x: auto; }
    .ifs-pms-table { width: 100%; border-collapse: collapse; font-size: 14px; text-align: left; }
    .ifs-pms-table th {
        background: #f8fafc;
        color: #64748b;
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 18px 24px;
        border-bottom: 1px solid #e2e8f0;
    }
    .ifs-pms-table td {
        padding: 20px 24px;
        border-bottom: 1px solid #f1f5f9;
        color: #0f172a;
        vertical-align: middle;
    }
    .ifs-pms-table tr:hover td { background: #f8fafc; }

    /* High-End Profile Avatar System */
    .ifs-pms-staff-avatar-box {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        color: #ffffff;
        font-weight: 800;
        font-size: 17px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-right: 16px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
        border: 2px solid #ffffff;
        overflow: hidden;
    }

    .ifs-pms-staff-avatar-box img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Modal Overlay */
    .ifs-pms-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(8px);
        z-index: 99999;
        align-items: center;
        justify-content: center;
    }

    .ifs-pms-modal-card {
        background: #ffffff;
        border: 1.5px solid #cbd5e1;
        border-radius: 24px;
        width: 580px;
        max-width: 95vw;
        padding: 36px;
        box-sizing: border-box;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
        animation: ifsPmsModalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes ifsPmsModalPop {
        from { opacity: 0; transform: scale(0.95) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    /* Action Buttons */
    #wpcontent .ifs-pms-staff-wrapper .ifs-pms-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
        font-weight: 700 !important;
        cursor: pointer !important;
        box-sizing: border-box !important;
        text-decoration: none !important;
        transition: all 0.2s ease !important;
    }

    #wpcontent .ifs-pms-staff-wrapper .ifs-pms-btn-primary.ifs-pms-btn-lg {
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

    #wpcontent .ifs-pms-staff-wrapper .ifs-pms-btn-primary.ifs-pms-btn-lg:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 30px rgba(2, 132, 199, 0.55) !important;
    }

    .ifs-pms-table .ifs-pms-btn-sm {
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

    .ifs-pms-table .ifs-pms-btn-sm:hover { transform: translateY(-2px); }

    .ifs-pms-table .ifs-pms-btn-view { background: rgba(16, 185, 129, 0.08) !important; color: #059669 !important; border: 1.5px solid rgba(16, 185, 129, 0.25) !important; }
    .ifs-pms-table .ifs-pms-btn-view:hover { background: #10b981 !important; color: #ffffff !important; }

    .ifs-pms-table .ifs-pms-btn-edit { background: rgba(2, 132, 199, 0.08) !important; color: #0284c7 !important; border: 1.5px solid rgba(2, 132, 199, 0.25) !important; }
    .ifs-pms-table .ifs-pms-btn-edit:hover { background: #0284c7 !important; color: #ffffff !important; }

    .ifs-pms-table .ifs-pms-btn-delete { background: rgba(244, 63, 94, 0.08) !important; color: #f43f5e !important; border: 1.5px solid rgba(244, 63, 94, 0.25) !important; }
    .ifs-pms-table .ifs-pms-btn-delete:hover { background: #f43f5e !important; color: #ffffff !important; }

    .ifs-pms-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }

    .ifs-pms-flex-gap-14 {
        display: flex;
        gap: 14px;
        margin-top: 14px;
    }
</style>

<div class="ifs-pms-staff-wrapper">
    <!-- Sub Navigation Tab Bar -->
    <div class="ifs-pms-subnav-bar" role="tablist">
        <button type="button" class="ifs-pms-subnav-btn <?php echo ( $active_tab === 'add' ) ? 'active' : ''; ?>" onclick="ifsPmsSwitchStaffTab('add', this)">
            <i class="fa-solid fa-user-plus"></i> <?php esc_html_e( 'Add Staff & Salary', 'ozone-skypool' ); ?>
        </button>
        <button type="button" class="ifs-pms-subnav-btn <?php echo ( $active_tab === 'list' ) ? 'active' : ''; ?>" onclick="ifsPmsSwitchStaffTab('list', this)">
            <i class="fa-solid fa-users"></i> <?php esc_html_e( 'All Staff Directory', 'ozone-skypool' ); ?>
        </button>
    </div>

    <!-- TAB 1: Add Staff & Salary Terminal -->
    <div id="ifsPmsStaffPaneAdd" class="ifs-pms-tab-pane <?php echo ( $active_tab === 'add' ) ? 'active' : ''; ?>">
        <div class="ifs-pms-form-box-centered">
            <div class="ifs-pms-panel-card">
                <div class="ifs-pms-panel-head">
                    <h3 class="ifs-pms-panel-title">
                        <i class="fa-solid fa-user-plus" style="color: #0284c7;"></i>
                        <?php esc_html_e( 'Provision Operator & Compensation', 'ozone-skypool' ); ?>
                    </h3>
                    <span class="ifs-pms-badge ifs-pms-badge-success"><?php esc_html_e( 'RBAC & Payroll', 'ozone-skypool' ); ?></span>
                </div>

                <form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=staff' ) ); ?>">
                    <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                    <input type="hidden" name="ifs_pms_action" value="create_staff">

                    <div class="ifs-pms-form-stack">
                        <div class="ifs-pms-grid-2">
                            <div class="ifs-pms-field-group">
                                <label class="ifs-pms-field-label"><?php esc_html_e( 'Username', 'ozone-skypool' ); ?> *</label>
                                <input type="text" name="user_login" required placeholder="e.g. cashier_tanvir" autocomplete="off">
                            </div>
                            <div class="ifs-pms-field-group">
                                <label class="ifs-pms-field-label"><?php esc_html_e( 'Display / Full Name', 'ozone-skypool' ); ?> *</label>
                                <input type="text" name="display_name" required placeholder="e.g. Tanvir Ahmed" autocomplete="off">
                            </div>
                        </div>

                        <div class="ifs-pms-grid-2">
                            <div class="ifs-pms-field-group">
                                <label class="ifs-pms-field-label"><?php esc_html_e( 'Email Address', 'ozone-skypool' ); ?> *</label>
                                <input type="email" name="user_email" required placeholder="cashier@ozoneskypool.com" autocomplete="off">
                            </div>
                            <div class="ifs-pms-field-group">
                                <label class="ifs-pms-field-label"><?php esc_html_e( 'Secure Password', 'ozone-skypool' ); ?> *</label>
                                <input type="password" name="user_pass" required placeholder="••••••••••••" autocomplete="new-password">
                            </div>
                        </div>

                        <div class="ifs-pms-field-group">
                            <label class="ifs-pms-field-label"><?php esc_html_e( 'Assigned Terminal Role', 'ozone-skypool' ); ?> *</label>
                            <select name="user_role">
                                <option value="ozone_cashier"><?php esc_html_e( 'Ozone Cashier (POS, Scanner & Members)', 'ozone-skypool' ); ?></option>
                                <option value="administrator"><?php esc_html_e( 'Administrator / Facility Manager (Full Access)', 'ozone-skypool' ); ?></option>
                            </select>
                        </div>

                        <div class="ifs-pms-field-group">
                            <label class="ifs-pms-field-label"><?php esc_html_e( 'Custom Avatar Image URL (Optional)', 'ozone-skypool' ); ?></label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" name="user_avatar_url" id="ifsPmsCreateAvatarUrl" placeholder="https://... (Leave blank for Gravatar)" autocomplete="off">
                                <button type="button" class="ifs-pms-btn ifs-pms-btn-sm" style="background: #f1f5f9; color: #475569; border: 1.5px solid #cbd5e1; height: 50px !important; border-radius: 14px; padding: 0 16px !important;" onclick="ifsPmsOpenMediaUploaderForAdd()">
                                    <i class="fa-solid fa-image"></i> <?php esc_html_e( 'Media Library', 'ozone-skypool' ); ?>
                                </button>
                            </div>
                        </div>

                        <hr style="border: 0; border-top: 1px solid #f1f5f9; margin: 6px 0;">

                        <div class="ifs-pms-grid-2">
                            <div class="ifs-pms-field-group">
                                <label class="ifs-pms-field-label"><?php printf( esc_html__( 'Base Salary (%s)', 'ozone-skypool' ), esc_html( $currency ) ); ?></label>
                                <input type="number" step="0.01" name="base_salary" class="ifs-pms-mono" placeholder="25000.00" value="0.00">
                            </div>
                            <div class="ifs-pms-field-group">
                                <label class="ifs-pms-field-label"><?php printf( esc_html__( 'Allowance (%s)', 'ozone-skypool' ), esc_html( $currency ) ); ?></label>
                                <input type="number" step="0.01" name="allowance" class="ifs-pms-mono" placeholder="3000.00" value="0.00">
                            </div>
                        </div>

                        <div class="ifs-pms-grid-2">
                            <div class="ifs-pms-field-group">
                                <label class="ifs-pms-field-label"><?php esc_html_e( 'Pay Frequency', 'ozone-skypool' ); ?></label>
                                <select name="pay_frequency">
                                    <option value="Monthly"><?php esc_html_e( 'Monthly', 'ozone-skypool' ); ?></option>
                                    <option value="Weekly"><?php esc_html_e( 'Weekly', 'ozone-skypool' ); ?></option>
                                    <option value="Daily"><?php esc_html_e( 'Daily Shift', 'ozone-skypool' ); ?></option>
                                </select>
                            </div>
                            <div class="ifs-pms-field-group">
                                <label class="ifs-pms-field-label"><?php esc_html_e( 'Effective Date', 'ozone-skypool' ); ?></label>
                                <input type="date" name="effective_date" class="ifs-pms-mono" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
                            </div>
                        </div>

                        <div style="margin-top: 10px;">
                            <button type="submit" class="ifs-pms-btn ifs-pms-btn-primary ifs-pms-btn-lg" style="width: 100%;">
                                <i class="fa-solid fa-user-check"></i> <?php esc_html_e( 'Create Operator & Compensation', 'ozone-skypool' ); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- TAB 2: All Staff Directory -->
    <div id="ifsPmsStaffPaneList" class="ifs-pms-tab-pane <?php echo ( $active_tab === 'list' ) ? 'active' : ''; ?>">
        <div class="ifs-pms-panel-card">
            <div class="ifs-pms-panel-head">
                <h3 class="ifs-pms-panel-title">
                    <i class="fa-solid fa-user-shield" style="color: #475569;"></i>
                    <?php esc_html_e( 'Active Operators Directory & Payroll Ledger', 'ozone-skypool' ); ?>
                </h3>
                <span class="ifs-pms-badge" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-weight: 700; padding: 6px 12px; border-radius: 8px;">
                    <?php echo count( $staff_users ); ?> <?php esc_html_e( 'Registered Operators', 'ozone-skypool' ); ?>
                </span>
            </div>

            <div class="ifs-pms-search-bar">
                <div class="ifs-pms-search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="ifsPmsStaffSearchInput" placeholder="<?php esc_attr_e( 'Filter by name, username, or email...', 'ozone-skypool' ); ?>" oninput="ifsPmsFilterStaffDirectory()" autocomplete="off">
                </div>
                <div style="font-size: 12.5px; font-weight: 700; color: #64748b;">
                    <?php esc_html_e( 'RBAC & Compensation Management', 'ozone-skypool' ); ?>
                </div>
            </div>

            <div class="ifs-pms-table-wrap">
                <table class="ifs-pms-table" id="ifsPmsStaffDirectoryTable">
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
                                $custom_avatar = get_user_meta( $u->ID, 'ifs_pms_custom_avatar', true );
                                $avatar_url    = ! empty( $custom_avatar ) ? $custom_avatar : get_avatar_url( $u->ID, array( 'size' => 96 ) );
                                $initial       = mb_strtoupper( mb_substr( $u->display_name, 0, 1 ) );
                                $role_name     = in_array( 'administrator', $u->roles, true ) ? __( 'Facility Manager', 'ozone-skypool' ) : __( 'Cashier Operator', 'ozone-skypool' );
                                $sal           = isset( $salaries_by_user[ $u->ID ] ) ? $salaries_by_user[ $u->ID ] : null;
                                $base          = $sal ? (float) $sal->base_salary : 0.00;
                                $allow         = $sal ? (float) $sal->allowance : 0.00;
                                $payout        = $base + $allow;
                            ?>
                                <tr class="ifs-pms-staff-record-row">
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            <div class="ifs-pms-staff-avatar-box">
                                                <?php if ( ! empty( $avatar_url ) ) : ?>
                                                    <img src="<?php echo esc_url( $avatar_url ); ?>" alt="Avatar">
                                                <?php else : ?>
                                                    <?php echo esc_html( $initial ); ?>
                                                <?php endif; ?>
                                            </div>
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
                                        <button type="button" class="ifs-pms-btn ifs-pms-btn-sm ifs-pms-btn-view" onclick='ifsPmsOpenViewStaffModal(<?php echo wp_json_encode( array(
                                            'name'   => $u->display_name,
                                            'login'  => $u->user_login,
                                            'email'  => $u->user_email,
                                            'role'   => $role_name,
                                            'avatar' => $avatar_url,
                                            'base'   => $base,
                                            'allow'  => $allow,
                                            'freq'   => $sal ? $sal->pay_frequency : 'Monthly',
                                        ) ); ?>)'>
                                            <i class="fa-solid fa-eye"></i> <?php esc_html_e( 'View', 'ozone-skypool' ); ?>
                                        </button>

                                        <button type="button" class="ifs-pms-btn ifs-pms-btn-sm ifs-pms-btn-edit" onclick='ifsPmsOpenEditStaffModal(<?php echo wp_json_encode( array(
                                            'id'       => $u->ID,
                                            'name'     => $u->display_name,
                                            'email'    => $u->user_email,
                                            'role'     => in_array( 'administrator', $u->roles, true ) ? 'administrator' : 'ozone_cashier',
                                            'avatar'   => $custom_avatar,
                                            'base'     => $base,
                                            'allow'    => $allow,
                                            'freq'     => $sal ? $sal->pay_frequency : 'Monthly',
                                            'eff_date' => $sal ? $sal->effective_date : current_time( 'Y-m-d' ),
                                        ) ); ?>)'>
                                            <i class="fa-solid fa-pen-to-square"></i> <?php esc_html_e( 'Edit', 'ozone-skypool' ); ?>
                                        </button>

                                        <?php if ( $is_admin && $u->ID !== get_current_user_id() ) : ?>
                                            <form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=staff' ) ); ?>" style="display: inline-block; margin: 0;" onsubmit="return confirm(<?php echo wp_json_encode( __( 'Permanently revoke and delete this operator account?', 'ozone-skypool' ) ); ?>);">
                                                <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                                                <input type="hidden" name="ifs_pms_action" value="delete_staff">
                                                <input type="hidden" name="user_id" value="<?php echo esc_attr( $u->ID ); ?>">
                                                <button type="submit" class="ifs-pms-btn ifs-pms-btn-sm ifs-pms-btn-delete" title="<?php esc_attr_e( 'Revoke Account', 'ozone-skypool' ); ?>">
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
<div id="ifsPmsViewStaffModal" class="ifs-pms-modal-overlay">
    <div class="ifs-pms-modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: 17px; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-user-shield" style="color: #0284c7;"></i>
                <?php esc_html_e( 'Operator Profile & Compensation', 'ozone-skypool' ); ?>
            </h3>
            <button type="button" style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 18px; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center;" onclick="ifsPmsCloseViewStaffModal()">&times;</button>
        </div>

        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 20px; background: #f8fafc; padding: 16px; border-radius: 16px; border: 1px solid #e2e8f0;">
            <div class="ifs-pms-staff-avatar-box" style="width: 56px; height: 56px; font-size: 20px;" id="ifsPmsViewAvatarBox">--</div>
            <div>
                <strong style="font-size: 16px; color: #0f172a;" id="ifsPmsViewStaffName">--</strong>
                <div style="font-size: 12.5px; color: #64748b;" id="ifsPmsViewStaffEmail">--</div>
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 16px; font-size: 14px;">
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Username:', 'ozone-skypool' ); ?></span>
                <strong class="ifs-pms-mono" style="color: #0284c7;" id="ifsPmsViewStaffLogin">--</strong>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Terminal Permission:', 'ozone-skypool' ); ?></span>
                <span id="ifsPmsViewStaffRoleBadge" class="ifs-pms-badge ifs-pms-badge-success">--</span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Base Salary:', 'ozone-skypool' ); ?></span>
                <span class="ifs-pms-mono" style="font-weight: 700;" id="ifsPmsViewStaffBase">--</span>
            </div>
            <div style="display: flex; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Allowance:', 'ozone-skypool' ); ?></span>
                <span class="ifs-pms-mono" style="font-weight: 700;" id="ifsPmsViewStaffAllow">--</span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span style="color: #64748b; font-weight: 600;"><?php esc_html_e( 'Pay Frequency:', 'ozone-skypool' ); ?></span>
                <span class="ifs-pms-badge" id="ifsPmsViewStaffFreq">--</span>
            </div>
        </div>

        <div style="margin-top: 28px;">
            <button type="button" class="ifs-pms-btn ifs-pms-btn-sm" style="width: 100%; height: 48px; border-radius: 12px; background: #f1f5f9; color: #475569; border: 1.5px solid #cbd5e1;" onclick="ifsPmsCloseViewStaffModal()">
                <?php esc_html_e( 'Close Profile', 'ozone-skypool' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- MODAL: Edit Staff Account & Salary -->
<div id="ifsPmsEditStaffModal" class="ifs-pms-modal-overlay">
    <div class="ifs-pms-modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: 17px; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-pen-to-square" style="color: #0284c7;"></i>
                <?php esc_html_e( 'Edit Operator Profile & Compensation', 'ozone-skypool' ); ?>
            </h3>
            <button type="button" style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 18px; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center;" onclick="ifsPmsCloseEditStaffModal()">&times;</button>
        </div>

        <form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=staff&tab=list' ) ); ?>">
            <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
            <input type="hidden" name="ifs_pms_action" value="edit_staff">
            <input type="hidden" name="user_id" id="ifsPmsEditStaffId">

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div class="ifs-pms-grid-2">
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Display Name', 'ozone-skypool' ); ?> *</label>
                        <input type="text" name="display_name" id="ifsPmsEditStaffName" required>
                    </div>
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Email Address', 'ozone-skypool' ); ?> *</label>
                        <input type="email" name="user_email" id="ifsPmsEditStaffEmail" required>
                    </div>
                </div>

                <div class="ifs-pms-grid-2">
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Terminal Role', 'ozone-skypool' ); ?> *</label>
                        <select name="user_role" id="ifsPmsEditStaffRole">
                            <option value="ozone_cashier"><?php esc_html_e( 'Ozone Cashier', 'ozone-skypool' ); ?></option>
                            <option value="administrator"><?php esc_html_e( 'Facility Manager', 'ozone-skypool' ); ?></option>
                        </select>
                    </div>
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'New Password', 'ozone-skypool' ); ?></label>
                        <input type="password" name="user_pass" placeholder="Leave blank if unchanged">
                    </div>
                </div>

                <div class="ifs-pms-field-group">
                    <label class="ifs-pms-field-label"><?php esc_html_e( 'Custom Avatar Image URL', 'ozone-skypool' ); ?></label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" name="user_avatar_url" id="ifsPmsEditAvatarUrl" placeholder="https://..." autocomplete="off">
                        <button type="button" class="ifs-pms-btn ifs-pms-btn-sm" style="background: #f1f5f9; color: #475569; border: 1.5px solid #cbd5e1; height: 50px !important; border-radius: 14px; padding: 0 16px !important;" onclick="ifsPmsOpenMediaUploaderForEdit()">
                            <i class="fa-solid fa-image"></i> <?php esc_html_e( 'Media Library', 'ozone-skypool' ); ?>
                        </button>
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid #f1f5f9; margin: 4px 0;">

                <div class="ifs-pms-grid-2">
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Base Salary', 'ozone-skypool' ); ?></label>
                        <input type="number" step="0.01" name="base_salary" id="ifsPmsEditStaffBase" class="ifs-pms-mono" required>
                    </div>
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Allowance', 'ozone-skypool' ); ?></label>
                        <input type="number" step="0.01" name="allowance" id="ifsPmsEditStaffAllow" class="ifs-pms-mono" required>
                    </div>
                </div>

                <div class="ifs-pms-grid-2">
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Pay Frequency', 'ozone-skypool' ); ?></label>
                        <select name="pay_frequency" id="ifsPmsEditStaffFreq">
                            <option value="Monthly"><?php esc_html_e( 'Monthly', 'ozone-skypool' ); ?></option>
                            <option value="Weekly"><?php esc_html_e( 'Weekly', 'ozone-skypool' ); ?></option>
                            <option value="Daily"><?php esc_html_e( 'Daily Shift', 'ozone-skypool' ); ?></option>
                        </select>
                    </div>
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Effective Date', 'ozone-skypool' ); ?></label>
                        <input type="date" name="effective_date" id="ifsPmsEditStaffDate" class="ifs-pms-mono" required>
                    </div>
                </div>

                <div class="ifs-pms-flex-gap-14">
                    <button type="submit" class="ifs-pms-btn ifs-pms-btn-primary" style="flex: 2; height: 50px; border-radius: 14px; font-weight: 800; font-size: 14.5px;"><?php esc_html_e( 'Save Changes', 'ozone-skypool' ); ?></button>
                    <button type="button" class="ifs-pms-btn ifs-pms-btn-sm" style="flex: 1; height: 50px; border-radius: 14px; font-weight: 700; background: #f1f5f9; color: #475569; border: 1.5px solid #cbd5e1;" onclick="ifsPmsCloseEditStaffModal()"><?php esc_html_e( 'Cancel', 'ozone-skypool' ); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function ifsPmsSwitchStaffTab(tabKey, btn) {
    document.querySelectorAll('.ifs-pms-subnav-btn').forEach(b => b.classList.remove('active'));
    if(btn) btn.classList.add('active');

    document.getElementById('ifsPmsStaffPaneAdd').classList.remove('active');
    document.getElementById('ifsPmsStaffPaneList').classList.remove('active');

    if (tabKey === 'add') document.getElementById('ifsPmsStaffPaneAdd').classList.add('active');
    else if (tabKey === 'list') document.getElementById('ifsPmsStaffPaneList').classList.add('active');

    if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.set('tab', tabKey);
        window.history.replaceState({}, '', url);
    }
}

function ifsPmsOpenViewStaffModal(data) {
    document.getElementById('ifsPmsViewStaffName').textContent = data.name;
    document.getElementById('ifsPmsViewStaffLogin').textContent = data.login;
    document.getElementById('ifsPmsViewStaffEmail').textContent = data.email;
    document.getElementById('ifsPmsViewStaffRoleBadge').textContent = data.role;
    document.getElementById('ifsPmsViewStaffBase').textContent = data.base.toFixed(2) + ' <?php echo esc_js( $currency ); ?>';
    document.getElementById('ifsPmsViewStaffAllow').textContent = data.allow.toFixed(2) + ' <?php echo esc_js( $currency ); ?>';
    document.getElementById('ifsPmsViewStaffFreq').textContent = data.freq;

    const avatarBox = document.getElementById('ifsPmsViewAvatarBox');
    if (avatarBox) {
        if (data.avatar) {
            avatarBox.innerHTML = '<img src="' + data.avatar + '" alt="Avatar">';
        } else {
            avatarBox.textContent = data.name.charAt(0).toUpperCase();
        }
    }

    document.getElementById('ifsPmsViewStaffModal').style.display = 'flex';
}
function ifsPmsCloseViewStaffModal() {
    document.getElementById('ifsPmsViewStaffModal').style.display = 'none';
}

function ifsPmsOpenEditStaffModal(data) {
    document.getElementById('ifsPmsEditStaffId').value = data.id;
    document.getElementById('ifsPmsEditStaffName').value = data.name;
    document.getElementById('ifsPmsEditStaffEmail').value = data.email;
    document.getElementById('ifsPmsEditStaffRole').value = data.role;
    document.getElementById('ifsPmsEditAvatarUrl').value = data.avatar || '';
    document.getElementById('ifsPmsEditStaffBase').value = data.base.toFixed(2);
    document.getElementById('ifsPmsEditStaffAllow').value = data.allow.toFixed(2);
    document.getElementById('ifsPmsEditStaffFreq').value = data.freq;
    document.getElementById('ifsPmsEditStaffDate').value = data.eff_date;
    document.getElementById('ifsPmsEditStaffModal').style.display = 'flex';
}
function ifsPmsCloseEditStaffModal() {
    document.getElementById('ifsPmsEditStaffModal').style.display = 'none';
}

function ifsPmsOpenMediaUploaderForAdd() {
    if (typeof window.wp === 'undefined' || !window.wp.media) {
        alert('WordPress Media Uploader is unavailable.');
        return;
    }
    const uploader = window.wp.media({ title: 'Select Avatar', button: { text: 'Use this image' }, multiple: false });
    uploader.on('select', function() {
        const attachment = uploader.state().get('selection').first().toJSON();
        const field = document.getElementById('ifsPmsCreateAvatarUrl');
        if (field && attachment && attachment.url) field.value = attachment.url;
    });
    uploader.open();
}

function ifsPmsOpenMediaUploaderForEdit() {
    if (typeof window.wp === 'undefined' || !window.wp.media) {
        alert('WordPress Media Uploader is unavailable.');
        return;
    }
    const uploader = window.wp.media({ title: 'Select Avatar', button: { text: 'Use this image' }, multiple: false });
    uploader.on('select', function() {
        const attachment = uploader.state().get('selection').first().toJSON();
        const field = document.getElementById('ifsPmsEditAvatarUrl');
        if (field && attachment && attachment.url) field.value = attachment.url;
    });
    uploader.open();
}

function ifsPmsFilterStaffDirectory() {
    const input = document.getElementById('ifsPmsStaffSearchInput');
    const filter = input ? input.value.toLowerCase().trim() : '';
    const rows = document.querySelectorAll('.ifs-pms-staff-record-row');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = (!filter || text.includes(filter)) ? '' : 'none';
    });
}
</script>