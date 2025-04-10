<?php

$dump = file_get_contents('players');

$lines = explode(PHP_EOL, $dump);

$players = [];

//$index = 0;
//$id = 1;
//
//while(true){
//    if(!isset($lines[$index])) break;
//
//    $country = trim($lines[$index]);
//    $player = trim($lines[$index + 1]);
//
//    $players[] = [
//        'id' => $id,
//        'name' => trim($lines[$index + 1]),
//        'country' => trim($lines[$index])
//    ];
//
//    $id++;
//    $index += 3;
//}
//
//$output = '';
//
//foreach($players as $player){
//    $output .= $player['id'] . '|' . $player['name'] . '|' . $player['country'] . PHP_EOL;
//}
//
//
//var_dump($output);exit();

foreach ($lines as $line){
    $chunks = explode('|', $line);
    if(count($chunks) != 3) continue;

    $players[] = [
        'id' => $chunks[0],
        'name' => $chunks[1],
        'country' => $chunks[2]
    ];
}

echo <<< EOT

<p>Players</p>
<div class="content">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Country</th>
                </tr>
            </thead>
            <tbody>
EOT;
foreach($players as $player){
    echo "<tr>";
    echo "<td>" . $player['id'] . "</td>";
    echo "<td>" . $player['name'] . "</td>";
    echo "<td>" . $player['country'] . "</td>";
}

echo "</tr></tbody></table>";