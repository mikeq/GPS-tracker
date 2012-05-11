<?php
    require_once 'lib/instamapper.class.php';
    require_once 'lib/importdata.class.php';

    $insta = new InstaMapper(Settings::MASTER_API_KEY);
    $importer = new ImportData();
//    $timestamp = strtotime('2011-05-17 10:30');
    $importer->write($insta->getPoints(100, $importer->getLastTimestamp()));
//    $importer->write($insta->getPoints(1000, $timestamp));
?>