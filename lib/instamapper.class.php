<?php
    class InstaMapper{
        private $apiKey;
        private $format = 'json';

        public function __construct($apiKey){
            $this->apiKey = $apiKey;
        }

        /**
         * Retrieve the most recent location from instamapper
         *
         */
        public function getMostRecentLocation(){
            $url = sprintf('http://www.instamapper.com/api?action=getPositions&key=%s&format=%s',
                $this->apiKey,
                $this->format
            );

            $content = file_get_contents($url);
            return $content;
        }

        /**
         * Retrieve a number of datapoints after a certain time
         *
         * @param $numberPoints
         * @param $timeStamp
         */
        public function getPoints($numberPoints, $timeStamp=''){
            //if timestamp is blank or omitted just use the current timestamp
            if (!isset($timeStamp) || $timeStamp == '' || $timeStamp == 0){
                $url = sprintf('http://www.instamapper.com/api?action=getPositions&key=%s&num=%d&format=%s',
                    $this->apiKey,
                    $numberPoints,
                    $this->format
                );
            }
            else{
                $url = sprintf('http://www.instamapper.com/api?action=getPositions&key=%s&num=%d&from_ts=%d&format=%s',
                    $this->apiKey,
                    $numberPoints,
                    $timeStamp,
                    $this->format
                );
            }


            $content = file_get_contents($url);
            return $content;
        }
    }
?>