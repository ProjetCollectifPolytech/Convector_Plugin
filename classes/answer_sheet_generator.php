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
 * Answer Sheet Generator class for creating answer sheet PDFs.
 *
 * @package    local_offlinequizaddons
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_offlinequizaddons;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/pdflib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/pdflib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');

/**
 * Generator for creating answer sheet PDFs using offlinequiz format.
 */
class answer_sheet_generator extends base_generator {

    /**
     * Generate answer sheet using offlinequiz format.
     *
     * @param int $groupid The group ID
     * @param array $data Group data
     * @return string|false Path to the PDF file or false on failure
     */
    public function generate($groupid, $data) {
        global $DB, $CFG, $USER;

        try {
            // Get the group
            $group = $DB->get_record('offlinequiz_groups', ['id' => $groupid]);
            if (!$group) {
                return false;
            }
            
            // Get course and context
            $course = $DB->get_record('course', ['id' => $this->offlinequiz->course]);
            $cm = get_coursemodule_from_instance('offlinequiz', $this->offlinequiz->id, $course->id);
            $context = \context_module::instance($cm->id);
            
            // Get the template usage
            $templateusage = offlinequiz_get_group_template_usage($this->offlinequiz, $group, $context);
            if (!$templateusage) {
                return false;
            }
            
            // Get max answers
            $maxanswers = offlinequiz_get_maxanswers($this->offlinequiz, array($group));
            
            // Use the original offlinequiz function to generate the answer PDF
            // But save it to our temp directory instead
            $answerfile = offlinequiz_create_pdf_answer(
                $maxanswers,
                $templateusage,
                $this->offlinequiz,
                $group,
                $course->id,
                $context
            );
            
            if (!$answerfile) {
                return false;
            }
            
            // Copy the file from Moodle file storage to our temp directory
            $letterstr = ' abcdefghijklmnopqrstuvwxyz';
            $groupletter = strtoupper($letterstr[$group->groupnumber]);
            
            $date = usergetdate(time());
            $timestamp = sprintf('%04d%02d%02d_%02d%02d%02d',
                    $date['year'], $date['mon'], $date['mday'], $date['hours'], $date['minutes'], $date['seconds']);
            
            $filename = get_string('fileprefixanswer', 'offlinequiz') . '_' . $groupletter . '_' . $timestamp . '.pdf';
            $filepath = $this->tempdir . DIRECTORY_SEPARATOR . $filename;
            
            // Copy file content
            $content = $answerfile->get_content();
            if (file_put_contents($filepath, $content) === false) {
                return false;
            }
            
            // Delete the temp file from Moodle storage
            $answerfile->delete();
            
            return $filepath;
            
        } catch (\Exception $e) {
            return false;
        }
    }
}
