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
 * Main page renderable class for local_offlinequizaddons.
 *
 * @package    local_offlinequizaddons
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_offlinequizaddons\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use stdClass;

/**
 * Main page renderable class.
 *
 * This class prepares data for the main page template.
 *
 * @package    local_offlinequizaddons
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mainpage implements renderable, templatable {

    /** @var int The course module ID */
    protected $cmid;

    /** @var object The offlinequiz instance */
    protected $offlinequiz;

    /**
     * Constructor.
     *
     * @param int $cmid The course module ID
     * @param object $offlinequiz The offlinequiz instance (optional)
     */
    public function __construct($cmid, $offlinequiz = null) {
        $this->cmid = $cmid;
        $this->offlinequiz = $offlinequiz;
    }

    /**
     * Export data for use in the template.
     *
     * @param renderer_base $output The renderer
     * @return stdClass Data for the template
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $data->cmid = $this->cmid;
        
        // Additional data can be added here as the plugin grows
        if ($this->offlinequiz) {
            $data->offlinequizname = format_string($this->offlinequiz->name);
        }

        return $data;
    }
}
