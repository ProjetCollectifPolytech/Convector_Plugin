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
 * Event fired when a normalized Convector archive is generated.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector\event;

use core\event\base;
use moodle_url;

/**
 * Convector archive-generation event.
 */
class archive_generated extends base {
    /**
     * Initialise event metadata.
     *
     * @return void
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'course_modules';
    }

    /**
     * Event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('event_archive_generated', 'local_convector');
    }

    /**
     * Event description.
     *
     * @return string
     */
    public function get_description(): string {
        return get_string('event_archive_generated_desc', 'local_convector', (object) [
            'userid' => $this->userid,
            'cmid' => $this->objectid,
            'courseid' => $this->courseid,
            'offlinequizid' => $this->other['offlinequizid'] ?? 0,
            'downloadname' => $this->other['downloadname'] ?? '',
            'groupcount' => $this->other['group_count'] ?? 0,
            'finalpages' => $this->other['final_pages'] ?? 0,
        ]);
    }

    /**
     * Event URL.
     *
     * @return moodle_url
     */
    public function get_url(): moodle_url {
        return new moodle_url('/local/convector/temporal.php', ['id' => $this->objectid]);
    }
}
