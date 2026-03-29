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
 * OfflineQuiz activity loading service.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector\service;

use local_convector\controller\temporal_request_context;

/**
 * Loads the records needed by the Convector page.
 */
class offlinequiz_activity_service {
    /**
     * Load the course, module, activity and context from a module id.
     *
     * @param int $cmid Course module id
     * @return temporal_request_context
     */
    public function load_request_context(int $cmid): temporal_request_context {
        global $DB;

        $cm = get_coursemodule_from_id('offlinequiz', $cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $offlinequiz = $DB->get_record('offlinequiz', ['id' => $cm->instance], '*', MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $url = new \moodle_url('/local/convector/temporal.php', ['id' => $cm->id]);

        return new temporal_request_context($cm, $course, $offlinequiz, $context, $url);
    }
}
