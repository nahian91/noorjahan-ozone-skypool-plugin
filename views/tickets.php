<?php
/**
 * View Router & High-Performance Front Desk POS / Master Ticket Registry / Dedicated Details Page
 *
 * @package Ozone_Skypool_OS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Enqueue separated POS & Tickets stylesheet
if ( defined( 'IFS_PMS_URL' ) && defined( 'IFS_PMS_VERSION' ) ) {
    wp_enqueue_style(
        'oz-pos-tickets-css',
        IFS_PMS_URL . 'assets/css/pos-tickets.css',
        array(),
        IFS_PMS_VERSION
    );
}

global $wpdb;

$t_tick        = $wpdb->prefix . 'ifs_pms_tickets';
$t_cust        = $wpdb->prefix . 'ifs_pms_customers';
$currency      = esc_html( (string) get_option( 'ifs_pms_currency', 'BDT' ) );
$default_price = (float) get_option( 'ifs_pms_ticket_price', 500.00 );
$b_name        = esc_html( (string) get_option( 'ifs_pms_business_name', 'Ozone Restaurant & Skypool' ) );
$address       = esc_html( (string) get_option( 'ifs_pms_address', '20th Floor, Ritz Tower, Dargah Gate, Sylhet' ) );
$phone         = esc_html( (string) get_option( 'ifs_pms_phone', '+880 1700-000000' ) );
$logo_url      = esc_url( (string) get_option( 'ifs_pms_logo_url', '' ) );
$base_url      = admin_url( 'admin.php?page=ifs-pms' );
$current_staff = wp_get_current_user()->display_name;
$is_admin      = current_user_can( 'manage_options' ) || current_user_can( 'ozone_manage_settings' );

// Determine current sub-view route
$current_view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'tickets';

// ==========================================
// ROUTE: DEDICATED TICKET DETAILS VIEW PAGE
// ==========================================
if ( $current_view === 'ticket_detail' ) :
    $ticket_id = isset( $_GET['ticket_id'] ) ? intval( $_GET['ticket_id'] ) : 0;
    
    $ticket = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT t.*, c.name AS customer_name, c.phone AS customer_phone 
             FROM {$t_tick} t 
             LEFT JOIN {$t_cust} c ON t.customer_id = c.id 
             WHERE t.id = %d",
            $ticket_id
        )
    );

    if ( ! $ticket ) :
        ?>
        <div class="wrap">
            <div style="background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 20px; padding: 50px; text-align: center; max-width: 600px; margin: 40px auto; box-shadow: 0 20px 40px rgba(0,0,0,0.05);">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 42px; color: #ef4444; margin-bottom: 16px; display: block;"></i>
                <h3 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 10px;"><?php esc_html_e( 'Ticket Record Not Found', 'swimming-pool-manager' ); ?></h3>
                <p style="color: #64748b; font-size: 14.5px; margin: 0 0 24px;"><?php esc_html_e( 'The requested ticket ID does not exist or has been removed from the registry.', 'swimming-pool-manager' ); ?></p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=tickets&tab=list' ) ); ?>" class="oz-btn oz-btn-primary" style="height: 48px; padding: 0 24px; border-radius: 12px; background: #0284c7; color: #ffffff; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-arrow-left"></i> <?php esc_html_e( 'Back to Master Ledger', 'swimming-pool-manager' ); ?>
                </a>
            </div>
        </div>
        <?php
        return;
    endif;

    $is_valid = ( $ticket->status === 'Valid' );
    $status_badge_class = $is_valid 
        ? 'background: rgba(16, 185, 129, 0.12); color: #059669; border: 1px solid rgba(16, 185, 129, 0.3);' 
        : ( $ticket->status === 'Used' 
            ? 'background: rgba(245, 158, 11, 0.12); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.3);' 
            : 'background: rgba(244, 63, 94, 0.12); color: #f43f5e; border: 1px solid rgba(244, 63, 94, 0.3);' );
    ?>

    <div class="oz-detail-wrap">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=tickets&tab=list' ) ); ?>" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 700; font-size: 13.5px; color: #0284c7; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i> <?php esc_html_e( 'Back to All Tickets Ledger', 'swimming-pool-manager' ); ?>
            </a>
            <div style="display: flex; gap: 12px;">
                <button type="button" onclick="window.print();" class="oz-btn" style="height: 42px; padding: 0 20px; border-radius: 12px; background: #ffffff; border: 1.5px solid #cbd5e1; font-weight: 700; color: #475569; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-print"></i> <?php esc_html_e( 'Print Ticket Slip', 'swimming-pool-manager' ); ?>
                </button>
            </div>
        </div>

        <div class="oz-detail-card">
            <div class="oz-detail-head">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 52px; height: 52px; border-radius: 16px; background: rgba(2, 132, 199, 0.1); color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                        <i class="fa-solid fa-ticket"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 19px; font-weight: 800; color: #0f172a;"><?php esc_html_e( 'Gate Pass Dossier', 'swimming-pool-manager' ); ?></h3>
                        <span class="ifs-pms-mono" style="font-size: 14px; color: #0284c7; font-weight: 800; letter-spacing: 0.5px;"><?php echo esc_html( $ticket->ticket_code ); ?></span>
                    </div>
                </div>
                <span style="padding: 8px 18px; border-radius: 30px; font-weight: 800; font-size: 13px; <?php echo esc_attr( $status_badge_class ); ?>">
                    <?php echo esc_html( $ticket->status ); ?>
                </span>
            </div>

            <div class="oz-detail-body">
                <div class="oz-detail-grid">
                    <div class="oz-info-group">
                        <span class="oz-info-label"><i class="fa-solid fa-user" style="color: #0284c7;"></i> <?php esc_html_e( 'Patron Name', 'swimming-pool-manager' ); ?></span>
                        <span class="oz-info-value"><?php echo esc_html( ! empty( $ticket->customer_name ) ? $ticket->customer_name : __( 'Walk-in Guest', 'swimming-pool-manager' ) ); ?></span>
                    </div>
                    <div class="oz-info-group">
                        <span class="oz-info-label"><i class="fa-solid fa-phone" style="color: #0284c7;"></i> <?php esc_html_e( 'Contact Number', 'swimming-pool-manager' ); ?></span>
                        <span class="oz-info-value ifs-pms-mono"><?php echo esc_html( $ticket->customer_phone ?: '-' ); ?></span>
                    </div>
                </div>

                <div class="oz-detail-grid">
                    <div class="oz-info-group">
                        <span class="oz-info-label"><i class="fa-solid fa-layer-group" style="color: #0284c7;"></i> <?php esc_html_e( 'Admission Package Details', 'swimming-pool-manager' ); ?></span>
                        <span class="oz-info-value" style="font-size: 14.5px;"><?php echo esc_html( $ticket->package_details ?: 'Standard Pass' ); ?></span>
                    </div>
                    <div class="oz-info-group">
                        <span class="oz-info-label"><i class="fa-solid fa-clock" style="color: #0284c7;"></i> <?php esc_html_e( 'Duration Booked', 'swimming-pool-manager' ); ?></span>
                        <span class="oz-info-value ifs-pms-mono"><?php echo esc_html( $ticket->duration_hours ); ?> <?php echo esc_html( _n( 'Hour', 'Hours', (int) $ticket->duration_hours, 'swimming-pool-manager' ) ); ?></span>
                    </div>
                </div>

                <div class="oz-detail-grid">
                    <div class="oz-info-group">
                        <span class="oz-info-label"><i class="fa-solid fa-money-bill-wave" style="color: #10b981;"></i> <?php esc_html_e( 'Total Amount Paid', 'swimming-pool-manager' ); ?></span>
                        <span class="oz-info-value ifs-pms-mono" style="color: #0284c7; font-size: 18px;"><?php echo esc_html( $currency . ' ' . number_format( (float) $ticket->amount, 2 ) ); ?></span>
                    </div>
                    <div class="oz-info-group">
                        <span class="oz-info-label"><i class="fa-solid fa-credit-card" style="color: #0284c7;"></i> <?php esc_html_e( 'Payment Tender Method', 'swimming-pool-manager' ); ?></span>
                        <span class="oz-info-value"><?php echo esc_html( $ticket->payment_method ?: 'Cash' ); ?></span>
                    </div>
                </div>

                <?php if ( ! empty( $ticket->room_no ) ) : ?>
                    <div class="oz-info-group">
                        <span class="oz-info-label"><i class="fa-solid fa-hotel" style="color: #0284c7;"></i> <?php esc_html_e( 'Hotel Room Number', 'swimming-pool-manager' ); ?></span>
                        <span class="oz-info-value ifs-pms-mono"><?php echo esc_html( $ticket->room_no ); ?></span>
                    </div>
                <?php endif; ?>

                <div class="oz-detail-grid" style="border-top: 1.5px dashed #e2e8f0; padding-top: 24px;">
                    <div class="oz-info-group" style="background: transparent; border: none; padding: 0;">
                        <span class="oz-info-label"><?php esc_html_e( 'Issuing Staff / Cashier', 'swimming-pool-manager' ); ?></span>
                        <span class="oz-info-value" style="font-weight: 600; color: #475569;"><?php echo esc_html( $ticket->sold_by ); ?></span>
                    </div>
                    <div class="oz-info-group" style="background: transparent; border: none; padding: 0;">
                        <span class="oz-info-label"><?php esc_html_e( 'Issue Timestamp', 'swimming-pool-manager' ); ?></span>
                        <span class="oz-info-value ifs-pms-mono" style="font-weight: 600; color: #475569;"><?php echo esc_html( $ticket->sold_at ); ?></span>
                    </div>
                </div>

                <?php if ( ! empty( $ticket->scanned_at ) && $ticket->scanned_at !== '0000-00-00 00:00:00' ) : ?>
                    <div class="oz-detail-grid" style="border-top: 1.5px dashed #e2e8f0; padding-top: 24px;">
                        <div class="oz-info-group" style="background: transparent; border: none; padding: 0;">
                            <span class="oz-info-label"><?php esc_html_e( 'Turnstile Gate Admission', 'swimming-pool-manager' ); ?></span>
                            <span class="oz-info-value ifs-pms-mono" style="font-weight: 600; color: #059669;"><?php echo esc_html( $ticket->scanned_at ); ?></span>
                        </div>
                        <div class="oz-info-group" style="background: transparent; border: none; padding: 0;">
                            <span class="oz-info-label"><?php esc_html_e( 'Verified / Scanned By', 'swimming-pool-manager' ); ?></span>
                            <span class="oz-info-value" style="font-weight: 600; color: #475569;"><?php echo esc_html( $ticket->scanned_by ?: '-' ); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return;
endif;
// ==========================================
// END OF ROUTE: TICKET DETAILS VIEW
// ==========================================


// Active Tab Router for Main POS / Ledger View
$active_tab = isset( $_GET['tab'] ) && $_GET['tab'] === 'list' ? 'list' : 'add';

// Retrieve Dynamic Settings & States
$pricing_tiers = get_option( 'ifs_pms_pricing_tiers', array(
    array( 'name' => 'Standard Sky Swim Pass', 'age_group' => 'Adult (13+ yrs)', 'price' => 500.00 ),
    array( 'name' => 'Junior Splash Pass', 'age_group' => 'Child (Under 13 yrs)', 'price' => 300.00 ),
) );

$amenity_addons = get_option( 'ifs_pms_amenity_addons', array(
    array( 'name' => 'Fresh Towel Rental', 'price' => 50.00 ),
    array( 'name' => 'Swim Goggles', 'price' => 100.00 ),
    array( 'name' => 'Swimwear Trunk', 'price' => 150.00 ),
) );

$enable_amenities = get_option( 'ifs_pms_enable_amenities', '1' );
$pool_status      = get_option( 'ifs_pms_pool_status', 'open' );

// Day-Wise Operating Hours Check
$current_day_key  = strtolower( current_time( 'l' ) );
$weekly_schedule  = get_option( 'ifs_pms_weekly_schedule', array() );
$today_schedule   = $weekly_schedule[ $current_day_key ] ?? array( 'status' => 'open', 'open' => '10:00', 'close' => '23:00' );
$is_today_open    = ( $today_schedule['status'] === 'open' );
$current_time_val = current_time( 'H:i' );
$is_within_hours  = ( $current_time_val >= $today_schedule['open'] && $current_time_val <= $today_schedule['close'] );

// --- PAGINATION SETUP ---
$per_page      = 10;
$paged         = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$offset        = ( $paged - 1 ) * $per_page;
$total_tickets = (int) $wpdb->get_var( "SELECT COUNT(t.id) FROM {$t_tick} t" );
$total_pages   = ceil( $total_tickets / $per_page );

// Query Paginated Tickets
$all_tickets = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT t.*, c.name AS customer_name, c.phone AS customer_phone 
         FROM {$t_tick} t 
         LEFT JOIN {$t_cust} c ON t.customer_id = c.id 
         ORDER BY t.id DESC 
         LIMIT %d OFFSET %d",
        $per_page,
        $offset
    )
);

// Dynamic Token Code Sequence Generator (OZONE-Mon-Day-Serial)
$today_start = current_time( 'Y-m-d 00:00:00' );
$today_end   = current_time( 'Y-m-d 23:59:59' );
$today_count = (int) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(id) FROM {$t_tick} WHERE sold_at >= %s AND sold_at <= %s",
        $today_start,
        $today_end
    )
);
$preview_code = 'OZONE-' . strtoupper( current_time( 'M' ) ) . '-' . current_time( 'd' ) . '-' . str_pad( $today_count + 1, 4, '0', STR_PAD_LEFT );
?>

<div class="oz-pos-wrapper">
    <!-- Sub Navigation Tab Bar -->
    <div class="oz-subnav-bar" role="tablist">
        <button type="button" class="oz-subnav-btn <?php echo ( $active_tab === 'add' ) ? 'active' : ''; ?>" id="ozTabBtnAdd" onclick="ozSwitchTicketTab('add', this)">
            <i class="fa-solid fa-plus-circle"></i> <?php esc_html_e( 'Add Ticket', 'swimming-pool-manager' ); ?>
        </button>
        <button type="button" class="oz-subnav-btn <?php echo ( $active_tab === 'list' ) ? 'active' : ''; ?>" id="ozTabBtnList" onclick="ozSwitchTicketTab('list', this)">
            <i class="fa-solid fa-table-list"></i> <?php esc_html_e( 'All Tickets', 'swimming-pool-manager' ); ?>
        </button>
    </div>

    <!-- TAB 1: Add Ticket POS -->
    <div id="ozTicketPaneAdd" class="oz-tab-pane <?php echo ( $active_tab === 'add' ) ? 'active' : ''; ?>">
        <?php if ( $pool_status !== 'open' || ! $is_today_open || ! $is_within_hours ) : ?>
            <div style="background: rgba(244, 63, 94, 0.08); border: 1.5px solid rgba(244, 63, 94, 0.3); color: #f43f5e; padding: 16px 22px; border-radius: 16px; margin-bottom: 24px; font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 14px;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 20px;"></i>
                <div>
                    <?php if ( $pool_status === 'closed' ) : ?>
                        <?php esc_html_e( 'Notice: The pool is currently marked as CLOSED by management.', 'swimming-pool-manager' ); ?>
                    <?php elseif ( $pool_status === 'maintenance' ) : ?>
                        <?php esc_html_e( 'Notice: Pool is undergoing routine MAINTENANCE.', 'swimming-pool-manager' ); ?>
                    <?php else : ?>
                        <?php printf( esc_html__( 'Notice: Outside normal operating hours (%s: %s to %s).', 'swimming-pool-manager' ), ucfirst( $current_day_key ), $today_schedule['open'], $today_schedule['close'] ); ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="oz-pos-layout">
            <!-- Left Console Form -->
            <div class="oz-pos-card">
                <div class="oz-pos-head">
                    <h3 class="oz-pos-title">
                        <i class="fa-solid fa-cash-register" style="color: #0284c7;"></i>
                        <?php esc_html_e( 'Skypool Front Gate POS', 'swimming-pool-manager' ); ?>
                    </h3>
                    <span style="font-size: 12px; color: #64748b;">
                        <?php esc_html_e( 'Print Shortcut:', 'swimming-pool-manager' ); ?> 
                        <kbd style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 3px 6px; border-radius: 6px; font-weight: 800; color: #0284c7;">Ctrl + Enter</kbd>
                    </span>
                </div>

                <form method="POST" action="<?php echo esc_url( $base_url . '&view=tickets' ); ?>" id="ozPosMasterForm" onsubmit="return ozValidateFormSubmission(event);">
                    <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                    <input type="hidden" name="ifs_pms_action" value="issue_ticket">
                    <input type="hidden" name="package_name" id="ozPackageNameInput" value="">
                    <!-- DURATION FIELD LINKED DIRECTLY TO TIER HOURS -->
                    <input type="hidden" name="duration_hours" id="ozDurationHoursInput" value="1">
                    <input type="hidden" name="amount" id="ozSubmittedAmount" value="500.00">
                    <input type="hidden" name="payment_method" id="ozSelectedPayment" value="Cash">

                    <div class="oz-pos-form-wrap">
                        <!-- Patron Classification -->
                        <div class="oz-field-group">
                            <label class="oz-field-label">
                                <i class="fa-solid fa-user-tag" style="color: #0284c7;"></i> <?php esc_html_e( 'Patron Classification', 'swimming-pool-manager' ); ?> *
                            </label>
                            <div class="oz-guest-type-selector">
                                <label class="oz-type-pill active" id="ozTypePillWalkin" onclick="ozSetGuestType('walkin')">
                                    <input type="radio" name="guest_type" value="customer" checked style="display: none;">
                                    <i class="fa-solid fa-users"></i>
                                    <div>
                                        <strong><?php esc_html_e( 'General Customer', 'swimming-pool-manager' ); ?></strong>
                                        <small><?php esc_html_e( 'Standard Ticket Rates Apply', 'swimming-pool-manager' ); ?></small>
                                    </div>
                                </label>

                                <label class="oz-type-pill" id="ozTypePillRoom" onclick="ozSetGuestType('room')">
                                    <input type="radio" name="guest_type" value="room_guest" style="display: none;">
                                    <i class="fa-solid fa-hotel"></i>
                                    <div>
                                        <strong><?php esc_html_e( 'Hotel Room Guest', 'swimming-pool-manager' ); ?></strong>
                                        <small><?php esc_html_e( 'Complimentary Pass', 'swimming-pool-manager' ); ?></small>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Patron Details -->
                        <div class="oz-grid-2">
                            <div class="oz-field-group">
                                <label class="oz-field-label" for="ozGuestName"><?php esc_html_e( 'Patron Name', 'swimming-pool-manager' ); ?> *</label>
                                <input type="text" name="name" id="ozGuestName" required placeholder="<?php esc_attr_e( 'e.g. Tanvir Ahmed', 'swimming-pool-manager' ); ?>" autocomplete="off">
                            </div>
                            <div class="oz-field-group">
                                <label class="oz-field-label" for="ozGuestPhone"><?php esc_html_e( 'Mobile Number (e-Pass SMS)', 'swimming-pool-manager' ); ?> *</label>
                                <input type="tel" name="phone" id="ozGuestPhone" required placeholder="017XXXXXXXX" pattern="[0-9+\s\-]{7,20}" autocomplete="off">
                            </div>
                        </div>

                        <!-- Hotel Room Number Input -->
                        <div class="oz-field-group" style="display: none;" id="ozRoomNumberWrap">
                            <label class="oz-field-label" for="ozHotelRoomNo">
                                <i class="fa-solid fa-door-open" style="color: #0284c7;"></i> <?php esc_html_e( 'Hotel Guest Room Number', 'swimming-pool-manager' ); ?> *
                            </label>
                            <input type="text" name="room_no" id="ozHotelRoomNo" class="ifs-pms-mono" placeholder="e.g. Room 402">
                        </div>

                        <!-- Modular Multi-Package Switch Deck -->
                        <div class="oz-field-group">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <label class="oz-field-label" style="margin: 0;">
                                    <i class="fa-solid fa-layer-group" style="color: #0284c7;"></i> <?php esc_html_e( 'Admission Packages & Tier Controls', 'swimming-pool-manager' ); ?> *
                                </label>
                                <span style="font-size: 11.5px; color: #64748b; font-weight: 600;">
                                    <?php esc_html_e( 'Toggle switch to enable each package tier', 'swimming-pool-manager' ); ?>
                                </span>
                            </div>

                            <div class="oz-modular-deck">
                                <?php if ( ! empty( $pricing_tiers ) ) : ?>
                                    <?php foreach ( $pricing_tiers as $index => $tier ) : 
                                        $is_default_on = ( $index === 0 );
                                    ?>
                                        <div class="oz-tier-box <?php echo $is_default_on ? 'is-enabled' : ''; ?>" id="ozTierBox_<?php echo $index; ?>"
                                             data-index="<?php echo $index; ?>"
                                             data-name="<?php echo esc_attr( $tier['name'] ); ?>" 
                                             data-age="<?php echo esc_attr( $tier['age_group'] ?? 'General' ); ?>"
                                             data-price="<?php echo esc_attr( $tier['price'] ); ?>">

                                            <div class="oz-tier-box-head">
                                                <div>
                                                    <div class="oz-tier-title"><?php echo esc_html( $tier['name'] ); ?></div>
                                                    <span class="oz-tier-badge">👤 <?php echo esc_html( $tier['age_group'] ?? 'General' ); ?></span>
                                                </div>
                                                <label class="oz-switch" title="<?php esc_attr_e( 'Enable / Disable Tier', 'swimming-pool-manager' ); ?>">
                                                    <input type="checkbox" class="oz-tier-toggle-input" id="ozTierToggle_<?php echo $index; ?>" <?php checked( $is_default_on ); ?> onchange="ozToggleTierSwitch(<?php echo $index; ?>)">
                                                    <span class="oz-slider"></span>
                                                </label>
                                            </div>

                                            <div class="oz-tier-controls-row">
                                                <div class="oz-metric-pill">
                                                    <span class="oz-metric-label"><?php esc_html_e( 'Base Rate', 'swimming-pool-manager' ); ?></span>
                                                    <strong class="ifs-pms-mono" style="color: #0284c7; font-size: 13.5px;">
                                                        <?php echo esc_html( $currency . ' ' . number_format( (float) $tier['price'], 2 ) ); ?>
                                                        <small style="font-weight: 500; color: #64748b;">/hr</small>
                                                    </strong>
                                                </div>

                                                <div class="oz-metric-pill">
                                                    <span class="oz-metric-label"><?php esc_html_e( 'Headcount', 'swimming-pool-manager' ); ?></span>
                                                    <div class="oz-mini-qty">
                                                        <button type="button" class="oz-mini-btn" onclick="ozDeltaModularQty(<?php echo $index; ?>, -1)"><i class="fa-solid fa-minus"></i></button>
                                                        <input type="number" id="ozTierPersons_<?php echo $index; ?>" class="oz-mini-input ifs-pms-mono" value="<?php echo $is_default_on ? 1 : 0; ?>" min="0" max="50" readonly>
                                                        <button type="button" class="oz-mini-btn" onclick="ozDeltaModularQty(<?php echo $index; ?>, 1)"><i class="fa-solid fa-plus"></i></button>
                                                    </div>
                                                </div>

                                                <div class="oz-metric-pill">
                                                    <span class="oz-metric-label"><?php esc_html_e( 'Duration (Hrs)', 'swimming-pool-manager' ); ?></span>
                                                    <div class="oz-mini-qty">
                                                        <button type="button" class="oz-mini-btn" onclick="ozDeltaModularHours(<?php echo $index; ?>, -1)"><i class="fa-solid fa-minus"></i></button>
                                                        <input type="number" id="ozTierHours_<?php echo $index; ?>" class="oz-mini-input ifs-pms-mono" value="1" min="1" max="12" readonly>
                                                        <button type="button" class="oz-mini-btn" onclick="ozDeltaModularHours(<?php echo $index; ?>, 1)"><i class="fa-solid fa-plus"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Addons -->
                        <?php if ( $enable_amenities === '1' && ! empty( $amenity_addons ) ) : ?>
                            <div class="oz-field-group">
                                <label class="oz-field-label"><?php esc_html_e( 'Amenity Add-Ons & Rentals', 'swimming-pool-manager' ); ?></label>
                                <div class="oz-addon-grid">
                                    <?php foreach ( $amenity_addons as $addon ) : ?>
                                        <div class="oz-addon-item" onclick="ozToggleAddon(this, <?php echo esc_attr( $addon['price'] ); ?>, '<?php echo esc_attr( $addon['name'] ); ?>')">
                                            <div>
                                                <div style="font-size: 13px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $addon['name'] ); ?></div>
                                                <div style="font-size: 11.5px; color: #0284c7; font-weight: 700; margin-top: 2px;">+<?php echo esc_html( number_format( (float) $addon['price'], 2 ) . ' ' . $currency ); ?></div>
                                            </div>
                                            <i class="fa-regular fa-square" style="font-size: 18px; color: #94a3b8;"></i>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Tender Methods & Calculations -->
                        <div class="oz-field-group" style="border-top: 1.5px solid #f1f5f9; padding-top: 20px;">
                            <label class="oz-field-label"><?php esc_html_e( 'Payment Tender Method', 'swimming-pool-manager' ); ?></label>
                            <div class="oz-tender-grid">
                                <div class="oz-tender-box active" onclick="ozSelectTender('Cash', this)">
                                    <i class="fa-solid fa-money-bill-wave" style="font-size: 17px;"></i> <?php esc_html_e( 'Cash', 'swimming-pool-manager' ); ?>
                                </div>
                                <div class="oz-tender-box" onclick="ozSelectTender('Complementary', this)">
                                    <i class="fa-solid fa-handshake" style="font-size: 17px;"></i> <?php esc_html_e( 'Complementary', 'swimming-pool-manager' ); ?>
                                </div>
                                <div class="oz-tender-box" onclick="ozSelectTender('bKash / Nagad', this)">
                                    <i class="fa-solid fa-mobile-screen-button" style="font-size: 17px;"></i> <?php esc_html_e( 'bKash / MFS', 'swimming-pool-manager' ); ?>
                                </div>
                                <div class="oz-tender-box" onclick="ozSelectTender('Card POS', this)">
                                    <i class="fa-solid fa-credit-card" style="font-size: 17px;"></i> <?php esc_html_e( 'POS Card', 'swimming-pool-manager' ); ?>
                                </div>
                            </div>

                            <!-- Cash Received Breakdown -->
                            <div class="oz-grid-2" style="margin-top: 18px;" id="ozCashWrap">
                                <div>
                                    <label class="oz-field-label" for="ozCashReceived">
                                        <?php printf( esc_html__( 'Cash Tendered (%s)', 'swimming-pool-manager' ), esc_html( $currency ) ); ?> 
                                        <span id="ozCashRequiredStar" style="color: #ef4444; font-weight: 800;">*</span>
                                    </label>
                                    <input type="number" step="0.01" id="ozCashReceived" class="ifs-pms-mono" placeholder="0.00" required>
                                    <div class="oz-quick-cash-row">
                                        <button type="button" class="oz-btn oz-btn-sm" onclick="ozQuickCash(500)">+500</button>
                                        <button type="button" class="oz-btn oz-btn-sm" onclick="ozQuickCash(1000)">+1000</button>
                                        <button type="button" class="oz-btn oz-btn-sm" onclick="ozQuickCash('exact')">Exact</button>
                                    </div>
                                </div>
                                <div>
                                    <label class="oz-field-label"><?php esc_html_e( 'Change Due Back', 'swimming-pool-manager' ); ?></label>
                                    <div id="ozChangeDue" class="ifs-pms-mono" style="background: #f8fafc; height: 48px; display: flex; align-items: center; padding: 0 16px; border-radius: 12px; font-weight: 800; color: #10b981; border: 1.5px solid #cbd5e1; font-size: 16px;">
                                        0.00 <?php echo $currency; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div style="display: flex; gap: 14px; margin-top: 10px;">
                            <button type="submit" class="oz-btn oz-btn-primary oz-btn-lg" style="flex: 2;">
                                <i class="fa-solid fa-print"></i> <?php esc_html_e( 'Confirm & Print Thermal Slip', 'swimming-pool-manager' ); ?>
                            </button>
                            <button type="button" class="oz-btn oz-btn-secondary oz-btn-lg" style="flex: 1;" onclick="ozResetTerminal()">
                                <?php esc_html_e( 'Reset (Alt+C)', 'swimming-pool-manager' ); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Right Terminal Live Slip Preview -->
            <div>
                <div class="oz-receipt-preview-card" id="ozReceiptPreviewContainer">
                    <div style="text-align: center;">
                        <?php if ( ! empty( $logo_url ) ) : ?>
                            <div style="display: flex; justify-content: center; margin-bottom: 8px;">
                                <img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo" style="max-height: 40px; width: auto; border-radius: 6px;">
                            </div>
                        <?php endif; ?>
                        <strong style="font-size: 15px; letter-spacing: 0.5px; text-transform: uppercase; color: #0f172a;"><?php echo $b_name; ?></strong>
                        <div style="font-size: 11px; color: #64748b; margin-top: 3px; line-height: 1.4;">
                            <?php echo $address; ?><br>
                            <?php echo esc_html__( 'Tel:', 'swimming-pool-manager' ) . ' ' . $phone; ?>
                        </div>
                    </div>

                    <div class="oz-receipt-sep"></div>

                    <div style="text-align: center; margin: 8px 0;">
                        <div id="ozReceiptQrWrap" style="display: flex; justify-content: center; margin-bottom: 8px;"></div>
                        <div style="font-size: 9.5px; font-weight: 800; letter-spacing: 1.2px; color: #64748b; text-transform: uppercase;">
                            <?php esc_html_e( 'Turnstile Access Token', 'swimming-pool-manager' ); ?>
                        </div>
                        <div id="ozPrevToken" class="ifs-pms-mono" style="font-weight: 800; font-size: 15px; letter-spacing: 1px; color: #0284c7; margin-top: 2px;">
                            <?php echo esc_html( $preview_code ); ?>
                        </div>
                    </div>

                    <div class="oz-receipt-sep"></div>

                    <!-- Section 1: Patron Identification -->
                    <div style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 6px;">
                        <?php esc_html_e( 'Patron Identification', 'swimming-pool-manager' ); ?>
                    </div>
                    <table class="oz-receipt-table">
                        <tr>
                            <td style="color: #64748b;"><?php esc_html_e( 'Patron Type', 'swimming-pool-manager' ); ?>:</td>
                            <td style="text-align: right; font-weight: 700; color: #0284c7;" id="ozPrevClassification"><?php esc_html_e( 'General Customer', 'swimming-pool-manager' ); ?></td>
                        </tr>
                        <tr>
                            <td style="color: #64748b;"><?php esc_html_e( 'Guest Name', 'swimming-pool-manager' ); ?>:</td>
                            <td style="text-align: right; font-weight: 700; color: #0f172a;" id="ozPrevName">Walk-in Guest</td>
                        </tr>
                        <tr>
                            <td style="color: #64748b;"><?php esc_html_e( 'Contact #', 'swimming-pool-manager' ); ?>:</td>
                            <td style="text-align: right; font-weight: 700; color: #0f172a;" id="ozPrevPhone">017XXXXXXXX</td>
                        </tr>
                        <tr id="ozPrevRoomRow" style="display: none;">
                            <td style="color: #64748b;"><?php esc_html_e( 'Hotel Room #', 'swimming-pool-manager' ); ?>:</td>
                            <td style="text-align: right; font-weight: 800; color: #0284c7;" id="ozPrevRoom">-</td>
                        </tr>
                    </table>

                    <div class="oz-receipt-sep"></div>

                    <!-- Section 2: Admission Breakdown -->
                    <div style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 6px;">
                        <?php esc_html_e( 'Admission Breakdown', 'swimming-pool-manager' ); ?>
                    </div>
                    <table class="oz-receipt-table">
                        <thead>
                            <tr style="border-bottom: 1.5px dashed #cbd5e1;">
                                <th style="text-align: left; color: #64748b; font-size: 10px; padding-bottom: 4px;"><?php esc_html_e( 'Package Item', 'swimming-pool-manager' ); ?></th>
                                <th style="text-align: right; color: #64748b; font-size: 10px; padding-bottom: 4px;"><?php esc_html_e( 'Qty', 'swimming-pool-manager' ); ?></th>
                                <th style="text-align: right; color: #64748b; font-size: 10px; padding-bottom: 4px;"><?php esc_html_e( 'Subtotal', 'swimming-pool-manager' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="ozPrevTiersBody"></tbody>
                        <tbody id="ozPrevAddonsBody"></tbody>
                    </table>

                    <div class="oz-receipt-sep"></div>

                    <!-- Section 3: Settlement Summary -->
                    <table class="oz-receipt-table" style="margin-bottom: 4px;">
                        <tr>
                            <td style="color: #64748b;"><?php esc_html_e( 'Payment Method', 'swimming-pool-manager' ); ?>:</td>
                            <td style="text-align: right; font-weight: 700; color: #0f172a;" id="ozPrevTender">Cash</td>
                        </tr>
                        <tr>
                            <td style="color: #64748b;"><?php esc_html_e( 'Issue Timestamp', 'swimming-pool-manager' ); ?>:</td>
                            <td style="text-align: right; color: #64748b;"><?php echo esc_html( current_time( 'M j, Y - H:i' ) ); ?></td>
                        </tr>
                        <tr>
                            <td style="color: #64748b;"><?php esc_html_e( 'Desk Cashier', 'swimming-pool-manager' ); ?>:</td>
                            <td style="text-align: right; color: #64748b;"><?php echo esc_html( $current_staff ); ?></td>
                        </tr>
                    </table>

                    <div class="oz-receipt-sep"></div>

                    <!-- Final Settlement -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 4px 0;">
                        <span style="font-weight: 800; font-size: 13px; color: #0f172a;"><?php esc_html_e( 'NET PAYABLE:', 'swimming-pool-manager' ); ?></span>
                        <span style="font-size: 20px; font-weight: 800; color: #0284c7;" id="ozPrevTotal">
                            <?php echo $currency . ' 500.00'; ?>
                        </span>
                    </div>

                    <!-- Structured Thermal Safety Disclaimers -->
                    <div class="oz-receipt-rules">
                        <div class="oz-rules-title"><?php esc_html_e( 'TURNSTILE PASS & POOL SAFETY RULES', 'swimming-pool-manager' ); ?></div>
                        <ul class="oz-rules-list">
                            <li><?php esc_html_e( 'Valid for single turnstile gate entry on date of issue only.', 'swimming-pool-manager' ); ?></li>
                            <li><?php esc_html_e( 'Proper synthetic swimwear compulsory; cotton wear strictly restricted.', 'swimming-pool-manager' ); ?></li>
                            <li><?php esc_html_e( 'Mandatory shower required before entering the pool water.', 'swimming-pool-manager' ); ?></li>
                            <li><?php esc_html_e( 'Outside food, glassware, and smoking are not permitted on the pool deck.', 'swimming-pool-manager' ); ?></li>
                            <li><?php esc_html_e( 'Children under 13 must be supervised by an adult at all times.', 'swimming-pool-manager' ); ?></li>
                            <li><?php esc_html_e( 'Management is not liable for personal belongings. Non-refundable.', 'swimming-pool-manager' ); ?></li>
                        </ul>
                    </div>

                    <!-- Thermal Slip Print Trigger -->
                    <div class="oz-receipt-print-action" style="margin-top: 18px; padding-top: 12px; border-top: 1.5px dashed #cbd5e1;">
                        <button type="button" class="oz-btn oz-btn-primary" style="width: 100%; height: 44px; border-radius: 10px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 14px;" onclick="ozPrintPreviewReceipt()">
                            <i class="fa-solid fa-print"></i> <?php esc_html_e( 'Print Thermal Slip Only', 'swimming-pool-manager' ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: All Tickets Master Registry -->
    <div id="ozTicketPaneList" class="oz-tab-pane <?php echo ( $active_tab === 'list' ) ? 'active' : ''; ?>">
        <div class="oz-pos-card">
            <div class="oz-pos-head">
                <h3 class="oz-pos-title">
                    <i class="fa-solid fa-list-check" style="color: #0284c7;"></i>
                    <?php esc_html_e( 'Gate Pass Master Ledger', 'swimming-pool-manager' ); ?>
                </h3>
                <span class="ifs-pms-badge" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-weight: 700; padding: 5px 12px; border-radius: 8px;">
                    <?php echo $total_tickets; ?> <?php esc_html_e( 'Total Records', 'swimming-pool-manager' ); ?>
                </span>
            </div>

            <div class="oz-search-bar">
                <div class="oz-search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="ozTicketSearchInput" placeholder="<?php esc_attr_e( 'Search visible page tickets by code, name, or phone...', 'swimming-pool-manager' ); ?>" oninput="ozFilterTicketTable()" autocomplete="off">
                </div>

                <select id="ozTicketStatusFilter" onchange="ozFilterTicketTable()">
                    <option value="ALL"><?php esc_html_e( 'All Statuses', 'swimming-pool-manager' ); ?></option>
                    <option value="Valid"><?php esc_html_e( 'Valid (Unused)', 'swimming-pool-manager' ); ?></option>
                    <option value="Used"><?php esc_html_e( 'Used (Admitted)', 'swimming-pool-manager' ); ?></option>
                    <option value="Cancelled"><?php esc_html_e( 'Cancelled / Void', 'swimming-pool-manager' ); ?></option>
                </select>
            </div>

            <div class="oz-table-wrap">
                <table class="oz-table" id="ozTicketsMasterTable">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Pass Code', 'swimming-pool-manager' ); ?></th>
                            <th><?php esc_html_e( 'Guest Name', 'swimming-pool-manager' ); ?></th>
                            <th><?php esc_html_e( 'Phone', 'swimming-pool-manager' ); ?></th>
                            <th><?php esc_html_e( 'Duration', 'swimming-pool-manager' ); ?></th>
                            <th><?php esc_html_e( 'Amount', 'swimming-pool-manager' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'swimming-pool-manager' ); ?></th>
                            <th><?php esc_html_e( 'Sold By', 'swimming-pool-manager' ); ?></th>
                            <th><?php esc_html_e( 'Sold Date', 'swimming-pool-manager' ); ?></th>
                            <th style="text-align: right;"><?php esc_html_e( 'Actions', 'swimming-pool-manager' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $all_tickets ) ) : ?>
                            <?php foreach ( $all_tickets as $tkt ) : 
                                $is_valid = ( $tkt->status === 'Valid' );
                                $status_class = $is_valid ? 'ifs-pms-badge-success' : ( $tkt->status === 'Used' ? 'ifs-pms-badge-warning' : 'ifs-pms-badge-danger' );
                            ?>
                                <tr class="oz-ticket-row" data-status="<?php echo esc_attr( $tkt->status ); ?>">
                                    <td class="ifs-pms-mono" style="font-weight: 800; color: #0284c7;">
                                        <?php echo esc_html( $tkt->ticket_code ); ?>
                                    </td>
                                    <td style="font-weight: 700;">
                                        <?php echo esc_html( ! empty( $tkt->customer_name ) ? $tkt->customer_name : __( 'Walk-in Guest', 'swimming-pool-manager' ) ); ?>
                                        <?php if ( ! empty( $tkt->room_no ) ) : ?>
                                            <span style="display: block; font-size: 11px; color: #0284c7; font-weight: 700;"><i class="fa-solid fa-door-open"></i> <?php echo esc_html( $tkt->room_no ); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="ifs-pms-mono" style="color: #475569;">
                                        <?php echo esc_html( $tkt->customer_phone ); ?>
                                    </td>
                                    <td class="ifs-pms-mono">
                                        <span class="ifs-pms-badge" style="background: rgba(2, 132, 199, 0.08); color: #0284c7; font-weight: 800;">
                                            <?php echo esc_html( (string) $tkt->duration_hours ); ?> <?php echo esc_html( _n( 'Hr', 'Hrs', (int) $tkt->duration_hours, 'swimming-pool-manager' ) ); ?>
                                        </span>
                                    </td>
                                    <td class="ifs-pms-mono" style="font-weight: 800;">
                                        <?php echo esc_html( $currency . ' ' . number_format( (float) $tkt->amount, 2 ) ); ?>
                                    </td>
                                    <td>
                                        <span class="ifs-pms-badge <?php echo esc_attr( $status_class ); ?>">
                                            <?php echo esc_html( $tkt->status ); ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 12.5px; color: #64748b;">
                                        <?php echo esc_html( $tkt->sold_by ); ?>
                                    </td>
                                    <td class="ifs-pms-mono" style="font-size: 12px; color: #475569;">
                                        <?php echo esc_html( $tkt->sold_at ); ?>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=ticket_detail&ticket_id=' . $tkt->id ) ); ?>" class="oz-btn oz-btn-sm oz-btn-view">
                                            <i class="fa-solid fa-eye"></i> <?php esc_html_e( 'View', 'swimming-pool-manager' ); ?>
                                        </a>

                                        <button type="button" class="oz-btn oz-btn-sm oz-btn-edit" onclick='ozOpenEditModal(<?php echo wp_json_encode( array(
                                            'id'     => $tkt->id,
                                            'code'   => $tkt->ticket_code,
                                            'name'   => ! empty( $tkt->customer_name ) ? $tkt->customer_name : '',
                                            'phone'  => $tkt->customer_phone,
                                            'amount' => $tkt->amount,
                                            'status' => $tkt->status,
                                        ) ); ?>)'>
                                            <i class="fa-solid fa-pen-to-square"></i> <?php esc_html_e( 'Edit', 'swimming-pool-manager' ); ?>
                                        </button>

                                        <?php if ( $is_admin ) : ?>
                                            <form method="POST" action="<?php echo esc_url( $base_url . '&view=tickets' ); ?>" style="display: inline-block; margin: 0;" onsubmit="return confirm(<?php echo wp_json_encode( __( 'Delete this ticket permanently from the registry?', 'swimming-pool-manager' ) ); ?>);">
                                                <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                                                <input type="hidden" name="ifs_pms_action" value="delete_ticket">
                                                <input type="hidden" name="ticket_id" value="<?php echo esc_attr( $tkt->id ); ?>">
                                                <button type="submit" class="oz-btn oz-btn-sm oz-btn-delete" title="<?php esc_attr_e( 'Delete Permanently', 'swimming-pool-manager' ); ?>">
                                                    <i class="fa-solid fa-trash-can"></i> <?php esc_html_e( 'Delete', 'swimming-pool-manager' ); ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="9" style="text-align: center; padding: 48px; color: #94a3b8;">
                                    <i class="fa-solid fa-ticket" style="font-size: 32px; margin-bottom: 10px; opacity: 0.3; display: block;"></i>
                                    <?php esc_html_e( 'No tickets found in database.', 'swimming-pool-manager' ); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <?php if ( $total_pages > 1 ) : ?>
                <div class="oz-pagination-footer">
                    <div style="font-size: 13px; color: #475569; font-weight: 600;">
                        <?php printf( esc_html__( 'Showing %1$d–%2$d of %3$d records', 'swimming-pool-manager' ), $offset + 1, min( $offset + $per_page, $total_tickets ), $total_tickets ); ?>
                    </div>
                    <div style="display: flex; gap: 6px;">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=tickets&tab=list&paged=' . max( 1, $paged - 1 ) ) ); ?>" class="oz-page-num <?php echo ($paged <= 1) ? 'disabled' : ''; ?>"><i class="fa-solid fa-chevron-left"></i></a>
                        <?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
                            <?php if ( $i == 1 || $i == $total_pages || ( $i >= $paged - 2 && $i <= $paged + 2 ) ) : ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=tickets&tab=list&paged=' . $i ) ); ?>" class="oz-page-num <?php echo ( $i == $paged ) ? 'active' : ''; ?>"><?php echo esc_html( $i ); ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=tickets&tab=list&paged=' . min( $total_pages, $paged + 1 ) ) ); ?>" class="oz-page-num <?php echo ($paged >= $total_pages) ? 'disabled' : ''; ?>"><i class="fa-solid fa-chevron-right"></i></a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="ozEditTicketModal" class="oz-modal-overlay">
    <div class="oz-modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-pen-to-square" style="color: #0284c7;"></i>
                <?php esc_html_e( 'Edit Ticket Record', 'swimming-pool-manager' ); ?> (<span id="ozModalTicketCode" class="ifs-pms-mono"></span>)
            </h3>
            <button type="button" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;" onclick="ozCloseEditModal()">&times;</button>
        </div>

        <form method="POST" action="<?php echo esc_url( $base_url . '&view=tickets' ); ?>" id="ozEditTicketForm">
            <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
            <input type="hidden" name="ifs_pms_action" value="edit_ticket">
            <input type="hidden" name="ticket_id" id="ozModalTicketId" value="">

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div class="oz-field-group">
                    <label class="oz-field-label"><?php esc_html_e( 'Guest Name', 'swimming-pool-manager' ); ?> *</label>
                    <input type="text" name="name" id="ozModalName" required>
                </div>
                <div class="oz-field-group">
                    <label class="oz-field-label"><?php esc_html_e( 'Contact Phone', 'swimming-pool-manager' ); ?> *</label>
                    <input type="tel" name="phone" id="ozModalPhone" required>
                </div>
                <div class="oz-grid-2">
                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php printf( esc_html__( 'Amount (%s)', 'swimming-pool-manager' ), esc_html( $currency ) ); ?> *</label>
                        <input type="number" step="0.01" name="amount" id="ozModalAmount" class="ifs-pms-mono" required>
                    </div>
                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php esc_html_e( 'Status', 'swimming-pool-manager' ); ?> *</label>
                        <select name="status" id="ozModalStatus" style="height: 48px; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 8px 14px; font-weight: 700;">
                            <option value="Valid"><?php esc_html_e( 'Valid (Unused)', 'swimming-pool-manager' ); ?></option>
                            <option value="Used"><?php esc_html_e( 'Used (Admitted)', 'swimming-pool-manager' ); ?></option>
                            <option value="Cancelled"><?php esc_html_e( 'Cancelled / Void', 'swimming-pool-manager' ); ?></option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 14px;">
                    <button type="submit" class="oz-btn oz-btn-primary" style="flex: 2; height: 46px; border-radius: 12px;">
                        <i class="fa-solid fa-floppy-disk"></i> <?php esc_html_e( 'Update Ticket', 'swimming-pool-manager' ); ?>
                    </button>
                    <button type="button" class="oz-btn oz-btn-secondary" style="flex: 1; height: 46px; border-radius: 12px;" onclick="ozCloseEditModal()">
                        <?php esc_html_e( 'Cancel', 'swimming-pool-manager' ); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const currencySym = <?php echo wp_json_encode( $currency ); ?>;
    let currentGuestType = 'customer';
    let selectedAddonsList = [];
    let addonsTotal        = 0;
    let finalPayable       = 0;

    window.ozSwitchTicketTab = function(tabKey, btn) {
        document.querySelectorAll('.oz-subnav-btn').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');

        document.getElementById('ozTicketPaneAdd').classList.remove('active');
        document.getElementById('ozTicketPaneList').classList.remove('active');

        if (tabKey === 'add') {
            document.getElementById('ozTicketPaneAdd').classList.add('active');
        } else {
            document.getElementById('ozTicketPaneList').classList.add('active');
        }

        if (window.history.replaceState) {
            const url = new URL(window.location);
            url.searchParams.set('tab', tabKey);
            window.history.replaceState({}, '', url);
        }
    };

    window.ozSetGuestType = function(type) {
        currentGuestType = (type === 'room') ? 'room_guest' : 'customer';

        const pillWalkin = document.getElementById('ozTypePillWalkin');
        const pillRoom   = document.getElementById('ozTypePillRoom');
        const roomWrap   = document.getElementById('ozRoomNumberWrap');
        const roomInput  = document.getElementById('ozHotelRoomNo');
        const cashWrap   = document.getElementById('ozCashWrap');
        const cashInput  = document.getElementById('ozCashReceived');

        if (currentGuestType === 'room_guest') {
            pillWalkin.classList.remove('active');
            pillRoom.classList.add('active');

            roomWrap.style.display = 'flex';
            roomInput.setAttribute('required', 'required');

            window.ozSelectTenderByName('Complementary');
            if (cashWrap) cashWrap.style.display = 'none';
            if (cashInput) cashInput.removeAttribute('required');
        } else {
            pillRoom.classList.remove('active');
            pillWalkin.classList.add('active');

            roomWrap.style.display = 'none';
            roomInput.removeAttribute('required');
            roomInput.value = '';

            window.ozSelectTenderByName('Cash');
            if (cashWrap) cashWrap.style.display = 'grid';
            if (cashInput) cashInput.setAttribute('required', 'required');
        }

        window.ozRecalculate();
    };

    window.ozSelectTenderByName = function(name) {
        document.querySelectorAll('.oz-tender-box').forEach(box => {
            if (box.textContent.trim().includes(name)) {
                document.querySelectorAll('.oz-tender-box').forEach(b => b.classList.remove('active'));
                box.classList.add('active');
                document.getElementById('ozSelectedPayment').value = name;
                document.getElementById('ozPrevTender').textContent = name;
            }
        });
    };

    window.ozOpenEditModal = function(data) {
        document.getElementById('ozModalTicketId').value = data.id;
        document.getElementById('ozModalTicketCode').textContent = data.code;
        document.getElementById('ozModalName').value = data.name;
        document.getElementById('ozModalPhone').value = data.phone;
        document.getElementById('ozModalAmount').value = parseFloat(data.amount).toFixed(2);
        document.getElementById('ozModalStatus').value = data.status;

        document.getElementById('ozEditTicketModal').style.display = 'flex';
    };

    window.ozCloseEditModal = function() {
        document.getElementById('ozEditTicketModal').style.display = 'none';
    };

    function renderQr(code) {
        const wrap = document.getElementById('ozReceiptQrWrap');
        if (!wrap || typeof QRCode === 'undefined') return;
        wrap.innerHTML = '';
        new QRCode(wrap, {
            text: code,
            width: 85,
            height: 85,
            colorDark: '#0f172a',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
    }

    // Toggle Box Switch
    window.ozToggleTierSwitch = function(index) {
        const box    = document.getElementById('ozTierBox_' + index);
        const toggle = document.getElementById('ozTierToggle_' + index);
        const qtyIn  = document.getElementById('ozTierPersons_' + index);

        if (toggle.checked) {
            box.classList.add('is-enabled');
            if (parseInt(qtyIn.value, 10) === 0) {
                qtyIn.value = 1;
            }
        } else {
            box.classList.remove('is-enabled');
            qtyIn.value = 0;
        }

        window.ozRecalculate();
    };

    // Stepper: Headcount per package
    window.ozDeltaModularQty = function(index, delta) {
        const qtyIn  = document.getElementById('ozTierPersons_' + index);
        const toggle = document.getElementById('ozTierToggle_' + index);
        const box    = document.getElementById('ozTierBox_' + index);

        let val = parseInt(qtyIn.value, 10) || 0;
        val = Math.max(0, Math.min(50, val + delta));
        qtyIn.value = val;

        if (val > 0) {
            toggle.checked = true;
            box.classList.add('is-enabled');
        } else {
            toggle.checked = false;
            box.classList.remove('is-enabled');
        }

        window.ozRecalculate();
    };

    // Stepper: Duration per package
    window.ozDeltaModularHours = function(index, delta) {
        const hrsIn = document.getElementById('ozTierHours_' + index);
        let val = parseInt(hrsIn.value, 10) || 1;
        val = Math.max(1, Math.min(12, val + delta));
        hrsIn.value = val;

        window.ozRecalculate();
    };

    // Master Recalculation Engine
    window.ozRecalculate = function() {
        const tenderMethod = document.getElementById('ozSelectedPayment').value;
        const isFreeTender = (currentGuestType === 'room_guest' || tenderMethod === 'Complementary' || tenderMethod === 'Room Guest' || tenderMethod === 'Complimentary');

        let totalTiersCost = 0;
        let selectedSummary = [];
        const boxes = document.querySelectorAll('.oz-tier-box');

        boxes.forEach(box => {
            const index  = box.getAttribute('data-index');
            const toggle = document.getElementById('ozTierToggle_' + index);
            
            if (toggle && toggle.checked) {
                const name    = box.getAttribute('data-name');
                const age     = box.getAttribute('data-age');
                const rate    = parseFloat(box.getAttribute('data-price')) || 0;
                const persons = parseInt(document.getElementById('ozTierPersons_' + index).value, 10) || 0;
                const hours   = parseInt(document.getElementById('ozTierHours_' + index).value, 10) || 1;

                if (persons > 0) {
                    const subtotal = isFreeTender ? 0 : (rate * persons * hours);
                    totalTiersCost += subtotal;
                    selectedSummary.push({
                        name: name,
                        age: age,
                        rate: rate,
                        persons: persons,
                        hours: hours,
                        subtotal: subtotal
                    });
                }
            }
        });

        // Ensure fallback to first tier if none active
        if (selectedSummary.length === 0 && boxes.length > 0) {
            const firstToggle = document.getElementById('ozTierToggle_0');
            const firstQty    = document.getElementById('ozTierPersons_0');
            const firstBox    = document.getElementById('ozTierBox_0');
            if (firstToggle && firstQty && firstBox) {
                firstToggle.checked = true;
                firstBox.classList.add('is-enabled');
                firstQty.value = 1;

                const rate = parseFloat(firstBox.getAttribute('data-price')) || 0;
                const hours = parseInt(document.getElementById('ozTierHours_0').value, 10) || 1;
                const subtotal = isFreeTender ? 0 : (rate * 1 * hours);
                totalTiersCost = subtotal;

                selectedSummary.push({
                    name: firstBox.getAttribute('data-name'),
                    age: firstBox.getAttribute('data-age'),
                    rate: rate,
                    persons: 1,
                    hours: hours,
                    subtotal: subtotal
                });
            }
        }

        finalPayable = totalTiersCost + addonsTotal;

        const amountField = document.getElementById('ozSubmittedAmount');
        if (amountField) amountField.value = finalPayable.toFixed(2);

        // SYNC DURATION HOURS TO HIDDEN INPUT
        const bookedDuration = selectedSummary.reduce((max, item) => Math.max(max, item.hours), 1);
        const durationField = document.getElementById('ozDurationHoursInput');
        if (durationField) {
            durationField.value = bookedDuration;
        }

        const packageInput = document.getElementById('ozPackageNameInput');
        if (packageInput) {
            packageInput.value = selectedSummary.map(s => s.name + ' (' + s.persons + 'p x ' + s.hours + 'h)').join(', ');
        }

        const guestName  = document.getElementById('ozGuestName').value.trim();
        const guestPhone = document.getElementById('ozGuestPhone').value.trim();
        const roomVal    = document.getElementById('ozHotelRoomNo').value.trim();

        document.getElementById('ozPrevClassification').textContent = (currentGuestType === 'room_guest') ? 'Hotel Room Guest' : 'General Customer';
        document.getElementById('ozPrevName').textContent           = guestName || 'Walk-in Guest';
        document.getElementById('ozPrevPhone').textContent          = guestPhone || '017XXXXXXXX';

        // Admission itemized breakdown
        const tiersTbody = document.getElementById('ozPrevTiersBody');
        if (tiersTbody) {
            tiersTbody.innerHTML = '';
            if (selectedSummary.length === 0) {
                tiersTbody.innerHTML = '<tr><td colspan="3" style="color:#94a3b8; font-size:11px; padding:6px 0;">No active package enabled</td></tr>';
            } else {
                selectedSummary.forEach(s => {
                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px dashed #e2e8f0';

                    const rateInfo = isFreeTender ? 'FREE' : (s.rate.toFixed(2) + ' ' + currencySym + '/hr');

                    tr.innerHTML = 
                        '<td style="padding: 6px 0; vertical-align: top;">' +
                            '<div style="color: #0f172a; font-weight: 700; font-size: 11.5px; line-height: 1.3;">' + s.name + '</div>' +
                            '<div style="color: #475569; font-weight: 600; font-size: 10.5px; margin-top: 1px;">' + s.age + '</div>' +
                            '<div style="font-size: 10px; color: #64748b; margin-top: 1px;">' +
                                s.hours + (s.hours > 1 ? ' hrs session (' : ' hr session (') + rateInfo + ')' +
                            '</div>' +
                        '</td>' +
                        '<td style="text-align: right; font-weight: 700; color: #0f172a; font-size: 11.5px; padding: 6px 0; vertical-align: top; white-space: nowrap;">' +
                            s.persons + ' x' +
                        '</td>' +
                        '<td style="text-align: right; font-weight: 800; color: #0284c7; font-size: 11.5px; padding: 6px 0; vertical-align: top; white-space: nowrap;">' +
                            (isFreeTender ? '0.00' : s.subtotal.toFixed(2) + ' ' + currencySym) +
                        '</td>';

                    tiersTbody.appendChild(tr);
                });
            }
        }

        // Addons table rows
        const addonsTbody = document.getElementById('ozPrevAddonsBody');
        if (addonsTbody) {
            addonsTbody.innerHTML = '';
            selectedAddonsList.forEach(a => {
                const tr = document.createElement('tr');
                tr.innerHTML = '<td colspan="2" style="color:#64748b; font-size:11px; padding:4px 0;">+ ' + a.name + '</td><td style="text-align:right; color:#64748b; font-size:11px; padding:4px 0;">' + a.price.toFixed(2) + ' ' + currencySym + '</td>';
                addonsTbody.appendChild(tr);
            });
        }

        document.getElementById('ozPrevTotal').textContent = currencySym + ' ' + finalPayable.toFixed(2);

        const roomRow = document.getElementById('ozPrevRoomRow');
        const roomTxt = document.getElementById('ozPrevRoom');
        if (currentGuestType === 'room_guest' && roomVal) {
            roomRow.style.display = 'table-row';
            roomTxt.textContent   = roomVal;
        } else {
            roomRow.style.display = 'none';
        }

        // Dynamically enforce minimum required cash
        const cashInput = document.getElementById('ozCashReceived');
        if (cashInput) {
            if (tenderMethod === 'Cash' && currentGuestType === 'customer' && finalPayable > 0) {
                cashInput.setAttribute('required', 'required');
                cashInput.setAttribute('min', finalPayable.toFixed(2));
            } else {
                cashInput.removeAttribute('required');
                cashInput.removeAttribute('min');
                cashInput.classList.remove('oz-input-invalid');
            }
        }

        window.ozComputeChange();
    };

    window.ozToggleAddon = function(card, price, name) {
        const isSelected = card.classList.toggle('selected');
        const icon = card.querySelector('i');

        if (isSelected) {
            icon.className = 'fa-solid fa-square-check';
            addonsTotal += price;
            selectedAddonsList.push({ name: name, price: price });
        } else {
            icon.className = 'fa-regular fa-square';
            addonsTotal -= price;
            selectedAddonsList = selectedAddonsList.filter(a => a.name !== name);
        }
        window.ozRecalculate();
    };

    window.ozSelectTender = function(method, el) {
        document.querySelectorAll('.oz-tender-box').forEach(b => b.classList.remove('active'));
        el.classList.add('active');

        document.getElementById('ozSelectedPayment').value = method;
        document.getElementById('ozPrevTender').textContent = method;

        const cashWrap = document.getElementById('ozCashWrap');
        const roomWrap = document.getElementById('ozRoomNumberWrap');
        const cashInput = document.getElementById('ozCashReceived');

        if (method === 'Complementary') {
            if (cashWrap) cashWrap.style.display = 'none';
            if (cashInput) cashInput.removeAttribute('required');
        } else {
            if (cashWrap) cashWrap.style.display = (method === 'Cash') ? 'grid' : 'none';
            if (roomWrap) roomWrap.style.display = (currentGuestType === 'room_guest') ? 'flex' : 'none';
            if (cashInput) {
                if (method === 'Cash' && currentGuestType === 'customer') {
                    cashInput.setAttribute('required', 'required');
                } else {
                    cashInput.removeAttribute('required');
                    cashInput.classList.remove('oz-input-invalid');
                }
            }
        }

        window.ozRecalculate();
    };

    window.ozQuickCash = function(val) {
        const tenderInput = document.getElementById('ozCashReceived');
        if (val === 'exact') {
            tenderInput.value = finalPayable.toFixed(2);
        } else {
            const current = parseFloat(tenderInput.value) || 0;
            tenderInput.value = (current + val).toFixed(2);
        }
        tenderInput.classList.remove('oz-input-invalid');
        window.ozComputeChange();
    };

    window.ozComputeChange = function() {
        const cashInput = document.getElementById('ozCashReceived');
        const received  = parseFloat(cashInput.value) || 0;
        const changeEl  = document.getElementById('ozChangeDue');
        const diff      = received - finalPayable;

        if (diff >= 0 && received > 0) {
            changeEl.textContent = diff.toFixed(2) + ' ' + currencySym;
            changeEl.style.color = '#10b981';
            cashInput.classList.remove('oz-input-invalid');
        } else {
            changeEl.textContent = '0.00 ' + currencySym;
            changeEl.style.color = '#475569';
        }
    };

    // Isolated Thermal Receipt Printer
    window.ozPrintPreviewReceipt = function() {
        const receiptEl = document.getElementById('ozReceiptPreviewContainer');
        if (!receiptEl) return;

        // Clone element to sanitize from action buttons
        const printClone = receiptEl.cloneNode(true);
        const actionBtn = printClone.querySelector('.oz-receipt-print-action');
        if (actionBtn) actionBtn.remove();

        const printIframe = document.createElement('iframe');
        printIframe.style.position = 'fixed';
        printIframe.style.right = '0';
        printIframe.style.bottom = '0';
        printIframe.style.width = '0';
        printIframe.style.height = '0';
        printIframe.style.border = '0';
        document.body.appendChild(printIframe);

        const doc = printIframe.contentWindow.document;
        doc.open();
        doc.write('<!DOCTYPE html><html><head><title>' + <?php echo wp_json_encode( __( 'Print Thermal Slip', 'swimming-pool-manager' ) ); ?> + '</title>');
        doc.write('<style>');
        doc.write('@page { size: 80mm auto; margin: 0; }');
        doc.write('body { margin: 0; padding: 10px; font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #000; background: #fff; width: 72mm; }');
        doc.write('.ifs-pms-mono { font-family: "JetBrains Mono", monospace; }');
        doc.write('.oz-receipt-sep { border-bottom: 1.5px dashed #475569; margin: 8px 0; }');
        doc.write('.oz-receipt-table { width: 100%; border-collapse: collapse; font-size: 11px; }');
        doc.write('.oz-receipt-table td, .oz-receipt-table th { padding: 3px 0; vertical-align: top; }');
        doc.write('.oz-receipt-rules { margin-top: 10px; padding: 8px; border: 1px dashed #64748b; border-radius: 6px; font-size: 8.5px; }');
        doc.write('.oz-rules-title { font-weight: 800; font-size: 9px; margin-bottom: 4px; text-align: center; }');
        doc.write('.oz-rules-list { margin: 0; padding-left: 14px; line-height: 1.35; }');
        doc.write('img { max-height: 40px; }');
        doc.write('</style></head><body>');
        doc.write(printClone.innerHTML);
        doc.write('</body></html>');
        doc.close();

        setTimeout(function() {
            printIframe.contentWindow.focus();
            printIframe.contentWindow.print();
            setTimeout(function() {
                document.body.removeChild(printIframe);
            }, 1000);
        }, 300);
    };

    // Validate Cash Received on Form Submission
    window.ozValidateFormSubmission = function(e) {
        const tenderMethod = document.getElementById('ozSelectedPayment').value;
        const cashInput    = document.getElementById('ozCashReceived');

        if (tenderMethod === 'Cash' && currentGuestType === 'customer' && finalPayable > 0) {
            const received = parseFloat(cashInput.value) || 0;
            if (received < finalPayable || isNaN(received)) {
                if (e) e.preventDefault();
                cashInput.classList.add('oz-input-invalid');
                cashInput.focus();
                alert(<?php echo wp_json_encode( __( 'Cash Tendered is required and must be equal to or greater than the net payable amount.', 'swimming-pool-manager' ) ); ?>);
                return false;
            }
        }
        return true;
    };

    window.ozResetTerminal = function() {
        document.getElementById('ozPosMasterForm').reset();
        addonsTotal = 0;
        selectedAddonsList = [];
        document.querySelectorAll('.oz-addon-item').forEach(c => {
            c.classList.remove('selected');
            c.querySelector('i').className = 'fa-regular fa-square';
        });

        document.querySelectorAll('.oz-tier-box').forEach((box, idx) => {
            const toggle = document.getElementById('ozTierToggle_' + idx);
            const qtyIn  = document.getElementById('ozTierPersons_' + idx);
            const hrsIn  = document.getElementById('ozTierHours_' + idx);
            if (toggle && qtyIn) {
                toggle.checked = (idx === 0);
                qtyIn.value = (idx === 0) ? 1 : 0;
                if (idx === 0) {
                    box.classList.add('is-enabled');
                } else {
                    box.classList.remove('is-enabled');
                }
            }
            if (hrsIn) hrsIn.value = 1;
        });

        const durationField = document.getElementById('ozDurationHoursInput');
        if (durationField) durationField.value = 1;

        const cashInput = document.getElementById('ozCashReceived');
        if (cashInput) cashInput.classList.remove('oz-input-invalid');

        window.ozSetGuestType('walkin');
        window.ozRecalculate();
        document.getElementById('ozGuestName').focus();
    };

    window.ozFilterTicketTable = function() {
        const query = (document.getElementById('ozTicketSearchInput').value || '').toLowerCase().trim();
        const status = document.getElementById('ozTicketStatusFilter').value;
        const rows = document.querySelectorAll('.oz-ticket-row');

        rows.forEach(r => {
            const rowStatus = r.getAttribute('data-status');
            const rowText = r.textContent.toLowerCase();

            const matchQuery  = !query || rowText.includes(query);
            const matchStatus = (status === 'ALL') || (rowStatus === status);

            r.style.display = (matchQuery && matchStatus) ? '' : 'none';
        });
    };

    function init() {
        ['ozGuestName', 'ozGuestPhone', 'ozHotelRoomNo'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', window.ozRecalculate);
        });

        const cashInput = document.getElementById('ozCashReceived');
        if (cashInput) cashInput.addEventListener('input', window.ozComputeChange);

        const tokenText = document.getElementById('ozPrevToken').textContent.trim();
        renderQr(tokenText);
        window.ozRecalculate();

        window.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                const form = document.getElementById('ozPosMasterForm');
                if (form && window.ozValidateFormSubmission(e) && form.checkValidity()) {
                    e.preventDefault();
                    form.submit();
                }
            }
            if (e.altKey && (e.key === 'c' || e.key === 'C')) {
                e.preventDefault();
                window.ozResetTerminal();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>