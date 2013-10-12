<?php

// This isn't exactly RESTful, is it?
// TODO: Make Hacker News happy.

$teams = [
    0 => 'RED',
    1 => 'BLUE',
    2 => 'SPECTATOR'
];

$classes = [
    0 => 'SCOUT',
    1 => 'SOLDIER',
    2 => 'SNIPER',
    3 => 'DEMOMAN',
    4 => 'MEDIC',
    5 => 'ENGINEER',
    6 => 'HEAVY',
    7 => 'SPY',
    8 => 'PYRO',
    9 => 'QUOTE'
];

$statTypes = [
    0 => 'KILLS',
    1 => 'DEATHS',
    2 => 'CAPS',
    3 => 'ASSISTS',
    4 => 'DESTRUCTION',
    5 => 'STABS',
    6 => 'HEALING',
    7 => 'DEFENSES',
    8 => 'INVULNS',
    9 => 'BONUS',
    10 => 'DOMINATIONS',
    11 => 'REVENGE',
    12 => 'POINTS'
];

$data = [];
$data['serverName'] = $_GET['serverName'];
$data['map'] = $_GET['map'];
$data['winners'] = $teams[$_GET['winners']];
$data['gameMode'] = $_GET['gameMode'];
if ($data['gameMode'] == 'cp') {
    $data['controlPoints'] = (int)$_GET['controlPoints'];
    $data['setupGate'] = (bool)(int)$_GET['setupGate'];
}
if (isset($_GET['caps0'])) {
    $data['caps']['RED'] = (int)$_GET['caps0'];
    $data['caps']['BLUE'] = (int)$_GET['caps1'];
}
if (isset($_GET['wins0'])) {
    $data['wins']['RED'] = (int)$_GET['wins0'];
    $data['wins']['BLUE'] = (int)$_GET['wins1'];
}

$playerCount = (int)$_GET['players'];
$data['players'] = [];
for ($i = 0; $i < $playerCount; $i++) {
    $prefix = 'player' . $i . '_';
    $stats = [];
    foreach (explode(',', $_GET[$prefix . 'stats']) as $key => $value) {
        $stats[$statTypes[$key]] = (int)$value;
    }
    $data['players'][] = [
        'team' => $teams[$_GET[$prefix . 'team']],
        'class' => $classes[$_GET[$prefix . 'class']],
        'name' => $_GET[$prefix . 'name'],
        'queueJump' => (bool)(int)$_GET[$prefix . 'queueJump'],
        'stats' => $stats
    ];
}

ob_start();
var_export($data);
error_log(ob_get_flush());
ob_clean();

echo "SUCCESS";
