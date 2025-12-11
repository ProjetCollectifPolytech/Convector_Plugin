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
 * Main view page for local_offlinequizaddons plugin.
 *
 * This page displays the main content of the OfflineQuiz addons tab.
 *
 * @package    local_offlinequizaddons
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/offlinequiz/lib.php');

// Get the course module ID from the URL parameter
$cmid = required_param('id', PARAM_INT);

// Get the course module and verify it's an OfflineQuiz
$cm = get_coursemodule_from_id('offlinequiz', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$offlinequiz = $DB->get_record('offlinequiz', ['id' => $cm->instance], '*', MUST_EXIST);

// Require login and get context
require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Check capability
require_capability('local/offlinequizaddons:view', $context);

// Set up the page
$PAGE->set_url('/local/offlinequizaddons/view.php', ['id' => $cm->id]);
$PAGE->set_context($context);
$PAGE->set_title(format_string($offlinequiz->name) . ' - ' . get_string('tabname', 'local_offlinequizaddons'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout('incourse');

// Add breadcrumb
$PAGE->navbar->add(get_string('tabname', 'local_offlinequizaddons'));

// Get the renderer
$output = $PAGE->get_renderer('local_offlinequizaddons');

// Create the renderable page
$page = new \local_offlinequizaddons\output\mainpage($cm->id, $offlinequiz);

// Output the page
echo $OUTPUT->header();
echo $output->render($page);
echo $OUTPUT->footer();
