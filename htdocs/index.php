<?php

$dbSchema = file_get_contents('../schema.sql');

$PDO = new PDO('sqlite:../stats.db');
$PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$PDO->exec($dbSchema);

function printTable($name, $query) {
    global $PDO;
    $stmt = $PDO->prepare($query);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo '<h2>' . htmlspecialchars($name) . '</h2>' . PHP_EOL;
    if (empty($rows)) {
        echo '<em>There doesn\'t appear to be anything here.</em>' . PHP_EOL;
    } else {
        echo '<table>' . PHP_EOL;
        echo '<thead>' . PHP_EOL;
        echo '<tr>' . PHP_EOL;
        foreach (array_keys($rows[0]) as $key) {
            echo '<th>' . htmlspecialchars($key) . '</th>' . PHP_EOL;
        }
        echo '</tr>' . PHP_EOL;
        echo '</thead>' . PHP_EOL;
        echo '<tbody>' . PHP_EOL;
        foreach ($rows as $row) {
            echo '<tr>' . PHP_EOL;
            foreach ($row as $value) {
                if ($value === NULL) {
                    echo '<td><em>N/A</em></td>' . PHP_EOL;
                } else {
                    echo '<td>' . htmlspecialchars($value) . '</td>' . PHP_EOL;
                }
            }
            echo '</tr>' . PHP_EOL;
        }
        echo '</tbody>' . PHP_EOL;
        echo '</table>' . PHP_EOL;
    }
}

// *** Make sure this is a real request ***

// This isn't exactly RESTful, is it?
// TODO/FIXME: Make Hacker News happy.
if (!isset($_GET['action']) || $_GET['action'] !== 'submit') {
    // Presumably we want a nice stats output?
    echo '<!doctype html>' . PHP_EOL;
    echo '<meta charset=utf-8>' . PHP_EOL;
    echo '<title>GG2 Stat collection plugin</title>' . PHP_EOL;
    echo '<h1>GG2 Stat collection plugin</h1>' . PHP_EOL;
    echo 'See <a href="http://www.ganggarrison.com/forums/index.php?topic=34728.0">the forum thread</a> for more info.' . PHP_EOL;
    printTable('game', '
        SELECT
            game.id AS gameId, version, serverName, serverIP, serverPort, map,
            winner, teamTypes.name AS winnerName, gameMode,  timer, timeLimit,
            respawnTime, controlPoints,  setupGate, capsRed, capsBlue, capLimit,
            winsRed, winsBlue
        FROM
            game
        LEFT JOIN
            teamTypes
        ON
            game.winner = teamTypes.id;
    ');
    printTable('player', '
        SELECT
            gameId, player.id AS id, player.name AS name, team,
            teamTypes.name AS teamName, class, classTypes.name as className,
            queueJump
        FROM
            player
        LEFT JOIN
            teamTypes
        ON
            player.team = teamTypes.id
        LEFT JOIN
            classTypes
        ON
            player.class = classTypes.id;
    ');
    printTable('stat', '
        SELECT 
            gameId, playerId, type, statTypes.name AS typeName, value
        FROM
            stat
        LEFT JOIN
            statTypes
        ON
            stat.type = statTypes.id;');
} else {
    // Submit mode
    // *** Get data ***

    $data = [];
    $data['version'] = (int)$_GET['version'];
    $data['serverName'] = $_GET['serverName'];
    $data['serverIP'] = $_SERVER['REMOTE_ADDR'];
    $data['serverPort'] = (int)$_GET['serverPort'];
    $data['map'] = $_GET['map'];
    $data['winners'] = (int)$_GET['winners'];
    if ($_GET['gameMode'] === '?') {
        $data['gameMode'] = NULL;
    } else {
        $data['gameMode'] = $_GET['gameMode'];
    }
    if ($data['gameMode'] == 'cp') {
        $data['controlPoints'] = (int)$_GET['controlPoints'];
        $data['setupGate'] = (bool)(int)$_GET['setupGate'];
    } else {
        $data['controlPoints'] = NULL;
        $data['setupGate'] = NULL;
    }
    if (isset($_GET['timer'])) {
        $data['timer'] = (int)$_GET['timer'];
        $data['timeLimit'] = (int)$_GET['timeLimit'];
    } else {
        $data['timer'] = NULL;
        $data['timeLimit'] = NULL;
    }
    if (isset($_GET['respawnTime'])) {
        $data['respawnTime'] = (int)$_GET['respawnTime'];
    } else {
        $data['respawnTime'] = NULL;
    }
    if (isset($_GET['caps0'])) {
        $data['caps'][0] = (int)$_GET['caps0'];
        $data['caps'][1] = (int)$_GET['caps1'];
        $data['capLimit'] = (int)$_GET['capLimit'];
    } else {
        $data['caps'][0] = NULL;
        $data['caps'][1] = NULL;
        $data['capLimit'] = NULL;
    }
    if (isset($_GET['wins0'])) {
        $data['wins'][0] = (int)$_GET['wins0'];
        $data['wins'][1] = (int)$_GET['wins1'];
    } else {
        $data['wins'][0] = NULL;
        $data['wins'][1] = NULL;
    }

    $playerCount = (int)$_GET['players'];
    $data['players'] = [];
    for ($i = 0; $i < $playerCount; $i++) {
        $prefix = 'player' . $i . '_';
        $stats = [];
        foreach (explode(',', $_GET[$prefix . 'stats']) as $key => $value) {
            $stats[$key] = (int)$value;
        }
        $data['players'][] = [
            'team' => (int)$_GET[$prefix . 'team'],
            'class' => (int)$_GET[$prefix . 'class'],
            'name' => $_GET[$prefix . 'name'],
            'queueJump' => (bool)(int)$_GET[$prefix . 'queueJump'],
            'stats' => $stats
        ];
    }

    // *** Insert data ***

    $PDO->beginTransaction();

    // Game info
    $stmt = $PDO->prepare('
        INSERT INTO
            game(
                version, serverName, serverIP, serverPort, map, winner,
                gameMode, timer, timeLimit, respawnTime, controlPoints,
                setupGate, capsRed, capsBlue,  capLimit, winsRed, winsBlue
            )
        VALUES
            (
                :version, :serverName, :serverIP, :serverPort, :map, :winner,
                :gameMode, :timer, :timeLimit, :respawnTime, :controlPoints,
                :setupGate, :capsRed, :capsBlue, :capLimit, :winsRed, :winsBlue
            );
    ');
    $stmt->execute([
        ':version' => $data['version'],
        ':serverName' => $data['serverName'],
        ':serverIP' => $data['serverIP'],
        ':serverPort' => $data['serverPort'],
        ':map' => $data['map'],
        ':winner' => $data['winners'],
        ':gameMode' => $data['gameMode'],
        ':timer' => $data['timer'],
        ':timeLimit' => $data['timeLimit'],
        ':respawnTime' => $data['respawnTime'],
        ':controlPoints' => $data['controlPoints'],
        ':setupGate' => $data['setupGate'],
        ':capsRed' => $data['caps'][0],
        ':capsBlue' => $data['caps'][1],
        ':capLimit' => $data['capLimit'],
        ':winsRed' => $data['wins'][0],
        ':winsBlue' => $data['wins'][1]
    ]);
    $gameId = $PDO->lastInsertId();

    // Player info
    foreach ($data['players'] as $id => $player) {
        $stmt = $PDO->prepare('
            INSERT INTO
                player(
                    gameId, id, name, team, class, queueJump
                )
            VALUES
                (
                    :gameId, :id, :name, :team, :class, :queueJump
                );
        ');
        $stmt->execute([
            ':gameId' => $gameId,
            ':id' => $id,
            ':name' => $player['name'],
            ':team' => $player['team'],
            ':class' => $player['class'],
            ':queueJump' => $player['queueJump']
        ]);

        // Stats
        foreach ($player['stats'] as $statType => $value) {
            $stmt = $PDO->prepare('
                INSERT INTO
                    stat(
                        gameId, playerId, type, value
                    )
                VALUES
                    (
                        :gameId, :playerId, :type, :value
                    );
            ');
            $stmt->execute([
                ':gameId' => $gameId,
                ':playerId' => $id,
                ':type' => $statType,
                ':value' => $value
            ]);
        }
    }

    $PDO->commit();

    echo "SUCCESS";
}
