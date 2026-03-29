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
 * Library functions for local_offlinequizaddons.
 *
 * @package    local_offlinequizaddons
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_offlinequizaddons\integration\activity_navigation_integration;

/**
 * Return the shared activity navigation integration instance.
 *
 * @return activity_navigation_integration
 */
function local_offlinequizaddons_get_activity_navigation_integration(): activity_navigation_integration {
    static $integration = null;

    if ($integration === null) {
        $integration = new activity_navigation_integration();
    }

    return $integration;
}

/**
 * Resolve activity access metadata for supported OfflineQuiz pages.
 *
 * @param \stdClass|\cm_info $cm Course module
 * @param context_course $coursecontext Course context
 * @return array|null
 */
function local_offlinequizaddons_get_activity_access_data($cm, context_course $coursecontext): ?array {
    return local_offlinequizaddons_get_activity_navigation_integration()->get_activity_access_data($cm, $coursecontext);
}

/**
 * Determine whether the current page should display a prominent activity button.
 *
 * @return bool
 */
function local_offlinequizaddons_should_add_activity_button(): bool {
    global $PAGE;

    return local_offlinequizaddons_get_activity_navigation_integration()->should_add_activity_button($PAGE);
}

/**
 * Add a visible page button for supported activity pages.
 *
 * @param array $accessdata Access metadata
 * @return void
 */
function local_offlinequizaddons_add_activity_button(array $accessdata): void {
    global $PAGE;

    local_offlinequizaddons_get_activity_navigation_integration()->add_activity_button($PAGE, $accessdata);
}

/**
 * Extend activity settings navigation with the Temporal Convector link.
 *
 * @param settings_navigation $settingsnav Settings navigation
 * @param context $context Current context
 * @return void
 */
function local_offlinequizaddons_extend_settings_navigation($settingsnav, $context): void {
    global $PAGE;

    local_offlinequizaddons_get_activity_navigation_integration()->extend_settings_navigation($settingsnav, $PAGE);
}

/**
 * Extend secondary navigation on supported activity pages.
 *
 * @param navigation_node $navigation Navigation node
 * @param \stdClass $course Course record
 * @param \cm_info $cm Course module info
 * @return void
 */
function local_offlinequizaddons_extend_navigation_module($navigation, $course, $cm): void {
    local_offlinequizaddons_get_activity_navigation_integration()->extend_navigation_module($navigation, $course, $cm);
}

/**
 * Backward-compatible wrapper for older custom integrations.
 *
 * @param navigation_node $navigation Navigation node
 * @return void
 */
function local_offlinequizaddons_extend_navigation($navigation): void {
    global $PAGE;

    if (empty($PAGE->cm) || empty($PAGE->course)) {
        return;
    }

    local_offlinequizaddons_extend_navigation_module($navigation, $PAGE->course, $PAGE->cm);
}
