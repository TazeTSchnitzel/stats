CREATE TABLE IF NOT EXISTS game (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    version         INTEGER NOT NULL,
    serverName      STRING NOT NULL,
    serverIP        STRING NOT NULL,
    serverPort      INTEGER NOT NULL,
    map             STRING NOT NULL,
    winner          INTEGER NOT NULL,
    gameMode        STRING NOT NULL,
    timer           INTEGER,
    timeLimit       INTEGER,
    respawnTime     INTEGER,
    controlPoints   INTEGER,
    setupGate       BOOLEAN,
    capsRed         INTEGER,
    capsBlue        INTEGER,
    capLimit        INTEGER,
    winsRed         INTEGER,
    winsBlue        INTEGER,
    CONSTRAINT game_winner_teamTypes_id FOREIGN KEY (winner) REFERENCES teamTypes(id)
);

CREATE TABLE IF NOT EXISTS player (
    gameId          INTEGER NOT NULL,
    id              INTEGER NOT NULL,
    name            STRING NOT NULL,
    team            INTEGER NOT NULL,
    class           INTEGER NOT NULL,
    queueJump       BOOLEAN NOT NULL,
    PRIMARY KEY(id, gameId),
    CONSTRAINT player_gameId_game_id FOREIGN KEY (gameId) REFERENCES game(id),
    CONSTRAINT player_team_teamTypes_id FOREIGN KEY (team) REFERENCES teamTypes(id),
    CONSTRAINT player_class_classTypes_id FOREIGN KEY (class) REFERENCES classTypes(id)
);

CREATE TABLE IF NOT EXISTS stat (
    gameId          INTEGER NOT NULL,
    playerId        INTEGER NOT NULL,
    type            INTEGER NOT NULL,
    value           INTEGER NOT NULL,
    PRIMARY KEY(gameId, playerId, type),
    CONSTRAINT stat_gameId_playerId_player_gameId_playerId FOREIGN KEY (gameId, playerId) REFERENCES player(gameId, id),
    CONSTRAINT stat_playerId_player_id FOREIGN KEY (playerId) REFERENCES player(id),
    CONSTRAINT stat_type_statTypes_id FOREIGN KEY (type) REFERENCES statTypes(id)
);

CREATE TABLE IF NOT EXISTS teamTypes (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    name            STRING NOT NULL
);

INSERT OR IGNORE INTO teamTypes(id, name) VALUES (0, 'Red');
INSERT OR IGNORE INTO teamTypes(id, name) VALUES (1, 'Blue');
INSERT OR IGNORE INTO teamTypes(id, name) VALUES (2, 'Spectator');

CREATE TABLE IF NOT EXISTS classTypes (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    name            STRING NOT NULL
);

INSERT OR IGNORE INTO classTypes(id, name) VALUES (0, 'Runner');
INSERT OR IGNORE INTO classTypes(id, name) VALUES (1, 'Rocketman');
INSERT OR IGNORE INTO classTypes(id, name) VALUES (2, 'Rifleman');
INSERT OR IGNORE INTO classTypes(id, name) VALUES (3, 'Detonator');
INSERT OR IGNORE INTO classTypes(id, name) VALUES (4, 'Healer');
INSERT OR IGNORE INTO classTypes(id, name) VALUES (5, 'Constructor');
INSERT OR IGNORE INTO classTypes(id, name) VALUES (6, 'Overweight');
INSERT OR IGNORE INTO classTypes(id, name) VALUES (7, 'Infiltrator');
INSERT OR IGNORE INTO classTypes(id, name) VALUES (8, 'Firebug');
INSERT OR IGNORE INTO classTypes(id, name) VALUES (9, 'Querly');

CREATE TABLE IF NOT EXISTS statTypes (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    name            STRING NOT NULL
);

INSERT OR IGNORE INTO statTypes(id, name) VALUES (0, 'Kills');
INSERT OR IGNORE INTO statTypes(id, name) VALUES (1, 'Deaths');
INSERT OR IGNORE INTO statTypes(id, name) VALUES (2, 'Caps');
INSERT OR IGNORE INTO statTypes(id, name) VALUES (3, 'Assists');
INSERT OR IGNORE INTO statTypes(id, name) VALUES (4, 'Destruction');
INSERT OR IGNORE INTO statTypes(id, name) VALUES (5, 'Stabs');
INSERT OR IGNORE INTO statTypes(id, name) VALUES (6, 'Healing');
INSERT OR IGNORE INTO statTypes(id, name) VALUES (7, 'Defenses');
INSERT OR IGNORE INTO statTypes(id, name) VALUES (8, 'Invulns');
INSERT OR IGNORE INTO statTypes(id, name) VALUES (9, 'Bonus');
INSERT OR IGNORE INTO statTypes(id, name) VALUES (10, 'Dominations');
INSERT OR IGNORE INTO statTypes(id, name) VALUES (11, 'Revenge');
INSERT OR IGNORE INTO statTypes(id, name) VALUES (12, 'Points');
