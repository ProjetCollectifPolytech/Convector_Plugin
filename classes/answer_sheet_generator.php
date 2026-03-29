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
 * Generator for answer-sheet PDFs.
 *
 * @package    local_convector
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_convector;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/pdflib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/pdflib.php');
require_once($CFG->dirroot . '/mod/offlinequiz/locallib.php');

/**
 * Delegates answer-sheet generation to the native OfflineQuiz helper.
 */
class answer_sheet_generator extends base_generator {
    /**
     * Generate one answer sheet PDF.
     *
     * @param int $groupid OfflineQuiz group id
     * @param array $data Group normalization data
     * @return string|false
     */
    public function generate($groupid, $data) {
        $groupcontext = $this->load_group_context((int) $groupid);
        if ($groupcontext === null) {
            return false;
        }

        $answerfile = null;

        try {
            $maxanswers = offlinequiz_get_maxanswers($this->offlinequiz, [$groupcontext['group']]);
            $answerfile = offlinequiz_create_pdf_answer(
                $maxanswers,
                $groupcontext['templateusage'],
                $this->offlinequiz,
                $groupcontext['group'],
                $groupcontext['course']->id,
                $groupcontext['context']
            );
            if (!$answerfile) {
                return false;
            }

            $filepath = $this->create_timestamped_file_path(
                get_string('fileprefixanswer', 'offlinequiz'),
                $groupcontext['groupletter']
            );

            return file_put_contents($filepath, $answerfile->get_content()) === false ? false : $filepath;
        } catch (\Throwable $e) {
            debugging(
                'Convector failed to generate answer sheet for group ' .
                $groupcontext['group']->id . ': ' . $e->getMessage(),
                DEBUG_DEVELOPER
            );
            return false;
        } finally {
            if (is_object($answerfile) && method_exists($answerfile, 'delete')) {
                $answerfile->delete();
            }
        }
    }
}
