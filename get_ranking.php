<?php
if ($argc !== 1) {
    fputs(STDERR, "Error: Invalid number of arguments.\n");
    exit(1);
}

main();

function main() {
    outputLine('エントリーデータが入っているcsvファイル名を入力してください。');
    $entryLogFileName = inputFileName();
    $entryData = parseCsv($entryLogFileName);

    outputLine('プレイログデータが入っているcsvファイル名を入力してください。');
    $playLogFileName = inputFileName();
    $playLogData = parseCsv($playLogFileName);
    
    $entryPlayersRankingData = entryPlayersRankingData($entryData, $playLogData);

    outputRankingData($entryPlayersRankingData);
}

function inputFileName() {
    $fileName = trim(fgets(STDIN));
    if (!file_exists($fileName)) {
        fputs(STDERR, "Error: file does not exists.");
        exit(1);
    }

    return $fileName;
}

// csvファイルのデータを行ごとに[列名 => データ]の配列にする
function parseCsv($csvFileName) {
    $file = fopen("./{$csvFileName}", 'r');

    $header = fgetcsv($file);

    $result = [];
    $currentKeyCount = 0;
    while ($row = fgetcsv($file)) {
        foreach ($header as $columnKey => $columnName) {
            $result[$currentKeyCount][$columnName] = $row[$columnKey];
        }
        $currentKeyCount++;
    }
    
    fclose($file);

    return $result;
}

function entryPlayersRankingData($entryData, $playLogData) {
    $entryPlayersData = entryPlayersData($entryData, $playLogData);

    $result = rankEntryPlayersData($entryPlayersData);

    return $result;
}

function outputRankingData($entryPlayersRanking) {
    outputLine('rank,player_id,handle_name,score');

    foreach ($entryPlayersRanking as $entryPlayer) {
        outputLine("{$entryPlayer['rank']},{$entryPlayer['player_id']},{$entryPlayer['handle_name']},{$entryPlayer['score']}");
    }
}

function outputLine($outputString) {
    echo "{$outputString}\n";
}

function entryPlayersData($entryData, $playLogData) {
    // エントリーしたプレイヤーのみのデータを抽出する
    return array_map(function($entryRowData) use($playLogData) {
        // エントリープレイヤー毎にスコアが一番高いデータのみを取り出す
        $playLogRowDataKeys = array_keys(array_column($playLogData, 'player_id'), $entryRowData['player_id']);
        $bestPlayLogRowData = array_reduce($playLogRowDataKeys, function($bestPlayLogRowData, $playLogRowDataKey) use($playLogData) {
            if (!$bestPlayLogRowData || $playLogData[$playLogRowDataKey]['score'] > $bestPlayLogRowData['score']) {
                return $playLogData[$playLogRowDataKey];
            }
            return $bestPlayLogRowData;
        }, []);
        
        return array_merge($entryRowData, $bestPlayLogRowData);
    }, $entryData);
}

function rankEntryPlayersData($entryPlayersData) {
    $result = [];
    $rank = 1;
    $prevScore = null;
    $loopCount = 1;
    foreach (sortByScore($entryPlayersData) as $entryPlayerRowData) {
        // 同一順位ありとする
        if ($prevScore !== $entryPlayerRowData['score']) {
            if ($loopCount > 10) {
                break;
            }

            $rank = $loopCount;
        }

        $result[] = [
            'rank' => $rank,
            'player_id' => $entryPlayerRowData['player_id'],
            'handle_name' => $entryPlayerRowData['handle_name'],
            'score' => $entryPlayerRowData['score'],
        ];

        $prevScore = $entryPlayerRowData['score'];
        $loopCount++;
    }

    return $result;
}

function sortByScore($entryPlayersData) {
    array_multisort(
        array_column($entryPlayersData, 'score'), SORT_DESC,
        array_column($entryPlayersData, 'player_id'), SORT_ASC,
        $entryPlayersData
    );
    return $entryPlayersData;
}