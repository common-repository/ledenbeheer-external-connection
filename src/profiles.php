<?php
require_once(ABSPATH . 'wp-admin/includes/user.php');

$profilesMetaBox = [
    'id' => 'profiles-meta-box',
    'title' => 'Contact info',
    'page' => 'profiles',
    'context' => 'normal',
    'priority' => 'high'
];

// Add meta box
function lbec_profiles_add_box() {
    global $profilesMetaBox;
    add_meta_box($profilesMetaBox['id'], $profilesMetaBox['title'], 'lbec_profiles_show_box', $profilesMetaBox['page'], $profilesMetaBox['context'], $profilesMetaBox['priority']);
}
add_action('admin_menu', 'lbec_profiles_add_box');

function lbec_profiles_show_box() {
    global $post;
    $meta = get_post_meta($post->ID);
    foreach ($meta as &$m) {
        $m = array_shift($m);
    }
    $locations = get_post_meta($post->ID, 'locations', true);
    ?>
    <table class="form-table">
        <tr>
            <th>Achternaam</th>
            <td><?php echo esc_html($meta['lb_lname']) ?></td>
        </tr>
        <tr>
            <th>Voornaam</th>
            <td><?php echo esc_html($meta['lb_fname']) ?></td>
        </tr>
        <tr>
            <th>Email</th>
            <td><?php echo $meta['lb_email'] ? esc_html($meta['lb_email']) : esc_html($meta['lb_parent_email']) ?></td>
        </tr>
        <tr>
            <th>Telefoon</th>
            <td><?php echo $meta['lb_phone'] ? esc_html($meta['lb_phone']) : esc_html($meta['lb_parent_phone']) ?></td>
        </tr>
        <tr>
            <th>Leeftijd</th>
            <td><?php echo esc_html($meta['lb_age']) ?></td>
        </tr>
        <?php if (!empty($locations)): ?>
        <tr>
            <th>Locaties</th>
            <td>
                <table class="form-table">
                    <?php foreach ($locations as $location): ?>
                    <tr>
                        <td><?php print $location['name']; ?></td>
                        <td><?php echo lbec_render_location($location); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </td>
        </tr>
        <?php endif; ?>
        <tr>
            <th><label for="lb-show">Profiel tonen</label></th>
            <td><input type="checkbox" id="lb-show" name="lb_show" <?php if ((int) $meta['lb_show'] === 1 || $meta['lb_show' === '']) echo 'checked' ?>></td>
        </tr>
    </table>
    <?php
}

function lbec_render_location($location): string
{
    $html = '';
    if ($location['street'] || $location['streetNumber']) {
        $html .= "${location['street']} ${location['streetNumber']}<br/>";
    }
    $args = [];
    if ($location['postal']) {
        $args[] = $location['postal'];
    }
    if ($location['city']) {
        $args[] = $location['city'];
    }
    if ($location['country']) {
        $args[] = $location['country'];
    }
    $html .= join(', ', $args);
    return $html;
}

function lbec_profiles_save_data($post_id) {
    $requestUri = esc_url_raw($_SERVER['REQUEST_URI']);
    if (strpos($requestUri, 'ledenbeheer/v1/sync') !== false) {
        return;
    }
    update_post_meta($post_id, 'lb_show', isset($_POST['lb_show']) && $_POST['lb_show'] === 'on' ? 1 : 0);
}
add_action('save_post', 'lbec_profiles_save_data');

function lbec_parse_locations_from_answers($answers): array
{
    $locationAnswers = array_filter($answers, function($answer) {
        return in_array($answer['default_key'], LOCATION_KEYS) || $answer['type'] === 'address';
    });

    $locations = [];
    $singleLocation = [];
    foreach ($locationAnswers as $answer) {
        if ($answer['type'] === 'address') {
            $location = $answer['answer'];
            $location['name'] = $answer['question'];
            $locations[] = $location;
        }

        if (!is_array($answer['answer']) && strlen($answer['answer'])) {
            switch ($answer['default_key']) {
                case 'address_location':
                    $singleLocation['city'] = $answer['answer'];
                    break;
                case 'address_street_no':
                    //-- This is street and number, find the last space and split
                    $startOfLastSpace = strrpos($answer['answer'], ' ');
                    $singleLocation['street'] = substr($answer['answer'], 0, $startOfLastSpace);
                    $singleLocation['streetNumber'] = substr($answer['answer'], $startOfLastSpace + 1);
                    break;
                case 'address_postal_code':
                    $singleLocation['postal'] = $answer['answer'];
                    break;
                case 'address_country':
                    //-- Country conversion
                    $conversion = [
                        'belgië' => 'BE',
                        'belgie' => 'BE',
                        'belgium' => 'BE',
                        'belgique' => 'BE',
                        'zuid-afrika' => 'ZA',
                        'nederland' => 'NL',
                        'netherlands' => 'NL',
                        'lettland' => 'LT',
                        'germany' => 'DE',
                        'frankrijk' => 'FR',
                        'portugal' => 'PT'
                    ];
                    if (isset($conversion[strtolower($answer['answer'])])) {
                        $answer['answer'] = $conversion[strtolower($answer['answer'])];
                    }
                    $singleLocation['country'] = $answer['answer'];
                    break;
            }
        }
    }
    if (!empty($singleLocation)) {
        if (!isset($singleLocation['country'])) {
            $singleLocation['country'] = 'BE';
        }
        $singleLocation['name'] = 'Adres';
        $locations[] = $singleLocation;
    }
    return $locations;
}

function lbec_sync_profiles()
{
    if (!(bool) get_option('lb_sync_profiles')) {
        //-- Delete all of them
        foreach (get_users([
            'meta_key' => 'lbec',
            'meta_value' => 1,
            'meta_compare' => '='
        ]) as $user) {
            wp_delete_user($user->ID);
        }
        return;
    }

    $profilesResponse = lbec_make_call_to_ledenbeheer(Q_GET_PROFILES_FOR_CLUB);
    if (is_wp_error($profilesResponse)) return;

    $ids = [];
    foreach ($profilesResponse['result'] as $profile) {
        $user = get_user_by('login', 'lbec_' . $profile['uid']);
        if (!$user) {
            $userID = wp_create_user('lbec_' . $profile['uid'], base64_encode(random_bytes(10)), $profile['email']);
            $user = new WP_User($userID);
            $user->user_status = 1;
            update_user_meta($userID, 'visible', true);
        }

        //-- Update basic info
        $user->first_name = sanitize_text_field($profile['fname']);
        $user->last_name = sanitize_text_field($profile['lname']);
        $user->user_email = !empty($profile['email']) ? sanitize_text_field($profile['email']) : sanitize_text_field($profile['parent_email']);
        $user->display_name = sanitize_text_field($profile['fname']) . ' ' . sanitize_text_field($profile['lname']);
        wp_update_user($user);

        //-- Update meta information
        update_user_meta($user->ID, 'lbec', 1);
        update_user_meta($user->ID, 'uid', sanitize_text_field($profile['uid']));
        update_user_meta($user->ID, 'phone', sanitize_text_field($profile['phone']));
        update_user_meta($user->ID, 'parent_phone', isset($profile['parent_phone']) ? sanitize_text_field($profile['parent_phone']) : '');
        update_user_meta($user->ID, 'parent_email', isset($profile['parent_email']) ? sanitize_text_field($profile['parent_email']) : '');
        update_user_meta($user->ID, 'age', sanitize_text_field($profile['age']));
        update_user_meta($user->ID, 'guardian', isset($profile['guardian']) ? sanitize_text_field($profile['guardian']) : '');
        update_user_meta($user->ID, 'answers', $profile['answers']);
        update_user_meta($user->ID, 'is_main_account', (int) $profile['parent_uid'] === 0);

        //-- Set locations
        lbec_set_profile_locations($user, $profile['answers']);

        //-- Set profile image | Todo: Use WP image for user
        lbec_set_profile_picture($user, $profile['answers']);

        //-- Keep track of which uids are processed
        $ids[] = $profile['uid'];
    }

    foreach (get_users([
        'meta_key' => 'lbec',
        'meta_value' => 1,
        'meta_compare' => '='
    ]) as $user) {
        $lbecUid = explode('_', $user->user_login);
        $uid = array_pop($lbecUid);

        if (!in_array($uid, $ids)) {
            wp_delete_user($user->ID);
        }
    }

    //-- Save questions
    if (!isset($profilesResponse['questions'])) {
        return;
    }

    $questions = [];
    foreach ($profilesResponse['questions'] as $q) {
        if (in_array($q['default_key'], LOCATION_KEYS)) continue;
        $questions[$q['qid']] = [
            'label' => sanitize_text_field($q['question']),
            'default_key' => $q['default_key']
        ];
    }

    set_transient('lb_questions', $questions);

    //-- Save answers
    $answers = [];
    foreach($profilesResponse['result'] as $profile) {
        $user = get_user_by('login', 'lbec_' . $profile['uid']);

        if (!$user) {
            continue;
        }

        if (!$user->user_status) {
            continue;
        }

        foreach ($profile['answers'] as $qid => $answer) {
            if (is_array($answer['answer'])) {
                $answers[$qid][] = $answer;
            } else {
                $answer = trim(ucfirst($answer['answer']));
                if (strlen($answer)) {
                    $answers[$qid][] = sanitize_text_field($answer);
                }
            }
        }
    }

    foreach ($profilesResponse['questions'] as $question) {
        $qid = $question['qid'];
        if (!isset($answers[$qid])) {
            delete_transient('answers_' . $qid);
        }
    }

    //-- Filter out duplicates
    foreach ($answers as $qid => &$possibleAnswers) {
        $possibleAnswers = array_unique($possibleAnswers);
        set_transient('answers_' . $qid, $possibleAnswers);
    }
}

function lbec_set_profile_picture(WP_User $user, $answers)
{
    $profilePictureAnswers = array_filter($answers, function($answer) {
        return $answer['default_key'] === 'profile_pictures';
    });
    if (empty($profilePictureAnswers)) return;

    $profilePictureAnswer = array_shift($profilePictureAnswers);

    $profilePictureURL = $profilePictureAnswer['answer'];
    if (strlen($profilePictureURL) === 0) return;

    //-- Create full url
    $fullURL = "https://api.ledenbeheer.be/{$profilePictureURL}";

    //-- Check if file exists
    $fileContents = @file_get_contents($fullURL);
    if ($fileContents === false) return;

    update_user_meta($user->ID, 'profile_image', $fullURL);
}

function lbec_set_profile_locations(WP_User $user, $answers)
{
    $locations = lbec_parse_locations_from_answers($answers);

    //-- Get current locations and check if any difference
    $storedLocations = (array) get_user_meta($user->ID, 'locations', true);

    $equal = count($storedLocations) === count($locations);
    if ($equal) {
        for ($i = 0; $i < count($storedLocations); $i++) {
            $storedLocation = $storedLocations[$i];
            $workingLocation = $locations[$i];

            $keysToBeEqual = ['street', 'streetNumber', 'postal', 'city', 'country', 'name'];
            foreach ($keysToBeEqual as $key) {
                if (!isset($storedLocation[$key])) {
                    $equal = false;
                    break 2;
                }
                if ($storedLocation[$key] !== $workingLocation[$key]) {
                    $equal = false;
                    break 2;
                }
            }

            if (
                    !isset($storedLocation['coords']) ||
                    !isset($storedLocation['coords']['lng']) ||
                    !isset($storedLocation['coords']['lat']) ||
                    empty($storedLocation['coords']['lng']) ||
                    empty($storedLocation['coords']['lat'])
            ) {
                $equal = false;
                break;
            }
        }
    }

    //-- if not equal, store it new
    //-- Foreach location, generate lat lng
    if (!$equal) {
        foreach ($locations as &$location) {
            $location['coords'] = lbec_get_coords_for_address("{$location['street']} {$location['streetNumber']}, {$location['postal']} {$location['city']}, {$location['country']}");
        }
        update_user_meta($user->ID, 'locations', $locations);
    }
}

function lbec_get_coords_for_address($address)
{
    $address = rawurlencode($address);
    $coord   = get_transient( 'geocode_' . $address );
    if( empty( $coord ) || is_null($coord['lat']) || is_null($coord['lng']) ) {
        $url  = 'http://nominatim.openstreetmap.org/?format=json&addressdetails=1&q=' . $address . '&format=json&limit=1';
        $json = wp_remote_get( $url );
        if ( 200 === (int) wp_remote_retrieve_response_code( $json ) ) {
            $body = wp_remote_retrieve_body( $json );
            $json = json_decode( $body, true );

            if (!isset($json[0])) {
                return false;
            } else {
                $coord['lat']  = $json[0]['lat'];
                $coord['lng'] = $json[0]['lon'];
                set_transient( 'geocode_' . $address, $coord, DAY_IN_SECONDS * 90 );
            }
        }
    }

    return $coord;
}


function lbec_render_profiles() {
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
    ob_start();
    lbec_print_profiles($profiles);
    return ob_get_clean();
}
add_shortcode('lbprofiles', 'lbec_render_profiles');

function lbec_calculate_distance($lat1, $lng1, $lat2, $lng2, $unit = 'K') {
    if (($lat1 == $lat2) && ($lng1 == $lng2)) {
        return 0;
    }
    else {
        $theta = $lng1 - $lng2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
            return ($miles * 1.609344);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }
}

function lbec_print_profiles($profiles) {
    $searchedLocation = null;
    $profiles = array_filter($profiles, function($profile) use ($searchedLocation) {
        $show = true;
        $answers = get_user_meta($profile->ID, 'answers', true);
        $locations = get_user_meta($profile->ID, 'locations', true);

        foreach ($_GET as $filter => $value) {
            if (empty($value)) {
                continue;
            }

            $value = sanitize_key($value);

            if ($filter === 'fullname') {
                $show = strpos(strtolower($profile->display_name), strtolower($value)) !== false;

                if (!$show) {
                    return false;
                }
            }

            $radius = sanitize_key($_GET['radius']);
            if ($filter === 'city' && !empty(sanitize_text_field($_GET[$filter])) && !empty($radius)) {
                if (empty($locations)) {
                    return false;
                }

                if (!$searchedLocation) {
                    //-- Geocode searched location
                    $searchedLocation = lbec_get_coords_for_address(sanitize_text_field($_GET[$filter]));
                }

                $isWithinDistance = false;
                foreach ($locations as $location) {
                    $distance = lbec_calculate_distance($searchedLocation['lat'], $searchedLocation['lng'], $location['coords']['lat'], $location['coords']['lng']);
                    if ($distance <= $radius) {
                        $isWithinDistance = true;
                        break;
                    }
                }
                if (!$isWithinDistance) {
                    return false;
                }
            }

            if (strpos($filter, 'filter_') === false) {
                continue;
            }

            $filter_qid = substr($filter, strlen('filter_'));


            $qids = array_keys($answers);
            if (in_array($filter_qid, $qids)) {
                foreach ($answers as $qid => $answer) {
                    if ((int) $qid !== (int) $filter_qid) {
                        continue;
                    }

                    if (strtolower($answer['answer']) !== $value) {
                        $show = false;
                    }
                }
            } else {
                $show = false;
            }
        }
        if (empty($_GET)) {
            return false;
        }
        return $show;
    });
    $questions = get_transient('lb_questions');
    $profileFilters = (array) get_option('lb_filter_profiles');
    $radiusses = range(5, 50, 5);
    ?>
    <form action="" class="lbec-form-filter">
        <fieldset class="lbec-form-filter-location">
            <p class="lbec-form-group">
                <label for="city">Woonplaats</label>
                <input type="text" id="city" class="form-control" name="city" value="<?php echo sanitize_text_field($_GET['city'] ?? ''); ?>">
            </p>
            <p class="lbec-form-group">
                <label for="radius">Radius</label>
                <select name="radius" id="radius" class="form-control">
                    <?php foreach($radiusses as $radius): ?>
                    <option value="<?php echo $radius; ?>" <?php echo $radius === (int) sanitize_key($_GET['radius'] ?? '') ? 'selected' : ''; ?>><?php echo $radius; ?>km</option>
                    <?php endforeach; ?>
                </select>
            </p>
        </fieldset>
        <fieldset class="lbec-form-filter-advanced">
            <p class="lbec-form-group">
                <label for="fullname">Naam</label>
                <input type="text" id="fullname" name="fullname" class="form-control" value="<?php echo sanitize_text_field($_GET['fullname'] ?? ''); ?>">
            </p>
        <?php
        if (is_array($profileFilters)) {
            foreach ($profileFilters as $qid) {
                $label = $questions[$qid]['label'];
                $answers = lbec_get_answers($qid);
                if (!empty($answers)):
                ?>
                <p class="lbec-form-group">
                    <label for="filter_<?php echo esc_html($qid) ?>"><?php echo esc_html($label); ?></label>
                    <select name="filter_<?php echo esc_html($qid) ?>" id="filter_<?php echo esc_html($qid) ?>" class="select form-control">
                        <?php lbec_render_options($answers, true, sanitize_text_field($_GET['filter_' . esc_html($qid)])) ?>
                    </select>
                </p>
            <?php
            endif;
            }
        }
        ?>
        </fieldset>
        <button type="submit" class="button btn btn-primary">Zoeken</button>
    </form>
    <div id="map" style="min-height: 350px;" class="lbec_map"></div>
    <ul class="lbec-profile-list">
        <?php
        $showProfilePicture = lbec_show_field('answers', 'profile_pictures');
        $showEmail = lbec_show_field('profile_fields', 'email');
        $showPhone = lbec_show_field('profile_fields', 'phone');
        $showAge = lbec_show_field('profile_fields', 'age');

        /** @var WP_User $profile */
        foreach ($profiles as $profile):
            $meta = get_user_meta($profile->ID);
            foreach ($meta as &$m) {
                $m = array_shift($m);
                $unserializedData = @unserialize($m);
                if ($unserializedData) {
                    $m = $unserializedData;
                }
            }
            ?>
            <li class="lbec-profile-list-item">
                <?php if (isset($meta['profile_image']) && $meta['profile_image'] && $showProfilePicture): ?>
                    <div class="lbec-profile-list-image">
                        <img src="<?php echo $meta['profile_image']; ?>" alt="<?php echo esc_html($profile->display_name) ?>">
                    </div>
                <?php endif; ?>
                <h4><a href="<?php echo get_home_url() . '/profielen/' . sanitize_key($profile->display_name) . '/' . $profile->ID; ?>"><?php echo esc_html($profile->display_name) ?></a></h4>
                <?php if ($showEmail): ?>
                    <p><strong>Email: </strong><?php echo !empty($profile->user_email) ? esc_html($profile->user_email) : esc_html($meta['parent_email']) ?></p>
                <?php endif; ?>
                <?php if ($showPhone): ?>
                    <p><strong>Telefoon: </strong><?php echo !empty($meta['phone']) ? esc_html($meta['phone']) : esc_html($meta['parent_phone']) ?></p>
                <?php endif; ?>
                <?php if ($showAge && !empty($meta['age'])): ?>
                    <p><strong>Leeftijd: </strong><?php echo esc_html($meta['age']) ?></p>
                <?php endif; ?>
                <?php if (!empty($meta['locations']) && is_array($meta['locations'])): ?>
                    <?php foreach ($meta['locations'] as $location): ?>
                    <address>
                        <strong class="text-muted"><?php echo $location['name']; ?>:</strong><br/>
                        <?php echo lbec_render_location($location); ?>
                    </address>
                    <?php endforeach; ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <script type="application/javascript">
        var profiles = [
            <?php foreach ($profiles as $profile):
            $profile->locations = get_user_meta($profile->ID, 'locations', true);

            if (!empty($profile->locations)):
            ?>
            {
                title: "<?php echo esc_html($profile->display_name) ?>",
                locations: [
                  <?php foreach ($profile->locations as $location): ?>
                    {
                      lat: <?php echo esc_html($location['coords']['lat']); ?>,
                      lng: <?php echo esc_html($location['coords']['lng']); ?>,
                      street: "<?php echo esc_html($location['street']); ?>",
                      street_number: "<?php echo esc_html($location['streetNumber']); ?>",
                      city: "<?php echo esc_html($location['city']); ?>",
                      postal: "<?php echo esc_html($location['postal']); ?>",
                      country: "<?php echo esc_html($location['country']); ?>"
                    },
                  <?php endforeach; ?>
                ]
            },
            <?php endif;
            endforeach; ?>
        ];

        function initMap() {
            let map = new google.maps.Map(document.getElementById('map'), {
                center: { lat: 51.08368455, lng: 2.973175048828 },
                zoom: 8,
                streetViewControl: false,
                fullscreenControl: false
            });
            let markers = [];

            let bounds = new google.maps.LatLngBounds();
            for (let profile of profiles) {
              for (let location of profile.locations) {
                let position = new google.maps.LatLng(location.lat, location.lng);
                let marker = new google.maps.Marker({
                  position: position,
                  map: map,
                  icon: "<?php echo plugins_url('../assets/img/marker-icon.png', __DIR__ . '/..'); ?>"
                });
                const infoWindow = new google.maps.InfoWindow({
                  content: `
                        <strong>${profile.title}</strong><br/>
                        ${location.street} ${location.street_number}<br/>
                        ${location.postal}, ${location.city}, ${location.country}
                    `
                });
                marker.addListener('click', () => {
                  infoWindow.open(map, marker);
                });
                markers.push(marker);
                bounds.extend(position);
              }
            }
            if (markers.length) {
                map.fitBounds(bounds);
            }

            let listener = google.maps.event.addListener(map, 'idle', function() {
                if (map.getZoom() > 8) map.setZoom(8);
                google.maps.event.removeListener(listener);
            });
        }
    </script>
    <script>
        const lbecGoogleApiKey = "<?php echo esc_html(get_option('lb_google_api_key')) ?>";
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo esc_html(get_option('lb_google_api_key')) ?>&callback=initMap" async defer></script>
    <?php
}

function lbec_get_answers($qid)
{
    return get_transient('answers_' . $qid);
}

function lbec_render_options($options, $allow_empty = true, $valueEntered = null) {
    if ($allow_empty) {
        echo "<option value=\"\"></option>";
    }
    foreach ($options as $option) {
        $value = strtolower($option);
        echo "<option value=\"" . esc_html($value) . "\"" . (($valueEntered && $value === $valueEntered) ? ' selected' : '') . ">" . esc_html($option) . "</option>";
    }
}
