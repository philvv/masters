<?php

require "vendor/autoload.php";
use PHPHtmlParser\Dom;



        $html = file_get_contents('http://www.espn.com/golf/leaderboard');

        $dom = new Dom;
        $dom->loadStr($html);

        $contents = $dom->find('tr');

        unset($contents[0]);

        $results = array();

        $colors = array("red", "green", "blue", "yellow", "orange", "cyan", "purple", "pink", "blue", "orange");

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

        $comp = $_GET['comp'] ?? 'foundry';

        if($comp == 'boob'){
            $entries['greg']['players'] = ['jason-day', 'adam-scott', 'dustin-johnson', 'rahm'];
            $entries['sk']['players'] = ['fitzpatrick', 'spieth', 'justin-thomas', 'schauffele'];
            $entries['shaw']['players'] = ['dechambeau', 'spieth', 'dustin-johnson', 'cantlay'];
            $entries['simmsy']['players'] = ['mcIlroy', 'conners', 'thomas', 'dustin-johnson'];
            $entries['charts']['players'] = ['koepka', 'kevin-na', 'stenson', 'patrick-reed'];
            $entries['stevie']['players'] = ['dechambeau', 'spieth', 'thomas', 'patrick-reed'];
            $entries['binso']['players'] = ['mcIlroy', 'spieth', 'dustin-johnson', 'thomas'];
            $entries['peedee']['players'] = ['fitzpatrick', 'fleetwood', 'dustin-johnson', 'westwood'];
            $entries['millsy']['players'] = ['koepka', 'spieth', 'dustin-johnson', 'thomas'];
            $entries['boob']['players'] = ['mcIlroy', 'spieth', 'westwood', 'rahm'];

        } else {
            $entries['chris']['players'] = ['mcIlroy', 'spieth', 'schauffele', 'dustin-johnson'];
            $entries['lucy']['players'] = ['mcIlroy', 'spieth', 'dustin-johnson', 'rahm'];
            $entries['david']['players'] = ['fitzpatrick', 'smith', 'dustin-johnson', 'thomas'];
            $entries['dermot']['players'] = ['dechambeau', 'spieth', 'morikawa', 'dustin-johnson'];
            $entries['phil']['players'] = ['dechambeau', 'lowry', 'stenson', 'zach-johnson'];
            $entries['emma']['players'] = ['mcIlroy', 'smith', 'thomas', 'dustin-johnson'];
            $entries['catherine']['players'] = ['dechambeau', 'garcia', 'dustin-johnson', 'rahm'];
            $entries['jill']['players'] = ['willett', 'fleetwood', 'dustin-johnson', 'stenson'];
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

        $title = $comp == 'boob' ? 'Boobs' : 'Foundry';

        echo "<pre>";
        echo json_encode($standings);
        echo print_r($standings);