<?php
//error_reporting(E_ALL & ~E_NOTICE);
require "vendor/autoload.php";
use PHPHtmlParser\Dom;
use \Colors\RandomColor;

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

$contents = $dom->find('tr');

unset($contents[0]);

$results = array();

$colors = RandomColor::many(15, array(
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

    $results[] = [
        'player'  => $name,
        'overall' => str_replace('</td', '', $bits[4]),
        'round_1' => str_replace('</td', '', $bits[10]),
        'round_2' => str_replace('</td', '', $bits[12]),
        'round_3' => str_replace('</td', '', $bits[14]),
        'round_4' => str_replace('</td', '', $bits[16]),
    ];
}

foreach($results as $key => $result){
    if($result['overall'] == 'WD') {
        $results[$key]['score'] = 0;
    } else if($result['overall'] == 'CUT') {
        $results[$key]['score'] = ($result['round_1'] + $result['round_2']) - 144;
    } else if($result['overall'] == 'E') {
        $results[$key]['score'] = 0;
    } else {
        $results[$key]['score'] = trim(str_replace('+', '', $result['overall']));
    }

    unset($results[$key]['overall']);
    unset($results[$key]['round_1']);
    unset($results[$key]['round_2']);
    unset($results[$key]['round_3']);
    unset($results[$key]['round_4']);
}

$lines = explode(PHP_EOL, file_get_contents('players'));

$players = [];

foreach ($lines as $line){
    $chunks = explode('|', $line);
    if(count($chunks) != 3) continue;

    foreach ($results as $result){
        if($result['player'] == $chunks[1]){
            $players[] = [
                'id' => $chunks[0],
                'name' => $chunks[1],
                'country' => $chunks[2],
                'score' => $result['score']
            ];
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
        'choices' => $choices
    ];
}

$standings = [];

foreach($entries as $entry){
    $entrant = $entry['name'];

    foreach($entry['choices'] as $chosen_player){
        $found = false;

        foreach ($results as $result){
            if(stripos($result['player'], $chosen_player)  !== false){
                $standings[$entrant]['players'][$chosen_player] = $result['score'];

                $found = true;
                // echo $entrant . ' chose ' . $result['player'] . ' ' . $result['score'] . PHP_EOL;

                if(!isset($standings[$entrant]['overall'])){
                    $standings[$entrant]['overall'] = $result['score'];
                } else {
                    $standings[$entrant]['overall'] = $standings[$entrant]['overall'] + $result['score'];
                }
                break;
            }
        }

        if(!$found){
            echo $entrant . ' NOT MATCHED ' . $chosen_player . PHP_EOL;
        }
    }
}

uasort($standings, function($a, $b) {
    return $a['overall'] - $b['overall'];
});

$year = date("Y");
$date = date("Md");
$time = date('H:i:s');

echo <<< EOT

<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
<script src="script.js"></script>

<h2>P130<span class="ceefax">Ceefax</span>130 $date <span id="time" style="color: yellow;"></span></h2>

<h1>
    <span class="bbc">B</span><span class="bbc">B</span><span class="bbc">C</span><span class="ceefax" style="color: limegreen;">GOLF</span>
</h1>
<hr style="border-color: blue;">
<p style="color: limegreen;">Masters tournament $year</p>
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
    echo "<td class='entry-name' style='background: $color'>$entrant<//td>" . PHP_EOL;
    echo "<td>$overall</td>" . PHP_EOL;


    foreach($standing['players'] as $player => $score){
        echo "<td>$player</td>" . PHP_EOL;
        echo "<td>$score</td>" . PHP_EOL;
    }
    $count ++;
}

echo "</tr></tbody></table></div><h2 class='ceefax' style='margin-top: 20px; text-align: center'>Ceefax: The world at your fingertips</h2>";