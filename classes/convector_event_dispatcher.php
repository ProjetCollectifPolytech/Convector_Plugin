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
 * Event dispatcher for Convector activity interactions.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector;

use local_convector\controller\temporal_request_context;
use local_convector\event\archive_generated;
use local_convector\event\page_viewed;

/**
 * Creates consistent module-level events for Convector.
 */
class convector_event_dispatcher {
    /**
     * Trigger the page-view event for one request.
     *
     * @param temporal_request_context $requestcontext Page request context
     * @param array<string, mixed> $pagedata Prepared page data
     * @param bool $cangenerate Whether the current user can generate files
     * @return void
     */
    public function trigger_page_viewed(
        temporal_request_context $requestcontext,
        array $pagedata,
        bool $cangenerate
    ): void {
        $analysis = $pagedata['analysis'] ?? null;

        page_viewed::create([
            'objectid' => (int) $requestcontext->cm->id,
            'courseid' => (int) $requestcontext->course->id,
            'context' => $requestcontext->context,
            'other' => [
                'offlinequizid' => (int) $requestcontext->offlinequiz->id,
                'valid' => !empty($pagedata['valid']) ? 1 : 0,
                'can_generate' => $cangenerate ? 1 : 0,
                'needs_normalization' => !empty($analysis['needsnormalization']) ? 1 : 0,
                'group_count' => is_array($analysis['groups'] ?? null) ? count($analysis['groups']) : 0,
            ],
        ])->trigger();
    }

    /**
     * Trigger the archive-generation event for one successful download.
     *
     * @param temporal_request_context $requestcontext Page request context
     * @param array<string, mixed> $result Successful generation result
     * @return void
     */
    public function trigger_archive_generated(temporal_request_context $requestcontext, array $result): void {
        $analysis = $result['analysis'] ?? [];

        archive_generated::create([
            'objectid' => (int) $requestcontext->cm->id,
            'courseid' => (int) $requestcontext->course->id,
            'context' => $requestcontext->context,
            'other' => [
                'offlinequizid' => (int) $requestcontext->offlinequiz->id,
                'downloadname' => (string) ($result['downloadname'] ?? ''),
                'group_count' => is_array($analysis['groups'] ?? null) ? count($analysis['groups']) : 0,
                'final_pages' => isset($analysis['maxpages']) ? (int) $analysis['maxpages'] + 2 : 0,
            ],
        ])->trigger();
    }
}
