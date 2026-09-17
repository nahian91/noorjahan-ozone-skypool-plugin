<?php
/**
 * View: Terminal Configuration & Master Settings (100% Dynamic Database Driven - Unified ifs-pms- CSS Prefix)
 *
 * @package Ozone_Skypool_OS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$currency      = esc_html( (string) get_option( 'ifs_pms_currency', '' ) );
$b_name        = esc_html( (string) get_option( 'ifs_pms_business_name', '' ) );
$phone         = esc_html( (string) get_option( 'ifs_pms_phone', '' ) );
$address       = esc_html( (string) get_option( 'ifs_pms_address', '' ) );
$capacity_raw  = get_option( 'ifs_pms_max_capacity', '' );
$capacity      = ( $capacity_raw !== '' && is_numeric( $capacity_raw ) ) ? (int) $capacity_raw : '';
$receipt_note  = esc_textarea( (string) get_option( 'ifs_pms_receipt_note', '' ) );
$logo_url      = esc_attr( (string) get_option( 'ifs_pms_logo_url', '' ) );
$base_url      = admin_url( 'admin.php?page=ifs-pms' );

// POS Operational States & Toggles - Strictly Pulled from Database
$active_tab          = sanitize_key( $_GET['tab'] ?? 'pos' );
$enable_strict_scan  = (string) get_option( 'ifs_pms_enable_strict_scan', '' );
$auto_expire_passes  = (string) get_option( 'ifs_pms_auto_expire_passes', '' );
$enable_audio_buzzer = (string) get_option( 'ifs_pms_enable_audio_buzzer', '' );
$enable_amenities    = (string) get_option( 'ifs_pms_enable_amenities', '' );
$pool_status         = (string) get_option( 'ifs_pms_pool_status', '' );

$pricing_tiers   = get_option( 'ifs_pms_pricing_tiers', array() );
$amenity_addons  = get_option( 'ifs_pms_amenity_addons', array() );
$enabled_tenders = get_option( 'ifs_pms_enabled_tenders', array() );
if ( ! is_array( $enabled_tenders ) ) {
    $enabled_tenders = array();
}

$quick_cash_str = esc_attr( (string) get_option( 'ifs_pms_quick_cash_presets', '' ) );
$paper_width    = (string) get_option( 'ifs_pms_thermal_paper_width', '' );

// Operating Days
$days_map = array(
    'monday'    => __( 'Monday', 'ozone-skypool' ),
    'tuesday'   => __( 'Tuesday', 'ozone-skypool' ),
    'wednesday' => __( 'Wednesday', 'ozone-skypool' ),
    'thursday'  => __( 'Thursday', 'ozone-skypool' ),
    'friday'    => __( 'Friday', 'ozone-skypool' ),
    'saturday'  => __( 'Saturday', 'ozone-skypool' ),
    'sunday'    => __( 'Sunday', 'ozone-skypool' ),
);

$weekly_schedule = get_option( 'ifs_pms_weekly_schedule', array() );
if ( ! is_array( $weekly_schedule ) ) {
    $weekly_schedule = array();
}
?>

<div class="ifs-pms-settings-wrapper">
    <!-- Navigation Tabs -->
    <div class="ifs-pms-subnav-bar" role="tablist">
        <button type="button" class="ifs-pms-subnav-btn <?php echo ( $active_tab === 'pos' ) ? 'active' : ''; ?>" onclick="ifsPmsSwitchSettingsTab('pos', this)">
            <i class="fa-solid fa-cash-register"></i> <?php esc_html_e( 'Tickets POS & Operations', 'ozone-skypool' ); ?>
        </button>
        <button type="button" class="ifs-pms-subnav-btn <?php echo ( $active_tab === 'general' ) ? 'active' : ''; ?>" onclick="ifsPmsSwitchSettingsTab('general', this)">
            <i class="fa-solid fa-building"></i> <?php esc_html_e( 'Venue & Receipt Header', 'ozone-skypool' ); ?>
        </button>
    </div>

    <form method="POST" action="<?php echo esc_url( $base_url . '&view=settings' ); ?>" id="ifsPmsSettingsForm">
        <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
        <input type="hidden" name="ifs_pms_action" value="save_settings">
        <input type="hidden" name="ifs_pms_active_tab" id="ifsPmsActiveTabInput" value="<?php echo esc_attr( $active_tab ); ?>">

        <!-- TAB 1: POS & Operational Settings -->
        <div id="ifsPmsSettingsPanePos" class="ifs-pms-tab-pane <?php echo ( $active_tab === 'pos' ) ? 'active' : ''; ?>">
            <!-- 1. Admission Package Tiers -->
            <div class="ifs-pms-panel-card">
                <div class="ifs-pms-panel-head">
                    <h3 class="ifs-pms-panel-title">
                        <i class="fa-solid fa-ticket ifs-pms-panel-icon"></i>
                        <?php esc_html_e( 'Admission Packages (Tier Names, Categories & Rates)', 'ozone-skypool' ); ?>
                    </h3>
                    <button type="button" class="ifs-pms-btn ifs-pms-btn-secondary" onclick="ifsPmsAddPricingTierRow()">
                        <i class="fa-solid fa-plus"></i> <?php esc_html_e( 'Add Package Tier', 'ozone-skypool' ); ?>
                    </button>
                </div>

                <div class="ifs-pms-form-stack">
                    <table class="ifs-pms-repeater-table" id="ifsPmsPricingTierTable">
                        <thead>
                            <tr>
                                <th class="ifs-pms-th-tier-name"><?php esc_html_e( 'Package Name', 'ozone-skypool' ); ?></th>
                                <th class="ifs-pms-th-tier-age"><?php esc_html_e( 'Attendee Category / Age Group', 'ozone-skypool' ); ?></th>
                                <th class="ifs-pms-th-tier-price"><?php printf( esc_html__( 'Rate / Hr (%s)', 'ozone-skypool' ), esc_html( $currency ) ); ?></th>
                                <th class="ifs-pms-th-tier-action"><?php esc_html_e( 'Action', 'ozone-skypool' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( ! empty( $pricing_tiers ) && is_array( $pricing_tiers ) ) : ?>
                                <?php foreach ( $pricing_tiers as $tier ) : ?>
                                    <tr>
                                        <td><input type="text" name="ifs_pricing_tier_name[]" value="<?php echo esc_attr( $tier['name'] ?? '' ); ?>" required placeholder="<?php esc_attr_e( 'Enter package title', 'ozone-skypool' ); ?>"></td>
                                        <td><input type="text" name="ifs_pricing_tier_age[]" value="<?php echo esc_attr( $tier['age_group'] ?? '' ); ?>" required placeholder="<?php esc_attr_e( 'Enter age/category group', 'ozone-skypool' ); ?>"></td>
                                        <td><input type="number" step="0.01" name="ifs_pricing_tier_price[]" value="<?php echo esc_attr( $tier['price'] ?? '' ); ?>" required class="ifs-pms-mono" placeholder="0.00"></td>
                                        <td class="ifs-pms-td-action">
                                            <button type="button" class="ifs-pms-btn ifs-pms-btn-danger ifs-pms-btn-sm" onclick="ifsPmsRemoveRow(this)"><i class="fa-solid fa-trash-can"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr class="ifs-pms-empty-row">
                                    <td colspan="4" class="ifs-pms-empty-table-notice">
                                        <?php esc_html_e( 'No package tiers registered. Click "Add Package Tier" above to configure.', 'ozone-skypool' ); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. Swimming Pool Status -->
            <div class="ifs-pms-panel-card">
                <div class="ifs-pms-panel-head">
                    <h3 class="ifs-pms-panel-title">
                        <i class="fa-solid fa-water-ladder ifs-pms-panel-icon"></i>
                        <?php esc_html_e( 'Swimming Pool Operational Status', 'ozone-skypool' ); ?>
                    </h3>
                </div>
                <div class="ifs-pms-form-stack">
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Current Pool State', 'ozone-skypool' ); ?></label>
                        <select name="ifs_pms_pool_status" id="ifsPmsPoolStatusSelect" class="ifs-pms-select-heavy">
                            <option value="open" <?php selected( $pool_status, 'open' ); ?>><?php esc_html_e( '🟢 Open (Accepting Visitors & Ticket Sales)', 'ozone-skypool' ); ?></option>
                            <option value="closed" <?php selected( $pool_status, 'closed' ); ?>><?php esc_html_e( '🔴 Closed (Sales Blocked)', 'ozone-skypool' ); ?></option>
                            <option value="maintenance" <?php selected( $pool_status, 'maintenance' ); ?>><?php esc_html_e( '🟡 Maintenance Mode (Servicing Deck)', 'ozone-skypool' ); ?></option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 3. Day-Wise Operating Hours -->
            <div class="ifs-pms-panel-card">
                <div class="ifs-pms-panel-head">
                    <h3 class="ifs-pms-panel-title">
                        <i class="fa-solid fa-clock ifs-pms-panel-icon"></i>
                        <?php esc_html_e( 'Weekly Operating Hours', 'ozone-skypool' ); ?>
                    </h3>
                </div>
                <div class="ifs-pms-form-stack">
                    <div class="ifs-pms-schedule-stack">
                        <?php foreach ( $days_map as $day_key => $day_label ) : 
                            $day_data = $weekly_schedule[ $day_key ] ?? array();
                            $saved_status = $day_data['status'] ?? '';
                            $saved_open   = $day_data['open'] ?? '';
                            $saved_close  = $day_data['close'] ?? '';
                        ?>
                            <div class="ifs-pms-schedule-row">
                                <strong class="ifs-pms-schedule-day-title"><?php echo esc_html( $day_label ); ?></strong>
                                
                                <select name="ifs_pms_schedule_<?php echo esc_attr( $day_key ); ?>_status" id="ifsPmsSchedStatus_<?php echo esc_attr( $day_key ); ?>" onchange="ifsPmsToggleDayHours('<?php echo esc_attr( $day_key ); ?>')">
                                    <option value="open" <?php selected( $saved_status, 'open' ); ?>><?php esc_html_e( '🟢 Open', 'ozone-skypool' ); ?></option>
                                    <option value="closed" <?php selected( $saved_status, 'closed' ); ?>><?php esc_html_e( '🔴 Closed', 'ozone-skypool' ); ?></option>
                                </select>

                                <div class="ifs-pms-schedule-time-block">
                                    <span class="ifs-pms-schedule-time-label"><?php esc_html_e( 'Open:', 'ozone-skypool' ); ?></span>
                                    <input type="time" name="ifs_pms_schedule_<?php echo esc_attr( $day_key ); ?>_open" id="ifsPmsOpen_<?php echo esc_attr( $day_key ); ?>" value="<?php echo esc_attr( $saved_open ); ?>" class="ifs-pms-mono ifs-pms-time-input">
                                </div>

                                <div class="ifs-pms-schedule-time-block">
                                    <span class="ifs-pms-schedule-time-label"><?php esc_html_e( 'Close:', 'ozone-skypool' ); ?></span>
                                    <input type="time" name="ifs_pms_schedule_<?php echo esc_attr( $day_key ); ?>_close" id="ifsPmsClose_<?php echo esc_attr( $day_key ); ?>" value="<?php echo esc_attr( $saved_close ); ?>" class="ifs-pms-mono ifs-pms-time-input">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 4. Gear Rentals & Amenities -->
            <div class="ifs-pms-panel-card">
                <div class="ifs-pms-panel-head">
                    <h3 class="ifs-pms-panel-title">
                        <i class="fa-solid fa-shirt ifs-pms-panel-icon"></i>
                        <?php esc_html_e( 'Gear Rentals & Amenity Add-Ons', 'ozone-skypool' ); ?>
                    </h3>
                </div>
                <div class="ifs-pms-form-stack">
                    <div class="ifs-pms-switch-card">
                        <div class="ifs-pms-switch-meta">
                            <div class="ifs-pms-switch-title"><?php esc_html_e( 'Enable Gear Rentals on POS', 'ozone-skypool' ); ?></div>
                            <div class="ifs-pms-switch-desc"><?php esc_html_e( 'Toggle to show or completely hide rentable gear at checkout.', 'ozone-skypool' ); ?></div>
                        </div>
                        <label class="ifs-pms-switch">
                            <input type="checkbox" name="ifs_pms_enable_amenities" value="1" <?php checked( $enable_amenities, '1' ); ?>>
                            <span class="ifs-pms-slider"></span>
                        </label>
                    </div>

                    <div id="ifsPmsAmenitiesRepeaterBox" class="ifs-pms-amenities-repeater-wrapper <?php echo ( $enable_amenities === '1' ) ? 'is-visible' : ''; ?>">
                        <div class="ifs-pms-amenities-box-header">
                            <label class="ifs-pms-field-label ifs-pms-field-label-compact"><?php esc_html_e( 'Rentable Gear Inventory & Rates', 'ozone-skypool' ); ?></label>
                            <button type="button" class="ifs-pms-btn ifs-pms-btn-secondary" onclick="ifsPmsAddAddonRow()">
                                <i class="fa-solid fa-plus"></i> <?php esc_html_e( 'Add Amenity Item', 'ozone-skypool' ); ?>
                            </button>
                        </div>

                        <table class="ifs-pms-repeater-table" id="ifsPmsAmenityAddonTable">
                            <thead>
                                <tr>
                                    <th class="ifs-pms-th-addon-name"><?php esc_html_e( 'Amenity Item Name', 'ozone-skypool' ); ?></th>
                                    <th class="ifs-pms-th-addon-price"><?php printf( esc_html__( 'Rental Fee (%s)', 'ozone-skypool' ), esc_html( $currency ) ); ?></th>
                                    <th class="ifs-pms-th-addon-action"><?php esc_html_e( 'Action', 'ozone-skypool' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ( ! empty( $amenity_addons ) && is_array( $amenity_addons ) ) : ?>
                                    <?php foreach ( $amenity_addons as $addon ) : ?>
                                        <tr>
                                            <td><input type="text" name="ifs_addon_name[]" value="<?php echo esc_attr( $addon['name'] ?? '' ); ?>" required placeholder="<?php esc_attr_e( 'Item name', 'ozone-skypool' ); ?>"></td>
                                            <td><input type="number" step="0.01" name="ifs_addon_price[]" value="<?php echo esc_attr( $addon['price'] ?? '' ); ?>" required class="ifs-pms-mono" placeholder="0.00"></td>
                                            <td class="ifs-pms-td-action">
                                                <button type="button" class="ifs-pms-btn ifs-pms-btn-danger ifs-pms-btn-sm" onclick="ifsPmsRemoveRow(this)"><i class="fa-solid fa-trash-can"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr class="ifs-pms-empty-addon-row">
                                        <td colspan="3" class="ifs-pms-empty-table-notice">
                                            <?php esc_html_e( 'No gear rental items currently configured.', 'ozone-skypool' ); ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 5. Payment Methods & POS Defaults -->
            <div class="ifs-pms-panel-card">
                <div class="ifs-pms-panel-head">
                    <h3 class="ifs-pms-panel-title">
                        <i class="fa-solid fa-credit-card ifs-pms-panel-icon"></i>
                        <?php esc_html_e( 'Payment Methods & Checkout Configuration', 'ozone-skypool' ); ?>
                    </h3>
                </div>
                <div class="ifs-pms-form-stack">
                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Active Payment Methods on POS', 'ozone-skypool' ); ?></label>
                        <div class="ifs-pms-check-grid">
                            <?php
                            $available_tenders = array(
                                'Cash'          => __( 'Cash Tender', 'ozone-skypool' ),
                                'Room Guest'    => __( 'Hotel Room Guest', 'ozone-skypool' ),
                                'Complementary' => __( 'Complementary Pass', 'ozone-skypool' ),
                                'bKash / Nagad' => __( 'Mobile Banking (MFS)', 'ozone-skypool' ),
                                'Card POS'      => __( 'Card Terminal (POS)', 'ozone-skypool' ),
                            );
                            foreach ( $available_tenders as $tender_key => $tender_label ) :
                                $checked = in_array( $tender_key, $enabled_tenders, true ) ? 'checked' : '';
                            ?>
                                <div class="ifs-pms-switch-card ifs-pms-switch-card-tight">
                                    <div class="ifs-pms-switch-title-sm"><?php echo esc_html( $tender_label ); ?></div>
                                    <label class="ifs-pms-switch">
                                        <input type="checkbox" name="ifs_pms_enabled_tenders[]" value="<?php echo esc_attr( $tender_key ); ?>" <?php echo esc_attr( $checked ); ?>>
                                        <span class="ifs-pms-slider"></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="ifs-pms-grid-2-compact">
                        <div class="ifs-pms-field-group">
                            <label class="ifs-pms-field-label" for="ifsPmsQuickCashPresets"><?php esc_html_e( 'Quick Cash Buttons (Comma Separated)', 'ozone-skypool' ); ?></label>
                            <input type="text" name="ifs_pms_quick_cash_presets" id="ifsPmsQuickCashPresets" value="<?php echo $quick_cash_str; ?>" placeholder="<?php esc_attr_e( 'e.g. 500, 1000, 2000', 'ozone-skypool' ); ?>" class="ifs-pms-mono">
                        </div>
                        <div class="ifs-pms-field-group">
                            <label class="ifs-pms-field-label" for="ifsPmsThermalPaperWidth"><?php esc_html_e( 'Thermal Printer Paper Size', 'ozone-skypool' ); ?></label>
                            <select name="ifs_pms_thermal_paper_width" id="ifsPmsThermalPaperWidth">
                                <option value="" <?php selected( $paper_width, '' ); ?>><?php esc_html_e( '-- Select Format --', 'ozone-skypool' ); ?></option>
                                <option value="80mm" <?php selected( $paper_width, '80mm' ); ?>><?php esc_html_e( '80mm Standard POS Slip', 'ozone-skypool' ); ?></option>
                                <option value="58mm" <?php selected( $paper_width, '58mm' ); ?>><?php esc_html_e( '58mm Mobile Bluetooth Slip', 'ozone-skypool' ); ?></option>
                            </select>
                        </div>
                    </div>

                    <hr class="ifs-pms-separator">

                    <div class="ifs-pms-switch-stack">
                        <div class="ifs-pms-switch-card">
                            <div class="ifs-pms-switch-meta">
                                <div class="ifs-pms-switch-title"><?php esc_html_e( 'Strict Pass Verification', 'ozone-skypool' ); ?></div>
                                <div class="ifs-pms-switch-desc"><?php esc_html_e( 'Prevents double-entry by marking tickets used immediately upon scanning.', 'ozone-skypool' ); ?></div>
                            </div>
                            <label class="ifs-pms-switch">
                                <input type="checkbox" name="ifs_pms_enable_strict_scan" value="1" <?php checked( $enable_strict_scan, '1' ); ?>>
                                <span class="ifs-pms-slider"></span>
                            </label>
                        </div>

                        <div class="ifs-pms-switch-card">
                            <div class="ifs-pms-switch-meta">
                                <div class="ifs-pms-switch-title"><?php esc_html_e( 'Auto-Expire Day Passes at Midnight', 'ozone-skypool' ); ?></div>
                                <div class="ifs-pms-switch-desc"><?php esc_html_e( 'Automatically expires single-entry day passes at midnight.', 'ozone-skypool' ); ?></div>
                            </div>
                            <label class="ifs-pms-switch">
                                <input type="checkbox" name="ifs_pms_auto_expire_passes" value="1" <?php checked( $auto_expire_passes, '1' ); ?>>
                                <span class="ifs-pms-slider"></span>
                            </label>
                        </div>

                        <div class="ifs-pms-switch-card">
                            <div class="ifs-pms-switch-meta">
                                <div class="ifs-pms-switch-title"><?php esc_html_e( 'Turnstile & Scanner Sound Alerts', 'ozone-skypool' ); ?></div>
                                <div class="ifs-pms-switch-desc"><?php esc_html_e( 'Plays synthesized success and access-denied chime tones on scan.', 'ozone-skypool' ); ?></div>
                            </div>
                            <label class="ifs-pms-switch">
                                <input type="checkbox" name="ifs_pms_enable_audio_buzzer" value="1" <?php checked( $enable_audio_buzzer, '1' ); ?>>
                                <span class="ifs-pms-slider"></span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="ifs-pms-btn ifs-pms-btn-primary ifs-pms-btn-lg ifs-pms-btn-submit-block">
                        <i class="fa-solid fa-floppy-disk"></i> <?php esc_html_e( 'Save Operational Settings', 'ozone-skypool' ); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- TAB 2: Venue & Thermal Receipt Header -->
        <div id="ifsPmsSettingsPaneGeneral" class="ifs-pms-tab-pane <?php echo ( $active_tab === 'general' ) ? 'active' : ''; ?>">
            <div class="ifs-pms-panel-card">
                <div class="ifs-pms-panel-head">
                    <h3 class="ifs-pms-panel-title">
                        <i class="fa-solid fa-building ifs-pms-panel-icon"></i>
                        <?php esc_html_e( 'Venue Identity, Logo & Receipt Slip Header', 'ozone-skypool' ); ?>
                    </h3>
                </div>

                <div class="ifs-pms-form-stack">
                    <div class="ifs-pms-grid-2">
                        <div class="ifs-pms-field-group">
                            <label class="ifs-pms-field-label" for="ifsPmsBusinessName"><?php esc_html_e( 'Venue / Property Name', 'ozone-skypool' ); ?> *</label>
                            <input type="text" name="ifs_pms_business_name" id="ifsPmsBusinessName" value="<?php echo esc_attr( $b_name ); ?>" required placeholder="<?php esc_attr_e( 'e.g. Ozone Restaurant & Skypool', 'ozone-skypool' ); ?>">
                        </div>
                        <div class="ifs-pms-field-group">
                            <label class="ifs-pms-field-label" for="ifsPmsCurrency"><?php esc_html_e( 'Currency Symbol / Code', 'ozone-skypool' ); ?> *</label>
                            <input type="text" name="ifs_pms_currency" id="ifsPmsCurrency" value="<?php echo esc_attr( $currency ); ?>" required class="ifs-pms-mono" placeholder="<?php esc_attr_e( 'e.g. BDT or $', 'ozone-skypool' ); ?>">
                        </div>
                    </div>

                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label" for="ifsPmsLogoUrl"><?php esc_html_e( 'Venue Logo URL', 'ozone-skypool' ); ?></label>
                        <div class="ifs-pms-logo-upload-group">
                            <input type="url" name="ifs_pms_logo_url" id="ifsPmsLogoUrl" value="<?php echo esc_attr( $logo_url ); ?>" placeholder="https://example.com/logo.png">
                            <button type="button" class="ifs-pms-btn ifs-pms-btn-secondary" onclick="ifsPmsOpenMediaUploader()"><?php esc_html_e( 'Upload', 'ozone-skypool' ); ?></button>
                        </div>
                        <?php if ( ! empty( $logo_url ) ) : ?>
                            <div class="ifs-pms-logo-preview-box">
                                <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'Logo Preview', 'ozone-skypool' ); ?>" class="ifs-pms-logo-img">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="ifs-pms-grid-2">
                        <div class="ifs-pms-field-group">
                            <label class="ifs-pms-field-label" for="ifsPmsPhone"><?php esc_html_e( 'Front Desk Phone Number', 'ozone-skypool' ); ?></label>
                            <input type="text" name="ifs_pms_phone" id="ifsPmsPhone" value="<?php echo esc_attr( $phone ); ?>" placeholder="<?php esc_attr_e( 'e.g. +880 1700-000000', 'ozone-skypool' ); ?>">
                        </div>
                        <div class="ifs-pms-field-group">
                            <label class="ifs-pms-field-label" for="ifsPmsCapacity"><?php esc_html_e( 'Rooftop Guest Capacity Limit', 'ozone-skypool' ); ?></label>
                            <input type="number" name="ifs_pms_max_capacity" id="ifsPmsCapacity" value="<?php echo esc_attr( (string) $capacity ); ?>" placeholder="<?php esc_attr_e( 'Maximum head count', 'ozone-skypool' ); ?>">
                        </div>
                    </div>

                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label" for="ifsPmsAddress"><?php esc_html_e( 'Physical Address (Printed on Slip)', 'ozone-skypool' ); ?></label>
                        <input type="text" name="ifs_pms_address" id="ifsPmsAddress" value="<?php echo esc_attr( $address ); ?>" placeholder="<?php esc_attr_e( 'Full address line', 'ozone-skypool' ); ?>">
                    </div>

                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label" for="ifsPmsReceiptNote"><?php esc_html_e( 'Thermal Receipt Footer Terms & Safety Policy', 'ozone-skypool' ); ?></label>
                        <textarea name="ifs_pms_receipt_note" id="ifsPmsReceiptNote" rows="3" placeholder="<?php esc_attr_e( 'Add thermal slip policy statements...', 'ozone-skypool' ); ?>"><?php echo $receipt_note; ?></textarea>
                    </div>

                    <button type="submit" class="ifs-pms-btn ifs-pms-btn-primary ifs-pms-btn-lg ifs-pms-btn-submit-block">
                        <i class="fa-solid fa-floppy-disk"></i> <?php esc_html_e( 'Save Venue Configuration', 'ozone-skypool' ); ?>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>