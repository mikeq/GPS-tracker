<?php
  /**
     * Settings
     *
     * Defines settings for use with GPS tracker
     *
     * @package util
     * @subpackage Settings
     *
     * @author Mike Quinn
     * @version 1.0
    */
class Settings{
    //set the DSN string for the database to store GPS data
    //should be host:port|user|password|database
    //i.e. localhost|username|password|dbname
    //if no port specified it will use the MySQL default port 3306
    const MASTER_DSN = 'localhost|gpsuser|gpspassword|dm_gps';
    const DEFAULT_MYSQL_PORT = '3306';
    const MASTER_API_KEY = '';
}
?>