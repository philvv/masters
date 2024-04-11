<?php

$dump = file_get_contents('dump');

$lines = explode(PHP_EOL, $dump);

$players = [];

for($i = 0; $i < 1000;  $i += 3){
    $player = array_slice($lines, $i, $i + 3);

    //print_r($player);exit();

    if(empty($player)) break;

    $players[] = [
        'name' => trim($player[1]),
        'country' => trim($player[0])
    ];
}

file_put_contents('players', '');

foreach($players as $key => $player){
    file_put_contents('players', $key + 1 . '|' . $player['name'] . '|' . $player['country'] . PHP_EOL, FILE_APPEND);
}

echo 'done';


