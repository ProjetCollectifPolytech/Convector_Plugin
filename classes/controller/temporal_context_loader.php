<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Context loader for the Temporal Convector page.
 *
 * @package    local_offlinequizaddons
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_offlinequizaddons\controller;

use local_offlinequizaddons\service\offlinequiz_activity_service;

/**
 * Loads Moodle state and prepares page globals.
 */
class temporal_context_loader {
    /** @var offlinequiz_activity_service */
    private offlinequiz_activity_service $activityservice;

    /**
     * Constructor.
     *
     * @param offlinequiz_activity_service|null $activityservice
     */
    public function __construct(?offlinequiz_activity_service $activityservice = null) {
        $this->activityservice = $activityservice ?? new offlinequiz_activity_service();
    }

    /**
     * Load the request context and prepare the page.
     *
     * @param int $cmid Course module id
     * @return temporal_request_context
     */
    public function load(int $cmid): temporal_request_context {
        global $PAGE;

        $requestcontext = $this->activityservice->load_request_context($cmid);

        require_login($requestcontext->course, true, $requestcontext->cm);
        require_capability('local/offlinequizaddons:view', $requestcontext->context);

        $PAGE->set_url($requestcontext->url);
        $PAGE->set_context($requestcontext->context);
        $PAGE->set_title(get_string('temporal_convector', 'local_offlinequizaddons'));
        $PAGE->set_heading(format_string($requestcontext->course->fullname));
        $PAGE->set_pagelayout('incourse');

        return $requestcontext;
    }
}
