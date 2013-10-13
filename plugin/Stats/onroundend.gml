// ***
// GG2 Stats reporting plugin v1 
// http://stats.ajf.me/
// Part 2/2: Plugins\Stats\onroundend.gml
// Copyright © 2013 Andrea Faulds
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
// 
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.
// ***

// This file is the meat of this plugin. It does the actual stat collection and reporting.
// It is called from stats.gml

var statMap;
statMap = ds_map_create();

ds_map_add(statMap, 'version', VERSION);
ds_map_add(statMap, 'serverName', global.serverName);
ds_map_add(statMap, 'serverPort', global.hostingPort);
ds_map_add(statMap, 'map', global.currentMap);
ds_map_add(statMap, 'winners', global.winners);

if (instance_exists(IntelligenceBaseBlue) || instance_exists(IntelligenceBaseRed)
    || instance_exists(IntelligenceRed) || instance_exists(IntelligenceBlue)) {
    ds_map_add(statMap, 'gameMode', 'ctf');
    ds_map_add(statMap, 'caps0', global.redCaps);
    ds_map_add(statMap, 'caps1', global.blueCaps);
    ds_map_add(statMap, 'capLimit', global.caplimit);
} else if (instance_exists(GeneratorBlue) || instance_exists(GeneratorRed)) {
    ds_map_add(statMap, 'gameMode', 'gen');
} else if (instance_exists(ArenaControlPoint)) {
    ds_map_add(statMap, 'gameMode', 'arena');
    ds_map_add(statMap, 'wins0', ArenaHUD.redWins);
    ds_map_add(statMap, 'wins1', ArenaHUD.blueWins);
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

if (ds_map_find_value(statMap, 'gameMode') == 'ctf'
    or ds_map_find_value(statMap, 'gameMode') == 'cp'
    or ds_map_find_value(statMap, 'gameMode') == 'arena'
    or ds_map_find_value(statMap, 'gameMode') == 'gen'){
    ds_map_add(statMap, 'timer', HUD.timer);
    ds_map_add(statMap, 'timeLimit', HUD.timeLimit);
}

if (ds_map_find_value(statMap, 'gameMode') != 'arena') {
    ds_map_add(statMap, 'respawnTime', global.Server_Respawntime);
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
ds_map_add(statMap, 'players', playerNum);

var key, queryString;
queryString = '';
// Iterate over stats map and build query string
for (key = ds_map_find_first(statMap); is_string(key); key = ds_map_find_next(statMap, key))
{
    var sanitised;
    sanitised = string(ds_map_find_value(statMap, key));
    sanitised = string_replace_all(sanitised, "&", "%26");
    sanitised = string_replace_all(sanitised, "=", "%3D");
    sanitised = string_replace_all(sanitised, " ", "%20");
    sanitised = string_replace_all(sanitised, " ", "%20");
    sanitised = string_replace_all(sanitised, chr(10), "%0A");
    sanitised = string_replace_all(sanitised, chr(13), "%0D");
    if (key != ds_map_find_first(statMap))
        queryString += "&";
    queryString += key + "=" + sanitised;
}
ds_map_destroy(statMap);

// Create handler - a *persistent* instance that'll finish sending the request for us
var handler;
handler = instance_create(0, 0, global.StatsReporterRequestHandler);
with (handler)
    handle = httpGet(global.StatsReporterEndpoint + "?action=submit&" + queryString, -1);
