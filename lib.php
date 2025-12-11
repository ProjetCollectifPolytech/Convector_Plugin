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
 * Library functions for local_offlinequizaddons plugin.
 *
 * @package    local_offlinequizaddons
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend the navigation for OfflineQuiz activities.
 *
 * This function adds a new tab to the OfflineQuiz module navigation.
 * It is called automatically by Moodle when viewing an OfflineQuiz activity.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course object
 * @param stdClass $context The context object
 * @return void
 */
function local_offlinequizaddons_extend_navigation($navigation, $course, $context) {
    global $PAGE;

    // Only extend navigation for OfflineQuiz module contexts
    if ($context->contextlevel != CONTEXT_MODULE) {
        return;
    }

    // Get the course module
    $cm = get_coursemodule_from_id('offlinequiz', $context->instanceid, 0, false, MUST_EXIST);
    if (!$cm || $cm->modname !== 'offlinequiz') {
        return;
    }

    // Check if user has capability to view the addons
    if (!has_capability('local/offlinequizaddons:view', $context)) {
        return;
    }

    // Add the new tab to the navigation
    $url = new moodle_url('/local/offlinequizaddons/view.php', ['id' => $cm->id]);
    $node = navigation_node::create(
        get_string('tabname', 'local_offlinequizaddons'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'offlinequizaddons',
        new pix_icon('i/settings', '')
    );

    if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
        $node->make_active();
    }

    $navigation->add_node($node);
}

/**
 * Extend the navigation in the settings block for OfflineQuiz.
 *
 * This is an alternative method that adds the tab to the settings block.
 *
 * @param settings_navigation $settingsnav The settings navigation object
 * @param context $context The context object
 * @return void
 */
function local_offlinequizaddons_extend_settings_navigation($settingsnav, $context) {
    global $PAGE;

    // Only extend for OfflineQuiz module contexts
    if ($context->contextlevel != CONTEXT_MODULE) {
        return;
    }

    // Verify this is an OfflineQuiz module
    $cm = get_coursemodule_from_id('offlinequiz', $context->instanceid, 0, false, IGNORE_MISSING);
    if (!$cm || $cm->modname !== 'offlinequiz') {
        return;
    }

    // Check capability
    if (!has_capability('local/offlinequizaddons:view', $context)) {
        return;
    }

    // Find the module settings node
    $modulenode = $settingsnav->find('modulesettings', navigation_node::TYPE_SETTING);
    if ($modulenode) {
        $url = new moodle_url('/local/offlinequizaddons/view.php', ['id' => $cm->id]);
        $node = navigation_node::create(
            get_string('tabname', 'local_offlinequizaddons'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'offlinequizaddons',
            new pix_icon('i/settings', '')
        );
        $modulenode->add_node($node);
    }
}
