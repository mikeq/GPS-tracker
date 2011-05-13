<?php
/**
 * Defines the class that acts as a wrapper around the Instamapper GPS tracking service
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

/**
 * Wrapper around the Instamapper GPS tracking service
 *
 * @category   GPS
 * @package    InstaMapper
 * @author     Mike Quinn <mikeqtoo@blueyonder.co.uk>
 * @copyright  2011 Mike Quinn
 */
class InstaMapper
{
    /**
     * Instamapper API Key
     *
     * @var string
     */
    private $_apiKey;

    /**
     * Format to return the location data
     * If format is not sent then the service returns CVS format
     *
     * @var string
     */
    private $_format = 'json';

    /**
     * __construct
     *
     * @param string $apiKey Corresponds with your API key on instamapper.com
     *
     * @access public
     */
    public function __construct($apiKey)
    {
        $this->_apiKey = $apiKey;
    }

    /**
     * Retrieve the most recent location from instamapper
     *
     * @access public
     *
     * @return string Content from the instamapper page call
     */
    public function getMostRecentLocation()
    {
        $url = sprintf(
            'http://www.instamapper.com/api?action=getPositions&key=%s&format=%s',
            $this->_apiKey,
            $this->_format
        );

        $content = file_get_contents($url);
        return $content;
    }

    /**
     * Retrieve a number of datapoints after a certain time
     *
     * @param integer $numberPoints How many location points to retrieve
     * @param integer $timeStamp    Start from unix timestamp to get data
     *
     * @access public
     *
     * @return string Content from the instamapper page call
     */
    public function getPoints($numberPoints, $timeStamp='')
    {
        //if timestamp is blank or omitted just use the current timestamp
        if (!isset($timeStamp) || $timeStamp == '' || $timeStamp == 0) {
            $url = sprintf(
                'http://www.instamapper.com/api?action=getPositions&key=%s&num=%d&format=%s',
                $this->_apiKey,
                $numberPoints,
                $this->_format
            );
        } else {
            $url = sprintf(
                'http://www.instamapper.com/api?action=getPositions&key=%s&num=%d&from_ts=%d&format=%s',
                $this->_apiKey,
                $numberPoints,
                $timeStamp,
                $this->_format
            );
        }


        $content = file_get_contents($url);
        return $content;
    }
}
?>