<?php
/**
 * Defines the class that generates a KML file for a particular date
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
require_once 'db.class.php';
require_once 'settings.class.php';

/**
 * Generate a KML for a particular date(track)
 *
 * @category   GPS
 * @package    InstaMapper
 * @author     Mike Quinn <mikeqtoo@blueyonder.co.uk>
 * @copyright  2011 Mike Quinn
 */
class KmlLibrary
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
     * Get stored data for a track
     *
     * @param string $trackTag Identifier for a track
     *
     * @access private
     * @return object|false MySQL result
     * @throws Exception
     */
    private function _getData($trackTag)
    {
        $query = "SELECT datatime, X(coord) AS lat, Y(coord) AS lng, speed, heading, distance FROM gpsdata WHERE tracktag = '$trackTag' ORDER BY datatime ASC";

        $result = $this->_dbConn->query($query);

        if ($this->_dbConn->errno) {
            error_log($query);
            error_log($this->_dbConn->errno . ": " . $this->_dbConn->error);
            throw new Exception("could not get data for tracktag: $trackTag");
        }

        if ($result->num_rows === 0) {
            return false;
        }

        return $result;
    }


    /**
     * Get stored coordinates for a track and return as a string
     *
     * @param string $trackTag Identifier for a track
     *
     * @access private
     * @return string|false String of coordinates (lng,lat)
     * @throws Exception
     */
    private function _getCoordinatesAsString($trackTag)
    {
        $data = $this->_getData($trackTag);

        if ($data === false) {
            return false;
        }

        $coordArray = array();

        while ($record = $data->fetch_object()) {
            $coordArray[] = "{$record->lng}, {$record->lat}";
        }

        return implode(" ", $coordArray);
    }

    /**
     * Generate a KML file
     *
     * @param string $date Date for which to generate KML file for
     *
     * @access public
     * @return void
     * @throws Exception
     */
    public function write($date)
    {
        $tag = date('Ymd', strtotime($date));
        $dom = new DOMDocument('1.0', 'UTF-8');
        $node = $dom->createElementNS('http://earth.google.com/kml/2.1', 'kml');
        $parNode = $dom->appendChild($node);

        //Create a Folder element and append it to the KML element
        $fnode = $dom->createElement('Folder');
        $folderNode = $parNode->appendChild($fnode);

        //Create a Placemark and append it to the document
        $node = $dom->createElement('Placemark');
        $placeNode = $folderNode->appendChild($node);

        //Create an id attribute and assign it the value of id column
        $placeNode->setAttribute('id', $tag);

        $coordString = $this->_getCoordinatesAsString($tag);

        if ($coordString === false) {
            return false;
        }

        $nameNode = $dom->createElement('name', "Path ($tag)");
        $placeNode->appendChild($nameNode);
        $descNode= $dom->createElement('description', 'This is track done on ' . date('d/m/Y', strtotime($date)));
        $placeNode->appendChild($descNode);

        //Create a LineString element
        $lineNode = $dom->createElement('LineString');
        $placeNode->appendChild($lineNode);

        $coorNode = $dom->createElement('coordinates', $coordString);
        $lineNode->appendChild($coorNode);
        $kmlOutput = $dom->saveXML();

        $f = fopen(Settings::KML_LOC . $tag . ".kml", 'w');
        fwrite($f, $kmlOutput);
        fclose($f);
    }
}
?>