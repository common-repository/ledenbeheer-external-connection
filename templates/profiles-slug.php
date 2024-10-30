<?php
$id = (int) sanitize_key(get_query_var('id'));
$user = new WP_User($id);

//-- Check if lbec
if (!$user->get('lbec') || !get_option('lb_sync_profiles')) {
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
$meta = get_user_meta($id, '', true);
foreach ($meta as &$m) {
    $m = array_shift($m);
    $unserializedData = @unserialize($m);
    if ($unserializedData) {
        $m = $unserializedData;
    }
}
get_header();
?>

<main id="site-content" role="main">
    <article class="container">
        <?php
        global $user;
        global $meta;
        include dirname(__FILE__) . '/../views/profiles/show.php';
        ?>
    </article>
</main><!-- #site-content -->

<?php get_template_part( 'template-parts/footer-menus-widgets' ); ?>
<?php get_footer(); ?>
