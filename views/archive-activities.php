<?php
get_header();
include_once __DIR__ . '/activities/overview.php';
?>

<main id="site-content" role="main">
    <div class="container">
        <?php if (get_option('lb_activities_display') === 'calendar'): ?>
            <?php lbec_render_activity_calendar() ?>
        <?php else: ?>
            <?php lbec_render_activity_raster() ?>
        <?php endif; ?>
    </div>
</main>

<?php get_template_part( 'template-parts/footer-menus-widgets' ); ?>
<?php get_footer(); ?>
