<?php
$db = new SQLite3('db.sqlite');

$html = file_get_contents('import/EDUCA_PENZE.html');
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
libxml_clear_errors();
$xpath = new DOMXPath($dom);

// Znalosti
$znalosti = $xpath->query("//div[contains(@class,'BLOK ZNALOSTI')]//div[contains(@class,'QUESTION')]");
foreach ($znalosti as $q) {
    importQuestion($q, "Penzijní produkty – Znalosti", 2, 1, $xpath, $db);
}

// Dovednosti
$dovednosti = $xpath->query("//div[contains(@class,'BLOK DOVEDNOSTI')]//div[contains(@class,'QUESTION')]");
foreach ($dovednosti as $q) {
    importQuestion($q, "Penzijní produkty – Dovednosti", 4, 2, $xpath, $db);
}

echo "Import hotov.\n";

function importQuestion($qNode, $category, $type, $points, $xpath, $db) {
    $questionText = trim($xpath->query(".//div[contains(@class,'TEXT')]", $qNode)->item(0)->textContent);

    $stmt = $db->prepare("INSERT INTO questions (external_question_id, category, question_type, question_text, points) VALUES (?, ?, ?, ?, ?)");
    $stmt->bindValue(1, null); // nemáme externí ID
    $stmt->bindValue(2, $category);
    $stmt->bindValue(3, $type);
    $stmt->bindValue(4, $questionText);
    $stmt->bindValue(5, $points);
    $stmt->execute();

    $questionID = $db->lastInsertRowID();

    $choices = $xpath->query(".//div[contains(@class,'CHOICE')]", $qNode);
    foreach ($choices as $choice) {
        $answerNode = $xpath->query(".//div[contains(@class,'VALUE')]", $choice)->item(0);
        $answerText = trim($answerNode->textContent);
        $is_correct = (strpos($answerNode->getAttribute('class'), 'CORRECT') !== false) ? 1 : 0;

        $stmtAns = $db->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
        $stmtAns->bindValue(1, $questionID);
        $stmtAns->bindValue(2, $answerText);
        $stmtAns->bindValue(3, $is_correct);
        $stmtAns->execute();
    }
}
