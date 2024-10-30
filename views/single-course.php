<?php
/**
 * The template for displaying single posts and pages.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty
 * @since 1.0.0
 */
if (get_option(PREFIX . 'skip_course_detail')) {
    if ( have_posts() ) {

        while ( have_posts() ) {
            global $post;
            the_post();

            $clubNid = get_option(PREFIX . 'club_nid');
            $courseNid = get_post_meta(get_the_ID(), PREFIX . 'nid', true);

            wp_redirect(lbec_get_external_link_for_course_activity($clubNid, $courseNid));
        }
    }
}
get_header();
?>

<main id="site-content" role="main">
    <article class="container">

    <?php

    if ( have_posts() ) {

        while ( have_posts() ) {
            the_post();

            include dirname(__FILE__) . '/courses/show.php';
        }
    }

    ?>
    </article>
</main><!-- #site-content -->

<?php get_template_part( 'template-parts/footer-menus-widgets' ); ?>

<?php get_footer(); ?>
