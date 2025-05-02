<?php
$db = new SQLite3('db.sqlite');

// Přetvoření databáze s novým sloupcem "points"
$db->exec("DROP TABLE IF EXISTS questions");
$db->exec("DROP TABLE IF EXISTS answers");

$db->exec("CREATE TABLE questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    external_question_id INTEGER,
    category TEXT,
    question_type INTEGER,
    question_text TEXT,
    case_study_text TEXT,
    case_question_order INTEGER,
    points INTEGER DEFAULT 1
)");

$db->exec("CREATE TABLE answers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question_id INTEGER,
    answer_id INTEGER,
    answer_text TEXT,
    is_correct BOOLEAN,
    FOREIGN KEY(question_id) REFERENCES questions(id)
)");

function getCategory($filename) {
    if (strpos($filename, 'SPOT_OTAZKY_ZDPZ_') !== false) return 'Pojištění';
    if (strpos($filename, 'SPOT_OTAZKY_ZSU_') !== false) return 'Úvěry';
    if (strpos($filename, 'SPOT_OTAZKY_ZPKT_') !== false) return 'Investice';
    return 'Neznámá kategorie';
}

$xmlFiles = glob('import/*.xml');
$totalFiles = count($xmlFiles);
$currentFile = 1;

foreach ($xmlFiles as $file) {
    echo "Importuji soubor ($currentFile z $totalFiles): $file\n";
    $xml = simplexml_load_file($file);
    $category = getCategory((string)$xml->MetaData->NazevSouboru);

    foreach ($xml->OtazkaSeznam->OtazkaZnalostiSeznam->Otazka as $question) {
        importQuestion($question, $category, null, $db);
    }

    foreach ($xml->OtazkaSeznam->OtazkaDovednostiSeznam->PripadovaStudie as $ps) {
        $case_study_text = trim((string)$ps->PSZadani);

        $stmt = $db->prepare("INSERT INTO questions (external_question_id, category, question_type, case_study_text, points) VALUES (?, ?, 4, ?, 2)");
        $stmt->bindValue(1, (int)$ps->OtazkaID);
        $stmt->bindValue(2, $category);
        $stmt->bindValue(3, $case_study_text);
        $stmt->execute();

        foreach ($ps->PSOtazkaSeznam->Otazka as $psQuestion) {
            importQuestion($psQuestion, $category, $case_study_text, $db);
        }
    }

    $currentFile++;
    echo "Dokončeno.\n\n";
}

function importQuestion($question, $category, $case_study_text, $db) {
    $otazkaID = (int)$question->OtazkaID;
    $otazkaTypID = (int)$question->OtazkaTypID;
    $otazkaText = trim((string)$question->OtazkaText);
    $OtazkaPSPoradi = isset($question->OtazkaPSPoradi) ? (int)$question->OtazkaPSPoradi : null;

    $points = ($otazkaTypID == 2) ? 1 : 2; // automatické nastavení bodů dle typu

    $stmt = $db->prepare("INSERT INTO questions (external_question_id, category, question_type, question_text, case_study_text, case_question_order, points) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bindValue(1, $otazkaID);
    $stmt->bindValue(2, $category);
    $stmt->bindValue(3, $otazkaTypID);
    $stmt->bindValue(4, $otazkaText);
    $stmt->bindValue(5, $case_study_text);
    $stmt->bindValue(6, $OtazkaPSPoradi);
    $stmt->bindValue(7, $points);
    $stmt->execute();

    $dbQuestionID = $db->lastInsertRowID();

    foreach ($question->OdpovedSeznam->Odpoved as $answer) {
        $odpovedID = (int)$answer->OdpovedID;
        $odpovedText = trim((string)$answer->OdpovedText);
        $odpovedSpravna = ((string)$answer->OdpovedSpravna) === 'A' ? 1 : 0;

        $stmtAns = $db->prepare("INSERT INTO answers (question_id, answer_id, answer_text, is_correct) VALUES (?, ?, ?, ?)");
        $stmtAns->bindValue(1, $dbQuestionID);
        $stmtAns->bindValue(2, $odpovedID);
        $stmtAns->bindValue(3, $odpovedText);
        $stmtAns->bindValue(4, $odpovedSpravna);
        $stmtAns->execute();
    }
}

echo "Import všech dat dokončen.\n";
