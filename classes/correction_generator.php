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
 * Correction Generator class for creating correction form PDFs.
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
 * Generator for creating correction form PDFs using offlinequiz format with normalization.
 */
class correction_generator extends base_generator {

    /**
     * Generate correction form using offlinequiz format with normalization.
     *
     * @param int $groupid The group ID
     * @param array $data Group data including blank pages needed
     * @return string|false Path to the PDF file or false on failure
     */
    public function generate($groupid, $data) {
        global $DB, $CFG;

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
            
            // FORCE NON-SHUFFLE MODE FOR CORRECT PAGE DISTRIBUTION
            $originalshufflesetting = $this->offlinequiz->shufflequestions;
            $this->offlinequiz->shufflequestions = 0;  // Force non-shuffle mode
            
            // Determine group letter
            $letterstr = 'abcdefghijklmnopqrstuvwxyz';
            $groupletter = strtoupper($letterstr[$group->groupnumber - 1]);
            
            // Get font
            $font = offlinequiz_get_pdffont($this->offlinequiz);
            
            // Create PDF
            $pdf = new \offlinequiz_question_pdf('P', 'mm', 'A4');
            $trans = new \offlinequiz_html_translator();
            
            // Set title
            $title = offlinequiz_str_html_pdf($this->offlinequiz->name);
            if (!empty($this->offlinequiz->time)) {
                $title .= ": " . offlinequiz_str_html_pdf(userdate($this->offlinequiz->time));
            }
            $title .= ", " . offlinequiz_str_html_pdf(get_string('group', 'offlinequiz') . " $groupletter");
            $pdf->set_title($title);
            
            $pdf->SetMargins(15, 28, 15);
            $pdf->SetAutoPageBreak(false, 25);
            $pdf->AddPage();
            
            // Print title page with "Correction" marker
            $pdf->SetFont($font, 'B', 14);
            $pdf->Ln(4);
            $pdf->Cell(0, 4, offlinequiz_str_html_pdf(get_string('correctionform', 'offlinequiz')), 0, 0, 'C');
            
            if ($this->offlinequiz->printstudycodefield) {
                $pdf->Rect(34, 42, 137, 50, 'D');
            } else {
                $pdf->Rect(34, 42, 137, 40, 'D');
            }
            
            $pdf->SetFont($font, '', 10);
            $pdf->Ln(14);
            $pdf->Cell(58, 10, offlinequiz_str_html_pdf(get_string('name')) . ":", 0, 0, 'R');
            $pdf->Rect(76, 54, 80, 0.3, 'F');
            $pdf->Ln(10);
            $pdf->Cell(58, 10, offlinequiz_str_html_pdf(get_string('idnumber', 'offlinequiz')) . ":", 0, 0, 'R');
            $pdf->Rect(76, 64, 80, 0.3, 'F');
            $pdf->Ln(10);
            if ($this->offlinequiz->printstudycodefield) {
                $pdf->Cell(58, 10, offlinequiz_str_html_pdf(get_string('studycode', 'offlinequiz')) . ":", 0, 0, 'R');
                $pdf->Rect(76, 74, 80, 0.3, 'F');
                $pdf->Ln(10);
            }
            $pdf->Cell(58, 10, offlinequiz_str_html_pdf(get_string('signature', 'offlinequiz')) . ":", 0, 0, 'R');
            if ($this->offlinequiz->printstudycodefield) {
                $pdf->Rect(76, 84, 80, 0.3, 'F');
            } else {
                $pdf->Rect(76, 74, 80, 0.3, 'F');
            }
            $pdf->Ln(25);
            $pdf->SetFont($font, '', $this->offlinequiz->fontsize);
            
            if (!empty($this->offlinequiz->pdfintro)) {
                $oldx = $pdf->GetX();
                $oldy = $pdf->GetY();
                
                $pdf->checkpoint();
                $pdf->writeHTMLCell(165, round($this->offlinequiz->fontsize / 2), $pdf->GetX(), $pdf->GetY(), 
                                   $this->offlinequiz->pdfintro);
                $pdf->Ln();
                
                if ($pdf->is_overflowing()) {
                    $pdf->backtrack();
                    $pdf->SetX($oldx);
                    $pdf->SetY($oldy);
                    $paragraphs = preg_split('/<p>/', $this->offlinequiz->pdfintro);
                    
                    foreach ($paragraphs as $paragraph) {
                        if (!empty($paragraph)) {
                            $sentences = preg_split('/<br\s*\/>/', $paragraph);
                            foreach ($sentences as $sentence) {
                                $pdf->checkpoint();
                                $pdf->writeHTMLCell(165, round($this->offlinequiz->fontsize / 2), 
                                                   $pdf->GetX(), $pdf->GetY(), $sentence . '<br/>');
                                $pdf->Ln();
                                if ($pdf->is_overflowing()) {
                                    $pdf->backtrack();
                                    $pdf->AddPage();
                                    $pdf->Ln(14);
                                    $pdf->writeHTMLCell(165, round($this->offlinequiz->fontsize / 2), 
                                                       $pdf->GetX(), $pdf->GetY(), $sentence);
                                    $pdf->Ln();
                                }
                            }
                        }
                    }
                }
            }
            
            $pdf->AddPage();
            $pdf->Ln(2);
            $pdf->SetMargins(15, 15, 15);
            
            // Get questions
            $sql = "SELECT q.*, c.contextid, ogq.page, ogq.slot, ogq.maxmark
                      FROM {offlinequiz_group_questions} ogq
                      JOIN {question} q ON q.id = ogq.questionid
                      JOIN {question_versions} qv ON qv.questionid = q.id
                      JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                      JOIN {question_categories} c ON c.id = qbe.questioncategoryid
                     WHERE ogq.offlinequizid = :offlinequizid
                       AND ogq.offlinegroupid = :offlinegroupid
                  ORDER BY ogq.slot ASC";
            $params = array('offlinequizid' => $this->offlinequiz->id, 'offlinegroupid' => $group->id);
            
            $questions = $DB->get_records_sql($sql, $params);
            if (!$questions) {
                return false;
            }
            
            if (!get_question_options($questions)) {
                return false;
            }
            
            $slots = $templateusage->get_slots();
            $texfilter = new \filter_tex\text_filter($context, []);
            $number = 1;
            
            // Print questions with correction markers (same as offlinequiz but with correction=true)
            if ($this->offlinequiz->shufflequestions) {
                foreach ($slots as $slot) {
                    $slotquestion = $templateusage->get_question($slot);
                    $currentquestionid = $slotquestion->id;
                    
                    if ($pdf->GetY() > 230) {
                        $pdf->AddPage();
                        $pdf->Ln(14);
                    }
                    
                    $question = $questions[$currentquestionid];
                    $html = offlinequiz_print_question_html($pdf, $question, $texfilter, $trans, $this->offlinequiz);
                    
                    if ($question->qtype == 'multichoice' || $question->qtype == 'multichoiceset') {
                        $html = $html . offlinequiz_get_answers_html($this->offlinequiz, $templateusage,
                            $slot, $question, $texfilter, $trans, true);  // Note: correction=true
                    }
                    
                    if ($this->offlinequiz->disableimgnewlines) {
                        $html = preg_replace("/(<span class=\"MathJax_Preview\">.+?)+(title=\"TeX\" >)/ms", "", $html);
                        $html = preg_replace("/<\/a><\/span>/ms", "", $html);
                        $html = preg_replace("/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/ms", "", $html);
                    }
                    
                    if ($question->qtype == 'multichoice' || $question->qtype == 'multichoiceset') {
                        $pdf->SetFont($font, 'B', $this->offlinequiz->fontsize);
                        $pdf->Cell(4, round($this->offlinequiz->fontsize / 2), "$number)  ", 0, 0, 'R');
                        $pdf->SetFont($font, '', $this->offlinequiz->fontsize);
                    }
                    
                    offlinequiz_write_question_to_pdf($pdf, $this->offlinequiz->fontsize, $question->qtype, $html, $number);
                    $number += $questions[$currentquestionid]->length;
                }
            } else {
                // Non-shuffled questions - respect explicit page breaks
                
                $questionslots = array();
                foreach ($slots as $slot) {
                    $questionslots[$templateusage->get_question($slot)->id] = $slot;
                }
                
                $currentpage = 1;
                foreach ($questions as $question) {
                    $currentquestionid = $question->id;
                    
                    // Add page break if set explicitly by teacher
                    // Note: question->page tells us which page this question should be on
                    if ($question->page > $currentpage) {
                        // Add pages until we reach the correct page
                        while ($question->page > $currentpage) {
                            $pdf->AddPage();
                            $pdf->Ln(14);
                            $currentpage++;
                        }
                    }
                    
                    // Add page break if necessary because of overflow
                    if ($pdf->GetY() > 230) {
                        $pdf->AddPage();
                        $pdf->Ln(14);
                    }
                    
                    $html = offlinequiz_print_question_html($pdf, $question, $texfilter, $trans, $this->offlinequiz);
                    
                    if ($question->qtype == 'multichoice' || $question->qtype == 'multichoiceset') {
                        $slot = $questionslots[$currentquestionid];
                        $html = $html . offlinequiz_get_answers_html($this->offlinequiz, $templateusage,
                            $slot, $question, $texfilter, $trans, true);  // Note: correction=true
                    }
                    
                    if ($question->qtype == 'multichoice' || $question->qtype == 'multichoiceset') {
                        $pdf->SetFont($font, 'B', $this->offlinequiz->fontsize);
                        $pdf->Cell(4, round($this->offlinequiz->fontsize / 2), "$number)  ", 0, 0, 'R');
                        $pdf->SetFont($font, '', $this->offlinequiz->fontsize);
                    }
                    
                    if ($this->offlinequiz->disableimgnewlines) {
                        $html = preg_replace("/(<span class=\"MathJax_Preview\">.+?)+(title=\"TeX\" >)/ms", "", $html);
                        $html = preg_replace("/<\/a><\/span>/ms", "", $html);
                        $html = preg_replace("/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/ms", "", $html);
                    }
                    
                    offlinequiz_write_question_to_pdf($pdf, $this->offlinequiz->fontsize, $question->qtype, $html, $number);
                    $number += $questions[$currentquestionid]->length;
                }
            }
            
            // Add blank pages for normalization
            // Count actual pages generated so far
            $actualpages = $pdf->getNumPages();
            $targetpages = $data['targetpages'];
            $blankpages = $targetpages - $actualpages;
            
            if ($blankpages > 0) {
                for ($i = 0; $i < $blankpages; $i++) {
                    $pdf->AddPage();
                }
            }
            
            // Save PDF
            $date = usergetdate(time());
            $timestamp = sprintf('%04d%02d%02d_%02d%02d%02d',
                    $date['year'], $date['mon'], $date['mday'], $date['hours'], $date['minutes'], $date['seconds']);
            
            $filename = get_string('fileprefixcorrection', 'offlinequiz') . '_' . $groupletter . '_' . $timestamp . '.pdf';
            $filepath = $this->tempdir . DIRECTORY_SEPARATOR . $filename;
            
            $pdfcontent = $pdf->Output('', 'S');
            
            if (file_put_contents($filepath, $pdfcontent) === false) {
                return false;
            }
            
            // Restore original shuffle setting
            $this->offlinequiz->shufflequestions = $originalshufflesetting;
            
            $trans->remove_temp_files();
            
            return $filepath;
            
        } catch (\Exception $e) {
            // Restore original shuffle setting in case of exception
            $this->offlinequiz->shufflequestions = $originalshufflesetting;
            
            return false;
        }
    }
}
