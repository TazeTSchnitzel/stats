<?php

$page_header = <<<HTML
<!doctype html>
<meta charset=utf-8>
<title>GG2 Stat collection plugin</title>
<link rel=stylesheet href=style.css>
<div id=head><img src="http://static.ganggarrison.com/GG2ForumLogo.png" alt="" id=logo><img src="http://static.ganggarrison.com/Themes/GG2/images/smflogo.gif" alt="" id=smflogo></div>
HTML;

$dbSchema = file_get_contents('../schema.sql');

$PDO = new PDO('sqlite:../stats.db');
$PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$PDO->exec($dbSchema);

function printTable($name, $query, $bindings, $link_column = NULL, $link_prefix = NULL) {
    global $PDO;
    $stmt = $PDO->prepare($query);
    $stmt->execute($bindings);
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
            foreach ($row as $key => $value) {
                echo '<td>';
                if ($key === $link_column) {
                    echo '<a href="' . $link_prefix . $value .'">';
                }
                if ($value === NULL) {
                    echo '<abbr title="Not Applicable" class=not-applicable>N/A</abbr>';
                } else {
                    echo htmlspecialchars($value);
                }
                if ($key === $link_column) {
                    echo '</a>';
                }
                echo '</td>' . PHP_EOL;
            }
            echo '</tr>' . PHP_EOL;
        }
        echo '</tbody>' . PHP_EOL;
        echo '</table>' . PHP_EOL;
    }
}

// *** Make sure this is a real request ***

$action = isset($_GET['action']) ? $_GET['action'] : 'home';

// This isn't exactly RESTful, is it?
// TODO/FIXME: Make Hacker News happy.
if ($action === 'home') {
    // Home page (game listing)
    echo $page_header;
    echo '<div id=desc>' . PHP_EOL;
    echo '<p>GG2 Stat collection plugin data.</p>' . PHP_EOL;
    echo '<p>See <a href="http://www.ganggarrison.com/forums/index.php?topic=34728.0">the forum thread</a> for more info.</p>' . PHP_EOL;
    echo '</div>';
    printTable('game', '
        SELECT
            game.id AS gameId, version, serverName, serverIP, serverPort, map,
            teamTypes.name AS winner, gameMode, timer, timeLimit, respawnTime,
            controlPoints,  setupGate, capsRed, capsBlue, capLimit
        FROM
            game
        LEFT JOIN
            teamTypes
        ON
            game.winner = teamTypes.id;
    ', [], 'gameId', '/?action=game&gameId=');
} else if ($action === 'game') {
    // Game page
    $gameId = (int)$_GET['gameId'];
    echo $page_header;
    echo '<a href=/>back</a>';
    printTable('game', '
        SELECT
            game.id AS gameId, version, serverName, serverIP, serverPort, map,
            teamTypes.name AS winner, gameMode, timer, timeLimit, respawnTime,
            controlPoints,  setupGate, capsRed, capsBlue, capLimit
        FROM
            game
        LEFT JOIN
            teamTypes
        ON
            game.winner = teamTypes.id
        WHERE
            gameId = :gameId
        LIMIT
            1;
    ', [':gameId' => $gameId]);
    printTable('player', '
        SELECT
            gameId, player.id AS id, teamTypes.name AS team,
            classTypes.name as class, queueJump
        FROM
            player
        LEFT JOIN
            teamTypes
        ON
            player.team = teamTypes.id
        LEFT JOIN
            classTypes
        ON
            player.class = classTypes.id
        WHERE
            gameId = :gameId;
    ', [':gameId' => $gameId], 'id', "/?action=player&gameId=$gameId&playerId=");
} else if ($action === 'player') {
    // Player page (stat listing)
    $gameId = (int)$_GET['gameId'];
    $playerId = (int)$_GET['playerId'];
    echo $page_header;
    echo "<a href=/?action=game&gameId=$gameId>back</a>";
    printTable('player', '
        SELECT
            gameId, player.id AS id, teamTypes.name AS team,
            classTypes.name as class, queueJump
        FROM
            player
        LEFT JOIN
            teamTypes
        ON
            player.team = teamTypes.id
        LEFT JOIN
            classTypes
        ON
            player.class = classTypes.id
        WHERE
            gameId = :gameId AND
            player.id = :playerId
        LIMIT
            1;
    ', [':gameId' => $gameId, ':playerId' => $playerId]);
    printTable('stat', '
        SELECT 
            gameId, playerId, statTypes.name AS type, value
        FROM
            stat
        LEFT JOIN
            statTypes
        ON
            stat.type = statTypes.id
        WHERE
            gameId = :gameId AND
            playerId = :playerId;
    ', [':gameId' => $gameId, ':playerId' => $playerId ]);
} else if ($action === 'submit') {
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
    } else if ($_GET['wins0'])) {
        $data['caps'][0] = (int)$_GET['wins0'];
        $data['caps'][1] = (int)$_GET['wins1'];
    } else {
        $data['caps'][0] = NULL;
        $data['caps'][1] = NULL;
    }
    if (isset($_GET['capLimit'])) {
        $data['capLimit'] = (int)$_GET['capLimit'];
    } else {
        $data['capLimit'] = NULL;
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
                setupGate, capsRed, capsBlue,  capLimit
            )
        VALUES
            (
                :version, :serverName, :serverIP, :serverPort, :map, :winner,
                :gameMode, :timer, :timeLimit, :respawnTime, :controlPoints,
                :setupGate, :capsRed, :capsBlue, :capLimit
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
        ':capLimit' => $data['capLimit']
    ]);
    $gameId = $PDO->lastInsertId();

    // Player info
    foreach ($data['players'] as $id => $player) {
        $stmt = $PDO->prepare('
            INSERT INTO
                player(
                    gameId, id, team, class, queueJump
                )
            VALUES
                (
                    :gameId, :id, :team, :class, :queueJump
                );
        ');
        $stmt->execute([
            ':gameId' => $gameId,
            ':id' => $id,
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
