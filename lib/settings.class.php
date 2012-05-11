<?php
  /**
     * Settings
     *
     * Defines settings for use with Velo Challenge site
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
    const MASTER_DSN = 'localhost|user|password|databasename';
    const DEFAULT_MYSQL_PORT = '3306';

    //GPS Data
    const MASTER_API_KEY = ''; //Instamapper API Key
    const DEVICE_API_KEY = ''; //Instamapper device API Key
    const MPS_TO_MPH = 2.23693629; //meters per second to miles per hour
    const EARTH_RADIUS = 6371; //in km
    const KM_TO_MILES = 0.621371192;
    const METRE_TO_FEET = 3.2808399;
    const KML_LOC = '/home/kml/'; //location to store KML files

    /**
     * General settings
     */
    const START_DATE = '2011-08-24 15:00'; //Origin date from which to start logging information.

}
?>