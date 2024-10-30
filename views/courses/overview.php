<?php

function lbec_render_course_overview($attr)
{
    if (isset($attr['type']) && $attr['type'] === 'raster') {
        //-- Render raster
        lbec_render_course_raster();
    } else {
        //-- Render calendar
        lbec_render_course_calendar();
    }
}

function lbec_render_course_calendar()
{
    include_once __DIR__ . '/../../src/calendar.php';
    lbec_render_calendar('lbec_get_courses_for_date');
}

function lbec_render_course_raster()
{
    $posts = get_posts([
        'post_type' => 'courses',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'meta_query' => [
            [
                'key' => 'lb_endDate',
                'value' => (new DateTime())->format('U'),
                'compare' => '>'
            ]
        ]
    ]);
    $skipDetail = get_option(PREFIX . 'skip_course_detail');
    $clubNid = get_option('lb_club_nid');
    if (!empty($posts)): ?>
    <table class="table">
        <thead>
        <tr>
            <th>Cursus</th>
            <th>Startdatum</th>
            <th>Einddatum</th>
            <th># Sessies</th>
            <th>Locatie</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($posts as $course):
            $meta = get_post_meta($course->ID);
            foreach ($meta as $k => &$m) {
                $m = array_shift($m);

                if ($k === 'lb_sessions') {
                    $m = unserialize($m);
                }
            }
            if ($skipDetail) {
                $link = lbec_get_external_link_for_course_activity($clubNid, $meta['lb_nid']);
            } else {
                $link = get_permalink($course->ID);
            }
            ?>
            <tr>
                <td><a href="<?php echo $link ?>"<?php echo $skipDetail ? ' target="_blank"' : ''; ?>><?php echo esc_html($course->post_title) ?></a></td>
                <td><?php echo lbec_unix_to_datetime(esc_html($meta['lb_startDate']))->format(DATE_FORMAT); ?></td>
                <td><?php echo lbec_unix_to_datetime(esc_html($meta['lb_endDate']))->format(DATE_FORMAT); ?></td>
                <td><?php echo count($meta['lb_sessions']); ?></td>
                <td><?php echo esc_html($meta['lb_locationLabel']) ?></td>
                <td><a href="<?php echo $link ?>"<?php echo $skipDetail ? ' target="_blank"' : ''; ?> class="button btn btn-primary">Meer details</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>Er zijn momenteel nog geen cursussen beschikbaar.</p>
    <?php endif;
}
