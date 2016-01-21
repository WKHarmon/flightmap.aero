CREATE TABLE `airports_simplified` (
  `SiteNumber` varchar(20) NOT NULL,
  `LocationID` varchar(4) DEFAULT NULL,
  `AirportType` varchar(13) DEFAULT NULL,
  `State` varchar(2) DEFAULT NULL,
  `ARPLatitudeS` decimal(10,7) DEFAULT NULL,
  `ARPLongitudeS` decimal(10,7) DEFAULT NULL,
  `AirportUse` varchar(2) DEFAULT NULL,
  `AirportStatusCode` varchar(2) DEFAULT NULL,
  `City` varchar(64) DEFAULT NULL,
  `FacilityName` tinytext,
  PRIMARY KEY (`SiteNumber`),
  KEY `AirportType` (`AirportType`),
  KEY `State` (`State`)
);

CREATE TABLE `userdata` (
  `checkin` varchar(256) NOT NULL DEFAULT '0',
  `userid` int(10) unsigned NOT NULL,
  `airportid` varchar(12) NOT NULL,
  `time` int(10) NOT NULL DEFAULT '0',
  `visited` int(11) NOT NULL,
  `notes` text NOT NULL,
  `photo` varchar(150) NOT NULL,
  `thumbnail` varchar(150) NOT NULL,
  PRIMARY KEY (`checkin`,`userid`,`airportid`),
  KEY `visited` (`visited`),
  KEY `userid` (`userid`),
  KEY `userid_2` (`userid`,`airportid`)
);

CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(256) NOT NULL,
  `password` char(32) NOT NULL,
  `startlat` double NOT NULL DEFAULT '37.511855',
  `startlong` double NOT NULL DEFAULT '-122.2495236',
  `title` varchar(256) NOT NULL DEFAULT 'My Flight Log',
  `foursquare` varchar(256) NOT NULL,
  `last_check` int(10) NOT NULL,
  `flickr_token` varchar(256) NOT NULL,
  `flickr_user` varchar(30) NOT NULL,
  `keyword` varchar(30) NOT NULL DEFAULT '#FlightLog',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `flickr_user` (`flickr_user`),
  KEY `foursquare` (`foursquare`)
);

