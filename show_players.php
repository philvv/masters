<?php

$dump = file_get_contents('players');

$lines = explode(PHP_EOL, $dump);

$players = [];

foreach ($lines as $line){
    $chunks = explode('|', $line);
    if(count($chunks) != 3) continue;

    $players[] = [
        'id' => $chunks[0],
        'name' => $chunks[1],
        'country' => $chunks[2]
    ];
}

print_r($players);