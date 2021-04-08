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

foreach ($contents as $content) {
    $tds = explode('<td class="Table__TD">', $content);

    $bits = explode('leaderboard_player_name', $tds[0]);

    if(!isset($bits[1])) continue;

    $bits = explode(">", $bits[1]);
    $name = explode("<", $bits[1])[0];
    $name = trim(strtolower(explode('(a)', $name)[0]));
    $name = str_replace(' ', '-', $name);

    $results[] = [
        'player' => $name,
        'overall' => str_replace('</td>', '', $tds[1]),
        'round_1' => str_replace('</td>', '', $tds[4]),
        'round_2' => str_replace('</td>', '', $tds[5]),
        'round_3' => str_replace('</td>', '', $tds[6]),
        'round_4' => str_replace('</td>', '', $tds[7]),
    ];
}

foreach($results as $key => $result){
    if($result['overall'] == 'WD') {
        unset($results[$key]);
        continue;
    }
    if($result['overall'] == 'CUT') {
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

$entries = array();

if(isset($_GET['comp']) && $_GET['comp'] == 'foundry'){
    $entries['chris']['players'] = ['mcilroy', 'spieth', 'schauffele', 'dustin-johnson'];
    $entries['lucy']['players'] = ['mcilroy', 'spieth', 'dustin-johnson', 'rahm'];
    $entries['jill']['players'] = [];
    $entries['dermot']['players'] = [];
    $entries['phil']['players'] = [];
    $entries['emma']['players'] = [];
}

$standings = array();

foreach($entries as $entrant => $entry){
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

//print_r($standings);exit();

echo <<< EOT
<style type="text/css">
.tftable {font-size:12px;color:#333333;width:100%;border-width: 1px;border-color: #729ea5;border-collapse: collapse;}
.tftable th {font-size:12px;background-color:#acc8cc;border-width: 1px;padding: 8px;border-style: solid;border-color: #729ea5;text-align:left;}
.tftable tr {background-color:#d4e3e5;}
.tftable td {font-size:12px;border-width: 1px;padding: 8px;border-style: solid;border-color: #729ea5;}
.tftable tr:hover {background-color:#ffffff;}
</style>

<table class="tftable" border="1">
<tr>
    <th>Name</th>   
    <th>Overall</th>
    <th>Choice 1</th>
    <th>Score</th>
    <th>Choice 2</th>
    <th>Score</th>
    <th>Choice 3</th>
    <th>Score</th>
    <th>Choice 4</th>
    <th>Score</th>
</tr>
EOT;

foreach($standings as $entrant => $standing){
    $overall = $standing['overall'];
    echo "<tr>" . PHP_EOL;
    echo "<td>$entrant</td>" . PHP_EOL;
    echo "<td>$overall</td>" . PHP_EOL;

    foreach($standing['players'] as $player => $score){
        echo "<td>$player</td>" . PHP_EOL;
        echo "<td>$score</td>" . PHP_EOL;
    }

    echo "<tr>" . PHP_EOL;
}

echo "</table>";
