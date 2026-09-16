<?php
/**
 * View: Terminal Configuration & Master Settings (Executive Edition)
 *
 * @package Ozone_Skypool_OS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$currency      = esc_html( get_option( 'ifs_pms_currency', 'BDT' ) );
$b_name        = esc_html( get_option( 'ifs_pms_business_name', 'Ozone Restaurant & Skypool' ) );
$ticket_price  = (float) get_option( 'ifs_pms_ticket_price', 500.00 );
$phone         = esc_html( get_option( 'ifs_pms_phone', '+880 1700-000000' ) );
$address       = esc_html( get_option( 'ifs_pms_address', '20th Floor, Ritz Tower, Dargah Gate, Sylhet' ) );
$capacity      = (int) get_option( 'ifs_pms_max_capacity', 80 );
$receipt_note  = esc_textarea( get_option( 'ifs_pms_receipt_note', 'Proper swimwear compulsory. Deck passes non-refundable.' ) );
$logo_url      = esc_attr( get_option( 'ifs_pms_logo_url', '' ) );
$base_url      = admin_url( 'admin.php?page=ifs-pms' );

// POS Settings & Switches
$active_tab           = sanitize_key( $_GET['tab'] ?? 'pos' );
$enable_strict_scan   = get_option( 'ifs_pms_enable_strict_scan', '1' );
$auto_expire_passes   = get_option( 'ifs_pms_auto_expire_passes', '1' );
$print_auto_popup     = get_option( 'ifs_pms_print_auto_popup', '1' );
$enable_audio_buzzer  = get_option( 'ifs_pms_enable_audio_buzzer', '1' );
$enable_amenities     = get_option( 'ifs_pms_enable_amenities', '1' );
$pool_status          = get_option( 'ifs_pms_pool_status', 'open' );

$pricing_tiers = get_option( 'ifs_pms_pricing_tiers', array(
    array( 'name' => 'Standard Adult Swim Pass', 'age_group' => 'Adult (13+ yrs)', 'price' => 500.00, 'features' => '2-Hour Pool Access, Locker, Towel Service' ),
    array( 'name' => 'Kids Splash Pass', 'age_group' => 'Child (4-12 yrs)', 'price' => 300.00, 'features' => 'Pool Access, Kid Vest, Toy Float' ),
) );

$amenity_addons = get_option( 'ifs_pms_amenity_addons', array(
    array( 'name' => 'Fresh Towel', 'price' => 50.00 ),
    array( 'name' => 'Swim Goggles', 'price' => 100.00 ),
    array( 'name' => 'Swimwear Trunk', 'price' => 150.00 ),
) );

$enabled_tenders = get_option( 'ifs_pms_enabled_tenders', array( 'Cash', 'bKash / Nagad', 'Card POS', 'Complimentary' ) );
$quick_cash_str  = esc_attr( get_option( 'ifs_pms_quick_cash_presets', '500, 1000' ) );

// Day-Wise Operating Hours
$days_map = array(
    'monday'    => __( 'Monday', 'ozone-skypool' ),
    'tuesday'   => __( 'Tuesday', 'ozone-skypool' ),
    'wednesday' => __( 'Wednesday', 'ozone-skypool' ),
    'thursday'  => __( 'Thursday', 'ozone-skypool' ),
    'friday'    => __( 'Friday', 'ozone-skypool' ),
    'saturday'  => __( 'Saturday', 'ozone-skypool' ),
    'sunday'    => __( 'Sunday', 'ozone-skypool' ),
);

$weekly_schedule = get_option( 'ifs_pms_weekly_schedule', array(
    'monday'    => array( 'status' => 'open', 'open' => '10:00', 'close' => '23:00' ),
    'tuesday'   => array( 'status' => 'open', 'open' => '10:00', 'close' => '23:00' ),
    'wednesday' => array( 'status' => 'open', 'open' => '10:00', 'close' => '23:00' ),
    'thursday'  => array( 'status' => 'open', 'open' => '10:00', 'close' => '23:00' ),
    'friday'    => array( 'status' => 'open', 'open' => '10:00', 'close' => '23:59' ),
    'saturday'  => array( 'status' => 'open', 'open' => '10:00', 'close' => '23:59' ),
    'sunday'    => array( 'status' => 'open', 'open' => '10:00', 'close' => '23:00' ),
) );
?>

<style>
    /* ==========================================================================
       EXECUTIVE SETTINGS CONSOLE & MODERN LAYOUT STYLING
       ========================================================================== */
    .oz-settings-wrapper {
        display: flex;
        flex-direction: column;
        gap: 24px;
        width: 100%;
        box-sizing: border-box;
    }

    /* Sub Navigation Tabs */
    .oz-subnav-bar {
        display: flex;
        width: 100%;
        gap: 8px;
        background: var(--ifs-surface-hover, #f1f5f9);
        padding: 6px;
        border-radius: 14px;
        border: 1px solid var(--ifs-border-subtle, #e2e8f0);
        box-sizing: border-box;
    }

    .oz-subnav-btn {
        flex: 1 1 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 20px;
        border-radius: 10px;
        font-size: 13.5px;
        font-weight: 700;
        cursor: pointer;
        background: transparent;
        color: var(--ifs-text-secondary, #475569);
        border: none !important;
        outline: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        text-align: center;
        white-space: nowrap;
    }

    .oz-subnav-btn:hover { color: var(--ifs-text-primary, #0f172a); }
    .oz-subnav-btn.active {
        background: var(--ifs-surface, #ffffff) !important;
        color: var(--ifs-accent, #0284c7) !important;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
    }

    .oz-tab-pane { display: none; }
    .oz-tab-pane.active {
        display: block;
        animation: ozFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes ozFadeIn {
        from { opacity: 0; transform: translateY(3px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Panel Card */
    .oz-panel-card {
        background: var(--ifs-surface, #ffffff);
        border: 1px solid var(--ifs-border-subtle, #e2e8f0);
        border-radius: 18px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.03);
        box-sizing: border-box;
        overflow: hidden;
        margin-bottom: 24px;
    }

    .oz-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 28px;
        border-bottom: 1px solid var(--ifs-border-subtle, #e2e8f0);
        background: var(--ifs-surface-hover, #fafbfd);
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

    .oz-form-stack {
        padding: 28px;
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
        font-size: 12.5px;
        font-weight: 700;
        color: var(--ifs-text-secondary, #334155);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .oz-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }

    @media (max-width: 760px) {
        .oz-grid-2 { grid-template-columns: 1fr; }
    }

    #wpcontent .oz-settings-wrapper input[type="text"],
    #wpcontent .oz-settings-wrapper input[type="url"],
    #wpcontent .oz-settings-wrapper input[type="number"],
    #wpcontent .oz-settings-wrapper input[type="time"],
    #wpcontent .oz-settings-wrapper textarea,
    #wpcontent .oz-settings-wrapper select {
        display: block !important;
        width: 100% !important;
        background: var(--ifs-surface, #ffffff) !important;
        border: 1.5px solid var(--ifs-border-strong, #cbd5e1) !important;
        border-radius: 10px !important;
        padding: 11px 16px !important;
        font-size: 13.5px !important;
        font-family: var(--ifs-font-sans, inherit) !important;
        color: var(--ifs-text-primary, #0f172a) !important;
        box-sizing: border-box !important;
    }

    #wpcontent .oz-settings-wrapper input:focus,
    #wpcontent .oz-settings-wrapper textarea:focus,
    #wpcontent .oz-settings-wrapper select:focus {
        border-color: var(--ifs-border-focus, #0284c7) !important;
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
        outline: none !important;
    }

    /* Modern Toggle Switch Component */
    .oz-switch-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: var(--ifs-surface-hover, #f8fafc);
        border: 1.5px solid var(--ifs-border-subtle, #e2e8f0);
        padding: 16px 20px;
        border-radius: 14px;
        transition: border-color 0.15s ease;
    }

    .oz-switch-card:hover { border-color: var(--ifs-border-strong, #cbd5e1); }

    .oz-switch-meta { display: flex; flex-direction: column; gap: 2px; }
    .oz-switch-title { font-size: 13.5px; font-weight: 800; color: var(--ifs-text-primary, #0f172a); }
    .oz-switch-desc { font-size: 11.5px; color: var(--ifs-text-tertiary, #64748b); }

    .oz-switch {
        position: relative; display: inline-block; width: 48px; height: 26px; flex-shrink: 0;
    }
    .oz-switch input { opacity: 0; width: 0; height: 0; }
    .oz-slider {
        position: absolute; cursor: pointer; inset: 0; background-color: #cbd5e1;
        transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1); border-radius: 26px;
    }
    .oz-slider:before {
        position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px;
        background-color: white; transition: 0.25s cubic-bezier(0.4, 0, 0.2, 1); border-radius: 50%;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }
    .oz-switch input:checked + .oz-slider { background-color: var(--ifs-accent, #0284c7); }
    .oz-switch input:checked + .oz-slider:before { transform: translateX(22px); }

    /* Repeater Table */
    .oz-repeater-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
    .oz-repeater-table th {
        background: var(--ifs-surface-hover, #fafbfd); color: var(--ifs-text-tertiary, #64748b);
        font-weight: 700; font-size: 11.5px; text-transform: uppercase; padding: 10px 14px;
        border: 1px solid var(--ifs-border-subtle, #e2e8f0); text-align: left;
    }
    .oz-repeater-table td { padding: 10px 14px; border: 1px solid var(--ifs-border-subtle, #e2e8f0); vertical-align: middle; }

    .oz-check-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-top: 6px; }

    #wpcontent .oz-settings-wrapper .oz-btn {
        display: inline-flex !important; align-items: center !important; justify-content: center !important;
        gap: 8px !important; font-weight: 700 !important; cursor: pointer !important; box-sizing: border-box !important; text-decoration: none !important;
    }
    #wpcontent .oz-settings-wrapper .oz-btn-primary.oz-btn-lg {
        height: 50px !important; padding: 0 24px !important; font-size: 14.5px !important; font-weight: 800 !important;
        border-radius: 12px !important; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important; color: #ffffff !important;
        border: 1px solid rgba(2, 132, 199, 0.8) !important; box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35) !important;
    }
    #wpcontent .oz-settings-wrapper .oz-btn-secondary {
        height: 38px !important; padding: 0 16px !important; font-size: 12.5px !important; border-radius: 10px !important;
        background: var(--ifs-surface, #ffffff) !important; color: var(--ifs-text-secondary, #475569) !important; border: 1.5px solid var(--ifs-border-strong, #cbd5e1) !important;
    }
    #wpcontent .oz-settings-wrapper .oz-btn-danger.oz-btn-sm {
        height: 34px !important; padding: 0 10px !important; font-size: 12px !important; border-radius: 8px !important;
        background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%) !important; color: #ffffff !important; border: 1px solid rgba(225, 29, 72, 0.5) !important;
    }
</style>

<div class="oz-settings-wrapper">
    <!-- Sub Navigation Tab Bar -->
    <div class="oz-subnav-bar" role="tablist">
        <button type="button" class="oz-subnav-btn <?php echo ( $active_tab === 'pos' ) ? 'active' : ''; ?>" onclick="ozSwitchSettingsTab('pos', this)">
            <i class="fa-solid fa-cash-register"></i> <?php esc_html_e( 'Tickets POS & Hardware', 'ozone-skypool' ); ?>
        </button>
        <button type="button" class="oz-subnav-btn <?php echo ( $active_tab === 'general' ) ? 'active' : ''; ?>" onclick="ozSwitchSettingsTab('general', this)">
            <i class="fa-solid fa-building"></i> <?php esc_html_e( 'Venue & Receipt Header', 'ozone-skypool' ); ?>
        </button>
    </div>

    <form method="POST" action="<?php echo esc_url( $base_url . '&view=settings' ); ?>" id="ozSettingsForm">
        <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
        <input type="hidden" name="ifs_pms_action" value="save_settings">
        <input type="hidden" name="ifs_pms_active_tab" id="ozActiveTabInput" value="<?php echo esc_attr( $active_tab ); ?>">

        <!-- TAB 1: Tickets POS Configuration & Hardware Switches -->
        <div id="ozSettingsPanePos" class="oz-tab-pane <?php echo ( $active_tab === 'pos' ) ? 'active' : ''; ?>">
            <!-- 1. Admission Packages (Adult & Kids Pricing Tiers) -->
            <div class="oz-panel-card">
                <div class="oz-panel-head">
                    <h3 class="oz-panel-title">
                        <i class="fa-solid fa-ticket" style="color: var(--ifs-accent, #0284c7);"></i>
                        <?php esc_html_e( 'Admission Packages (Adult & Kids Pricing Tiers with Age)', 'ozone-skypool' ); ?>
                    </h3>
                    <button type="button" class="oz-btn oz-btn-secondary" onclick="ozAddPricingTierRow()">
                        <i class="fa-solid fa-plus"></i> <?php esc_html_e( 'Add Package Tier', 'ozone-skypool' ); ?>
                    </button>
                </div>

                <div class="oz-form-stack">
                    <table class="oz-repeater-table" id="ozPricingTierTable">
                        <thead>
                            <tr>
                                <th style="width: 28%;"><?php esc_html_e( 'Package Title', 'ozone-skypool' ); ?></th>
                                <th style="width: 22%;"><?php esc_html_e( 'Category / Age Group', 'ozone-skypool' ); ?></th>
                                <th style="width: 16%;"><?php printf( esc_html__( 'Fee (%s)', 'ozone-skypool' ), esc_html( $currency ) ); ?></th>
                                <th style="width: 26%;"><?php esc_html_e( 'Privileges & Features', 'ozone-skypool' ); ?></th>
                                <th style="width: 8%; text-align: center;"><?php esc_html_e( 'Action', 'ozone-skypool' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( ! empty( $pricing_tiers ) ) : ?>
                                <?php foreach ( $pricing_tiers as $tier ) : ?>
                                    <tr>
                                        <td><input type="text" name="ifs_pricing_tier_name[]" value="<?php echo esc_attr( $tier['name'] ); ?>" required placeholder="Package Name"></td>
                                        <td><input type="text" name="ifs_pricing_tier_age[]" value="<?php echo esc_attr( $tier['age_group'] ?? 'Adult (13+ yrs)' ); ?>" required placeholder="e.g. Child (4-12 yrs)"></td>
                                        <td><input type="number" step="0.01" name="ifs_pricing_tier_price[]" value="<?php echo esc_attr( $tier['price'] ); ?>" required class="ifs-pms-mono"></td>
                                        <td><input type="text" name="ifs_pricing_tier_features[]" value="<?php echo esc_attr( $tier['features'] ); ?>" placeholder="Features"></td>
                                        <td style="text-align: center;">
                                            <button type="button" class="oz-btn oz-btn-danger oz-btn-sm" onclick="ozRemoveRow(this)"><i class="fa-solid fa-trash-can"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Swimming Pool Operational State -->
            <div class="oz-panel-card">
                <div class="oz-panel-head">
                    <h3 class="oz-panel-title">
                        <i class="fa-solid fa-water-ladder" style="color: var(--ifs-accent, #0284c7);"></i>
                        <?php esc_html_e( 'Swimming Pool Operational State & Gate Control', 'ozone-skypool' ); ?>
                    </h3>
                </div>
                <div class="oz-form-stack">
                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php esc_html_e( 'Select Active Pool Status', 'ozone-skypool' ); ?></label>
                        <select name="ifs_pms_pool_status" id="ozPoolStatusSelect" style="font-weight: 700; height: 46px;">
                            <option value="open" <?php selected( $pool_status, 'open' ); ?>>🟢 Open (Accepting Guests & Sales)</option>
                            <option value="closed" <?php selected( $pool_status, 'closed' ); ?>>🔴 Closed (Sales Suspended)</option>
                            <option value="maintenance" <?php selected( $pool_status, 'maintenance' ); ?>>🟡 Maintenance Mode (Cleaning & Servicing)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 3. Day-Wise Operating Hours -->
            <div class="oz-panel-card">
                <div class="oz-panel-head">
                    <h3 class="oz-panel-title">
                        <i class="fa-solid fa-clock" style="color: var(--ifs-accent, #0284c7);"></i>
                        <?php esc_html_e( 'Day-Wise Operating & Gate Hours', 'ozone-skypool' ); ?>
                    </h3>
                </div>
                <div class="oz-form-stack">
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ( $days_map as $day_key => $day_label ) : 
                            $day_data = $weekly_schedule[ $day_key ] ?? array( 'status' => 'open', 'open' => '10:00', 'close' => '23:00' );
                            $is_open  = ( $day_data['status'] === 'open' );
                        ?>
                            <div style="display: grid; grid-template-columns: 160px 140px 1fr 1fr; gap: 14px; align-items: center; background: var(--ifs-surface-hover, #f8fafc); border: 1px solid var(--ifs-border-subtle, #e2e8f0); padding: 12px 18px; border-radius: 12px;">
                                <strong style="font-size: 13.5px; color: var(--ifs-text-primary);"><?php echo esc_html( $day_label ); ?></strong>
                                
                                <select name="ifs_pms_schedule_<?php echo esc_attr( $day_key ); ?>_status" id="ozSchedStatus_<?php echo esc_attr( $day_key ); ?>" onchange="ozToggleDayHours('<?php echo esc_attr( $day_key ); ?>')">
                                    <option value="open" <?php selected( $is_open, true ); ?>>🟢 Open</option>
                                    <option value="closed" <?php selected( $is_open, false ); ?>>🔴 Closed</option>
                                </select>

                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 11px; font-weight: 700; color: var(--ifs-text-tertiary);"><?php esc_html_e( 'Open:', 'ozone-skypool' ); ?></span>
                                    <input type="time" name="ifs_pms_schedule_<?php echo esc_attr( $day_key ); ?>_open" id="ozOpen_<?php echo esc_attr( $day_key ); ?>" value="<?php echo esc_attr( $day_data['open'] ); ?>" class="ifs-pms-mono" style="height: 38px !important;">
                                </div>

                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 11px; font-weight: 700; color: var(--ifs-text-tertiary);"><?php esc_html_e( 'Close:', 'ozone-skypool' ); ?></span>
                                    <input type="time" name="ifs_pms_schedule_<?php echo esc_attr( $day_key ); ?>_close" id="ozClose_<?php echo esc_attr( $day_key ); ?>" value="<?php echo esc_attr( $day_data['close'] ); ?>" class="ifs-pms-mono" style="height: 38px !important;">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 4. Amenity Add-Ons Master Toggle & Repeater -->
            <div class="oz-panel-card">
                <div class="oz-panel-head">
                    <h3 class="oz-panel-title">
                        <i class="fa-solid fa-shirt" style="color: var(--ifs-accent, #0284c7);"></i>
                        <?php esc_html_e( 'Amenity Add-Ons & Gear Rentals Console', 'ozone-skypool' ); ?>
                    </h3>
                </div>
                <div class="oz-form-stack">
                    <div class="oz-switch-card">
                        <div class="oz-switch-meta">
                            <div class="oz-switch-title"><?php esc_html_e( 'Enable Amenity Add-Ons on POS', 'ozone-skypool' ); ?></div>
                            <div class="oz-switch-desc"><?php esc_html_e( 'Master switch to show or completely hide gear rentals on checkout.', 'ozone-skypool' ); ?></div>
                        </div>
                        <label class="oz-switch">
                            <input type="checkbox" name="ifs_pms_enable_amenities" value="1" <?php checked( $enable_amenities, '1' ); ?>>
                            <span class="oz-slider"></span>
                        </label>
                    </div>

                    <div id="ozAmenitiesRepeaterBox" style="<?php echo ( $enable_amenities !== '1' ) ? 'display:none;' : ''; ?>">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin: 12px 0 8px 0;">
                            <label class="oz-field-label" style="font-size: 14px; margin: 0;"><?php esc_html_e( 'Gear Inventory & Rental Rates', 'ozone-skypool' ); ?></label>
                            <button type="button" class="oz-btn oz-btn-secondary" onclick="ozAddAddonRow()">
                                <i class="fa-solid fa-plus"></i> <?php esc_html_e( 'Add Amenity', 'ozone-skypool' ); ?>
                            </button>
                        </div>

                        <table class="oz-repeater-table" id="ozAmenityAddonTable">
                            <thead>
                                <tr>
                                    <th style="width: 55%;"><?php esc_html_e( 'Amenity Item Name', 'ozone-skypool' ); ?></th>
                                    <th style="width: 35%;"><?php printf( esc_html__( 'Rental Price (%s)', 'ozone-skypool' ), esc_html( $currency ) ); ?></th>
                                    <th style="width: 10%; text-align: center;"><?php esc_html_e( 'Action', 'ozone-skypool' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ( ! empty( $amenity_addons ) ) : ?>
                                    <?php foreach ( $amenity_addons as $addon ) : ?>
                                        <tr>
                                            <td><input type="text" name="ifs_addon_name[]" value="<?php echo esc_attr( $addon['name'] ); ?>" required placeholder="Amenity Name"></td>
                                            <td><input type="number" step="0.01" name="ifs_addon_price[]" value="<?php echo esc_attr( $addon['price'] ); ?>" required class="ifs-pms-mono"></td>
                                            <td style="text-align: center;">
                                                <button type="button" class="oz-btn oz-btn-danger oz-btn-sm" onclick="ozRemoveRow(this)"><i class="fa-solid fa-trash-can"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 5. Tenders & Hardware Automation -->
            <div class="oz-panel-card">
                <div class="oz-panel-head">
                    <h3 class="oz-panel-title">
                        <i class="fa-solid fa-credit-card" style="color: var(--ifs-accent, #0284c7);"></i>
                        <?php esc_html_e( 'Payment Tenders & Hardware Controls', 'ozone-skypool' ); ?>
                    </h3>
                </div>
                <div class="oz-form-stack">
                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php esc_html_e( 'Active Tender Methods on POS', 'ozone-skypool' ); ?></label>
                        <div class="oz-check-grid">
                            <?php
                            $available_tenders = array(
                                'Cash'          => __( 'Cash Tender', 'ozone-skypool' ),
                                'bKash / Nagad' => __( 'Mobile Banking (MFS)', 'ozone-skypool' ),
                                'Card POS'      => __( 'Card Terminal (POS)', 'ozone-skypool' ),
                                'Complimentary' => __( 'VIP / Complimentary', 'ozone-skypool' ),
                            );
                            foreach ( $available_tenders as $tender_key => $tender_label ) :
                                $checked = in_array( $tender_key, $enabled_tenders, true ) ? 'checked' : '';
                            ?>
                                <div class="oz-switch-card" style="padding: 12px 16px;">
                                    <div style="font-size: 13px; font-weight: 700; color: var(--ifs-text-primary);"><?php echo esc_html( $tender_label ); ?></div>
                                    <label class="oz-switch">
                                        <input type="checkbox" name="ifs_pms_enabled_tenders[]" value="<?php echo esc_attr( $tender_key ); ?>" <?php echo esc_attr( $checked ); ?>>
                                        <span class="oz-slider"></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="oz-grid-2" style="margin-top: 4px;">
                        <div class="oz-field-group">
                            <label class="oz-field-label" for="ozQuickCashPresets"><?php esc_html_e( 'Quick Cash Suggestion Chips (Comma Separated)', 'ozone-skypool' ); ?></label>
                            <input type="text" name="ifs_pms_quick_cash_presets" id="ozQuickCashPresets" value="<?php echo $quick_cash_str; ?>" placeholder="500, 1000, 2000" class="ifs-pms-mono">
                        </div>
                        <div class="oz-field-group">
                            <label class="oz-field-label" for="ozThermalPaperWidth"><?php esc_html_e( 'POS Thermal Paper Standard', 'ozone-skypool' ); ?></label>
                            <select name="ifs_pms_thermal_paper_width" id="ozThermalPaperWidth">
                                <option value="80mm" <?php selected( get_option( 'ifs_pms_thermal_paper_width', '80mm' ), '80mm' ); ?>>80mm Standard POS Slip</option>
                                <option value="58mm" <?php selected( get_option( 'ifs_pms_thermal_paper_width' ), '58mm' ); ?>>58mm Mobile Bluetooth Printer</option>
                            </select>
                        </div>
                    </div>

                    <hr style="border: 0; border-top: 1px solid var(--ifs-border-subtle); margin: 12px 0;">

                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <div class="oz-switch-card">
                            <div class="oz-switch-meta">
                                <div class="oz-switch-title"><?php esc_html_e( 'Strict Turnstile Pass Enforcement', 'ozone-skypool' ); ?></div>
                                <div class="oz-switch-desc"><?php esc_html_e( 'Prevents double-entry by invalidating passes immediately after initial scan.', 'ozone-skypool' ); ?></div>
                            </div>
                            <label class="oz-switch">
                                <input type="checkbox" name="ifs_pms_enable_strict_scan" value="1" <?php checked( $enable_strict_scan, '1' ); ?>>
                                <span class="oz-slider"></span>
                            </label>
                        </div>

                        <div class="oz-switch-card">
                            <div class="oz-switch-meta">
                                <div class="oz-switch-title"><?php esc_html_e( 'Auto-Expire Daily Passes at Midnight', 'ozone-skypool' ); ?></div>
                                <div class="oz-switch-desc"><?php esc_html_e( 'Automatically voids unredeemed single-admission passes at midnight.', 'ozone-skypool' ); ?></div>
                            </div>
                            <label class="oz-switch">
                                <input type="checkbox" name="ifs_pms_auto_expire_passes" value="1" <?php checked( $auto_expire_passes, '1' ); ?>>
                                <span class="oz-slider"></span>
                            </label>
                        </div>

                        <div class="oz-switch-card">
                            <div class="oz-switch-meta">
                                <div class="oz-switch-title"><?php esc_html_e( 'Acoustic Audio Buzzer Feedback', 'ozone-skypool' ); ?></div>
                                <div class="oz-switch-desc"><?php esc_html_e( 'Plays synthesized success chimes or rejection tones on POS and turnstile scans.', 'ozone-skypool' ); ?></div>
                            </div>
                            <label class="oz-switch">
                                <input type="checkbox" name="ifs_pms_enable_audio_buzzer" value="1" <?php checked( $enable_audio_buzzer, '1' ); ?>>
                                <span class="oz-slider"></span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="oz-btn oz-btn-primary oz-btn-lg" style="width: 100%; margin-top: 14px;">
                        <i class="fa-solid fa-floppy-disk"></i> <?php esc_html_e( 'Save All POS & Hardware Configurations', 'ozone-skypool' ); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- TAB 2: Business, Logo & Receipt Header -->
        <div id="ozSettingsPaneGeneral" class="oz-tab-pane <?php echo ( $active_tab === 'general' ) ? 'active' : ''; ?>">
            <div class="oz-panel-card">
                <div class="oz-panel-head">
                    <h3 class="oz-panel-title">
                        <i class="fa-solid fa-building" style="color: var(--ifs-accent, #0284c7);"></i>
                        <?php esc_html_e( 'Venue Identity, Logo & Slip Details', 'ozone-skypool' ); ?>
                    </h3>
                </div>

                <div class="oz-form-stack">
                    <div class="oz-grid-2">
                        <div class="oz-field-group">
                            <label class="oz-field-label" for="ozBusinessName"><?php esc_html_e( 'Venue / Business Name', 'ozone-skypool' ); ?> *</label>
                            <input type="text" name="ifs_pms_business_name" id="ozBusinessName" value="<?php echo esc_attr( $b_name ); ?>" required>
                        </div>
                        <div class="oz-field-group">
                            <label class="oz-field-label" for="ozCurrency"><?php esc_html_e( 'Currency Code', 'ozone-skypool' ); ?> *</label>
                            <input type="text" name="ifs_pms_currency" id="ozCurrency" value="<?php echo esc_attr( $currency ); ?>" required class="ifs-pms-mono">
                        </div>
                    </div>

                    <div class="oz-field-group">
                        <label class="oz-field-label" for="ozLogoUrl"><?php esc_html_e( 'Venue Logo Image URL', 'ozone-skypool' ); ?></label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="url" name="ifs_pms_logo_url" id="ozLogoUrl" value="<?php echo esc_attr( $logo_url ); ?>" placeholder="https://example.com/logo.png">
                            <button type="button" class="oz-btn oz-btn-secondary" onclick="ozOpenMediaUploader()"><?php esc_html_e( 'Upload', 'ozone-skypool' ); ?></button>
                        </div>
                        <?php if ( ! empty( $logo_url ) ) : ?>
                            <div style="margin-top: 10px;">
                                <img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo Preview" style="max-height: 50px; width: auto; border-radius: 8px; border: 1px solid var(--ifs-border-subtle); padding: 4px; background: #fff;">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="oz-grid-2">
                        <div class="oz-field-group">
                            <label class="oz-field-label" for="ozPhone"><?php esc_html_e( 'Customer Support Phone', 'ozone-skypool' ); ?></label>
                            <input type="text" name="ifs_pms_phone" id="ozPhone" value="<?php echo esc_attr( $phone ); ?>">
                        </div>
                        <div class="oz-field-group">
                            <label class="oz-field-label" for="ozCapacity"><?php esc_html_e( 'Max Rooftop Capacity', 'ozone-skypool' ); ?></label>
                            <input type="number" name="ifs_pms_max_capacity" id="ozCapacity" value="<?php echo esc_attr( (string) $capacity ); ?>">
                        </div>
                    </div>

                    <div class="oz-field-group">
                        <label class="oz-field-label" for="ozAddress"><?php esc_html_e( 'Address Header Line', 'ozone-skypool' ); ?></label>
                        <input type="text" name="ifs_pms_address" id="ozAddress" value="<?php echo esc_attr( $address ); ?>">
                    </div>

                    <div class="oz-field-group">
                        <label class="oz-field-label" for="ozReceiptNote"><?php esc_html_e( 'Thermal Receipt Footer Terms', 'ozone-skypool' ); ?></label>
                        <textarea name="ifs_pms_receipt_note" id="ozReceiptNote" rows="3"><?php echo $receipt_note; ?></textarea>
                    </div>

                    <button type="submit" class="oz-btn oz-btn-primary oz-btn-lg" style="width: 100%; margin-top: 14px;">
                        <i class="fa-solid fa-floppy-disk"></i> <?php esc_html_e( 'Save General Configuration', 'ozone-skypool' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function ozSwitchSettingsTab(tabKey, btn) {
    document.querySelectorAll('.oz-subnav-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    document.getElementById('ozSettingsPanePos').classList.remove('active');
    document.getElementById('ozSettingsPaneGeneral').classList.remove('active');

    if (tabKey === 'pos') {
        document.getElementById('ozSettingsPanePos').classList.add('active');
    } else {
        document.getElementById('ozSettingsPaneGeneral').classList.add('active');
    }

    document.getElementById('ozActiveTabInput').value = tabKey;

    if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.set('tab', tabKey);
        window.history.replaceState({}, '', url);
    }
}

function ozAddPricingTierRow() {
    const tableBody = document.querySelector('#ozPricingTierTable tbody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="text" name="ifs_pricing_tier_name[]" required placeholder="Package Name"></td>
        <td><input type="text" name="ifs_pricing_tier_age[]" required placeholder="e.g. Child (4-12 yrs)"></td>
        <td><input type="number" step="0.01" name="ifs_pricing_tier_price[]" required class="ifs-pms-mono" value="0.00"></td>
        <td><input type="text" name="ifs_pricing_tier_features[]" placeholder="Features"></td>
        <td style="text-align: center;">
            <button type="button" class="oz-btn oz-btn-danger oz-btn-sm" onclick="ozRemoveRow(this)"><i class="fa-solid fa-trash-can"></i></button>
        </td>
    `;
    tableBody.appendChild(row);
}

function ozAddAddonRow() {
    const tableBody = document.querySelector('#ozAmenityAddonTable tbody');
    const row = document.createElement('tr');
    row.innerHTML = `
        <td><input type="text" name="ifs_addon_name[]" required placeholder="Amenity Name"></td>
        <td><input type="number" step="0.01" name="ifs_addon_price[]" required class="ifs-pms-mono" value="50.00"></td>
        <td style="text-align: center;">
            <button type="button" class="oz-btn oz-btn-danger oz-btn-sm" onclick="ozRemoveRow(this)"><i class="fa-solid fa-trash-can"></i></button>
        </td>
    `;
    tableBody.appendChild(row);
}

function ozRemoveRow(btn) {
    const tr = btn.closest('tr');
    if (tr) tr.remove();
}

function ozOpenMediaUploader() {
    if (typeof wp === 'undefined' || !wp.media) {
        alert('WordPress Media Uploader is loading or unavailable. Please refresh the page.');
        return;
    }
    const mediaUploader = wp.media({
        title: 'Select Venue Logo',
        button: { text: 'Use this logo' },
        multiple: false
    });
    mediaUploader.on('select', function() {
        const attachment = mediaUploader.state().get('selection').first().toJSON();
        document.getElementById('ozLogoUrl').value = attachment.url;
    });
    mediaUploader.open();
}

document.addEventListener('DOMContentLoaded', function() {
    const amenCheckbox = document.querySelector('input[name="ifs_pms_enable_amenities"]');
    const amenBox = document.getElementById('ozAmenitiesRepeaterBox');
    if (amenCheckbox && amenBox) {
        amenCheckbox.addEventListener('change', function() {
            amenBox.style.display = this.checked ? 'block' : 'none';
        });
    }
});
</script>