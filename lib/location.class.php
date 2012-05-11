<?php
/**
 * Defines a class that interacts with the stored instamapper data
 *
 * PHP version 5
 *
 * This file is part of GPS-Tracker.
 *
 * GPS-Tracker is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * GPS-Tracker is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GPS-Tracker.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category   GPS
 * @package    InstaMapper
 * @author     Mike Quinn <mikeqtoo@blueyonder.co.uk>
 * @copyright  2011 Mike Quinn
 * @license    http://www.gnu.org/licenses/ GNU General Public License
 */
require_once 'settings.class.php';
require_once 'db.class.php';

/**
 * Location class for interfacing with the database of stored instamapper data
 *
 * @category   GPS
 * @package    InstaMapper
 * @author     Mike Quinn <mikeqtoo@blueyonder.co.uk>
 * @copyright  2011 Mike Quinn
 */
class Location
{
    private $_dbConn;

    /**
     * __construct
     */
    public function __construct()
    {
        try{
            $this->_dbConn = DatabaseConnection::getInstance(Settings::MASTER_DSN)->getConnection();
        } catch (Exception $e) {
            error_log($e->getMessage . PHP_EOL . $e->getTraceAsString());
        }

    }

    /**
     * Get total mileage done
     * This a SUM of previous day(s) mileage plus the current days mileage
     *
     * @access public
     * @return integer Total mileage done
     * @throws Exception
     */
    public function getMilesDone()
    {
        $totalMiles = 0;

        //get set miles for days alread complete
        $currentTrackTag = date('Ymd');

        $setMiles = "SELECT SUM(mileage) AS totalmiles FROM bikestages WHERE tracktag < '$currentTrackTag'";

        $result = $this->_dbConn->query($setMiles);

        if ($this->_dbConn->errno) {
            error_log($this->_dbConn->errno . ": " . $this->_dbConn->error);
            throw new Exception($this->_dbConn->error);
        }

        $record = $result->fetch_object();

        if ($record->totalmiles != null) {
            $totalMiles += $record->totalmiles;
        }

        $totalMiles += $this->getTagMiles($currentTrackTag);

        return round($totalMiles);
    }

    /**
     * Get miles done for a particular tag
     *
     * @param string $tag Identifier for a track
     * @return integer Total miles for the track
     * @throws Exception
     */
    public function getTagMiles($tag)
    {
        $currentMileage = "SELECT SUM(distance) * " . Settings::KM_TO_MILES . " AS currentmiles FROM gpsdata WHERE tracktag = '$tag'";

        $result = $this->_dbConn->query($currentMileage);

        if ($this->_dbConn->errno) {
            error_log($this->_dbConn->errno . ": " . $this->_dbConn->error);
            throw new Exception($this->_dbConn->error);
        }

        $record = $result->fetch_object();

        if ($record->currentmiles != null) {
            $totalMiles = $record->currentmiles;
        } else {
            $totalMiles = 0;
        }

        return round($totalMiles);
    }

    /**
     * Get stats for the current (todays) track
     * Including current location, speed, altitude, heading and miles done
     *
     * @access public
     * @return object|false
     * @throws Exception
     */
    public function getLastLocation()
    {
        $currentTrackTag = date('Ymd');
        $query =
            "SELECT X(coord) AS lat, Y(coord) AS lng, ROUND((altitude * " . Settings::METRE_TO_FEET . ")) AS altitude, ROUND((speed * " . Settings::MPS_TO_MPH . ")) AS mph, heading
            FROM gpsdata
            WHERE datatime >= '" . Settings::START_DATE . "'
            ORDER BY datatime DESC
            LIMIT 1";

        $result = $this->_dbConn->query($query);

        if ($this->_dbConn->errno) {
            error_log($this->_dbConn->errno . ": " . $this->_dbConn->error);
            throw new Exception($this->_dbConn->error);
        }

        if ($result->num_rows === 0) {
            return false;
        }

        $returnObj = $result->fetch_object();
        $returnObj->todaysmiles = $this->getTagMiles($currentTrackTag);

        return $returnObj;
    }
}