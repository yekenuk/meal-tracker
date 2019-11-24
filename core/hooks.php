<?php

defined('ABSPATH') or die("Jog on!");

/**
 * Listen for a user saving their settings, if so, do any follow up tasks.
 */
function yk_mt_actions_settings_post_save() {

    // Do we need to consider changing the calories for today's entry based upon the user settings?
    yk_mt_allowed_calories_refresh();

}
add_action( 'yk_mt_settings_saved', 'yk_mt_actions_settings_post_save' );

/**
 * Add a CSS classes to the <body>
 * @param $classes
 * @return array
 */
function yk_mt_body_class( $classes ) {

    if ( true === yk_mt_site_options_as_bool( 'accordion-enabled' ) ) {
        $classes[] = 'yk-mt-accordion-enabled';
    }

    return $classes;
}
add_filter( 'body_class','yk_mt_body_class' );

/**
 * Build admin menu
 */
function yk_mt_build_admin_menu() {

    $permission_level = yk_mt_admin_permission_check_setting();

    add_menu_page( YK_MT_PLUGIN_NAME, YK_MT_PLUGIN_NAME, $permission_level, 'yk-mt-main-menu', '', 'dashicons-chart-pie' );

    // Hide duplicated sub menu (wee hack!)
    add_submenu_page( 'yk-mt-main-menu', '', '', $permission_level, 'yk-mt-main-menu', 'yk_mt_admin_page_data_home');

    add_submenu_page( 'yk-mt-main-menu', __( 'User Data', YK_MT_SLUG ),  __( 'User Data', YK_MT_SLUG ), $permission_level, 'yk-mt-user', 'yk_mt_admin_page_data_home' );

    $menu_text = ( true === yk_mt_license_is_premium() ) ? __( 'Your License', YK_MT_SLUG ) : __( 'Upgrade to Pro', YK_MT_SLUG );
    add_submenu_page( 'yk-mt-main-menu', $menu_text,  $menu_text, 'manage_options', 'yk-mt-license', 'yk_mt_advertise_pro');

    add_submenu_page( 'yk-mt-main-menu', __( 'Settings', YK_MT_SLUG ),  __( 'Settings', YK_MT_SLUG ), 'manage_options', 'yk-mt-settings', 'yk_mt_settings_page_generic' );
    add_submenu_page( 'yk-mt-main-menu', __( 'Help', YK_MT_SLUG ),  __( 'Help', YK_MT_SLUG ), $permission_level, 'yk-mt-help', 'yk_mt_help_page' );
}
add_action( 'admin_menu', 'yk_mt_build_admin_menu' );

/**
 * Enqueue admin JS / CSS
 */
function yk_mt_enqueue_admin_files() {

    if ( false === in_array( $_GET['page'], [ 'yk-mt-user', 'yk-mt-main-menu', 'yk-mt-settings' ] ) ) {
        return;
    }

    wp_enqueue_style( 'yk-mt-admin', plugins_url('../assets/css/admin.css', __FILE__), [], YK_MT_PLUGIN_VERSION );

    // Enqueue admin.js regardless (needed to dismiss notices)
    wp_enqueue_script( 'yk-mt-admin', plugins_url('../assets/js/admin.js', __FILE__), ['jquery'], YK_MT_PLUGIN_VERSION );

    // Settings page
    if ( false === empty( $_GET['page'] ) && true === in_array( $_GET[ 'page' ], [ 'yk-mt-settings' ] ) ) {
        wp_enqueue_script( 'jquery-tabs', plugins_url( '../assets/js/tabs.min.js', __FILE__ ), ['jquery'], YK_MT_PLUGIN_VERSION );
        wp_enqueue_style( 'mt-tabs', plugins_url( '../assets/css/tabs.min.css', __FILE__ ), [], YK_MT_PLUGIN_VERSION );
        wp_enqueue_style( 'mt-tabs-flat', plugins_url( '../assets/css/tabs.flat.min.css', __FILE__ ), [], YK_MT_PLUGIN_VERSION );
    }

    if ( false === empty( $_GET['page'] ) && true === in_array( $_GET['page'], [ 'yk-mt-user', 'yk-mt-main-menu' ] ) ) {

        wp_enqueue_style( 'mt-font-awesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css', [], YK_MT_PLUGIN_VERSION );

        yk_mt_enqueue_scripts_chart();

        yk_mt_enqueue_scripts_footable();

        yk_mt_admin_localise();
    }
}
add_action( 'admin_enqueue_scripts', 'yk_mt_enqueue_admin_files');

/**
 * Enqueue Footable scripts
 */
function yk_mt_enqueue_scripts_footable() {

    wp_enqueue_style( 'mt-footable', plugins_url( '/assets/css/footable.standalone.min.css', __DIR__  ), [], YK_MT_PLUGIN_VERSION );
    wp_enqueue_script( 'mt-footable', plugins_url( '/assets/js/footable.min.js', __DIR__ ), [ 'jquery' ], YK_MT_PLUGIN_VERSION, true );
}

/**
 * Enqueue Chart scripts
 */
function yk_mt_enqueue_scripts_chart() {

    wp_enqueue_script( 'mt-chart-js', YK_MT_CHART_JS, [ 'jquery' ], YK_MT_PLUGIN_VERSION );
    wp_enqueue_script( 'mt-chart', plugins_url( 'assets/js/core.chart.js', __DIR__ ), [ 'jquery', 'mt-chart-js' ], YK_MT_PLUGIN_VERSION, true );
}


/**
 * Add view link alongside WP action links
 * @param $actions
 * @param $user_object
 * @return mixed
 */
function yk_mt_user_action_links( $actions, $user_object ) {

    $profile_url = yk_mt_link_admin_page_user( $user_object->ID );
    $profile_url = yk_mt_link_add_back_link( $profile_url );

    $actions[ 'meal-tracker' ] = sprintf(  '<a href="%s">%s</a>',
        $profile_url,
        __( 'Meal entries', YK_MT_SLUG )
    );

    return $actions;
}
add_filter( 'user_row_actions', 'yk_mt_user_action_links', 10, 2 );

/**
 * Add MT profile button to WT header link
 * @param $links
 * @return string
 */
function yk_mt_wlt_user_profile_add_header_link( $links ) {

    $links .= sprintf( '<a href="%1$s" class="button-secondary"><i class="fa fa-pie-chart"></i> %2$s</a>',
        yk_mt_link_admin_page_user( get_current_user_id() ),
        __('Meal Tracker Record', YK_MT_SLUG )
    );

    return $links;
}
add_filter( 'wt_ls_user_profile_header_links', 'yk_mt_wlt_user_profile_add_header_link' );

/**
 * Add class to admin body if Premium or not
 * @param $classes
 * @return array
 */
function yk_mt_admin_add_body_classes( $existing_classes ) {

    $class = ( true === YK_MT_IS_PREMIUM ) ? 'yk-mt-is-premium' : 'yk-mt-not-premium' ;

    return sprintf('%1$s %2$s', $existing_classes, $class );
}
add_filter( 'admin_body_class', 'yk_mt_admin_add_body_classes' );