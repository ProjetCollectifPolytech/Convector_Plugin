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
 * PDF Generator class for temporal processor - using offlinequiz native format.
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

// Include generator classes
require_once(__DIR__ . '/base_generator.php');
require_once(__DIR__ . '/quiz_generator.php');
require_once(__DIR__ . '/answer_sheet_generator.php');
require_once(__DIR__ . '/correction_generator.php');

/**
 * PDF Generator for creating normalized exam copies using offlinequiz format.
 *
 * This class generates PDF files with normalized page counts while maintaining
 * the exact format used by the offlinequiz module.
 */
class pdf_generator {

    /** @var object The offlinequiz instance */
    private $offlinequiz;

    /** @var temporal_processor The processor */
    private $processor;

    /** @var string Temporary directory for PDFs */
    private $tempdir;

    /** @var quiz_generator Quiz generator instance */
    private $quiz_generator;

    /** @var answer_sheet_generator Answer sheet generator instance */
    private $answer_sheet_generator;

    /** @var correction_generator Correction generator instance */
    private $correction_generator;

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
        
        // Initialize generator instances
        $this->quiz_generator = new quiz_generator($offlinequiz, $processor, $this->tempdir);
        $this->answer_sheet_generator = new answer_sheet_generator($offlinequiz, $processor, $this->tempdir);
        $this->correction_generator = new correction_generator($offlinequiz, $processor, $this->tempdir);
        
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
                    @unlink($file);
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
            
            // Generate main question sheet with normalization
            $pdfpath = $this->quiz_generator->generate($groupid, $data);
            
            if ($pdfpath && file_exists($pdfpath)) {
                $size = filesize($pdfpath);
                file_put_contents($logfile, "  PDF created: $pdfpath (size: $size bytes)\n", FILE_APPEND);
                $pdffiles[] = ['path' => $pdfpath, 'type' => 'questionnaire'];
            } else {
                file_put_contents($logfile, "  FAILED to create PDF for group $groupid\n", FILE_APPEND);
            }
            
            // Generate answer sheet
            $answersheet = $this->answer_sheet_generator->generate($groupid, $data);
            if ($answersheet && file_exists($answersheet)) {
                file_put_contents($logfile, "  Answer sheet created: $answersheet\n", FILE_APPEND);
                $pdffiles[] = ['path' => $answersheet, 'type' => 'grille'];
            }
            
            // Generate correction form with normalization
            $correctionform = $this->correction_generator->generate($groupid, $data);
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
                $normalizedpath = str_replace('/', DIRECTORY_SEPARATOR, $fileinfo['path']);
                
                file_put_contents($logfile, "  Checking file: $normalizedpath\n", FILE_APPEND);
                
                if (file_exists($normalizedpath)) {
                    $basename = basename($normalizedpath);
                    $folder = isset($folderMap[$fileinfo['type']]) ? $folderMap[$fileinfo['type']] : 'Autres';
                    $zipname = $folder . '/' . $basename;
                    
                    if ($zip->addFile($normalizedpath, $zipname)) {
                        file_put_contents($logfile, "    Added to ZIP as: $zipname\n", FILE_APPEND);
                    } else {
                        file_put_contents($logfile, "    FAILED to add to ZIP\n", FILE_APPEND);
                    }
                } else {
                    file_put_contents($logfile, "    File does NOT exist!\n", FILE_APPEND);
                }
            }
            
            $numfiles = $zip->numFiles;
            file_put_contents($logfile, "ZIP contains $numfiles files before close\n", FILE_APPEND);
            
            $closeresult = $zip->close();
            file_put_contents($logfile, "ZIP close result: " . ($closeresult ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
            
            if ($closeresult && file_exists($zippath)) {
                clearstatcache(true, $zippath);
                $zipsize = filesize($zippath);
                file_put_contents($logfile, "ZIP file exists with size: $zipsize bytes\n", FILE_APPEND);
            } else {
                file_put_contents($logfile, "ZIP file does NOT exist after close!\n", FILE_APPEND);
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
                if (is_file($file) && ($now - filemtime($file)) > 86400) {
                    @unlink($file);
                }
            }
        }
    }
}
