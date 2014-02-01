// ***
// GG2 Stats reporting plugin v1.2.1
// http://stats.ajf.me/
// Part 1/2: Plugins\stats.gml
// Copyright © 2013-2014 Andrea Faulds
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

// This file doesn't do much by itself - it just adds a check for endround which calls the other file
// It also prepares the HTTP request-handling object

// Run only when hosting

global.StatsReporterEndpoint = "http://stats.ajf.me/";

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
    // End of request
    if (http_step(handle)) {
        // Request failed
        if (http_status_code(handle) != 200) {
            // Give up
            http_destroy(handle);
            instance_destroy();
            exit;
        }
    
        var data;
        data = http_response_body(handle);
        data = read_string(data, buffer_size(data));
        http_destroy(handle);
    
        if (data != "SUCCESS") {
            // Failure, give up
        }
        instance_destroy();
    }
');
