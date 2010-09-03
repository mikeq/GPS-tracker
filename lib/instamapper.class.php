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

        }

        /**
         * Retrieve a number of datapoints after a certain time
         *
         * @param $numberPoints
         * @param $timeStamp
         */
        public function getPoints($numberPoints, $timeStamp){

        }
    }
?>