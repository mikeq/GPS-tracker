<?php
/**
 * Defines the class that imports JSON data from the Instamapper GPS tracking service
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
 * Import data from the Instamapper GPS tracking service
 *
 * @category   GPS
 * @package    InstaMapper
 * @author     Mike Quinn <mikeqtoo@blueyonder.co.uk>
 * @copyright  2011 Mike Quinn
 */
class ImportData
{
    private $dbConn;

    /**
     * __construct
     */
    public function __construct(){
        try{
            $this->dbConn = DatabaseConnection::getInstance(Settings::MASTER_DSN)->getConnection();
        } catch (Exception $e) {
            error_log($e->getMessage . PHP_EOL . $e->getTraceAsString());
        }
    }

    /**
     * Write the JSON data returned from the Instamapper service to the database
     *
     * @param string $jsonData JSON data returned from the instamapper service
     *
     * @access public
     * @return void
     * @throws WriteDataException
     */
    public function write($jsonData){
        $data = json_decode($jsonData);

        if (count($data->positions) === 0) {
            return;
        }

        $insert = "INSERT IGNORE INTO gpsdata (devicekey, datatime, prev_coord, coord, altitude, speed, heading, distance, tracktag)
                    VALUES ";

        $previousTrackTag = '';
        $previousPoint = "";
        $distance = 0;

        foreach ($data->positions as $point) {
            $trackTag = date('Ymd', $point->timestamp);

            if($trackTag !== $previousTrackTag) {
                $previousPoint = '';
                $distance = 0;
            }

            if ($previousPoint != '') {
                $distance = $this->calculateDistance($previousLatitude, $previousLongitude, $point->latitude, $point->longitude);
            } else {
                //check to see if a point on the same tracktag has been saved
                $savedPoint = $this->_getLastTrackCoord($trackTag);

                if ($savedPoint !== false) {
                    $previousPoint = $savedPoint->latitude . " " . $savedPoint->longitude;
                    $distance = $this->calculateDistance($savedPoint->latitude, $savedPoint->longitude, $point->latitude, $point->longitude);
                }
            }

            $insert .= sprintf("('%s','%s', GeomFromText('POINT(%s)'),GeomFromText('POINT(%s)'),%f,%f,%d,%f,'%s'),",
                $point->device_key,
                date('Y-m-d H:i:s', $point->timestamp),
                $previousPoint,
                $point->latitude . " " . $point->longitude,
                $point->altitude,
                $point->speed,
                $point->heading,
                $distance,
                $trackTag
            );

            $previousPoint = $point->latitude . " " . $point->longitude;
            $previousLatitude = $point->latitude;
            $previousLongitude = $point->longitude;
            $previousTrackTag = $trackTag;
        }

        $insert = substr($insert, 0, -1);

        $this->dbConn->query($insert);

        if ($this->dbConn->errno) {
            error_log($this->dbConn->errno . ": " . $this->dbConn->error);
            throw new WriteDataException($this->dbConn->error);
        }
    }

    /**
     * Calculates the distance between 2 coordinates
     *
     * @param float $lat1  Starting Latitude
     * @param float $long1 Starting Longitude
     * @param float $lat2  End Latitude
     * @param float $long2 End Longitude
     *
     * @access private
     * @return float Distance in km
     */
    private function calculateDistance($lat1, $long1, $lat2, $long2)
    {
        $distance = $this->haversine($lat1, $long1, $lat2, $long2);

        return $distance;
    }

    /**
     * Implementation of the Haversine distance calculation
     *
     * @param float $lat1  Starting Latitude
     * @param float $long1 Starting Longitude
     * @param float $lat2  End Latitude
     * @param float $long2 End Longitude
     *
     * @access private
     * @return float Distance in km
     */
    private function haversine($lat1, $long1, $lat2, $long2)
    {
        $lat1Rad  = deg2rad($lat1);
        $lat2Rad  = deg2rad($lat2);
        $long1Rad = deg2rad($long1);
        $long2Rad = deg2rad($long2);
        $dLat     = deg2rad(($lat2 - $lat1));
        $dLon     = deg2rad(($long2 - $long1));
        $a        = (sin($dLat/2) * sin($dLat/2)) + cos($lat1Rad) * cos($lat2Rad) * (sin($dLon/2) * sin($dLon/2));
        $c        = 2 * atan2(sqrt($a), sqrt(1-$a));

        return Settings::EARTH_RADIUS * $c;
    }

    /**
     * Implementation of the Sperical Law distance calculation
     *
     * @param float $lat1  Starting Latitude
     * @param float $long1 Starting Longitude
     * @param float $lat2  End Latitude
     * @param float $long2 End Longitude
     *
     * @access private
     * @return float Distance in km
     */
    private function sphericalLaw($lat1, $long1, $lat2, $long2){
        $lat1Rad  = deg2rad($lat1);
        $lat2Rad  = deg2rad($lat2);
        $long1Rad = deg2rad($long1);
        $long2Rad = deg2rad($long2);
        $d        = acos(sin($lat1Rad) * sin($lat2Rad) + cos($lat1Rad) * cos($lat2Rad) * cos($long2Rad-$long1Rad)) * Settings::EARTH_RADIUS;

        return $d;
    }

    /**
     * Get the last timestamp of the latest record entered into the database
     *
     * @access public
     * @return integer Timestamp of last record or the timestamp of Settings::START_DATE
     * @throws Exception
     */
    public function getLastTimestamp()
    {
        $query = "SELECT UNIX_TIMESTAMP(MAX(datatime)) AS maxtime
                 FROM gpsdata";

        $result = $this->dbConn->query($query);

        if ($this->dbConn->errno) {
            error_log($this->dbConn->errno . ": " . $this->dbConn->error);
            throw new Exception("Could not get last timestamp");
        }

        $record = $result->fetch_object();

        return $record->maxtime == '' ? strtotime(Settings::START_DATE) : $record->maxtime;

    }

    /**
     * Get last recorded coordinate of a particular track
     *
     * @param string $trackTag Track identifier
     *
     * @access private
     * @return object|false Object containing the latitude and longitude of the last point for a track
     * @throws Exception
     */
    private function _getLastTrackCoord($trackTag)
    {
        $point = new stdClass();

        $query = "SELECT X(coord) AS latitude, Y(coord) AS longitude
                 FROM gpsdata
                 WHERE tracktag = '$trackTag'
                 ORDER BY datatime DESC
                 LIMIT 1";

        $result = $this->dbConn->query($query);

        if ($this->dbConn->errno) {
            error_log($this->dbConn->errno . ": " . $this->dbConn->error);
            throw new Exception("Could not get last point for track $trackTag");
        }

        if ($result->num_rows === 0) {
            return false;
        }

        $record = $result->fetch_object();
        $point->latitude = $record->latitude;
        $point->longitude = $record->longitude;

        return $point;
    }
}

/**
 * Custom Exception
 *
 * @category   GPS
 * @package    InstaMapper
 * @author     Mike Quinn <mikeqtoo@blueyonder.co.uk>
 * @copyright  2011 Mike Quinn
 */
class WriteDataException extends Exception{}
?>