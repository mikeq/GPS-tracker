<?php
// Set this is up in cron job to regularly update the KML file
// ie: * * * * * /usr/bin/php /path/to/scripts/writekml.php to call every minute
// make sure tracker/lib is in the php path
require_once 'tracker/lib/settings.class.php';
require_once 'tracker/lib/db.class.php';
require_once 'tracker/lib/KmlLibrary.class.php';

$db = DatabaseConnection::getInstance()->getConnection();

$tagQuery = "SELECT DISTINCT(tracktag) as tag FROM gpsdata";
$result = $db->query($tagQuery);

if ($result->num_rows === 0) {
    exit;
}

$kml = new KmlLibrary();

$todayTag = date('Ymd');

while ($record = $result->fetch_object()) {
    $date = substr($record->tag, 0, 4) . "-" . substr($record->tag, 4, 2) . "-" . substr($record->tag, 6, 2);

    //check if kml file exists and its not todays
    if ($record->tag !== $todayTag && !file_exists(Settings::KML_LOC . $record->tag . ".kml")){
        $kml->write($date);
    }

    if ($todayTag === $record->tag) {
        $kml->write($date);
    }
}
?>