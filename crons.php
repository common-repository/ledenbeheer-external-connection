<?php
function lbec_cron_sync()
{
    update_option(PREFIX . 'syncing', true);
    lbec_sync_profiles();
    lbec_sync_courses();
    lbec_sync_activities();
    update_option(PREFIX . 'syncing', false);
}

add_action('rest_api_init', function() {
   register_rest_route('ledenbeheer/v1', '/sync', [
       'methods' => 'GET',
       'callback' => 'lbec_cron_sync'
   ]);
});
