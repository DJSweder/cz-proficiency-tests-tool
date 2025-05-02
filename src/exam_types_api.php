<?php
$db = new SQLite3('db.sqlite');
$res = $db->query("SELECT id, name, category FROM exam_types");
$exams = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $exams[] = $row;
}
header('Content-Type: application/json');
echo json_encode($exams);
