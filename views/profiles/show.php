<?php
global $user;
global $meta;
$answers = $meta['answers'];
$fields = (array) get_option(PREFIX . 'fields_profiles');

$allowedAnswers = array_filter((array) $answers, function($answer) use ($fields) {
    if (in_array($answer['default_key'], LOCATION_KEYS)) {
        return false;
    }
  return in_array($answer['qid'], $fields);
});

$locations = is_array($meta['locations']) ? $meta['locations'] : [];
?>
<div class="lbec-profile">
<h1><?php echo $user->display_name ?></h1>
    <?php if (lbec_show_field('answers', 'profile_pictures') && $meta['profile_image']): ?>
        <div class="lbec-profile-image">
            <img src="<?php echo $meta['profile_image'] ?>" alt="<?php echo $user->display_name ?>">
        </div>
    <?php endif; ?>
    <table class="table lbec-table">
        <tbody>
            <?php if (lbec_show_field('profile_fields', 'email')): ?>
                <tr class="lbec-profile-info-field"><th>Email:</th><td><?php echo esc_html(!empty($user->user_email) ? $user->user_email : $meta['parent_email']) ?></td></tr>
            <?php endif; ?>
            <?php if (lbec_show_field('profile_fields', 'phone')): ?>
                <tr class="lbec-profile-info-field"><th>Telefoon:</th><td><?php echo esc_html(!empty($meta['phone']) ? $meta['phone'] : $meta['parent_phone']) ?></td></tr>
            <?php endif; ?>
            <?php if (lbec_show_field('profile_fields', 'age') && !empty($meta['age'])): ?>
                <tr class="lbec-profile-info-field"><th>Leeftijd:</th><td><?php echo esc_html($meta['age']) ?></td></tr>
            <?php endif; ?>
            <?php if (!empty($locations)): ?>
                <?php foreach ($locations as $location): ?>
                    <tr class="lbec-profile-info-field">
                        <th><?php echo $location['name']; ?>:</th>
                        <td>
                            <address>
                                <?php echo lbec_render_location($location); ?>
                            </address>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php foreach ($allowedAnswers as $allowedAnswer):
                $answer = esc_html($allowedAnswer['answer']);
                if (strlen($answer) === 0) continue;
                if (in_array($allowedAnswer['type'], ['image', 'file', 'address'])) continue;
            ?>
                <tr class="lbec-profile-info-field">
                    <th><?php echo esc_html($allowedAnswer['question']); ?><?php echo (strlen($allowedAnswer['course_specific_info']) ? (' <span class="lbec course-specific-info text-muted">(' . $allowedAnswer['course_specific_info'] . ')</span>') : ''); ?>: </th>
                    <td><?php echo $answer; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    //-- Find out link to return to
    $linkBackId = get_option('lb_main_profiles_page');
    $linkBack = get_permalink($linkBackId);
    ?>
    <p><a href="<?php echo $linkBack; ?>" class="button btn btn-secondary">Terug naar overzicht</a></p>
</div>
