<?php
/*
Plugin Name: Foys Blokkenschema
Description: Toont een responsieve tabel met de reserveringen van vijf squashbanen op basis van de Foys JSON API.
Version: 1.0
Author: Elmer Smaling
*/

if (!defined('ABSPATH')) exit;

add_shortcode('foys_baantabel', 'foys_render_baantabel');

function foys_render_baantabel() {
    $api_url = rest_url('foys-json/v1/reservations');

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
    <style>
    .foys-tabel {
        width: 100%;
        border-collapse: collapse;
        overflow-x: auto;
        display: block;
    }
    .foys-tabel th, .foys-tabel td {
        border: 1px solid #ccc;
        padding: 5px;
        text-align: center;
        font-size: 0.9em;
    }
    .foys-tabel th {
        background: #f3f3f3;
        position: sticky;
        top: 0;
        z-index: 2;
    }
    .foys-tabel td.bezet {
        background-color: #ffd5d5;
    }
    </style>
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
