<?php

require "vendor/autoload.php";
use PHPHtmlParser\Dom;

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

$html = file_get_contents('http://www.espn.com/golf/leaderboard');

$dom = new Dom;
$dom->loadStr($html);

$contents = $dom->find('tr');

unset($contents[0]);

$results = array();

$colors = array("Moccasin", "white", "yellow", "LightBlue", "SandyBrown", "cyan", "pink", "LightGray");

foreach ($contents as $content) {
    $tds = explode('<td class="PlayerRow__Overview PlayerRow__Overview--expandable Table__TR Table__even">', $content);

    $bits = explode('leaderboard_player_name', $tds[0]);

    if(!isset($bits[1])) continue;

    $bits = explode(">", $bits[1]);
    $name = explode("<", $bits[1])[0];
    $name = trim(strtolower(explode('(a)', $name)[0]));
    $name = str_replace(' ', '-', $name);

    $results[] = [
        'player' => strtolower($name),
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

$players_raw = file_get_contents('players');
$players = [];

foreach (explode(PHP_EOL, $players_raw) as $player){
    $chunks = explode(' ', $player);
    $players[$chunks[0]] = $chunks[1];
}

$entries_raw = file_get_contents('entries');
$entries = [];

foreach (explode(PHP_EOL, $entries_raw) as $entry){
    $chunks = explode(' ', $entry);
    $codes = explode(',', $chunks[1]);


    $players_code = [];

    $entries[] = [
        'name' => $chunks[0],
        'players' => [$players[$codes[0]], $players[$codes[1]], $players[$codes[2]], $players[$codes[3]]]
    ];
}

$standings = array();

foreach($entries as $entry){
    $entrant = $entry['name'];
    foreach($entry['players'] as $chosen_player){
        $found = false;

        foreach ($results as $result){
            if(stripos($result['player'], $chosen_player)  !== false){
                $standings[$entrant]['players'][$chosen_player] = $result['score'];

                $found = true;
                //echo $entrant . ' chose ' . $result['player'] . ' ' . $result['score'] . PHP_EOL;

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

echo <<< EOT

<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Press Start 2P', cursive;
            background: black;
            color: limegreen;
            text-transform: capitalize;
        }
        
        h1 {
            font-size: 36px;
            text-align: center;
        }
        
        marquee {
          display: block;
          margin-left: auto;
          margin-right: auto;
          width: 40%;
        }
        
        @media only screen and (max-width: 600px) {
            marquee {
                width: 100%;
                margin-bottom: 0.67rem;
            }    
        }
        
        .table-responsive {
            overflow-x: auto;
            display: block;
            width: 100%;
        }
        
        .content {
            
        }
        
        .table {
            width: 100%;
        }
        
        tr {
            border-width: 1px 1px 1px 1px;
            border-style: solid;
            border-color: limegreen;
        }
        
        td {
            padding: 10px;
            border-width: 1px 1px 1px 1px;
            border-style: solid;
            border-color: limegreen;
            font-size: 10px;
        }
        
        th {
            padding: 10px;
            text-align: left;
            border-width: 1px 1px 1px 1px;
            border-style: solid;
            border-color: limegreen;
        }
        
        thead {
            font-size: 16px;
        }
        
        tr:hover {
            background-color: gray;
        }
        
        .table-header tr:first-child { 
            border-width: 1px 1px 1px 1px;
            border-style: solid;
            border-color: limegreen;
            background: limegreen;
            color: black;
        }
        
        tr:last-child { 
            border-width: 1px 1px 1px 1px;
            border-style: solid;
            border-color: limegreen;
        }
        
        td:nth-child(odd){
            width: 15%;
        }
       
    </style>
<h1>Ceefax style Open for SK</h1>
<marquee>Winner Winner 🐔 Dinner</marquee>
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
    echo "<td style='color: black; background: $color'>$entrant<//td>" . PHP_EOL;
    echo "<td>$overall</td>" . PHP_EOL;


    foreach($standing['players'] as $player => $score){
        echo "<td>$player</td>" . PHP_EOL;
        echo "<td>$score</td>" . PHP_EOL;
    }
    $count ++;
}

echo "</div></tbody></table></div>";