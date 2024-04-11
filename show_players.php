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