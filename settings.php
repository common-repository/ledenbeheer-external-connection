<?php
global $optionsPageHook;
/**
 * Register settings in WP db
 */
function lbec_register_settings() {
    add_option( PREFIX . 'api_key');
    add_option( PREFIX . 'club_nid');
    add_option( PREFIX . 'google_api_key');
    add_option( PREFIX . 'profile_fields', []);
    add_option( PREFIX . 'course_fields', []);
    add_option( PREFIX . 'courses_display');
    add_option( PREFIX . 'activities_display');
    add_option( PREFIX . 'status');
    add_option( PREFIX . 'sync_profiles', false);
    add_option( PREFIX . 'sync_courses', true);
    add_option( PREFIX . 'sync_activities', true);
    add_option( PREFIX . 'filter_profiles', []);
    add_option( PREFIX . 'fields_profiles', []);
    add_option( PREFIX . 'syncing', false);
    add_option( PREFIX . 'main_profiles_page');
    add_option( PREFIX . 'main_courses_page');
    add_option( PREFIX . 'main_activities_page');
    add_option( PREFIX . 'skip_course_detail');
    add_option( PREFIX . 'skip_activity_detail');
    add_option( PREFIX . 'enable_login');

    register_setting( 'ledenbeheer_options_group', PREFIX . 'api_key');
    register_setting( 'ledenbeheer_options_group', PREFIX . 'club_nid');
    register_setting( 'ledenbeheer_options_group', PREFIX . 'google_api_key');
    register_setting( 'ledenbeheer_options_group', PREFIX . 'profile_fields');
    register_setting( 'ledenbeheer_options_group', PREFIX . 'course_fields');
    register_setting( 'ledenbeheer_options_group', PREFIX . 'courses_display');
    register_setting( 'ledenbeheer_options_group', PREFIX . 'activities_display');
    register_setting( 'ledenbeheer_options_group', PREFIX . 'status');
    register_setting( 'ledenbeheer_options_group', PREFIX . 'sync_profiles');
    register_setting( 'ledenbeheer_options_group', PREFIX . 'sync_courses');
    register_setting( 'ledenbeheer_options_group', PREFIX . 'sync_activities');
    register_setting( 'ledenbeheer_options_group', PREFIX . 'filter_profiles');
    register_setting( 'ledenbeheer_options_group', PREFIX . 'fields_profiles');
    register_setting( 'ledenbeheer_options_group', PREFIX . 'syncing');
    register_setting( 'ledenbeheer_options_group', PREFIX . 'main_profiles_page');
    register_setting( 'ledenbeheer_options_group', PREFIX . 'main_courses_page');
    register_setting( 'ledenbeheer_options_group', PREFIX . 'main_activities_page');
    register_setting( 'ledenbeheer_options_group', PREFIX . 'skip_course_detail');
    register_setting( 'ledenbeheer_options_group', PREFIX . 'skip_activity_detail');
    register_setting( 'ledenbeheer_options_group', PREFIX . 'enable_login');
    remove_post_type_support('profiles', 'title');
    remove_post_type_support('courses', 'title');
    remove_post_type_support('courses', 'editor');
    remove_post_type_support('activities', 'title');
    remove_post_type_support('activities', 'editor');
}
add_action( 'admin_init', 'lbec_register_settings' );

/**
 * Register settings in WP menu
 */
function lbec_register_options_page() {
    global $optionsPageHook;
    $optionsPageHook = add_options_page('Ledenbeheer options', 'Ledenbeheer', 'manage_options', 'ledenbeheer', 'lbec_options_page');
    add_action('load-' . $optionsPageHook, 'lbec_on_options_save');
}
add_action('admin_menu', 'lbec_register_options_page');

/**
 * Render the settings page
 */
function lbec_options_page()
{
    $questions = get_transient('lb_questions');
    ?>
    <div>
        <h1>Ledenbeheer Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'ledenbeheer_options_group' ); ?>
            <h2>Algemeen</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="lb_api_key">API key</label></th>
                    <td><input type="text" id="lb_api_key" name="lb_api_key" value="<?php echo esc_html(get_option('lb_api_key')); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="lb_club_nid">Club ID</label></th>
                    <td><input type="text" id="lb_api_key" name="lb_club_nid" value="<?php echo esc_html(get_option('lb_club_nid')); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="lb_google_api_key">Google API key</label></th>
                    <td><input type="text" id="lb_google_api_key" name="lb_google_api_key" value="<?php echo esc_html(get_option('lb_google_api_key')); ?>" /></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td style="color: <?php echo get_option('lb_status') ? 'green' : 'red'; ?>"><?php echo get_option('lb_status', false) ? 'Actief' : 'Inactief' ?></td>
                </tr>
                <tr>
                    <th scope="row"><label for="lb_enable_login">Login toestaan</label></th>
                    <td><input type="checkbox" id="lb_enable_login" name="lb_enable_login"<?php echo get_option('lb_enable_login') ? 'checked': ''; ?> /></td>
                </tr>
                <tr>
                    <th>Synchronisatie opties</th>
                    <td>
                        <fieldset>
                            <label for="lb_sync_profiles">
                                <input name="lb_sync_profiles" type="checkbox" id="lb_sync_profiles" <?php echo get_option('lb_sync_profiles') ? 'checked' : ''?>> Synchroniseren van profielen
                            </label><br/>
                            <label for="lb_sync_courses">
                                <input name="lb_sync_courses" type="checkbox" id="lb_sync_courses" <?php echo get_option('lb_sync_courses') ? 'checked' : ''?>> Synchroniseren van cursussen
                            </label><br/>
                            <label for="lb_sync_activities">
                                <input name="lb_sync_activities" type="checkbox" id="lb_sync_activities" <?php echo get_option('lb_sync_activities') ? 'checked' : ''?>> Synchroniseren van activiteiten
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
            <h2>Keuzes werking</h2>
            <table class="form-table">
                <tr>
                    <th><label for="lb_main_profiles_page">Hoofdpagina profielen</label></th>
                    <td>
                        <select name="lb_main_profiles_page" id="lb_main_profiles_page" required>
                            <?php foreach(get_pages(['post_status' => 'draft,publish']) as $page): ?>
                            <option value="<?php echo $page->ID; ?>"<?php echo (int) get_option('lb_main_profiles_page') === $page->ID ? ' selected="selected"' : ''; ?>><?php echo $page->post_title; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="lb_main_courses_page">Hoofdpagina cursussen</label></th>
                    <td>
                        <select name="lb_main_courses_page" id="lb_main_courses_page">
                            <option value="">Gebruik de standaard pagina</option>
                            <?php foreach(get_pages(['post_status' => 'draft,publish']) as $page): ?>
                                <option value="<?php echo $page->ID; ?>"<?php echo (int) get_option('lb_main_courses_page') === $page->ID ? ' selected="selected"' : ''; ?>><?php echo $page->post_title; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="lb_main_activities_page">Hoofdpagina activiteiten</label></th>
                    <td>
                        <select name="lb_main_activities_page" id="lb_main_activities_page">
                            <option value="">Gebruik de standaard pagina</option>
                            <?php foreach(get_pages(['post_status' => 'draft,publish']) as $page): ?>
                                <option value="<?php echo $page->ID; ?>"<?php echo (int) get_option('lb_main_activities_page') === $page->ID ? ' selected="selected"' : ''; ?>><?php echo $page->post_title; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="lb_skip_course_detail">Sla cursus detail over</label></th>
                    <td><input type="checkbox" name="lb_skip_course_detail" id="lb_skip_course_detail"<?php echo get_option(PREFIX . 'skip_course_detail') ? ' checked="checked"' : ''; ?> /></td>
                </tr>
                <tr>
                    <th><label for="lb_skip_activity_detail">Sla activiteit detail over</label></th>
                    <td><input type="checkbox" name="lb_skip_activity_detail" id="lb_skip_activity_detail"<?php echo get_option(PREFIX . 'skip_activity_detail') ? ' checked="checked"' : ''; ?> /></td>
                </tr>
            </table>

            <h2>Weergave</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="lb_courses_display">Weergave cursusaanbod</label></th>
                    <td>
                        <select name="lb_courses_display" id="lb_courses_display">
                            <option value="raster"<?php echo get_option('lb_courses_display') === 'raster' ? ' selected="true"' : '' ?>>Raster</option>
                            <option value="calendar"<?php echo get_option('lb_courses_display') === 'calendar' ? ' selected="true"' : '' ?>>Kalender</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="lb_activities_display">Weergave activiteiten</label></th>
                    <td>
                        <select name="lb_activities_display" id="lb_activities_display">
                            <option value="raster"<?php echo get_option('lb_activities_display') === 'raster' ? ' selected="true"' : '' ?>>Raster</option>
                            <option value="calendar"<?php echo get_option('lb_activities_display') === 'calendar' ? ' selected="true"' : '' ?>>Kalender</option>
                        </select>
                    </td>
                </tr>
            </table>
            <h2>Shortcodes</h2>
            <table class="form-table">
                <tr>
                    <th>Cursusaanbod</th>
                    <td><code>[lbcourses]</code></td>
                </tr>
                <tr>
                    <th>Cursusaanbod (raster)</th>
                    <td><code>[lbcourses type="raster"]</code></td>
                </tr>
                <tr>
                    <th>Extra activiteiten</th>
                    <td><code>[lbactivities]</code></td>
                </tr>
                <tr>
                    <th>Extra activiteiten (raster)</th>
                    <td><code>[lbactivities type="raster"]</code></td>
                </tr>
                <tr>
                    <th>Contacten</th>
                    <td><code>[lbprofiles]</code></td>
                </tr>
                <tr>
                    <th>Cursus / Activiteit</th>
                    <td>De shortcode voor deze kan je vinden bij het bewerken van een cursus/activiteit.</td>
                </tr>
            </table>
            <h2>Instellingen profielen</h2>
            <h3>Weergave velden</h3>
            <p>Selecteer welke velden je wilt laten weergeven op de profielpagina's.</p>
            <table class="form-table">
                <tr>
                    <th>Weergave</th>
                    <td>
                        <fieldset>
                            <label for="lb_profile_field_email">
                                <input name="lb_profile_fields[]" value="email" type="checkbox" id="lb_profile_field_email" <?php echo lbec_isOptionChecked('profile_fields', 'email') ?>>E-mail
                            </label><br/>
                            <label for="lb_profile_field_age">
                                <input name="lb_profile_fields[]" value="age" type="checkbox" id="lb_profile_field_age" <?php echo lbec_isOptionChecked('profile_fields', 'age') ?>>Leeftijd
                            </label><br/>
                            <label for="lb_profile_field_phone">
                                <input name="lb_profile_fields[]" value="phone" type="checkbox" id="lb_profile_field_phone" <?php echo lbec_isOptionChecked('profile_fields', 'phone') ?>>Telefoon
                            </label><br/>
                            <?php if (is_array($questions) && count($questions) > 0): ?>
                                <?php foreach ($questions as $qid => $question): ?>
                                    <label for="lb_fields_profiles_qid_<?php echo esc_html($qid); ?>">
                                        <input name="lb_fields_profiles[]" value="<?php echo esc_html($qid) ?>" type="checkbox" id="lb_fields_profiles_qid_<?php echo esc_html($qid); ?>" <?php echo lbec_isOptionChecked('fields_profiles', $qid) ?>><?php echo esc_html($question['label']); ?>
                                    </label><br/>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>Momenteel geen vragen beschikbaar</p>
                            <?php endif; ?>
                        </fieldset>
                    </td>
                </tr>
            </table>
            <h3>Filter velden</h3>
            <p>Selecteer op welke velden je wilt filteren op het profielen overzicht.</p>
            <table class="form-table">
                <tr>
                    <th>Filter</th>
                    <td>
                        <fieldset>
                            <?php if (is_array($questions) && count($questions) > 0): ?>
                                <?php foreach ($questions as $qid => $question): ?>
                                    <label for="lb_filter_profiles_qid_<?php echo esc_html($qid); ?>">
                                        <input name="lb_filter_profiles[]" value="<?php echo esc_html($qid) ?>" type="checkbox" id="lb_filter_profiles_qid_<?php echo esc_html($qid); ?>" <?php echo lbec_isOptionChecked('filter_profiles', $qid) ?>><?php echo esc_html(is_array($question) ? $question['label'] : $question); ?>
                                    </label><br/>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>Momenteel geen vragen beschikbaar</p>
                            <?php endif; ?>
                        </fieldset>
                    </td>
                </tr>
            </table>
            <?php  submit_button(); ?>
        </form>
    </div>
    <?php
}

add_action('update_option_lb_filter_profiles', function( $old_value, $new_value, $option_name) {
    if (empty($new_value)) {
        $new_value = [];
        update_option($option_name, $new_value);
    }
}, 10, 3);

function lbec_on_options_save()
{
    if (isset($_GET['settings-updated'])) {
      $settingsUpdated = sanitize_key($_GET['settings-updated']);

      if ($settingsUpdated === 'true') {
        //-- Validate the connection with Ledenbeheer
        $resp = lbec_make_call_to_ledenbeheer(Q_VALIDATE);
        if (is_wp_error($resp)) return;

        if (isset($resp['result']) && (bool) $resp['result'] === true) {
          update_option('lb_status', true);
        }
      }
    }
}

function lbec_isOptionChecked($optionKey, $value) {
    $option = get_option(PREFIX . $optionKey);
    if (is_array($option) && in_array($value, $option)) {
        return 'checked';
    }
    return '';
}
