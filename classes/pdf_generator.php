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
 * PDF Generator class for temporal processor.
 *
 * @package    local_offlinequizaddons
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_offlinequizaddons;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/pdflib.php');

/**
 * PDF Generator for creating normalized exam copies.
 *
 * This class generates PDF files with normalized page counts.
 */
class pdf_generator {

    /** @var object The offlinequiz instance */
    private $offlinequiz;

    /** @var temporal_processor The processor */
    private $processor;

    /** @var string Temporary directory for PDFs */
    private $tempdir;

    /**
     * Constructor.
     *
     * @param object $offlinequiz The offlinequiz instance
     * @param temporal_processor $processor The temporal processor
     */
    public function __construct($offlinequiz, $processor) {
        $this->offlinequiz = $offlinequiz;
        $this->processor = $processor;
        $this->tempdir = make_temp_directory('offlinequizaddons/temporal');
        
        // Clean old files on initialization
        $this->cleanup_old_files();
    }

    /**
     * Clean up old ZIP and PDF files from previous generations.
     */
    private function cleanup_old_files() {
        // Get all files in the temp directory
        $files = glob($this->tempdir . DIRECTORY_SEPARATOR . '*');
        
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    $filename = basename($file);
                    // Delete ZIP and PDF files, keep log files for now
                    if (preg_match('/\.(zip|pdf)$/i', $filename)) {
                        @unlink($file);
                    }
                }
            }
        }
    }

    /**
     * Generate normalized PDFs for all groups.
     *
     * @return string Path to the ZIP file containing all PDFs
     */
    public function generate_normalized_pdfs() {
        global $CFG;

        // Create log file for debugging
        $logfile = $this->tempdir . DIRECTORY_SEPARATOR . 'generation_log.txt';
        file_put_contents($logfile, "=== PDF Generation Log ===\n" . date('Y-m-d H:i:s') . "\n\n", FILE_APPEND);

        $blankpages = $this->processor->calculate_blank_pages();
        $pdffiles = [];

        file_put_contents($logfile, "Groups to process: " . count($blankpages) . "\n", FILE_APPEND);

        foreach ($blankpages as $groupid => $data) {
            file_put_contents($logfile, "Processing group $groupid...\n", FILE_APPEND);
            
            $pdfpath = $this->generate_group_pdf($groupid, $data);
            
            if ($pdfpath && file_exists($pdfpath)) {
                $size = filesize($pdfpath);
                file_put_contents($logfile, "  PDF created: $pdfpath (size: $size bytes)\n", FILE_APPEND);
                $pdffiles[] = $pdfpath;
            } else {
                file_put_contents($logfile, "  FAILED to create PDF for group $groupid\n", FILE_APPEND);
            }
        }

        if (empty($pdffiles)) {
            file_put_contents($logfile, "ERROR: No PDF files were created\n", FILE_APPEND);
            return false;
        }

        file_put_contents($logfile, "\nCreating ZIP with " . count($pdffiles) . " files...\n", FILE_APPEND);

        // Create ZIP file
        $zippath = $this->create_zip_archive($pdffiles);

        file_put_contents($logfile, "ZIP created: $zippath\n", FILE_APPEND);
        if (file_exists($zippath)) {
            file_put_contents($logfile, "ZIP size: " . filesize($zippath) . " bytes\n", FILE_APPEND);
        }

        return $zippath;
    }

    /**
     * Generate PDF for a single group with normalized pages.
     *
     * @param int $groupid The group ID
     * @param array $data Group data including blank pages needed
     * @return string|false Path to the PDF file or false on failure
     */
    private function generate_group_pdf($groupid, $data) {
        global $DB;

        try {
            // Create PDF
            $pdf = new \pdf('P', 'mm', 'A4', true, 'UTF-8');
            $pdf->SetTitle($this->offlinequiz->name . ' - ' . $data['groupname']);
            $pdf->SetAuthor('Moodle - OfflineQuiz');
            $pdf->SetCreator('OfflineQuiz Addons - Convecteur Temporel');
            $pdf->SetMargins(20, 20, 20);
            $pdf->SetAutoPageBreak(false);
            
            // Disable default header and footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Get the original offlinequiz group
            $group = $DB->get_record('offlinequiz_groups', ['id' => $groupid]);
            
            // Get the course
            $course = $DB->get_record('course', ['id' => $this->offlinequiz->course]);
            
            // Determine group letter (A, B, C, D...)
            $groups = $DB->get_records('offlinequiz_groups', ['offlinequizid' => $this->offlinequiz->id], 'id ASC');
            $groupletter = 'A';
            $groupindex = 0;
            foreach ($groups as $g) {
                if ($g->id == $groupid) {
                    $groupletter = chr(65 + $groupindex); // A=65, B=66, etc.
                    break;
                }
                $groupindex++;
            }
            
            // === PAGE 1: Cover page with offlinequiz format ===
            $pdf->AddPage();
            
            // Get course year/semester info from course name if available
            $courseyear = '';
            if (preg_match('/(\d{4})\s*[-–—]\s*(\d{4})/', $course->fullname, $matches)) {
                $courseyear = $matches[0];
            }
            
            // Extract course info for header
            $courseinfo = $course->fullname;
            
            // Top header - small italic text centered
            $pdf->SetFont('times', 'I', 9);
            $headertext = $courseyear . ' – ' . $this->offlinequiz->name . ', Sujet ' . $groupletter;
            $pdf->Cell(0, 5, $headertext, 0, 1, 'C');
            
            // Black horizontal line BELOW the text
            $y = $pdf->GetY();
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetLineWidth(0.3);
            $pdf->Line(10, $y, 200, $y);
            $pdf->Ln(5);
            
            // Main title - "Feuille de questions" - large, bold, centered
            $pdf->SetFont('times', 'B', 18);
            $pdf->Cell(0, 10, 'Feuille de questions', 0, 1, 'C');
            $pdf->Ln(10);
            
            // Centered box with student information
            $boxWidth = 140;
            $boxX = ($pdf->getPageWidth() - $boxWidth) / 2;
            $boxY = $pdf->GetY();
            
            // Draw box with normal border (not thick)
            $pdf->SetLineWidth(0.3);
            $pdf->Rect($boxX, $boxY, $boxWidth, 50);
            
            // Inside the box - student fields with aligned colons
            $pdf->SetFont('times', '', 11);
            $labelWidth = 50; // Width for label alignment
            
            // Nom
            $pdf->SetXY($boxX + 10, $boxY + 10);
            $pdf->Cell($labelWidth, 7, 'Nom:', 0, 0, 'R');
            $pdf->SetLineWidth(0.8);
            $pdf->Line($boxX + $labelWidth + 12, $boxY + 16, $boxX + $boxWidth - 10, $boxY + 16);
            
            // Numéro d'étudiant
            $pdf->SetXY($boxX + 10, $boxY + 22);
            $pdf->Cell($labelWidth, 7, 'Numéro d\'étudiant:', 0, 0, 'R');
            $pdf->SetLineWidth(0.8);
            $pdf->Line($boxX + $labelWidth + 12, $boxY + 28, $boxX + $boxWidth - 10, $boxY + 28);
            
            // Signature
            $pdf->SetXY($boxX + 10, $boxY + 34);
            $pdf->Cell($labelWidth, 7, 'Signature:', 0, 0, 'R');
            $pdf->SetLineWidth(0.8);
            $pdf->Line($boxX + $labelWidth + 12, $boxY + 40, $boxX + $boxWidth - 10, $boxY + 40);
            
            // Move below the box
            $pdf->SetY($boxY + 55);
            $pdf->Ln(8);
            
            // Important section
            $pdf->SetFont('times', 'B', 11);
            $pdf->Cell(0, 6, 'Important :', 0, 1, 'L');
            
            $pdf->SetFont('times', '', 10);
            $importanttext = 'Reportez vos réponses sur la grille de réponses ! Elle sera scannée automatiquement. ' .
                           'Attention de ne pas la plier ni la tacher. Utilisez un stylo noir ou bleu pour remplir les champs. ' .
                           'Pour corriger une case cochée, remplissez complètement la case de couleur : elle sera interprétée comme non cochée.';
            $pdf->MultiCell(0, 5, $importanttext, 0, 'L');
            
            // Footer - page number centered in italic
            $pdf->SetY(-20);
            $pdf->SetFont('times', 'I', 9);
            $pdf->Cell(0, 5, 'Page 1/' . $data['targetpages'], 0, 0, 'C');

            // === PAGES 2+: Questions ===
            // Get questions for this group
            $questions = $this->get_group_questions($groupid);
            
            $currentpage = 0; // Track which question page we're on
            $questionnumber = 1;
            $pagenum = 1; // Absolute page number (1 = cover page)
            
            foreach ($questions as $question) {
                // Check if we need a new page
                if ($question->page != $currentpage) {
                    $pdf->AddPage();
                    $pagenum++;
                    $currentpage = $question->page;
                    
                    // Add page header - text centered
                    $pdf->SetFont('times', 'I', 9);
                    $headertext = $courseyear . ' – ' . $this->offlinequiz->name . ', Sujet ' . $groupletter;
                    $pdf->Cell(0, 5, $headertext, 0, 1, 'C');
                    
                    // Black horizontal line BELOW the text (thin line, not thick)
                    $y = $pdf->GetY();
                    $pdf->SetDrawColor(0, 0, 0);
                    $pdf->SetLineWidth(0.3);
                    $pdf->Line(10, $y, 200, $y);
                    $pdf->Ln(8);
                    
                    // Add footer for this page
                    $pdf->SetY(-20);
                    $pdf->SetFont('times', 'I', 9);
                    $pdf->Cell(0, 5, 'Page ' . $pagenum . '/' . $data['targetpages'], 0, 0, 'C');
                    
                    // Reset Y position after footer
                    $pdf->SetY($y + 8);
                }
                
                // Question number and text on same line - bold
                $pdf->SetFont('times', 'B', 11);
                $questiontitle = $questionnumber . ')  ' . strip_tags($question->questiontext);
                
                // Add feedback/context if present
                if (!empty($question->generalfeedback)) {
                    $questiontitle .= ' ' . strip_tags($question->generalfeedback);
                }
                
                $pdf->MultiCell(0, 6, $questiontitle, 0, 'L');
                $pdf->Ln(2);
                
                // Add answer options for multichoice questions
                if ($question->qtype == 'multichoice') {
                    $answers = $this->get_question_answers($question->id);
                    $pdf->SetFont('times', '', 10);
                    
                    $answerlabels = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
                    $answerindex = 0;
                    
                    foreach ($answers as $answer) {
                        if ($answerindex < count($answerlabels)) {
                            $label = $answerlabels[$answerindex];
                            // Tabulation + answer
                            $pdf->Cell(15, 5, '', 0, 0); // Tabulation
                            $pdf->MultiCell(0, 5, $label . ')  ' . strip_tags($answer->answer), 0, 'L');
                            $answerindex++;
                        }
                    }
                    $pdf->Ln(5);
                } else if ($question->qtype == 'essay') {
                    // Add space for essay questions
                    $pdf->SetFont('times', 'I', 9);
                    $pdf->Cell(15, 5, '', 0, 0); // Tabulation
                    $pdf->MultiCell(0, 5, 'Répondez dans l\'espace ci-dessous :', 0, 'L');
                    $pdf->Ln(2);
                    $pdf->SetFont('times', '', 10);
                    for ($i = 0; $i < 8; $i++) {
                        $pdf->Cell(0, 6, '', 'B', 1);
                    }
                    $pdf->Ln(5);
                } else if ($question->qtype == 'truefalse') {
                    $pdf->SetFont('times', '', 10);
                    $pdf->Cell(15, 5, '', 0, 0); // Tabulation
                    $pdf->MultiCell(0, 5, 'a)  Vrai', 0, 'L');
                    $pdf->Cell(15, 5, '', 0, 0); // Tabulation
                    $pdf->MultiCell(0, 5, 'b)  Faux', 0, 'L');
                    $pdf->Ln(5);
                }
                
                $questionnumber++;
            }

            // === Add blank pages if needed ===
            if ($data['blankpages'] > 0) {
                for ($i = 0; $i < $data['blankpages']; $i++) {
                    $pdf->AddPage();
                    $pagenum++;
                    
                    // Add header - text centered
                    $pdf->SetFont('times', 'I', 9);
                    $headertext = $courseyear . ' – ' . $this->offlinequiz->name . ', Sujet ' . $groupletter;
                    $pdf->Cell(0, 5, $headertext, 0, 1, 'C');
                    
                    // Black horizontal line BELOW the text (thin line)
                    $y = $pdf->GetY();
                    $pdf->SetDrawColor(0, 0, 0);
                    $pdf->SetLineWidth(0.3);
                    $pdf->Line(10, $y, 200, $y);
                    
                    // Add footer
                    $pdf->SetY(-20);
                    $pdf->SetFont('times', 'I', 9);
                    $pdf->Cell(0, 5, 'Page ' . $pagenum . '/' . $data['targetpages'], 0, 0, 'C');
                }
            }

            // Save PDF - use Output with string mode then write to file
            $filename = 'exam_' . $this->offlinequiz->id . '_group_' . $groupid . '_normalized.pdf';
            $filepath = $this->tempdir . DIRECTORY_SEPARATOR . $filename;
            
            // Get PDF content as string
            $pdfcontent = $pdf->Output('', 'S');
            
            // Write to file
            if (file_put_contents($filepath, $pdfcontent) === false) {
                return false;
            }

            return $filepath;

        } catch (\Exception $e) {
            // Log error to file for debugging
            $logfile = $this->tempdir . DIRECTORY_SEPARATOR . 'error_log.txt';
            file_put_contents($logfile, date('Y-m-d H:i:s') . ' - Error generating PDF for group ' . $groupid . ': ' . $e->getMessage() . "\n", FILE_APPEND);
            return false;
        }
    }

    /**
     * Get questions for a specific group.
     *
     * @param int $groupid The group ID
     * @return array Array of question objects
     */
    private function get_group_questions($groupid) {
        global $DB;

        $sql = "SELECT q.*, oqg.page, oqg.slot
                FROM {question} q
                JOIN {offlinequiz_group_questions} oqg ON oqg.questionid = q.id
                WHERE oqg.offlinequizid = :offlinequizid
                  AND oqg.offlinegroupid = :groupid
                ORDER BY oqg.page, oqg.slot";

        return $DB->get_records_sql($sql, [
            'offlinequizid' => $this->offlinequiz->id,
            'groupid' => $groupid
        ]);
    }

    /**
     * Get answers for a specific question.
     *
     * @param int $questionid The question ID
     * @return array Array of answer objects
     */
    private function get_question_answers($questionid) {
        global $DB;

        return $DB->get_records('question_answers', 
            ['question' => $questionid], 
            'fraction DESC, id ASC'
        );
    }

    /**
     * Create a ZIP archive containing all PDF files.
     *
     * @param array $pdffiles Array of PDF file paths
     * @return string Path to the ZIP file
     */
    private function create_zip_archive($pdffiles) {
        $logfile = $this->tempdir . DIRECTORY_SEPARATOR . 'generation_log.txt';
        
        $zipfilename = 'offlinequiz_' . $this->offlinequiz->id . '_normalized_' . time() . '.zip';
        $zippath = $this->tempdir . DIRECTORY_SEPARATOR . $zipfilename;

        $zip = new \ZipArchive();
        $result = $zip->open($zippath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        
        file_put_contents($logfile, "ZIP open result: " . ($result === true ? 'SUCCESS' : $result) . "\n", FILE_APPEND);
        file_put_contents($logfile, "ZIP path: $zippath\n", FILE_APPEND);
        
        if ($result === true) {
            foreach ($pdffiles as $file) {
                // Normalize path separators for Windows
                $normalizedpath = str_replace('/', DIRECTORY_SEPARATOR, $file);
                
                file_put_contents($logfile, "  Checking file: $normalizedpath\n", FILE_APPEND);
                
                if (file_exists($normalizedpath)) {
                    $basename = basename($normalizedpath);
                    $realpath = realpath($normalizedpath);
                    
                    file_put_contents($logfile, "    Real path: $realpath\n", FILE_APPEND);
                    
                    $added = $zip->addFile($realpath, $basename);
                    file_put_contents($logfile, "    Adding $basename: " . ($added ? 'OK' : 'FAILED') . "\n", FILE_APPEND);
                } else {
                    file_put_contents($logfile, "    File not found!\n", FILE_APPEND);
                }
            }
            
            $numfiles = $zip->numFiles;
            file_put_contents($logfile, "ZIP contains $numfiles files before close\n", FILE_APPEND);
            
            $closeresult = $zip->close();
            file_put_contents($logfile, "ZIP close result: " . ($closeresult ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
            
            // Ensure the ZIP is fully written to disk
            if ($closeresult && file_exists($zippath)) {
                clearstatcache(true, $zippath);
                // Wait a bit to ensure file is completely flushed
                usleep(200000); // 0.2 seconds
                
                // Verify ZIP integrity by trying to open it again
                $testzip = new \ZipArchive();
                $testresult = $testzip->open($zippath, \ZipArchive::CHECKCONS);
                if ($testresult === true) {
                    $testzip->close();
                    file_put_contents($logfile, "ZIP integrity check: PASSED\n", FILE_APPEND);
                } else {
                    file_put_contents($logfile, "ZIP integrity check: FAILED (error code: $testresult)\n", FILE_APPEND);
                }
            }
        }

        return $zippath;
    }

    /**
     * Clean up temporary files.
     */
    public function cleanup() {
        // Clean up old files older than 24 hours
        $files = glob($this->tempdir . DIRECTORY_SEPARATOR . '*');
        
        if ($files) {
            $now = time();
            foreach ($files as $file) {
                if (is_file($file)) {
                    // Delete files older than 24 hours
                    if ($now - filemtime($file) > 86400) {
                        @unlink($file);
                    }
                }
            }
        }
    }
}
