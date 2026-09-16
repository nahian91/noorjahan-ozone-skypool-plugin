<?php
/**
 * View: Patron Directory & Complete Ticket Intelligence Registry (Inline Executive Edition)
 *
 * @package Ozone_Skypool_OS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$t_cust   = $wpdb->prefix . 'ifs_pms_customers';
$t_tick   = $wpdb->prefix . 'ifs_pms_tickets';
$currency = esc_html( get_option( 'ifs_pms_currency', 'BDT' ) );
$base_url = admin_url( 'admin.php?page=ifs-pms&view=customers' );
$is_admin = current_user_can( 'manage_options' ) || current_user_can( 'ozone_manage_settings' );

// Handle Profile View / Edit Routing States
$action_mode = sanitize_key( $_GET['mode'] ?? 'list' );
$target_id   = absint( $_GET['customer_id'] ?? 0 );

// Pagination Setup for Master List
$per_page = 15;
$paged    = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$offset   = ( $paged - 1 ) * $per_page;

$total_customers = $wpdb->get_var( "SELECT COUNT(id) FROM {$t_cust}" );
$total_pages     = ceil( $total_customers / $per_page );

// Aggregate Customers with Ticket Stats
$customers = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT c.*, 
                COUNT(t.id) AS total_visits, 
                COALESCE(SUM(t.amount), 0.00) AS lifetime_spend,
                MAX(t.sold_at) AS last_visit
         FROM {$t_cust} c
         LEFT JOIN {$t_tick} t ON c.id = t.customer_id AND t.status != 'Cancelled'
         GROUP BY c.id
         ORDER BY c.id DESC
         LIMIT %d OFFSET %d",
        $per_page,
        $offset
    )
);

// If viewing a specific customer intelligence profile
$single_customer = null;
$customer_tickets = array();
if ( $action_mode === 'view' && $target_id > 0 ) {
    $single_customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t_cust} WHERE id = %d", $target_id ) );
    if ( $single_customer ) {
        $customer_tickets = $wpdb->get_results( $wpdb->prepare( 
            "SELECT * FROM {$t_tick} WHERE customer_id = %d ORDER BY id DESC", 
            $target_id 
        ) );
    }
}

// If editing a customer inline
$edit_customer = null;
if ( $action_mode === 'edit' && $target_id > 0 ) {
    $edit_customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t_cust} WHERE id = %d", $target_id ) );
}
?>

<style>
    .oz-pos-card {
        background: var(--ifs-surface, #ffffff);
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 24px;
        box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.07);
        overflow: hidden;
    }
    .oz-pos-head {
        display: flex; justify-content: space-between; align-items: center;
        padding: 24px 32px; border-bottom: 1px solid #f1f5f9;
        background: linear-gradient(to bottom, #fafbfd, #f8fafc);
    }
    .oz-pos-title {
        margin: 0; font-size: 17px; font-weight: 800;
        display: flex; align-items: center; gap: 12px; color: var(--ifs-text-primary, #0f172a);
    }
    .oz-search-bar { display: flex; gap: 16px; padding: 22px 32px; border-bottom: 1px solid #f1f5f9; align-items: center; background: #ffffff; }
    .oz-search-box { position: relative; flex: 1; min-width: 280px; }
    .oz-search-box i.search-icon { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px; pointer-events: none; }
    #wpcontent .oz-search-box input { padding-left: 44px !important; height: 46px !important; border-radius: 14px !important; width: 100% !important; border: 1.5px solid #cbd5e1 !important; }
    
    .oz-table-wrap { width: 100%; overflow-x: auto; }
    .oz-table { width: 100%; border-collapse: collapse; font-size: 14px; text-align: left; }
    .oz-table th { background: #f8fafc; color: #64748b; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; }
    .oz-table td { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; color: #0f172a; vertical-align: middle; }
    .oz-table tr:hover td { background: #f8fafc; }

    .oz-btn-sm {
        height: 36px !important; padding: 0 14px !important; font-size: 12.5px !important; border-radius: 10px !important;
        font-weight: 700 !important; display: inline-flex !important; align-items: center !important; gap: 6px !important; cursor: pointer; border: none;
        text-decoration: none !important; transition: all 0.2s ease;
    }
    .oz-btn-view { background: rgba(16, 185, 129, 0.08); color: #059669; border: 1.5px solid rgba(16, 185, 129, 0.25); }
    .oz-btn-view:hover { background: #10b981; color: #fff; }
    .oz-btn-edit { background: rgba(2, 132, 199, 0.08); color: #0284c7; border: 1.5px solid rgba(2, 132, 199, 0.25); }
    .oz-btn-edit:hover { background: #0284c7; color: #fff; }
    .oz-btn-delete { background: rgba(244, 63, 94, 0.08); color: #f43f5e; border: 1.5px solid rgba(244, 63, 94, 0.25); }
    .oz-btn-delete:hover { background: #f43f5e; color: #fff; }

    .oz-pagination-footer { display: flex; justify-content: space-between; align-items: center; padding: 20px 32px; background: #f8fafc; border-top: 1px solid #e2e8f0; flex-wrap: wrap; gap: 14px; }
    .oz-page-num { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 10px; border-radius: 10px; font-size: 13px; font-weight: 700; text-decoration: none !important; background: #ffffff; color: #475569; border: 1.5px solid #cbd5e1; }
    .oz-page-num.active { background: #0284c7 !important; color: #ffffff !important; border-color: #0284c7 !important; }

    .oz-inline-form-card { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 20px; padding: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.04); max-width: 600px; margin: 0 auto; }
    .oz-field-group { display: flex; flex-direction: column; width: 100%; margin-bottom: 20px; }
    .oz-field-label { font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 8px; }
    #wpcontent .oz-inline-form-card input { height: 50px !important; border-radius: 14px !important; border: 1.5px solid #cbd5e1 !important; padding: 0 18px !important; width: 100% !important; font-size: 15px !important; }

    /* Profile Intelligence Cards */
    .oz-profile-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-bottom: 24px; }
    @media (max-width: 800px) { .oz-profile-grid { grid-template-columns: 1fr; } }
    .oz-profile-stat { background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 20px; text-align: center; }
</style>

<?php if ( $action_mode === 'view' && $single_customer ) : ?>
    <!-- ========================================================================== -->
    <!-- VIEW MODE: PATRON PROFILE & TICKET INTELLIGENCE MATRIX -->
    <!-- ========================================================================== -->
    <div class="oz-pos-card">
        <div class="oz-pos-head">
            <h3 class="oz-pos-title">
                <i class="fa-solid fa-address-card" style="color: var(--ifs-accent, #0284c7);"></i>
                <?php printf( esc_html__( 'Patron Profile Intelligence: %s', 'ozone-skypool' ), esc_html( $single_customer->name ) ); ?>
            </h3>
            <a href="<?php echo esc_url( $base_url ); ?>" class="oz-btn-sm oz-btn-edit" style="background: #f1f5f9; color: #475569; border-color: #cbd5e1;">
                <i class="fa-solid fa-arrow-left"></i> <?php esc_html_e( 'Back to Directory', 'ozone-skypool' ); ?>
            </a>
        </div>

        <div style="padding: 32px;">
            <!-- Profile Stat Cards -->
            <div class="oz-profile-grid">
                <div class="oz-profile-stat">
                    <div style="font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em;"><?php esc_html_e( 'Contact Phone', 'ozone-skypool' ); ?></div>
                    <div class="ifs-pms-mono" style="font-size: 18px; font-weight: 800; color: #0f172a; margin-top: 6px;"><?php echo esc_html( $single_customer->phone ); ?></div>
                </div>
                <div class="oz-profile-stat">
                    <div style="font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em;"><?php esc_html_e( 'Account Registered', 'ozone-skypool' ); ?></div>
                    <div class="ifs-pms-mono" style="font-size: 16px; font-weight: 800; color: #0f172a; margin-top: 6px;"><?php echo esc_html( $single_customer->created_at ); ?></div>
                </div>
                <div class="oz-profile-stat">
                    <div style="font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em;"><?php esc_html_e( 'Total Admissions', 'ozone-skypool' ); ?></div>
                    <div class="ifs-pms-mono" style="font-size: 18px; font-weight: 800; color: #0284c7; margin-top: 6px;"><?php echo count( $customer_tickets ); ?> <?php esc_html_e( 'Passes Issued', 'ozone-skypool' ); ?></div>
                </div>
            </div>

            <h4 style="font-size: 15px; font-weight: 800; color: #0f172a; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-ticket" style="color: #0284c7;"></i> <?php esc_html_e( 'Associated Ticket & Pass History', 'ozone-skypool' ); ?>
            </h4>

            <div class="oz-table-wrap" style="border: 1px solid #e2e8f0; border-radius: 16px;">
                <table class="oz-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Pass Code', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Amount Paid', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Sold By', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Issue Timestamp', 'ozone-skypool' ); ?></th>
                            <th style="text-align: right;"><?php esc_html_e( 'Gate Verification', 'ozone-skypool' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $customer_tickets ) ) : ?>
                            <?php foreach ( $customer_tickets as $tkt ) : 
                                $is_valid = ( $tkt->status === 'Valid' );
                                $status_class = $is_valid ? 'ifs-pms-badge-success' : ( $tkt->status === 'Used' ? 'ifs-pms-badge-warning' : 'ifs-pms-badge-danger' );
                            ?>
                                <tr>
                                    <td class="ifs-pms-mono" style="font-weight: 800; color: #0284c7;">
                                        <?php echo esc_html( $tkt->ticket_code ); ?>
                                    </td>
                                    <td class="ifs-pms-mono" style="font-weight: 800;">
                                        <?php echo esc_html( $currency . ' ' . number_format( (float) $tkt->amount, 2 ) ); ?>
                                    </td>
                                    <td>
                                        <span class="ifs-pms-badge <?php echo esc_attr( $status_class ); ?>">
                                            <?php echo esc_html( $tkt->status ); ?>
                                        </span>
                                    </td>
                                    <td style="color: #475569; font-size: 13px;">
                                        <?php echo esc_html( $tkt->sold_by ); ?>
                                    </td>
                                    <td class="ifs-pms-mono" style="font-size: 12.5px; color: #64748b;">
                                        <?php echo esc_html( $tkt->sold_at ); ?>
                                    </td>
                                    <td class="ifs-pms-mono" style="text-align: right; font-size: 12.5px; color: #475569;">
                                        <?php echo esc_html( $tkt->scanned_at ? $tkt->scanned_at : __( 'Pending Scan', 'ozone-skypool' ) ); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">
                                    <?php esc_html_e( 'No tickets found for this patron.', 'ozone-skypool' ); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ( $action_mode === 'edit' && $edit_customer ) : ?>
    <!-- ========================================================================== -->
    <!-- EDIT MODE: INLINE EDIT FORM -->
    <!-- ========================================================================== -->
    <div class="oz-pos-card">
        <div class="oz-pos-head">
            <h3 class="oz-pos-title">
                <i class="fa-solid fa-user-pen" style="color: var(--ifs-accent, #0284c7);"></i>
                <?php esc_html_e( 'Edit Patron Profile Details', 'ozone-skypool' ); ?>
            </h3>
            <a href="<?php echo esc_url( $base_url ); ?>" class="oz-btn-sm oz-btn-edit" style="background: #f1f5f9; color: #475569; border-color: #cbd5e1;">
                <i class="fa-solid fa-arrow-left"></i> <?php esc_html_e( 'Cancel', 'ozone-skypool' ); ?>
            </a>
        </div>

        <div style="padding: 40px;">
            <div class="oz-inline-form-card">
                <form method="POST" action="<?php echo esc_url( $base_url ); ?>">
                    <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                    <input type="hidden" name="ifs_pms_action" value="edit_customer">
                    <input type="hidden" name="customer_id" value="<?php echo esc_attr( $edit_customer->id ); ?>">

                    <div class="oz-field-group">
                        <label class="oz-field-label"><?php esc_html_e( 'Patron Full Name', 'ozone-skypool' ); ?> *</label>
                        <input type="text" name="name" value="<?php echo esc_attr( $edit_customer->name ); ?>" required>
                    </div>

                    <div class="oz-field-group" style="margin-bottom: 28px;">
                        <label class="oz-field-label"><?php esc_html_e( 'Mobile Contact / Phone', 'ozone-skypool' ); ?> *</label>
                        <input type="tel" name="phone" value="<?php echo esc_attr( $edit_customer->phone ); ?>" required>
                    </div>

                    <div style="display: flex; gap: 14px;">
                        <button type="submit" class="oz-btn oz-btn-primary" style="flex: 2; height: 50px; border-radius: 14px; font-weight: 800; font-size: 15px;">
                            <i class="fa-solid fa-floppy-disk"></i> <?php esc_html_e( 'Update Patron Profile', 'ozone-skypool' ); ?>
                        </button>
                        <a href="<?php echo esc_url( $base_url ); ?>" class="oz-btn oz-btn-secondary" style="flex: 1; height: 50px; border-radius: 14px; text-decoration: none; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                            <?php esc_html_e( 'Cancel', 'ozone-skypool' ); ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php else : ?>
    <!-- ========================================================================== -->
    <!-- LIST MODE: MASTER CUSTOMER DIRECTORY TABLE -->
    <!-- ========================================================================== -->
    <div class="oz-pos-card">
        <div class="oz-pos-head">
            <h3 class="oz-pos-title">
                <i class="fa-solid fa-users" style="color: var(--ifs-accent, #0284c7);"></i>
                <?php esc_html_e( 'Patron Directory & Ticket Intelligence', 'ozone-skypool' ); ?>
            </h3>
            <span class="ifs-pms-badge" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-weight: 700; padding: 6px 12px; border-radius: 8px;">
                <?php echo esc_html( $total_customers ); ?> <?php esc_html_e( 'Registered Patrons', 'ozone-skypool' ); ?>
            </span>
        </div>

        <div class="oz-search-bar">
            <div class="oz-search-box">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="ozCustomerSearchInput" placeholder="<?php esc_attr_e( 'Search patrons by name or phone...', 'ozone-skypool' ); ?>" oninput="ozFilterCustomerTable()" autocomplete="off">
            </div>
        </div>

        <div class="oz-table-wrap">
            <table class="oz-table" id="ozCustomersTable">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ID', 'ozone-skypool' ); ?></th>
                        <th><?php esc_html_e( 'Patron Name', 'ozone-skypool' ); ?></th>
                        <th><?php esc_html_e( 'Phone Contact', 'ozone-skypool' ); ?></th>
                        <th><?php esc_html_e( 'Total Visits', 'ozone-skypool' ); ?></th>
                        <th><?php esc_html_e( 'Lifetime Spend', 'ozone-skypool' ); ?></th>
                        <th><?php esc_html_e( 'Last Activity', 'ozone-skypool' ); ?></th>
                        <th style="text-align: right;"><?php esc_html_e( 'Actions', 'ozone-skypool' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $customers ) ) : ?>
                        <?php foreach ( $customers as $cust ) : ?>
                            <tr class="oz-customer-row">
                                <td class="ifs-pms-mono" style="font-weight: 700; color: #64748b;">
                                    #<?php echo esc_html( $cust->id ); ?>
                                </td>
                                <td style="font-weight: 800; color: #0f172a;">
                                    <?php echo esc_html( $cust->name ); ?>
                                </td>
                                <td class="ifs-pms-mono" style="color: #475569;">
                                    <?php echo esc_html( $cust->phone ); ?>
                                </td>
                                <td>
                                    <span class="ifs-pms-badge" style="background: rgba(2, 132, 199, 0.08); color: #0284c7; font-weight: 800;">
                                        <?php echo esc_html( $cust->total_visits ); ?> <?php esc_html_e( 'Passes', 'ozone-skypool' ); ?>
                                    </span>
                                </td>
                                <td class="ifs-pms-mono" style="font-weight: 800; color: #10b981;">
                                    <?php echo esc_html( $currency . ' ' . number_format( (float) $cust->lifetime_spend, 2 ) ); ?>
                                </td>
                                <td class="ifs-pms-mono" style="font-size: 12.5px; color: #64748b;">
                                    <?php echo esc_html( $cust->last_visit ? $cust->last_visit : __( 'Never', 'ozone-skypool' ) ); ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <!-- View Intelligence Matrix Button -->
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=customers&mode=view&customer_id=' . $cust->id ) ); ?>" class="oz-btn-sm oz-btn-view">
                                        <i class="fa-solid fa-eye"></i> <?php esc_html_e( 'View', 'ozone-skypool' ); ?>
                                    </a>

                                    <!-- Edit Inline Button -->
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=customers&mode=edit&customer_id=' . $cust->id ) ); ?>" class="oz-btn-sm oz-btn-edit">
                                        <i class="fa-solid fa-pen-to-square"></i> <?php esc_html_e( 'Edit', 'ozone-skypool' ); ?>
                                    </a>

                                    <?php if ( $is_admin ) : ?>
                                        <form method="POST" action="<?php echo esc_url( $base_url ); ?>" style="display: inline-block; margin: 0;" onsubmit="return confirm(<?php echo wp_json_encode( __( 'Delete this customer profile permanently?', 'ozone-skypool' ) ); ?>);">
                                            <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                                            <input type="hidden" name="ifs_pms_action" value="delete_customer">
                                            <input type="hidden" name="customer_id" value="<?php echo esc_attr( $cust->id ); ?>">
                                            <button type="submit" class="oz-btn-sm oz-btn-delete" title="<?php esc_attr_e( 'Delete Profile', 'ozone-skypool' ); ?>">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 56px; color: #64748b;">
                                <i class="fa-solid fa-users-slash" style="font-size: 36px; margin-bottom: 14px; opacity: 0.3; display: block;"></i>
                                <?php esc_html_e( 'No customer profiles found in database.', 'ozone-skypool' ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <?php if ( $total_pages > 1 ) : ?>
            <div class="oz-pagination-footer">
                <div style="font-size: 13px; color: #475569; font-weight: 600;">
                    <?php 
                    $start = $offset + 1;
                    $end   = min( $offset + $per_page, $total_customers );
                    printf( esc_html__( 'Showing %1$d–%2$d of %3$d patrons', 'ozone-skypool' ), $start, $end, $total_customers ); 
                    ?>
                </div>
                <div style="display: flex; gap: 6px;">
                    <?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=customers&paged=' . $i ) ); ?>" 
                           class="oz-page-num <?php echo ( $i == $paged ) ? 'active' : ''; ?>">
                            <?php echo esc_html( $i ); ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
function ozFilterCustomerTable() {
    const query = document.getElementById('ozCustomerSearchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.oz-customer-row');

    rows.forEach(r => {
        const text = r.textContent.toLowerCase();
        r.style.display = (!query || text.includes(query)) ? '' : 'none';
    });
}
</script>