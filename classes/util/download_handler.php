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
 * Download helper for generated archives.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector\util;

/**
 * Sends generated files through Moodle's temp-file helper.
 */
class download_handler {
    /**
     * Send a generated file and terminate the request.
     *
     * @param string $filepath File path
     * @param string $downloadname Download file name
     * @return void
     */
    public static function send_file(string $filepath, string $downloadname): void {
        send_temp_file($filepath, clean_filename($downloadname));
    }
}
