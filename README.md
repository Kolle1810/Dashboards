# 💑 Unser Dashboard

Ein gemeinsames Dashboard für zwei, gehostet auf eurem eigenen QNAP-NAS.
Alle Geräte greifen auf dieselben, zentral auf dem NAS gespeicherten Daten zu.

## Was kann das Dashboard?

| Bereich | Was es macht |
|---|---|
| **Übersicht** | Die wichtigsten Zahlen auf einen Blick: Ausgaben, wer wem etwas schuldet, offene To-dos |
| **Meetings** | Themenliste fürs nächste Paar-Meeting + Protokolle vergangener Meetings |
| **Kosten** | Ausgaben eintragen (wer hat bezahlt?, ggf. „nicht teilen“), Monatsübersicht mit Diagramm und automatischer 50/50-Abrechnung |
| **Kalender** | Google-Login mit gemeinsamer Terminliste beider Kalender direkt im Dashboard, plus zwei Buttons zum direkten Öffnen als Rückfalloption |
| **To-dos** | Wochenaufgaben mit Zuordnung (wer macht's?) zum Abhaken |
| **Geschenke** | Eure eigene Wunschliste (versteckbar) + eine dritte Liste für Geschenkideen für andere |
| **Projekte** | Größere Vorhaben (Urlaub, Haus …) mit Checklisten, Fortschrittsbalken und Archiv für erledigte Schritte |

## Aufbau

- `index.html` – das komplette Dashboard (Oberfläche + Logik)
- `api.php` – kleines Backend, das die Daten zentral in `data.json` auf dem NAS speichert
- `data.json` – wird beim ersten Speichern automatisch angelegt, enthält eure echten Daten (**nicht** Teil von Git, siehe `.gitignore`)

## Einrichtung auf dem QNAP-NAS

1. **Web Station** auf dem NAS aktivieren (Control Panel → Applications → Web Station), PHP-Unterstützung ist dabei standardmäßig enthalten.
2. `index.html` und `api.php` in denselben Ordner legen – z. B. den „Web“-Ordner von Web Station.
3. Sicherstellen, dass dieser Ordner für den Webserver **beschreibbar** ist, damit `data.json` automatisch angelegt werden kann (Berechtigungen ggf. in File Station anpassen).
4. `api.php` öffnen und bei `$API_KEY` einen eigenen, frei erfundenen Schlüssel eintragen (z. B. ein langes zufälliges Wort). Diese Bearbeitung nur direkt auf dem NAS vornehmen, nicht zurück nach GitHub pushen.
5. Das Dashboard im Browser öffnen, unter **⚙️ Einstellungen → „Geräteübergreifende Synchronisierung“** denselben Schlüssel eintragen.
6. Fertig – ab jetzt sehen alle Geräte, die dieselbe NAS-Adresse mit demselben Schlüssel nutzen, automatisch dieselben Daten.

**Zugriff von unterwegs:** Am einfachsten ist eine **https-Adresse**, die auch von zuhause funktioniert (z. B. über euren NAS-Fernzugriff oder – bei rein lokalem Zugriff per VPN – über einen Dienst wie `nip.io`, der eure lokale IP in einen gültigen Domainnamen verpackt, z. B. `https://192.168.178.42.nip.io:8081`). Details dazu gerne im Chat nachlesen, falls ihr das noch mal nachschlagen wollt.

## Wie funktioniert die Synchronisierung technisch?

Jedes Gerät hat weiterhin eine schnelle lokale Kopie der Daten (falls das NAS
mal kurz nicht erreichbar ist, geht nichts verloren). Bei jeder Änderung wird
zusätzlich automatisch mit `api.php` auf dem NAS abgeglichen:

- Beim Öffnen des Dashboards und regelmäßig alle 20 Sekunden wird geprüft, ob es neue Daten vom NAS gibt.
- Bei jeder eigenen Änderung wird der komplette Datenstand zum NAS hochgeladen.
- Ändern beide Personen **gleichzeitig** etwas, gewinnt die zuletzt gespeicherte Version (kein automatisches Zusammenführen) – für den Alltag i. d. R. unproblematisch.

Ganz unten im Dashboard zeigt ein kleiner Hinweis den Synchronisierungsstatus an.

## Google-Kalender & Google-Login

Die Schritt-für-Schritt-Anleitungen (Kalender-IDs, private Freigabe, sowie
optional der Google-Login fürs Anzeigen voller Termindetails direkt im
Dashboard) stehen ausklappbar direkt im Tab **„Kalender“**.

## Backup

Unter ⚙️ Einstellungen → „Daten sichern & teilen“ könnt ihr jederzeit
zusätzlich eine Sicherungsdatei herunterladen – nützlich als Backup oder um
die Daten notfalls manuell auf ein neues Gerät zu übertragen.
