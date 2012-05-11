DROP DATABASE IF EXISTS dm_gps;
CREATE DATABASE dm_gps;
GRANT ALL PRIVILEGES ON dm_gps.* TO 'gpsuser'@'localhost' IDENTIFIED BY 'gpspassword';
USE dm_gps;

DROP TABLE IF EXISTS gpsdevice;
DROP TABLE IF EXISTS gpsdata;

CREATE TABLE gpsdevice (
    devicekey VARCHAR(30) NOT NULL PRIMARY KEY COMMENT 'As defined for the device on instamapper.com',
    apikey VARCHAR(30) NOT NULL COMMENT 'API Key as defined for this device on instamapper.com',
    devicename VARCHAR(50)
);

CREATE TABLE gpsdata (
    devicekey VARCHAR(30) NOT NULL COMMENT 'Key of device from instamapper.com',
    datatime TIMESTAMP NOT NULL COMMENT 'timestamp when datapoint was logged',
    prev_coord POINT,
    coord POINT NOT NULL COMMENT 'latitude and longitude of logged datapoint',
    altitude FLOAT(3,1) NOT NULL COMMENT 'altitude in meters',
    speed FLOAT(5,2) NOT NULL COMMENT 'speed in meters per second',
    heading SMALLINT NOT NULL COMMENT 'direction travelling in degrees',
    distance FLOAT(10,8) COMMENT 'distance in kilometers',
    tracktag VARCHAR(10) NOT NULL COMMENT 'track id for grouping together data items',
    PRIMARY KEY (devicekey, datatime),
    SPATIAL KEY idx_coord(coord),
    KEY idx_devicekey(devicekey),
    KEY idx_track_time(tracktag, datatime)
); 

--only required if you want to store predetermined mileages for certain days/tracks
CREATE TABLE bikestages (
    tracktag VARCHAR(10) NOT NULL PRIMARY KEY,
    mileage INT(11) NOT NULL DEFAULT 0,
    acc_mileage INT(11) NOT NULL DEFAULT 0
);

INSERT INTO bikestages (tracktag, mileage, acc_mileage)
VALUES
('20110825', 46, 46),
('20110826', 63, 109),
('20110827', 51, 160),
('20110828', 48, 208),
('20110829', 61, 269),
('20110830', 29, 298),
('20110831', 62, 360),
('20110901', 61, 421),
('20110902', 42, 463),
('20110903', 52, 515);