<?php
/**
 * Plugin Name:        Ozone Skypool Management System (Ozone Skypool OS)
 * Plugin URI:         https://ozoneskypool.com/management-system
 * Description:        Enterprise Aquatic POS, QR Gate Turnstile Control, RFID/Pass Ledger & Financial Operating System with Role-Based Access Control and Native SVG UI.
 * Version:            7.3.1
 * Author:             Ozone Tech
 * Author URI:         https://ozoneskypool.com
 * License:            GPL-2.0-or-later
 * License URI:        https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:        swimming-pool-manager
 * Domain Path:        /languages
 * Requires at least:  6.0
 * Requires PHP:       7.4
 *
 * @package SwimmingPoolManager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'IFS_PMS_VERSION', '7.3.1' );
define( 'IFS_PMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'IFS_PMS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load plugin text domain.
 */
add_action( 'plugins_loaded', 'ifs_pms_load_textdomain' );
function ifs_pms_load_textdomain() {
    load_plugin_textdomain( 'swimming-pool-manager', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Native SVG Icon Helper Engine (Zero External Dependencies)
 *
 * @param string      $icon_name Identifier of the icon.
 * @param string      $class     Optional CSS classes.
 * @param int|string  $size      Optional width/height in pixels.
 * @return string HTML markup for the SVG.
 */
function ifs_pms_get_svg( $icon_name, $class = '', $size = 16 ) {
    $icons = array(
        'overview'     => '<path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>',
        'ticket'       => '<path d="M20 6h-2.18c.11-.31.18-.65.18-1a2.996 2.996 0 0 0-3-3c-1.11 0-2.08.6-2.58 1.5C11.92 2.6 10.95 2 9.84 2a2.996 2.996 0 0 0-3 1c0 .35.07.69.18 1H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm-1 12H5V8h14v10z"/>',
        'qrcode'       => '<path d="M2 2h8v8H2V2zm2 4h4V4H4v2zm8-4h8v8h-8V2zm2 4h4V4h-4v2zM2 12h8v8H2v-8zm2 4h4v-4H4v4zm10-4h2v2h-2v-2zm4 0h2v2h-2v-2zm-4 4h2v2h-2v-2zm4 0h2v2h-2v-2zm-4 4h2v2h-2v-2zm4 0h2v2h-2v-2z"/>',
        'tower'        => '<path d="M12 2L2 7v2h20V7L12 2zm0 3.25L18.5 8H5.5L12 5.25zM6 10v10h2V10H6zm4 0v10h4V10h-4zm6 0v10h2V10h-2zm-8 12h8v2H8v-2z"/>',
        'users'        => '<path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>',
        'card'         => '<path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>',
        'shield'       => '<path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>',
        'receipt'      => '<path d="M18 17h-6v-2h6v2zm0-4h-6v-2h6v2zm0-4h-6V7h6v2zM16 2H6c-1.1 0-2 .9-2 2v16l3-1.5L10 20l3-1.5L16 20V4c0-1.1-.9-2-2-2zm0 15.5l-3-1.5-3 1.5V4h6v13.5z"/>',
        'sliders'      => '<path d="M3 17v2h6v-2H3zM3 5v2h10V5H3zm10 16v-2h8v-2h-8v-2h-2v6h2zM7 9v2H3v2h4v2h2V9H7zm14 4h-8v2h8v-2zm0-6h-4V5h-2v6h6V7z"/>',
        'clock'        => '<path d="M11.99 2C6.47 2 2 6.47 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>',
        'print'        => '<path d="M19 8h-1V3H6v5H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zM8 5h8v3H8V5zm8 14H8v-4h8v4zm4-4h-2v-2H6v2H4v-4c0-.55.45-1 1-1h14c.55 0 1 .45 1 1v4z"/>',
        'sun'          => '<path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1z"/>',
        'moon'         => '<path d="M12.3 2c-4.97 0-9 4.03-9 9 0 2.12.74 4.07 1.97 5.61L4.35 19.4c-.39.39-.39 1.02 0 1.41.39.39 1.02.39 1.41 0l2.79-2.79C10.08 18.59 11.16 19 12.3 19c4.97 0 9-4.03 9-9s-4.03-9-9-9zm0 15c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6z"/>',
        'check'        => '<path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>',
    );

    $path       = $icons[ $icon_name ] ?? '<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>';
    $class_attr = ! empty( $class ) ? ' class="' . esc_attr( $class ) . '"' : '';

    return sprintf(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="%d" height="%d" fill="currentColor"%s>%s</svg>',
        intval( $size ),
        intval( $size ),
        $class_attr,
        $path
    );
}

/**
 * 1. Database Installation & Dynamic Migration Handler
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
        PRIMARY KEY  (id),
        UNIQUE KEY phone (phone)
    ) {$charset_collate};

    CREATE TABLE {$t_tick} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        ticket_code varchar(50) NOT NULL,
        customer_id mediumint(9) NOT NULL,
        guest_type varchar(20) DEFAULT 'customer' NOT NULL,
        package_details text DEFAULT NULL,
        duration_hours int(3) DEFAULT 1 NOT NULL,
        payment_method varchar(30) DEFAULT 'Cash' NOT NULL,
        room_no varchar(20) DEFAULT NULL,
        amount decimal(10,2) NOT NULL DEFAULT 0.00,
        sold_by varchar(100) NOT NULL,
        scanned_by varchar(100) DEFAULT NULL,
        status varchar(20) DEFAULT 'Valid' NOT NULL,
        sold_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        scanned_at datetime NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY ticket_code (ticket_code),
        KEY idx_customer (customer_id),
        KEY idx_guest_type (guest_type),
        KEY idx_payment (payment_method),
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
        profile_image varchar(255) DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
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
        PRIMARY KEY  (id),
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
        PRIMARY KEY  (id),
        KEY idx_user (user_id)
    ) {$charset_collate};";

    dbDelta( $sql );

    $default_options = array(
        'ifs_pms_business_name'     => '',
        'ifs_pms_ticket_price'      => '0.00',
        'ifs_pms_currency'          => '',
        'ifs_pms_phone'             => '',
        'ifs_pms_address'           => '',
        'ifs_pms_receipt_note'      => '',
        'ifs_pms_max_capacity'      => '',
        'ifs_pms_logo_url'          => '',
        'ifs_pms_pool_status'       => '',
        'ifs_pms_enable_amenities'    => '0',
        'ifs_pms_pricing_tiers'       => array(),
        'ifs_pms_amenity_addons'      => array(),
        'ifs_pms_weekly_schedule'     => array(),
        'ifs_pms_enabled_tenders'     => array( 'Cash' ),
        'ifs_pms_quick_cash_presets'  => '100, 200, 500, 1000',
        'ifs_pms_thermal_paper_width' => '80mm',
        'ifs_pms_enable_strict_scan'  => '1',
        'ifs_pms_auto_expire_passes'  => '1',
        'ifs_pms_enable_audio_buzzer' => '1',
        'ifs_pms_version'             => IFS_PMS_VERSION,
    );

    foreach ( $default_options as $key => $val ) {
        if ( false === get_option( $key ) ) {
            add_option( $key, $val );
        }
    }
}

/**
 * Migration Runner: Runs exclusively when the database version changes.
 */
add_action( 'admin_init', 'ifs_pms_maybe_upgrade_db' );

function ifs_pms_maybe_upgrade_db() {
    $installed_ver = get_option( 'ifs_pms_version', '0.0.0' );
    if ( version_compare( $installed_ver, IFS_PMS_VERSION, '<' ) ) {
        ifs_pms_check_db_migrations();
        update_option( 'ifs_pms_version', IFS_PMS_VERSION );
    }
}

function ifs_pms_check_db_migrations() {
    global $wpdb;
    $t_tick = '`' . esc_sql( $wpdb->prefix . 'ifs_pms_tickets' ) . '`';

    if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', trim( $t_tick, '`' ) ) ) === trim( $t_tick, '`' ) ) {
        $cols = $wpdb->get_col( "DESC {$t_tick}", 0 );
        if ( is_array( $cols ) ) {
            if ( ! in_array( 'guest_type', $cols, true ) ) {
                $wpdb->query( "ALTER TABLE {$t_tick} ADD `guest_type` varchar(20) NOT NULL DEFAULT 'customer' AFTER `customer_id`" );
            }
            if ( ! in_array( 'package_details', $cols, true ) ) {
                $wpdb->query( "ALTER TABLE {$t_tick} ADD `package_details` text DEFAULT NULL AFTER `guest_type`" );
            }
            if ( ! in_array( 'duration_hours', $cols, true ) ) {
                $wpdb->query( "ALTER TABLE {$t_tick} ADD `duration_hours` int(3) NOT NULL DEFAULT 1 AFTER `package_details`" );
            }
            if ( ! in_array( 'payment_method', $cols, true ) ) {
                $wpdb->query( "ALTER TABLE {$t_tick} ADD `payment_method` varchar(30) NOT NULL DEFAULT 'Cash' AFTER `duration_hours`" );
            }
        }
    }
}

/**
 * 2. Dynamic RBAC Registration & Capability Synchronization
 */
add_action( 'admin_init', 'ifs_pms_ensure_capabilities' );

function ifs_pms_ensure_capabilities() {
    $admin_role = get_role( 'administrator' );
    if ( $admin_role and ! $admin_role->has_cap( 'ozone_access_terminal' ) ) {
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
        add_role(
            'ozone_cashier',
            __( 'Ozone Cashier', 'swimming-pool-manager' ),
            array(
                'read'                  => true,
                'ozone_access_terminal' => true,
                'ozone_sell_tickets'    => true,
                'ozone_scan_passes'     => true,
                'ozone_manage_patrons'  => true,
                'ozone_view_history'    => true,
            )
        );
    }
}

/**
 * 3. Menu Registration
 */
add_action( 'admin_menu', 'ifs_pms_register_menu' );

function ifs_pms_register_menu() {
    $capability = current_user_can( 'manage_options' ) ? 'manage_options' : 'ozone_access_terminal';

    add_menu_page(
        __( 'Ozone Skypool', 'swimming-pool-manager' ),
        __( 'Ozone Skypool', 'swimming-pool-manager' ),
        $capability,
        'ifs-pms',
        'ifs_pms_render_application',
        'dashicons-cloud',
        2
    );

    add_submenu_page(
        'ifs-pms',
        __( 'Live Status & Telemetry', 'swimming-pool-manager' ),
        __( 'Live Status', 'swimming-pool-manager' ),
        'ozone_access_terminal',
        'ifs-pms&view=live-status',
        'ifs_pms_render_application'
    );

    add_submenu_page(
        'ifs-pms',
        __( 'Patron Directory', 'swimming-pool-manager' ),
        __( 'Customers', 'swimming-pool-manager' ),
        'ozone_manage_patrons',
        'ifs-pms&view=customers',
        'ifs_pms_render_application'
    );
}

/**
 * 4. Admin Asset Queue & Script Localization
 */
add_action( 'admin_enqueue_scripts', 'ifs_pms_enqueue_assets' );

function ifs_pms_enqueue_assets( $hook ) {
    if ( 'toplevel_page_ifs-pms' !== $hook ) {
        return;
    }

    wp_enqueue_media();

    wp_enqueue_style( 'ozone-skypool-admin-css', IFS_PMS_URL . 'assets/css/style.css', array(), IFS_PMS_VERSION );
    wp_enqueue_script( 'ozone-skypool-admin-js', IFS_PMS_URL . 'assets/js/main.js', array( 'jquery' ), IFS_PMS_VERSION, true );

    $transient_key = 'ifs_pms_last_ticket_' . get_current_user_id();
    $last_ticket   = get_transient( $transient_key );
    if ( $last_ticket ) {
        delete_transient( $transient_key );
    }

    wp_localize_script(
        'ozone-skypool-admin-js',
        'ifsPmsConfig',
        array(
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'ifs_pms_security_token' ),
            'currency'    => esc_html( (string) get_option( 'ifs_pms_currency', '$' ) ),
            'last_ticket' => $last_ticket ? $last_ticket : null,
            'i18n'        => array(
                'packageTitle' => __( 'Package title', 'swimming-pool-manager' ),
                'ageCategory'  => __( 'Age/category', 'swimming-pool-manager' ),
                'itemName'     => __( 'Item name', 'swimming-pool-manager' ),
                'mediaTitle'   => __( 'Select Venue Logo', 'swimming-pool-manager' ),
                'mediaBtn'     => __( 'Use this logo', 'swimming-pool-manager' ),
                'mediaAlert'   => __( 'WordPress Media Uploader is loading or unavailable. Please refresh the page.', 'swimming-pool-manager' ),
            ),
        )
    );
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

    $action = isset( $_POST['ifs_pms_action'] ) ? sanitize_key( wp_unslash( $_POST['ifs_pms_action'] ) ) : '';
    $base   = admin_url( 'admin.php?page=ifs-pms' );

    // --- STAFF & SALARY CRUD ---
    if ( 'create_staff' === $action ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Administrator privileges required.', 'swimming-pool-manager' ), 403 );
        }

        $username = isset( $_POST['user_login'] ) ? sanitize_user( wp_unslash( $_POST['user_login'] ) ) : '';
        $email    = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
        $password = isset( $_POST['user_pass'] ) ? trim( (string) wp_unslash( $_POST['user_pass'] ) ) : '';
        $fullname = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';

        $requested_role = isset( $_POST['user_role'] ) ? sanitize_key( wp_unslash( $_POST['user_role'] ) ) : 'ozone_cashier';
        $allowed_roles  = array( 'ozone_cashier', 'administrator' );
        $role           = in_array( $requested_role, $allowed_roles, true ) ? $requested_role : 'ozone_cashier';

        $base_sal = isset( $_POST['base_salary'] ) ? floatval( wp_unslash( $_POST['base_salary'] ) ) : 0.00;
        $allow    = isset( $_POST['allowance'] ) ? floatval( wp_unslash( $_POST['allowance'] ) ) : 0.00;
        $freq     = isset( $_POST['pay_frequency'] ) ? sanitize_text_field( wp_unslash( $_POST['pay_frequency'] ) ) : 'Monthly';
        $eff_date = isset( $_POST['effective_date'] ) ? sanitize_text_field( wp_unslash( $_POST['effective_date'] ) ) : current_time( 'Y-m-d' );

        if ( ! empty( $username ) and is_email( $email ) and ! empty( $password ) ) {
            $user_id = wp_create_user( $username, $password, $email );
            if ( ! is_wp_error( $user_id ) ) {
                wp_update_user(
                    array(
                        'ID'           => $user_id,
                        'display_name' => $fullname ? $fullname : $username,
                        'role'         => $role,
                    )
                );

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
                wp_safe_redirect( add_query_arg( array( 'view' => 'staff', 'msg' => 'staff_created', 'tab' => 'list' ), $base ) );
                exit;
            }
        }
        wp_safe_redirect( add_query_arg( array( 'view' => 'staff', 'msg' => 'error', 'tab' => 'add' ), $base ) );
        exit;
    }

    if ( 'edit_staff' === $action ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Administrator privileges required.', 'swimming-pool-manager' ), 403 );
        }

        $user_id  = isset( $_POST['user_id'] ) ? absint( wp_unslash( $_POST['user_id'] ) ) : 0;
        $fullname = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
        $email    = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';

        $requested_role = isset( $_POST['user_role'] ) ? sanitize_key( wp_unslash( $_POST['user_role'] ) ) : 'ozone_cashier';
        $allowed_roles  = array( 'ozone_cashier', 'administrator' );
        $role           = in_array( $requested_role, $allowed_roles, true ) ? $requested_role : 'ozone_cashier';

        $new_pass = isset( $_POST['user_pass'] ) ? trim( (string) wp_unslash( $_POST['user_pass'] ) ) : '';

        $base_sal = isset( $_POST['base_salary'] ) ? floatval( wp_unslash( $_POST['base_salary'] ) ) : 0.00;
        $allow    = isset( $_POST['allowance'] ) ? floatval( wp_unslash( $_POST['allowance'] ) ) : 0.00;
        $freq     = isset( $_POST['pay_frequency'] ) ? sanitize_text_field( wp_unslash( $_POST['pay_frequency'] ) ) : 'Monthly';
        $eff_date = isset( $_POST['effective_date'] ) ? sanitize_text_field( wp_unslash( $_POST['effective_date'] ) ) : current_time( 'Y-m-d' );

        if ( $user_id > 0 and is_email( $email ) ) {
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

            wp_safe_redirect( add_query_arg( array( 'view' => 'staff', 'msg' => 'staff_updated', 'tab' => 'list' ), $base ) );
            exit;
        }
    }

    if ( 'delete_staff' === $action ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Administrator privileges required.', 'swimming-pool-manager' ), 403 );
        }

        $user_id = isset( $_POST['user_id'] ) ? absint( wp_unslash( $_POST['user_id'] ) ) : 0;
        if ( $user_id > 0 and $user_id !== get_current_user_id() ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user( $user_id );
            $wpdb->delete( $t_salary, array( 'user_id' => $user_id ), array( '%d' ) );
            wp_safe_redirect( add_query_arg( array( 'view' => 'staff', 'tab' => 'list' ), $base ) );
            exit;
        }
    }

    // --- TICKETS CRUD ---
    if ( 'issue_ticket' === $action ) {
        if ( ! current_user_can( 'ozone_sell_tickets' ) and ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Insufficient permissions to issue tickets.', 'swimming-pool-manager' ), 403 );
        }

        $name           = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $phone          = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $guest_type     = isset( $_POST['guest_type'] ) ? sanitize_key( wp_unslash( $_POST['guest_type'] ) ) : 'customer';
        $package_name   = isset( $_POST['package_name'] ) ? sanitize_text_field( wp_unslash( $_POST['package_name'] ) ) : '';
        $duration_hours = isset( $_POST['duration_hours'] ) ? max( 1, absint( wp_unslash( $_POST['duration_hours'] ) ) ) : 1;
        $payment_method = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : 'Cash';
        $room_no        = isset( $_POST['room_no'] ) ? sanitize_text_field( wp_unslash( $_POST['room_no'] ) ) : '';

        $is_free = ( 'room_guest' === $guest_type or 'Room Guest' === $payment_method or 'Complementary' === $payment_method );
        $amount  = $is_free ? 0.00 : ( isset( $_POST['amount'] ) ? max( 0.00, floatval( wp_unslash( $_POST['amount'] ) ) ) : 0.00 );
        $staff   = wp_get_current_user()->display_name;

        if ( ! empty( $name ) and ! empty( $phone ) ) {
            $customer = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$t_cust} WHERE phone = %s", $phone ) );
            if ( ! $customer ) {
                $wpdb->insert( $t_cust, array( 'name' => $name, 'phone' => $phone ), array( '%s', '%s' ) );
                $cust_id = $wpdb->insert_id;
            } else {
                $cust_id = $customer->id;
                $wpdb->update( $t_cust, array( 'name' => $name ), array( 'id' => $cust_id ), array( '%s' ), array( '%d' ) );
            }

            $entropy = strtoupper( wp_generate_password( 4, false, false ) );
            $code    = 'OZ-' . current_time( 'ymd' ) . '-' . str_pad( (string) $cust_id, 4, '0', STR_PAD_LEFT ) . '-' . $entropy;

            $inserted = $wpdb->insert(
                $t_tick,
                array(
                    'ticket_code'     => $code,
                    'customer_id'     => $cust_id,
                    'guest_type'      => $guest_type,
                    'package_details' => $package_name,
                    'duration_hours'  => $duration_hours,
                    'payment_method'  => $payment_method,
                    'room_no'         => $room_no,
                    'amount'          => $amount,
                    'sold_by'         => $staff,
                    'status'          => 'Valid',
                    'sold_at'         => current_time( 'mysql' ),
                ),
                array( '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%f', '%s', '%s', '%s' )
            );

            if ( $inserted ) {
                set_transient(
                    'ifs_pms_last_ticket_' . get_current_user_id(),
                    array(
                        'code'           => $code,
                        'name'           => $name,
                        'phone'          => $phone,
                        'guest_type'     => $guest_type,
                        'package'        => $package_name,
                        'duration_hours' => $duration_hours,
                        'payment_method' => $payment_method,
                        'room'           => $room_no,
                        'amount'         => number_format( $amount, 2 ),
                        'staff'          => $staff,
                        'date'           => current_time( 'mysql' ),
                    ),
                    120
                );

                wp_safe_redirect( add_query_arg( array( 'view' => 'tickets', 'msg' => 'ticket_created', 'tab' => 'add' ), $base ) );
                exit;
            }
        }
    }

    if ( 'edit_ticket' === $action ) {
        if ( ! current_user_can( 'ozone_sell_tickets' ) and ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Insufficient privileges.', 'swimming-pool-manager' ), 403 );
        }

        $ticket_id = isset( $_POST['ticket_id'] ) ? absint( wp_unslash( $_POST['ticket_id'] ) ) : 0;
        $name      = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $phone     = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $amount    = isset( $_POST['amount'] ) ? max( 0.00, floatval( wp_unslash( $_POST['amount'] ) ) ) : 0.00;

        $requested_status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'Valid';
        $allowed_statuses = array( 'Valid', 'Used', 'Cancelled' );
        $status           = in_array( $requested_status, $allowed_statuses, true ) ? $requested_status : 'Valid';

        if ( $ticket_id > 0 and ! empty( $name ) and ! empty( $phone ) ) {
            $ticket = $wpdb->get_row( $wpdb->prepare( "SELECT customer_id FROM {$t_tick} WHERE id = %d", $ticket_id ) );
            if ( $ticket ) {
                $wpdb->update( $t_cust, array( 'name' => $name, 'phone' => $phone ), array( 'id' => $ticket->customer_id ), array( '%s', '%s' ), array( '%d' ) );
                $wpdb->update( $t_tick, array( 'amount' => $amount, 'status' => $status ), array( 'id' => $ticket_id ), array( '%f', '%s' ), array( '%d' ) );
            }
            wp_safe_redirect( add_query_arg( array( 'view' => 'tickets', 'msg' => 'ticket_updated', 'tab' => 'list' ), $base ) );
            exit;
        }
    }

    if ( 'delete_ticket' === $action ) {
        if ( ! current_user_can( 'manage_options' ) and ! current_user_can( 'ozone_manage_settings' ) ) {
            wp_die( esc_html__( 'Forbidden: Only managers can delete ticket records.', 'swimming-pool-manager' ), 403 );
        }

        $ticket_id = isset( $_POST['ticket_id'] ) ? absint( wp_unslash( $_POST['ticket_id'] ) ) : 0;
        if ( $ticket_id > 0 ) {
            $wpdb->delete( $t_tick, array( 'id' => $ticket_id ), array( '%d' ) );
            wp_safe_redirect( add_query_arg( array( 'view' => 'tickets', 'msg' => 'ticket_deleted', 'tab' => 'list' ), $base ) );
            exit;
        }
    }

    // --- CUSTOMERS CRUD ---
    if ( 'edit_customer' === $action ) {
        if ( ! current_user_can( 'ozone_manage_patrons' ) and ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Insufficient privileges to update patron records.', 'swimming-pool-manager' ), 403 );
        }

        $cust_id = isset( $_POST['customer_id'] ) ? absint( wp_unslash( $_POST['customer_id'] ) ) : 0;
        $name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

        if ( $cust_id > 0 and ! empty( $name ) and ! empty( $phone ) ) {
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

    if ( 'delete_customer' === $action ) {
        if ( ! current_user_can( 'manage_options' ) and ! current_user_can( 'ozone_manage_settings' ) ) {
            wp_die( esc_html__( 'Forbidden: Only managers can delete customer profiles.', 'swimming-pool-manager' ), 403 );
        }

        $cust_id = isset( $_POST['customer_id'] ) ? absint( wp_unslash( $_POST['customer_id'] ) ) : 0;
        if ( $cust_id > 0 ) {
            $wpdb->delete( $t_cust, array( 'id' => $cust_id ), array( '%d' ) );
            wp_safe_redirect( add_query_arg( array( 'view' => 'customers', 'tab' => 'list' ), $base ) );
            exit;
        }
    }

    // --- MEMBERSHIP CRUD ---
    if ( 'create_membership' === $action ) {
        if ( ! current_user_can( 'ozone_manage_patrons' ) and ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Insufficient permissions to enroll members.', 'swimming-pool-manager' ), 403 );
        }

        $name          = isset( $_POST['m_name'] ) ? sanitize_text_field( wp_unslash( $_POST['m_name'] ) ) : '';
        $phone         = isset( $_POST['m_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['m_phone'] ) ) : '';
        $plan          = isset( $_POST['plan_type'] ) ? sanitize_text_field( wp_unslash( $_POST['plan_type'] ) ) : '';
        $amount        = isset( $_POST['m_amount'] ) ? max( 0.00, floatval( wp_unslash( $_POST['m_amount'] ) ) ) : 0.00;
        $duration      = isset( $_POST['duration_months'] ) ? max( 1, absint( wp_unslash( $_POST['duration_months'] ) ) ) : 1;
        $profile_image = isset( $_POST['profile_image'] ) ? esc_url_raw( wp_unslash( $_POST['profile_image'] ) ) : '';

        $mem_code = 'OZONE-MEM-' . strtoupper( wp_generate_password( 6, false, false ) );
        $start    = current_time( 'Y-m-d' );
        $expiry   = gmdate( 'Y-m-d', strtotime( "+{$duration} months", strtotime( $start ) ) );

        $wpdb->insert(
            $t_members,
            array(
                'member_code'   => $mem_code,
                'name'          => $name,
                'phone'         => $phone,
                'plan_type'     => $plan,
                'amount'        => $amount,
                'start_date'    => $start,
                'expiry_date'   => $expiry,
                'status'        => 'Active',
                'profile_image' => $profile_image,
            ),
            array( '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s' )
        );

        wp_safe_redirect( add_query_arg( array( 'view' => 'membership', 'msg' => 'membership_enrolled', 'tab' => 'list' ), $base ) );
        exit;
    }

    if ( 'edit_membership' === $action ) {
        if ( ! current_user_can( 'ozone_manage_patrons' ) and ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Insufficient privileges.', 'swimming-pool-manager' ), 403 );
        }

        $member_id     = isset( $_POST['member_id'] ) ? absint( wp_unslash( $_POST['member_id'] ) ) : 0;
        $name          = isset( $_POST['m_name'] ) ? sanitize_text_field( wp_unslash( $_POST['m_name'] ) ) : '';
        $phone         = isset( $_POST['m_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['m_phone'] ) ) : '';
        $plan          = isset( $_POST['plan_type'] ) ? sanitize_text_field( wp_unslash( $_POST['plan_type'] ) ) : '';
        $amount        = isset( $_POST['m_amount'] ) ? max( 0.00, floatval( wp_unslash( $_POST['m_amount'] ) ) ) : 0.00;
        $expiry        = isset( $_POST['expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : '';
        $status        = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'Active';
        $profile_image = isset( $_POST['profile_image'] ) ? esc_url_raw( wp_unslash( $_POST['profile_image'] ) ) : '';

        if ( $member_id > 0 and ! empty( $name ) and ! empty( $phone ) ) {
            $wpdb->update(
                $t_members,
                array(
                    'name'          => $name,
                    'phone'         => $phone,
                    'plan_type'     => $plan,
                    'amount'        => $amount,
                    'expiry_date'   => $expiry,
                    'status'        => $status,
                    'profile_image' => $profile_image,
                ),
                array( 'id' => $member_id ),
                array( '%s', '%s', '%s', '%f', '%s', '%s', '%s' ),
                array( '%d' )
            );

            wp_safe_redirect( add_query_arg( array( 'view' => 'membership', 'msg' => 'membership_enrolled', 'tab' => 'list' ), $base ) );
            exit;
        }
    }

    if ( 'delete_membership' === $action ) {
        if ( ! current_user_can( 'manage_options' ) and ! current_user_can( 'ozone_manage_settings' ) ) {
            wp_die( esc_html__( 'Forbidden: Only managers can delete member accounts.', 'swimming-pool-manager' ), 403 );
        }

        $member_id = isset( $_POST['member_id'] ) ? absint( wp_unslash( $_POST['member_id'] ) ) : 0;
        if ( $member_id > 0 ) {
            $wpdb->delete( $t_members, array( 'id' => $member_id ), array( '%d' ) );
            wp_safe_redirect( add_query_arg( array( 'view' => 'membership', 'tab' => 'list' ), $base ) );
            exit;
        }
    }

    // --- EXPENSES CRUD ---
    if ( 'add_expense' === $action ) {
        if ( ! current_user_can( 'ozone_manage_expenses' ) and ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Only managers can log expenses.', 'swimming-pool-manager' ), 403 );
        }

        $wpdb->insert(
            $t_expense,
            array(
                'title'        => isset( $_POST['expense_title'] ) ? sanitize_text_field( wp_unslash( $_POST['expense_title'] ) ) : '',
                'category'     => isset( $_POST['expense_cat'] ) ? sanitize_text_field( wp_unslash( $_POST['expense_cat'] ) ) : 'Operations',
                'amount'       => isset( $_POST['expense_amount'] ) ? max( 0.00, floatval( wp_unslash( $_POST['expense_amount'] ) ) ) : 0.00,
                'expense_date' => isset( $_POST['expense_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expense_date'] ) ) : current_time( 'Y-m-d' ),
                'added_by'     => wp_get_current_user()->display_name,
            ),
            array( '%s', '%s', '%f', '%s', '%s' )
        );

        wp_safe_redirect( add_query_arg( array( 'view' => 'expenses', 'msg' => 'expense_logged', 'tab' => 'list' ), $base ) );
        exit;
    }

    if ( 'edit_expense' === $action ) {
        if ( ! current_user_can( 'ozone_manage_expenses' ) and ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Insufficient privileges.', 'swimming-pool-manager' ), 403 );
        }

        $expense_id = isset( $_POST['expense_id'] ) ? absint( wp_unslash( $_POST['expense_id'] ) ) : 0;
        $title      = isset( $_POST['expense_title'] ) ? sanitize_text_field( wp_unslash( $_POST['expense_title'] ) ) : '';
        $category   = isset( $_POST['expense_cat'] ) ? sanitize_text_field( wp_unslash( $_POST['expense_cat'] ) ) : 'Operations';
        $amount     = isset( $_POST['expense_amount'] ) ? max( 0.00, floatval( wp_unslash( $_POST['expense_amount'] ) ) ) : 0.00;
        $date       = isset( $_POST['expense_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expense_date'] ) ) : current_time( 'Y-m-d' );

        if ( $expense_id > 0 and ! empty( $title ) and $amount > 0 ) {
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

    if ( 'delete_expense' === $action ) {
        if ( ! current_user_can( 'manage_options' ) and ! current_user_can( 'ozone_manage_settings' ) ) {
            wp_die( esc_html__( 'Forbidden: Only managers can delete expense logs.', 'swimming-pool-manager' ), 403 );
        }

        $expense_id = isset( $_POST['expense_id'] ) ? absint( wp_unslash( $_POST['expense_id'] ) ) : 0;
        if ( $expense_id > 0 ) {
            $wpdb->delete( $t_expense, array( 'id' => $expense_id ), array( '%d' ) );
            wp_safe_redirect( add_query_arg( array( 'view' => 'expenses', 'tab' => 'list' ), $base ) );
            exit;
        }
    }

    // --- MASTER SETTINGS ---
    if ( 'save_settings' === $action ) {
        if ( ! current_user_can( 'ozone_manage_settings' ) and ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Forbidden: Insufficient administrative privileges.', 'swimming-pool-manager' ), 403 );
        }

        $active_tab = isset( $_POST['ifs_pms_active_tab'] ) ? sanitize_key( wp_unslash( $_POST['ifs_pms_active_tab'] ) ) : 'pos';

        update_option( 'ifs_pms_business_name', isset( $_POST['ifs_pms_business_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ifs_pms_business_name'] ) ) : '' );
        update_option( 'ifs_pms_currency', isset( $_POST['ifs_pms_currency'] ) ? sanitize_text_field( wp_unslash( $_POST['ifs_pms_currency'] ) ) : '' );
        update_option( 'ifs_pms_phone', isset( $_POST['ifs_pms_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['ifs_pms_phone'] ) ) : '' );
        update_option( 'ifs_pms_address', isset( $_POST['ifs_pms_address'] ) ? sanitize_text_field( wp_unslash( $_POST['ifs_pms_address'] ) ) : '' );
        update_option( 'ifs_pms_max_capacity', isset( $_POST['ifs_pms_max_capacity'] ) ? sanitize_text_field( wp_unslash( $_POST['ifs_pms_max_capacity'] ) ) : '' );
        update_option( 'ifs_pms_receipt_note', isset( $_POST['ifs_pms_receipt_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ifs_pms_receipt_note'] ) ) : '' );
        update_option( 'ifs_pms_logo_url', isset( $_POST['ifs_pms_logo_url'] ) ? esc_url_raw( wp_unslash( $_POST['ifs_pms_logo_url'] ) ) : '' );

        update_option( 'ifs_pms_enable_strict_scan', isset( $_POST['ifs_pms_enable_strict_scan'] ) ? '1' : '0' );
        update_option( 'ifs_pms_auto_expire_passes', isset( $_POST['ifs_pms_auto_expire_passes'] ) ? '1' : '0' );
        update_option( 'ifs_pms_thermal_paper_width', isset( $_POST['ifs_pms_thermal_paper_width'] ) ? sanitize_text_field( wp_unslash( $_POST['ifs_pms_thermal_paper_width'] ) ) : '' );
        update_option( 'ifs_pms_enable_audio_buzzer', isset( $_POST['ifs_pms_enable_audio_buzzer'] ) ? '1' : '0' );
        update_option( 'ifs_pms_enable_amenities', isset( $_POST['ifs_pms_enable_amenities'] ) ? '1' : '0' );
        update_option( 'ifs_pms_pool_status', isset( $_POST['ifs_pms_pool_status'] ) ? sanitize_text_field( wp_unslash( $_POST['ifs_pms_pool_status'] ) ) : '' );

        $tenders = isset( $_POST['ifs_pms_enabled_tenders'] ) and is_array( $_POST['ifs_pms_enabled_tenders'] )
            ? array_map( 'sanitize_text_field', wp_unslash( $_POST['ifs_pms_enabled_tenders'] ) )
            : array();
        update_option( 'ifs_pms_enabled_tenders', $tenders );

        $cash_presets = isset( $_POST['ifs_pms_quick_cash_presets'] ) ? sanitize_text_field( wp_unslash( $_POST['ifs_pms_quick_cash_presets'] ) ) : '';
        update_option( 'ifs_pms_quick_cash_presets', $cash_presets );

        // Dynamic Pricing Tiers.
        if ( isset( $_POST['ifs_pricing_tier_name'] ) and is_array( $_POST['ifs_pricing_tier_name'] ) ) {
            $tiers         = array();
            $posted_names  = array_map( 'sanitize_text_field', wp_unslash( $_POST['ifs_pricing_tier_name'] ) );
            $posted_ages   = isset( $_POST['ifs_pricing_tier_age'] ) and is_array( $_POST['ifs_pricing_tier_age'] )
                ? array_map( 'sanitize_text_field', wp_unslash( $_POST['ifs_pricing_tier_age'] ) )
                : array();
            $posted_prices = isset( $_POST['ifs_pricing_tier_price'] ) and is_array( $_POST['ifs_pricing_tier_price'] )
                ? array_map( 'floatval', wp_unslash( $_POST['ifs_pricing_tier_price'] ) )
                : array();

            $names  = array_values( $posted_names );
            $ages   = array_values( $posted_ages );
            $prices = array_values( $posted_prices );

            for ( $i = 0; $i < count( $names ); $i++ ) {
                $item_name = trim( $names[ $i ] ?? '' );
                if ( ! empty( $item_name ) ) {
                    $tiers[] = array(
                        'name'      => $item_name,
                        'age_group' => $ages[ $i ] ?? '',
                        'price'     => max( 0.00, $prices[ $i ] ?? 0.00 ),
                    );
                }
            }
            update_option( 'ifs_pms_pricing_tiers', $tiers );
        } else {
            update_option( 'ifs_pms_pricing_tiers', array() );
        }

        // Dynamic Amenity Add-Ons.
        if ( isset( $_POST['ifs_addon_name'] ) and is_array( $_POST['ifs_addon_name'] ) ) {
            $addons         = array();
            $posted_anames  = array_map( 'sanitize_text_field', wp_unslash( $_POST['ifs_addon_name'] ) );
            $posted_aprices = isset( $_POST['ifs_addon_price'] ) and is_array( $_POST['ifs_addon_price'] )
                ? array_map( 'floatval', wp_unslash( $_POST['ifs_addon_price'] ) )
                : array();

            $a_names  = array_values( $posted_anames );
            $a_prices = array_values( $posted_aprices );

            for ( $j = 0; $j < count( $a_names ); $j++ ) {
                $add_name = trim( $a_names[ $j ] ?? '' );
                if ( ! empty( $add_name ) ) {
                    $addons[] = array(
                        'name'  => $add_name,
                        'price' => max( 0.00, $a_prices[ $j ] ?? 0.00 ),
                    );
                }
            }
            update_option( 'ifs_pms_amenity_addons', $addons );
        } else {
            update_option( 'ifs_pms_amenity_addons', array() );
        }

        // Day-Wise Operating Hours.
        $days_of_week = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
        $schedule     = array();
        foreach ( $days_of_week as $day ) {
            $status_key = 'ifs_pms_schedule_' . $day . '_status';
            $open_key   = 'ifs_pms_schedule_' . $day . '_open';
            $close_key  = 'ifs_pms_schedule_' . $day . '_close';

            $schedule[ $day ] = array(
                'status' => isset( $_POST[ $status_key ] ) ? sanitize_key( wp_unslash( $_POST[ $status_key ] ) ) : 'open',
                'open'   => isset( $_POST[ $open_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $open_key ] ) ) : '08:00',
                'close'  => isset( $_POST[ $close_key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $close_key ] ) ) : '20:00',
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
    if ( ! current_user_can( 'ozone_access_terminal' ) and ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'swimming-pool-manager' ), 403 );
    }

    $is_admin          = current_user_can( 'manage_options' ) or current_user_can( 'ozone_manage_settings' );
    $admin_only_views = array( 'staff', 'expenses', 'reports', 'settings' );

    $current_view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'dashboard';

    if ( ! $is_admin and in_array( $current_view, $admin_only_views, true ) ) {
        $current_view = 'dashboard';
    }

    $base_url = admin_url( 'admin.php?page=ifs-pms' );

    $msg_code   = isset( $_GET['msg'] ) ? sanitize_key( wp_unslash( $_GET['msg'] ) ) : '';
    $flash_msg = '';
    $flash_map = array(
        'ticket_created'      => __( 'Single Admission Pass Created Successfully.', 'swimming-pool-manager' ),
        'ticket_updated'      => __( 'Ticket Record Updated.', 'swimming-pool-manager' ),
        'ticket_deleted'      => __( 'Ticket Removed Permanently.', 'swimming-pool-manager' ),
        'customer_updated'    => __( 'Patron Profile Updated Successfully.', 'swimming-pool-manager' ),
        'membership_enrolled' => __( 'Member Subscription Record Synchronized.', 'swimming-pool-manager' ),
        'expense_logged'      => __( 'Operational Outflow Logged.', 'swimming-pool-manager' ),
        'staff_created'       => __( 'Operator Account & Compensation Provisioned.', 'swimming-pool-manager' ),
        'staff_updated'       => __( 'Operator Profile Updated.', 'swimming-pool-manager' ),
        'settings_saved'      => __( 'Master Configuration Saved.', 'swimming-pool-manager' ),
    );
    if ( isset( $flash_map[ $msg_code ] ) ) {
        $flash_msg = $flash_map[ $msg_code ];
    }

    $b_name       = (string) get_option( 'ifs_pms_business_name', '' );
    $address      = (string) get_option( 'ifs_pms_address', '' );
    $phone        = (string) get_option( 'ifs_pms_phone', '' );
    $receipt_note = (string) get_option( 'ifs_pms_receipt_note', '' );
    $logo_url     = (string) get_option( 'ifs_pms_logo_url', '' );
    ?>

    <div class="ifs-pms-shell" id="ifsPmsAppShell">
        <aside class="ifs-pms-aside">
            <div>
                <div class="ifs-pms-brand">
                    <div>
                        <div style="font-size: 15px; font-weight: 800; color: var(--ifs-text-primary);"><?php echo $b_name ? esc_html( $b_name ) : esc_html__( 'Ozone Skypool', 'swimming-pool-manager' ); ?></div>
                        <div style="font-size: 10px; color: var(--ifs-accent); font-weight: 700; letter-spacing: 0.8px;">
                            <?php echo $is_admin ? esc_html__( 'MANAGER TERMINAL', 'swimming-pool-manager' ) : esc_html__( 'CASHIER DESK', 'swimming-pool-manager' ); ?>
                        </div>
                    </div>
                </div>

                <div class="ifs-pms-nav-group-title"><?php esc_html_e( 'Front Desk', 'swimming-pool-manager' ); ?></div>
                <ul class="ifs-pms-nav">
                    <li class="ifs-pms-nav-item <?php echo ( 'dashboard' === $current_view ) ? 'ifs-pms-active' : ''; ?>">
                        <a class="ifs-pms-nav-link" href="<?php echo esc_url( $base_url . '&view=dashboard' ); ?>"><?php echo wp_kses( ifs_pms_get_svg( 'overview', '', 14 ), array( 'svg' => array( 'xmlns' => true, 'viewBox' => true, 'width' => true, 'height' => true, 'fill' => true, 'class' => true ), 'path' => array( 'd' => true ) ) ); ?> <?php esc_html_e( 'Overview', 'swimming-pool-manager' ); ?></a>
                    </li>
                    <li class="ifs-pms-nav-item <?php echo ( 'tickets' === $current_view or 'ticket_detail' === $current_view ) ? 'ifs-pms-active' : ''; ?>">
                        <a class="ifs-pms-nav-link" href="<?php echo esc_url( $base_url . '&view=tickets' ); ?>"><?php echo wp_kses( ifs_pms_get_svg( 'ticket', '', 14 ), array( 'svg' => array( 'xmlns' => true, 'viewBox' => true, 'width' => true, 'height' => true, 'fill' => true, 'class' => true ), 'path' => array( 'd' => true ) ) ); ?> <?php esc_html_e( 'Tickets', 'swimming-pool-manager' ); ?></a>
                    </li>
                    <li class="ifs-pms-nav-item <?php echo ( 'scanner' === $current_view ) ? 'ifs-pms-active' : ''; ?>">
                        <a class="ifs-pms-nav-link" href="<?php echo esc_url( $base_url . '&view=scanner' ); ?>"><?php echo wp_kses( ifs_pms_get_svg( 'qrcode', '', 14 ), array( 'svg' => array( 'xmlns' => true, 'viewBox' => true, 'width' => true, 'height' => true, 'fill' => true, 'class' => true ), 'path' => array( 'd' => true ) ) ); ?> <?php esc_html_e( 'Scan Pass', 'swimming-pool-manager' ); ?></a>
                    </li>
                    <li class="ifs-pms-nav-item <?php echo ( 'live-status' === $current_view ) ? 'ifs-pms-active' : ''; ?>">
                        <a class="ifs-pms-nav-link" href="<?php echo esc_url( $base_url . '&view=live-status' ); ?>"><?php echo wp_kses( ifs_pms_get_svg( 'tower', '', 14 ), array( 'svg' => array( 'xmlns' => true, 'viewBox' => true, 'width' => true, 'height' => true, 'fill' => true, 'class' => true ), 'path' => array( 'd' => true ) ) ); ?> <?php esc_html_e( 'Live Status', 'swimming-pool-manager' ); ?></a>
                    </li>
                    <li class="ifs-pms-nav-item <?php echo ( 'customers' === $current_view ) ? 'ifs-pms-active' : ''; ?>">
                        <a class="ifs-pms-nav-link" href="<?php echo esc_url( $base_url . '&view=customers' ); ?>"><?php echo wp_kses( ifs_pms_get_svg( 'users', '', 14 ), array( 'svg' => array( 'xmlns' => true, 'viewBox' => true, 'width' => true, 'height' => true, 'fill' => true, 'class' => true ), 'path' => array( 'd' => true ) ) ); ?> <?php esc_html_e( 'Customers', 'swimming-pool-manager' ); ?></a>
                    </li>
                    <li class="ifs-pms-nav-item <?php echo ( 'membership' === $current_view ) ? 'ifs-pms-active' : ''; ?>">
                        <a class="ifs-pms-nav-link" href="<?php echo esc_url( $base_url . '&view=membership' ); ?>"><?php echo wp_kses( ifs_pms_get_svg( 'card', '', 14 ), array( 'svg' => array( 'xmlns' => true, 'viewBox' => true, 'width' => true, 'height' => true, 'fill' => true, 'class' => true ), 'path' => array( 'd' => true ) ) ); ?> <?php esc_html_e( 'Members', 'swimming-pool-manager' ); ?></a>
                    </li>
                </ul>

                <?php if ( $is_admin ) : ?>
                    <div class="ifs-pms-nav-group-title"><?php esc_html_e( 'Management & Ledger', 'swimming-pool-manager' ); ?></div>
                    <ul class="ifs-pms-nav">
                        <li class="ifs-pms-nav-item <?php echo ( 'staff' === $current_view ) ? 'ifs-pms-active' : ''; ?>">
                            <a class="ifs-pms-nav-link" href="<?php echo esc_url( $base_url . '&view=staff' ); ?>"><?php echo wp_kses( ifs_pms_get_svg( 'shield', '', 14 ), array( 'svg' => array( 'xmlns' => true, 'viewBox' => true, 'width' => true, 'height' => true, 'fill' => true, 'class' => true ), 'path' => array( 'd' => true ) ) ); ?> <?php esc_html_e( 'Staffs', 'swimming-pool-manager' ); ?></a>
                        </li>
                        <li class="ifs-pms-nav-item <?php echo ( 'expenses' === $current_view ) ? 'ifs-pms-active' : ''; ?>">
                            <a class="ifs-pms-nav-link" href="<?php echo esc_url( $base_url . '&view=expenses' ); ?>"><?php echo wp_kses( ifs_pms_get_svg( 'receipt', '', 14 ), array( 'svg' => array( 'xmlns' => true, 'viewBox' => true, 'width' => true, 'height' => true, 'fill' => true, 'class' => true ), 'path' => array( 'd' => true ) ) ); ?> <?php esc_html_e( 'Expenses', 'swimming-pool-manager' ); ?></a>
                        </li>
                        <li class="ifs-pms-nav-item <?php echo ( 'reports' === $current_view ) ? 'ifs-pms-active' : ''; ?>">
                            <a class="ifs-pms-nav-link" href="<?php echo esc_url( $base_url . '&view=reports' ); ?>"><?php echo wp_kses( ifs_pms_get_svg( 'card', '', 14 ), array( 'svg' => array( 'xmlns' => true, 'viewBox' => true, 'width' => true, 'height' => true, 'fill' => true, 'class' => true ), 'path' => array( 'd' => true ) ) ); ?> <?php esc_html_e( 'Reports', 'swimming-pool-manager' ); ?></a>
                        </li>
                    </ul>

                    <div class="ifs-pms-nav-group-title"><?php esc_html_e( 'Administration', 'swimming-pool-manager' ); ?></div>
                    <ul class="ifs-pms-nav">
                        <li class="ifs-pms-nav-item <?php echo ( 'settings' === $current_view ) ? 'ifs-pms-active' : ''; ?>">
                            <a class="ifs-pms-nav-link" href="<?php echo esc_url( $base_url . '&view=settings' ); ?>"><?php echo wp_kses( ifs_pms_get_svg( 'sliders', '', 14 ), array( 'svg' => array( 'xmlns' => true, 'viewBox' => true, 'width' => true, 'height' => true, 'fill' => true, 'class' => true ), 'path' => array( 'd' => true ) ) ); ?> <?php esc_html_e( 'Settings', 'swimming-pool-manager' ); ?></a>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>

            <div>
                <div class="ifs-pms-theme-toggle-wrap">
                    <span style="font-size: 11px; font-weight: 700; color: var(--ifs-text-tertiary);"><?php esc_html_e( 'Mode', 'swimming-pool-manager' ); ?></span>
                    <div style="display: flex; gap: 4px;">
                        <button type="button" class="ifs-pms-theme-btn" id="ifsThemeLightBtn" onclick="ifsPmsSetTheme('light')">
                            <?php echo wp_kses( ifs_pms_get_svg( 'sun', '', 12 ), array( 'svg' => array( 'xmlns' => true, 'viewBox' => true, 'width' => true, 'height' => true, 'fill' => true, 'class' => true ), 'path' => array( 'd' => true ) ) ); ?> <?php esc_html_e( 'Light', 'swimming-pool-manager' ); ?>
                        </button>
                        <button type="button" class="ifs-pms-theme-btn" id="ifsThemeDarkBtn" onclick="ifsPmsSetTheme('dark')">
                            <?php echo wp_kses( ifs_pms_get_svg( 'moon', '', 12 ), array( 'svg' => array( 'xmlns' => true, 'viewBox' => true, 'width' => true, 'height' => true, 'fill' => true, 'class' => true ), 'path' => array( 'd' => true ) ) ); ?> <?php esc_html_e( 'Dark', 'swimming-pool-manager' ); ?>
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
                            <?php echo $is_admin ? esc_html__( 'Facility Manager', 'swimming-pool-manager' ) : esc_html__( 'Active Cashier', 'swimming-pool-manager' ); ?>
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
                        <h1><?php echo $b_name ? esc_html( $b_name ) : esc_html__( 'Ozone Skypool', 'swimming-pool-manager' ); ?></h1>
                        <p><span class="ifs-pms-pulse-dot"></span> <?php esc_html_e( 'Terminal Online', 'swimming-pool-manager' ); ?> &bull; <?php echo esc_html( current_time( 'l, F j, Y' ) ); ?></p>
                    </div>
                </div>
            </header>

            <?php if ( ! empty( $flash_msg ) ) : ?>
                <div style="background: var(--ifs-success-soft); border: 1px solid rgba(16, 185, 129, 0.3); color: var(--ifs-success); padding: 12px 18px; border-radius: 8px; margin-bottom: 22px; font-weight: 600; font-size: 13.5px; display: flex; align-items: center; gap: 10px;">
                    <?php echo wp_kses( ifs_pms_get_svg( 'check', '', 16 ), array( 'svg' => array( 'xmlns' => true, 'viewBox' => true, 'width' => true, 'height' => true, 'fill' => true, 'class' => true ), 'path' => array( 'd' => true ) ) ); ?> <?php echo esc_html( $flash_msg ); ?>
                </div>
            <?php endif; ?>

            <?php
            $view_to_include = ( 'ticket_detail' === $current_view ) ? 'tickets' : $current_view;
            $view_path       = IFS_PMS_PATH . 'views/' . $view_to_include . '.php';

            if ( file_exists( $view_path ) ) {
                include $view_path;
            } else {
                echo '<div class="ifs-pms-panel" style="padding: 40px; text-align: center;"><p style="font-size: 15px; font-weight: 700; color: #ef4444;">' . esc_html__( 'Selected dashboard view was not found:', 'swimming-pool-manager' ) . ' <code>' . esc_html( $view_to_include ) . '.php</code></p></div>';
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
                <?php if ( ! empty( $phone ) ) : ?>
                    <?php echo esc_html__( 'Tel:', 'swimming-pool-manager' ) . ' ' . esc_html( $phone ); ?>
                <?php endif; ?>
            </p>
            <div class="ifs-pms-dashed-sep"></div>
            <div id="ifs-pms-thermal-qr" style="display: flex; justify-content: center; margin: 12px 0;"></div>
            <div id="ifs-pms-slip-code" style="font-size: 16px; font-weight: 800; letter-spacing: 1.5px;"></div>
            <div class="ifs-pms-dashed-sep"></div>
            <div id="ifs-pms-slip-meta" style="font-size: 11.5px; line-height: 1.6; text-align: left;"></div>
            <div class="ifs-pms-dashed-sep"></div>
            <?php if ( ! empty( $receipt_note ) ) : ?>
                <p style="font-size: 9.5px; color: #64748b; margin: 0;"><?php echo esc_html( $receipt_note ); ?></p>
            <?php endif; ?>
            <div class="no-print" style="margin-top: 16px; display: flex; gap: 8px;">
                <button onclick="window.print()" class="ifs-pms-btn ifs-pms-btn-primary" style="flex: 1;"><?php echo wp_kses( ifs_pms_get_svg( 'print', '', 14 ), array( 'svg' => array( 'xmlns' => true, 'viewBox' => true, 'width' => true, 'height' => true, 'fill' => true, 'class' => true ), 'path' => array( 'd' => true ) ) ); ?> <?php esc_html_e( 'Print', 'swimming-pool-manager' ); ?></button>
                <button onclick="ifs_pms_close_receipt()" class="ifs-pms-btn ifs-pms-btn-secondary" style="flex: 1;"><?php esc_html_e( 'Close', 'swimming-pool-manager' ); ?></button>
            </div>
        </div>
    </div>
    <?php
}

/**
 * 7. Secure Pass Verification Endpoint (Turnstile / Scanner with Atomic Concurrency Protection)
 */
add_action( 'wp_ajax_ifs_pms_verify_pass_action', 'ifs_pms_verify_pass_callback' );

function ifs_pms_verify_pass_callback() {
    check_ajax_referer( 'ifs_pms_security_token', 'security' );

    if ( ! current_user_can( 'ozone_scan_passes' ) and ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Forbidden: Insufficient privileges.', 'swimming-pool-manager' ) ), 403 );
    }

    global $wpdb;
    $t_tick = '`' . esc_sql( $wpdb->prefix . 'ifs_pms_tickets' ) . '`';
    $t_cust = '`' . esc_sql( $wpdb->prefix . 'ifs_pms_customers' ) . '`';
    $code   = isset( $_POST['ticket_code'] ) ? sanitize_text_field( wp_unslash( $_POST['ticket_code'] ) ) : '';

    if ( empty( $code ) ) {
        wp_send_json_error( array( 'message' => __( 'Missing pass identification parameter.', 'swimming-pool-manager' ) ) );
    }

    $now   = current_time( 'mysql' );$staff = wp_get_current_user()->display_name;

    // ATOMIC LOCK STEP: Single query update prevents double-scanning concurrent requests.
    $affected = $wpdb->query($wpdb->prepare(
            "UPDATE {$t_tick} 
            SET status = %s, scanned_at = %s, scanned_by = %s 
            WHERE ticket_code = %s AND status = %s",
            'Used',
            $now,
            $staff,$code,
            'Valid'
        )
    );

    if ( 0 === $affected ) {$ticket_state = $wpdb->get_row($wpdb->prepare(
                "SELECT status, scanned_at, scanned_by FROM {$t_tick} WHERE ticket_code = %s",
                $code
            )
        );

        if ( ! $ticket_state ) {
            wp_send_json_error( array( 'message' => __( 'Unrecognized / Forged Pass ID.', 'swimming-pool-manager' ) ) );
        }

        if ( 'Used' === $ticket_state->status ) {
            wp_send_json_error(
                array(
                    /* translators: 1: formatted datetime of scan, 2: staff member display name */
                    'message' => sprintf( __( 'Pass already redeemed at %1$s by %2$s', 'swimming-pool-manager' ), esc_html( $ticket_state->scanned_at ), esc_html($ticket_state->scanned_by ) ),
                )
            );
        }

        if ( 'Cancelled' === $ticket_state->status ) {
            wp_send_json_error( array( 'message' => __( 'Pass has been voided or refunded.', 'swimming-pool-manager' ) ) );
        }

        wp_send_json_error( array( 'message' => __( 'Pass verification failed.', 'swimming-pool-manager' ) ) );
    }

    // Pass valid & verified: Retrieve hydrated meta.
    $ticket = $wpdb->get_row($wpdb->prepare(
            "SELECT t.*, c.name as customer_name, c.phone as customer_phone 
            FROM {$t_tick} t 
            LEFT JOIN {$t_cust} c ON t.customer_id = c.id 
            WHERE t.ticket_code = %s",
            $code
        )
    );

    $duration    = ! empty( $ticket->duration_hours ) ? (int)$ticket->duration_hours : 1;
    $valid_until = gmdate( 'h:i A', strtotime( "+{$duration} hours", strtotime( $now ) ) );

    wp_send_json_success(
        array(
            'message'        => __( 'Access Granted: Gate Relay Actuated', 'swimming-pool-manager' ),
            'ticket_code'    => $code,
            'customer_name'  => ! empty( $ticket->customer_name ) ? esc_html( $ticket->customer_name ) : __( 'Walk-in Guest', 'swimming-pool-manager' ),
            'customer_phone' => ! empty( $ticket->customer_phone ) ? esc_html( $ticket->customer_phone ) : '-',
            'guest_type'     => ! empty( $ticket->guest_type ) ?$ticket->guest_type : 'customer',
            'room_no'        => ! empty( $ticket->room_no ) ?$ticket->room_no : '',
            'package'        => ! empty( $ticket->package_details ) ?$ticket->package_details : __( 'Standard Swim Pass', 'swimming-pool-manager' ),
            'duration_hours' => $duration,
            'valid_until'    => $valid_until,
            'amount'         => number_format( (float) $ticket->amount, 2 ),
            'payment_method' =>$ticket->payment_method ?? 'Cash',
            'sold_by'        => ! empty( $ticket->sold_by ) ? esc_html( $ticket->sold_by ) : '-',
            'sold_at'        => ! empty( $ticket->sold_at ) ? esc_html( $ticket->sold_at ) : '-',
            'scanned_by'     => esc_html( $staff ),
            'scanned_at'     => gmdate( 'h:i:s A', strtotime( $now ) ),
        )
    );
}

/**
 * 8. Secure CSV Exporter Callback (Managers Only)
 */
add_action( 'wp_ajax_ifs_pms_export_csv_action', 'ifs_pms_export_csv_action_callback' );

function ifs_pms_export_csv_action_callback() {
    check_ajax_referer( 'ifs_pms_security_token', 'security' );

    if ( ! current_user_can( 'ozone_view_finances' ) and ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Unauthorized access.', 'swimming-pool-manager' ), 403 );
    }

    global $wpdb;
    $t_tick = '`' . esc_sql( $wpdb->prefix . 'ifs_pms_tickets' ) . '`';
    $t_cust = '`' . esc_sql( $wpdb->prefix . 'ifs_pms_customers' ) . '`';

    $tickets =$wpdb->get_results(
        "SELECT t.ticket_code, c.name as customer_name, c.phone as customer_phone, t.guest_type, t.package_details, t.duration_hours, t.payment_method, t.room_no, t.amount, t.sold_by, t.status, t.sold_at, t.scanned_at, t.scanned_by 
        FROM {$t_tick} t 
        LEFT JOIN {$t_cust} c ON t.customer_id = c.id 
        ORDER BY t.id DESC",
        ARRAY_A
    );

    nocache_headers();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=ozone_skypool_ledger_' . gmdate( 'Y-m-d' ) . '.csv' );

    $output = fopen( 'php://output', 'w' );
    if ( false !== $output ) {
        fputcsv( $output, array( 'Ticket Code', 'Patron Name', 'Phone', 'Guest Type', 'Packages Enrolled', 'Hours', 'Payment Method', 'Room Number', 'Amount', 'Sold By', 'Status', 'Sold At', 'Scanned At', 'Scanned By' ) );

        if ( ! empty( $tickets ) ) {
            foreach ( $tickets as$row ) {
                fputcsv(
                    $output,
                    array(
                        $row['ticket_code'],$row['customer_name'],
                        $row['customer_phone'],$row['guest_type'],
                        $row['package_details'],$row['duration_hours'],
                        $row['payment_method'],$row['room_no'],
                        $row['amount'],$row['sold_by'],
                        $row['status'],$row['sold_at'],
                        $row['scanned_at'],$row['scanned_by'],
                    )
                );
            }
        }
        fclose( $output );
    }
    exit;
}

/**
 * 9. Custom Login Customization: Number Captcha & Auto-Redirect
 */
add_action( 'login_form', 'ifs_pms_add_number_captcha' );

function ifs_pms_add_number_captcha() {
    $num1 = wp_rand( 1, 9 );
    $num2 = wp_rand( 1, 9 );$sum  = $num1 +$num2;

    $captcha_token = wp_generate_password( 16, false, false );
    set_transient( 'ifs_captcha_' . $captcha_token,$sum, 300 );
    ?>
    <p class="pms-captcha-wrap" style="margin-bottom: 20px;">
        <label for="pms_captcha_answer" style="display: block; font-weight: 700; margin-bottom: 6px; color: #334155;">
            <?php
            /* translators: 1: first integer in security sum, 2: second integer in security sum */
            printf( esc_html__( 'Security Check: What is %1$d + \%2$d?', 'swimming-pool-manager' ), absint( $num1 ), absint($num2 ) );
            ?>
            <span style="color: #ef4444;">*</span>
        </label>
        <input type="number" name="pms_captcha_answer" id="pms_captcha_answer" class="input" value="" size="20" required autocomplete="off" style="border-radius: 8px !important; border: 1.5px solid #cbd5e1 !important; height: 42px !important;">
        <input type="hidden" name="pms_captcha_token" value="<?php echo esc_attr( $captcha_token ); ?>">
    </p>
    <?php
}

add_filter( 'authenticate', 'ifs_pms_verify_number_captcha', 30, 3 );

function ifs_pms_verify_number_captcha( $user, $username,$password ) {
    if ( empty( $username ) or empty( $password ) or is_wp_error($user ) ) {
        return $user;
    }

    if ( isset( $_POST['pms_captcha_answer'],$_POST['pms_captcha_token'] ) ) {
        $token  = sanitize_text_field( wp_unslash($_POST['pms_captcha_token'] ) );
        $answer = intval( wp_unslash($_POST['pms_captcha_answer'] ) );

        $correct_sum = get_transient( 'ifs_captcha_' .$token );
        delete_transient( 'ifs_captcha_' . $token );

        if ( false === $correct_sum or $answer !== intval($correct_sum ) ) {
            return new WP_Error( 'invalid_captcha', __( '<strong>ERROR</strong>: Incorrect security captcha calculation. Please try again.', 'swimming-pool-manager' ) );
        }
    } else {
        return new WP_Error( 'invalid_captcha', __( '<strong>ERROR</strong>: Captcha answer missing.', 'swimming-pool-manager' ) );
    }

    return $user;
}

add_filter( 'login_redirect', 'ifs_pms_login_redirect_dashboard', 10, 3 );

function ifs_pms_login_redirect_dashboard( $redirect_to, $requested_redirect_to,$user ) {
    if ( isset( $user->ID ) ) {
        if ( user_can( $user, 'ozone_access_terminal' ) or user_can($user, 'manage_options' ) ) {
            return admin_url( 'admin.php?page=ifs-pms' );
        }
    }
    return $redirect_to;
}

add_action( 'login_enqueue_scripts', 'ifs_pms_custom_login_style' );

function ifs_pms_custom_login_style() {
    $logo_url = get_option( 'ifs_pms_logo_url', '' );
    ?>
    <style>
        body.login {
            background: linear-gradient(135deg, #090d16 0%, #0c1c2e 50%, #0284c7 100%) !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            position: relative;
            overflow: hidden;
        }
        body.login::before {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(2,132,199,0.2) 0%, rgba(0,0,0,0) 70%);
            top: -150px;
            left: -150px;
            border-radius: 50%;
            z-index: 0;
        }
        #login {
            width: 420px !important;
            padding: 20px !important;
            z-index: 10;
            position: relative;
        }
        .login form {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            border-radius: 28px !important;
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.1) inset !important;
            padding: 40px !important;
        }
        .login h1 a {
            <?php if ( ! empty( $logo_url ) ) : ?>
                background-image: url('<?php echo esc_url( $logo_url ); ?>') !important;
                background-size: contain !important;
                background-repeat: no-repeat !important;
                background-position: center !important;
                width: 100% !important;
                height: 80px !important;
                margin-bottom: 20px !important;
            <?php else : ?>
                background-image: none !important;
                text-indent: 0 !important;
                color: #0f172a !important;
                font-size: 26px !important;
                font-weight: 800 !important;
                width: 100% !important;
                height: auto !important;
                margin-bottom: 25px !important;
                text-align: center;
                letter-spacing: -0.5px;
            <?php endif; ?>
        }
        <?php if ( empty( $logo_url ) ) : ?>
        .login h1 a::after {
            content: 'Ozone Skypool OS';
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        <?php endif; ?>
        .login label {
            color: #334155 !important;
            font-weight: 700 !important;
            font-size: 13px !important;
            margin-bottom: 6px;
            display: block;
        }
        .login input.input {
            border-radius: 14px !important;
            border: 1.5px solid #cbd5e1 !important;
            padding: 12px 18px !important;
            height: 52px !important;
            font-size: 15px !important;
            background: #f8fafc !important;
            color: #0f172a !important;
            transition: all 0.25s ease;
        }
        .login input.input:focus {
            border-color: #0284c7 !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.15) !important;
        }
        .login .button.wp-submit {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
            border: none !important;
            height: 52px !important;
            border-radius: 16px !important;
            font-weight: 800 !important;
            font-size: 15.5px !important;
            width: 100% !important;
            box-shadow: 0 10px 25px rgba(2, 132, 199, 0.4) !important;
            cursor: pointer;
            transition: all 0.25s ease;
            color: #ffffff !important;
            letter-spacing: 0.3px;
        }
        .login .button.wp-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(2, 132, 199, 0.5) !important;
        }
        #nav, #backtoblog {
            text-align: center;
            margin-top: 20px !important;
        }
        #nav a, #backtoblog a {
            color: rgba(255, 255, 255, 0.85) !important;
            font-weight: 600 !important;
            font-size: 13.5px;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        #nav a:hover, #backtoblog a:hover {
            color: #ffffff !important;
            text-decoration: underline;
        }
        .pms-captcha-wrap input {
            width: 100% !important;
        }
    </style>
    <?php
}

/**
 * 10. Replace Powered by WordPress Footer Text
 */
add_filter( 'login_footertext', 'ifs_pms_custom_login_footer' );

function ifs_pms_custom_login_footer() {
    $b_name = get_option( 'ifs_pms_business_name', '' );
    return sprintf(
        /* translators: 1: current year, 2: company or establishment business name */
        esc_html__( '&copy; %1$d \%2$s &bull; Enterprise Operating System', 'swimming-pool-manager' ),
        (int) gmdate( 'Y' ),
        esc_html( $b_name ? $b_name : 'Ozone Skypool' )
    );
}