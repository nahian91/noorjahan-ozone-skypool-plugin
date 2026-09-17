<?php
/**
 * View: Member Directory & Pass Enrollment Terminal (Enterprise Edition v2 - Zero Inline CSS)
 *
 * @package Ozone_Skypool_OS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$t_members = $wpdb->prefix . 'ifs_pms_memberships';
$currency  = esc_html( (string) get_option( 'ifs_pms_currency', 'BDT' ) );
$b_name    = esc_html( (string) get_option( 'ifs_pms_business_name', 'Ozone Restaurant & Skypool' ) );
$base_url  = admin_url( 'admin.php?page=ifs-pms' );
$today_dt  = current_time( 'Y-m-d' );
$is_admin  = current_user_can( 'manage_options' ) || current_user_can( 'ozone_manage_settings' );

// Tab Router
$active_tab = isset( $_GET['tab'] ) && $_GET['tab'] === 'list' ? 'list' : 'add';

// Query Members
$members = $wpdb->get_results(
    "SELECT * FROM {$t_members} ORDER BY id DESC LIMIT 200"
);
?>

<div class="ifs-pms-membership-wrapper">
    <!-- Sub Navigation Tab Bar -->
    <div class="ifs-pms-subnav-bar" role="tablist">
        <button type="button" class="ifs-pms-subnav-btn <?php echo ( $active_tab === 'add' ) ? 'active' : ''; ?>" id="ifsPmsMemberTabBtnAdd" onclick="ifsPmsSwitchMemberTab('add', this)">
            <i class="fa-solid fa-user-plus"></i> <?php esc_html_e( 'Add Member', 'swimming-pool-manager' ); ?>
        </button>
        <button type="button" class="ifs-pms-subnav-btn <?php echo ( $active_tab === 'list' ) ? 'active' : ''; ?>" id="ifsPmsMemberTabBtnList" onclick="ifsPmsSwitchMemberTab('list', this)">
            <i class="fa-solid fa-users"></i> <?php esc_html_e( 'All Members', 'swimming-pool-manager' ); ?>
        </button>
    </div>

    <!-- TAB 1: Add Member Terminal -->
    <div id="ifsPmsMemberPaneAdd" class="ifs-pms-tab-pane <?php echo ( $active_tab === 'add' ) ? 'active' : ''; ?>">
        <div class="ifs-pms-membership-layout">
            <!-- Enrollment Form -->
            <div class="ifs-pms-panel-card">
                <div class="ifs-pms-panel-head">
                    <h3 class="ifs-pms-panel-title">
                        <i class="fa-solid fa-id-card-clip ifs-pms-icon-primary"></i>
                        <?php esc_html_e( 'Enroll Aquatic Member', 'swimming-pool-manager' ); ?>
                    </h3>
                    <span class="ifs-pms-badge ifs-pms-badge-success">
                        <i class="fa-solid fa-bolt"></i> <?php esc_html_e( 'Auto RFID Pass', 'swimming-pool-manager' ); ?>
                    </span>
                </div>

                <form method="POST" action="<?php echo esc_url( $base_url . '&view=membership' ); ?>" id="ifsPmsMemberForm">
                    <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                    <input type="hidden" name="ifs_pms_action" value="create_membership">

                    <div class="ifs-pms-form-stack">
                        <div class="ifs-pms-field-group">
                            <label class="ifs-pms-field-label" for="ifsMemName"><?php esc_html_e( 'Subscriber Full Name', 'swimming-pool-manager' ); ?> *</label>
                            <input type="text" name="m_name" id="ifsMemName" required placeholder="<?php esc_attr_e( 'e.g. Farhan Chowdhury', 'swimming-pool-manager' ); ?>" autocomplete="off">
                        </div>

                        <div class="ifs-pms-field-group">
                            <label class="ifs-pms-field-label" for="ifsMemPhone"><?php esc_html_e( 'Mobile Contact (Primary Key)', 'swimming-pool-manager' ); ?> *</label>
                            <input type="tel" name="m_phone" id="ifsMemPhone" required placeholder="017XXXXXXXX" pattern="[0-9+\s\-]{7,20}" autocomplete="off">
                        </div>

                        <div class="ifs-pms-field-group">
                            <label class="ifs-pms-field-label"><?php esc_html_e( 'Patron Profile Image / Avatar', 'swimming-pool-manager' ); ?></label>
                            <div class="ifs-pms-avatar-upload-row">
                                <input type="text" name="profile_image" id="ifsMemAvatarInput" placeholder="https://... image URL" class="ifs-pms-flex-1" oninput="ifsPmsSyncCardDisplay()">
                                <button type="button" class="ifs-pms-btn ifs-pms-btn-sm ifs-pms-btn-media" onclick="ifsPmsOpenMemberMediaUploader()">
                                    <i class="fa-solid fa-image"></i> <?php esc_html_e( 'Browse', 'swimming-pool-manager' ); ?>
                                </button>
                            </div>
                        </div>

                        <div class="ifs-pms-field-group">
                            <label class="ifs-pms-field-label" for="ifsMemPlan"><?php esc_html_e( 'Membership Tier Plan', 'swimming-pool-manager' ); ?></label>
                            <select name="plan_type" id="ifsMemPlan" onchange="ifsPmsHandlePlanSelect()">
                                <option value="Monthly Sky Pass" data-months="1" data-price="4500.00"><?php esc_html_e( 'Monthly Sky Pass (1 Month)', 'swimming-pool-manager' ); ?></option>
                                <option value="Quarterly Lounge Pass" data-months="3" data-price="12500.00"><?php esc_html_e( 'Quarterly Lounge Pass (3 Months)', 'swimming-pool-manager' ); ?></option>
                                <option value="Half-Yearly VIP Pass" data-months="6" data-price="22500.00"><?php esc_html_e( 'Half-Yearly VIP Cabana Pass (6 Months)', 'swimming-pool-manager' ); ?></option>
                                <option value="Annual Elite Corporate" data-months="12" data-price="42000.00"><?php esc_html_e( 'Annual Elite VIP Pass (12 Months)', 'swimming-pool-manager' ); ?></option>
                            </select>
                        </div>

                        <div class="ifs-pms-grid-2">
                            <div class="ifs-pms-field-group">
                                <label class="ifs-pms-field-label" for="ifsMemDuration"><?php esc_html_e( 'Duration (Months)', 'swimming-pool-manager' ); ?></label>
                                <input type="number" name="duration_months" id="ifsMemDuration" class="ifs-pms-mono" value="1" min="1" max="36" required oninput="ifsPmsSyncCardDisplay()">
                            </div>
                            <div class="ifs-pms-field-group">
                                <label class="ifs-pms-field-label" for="ifsMemAmount"><?php printf( esc_html__( 'Total Due (%s)', 'swimming-pool-manager' ), esc_html( $currency ) ); ?></label>
                                <input type="number" step="0.01" name="m_amount" id="ifsMemAmount" class="ifs-pms-mono" value="4500.00" required>
                            </div>
                        </div>

                        <button type="submit" class="ifs-pms-btn ifs-pms-btn-primary ifs-pms-btn-lg ifs-pms-btn-submit-block">
                            <i class="fa-solid fa-id-card"></i> <?php esc_html_e( 'Enroll & Provision Member Pass', 'swimming-pool-manager' ); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Live Holographic Card Preview -->
            <div>
                <div class="ifs-pms-rfid-card-stage">
                    <div class="ifs-pms-virtual-rfid-card">
                        <i class="fa-solid fa-water-ladder ifs-pms-card-watermark"></i>
                        
                        <div class="ifs-pms-card-head-row">
                            <div class="ifs-pms-card-chip"></div>
                            <span class="ifs-pms-badge ifs-pms-badge-success ifs-pms-card-tier-pill" id="ifsPmsCardTierPill">
                                <?php esc_html_e( 'Monthly Sky Pass', 'swimming-pool-manager' ); ?>
                            </span>
                        </div>

                        <div class="ifs-pms-card-profile-row">
                            <div id="ifsPmsCardAvatarBox" class="ifs-pms-card-avatar">FC</div>
                            <div>
                                <div class="ifs-pms-card-business-title">
                                    <?php echo $b_name; ?>
                                </div>
                                <div class="ifs-pms-card-holder-name" id="ifsPmsCardHolder">
                                    <?php esc_html_e( 'Farhan Chowdhury', 'swimming-pool-manager' ); ?>
                                </div>
                            </div>
                        </div>

                        <div class="ifs-pms-card-footer-row ifs-pms-mono">
                            <div>
                                <div class="ifs-pms-card-footer-label"><?php esc_html_e( 'MEMBER TOKEN', 'swimming-pool-manager' ); ?></div>
                                <strong class="ifs-pms-card-token">OZONE-MEM-PRO</strong>
                            </div>
                            <div class="ifs-pms-text-align-right">
                                <div class="ifs-pms-card-footer-label"><?php esc_html_e( 'VALID THROUGH', 'swimming-pool-manager' ); ?></div>
                                <span id="ifsPmsCardExpiryDate" class="ifs-pms-card-expiry">--</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: All Members Registry -->
    <div id="ifsPmsMemberPaneList" class="ifs-pms-tab-pane <?php echo ( $active_tab === 'list' ) ? 'active' : ''; ?>">
        <div class="ifs-pms-panel-card">
            <div class="ifs-pms-panel-head">
                <h3 class="ifs-pms-panel-title">
                    <i class="fa-solid fa-users ifs-pms-icon-muted"></i>
                    <?php esc_html_e( 'Subscriber Directory Ledger', 'swimming-pool-manager' ); ?>
                </h3>
                <span class="ifs-pms-badge ifs-pms-badge-neutral">
                    <?php echo count( $members ); ?> <?php esc_html_e( 'Registered Members', 'swimming-pool-manager' ); ?>
                </span>
            </div>

            <div class="ifs-pms-search-bar">
                <div class="ifs-pms-search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="ifsPmsMemberFilterInput" placeholder="<?php esc_attr_e( 'Filter by subscriber name, mobile, or UID...', 'swimming-pool-manager' ); ?>" oninput="ifsPmsFilterDirectory()" autocomplete="off">
                    <button type="button" class="ifs-pms-search-clear" id="ifsPmsFilterClearBtn" onclick="ifsPmsClearFilter()">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div class="ifs-pms-record-counter-text" id="ifsPmsRecordCounter">
                    <?php printf( esc_html__( 'Total: %d members', 'swimming-pool-manager' ), count( $members ) ); ?>
                </div>
            </div>

            <div class="ifs-pms-table-wrap">
                <table class="ifs-pms-table" id="ifsPmsMemberDirectoryTable">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Subscriber UID', 'swimming-pool-manager' ); ?></th>
                            <th><?php esc_html_e( 'Patron Profile', 'swimming-pool-manager' ); ?></th>
                            <th><?php esc_html_e( 'Tier Plan', 'swimming-pool-manager' ); ?></th>
                            <th><?php esc_html_e( 'Valid Through', 'swimming-pool-manager' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'swimming-pool-manager' ); ?></th>
                            <th class="ifs-pms-th-actions"><?php esc_html_e( 'Actions', 'swimming-pool-manager' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $members ) ) : ?>
                            <?php foreach ( $members as $m) :
                                $expiry_stamp = strtotime( $m->expiry_date );
                                $today_stamp  = strtotime( $today_dt );
                                $is_expired   = ( $m->expiry_date < $today_dt );
                                $diff_days    = (int) floor( ( $expiry_stamp - $today_stamp ) / DAY_IN_SECONDS );

                                if ( $is_expired ) {
                                    $badge_class = 'ifs-pms-badge-danger';
                                    $status_text = __( 'Expired', 'swimming-pool-manager' );
                                } elseif ( $diff_days <= 7 ) {
                                    $badge_class = 'ifs-pms-badge-warning';
                                    $status_text = sprintf( __( 'Expires in %d d', 'swimming-pool-manager' ), $diff_days );
                                } else {
                                    $badge_class = 'ifs-pms-badge-success';
                                    $status_text = ! empty( $m->status ) ? $m->status : __( 'Active', 'swimming-pool-manager' );
                                }
                            ?>
                                <tr class="ifs-pms-member-record-row">
                                    <td class="ifs-pms-mono ifs-pms-text-uid">
                                        <?php echo esc_html( $m->member_code ); ?>
                                    </td>
                                    <td>
                                        <div class="ifs-pms-table-profile-row">
                                            <?php if ( ! empty( $m->profile_image ) ) : ?>
                                                <img src="<?php echo esc_url( $m->profile_image ); ?>" alt="Avatar" class="ifs-pms-table-avatar-img">
                                            <?php else : ?>
                                                <div class="ifs-pms-table-avatar-fallback">
                                                    <?php echo esc_html( mb_strtoupper( mb_substr( $m->name, 0, 1 ) ) ); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong class="ifs-pms-table-name"><?php echo esc_html( $m->name ); ?></strong><br>
                                                <span class="ifs-pms-table-phone"><?php echo esc_html( $m->phone ); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="ifs-pms-table-plan-title"><?php echo esc_html( $m->plan_type ); ?></span>
                                        <div class="ifs-pms-mono ifs-pms-table-plan-amount">
                                            <?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $m->amount, 2 ) ); ?>
                                        </div>
                                    </td>
                                    <td class="ifs-pms-mono ifs-pms-table-expiry">
                                        <?php echo esc_html( $m->expiry_date ); ?>
                                    </td>
                                    <td>
                                        <span class="ifs-pms-badge <?php echo esc_attr( $badge_class ); ?>">
                                            <?php echo esc_html( $status_text ); ?>
                                        </span>
                                    </td>
                                    <td class="ifs-pms-td-actions">
                                        <button type="button" class="ifs-pms-btn ifs-pms-btn-sm ifs-pms-btn-view" onclick='ifsPmsOpenViewMemberModal(<?php echo wp_json_encode( array(
                                            'code'          => $m->member_code,
                                            'name'          => $m->name,
                                            'phone'         => $m->phone,
                                            'plan'          => $m->plan_type,
                                            'amount'        => number_format_i18n( (float) $m->amount, 2 ),
                                            'start'         => $m->start_date,
                                            'expiry'        => $m->expiry_date,
                                            'status'        => $m->status,
                                            'profile_image' => $m->profile_image ?? '',
                                        ) ); ?>)'>
                                            <i class="fa-solid fa-eye"></i> <?php esc_html_e( 'View', 'swimming-pool-manager' ); ?>
                                        </button>

                                        <button type="button" class="ifs-pms-btn ifs-pms-btn-sm ifs-pms-btn-edit" onclick='ifsPmsOpenEditMemberModal(<?php echo wp_json_encode( array(
                                            'id'            => $m->id,
                                            'code'          => $m->member_code,
                                            'name'          => $m->name,
                                            'phone'         => $m->phone,
                                            'plan'          => $m->plan_type,
                                            'amount'        => $m->amount,
                                            'expiry'        => $m->expiry_date,
                                            'status'        => $m->status,
                                            'profile_image' => $m->profile_image ?? '',
                                        ) ); ?>)'>
                                            <i class="fa-solid fa-pen-to-square"></i> <?php esc_html_e( 'Edit', 'swimming-pool-manager' ); ?>
                                        </button>

                                        <?php if ( $is_admin ) : ?>
                                            <form method="POST" action="<?php echo esc_url( $base_url . '&view=membership' ); ?>" class="ifs-pms-inline-form" onsubmit="return confirm(<?php echo wp_json_encode( __( 'Permanently revoke and delete this membership account?', 'swimming-pool-manager' ) ); ?>);">
                                                <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                                                <input type="hidden" name="ifs_pms_action" value="delete_membership">
                                                <input type="hidden" name="member_id" value="<?php echo esc_attr( $m->id ); ?>">
                                                <button type="submit" class="ifs-pms-btn ifs-pms-btn-sm ifs-pms-btn-delete" title="<?php esc_attr_e( 'Revoke Membership', 'swimming-pool-manager' ); ?>">
                                                    <i class="fa-solid fa-trash-can"></i> <?php esc_html_e( 'Delete', 'swimming-pool-manager' ); ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr id="ifsPmsEmptyTableNotice">
                                <td colspan="6" class="ifs-pms-empty-state">
                                    <i class="fa-solid fa-address-card ifs-pms-empty-icon"></i>
                                    <?php esc_html_e( 'No active subscriptions recorded in system.', 'swimming-pool-manager' ); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: View Member Digital Card -->
<div id="ifsPmsViewMemberModal" class="ifs-pms-modal-overlay">
    <div class="ifs-pms-modal-card ifs-pms-text-align-center">
        <div class="ifs-pms-modal-head-row">
            <h3 class="ifs-pms-modal-title">
                <i class="fa-solid fa-id-badge ifs-pms-icon-primary"></i>
                <?php esc_html_e( 'Aquatic Pass Identity', 'swimming-pool-manager' ); ?>
            </h3>
            <button type="button" class="ifs-pms-modal-close-btn" onclick="ifsPmsCloseViewMemberModal()">&times;</button>
        </div>

        <div class="ifs-pms-virtual-rfid-card ifs-pms-modal-rfid-preview">
            <i class="fa-solid fa-water-ladder ifs-pms-card-watermark"></i>
            
            <div class="ifs-pms-card-head-row">
                <div class="ifs-pms-card-chip"></div>
                <span class="ifs-pms-badge ifs-pms-badge-success ifs-pms-card-tier-pill-small" id="ifsPmsViewModalTierPill">--</span>
            </div>

            <div class="ifs-pms-card-profile-row">
                <div id="ifsPmsViewModalAvatarBox" class="ifs-pms-card-avatar">--</div>
                <div>
                    <div class="ifs-pms-card-business-title"><?php echo $b_name; ?></div>
                    <div class="ifs-pms-modal-holder-title" id="ifsPmsViewModalHolder">--</div>
                    <div class="ifs-pms-modal-holder-phone" id="ifsPmsViewModalPhone">--</div>
                </div>
            </div>

            <div class="ifs-pms-card-footer-row ifs-pms-mono">
                <div>
                    <div class="ifs-pms-card-footer-label"><?php esc_html_e( 'MEMBER TOKEN', 'swimming-pool-manager' ); ?></div>
                    <strong class="ifs-pms-card-token" id="ifsPmsViewModalCode">--</strong>
                </div>
                <div class="ifs-pms-text-align-right">
                    <div class="ifs-pms-card-footer-label"><?php esc_html_e( 'EXPIRES', 'swimming-pool-manager' ); ?></div>
                    <span id="ifsPmsViewModalExpiry" class="ifs-pms-card-expiry">--</span>
                </div>
            </div>
        </div>

        <div class="ifs-pms-modal-actions-split">
            <button type="button" class="ifs-pms-btn ifs-pms-btn-primary ifs-pms-modal-print-btn" onclick="window.print()">
                <i class="fa-solid fa-print"></i> <?php esc_html_e( 'Print Pass Card', 'swimming-pool-manager' ); ?>
            </button>
            <button type="button" class="ifs-pms-btn ifs-pms-btn-sm ifs-pms-modal-close-action-btn" onclick="ifsPmsCloseViewMemberModal()">
                <?php esc_html_e( 'Close', 'swimming-pool-manager' ); ?>
            </button>
        </div>
    </div>
</div>

<!-- MODAL: Edit Member Record -->
<div id="ifsPmsEditMemberModal" class="ifs-pms-modal-overlay">
    <div class="ifs-pms-modal-card">
        <div class="ifs-pms-modal-head-row">
            <h3 class="ifs-pms-modal-title">
                <i class="fa-solid fa-pen-to-square ifs-pms-icon-primary"></i>
                <?php esc_html_e( 'Edit Member Subscription', 'swimming-pool-manager' ); ?> (<span id="ifsPmsEditModalCode" class="ifs-pms-mono"></span>)
            </h3>
            <button type="button" class="ifs-pms-modal-close-btn" onclick="ifsPmsCloseEditMemberModal()">&times;</button>
        </div>

        <form method="POST" action="<?php echo esc_url( $base_url . '&view=membership' ); ?>" id="ifsPmsEditMemberForm">
            <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
            <input type="hidden" name="ifs_pms_action" value="edit_membership">
            <input type="hidden" name="member_id" id="ifsPmsEditModalId" value="">

            <div class="ifs-pms-form-stack">
                <div class="ifs-pms-field-group">
                    <label class="ifs-pms-field-label"><?php esc_html_e( 'Subscriber Full Name', 'swimming-pool-manager' ); ?> *</label>
                    <input type="text" name="m_name" id="ifsPmsEditModalName" required>
                </div>

                <div class="ifs-pms-field-group">
                    <label class="ifs-pms-field-label"><?php esc_html_e( 'Mobile Contact', 'swimming-pool-manager' ); ?> *</label>
                    <input type="tel" name="m_phone" id="ifsPmsEditModalPhone" required>
                </div>

                <div class="ifs-pms-field-group">
                    <label class="ifs-pms-field-label"><?php esc_html_e( 'Patron Profile Image / Avatar', 'swimming-pool-manager' ); ?></label>
                    <div class="ifs-pms-avatar-upload-row">
                        <input type="text" name="profile_image" id="ifsPmsEditModalAvatarInput" placeholder="https://... image URL" class="ifs-pms-flex-1">
                        <button type="button" class="ifs-pms-btn ifs-pms-btn-sm ifs-pms-btn-media" onclick="ifsPmsOpenEditMemberMediaUploader()">
                            <i class="fa-solid fa-image"></i> <?php esc_html_e( 'Browse', 'swimming-pool-manager' ); ?>
                        </button>
                    </div>
                </div>

                <div class="ifs-pms-grid-plan-fee">
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Tier Plan Type', 'swimming-pool-manager' ); ?> *</label>
                        <input type="text" name="plan_type" id="ifsPmsEditModalPlan" required>
                    </div>

                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php printf( esc_html__( 'Fee (%s)', 'swimming-pool-manager' ), esc_html( $currency ) ); ?> *</label>
                        <input type="number" step="0.01" name="m_amount" id="ifsPmsEditModalAmount" class="ifs-pms-mono" required>
                    </div>
                </div>

                <div class="ifs-pms-grid-2">
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Expiry Date', 'swimming-pool-manager' ); ?> *</label>
                        <input type="date" name="expiry_date" id="ifsPmsEditModalExpiry" class="ifs-pms-mono" required>
                    </div>

                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Status', 'swimming-pool-manager' ); ?> *</label>
                        <select name="status" id="ifsPmsEditModalStatus">
                            <option value="Active"><?php esc_html_e( 'Active', 'swimming-pool-manager' ); ?></option>
                            <option value="Suspended"><?php esc_html_e( 'Suspended', 'swimming-pool-manager' ); ?></option>
                            <option value="Expired"><?php esc_html_e( 'Expired', 'swimming-pool-manager' ); ?></option>
                        </select>
                    </div>
                </div>

                <div class="ifs-pms-modal-actions">
                    <button type="submit" class="ifs-pms-btn ifs-pms-btn-primary ifs-pms-btn-flex-2">
                        <i class="fa-solid fa-floppy-disk"></i> <?php esc_html_e( 'Save Changes', 'swimming-pool-manager' ); ?>
                    </button>
                    <button type="button" class="ifs-pms-btn ifs-pms-btn-sm ifs-pms-btn-flex-1 ifs-pms-btn-cancel" onclick="ifsPmsCloseEditMemberModal()">
                        <?php esc_html_e( 'Cancel', 'swimming-pool-manager' ); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    // Tab Switching Router
    window.ifsPmsSwitchMemberTab = function(tabKey, btn) {
        document.querySelectorAll('.ifs-pms-subnav-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        document.getElementById('ifsPmsMemberPaneAdd').classList.remove('active');
        document.getElementById('ifsPmsMemberPaneList').classList.remove('active');

        if (tabKey === 'add') {
            document.getElementById('ifsPmsMemberPaneAdd').classList.add('active');
        } else {
            document.getElementById('ifsPmsMemberPaneList').classList.add('active');
        }

        if (window.history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.set('tab', tabKey);
            window.history.replaceState({}, '', url);
        }
    };

    // WordPress Media Uploader for Add Form
    window.ifsPmsOpenMemberMediaUploader = function() {
        const uploader = wp.media({
            title: 'Select Patron Profile Photo',
            button: { text: 'Use this photo' },
            multiple: false
        });
        uploader.on('select', function() {
            const attachment = uploader.state().get('selection').first().toJSON();
            document.getElementById('ifsMemAvatarInput').value = attachment.url;
            window.ifsPmsSyncCardDisplay();
        });
        uploader.open();
    };

    // WordPress Media Uploader for Edit Modal
    window.ifsPmsOpenEditMemberMediaUploader = function() {
        const uploader = wp.media({
            title: 'Select Patron Profile Photo',
            button: { text: 'Use this photo' },
            multiple: false
        });
        uploader.on('select', function() {
            const attachment = uploader.state().get('selection').first().toJSON();
            document.getElementById('ifsPmsEditModalAvatarInput').value = attachment.url;
        });
        uploader.open();
    };

    // Modal Control: View Pass
    window.ifsPmsOpenViewMemberModal = function(data) {
        document.getElementById('ifsPmsViewModalTierPill').textContent = data.plan;
        document.getElementById('ifsPmsViewModalHolder').textContent   = data.name;
        document.getElementById('ifsPmsViewModalPhone').textContent    = data.phone;
        document.getElementById('ifsPmsViewModalCode').textContent     = data.code;
        document.getElementById('ifsPmsViewModalExpiry').textContent   = data.expiry;

        const avatarBox = document.getElementById('ifsPmsViewModalAvatarBox');
        if (data.profile_image) {
            avatarBox.innerHTML = '<img src="' + data.profile_image + '" alt="Avatar" class="ifs-pms-modal-avatar-img">';
        } else {
            avatarBox.textContent = data.name ? data.name.charAt(0).toUpperCase() : 'M';
        }

        document.getElementById('ifsPmsViewMemberModal').style.display = 'flex';
    };

    window.ifsPmsCloseViewMemberModal = function() {
        document.getElementById('ifsPmsViewMemberModal').style.display = 'none';
    };

    // Modal Control: Edit Member
    window.ifsPmsOpenEditMemberModal = function(data) {
        document.getElementById('ifsPmsEditModalId').value     = data.id;
        document.getElementById('ifsPmsEditModalCode').textContent = data.code;
        document.getElementById('ifsPmsEditModalName').value   = data.name;
        document.getElementById('ifsPmsEditModalPhone').value  = data.phone;
        document.getElementById('ifsPmsEditModalPlan').value   = data.plan;
        document.getElementById('ifsPmsEditModalAmount').value = parseFloat(data.amount).toFixed(2);
        document.getElementById('ifsPmsEditModalExpiry').value = data.expiry;
        document.getElementById('ifsPmsEditModalStatus').value = data.status;
        document.getElementById('ifsPmsEditModalAvatarInput').value = data.profile_image || '';

        document.getElementById('ifsPmsEditMemberModal').style.display = 'flex';
    };

    window.ifsPmsCloseEditMemberModal = function() {
        document.getElementById('ifsPmsEditMemberModal').style.display = 'none';
    };

    // Auto Plan Selection
    window.ifsPmsHandlePlanSelect = function() {
        const selectBox = document.getElementById('ifsMemPlan');
        if (!selectBox) return;

        const activeOpt = selectBox.options[selectBox.selectedIndex];
        const months    = activeOpt.getAttribute('data-months');
        const price     = activeOpt.getAttribute('data-price');

        const durationInput = document.getElementById('ifsMemDuration');
        const amountInput   = document.getElementById('ifsMemAmount');

        if (durationInput) durationInput.value = months;
        if (amountInput) amountInput.value = parseFloat(price).toFixed(2);

        window.ifsPmsSyncCardDisplay();
    };

    // Sync Live Digital Card
    window.ifsPmsSyncCardDisplay = function() {
        const nameVal       = document.getElementById('ifsMemName').value.trim();
        const avatarUrl     = document.getElementById('ifsMemAvatarInput').value.trim();
        const selectBox     = document.getElementById('ifsMemPlan');
        const planName      = selectBox ? selectBox.options[selectBox.selectedIndex].value : 'Monthly Sky Pass';
        const durationInput = document.getElementById('ifsMemDuration');
        const monthsCount   = durationInput ? (parseInt(durationInput.value, 10) || 1) : 1;

        const holderEl  = document.getElementById('ifsPmsCardHolder');
        const tierPill  = document.getElementById('ifsPmsCardTierPill');
        const expiryEl  = document.getElementById('ifsPmsCardExpiryDate');
        const avatarBox = document.getElementById('ifsPmsCardAvatarBox');

        if (holderEl) holderEl.textContent = nameVal ? nameVal : <?php echo wp_json_encode( __( 'Farhan Chowdhury', 'swimming-pool-manager' ) ); ?>;
        if (tierPill) tierPill.textContent = planName;

        if (avatarBox) {
            if (avatarUrl) {
                avatarBox.innerHTML = '<img src="' + avatarUrl + '" alt="Avatar" class="ifs-pms-modal-avatar-img">';
            } else {
                avatarBox.textContent = nameVal ? nameVal.charAt(0).toUpperCase() : 'FC';
            }
        }

        const calcDate = new Date();
        calcDate.setMonth(calcDate.getMonth() + monthsCount);

        const yyyy = calcDate.getFullYear();
        const mm   = String(calcDate.getMonth() + 1).padStart(2, '0');
        const dd   = String(calcDate.getDate()).padStart(2, '0');

        if (expiryEl) expiryEl.textContent = yyyy + '-' + mm + '-' + dd;
    };

    // Filter Directory
    window.ifsPmsFilterDirectory = function() {
        const inputEl   = document.getElementById('ifsPmsMemberFilterInput');
        const clearBtn  = document.getElementById('ifsPmsFilterClearBtn');
        const filterStr = inputEl ? inputEl.value.toLowerCase().trim() : '';
        const rows      = document.querySelectorAll('.ifs-pms-member-record-row');
        let matched     = 0;

        if (clearBtn) {
            clearBtn.style.display = filterStr ? 'block' : 'none';
        }

        rows.forEach(row => {
            const rowContent = row.textContent.toLowerCase();
            if (rowContent.includes(filterStr)) {
                row.style.display = '';
                matched++;
            } else {
                row.style.display = 'none';
            }
        });

        const counterEl = document.getElementById('ifsPmsRecordCounter');
        if (counterEl) {
            counterEl.textContent = filterStr 
                ? <?php echo wp_json_encode( __( 'Matching records: ', 'swimming-pool-manager' ) ); ?> + matched
                : <?php echo wp_json_encode( sprintf( __( 'Total: %d members', 'swimming-pool-manager' ), count( $members ) ) ); ?>;
        }
    };

    window.ifsPmsClearFilter = function() {
        const inputEl = document.getElementById('ifsPmsMemberFilterInput');
        if (inputEl) {
            inputEl.value = '';
            window.ifsPmsFilterDirectory();
            inputEl.focus();
        }
    };

    function init() {
        ['ifsMemName', 'ifsMemPhone'].forEach(id => {
            const input = document.getElementById(id);
            if (input) input.addEventListener('input', window.ifsPmsSyncCardDisplay);
        });

        window.ifsPmsSyncCardDisplay();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>