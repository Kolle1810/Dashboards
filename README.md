# 💑 Bürokratie der Liebe

Ein gemeinsames Dashboard für zwei, gehostet auf eurem eigenen QNAP-NAS.
Alle Geräte greifen auf dieselben, zentral auf dem NAS gespeicherten Daten zu.

## Was kann das Dashboard?

| Bereich | Was es macht |
|---|---|
| **Übersicht** | Die wichtigsten Zahlen auf einen Blick: Ausgaben, wer wem etwas schuldet, offene To-dos |
| **Meetings** | Themenliste fürs nächste Paar-Meeting + Protokolle vergangener Meetings |
| **Kosten** | Ausgaben eintragen (wer hat bezahlt? wer trägt die Kosten: beide je 50 % oder eine*r zu 100 %), Monatsübersicht mit Diagramm und automatischer Abrechnung |
| **To-dos** | Wochenaufgaben mit Zuordnung (wer macht's?), Datum, Frist und „sehr wichtig“-Markierung zum Abhaken |
| **Wunschlisten** | Eure eigene Wunschliste + eine dritte Liste für Geschenkideen für andere |
| **Projekte** | Größere Vorhaben (Urlaub, Haus …) mit Checklisten, Fortschrittsbalken und Archiv für erledigte Schritte |

## Texte formatieren

In allen Textfeldern für Inhalte (Themen, Protokolle, Beschreibungen von
Ausgaben und Fixkosten, To-dos, Wunschlisten, Projekte) lassen sich
**Schriftart, Schriftgröße und Schriftfarbe** ändern sowie **Links**
hinterlegen. Sobald ihr in ein solches Feld tippt, erscheint unten eine
Formatierungsleiste. Beträge, Datumsangaben, Kategorien sowie eure Namen und
der API-Schlüssel bleiben bewusst reine Eingabefelder – dort würde
Formatierung die Berechnungen bzw. die Technik stören.

Eingefügte Inhalte werden dabei automatisch bereinigt: Erlaubt sind nur
Textauszeichnungen und Links auf `https://`, `mailto:` oder `tel:` – alles
andere (z. B. Skripte aus einer kopierten Webseite) wird zu reinem Text.

## Aufbau

- `index.html` – das komplette Dashboard (Oberfläche + Logik)
- `api.php` – kleines Backend, das die Daten zentral in `data.json` auf dem NAS speichert
- `data.json` – wird beim ersten Speichern automatisch angelegt, enthält eure echten Daten (**nicht** Teil von Git, siehe `.gitignore`)
- `manifest.json` + `icons/` – sorgen für ein eigenes Logo, wenn ihr das Dashboard auf dem Homebildschirm platziert

## Einrichtung auf dem QNAP-NAS

1. **Web Station** auf dem NAS aktivieren (Control Panel → Applications → Web Station), PHP-Unterstützung ist dabei standardmäßig enthalten.
2. `index.html`, `api.php`, `manifest.json` und den Ordner `icons/` (mit allen Dateien darin) in denselben Ordner legen – z. B. den „Web“-Ordner von Web Station.
3. Sicherstellen, dass dieser Ordner für den Webserver **beschreibbar** ist, damit `data.json` automatisch angelegt werden kann (Berechtigungen ggf. in File Station anpassen).
4. `api.php` öffnen und bei `$API_KEY` einen eigenen, frei erfundenen Schlüssel eintragen (z. B. ein langes zufälliges Wort). Diese Bearbeitung nur direkt auf dem NAS vornehmen, nicht zurück nach GitHub pushen.
5. Das Dashboard im Browser öffnen, unter **⚙️ Einstellungen → „Geräteübergreifende Synchronisierung“** denselben Schlüssel eintragen.
6. Fertig – ab jetzt sehen alle Geräte, die dieselbe NAS-Adresse mit demselben Schlüssel nutzen, automatisch dieselben Daten.

**Logo auf dem Homebildschirm:** Öffnet das Dashboard im Browser eures Handys und wählt „Zum Home-Bildschirm hinzufügen“ (iPhone/Safari: Teilen-Symbol → „Zum Home-Bildschirm“; Android/Chrome: Menü ⋮ → „Zum Startbildschirm hinzufügen“ bzw. „App installieren“). Es erscheint dann automatisch das 💑-Logo als Icon, und die Seite öffnet sich beim Antippen ohne Adressleiste wie eine echte App.

**Zugriff von unterwegs:** Am einfachsten ist eine **https-Adresse**, die auch von zuhause funktioniert (z. B. über euren NAS-Fernzugriff oder – bei rein lokalem Zugriff per VPN – über einen Dienst wie `nip.io`, der eure lokale IP in einen gültigen Domainnamen verpackt, z. B. `https://192.168.178.42.nip.io:8081`). Details dazu gerne im Chat nachlesen, falls ihr das noch mal nachschlagen wollt.

## Wie funktioniert die Synchronisierung technisch?

Jedes Gerät hat weiterhin eine schnelle lokale Kopie der Daten (falls das NAS
mal kurz nicht erreichbar ist, geht nichts verloren). Bei jeder Änderung wird
zusätzlich automatisch mit `api.php` auf dem NAS abgeglichen:

- Beim Öffnen des Dashboards und regelmäßig alle 20 Sekunden wird geprüft, ob es neue Daten vom NAS gibt.
- Bei jeder eigenen Änderung wird der komplette Datenstand zum NAS hochgeladen.
- Ändern beide Personen **gleichzeitig** etwas, gewinnt die zuletzt gespeicherte Version (kein automatisches Zusammenführen) – für den Alltag i. d. R. unproblematisch.

Ganz unten im Dashboard zeigt ein kleiner Hinweis den Synchronisierungsstatus an.

## Backup

Unter ⚙️ Einstellungen → „Daten sichern & teilen“ könnt ihr jederzeit
zusätzlich eine Sicherungsdatei herunterladen – nützlich als Backup oder um
die Daten notfalls manuell auf ein neues Gerät zu übertragen.

Zusätzlich legt `api.php` bei **jeder** Änderung automatisch eine Kopie des
bisherigen Datenstands im Ordner `backups/` an (die letzten 50 Versionen),
bevor `data.json` überschrieben wird. Das schützt vor dem Fall, dass ein
Gerät mit veraltetem lokalem Stand synchronisiert und dabei neuere Einträge
überschreibt (siehe oben: „gewinnt die zuletzt gespeicherte Version“).

**Wiederherstellen, falls doch mal Daten fehlen:**

Am einfachsten direkt im Dashboard unter **⚙️ Einstellungen → „🛟 Sicherungen
auf dem NAS“** → „Sicherungen anzeigen“. Dort steht zu jeder Sicherung, was
sie enthält (z. B. „12 Ausgaben · 5 To-dos · 2 Projekte“) – so ist ohne
Dateisuche erkennbar, welcher Stand der richtige ist. Ein Klick auf
„Wiederherstellen“ genügt; der aktuelle Stand wird dabei vorher automatisch
zusätzlich gesichert, die Wiederherstellung lässt sich also selbst wieder
rückgängig machen. Danach die anderen Geräte einmal neu öffnen.

Alternativ von Hand über **File Station**: die passende Datei aus `backups/`
in den Web-Ordner kopieren und in `data.json` umbenennen (die bestehende
`data.json` vorher umbenennen statt löschen).

> Hinweis zu den Uhrzeiten: Im Dashboard werden die Sicherungen immer in der
> Ortszeit des jeweiligen Geräts angezeigt. Der Zeitstempel im **Dateinamen**
> ist dagegen UTC (Weltzeit) – in Deutschland also 1 Stunde (Winter) bzw.
> 2 Stunden (Sommer) früher als die Ortszeit. Das ist bewusst so, weil die in
> PHP eingestellte Zeitzone auf NAS-Geräten oft nicht der Ortszeit entspricht.
