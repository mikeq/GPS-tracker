<?php
    require_once 'settings.class.php';
    require_once 'db.class.php';

    class ImportData{
        private $dbConn;
        private static $earthRadius = 6371; //earth radius in km

        public function __construct(){
            try{
                $this->dbConn = DatabaseConnection::getInstance(Settings::MASTER_DSN)->getConnection();
            }
            catch(Exception $e){
                error_log($e->getMessage.PHP_EOL.$e->getTraceAsString());
            }
        }

        public function write($jsonData){
            $data = json_decode($jsonData);

            $insert = "INSERT IGNORE INTO gpsdata (devicekey, datatime, prev_coord, coord, altitude, speed, heading, distance)
                        VALUES ";

            $previousPoint = "";
            $distance = 0;

            foreach ($data->positions as $point){
                if ($previousPoint != ''){
                    $distance = $this->calculateDistance($previousLatitude, $previousLongitude, $point->latitude, $point->longitude);
                }

                $insert .= sprintf("('%s','%s', GeomFromText('POINT(%s)'),GeomFromText('POINT(%s)'),%f,%f,%d,%f),",
                    $point->device_key,
                    date('Y-m-d H:i:s',$point->timestamp),
                    $previousPoint,
                    $point->latitude." ".$point->longitude,
                    $point->altitude,
                    $point->speed,
                    $point->heading,
                    $distance
                );


                $previousPoint = $point->latitude." ".$point->longitude;
                $previousLatitude = $point->latitude;
                $previousLongitude = $point->longitude;
            }

            $insert = substr($insert, 0, -1);
            error_log($insert);
            $this->dbConn->query($insert);

            if ($this->dbConn->errno){
                error_log($this->dbConn->errno.": ".$this->dbConn->error);
                throw new WriteDataException($this->dbConn->error);
            }
        }

        private function calculateDistance($lat1, $long1, $lat2, $long2){
            $distance = $this->haversine($lat1, $long1, $lat2, $long2);

            return $distance;
        }

        private function haversine($lat1, $long1, $lat2, $long2){
            $lat1Rad = deg2rad($lat1);
            $lat2Rad = deg2rad($lat2);
            $long1Rad = deg2rad($long1);
            $long2Rad = deg2rad($long2);
            $dLat = deg2rad(($lat2-$lat1));
            $dLon = deg2rad(($long2-$long1));
            $a = (sin($dLat/2)*sin($dLat/2)) + cos($lat1Rad)*cos($lat2Rad)*(sin($dLon/2)*sin($dLon/2));
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));

            return self::$earthRadius * $c;
        }

        private function sphericalLaw($lat1, $long1, $lat2, $long2){
            $lat1Rad = deg2rad($lat1);
            $lat2Rad = deg2rad($lat2);
            $long1Rad = deg2rad($long1);
            $long2Rad = deg2rad($long2);
            $d = acos(sin($lat1Rad)*sin($lat2Rad)+cos($lat1Rad)*cos($lat2Rad)*cos($long2Rad-$long1Rad))*self::$earthRadius;

            return $d;
        }
    }

    class WriteDataException extends Exception{}
?>