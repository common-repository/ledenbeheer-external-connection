<?php
$activityMetaBox = [
    'id' => 'activities-meta-box',
    'title' => 'Extra activiteit info',
    'page' => 'activities',
    'context' => 'normal',
    'priority' => 'high'
];

// Add meta box
function lbec_activities_add_box() {
    global $activityMetaBox;
    add_meta_box($activityMetaBox['id'], $activityMetaBox['title'], 'lbec_activities_show_box', $activityMetaBox['page'], $activityMetaBox['context'], $activityMetaBox['priority']);
}
add_action('admin_menu', 'lbec_activities_add_box');


function lbec_activities_show_box() {
    global $post;
    $meta = get_post_meta($post->ID);
    foreach ($meta as &$m) {
        $m = array_shift($m);
    }

    ?>
    <table class="form-table">
        <tr>
            <th>Naam</th>
            <td><?php echo esc_html($post->post_title) ?></td>
        </tr>
        <tr>
            <th>Adres</th>
            <td><?php echo esc_html($meta['lb_locationAddress']) ?> - <?php echo esc_html($meta['lb_locationLabel']); ?></td>
        </tr>
        <tr>
            <th>Maximum inschrijvingen per sessie</th>
            <td><?php echo $meta['lb_maxAttendeesPerSession'] ? esc_html($meta['lb_maxAttendeesPerSession']) : '&infin;'; ?></td>
        </tr>
        <tr>
            <th>Startdatum</th>
            <td><?php echo lbec_unix_to_datetime($meta['lb_startDate'])->format(DATE_FORMAT) ?></td>
        </tr>
        <tr>
            <th>Einddatum</th>
            <td><?php echo lbec_unix_to_datetime($meta['lb_endDate'])->format(DATE_FORMAT) ?></td>
        </tr>
        <tr>
            <th>Shortcode</th>
            <td><code><?php echo '[lbactivity id="' . get_the_ID() . '"]' ?></code></td>
        </tr>
    </table>
    <?php
}

function lbec_sync_activities() {
    if (!(bool) get_option('lb_sync_activities')) {
        //-- Delete all of them
        foreach (get_posts([
            'post_type' => 'activities',
            'numberposts' => -1
        ]) as $activity) {
            wp_delete_post($activity->ID);
        }
        return;
    }
    $activities = lbec_make_call_to_ledenbeheer(Q_GET_ACTIVITIES_FOR_CLUB);
    if (is_wp_error($activities)) return;

    $ids = [];
    foreach ($activities['result'] as $activity) {
        $args = [
            'post_type' => 'activities',
            'meta_key' => PREFIX . 'nid',
            'meta_value' => $activity['nid'],
            'meta_compare' => '='
        ];

        $ids[] = $activity['nid'];

        $query = new WP_Query($args);
        $matchingActivities = $query->get_posts();
        $matchingActivity = array_pop($matchingActivities);

        if ($matchingActivity instanceof WP_Post) {
            wp_update_post([
                'ID' => $matchingActivity->ID,
                'post_title' => $activity['title'],
                'post_content' => $activity['content']
            ]);
            update_post_meta($matchingActivity->ID, PREFIX . 'locationAddress', $activity['locationAddress']);
            if (isset($activity['locationLabel'])) {
                update_post_meta($matchingActivity->ID, PREFIX . 'locationLabel', $activity['locationLabel']);
            }
            update_post_meta($matchingActivity->ID, PREFIX . 'startDate', $activity['startDate']);
            update_post_meta($matchingActivity->ID, PREFIX . 'endDate', $activity['endDate']);
            update_post_meta($matchingActivity->ID, PREFIX . 'years', $activity['years']);
            update_post_meta($matchingActivity->ID, PREFIX . 'period', $activity['period']);
            update_post_meta($matchingActivity->ID, PREFIX . 'statusKey', $activity['statusKey']);
            $sessions = lbec_filter_disabled_sessions_tariffs($activity['sessions']);
            $tariffs = lbec_filter_disabled_sessions_tariffs($activity['tariffs']);
            update_post_meta($matchingActivity->ID, PREFIX . 'sessions', $sessions);
            update_post_meta($matchingActivity->ID, PREFIX . 'tariffs', $tariffs);
            update_post_meta($matchingActivity->ID, PREFIX . 'ageRestrictions', $activity['ageRestrictions']);
            update_post_meta($matchingActivity->ID, PREFIX . 'ageRestrictionsByYear', $activity['ageRestrictionsByYear']);
            update_post_meta($matchingActivity->ID, PREFIX . 'image', $activity['image'] ?? null);

            $tariffs = [];
            foreach ($activity['tariffs'] as $tariff) {
                $tariffs[] = [
                    'nid' => $tariff['nid'],
                    'title' => $tariff['status'],
                    'price' => $tariff['price'],
                    'type' => $tariff['tariffType'],
                    'period' => $tariff['period'],
                    'enabledPeriod' => $tariff['enabledPeriod']
                ];
            }
            add_post_meta($matchingActivity->ID, PREFIX . 'tariffs', $tariffs);

            $sessions = [];
            foreach ($activity['sessions'] as $session) {
                $sessions[] = [
                    'nid' => $session['nid'],
                    'title' => $session['title'],
                    'date' => $session['date'],
                    'from' => $session['from'],
                    'to' => $session['to']
                ];
            }
            add_post_meta($matchingActivity->ID, PREFIX . 'sessions', $sessions);
        } else {
            $activityId = wp_insert_post([
                'post_title' => $activity['title'],
                'post_type' => 'activities',
                'post_status' => 'publish',
                'post_content' => $activity['content']
            ]);
            add_post_meta($activityId, PREFIX . 'nid', $activity['nid'], true);
            add_post_meta($activityId, PREFIX . 'locationAddress', $activity['locationAddress']);
            if (isset($activity['locationLabel'])) {
                add_post_meta($activityId, PREFIX . 'locationLabel', $activity['locationLabel']);
            }
            add_post_meta($activityId, PREFIX . 'startDate', $activity['startDate']);
            add_post_meta($activityId, PREFIX . 'endDate', $activity['endDate']);
            add_post_meta($activityId, PREFIX . 'years', $activity['years']);
            add_post_meta($activityId, PREFIX . 'period', $activity['period']);
            add_post_meta($activityId, PREFIX . 'statusKey', $activity['statusKey']);
            $sessions = lbec_filter_disabled_sessions_tariffs($activity['sessions']);
            $tariffs = lbec_filter_disabled_sessions_tariffs($activity['tariffs']);
            add_post_meta($activityId, PREFIX . 'sessions', $sessions);
            add_post_meta($activityId, PREFIX . 'tariffs', $tariffs);
            add_post_meta($activityId, PREFIX . 'ageRestrictions', $activity['ageRestrictions']);
            add_post_meta($activityId, PREFIX . 'ageRestrictionsByYear', $activity['ageRestrictionsByYear']);
            if (isset($activity['image'])) {
                add_post_meta($activityId, PREFIX . 'image', $activity['image']);
            }

            $tariffs = [];
            foreach ($activity['tariffs'] as $tariff) {
                $tariffs[] = [
                    'nid' => $tariff['nid'],
                    'title' => $tariff['status'],
                    'price' => $tariff['price'],
                    'type' => $tariff['tariffType'],
                    'period' => $tariff['period'],
                    'enabledPeriod' => $tariff['enabledPeriod']
                ];
            }
            add_post_meta($activityId, PREFIX . 'tariffs', $tariffs);

            $sessions = [];
            foreach ($activity['sessions'] as $session) {
                $sessions[] = [
                    'nid' => $session['nid'],
                    'title' => $session['title'],
                    'date' => $session['date'],
                    'from' => $session['from'],
                    'to' => $session['to']
                ];
            }
            add_post_meta($activityId, PREFIX . 'sessions', $sessions);
        }
    }

    foreach (get_posts([
        'post_type' => 'activities',
        'numberposts' => -1
    ]) as $activity) {
        $nid = get_post_meta($activity->ID, PREFIX . 'nid', true);

        if (!in_array($nid, $ids)) {
            wp_delete_post($activity->ID);
        }
    }
}

function lbec_get_activities_for_date(DateTime $firstDayOfMonth) {
    $lastDayOfMonth = clone $firstDayOfMonth;
    $lastDayOfMonth->modify('last day of this month');
    $lastDayOfMonth->setTime(24, 0, 0, 0);

    $activities = get_posts([
        'post_type' => 'activities',
        'numberposts' => -1,
        'meta_query' => [
            [
                'key' => 'lb_endDate',
                'value' => $firstDayOfMonth->format('U'),
                'compare' => '>'
            ],
            [
                'key' => 'lb_startDate',
                'value' => $lastDayOfMonth->format('U'),
                'compare' => '<'
            ]
        ]
    ]);

    foreach ($activities as &$activity) {
        $activity->startDate = get_post_meta($activity->ID, 'lb_startDate', true);
        $activity->endDate = get_post_meta($activity->ID, 'lb_endDate', true);

        $sessions = array_values(get_post_meta($activity->ID, 'lb_sessions', true));
        if (!empty($sessions)) {
            $activity->startTime = $sessions[0]['from'];
            $activity->endTime = $sessions[1]['to'];
        }
    }

    return $activities;
}

function lbec_render_activity($attr) {
    if (!isset($attr['id'])) {
        return '';
    }

    ob_start();
    $id = $attr['id'];

    $post = get_post($attr['id']);

    $startDate = (int) get_post_meta($id, 'lb_startDate', true);
    $endDate = (int) get_post_meta($id, 'lb_endDate', true);
    $locationAddress = esc_html(get_post_meta($id, 'lb_locationAddress', true));
    $locationLabel = esc_html(get_post_meta($id, 'lb_locationLabel', true));
    $clubNid = esc_html(get_option('lb_club_nid'));
    $courseNid = esc_html(get_post_meta($post->ID, 'lb_nid', true));

    if ($startDate) {
        $startDate = lbec_unix_to_datetime($startDate)->format(DATE_FORMAT);
    }

    if ($endDate) {
        $endDate = lbec_unix_to_datetime($endDate)->format(DATE_FORMAT);
    }

    $html = "<div class='lbec-activity-shortcode'>";
    $html .= "<p><strong>" . esc_html($post->post_title) . "</strong></p>";
    $html .= "<dl>";
    if ($startDate) {
        $html .= "<dt>Van</dt><dd>{$startDate}</dd>";
    }
    if ($endDate) {
        $html .= "<dt>Tot</dt><dd>{$endDate}</dd>";
    }
    if ($locationAddress || $locationLabel) {
        $html .= "<dt>Adres</dt><dd>{$locationAddress} - {$locationLabel}</dd>";
    }
    $html .= "</dl>";
    $html .= "
        <p>
            <a href=\"#\" target=\"_blank\" id=\"view-more-{$id}\">Meer info op ledenbeheer</a>
        </p>
    ";
    $html .= "</div>";
    $html .= "
        <script type=\"application/javascript\">
            document.getElementById('view-more-{$id}').addEventListener('click', function(e) {
                e.preventDefault();
                window.open('https://www.ledenbeheer.be/public/?club={$clubNid}#{$courseNid}', '_blank', 'height=500,width=800,location=no,menubar=no,resizable=yes,scrollbars=yes,status=no,titlebar=yes,toolbar=no');
            });
        </script>
    ";
    echo $html;
    return ob_get_clean();
}
add_shortcode('lbactivity', 'lbec_render_activity');

function lbec_render_activities($attr) {
    include_once __DIR__ . '/../views/activities/overview.php';
    ob_start();
    lbec_render_activity_overview($attr);
    return ob_get_clean();
}
add_shortcode('lbactivities', 'lbec_render_activities');
