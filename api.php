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

if ($API_KEY === 'BITTE-EIGENEN-SCHLUESSEL-EINTRAGEN' || strlen($API_KEY) < 8) {
    respond(500, array('error' => 'server_not_configured', 'hint' => 'In api.php auf dem NAS steht noch der Platzhalter statt eures eigenen API_KEY (mindestens 8 Zeichen). Das passiert leicht, wenn api.php neu hochgeladen wurde - bitte den Schlüssel dort wieder eintragen (zu finden im Dashboard unter Einstellungen -> Geräteübergreifende Synchronisierung).'));
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
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Nur exakt die selbst erzeugten Sicherungsnamen zulassen - verhindert, dass über den
// Dateinamen beliebige andere Dateien vom NAS gelesen oder überschrieben werden.
function safeBackupName($name) {
    return preg_match('/^data_[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{6}_[0-9]{6}\.json$/', $name) ? $name : null;
}

// Kurzfassung des Inhalts, damit im Dashboard erkennbar ist, welche Sicherung die
// gesuchte ist, ohne jede Datei einzeln öffnen zu müssen.
function summarize($json) {
    $d = json_decode($json, true);
    if (!is_array($d)) {
        return array('readable' => false);
    }
    $countOf = function ($v) { return is_array($v) ? count($v) : 0; };
    $gifts = isset($d['gifts']) && is_array($d['gifts']) ? $d['gifts'] : array();

    // Aus welchen Monaten stammen die Ausgaben? Damit ist auf einen Blick erkennbar,
    // ob eine Sicherung auch die Vormonate enthält - und nicht nur den laufenden Monat.
    $months = array();
    if (isset($d['expenses']) && is_array($d['expenses'])) {
        foreach ($d['expenses'] as $e) {
            if (is_array($e) && isset($e['date']) && is_string($e['date']) && strlen($e['date']) >= 7) {
                $m = substr($e['date'], 0, 7);
                if (!isset($months[$m])) { $months[$m] = 0; }
                $months[$m]++;
            }
        }
        ksort($months);
    }

    return array(
        'readable'      => true,
        'expenseMonths' => $months, // z. B. {"2026-06": 8, "2026-07": 11}
        'expenses'      => $countOf(isset($d['expenses']) ? $d['expenses'] : null),
        'todos'      => $countOf(isset($d['todos']) ? $d['todos'] : null),
        'protocols'  => $countOf(isset($d['protocols']) ? $d['protocols'] : null),
        'topics'     => $countOf(isset($d['topics']) ? $d['topics'] : null),
        'projects'   => $countOf(isset($d['projects']) ? $d['projects'] : null),
        'fixedCosts' => $countOf(isset($d['fixedCosts']) ? $d['fixedCosts'] : null),
        'gifts'      => $countOf(isset($gifts['A']) ? $gifts['A'] : null)
                      + $countOf(isset($gifts['B']) ? $gifts['B'] : null)
                      + $countOf(isset($gifts['others']) ? $gifts['others'] : null)
    );
}

if ($method === 'GET' && $action === 'backups') {
    $out = array();
    $files = is_dir($BACKUP_DIR) ? glob($BACKUP_DIR . '/data_*.json') : array();
    if ($files === false) { $files = array(); }
    rsort($files); // neueste zuerst
    foreach ($files as $f) {
        $name = basename($f);
        if (safeBackupName($name) === null) { continue; }
        $content = file_get_contents($f);
        $out[] = array(
            'name'    => $name,
            'size'    => filesize($f),
            'summary' => summarize($content !== false ? $content : '')
        );
    }
    // Auch den aktuellen Stand mitliefern, damit sich beides direkt vergleichen lässt.
    $currentSummary = null;
    if (file_exists($DATA_FILE)) {
        $cur = file_get_contents($DATA_FILE);
        $currentSummary = summarize($cur !== false ? $cur : '');
    }
    respond(200, array('backups' => $out, 'current' => $currentSummary));
}

if ($method === 'GET' && $action === 'backup') {
    $name = safeBackupName(isset($_GET['file']) ? $_GET['file'] : '');
    if ($name === null) { respond(400, array('error' => 'invalid_name')); }
    $path = $BACKUP_DIR . '/' . $name;
    if (!file_exists($path)) { respond(404, array('error' => 'not_found')); }
    $content = file_get_contents($path);
    http_response_code(200);
    echo ($content !== false && trim($content) !== '') ? $content : '{}';
    exit;
}

if ($method === 'POST' && $action === 'restore') {
    $name = safeBackupName(isset($_GET['file']) ? $_GET['file'] : '');
    if ($name === null) { respond(400, array('error' => 'invalid_name')); }
    $path = $BACKUP_DIR . '/' . $name;
    if (!file_exists($path)) { respond(404, array('error' => 'not_found')); }
    $content = file_get_contents($path);
    if ($content === false || json_decode($content) === null) {
        respond(400, array('error' => 'invalid_backup'));
    }
    // Auch den aktuellen (womöglich kaputten) Stand vorher sichern - eine
    // Wiederherstellung soll nie der letzte unumkehrbare Schritt sein.
    backupBeforeOverwrite($DATA_FILE, $BACKUP_DIR, $BACKUP_KEEP);
    if (file_put_contents($DATA_FILE, $content, LOCK_EX) === false) {
        respond(500, array('error' => 'write_failed'));
    }
    respond(200, array('ok' => true, 'restored' => $name));
}

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
