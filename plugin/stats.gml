// Stats reporting plugin v1
// Part 1/2: Plugins\stats.gml
// Copyright © 2013 Andrea Faulds

// This file doesn't do much by itself - it just adds a check for endround which calls the other file
// It also prepares the HTTP request-handling object

// Run only when hosting

global.StatsReporterEndpoint = "http://localhost:8000/";

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

global.StatsReporterRequestHandler = object_add();
object_set_persistent(global.StatsReporterRequestHandler, 1);
object_event_add(global.StatsReporterRequestHandler, ev_create, 0, '
    handle = -1;
');
object_event_add(global.StatsReporterRequestHandler, ev_step, ev_step_begin, '
    httpRequestStep(handle);
    
    var status;
    status = httpRequestStatus(handle);
    
    // Request failed
    if (status == 2)
    {
        // Give up
        httpRequestDestroy(handle);
        instance_destroy();
        exit;
    }
    // Request finished
    else if (status == 1)
    {    
        var statusCode;
        statusCode = httpRequestStatusCode(handle);
    
        // Request failed
        if (statusCode != 200)
        {
            // Give up
            httpRequestDestroy(handle);
            instance_destroy();
            exit;
        }
    
        var data;
        data = httpRequestResponseBody(handle);
        data = read_string(data, buffer_size(data));
        httpRequestDestroy(handle);
    
        if (data != "SUCCESS") {
            // Failure, give up
        }
        instance_destroy();
    }
');
