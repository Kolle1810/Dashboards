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

error_reporting(0); // verhindert, dass PHP-Warnungen/Hinweise die JSON-Antwort zerstören
header('Content-Type: application/json; charset=utf-8');

$API_KEY = 'BITTE-EIGENEN-SCHLUESSEL-EINTRAGEN';

$DATA_FILE = __DIR__ . '/data.json';
$BACKUP_DIR = __DIR__ . '/backups';
$BACKUP_KEEP = 50; // Anzahl der zuletzt aufbewahrten Sicherungen

function respond($code, $data) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Sichert den aktuellen data.json-Inhalt als Zeitstempel-Kopie, bevor er überschrieben
// wird - so lässt sich ein versehentliches Überschreiben (z. B. durch ein Gerät mit
// veraltetem lokalem Stand) über File Station manuell wieder rückgängig machen.
function backupBeforeOverwrite($dataFile, $backupDir, $keep) {
    if (!file_exists($dataFile)) {
        return;
    }
    if (!is_dir($backupDir)) {
        @mkdir($backupDir, 0755, true);
    }
    if (!is_dir($backupDir)) {
        return; // Ordner konnte nicht angelegt werden (z. B. fehlende Schreibrechte) - Backup einfach auslassen
    }
    // Mikrosekunden-Suffix, damit mehrere Sicherungen innerhalb derselben Sekunde
    // (z. B. durch mehrere schnell aufeinanderfolgende Änderungen) sich nicht gegenseitig
    // überschreiben, aber der Dateiname trotzdem chronologisch sortierbar bleibt.
    $stamp = date('Y-m-d_His') . '_' . sprintf('%06d', (int) (microtime(true) * 1000000) % 1000000);
    @copy($dataFile, $backupDir . '/data_' . $stamp . '.json');

    $files = glob($backupDir . '/data_*.json');
    if ($files === false) {
        return;
    }
    sort($files);
    $excess = count($files) - $keep;
    for ($i = 0; $i < $excess; $i++) {
        @unlink($files[$i]);
    }
}

if (strlen($API_KEY) < 8) {
    respond(500, array('error' => 'server_not_configured', 'hint' => 'Bitte in api.php einen eigenen API_KEY eintragen (mindestens 8 Zeichen).'));
}
// Manche Webserver/PHP-Einbindungen entfernen individuelle Header wie
// X-Api-Key auf dem Weg zu PHP - deshalb zusätzlich als URL-Parameter
// akzeptieren, der zuverlässig ankommt.
$providedKey = isset($_SERVER['HTTP_X_API_KEY']) ? $_SERVER['HTTP_X_API_KEY'] : '';
if ($providedKey === '' && isset($_GET['key'])) {
    $providedKey = $_GET['key'];
}
if (!hash_equals($API_KEY, $providedKey)) {
    respond(401, array('error' => 'invalid_key'));
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
        respond(400, array('error' => 'invalid_json'));
    }
    backupBeforeOverwrite($DATA_FILE, $BACKUP_DIR, $BACKUP_KEEP);
    $ok = file_put_contents($DATA_FILE, $raw, LOCK_EX);
    if ($ok === false) {
        respond(500, array('error' => 'write_failed'));
    }
    respond(200, array('ok' => true));
}

respond(405, array('error' => 'method_not_allowed'));
