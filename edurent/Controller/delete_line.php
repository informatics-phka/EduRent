<?php
if(isset($_POST['file']) && isset($_POST['line'])) {
    $file = $_POST['file'];
    $line_number = $_POST['line'];

    $lines = file($file);

    // Zeile löschen
    if(isset($lines[$line_number])) {
        unset($lines[$line_number]);
    } 
    else {
        save_in_logs("ERROR: Die angegebene Zeile ($line_number) existiert nicht in der Datei: $file - $lines");
    }

    if(file_put_contents($file, implode('', $lines)) == false) {
        save_in_logs("ERROR: Fehler beim Löschen der Zeile aus der Datei: $file");
    }
} else {
    save_in_logs("ERROR: Nicht alle erforderlichen Parameter wurden beim Löschen einer Zeile übergeben.");
}
