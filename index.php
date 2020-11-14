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
    $entries['chris']['players'] = ['schauffele', 'rahm', 'hatton', 'oosthuizen', 'rose'];
    $entries['emma']['players'] = ['dustin-johnson', 'rahm', 'day', 'spieth', 'mickelson'];
    $entries['phil']['players'] = ['dechambeau', 'mcilroy', 'woods', 'oosthuizen', 'westwood'];
    $entries['dermot']['players'] = ['koepka', 'mcilroy', 'reed', 'casey', 'griffin'];
    $entries['lucy']['players'] = ['dustin-johnson', 'rahm', 'finau', 'casey', 'rose'];
} else {
    $entries['phil']['players'] = ['dechambeau', 'mcilroy', 'woods', 'oosthuizen', 'westwood'];
    $entries['pete']['players'] = ['dustin-johnson', 'koepka', 'fleetwood', 'fowler', 'westwood'];
    $entries['neil']['players'] = ['mcilroy', 'rahm', 'fleetwood', 'schauffele', 'wallace'];
    $entries['david']['players'] = ['mcilroy', 'rahm', 'scott', 'fowler', 'mickelson'];
    $entries['naomi']['players'] = ['thomas', 'dechambeau', 'hatton', 'spieth', 'westwood'];
    $entries['alan']['players'] = ['dechambeau', 'mcilroy', 'finau', 'casey', 'munoz'];
    $entries['stevie']['players'] = ['dechambeau', 'mcilroy', 'finau', 'oosthuizen', 'molinari'];
    $entries['chris']['players'] = ['brooks', 'dustin-johnson', 'fleetwood', 'fowler', 'molinari'];
    $entries['greg']['players'] = ['dustin-johnson', 'rahm', 'scott', 'oosthuizen', 'rose'];
}

$output = array();

foreach($entries as $entrant => $entry){
    foreach($entry['players'] as $chosen_player){
        $found = false;

        foreach ($results as $result){
            if(stripos($result['player'], $chosen_player)  !== false){
                $found = true;
                //echo $entrant . ' chose ' . $result['player'] . ' ' . $result['score'] . PHP_EOL;

                if(!isset($entries[$entrant]['score'])){
                    $entries[$entrant]['score'] = $result['score'];
                } else {
                    $entries[$entrant]['score'] = $entries[$entrant]['score'] + $result['score'];
                }
                break;
            }
        }

        if(!$found){
            echo $entrant . ' NOT MATCHED ' . $chosen_player . PHP_EOL;
        }
    }
}

foreach($entries as $entrant => $entry){
    echo $entrant . ' ' .  $entry['score'] . '<br>';
}
