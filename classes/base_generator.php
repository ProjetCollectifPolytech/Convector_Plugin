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
 * Base generator for PDF artifacts.
 *
 * @package    local_offlinequizaddons
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_offlinequizaddons;

use local_offlinequizaddons\service\offlinequiz_group_context_loader;
use stdClass;

/**
 * Base generator class with shared state for PDF generation.
 */
abstract class base_generator {
    /** @var stdClass */
    protected stdClass $offlinequiz;

    /** @var string */
    protected string $tempdir;

    /** @var offlinequiz_group_context_loader */
    private offlinequiz_group_context_loader $groupcontextloader;

    /**
     * Constructor.
     *
     * @param stdClass $offlinequiz OfflineQuiz activity
     * @param string $tempdir Temporary directory
     */
    public function __construct(
        stdClass $offlinequiz,
        string $tempdir,
        ?offlinequiz_group_context_loader $groupcontextloader = null
    ) {
        $this->offlinequiz = $offlinequiz;
        $this->tempdir = $tempdir;
        $this->groupcontextloader = $groupcontextloader ?? new offlinequiz_group_context_loader();
    }

    /**
     * Load the Moodle records needed to generate one group artifact.
     *
     * @param int $groupid OfflineQuiz group id
     * @return array<string, mixed>|null
     */
    protected function load_group_context(int $groupid): ?array {
        return $this->groupcontextloader->load($this->offlinequiz, $groupid);
    }

    /**
     * Run one generation step with question shuffling disabled.
     *
     * @param callable $callback Callback executed while shuffle is disabled
     * @return mixed
     */
    protected function with_disabled_shuffle(callable $callback) {
        $originalshuffle = (int) ($this->offlinequiz->shufflequestions ?? 0);
        $this->offlinequiz->shufflequestions = 0;

        try {
            return $callback();
        } finally {
            $this->offlinequiz->shufflequestions = $originalshuffle;
        }
    }

    /**
     * Build a timestamped output path inside the request temp directory.
     *
     * @param string $prefix Base filename prefix
     * @param string $groupletter Group letter
     * @param string $extension File extension
     * @return string
     */
    protected function create_timestamped_file_path(
        string $prefix,
        string $groupletter,
        string $extension = 'pdf'
    ): string {
        $date = usergetdate(time());
        $timestamp = sprintf(
            '%04d%02d%02d_%02d%02d%02d',
            $date['year'],
            $date['mon'],
            $date['mday'],
            $date['hours'],
            $date['minutes'],
            $date['seconds']
        );

        $filename = clean_filename($prefix . '_' . $groupletter . '_' . $timestamp . '.' . $extension);
        return rtrim($this->tempdir, '\\/') . DIRECTORY_SEPARATOR . $filename;
    }
}
