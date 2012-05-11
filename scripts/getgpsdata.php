<?php
// Set this is up in cron job to regularly import data from the Instamapper service
// ie: * * * * * /usr/bin/php /path/to/scripts/getgpsdata.php to call every minute
// make sure tracker/lib is in the php path
require_once 'tracker/lib/settings.class.php';
require_once 'tracker/lib/instamapper.class.php';
require_once 'tracker/lib/importdata.class.php';

if (time() < strtotime(Settings::START_DATE)) {
    exit;
}

$insta = new InstaMapper(Settings::MASTER_API_KEY);
$importer = new ImportData();
$importer->write($insta->getPoints(10, $importer->getLastTimestamp()));
?>