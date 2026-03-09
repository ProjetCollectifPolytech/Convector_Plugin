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
 * French language strings for local_offlinequizaddons plugin.
 *
 * @package    local_offlinequizaddons
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Extensions OfflineQuiz';
$string['offlinequizaddons:view'] = 'Voir les extensions OfflineQuiz';
$string['tabname'] = 'Extensions';
$string['helloworld'] = 'Bonjour le monde';
$string['welcome'] = 'Bienvenue dans les Extensions OfflineQuiz';
$string['mainpagetitle'] = 'Extensions OfflineQuiz';

// Temporal Convector strings
$string['temporal_convector'] = 'Convecteur Temporel';
$string['temporal_convector_link'] = 'Télécharger les données à partir du convecteur temporel';
$string['temporal_description'] = 'Le Convecteur Temporel normalise le nombre de pages sur toutes les copies de votre examen. Cela garantit que les copies d\'examen aient le même nombre de pages, évitant ainsi les incohérences de mise en page.';
$string['analysis_summary'] = 'Résumé de l\'analyse';
$string['group_analysis'] = 'Analyse par groupe';
$string['question_details'] = 'Détails des questions';
$string['max_pages'] = 'Pages maximum';
$string['needs_normalization'] = 'Nécessite une normalisation';
$string['question_count'] = 'Nombre de questions';
$string['current_pages'] = 'Pages actuelles';
$string['blank_pages_needed'] = 'Pages blanches nécessaires';
$string['final_pages'] = 'Pages finales';
$string['pages'] = 'Pages';
$string['group'] = 'Groupe';
$string['generate_normalized_pdfs'] = 'Générer les PDF normalisés';
$string['final_pages_info'] = 'Nombre de pages : {$a}';
$string['no_normalization_needed'] = 'Toutes les copies d\'examen ont déjà le même nombre de pages. Aucune normalisation n\'est nécessaire.';
$string['temporal_info'] = 'Cet examen comporte actuellement {$a->currentpages} pages et sera normalisé à {$a->targetpages} pages.';
$string['blankpage'] = 'Page blanche pour la normalisation';

// Instructions for PDF cover page
$string['instructions'] = 'Instructions';
$string['instructions_text'] = "- Remplissez le formulaire complètement\n- Marquez vos réponses clairement\n- Utilisez uniquement un stylo noir ou bleu\n- Ne pliez pas et n'abîmez pas ce formulaire";

// Error messages
$string['error_no_offlinequiz'] = 'Erreur : Aucun quiz hors ligne trouvé.';
$string['error_no_questions'] = 'Erreur : Cet examen ne contient aucune question. Au moins une question est requise.';
$string['error_invalid_question_type'] = 'Erreur : Le type de question "{$a}" n\'est pas compatible. Seuls les QCM et les questions de rédaction sont supportés.';
$string['error_pdf_generation'] = 'Erreur : Échec de la génération des fichiers PDF. Veuillez réessayer.';
