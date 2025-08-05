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
add_action('admin_menu', 'foys_admin_menu');
add_action('admin_init', 'foys_admin_init');

function foys_enqueue_scripts() {
    wp_enqueue_style('foys-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.css', [], '1.0');
}

function foys_render_baantabel() {
    $api_key = get_option('foys_api_key', '');
    $api_url = rest_url('foys-json/v1/reservations');
    
    if (!empty($api_key)) {
        $api_url = add_query_arg('api_key', $api_key, $api_url);
    }

    $response = wp_remote_get($api_url, ['timeout' => 15]);

    if (is_wp_error($response)) return '<p>Kan gegevens niet ophalen.</p>';

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($data['inventoryItems'])) return '<p>Ongeldig gegevensformaat.</p>';

    $banen = array_slice($data['inventoryItems'], 0, 5); // eerste 5 banen
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
                        <td class="<?php echo foys_is_bezet($baan['reservations'], $tijd) ? 'bezet' : ''; ?>">
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
    
    add_settings_section(
        'foys_main_section',
        'API Instellingen',
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
}

function foys_api_key_field() {
    $api_key = get_option('foys_api_key', '');
    echo '<input type="text" name="foys_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
    echo '<p class="description">Voer uw API key in voor toegang tot de Foys reserveringsdata.</p>';
}

function foys_settings_page() {
    ?>
    <div class="wrap">
        <h1>Foys Blokkenschema Instellingen</h1>
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
