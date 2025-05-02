<?php
header('Content-Type: application/json');
$db = new SQLite3('db.sqlite');

$search = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';

$stmt = $db->prepare("
    SELECT * FROM questions 
    WHERE category = :category AND 
    (question_text LIKE :search OR case_study_text LIKE :search)
    ORDER BY case_question_order ASC
");
$stmt->bindValue(':category', $category, SQLITE3_TEXT);
$stmt->bindValue(':search', "%$search%", SQLITE3_TEXT);
$results = $stmt->execute();

$data = [];
while ($q = $results->fetchArray(SQLITE3_ASSOC)) {
    $answers = [];
    $resAnswers = $db->query("SELECT * FROM answers WHERE question_id = ".$q['id']);
    while ($a = $resAnswers->fetchArray(SQLITE3_ASSOC)) {
        $answers[] = $a;
    }
    $q['answers'] = $answers;
    $data[] = $q;
}

echo json_encode($data);
