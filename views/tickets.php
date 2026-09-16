<?php
/**
 * View: High-Performance Front Desk POS & Master Ticket Registry (Executive UX Edition v6)
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

// Active Tab Router (Preserve tab state in pagination links)
$active_tab = isset( $_GET['tab'] ) && $_GET['tab'] === 'list' ? 'list' : 'add';

// Retrieve Dynamic Settings & States
$pricing_tiers    = get_option( 'ifs_pms_pricing_tiers', array(
    array( 'name' => 'Standard Adult Swim Pass', 'age_group' => 'Adult (13+ yrs)', 'price' => 500.00, 'features' => '2-Hour Pool Access, Locker, Towel Service' ),
    array( 'name' => 'Kids Splash Pass', 'age_group' => 'Child (4-12 yrs)', 'price' => 300.00, 'features' => 'Pool Access, Kid Vest, Toy Float' ),
) );

$amenity_addons   = get_option( 'ifs_pms_amenity_addons', array(
    array( 'name' => 'Fresh Towel', 'price' => 50.00 ),
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
$per_page    = 10; // Number of tickets per page
$paged       = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$offset      = ( $paged - 1 ) * $per_page;

// Get Total Count for Pagination Calculation
$total_tickets = $wpdb->get_var( "SELECT COUNT(t.id) FROM {$t_tick} t" );
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
?>

<style>
    /* ==========================================================================
       EXECUTIVE UI/UX DESIGN SYSTEM (V6 ULTRA-PREMIUM TICKETS POS)
       ========================================================================== */
    .oz-subnav-bar {
        display: flex;
        width: 100%;
        gap: 10px;
        background: linear-gradient(135deg, rgba(241, 245, 249, 0.8) 0%, rgba(226, 232, 240, 0.6) 100%);
        backdrop-filter: blur(12px);
        padding: 8px;
        border-radius: 16px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        margin-bottom: 28px;
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

    .oz-pos-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.45fr) minmax(380px, 420px);
        gap: 32px;
        align-items: start;
        box-sizing: border-box;
        width: 100%;
    }

    @media (max-width: 1200px) {
        .oz-pos-layout { grid-template-columns: 1fr; }
    }

    .oz-pos-card {
        background: var(--ifs-surface, #ffffff);
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 24px;
        box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.07);
        overflow: hidden;
        transition: box-shadow 0.3s ease;
    }

    .oz-pos-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 24px 32px;
        border-bottom: 1px solid var(--ifs-border-subtle, #f1f5f9);
        background: linear-gradient(to bottom, #fafbfd, #f8fafc);
    }

    .oz-pos-title {
        margin: 0;
        font-size: 17px;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--ifs-text-primary, #0f172a);
        letter-spacing: -0.01em;
    }

    .oz-pos-form-wrap {
        padding: 32px;
        display: flex;
        flex-direction: column;
        gap: 26px;
    }

    .oz-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
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
        font-size: 13px;
        font-weight: 700;
        color: var(--ifs-text-secondary, #475569);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
        letter-spacing: 0.01em;
    }

    #wpcontent .oz-pos-layout input[type="text"],
    #wpcontent .oz-pos-layout input[type="number"],
    #wpcontent .oz-pos-layout input[type="tel"] {
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
        box-shadow: 0 2px 4px rgba(0,0,0,0.01);
    }

    #wpcontent .oz-pos-layout input:focus {
        border-color: var(--ifs-border-focus, #0284c7) !important;
        box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.12), 0 4px 12px rgba(2, 132, 199, 0.08) !important;
        outline: none !important;
    }

    /* Modern Package Cards */
    .oz-package-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 16px;
        margin-top: 4px;
    }

    .oz-package-card {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 2px solid #e2e8f0;
        border-radius: 18px;
        padding: 20px;
        cursor: pointer;
        user-select: none;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }

    .oz-package-card::after {
        content: '';
        position: absolute;
        top: 0; right: 0; width: 60px; height: 60px;
        background: radial-gradient(circle, rgba(2,132,199,0.08) 0%, transparent 70%);
        opacity: 0;
        transition: opacity 0.25s ease;
    }

    .oz-package-card:hover {
        border-color: #94a3b8;
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
    }
    .oz-package-card:hover::after { opacity: 1; }

    .oz-package-card.selected {
        background: linear-gradient(135deg, rgba(2, 132, 199, 0.04) 0%, rgba(2, 132, 199, 0.08) 100%);
        border-color: var(--ifs-accent, #0284c7);
        box-shadow: 0 12px 32px rgba(2, 132, 199, 0.18);
    }

    .oz-pkg-title { font-size: 14px; font-weight: 800; color: var(--ifs-text-primary, #0f172a); }
    .oz-pkg-price { font-size: 19px; font-weight: 800; font-family: var(--ifs-font-mono, monospace); color: var(--ifs-accent, #0284c7); margin: 8px 0 4px 0; }
    .oz-pkg-features { font-size: 12px; color: var(--ifs-text-tertiary, #64748b); line-height: 1.45; }

    /* Quantity Control */
    .oz-qty-control {
        display: flex; align-items: center; background: #f8fafc;
        border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 5px; height: 50px; box-sizing: border-box;
    }
    .oz-qty-control .oz-btn-icon {
        width: 38px; height: 38px; border-radius: 10px; background: #ffffff;
        color: var(--ifs-text-primary, #0f172a); border: 1px solid #e2e8f0;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04); display: flex; align-items: center; justify-content: center; cursor: pointer; padding: 0; outline: none; transition: all 0.15s ease;
    }
    .oz-qty-control .oz-btn-icon:hover { background: #f1f5f9; color: var(--ifs-accent); }
    .oz-qty-control .oz-btn-icon:active { transform: scale(0.92); }
    .oz-qty-input {
        width: 100% !important; height: 100% !important; text-align: center; border: none !important;
        background: transparent !important; font-weight: 800 !important; font-family: var(--ifs-font-mono) !important; font-size: 17px !important; padding: 0 !important; color: var(--ifs-text-primary);
    }

    /* Amenity Grid */
    .oz-addon-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
    @media (max-width: 600px) { .oz-addon-grid { grid-template-columns: 1fr; } }

    .oz-addon-item {
        background: #f8fafc; border: 1.5px solid #e2e8f0;
        border-radius: 16px; padding: 16px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; user-select: none; transition: all 0.2s ease;
    }
    .oz-addon-item:hover { border-color: #cbd5e1; background: #f1f5f9; }
    .oz-addon-item.selected { background: rgba(2, 132, 199, 0.05); border-color: var(--ifs-accent, #0284c7); box-shadow: 0 4px 12px rgba(2, 132, 199, 0.08); }
    .oz-addon-item.selected i { color: var(--ifs-accent, #0284c7); }

    /* Tender Grid */
    .oz-tender-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    @media (max-width: 600px) { .oz-tender-grid { grid-template-columns: repeat(2, 1fr); } }

    .oz-tender-box {
        background: #f8fafc; border: 2px solid #e2e8f0;
        border-radius: 16px; padding: 16px 12px; cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 10px;
        font-size: 13px; font-weight: 700; color: var(--ifs-text-secondary, #475569); transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .oz-tender-box:hover { border-color: #cbd5e1; transform: translateY(-2px); background: #f1f5f9; }
    .oz-tender-box.active {
        background: linear-gradient(135deg, rgba(2, 132, 199, 0.06) 0%, rgba(2, 132, 199, 0.12) 100%); 
        color: var(--ifs-accent, #0284c7); 
        border-color: var(--ifs-accent, #0284c7); 
        box-shadow: 0 6px 18px rgba(2, 132, 199, 0.16);
    }

    .oz-quick-cash-row { display: flex; gap: 8px; margin-top: 10px; }
    .oz-quick-cash-row .oz-btn-sm {
        height: 34px !important; padding: 0 16px !important; font-size: 12.5px !important; font-weight: 700 !important;
        font-family: var(--ifs-font-mono, monospace) !important; background: #f1f5f9 !important;
        border: 1.5px solid #e2e8f0 !important; color: var(--ifs-text-secondary, #475569) !important; border-radius: 10px !important; cursor: pointer; transition: all 0.15s ease;
    }
    .oz-quick-cash-row .oz-btn-sm:hover { border-color: var(--ifs-accent); color: var(--ifs-accent); background: #ffffff !important; }

    /* POS Action Buttons */
    #wpcontent .oz-pos-layout .oz-btn {
        position: relative;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 12px !important;
        font-family: var(--ifs-font-sans, inherit) !important;
        font-weight: 700 !important;
        cursor: pointer !important;
        box-sizing: border-box !important;
        outline: none !important;
        overflow: hidden;
        z-index: 1;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }

    #wpcontent .oz-pos-layout .oz-btn-primary.oz-btn-lg {
        height: 58px !important;
        padding: 0 34px !important;
        font-size: 16px !important;
        font-weight: 800 !important;
        border-radius: 18px !important;
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        box-shadow: 0 10px 25px -5px rgba(2, 132, 199, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.3) !important;
    }

    #wpcontent .oz-pos-layout .oz-btn-primary.oz-btn-lg:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 32px -6px rgba(2, 132, 199, 0.65) !important;
        background: linear-gradient(135deg, #0369a1 0%, #075985) !important;
    }

    #wpcontent .oz-pos-layout .oz-btn-secondary.oz-btn-lg {
        height: 58px !important;
        padding: 0 26px !important;
        font-size: 15.5px !important;
        font-weight: 700 !important;
        border-radius: 18px !important;
        background: #ffffff !important;
        color: var(--ifs-text-secondary, #475569) !important;
        border: 1.5px solid #cbd5e1 !important;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03) !important;
    }

    #wpcontent .oz-pos-layout .oz-btn-secondary.oz-btn-lg:hover {
        background: #f8fafc !important;
        border-color: #94a3b8 !important;
        color: #0f172a !important;
        transform: translateY(-2px);
    }

    /* Harmonious Data Table Action Buttons */
    .oz-table .oz-btn-sm {
        height: 36px !important;
        padding: 0 14px !important;
        font-size: 12.5px !important;
        border-radius: 10px !important;
        font-weight: 700 !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
        box-shadow: 0 2px 4px rgba(15, 23, 42, 0.03) !important;
    }

    .oz-table .oz-btn-sm:hover { transform: translateY(-2px); }

    .oz-table .oz-btn-view {
        background: rgba(16, 185, 129, 0.08) !important;
        color: #059669 !important;
        border: 1.5px solid rgba(16, 185, 129, 0.25) !important;
    }
    .oz-table .oz-btn-view:hover {
        background: #10b981 !important; color: #ffffff !important; border-color: #10b981 !important;
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35) !important;
    }

    .oz-table .oz-btn-edit {
        background: rgba(2, 132, 199, 0.08) !important;
        color: #0284c7 !important;
        border: 1.5px solid rgba(2, 132, 199, 0.25) !important;
    }
    .oz-table .oz-btn-edit:hover {
        background: #0284c7 !important; color: #ffffff !important; border-color: #0284c7 !important;
        box-shadow: 0 6px 16px rgba(2, 132, 199, 0.35) !important;
    }

    .oz-table .oz-btn-delete {
        background: rgba(244, 63, 94, 0.08) !important;
        color: #f43f5e !important;
        border: 1.5px solid rgba(244, 63, 94, 0.25) !important;
    }
    .oz-table .oz-btn-delete:hover {
        background: #f43f5e !important; color: #ffffff !important; border-color: #f43f5e !important;
        box-shadow: 0 6px 16px rgba(244, 63, 94, 0.35) !important;
    }

    /* Modern Pagination Footer Styling */
    .oz-pagination-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 32px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        flex-wrap: wrap;
        gap: 14px;
    }
    .oz-pagination-info {
        font-size: 13px;
        color: var(--ifs-text-secondary, #475569);
        font-weight: 600;
    }
    .oz-pagination-links {
        display: flex;
        gap: 6px;
        align-items: center;
    }
    .oz-page-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 36px;
        padding: 0 10px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none !important;
        background: #ffffff;
        color: #475569;
        border: 1.5px solid #cbd5e1;
        transition: all 0.2s ease;
    }
    .oz-page-num:hover {
        border-color: #0284c7;
        color: #0284c7;
        background: #f0f9ff;
    }
    .oz-page-num.active {
        background: #0284c7 !important;
        color: #ffffff !important;
        border-color: #0284c7 !important;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
    }
    .oz-page-num.disabled {
        opacity: 0.4;
        pointer-events: none;
        background: #f1f5f9;
    }

    /* Executive Glassmorphic Receipt Slip Preview */
    .oz-receipt-preview-card {
        background: linear-gradient(180deg, #ffffff 0%, #fcfdfe 100%); 
        color: #0f172a; border-radius: 24px; padding: 32px; font-family: var(--ifs-font-mono, monospace);
        box-shadow: 0 24px 50px -12px rgba(15, 23, 42, 0.12); border: 1.5px solid #cbd5e1; position: sticky; top: 24px;
    }
    .oz-receipt-sep { border-top: 1.5px dashed #cbd5e1; margin: 18px 0; }
    .oz-receipt-table { width: 100%; border-collapse: collapse; font-size: 12.5px; line-height: 1.85; }

    /* DataTable Layout enhancements */
    .oz-search-bar { display: flex; gap: 16px; padding: 22px 32px; border-bottom: 1px solid var(--ifs-border-subtle, #f1f5f9); align-items: center; background: #ffffff; }
    .oz-search-box { position: relative; flex: 1; min-width: 280px; }
    .oz-search-box i.search-icon { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: var(--ifs-text-tertiary, #94a3b8); font-size: 14px; pointer-events: none; }
    #wpcontent .oz-search-box input { padding-left: 44px !important; height: 46px !important; border-radius: 14px !important; }
    #wpcontent .oz-search-bar select { min-width: 190px !important; height: 46px !important; border-radius: 14px !important; padding: 8px 18px !important; font-weight: 700 !important; }

    .oz-table-wrap { width: 100%; overflow-x: auto; }
    .oz-table { width: 100%; border-collapse: collapse; font-size: 14px; text-align: left; }
    .oz-table th { background: #f8fafc; color: var(--ifs-text-tertiary, #64748b); font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; }
    .oz-table td { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; color: var(--ifs-text-primary, #0f172a); vertical-align: middle; }
    .oz-table tr:hover td { background: #f8fafc; }

    .oz-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); z-index: 99999; align-items: center; justify-content: center; }
    .oz-modal-card { background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 24px; width: 540px; max-width: 95vw; padding: 36px; box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3); box-sizing: border-box; animation: ozModalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    
    @keyframes ozModalPop {
        from { opacity: 0; transform: scale(0.95) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
</style>

<!-- Sub Navigation Tab Bar -->
<div class="oz-subnav-bar" role="tablist">
    <button type="button" class="oz-subnav-btn <?php echo ( $active_tab === 'add' ) ? 'active' : ''; ?>" id="ozTabBtnAdd" onclick="ozSwitchTicketTab('add', this)">
        <i class="fa-solid fa-plus-circle" style="font-size: 15px;"></i> <?php esc_html_e( 'Add Ticket', 'ozone-skypool' ); ?>
    </button>
    <button type="button" class="oz-subnav-btn <?php echo ( $active_tab === 'list' ) ? 'active' : ''; ?>" id="ozTabBtnList" onclick="ozSwitchTicketTab('list', this)">
        <i class="fa-solid fa-table-list" style="font-size: 15px;"></i> <?php esc_html_e( 'All Tickets', 'ozone-skypool' ); ?>
    </button>
</div>

<!-- TAB 1: Add Ticket POS -->
<div id="ozTicketPaneAdd" class="oz-tab-pane <?php echo ( $active_tab === 'add' ) ? 'active' : ''; ?>">
    
    <!-- Pool Status & Schedule Warnings -->
    <?php if ( $pool_status !== 'open' || ! $is_today_open || ! $is_within_hours ) : ?>
        <div style="background: rgba(244, 63, 94, 0.08); border: 1.5px solid rgba(244, 63, 94, 0.3); color: #f43f5e; padding: 18px 24px; border-radius: 18px; margin-bottom: 28px; font-weight: 700; font-size: 14.5px; display: flex; align-items: center; gap: 16px; box-shadow: 0 6px 20px rgba(244, 63, 94, 0.06);">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 22px;"></i>
            <div>
                <?php if ( $pool_status === 'closed' ) : ?>
                    <?php esc_html_e( 'Warning: The swimming pool is currently CLOSED by management. Gate sales are suspended.', 'ozone-skypool' ); ?>
                <?php elseif ( $pool_status === 'maintenance' ) : ?>
                    <?php esc_html_e( 'Notice: Pool is under MAINTENANCE mode. Exercise caution during guest onboarding.', 'ozone-skypool' ); ?>
                <?php else : ?>
                    <?php printf( esc_html__( 'Notice: Pool is outside operating hours for today (%s: %s to %s).', 'ozone-skypool' ), ucfirst( $current_day_key ), $today_schedule['open'], $today_schedule['close'] ); ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="oz-pos-layout">
        <!-- High-Velocity Checkout Console -->
        <div class="oz-pos-card">
            <div class="oz-pos-head">
                <h3 class="oz-pos-title">
                    <i class="fa-solid fa-cash-register" style="color: var(--ifs-accent, #0284c7); font-size: 19px;"></i>
                    <?php esc_html_e( 'Rooftop Skypool Gate POS Terminal', 'ozone-skypool' ); ?>
                </h3>
                <span style="font-size: 12.5px; color: var(--ifs-text-tertiary, #64748b);">
                    <?php esc_html_e( 'Quick Print:', 'ozone-skypool' ); ?> 
                    <kbd style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 4px 8px; border-radius: 6px; font-weight: 800; color: #0284c7; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">Ctrl + Enter</kbd>
                </span>
            </div>

            <form method="POST" action="<?php echo esc_url( $base_url . '&view=tickets' ); ?>" id="ozPosMasterForm">
                <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                <input type="hidden" name="ifs_pms_action" value="issue_ticket">
                <input type="hidden" name="package_name" id="ozPackageNameInput" value="<?php echo esc_attr( $pricing_tiers[0]['name'] ?? 'Standard Adult Swim Pass' ); ?>">
                <input type="hidden" name="amount" id="ozSubmittedAmount" value="<?php echo esc_attr( $pricing_tiers[0]['price'] ?? '500.00' ); ?>">
                <input type="hidden" name="payment_method" id="ozSelectedPayment" value="Cash">

                <div class="oz-pos-form-wrap">
                    <!-- Customer Identification -->
                    <div class="oz-grid-2">
                        <div class="oz-field-group">
                            <label class="oz-field-label" for="ozGuestName"><?php esc_html_e( 'Patron / Group Lead Name', 'ozone-skypool' ); ?> *</label>
                            <input type="text" name="name" id="ozGuestName" required placeholder="<?php esc_attr_e( 'e.g. Tanvir Ahmed', 'ozone-skypool' ); ?>" autocomplete="off">
                        </div>
                        <div class="oz-field-group">
                            <label class="oz-field-label" for="ozGuestPhone"><?php esc_html_e( 'Mobile Contact (SMS e-Pass)', 'ozone-skypool' ); ?> *</label>
                            <input type="tel" name="phone" id="ozGuestPhone" required placeholder="017XXXXXXXX" pattern="[0-9+\s\-]{7,20}" autocomplete="off">
                        </div>
                    </div>

                    <!-- Dynamic Pricing Tiers Grid -->
                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php esc_html_e( 'Select Admission Package Tier', 'ozone-skypool' ); ?></label>
                        <div class="oz-package-grid">
                            <?php if ( ! empty( $pricing_tiers ) ) : ?>
                                <?php foreach ( $pricing_tiers as $index => $tier ) : ?>
                                    <div class="oz-package-card <?php echo $index === 0 ? 'selected' : ''; ?>" 
                                         data-name="<?php echo esc_attr( $tier['name'] . ' (' . ( $tier['age_group'] ?? 'Adult' ) . ')' ); ?>" 
                                         data-price="<?php echo esc_attr( $tier['price'] ); ?>"
                                         onclick="ozSelectPackage(this)">
                                        <div>
                                            <div class="oz-pkg-title"><?php echo esc_html( $tier['name'] ); ?></div>
                                            <div style="font-size: 12px; font-weight: 700; color: var(--ifs-accent, #0284c7); margin-top: 4px;">👤 <?php echo esc_html( $tier['age_group'] ?? 'Adult' ); ?></div>
                                            <div class="oz-pkg-price"><?php echo esc_html( $currency . ' ' . number_format( (float) $tier['price'], 2 ) ); ?></div>
                                        </div>
                                        <div class="oz-pkg-features" style="margin-top: 12px;"><?php echo esc_html( $tier['features'] ); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quantities & Locker Assignment Hook -->
                    <div class="oz-grid-2">
                        <div class="oz-field-group">
                            <label class="oz-field-label"><?php esc_html_e( 'Pass Count / Headcount', 'ozone-skypool' ); ?></label>
                            <div class="oz-qty-control">
                                <button type="button" class="oz-btn-icon" onclick="ozDeltaQty(-1)">
                                    <i class="fa-solid fa-minus"></i>
                                </button>
                                <input type="number" id="ozPassQuantity" class="oz-qty-input" value="1" min="1" max="50" readonly>
                                <button type="button" class="oz-btn-icon" onclick="ozDeltaQty(1)">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>
                        </div>

                        <div class="oz-field-group">
                            <label class="oz-field-label" for="ozLockerNumber">
                                <i class="fa-solid fa-lock" style="color: var(--ifs-accent);"></i> <?php esc_html_e( 'Assign Locker Key #', 'ozone-skypool' ); ?>
                            </label>
                            <input type="text" name="locker_no" id="ozLockerNumber" class="ifs-pms-mono" placeholder="Optional (e.g. L-102)">
                        </div>
                    </div>

                    <!-- Amenity Add-Ons -->
                    <?php if ( $enable_amenities === '1' && ! empty( $amenity_addons ) ) : ?>
                        <div class="oz-field-group">
                            <label class="oz-field-label"><?php esc_html_e( 'Amenity Add-Ons & Rentals', 'ozone-skypool' ); ?></label>
                            <div class="oz-addon-grid">
                                <?php foreach ( $amenity_addons as $addon ) : ?>
                                    <div class="oz-addon-item" onclick="ozToggleAddon(this, <?php echo esc_attr( $addon['price'] ); ?>, '<?php echo esc_attr( $addon['name'] ); ?>')">
                                        <div>
                                            <div style="font-size: 13.5px; font-weight: 700; color: var(--ifs-text-primary);"><?php echo esc_html( $addon['name'] ); ?></div>
                                            <div style="font-size: 12px; color: var(--ifs-accent); font-family: var(--ifs-font-mono); font-weight: 700; margin-top: 3px;">+<?php echo esc_html( number_format( (float) $addon['price'], 2 ) . ' ' . $currency ); ?></div>
                                        </div>
                                        <i class="fa-regular fa-square" style="font-size: 20px; color: #94a3b8;"></i>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tender Selection & Cash Change Engine -->
                    <div class="oz-field-group" style="border-top: 1.5px solid #f1f5f9; padding-top: 24px;">
                        <label class="oz-field-label"><?php esc_html_e( 'Payment Tender Method', 'ozone-skypool' ); ?></label>
                        <div class="oz-tender-grid">
                            <div class="oz-tender-box active" onclick="ozSelectTender('Cash', this)">
                                <i class="fa-solid fa-money-bill-wave" style="font-size: 18px;"></i> <?php esc_html_e( 'Cash', 'ozone-skypool' ); ?>
                            </div>
                            <div class="oz-tender-box" onclick="ozSelectTender('bKash / Nagad', this)">
                                <i class="fa-solid fa-mobile-screen-button" style="font-size: 18px;"></i> <?php esc_html_e( 'MFS / bKash', 'ozone-skypool' ); ?>
                            </div>
                            <div class="oz-tender-box" onclick="ozSelectTender('Card POS', this)">
                                <i class="fa-solid fa-credit-card" style="font-size: 18px;"></i> <?php esc_html_e( 'POS Card', 'ozone-skypool' ); ?>
                            </div>
                            <div class="oz-tender-box" onclick="ozSelectTender('Complimentary', this)">
                                <i class="fa-solid fa-handshake" style="font-size: 18px;"></i> <?php esc_html_e( 'VIP Pass', 'ozone-skypool' ); ?>
                            </div>
                        </div>

                        <div class="oz-grid-2" style="margin-top: 20px;" id="ozCashWrap">
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
                                <div id="ozChangeDue" class="ifs-pms-mono" style="background: #f8fafc; height: 50px; display: flex; align-items: center; padding: 0 18px; border-radius: 14px; font-weight: 800; color: var(--ifs-success, #10b981); border: 1.5px solid #e2e8f0; font-size: 17px;">
                                    0.00 <?php echo $currency; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Final Action Controls -->
                    <div style="display: flex; gap: 16px; margin-top: 14px;">
                        <button type="submit" class="oz-btn oz-btn-primary oz-btn-lg" style="flex: 2;" <?php echo ( $pool_status === 'closed' ) ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''; ?>>
                            <i class="fa-solid fa-print"></i> <?php esc_html_e( 'Confirm & Print Thermal Slip', 'ozone-skypool' ); ?>
                        </button>
                        <button type="button" class="oz-btn oz-btn-secondary oz-btn-lg" style="flex: 1;" onclick="ozResetTerminal()">
                            <?php esc_html_e( 'Clear (Alt+C)', 'ozone-skypool' ); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- RIGHT: Real-Time Slip Ticket Preview with Venue Name & Logo -->
        <div>
            <div class="oz-receipt-preview-card">
                <div style="text-align: center;">
                    <?php if ( ! empty( $logo_url ) ) : ?>
                        <div style="display: flex; justify-content: center; margin-bottom: 12px;">
                            <img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo" style="max-height: 52px; width: auto; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
                        </div>
                    <?php endif; ?>
                    <strong style="font-size: 17px; text-transform: uppercase; color: #0f172a; letter-spacing: 0.5px;"><?php echo $b_name; ?></strong>
                    <div style="font-size: 11.5px; color: #64748b; margin-top: 6px; line-height: 1.55;">
                        <?php echo $address; ?><br>
                        <?php echo esc_html__( 'Tel:', 'ozone-skypool' ) . ' ' . $phone; ?>
                    </div>
                </div>

                <div class="oz-receipt-sep"></div>

                <div style="text-align: center; margin: 14px 0;">
                    <div id="ozReceiptQrWrap" style="display: flex; justify-content: center; margin-bottom: 10px;"></div>
                    <div style="font-size: 10.5px; font-weight: 800; color: #64748b; letter-spacing: 1.2px;"><?php esc_html_e( 'TURNSTILE ACCESS QR TOKEN', 'ozone-skypool' ); ?></div>
                    <div style="font-weight: 800; font-size: 17px; letter-spacing: 1.5px; color: #0284c7; margin-top: 4px;" id="ozPrevToken">OZONE-TKT-DEMO</div>
                </div>

                <div class="oz-receipt-sep"></div>

                <table class="oz-receipt-table">
                    <tr>
                        <td style="color: #64748b;"><?php esc_html_e( 'Guest:', 'ozone-skypool' ); ?></td>
                        <td style="text-align: right; font-weight: 700; color: #0f172a;" id="ozPrevName">Walk-in Guest</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;"><?php esc_html_e( 'Phone:', 'ozone-skypool' ); ?></td>
                        <td style="text-align: right; font-weight: 700; color: #0f172a;" id="ozPrevPhone">017XXXXXXXX</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;"><?php esc_html_e( 'Package:', 'ozone-skypool' ); ?></td>
                        <td style="text-align: right; font-weight: 700; color: #0f172a;" id="ozPrevPkg">Standard Adult Swim Pass</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;"><?php esc_html_e( 'Admissions:', 'ozone-skypool' ); ?></td>
                        <td style="text-align: right; font-weight: 700; color: #0f172a;" id="ozPrevQty">1 Pass</td>
                    </tr>
                    <tr id="ozPrevLockerRow" style="display: none;">
                        <td style="color: #64748b;"><?php esc_html_e( 'Assigned Locker:', 'ozone-skypool' ); ?></td>
                        <td style="text-align: right; font-weight: 700; color: #0284c7;" id="ozPrevLocker">-</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;"><?php esc_html_e( 'Tender:', 'ozone-skypool' ); ?></td>
                        <td style="text-align: right; font-weight: 700; color: #0f172a;" id="ozPrevTender">Cash</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;"><?php esc_html_e( 'Operator:', 'ozone-skypool' ); ?></td>
                        <td style="text-align: right; color: #0f172a;"><?php echo esc_html( $current_staff ); ?></td>
                    </tr>
                    <tr>
                        <td style="color: #64748b;"><?php esc_html_e( 'Timestamp:', 'ozone-skypool' ); ?></td>
                        <td style="text-align: right; color: #0f172a;"><?php echo esc_html( current_time( 'M j, Y - H:i' ) ); ?></td>
                    </tr>
                </table>

                <div class="oz-receipt-sep"></div>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 800; font-size: 13.5px; color: #0f172a;"><?php esc_html_e( 'TOTAL PAYABLE:', 'ozone-skypool' ); ?></span>
                    <span style="font-size: 22px; font-weight: 800; color: #0284c7;" id="ozPrevTotal"><?php echo $currency . ' ' . number_format( (float)( $pricing_tiers[0]['price'] ?? 500.00 ), 2 ); ?></span>
                </div>

                <div style="text-align: center; font-size: 10.5px; color: #94a3b8; margin-top: 18px; line-height: 1.45;">
                    <?php esc_html_e( 'Swimwear compulsory • Valid for single turnstile gate pass today', 'ozone-skypool' ); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TAB 2: All Tickets Master Registry with Native Pagination -->
<div id="ozTicketPaneList" class="oz-tab-pane <?php echo ( $active_tab === 'list' ) ? 'active' : ''; ?>">
    <div class="oz-pos-card">
        <div class="oz-pos-head">
            <h3 class="oz-pos-title">
                <i class="fa-solid fa-list-check" style="color: var(--ifs-accent, #0284c7); font-size: 19px;"></i>
                <?php esc_html_e( 'Gate Pass Master Ledger', 'ozone-skypool' ); ?>
            </h3>
            <span class="ifs-pms-badge" style="background: #f1f5f9; color: var(--ifs-text-secondary); border: 1px solid #cbd5e1; font-weight: 700; padding: 6px 12px; border-radius: 8px;">
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
                                <td class="ifs-pms-mono" style="font-weight: 800; color: var(--ifs-accent, #0284c7);">
                                    <?php echo esc_html( $tkt->ticket_code ); ?>
                                </td>
                                <td style="font-weight: 700;">
                                    <?php echo esc_html( ! empty( $tkt->customer_name ) ? $tkt->customer_name : __( 'Walk-in Guest', 'ozone-skypool' ) ); ?>
                                </td>
                                <td class="ifs-pms-mono" style="color: var(--ifs-text-secondary, #475569);">
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
                                <td style="font-size: 13px; color: var(--ifs-text-tertiary, #64748b);">
                                    <?php echo esc_html( $tkt->sold_by ); ?>
                                </td>
                                <td class="ifs-pms-mono" style="font-size: 12.5px; color: var(--ifs-text-secondary, #475569);">
                                    <?php echo esc_html( $tkt->sold_at ); ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <!-- View Button -->
                                    <button type="button" class="oz-btn oz-btn-sm oz-btn-view" onclick='ifs_pms_show_receipt(<?php echo wp_json_encode( array(
                                        'code'   => $tkt->ticket_code,
                                        'name'   => ! empty( $tkt->customer_name ) ? $tkt->customer_name : __( 'Walk-in Guest', 'ozone-skypool' ),
                                        'phone'  => $tkt->customer_phone,
                                        'amount' => number_format( (float) $tkt->amount, 2 ),
                                        'staff'  => $tkt->sold_by,
                                        'date'   => $tkt->sold_at,
                                    ) ); ?>)'>
                                        <i class="fa-solid fa-eye"></i> <?php esc_html_e( 'View', 'ozone-skypool' ); ?>
                                    </button>

                                    <!-- Edit Button -->
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
                                        <!-- Delete Button -->
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
                            <td colspan="8" style="text-align: center; padding: 56px; color: var(--ifs-text-tertiary, #64748b);">
                                <i class="fa-solid fa-ticket" style="font-size: 36px; margin-bottom: 14px; opacity: 0.3; display: block;"></i>
                                <?php esc_html_e( 'No tickets found in database.', 'ozone-skypool' ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls Footer -->
        <?php if ( $total_pages > 1 ) : ?>
            <div class="oz-pagination-footer">
                <div class="oz-pagination-info">
                    <?php 
                    $start_record = $offset + 1;
                    $end_record   = min( $offset + $per_page, $total_tickets );
                    printf( 
                        esc_html__( 'Showing %1$d–%2$d of %3$d records', 'ozone-skypool' ), 
                        $start_record, 
                        $end_record, 
                        $total_tickets 
                    ); 
                    ?>
                </div>

                <div class="oz-pagination-links">
                    <!-- Previous Page -->
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=tickets&tab=list&paged=' . max( 1, $paged - 1 ) ) ); ?>" 
                       class="oz-page-num <?php echo ($paged <= 1) ? 'disabled' : ''; ?>">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>

                    <?php 
                    // Render page number buttons
                    for ( $i = 1; $i <= $total_pages; $i++ ) :
                        if ( $i == 1 || $i == $total_pages || ( $i >= $paged - 2 && $i <= $paged + 2 ) ) :
                            $active_class = ( $i == $paged ) ? 'active' : '';
                            ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=tickets&tab=list&paged=' . $i ) ); ?>" 
                               class="oz-page-num <?php echo esc_attr( $active_class ); ?>">
                                <?php echo esc_html( $i ); ?>
                            </a>
                            <?php
                        elseif ( $i == $paged - 3 || $i == $paged + 3 ) :
                            echo '<span style="padding: 0 4px; color: #94a3b8; font-weight: 700;">…</span>';
                        endif;
                    endfor;
                    ?>

                    <!-- Next Page -->
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=tickets&tab=list&paged=' . min( $total_pages, $paged + 1 ) ) ); ?>" 
                       class="oz-page-num <?php echo ($paged >= $total_pages) ? 'disabled' : ''; ?>">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL: Edit Ticket Form -->
<div id="ozEditTicketModal" class="oz-modal-overlay">
    <div class="oz-modal-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-size: 17px; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-pen-to-square" style="color: var(--ifs-accent, #0284c7);"></i>
                <?php esc_html_e( 'Edit Ticket Record', 'ozone-skypool' ); ?> (<span id="ozModalTicketCode" class="ifs-pms-mono"></span>)
            </h3>
            <button type="button" style="background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 18px; cursor: pointer; color: var(--ifs-text-secondary); display: flex; align-items: center; justify-content: center;" onclick="ozCloseEditModal()">&times;</button>
        </div>

        <form method="POST" action="<?php echo esc_url( $base_url . '&view=tickets' ); ?>" id="ozEditTicketForm">
            <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
            <input type="hidden" name="ifs_pms_action" value="edit_ticket">
            <input type="hidden" name="ticket_id" id="ozModalTicketId" value="">

            <div style="display: flex; flex-direction: column; gap: 20px;">
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
                        <select name="status" id="ozModalStatus" style="height: 50px; border-radius: 14px; border: 1.5px solid #cbd5e1; padding: 8px 16px; font-weight: 700; background: #ffffff;">
                            <option value="Valid"><?php esc_html_e( 'Valid (Unused)', 'ozone-skypool' ); ?></option>
                            <option value="Used"><?php esc_html_e( 'Used (Admitted)', 'ozone-skypool' ); ?></option>
                            <option value="Cancelled"><?php esc_html_e( 'Cancelled / Void', 'ozone-skypool' ); ?></option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 14px; margin-top: 18px;">
                    <button type="submit" class="oz-btn oz-btn-primary" style="flex: 2; height: 50px; border-radius: 14px; font-size: 14.5px;">
                        <i class="fa-solid fa-floppy-disk"></i> <?php esc_html_e( 'Update Ticket', 'ozone-skypool' ); ?>
                    </button>
                    <button type="button" class="oz-btn oz-btn-secondary" style="flex: 1; height: 50px; border-radius: 14px; font-size: 14.5px;" onclick="ozCloseEditModal()">
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
    let selectedPackagePrice = <?php echo (float)( $pricing_tiers[0]['price'] ?? 500.00 ); ?>;
    let selectedPackageName  = <?php echo wp_json_encode( ( $pricing_tiers[0]['name'] ?? 'Standard Adult Swim Pass' ) . ' (' . ( $pricing_tiers[0]['age_group'] ?? 'Adult' ) . ')' ); ?>;
    let addonsTotal          = 0;
    let finalPayable         = 0;

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
            width: 100,
            height: 100,
            colorDark: '#0f172a',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M
        });
    }

    window.ozSelectPackage = function(el) {
        document.querySelectorAll('.oz-package-card').forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');

        selectedPackagePrice = parseFloat(el.getAttribute('data-price')) || 0;
        selectedPackageName  = el.getAttribute('data-name');

        const pkgInput = document.getElementById('ozPackageNameInput');
        if (pkgInput) pkgInput.value = selectedPackageName;

        window.ozRecalculate();
    };

    window.ozRecalculate = function() {
        const qty = parseInt(document.getElementById('ozPassQuantity').value, 10) || 1;
        finalPayable = (selectedPackagePrice * qty) + addonsTotal;

        const amountField = document.getElementById('ozSubmittedAmount');
        if (amountField) amountField.value = finalPayable.toFixed(2);

        const guestName  = document.getElementById('ozGuestName').value.trim();
        const guestPhone = document.getElementById('ozGuestPhone').value.trim();
        const lockerVal  = document.getElementById('ozLockerNumber').value.trim();

        document.getElementById('ozPrevName').textContent  = guestName ? guestName : 'Walk-in Guest';
        document.getElementById('ozPrevPhone').textContent = guestPhone ? guestPhone : '017XXXXXXXX';
        document.getElementById('ozPrevPkg').textContent   = selectedPackageName;
        document.getElementById('ozPrevQty').textContent   = qty + ' ' + (qty > 1 ? 'Passes' : 'Pass');
        document.getElementById('ozPrevTotal').textContent = currencySym + ' ' + finalPayable.toFixed(2);

        const lockerRow = document.getElementById('ozPrevLockerRow');
        const lockerTxt = document.getElementById('ozPrevLocker');
        if (lockerVal) {
            lockerRow.style.display = 'table-row';
            lockerTxt.textContent   = lockerVal;
        } else {
            lockerRow.style.display = 'none';
        }

        window.ozComputeChange();
    };

    window.ozToggleAddon = function(card, price, name) {
        const isSelected = card.classList.toggle('selected');
        const icon = card.querySelector('i');

        if (isSelected) {
            icon.className = 'fa-solid fa-square-check';
            addonsTotal += price;
        } else {
            icon.className = 'fa-regular fa-square';
            addonsTotal -= price;
        }
        window.ozRecalculate();
    };

    window.ozSelectTender = function(method, el) {
        document.querySelectorAll('.oz-tender-box').forEach(b => b.classList.remove('active'));
        el.classList.add('active');

        document.getElementById('ozSelectedPayment').value = method;
        document.getElementById('ozPrevTender').textContent = method;

        const cashWrap = document.getElementById('ozCashWrap');
        if (cashWrap) {
            cashWrap.style.display = (method === 'Cash') ? 'grid' : 'none';
        }
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
            changeEl.style.color = 'var(--ifs-success, #10b981)';
        } else {
            changeEl.textContent = '0.00 ' + currencySym;
            changeEl.style.color = 'var(--ifs-text-secondary, #475569)';
        }
    };

    window.ozDeltaQty = function(delta) {
        const input = document.getElementById('ozPassQuantity');
        if (!input) return;

        let val = parseInt(input.value, 10) || 1;
        val = Math.max(1, Math.min(50, val + delta));
        input.value = val;
        window.ozRecalculate();
    };

    window.ozResetTerminal = function() {
        document.getElementById('ozPosMasterForm').reset();
        addonsTotal = 0;
        document.querySelectorAll('.oz-addon-item').forEach(c => {
            c.classList.remove('selected');
            c.querySelector('i').className = 'fa-regular fa-square';
        });

        const firstCard = document.querySelector('.oz-package-card');
        if (firstCard) {
            window.ozSelectPackage(firstCard);
        }
        document.getElementById('ozPassQuantity').value = 1;
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
        ['ozGuestName', 'ozGuestPhone', 'ozLockerNumber'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', window.ozRecalculate);
        });

        const cashInput = document.getElementById('ozCashReceived');
        if (cashInput) cashInput.addEventListener('input', window.ozComputeChange);

        renderQr('OZONE-TKT-DEMO');
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