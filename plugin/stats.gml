// Stats reporting plugin v1
// Part 1/2: Plugins\stats.gml
// Copyright © 2013 Andrea Faulds

// This file doesn't do much by itself - it just adds a check for endround which calls the other file

// Run only when hosting

global.StatsReporter = object_add();
object_event_add(global.StatsReporter, ev_create, 0, '
    seen = false;
');
object_event_add(global.StatsReporter, ev_step, ev_step_begin, '
    // It begins!
    if(global.isHost and global.winners != -1 and !seen) {
        seen = true;
        execute_file(working_directory + "\Plugins\Stats\onroundend.gml");
    }
');

object_event_add(PlayerControl, ev_step, ev_step_begin, '
    if (!instance_exists(global.StatsReporter))
        instance_create(0, 0, global.StatsReporter);
');
