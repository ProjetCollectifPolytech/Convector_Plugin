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
            
            // Generate main question sheet
            $pdfpath = $this->generate_group_pdf($groupid, $data);
            
            if ($pdfpath && file_exists($pdfpath)) {
                $size = filesize($pdfpath);
                file_put_contents($logfile, "  PDF created: $pdfpath (size: $size bytes)\n", FILE_APPEND);
                $pdffiles[] = ['path' => $pdfpath, 'type' => 'questionnaire'];
            } else {
                file_put_contents($logfile, "  FAILED to create PDF for group $groupid\n", FILE_APPEND);
            }
            
            // Get additional info for answer sheet and correction form
            global $DB;
            $groups = $DB->get_records('offlinequiz_groups', ['offlinequizid' => $this->offlinequiz->id], 'id ASC');
            $groupletter = 'A';
            $groupindex = 0;
            foreach ($groups as $g) {
                if ($g->id == $groupid) {
                    $groupletter = chr(65 + $groupindex);
                    break;
                }
                $groupindex++;
            }
            
            $course = $DB->get_record('course', ['id' => $this->offlinequiz->course]);
            $courseyear = '';
            if (preg_match('/(\d{4})\s*[-–—]\s*(\d{4})/', $course->fullname, $matches)) {
                $courseyear = $matches[0];
            }
            
            $questions = $this->get_group_questions($groupid);
            
            // Generate answer sheet
            $answersheet = $this->generate_answer_sheet($groupid, $groupletter, $questions, $courseyear);
            if ($answersheet && file_exists($answersheet)) {
                file_put_contents($logfile, "  Answer sheet created: $answersheet\n", FILE_APPEND);
                $pdffiles[] = ['path' => $answersheet, 'type' => 'grille'];
            }
            
            // Generate correction form
            $correctionform = $this->generate_correction_form($groupid, $groupletter, $questions, $courseyear);
            if ($correctionform && file_exists($correctionform)) {
                file_put_contents($logfile, "  Correction form created: $correctionform\n", FILE_APPEND);
                $pdffiles[] = ['path' => $correctionform, 'type' => 'correction'];
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
            $pdf->SetFont('helvetica', 'I', 9);
            $headertext = $courseyear . ' – ' . $this->offlinequiz->name . ', Sujet ' . $groupletter;
            $pdf->Cell(0, 10, $headertext, 0, 1, 'C');
            
            // Black horizontal line BELOW the text
            $y = $pdf->GetY();
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetLineWidth(0.3);
            $pdf->Line(10, $y, 200, $y);
            $pdf->Ln(8);
            
            // Main title - "Feuille de questions" - large, bold, centered
            $pdf->SetFont('helvetica', 'B', 18);
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
            $pdf->SetFont('helvetica', '', 11);
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
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 6, 'Important :', 0, 1, 'L');
            
            $pdf->SetFont('helvetica', '', 10);
            $importanttext = 'Reportez vos réponses sur la grille de réponses ! Elle sera scannée automatiquement. ' .
                           'Attention de ne pas la plier ni la tacher. Utilisez un stylo noir ou bleu pour remplir les champs. ' .
                           'Pour corriger une case cochée, remplissez complètement la case de couleur : elle sera interprétée comme non cochée.';
            $pdf->MultiCell(0, 5, $importanttext, 0, 'L');
            
            // Footer - page number centered in italic
            $pdf->SetY(-20);
            $pdf->SetFont('helvetica', 'I', 9);
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
                    $pdf->SetFont('helvetica', 'I', 9);
                    $headertext = $courseyear . ' – ' . $this->offlinequiz->name . ', Sujet ' . $groupletter;
                    $pdf->Cell(0, 10, $headertext, 0, 1, 'C');
                    
                    // Black horizontal line BELOW the text (thin line, not thick)
                    $y = $pdf->GetY();
                    $pdf->SetDrawColor(0, 0, 0);
                    $pdf->SetLineWidth(0.3);
                    $pdf->Line(10, $y, 200, $y);
                    $pdf->Ln(8);
                    
                    // Add footer for this page
                    $pdf->SetY(-20);
                    $pdf->SetFont('helvetica', 'I', 9);
                    $pdf->Cell(0, 5, 'Page ' . $pagenum . '/' . $data['targetpages'], 0, 0, 'C');
                    
                    // Reset Y position after footer
                    $pdf->SetY($y + 8);
                }
                
                // Question number and text on same line - bold
                $pdf->SetFont('helvetica', 'B', 11);
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
                    $pdf->SetFont('helvetica', '', 10);
                    
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
                    $pdf->SetFont('helvetica', 'I', 9);
                    $pdf->Cell(15, 5, '', 0, 0); // Tabulation
                    $pdf->MultiCell(0, 5, 'Répondez dans l\'espace ci-dessous :', 0, 'L');
                    $pdf->Ln(2);
                    $pdf->SetFont('helvetica', '', 10);
                    for ($i = 0; $i < 8; $i++) {
                        $pdf->Cell(0, 6, '', 'B', 1);
                    }
                    $pdf->Ln(5);
                } else if ($question->qtype == 'truefalse') {
                    $pdf->SetFont('helvetica', '', 10);
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
                    $pdf->SetFont('helvetica', 'I', 9);
                    $headertext = $courseyear . ' – ' . $this->offlinequiz->name . ', Sujet ' . $groupletter;
                    $pdf->Cell(0, 10, $headertext, 0, 1, 'C');
                    
                    // Black horizontal line BELOW the text (thin line)
                    $y = $pdf->GetY();
                    $pdf->SetDrawColor(0, 0, 0);
                    $pdf->SetLineWidth(0.3);
                    $pdf->Line(10, $y, 200, $y);
                    
                    // Add footer
                    $pdf->SetY(-20);
                    $pdf->SetFont('helvetica', 'I', 9);
                    $pdf->Cell(0, 5, 'Page ' . $pagenum . '/' . $data['targetpages'], 0, 0, 'C');
                }
            }

            // Save PDF - use Output with string mode then write to file
            $date = date('Ymd_His');
            $filename = 'formulaire_questions_' . $groupletter . '_' . $date . '.pdf';
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
     * Generate answer sheet (grille de réponses) for a group.
     *
     * @param int $groupid The group ID
     * @param string $groupletter The group letter (A, B, C, etc.)
     * @param array $questions Array of questions
     * @param string $courseyear Course year string
     * @return string|false Path to the PDF file or false on failure
     */
    private function generate_answer_sheet($groupid, $groupletter, $questions, $courseyear) {
        try {
            $pdf = new \pdf('P', 'mm', 'A4', true, 'UTF-8');
            $pdf->SetTitle($this->offlinequiz->name . ' - Grille de réponses ' . $groupletter);
            $pdf->SetAuthor('Moodle - OfflineQuiz');
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(false);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            $pdf->AddPage();

            // Top markers (crosses in corners)
            $pdf->SetFont('helvetica', '', 20);
            $pdf->SetXY(10, 10);
            $pdf->Cell(10, 10, '+', 0, 0, 'L');
            $pdf->SetXY(190, 10);
            $pdf->Cell(10, 10, '+', 0, 0, 'R');

            // Title
            $pdf->SetFont('helvetica', 'B', 18);
            $pdf->SetY(25);
            $pdf->Cell(0, 10, 'Grille de réponses', 0, 1, 'C');
            
            // Subtitle
            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(0, 5, 'Pour analyse automatique', 0, 1, 'C');
            $pdf->Ln(5);

            // Left section: Student info with boxes
            $pdf->SetFont('helvetica', '', 10);
            $leftBoxX = 15;
            $leftBoxY = $pdf->GetY();
            $leftBoxWidth = 60;
            $leftBoxHeight = 30;
            
            // Main box for student info
            $pdf->Rect($leftBoxX, $leftBoxY, $leftBoxWidth, $leftBoxHeight);
            
            // Prénom field
            $pdf->SetXY($leftBoxX + 2, $leftBoxY + 2);
            $pdf->Cell(0, 5, 'Prénom:', 0, 1, 'L');
            $pdf->Line($leftBoxX, $leftBoxY + 8, $leftBoxX + $leftBoxWidth, $leftBoxY + 8);
            
            // Nom field
            $pdf->SetXY($leftBoxX + 2, $leftBoxY + 10);
            $pdf->Cell(0, 5, 'Nom:', 0, 1, 'L');
            $pdf->Line($leftBoxX, $leftBoxY + 16, $leftBoxX + $leftBoxWidth, $leftBoxY + 16);
            
            // Signature field (same size as others)
            $pdf->SetXY($leftBoxX + 2, $leftBoxY + 18);
            $pdf->Cell(0, 5, 'Signature:', 0, 1, 'L');
            
            // N° de place box (right side of student info, square with text on top)
            $placeBoxX = $leftBoxX + $leftBoxWidth + 5;
            $placeBoxSize = 20;
            $pdf->Rect($placeBoxX, $leftBoxY, $placeBoxSize, $placeBoxSize);
            $pdf->SetXY($placeBoxX, $leftBoxY + 1);
            $pdf->SetFont('helvetica', '', 7);
            $pdf->Cell($placeBoxSize, 4, 'N° de place', 0, 1, 'C');

            // Right section: Numéro d'étudiant with digit boxes (positioned on the right side)
            $rightBoxX = 145;  // Position on the right side of the page
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetXY($rightBoxX, $leftBoxY + 5);
            $pdf->Cell(0, 5, 'Numéro d\'étudiant', 0, 1, 'L');
            
            // Draw digit boxes (8 boxes)
            $digitBoxSize = 6;
            $digitBoxY = $leftBoxY + 12;
            for ($i = 0; $i < 8; $i++) {
                $pdf->Rect($rightBoxX + ($i * $digitBoxSize), $digitBoxY, $digitBoxSize, 8);
            }
            
            // Draw digit checkboxes (0-9 for each position)
            $checkboxSize = 3.5;
            $checkboxStartY = $digitBoxY + 10;
            for ($digit = 0; $digit <= 9; $digit++) {
                $y = $checkboxStartY + ($digit * 5.5);
                
                // Digit label on left
                $pdf->SetXY($rightBoxX - 5, $y - 0.5);
                $pdf->SetFont('helvetica', '', 8);
                $pdf->Cell(4, $checkboxSize, $digit, 0, 0, 'R');
                
                // Checkboxes for this digit
                for ($i = 0; $i < 8; $i++) {
                    $x = $rightBoxX + ($i * $digitBoxSize) + ($digitBoxSize - $checkboxSize) / 2;
                    $pdf->Rect($x, $y, $checkboxSize, $checkboxSize);
                }
                
                // Digit label on right
                $pdf->SetXY($rightBoxX + (8 * $digitBoxSize) + 1, $y - 0.5);
                $pdf->Cell(4, $checkboxSize, $digit, 0, 0, 'L');
            }

            // Subject selection checkboxes (positioned below the left box)
            $subjectY = $leftBoxY + $leftBoxHeight + 3;
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetXY($leftBoxX, $subjectY);
            $pdf->Cell(15, 6, 'Sujet:', 0, 0, 'L');
            
            $subjects = ['A', 'B', 'C', 'D', 'E', 'F'];
            $subjectCheckSize = 4;
            for ($i = 0; $i < 6; $i++) {
                $x = $leftBoxX + 20 + ($i * 15);
                $pdf->SetXY($x, $subjectY);
                $pdf->Cell(5, 6, $subjects[$i], 0, 0, 'L');
                $checkX = $x + 6;
                $checkY = $subjectY + 1;
                $pdf->Rect($checkX, $checkY, $subjectCheckSize, $subjectCheckSize);
            }
            
            // Instructions text
            $instructY = $subjectY + 10;
            $pdf->SetXY($leftBoxX, $instructY);
            $pdf->SetFont('helvetica', '', 8);
            $textWidth = 125;  // Width to avoid overlapping with the grid on the right
            $pdf->MultiCell($textWidth, 3.5, 
                "Cette grille de réponses sera scannée automatiquement. Veuillez ne pas plier ou tâcher.\n" .
                "Utilisez un stylo noir ou bleu pour remplir les champs :", 0, 'L');
            
            $pdf->Ln(2);
            
            // Checkbox examples
            $exampleY = $pdf->GetY();
            $pdf->SetFont('zapfdingbats', '', 16);
            $pdf->SetXY($leftBoxX, $exampleY);
            $pdf->Cell(6, 6, '8', 0, 0, 'C');  // '8' in Zapfdingbats = checkmark
            $pdf->Rect($leftBoxX, $exampleY, 6, 6);
            
            $pdf->SetXY($leftBoxX, $exampleY + 8);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->MultiCell($textWidth, 3.5, 
                'Seules les cases cochées clairement sont interprétées correctement ! Pour corriger une case cochée, remplissez complètement la case de couleur : elle sera interprétée comme non cochée :', 0, 'L');
            
            $exampleY2 = $pdf->GetY() + 2;
            $pdf->SetFillColor(0, 0, 0);
            $pdf->Rect($leftBoxX, $exampleY2, 6, 6, 'F');
            
            $pdf->SetXY($leftBoxX, $exampleY2 + 8);
            $pdf->MultiCell($textWidth, 3.5,
                "Les cases ainsi corrigées ne peuvent pas être marquées à nouveau. Veuillez ne rien inscrire en dehors des cases.", 0, 'L');

            $pdf->Ln(3);

            // Generate answer grid
            $this->draw_answer_grid($pdf, $questions);

            $date = date('Ymd_His');
            $filename = 'grille_reponses_' . $groupletter . '_' . $date . '.pdf';
            $filepath = $this->tempdir . DIRECTORY_SEPARATOR . $filename;
            $pdfcontent = $pdf->Output('', 'S');
            file_put_contents($filepath, $pdfcontent);

            return $filepath;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Draw answer grid with checkboxes.
     *
     * @param \pdf $pdf The PDF object
     * @param array $questions Array of questions
     */
    private function draw_answer_grid($pdf, $questions) {
        $questionsPerColumn = 20;
        $columnsPerPage = 3;
        $questionsPerPage = $questionsPerColumn * $columnsPerPage;
        
        $columnWidth = 60;
        $rowHeight = 8;
        $checkboxSize = 4;
        $startX = 15;
        $startY = $pdf->GetY();
        
        $questionNum = 1;
        $currentColumn = 0;
        $currentRow = 0;
        $pageNum = 1;

        $pdf->SetFont('helvetica', '', 9);
        
        foreach ($questions as $question) {
            // Check if we need a new page
            if ($questionNum > 1 && ($questionNum - 1) % $questionsPerPage === 0) {
                $pdf->AddPage();
                $currentColumn = 0;
                $currentRow = 0;
                $startY = 20;
            }
            
            // Check if we need a new column
            if ($currentRow >= $questionsPerColumn) {
                $currentColumn++;
                $currentRow = 0;
            }
            
            $x = $startX + ($currentColumn * $columnWidth);
            $y = $startY + ($currentRow * $rowHeight);
            
            // Draw labels above checkboxes only once every 8 questions
            $addExtraSpacing = false;
            if ($currentRow % 8 === 0) {
                $addExtraSpacing = true;
                // Get number of answers for this question to draw labels
                $answers = $this->get_question_answers($question->id);
                $numAnswers = count($answers);
                $answerLabels = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
                
                for ($i = 0; $i < min($numAnswers, 8); $i++) {
                    $checkX = $x + 12 + ($i * 7);
                    $labelY = $y - 2;
                    
                    // Label above checkbox
                    $pdf->SetXY($checkX - 0.5, $labelY);
                    $pdf->Cell($checkboxSize + 1, 2, $answerLabels[$i], 0, 0, 'C');
                }
            }
            
            // Adjust Y position if labels were drawn (add extra spacing)
            $adjustedY = $addExtraSpacing ? $y + 2 : $y;
            
            // Question number
            $pdf->SetXY($x, $adjustedY);
            $pdf->Cell(10, $rowHeight, $questionNum . '.', 0, 0, 'R');
            
            // Get number of answers for this question
            $answers = $this->get_question_answers($question->id);
            $numAnswers = count($answers);
            
            // Draw checkboxes
            for ($i = 0; $i < min($numAnswers, 8); $i++) {
                $checkX = $x + 12 + ($i * 7);
                $checkY = $adjustedY + 2;
                
                // Draw checkbox
                $pdf->Rect($checkX, $checkY, $checkboxSize, $checkboxSize);
            }
            
            $currentRow++;
            $questionNum++;
        }
    }

    /**
     * Generate correction form (formulaire de correction) for a group.
     *
     * @param int $groupid The group ID
     * @param string $groupletter The group letter (A, B, C, etc.)
     * @param array $questions Array of questions
     * @param string $courseyear Course year string
     * @return string|false Path to the PDF file or false on failure
     */
    private function generate_correction_form($groupid, $groupletter, $questions, $courseyear) {
        global $DB;
        
        try {
            $pdf = new \pdf('P', 'mm', 'A4', true, 'UTF-8');
            $pdf->SetTitle($this->offlinequiz->name . ' - Formulaire de correction ' . $groupletter);
            $pdf->SetAuthor('Moodle - OfflineQuiz');
            $pdf->SetMargins(20, 20, 20);
            $pdf->SetAutoPageBreak(false);
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Get target pages for footer calculation
            $data = $this->processor->calculate_blank_pages();
            $groupData = $data[$groupid];
            $targetPages = $groupData['targetpages'];

            // Process questions - same format as questionnaire but with bold correct answers
            $currentpage = 0;  // Start at 0 so first question triggers new page
            $pagenum = 0;
            $questionnumber = 1;

            foreach ($questions as $question) {
                // Check if we need a new page
                if ($question->page != $currentpage) {
                    $pdf->AddPage();
                    $pagenum++;
                    $currentpage = $question->page;
                    
                    // Add page header
                    $pdf->SetFont('helvetica', 'I', 9);
                    $pdf->Cell(0, 10, $headertext, 0, 1, 'C');
                    $y = $pdf->GetY();
                    $pdf->SetDrawColor(0, 0, 0);
                    $pdf->SetLineWidth(0.3);
                    $pdf->Line(10, $y, 200, $y);
                    $pdf->Ln(8);
                    
                    // Add footer
                    $pdf->SetY(-20);
                    $pdf->SetFont('helvetica', 'I', 9);
                    $pdf->Cell(0, 5, 'Page ' . $pagenum . '/' . $targetPages, 0, 0, 'C');
                    
                    $pdf->SetY($y + 8);
                }
                
                // Check if question will fit on current page (with margins)
                $currentY = $pdf->GetY();
                if ($currentY > 250) {  // If too close to bottom, start new page
                    $pdf->AddPage();
                    $pagenum++;
                    
                    // Add page header
                    $pdf->SetFont('helvetica', 'I', 9);
                    $pdf->Cell(0, 10, $headertext, 0, 1, 'C');
                    $y = $pdf->GetY();
                    $pdf->SetLineWidth(0.3);
                    $pdf->Line(10, $y, 200, $y);
                    $pdf->Ln(8);
                    
                    // Add footer
                    $pdf->SetY(-20);
                    $pdf->SetFont('helvetica', 'I', 9);
                    $pdf->Cell(0, 5, 'Page ' . $pagenum . '/' . $targetPages, 0, 0, 'C');
                    
                    $pdf->SetY($y + 8);
                }
                
                // Question text - bold
                $pdf->SetFont('helvetica', 'B', 11);
                $questiontitle = $questionnumber . ')  ' . strip_tags($question->questiontext);
                
                if (!empty($question->generalfeedback)) {
                    $questiontitle .= ' ' . strip_tags($question->generalfeedback);
                }
                
                $pdf->MultiCell(0, 5, $questiontitle, 0, 'L');
                $pdf->Ln(3);
                
                // Answers - bold if correct
                if ($question->qtype == 'multichoice') {
                    $answers = $this->get_question_answers($question->id);
                    
                    $answerlabels = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
                    $answerindex = 0;
                    
                    foreach ($answers as $answer) {
                        if ($answerindex < count($answerlabels)) {
                            $label = $answerlabels[$answerindex];
                            
                            // Set font to bold if this is the correct answer (fraction > 0)
                            if ($answer->fraction > 0) {
                                $pdf->SetFont('helvetica', 'B', 10);
                            } else {
                                $pdf->SetFont('helvetica', '', 10);
                            }
                            
                            $pdf->Cell(15, 5, '', 0, 0);
                            $pdf->MultiCell(0, 5, $label . ')  ' . strip_tags($answer->answer), 0, 'L');
                            $answerindex++;
                        }
                    }
                    $pdf->Ln(5);
                } else if ($question->qtype == 'essay') {
                    $pdf->SetFont('helvetica', 'I', 9);
                    $pdf->Cell(15, 5, '', 0, 0);
                    $pdf->MultiCell(0, 5, 'Répondez dans l\'espace ci-dessous :', 0, 'L');
                    $pdf->Ln(2);
                    $pdf->SetFont('helvetica', '', 10);
                    for ($i = 0; $i < 8; $i++) {
                        $pdf->Cell(0, 6, '', 'B', 1);
                    }
                    $pdf->Ln(5);
                } else if ($question->qtype == 'truefalse') {
                    $answers = $this->get_question_answers($question->id);
                    
                    foreach ($answers as $answer) {
                        if ($answer->fraction > 0) {
                            $pdf->SetFont('helvetica', 'B', 10);
                        } else {
                            $pdf->SetFont('helvetica', '', 10);
                        }
                        
                        $pdf->Cell(15, 5, '', 0, 0);
                        $answertext = (stripos($answer->answer, 'true') !== false || stripos($answer->answer, 'vrai') !== false) ? 'Vrai' : 'Faux';
                        $label = (stripos($answer->answer, 'true') !== false || stripos($answer->answer, 'vrai') !== false) ? 'a' : 'b';
                        $pdf->MultiCell(0, 5, $label . ')  ' . $answertext, 0, 'L');
                    }
                    $pdf->Ln(5);
                }
                
                $questionnumber++;
            }

            // Add blank pages if needed
            if ($groupData['blankpages'] > 0) {
                for ($i = 0; $i < $groupData['blankpages']; $i++) {
                    $pdf->AddPage();
                    $pagenum++;
                    
                    $pdf->SetFont('helvetica', 'I', 9);
                    $pdf->Cell(0, 10, $headertext, 0, 1, 'C');
                    $y = $pdf->GetY();
                    $pdf->SetLineWidth(0.3);
                    $pdf->Line(10, $y, 200, $y);
                    
                    $pdf->SetY(-20);
                    $pdf->SetFont('helvetica', 'I', 9);
                    $pdf->Cell(0, 5, 'Page ' . $pagenum . '/' . $targetPages, 0, 0, 'C');
                }
            }

            $date = date('Ymd_His');
            $filename = 'formulaire_correction_' . $groupletter . '_' . $date . '.pdf';
            $filepath = $this->tempdir . DIRECTORY_SEPARATOR . $filename;
            $pdfcontent = $pdf->Output('', 'S');
            file_put_contents($filepath, $pdfcontent);

            return $filepath;
        } catch (\Exception $e) {
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
            'id ASC'
        );
    }

    /**
     * Create a ZIP archive containing all PDF files.
     *
     * @param array $pdffiles Array of PDF file info (path and type)
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
        
        // Map file types to folder names
        $folderMap = [
            'questionnaire' => 'Questionnaires',
            'grille' => 'Grilles de réponses',
            'correction' => 'Formulaires de correction'
        ];
        
        if ($result === true) {
            foreach ($pdffiles as $fileinfo) {
                // Normalize path separators for Windows
                $normalizedpath = str_replace('/', DIRECTORY_SEPARATOR, $fileinfo['path']);
                
                file_put_contents($logfile, "  Checking file: $normalizedpath\n", FILE_APPEND);
                
                if (file_exists($normalizedpath)) {
                    $basename = basename($normalizedpath);
                    $realpath = realpath($normalizedpath);
                    
                    // Determine folder name based on file type
                    $folder = isset($folderMap[$fileinfo['type']]) ? $folderMap[$fileinfo['type']] : '';
                    $zipname = $folder ? $folder . '/' . $basename : $basename;
                    
                    file_put_contents($logfile, "    Real path: $realpath\n", FILE_APPEND);
                    file_put_contents($logfile, "    ZIP name: $zipname\n", FILE_APPEND);
                    
                    $added = $zip->addFile($realpath, $zipname);
                    file_put_contents($logfile, "    Adding $basename to $folder: " . ($added ? 'OK' : 'FAILED') . "\n", FILE_APPEND);
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
