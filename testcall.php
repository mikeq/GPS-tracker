<?php
//testing from eclipse
    require_once 'lib/instamapper.class.php';
    require_once 'lib/importdata.class.php';

    $insta = new InstaMapper('[yourdevicekey]');
    $importer = new ImportData();
    $timestamp = strtotime('2010-08-31 07:30');
    $importer->write($insta->getPoints(100, $timestamp));
?>
