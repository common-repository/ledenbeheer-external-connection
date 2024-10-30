<?php
get_header();
include_once __DIR__ . '/../src/calendar.php';
include_once __DIR__ . '/../src/profiles.php';

if (!get_option('lb_sync_profiles')) {
    global $wp_query;
    $wp_query->set_404();

    add_action( 'wp_title', function () {
        return '404: Not Found';
    }, 9999 );

    status_header( 404 );
    nocache_headers();

    require get_404_template();

    exit;
}

$profiles = get_users([
    'meta_query' => [
        'relation' => 'AND',
        [
            'key' => 'lbec',
            'value' => 1
        ],
        [
            'key' => 'visible',
            'value' => 1
        ]
    ]
]);
?>

<main id="site-content" role="main">
    <div class="container">
        <?php lbec_print_profiles($profiles) ?>
    </div>
</main>

<?php get_template_part( 'template-parts/footer-menus-widgets' ); ?>
<?php get_footer(); ?>
