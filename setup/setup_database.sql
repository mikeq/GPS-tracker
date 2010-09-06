drop database if exists dm_gps;
create database dm_gps;
grant all privileges on dm_gps.* to 'gpsuser'@'localhost' identified by 'gpspassword';
use dm_gps;

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
    distance FLOAT(10,8),
    tracktag SMALLINT COMMENT 'track id for grouping together data items',
    PRIMARY KEY (devicekey, datatime),
    SPATIAL KEY idx_coord(coord),
    KEY idx_devicekey(devicekey)    
); 