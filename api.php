<?php
/**
 * Kleines Backend fürs "Unser Dashboard" - speichert den kompletten
 * Datenstand zentral in data.json, damit alle Geräte dieselben Daten sehen.
 *
 * Einrichtung:
 * 1. Diese Datei zusammen mit index.html in denselben Ordner auf dem NAS legen
 *    (z. B. den "Web"-Ordner von QNAP Web Station).
 * 2. Unten bei $API_KEY einen eigenen, frei erfundenen Schlüssel eintragen
 *    (z. B. ein langes zufälliges Wort) - denselben Schlüssel dann im
 *    Dashboard unter Einstellungen -> "Geräteübergreifende Synchronisierung"
 *    eintragen.
 * 3. Sicherstellen, dass der Ordner für den Webserver beschreibbar ist,
 *    damit data.json automatisch angelegt werden kann.
 */

header('Content-Type: application/json; charset=utf-8');

$API_KEY = 'BITTE-EIGENEN-SCHLUESSEL-EINTRAGEN';

$DATA_FILE = __DIR__ . '/data.json';

function respond($code, $data) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if (strlen($API_KEY) < 8) {
    respond(500, ['error' => 'server_not_configured', 'hint' => 'Bitte in api.php einen eigenen API_KEY eintragen (mindestens 8 Zeichen).']);
}
$providedKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if (!hash_equals($API_KEY, $providedKey)) {
    respond(401, ['error' => 'invalid_key']);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (!file_exists($DATA_FILE)) {
        respond(200, new stdClass());
    }
    $content = file_get_contents($DATA_FILE);
    http_response_code(200);
    echo ($content !== false && trim($content) !== '') ? $content : '{}';
    exit;
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw);
    if ($decoded === null && trim($raw) !== 'null') {
        respond(400, ['error' => 'invalid_json']);
    }
    $ok = file_put_contents($DATA_FILE, $raw, LOCK_EX);
    if ($ok === false) {
        respond(500, ['error' => 'write_failed']);
    }
    respond(200, ['ok' => true]);
}

respond(405, ['error' => 'method_not_allowed']);
