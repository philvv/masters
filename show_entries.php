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

$dump = file_get_contents('entries');

$lines = explode(PHP_EOL, $dump);

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

echo <<< EOT

<p>Entries</p>
<div class="content">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>A</th>
                    <th>B</th>
                    <th>B</th>
                    <th>C</th>
                </tr>
            </thead>
            <tbody>
EOT;
foreach($entries as $entry){
    echo "<tr>";
    echo "<td>" . $entry['name'] . "</td>";
    echo "<td>" . $entry['choices'][0] . "</td>";
    echo "<td>" . $entry['choices'][1] . "</td>";
    echo "<td>" . $entry['choices'][2] . "</td>";
    echo "<td>" . $entry['choices'][3] . "</td>";
}

echo "</tr></tbody></table>";