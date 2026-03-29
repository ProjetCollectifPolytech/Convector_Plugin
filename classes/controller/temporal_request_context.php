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
 * Request context DTO for the Convector page.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector\controller;

use context_module;
use moodle_url;
use stdClass;

/**
 * Carries the records needed by the controller and presenter.
 */
class temporal_request_context {
    /** @var stdClass */
    public stdClass $cm;

    /** @var stdClass */
    public stdClass $course;

    /** @var stdClass */
    public stdClass $offlinequiz;

    /** @var context_module */
    public context_module $context;

    /** @var moodle_url */
    public moodle_url $url;

    /**
     * Constructor.
     *
     * @param stdClass $cm Course module record
     * @param stdClass $course Course record
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @param context_module $context Module context
     * @param moodle_url $url Page URL
     */
    public function __construct(
        stdClass $cm,
        stdClass $course,
        stdClass $offlinequiz,
        context_module $context,
        moodle_url $url
    ) {
        $this->cm = $cm;
        $this->course = $course;
        $this->offlinequiz = $offlinequiz;
        $this->context = $context;
        $this->url = $url;
    }
}
