<?php
/**
 * View: Financial Performance Reports & Date-Wise Audit Statement (Zero Inline CSS - 100% ifs-pms- CSS Class Namespace)
 *
 * @package Ozone_Skypool_OS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$table_tickets     = $wpdb->prefix . 'ifs_pms_tickets';
$table_memberships = $wpdb->prefix . 'ifs_pms_memberships';
$table_expenses    = $wpdb->prefix . 'ifs_pms_expenses';
$table_customers   = $wpdb->prefix . 'ifs_pms_customers';
$currency          = esc_html( (string) get_option( 'ifs_pms_currency', 'BDT' ) );
$business_name     = esc_html( (string) get_option( 'ifs_pms_business_name', 'Ozone Skypool' ) );

// 1. Date Range Filter Parameters
$today_date_str    = current_time( 'Y-m-d' );
$default_start     = gmdate( 'Y-m-01', strtotime( $today_date_str ) );
$default_end       = $today_date_str;

$start_date_input  = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : $default_start;
$end_date_input    = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : $default_end;

if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date_input ) ) {
    $start_date_input = $default_start;
}
if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date_input ) ) {
    $end_date_input = $default_end;
}

$active_sub_tab     = sanitize_key( $_GET['sub_tab'] ?? 'income' );
$income_filter      = sanitize_key( $_GET['income_filter'] ?? 'all' );
$expense_cat_filter = sanitize_text_field( wp_unslash( $_GET['expense_cat'] ?? 'all' ) );

// 2. High-Performance SQL Timestamp Boundaries
$start_datetime = $start_date_input . ' 00:00:00';
$end_datetime   = $end_date_input . ' 23:59:59';

// 3. High-Level Summary Statistics
$ticket_stats = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT 
            COALESCE(SUM(amount), 0) AS total_money,
            COUNT(id) AS total_count,
            SUM(CASE WHEN payment_method = 'Cash' THEN amount ELSE 0 END) AS cash_money,
            SUM(CASE WHEN payment_method = 'Card POS' THEN amount ELSE 0 END) AS card_money,
            SUM(CASE WHEN payment_method = 'bKash / Nagad' THEN amount ELSE 0 END) AS mfs_money,
            SUM(CASE WHEN guest_type = 'room_guest' OR payment_method IN ('Room Guest', 'Complementary') THEN 1 ELSE 0 END) AS free_count,
            SUM(CASE WHEN status = 'Used' THEN 1 ELSE 0 END) AS used_count
         FROM {$table_tickets} 
         WHERE sold_at >= %s AND sold_at <= %s AND status != 'Cancelled'",
        $start_datetime,
        $end_datetime
    )
);

$ticket_income     = (float) ( $ticket_stats->total_money ?? 0.00 );
$total_tickets     = (int) ( $ticket_stats->total_count ?? 0 );
$cash_income       = (float) ( $ticket_stats->cash_money ?? 0.00 );
$card_income       = (float) ( $ticket_stats->card_money ?? 0.00 );
$mfs_income        = (float) ( $ticket_stats->mfs_money ?? 0.00 );

$member_stats = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT 
            COALESCE(SUM(amount), 0) AS total_money,
            COUNT(id) AS total_count
         FROM {$table_memberships} 
         WHERE created_at >= %s AND created_at <= %s",
        $start_datetime,
        $end_datetime
    )
);

$member_income     = (float) ( $member_stats->total_money ?? 0.00 );
$total_members     = (int) ( $member_stats->total_count ?? 0 );
$total_income      = $ticket_income + $member_income;

$total_expense     = (float) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COALESCE(SUM(amount), 0) 
         FROM {$table_expenses} 
         WHERE expense_date >= %s AND expense_date <= %s",
        $start_date_input,
        $end_date_input
    )
);

$net_profit        = $total_income - $total_expense;
$profit_rate       = $total_income > 0 ? round( ( $net_profit / $total_income ) * 100, 1 ) : 0.0;

// 4. Detailed Ledger Query Lists based on Filters
$detailed_tickets     = array();
$detailed_memberships = array();

if ( $income_filter === 'all' || $income_filter === 'tickets' ) {
    $detailed_tickets = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT t.*, c.name AS customer_name, c.phone AS customer_phone 
             FROM {$table_tickets} t 
             LEFT JOIN {$table_customers} c ON t.customer_id = c.id 
             WHERE t.sold_at >= %s AND t.sold_at <= %s AND t.status != 'Cancelled' 
             ORDER BY t.id DESC",
            $start_datetime,
            $end_datetime
        )
    );
}

if ( $income_filter === 'all' || $income_filter === 'memberships' ) {
    $detailed_memberships = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table_memberships} 
             WHERE created_at >= %s AND created_at <= %s 
             ORDER BY id DESC",
            $start_datetime,
            $end_datetime
        )
    );
}

$expense_categories = $wpdb->get_results( "SELECT DISTINCT category FROM {$table_expenses} ORDER BY category ASC", ARRAY_A );

$expense_query_sql  = "SELECT * FROM {$table_expenses} WHERE expense_date >= %s AND expense_date <= %s";
$expense_query_args = array( $start_date_input, $end_date_input );

if ( ! empty( $expense_cat_filter ) && $expense_cat_filter !== 'all' ) {
    $expense_query_sql  .= " AND category = %s";
    $expense_query_args[] = $expense_cat_filter;
}
$expense_query_sql .= " ORDER BY expense_date DESC, id DESC";

$detailed_expenses = $wpdb->get_results( $wpdb->prepare( $expense_query_sql, $expense_query_args ) );

$date_range_label = sprintf( '%s to %s', date_i18n( 'M j, Y', strtotime( $start_date_input ) ), date_i18n( 'M j, Y', strtotime( $end_date_input ) ) );
?>

<div class="ifs-pms-report-wrap">
    <!-- Top Date-Wise Filter Bar -->
    <div class="ifs-pms-top-bar">
        <div>
            <div class="ifs-pms-tag-badge">
                <span class="ifs-pms-pulse-dot"></span> <?php esc_html_e( 'Date Range Filtered Audit', 'ozone-skypool' ); ?>
            </div>
            <h2 class="ifs-pms-bar-title">
                <?php echo esc_html( $business_name ); ?> &bull; <?php echo esc_html( $date_range_label ); ?>
            </h2>
        </div>

        <div class="ifs-pms-bar-controls">
            <form method="GET" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display: flex; gap: 8px; align-items: center; margin: 0;" id="ifsPmsReportGlobalForm">
                <input type="hidden" name="page" value="ifs-pms">
                <input type="hidden" name="view" value="reports">
                <input type="hidden" name="sub_tab" id="ifsPmsSubTabInput" value="<?php echo esc_attr( $active_sub_tab ); ?>">
                <input type="hidden" name="income_filter" value="<?php echo esc_attr( $income_filter ); ?>">
                <input type="hidden" name="expense_cat" value="<?php echo esc_attr( $expense_cat_filter ); ?>">

                <div class="ifs-pms-date-picker-pill">
                    <span class="ifs-pms-date-label"><?php esc_html_e( 'From', 'ozone-skypool' ); ?></span>
                    <input type="date" name="start_date" value="<?php echo esc_attr( $start_date_input ); ?>">
                    <span class="ifs-pms-date-label"><?php esc_html_e( 'To', 'ozone-skypool' ); ?></span>
                    <input type="date" name="end_date" value="<?php echo esc_attr( $end_date_input ); ?>">
                </div>

                <button type="submit" class="ifs-pms-btn ifs-pms-btn-blue">
                    <i class="fa-solid fa-filter"></i> <?php esc_html_e( 'Apply Filter', 'ozone-skypool' ); ?>
                </button>
            </form>

            <button type="button" class="ifs-pms-btn ifs-pms-btn-white" onclick="window.print()">
                <i class="fa-solid fa-print"></i> <?php esc_html_e( 'Print', 'ozone-skypool' ); ?>
            </button>
        </div>
    </div>

    <!-- 4 Summary Cards -->
    <div class="ifs-pms-card-grid">
        <div class="ifs-pms-stat-card">
            <div class="ifs-pms-stat-head">
                <span class="ifs-pms-stat-name"><?php esc_html_e( 'Total Income', 'ozone-skypool' ); ?></span>
                <div class="ifs-pms-stat-icon ifs-pms-stat-icon-green">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
            <div class="ifs-pms-stat-number ifs-pms-mono ifs-pms-stat-number-green">
                <?php echo esc_html( $currency . ' ' . number_format( $total_income, 2 ) ); ?>
            </div>
            <div class="ifs-pms-stat-bottom">
                <span><?php printf( esc_html__( 'Tickets: %s', 'ozone-skypool' ), esc_html( number_format( $ticket_income, 2 ) ) ); ?></span>
                <span><?php printf( esc_html__( 'Members: %s', 'ozone-skypool' ), esc_html( number_format( $member_income, 2 ) ) ); ?></span>
            </div>
        </div>

        <div class="ifs-pms-stat-card">
            <div class="ifs-pms-stat-head">
                <span class="ifs-pms-stat-name"><?php esc_html_e( 'Total Expenses', 'ozone-skypool' ); ?></span>
                <div class="ifs-pms-stat-icon ifs-pms-stat-icon-red">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>
            <div class="ifs-pms-stat-number ifs-pms-mono ifs-pms-stat-number-red">
                <?php echo esc_html( $currency . ' ' . number_format( $total_expense, 2 ) ); ?>
            </div>
            <div class="ifs-pms-stat-bottom">
                <span><?php printf( esc_html__( '%d Vouchers', 'ozone-skypool' ), count( $detailed_expenses ) ); ?></span>
                <span><?php esc_html_e( 'Outflows', 'ozone-skypool' ); ?></span>
            </div>
        </div>

        <div class="ifs-pms-stat-card">
            <div class="ifs-pms-stat-head">
                <span class="ifs-pms-stat-name"><?php esc_html_e( 'Net Profit', 'ozone-skypool' ); ?></span>
                <div class="ifs-pms-stat-icon ifs-pms-stat-icon-blue">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
            </div>
            <div class="ifs-pms-stat-number ifs-pms-mono" style="color: <?php echo ( $net_profit >= 0 ) ? '#0f172a' : '#ef4444'; ?>;">
                <?php echo esc_html( $currency . ' ' . number_format( $net_profit, 2 ) ); ?>
            </div>
            <div class="ifs-pms-stat-bottom">
                <strong style="color: <?php echo $net_profit >= 0 ? '#10b981' : '#ef4444'; ?>;">
                    <?php echo esc_html( $net_profit >= 0 ? __( 'Profitable', 'ozone-skypool' ) : __( 'Loss Recorded', 'ozone-skypool' ) ); ?>
                </strong>
            </div>
        </div>

        <div class="ifs-pms-stat-card">
            <div class="ifs-pms-stat-head">
                <span class="ifs-pms-stat-name"><?php esc_html_e( 'Profit Margin', 'ozone-skypool' ); ?></span>
                <div class="ifs-pms-stat-icon ifs-pms-stat-icon-purple">
                    <i class="fa-solid fa-percent"></i>
                </div>
            </div>
            <div class="ifs-pms-stat-number ifs-pms-mono ifs-pms-stat-number-purple">
                <?php echo esc_html( (string) $profit_rate ); ?>%
            </div>
            <div class="ifs-pms-stat-bottom">
                <span><?php esc_html_e( 'Income vs Cost', 'ozone-skypool' ); ?></span>
            </div>
        </div>
    </div>

    <!-- Sub Navigation Tabs for Income vs Expense Statements -->
    <div class="ifs-pms-sub-tabs" role="tablist">
        <button type="button" class="ifs-pms-sub-tab-btn <?php echo ( $active_sub_tab === 'income' ) ? 'active' : ''; ?>" onclick="ifsPmsSwitchSubTab('income')">
            <i class="fa-solid fa-circle-arrow-down" style="color: #10b981;"></i> <?php esc_html_e( 'Income Details & Breakdown', 'ozone-skypool' ); ?>
        </button>
        <button type="button" class="ifs-pms-sub-tab-btn <?php echo ( $active_sub_tab === 'expense' ) ? 'active' : ''; ?>" onclick="ifsPmsSwitchSubTab('expense')">
            <i class="fa-solid fa-circle-arrow-up" style="color: #ef4444;"></i> <?php esc_html_e( 'Expense Details & Breakdown', 'ozone-skypool' ); ?>
        </button>
    </div>

    <!-- TAB CONTENT 1: INCOME STATEMENT -->
    <div id="ifsPmsIncomeTabContent" class="ifs-pms-tab-content <?php echo ( $active_sub_tab === 'income' ) ? 'active' : ''; ?>">
        <div class="ifs-pms-table-box">
            <div class="ifs-pms-table-head">
                <h3 class="ifs-pms-table-title">
                    <i class="fa-solid fa-ticket" style="color: #0284c7;"></i>
                    <?php esc_html_e( 'Income Ledger Transactions', 'ozone-skypool' ); ?>
                </h3>

                <!-- Income Detailed Filter -->
                <div class="ifs-pms-table-filter-group">
                    <span class="ifs-pms-table-filter-label"><?php esc_html_e( 'Filter Type:', 'ozone-skypool' ); ?></span>
                    <select id="ifsPmsIncomeFilterSelect" onchange="ifsPmsApplyIncomeFilter(this.value)">
                        <option value="all" <?php selected( $income_filter, 'all' ); ?>><?php esc_html_e( 'All Incomes (Tickets + Memberships)', 'ozone-skypool' ); ?></option>
                        <option value="tickets" <?php selected( $income_filter, 'tickets' ); ?>><?php esc_html_e( 'Ticket Sales Only', 'ozone-skypool' ); ?></option>
                        <option value="memberships" <?php selected( $income_filter, 'memberships' ); ?>><?php esc_html_e( 'Member Subscriptions Only', 'ozone-skypool' ); ?></option>
                    </select>
                </div>
            </div>

            <div style="width: 100%; overflow-x: auto;">
                <table class="ifs-pms-data-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Reference / Code', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Patron / Subscriber', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Type / Details', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Payment Method', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Cashier / Staff', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Date & Time', 'ozone-skypool' ); ?></th>
                            <th style="text-align: right;"><?php esc_html_e( 'Amount', 'ozone-skypool' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $has_income_rows = false;

                        // Render Tickets
                        if ( ! empty( $detailed_tickets ) ) {
                            $has_income_rows = true;
                            foreach ( $detailed_tickets as $t ) {
                                ?>
                                <tr>
                                    <td class="ifs-pms-mono ifs-pms-code-cell ifs-pms-code-ticket"><?php echo esc_html( $t->ticket_code ); ?></td>
                                    <td class="ifs-pms-patron-cell">
                                        <?php echo esc_html( ! empty( $t->customer_name ) ? $t->customer_name : __( 'Walk-in Guest', 'ozone-skypool' ) ); ?>
                                        <div class="ifs-pms-patron-sub ifs-pms-mono"><?php echo esc_html( $t->customer_phone ); ?></div>
                                    </td>
                                    <td>
                                        <span class="ifs-pms-badge ifs-pms-badge-ticket">
                                            <?php echo esc_html( ! empty( $t->package_details ) ? $t->package_details : __( 'Standard Ticket', 'ozone-skypool' ) ); ?>
                                        </span>
                                        <?php if ( ! empty( $t->room_no ) ) : ?>
                                            <div class="ifs-pms-room-tag"><i class="fa-solid fa-door-open"></i> <?php echo esc_html( $t->room_no ); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight: 600;"><?php echo esc_html( $t->payment_method ); ?></td>
                                    <td class="ifs-pms-text-muted"><?php echo esc_html( $t->sold_by ); ?></td>
                                    <td class="ifs-pms-mono ifs-pms-text-date"><?php echo esc_html( $t->sold_at ); ?></td>
                                    <td class="ifs-pms-mono ifs-pms-amount-green">
                                        +<?php echo esc_html( $currency . ' ' . number_format( (float) $t->amount, 2 ) ); ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        }

                        // Render Memberships
                        if ( ! empty( $detailed_memberships ) ) {
                            $has_income_rows = true;
                            foreach ( $detailed_memberships as $m ) {
                                ?>
                                <tr>
                                    <td class="ifs-pms-mono ifs-pms-code-cell ifs-pms-code-member"><?php echo esc_html( $m->member_code ); ?></td>
                                    <td class="ifs-pms-patron-cell">
                                        <?php echo esc_html( $m->name ); ?>
                                        <div class="ifs-pms-patron-sub ifs-pms-mono"><?php echo esc_html( $m->phone ); ?></div>
                                    </td>
                                    <td>
                                        <span class="ifs-pms-badge ifs-pms-badge-member">
                                            <?php echo esc_html( $m->plan_type ); ?>
                                        </span>
                                    </td>
                                    <td style="font-weight: 600;"><?php esc_html_e( 'Subscription', 'ozone-skypool' ); ?></td>
                                    <td class="ifs-pms-text-muted"><?php esc_html_e( 'Front Desk', 'ozone-skypool' ); ?></td>
                                    <td class="ifs-pms-mono ifs-pms-text-date"><?php echo esc_html( $m->created_at ); ?></td>
                                    <td class="ifs-pms-mono ifs-pms-amount-green">
                                        +<?php echo esc_html( $currency . ' ' . number_format( (float) $m->amount, 2 ) ); ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        }

                        if ( ! $has_income_rows ) {
                            ?>
                            <tr>
                                <td colspan="7" class="ifs-pms-empty-state">
                                    <i class="fa-solid fa-wallet ifs-pms-empty-icon"></i>
                                    <?php esc_html_e( 'No income records found for this date range.', 'ozone-skypool' ); ?>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="ifs-pms-table-footer">
                <span class="ifs-pms-footer-label"><?php esc_html_e( 'Total Filtered Income Inflow', 'ozone-skypool' ); ?></span>
                <strong class="ifs-pms-mono ifs-pms-footer-value-green">
                    <?php echo esc_html( $currency . ' ' . number_format( $total_income, 2 ) ); ?>
                </strong>
            </div>
        </div>
    </div>

    <!-- TAB CONTENT 2: EXPENSE STATEMENT -->
    <div id="ifsPmsExpenseTabContent" class="ifs-pms-tab-content <?php echo ( $active_sub_tab === 'expense' ) ? 'active' : ''; ?>">
        <div class="ifs-pms-table-box">
            <div class="ifs-pms-table-head">
                <h3 class="ifs-pms-table-title">
                    <i class="fa-solid fa-receipt" style="color: #ef4444;"></i>
                    <?php esc_html_e( 'Expense Outflows Ledger', 'ozone-skypool' ); ?>
                </h3>

                <!-- Expense Category Filter -->
                <div class="ifs-pms-table-filter-group">
                    <span class="ifs-pms-table-filter-label"><?php esc_html_e( 'Category:', 'ozone-skypool' ); ?></span>
                    <select id="ifsPmsExpenseCatSelect" onchange="ifsPmsApplyExpenseFilter(this.value)">
                        <option value="all" <?php selected( $expense_cat_filter, 'all' ); ?>><?php esc_html_e( 'All Categories', 'ozone-skypool' ); ?></option>
                        <?php if ( ! empty( $expense_categories ) ) : ?>
                            <?php foreach ( $expense_categories as $cat ) : ?>
                                <option value="<?php echo esc_attr( $cat['category'] ); ?>" <?php selected( $expense_cat_filter, $cat['category'] ); ?>>
                                    <?php echo esc_html( $cat['category'] ); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div style="width: 100%; overflow-x: auto;">
                <table class="ifs-pms-data-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Expense Title', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Category', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Logged By', 'ozone-skypool' ); ?></th>
                            <th><?php esc_html_e( 'Voucher Date', 'ozone-skypool' ); ?></th>
                            <th style="text-align: right;"><?php esc_html_e( 'Disbursement', 'ozone-skypool' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $detailed_expenses ) ) : ?>
                            <?php foreach ( $detailed_expenses as $e ) : ?>
                                <tr>
                                    <td class="ifs-pms-patron-cell"><?php echo esc_html( $e->title ); ?></td>
                                    <td>
                                        <span class="ifs-pms-badge ifs-pms-badge-expense">
                                            <?php echo esc_html( $e->category ); ?>
                                        </span>
                                    </td>
                                    <td class="ifs-pms-text-muted"><?php echo esc_html( $e->added_by ); ?></td>
                                    <td class="ifs-pms-mono ifs-pms-text-date"><?php echo esc_html( $e->expense_date ); ?></td>
                                    <td class="ifs-pms-mono ifs-pms-amount-red">
                                        -<?php echo esc_html( $currency . ' ' . number_format( (float) $e->amount, 2 ) ); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="5" class="ifs-pms-empty-state">
                                    <i class="fa-solid fa-receipt ifs-pms-empty-icon"></i>
                                    <?php esc_html_e( 'No expense disbursements recorded for this date range.', 'ozone-skypool' ); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="ifs-pms-table-footer">
                <span class="ifs-pms-footer-label"><?php esc_html_e( 'Total Filtered Expense Outflow', 'ozone-skypool' ); ?></span>
                <strong class="ifs-pms-mono ifs-pms-footer-value-red">
                    -<?php echo esc_html( $currency . ' ' . number_format( $total_expense, 2 ) ); ?>
                </strong>
            </div>
        </div>
    </div>
</div>