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
 * Access resolver for OfflineQuiz activity links.
 *
 * @package    local_offlinequizaddons
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_offlinequizaddons\integration;

use context_course;
use navigation_node;
use pix_icon;

/**
 * Resolves whether an activity should expose the Temporal Convector entry.
 */
class activity_access_resolver {
    /**
     * Resolve access metadata for one activity.
     *
     * @param \stdClass|\cm_info $cm Course module
     * @param context_course $coursecontext Course context
     * @return array|null
     */
    public function resolve($cm, context_course $coursecontext): ?array {
        if (($cm->modname ?? '') !== 'offlinequiz') {
            return null;
        }

        $modulecontext = \context_module::instance($cm->id);
        if (!has_capability('local/offlinequizaddons:view', $modulecontext)) {
            return null;
        }

        return [
            'url' => new \moodle_url('/local/offlinequizaddons/temporal.php', ['id' => $cm->id]),
            'label' => get_string('temporal_convector', 'local_offlinequizaddons'),
            'nodekey' => 'offlinequizaddons_temporal',
            'icon' => new pix_icon('i/report', ''),
            'nodetype' => navigation_node::TYPE_CUSTOM,
        ];
    }
}
