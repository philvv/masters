<?php
error_reporting(E_ERROR);
require "vendor/autoload.php";
use PHPHtmlParser\Dom;
use \Colors\RandomColor;
use Stidges\CountryFlags\CountryFlag;
use writecrow\CountryCodeConverter\CountryCodeConverter;

//$scores_file = 'scores.html';
//
//if(!file_exists($scores_file)){
//    $html = file_get_contents('http://www.espn.com/golf/leaderboard');
//    file_put_contents($scores_file, $html);
//} else {
//    $html = file_get_contents($scores_file);
//}
//
//$dom = new Dom;
//$dom->loadFromFile($scores_file);

$html = file_get_contents('https://www.espn.com/golf/leaderboard');

$dom = new Dom;
$dom->loadStr($html);


$status_node = $dom->find('.status.cf.mt4.mb4', 0);
$round_status = '';
if ($status_node) {
    $status_span = $status_node->find('span', 0);
    if ($status_span) {
        $round_status = trim($status_span->text);
    }
}

$contents = $dom->find('tr');

unset($contents[0]);

$results = array();

$colors = RandomColor::many(30, array(
    'hue' => 'random'
));

foreach ($contents as $content) {
    $tds = explode('<td class="PlayerRow__Overview PlayerRow__Overview--expandable Table__TR Table__even">', $content);

    $bits = explode('leaderboard_player_name', $tds[0]);

    if(!isset($bits[1])) continue;

    $bits = explode(">", $bits[1]);
    $name = explode("<", $bits[1])[0];
    //$name = trim(strtolower(explode('(a)', $name)[0]));
    //$name = str_replace(' ', '-', $name);

    $is_final = strcasecmp($round_status, 'Final') === 0;

    $results[] = [
        'player'   => $name,
        'overall'  => str_replace('</td', '', $bits[4]),
        'tee_time' => $is_final ? '' : trim(strip_tags(str_replace('</td', '', $bits[8]))),
        'round_1'  => str_replace('</td', '', $is_final ? $bits[6]  : $bits[10]),
        'round_2'  => str_replace('</td', '', $is_final ? $bits[8]  : $bits[12]),
        'round_3'  => str_replace('</td', '', $is_final ? $bits[10] : $bits[14]),
        'round_4'  => str_replace('</td', '', $is_final ? $bits[12] : $bits[16]),
    ];
}

foreach($results as $key => $result){
    if($result['overall'] == 'WD') {
        $results[$key]['score'] = 0;
    } else if($result['overall'] == 'CUT') {
        $results[$key]['score'] = ((int) $result['round_1'] + (int) $result['round_2']) - 144;
    } else if($result['overall'] == 'E') {
        $results[$key]['score'] = 0;
    } else {
        $results[$key]['score'] = (int) trim(str_replace('+', '', $result['overall']));
    }

    unset($results[$key]['overall']);
    unset($results[$key]['round_1']);
    unset($results[$key]['round_2']);
    unset($results[$key]['round_3']);
    unset($results[$key]['round_4']);
}

$lines = explode(PHP_EOL, file_get_contents('players'));

$players = [];
$countries = [];

foreach ($lines as $line){
    $chunks = explode('|', $line);
    if(count($chunks) != 3) continue;

    foreach ($results as $result){
        if($result['player'] == $chunks[1]){
            $players[] = [
                'id' => $chunks[0],
                'name' => $chunks[1],
                'country' => $chunks[2],
                'score' => (int) $result['score'],
                'tee_time' => $result['tee_time']
            ];
            $countries[$chunks[1]] = $chunks[2];
        }
    }
}

$lines = explode(PHP_EOL, file_get_contents('entries'));

$entries = [];

foreach ($lines as $line){
    $chunks = explode(' ', $line);

    $choices = [];

    $ids = explode(',', $chunks[1]);

    foreach($ids as $id){
        foreach($players as $player) {
            if ($id == $player['id']) {
                $choices[] = $player['name'];
            }
        }
    }

    $entries[] = [
        'name' => $chunks[0],
        'country' => $player['country'],
        'choices' => $choices,
    ];
}

$standings = [];

foreach($entries as $entry){
    $entrant = $entry['name'];

    foreach($entry['choices'] as $chosen_player){
        $found = false;

        foreach ($results as $result){
            if(stripos($result['player'], $chosen_player)  !== false){
                $standings[$entrant]['players'][$chosen_player] = (int) $result['score'];

                $found = true;
                // echo $entrant . ' chose ' . $result['player'] . ' ' . $result['score'] . PHP_EOL;

                if(!isset($standings[$entrant]['overall'])){
                    $standings[$entrant]['overall'] = (int) $result['score'];
                } else {
                    $standings[$entrant]['overall'] = (int) $standings[$entrant]['overall'] + (int) $result['score'];
                }
                break;
            }
        }

        if(!$found){
            echo $entrant . ' NOT MATCHED ' . $chosen_player . PHP_EOL;
        }
    }
}

uksort($standings, function ($entrant_a, $entrant_b) use ($standings) {
    $overall_a = (int) ($standings[$entrant_a]['overall'] ?? 0);
    $overall_b = (int) ($standings[$entrant_b]['overall'] ?? 0);
    if ($overall_a !== $overall_b) {
        return $overall_a <=> $overall_b;
    }
    return strcasecmp($entrant_a, $entrant_b);
});

$year = date("Y");
$date = date("Md");

$masters_players_json = @file_get_contents("https://www.masters.com/en_US/cms/feeds/players/{$year}/players.json");
$masters_player_urls = [];
if ($masters_players_json !== false) {
    $masters_players_data = json_decode($masters_players_json, true);
    foreach ($masters_players_data['players'] ?? [] as $mp) {
        if (!empty($mp['name']) && !empty($mp['id'])) {
            $masters_player_urls[$mp['name']] = "https://www.masters.com/en_US/players/player_{$mp['id']}.html";
        }
    }
}

// Marquee tees: scraped text is usually Eastern; UK site shows local. Match UK with Europe/London, or use America/New_York for US.
$tee_marquee_display_timezone = 'Europe/London';

function getCountryIcon($country){
    if($country == 'Northern Ireland') return 'gb-nir';

    if($country == 'United States') $country = 'United States of America';

    $code = CountryCodeConverter::convert($country);

    return strtolower($code);
}

function player_surname($full_name){
    $full_name = trim((string) $full_name);
    if ($full_name === '') {
        return '';
    }
    $parts = preg_split('/\s+/u', $full_name);
    return $parts[count($parts) - 1];
}

function tee_time_clean($tee_time_raw){
    return trim(html_entity_decode(strip_tags((string) $tee_time_raw), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

/**
 * ESPN THRU column: hole 1–18 = current hole; "F" or dash = round done (not a clock time).
 * Sort key 19 groups finished so they list after hole 18.
 */
function thru_field_sort_key($tee_time_raw){
    $clean = tee_time_clean($tee_time_raw);
    if ($clean === '') {
        return null;
    }
    if (preg_match('/^(?:[1-9]|1[0-8])$/', $clean)) {
        return (int) $clean;
    }
    if (strcasecmp($clean, 'F') === 0) {
        return 19;
    }
    if ($clean === '-' || $clean === '—' || $clean === '–') {
        return 19;
    }
    return null;
}

/** Display label uses the ESPN THRU value directly. */
function hole_marquee_display_label($tee_time_raw){
    $clean = tee_time_clean($tee_time_raw);
    if (preg_match('/^(?:[1-9]|1[0-8])$/', $clean)) {
        return $clean;
    }
    if (strcasecmp($clean, 'F') === 0) {
        return 'F';
    }
    if ($clean === '-' || $clean === '—' || $clean === '–') {
        return '—';
    }
    return $clean;
}

function parse_tee_time($tee_time_raw){
    $clean = tee_time_clean($tee_time_raw);
    if ($clean === '' || $clean === '-' || $clean === '—' || $clean === '–') {
        return null;
    }
    if (strcasecmp($clean, 'F') === 0) {
        return null;
    }
    if (preg_match('/^(?:[1-9]|1[0-8])$/', $clean)) {
        return null;
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}[T\s]/', $clean)) {
        $dt = date_create_immutable($clean);
        return $dt !== false ? $dt : null;
    }
    if (preg_match('/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i', $clean, $m)) {
        $tz_et = new DateTimeZone('America/New_York');
        $today_et = (new DateTimeImmutable('now', $tz_et))->format('Y-m-d');
        $line = sprintf('%s %d:%02d %s', $today_et, (int) $m[1], (int) $m[2], strtoupper($m[3]));
        $dt = DateTimeImmutable::createFromFormat('Y-m-d g:i A', $line, $tz_et);
        return $dt !== false ? $dt : null;
    }
    $parsed = strtotime($clean);
    return $parsed !== false ? new DateTimeImmutable('@' . $parsed) : null;
}

function tee_time_sort_timestamp($tee_time_raw){
    $dt = parse_tee_time($tee_time_raw);
    return $dt !== null ? $dt->getTimestamp() : PHP_INT_MAX;
}

function tee_time_format_for_marquee($tee_time_raw, $display_timezone){
    $dt = parse_tee_time($tee_time_raw);
    if ($dt === null) {
        return tee_time_clean($tee_time_raw);
    }
    return $dt->setTimezone(new DateTimeZone($display_timezone))->format('g:i A');
}

$tee_time_marquee_rows = [];
foreach ($players as $marquee_player) {
    $raw = $marquee_player['tee_time'] ?? '';
    if (parse_tee_time($raw) === null) {
        continue;
    }
    $tee = tee_time_clean($raw);
    $tee_time_marquee_rows[] = [
        'sort' => tee_time_sort_timestamp($tee),
        'name' => $marquee_player['name'],
        'tee_raw' => $tee,
    ];
}
usort($tee_time_marquee_rows, function ($a, $b){
    if ($a['sort'] !== $b['sort']) {
        return $a['sort'] <=> $b['sort'];
    }
    $by_surname = strcasecmp(player_surname($a['name']), player_surname($b['name']));
    if ($by_surname !== 0) {
        return $by_surname;
    }
    return strcasecmp($a['name'], $b['name']);
});
$tee_time_marquee_segments = [];
$row_count = count($tee_time_marquee_rows);
$i = 0;
while ($i < $row_count) {
    $block_sort = $tee_time_marquee_rows[$i]['sort'];
    $names_html = [];
    $tee_raw = $tee_time_marquee_rows[$i]['tee_raw'];
    while ($i < $row_count && $tee_time_marquee_rows[$i]['sort'] === $block_sort) {
        $names_html[] = htmlspecialchars(player_surname($tee_time_marquee_rows[$i]['name']), ENT_QUOTES, 'UTF-8');
        $i++;
    }
    $time_html = htmlspecialchars(tee_time_format_for_marquee($tee_raw, $tee_marquee_display_timezone), ENT_QUOTES, 'UTF-8');
    $tee_time_marquee_segments[] = implode(', ', $names_html) . ' — ' . $time_html;
}
$tee_time_marquee_text = count($tee_time_marquee_segments) > 0
    ? implode(' &nbsp;&nbsp; ', $tee_time_marquee_segments)
    : 'No tee times loaded';
$tee_time_marquee_display = ' Up next... ' . $tee_time_marquee_text;

$played_marquee_rows = [];
foreach ($players as $marquee_player) {
    $raw = $marquee_player['tee_time'] ?? '';
    $hole_sort = thru_field_sort_key($raw);
    if ($hole_sort === null) {
        continue;
    }
    $played_marquee_rows[] = [
        'sort' => $hole_sort,
        'name' => $marquee_player['name'],
        'tee_raw' => $raw,
    ];
}
usort($played_marquee_rows, function ($a, $b) {
    if ($a['sort'] !== $b['sort']) {
        return $b['sort'] <=> $a['sort'];
    }
    $by_surname = strcasecmp(player_surname($a['name']), player_surname($b['name']));
    if ($by_surname !== 0) {
        return $by_surname;
    }
    return strcasecmp($a['name'], $b['name']);
});
$played_marquee_segments = [];
$row_count = count($played_marquee_rows);
$i = 0;
while ($i < $row_count) {
    $block_sort = $played_marquee_rows[$i]['sort'];
    $tee_raw = $played_marquee_rows[$i]['tee_raw'];
    $names_html = [];
    while ($i < $row_count && $played_marquee_rows[$i]['sort'] === $block_sort) {
        $full_name = $played_marquee_rows[$i]['name'];
        $surname_html = htmlspecialchars(player_surname($full_name), ENT_QUOTES, 'UTF-8');
        if (isset($masters_player_urls[$full_name])) {
            $player_url = htmlspecialchars($masters_player_urls[$full_name], ENT_QUOTES, 'UTF-8');
            $names_html[] = "<a href=\"{$player_url}\" target=\"_blank\">{$surname_html}</a>";
        } else {
            $names_html[] = $surname_html;
        }
        $i++;
    }
    $disp = hole_marquee_display_label($tee_raw);
    $disp_html = htmlspecialchars($disp, ENT_QUOTES, 'UTF-8');
    if (ctype_digit($disp)) {
        $played_marquee_segments[] = implode(', ', $names_html) . ' — Hole ' . $disp_html;
    } else {
        $played_marquee_segments[] = implode(', ', $names_html) . ' — ' . $disp_html;
    }
}
$played_marquee_text = count($played_marquee_segments) > 0
    ? implode(' &nbsp;&nbsp; ', $played_marquee_segments)
    : 'No players playing yet';
$played_marquee_display = ' Currently playing... ' . $played_marquee_text;

$tee_marquee_display_timezone_js = htmlspecialchars($tee_marquee_display_timezone, ENT_QUOTES, 'UTF-8');
$dt_clock = new DateTimeImmutable('now', new DateTimeZone($tee_marquee_display_timezone));
$time_display = htmlspecialchars($dt_clock->format('g:i:s A'), ENT_QUOTES, 'UTF-8');

echo <<< EOT

<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.0.0/css/flag-icons.min.css"/>
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
<script>window.teeTimeDisplayTimezone = '$tee_marquee_display_timezone_js';</script>
<script src="script.js"></script>

<h2>P130<span class="ceefax">Ceefax</span>130 $date <span id="time" style="color: yellow;">$time_display</span></h2>

<h1>
    <span class="bbc">B</span><span class="bbc">B</span><span class="bbc">C</span><span class="ceefax" style="color: limegreen;">GOLF</span>
</h1>
<hr style="border-color: blue;">
<p style="color: yellow; text-align: center;">Masters tournament $year</p>
<marquee class="tee-times-marquee played-marquee" width="100%" direction="left" scrollamount="4">$played_marquee_display</marquee>
<marquee class="tee-times-marquee" style="display: block; margin: 1em 0;" width="100%" direction="left" scrollamount="4">$tee_time_marquee_display</marquee>
<div class="content">
    <div class="table-responsive">
        <table class="table tftable">
            <thead class="table-header">
                <tr>
                    <th>Name</th>   
                    <th>Overall</th>
                    <th>Pick 1</th>
                    <th>Score</th>
                    <th>Pick 2</th>
                    <th>Score</th>
                    <th>Pick 3</th>
                    <th>Score</th>
                    <th>Pick 4</th>
                    <th>Score</th>
                </tr>
            </thead>
            <tbody class="table-body">
EOT;
$count = 0;
foreach($standings as $entrant => $standing){
    $overall = $standing['overall'];
    $color = $colors[$count];
    echo "<tr>" . PHP_EOL;
    echo "<td class='entry-name' style='background: $color'>$entrant</td>" . PHP_EOL;
    echo "<td>$overall</td>" . PHP_EOL;

    foreach($standing['players'] as $player => $score){
        $player_name_html = htmlspecialchars($player, ENT_QUOTES, 'UTF-8');
        if (isset($masters_player_urls[$player])) {
            $player_url = htmlspecialchars($masters_player_urls[$player], ENT_QUOTES, 'UTF-8');
            $player_name_html = "<a href=\"{$player_url}\" target=\"_blank\">{$player_name_html}</a>";
        }
        echo "<td>{$player_name_html} " . '<span class="fi fi-' . getCountryIcon($countries[$player]) . '"></span>' . "</td>" . PHP_EOL;
        echo "<td>$score</td>" . PHP_EOL;
    }
    echo "</tr>" . PHP_EOL;
    $count ++;
}

echo "</tbody></table></div><h2 class='ceefax' style='margin-top: 20px; text-align: center'>Ceefax: The world at your fingertips</h2>";
