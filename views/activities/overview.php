<?php

function lbec_render_activity_overview($attr)
{
    if (isset($attr['type']) && $attr['type'] === 'raster') {
        //-- Render raster
        lbec_render_activity_raster();
    } else {
        //-- Render calendar
        lbec_render_activity_calendar();
    }
}

function lbec_render_activity_calendar()
{
    include_once __DIR__ . '/../../src/calendar.php';
    lbec_render_calendar('lbec_get_activities_for_date');
}

function lbec_render_activity_raster()
{
    $posts = get_posts([
        'post_type' => 'activities',
        'numberposts' => -1,
        'orderby' => 'meta_value_num',
        'meta_key' => 'lb_startDate',
        'order' => 'ASC'
    ]);
    $skipDetail = get_option(PREFIX . 'skip_activity_detail');
    $clubNid = get_option('lb_club_nid');
    if (!empty($posts)): ?>
    <table class="table lbec-table">
        <thead>
        <tr>
            <th>Activiteit</th>
            <th>Startdatum</th>
            <th>Einddatum</th>
            <th>Locatie</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
    <?php
    foreach ($posts as $activity):
        $meta = get_post_meta($activity->ID);
        foreach ($meta as $k => &$m) {
            $m = array_shift($m);
        }

        $startDate = (lbec_unix_to_datetime($meta['lb_startDate']))->format(DATE_FORMAT);
        $endDate = (lbec_unix_to_datetime($meta['lb_endDate']))->format(DATE_FORMAT);

        if ($skipDetail) {
            $link = lbec_get_external_link_for_course_activity($clubNid, $meta['lb_nid']);
        } else {
            $link = get_permalink($activity->ID);
        }

        ?>
        <tr>
            <td><a href="<?php echo $link ?>"<?php echo $skipDetail ? ' target="_blank"' : ''; ?>><?php echo esc_html($activity->post_title); ?></a></td>
            <td><?php echo $startDate; ?></td>
            <td><?php echo $endDate; ?></td>
            <td><?php esc_html($meta['lb_locationLabel']); ?></td>
            <td><a href="<?php echo $link; ?>"<?php echo $skipDetail ? ' target="_blank"' : ''; ?> class="button btn btn-primary">Meer details</a></td>
        </tr>
    <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>Er zijn momenteel nog geen activiteiten beschikbaar.</p>
    <?php endif;
}
