<?php
/**
 * Plugin name: Ledenbeheer
 * Description: Koppeling tussen Wordpress en Ledenbeheer
 * Version: 2.1.0
 * Author: Ledenbeheer
 * Author URI: https://ledenbeheer.be
 * Domain Path: /languages
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

const API_ENDPOINT = WP_DEBUG ? 'https://staging.api.ledenbeheer.be' : 'https://api.ledenbeheer.be';
const Q_VALIDATE = 'ec_validate';
const Q_GET_PROFILES_FOR_CLUB = 'ec_get_profiles';
const Q_GET_COURSES_FOR_CLUB = 'ec_get_courses';
const Q_GET_ACTIVITIES_FOR_CLUB = 'ec_get_activities';
const DATE_FORMAT = 'd/m/Y';
const PREFIX = 'lb_';
const LOCATION_KEYS = ['address_street_no', 'address_postal_code', 'address_location', 'address_country'];

//-- One time setup
include_once 'setup.php';

//-- Include handles
include_once 'src/profiles.php';
include_once 'src/courses.php';
include_once 'src/activities.php';

//-- Crons
include_once 'crons.php';

//-- Settings
include_once 'settings.php';

//-- Auth
include_once 'auth.php';

/**
 * Retreive API options
 */
function lbec_get_api_key_and_club_nid() {
    return [
        'api_key' => get_option('lb_api_key'),
        'club' => get_option('lb_club_nid')
    ];
}

/**
 * Main call for ledenbeheer requests
 */
function lbec_make_call_to_ledenbeheer($q, array $args = [])
{
    if( !function_exists('get_plugin_data') ) {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    }
    $plugin_data = get_plugin_data( __FILE__ );
    $plugin_version = $plugin_data['Version'];

    $apiKeyAndClubNid = lbec_get_api_key_and_club_nid();
    $args = array_merge($args, [
        'club' => $apiKeyAndClubNid['club'],
        'api_key' => $apiKeyAndClubNid['api_key'],
        'version' => $plugin_version,
        'timeout' => 10
    ]);

    $response = wp_remote_post(API_ENDPOINT . '?q=' . $q, [
        'body' => $args,
        'headers' => [
          'Referer' => get_home_url()
        ]
    ]);
    if (is_wp_error($response)) {
        return $response;
    }
    $body = wp_remote_retrieve_body($response);
    return json_decode($body, true);
}

function lbec_get_custom_archive_type_template($archive_template) {
    global $post;

    if ($post->post_type == 'activities') {
        $archive_template = dirname( __FILE__ ) . '/views/archive-activities.php';
    }
    if ($post->post_type == 'courses') {
        $archive_template = dirname( __FILE__ ) . '/views/archive-courses.php';
    }
    return $archive_template;
}

add_filter( "archive_template", "lbec_get_custom_archive_type_template" );

function lbec_get_custom_single_type_template($archive_template) {
    global $post;

    if ($post->post_type == 'activities') {
        $archive_template = dirname( __FILE__ ) . '/views/single-activity.php';
    }

    if ($post->post_type == 'courses') {
        $archive_template = dirname( __FILE__ ) . '/views/single-course.php';
    }
    return $archive_template;
}

add_filter( "single_template", "lbec_get_custom_single_type_template" );

function lbec_front_styles() {
    wp_register_style('lbec', plugins_url('assets/css/lbec.css', __FILE__));
    wp_register_style('calendar', plugins_url('assets/css/calendar.css', __FILE__));
    wp_enqueue_style('calendar');
    wp_enqueue_style('lbec');
}
add_action('wp_print_styles', 'lbec_front_styles');

function lbec_admin_style() {
    wp_register_style('note', plugins_url('assets/css/note.css', __FILE__));
    wp_register_style('fontawesome', 'https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
    wp_enqueue_style('note');
    wp_enqueue_style('fontawesome');
}
add_action('admin_enqueue_scripts', 'lbec_admin_style');

function lbec_front_scripts() {
    wp_register_script('profiles', plugins_url('assets/js/profiles.js', __FILE__));
    wp_register_script('calendar', plugins_url('assets/js/calendar.js', __FILE__));
    wp_enqueue_script('calendar');
    wp_enqueue_script('profiles');
}
add_action('wp_print_scripts', 'lbec_front_scripts');

function lbec_unix_to_datetime($time)
{
    $datetime = (new DateTime())->setTimestamp($time);
    return lbec_get_utc_time($datetime);
}

/**
 * @param $date
 * @param false $invert
 * @return DateTime
 */
function lbec_get_utc_time($date, $invert = false) {
    $return = clone $date;
    if ($invert) {
        $return->setTimeZone(new DateTimeZone('Europe/London'));
    } else {
        $return->setTimeZone(new DateTimeZone('Europe/Brussels'));
    }
    return $return;
}

function lbec_get_events_for_date($events, DateTime $date) {
    $date = clone $date;
    $startDate = clone $date;
    $startDate->setTimezone(new DateTimeZone('Europe/Brussels'));
    $startDate->setTime(0, 0, 0, 0);

    $endDate = clone $date;
    $endDate->setTimezone(new DateTimeZone('Europe/Brussels'));
    $endDate->setTime(24, 0, 0, 0);

    $result = array_filter($events, function(&$event) use ($startDate) {
        if ((int) $event->startDate > (int) $startDate->format('U')) {
            return false;
        }
        if ((int) $event->endDate < (int) $startDate->format('U')) {
            return false;
        }

        if (!empty($event->sessions)) {
            $sessions = lbec_filter_sessions_for_date($event->sessions, $startDate);
            if (empty($sessions)) {
                return false;
            }
        }

        if (empty($event->startTime)) {
            return false;
        }

        return true;
    });

    //-- Sort by start time
    if (!empty($result)) {
        $startTimes = array_column($result, 'startTime');
        array_multisort($startTimes, SORT_ASC, $result);
    }

    return $result;
}

function lbec_filter_sessions_for_date(array $sessions, DateTime $date) {
    return array_filter($sessions, function($session) use ($date) {
        return (int) $date->format('U') === (int) $session['date'];
    });
}

function lbec_filter_disabled_sessions_tariffs($sessions) {
    return array_filter($sessions, function($session) {
        return (int) $session['status'] === 1;
    });
}

function lbec_show_field($type, $field) {
    if ($type === 'answers') {
        $default_key = $field;

        //-- Get qid for default key
        $questions = get_transient('lb_questions');
        $questions = array_filter($questions, function($question) use ($default_key) {
            return $question['default_key'] === $default_key;
        });

        if (empty($questions)) {
            return false;
        }

        $fieldQid = array_keys($questions)[0];

        return in_array($fieldQid, (array) get_option('lb_fields_profiles', []));
    }

    $fieldsToBeDisplayed = get_option('lb_' . $type);
    if (!is_array($fieldsToBeDisplayed)) {
        return false;
    }

    return array_search($field, $fieldsToBeDisplayed) !== false;
}

function lbec_get_external_link_for_course_activity($clubNid, $courseActivityNid) {
    return 'https://www.ledenbeheer.be/public/' . $clubNid . '#' . $courseActivityNid;
}

register_activation_hook(__FILE__, 'add_profiles_page');
function add_profiles_page() {
    //-- See if page with meta lbec === profiles exists
    $pages = get_pages([
        'meta_key' => 'lbec',
        'meta_value' => 'profiles',
        'meta_compare' => '=',
        'post_status' => 'draft,publish'
    ]);

    if (empty($pages)) {
        // Create post object
        $my_post = array(
            'post_title'    => wp_strip_all_tags( 'Gebruikers' ),
            'post_content'  => '[lbprofiles]',
            'post_status'   => 'draft',
            'post_author'   => 1,
            'post_type'     => 'page',
        );

        // Insert the post into the database
        $pageId = wp_insert_post( $my_post );
        update_post_meta($pageId, 'lbec', 'profiles');

        //-- Save profiles redirect option
        update_option('lb_main_profiles_page', $pageId);
    }
}

function lbec_register_my_bulk_actions($bulk_actions) {
    $bulk_actions['hide'] = 'Verberg contacten';
    $bulk_actions['show'] = 'Toon contacten';
    unset($bulk_actions['edit']);
    unset($bulk_actions['trash']);
    return $bulk_actions;
}
add_filter( 'bulk_actions-users', 'lbec_register_my_bulk_actions' );

function lbec_my_bulk_action_handler( $redirect_to, $doaction, $userIds ) {
    if ($doaction === 'hide') {
        foreach ($userIds as $userId) {
            update_user_meta($userId, 'visible', 0);
        }
    }
    if ($doaction === 'show') {
        foreach ($userIds as $userId) {
            update_user_meta($userId, 'visible', 1);
        }
    }
    return $redirect_to;
}
add_filter( 'handle_bulk_actions-users', 'lbec_my_bulk_action_handler', 10, 3 );


function lbec_set_custom_edit_profile_columns($columns) {
    $columns['visibility'] = 'Zichtbaarheid';
    return $columns;
}
add_filter( 'manage_users_columns', 'lbec_set_custom_edit_profile_columns' );


// Add the data to the custom columns for the book post type:
function lbec_modify_user_table_row( $val, $column, $user_id ) {
    switch ($column) {
        case 'visibility' :
            return get_user_meta($user_id, 'visible', true) ? 'Ja' : 'Nee';
            break;
    }
    return $val;
}
add_action( 'manage_users_custom_column' , 'lbec_modify_user_table_row', 10, 3);

add_filter('kses_allowed_protocols', function ($protocols) {
    $protocols[] = 'data';
    return $protocols;
});

add_action( 'init',  function() {
    add_rewrite_rule( 'profielen/([a-z0-9-]+)/([a-z0-9-]+)', 'index.php?profielen=1&slug=$matches[1]&id=$matches[2]', 'top' );
});

add_filter( 'query_vars', function( $query_vars ) {
    $query_vars[] = 'slug';
    $query_vars[] = 'id';
    $query_vars[] = 'profielen';
    return $query_vars;
});

add_action( 'template_include', function( $template ) {
    if ( get_query_var( 'profielen' ) == false || get_query_var( 'profielen' ) == '' ) {
        return $template;
    }
    return dirname( __FILE__ ) . '/templates/profiles-slug.php';
} );
