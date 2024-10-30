<?php

add_filter('authenticate', 'lbec_auth', 30, 3);
function lbec_auth($user, $username, $password)
{
    /**
     * Use WP user if logged in
     */
    if ($user instanceof WP_User) {
        return $user;
    }

    //-- Check if login enabled
    $lbLoginEnabled = (bool) get_option(PREFIX . 'enable_login');

    if (!$lbLoginEnabled) {
        return $user;
    }

    if ($username == '' || $password == '') return;

    $response = wp_remote_get("https://api.ledenbeheer.be/?__branch=ledenbeheer&q=login&callback=_cbd&username=$username&password=$password");
    if (is_array($response) && !is_wp_error($response)) {
        $ext_auth = $response['body'];

        if (strpos($ext_auth, '_cbd') !== false) {
            $ext_auth = substr($ext_auth, strlen('_cbd('), strlen($ext_auth) - (strlen('_cbd(') + 1));
            $result = json_decode($ext_auth, true);
        } else {
            $result = json_decode($ext_auth, true);
        }

        if ($result['result'] !== true) {
            $user = new WP_Error('denied', __("ERROR: User/pass bad"));
        } else {
            $user = get_users([
                'search' => $username,
                'search_columns' => [
                    'user_email'
                ],
                'meta_key' => 'is_main_account',
                'meta_value' => true,
                'meta_compare' => '='
            ]);
            $user = array_shift($user);

            if (!$user) {
                $user = get_users([
                    'search' => $username,
                    'search_columns' => [
                        'user_email'
                    ]
                ]);
                $user = array_shift($user);

                if (!$user) {
                    $user = new WP_Error('denied', __("ERROR: Not a valid user for this system"));
                }
            }
        }
    } else {
        return new WP_Error('denied', __("ERROR: User/pass bad"));
    }

    return $user;
}

/**
 * This allows users to have same e-mail, this is needed because Ledenbeheer has child accounts, and they should have seperate users but same e-mail address
 */
add_filter('pre_user_email', 'skip_email_exist');
function skip_email_exist($user_email){
    if (!defined('WP_IMPORTING')) {
        define( 'WP_IMPORTING', 'SKIP_EMAIL_EXIST' );
    }
    return $user_email;
}
