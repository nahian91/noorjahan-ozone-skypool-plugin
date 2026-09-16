<?php
/**
 * View: Member Directory & Pass Enrollment Terminal (Executive UX Edition with Avatar Support)
 *
 * @package Ozone_Skypool_OS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$t_members = $wpdb->prefix . 'ifs_pms_memberships';
$currency  = esc_html( get_option( 'ifs_pms_currency', 'BDT' ) );
$b_name    = esc_html( get_option( 'ifs_pms_business_name', 'Ozone Restaurant & Skypool' ) );
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

<style>
    /* ==========================================================================
       EXECUTIVE MEMBER DIRECTORY & PASS TERMINAL
       ========================================================================== */
    .oz-membership-wrapper {
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

    /* Layout & Form Controls */
    .oz-membership-layout {
        display: grid;
        grid-template-columns: minmax(380px, 440px) minmax(0, 1fr);
        gap: 28px;
        align-items: start;
        box-sizing: border-box;
        width: 100%;
    }

    @media (max-width: 1180px) {
        .oz-membership-layout {
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

    .oz-form-stack {
        padding: 32px;
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

    #wpcontent .oz-membership-layout input[type="text"],
    #wpcontent .oz-membership-layout input[type="number"],
    #wpcontent .oz-membership-layout input[type="tel"],
    #wpcontent .oz-membership-layout select,
    #wpcontent .oz-modal-card input[type="text"],
    #wpcontent .oz-modal-card input[type="number"],
    #wpcontent .oz-modal-card input[type="tel"],
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

    #wpcontent .oz-membership-layout input:focus,
    #wpcontent .oz-membership-layout select:focus,
    #wpcontent .oz-modal-card input:focus,
    #wpcontent .oz-modal-card select:focus {
        border-color: var(--ifs-border-focus, #0284c7) !important;
        box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.12), 0 4px 12px rgba(2, 132, 199, 0.08) !important;
        outline: none !important;
    }

    /* Holographic Virtual RFID Aquatic Pass */
    .oz-rfid-card-stage {
        perspective: 1000px;
    }

    .oz-virtual-rfid-card {
        background: linear-gradient(135deg, #070f26 0%, #0f172a 48%, #0369a1 100%);
        border: 1.5px solid rgba(56, 189, 248, 0.4);
        border-radius: 20px;
        padding: 28px;
        color: #ffffff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 20px 45px rgba(2, 6, 23, 0.35);
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s ease;
    }

    .oz-virtual-rfid-card:hover {
        transform: translateY(-3px) rotateX(2deg);
        box-shadow: 0 25px 50px rgba(2, 6, 23, 0.45);
    }

    .oz-card-chip {
        width: 44px;
        height: 32px;
        background: linear-gradient(135deg, #fef08a 0%, #eab308 100%);
        border-radius: 6px;
        border: 1px solid rgba(0, 0, 0, 0.3);
        position: relative;
        overflow: hidden;
        box-shadow: inset 0 0 4px rgba(0, 0, 0, 0.35);
    }

    .oz-card-chip::after {
        content: '';
        position: absolute;
        inset: 4px;
        border: 1px solid rgba(0, 0, 0, 0.2);
        border-radius: 4px;
    }

    .oz-card-watermark {
        position: absolute;
        right: -15px;
        bottom: -25px;
        font-size: 135px;
        color: rgba(255, 255, 255, 0.05);
        pointer-events: none;
        user-select: none;
    }

    /* Search Toolbar */
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

    /* Ledger Table */
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
    #wpcontent .oz-membership-wrapper .oz-btn {
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

    #wpcontent .oz-membership-wrapper .oz-btn-primary.oz-btn-lg {
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

    #wpcontent .oz-membership-wrapper .oz-btn-primary.oz-btn-lg:hover {
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

<div class="oz-membership-wrapper">
    <!-- Sub Navigation Tab Bar -->
    <div class="oz-subnav-bar" role="tablist">
        <button type="button" class="oz-subnav-btn <?php echo ( $active_tab === 'add' ) ? 'active' : ''; ?>" id="ozMemberTabBtnAdd" onclick="ozSwitchMemberTab('add', this)">
            <i class="fa-solid fa-user-plus"></i> <?php esc_html_e( 'Add Member', 'ozone-skypool' ); ?>
        </button>
        <button type="button" class="oz-subnav-btn <?php echo ( $active_tab === 'list' ) ? 'active' : ''; ?>" id="ozMemberTabBtnList" onclick="ozSwitchMemberTab('list', this)">
            <i class="fa-solid fa-users"></i> <?php esc_html_e( 'All Members', 'ozone-skypool' ); ?>
        </button>
    </div>

    <!-- TAB 1: Add Member Terminal -->
    <div id="ozMemberPaneAdd" class="oz-tab-pane <?php echo ( $active_tab === 'add' ) ? 'active' : ''; ?>">
        <div class="oz-membership-layout">
            <!-- Enrollment Form -->
            <div class="oz-panel-card">
                <div class="oz-panel-head">
                    <h3 class="oz-panel-title">
                        <i class="fa-solid fa-id-card-clip" style="color: var(--ifs-accent, #0284c7);"></i>
                        <?php esc_html_e( 'Enroll Aquatic Member', 'ozone-skypool' ); ?>
                    </h3>
                    <span class="ifs-pms-badge ifs-pms-badge-success">
                        <i class="fa-solid fa-bolt"></i> <?php esc_html_e( 'Auto RFID Pass', 'ozone-skypool' ); ?>
                    </span>
                </div>

                <form method="POST" action="<?php echo esc_url( $base_url . '&view=membership' ); ?>" id="ozMemberForm">
                    <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                    <input type="hidden" name="ifs_pms_action" value="create_membership">

                    <div class="oz-form-stack">
                        <div class="oz-field-group">
                            <label class="oz-field-label" for="ozMemName"><?php esc_html_e( 'Subscriber Full Name', 'ozone-skypool' ); ?> *</label>
                            <input type="text" name="m_name" id="ozMemName" required placeholder="<?php esc_attr_e( 'e.g. Farhan Chowdhury', 'ozone-skypool' ); ?>" autocomplete="off">
                        </div>

                        <div class="oz-field-group">
                            <label class="oz-field-label" for="ozMemPhone"><?php esc_html_e( 'Mobile Contact (Primary Key)', 'ozone-skypool' ); ?> *</label>
                            <input type="tel" name="m_phone" id="ozMemPhone" required placeholder="017XXXXXXXX" pattern="[0-9+\s\-]{7,20}" autocomplete="off">
                        </div>

                        <div class="oz-field-group">
                            <label class="oz-field-label"><?php esc_html_e( 'Patron Profile Image / Avatar', 'ozone-skypool' ); ?></label>
                            <div style="display: flex; gap: 12px; align-items: center;">
                                <input type="text" name="profile_image" id="ozMemAvatarInput" placeholder="https://... image URL" style="flex: 1;">
                                <button type="button" class="oz-btn oz-btn-secondary" style="height: 50px; border-radius: 14px; padding: 0 18px;" onclick="ozOpenMemberMediaUploader()">
                                    <i class="fa-solid fa-image"></i> <?php esc_html_e( 'Browse', 'ozone-skypool' ); ?>
                                </button>
                            </div>
                        </div>

                        <div class="oz-field-group">
                            <label class="oz-field-label" for="ozMemPlan"><?php esc_html_e( 'Membership Tier Plan', 'ozone-skypool' ); ?></label>
                            <select name="plan_type" id="ozMemPlan" onchange="ozHandlePlanSelect()">
                                <option value="Monthly Sky Pass" data-months="1" data-price="4500.00"><?php esc_html_e( 'Monthly Sky Pass (1 Month)', 'ozone-skypool' ); ?></option>
                                <option value="Quarterly Lounge Pass" data-months="3" data-price="12500.00"><?php esc_html_e( 'Quarterly Lounge Pass (3 Months)', 'ozone-skypool' ); ?></option>
                                <option value="Half-Yearly VIP Pass" data-months="6" data-price="22500.00"><?php esc_html_e( 'Half-Yearly VIP Cabana Pass (6 Months)', 'ozone-skypool' ); ?></option>
                                <option value="Annual Elite Corporate" data-months="12" data-price="42000.00"><?php esc_html_e( 'Annual Elite VIP Pass (12 Months)', 'ozone-skypool' ); ?></option>
                            </select>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px;">
                            <div class="oz-field-group">
                                <label class="oz-field-label" for="ozMemDuration"><?php esc_html_e( 'Duration (Months)', 'ozone-skypool' ); ?></label>
                                <input type="number" name="duration_months" id="ozMemDuration" class="ifs-pms-mono" value="1" min="1" max="36" required oninput="ozSyncCardDisplay()">
                            </div>
                            <div class="oz-field-group">
                                <label class="oz-field-label" for="ozMemAmount"><?php printf( esc_html__( 'Total Due (%s)', 'ozone-skypool' ), esc_html( $currency ) ); ?></label>
                                <input type="number" step="0.01" name="m_amount" id="ozMemAmount" class="ifs-pms-mono" value="4500.00" required>
                            </div>
                        </div>

                        <button type="submit" class="oz-btn oz-btn-primary oz-btn-lg" style="width: 100%; margin-top: 6px;">
                            <i class="fa-solid fa-id-card"></i> <?php esc_html_e( 'Enroll & Provision Member Pass', 'ozone-skypool' ); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Live Card Preview -->
            <div>
                <div class="oz-rfid-card-stage">
                    <div class="oz-virtual-rfid-card">
                        <i class="fa-solid fa-water-ladder oz-card-watermark"></i>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px;">
                            <div class="oz-card-chip"></div>
                            <span class="ifs-pms-badge ifs-pms-badge-success" id="ozCardTierPill" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">
                                <?php esc_html_e( 'Monthly Sky Pass', 'ozone-skypool' ); ?>
                            </span>
                        </div>

                        <div style="margin-bottom: 28px;">
                            <div style="font-size: 10.5px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase; color: #38bdf8; margin-bottom: 6px;">
                                <?php echo $b_name; ?>
                            </div>
                            <div style="font-size: 20px; font-weight: 800; letter-spacing: -0.2px; text-shadow: 0 1px 3px rgba(0,0,0,0.5);" id="ozCardHolder">
                                <?php esc_html_e( 'Farhan Chowdhury', 'ozone-skypool' ); ?>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: flex-end; font-size: 12px; color: #94a3b8;" class="ifs-pms-mono">
                            <div>
                                <div style="font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.6px; color: #64748b;"><?php esc_html_e( 'MEMBER TOKEN', 'ozone-skypool' ); ?></div>
                                <strong style="color: #ffffff; font-size: 13.5px; letter-spacing: 1px;">OZONE-MEM-PRO</strong>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.6px; color: #64748b;"><?php esc_html_e( 'VALID THROUGH', 'ozone-skypool' ); ?></div>
                                <span id="ozCardExpiryDate" style="color: #38bdf8; font-weight: 800;">--</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: All Members Registry -->
    <div id="ozMemberPaneList" class="oz-tab-pane <?php echo ( $active_tab === 'list' ) ? 'active' : ''; ?>">
        <div class="oz-panel-card">
            <div class="oz-panel-head">
                <h3 class="oz-panel-title">
                    <i class="fa-solid fa-users" style="color: var(--ifs-text-secondary, #475569);"></i>
                    <?php esc_html_e( 'Subscriber Directory Ledger', 'ozone-skypool' ); ?>
                </h3>
                <span class="ifs-pms-badge" style="background: #f1f5f9; color: var(--ifs-text-secondary); border: 1px solid #cbd5e1; font-weight: 700; padding: 6px 12px; border-radius: 8px;">
                    <?php echo count( $members ); ?> <?php esc_html_e( 'Registered Members', 'ozone-skypool' ); ?>
                </span>
            </div>

            <div class="oz-search-bar">
                <div class="oz-search-container">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="ozMemberFilterInput" placeholder="<?php esc_attr_e( 'Filter by subscriber name, mobile, or UID...', 'ozone-skypool' ); ?>" oninput="ozFilterDirectory()" autocomplete="off">
                    <button type="button" class="oz-search-clear" id="ozFilterClearBtn" onclick="ozClearFilter()">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <div style="font-size: 12.5px; font-weight: 700; color: #64748b;" id="ozRecordCounter">
                    <?php printf( esc_html__( 'Total: %d members', 'ozone-skypool' ), count( $members ) ); ?>
                </div>
            </div>

            <div class="oz-table-wrap">
                <table class="oz-table" id="ozMemberDirectoryTable">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Subscriber UID', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Patron Profile', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Tier Plan', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Valid Through', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'ozone-skypool' ); ?></th>
                            <th style="text-align: right;"><?php esc_html_e( 'Actions', 'ozone-skypool' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $members ) ) : ?>
                            <?php foreach ( $members as $m ) :
                                $expiry_stamp = strtotime( $m->expiry_date );
                                $today_stamp  = strtotime( $today_dt );
                                $is_expired   = ( $m->expiry_date < $today_dt );
                                $diff_days    = (int) floor( ( $expiry_stamp - $today_stamp ) / DAY_IN_SECONDS );

                                if ( $is_expired ) {
                                    $badge_class = 'ifs-pms-badge-danger';
                                    $status_text = __( 'Expired', 'ozone-skypool' );
                                } elseif ( $diff_days <= 7 ) {
                                    $badge_class = 'ifs-pms-badge-warning';
                                    $status_text = sprintf( __( 'Expires in %d d', 'ozone-skypool' ), $diff_days );
                                } else {
                                    $badge_class = 'ifs-pms-badge-success';
                                    $status_text = ! empty( $m->status ) ? $m->status : __( 'Active', 'ozone-skypool' );
                                }
                            ?>
                                <tr class="oz-member-record-row">
                                    <td style="font-weight: 800; color: var(--ifs-accent, #0284c7);" class="ifs-pms-mono">
                                        <?php echo esc_html( $m->member_code ); ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 14px;">
                                            <?php if ( ! empty( $m->profile_image ) ) : ?>
                                                <img src="<?php echo esc_url( $m->profile_image ); ?>" alt="Avatar" style="width: 40px; height: 40px; border-radius: 12px; object-fit: cover; border: 1.5px solid #cbd5e1; flex-shrink: 0;">
                                            <?php else : ?>
                                                <div style="width: 40px; height: 40px; border-radius: 12px; background: rgba(2, 132, 199, 0.1); color: #0284c7; font-weight: 800; font-size: 15px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                    <?php echo esc_html( mb_strtoupper( mb_substr( $m->name, 0, 1 ) ) ); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong style="color: #0f172a; font-size: 14px;"><?php echo esc_html( $m->name ); ?></strong><br>
                                                <span style="color: #64748b; font-family: var(--ifs-font-mono); font-size: 12px;"><?php echo esc_html( $m->phone ); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-weight: 700; font-size: 13.5px; color: #0f172a;"><?php echo esc_html( $m->plan_type ); ?></span>
                                        <div style="font-size: 11.5px; color: #64748b;" class="ifs-pms-mono">
                                            <?php echo esc_html( $currency . ' ' . number_format_i18n( (float) $m->amount, 2 ) ); ?>
                                        </div>
                                    </td>
                                    <td style="font-size: 12.5px;" class="ifs-pms-mono">
                                        <?php echo esc_html( $m->expiry_date ); ?>
                                    </td>
                                    <td>
                                        <span class="ifs-pms-badge <?php echo esc_attr( $badge_class ); ?>">
                                            <?php echo esc_html( $status_text ); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <!-- View Button: Emerald Green Harmony -->
                                        <button type="button" class="oz-btn oz-btn-sm oz-btn-view" onclick='ozOpenViewMemberModal(<?php echo wp_json_encode( array(
                                            'code'   => $m->member_code,
                                            'name'   => $m->name,
                                            'phone'  => $m->phone,
                                            'plan'   => $m->plan_type,
                                            'amount' => number_format_i18n( (float) $m->amount, 2 ),
                                            'start'  => $m->start_date,
                                            'expiry' => $m->expiry_date,
                                            'status' => $m->status,
                                        ) ); ?>)'>
                                            <i class="fa-solid fa-eye"></i> <?php esc_html_e( 'View', 'ozone-skypool' ); ?>
                                        </button>

                                        <!-- Edit Button: Sky Blue Harmony -->
                                        <button type="button" class="oz-btn oz-btn-sm oz-btn-edit" onclick='ozOpenEditMemberModal(<?php echo wp_json_encode( array(
                                            'id'     => $m->id,
                                            'code'   => $m->member_code,
                                            'name'   => $m->name,
                                            'phone'  => $m->phone,
                                            'plan'   => $m->plan_type,
                                            'amount' => $m->amount,
                                            'expiry' => $m->expiry_date,
                                            'status' => $m->status,
                                        ) ); ?>)'>
                                            <i class="fa-solid fa-pen-to-square"></i> <?php esc_html_e( 'Edit', 'ozone-skypool' ); ?>
                                        </button>

                                        <!-- Delete Action: Rose Red Harmony -->
                                        <?php if ( $is_admin ) : ?>
                                            <form method="POST" action="<?php echo esc_url( $base_url . '&view=membership' ); ?>" style="display: inline-block; margin: 0;" onsubmit="return confirm(<?php echo wp_json_encode( __( 'Permanently revoke and delete this membership account?', 'ozone-skypool' ) ); ?>);">
                                                <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                                                <input type="hidden" name="ifs_pms_action" value="delete_membership">
                                                <input type="hidden" name="member_id" value="<?php echo esc_attr( $m->id ); ?>">
                                                <button type="submit" class="oz-btn oz-btn-sm oz-btn-delete" title="<?php esc_attr_e( 'Revoke Membership', 'ozone-skypool' ); ?>">
                                                    <i class="fa-solid fa-trash-can"></i> <?php esc_html_e( 'Delete', 'ozone-skypool' ); ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr id="ozEmptyTableNotice">
                                <td colspan="6" style="text-align: center; padding: 56px 16px; color: #94a3b8;">
                                    <i class="fa-solid fa-address-card" style="font-size: 36px; opacity: 0.35; margin-bottom: 14px; display: block;"></i>
                                    <?php esc_html_e( 'No active subscriptions recorded in system.', 'ozone-skypool' ); ?>
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
<div id="ozViewMemberModal" class="oz-modal-overlay">
    <div class="oz-modal-card" style="text-align: center;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: 17px; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-id-badge" style="color: var(--ifs-accent, #0284c7);"></i>
                <?php esc_html_e( 'Aquatic Pass Identity', 'ozone-skypool' ); ?>
            </h3>
            <button type="button" style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 18px; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center;" onclick="ozCloseViewMemberModal()">&times;</button>
        </div>

        <div class="oz-virtual-rfid-card" style="text-align: left; margin-bottom: 24px;">
            <i class="fa-solid fa-water-ladder oz-card-watermark"></i>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px;">
                <div class="oz-card-chip"></div>
                <span class="ifs-pms-badge ifs-pms-badge-success" id="ozViewModalTierPill" style="font-size: 11px; text-transform: uppercase;">
                    --
                </span>
            </div>

            <div style="margin-bottom: 24px;">
                <div style="font-size: 10.5px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase; color: #38bdf8; margin-bottom: 6px;">
                    <?php echo $b_name; ?>
                </div>
                <div style="font-size: 20px; font-weight: 800;" id="ozViewModalHolder">--</div>
                <div style="font-size: 12.5px; color: #94a3b8; font-family: var(--ifs-font-mono);" id="ozViewModalPhone">--</div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: flex-end; font-size: 12px; color: #94a3b8;" class="ifs-pms-mono">
                <div>
                    <div style="font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.6px; color: #64748b;"><?php esc_html_e( 'MEMBER TOKEN', 'ozone-skypool' ); ?></div>
                    <strong style="color: #ffffff; font-size: 13.5px; letter-spacing: 1px;" id="ozViewModalCode">--</strong>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.6px; color: #64748b;"><?php esc_html_e( 'EXPIRES', 'ozone-skypool' ); ?></div>
                    <span id="ozViewModalExpiry" style="color: #38bdf8; font-weight: 800;">--</span>
                </div>
            </div>
        </div>

        <button type="button" class="oz-btn oz-btn-secondary" style="width: 100%; height: 48px; border-radius: 14px;" onclick="ozCloseViewMemberModal()">
            <?php esc_html_e( 'Dismiss Pass Card', 'ozone-skypool' ); ?>
        </button>
    </div>
</div>

<!-- MODAL: Edit Member Record -->
<div id="ozEditMemberModal" class="oz-modal-overlay">
    <div class="oz-modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: 17px; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-pen-to-square" style="color: var(--ifs-accent, #0284c7);"></i>
                <?php esc_html_e( 'Edit Member Subscription', 'ozone-skypool' ); ?> (<span id="ozEditModalCode" class="ifs-pms-mono"></span>)
            </h3>
            <button type="button" style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 18px; cursor: pointer; color: #475569; display: flex; align-items: center; justify-content: center;" onclick="ozCloseEditMemberModal()">&times;</button>
        </div>

        <form method="POST" action="<?php echo esc_url( $base_url . '&view=membership' ); ?>" id="ozEditMemberForm">
            <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
            <input type="hidden" name="ifs_pms_action" value="edit_membership">
            <input type="hidden" name="member_id" id="ozEditModalId" value="">

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div class="oz-field-group">
                    <label class="oz-field-label"><?php esc_html_e( 'Subscriber Full Name', 'ozone-skypool' ); ?> *</label>
                    <input type="text" name="m_name" id="ozEditModalName" required>
                </div>

                <div class="oz-field-group">
                    <label class="oz-field-label"><?php esc_html_e( 'Mobile Contact', 'ozone-skypool' ); ?> *</label>
                    <input type="tel" name="m_phone" id="ozEditModalPhone" required>
                </div>

                <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 16px;">
                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php esc_html_e( 'Tier Plan Type', 'ozone-skypool' ); ?> *</label>
                        <input type="text" name="plan_type" id="ozEditModalPlan" required>
                    </div>

                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php printf( esc_html__( 'Fee (%s)', 'ozone-skypool' ), esc_html( $currency ) ); ?> *</label>
                        <input type="number" step="0.01" name="m_amount" id="ozEditModalAmount" class="ifs-pms-mono" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php esc_html_e( 'Expiry Date', 'ozone-skypool' ); ?> *</label>
                        <input type="date" name="expiry_date" id="ozEditModalExpiry" class="ifs-pms-mono" required>
                    </div>

                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php esc_html_e( 'Status', 'ozone-skypool' ); ?> *</label>
                        <select name="status" id="ozEditModalStatus">
                            <option value="Active"><?php esc_html_e( 'Active', 'ozone-skypool' ); ?></option>
                            <option value="Suspended"><?php esc_html_e( 'Suspended', 'ozone-skypool' ); ?></option>
                            <option value="Expired"><?php esc_html_e( 'Expired', 'ozone-skypool' ); ?></option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 14px; margin-top: 14px;">
                    <button type="submit" class="oz-btn oz-btn-primary" style="flex: 2; height: 50px; border-radius: 14px; font-weight: 800; font-size: 14.5px;">
                        <i class="fa-solid fa-floppy-disk"></i> <?php esc_html_e( 'Save Changes', 'ozone-skypool' ); ?>
                    </button>
                    <button type="button" class="oz-btn oz-btn-secondary" style="flex: 1; height: 50px; border-radius: 14px; font-weight: 700;" onclick="ozCloseEditMemberModal()">
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
    window.ozSwitchMemberTab = function(tabKey, btn) {
        document.querySelectorAll('.oz-subnav-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        document.getElementById('ozMemberPaneAdd').classList.remove('active');
        document.getElementById('ozMemberPaneList').classList.remove('active');

        if (tabKey === 'add') {
            document.getElementById('ozMemberPaneAdd').classList.add('active');
        } else {
            document.getElementById('ozMemberPaneList').classList.add('active');
        }

        if (window.history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.set('tab', tabKey);
            window.history.replaceState({}, '', url);
        }
    };

    // WordPress Media Uploader for Patron Avatar
    window.ozOpenMemberMediaUploader = function() {
        const uploader = wp.media({
            title: 'Select Patron Profile Photo',
            button: { text: 'Use this photo' },
            multiple: false
        });
        uploader.on('select', function() {
            const attachment = uploader.state().get('selection').first().toJSON();
            document.getElementById('ozMemAvatarInput').value = attachment.url;
        });
        uploader.open();
    };

    // Modal Control: View Pass
    window.ozOpenViewMemberModal = function(data) {
        document.getElementById('ozViewModalTierPill').textContent = data.plan;
        document.getElementById('ozViewModalHolder').textContent   = data.name;
        document.getElementById('ozViewModalPhone').textContent    = data.phone;
        document.getElementById('ozViewModalCode').textContent     = data.code;
        document.getElementById('ozViewModalExpiry').textContent   = data.expiry;

        document.getElementById('ozViewMemberModal').style.display = 'flex';
    };

    window.ozCloseViewMemberModal = function() {
        document.getElementById('ozViewMemberModal').style.display = 'none';
    };

    // Modal Control: Edit Member
    window.ozOpenEditMemberModal = function(data) {
        document.getElementById('ozEditModalId').value     = data.id;
        document.getElementById('ozEditModalCode').textContent = data.code;
        document.getElementById('ozEditModalName').value   = data.name;
        document.getElementById('ozEditModalPhone').value  = data.phone;
        document.getElementById('ozEditModalPlan').value   = data.plan;
        document.getElementById('ozEditModalAmount').value = parseFloat(data.amount).toFixed(2);
        document.getElementById('ozEditModalExpiry').value = data.expiry;
        document.getElementById('ozEditModalStatus').value = data.status;

        document.getElementById('ozEditMemberModal').style.display = 'flex';
    };

    window.ozCloseEditMemberModal = function() {
        document.getElementById('ozEditMemberModal').style.display = 'none';
    };

    // Auto Plan Selection
    window.ozHandlePlanSelect = function() {
        const selectBox = document.getElementById('ozMemPlan');
        if (!selectBox) return;

        const activeOpt = selectBox.options[selectBox.selectedIndex];
        const months    = activeOpt.getAttribute('data-months');
        const price     = activeOpt.getAttribute('data-price');

        const durationInput = document.getElementById('ozMemDuration');
        const amountInput   = document.getElementById('ozMemAmount');

        if (durationInput) durationInput.value = months;
        if (amountInput) amountInput.value = parseFloat(price).toFixed(2);

        window.ozSyncCardDisplay();
    };

    // Sync Live Digital Card
    window.ozSyncCardDisplay = function() {
        const nameVal       = document.getElementById('ozMemName').value.trim();
        const selectBox     = document.getElementById('ozMemPlan');
        const planName      = selectBox ? selectBox.options[selectBox.selectedIndex].value : 'Monthly Sky Pass';
        const durationInput = document.getElementById('ozMemDuration');
        const monthsCount   = durationInput ? (parseInt(durationInput.value, 10) || 1) : 1;

        const holderEl = document.getElementById('ozCardHolder');
        const tierPill = document.getElementById('ozCardTierPill');
        const expiryEl = document.getElementById('ozCardExpiryDate');

        if (holderEl) holderEl.textContent = nameVal ? nameVal : <?php echo wp_json_encode( __( 'Farhan Chowdhury', 'ozone-skypool' ) ); ?>;
        if (tierPill) tierPill.textContent = planName;

        const calcDate = new Date();
        calcDate.setMonth(calcDate.getMonth() + monthsCount);

        const yyyy = calcDate.getFullYear();
        const mm   = String(calcDate.getMonth() + 1).padStart(2, '0');
        const dd   = String(calcDate.getDate()).padStart(2, '0');

        if (expiryEl) expiryEl.textContent = yyyy + '-' + mm + '-' + dd;
    };

    // Filter Directory
    window.ozFilterDirectory = function() {
        const inputEl   = document.getElementById('ozMemberFilterInput');
        const clearBtn  = document.getElementById('ozFilterClearBtn');
        const filterStr = inputEl ? inputEl.value.toLowerCase().trim() : '';
        const rows      = document.querySelectorAll('.oz-member-record-row');
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

        const counterEl = document.getElementById('ozRecordCounter');
        if (counterEl) {
            counterEl.textContent = filterStr 
                ? <?php echo wp_json_encode( __( 'Matching records: ', 'ozone-skypool' ) ); ?> + matched
                : <?php echo wp_json_encode( sprintf( __( 'Total: %d members', 'ozone-skypool' ), count( $members ) ) ); ?>;
        }
    };

    window.ozClearFilter = function() {
        const inputEl = document.getElementById('ozMemberFilterInput');
        if (inputEl) {
            inputEl.value = '';
            window.ozFilterDirectory();
            inputEl.focus();
        }
    };

    function init() {
        ['ozMemName', 'ozMemPhone'].forEach(id => {
            const input = document.getElementById(id);
            if (input) input.addEventListener('input', window.ozSyncCardDisplay);
        });

        window.ozSyncCardDisplay();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>