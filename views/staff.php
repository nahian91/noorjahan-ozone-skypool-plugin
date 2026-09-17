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

<div class="ifs-pms-staff-wrapper">
    <!-- Sub Navigation Tab Bar -->
    <div class="ifs-pms-subnav-bar" role="tablist">
        <button type="button" class="ifs-pms-subnav-btn <?php echo ( $active_tab === 'add' ) ? 'active' : ''; ?>" onclick="ifsPmsSwitchStaffTab('add', this)">
            <i class="fa-solid fa-user-plus"></i> <?php esc_html_e( 'Add Staff & Salary', 'swimming-pool-manager' ); ?>
        </button>
        <button type="button" class="ifs-pms-subnav-btn <?php echo ( $active_tab === 'list' ) ? 'active' : ''; ?>" onclick="ifsPmsSwitchStaffTab('list', this)">
            <i class="fa-solid fa-users"></i> <?php esc_html_e( 'All Staff Directory', 'swimming-pool-manager' ); ?>
        </button>
    </div>

    <!-- TAB 1: Add Staff & Salary Terminal -->
    <div id="ifsPmsStaffPaneAdd" class="ifs-pms-tab-pane <?php echo ( $active_tab === 'add' ) ? 'active' : ''; ?>">
        <div class="ifs-pms-form-box-centered">
            <div class="ifs-pms-panel-card">
                <div class="ifs-pms-panel-head">
                    <h3 class="ifs-pms-panel-title">
                        <i class="fa-solid fa-user-plus ifs-pms-icon-primary"></i>
                        <?php esc_html_e( 'Provision Operator & Compensation', 'swimming-pool-manager' ); ?>
                    </h3>
                    <span class="ifs-pms-badge ifs-pms-badge-success"><?php esc_html_e( 'RBAC & Payroll', 'swimming-pool-manager' ); ?></span>
                </div>

                <form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=staff' ) ); ?>">
                    <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                    <input type="hidden" name="ifs_pms_action" value="create_staff">

                    <div class="ifs-pms-form-stack">
                        <div class="ifs-pms-grid-2">
                            <div class="ifs-pms-field-group">
                                <label class="ifs-pms-field-label"><?php esc_html_e( 'Username', 'swimming-pool-manager' ); ?> *</label>
                                <input type="text" name="user_login" required placeholder="e.g. cashier_tanvir" autocomplete="off">
                            </div>
                            <div class="ifs-pms-field-group">
                                <label class="ifs-pms-field-label"><?php esc_html_e( 'Display / Full Name', 'swimming-pool-manager' ); ?> *</label>
                                <input type="text" name="display_name" required placeholder="e.g. Tanvir Ahmed" autocomplete="off">
                            </div>
                        </div>

                        <div class="ifs-pms-grid-2">
                            <div class="ifs-pms-field-group">
                                <label class="ifs-pms-field-label"><?php esc_html_e( 'Email Address', 'swimming-pool-manager' ); ?> *</label>
                                <input type="email" name="user_email" required placeholder="cashier@ozoneskypool.com" autocomplete="off">
                            </div>
                            <div class="ifs-pms-field-group">
                                <label class="ifs-pms-field-label"><?php esc_html_e( 'Secure Password', 'swimming-pool-manager' ); ?> *</label>
                                <input type="password" name="user_pass" required placeholder="••••••••••••" autocomplete="new-password">
                            </div>
                        </div>

                        <div class="ifs-pms-field-group">
                            <label class="ifs-pms-field-label"><?php esc_html_e( 'Assigned Terminal Role', 'swimming-pool-manager' ); ?> *</label>
                            <select name="user_role">
                                <option value="ozone_cashier"><?php esc_html_e( 'Ozone Cashier (POS, Scanner & Members)', 'swimming-pool-manager' ); ?></option>
                                <option value="administrator"><?php esc_html_e( 'Administrator / Facility Manager (Full Access)', 'swimming-pool-manager' ); ?></option>
                            </select>
                        </div>

                        <div class="ifs-pms-field-group">
                            <label class="ifs-pms-field-label"><?php esc_html_e( 'Custom Avatar Image URL (Optional)', 'swimming-pool-manager' ); ?></label>
                            <div class="ifs-pms-avatar-upload-group">
                                <input type="text" name="user_avatar_url" id="ifsPmsCreateAvatarUrl" placeholder="https://... (Leave blank for Gravatar)" autocomplete="off">
                                <button type="button" class="ifs-pms-btn ifs-pms-btn-sm ifs-pms-btn-media-action" onclick="ifsPmsOpenMediaUploaderForAdd()">
                                    <i class="fa-solid fa-image"></i> <?php esc_html_e( 'Media Library', 'swimming-pool-manager' ); ?>
                                </button>
                            </div>
                        </div>

                        <hr class="ifs-pms-separator">

                        <div class="ifs-pms-grid-2">
                            <div class="ifs-pms-field-group">
                                <label class="ifs-pms-field-label"><?php printf( esc_html__( 'Base Salary (%s)', 'swimming-pool-manager' ), esc_html( $currency ) ); ?></label>
                                <input type="number" step="0.01" name="base_salary" class="ifs-pms-mono" placeholder="25000.00" value="0.00">
                            </div>
                            <div class="ifs-pms-field-group">
                                <label class="ifs-pms-field-label"><?php printf( esc_html__( 'Allowance (%s)', 'swimming-pool-manager' ), esc_html( $currency ) ); ?></label>
                                <input type="number" step="0.01" name="allowance" class="ifs-pms-mono" placeholder="3000.00" value="0.00">
                            </div>
                        </div>

                        <div class="ifs-pms-grid-2">
                            <div class="ifs-pms-field-group">
                                <label class="ifs-pms-field-label"><?php esc_html_e( 'Pay Frequency', 'swimming-pool-manager' ); ?></label>
                                <select name="pay_frequency">
                                    <option value="Monthly"><?php esc_html_e( 'Monthly', 'swimming-pool-manager' ); ?></option>
                                    <option value="Weekly"><?php esc_html_e( 'Weekly', 'swimming-pool-manager' ); ?></option>
                                    <option value="Daily"><?php esc_html_e( 'Daily Shift', 'swimming-pool-manager' ); ?></option>
                                </select>
                            </div>
                            <div class="ifs-pms-field-group">
                                <label class="ifs-pms-field-label"><?php esc_html_e( 'Effective Date', 'swimming-pool-manager' ); ?></label>
                                <input type="date" name="effective_date" class="ifs-pms-mono" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
                            </div>
                        </div>

                        <div class="ifs-pms-submit-wrap">
                            <button type="submit" class="ifs-pms-btn ifs-pms-btn-primary ifs-pms-btn-lg ifs-pms-btn-submit-block">
                                <i class="fa-solid fa-user-check"></i> <?php esc_html_e( 'Create Operator & Compensation', 'swimming-pool-manager' ); ?>
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
                    <i class="fa-solid fa-user-shield ifs-pms-icon-muted"></i>
                    <?php esc_html_e( 'Active Operators Directory & Payroll Ledger', 'swimming-pool-manager' ); ?>
                </h3>
                <span class="ifs-pms-badge ifs-pms-badge-neutral">
                    <?php echo count( $staff_users ); ?> <?php esc_html_e( 'Registered Operators', 'swimming-pool-manager' ); ?>
                </span>
            </div>

            <div class="ifs-pms-search-bar">
                <div class="ifs-pms-search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="ifsPmsStaffSearchInput" placeholder="<?php esc_attr_e( 'Filter by name, username, or email...', 'swimming-pool-manager' ); ?>" oninput="ifsPmsFilterStaffDirectory()" autocomplete="off">
                </div>
                <div class="ifs-pms-directory-subtext">
                    <?php esc_html_e( 'RBAC & Compensation Management', 'swimming-pool-manager' ); ?>
                </div>
            </div>

            <div class="ifs-pms-table-wrap">
                <table class="ifs-pms-table" id="ifsPmsStaffDirectoryTable">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Operator Profile', 'swimming-pool-manager' ); ?></th>
                            <th><?php esc_html_e( 'Username', 'swimming-pool-manager' ); ?></th>
                            <th><?php esc_html_e( 'Role', 'swimming-pool-manager' ); ?></th>
                            <th><?php esc_html_e( 'Salary & Payout', 'swimming-pool-manager' ); ?></th>
                            <th class="ifs-pms-th-actions"><?php esc_html_e( 'Actions', 'swimming-pool-manager' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $staff_users ) ) : ?>
                            <?php foreach ( $staff_users as $u ) : 
                                $custom_avatar = get_user_meta( $u->ID, 'ifs_pms_custom_avatar', true );
                                $avatar_url    = ! empty( $custom_avatar ) ? $custom_avatar : get_avatar_url( $u->ID, array( 'size' => 96 ) );
                                $initial       = mb_strtoupper( mb_substr( $u->display_name, 0, 1 ) );
                                $role_name     = in_array( 'administrator', $u->roles, true ) ? __( 'Facility Manager', 'swimming-pool-manager' ) : __( 'Cashier Operator', 'swimming-pool-manager' );
                                $sal           = isset( $salaries_by_user[ $u->ID ] ) ? $salaries_by_user[ $u->ID ] : null;
                                $base          = $sal ? (float) $sal->base_salary : 0.00;
                                $allow         = $sal ? (float) $sal->allowance : 0.00;
                                $payout        = $base + $allow;
                            ?>
                                <tr class="ifs-pms-staff-record-row">
                                    <td>
                                        <div class="ifs-pms-flex-center">
                                            <div class="ifs-pms-staff-avatar-box">
                                                <?php if ( ! empty( $avatar_url ) ) : ?>
                                                    <img src="<?php echo esc_url( $avatar_url ); ?>" alt="Avatar">
                                                <?php else : ?>
                                                    <?php echo esc_html( $initial ); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <strong class="ifs-pms-text-user-name"><?php echo esc_html( $u->display_name ); ?></strong>
                                                <div class="ifs-pms-text-user-email"><?php echo esc_html( $u->user_email ); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="ifs-pms-mono ifs-pms-text-username">
                                        <?php echo esc_html( $u->user_login ); ?>
                                    </td>
                                    <td>
                                        <span class="ifs-pms-badge <?php echo in_array( 'administrator', $u->roles, true ) ? 'ifs-pms-badge-success' : 'ifs-pms-badge-warning'; ?>">
                                            <?php echo esc_html( $role_name ); ?>
                                        </span>
                                    </td>
                                    <td class="ifs-pms-mono">
                                        <?php if ( $sal ) : ?>
                                            <strong class="ifs-pms-text-payout"><?php echo esc_html( $currency . ' ' . number_format_i18n( $payout, 2 ) ); ?></strong>
                                            <div class="ifs-pms-text-frequency"><?php echo esc_html( $sal->pay_frequency ); ?></div>
                                        <?php else : ?>
                                            <span class="ifs-pms-text-unset"><?php esc_html_e( 'Not set', 'swimming-pool-manager' ); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="ifs-pms-td-actions">
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
                                            <i class="fa-solid fa-eye"></i> <?php esc_html_e( 'View', 'swimming-pool-manager' ); ?>
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
                                            <i class="fa-solid fa-pen-to-square"></i> <?php esc_html_e( 'Edit', 'swimming-pool-manager' ); ?>
                                        </button>

                                        <?php if ( $is_admin && $u->ID !== get_current_user_id() ) : ?>
                                            <form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=staff' ) ); ?>" class="ifs-pms-inline-form" onsubmit="return confirm(<?php echo wp_json_encode( __( 'Permanently revoke and delete this operator account?', 'swimming-pool-manager' ) ); ?>);">
                                                <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                                                <input type="hidden" name="ifs_pms_action" value="delete_staff">
                                                <input type="hidden" name="user_id" value="<?php echo esc_attr( $u->ID ); ?>">
                                                <button type="submit" class="ifs-pms-btn ifs-pms-btn-sm ifs-pms-btn-delete" title="<?php esc_attr_e( 'Revoke Account', 'swimming-pool-manager' ); ?>">
                                                    <i class="fa-solid fa-trash-can"></i> <?php esc_html_e( 'Delete', 'swimming-pool-manager' ); ?>
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
        <div class="ifs-pms-modal-head-row">
            <h3 class="ifs-pms-modal-title">
                <i class="fa-solid fa-user-shield ifs-pms-icon-primary"></i>
                <?php esc_html_e( 'Operator Profile & Compensation', 'swimming-pool-manager' ); ?>
            </h3>
            <button type="button" class="ifs-pms-modal-close-btn" onclick="ifsPmsCloseViewStaffModal()">&times;</button>
        </div>

        <div class="ifs-pms-profile-preview-box">
            <div class="ifs-pms-staff-avatar-box ifs-pms-avatar-lg" id="ifsPmsViewAvatarBox">--</div>
            <div>
                <strong class="ifs-pms-profile-name" id="ifsPmsViewStaffName">--</strong>
                <div class="ifs-pms-profile-email" id="ifsPmsViewStaffEmail">--</div>
            </div>
        </div>

        <div class="ifs-pms-modal-details-stack">
            <div class="ifs-pms-modal-row">
                <span class="ifs-pms-modal-label"><?php esc_html_e( 'Username:', 'swimming-pool-manager' ); ?></span>
                <strong class="ifs-pms-mono ifs-pms-text-username" id="ifsPmsViewStaffLogin">--</strong>
            </div>
            <div class="ifs-pms-modal-row">
                <span class="ifs-pms-modal-label"><?php esc_html_e( 'Terminal Permission:', 'swimming-pool-manager' ); ?></span>
                <span id="ifsPmsViewStaffRoleBadge" class="ifs-pms-badge ifs-pms-badge-success">--</span>
            </div>
            <div class="ifs-pms-modal-row">
                <span class="ifs-pms-modal-label"><?php esc_html_e( 'Base Salary:', 'swimming-pool-manager' ); ?></span>
                <span class="ifs-pms-mono ifs-pms-text-weight-bold" id="ifsPmsViewStaffBase">--</span>
            </div>
            <div class="ifs-pms-modal-row">
                <span class="ifs-pms-modal-label"><?php esc_html_e( 'Allowance:', 'swimming-pool-manager' ); ?></span>
                <span class="ifs-pms-mono ifs-pms-text-weight-bold" id="ifsPmsViewStaffAllow">--</span>
            </div>
            <div class="ifs-pms-modal-row-last">
                <span class="ifs-pms-modal-label"><?php esc_html_e( 'Pay Frequency:', 'swimming-pool-manager' ); ?></span>
                <span class="ifs-pms-badge" id="ifsPmsViewStaffFreq">--</span>
            </div>
        </div>

        <div class="ifs-pms-modal-footer">
            <button type="button" class="ifs-pms-btn ifs-pms-btn-sm ifs-pms-btn-secondary ifs-pms-btn-block" onclick="ifsPmsCloseViewStaffModal()">
                <?php esc_html_e( 'Close Profile', 'swimming-pool-manager' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- MODAL: Edit Staff Account & Salary -->
<div id="ifsPmsEditStaffModal" class="ifs-pms-modal-overlay">
    <div class="ifs-pms-modal-card">
        <div class="ifs-pms-modal-head-row">
            <h3 class="ifs-pms-modal-title">
                <i class="fa-solid fa-pen-to-square ifs-pms-icon-primary"></i>
                <?php esc_html_e( 'Edit Operator Profile & Compensation', 'swimming-pool-manager' ); ?>
            </h3>
            <button type="button" class="ifs-pms-modal-close-btn" onclick="ifsPmsCloseEditStaffModal()">&times;</button>
        </div>

        <form method="POST" action="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=staff&tab=list' ) ); ?>">
            <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
            <input type="hidden" name="ifs_pms_action" value="edit_staff">
            <input type="hidden" name="user_id" id="ifsPmsEditStaffId">

            <div class="ifs-pms-form-stack">
                <div class="ifs-pms-grid-2">
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Display Name', 'swimming-pool-manager' ); ?> *</label>
                        <input type="text" name="display_name" id="ifsPmsEditStaffName" required>
                    </div>
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Email Address', 'swimming-pool-manager' ); ?> *</label>
                        <input type="email" name="user_email" id="ifsPmsEditStaffEmail" required>
                    </div>
                </div>

                <div class="ifs-pms-grid-2">
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Terminal Role', 'swimming-pool-manager' ); ?> *</label>
                        <select name="user_role" id="ifsPmsEditStaffRole">
                            <option value="ozone_cashier"><?php esc_html_e( 'Ozone Cashier', 'swimming-pool-manager' ); ?></option>
                            <option value="administrator"><?php esc_html_e( 'Facility Manager', 'swimming-pool-manager' ); ?></option>
                        </select>
                    </div>
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'New Password', 'swimming-pool-manager' ); ?></label>
                        <input type="password" name="user_pass" placeholder="Leave blank if unchanged">
                    </div>
                </div>

                <div class="ifs-pms-field-group">
                    <label class="ifs-pms-field-label"><?php esc_html_e( 'Custom Avatar Image URL', 'swimming-pool-manager' ); ?></label>
                    <div class="ifs-pms-avatar-upload-group">
                        <input type="text" name="user_avatar_url" id="ifsPmsEditAvatarUrl" placeholder="https://..." autocomplete="off">
                        <button type="button" class="ifs-pms-btn ifs-pms-btn-sm ifs-pms-btn-media-action" onclick="ifsPmsOpenMediaUploaderForEdit()">
                            <i class="fa-solid fa-image"></i> <?php esc_html_e( 'Media Library', 'swimming-pool-manager' ); ?>
                        </button>
                    </div>
                </div>

                <hr class="ifs-pms-separator-light">

                <div class="ifs-pms-grid-2">
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Base Salary', 'swimming-pool-manager' ); ?></label>
                        <input type="number" step="0.01" name="base_salary" id="ifsPmsEditStaffBase" class="ifs-pms-mono" required>
                    </div>
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Allowance', 'swimming-pool-manager' ); ?></label>
                        <input type="number" step="0.01" name="allowance" id="ifsPmsEditStaffAllow" class="ifs-pms-mono" required>
                    </div>
                </div>

                <div class="ifs-pms-grid-2">
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Pay Frequency', 'swimming-pool-manager' ); ?></label>
                        <select name="pay_frequency" id="ifsPmsEditStaffFreq">
                            <option value="Monthly"><?php esc_html_e( 'Monthly', 'swimming-pool-manager' ); ?></option>
                            <option value="Weekly"><?php esc_html_e( 'Weekly', 'swimming-pool-manager' ); ?></option>
                            <option value="Daily"><?php esc_html_e( 'Daily Shift', 'swimming-pool-manager' ); ?></option>
                        </select>
                    </div>
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Effective Date', 'swimming-pool-manager' ); ?></label>
                        <input type="date" name="effective_date" id="ifsPmsEditStaffDate" class="ifs-pms-mono" required>
                    </div>
                </div>

                <div class="ifs-pms-modal-actions">
                    <button type="submit" class="ifs-pms-btn ifs-pms-btn-primary ifs-pms-btn-flex-2"><?php esc_html_e( 'Save Changes', 'swimming-pool-manager' ); ?></button>
                    <button type="button" class="ifs-pms-btn ifs-pms-btn-sm ifs-pms-btn-secondary ifs-pms-btn-flex-1" onclick="ifsPmsCloseEditStaffModal()"><?php esc_html_e( 'Cancel', 'swimming-pool-manager' ); ?></button>
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