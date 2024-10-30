<?php
$courseMetaBox = [
    'id' => 'courses-meta-box',
    'title' => 'Cursus info',
    'page' => 'courses',
    'context' => 'normal',
    'priority' => 'high'
];

// Add meta box
function lbec_courses_add_box() {
    global $courseMetaBox;
    add_meta_box($courseMetaBox['id'], $courseMetaBox['title'], 'lbec_courses_show_box', $courseMetaBox['page'], $courseMetaBox['context'], $courseMetaBox['priority']);
}
add_action('admin_menu', 'lbec_courses_add_box');

function lbec_courses_show_box() {
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
            <td><?php echo esc_html(['lb_maxAttendeesPerSession']); ?></td>
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
            <td><code><?php echo '[lbcourse id="' . get_the_ID() . '"]' ?></code></td>
        </tr>
    </table>
    <?php
}

function lbec_sync_courses()
{
    if (!(bool) get_option('lb_sync_courses')) {
        //-- Delete all of them
        foreach (get_posts([
            'post_type' => 'courses',
            'numberposts' => -1
        ]) as $course) {
            wp_delete_post($course->ID);
        }
        return;
    }

    $coursesResponse = lbec_make_call_to_ledenbeheer(Q_GET_COURSES_FOR_CLUB);
    if (is_wp_error($coursesResponse)) return;

    $ids = [];
    foreach ($coursesResponse['result'] as $course) {
        //-- Check if course exists
        $args = [
            'post_type' => 'courses',
            'meta_key' => PREFIX . 'nid',
            'meta_value' => $course['nid'],
            'meta_compare' => '='
        ];
        $query = new WP_Query($args);
        $matchingCourses = $query->get_posts();
        $matchingCourse = array_pop($matchingCourses);

        $ids[] = $course['nid'];

        if ($matchingCourse instanceof WP_Post) {
            wp_update_post([
                'ID' => $matchingCourse->ID,
                'post_title' => $course['title'],
                'post_content' => $course['content']
            ]);

            update_post_meta($matchingCourse->ID, PREFIX . 'modified', $course['modified'], true);
            update_post_meta($matchingCourse->ID, PREFIX . 'locationAddress', $course['locationAddress']);
            if (isset($course['locationLabel'])) {
                update_post_meta($matchingCourse->ID, PREFIX . 'locationLabel', $course['locationLabel']);
            }
            update_post_meta($matchingCourse->ID, PREFIX . 'maxAttendeesPerSession', $course['maxAttendeesPerSession']);
            update_post_meta($matchingCourse->ID, PREFIX . 'startDate', $course['startDate']);
            update_post_meta($matchingCourse->ID, PREFIX . 'endDate', $course['endDate']);
            $sessions = lbec_filter_disabled_sessions_tariffs($course['sessions']);
            $tariffs = lbec_filter_disabled_sessions_tariffs($course['tariffs']);
            update_post_meta($matchingCourse->ID, PREFIX . 'tariffs', $tariffs);
            update_post_meta($matchingCourse->ID, PREFIX . 'sessions', $sessions);
            update_post_meta($matchingCourse->ID, PREFIX . 'ageRestrictions', $course['ageRestrictions']);
            update_post_meta($matchingCourse->ID, PREFIX . 'ageRestrictionsByYear', $course['ageRestrictionsByYear']);
            update_post_meta($matchingCourse->ID, PREFIX . 'taxonomies', $course['taxonomies']);
            update_post_meta($matchingCourse->ID, PREFIX . 'image', $course['image'] ?? null);
        } else {
            //-- Not found ,add it!
            $courseId = wp_insert_post([
                'post_title' => $course['title'],
                'post_type' => 'courses',
                'post_status' => 'publish',
                'post_content' => $course['content']
            ]);
            add_post_meta($courseId, PREFIX . 'nid', $course['nid'], true);
            add_post_meta($courseId, PREFIX . 'created', $course['created']);
            add_post_meta($courseId, PREFIX . 'modified', $course['modified']);
            add_post_meta($courseId, PREFIX . 'locationAddress', $course['locationAddress']);
            if (isset($course['locationLabel'])) {
                add_post_meta($courseId, PREFIX . 'locationLabel', $course['locationLabel']);
            }
            add_post_meta($courseId, PREFIX . 'maxAttendeesPerSession', $course['maxAttendeesPerSession']);
            add_post_meta($courseId, PREFIX . 'startDate', $course['startDate']);
            add_post_meta($courseId, PREFIX . 'endDate', $course['endDate']);
            $sessions = lbec_filter_disabled_sessions_tariffs($course['sessions']);
            $tariffs = lbec_filter_disabled_sessions_tariffs($course['tariffs']);
            add_post_meta($courseId, PREFIX . 'tariffs', $tariffs);
            add_post_meta($courseId, PREFIX . 'sessions', $sessions);
            add_post_meta($courseId, PREFIX . 'ageRestrictionsByYear', $course['ageRestrictionsByYear']);
            add_post_meta($courseId, PREFIX . 'ageRestrictions', $course['ageRestrictions']);
            add_post_meta($courseId, PREFIX . 'taxonomies', $course['taxonomies']);
            if (isset($course['image'])) {
                add_post_meta($courseId, PREFIX . 'image', $course['image']);
            }
        }
    }

    foreach (get_posts([
        'post_type' => 'courses',
        'numberposts' => -1
    ]) as $course) {
        $id = get_post_meta($course->ID, PREFIX . 'nid', true);

        if (!in_array($id, $ids)) {
            wp_delete_post($course->ID);
        }
    }
}

function lbec_get_courses_for_date(DateTime $firstDayOfMonth) {
    $lastDayOfMonth = clone $firstDayOfMonth;
    $lastDayOfMonth->modify('last day of this month');
    $lastDayOfMonth->setTime(24, 0, 0, 0);

    $courses = get_posts([
        'post_type' => 'courses',
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

    foreach ($courses as &$course) {
        $course->startDate = get_post_meta($course->ID, 'lb_startDate', true);
        $course->endDate = get_post_meta($course->ID, 'lb_endDate', true);

        $sessions = array_values(get_post_meta($course->ID, 'lb_sessions', true));
        if (!empty($sessions)) {
            $course->startTime = $sessions[0]['from'];
            $course->endTime = $sessions[1]['to'];
        }
        $course->sessions = $sessions;
    }

    return $courses;
}

function lbec_render_course($attr) {
    if (!isset($attr['id'])) {
        return;
    }
    ob_start();


    $id = $attr['id'];

    $post = get_post($attr['id']);
    if (!$post) {
        die('Cursus niet gevonden.');
    }

    $startDate = get_post_meta($id, 'lb_startDate', true);
    $endDate = get_post_meta($id, 'lb_endDate', true);
    $locationAddress = esc_html(get_post_meta($id, 'lb_locationAddress', true));
    $locationLabel = esc_html(get_post_meta($id, 'lb_locationLabel', true));
    $maxAttendeesPerSession = esc_html(get_post_meta($id, 'lb_maxAttendeesPerSession', true));
    $clubNid = esc_html(get_option('lb_club_nid'));
    $courseNid = esc_html(get_post_meta($post->ID, 'lb_nid', true));
    ?>
        <div class="lbec-course-shortcode">
            <p><strong><?php echo esc_html($post->post_title) ?></strong></p>
            <dl>
                <dt>Van</dt>
                <dd><?php echo lbec_unix_to_datetime($startDate)->format(DATE_FORMAT) ?></dd>
                <dt>Tot</dt>
                <dd><?php echo lbec_unix_to_datetime($endDate)->format(DATE_FORMAT) ?></dd>
                <dt>Adres</dt>
                <dd><?php echo $locationAddress . ' - ' . $locationLabel ?></dd>
                <dt>Maximum inschrijvingen per sessie</dt>
                <dd><?php echo $maxAttendeesPerSession ?></dd>
            </dl>
            <p>
                <a href="#" target="_blank" id="view-more-<?php echo $id ?>">Meer info op ledenbeheer</a>
            </p>
        </div>
        <script type="application/javascript">
            document.getElementById('view-more-<?php echo $id ?>').addEventListener('click', function(e) {
                e.preventDefault();
                window.open('https://www.ledenbeheer.be/public/?club=<?php echo $clubNid ?>>#<?php echo $courseNid ?>', '_blank', 'height=500,width=800,location=no,menubar=no,resizable=yes,scrollbars=yes,status=no,titlebar=yes,toolbar=no');
            });
        </script>
    <?php
    return ob_get_clean();
}
add_shortcode('lbcourse', 'lbec_render_course');

function lbec_render_courses($attr) {
    include_once __DIR__ . '/../views/courses/overview.php';
    ob_start();
    lbec_render_course_overview($attr);
    return ob_get_clean();
}
add_shortcode('lbcourses', 'lbec_render_courses');
