// Stats reporting plugin v1
// Part 2/2: Plugins\Stats\stats_onroundend.gml
// Copyright © 2013 Andrea Faulds

// This file is the meat of this plugin. It does the actual stat collection and reporting.
// It is called from stats.gml

var statMap;
statMap = ds_map_create();

ds_map_add(statMap, 'serverName', global.serverName);
ds_map_add(statMap, 'map', global.currentMap);
ds_map_add(statMap, 'winners', global.winners);

if (instance_exists(IntelligenceBaseBlue) || instance_exists(IntelligenceBaseRed)
    || instance_exists(IntelligenceRed) || instance_exists(IntelligenceBlue)) {
    ds_map_add(statMap, 'gameMode', 'ctf');
} else if (instance_exists(GeneratorBlue) || instance_exists(GeneratorRed)) {
    ds_map_add(statMap, 'gameMode', 'gen');
} else if (instance_exists(ArenaControlPoint)) {
    ds_map_add(statMap, 'gameMode', 'arena');
} else if (instance_exists(KothControlPoint)) {
    ds_map_add(statMap, 'gameMode', 'koth');
} else if (instance_exists(KothRedControlPoint) && instance_exists(KothBlueControlPoint)) {
    ds_map_add(statMap, 'gameMode', 'dkoth');
} else if instance_exists(ControlPoint) {
    ds_map_add(statMap, 'gameMode', 'cp');
    ds_map_add(statMap, 'controlPoints', instance_number(ControlPoint));
    ds_map_add(statMap, 'setupGate', instance_exists(ControlPointSetupGate));
} else {
    ds_map_add(statMap, 'gameMode', '?');
}

if instance_exists(ScorePanel){
    ds_map_add(statMap, 'caps0', global.redCaps);
    ds_map_add(statMap, 'caps1', global.blueCaps);
}
else if instance_exists(ArenaHUD){
    ds_map_add(statMap, 'wins0', ArenaHUD.redWins);
    ds_map_add(statMap, 'wins1', ArenaHUD.blueWins);
}

var teamCount, teamStats;

// Iterate over players, adding entries for each
var playerNum;
playerNum = 0;
with (Player) {
    ds_map_add(statMap, 'player' + string(playerNum) + '_team', team);
    ds_map_add(statMap, 'player' + string(playerNum) + '_class', class);
    ds_map_add(statMap, 'player' + string(playerNum) + '_name', name);
    ds_map_add(statMap, 'player' + string(playerNum) + '_queueJump', queueJump);

    // iterate over stats and build csv string
    var statsString, j;
    statsString = '';
    for (i = KILLS; i <= POINTS; i += 1) {
        if (i != 0) {
            statsString += ',';
        }
        statsString += string(stats[i]);
    }
    ds_map_add(statMap, 'player' + string(playerNum) + '_stats', statsString);
    
    playerNum += 1;
}

var key, str;
str = '';
// Iterate over headers map
for (key = ds_map_find_first(statMap); is_string(key); key = ds_map_find_next(statMap, key))
{
    var sanitised;
    sanitised = string(ds_map_find_value(statMap, key));
    string_replace_all(sanitised, "&", "%26");
    string_replace_all(sanitised, "=", "%3D");
    if (key != ds_map_find_first(statMap))
        str += "&";
    str += key + "=" + sanitised;
}
show_message(str);
show_message("length: " + string(string_length(str)));

ds_map_destroy(statMap);
