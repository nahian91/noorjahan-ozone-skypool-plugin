<?php
/**
 * View: Patron Directory & Complete Ticket Intelligence Registry (Zero Inline CSS - Enhanced Executive Details Edition)
 *
 * @package Ozone_Skypool_OS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$t_cust   = $wpdb->prefix . 'ifs_pms_customers';
$t_tick   = $wpdb->prefix . 'ifs_pms_tickets';
$currency = esc_html( (string) get_option( 'ifs_pms_currency', 'BDT' ) );
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

// Aggregate Customers with Ticket Stats & Additional Intelligence Metrics
$customers = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT c.*, 
                COUNT(t.id) AS total_visits, 
                COALESCE(SUM(t.amount), 0.00) AS lifetime_spend,
                MAX(t.sold_at) AS last_visit,
                MIN(t.sold_at) AS first_visit,
                AVG(t.amount) AS avg_ticket_spend
         FROM {$t_cust} c
         LEFT JOIN {$t_tick} t ON c.id = t.customer_id AND t.status != 'Cancelled'
         GROUP BY c.id
         ORDER BY c.id DESC
         LIMIT %d OFFSET %d",
        $per_page,
        $offset
    )
);

// If viewing a specific customer intelligence profile with extended details
$single_customer  = null;
$customer_tickets = array();
$customer_metrics = null;
if ( $action_mode === 'view' && $target_id > 0 ) {
    $single_customer = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t_cust} WHERE id = %d", $target_id ) );
    if ( $single_customer ) {
        $customer_tickets = $wpdb->get_results( $wpdb->prepare( 
            "SELECT * FROM {$t_tick} WHERE customer_id = %d ORDER BY id DESC", 
            $target_id 
        ) );

        $customer_metrics = $wpdb->get_row( $wpdb->prepare(
            "SELECT 
                COUNT(id) AS total_passes,
                COALESCE(SUM(amount), 0.00) AS total_spend,
                COALESCE(AVG(amount), 0.00) AS avg_spend,
                SUM(CASE WHEN payment_method = 'Cash' THEN amount ELSE 0 END) AS cash_spend,
                SUM(CASE WHEN payment_method = 'Card POS' THEN amount ELSE 0 END) AS card_spend,
                SUM(CASE WHEN payment_method = 'bKash / Nagad' THEN amount ELSE 0 END) AS mfs_spend,
                SUM(CASE WHEN status = 'Used' THEN 1 ELSE 0 END) AS redeemed_count,
                MAX(sold_at) AS recent_pass_date
             FROM {$t_tick} 
             WHERE customer_id = %d AND status != 'Cancelled'",
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

<div class="ifs-pms-customer-wrapper">
<?php if ( $action_mode === 'view' && $single_customer ) : ?>
    <!-- ========================================================================== -->
    <!-- VIEW MODE: DETAILED PATRON INTELLIGENCE & ANALYTICS MATRIX -->
    <!-- ========================================================================== -->
    <div class="ifs-pms-pos-card">
        <div class="ifs-pms-pos-head">
            <h3 class="ifs-pms-pos-title">
                <i class="fa-solid fa-address-card ifs-pms-icon-primary"></i>
                <?php printf( esc_html__( 'Executive Patron Profile: %s', 'swimming-pool-manager' ), esc_html( $single_customer->name ) ); ?>
            </h3>
            <div class="ifs-pms-flex-gap-10">
                <button type="button" class="ifs-pms-btn-sm ifs-pms-btn-edit ifs-pms-btn-action-print" onclick="window.print()">
                    <i class="fa-solid fa-print"></i> <?php esc_html_e( 'Print Dossier', 'swimming-pool-manager' ); ?>
                </button>
                <a href="<?php echo esc_url( $base_url ); ?>" class="ifs-pms-btn-sm ifs-pms-btn-edit ifs-pms-btn-action-back">
                    <i class="fa-solid fa-arrow-left"></i> <?php esc_html_e( 'Back to Directory', 'swimming-pool-manager' ); ?>
                </a>
            </div>
        </div>

        <div class="ifs-pms-profile-content-wrap">
            <!-- Extended 4-Column Intelligence Stat Grid -->
            <div class="ifs-pms-profile-grid-4">
                <div class="ifs-pms-profile-stat">
                    <div class="ifs-pms-stat-header"><?php esc_html_e( 'Mobile Contact', 'swimming-pool-manager' ); ?></div>
                    <div class="ifs-pms-mono ifs-pms-stat-value-dark"><?php echo esc_html( $single_customer->phone ); ?></div>
                </div>
                <div class="ifs-pms-profile-stat">
                    <div class="ifs-pms-stat-header"><?php esc_html_e( 'Lifetime Spend', 'swimming-pool-manager' ); ?></div>
                    <div class="ifs-pms-mono ifs-pms-stat-value-green"><?php echo esc_html( $currency . ' ' . number_format( (float) ( $customer_metrics->total_spend ?? 0 ), 2 ) ); ?></div>
                </div>
                <div class="ifs-pms-profile-stat">
                    <div class="ifs-pms-stat-header"><?php esc_html_e( 'Average Ticket Outlay', 'swimming-pool-manager' ); ?></div>
                    <div class="ifs-pms-mono ifs-pms-stat-value-blue"><?php echo esc_html( $currency . ' ' . number_format( (float) ( $customer_metrics->avg_spend ?? 0 ), 2 ) ); ?></div>
                </div>
                <div class="ifs-pms-profile-stat">
                    <div class="ifs-pms-stat-header"><?php esc_html_e( 'Redemption Success', 'swimming-pool-manager' ); ?></div>
                    <div class="ifs-pms-mono ifs-pms-stat-value-purple"><?php echo esc_html( (int) ( $customer_metrics->redeemed_count ?? 0 ) ); ?> / <?php echo count( $customer_tickets ); ?> <?php esc_html_e( 'Scanned', 'swimming-pool-manager' ); ?></div>
                </div>
            </div>

            <!-- Payment Breakdown & Activity Summary Row -->
            <div class="ifs-pms-analytics-grid-2">
                <div class="ifs-pms-analytics-box">
                    <h5 class="ifs-pms-analytics-title">
                        <i class="fa-solid fa-wallet ifs-pms-icon-primary"></i> <?php esc_html_e( 'Payment Method Breakdown', 'swimming-pool-manager' ); ?>
                    </h5>
                    <div class="ifs-pms-analytics-stack">
                        <div class="ifs-pms-analytics-row">
                            <span class="ifs-pms-analytics-label"><?php esc_html_e( 'Cash Payments:', 'swimming-pool-manager' ); ?></span>
                            <strong class="ifs-pms-mono ifs-pms-text-dark"><?php echo esc_html( $currency . ' ' . number_format( (float) ( $customer_metrics->cash_spend ?? 0 ), 2 ) ); ?></strong>
                        </div>
                        <div class="ifs-pms-analytics-row">
                            <span class="ifs-pms-analytics-label"><?php esc_html_e( 'Card POS Payments:', 'swimming-pool-manager' ); ?></span>
                            <strong class="ifs-pms-mono ifs-pms-text-dark"><?php echo esc_html( $currency . ' ' . number_format( (float) ( $customer_metrics->card_spend ?? 0 ), 2 ) ); ?></strong>
                        </div>
                        <div class="ifs-pms-analytics-row">
                            <span class="ifs-pms-analytics-label"><?php esc_html_e( 'bKash / MFS Payments:', 'swimming-pool-manager' ); ?></span>
                            <strong class="ifs-pms-mono ifs-pms-text-dark"><?php echo esc_html( $currency . ' ' . number_format( (float) ( $customer_metrics->mfs_spend ?? 0 ), 2 ) ); ?></strong>
                        </div>
                    </div>
                </div>

                <div class="ifs-pms-analytics-box">
                    <h5 class="ifs-pms-analytics-title">
                        <i class="fa-solid fa-clock-rotate-left ifs-pms-icon-success"></i> <?php esc_html_e( 'Engagement Timeline', 'swimming-pool-manager' ); ?>
                    </h5>
                    <div class="ifs-pms-analytics-stack">
                        <div class="ifs-pms-analytics-row">
                            <span class="ifs-pms-analytics-label"><?php esc_html_e( 'First Registered Visit:', 'swimming-pool-manager' ); ?></span>
                            <strong class="ifs-pms-mono ifs-pms-text-dark"><?php echo esc_html( $single_customer->created_at ); ?></strong>
                        </div>
                        <div class="ifs-pms-analytics-row">
                            <span class="ifs-pms-analytics-label"><?php esc_html_e( 'Most Recent Activity:', 'swimming-pool-manager' ); ?></span>
                            <strong class="ifs-pms-mono ifs-pms-text-dark"><?php echo esc_html( $customer_metrics->recent_pass_date ? $customer_metrics->recent_pass_date : __( 'None', 'swimming-pool-manager' ) ); ?></strong>
                        </div>
                        <div class="ifs-pms-analytics-row">
                            <span class="ifs-pms-analytics-label"><?php esc_html_e( 'Patron Record ID:', 'swimming-pool-manager' ); ?></span>
                            <strong class="ifs-pms-mono ifs-pms-text-blue">#<?php echo esc_html( $single_customer->id ); ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <h4 class="ifs-pms-section-title">
                <i class="fa-solid fa-ticket ifs-pms-icon-primary"></i> <?php esc_html_e( 'Associated Ticket & Pass History Ledger', 'swimming-pool-manager' ); ?>
            </h4>

            <div class="ifs-pms-table-wrap ifs-pms-table-container-bordered">
                <table class="ifs-pms-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Pass Code', 'swimming-pool-manager' ); ?></th>
                            <th><?php esc_html_e( 'Package / Details', 'swimming-pool-manager' ); ?></th>
                            <th><?php esc_html_e( 'Amount Paid', 'swimming-pool-manager' ); ?></th>
                            <th><?php esc_html_e( 'Payment Method', 'swimming-pool-manager' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'swimming-pool-manager' ); ?></th>
                            <th><?php esc_html_e( 'Sold By', 'swimming-pool-manager' ); ?></th>
                            <th class="ifs-pms-th-right"><?php esc_html_e( 'Gate Verification', 'swimming-pool-manager' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $customer_tickets ) ) : ?>
                            <?php foreach ( $customer_tickets as $tkt ) : 
                                $is_valid = ( $tkt->status === 'Valid' );
                                $status_class = $is_valid ? 'ifs-pms-badge-success' : ( $tkt->status === 'Used' ? 'ifs-pms-badge-warning' : 'ifs-pms-badge-danger' );
                            ?>
                                <tr>
                                    <td class="ifs-pms-mono ifs-pms-text-blue ifs-pms-text-weight-bold">
                                        <?php echo esc_html( $tkt->ticket_code ); ?>
                                    </td>
                                    <td>
                                        <span class="ifs-pms-text-dark ifs-pms-text-weight-bold"><?php echo esc_html( ! empty( $tkt->package_details ) ? $tkt->package_details : __( 'Standard Ticket', 'swimming-pool-manager' ) ); ?></span>
                                        <?php if ( ! empty( $tkt->room_no ) ) : ?>
                                            <div class="ifs-pms-room-badge"><i class="fa-solid fa-door-open"></i> Room <?php echo esc_html( $tkt->room_no ); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="ifs-pms-mono ifs-pms-text-weight-bold">
                                        <?php echo esc_html( $currency . ' ' . number_format( (float) $tkt->amount, 2 ) ); ?>
                                    </td>
                                    <td class="ifs-pms-text-muted ifs-pms-text-weight-semi">
                                        <?php echo esc_html( $tkt->payment_method ); ?>
                                    </td>
                                    <td>
                                        <span class="ifs-pms-badge <?php echo esc_attr( $status_class ); ?>">
                                            <?php echo esc_html( $tkt->status ); ?>
                                        </span>
                                    </td>
                                    <td class="ifs-pms-text-muted ifs-pms-text-sm">
                                        <?php echo esc_html( $tkt->sold_by ); ?>
                                    </td>
                                    <td class="ifs-pms-mono ifs-pms-th-right ifs-pms-text-xs ifs-pms-text-sub">
                                        <?php echo esc_html( $tkt->scanned_at ? $tkt->scanned_at : __( 'Pending Scan', 'swimming-pool-manager' ) ); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="7" class="ifs-pms-empty-tickets">
                                    <?php esc_html_e( 'No tickets found for this patron.', 'swimming-pool-manager' ); ?>
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
    <div class="ifs-pms-pos-card">
        <div class="ifs-pms-pos-head">
            <h3 class="ifs-pms-pos-title">
                <i class="fa-solid fa-user-pen ifs-pms-icon-primary"></i>
                <?php esc_html_e( 'Edit Patron Profile Details', 'swimming-pool-manager' ); ?>
            </h3>
            <a href="<?php echo esc_url( $base_url ); ?>" class="ifs-pms-btn-sm ifs-pms-btn-edit ifs-pms-btn-action-back">
                <i class="fa-solid fa-arrow-left"></i> <?php esc_html_e( 'Cancel', 'swimming-pool-manager' ); ?>
            </a>
        </div>

        <div class="ifs-pms-form-content-pad">
            <div class="ifs-pms-inline-form-card">
                <form method="POST" action="<?php echo esc_url( $base_url ); ?>">
                    <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                    <input type="hidden" name="ifs_pms_action" value="edit_customer">
                    <input type="hidden" name="customer_id" value="<?php echo esc_attr( $edit_customer->id ); ?>">

                    <div class="ifs-pms-field-group">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Patron Full Name', 'swimming-pool-manager' ); ?> *</label>
                        <input type="text" name="name" value="<?php echo esc_attr( $edit_customer->name ); ?>" required>
                    </div>

                    <div class="ifs-pms-field-group ifs-pms-mb-28">
                        <label class="ifs-pms-field-label"><?php esc_html_e( 'Mobile Contact / Phone', 'swimming-pool-manager' ); ?> *</label>
                        <input type="tel" name="phone" value="<?php echo esc_attr( $edit_customer->phone ); ?>" required>
                    </div>

                    <div class="ifs-pms-modal-actions-split">
                        <button type="submit" class="ifs-pms-btn ifs-pms-btn-primary ifs-pms-btn-submit-action">
                            <i class="fa-solid fa-floppy-disk"></i> <?php esc_html_e( 'Update Patron Profile', 'swimming-pool-manager' ); ?>
                        </button>
                        <a href="<?php echo esc_url( $base_url ); ?>" class="ifs-pms-btn ifs-pms-btn-cancel-action">
                            <?php esc_html_e( 'Cancel', 'swimming-pool-manager' ); ?>
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
    <div class="ifs-pms-pos-card">
        <div class="ifs-pms-pos-head">
            <h3 class="ifs-pms-pos-title">
                <i class="fa-solid fa-users ifs-pms-icon-primary"></i>
                <?php esc_html_e( 'Patron Directory & Ticket Intelligence', 'swimming-pool-manager' ); ?>
            </h3>
            <span class="ifs-pms-badge ifs-pms-badge-neutral">
                <?php echo esc_html( $total_customers ); ?> <?php esc_html_e( 'Registered Patrons', 'swimming-pool-manager' ); ?>
            </span>
        </div>

        <div class="ifs-pms-search-bar">
            <div class="ifs-pms-search-box">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="ifsPmsCustomerSearchInput" placeholder="<?php esc_attr_e( 'Search patrons by name or phone...', 'swimming-pool-manager' ); ?>" oninput="ifsPmsFilterCustomerTable()" autocomplete="off">
            </div>
        </div>

        <div class="ifs-pms-table-wrap">
            <table class="ifs-pms-table" id="ifsPmsCustomersTable">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'ID', 'swimming-pool-manager' ); ?></th>
                        <th><?php esc_html_e( 'Patron Name', 'swimming-pool-manager' ); ?></th>
                        <th><?php esc_html_e( 'Phone Contact', 'swimming-pool-manager' ); ?></th>
                        <th><?php esc_html_e( 'Total Visits', 'swimming-pool-manager' ); ?></th>
                        <th><?php esc_html_e( 'Lifetime Spend', 'swimming-pool-manager' ); ?></th>
                        <th><?php esc_html_e( 'Last Activity', 'swimming-pool-manager' ); ?></th>
                        <th class="ifs-pms-th-right"><?php esc_html_e( 'Actions', 'swimming-pool-manager' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $customers ) ) : ?>
                        <?php foreach ( $customers as $cust ) : ?>
                            <tr class="ifs-pms-customer-row">
                                <td class="ifs-pms-mono ifs-pms-text-muted ifs-pms-text-weight-bold">
                                    #<?php echo esc_html( $cust->id ); ?>
                                </td>
                                <td class="ifs-pms-text-dark ifs-pms-text-weight-heavy">
                                    <?php echo esc_html( $cust->name ); ?>
                                </td>
                                <td class="ifs-pms-mono ifs-pms-text-muted">
                                    <?php echo esc_html( $cust->phone ); ?>
                                </td>
                                <td>
                                    <span class="ifs-pms-badge ifs-pms-badge-info">
                                        <?php echo esc_html( $cust->total_visits ); ?> <?php esc_html_e( 'Passes', 'swimming-pool-manager' ); ?>
                                    </span>
                                </td>
                                <td class="ifs-pms-mono ifs-pms-text-green ifs-pms-text-weight-bold">
                                    <?php echo esc_html( $currency . ' ' . number_format( (float) $cust->lifetime_spend, 2 ) ); ?>
                                </td>
                                <td class="ifs-pms-mono ifs-pms-text-xs ifs-pms-text-sub">
                                    <?php echo esc_html( $cust->last_visit ? $cust->last_visit : __( 'Never', 'swimming-pool-manager' ) ); ?>
                                </td>
                                <td class="ifs-pms-td-actions">
                                    <!-- View Intelligence Matrix Button -->
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=customers&mode=view&customer_id=' . $cust->id ) ); ?>" class="ifs-pms-btn-sm ifs-pms-btn-view">
                                        <i class="fa-solid fa-eye"></i> <?php esc_html_e( 'View', 'swimming-pool-manager' ); ?>
                                    </a>

                                    <!-- Edit Inline Button -->
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=customers&mode=edit&customer_id=' . $cust->id ) ); ?>" class="ifs-pms-btn-sm ifs-pms-btn-edit">
                                        <i class="fa-solid fa-pen-to-square"></i> <?php esc_html_e( 'Edit', 'swimming-pool-manager' ); ?>
                                    </a>

                                    <?php if ( $is_admin ) : ?>
                                        <form method="POST" action="<?php echo esc_url( $base_url ); ?>" class="ifs-pms-inline-form" onsubmit="return confirm(<?php echo wp_json_encode( __( 'Delete this customer profile permanently?', 'swimming-pool-manager' ) ); ?>);">
                                            <?php wp_nonce_field( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' ); ?>
                                            <input type="hidden" name="ifs_pms_action" value="delete_customer">
                                            <input type="hidden" name="customer_id" value="<?php echo esc_attr( $cust->id ); ?>">
                                            <button type="submit" class="ifs-pms-btn-sm ifs-pms-btn-delete" title="<?php esc_attr_e( 'Delete Profile', 'swimming-pool-manager' ); ?>">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7" class="ifs-pms-empty-state-large">
                                <i class="fa-solid fa-users-slash ifs-pms-empty-icon-large"></i>
                                <?php esc_html_e( 'No customer profiles found in database.', 'swimming-pool-manager' ); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <?php if ( $total_pages > 1 ) : ?>
            <div class="ifs-pms-pagination-footer">
                <div class="ifs-pms-pagination-counter">
                    <?php 
                    $start = $offset + 1;
                    $end   = min( $offset + $per_page, $total_customers );
                    printf( esc_html__( 'Showing %1$d–%2$d of %3$d patrons', 'swimming-pool-manager' ), $start, $end, $total_customers ); 
                    ?>
                </div>
                <div class="ifs-pms-flex-gap-6">
                    <?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs-pms&view=customers&paged=' . $i ) ); ?>" 
                           class="ifs-pms-page-num <?php echo ( $i == $paged ) ? 'active' : ''; ?>">
                            <?php echo esc_html( $i ); ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
</div>

<script>
function ifsPmsFilterCustomerTable() {
    const query = document.getElementById('ifsPmsCustomerSearchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('.ifs-pms-customer-row');

    rows.forEach(r => {
        const text = r.textContent.toLowerCase();
        r.style.display = (!query || text.includes(query)) ? '' : 'none';
    });
}
</script>