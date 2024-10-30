<?php
global $post;
$meta = get_post_meta($post->ID);
foreach ($meta as &$m) {
    $m = array_shift($m);
}

$tariffs = get_post_meta($post->ID, 'lb_tariffs', 'true');
$sessions = get_post_meta($post->ID, 'lb_sessions', 'true');

$ageRestrictions = get_post_meta($post->ID, 'lb_ageRestrictions', true);
$ageRestrictionsByYear = get_post_meta($post->ID, 'lb_ageRestrictionsByYear', true);

$taxonomies = (array) get_post_meta($post->ID, PREFIX . 'taxonomies', true);

//-- Which is used, year or restrictions?
if (!empty($ageRestrictions) && is_array($ageRestrictions) && (int) $ageRestrictions[0] !== 0 && (int) $ageRestrictions[1] !== 0) {
    // by age
    $ageRestriction = (int) $ageRestrictions[0] . ' - ' . (int) $ageRestrictions[1];
} else {
    // by year
    $ageRestriction = (int) $ageRestrictionsByYear[0] . ' - ' . (int) $ageRestrictionsByYear[1];
}
$image = get_post_meta($post->ID, PREFIX . 'image', true);
?>
<h1><?php echo esc_html($post->post_title); ?></h1>
<?php if ($image): ?>
<img src="<?php echo $image; ?>" alt="Afbeelding van <?php echo $post->post_title; ?>" class="lbec-image" style="max-width: 100%;" />
<?php endif; ?>
<?php echo wp_kses($post->post_content, 'post') ?>

<table class="table lbec-table">
    <tbody>
    <tr>
        <th><?php echo __('Startdatum', 'ledenbeheer-external-connection') ?></th>
        <td><?php echo esc_html(lbec_unix_to_datetime($meta['lb_startDate'])->format(DATE_FORMAT)) ?></td>
    </tr>
    <tr>
        <th><?php echo __('Einddatum', 'ledenbeheer-external-connection') ?></th>
        <td><?php echo esc_html(lbec_unix_to_datetime($meta['lb_endDate'])->format(DATE_FORMAT)) ?></td>
    </tr>
    <tr>
        <th><?php echo __('Adres', 'ledenbeheer-external-connection') ?></th>
        <td><?php echo esc_html($meta['lb_locationAddress']) ?></td>
    </tr>
    <?php if ($meta['lb_locationLabel']): ?>
    <tr>
        <th><?php echo __('Locatie', 'ledenbeheer-external-connection') ?></th>
        <td><?php echo esc_html($meta['lb_locationLabel']) ?></td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($ageRestriction)): ?>
    <tr>
        <th><?php echo __('Leeftijdsrestrictie', 'ledenbeheer-external-connection') ?></th>
        <td><?php echo esc_html($ageRestriction) ?></td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($taxonomies)):
        foreach ($taxonomies as $taxonomy): ?>
        <tr>
            <th><?php echo $taxonomy['taxonomy']; ?></th>
            <td><?php echo implode(', ', $taxonomy['items']); ?></td>
        </tr>
        <?php endforeach;
    endif; ?>
    </tbody>
</table>

<h3>Tarieven</h3>
<table class="table lbec-table">
    <thead>
    <tr>
        <th>Titel</th>
        <th>Van</th>
        <th>Tot</th>
        <th>Prijs</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($tariffs as $tariff): ?>
    <?php
        $from = lbec_unix_to_datetime($tariff['period'][0]);
        $to = lbec_unix_to_datetime($tariff['period'][1]);
    ?>
    <tr>
        <td><?php echo esc_html($tariff['title']); ?></td>
        <td><?php echo $from->format(DATE_FORMAT); ?></td>
        <td><?php echo $to->format(DATE_FORMAT); ?></td>
        <td><?php echo esc_html((float) $tariff['price'] == 0 ? 'Gratis' : '€' . $tariff['price']); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php
    $clubNid = get_option('lb_club_nid');
    $courseNid = get_post_meta($post->ID, 'lb_nid', true);
?>
<p>
    <?php
    //-- Find out link to return to
    $linkBackId = get_option('lb_main_courses_page');
    if ($linkBackId) {
        $linkBack = get_permalink($linkBackId);
    } else {
        $postType = get_post_type_object('courses');
        $linkBack = '/' . $postType->rewrite['slug'];
    }
    ?>
    <a href="<?php echo $linkBack; ?>" class="button btn btn-secondary">Terug naar overzicht</a>
    <a href="#" target="_blank" id="view-more" class="button btn btn-primary">Meer info op ledenbeheer</a>
</p>


<script type="application/javascript">
    document.getElementById('view-more').addEventListener('click', function(e) {
        e.preventDefault();
        window.open('https://www.ledenbeheer.be/public/<?php echo esc_html($clubNid) ?>#<?php echo esc_html($courseNid) ?>', '_blank', 'height=500,width=800,location=no,menubar=no,resizable=yes,scrollbars=yes,status=no,titlebar=yes,toolbar=no');
    });
</script>

<?php include dirname(__FILE__) . '/../social.php'; ?>


