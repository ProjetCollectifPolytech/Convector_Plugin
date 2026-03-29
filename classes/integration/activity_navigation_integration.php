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
 * Navigation integration for the Convector plugin.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector\integration;

use cm_info;
use context_course;
use html_writer;
use moodle_page;
use navigation_node;
use settings_navigation;
use stdClass;

/**
 * Keeps OfflineQuiz navigation glue out of lib.php.
 */
class activity_navigation_integration {
    /** @var activity_access_resolver */
    private activity_access_resolver $accessresolver;

    /**
     * Constructor.
     *
     * @param activity_access_resolver|null $accessresolver
     */
    public function __construct(?activity_access_resolver $accessresolver = null) {
        $this->accessresolver = $accessresolver ?? new activity_access_resolver();
    }

    /**
     * Resolve access metadata for one activity.
     *
     * @param stdClass|cm_info $cm Course module
     * @param context_course $coursecontext Course context
     * @return array|null
     */
    public function get_activity_access_data($cm, context_course $coursecontext): ?array {
        return $this->accessresolver->resolve($cm, $coursecontext);
    }

    /**
     * Determine whether the current page should display an activity button.
     *
     * @param moodle_page $page Moodle page
     * @return bool
     */
    public function should_add_activity_button(moodle_page $page): bool {
        if (empty($page->cm) || empty($page->activityname)) {
            return false;
        }

        if ((int) $page->context->contextlevel !== CONTEXT_MODULE) {
            return false;
        }

        return str_starts_with((string) $page->pagetype, 'mod-' . $page->activityname . '-');
    }

    /**
     * Add an activity button when it is not already present.
     *
     * @param moodle_page $page Moodle page
     * @param array $accessdata Access metadata
     * @return void
     */
    public function add_activity_button(moodle_page $page, array $accessdata): void {
        if (strpos((string) $page->button, 'local-convector-activity-button') !== false) {
            return;
        }

        $button = html_writer::link($accessdata['url'], $accessdata['label'], ['class' => 'btn btn-secondary']);
        $page->set_button(
            html_writer::div($button, 'singlebutton local-convector-activity-button') . (string) $page->button
        );
    }

    /**
     * Extend settings navigation for the current page.
     *
     * @param settings_navigation $settingsnav Settings navigation
     * @param moodle_page $page Moodle page
     * @return void
     */
    public function extend_settings_navigation(settings_navigation $settingsnav, moodle_page $page): void {
        $accessdata = $this->get_page_access_data($page);
        if ($accessdata === null) {
            return;
        }

        if ($this->should_add_activity_button($page)) {
            $this->add_activity_button($page, $accessdata);
        }

        $settingsnode = $settingsnav->find('modulesettings', navigation_node::TYPE_SETTING);
        if (
            $settingsnode instanceof navigation_node &&
            !$settingsnode->find($accessdata['nodekey'], navigation_node::TYPE_SETTING)
        ) {
            $settingsnode->add(
                $accessdata['label'],
                $accessdata['url'],
                navigation_node::TYPE_SETTING,
                null,
                $accessdata['nodekey'],
                $accessdata['icon']
            );
        }
    }

    /**
     * Extend secondary navigation on module pages.
     *
     * @param navigation_node $navigation Navigation node
     * @param stdClass $course Course object
     * @param cm_info $cm Course module
     * @return void
     */
    public function extend_navigation_module(navigation_node $navigation, stdClass $course, cm_info $cm): void {
        $coursecontext = context_course::instance($course->id);
        $accessdata = $this->get_activity_access_data($cm, $coursecontext);
        if ($accessdata === null || $navigation->find($accessdata['nodekey'], navigation_node::TYPE_CUSTOM)) {
            return;
        }

        $navigation->add(
            $accessdata['label'],
            $accessdata['url'],
            navigation_node::TYPE_CUSTOM,
            null,
            $accessdata['nodekey'],
            $accessdata['icon']
        );
    }

    /**
     * Resolve access data from the current page.
     *
     * @param moodle_page $page Moodle page
     * @return array|null
     */
    private function get_page_access_data(moodle_page $page): ?array {
        if ($page->cm === null || $page->course === null) {
            return null;
        }

        return $this->get_activity_access_data($page->cm, context_course::instance($page->course->id));
    }
}
