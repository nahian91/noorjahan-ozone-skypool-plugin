<?php
/**
 * View: High-Performance Front Desk POS & Master Ticket Registry (Executive UI/UX Edition v16 - Separate Line Hierarchy & Thermal Safety Rules)
 *
 * @package Ozone_Skypool_OS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$t_tick        = $wpdb->prefix . 'ifs_pms_tickets';
$t_cust        = $wpdb->prefix . 'ifs_pms_customers';
$currency      = esc_html( get_option( 'ifs_pms_currency', 'BDT' ) );
$default_price = (float) get_option( 'ifs_pms_ticket_price', 500.00 );
$b_name        = esc_html( get_option( 'ifs_pms_business_name', 'Ozone Restaurant & Skypool' ) );
$address       = esc_html( get_option( 'ifs_pms_address', '20th Floor, Ritz Tower, Dargah Gate, Sylhet' ) );
$phone         = esc_html( get_option( 'ifs_pms_phone', '+880 1700-000000' ) );
$logo_url      = esc_url( get_option( 'ifs_pms_logo_url', '' ) );
$base_url      = admin_url( 'admin.php?page=ifs-pms' );
$current_staff = wp_get_current_user()->display_name;
$is_admin      = current_user_can( 'manage_options' ) || current_user_can( 'ozone_manage_settings' );

// Active Tab Router
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

<style>
    .oz-pos-wrapper {
        display: flex;
        flex-direction: column;
        gap: 24px;
        width: 100%;
        box-sizing: border-box;
    }

    .oz-subnav-bar {
        display: flex;
        width: 100%;
        gap: 10px;
        background: linear-gradient(135deg, rgba(241, 245, 249, 0.8) 0%, rgba(226, 232, 240, 0.6) 100%);
        backdrop-filter: blur(12px);
        padding: 6px;
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
        padding: 12px 24px;
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
        background: rgba(255, 255, 255, 0.6);
    }

    .oz-subnav-btn.active {
        background: #ffffff !important;
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

    .oz-pos-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.45fr) minmax(360px, 420px);
        gap: 28px;
        align-items: start;
        box-sizing: border-box;
        width: 100%;
    }

    @media (max-width: 1200px) {
        .oz-pos-layout { grid-template-columns: 1fr; }
    }

    .oz-pos-card {
        background: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 24px;
        box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .oz-pos-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 22px 30px;
        border-bottom: 1px solid #f1f5f9;
        background: linear-gradient(to bottom, #fafbfd, #f8fafc);
    }

    .oz-pos-title {
        margin: 0;
        font-size: 16.5px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #0f172a;
        letter-spacing: -0.01em;
    }

    .oz-pos-form-wrap {
        padding: 30px;
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .oz-guest-type-selector {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    @media (max-width: 600px) {
        .oz-guest-type-selector {
            grid-template-columns: 1fr;
        }
    }

    .oz-type-pill {
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 14px;
        padding: 12px 18px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        user-select: none;
    }

    .oz-type-pill i {
        font-size: 20px;
        color: #64748b;
        transition: color 0.2s ease;
    }

    .oz-type-pill div {
        display: flex;
        flex-direction: column;
    }

    .oz-type-pill strong {
        font-size: 13.5px;
        color: #0f172a;
    }

    .oz-type-pill small {
        font-size: 11px;
        color: #64748b;
        margin-top: 2px;
    }

    .oz-type-pill:hover {
        border-color: #cbd5e1;
        background: #f1f5f9;
    }

    .oz-type-pill.active {
        background: rgba(2, 132, 199, 0.06);
        border-color: #0284c7;
        box-shadow: 0 4px 14px rgba(2, 132, 199, 0.12);
    }

    .oz-type-pill.active i,
    .oz-type-pill.active strong {
        color: #0284c7;
    }

    .oz-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }

    @media (max-width: 760px) {
        .oz-grid-2 { grid-template-columns: 1fr; }
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
        color: #475569;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    #wpcontent .oz-pos-layout input[type="text"],
    #wpcontent .oz-pos-layout input[type="number"],
    #wpcontent .oz-pos-layout input[type="tel"] {
        display: block !important;
        width: 100% !important;
        background: #ffffff !important;
        border: 1.5px solid #cbd5e1 !important;
        border-radius: 12px !important;
        padding: 12px 16px !important;
        font-size: 14px !important;
        font-family: var(--ifs-font-sans, inherit) !important;
        color: #0f172a !important;
        height: 48px !important;
        box-sizing: border-box !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    #wpcontent .oz-pos-layout input:focus {
        border-color: #0284c7 !important;
        box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.12) !important;
        outline: none !important;
    }

    .oz-modular-deck {
        display: flex;
        flex-direction: column;
        gap: 16px;
        width: 100%;
    }

    .oz-tier-box {
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 18px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        opacity: 0.72;
    }

    .oz-tier-box.is-enabled {
        background: #ffffff;
        border-color: #0284c7;
        box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.12), 0 2px 6px rgba(0,0,0,0.02);
        opacity: 1;
    }

    .oz-tier-box-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px dashed #e2e8f0;
        padding-bottom: 14px;
    }

    .oz-tier-title {
        font-size: 15px;
        font-weight: 800;
        color: #0f172a;
    }

    .oz-tier-badge {
        display: inline-block;
        font-size: 11px;
        font-weight: 700;
        color: #0284c7;
        background: rgba(2, 132, 199, 0.08);
        padding: 2px 8px;
        border-radius: 6px;
        margin-top: 4px;
    }

    .oz-switch {
        position: relative;
        display: inline-block;
        width: 46px;
        height: 24px;
        flex-shrink: 0;
    }

    .oz-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .oz-slider {
        position: absolute;
        cursor: pointer;
        inset: 0;
        background-color: #cbd5e1;
        border-radius: 24px;
        transition: 0.25s;
    }

    .oz-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: #ffffff;
        border-radius: 50%;
        transition: 0.25s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }

    .oz-switch input:checked + .oz-slider {
        background-color: #0284c7;
    }

    .oz-switch input:checked + .oz-slider:before {
        transform: translateX(22px);
    }

    .oz-tier-controls-row {
        display: grid;
        grid-template-columns: 1.2fr 1fr 1fr;
        gap: 12px;
    }

    @media (max-width: 650px) {
        .oz-tier-controls-row {
            grid-template-columns: 1fr;
        }
    }

    .oz-metric-pill {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 8px 12px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 4px;
    }

    .oz-metric-label {
        font-size: 10.5px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .oz-mini-qty {
        display: flex;
        align-items: center;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        height: 32px;
        padding: 2px;
        box-sizing: border-box;
    }

    .oz-mini-btn {
        width: 26px;
        height: 26px;
        background: #f1f5f9;
        border: none;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #0f172a;
        font-size: 11px;
        transition: background 0.15s;
    }

    .oz-mini-btn:hover {
        background: #e2e8f0;
        color: #0284c7;
    }

    .oz-mini-input {
        width: 100% !important;
        height: 100% !important;
        text-align: center;
        border: none !important;
        background: transparent !important;
        font-size: 13.5px !important;
        font-weight: 800 !important;
        color: #0f172a !important;
        padding: 0 !important;
        box-shadow: none !important;
    }

    .oz-addon-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    @media (max-width: 600px) { .oz-addon-grid { grid-template-columns: 1fr; } }

    .oz-addon-item {
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 14px;
        padding: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        user-select: none;
        transition: all 0.2s ease;
    }

    .oz-addon-item:hover { border-color: #cbd5e1; background: #f1f5f9; }
    .oz-addon-item.selected { background: rgba(2, 132, 199, 0.05); border-color: #0284c7; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.08); }
    .oz-addon-item.selected i { color: #0284c7; }

    .oz-tender-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    @media (max-width: 700px) { .oz-tender-grid { grid-template-columns: repeat(2, 1fr); } }

    .oz-tender-box {
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 14px;
        padding: 14px 10px;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        font-size: 12.5px;
        font-weight: 700;
        color: #475569;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .oz-tender-box:hover { border-color: #cbd5e1; transform: translateY(-2px); background: #f1f5f9; }
    .oz-tender-box.active {
        background: rgba(2, 132, 199, 0.08);
        color: #0284c7;
        border-color: #0284c7;
        box-shadow: 0 6px 16px rgba(2, 132, 199, 0.16);
    }

    .oz-quick-cash-row { display: flex; gap: 8px; margin-top: 10px; }
    .oz-quick-cash-row .oz-btn-sm {
        height: 32px !important;
        padding: 0 14px !important;
        font-size: 12px !important;
        font-weight: 700 !important;
        font-family: var(--ifs-font-mono, monospace) !important;
        background: #f1f5f9 !important;
        border: 1.5px solid #e2e8f0 !important;
        color: #475569 !important;
        border-radius: 8px !important;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .oz-quick-cash-row .oz-btn-sm:hover { border-color: #0284c7; color: #0284c7; background: #ffffff !important; }

    #wpcontent .oz-pos-layout .oz-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 10px !important;
        font-weight: 800 !important;
        cursor: pointer !important;
        border-radius: 14px !important;
        transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }

    #wpcontent .oz-pos-layout .oz-btn-primary.oz-btn-lg {
        height: 54px !important;
        font-size: 15px !important;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
        color: #ffffff !important;
        border: 1px solid rgba(2, 132, 199, 0.8) !important;
        box-shadow: 0 10px 24px rgba(2, 132, 199, 0.4) !important;
    }

    #wpcontent .oz-pos-layout .oz-btn-primary.oz-btn-lg:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 28px rgba(2, 132, 199, 0.5) !important;
    }

    #wpcontent .oz-pos-layout .oz-btn-secondary.oz-btn-lg {
        height: 54px !important;
        font-size: 14.5px !important;
        background: #ffffff !important;
        color: #475569 !important;
        border: 1.5px solid #cbd5e1 !important;
    }

    #wpcontent .oz-pos-layout .oz-btn-secondary.oz-btn-lg:hover {
        background: #f8fafc !important;
        color: #0f172a !important;
        transform: translateY(-2px);
    }

    .oz-receipt-preview-card {
        background: #ffffff;
        color: #0f172a;
        border-radius: 20px;
        padding: 24px;
        font-family: var(--ifs-font-mono, monospace);
        box-shadow: 0 20px 45px -12px rgba(15, 23, 42, 0.1);
        border: 1.5px solid #cbd5e1;
        position: sticky;
        top: 24px;
    }

    .oz-receipt-sep { border-top: 1.5px dashed #cbd5e1; margin: 14px 0; }
    .oz-receipt-table { width: 100%; border-collapse: collapse; font-size: 12px; line-height: 1.75; }

    /* Turnstile Safety Rules Notice */
    .oz-receipt-rules {
        margin-top: 14px;
        padding-top: 10px;
        border-top: 1px dashed #cbd5e1;
        text-align: left;
    }

    .oz-rules-title {
        font-size: 9px;
        font-weight: 800;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        text-align: center;
        margin-bottom: 6px;
    }

    .oz-rules-list {
        margin: 0;
        padding-left: 14px;
        list-style-type: square;
        color: #64748b;
        font-size: 8.5px;
        line-height: 1.5;
    }

    .oz-rules-list li {
        margin-bottom: 2px;
    }

    .oz-search-bar { display: flex; gap: 16px; padding: 20px 28px; border-bottom: 1px solid #f1f5f9; align-items: center; background: #ffffff; }
    .oz-search-box { position: relative; flex: 1; min-width: 260px; }
    .oz-search-box i.search-icon { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; pointer-events: none; }
    #wpcontent .oz-search-box input { padding-left: 42px !important; height: 44px !important; border-radius: 12px !important; }
    #wpcontent .oz-search-bar select { min-width: 180px !important; height: 44px !important; border-radius: 12px !important; font-weight: 700 !important; }

    .oz-table-wrap { width: 100%; overflow-x: auto; }
    .oz-table { width: 100%; border-collapse: collapse; font-size: 13.5px; text-align: left; }
    .oz-table th { background: #f8fafc; color: #64748b; font-weight: 700; font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.05em; padding: 16px 22px; border-bottom: 1px solid #e2e8f0; }
    .oz-table td { padding: 18px 22px; border-bottom: 1px solid #f1f5f9; color: #0f172a; vertical-align: middle; }
    .oz-table tr:hover td { background: #f8fafc; }

    .oz-table .oz-btn-sm {
        height: 34px !important;
        padding: 0 12px !important;
        font-size: 12px !important;
        border-radius: 8px !important;
        font-weight: 700 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        transition: all 0.2s ease !important;
    }

    .oz-table .oz-btn-sm:hover { transform: translateY(-2px); }
    .oz-table .oz-btn-view { background: rgba(16, 185, 129, 0.08) !important; color: #059669 !important; border: 1.5px solid rgba(16, 185, 129, 0.25) !important; }
    .oz-table .oz-btn-view:hover { background: #10b981 !important; color: #ffffff !important; border-color: #10b981 !important; }
    .oz-table .oz-btn-edit { background: rgba(2, 132, 199, 0.08) !important; color: #0284c7 !important; border: 1.5px solid rgba(2, 132, 199, 0.25) !important; }
    .oz-table .oz-btn-edit:hover { background: #0284c7 !important; color: #ffffff !important; border-color: #0284c7 !important; }
    .oz-table .oz-btn-delete { background: rgba(244, 63, 94, 0.08) !important; color: #f43f5e !important; border: 1.5px solid rgba(244, 63, 94, 0.25) !important; }
    .oz-table .oz-btn-delete:hover { background: #f43f5e !important; color: #ffffff !important; border-color: #f43f5e !important; }

    .oz-pagination-footer { display: flex; justify-content: space-between; align-items: center; padding: 18px 28px; background: #f8fafc; border-top: 1px solid #e2e8f0; flex-wrap: wrap; gap: 12px; }
    .oz-page-num { display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 34px; padding: 0 8px; border-radius: 8px; font-size: 12.5px; font-weight: 700; text-decoration: none !important; background: #ffffff; color: #475569; border: 1.5px solid #cbd5e1; }
    .oz-page-num.active { background: #0284c7 !important; color: #ffffff !important; border-color: #0284c7 !important; }
    .oz-page-num.disabled { opacity: 0.4; pointer-events: none; background: #f1f5f9; }

    .oz-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center; }
    .oz-modal-card { background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 20px; width: 500px; max-width: 95vw; padding: 30px; box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3); }
</style>

<div class="oz-pos-wrapper">
    <!-- Sub Navigation Tab Bar -->
    <div class="oz-subnav-bar" role="tablist">
        <button type="button" class="oz-subnav-btn <?php echo ( $active_tab === 'add' ) ? 'active' : ''; ?>" id="ozTabBtnAdd" onclick="ozSwitchTicketTab('add', this)">
            <i class="fa-solid fa-plus-circle"></i> <?php esc_html_e( 'Issue Ticket POS', 'ozone-skypool' ); ?>
        </button>
        <button type="button" class="oz-subnav-btn <?php echo ( $active_tab === 'list' ) ? 'active' : ''; ?>" id="ozTabBtnList" onclick="ozSwitchTicketTab('list', this)">
            <i class="fa-solid fa-table-list"></i> <?php esc_html_e( 'Pass Ledger Registry', 'ozone-skypool' ); ?>
        </button>
    </div>

    <!-- TAB 1: Add Ticket POS -->
    <div id="ozTicketPaneAdd" class="oz-tab-pane <?php echo ( $active_tab === 'add' ) ? 'active' : ''; ?>">
        <?php if ( $pool_status !== 'open' || ! $is_today_open || ! $is_within_hours ) : ?>
            <div style="background: rgba(244, 63, 94, 0.08); border: 1.5px solid rgba(244, 63, 94, 0.3); color: #f43f5e; padding: 16px 22px; border-radius: 16px; margin-bottom: 24px; font-weight: 700; font-size: 14px; display: flex; align-items: center; gap: 14px;">
                <i class="fa-solid fa-triangle-exclamation" style="font-size: 20px;"></i>
                <div>
                    <?php if ( $pool_status === 'closed' ) : ?>
                        <?php esc_html_e( 'Notice: The pool is currently marked as CLOSED by management.', 'ozone-skypool' ); ?>
                    <?php elseif ( $pool_status === 'maintenance' ) : ?>
                        <?php esc_html_e( 'Notice: Pool is undergoing routine MAINTENANCE.', 'ozone-skypool' ); ?>
                    <?php else : ?>
                        <?php printf( esc_html__( 'Notice: Outside normal operating hours (%s: %s to %s).', 'ozone-skypool' ), ucfirst( $current_day_key ), $today_schedule['open'], $today_schedule['close'] ); ?>
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
                        <?php esc_html_e( 'Skypool Front Gate POS', 'ozone-skypool' ); ?>
                    </h3>
                    <span style="font-size: 12px; color: #64748b;">
                        <?php esc_html_e( 'Print Shortcut:', 'ozone-skypool' ); ?> 
                        <kbd style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 3px 6px; border-radius: 6px; font-weight: 800; color: #0284c7;">Ctrl + Enter</kbd>
                    </span>
                </div>

                <form method="POST" action="<?php echo esc_url( $base_url . '&view=tickets' ); ?>" id="ozPosMasterForm">
                    <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                    <input type="hidden" name="ifs_pms_action" value="issue_ticket">
                    <input type="hidden" name="package_name" id="ozPackageNameInput" value="">
                    <input type="hidden" name="amount" id="ozSubmittedAmount" value="500.00">
                    <input type="hidden" name="payment_method" id="ozSelectedPayment" value="Cash">

                    <div class="oz-pos-form-wrap">
                        <!-- Patron Classification -->
                        <div class="oz-field-group">
                            <label class="oz-field-label">
                                <i class="fa-solid fa-user-tag" style="color: #0284c7;"></i> <?php esc_html_e( 'Patron Classification', 'ozone-skypool' ); ?> *
                            </label>
                            <div class="oz-guest-type-selector">
                                <label class="oz-type-pill active" id="ozTypePillWalkin" onclick="ozSetGuestType('walkin')">
                                    <input type="radio" name="guest_type" value="customer" checked style="display: none;">
                                    <i class="fa-solid fa-users"></i>
                                    <div>
                                        <strong><?php esc_html_e( 'General Customer', 'ozone-skypool' ); ?></strong>
                                        <small><?php esc_html_e( 'Standard Ticket Rates Apply', 'ozone-skypool' ); ?></small>
                                    </div>
                                </label>

                                <label class="oz-type-pill" id="ozTypePillRoom" onclick="ozSetGuestType('room')">
                                    <input type="radio" name="guest_type" value="room_guest" style="display: none;">
                                    <i class="fa-solid fa-hotel"></i>
                                    <div>
                                        <strong><?php esc_html_e( 'Hotel Room Guest', 'ozone-skypool' ); ?></strong>
                                        <small><?php esc_html_e( 'Complimentary Pass', 'ozone-skypool' ); ?></small>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Patron Details -->
                        <div class="oz-grid-2">
                            <div class="oz-field-group">
                                <label class="oz-field-label" for="ozGuestName"><?php esc_html_e( 'Patron Name', 'ozone-skypool' ); ?> *</label>
                                <input type="text" name="name" id="ozGuestName" required placeholder="<?php esc_attr_e( 'e.g. Tanvir Ahmed', 'ozone-skypool' ); ?>" autocomplete="off">
                            </div>
                            <div class="oz-field-group">
                                <label class="oz-field-label" for="ozGuestPhone"><?php esc_html_e( 'Mobile Number (e-Pass SMS)', 'ozone-skypool' ); ?> *</label>
                                <input type="tel" name="phone" id="ozGuestPhone" required placeholder="017XXXXXXXX" pattern="[0-9+\s\-]{7,20}" autocomplete="off">
                            </div>
                        </div>

                        <!-- Hotel Room Number Input (Toggled by Room Guest) -->
                        <div class="oz-field-group" style="display: none;" id="ozRoomNumberWrap">
                            <label class="oz-field-label" for="ozHotelRoomNo">
                                <i class="fa-solid fa-door-open" style="color: #0284c7;"></i> <?php esc_html_e( 'Hotel Guest Room Number', 'ozone-skypool' ); ?> *
                            </label>
                            <input type="text" name="room_no" id="ozHotelRoomNo" class="ifs-pms-mono" placeholder="e.g. Room 402">
                        </div>

                        <!-- Modular Multi-Package Switch Deck -->
                        <div class="oz-field-group">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                <label class="oz-field-label" style="margin: 0;">
                                    <i class="fa-solid fa-layer-group" style="color: #0284c7;"></i> <?php esc_html_e( 'Admission Packages & Tier Controls', 'ozone-skypool' ); ?> *
                                </label>
                                <span style="font-size: 11.5px; color: #64748b; font-weight: 600;">
                                    <?php esc_html_e( 'Toggle switch to enable each package tier', 'ozone-skypool' ); ?>
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

                                            <!-- Card Header: Title & Master Switch -->
                                            <div class="oz-tier-box-head">
                                                <div>
                                                    <div class="oz-tier-title"><?php echo esc_html( $tier['name'] ); ?></div>
                                                    <span class="oz-tier-badge">👤 <?php echo esc_html( $tier['age_group'] ?? 'General' ); ?></span>
                                                </div>
                                                <label class="oz-switch" title="<?php esc_attr_e( 'Enable / Disable Tier', 'ozone-skypool' ); ?>">
                                                    <input type="checkbox" class="oz-tier-toggle-input" id="ozTierToggle_<?php echo $index; ?>" <?php checked( $is_default_on ); ?> onchange="ozToggleTierSwitch(<?php echo $index; ?>)">
                                                    <span class="oz-slider"></span>
                                                </label>
                                            </div>

                                            <!-- Card Metrics: Rate, Qty & Dedicated Duration -->
                                            <div class="oz-tier-controls-row">
                                                <div class="oz-metric-pill">
                                                    <span class="oz-metric-label"><?php esc_html_e( 'Base Rate', 'ozone-skypool' ); ?></span>
                                                    <strong class="ifs-pms-mono" style="color: #0284c7; font-size: 13.5px;">
                                                        <?php echo esc_html( $currency . ' ' . number_format( (float) $tier['price'], 2 ) ); ?>
                                                        <small style="font-weight: 500; color: #64748b;">/hr</small>
                                                    </strong>
                                                </div>

                                                <div class="oz-metric-pill">
                                                    <span class="oz-metric-label"><?php esc_html_e( 'Headcount', 'ozone-skypool' ); ?></span>
                                                    <div class="oz-mini-qty">
                                                        <button type="button" class="oz-mini-btn" onclick="ozDeltaModularQty(<?php echo $index; ?>, -1)"><i class="fa-solid fa-minus"></i></button>
                                                        <input type="number" id="ozTierPersons_<?php echo $index; ?>" class="oz-mini-input ifs-pms-mono" value="<?php echo $is_default_on ? 1 : 0; ?>" min="0" max="50" readonly>
                                                        <button type="button" class="oz-mini-btn" onclick="ozDeltaModularQty(<?php echo $index; ?>, 1)"><i class="fa-solid fa-plus"></i></button>
                                                    </div>
                                                </div>

                                                <div class="oz-metric-pill">
                                                    <span class="oz-metric-label"><?php esc_html_e( 'Duration (Hrs)', 'ozone-skypool' ); ?></span>
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
                                <label class="oz-field-label"><?php esc_html_e( 'Amenity Add-Ons & Rentals', 'ozone-skypool' ); ?></label>
                                <div class="oz-addon-grid">
                                    <?php foreach ( $amenity_addons as $addon ) : ?>
                                        <div class="oz-addon-item" onclick="ozToggleAddon(this, <?php echo esc_attr( $addon['price'] ); ?>, '<?php echo esc_attr( $addon['name'] ); ?>')">
                                            <div>
                                                <div style="font-size: 13px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $addon['name'] ); ?></div>
                                                <div style="font-size: 11.5px; color: #0284c7; font-family: var(--ifs-font-mono); font-weight: 700; margin-top: 2px;">+<?php echo esc_html( number_format( (float) $addon['price'], 2 ) . ' ' . $currency ); ?></div>
                                            </div>
                                            <i class="fa-regular fa-square" style="font-size: 18px; color: #94a3b8;"></i>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Tender Methods & Calculations -->
                        <div class="oz-field-group" style="border-top: 1.5px solid #f1f5f9; padding-top: 20px;">
                            <label class="oz-field-label"><?php esc_html_e( 'Payment Tender Method', 'ozone-skypool' ); ?></label>
                            <div class="oz-tender-grid">
                                <div class="oz-tender-box active" onclick="ozSelectTender('Cash', this)">
                                    <i class="fa-solid fa-money-bill-wave" style="font-size: 17px;"></i> <?php esc_html_e( 'Cash', 'ozone-skypool' ); ?>
                                </div>
                                <div class="oz-tender-box" onclick="ozSelectTender('Complementary', this)">
                                    <i class="fa-solid fa-handshake" style="font-size: 17px;"></i> <?php esc_html_e( 'Complementary', 'ozone-skypool' ); ?>
                                </div>
                                <div class="oz-tender-box" onclick="ozSelectTender('bKash / Nagad', this)">
                                    <i class="fa-solid fa-mobile-screen-button" style="font-size: 17px;"></i> <?php esc_html_e( 'bKash / MFS', 'ozone-skypool' ); ?>
                                </div>
                                <div class="oz-tender-box" onclick="ozSelectTender('Card POS', this)">
                                    <i class="fa-solid fa-credit-card" style="font-size: 17px;"></i> <?php esc_html_e( 'POS Card', 'ozone-skypool' ); ?>
                                </div>
                            </div>

                            <!-- Cash Received Breakdown -->
                            <div class="oz-grid-2" style="margin-top: 18px;" id="ozCashWrap">
                                <div>
                                    <label class="oz-field-label" for="ozCashReceived"><?php printf( esc_html__( 'Cash Tendered (%s)', 'ozone-skypool' ), esc_html( $currency ) ); ?></label>
                                    <input type="number" step="0.01" id="ozCashReceived" class="ifs-pms-mono" placeholder="0.00">
                                    <div class="oz-quick-cash-row">
                                        <button type="button" class="oz-btn oz-btn-sm" onclick="ozQuickCash(500)">+500</button>
                                        <button type="button" class="oz-btn oz-btn-sm" onclick="ozQuickCash(1000)">+1000</button>
                                        <button type="button" class="oz-btn oz-btn-sm" onclick="ozQuickCash('exact')">Exact</button>
                                    </div>
                                </div>
                                <div>
                                    <label class="oz-field-label"><?php esc_html_e( 'Change Due Back', 'ozone-skypool' ); ?></label>
                                    <div id="ozChangeDue" class="ifs-pms-mono" style="background: #f8fafc; height: 48px; display: flex; align-items: center; padding: 0 16px; border-radius: 12px; font-weight: 800; color: #10b981; border: 1.5px solid #cbd5e1; font-size: 16px;">
                                        0.00 <?php echo $currency; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div style="display: flex; gap: 14px; margin-top: 10px;">
                            <button type="submit" class="oz-btn oz-btn-primary oz-btn-lg" style="flex: 2;">
                                <i class="fa-solid fa-print"></i> <?php esc_html_e( 'Confirm & Print Thermal Slip', 'ozone-skypool' ); ?>
                            </button>
                            <button type="button" class="oz-btn oz-btn-secondary oz-btn-lg" style="flex: 1;" onclick="ozResetTerminal()">
                                <?php esc_html_e( 'Reset (Alt+C)', 'ozone-skypool' ); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Right Terminal Live Slip Preview -->
            <div>
                <div class="oz-receipt-preview-card">
                    <!-- Venue Brand Header -->
                    <div style="text-align: center;">
                        <?php if ( ! empty( $logo_url ) ) : ?>
                            <div style="display: flex; justify-content: center; margin-bottom: 8px;">
                                <img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo" style="max-height: 40px; width: auto; border-radius: 6px;">
                            </div>
                        <?php endif; ?>
                        <strong style="font-size: 15px; letter-spacing: 0.5px; text-transform: uppercase; color: #0f172a;"><?php echo $b_name; ?></strong>
                        <div style="font-size: 11px; color: #64748b; margin-top: 3px; line-height: 1.4;">
                            <?php echo $address; ?><br>
                            <?php echo esc_html__( 'Tel:', 'ozone-skypool' ) . ' ' . $phone; ?>
                        </div>
                    </div>

                    <div class="oz-receipt-sep"></div>

                    <!-- Turnstile QR & Token -->
                    <div style="text-align: center; margin: 8px 0;">
                        <div id="ozReceiptQrWrap" style="display: flex; justify-content: center; margin-bottom: 8px;"></div>
                        <div style="font-size: 9.5px; font-weight: 800; letter-spacing: 1.2px; color: #64748b; text-transform: uppercase;">
                            <?php esc_html_e( 'Turnstile Access Token', 'ozone-skypool' ); ?>
                        </div>
                        <div id="ozPrevToken" class="ifs-pms-mono" style="font-weight: 800; font-size: 15px; letter-spacing: 1px; color: #0284c7; margin-top: 2px;">
                            <?php echo esc_html( $preview_code ); ?>
                        </div>
                    </div>

                    <div class="oz-receipt-sep"></div>

                    <!-- Section 1: Patron Identification -->
                    <div style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 6px;">
                        <?php esc_html_e( 'Patron Identification', 'ozone-skypool' ); ?>
                    </div>
                    <table class="oz-receipt-table">
                        <tr>
                            <td style="color: #64748b;"><?php esc_html_e( 'Patron Type', 'ozone-skypool' ); ?>:</td>
                            <td style="text-align: right; font-weight: 700; color: #0284c7;" id="ozPrevClassification"><?php esc_html_e( 'General Customer', 'ozone-skypool' ); ?></td>
                        </tr>
                        <tr>
                            <td style="color: #64748b;"><?php esc_html_e( 'Guest Name', 'ozone-skypool' ); ?>:</td>
                            <td style="text-align: right; font-weight: 700; color: #0f172a;" id="ozPrevName">Walk-in Guest</td>
                        </tr>
                        <tr>
                            <td style="color: #64748b;"><?php esc_html_e( 'Contact #', 'ozone-skypool' ); ?>:</td>
                            <td style="text-align: right; font-weight: 700; color: #0f172a;" id="ozPrevPhone">017XXXXXXXX</td>
                        </tr>
                        <tr id="ozPrevRoomRow" style="display: none;">
                            <td style="color: #64748b;"><?php esc_html_e( 'Hotel Room #', 'ozone-skypool' ); ?>:</td>
                            <td style="text-align: right; font-weight: 800; color: #0284c7;" id="ozPrevRoom">-</td>
                        </tr>
                    </table>

                    <div class="oz-receipt-sep"></div>

                    <!-- Section 2: Admission Breakdown (Clean Single-Line Items) -->
                    <div style="font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 6px;">
                        <?php esc_html_e( 'Admission Breakdown', 'ozone-skypool' ); ?>
                    </div>
                    <table class="oz-receipt-table">
                        <thead>
                            <tr style="border-bottom: 1.5px dashed #cbd5e1;">
                                <th style="text-align: left; color: #64748b; font-size: 10px; padding-bottom: 4px;"><?php esc_html_e( 'Package Item', 'ozone-skypool' ); ?></th>
                                <th style="text-align: right; color: #64748b; font-size: 10px; padding-bottom: 4px;"><?php esc_html_e( 'Qty', 'ozone-skypool' ); ?></th>
                                <th style="text-align: right; color: #64748b; font-size: 10px; padding-bottom: 4px;"><?php esc_html_e( 'Subtotal', 'ozone-skypool' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="ozPrevTiersBody"></tbody>
                        <tbody id="ozPrevAddonsBody"></tbody>
                    </table>

                    <div class="oz-receipt-sep"></div>

                    <!-- Section 3: Settlement Summary -->
                    <table class="oz-receipt-table" style="margin-bottom: 4px;">
                        <tr>
                            <td style="color: #64748b;"><?php esc_html_e( 'Payment Method', 'ozone-skypool' ); ?>:</td>
                            <td style="text-align: right; font-weight: 700; color: #0f172a;" id="ozPrevTender">Cash</td>
                        </tr>
                        <tr>
                            <td style="color: #64748b;"><?php esc_html_e( 'Issue Timestamp', 'ozone-skypool' ); ?>:</td>
                            <td style="text-align: right; color: #64748b;"><?php echo esc_html( current_time( 'M j, Y - H:i' ) ); ?></td>
                        </tr>
                        <tr>
                            <td style="color: #64748b;"><?php esc_html_e( 'Desk Cashier', 'ozone-skypool' ); ?>:</td>
                            <td style="text-align: right; color: #64748b;"><?php echo esc_html( $current_staff ); ?></td>
                        </tr>
                    </table>

                    <div class="oz-receipt-sep"></div>

                    <!-- Final Settlement -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 4px 0;">
                        <span style="font-weight: 800; font-size: 13px; color: #0f172a;"><?php esc_html_e( 'NET PAYABLE:', 'ozone-skypool' ); ?></span>
                        <span style="font-size: 20px; font-weight: 800; color: #0284c7;" id="ozPrevTotal">
                            <?php echo $currency . ' 500.00'; ?>
                        </span>
                    </div>

                    <!-- Structured Thermal Safety Disclaimers -->
                    <div class="oz-receipt-rules">
                        <div class="oz-rules-title"><?php esc_html_e( 'TURNSTILE PASS & POOL SAFETY RULES', 'ozone-skypool' ); ?></div>
                        <ul class="oz-rules-list">
                            <li><?php esc_html_e( 'Valid for single turnstile gate entry on date of issue only.', 'ozone-skypool' ); ?></li>
                            <li><?php esc_html_e( 'Proper synthetic swimwear compulsory; cotton wear strictly restricted.', 'ozone-skypool' ); ?></li>
                            <li><?php esc_html_e( 'Mandatory shower required before entering the pool water.', 'ozone-skypool' ); ?></li>
                            <li><?php esc_html_e( 'Outside food, glassware, and smoking are not permitted on the pool deck.', 'ozone-skypool' ); ?></li>
                            <li><?php esc_html_e( 'Children under 13 must be supervised by an adult at all times.', 'ozone-skypool' ); ?></li>
                            <li><?php esc_html_e( 'Management is not liable for personal belongings. Non-refundable.', 'ozone-skypool' ); ?></li>
                        </ul>
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
                    <?php esc_html_e( 'Gate Pass Master Ledger', 'ozone-skypool' ); ?>
                </h3>
                <span class="ifs-pms-badge" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-weight: 700; padding: 5px 12px; border-radius: 8px;">
                    <?php echo $total_tickets; ?> <?php esc_html_e( 'Total Records', 'ozone-skypool' ); ?>
                </span>
            </div>

            <div class="oz-search-bar">
                <div class="oz-search-box">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="ozTicketSearchInput" placeholder="<?php esc_attr_e( 'Search visible page tickets by code, name, or phone...', 'ozone-skypool' ); ?>" oninput="ozFilterTicketTable()" autocomplete="off">
                </div>

                <select id="ozTicketStatusFilter" onchange="ozFilterTicketTable()">
                    <option value="ALL"><?php esc_html_e( 'All Statuses', 'ozone-skypool' ); ?></option>
                    <option value="Valid"><?php esc_html_e( 'Valid (Unused)', 'ozone-skypool' ); ?></option>
                    <option value="Used"><?php esc_html_e( 'Used (Admitted)', 'ozone-skypool' ); ?></option>
                    <option value="Cancelled"><?php esc_html_e( 'Cancelled / Void', 'ozone-skypool' ); ?></option>
                </select>
            </div>

            <div class="oz-table-wrap">
                <table class="oz-table" id="ozTicketsMasterTable">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Pass Code', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Guest Name', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Phone', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Amount', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Sold By', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Sold Date', 'ozone-skypool' ); ?></th>
                            <th style="text-align: right;"><?php esc_html_e( 'Actions', 'ozone-skypool' ); ?></th>
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
                                        <?php echo esc_html( ! empty( $tkt->customer_name ) ? $tkt->customer_name : __( 'Walk-in Guest', 'ozone-skypool' ) ); ?>
                                        <?php if ( ! empty( $tkt->room_no ) ) : ?>
                                            <span style="display: block; font-size: 11px; color: #0284c7; font-weight: 700;"><i class="fa-solid fa-door-open"></i> <?php echo esc_html( $tkt->room_no ); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="ifs-pms-mono" style="color: #475569;">
                                        <?php echo esc_html( $tkt->customer_phone ); ?>
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
                                        <button type="button" class="oz-btn oz-btn-sm oz-btn-view" onclick='ifs_pms_show_receipt(<?php echo wp_json_encode( array(
                                            'code'   => $tkt->ticket_code,
                                            'name'   => ! empty( $tkt->customer_name ) ? $tkt->customer_name : __( 'Walk-in Guest', 'ozone-skypool' ),
                                            'phone'  => $tkt->customer_phone,
                                            'amount' => number_format( (float) $tkt->amount, 2 ),
                                            'staff'  => $tkt->sold_by,
                                            'date'   => $tkt->sold_at,
                                            'room'   => $tkt->room_no ?? '',
                                        ) ); ?>)'>
                                            <i class="fa-solid fa-eye"></i> <?php esc_html_e( 'View', 'ozone-skypool' ); ?>
                                        </button>

                                        <button type="button" class="oz-btn oz-btn-sm oz-btn-edit" onclick='ozOpenEditModal(<?php echo wp_json_encode( array(
                                            'id'     => $tkt->id,
                                            'code'   => $tkt->ticket_code,
                                            'name'   => ! empty( $tkt->customer_name ) ? $tkt->customer_name : '',
                                            'phone'  => $tkt->customer_phone,
                                            'amount' => $tkt->amount,
                                            'status' => $tkt->status,
                                        ) ); ?>)'>
                                            <i class="fa-solid fa-pen-to-square"></i> <?php esc_html_e( 'Edit', 'ozone-skypool' ); ?>
                                        </button>

                                        <?php if ( $is_admin ) : ?>
                                            <form method="POST" action="<?php echo esc_url( $base_url . '&view=tickets' ); ?>" style="display: inline-block; margin: 0;" onsubmit="return confirm(<?php echo wp_json_encode( __( 'Delete this ticket permanently from the registry?', 'ozone-skypool' ) ); ?>);">
                                                <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                                                <input type="hidden" name="ifs_pms_action" value="delete_ticket">
                                                <input type="hidden" name="ticket_id" value="<?php echo esc_attr( $tkt->id ); ?>">
                                                <button type="submit" class="oz-btn oz-btn-sm oz-btn-delete" title="<?php esc_attr_e( 'Delete Permanently', 'ozone-skypool' ); ?>">
                                                    <i class="fa-solid fa-trash-can"></i> <?php esc_html_e( 'Delete', 'ozone-skypool' ); ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 48px; color: #94a3b8;">
                                    <i class="fa-solid fa-ticket" style="font-size: 32px; margin-bottom: 10px; opacity: 0.3; display: block;"></i>
                                    <?php esc_html_e( 'No tickets found in database.', 'ozone-skypool' ); ?>
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
                        <?php printf( esc_html__( 'Showing %1$d–%2$d of %3$d records', 'ozone-skypool' ), $offset + 1, min( $offset + $per_page, $total_tickets ), $total_tickets ); ?>
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
                <?php esc_html_e( 'Edit Ticket Record', 'ozone-skypool' ); ?> (<span id="ozModalTicketCode" class="ifs-pms-mono"></span>)
            </h3>
            <button type="button" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #64748b;" onclick="ozCloseEditModal()">&times;</button>
        </div>

        <form method="POST" action="<?php echo esc_url( $base_url . '&view=tickets' ); ?>" id="ozEditTicketForm">
            <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
            <input type="hidden" name="ifs_pms_action" value="edit_ticket">
            <input type="hidden" name="ticket_id" id="ozModalTicketId" value="">

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div class="oz-field-group">
                    <label class="oz-field-label"><?php esc_html_e( 'Guest Name', 'ozone-skypool' ); ?> *</label>
                    <input type="text" name="name" id="ozModalName" required>
                </div>
                <div class="oz-field-group">
                    <label class="oz-field-label"><?php esc_html_e( 'Contact Phone', 'ozone-skypool' ); ?> *</label>
                    <input type="tel" name="phone" id="ozModalPhone" required>
                </div>
                <div class="oz-grid-2">
                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php printf( esc_html__( 'Amount (%s)', 'ozone-skypool' ), esc_html( $currency ) ); ?> *</label>
                        <input type="number" step="0.01" name="amount" id="ozModalAmount" class="ifs-pms-mono" required>
                    </div>
                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php esc_html_e( 'Status', 'ozone-skypool' ); ?> *</label>
                        <select name="status" id="ozModalStatus" style="height: 48px; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 8px 14px; font-weight: 700;">
                            <option value="Valid"><?php esc_html_e( 'Valid (Unused)', 'ozone-skypool' ); ?></option>
                            <option value="Used"><?php esc_html_e( 'Used (Admitted)', 'ozone-skypool' ); ?></option>
                            <option value="Cancelled"><?php esc_html_e( 'Cancelled / Void', 'ozone-skypool' ); ?></option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 14px;">
                    <button type="submit" class="oz-btn oz-btn-primary" style="flex: 2; height: 46px; border-radius: 12px;">
                        <i class="fa-solid fa-floppy-disk"></i> <?php esc_html_e( 'Update Ticket', 'ozone-skypool' ); ?>
                    </button>
                    <button type="button" class="oz-btn oz-btn-secondary" style="flex: 1; height: 46px; border-radius: 12px;" onclick="ozCloseEditModal()">
                        <?php esc_html_e( 'Cancel', 'ozone-skypool' ); ?>
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

        if (currentGuestType === 'room_guest') {
            pillWalkin.classList.remove('active');
            pillRoom.classList.add('active');

            roomWrap.style.display = 'flex';
            roomInput.setAttribute('required', 'required');

            // Automatically switch Payment Tender to Complementary
            window.ozSelectTenderByName('Complementary');
            if (cashWrap) cashWrap.style.display = 'none';
        } else {
            pillRoom.classList.remove('active');
            pillWalkin.classList.add('active');

            roomWrap.style.display = 'none';
            roomInput.removeAttribute('required');
            roomInput.value = '';

            // Revert back to Cash
            window.ozSelectTenderByName('Cash');
            if (cashWrap) cashWrap.style.display = 'grid';
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

        // Ensure at least 1 tier line if all toggled off accidentally
        if (selectedSummary.length === 0 && boxes.length > 0) {
            const firstToggle = document.getElementById('ozTierToggle_0');
            const firstQty    = document.getElementById('ozTierPersons_0');
            const firstBox    = document.getElementById('ozTierBox_0');
            if (firstToggle && firstQty && firstBox) {
                firstToggle.checked = true;
                firstBox.classList.add('is-enabled');
                firstQty.value = 1;

                const rate  = parseFloat(firstBox.getAttribute('data-price')) || 0;
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

        const packageInput = document.getElementById('ozPackageNameInput');
        if (packageInput) {
            packageInput.value = selectedSummary.map(s => s.name + ' (' + s.persons + 'p x ' + s.hours + 'h)').join(', ');
        }

        const guestName  = document.getElementById('ozGuestName').value.trim();
        const guestPhone = document.getElementById('ozGuestPhone').value.trim();
        const roomVal    = document.getElementById('ozHotelRoomNo').value.trim();

        // Patron identification preview
        document.getElementById('ozPrevClassification').textContent = (currentGuestType === 'room_guest') ? 'Hotel Room Guest' : 'General Customer';
        document.getElementById('ozPrevName').textContent           = guestName || 'Walk-in Guest';
        document.getElementById('ozPrevPhone').textContent          = guestPhone || '017XXXXXXXX';

        // Admission itemized breakdown: Each item description on an independent line
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

        // Final Payable Banner
        document.getElementById('ozPrevTotal').textContent = currencySym + ' ' + finalPayable.toFixed(2);

        // Room Row
        const roomRow = document.getElementById('ozPrevRoomRow');
        const roomTxt = document.getElementById('ozPrevRoom');
        if (currentGuestType === 'room_guest' && roomVal) {
            roomRow.style.display = 'table-row';
            roomTxt.textContent   = roomVal;
        } else {
            roomRow.style.display = 'none';
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

        if (method === 'Complementary') {
            if (cashWrap) cashWrap.style.display = 'none';
        } else {
            if (cashWrap) cashWrap.style.display = (method === 'Cash') ? 'grid' : 'none';
            if (roomWrap) roomWrap.style.display = (currentGuestType === 'room_guest') ? 'flex' : 'none';
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
        window.ozComputeChange();
    };

    window.ozComputeChange = function() {
        const received = parseFloat(document.getElementById('ozCashReceived').value) || 0;
        const changeEl = document.getElementById('ozChangeDue');
        const diff     = received - finalPayable;

        if (diff >= 0 && received > 0) {
            changeEl.textContent = diff.toFixed(2) + ' ' + currencySym;
            changeEl.style.color = '#10b981';
        } else {
            changeEl.textContent = '0.00 ' + currencySym;
            changeEl.style.color = '#475569';
        }
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
                if (form && form.checkValidity()) {
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