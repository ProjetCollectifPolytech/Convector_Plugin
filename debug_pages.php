<?php
require_once('../../config.php');

global $DB;

// Get some questions with their page numbers
$sql = "SELECT ogq.questionid, ogq.page, ogq.slot, ogq.offlinegroupid, q.name
        FROM {offlinequiz_group_questions} ogq
        JOIN {question} q ON q.id = ogq.questionid
        ORDER BY ogq.offlinegroupid, ogq.slot
        LIMIT 20";

$questions = $DB->get_records_sql($sql);

echo "<pre>";
echo "Question page analysis:\n\n";

$currentgroup = null;
foreach($questions as $q) {
    if ($currentgroup !== $q->offlinegroupid) {
        echo "\n=== Group ID: " . $q->offlinegroupid . " ===\n";
        $currentgroup = $q->offlinegroupid;
    }
    echo "  Question " . $q->questionid . " (slot " . $q->slot . "): page=" . $q->page . " - " . substr($q->name, 0, 50) . "\n";
}

echo "\n\nOfflinequiz shuffle settings:\n";
$offlinequizzes = $DB->get_records('offlinequiz', null, '', 'id, name, shufflequestions', 0, 5);
foreach($offlinequizzes as $oq) {
    echo "  ID " . $oq->id . ": shufflequestions=" . $oq->shufflequestions . " - " . $oq->name . "\n";
}

echo "</pre>";
