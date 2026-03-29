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
 * French language strings for local_offlinequizaddons.
 *
 * @package    local_offlinequizaddons
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Extensions OfflineQuiz';
$string['offlinequizaddons:view'] = 'Voir les extensions OfflineQuiz';
$string['offlinequizaddons:generate'] = 'Generer les archives OfflineQuiz normalisees';

$string['temporal_convector'] = 'Convecteur Temporel';
$string['temporal_description'] = 'Le Convecteur Temporel normalise le nombre de pages entre tous les groupes OfflineQuiz afin que chaque copie generee suive la meme pagination.';
$string['group_analysis'] = 'Analyse par groupe';
$string['question_details'] = 'Details des questions';
$string['question_count'] = 'Nombre de questions';
$string['current_pages'] = 'Pages actuelles';
$string['blank_pages_needed'] = 'Pages blanches necessaires';
$string['final_pages'] = 'Pages finales';
$string['pages'] = 'Pages';
$string['group'] = 'Groupe';
$string['generate_normalized_pdfs'] = 'Generer les PDF normalises';
$string['cannot_generate_normalized_pdfs'] = 'Vous pouvez consulter l\'analyse du Convecteur Temporel, mais vous ne disposez pas du droit de generer l\'archive normalisee.';
$string['final_pages_info'] = 'Nombre de pages : {$a}';
$string['no_normalization_needed'] = 'Toutes les copies d\'examen ont deja le meme nombre de pages. Aucune normalisation n\'est necessaire.';

$string['error_no_offlinequiz'] = 'Erreur : aucune activite OfflineQuiz n\'a ete trouvee.';
$string['error_no_questions'] = 'Erreur : cet examen ne contient aucune question. Au moins une question est requise.';
$string['error_invalid_question_type'] = 'Erreur : le type de question "{$a}" n\'est pas compatible. Seuls multichoice, essay, shortanswer et truefalse sont supportes.';
$string['error_pdf_generation'] = 'Erreur : echec de la generation des fichiers PDF. Veuillez reessayer.';
