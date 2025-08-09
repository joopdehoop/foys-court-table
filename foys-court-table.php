<?php
/*
Plugin Name: Foys Blokkenschema
Description: Toont een responsieve tabel met de reserveringen van vijf squashbanen op basis van de Foys JSON API.
Version: 1.0
Author: Elmer Smaling
*/

if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', 'foys_enqueue_scripts');
add_shortcode('foys_baantabel', 'foys_render_baantabel');
add_shortcode('foys_baantabel_anonymous', 'foys_render_baantabel_anonymous');
add_action('admin_menu', 'foys_admin_menu');
add_action('admin_init', 'foys_admin_init');

function foys_enqueue_scripts() {
    wp_enqueue_style('foys-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.css', [], '1.0');
}

function foys_render_baantabel() {
    $allowed_paths = get_option('foys_allowed_paths', '');
    $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    if (!empty($allowed_paths)) {
        $paths = array_map('trim', explode("\n", $allowed_paths));
        $is_allowed = false;
        
        foreach ($paths as $path) {
            if (!empty($path) && strpos($current_path, $path) !== false) {
                $is_allowed = true;
                break;
            }
        }
        
        if (!$is_allowed) {
            return $current_path;
        }
    }
    
    $api_key = get_option('foys_api_key', '');
    $api_url = rest_url('foys-json/v1/reservations');
    
    if (!empty($api_key)) {
        $api_url = add_query_arg('api_key', $api_key, $api_url);
    }

    $response = wp_remote_get($api_url, ['timeout' => 15]);

    if (is_wp_error($response)) return '<p>Kan gegevens niet ophalen.</p>';

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($data['inventoryItems'])) return '<p>Ongeldig gegevensformaat.</p>';

    // Merge courts with same 6-character prefix
    $merged_courts = [];
    foreach ($data['inventoryItems'] as $court) {
        $prefix = substr($court['name'], 0, 6);
        if (!isset($merged_courts[$prefix])) {
            $merged_courts[$prefix] = [
                'name' => $prefix,
                'reservations' => []
            ];
        }
        $merged_courts[$prefix]['reservations'] = array_merge(
            $merged_courts[$prefix]['reservations'], 
            $court['reservations'] ?? []
        );
    }
    $banen = array_slice(array_values($merged_courts), 0, 5);
    $tijdvakken = [];

    for ($uur = 9; $uur < 23; $uur++) {
        $tijdvakken[] = sprintf('%02d:00', $uur);
        $tijdvakken[] = sprintf('%02d:30', $uur);
    }

    ob_start();
    ?>
    <table class="foys-tabel">
        <thead>
            <tr>
                <th>Tijd</th>
                <?php foreach ($banen as $baan): ?>
                    <th><?php echo esc_html($baan['name']); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tijdvakken as $tijd): ?>
                <tr>
                    <td><?php echo esc_html($tijd); ?></td>
                    <?php foreach ($banen as $baan): ?>
                        <?php 
                        $spelers = foys_get_reservering_info($baan['reservations'], $tijd); 
						$lastNames = [];
						if (is_array($spelers)) { 
							foreach ($spelers as $speler) {
								$lastNames[] = $speler['lastName'];
							}
						}
						$achternamen = implode("-", $lastNames);
                        ?>
                        <td class="<?php echo (!empty($achternamen) || $spelers == 'Bezet') ? 'bezet' : ''; ?>" data-lastname="<?php echo $spelers == 'Bezet' ? 'Bezet' : esc_attr($achternamen); ?>">
                            <?php 
                            if (empty($spelers)) {
                                // Empty cell
                            } elseif ($spelers == "Bezet") {
                                echo "Bezet";
                            } else {
                                foreach ($spelers as $speler) {
                                    echo esc_html($speler['fullName']); ?><br><?php
                                }
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    return ob_get_clean();
}

function foys_render_baantabel_anonymous() {
    $api_key = get_option('foys_api_key', '');
    $api_url = rest_url('foys-json/v1/reservations');
    
    if (!empty($api_key)) {
        $api_url = add_query_arg('api_key', $api_key, $api_url);
    }

    $response = wp_remote_get($api_url, ['timeout' => 15]);

    if (is_wp_error($response)) return '<p>Kan gegevens niet ophalen.</p>';

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($data['inventoryItems'])) return '<p>Ongeldig gegevensformaat.</p>';

    // Merge courts with same 6-character prefix
    $merged_courts = [];
    foreach ($data['inventoryItems'] as $court) {
        $prefix = substr($court['name'], 0, 6);
        if (!isset($merged_courts[$prefix])) {
            $merged_courts[$prefix] = [
                'name' => $prefix,
                'reservations' => []
            ];
        }
        $merged_courts[$prefix]['reservations'] = array_merge(
            $merged_courts[$prefix]['reservations'], 
            $court['reservations'] ?? []
        );
    }
    $banen = array_slice(array_values($merged_courts), 0, 5);
    $tijdvakken = [];

    for ($uur = 9; $uur < 23; $uur++) {
        $tijdvakken[] = sprintf('%02d:00', $uur);
        $tijdvakken[] = sprintf('%02d:30', $uur);
    }

    ob_start();
    ?>
    <table class="foys-tabel">
        <thead>
            <tr>
                <th>Tijd</th>
                <?php foreach ($banen as $baan): ?>
                    <th><?php echo esc_html($baan['name']); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tijdvakken as $tijd): ?>
                <tr>
                    <td><?php echo esc_html($tijd); ?></td>
                    <?php foreach ($banen as $baan): ?>
                        <td class="<?php echo foys_is_bezet($baan['reservations'], $tijd) ? 'bezet' : ''; ?>" data-lastname="Bezet">
                            <?php echo foys_is_bezet($baan['reservations'], $tijd) ? 'Bezet' : ''; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    return ob_get_clean();
}

function foys_get_reservering_info($reserveringen, $tijdvak) {
    foreach ($reserveringen as $res) {
        $start = strtotime($res['startDateTime']);
        $eind  = strtotime($res['endDateTime']);
        $tijd  = strtotime(date('Y-m-d') . ' ' . $tijdvak);
		$tussenvoegsels = ['van', 'van der', 'van de', 'de', 'den', 'von'];

        if ($tijd >= $start && $tijd < $eind) {
            $spelers = [];
            if (isset($res['players']) && is_array($res['players'])) {
                foreach ($res['players'] as $player) {
                    if (isset($player['person']['fullName'])) {
						
                        // splits de naam op in een voor- en achternaam
						$woorden = explode(' ', $player['person']['fullName']);
						$aantal = count($woorden);

						// Begin van achteren en zoek naar tussenvoegsel
						$lastName = $woorden[$aantal - 1];
						for ($i = $aantal - 2; $i >= 0; $i--) {
							$deel = implode(' ', array_slice($woorden, $i, $aantal - $i));
							if (in_array(strtolower($deel), $tussenvoegsels)) {
								$lastName = $deel . ' ' . $lastName;
							} else {
								break;
							}
						}

						$spelers[] = [
							'fullName' => $player['person']['fullName'],
							'lastName' => $lastName
						];
						
						
                    }
                }
            }
            return !empty($spelers) ? $spelers : 'Bezet';
        }
    }
    return false;
}

function foys_is_bezet($reserveringen, $tijdvak) {
    foreach ($reserveringen as $res) {
        $start = strtotime($res['startDateTime']);
        $eind  = strtotime($res['endDateTime']);
        $tijd  = strtotime(date('Y-m-d') . ' ' . $tijdvak);

        if ($tijd >= $start && $tijd < $eind) return true;
    }
    return false;
}

function foys_admin_menu() {
    add_options_page(
        'Foys Blokkenschema Instellingen',
        'Foys Blokkenschema',
        'manage_options',
        'foys-settings',
        'foys_settings_page'
    );
}

function foys_admin_init() {
    register_setting('foys_settings', 'foys_api_key');
    register_setting('foys_settings', 'foys_allowed_paths');
    
    add_settings_section(
        'foys_main_section',
        'API instellingen',
        null,
        'foys_settings'
    );
    
    add_settings_field(
        'foys_api_key',
        'API Key',
        'foys_api_key_field',
        'foys_settings',
        'foys_main_section'
    );
    
    add_settings_field(
        'foys_allowed_paths',
        'Whitelist voor Baantabel met namen',
        'foys_allowed_paths_field',
        'foys_settings',
        'foys_main_section'
    );
}

function foys_api_key_field() {
    $api_key = get_option('foys_api_key', '');
    echo '<input type="text" name="foys_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
    echo '<p class="description">Voer uw API key in voor toegang tot de Foys reserveringsdata.</p>';
}

function foys_allowed_paths_field() {
    $allowed_paths = get_option('foys_allowed_paths', '');
    echo '<textarea name="foys_allowed_paths" rows="5" class="large-text">' . esc_textarea($allowed_paths) . '</textarea>';
    echo '<p class="description">Voer de paden in waar de niet-anonieme baantabel getoond mag worden. Eén pad per regel. Bijvoorbeeld:<br>/squash/<br>/reserveringen/<br>/baantabel/</p>';
}

function foys_settings_page() {
    ?>
    <div class="wrap">
        <h1>Foys Blokkenschema instellingen</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('foys_settings');
            do_settings_sections('foys_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
