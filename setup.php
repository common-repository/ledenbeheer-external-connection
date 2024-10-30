<?php
function lbec_frontpage_admin_menu() {

    add_menu_page(
        'Ledenbeheer',
        'Ledenbeheer',
        'read',
        'ledenbeheer',
        '',
        'dashicons-admin-home',
        40
    );

    add_submenu_page('ledenbeheer', 'Profielen', 'Profielen', 'read', 'users.php');
    add_submenu_page('ledenbeheer', 'Instellingen', 'Instellingen', 'read', 'options-general.php?page=ledenbeheer');
}

add_action( 'admin_menu', 'lbec_frontpage_admin_menu' );

/**
 * Register courses post type
 */
function lbec_courses_type() {
    //-- Create custom post type
    register_post_type('courses', [
        'labels' => [
            'name' => __('Courses', 'ledenbeheer-external-connection'),
            'singular_name' => __('Course', 'ledenbeheer-external-connection')
        ],
        'public' => get_option('lb_sync_courses'),
        'show_in_menu' => 'ledenbeheer',
        'has_archive' => true,
        'rewrite' => ['slug' => 'cursussen'],
        'show_in_rest' => true,
        'supports' => [
            'title', 'editor'
        ],
        'capabilities' => [
            'create_posts' => false
        ],
        'map_meta_cap' => true
    ]);
    flush_rewrite_rules();
}
add_action('init', 'lbec_courses_type');


/**
 * Register activity post type
 */
function lbec_activities_type() {
    //-- Create custom post type
    register_post_type('activities', [
        'labels' => [
            'name' => __('Activities', 'ledenbeheer-external-connection'),
            'singular_name' => __('Activity', 'ledenbeheer-external-connection')
        ],
        'public' => get_option('lb_sync_activities'),
        'show_in_menu' => 'ledenbeheer',
        'has_archive' => true,
        'rewrite' => ['slug' => 'activiteiten'],
        'show_in_rest' => true,
        'supports' => [
            'title', 'editor'
        ],
        'capabilities' => [
            'create_posts' => false
        ],
        'map_meta_cap' => true
    ]);
    flush_rewrite_rules();
}
add_action('init', 'lbec_activities_type');

function lbec_load_plugin_textdomain() {
    load_plugin_textdomain( 'ledenbeheer-external-connection', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'lbec_load_plugin_textdomain' );

function lbec_show_notes() {
    global $post_type;

    $screen = get_current_screen();

    if ($screen->parent_base === 'ledenbeheer' || (isset($_GET['page']) && ($_GET['page'] === 'ledenbeheer'))) {
        $html = '';
        $html .= '<img src="' . plugins_url('assets/img/logo.png', __FILE__) . '" />';
        if (get_option(PREFIX . 'syncing')) {
            if ($post_type === 'courses') {
                $html .= '<div class="note">Cursussen zijn nog steeds aan het laden.</div>';
            }
            if ($post_type === 'activities') {
                $html .= '<div class="note">Extra activiteiten zijn nog steeds aan het laden.</div>';
            }
        }
        echo $html;
    }
}

// Set the function up to execute when the admin_notices action is called
add_action( 'admin_notices', 'lbec_show_notes' );
