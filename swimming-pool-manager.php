<?php
/**
 * Plugin Name:       Ozone Skypool Management System (Ozone Skypool OS)
 * Plugin URI:        https://example.com/ozone-skypool
 * Description:       Enterprise Aquatic POS, QR Gate Turnstile Control, RFID/Pass Ledger & Financial Operating System with Role-Based Access Control.
 * Version:           7.2.3
 * Author:            Ozone Tech
 * Text Domain:       ozone-skypool
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'IFS_PMS_VERSION', '7.2.3' );
define( 'IFS_PMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'IFS_PMS_URL', plugin_dir_url( __FILE__ ) );

/**
 * 1. Database Installation, Migration & Initial Seed
 */
register_activation_hook( __FILE__, 'ifs_pms_install' );

function ifs_pms_install() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();

    $t_cust    = $wpdb->prefix . 'ifs_pms_customers';
    $t_tick    = $wpdb->prefix . 'ifs_pms_tickets';
    $t_members = $wpdb->prefix . 'ifs_pms_memberships';
    $t_expense = $wpdb->prefix . 'ifs_pms_expenses';
    $t_salary  = $wpdb->prefix . 'ifs_pms_salaries';

    $sql = "CREATE TABLE {$t_cust} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        phone varchar(20) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY phone (phone)
    ) {$charset_collate};

    CREATE TABLE {$t_tick} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        ticket_code varchar(50) NOT NULL,
        customer_id mediumint(9) NOT NULL,
        amount decimal(10,2) NOT NULL DEFAULT 0.00,
        sold_by varchar(100) NOT NULL,
        scanned_by varchar(100) DEFAULT NULL,
        status varchar(20) DEFAULT 'Valid' NOT NULL,
        sold_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        scanned_at datetime NULL,
        PRIMARY KEY (id),
        UNIQUE KEY ticket_code (ticket_code),
        KEY idx_customer (customer_id),
        KEY idx_status (status),
        KEY idx_sold_at (sold_at),
        KEY idx_scanned_at (scanned_at)
    ) {$charset_collate};

    CREATE TABLE {$t_members} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        member_code varchar(50) NOT NULL,
        name varchar(100) NOT NULL,
        phone varchar(20) NOT NULL,
        plan_type varchar(50) NOT NULL,
        amount decimal(10,2) NOT NULL DEFAULT 0.00,
        start_date date NOT NULL,
        expiry_date date NOT NULL,
        status varchar(20) DEFAULT 'Active' NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY member_code (member_code),
        KEY idx_phone (phone),
        KEY idx_expiry (expiry_date),
        KEY idx_status (status)
    ) {$charset_collate};

    CREATE TABLE {$t_expense} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(200) NOT NULL,
        category varchar(100) NOT NULL,
        amount decimal(10,2) NOT NULL DEFAULT 0.00,
        added_by varchar(100) NOT NULL,
        expense_date date NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id),
        KEY idx_expense_date (expense_date),
        KEY idx_category (category)
    ) {$charset_collate};

    CREATE TABLE {$t_salary} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        base_salary decimal(10,2) NOT NULL DEFAULT 0.00,
        allowance decimal(10,2) NOT NULL DEFAULT 0.00,
        pay_frequency varchar(20) DEFAULT 'Monthly' NOT NULL,
        effective_date date NOT NULL,
        status varchar(20) DEFAULT 'Active' NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id),
        KEY idx_user (user_id)
    ) {$charset_collate};";

    dbDelta( $sql );

    add_option( 'ifs_pms_business_name', __( 'Ozone Restaurant & Skypool', 'ozone-skypool' ) );
    add_option( 'ifs_pms_ticket_price', '500.00' );
    add_option( 'ifs_pms_currency', 'BDT' );
    add_option( 'ifs_pms_phone', '+880 1700-000000' );
    add_option( 'ifs_pms_address', '20th Floor, Ritz Tower, Dargah Gate, Sylhet' );
    add_option( 'ifs_pms_receipt_note', __( 'Proper swimwear compulsory. Deck passes non-refundable. Outside food and beverages not permitted.', 'ozone-skypool' ) );
    add_option( 'ifs_pms_max_capacity', 80 );
    add_option( 'ifs_pms_logo_url', '' );
    add_option( 'ifs_pms_pool_status', 'open' );
    add_option( 'ifs_pms_enable_amenities', '1' );
    add_option( 'ifs_pms_version', IFS_PMS_VERSION );
}

/**
 * 2. Dynamic RBAC Registration & Capability Synchronization
 */
add_action( 'admin_init', 'ifs_pms_ensure_capabilities' );

function ifs_pms_ensure_capabilities() {
    $admin_role = get_role( 'administrator' );
    if ( $admin_role && ! $admin_role->has_cap( 'ozone_access_terminal' ) ) {
        $caps = array(
            'ozone_access_terminal',
            'ozone_sell_tickets',
            'ozone_scan_passes',
            'ozone_manage_patrons',
            'ozone_view_history',
            'ozone_view_finances',
            'ozone_manage_expenses',
            'ozone_manage_settings',
        );
        foreach ( $caps as $cap ) {
            $admin_role->add_cap( $cap );
        }
    }

    if ( ! get_role( 'ozone_cashier' ) ) {
        add_role( 'ozone_cashier', __( 'Ozone Cashier', 'ozone-skypool' ), array(
            'read'                 => true,
            'ozone_access_terminal' => true,
            'ozone_sell_tickets'    => true,
            'ozone_scan_passes'     => true,
            'ozone_manage_patrons'  => true,
            'ozone_view_history'    => true,
        ) );
    }
}

/**
 * 3. Menu Registration with Role Fallback & Customers Submenu
 */
add_action( 'admin_menu', 'ifs_pms_register_menu' );

function ifs_pms_register_menu() {
    $capability = current_user_can( 'manage_options' ) ? 'manage_options' : 'ozone_access_terminal';

    add_menu_page(
        __( 'Ozone Skypool', 'ozone-skypool' ),
        __( 'Ozone Skypool', 'ozone-skypool' ),
        $capability,
        'ifs-pms',
        'ifs_pms_render_application',
        'dashicons-cloud',
        2
    );

    add_submenu_page(
        'ifs-pms',
        __( 'Patron Directory', 'ozone-skypool' ),
        __( 'Customers', 'ozone-skypool' ),
        'ozone_manage_patrons',
        'ifs-pms&view=customers',
        'ifs_pms_render_application'
    );
}

/**
 * 4. Clean Admin Asset Queue & Script Localization
 */
add_action( 'admin_enqueue_scripts', 'ifs_pms_enqueue_assets' );

function ifs_pms_enqueue_assets( $hook ) {
    if ( $hook !== 'toplevel_page_ifs-pms' ) {
        return;
    }

    wp_enqueue_media();

    wp_enqueue_style( 'ifs-pms-font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css', array(), '6.5.1' );
    wp_enqueue_style( 'ifs-pms-fonts', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap', array(), null );
    wp_enqueue_script( 'ifs-pms-qrcode', 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js', array(), '1.0.0', true );

    wp_enqueue_style( 'ozone-skypool-admin-css', IFS_PMS_URL . 'assets/css/style.css', array(), IFS_PMS_VERSION );
    wp_enqueue_script( 'ozone-skypool-admin-js', IFS_PMS_URL . 'assets/js/main.js', array( 'ifs-pms-qrcode' ), IFS_PMS_VERSION, true );

    $last_ticket = get_transient( 'ifs_pms_last_ticket_' . get_current_user_id() );
    if ( $last_ticket ) {
        delete_transient( 'ifs_pms_last_ticket_' . get_current_user_id() );
    }

    wp_localize_script( 'ozone-skypool-admin-js', 'ifsPmsConfig', array(
        'ajax_url'    => admin_url( 'admin-ajax.php' ),
        'nonce'       => wp_create_nonce( 'ifs_pms_security_token' ),
        'currency'    => esc_html( get_option( 'ifs_pms_currency', 'BDT' ) ),
        'last_ticket' => $last_ticket ? $last_ticket : null,
    ) );
}

/**
 * 5. PRG Form Post Handlers with Capability Guards (Full CRUD)
 */
add_action( 'admin_init', 'ifs_pms_handle_form_submissions' );

function ifs_pms_handle_form_submissions() {
    if ( ! isset( $_POST['ifs_pms_action'] ) ) {
        return;
    }

    check_admin_referer( 'ifs_pms_secure_action', 'ifs_pms_action_nonce' );

    global $wpdb;
    $t_cust    = $wpdb->prefix . 'ifs_pms_customers';
    $t_tick    = $wpdb->prefix . 'ifs_pms_tickets';
    $t_members = $wpdb->prefix . 'ifs_pms_memberships';
    $t_expense = $wpdb->prefix . 'ifs_pms_expenses';
    $t_salary  = $wpdb->prefix . 'ifs_pms_salaries';

    $action = sanitize_key( $_POST['ifs_pms_action'] );
    $base   = admin_url( 'admin.php?page=ifs-pms' );

    // --- STAFF & SALARY CRUD ---

    if ( $action === 'create_staff' ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Administrator privileges required.', 'ozone-skypool' ) );
        }

        $username = sanitize_user( $_POST['user_login'] ?? '' );
        $email    = sanitize_email( $_POST['user_email'] ?? '' );
        $password = $_POST['user_pass'] ?? '';
        $fullname = sanitize_text_field( wp_unslash( $_POST['display_name'] ?? '' ) );
        $role     = sanitize_key( $_POST['user_role'] ?? 'ozone_cashier' );

        $base_sal = floatval( $_POST['base_salary'] ?? 0.00 );
        $allow    = floatval( $_POST['allowance'] ?? 0.00 );
        $freq     = sanitize_text_field( wp_unslash( $_POST['pay_frequency'] ?? 'Monthly' ) );
        $eff_date = sanitize_text_field( wp_unslash( $_POST['effective_date'] ?? current_time( 'Y-m-d' ) ) );

        if ( ! empty( $username ) && ! empty( $email ) && ! empty( $password ) ) {
            $user_id = wp_create_user( $username, $password, $email );
            if ( ! is_wp_error( $user_id ) ) {
                wp_update_user( array(
                    'ID'           => $user_id,
                    'display_name' => $fullname ? $fullname : $username,
                    'role'         => $role,
                ) );

                $wpdb->insert(
                    $t_salary,
                    array(
                        'user_id'        => $user_id,
                        'base_salary'    => $base_sal,
                        'allowance'      => $allow,
                        'pay_frequency'  => $freq,
                        'effective_date' => $eff_date,
                        'status'         => 'Active',
                    ),
                    array( '%d', '%f', '%f', '%s', '%s', '%s' )
                );
            }
            wp_safe_redirect( add_query_arg( array( 'view' => 'staff', 'msg' => 'staff_created', 'tab' => 'list' ), admin_url( 'admin.php?page=ifs-pms' ) ) );
            exit;
        }
    }

    if ( $action === 'edit_staff' ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Administrator privileges required.', 'ozone-skypool' ) );
        }

        $user_id  = absint( $_POST['user_id'] ?? 0 );
        $fullname = sanitize_text_field( wp_unslash( $_POST['display_name'] ?? '' ) );
        $email    = sanitize_email( $_POST['user_email'] ?? '' );
        $role     = sanitize_key( $_POST['user_role'] ?? 'ozone_cashier' );
        $new_pass = $_POST['user_pass'] ?? '';

        $base_sal = floatval( $_POST['base_salary'] ?? 0.00 );
        $allow    = floatval( $_POST['allowance'] ?? 0.00 );
        $freq     = sanitize_text_field( wp_unslash( $_POST['pay_frequency'] ?? 'Monthly' ) );
        $eff_date = sanitize_text_field( wp_unslash( $_POST['effective_date'] ?? current_time( 'Y-m-d' ) ) );

        if ( $user_id > 0 ) {
            $user_data = array(
                'ID'           => $user_id,
                'user_email'   => $email,
                'display_name' => $fullname,
            );
            if ( ! empty( $new_pass ) ) {
                $user_data['user_pass'] = $new_pass;
            }
            wp_update_user( $user_data );

            $user = get_userdata( $user_id );
            if ( $user ) {
                $user->set_role( $role );
            }

            $existing_sal = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$t_salary} WHERE user_id = %d", $user_id ) );
            if ( $existing_sal ) {
                $wpdb->update(
                    $t_salary,
                    array(
                        'base_salary'    => $base_sal,
                        'allowance'      => $allow,
                        'pay_frequency'  => $freq,
                        'effective_date' => $eff_date,
                    ),
                    array( 'user_id' => $user_id ),
                    array( '%f', '%f', '%s', '%s' ),
                    array( '%d' )
                );
            } else {
                $wpdb->insert(
                    $t_salary,
                    array(
                        'user_id'        => $user_id,
                        'base_salary'    => $base_sal,
                        'allowance'      => $allow,
                        'pay_frequency'  => $freq,
                        'effective_date' => $eff_date,
                        'status'         => 'Active',
                    ),
                    array( '%d', '%f', '%f', '%s', '%s', '%s' )
                );
            }

            wp_safe_redirect( add_query_arg( array( 'view' => 'staff', 'msg' => 'staff_updated', 'tab' => 'list' ), admin_url( 'admin.php?page=ifs-pms' ) ) );
            exit;
        }
    }

    if ( $action === 'delete_staff' ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Administrator privileges required.', 'ozone-skypool' ) );
        }

        $user_id = absint( $_POST['user_id'] ?? 0 );
        if ( $user_id > 0 && $user_id !== get_current_user_id() ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user( $user_id );
            $wpdb->delete( $t_salary, array( 'user_id' => $user_id ), array( '%d' ) );
            wp_safe_redirect( add_query_arg( array( 'view' => 'staff', 'tab' => 'list' ), admin_url( 'admin.php?page=ifs-pms' ) ) );
            exit;
        }
    }

    // --- TICKETS CRUD ---

    if ( $action === 'issue_ticket' ) {
        if ( ! current_user_can( 'ozone_sell_tickets' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Insufficient permissions to issue tickets.', 'ozone-skypool' ) );
        }

        $name   = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        $phone  = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
        $amount = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0.00;
        $staff  = sanitize_text_field( wp_unslash( $_POST['staff'] ?? wp_get_current_user()->display_name ) );

        if ( ! empty( $name ) && ! empty( $phone ) ) {
            $customer = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$t_cust} WHERE phone = %s", $phone ) );
            if ( ! $customer ) {
                $wpdb->insert( $t_cust, array( 'name' => $name, 'phone' => $phone ) );
                $cust_id = $wpdb->insert_id;
            } else {
                $cust_id = $customer->id;
                $wpdb->update( $t_cust, array( 'name' => $name ), array( 'id' => $cust_id ) );
            }

            $code = 'OZONE-TKT-' . strtoupper( wp_generate_password( 6, false ) );
            $wpdb->insert( $t_tick, array(
                'ticket_code' => $code,
                'customer_id' => $cust_id,
                'amount'      => $amount,
                'sold_by'     => $staff,
                'status'      => 'Valid',
                'sold_at'     => current_time( 'mysql' ),
            ) );

            set_transient( 'ifs_pms_last_ticket_' . get_current_user_id(), array(
                'code'   => $code,
                'name'   => $name,
                'phone'  => $phone,
                'amount' => number_format( $amount, 2 ),
                'staff'  => $staff,
                'date'   => current_time( 'mysql' ),
            ), 60 );

            wp_safe_redirect( add_query_arg( array( 'view' => 'tickets', 'msg' => 'ticket_created', 'tab' => 'add' ), $base ) );
            exit;
        }
    }

    if ( $action === 'edit_ticket' ) {
        if ( ! current_user_can( 'ozone_sell_tickets' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Insufficient privileges.', 'ozone-skypool' ) );
        }

        $ticket_id = absint( $_POST['ticket_id'] ?? 0 );
        $name      = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        $phone     = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
        $amount    = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0.00;
        $status    = sanitize_key( $_POST['status'] ?? 'Valid' );

        if ( $ticket_id > 0 && ! empty( $name ) && ! empty( $phone ) ) {
            $ticket = $wpdb->get_row( $wpdb->prepare( "SELECT customer_id FROM {$t_tick} WHERE id = %d", $ticket_id ) );
            if ( $ticket ) {
                $wpdb->update( $t_cust, array( 'name' => $name, 'phone' => $phone ), array( 'id' => $ticket->customer_id ) );
                $wpdb->update( $t_tick, array( 'amount' => $amount, 'status' => $status ), array( 'id' => $ticket_id ) );
            }
            wp_safe_redirect( add_query_arg( array( 'view' => 'tickets', 'msg' => 'ticket_updated', 'tab' => 'list' ), $base ) );
            exit;
        }
    }

    if ( $action === 'delete_ticket' ) {
        if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'ozone_manage_settings' ) ) {
            wp_die( esc_html__( 'Forbidden: Only managers can delete ticket records.', 'ozone-skypool' ) );
        }

        $ticket_id = absint( $_POST['ticket_id'] ?? 0 );
        if ( $ticket_id > 0 ) {
            $wpdb->delete( $t_tick, array( 'id' => $ticket_id ), array( '%d' ) );
            wp_safe_redirect( add_query_arg( array( 'view' => 'tickets', 'msg' => 'ticket_deleted', 'tab' => 'list' ), $base ) );
            exit;
        }
    }

    // --- CUSTOMERS CRUD ---

    if ( $action === 'edit_customer' ) {
        if ( ! current_user_can( 'ozone_manage_patrons' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Insufficient privileges to update patron records.', 'ozone-skypool' ) );
        }

        $cust_id = absint( $_POST['customer_id'] ?? 0 );
        $name    = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        $phone   = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );

        if ( $cust_id > 0 && ! empty( $name ) && ! empty( $phone ) ) {
            $wpdb->update(
                $t_cust,
                array( 'name' => $name, 'phone' => $phone ),
                array( 'id' => $cust_id ),
                array( '%s', '%s' ),
                array( '%d' )
            );

            wp_safe_redirect( add_query_arg( array( 'view' => 'customers', 'msg' => 'customer_updated', 'tab' => 'list' ), $base ) );
            exit;
        }
    }

    if ( $action === 'delete_customer' ) {
        if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'ozone_manage_settings' ) ) {
            wp_die( esc_html__( 'Forbidden: Only managers can delete customer profiles.', 'ozone-skypool' ) );
        }

        $cust_id = absint( $_POST['customer_id'] ?? 0 );
        if ( $cust_id > 0 ) {
            $wpdb->delete( $t_cust, array( 'id' => $cust_id ), array( '%d' ) );
            wp_safe_redirect( add_query_arg( array( 'view' => 'customers', 'tab' => 'list' ), $base ) );
            exit;
        }
    }

    // --- MEMBERSHIP CRUD ---

    if ( $action === 'create_membership' ) {
        if ( ! current_user_can( 'ozone_manage_patrons' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Insufficient permissions to enroll members.', 'ozone-skypool' ) );
        }

        $name     = sanitize_text_field( wp_unslash( $_POST['m_name'] ?? '' ) );
        $phone    = sanitize_text_field( wp_unslash( $_POST['m_phone'] ?? '' ) );
        $plan     = sanitize_text_field( wp_unslash( $_POST['plan_type'] ?? 'Monthly Sky Pass' ) );
        $amount   = isset( $_POST['m_amount'] ) ? floatval( $_POST['m_amount'] ) : 0.00;
        $duration = isset( $_POST['duration_months'] ) ? absint( $_POST['duration_months'] ) : 1;

        $mem_code = 'OZONE-MEM-' . strtoupper( wp_generate_password( 6, false ) );
        $start    = current_time( 'Y-m-d' );
        $expiry   = gmdate( 'Y-m-d', strtotime( "+{$duration} months", strtotime( $start ) ) );

        $wpdb->insert( $t_members, array(
            'member_code' => $mem_code,
            'name'        => $name,
            'phone'       => $phone,
            'plan_type'   => $plan,
            'amount'      => $amount,
            'start_date'  => $start,
            'expiry_date' => $expiry,
            'status'      => 'Active',
        ) );

        wp_safe_redirect( add_query_arg( array( 'view' => 'membership', 'msg' => 'membership_enrolled', 'tab' => 'list' ), $base ) );
        exit;
    }

    if ( $action === 'edit_membership' ) {
        if ( ! current_user_can( 'ozone_manage_patrons' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Insufficient privileges.', 'ozone-skypool' ) );
        }

        $member_id = absint( $_POST['member_id'] ?? 0 );
        $name      = sanitize_text_field( wp_unslash( $_POST['m_name'] ?? '' ) );
        $phone     = sanitize_text_field( wp_unslash( $_POST['m_phone'] ?? '' ) );
        $plan      = sanitize_text_field( wp_unslash( $_POST['plan_type'] ?? '' ) );
        $amount    = isset( $_POST['m_amount'] ) ? floatval( $_POST['m_amount'] ) : 0.00;
        $expiry    = sanitize_text_field( wp_unslash( $_POST['expiry_date'] ?? '' ) );
        $status    = sanitize_key( $_POST['status'] ?? 'Active' );

        if ( $member_id > 0 && ! empty( $name ) && ! empty( $phone ) ) {
            $wpdb->update(
                $t_members,
                array(
                    'name'        => $name,
                    'phone'       => $phone,
                    'plan_type'   => $plan,
                    'amount'      => $amount,
                    'expiry_date' => $expiry,
                    'status'      => $status,
                ),
                array( 'id' => $member_id ),
                array( '%s', '%s', '%s', '%f', '%s', '%s' ),
                array( '%d' )
            );

            wp_safe_redirect( add_query_arg( array( 'view' => 'membership', 'msg' => 'membership_enrolled', 'tab' => 'list' ), $base ) );
            exit;
        }
    }

    if ( $action === 'delete_membership' ) {
        if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'ozone_manage_settings' ) ) {
            wp_die( esc_html__( 'Forbidden: Only managers can delete member accounts.', 'ozone-skypool' ) );
        }

        $member_id = absint( $_POST['member_id'] ?? 0 );
        if ( $member_id > 0 ) {
            $wpdb->delete( $t_members, array( 'id' => $member_id ), array( '%d' ) );
            wp_safe_redirect( add_query_arg( array( 'view' => 'membership', 'tab' => 'list' ), $base ) );
            exit;
        }
    }

    // --- EXPENSES CRUD ---

    if ( $action === 'add_expense' ) {
        if ( ! current_user_can( 'ozone_manage_expenses' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Only managers can log expenses.', 'ozone-skypool' ) );
        }

        $wpdb->insert( $t_expense, array(
            'title'        => sanitize_text_field( wp_unslash( $_POST['expense_title'] ?? '' ) ),
            'category'     => sanitize_text_field( wp_unslash( $_POST['expense_cat'] ?? 'Operations' ) ),
            'amount'       => isset( $_POST['expense_amount'] ) ? floatval( $_POST['expense_amount'] ) : 0.00,
            'expense_date' => sanitize_text_field( wp_unslash( $_POST['expense_date'] ?? current_time( 'Y-m-d' ) ) ),
            'added_by'     => wp_get_current_user()->display_name,
        ) );

        wp_safe_redirect( add_query_arg( array( 'view' => 'expenses', 'msg' => 'expense_logged', 'tab' => 'list' ), $base ) );
        exit;
    }

    if ( $action === 'edit_expense' ) {
        if ( ! current_user_can( 'ozone_manage_expenses' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Insufficient privileges.', 'ozone-skypool' ) );
        }

        $expense_id = absint( $_POST['expense_id'] ?? 0 );
        $title      = sanitize_text_field( wp_unslash( $_POST['expense_title'] ?? '' ) );
        $category   = sanitize_text_field( wp_unslash( $_POST['expense_cat'] ?? 'Operations' ) );
        $amount     = isset( $_POST['expense_amount'] ) ? floatval( $_POST['expense_amount'] ) : 0.00;
        $date       = sanitize_text_field( wp_unslash( $_POST['expense_date'] ?? current_time( 'Y-m-d' ) ) );

        if ( $expense_id > 0 && ! empty( $title ) && $amount > 0 ) {
            $wpdb->update(
                $t_expense,
                array(
                    'title'        => $title,
                    'category'     => $category,
                    'amount'       => $amount,
                    'expense_date' => $date,
                ),
                array( 'id' => $expense_id ),
                array( '%s', '%s', '%f', '%s' ),
                array( '%d' )
            );

            wp_safe_redirect( add_query_arg( array( 'view' => 'expenses', 'msg' => 'expense_logged', 'tab' => 'list' ), $base ) );
            exit;
        }
    }

    if ( $action === 'delete_expense' ) {
        if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'ozone_manage_settings' ) ) {
            wp_die( esc_html__( 'Forbidden: Only managers can delete expense logs.', 'ozone-skypool' ) );
        }

        $expense_id = absint( $_POST['expense_id'] ?? 0 );
        if ( $expense_id > 0 ) {
            $wpdb->delete( $t_expense, array( 'id' => $expense_id ), array( '%d' ) );
            wp_safe_redirect( add_query_arg( array( 'view' => 'expenses', 'tab' => 'list' ), $base ) );
            exit;
        }
    }

    // --- MASTER SETTINGS ---

    if ( $action === 'save_settings' ) {
        if ( ! current_user_can( 'ozone_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Insufficient administrative privileges.', 'ozone-skypool' ) );
        }

        $active_tab = sanitize_key( $_POST['ifs_pms_active_tab'] ?? 'pos' );

        update_option( 'ifs_pms_business_name', sanitize_text_field( wp_unslash( $_POST['ifs_pms_business_name'] ?? '' ) ) );
        update_option( 'ifs_pms_ticket_price', floatval( $_POST['ifs_pms_ticket_price'] ?? 0.00 ) );
        update_option( 'ifs_pms_currency', sanitize_text_field( wp_unslash( $_POST['ifs_pms_currency'] ?? 'BDT' ) ) );
        update_option( 'ifs_pms_phone', sanitize_text_field( wp_unslash( $_POST['ifs_pms_phone'] ?? '' ) ) );
        update_option( 'ifs_pms_address', sanitize_text_field( wp_unslash( $_POST['ifs_pms_address'] ?? '' ) ) );
        update_option( 'ifs_pms_max_capacity', absint( $_POST['ifs_pms_max_capacity'] ?? 80 ) );
        update_option( 'ifs_pms_receipt_note', sanitize_textarea_field( wp_unslash( $_POST['ifs_pms_receipt_note'] ?? '' ) ) );
        update_option( 'ifs_pms_logo_url', esc_url_raw( $_POST['ifs_pms_logo_url'] ?? '' ) );

        update_option( 'ifs_pms_enable_strict_scan', isset( $_POST['ifs_pms_enable_strict_scan'] ) ? '1' : '0' );
        update_option( 'ifs_pms_auto_expire_passes', isset( $_POST['ifs_pms_auto_expire_passes'] ) ? '1' : '0' );
        update_option( 'ifs_pms_thermal_paper_width', sanitize_text_field( wp_unslash( $_POST['ifs_pms_thermal_paper_width'] ?? '80mm' ) ) );
        update_option( 'ifs_pms_print_auto_popup', isset( $_POST['ifs_pms_print_auto_popup'] ) ? '1' : '0' );
        update_option( 'ifs_pms_enable_audio_buzzer', isset( $_POST['ifs_pms_enable_audio_buzzer'] ) ? '1' : '0' );
        update_option( 'ifs_pms_enable_amenities', isset( $_POST['ifs_pms_enable_amenities'] ) ? '1' : '0' );
        update_option( 'ifs_pms_pool_status', sanitize_text_field( wp_unslash( $_POST['ifs_pms_pool_status'] ?? 'open' ) ) );

        $tenders = isset( $_POST['ifs_pms_enabled_tenders'] ) && is_array( $_POST['ifs_pms_enabled_tenders'] )
            ? array_map( 'sanitize_text_field', wp_unslash( $_POST['ifs_pms_enabled_tenders'] ) )
            : array( 'Cash', 'bKash / Nagad', 'Card POS', 'Complimentary' );
        update_option( 'ifs_pms_enabled_tenders', $tenders );

        $cash_presets = sanitize_text_field( wp_unslash( $_POST['ifs_pms_quick_cash_presets'] ?? '500, 1000' ) );
        update_option( 'ifs_pms_quick_cash_presets', $cash_presets );

        // Pricing Tiers with Age Groups
        if ( isset( $_POST['ifs_pricing_tier_name'] ) && is_array( $_POST['ifs_pricing_tier_name'] ) ) {
            $tiers    = array();
            $names    = $_POST['ifs_pricing_tier_name'];
            $ages     = $_POST['ifs_pricing_tier_age'] ?? array();
            $prices   = $_POST['ifs_pricing_tier_price'];
            $features = $_POST['ifs_pricing_tier_features'];

            for ( $i = 0; $i < count( $names ); $i++ ) {
                if ( ! empty( $names[ $i ] ) ) {
                    $tiers[] = array(
                        'name'      => sanitize_text_field( wp_unslash( $names[ $i ] ) ),
                        'age_group' => sanitize_text_field( wp_unslash( $ages[ $i ] ?? 'Adult' ) ),
                        'price'     => floatval( $prices[ $i ] ),
                        'features'  => sanitize_text_field( wp_unslash( $features[ $i ] ) ),
                    );
                }
            }
            update_option( 'ifs_pms_pricing_tiers', $tiers );
        }

        if ( isset( $_POST['ifs_addon_name'] ) && is_array( $_POST['ifs_addon_name'] ) ) {
            $addons   = array();
            $a_names  = $_POST['ifs_addon_name'];
            $a_prices = $_POST['ifs_addon_price'];

            for ( $j = 0; $j < count( $a_names ); $j++ ) {
                if ( ! empty( $a_names[ $j ] ) ) {
                    $addons[] = array(
                        'name'  => sanitize_text_field( wp_unslash( $a_names[ $j ] ) ),
                        'price' => floatval( $a_prices[ $j ] ),
                    );
                }
            }
            update_option( 'ifs_pms_amenity_addons', $addons );
        }

        // Day-Wise Operating Hours
        $days_of_week = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
        $schedule = array();
        foreach ( $days_of_week as $day ) {
            $schedule[ $day ] = array(
                'status' => sanitize_key( $_POST['ifs_pms_schedule_' . $day . '_status'] ?? 'open' ),
                'open'   => sanitize_text_field( wp_unslash( $_POST['ifs_pms_schedule_' . $day . '_open'] ?? '10:00' ) ),
                'close'  => sanitize_text_field( wp_unslash( $_POST['ifs_pms_schedule_' . $day . '_close'] ?? '23:00' ) ),
            );
        }
        update_option( 'ifs_pms_weekly_schedule', $schedule );

        wp_safe_redirect( add_query_arg( array( 'view' => 'settings', 'msg' => 'settings_saved', 'tab' => $active_tab ), $base ) );
        exit;
    }
}

/**
 * 6. Main Terminal Canvas Shell
 */
function ifs_pms_render_application() {
    if ( ! current_user_can( 'ozone_access_terminal' ) && ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized user role.', 'ozone-skypool' ) );
    }

    $is_admin = current_user_can( 'manage_options' ) || current_user_can( 'ozone_manage_settings' );
    $admin_only_views = array( 'staff', 'expenses', 'reports', 'settings' );

    $current_view = sanitize_key( $_GET['view'] ?? 'dashboard' );

    if ( ! $is_admin && in_array( $current_view, $admin_only_views, true ) ) {
        $current_view = 'dashboard';
    }

    $base_url = admin_url( 'admin.php?page=ifs-pms' );

    // Flash Messages
    $msg_code  = sanitize_key( $_GET['msg'] ?? '' );
    $flash_msg = '';
    if ( $msg_code === 'ticket_created' ) {
        $flash_msg = __( 'Single Admission Pass Created Successfully.', 'ozone-skypool' );
    } elseif ( $msg_code === 'ticket_updated' ) {
        $flash_msg = __( 'Ticket Record Updated.', 'ozone-skypool' );
    } elseif ( $msg_code === 'ticket_deleted' ) {
        $flash_msg = __( 'Ticket Removed Permanently.', 'ozone-skypool' );
    } elseif ( $msg_code === 'customer_updated' ) {
        $flash_msg = __( 'Patron Profile Updated Successfully.', 'ozone-skypool' );
    } elseif ( $msg_code === 'membership_enrolled' ) {
        $flash_msg = __( 'Member Subscription Record Synchronized.', 'ozone-skypool' );
    } elseif ( $msg_code === 'expense_logged' ) {
        $flash_msg = __( 'Operational Outflow Logged.', 'ozone-skypool' );
    } elseif ( $msg_code === 'staff_created' ) {
        $flash_msg = __( 'Operator Account & Compensation Provisioned.', 'ozone-skypool' );
    } elseif ( $msg_code === 'staff_updated' ) {
        $flash_msg = __( 'Operator Profile Updated.', 'ozone-skypool' );
    } elseif ( $msg_code === 'settings_saved' ) {
        $flash_msg = __( 'Master Configuration Saved.', 'ozone-skypool' );
    }

    $b_name       = get_option( 'ifs_pms_business_name', 'Ozone Restaurant & Skypool' );
    $address      = get_option( 'ifs_pms_address', '' );
    $phone        = get_option( 'ifs_pms_phone', '' );
    $receipt_note = get_option( 'ifs_pms_receipt_note', '' );
    $logo_url     = get_option( 'ifs_pms_logo_url', '' );
    ?>

    <div class="ifs-pms-shell" id="ifsPmsAppShell">
        <aside class="ifs-pms-aside">
            <div>
                <div class="ifs-pms-brand">
                    <div>
                        <div style="font-size: 15px; font-weight: 800; color: var(--ifs-text-primary);"><?php esc_html_e( 'Ozone Skypool', 'ozone-skypool' ); ?></div>
                        <div style="font-size: 10px; color: var(--ifs-accent); font-weight: 700; letter-spacing: 0.8px;">
                            <?php echo $is_admin ? esc_html__( 'MANAGER TERMINAL', 'ozone-skypool' ) : esc_html__( 'CASHIER DESK', 'ozone-skypool' ); ?>
                        </div>
                    </div>
                </div>

                <div class="ifs-pms-nav-group-title"><?php esc_html_e( 'Front Desk', 'ozone-skypool' ); ?></div>
                <ul class="ifs-pms-nav">
                    <li class="ifs-pms-nav-item <?php echo ( $current_view === 'dashboard' ) ? 'ifs-pms-active' : ''; ?>">
                        <a class="ifs-pms-nav-link" href="<?php echo esc_url( $base_url . '&view=dashboard' ); ?>"><i class="fa-solid fa-chart-pie"></i> <?php esc_html_e( 'Overview', 'ozone-skypool' ); ?></a>
                    </li>
                    <li class="ifs-pms-nav-item <?php echo ( $current_view === 'tickets' ) ? 'ifs-pms-active' : ''; ?>">
                        <a class="ifs-pms-nav-link" href="<?php echo esc_url( $base_url . '&view=tickets' ); ?>"><i class="fa-solid fa-ticket"></i> <?php esc_html_e( 'Tickets POS', 'ozone-skypool' ); ?></a>
                    </li>
                    <li class="ifs-pms-nav-item <?php echo ( $current_view === 'scanner' ) ? 'ifs-pms-active' : ''; ?>">
                        <a class="ifs-pms-nav-link" href="<?php echo esc_url( $base_url . '&view=scanner' ); ?>"><i class="fa-solid fa-qrcode"></i> <?php esc_html_e( 'Scan Pass', 'ozone-skypool' ); ?></a>
                    </li>
                    <li class="ifs-pms-nav-item <?php echo ( $current_view === 'customers' ) ? 'ifs-pms-active' : ''; ?>">
                        <a class="ifs-pms-nav-link" href="<?php echo esc_url( $base_url . '&view=customers' ); ?>"><i class="fa-solid fa-users"></i> <?php esc_html_e( 'Customers', 'ozone-skypool' ); ?></a>
                    </li>
                    <li class="ifs-pms-nav-item <?php echo ( $current_view === 'membership' ) ? 'ifs-pms-active' : ''; ?>">
                        <a class="ifs-pms-nav-link" href="<?php echo esc_url( $base_url . '&view=membership' ); ?>"><i class="fa-solid fa-id-card"></i> <?php esc_html_e( 'Members', 'ozone-skypool' ); ?></a>
                    </li>
                </ul>

                <?php if ( $is_admin ) : ?>
                    <div class="ifs-pms-nav-group-title"><?php esc_html_e( 'Management & Ledger', 'ozone-skypool' ); ?></div>
                    <ul class="ifs-pms-nav">
                        <li class="ifs-pms-nav-item <?php echo ( $current_view === 'staff' ) ? 'ifs-pms-active' : ''; ?>">
                            <a class="ifs-pms-nav-link" href="<?php echo esc_url( $base_url . '&view=staff' ); ?>"><i class="fa-solid fa-user-shield"></i> <?php esc_html_e( 'Staff Sales', 'ozone-skypool' ); ?></a>
                        </li>
                        <li class="ifs-pms-nav-item <?php echo ( $current_view === 'expenses' ) ? 'ifs-pms-active' : ''; ?>">
                            <a class="ifs-pms-nav-link" href="<?php echo esc_url( $base_url . '&view=expenses' ); ?>"><i class="fa-solid fa-receipt"></i> <?php esc_html_e( 'Expenses', 'ozone-skypool' ); ?></a>
                        </li>
                        <li class="ifs-pms-nav-item <?php echo ( $current_view === 'reports' ) ? 'ifs-pms-active' : ''; ?>">
                            <a class="ifs-pms-nav-link" href="<?php echo esc_url( $base_url . '&view=reports' ); ?>"><i class="fa-solid fa-file-invoice-dollar"></i> <?php esc_html_e( 'Reports (P&L)', 'ozone-skypool' ); ?></a>
                        </li>
                    </ul>

                    <div class="ifs-pms-nav-group-title"><?php esc_html_e( 'Administration', 'ozone-skypool' ); ?></div>
                    <ul class="ifs-pms-nav">
                        <li class="ifs-pms-nav-item <?php echo ( $current_view === 'settings' ) ? 'ifs-pms-active' : ''; ?>">
                            <a class="ifs-pms-nav-link" href="<?php echo esc_url( $base_url . '&view=settings' ); ?>"><i class="fa-solid fa-sliders"></i> <?php esc_html_e( 'Settings', 'ozone-skypool' ); ?></a>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>

            <div>
                <div class="ifs-pms-theme-toggle-wrap">
                    <span style="font-size: 11px; font-weight: 700; color: var(--ifs-text-tertiary);"><i class="fa-solid fa-circle-half-stroke"></i> <?php esc_html_e( 'Mode', 'ozone-skypool' ); ?></span>
                    <div style="display: flex; gap: 4px;">
                        <button type="button" class="ifs-pms-theme-btn" id="ifsThemeLightBtn" onclick="ifsPmsSetTheme('light')">
                            <i class="fa-solid fa-sun"></i> <?php esc_html_e( 'Light', 'ozone-skypool' ); ?>
                        </button>
                        <button type="button" class="ifs-pms-theme-btn" id="ifsThemeDarkBtn" onclick="ifsPmsSetTheme('dark')">
                            <i class="fa-solid fa-moon"></i> <?php esc_html_e( 'Dark', 'ozone-skypool' ); ?>
                        </button>
                    </div>
                </div>

                <div class="ifs-pms-operator-card">
                    <div class="ifs-pms-operator-avatar"><?php echo esc_html( strtoupper( substr( wp_get_current_user()->display_name, 0, 1 ) ) ); ?></div>
                    <div style="overflow: hidden;">
                        <div style="font-size: 13px; font-weight: 700; white-space: nowrap; text-overflow: ellipsis; overflow: hidden; color: var(--ifs-text-primary);">
                            <?php echo esc_html( wp_get_current_user()->display_name ); ?>
                        </div>
                        <div style="font-size: 11px; color: var(--ifs-text-tertiary);">
                            <?php echo $is_admin ? esc_html__( 'Facility Manager', 'ozone-skypool' ) : esc_html__( 'Active Cashier', 'ozone-skypool' ); ?>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <main class="ifs-pms-canvas">
            <header class="ifs-pms-header">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <?php if ( ! empty( $logo_url ) ) : ?>
                        <img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo" style="max-height: 44px; width: auto; border-radius: 10px; border: 1px solid var(--ifs-border-subtle);">
                    <?php endif; ?>
                    <div>
                        <h1><?php echo esc_html( $b_name ); ?></h1>
                        <p><span class="ifs-pms-pulse-dot"></span> <?php esc_html_e( 'Terminal Online', 'ozone-skypool' ); ?> &bull; <?php echo esc_html( current_time( 'l, F j, Y' ) ); ?></p>
                    </div>
                </div>
            </header>

            <?php if ( ! empty( $flash_msg ) ) : ?>
                <div style="background: var(--ifs-success-soft); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--ifs-success); padding: 12px 18px; border-radius: 8px; margin-bottom: 22px; font-weight: 600; font-size: 13.5px; display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo esc_html( $flash_msg ); ?>
                </div>
            <?php endif; ?>

            <?php
            $view_path = IFS_PMS_PATH . 'views/' . $current_view . '.php';

            if ( file_exists( $view_path ) ) {
                include $view_path;
            } else {
                echo '<div class="ifs-pms-panel"><p>' . esc_html__( 'Selected dashboard view was not found.', 'ozone-skypool' ) . '</p></div>';
            }
            ?>
        </main>
    </div>

    <!-- Global Thermal Receipt Modal -->
    <div id="ifs-pms-thermal-modal">
        <div class="ifs-pms-receipt-card">
            <?php if ( ! empty( $logo_url ) ) : ?>
                <div style="display: flex; justify-content: center; margin-bottom: 8px;">
                    <img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo" style="max-height: 36px; width: auto;">
                </div>
            <?php endif; ?>
            <h2 style="margin: 0; font-size: 16px; font-weight: 800;"><?php echo esc_html( $b_name ); ?></h2>
            <p style="margin: 4px 0 0 0; font-size: 11px; color: #475569;">
                <?php echo esc_html( $address ); ?><br>
                <?php echo esc_html__( 'Tel:', 'ozone-skypool' ) . ' ' . esc_html( $phone ); ?>
            </p>
            <div class="ifs-pms-dashed-sep"></div>
            <div id="ifs-pms-thermal-qr" style="display: flex; justify-content: center; margin: 12px 0;"></div>
            <div id="ifs-pms-slip-code" style="font-size: 16px; font-weight: 800; letter-spacing: 1.5px;"></div>
            <div class="ifs-pms-dashed-sep"></div>
            <div id="ifs-pms-slip-meta" style="font-size: 11.5px; line-height: 1.6; text-align: left;"></div>
            <div class="ifs-pms-dashed-sep"></div>
            <p style="font-size: 9.5px; color: #64748b; margin: 0;"><?php echo esc_html( $receipt_note ); ?></p>
            <div class="no-print" style="margin-top: 16px; display: flex; gap: 8px;">
                <button onclick="window.print()" class="oz-btn oz-btn-primary oz-btn-block"><i class="fa-solid fa-print"></i> <?php esc_html_e( 'Print', 'ozone-skypool' ); ?></button>
                <button onclick="ifs_pms_close_receipt()" class="oz-btn oz-btn-secondary oz-btn-block"><?php esc_html_e( 'Close', 'ozone-skypool' ); ?></button>
            </div>
        </div>
    </div>
    <?php
}

/**
 * 7. Secure Pass Verification Endpoint (Turnstile / Scanner)
 */
add_action( 'wp_ajax_ifs_pms_verify_pass_action', 'ifs_pms_verify_pass_callback' );

function ifs_pms_verify_pass_callback() {
    check_ajax_referer( 'ifs_pms_security_token', 'security' );

    if ( ! current_user_can( 'ozone_scan_passes' ) && ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Forbidden access: Insufficient privileges.', 'ozone-skypool' ) ), 403 );
    }

    global $wpdb;
    $t_tick = $wpdb->prefix . 'ifs_pms_tickets';
    $t_cust = $wpdb->prefix . 'ifs_pms_customers';
    $code   = sanitize_text_field( wp_unslash( $_POST['ticket_code'] ?? '' ) );

    if ( empty( $code ) ) {
        wp_send_json_error( array( 'message' => __( 'Missing pass identification parameter.', 'ozone-skypool' ) ) );
    }

    $ticket = $wpdb->get_row( $wpdb->prepare(
        "SELECT t.*, c.name as customer_name 
         FROM {$t_tick} t 
         LEFT JOIN {$t_cust} c ON t.customer_id = c.id 
         WHERE t.ticket_code = %s",
        $code
    ) );

    if ( ! $ticket ) {
        wp_send_json_error( array( 'message' => __( 'Unrecognized / Forged Pass ID.', 'ozone-skypool' ) ) );
    }

    if ( $ticket->status === 'Used' ) {
        wp_send_json_error( array( 'message' => sprintf( __( 'Pass already redeemed at %s by %s', 'ozone-skypool' ), $ticket->scanned_at, $ticket->scanned_by ) ) );
    }

    if ( $ticket->status === 'Cancelled' ) {
        wp_send_json_error( array( 'message' => __( 'Pass has been voided or refunded.', 'ozone-skypool' ) ) );
    }

    $now   = current_time( 'mysql' );
    $staff = wp_get_current_user()->display_name;

    $updated = $wpdb->update(
        $t_tick,
        array(
            'status'     => 'Used',
            'scanned_at' => $now,
            'scanned_by' => $staff,
        ),
        array( 'ticket_code' => $code ),
        array( '%s', '%s', '%s' ),
        array( '%s' )
    );

    if ( false === $updated ) {
        wp_send_json_error( array( 'message' => __( 'Database lock error while updating pass.', 'ozone-skypool' ) ) );
    }

    wp_send_json_success( array(
        'message'       => __( 'Access Granted: Valid Admission Pass', 'ozone-skypool' ),
        'ticket_code'   => $code,
        'customer_name' => ! empty( $ticket->customer_name ) ? esc_html( $ticket->customer_name ) : __( 'Walk-in Guest', 'ozone-skypool' ),
        'scanned_at'    => $now,
    ) );
}

/**
 * 8. Secure CSV Exporter Callback (Managers Only)
 */
add_action( 'wp_ajax_ifs_pms_export_csv_action', 'ifs_pms_export_csv_action_callback' );

function ifs_pms_export_csv_action_callback() {
    check_ajax_referer( 'ifs_pms_security_token', 'security' );

    if ( ! current_user_can( 'ozone_view_finances' ) && ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'ozone-skypool' ) );
    }

    global $wpdb;
    $t_tick = $wpdb->prefix . 'ifs_pms_tickets';
    $t_cust = $wpdb->prefix . 'ifs_pms_customers';

    $tickets = $wpdb->get_results(
        "SELECT t.ticket_code, c.name as customer_name, c.phone as customer_phone, t.amount, t.sold_by, t.status, t.sold_at, t.scanned_at, t.scanned_by 
         FROM {$t_tick} t 
         LEFT JOIN {$t_cust} c ON t.customer_id = c.id 
         ORDER BY t.id DESC",
        ARRAY_A
    );

    nocache_headers();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=ozone_skypool_ledger_' . gmdate( 'Y-m-d' ) . '.csv' );

    $output = fopen( 'php://output', 'w' );
    fputcsv( $output, array( 'Ticket Code', 'Patron Name', 'Phone', 'Amount', 'Sold By', 'Status', 'Sold At', 'Scanned At', 'Scanned By' ) );

    if ( ! empty( $tickets ) ) {
        foreach ( $tickets as $row ) {
            fputcsv( $output, array(
                $row['ticket_code'],
                $row['customer_name'],
                $row['customer_phone'],
                $row['amount'],
                $row['sold_by'],
                $row['status'],
                $row['sold_at'],
                $row['scanned_at'],
                $row['scanned_by'],
            ) );
        }
    }

    fclose( $output );
    exit;
}