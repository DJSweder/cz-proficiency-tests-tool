<?php
$exam_id = $_GET['exam_id'];
$db = new SQLite3('db.sqlite');

$exam = $db->querySingle("SELECT total_points, min_total_points FROM exam_types WHERE id=$exam_id", true);
$res_sections = $db->query("SELECT id, name, max_points, min_points FROM exam_sections WHERE exam_type_id=$exam_id");

$sections = [];
while ($section = $res_sections->fetchArray(SQLITE3_ASSOC)) {
    $section_id = $section['id'];
    $res_questions = $db->query("SELECT description, points_per_question FROM question_types WHERE section_id=$section_id");
    $questions = [];
    while ($question = $res_questions->fetchArray(SQLITE3_ASSOC)) {
        $questions[] = $question;
    }
    $section['questions'] = $questions;
    $sections[] = $section;
}

$exam['sections'] = $sections;

header('Content-Type: application/json');
echo json_encode($exam);
