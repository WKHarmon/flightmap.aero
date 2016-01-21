CREATE TABLE airports_simplified (
	SiteNumber VARCHAR(20) PRIMARY KEY,
	LocationID VARCHAR(4),
	AirportType VARCHAR(13),
	State VARCHAR(2),
	ARPLatitudeS DECIMAL(10, 7),
	ARPLongitudeS DECIMAL(10, 7),
	AirportUse VARCHAR(2),
	AirportStatusCode VARCHAR(2),
	City VARCHAR(64),
	FacilityName tinytext,
	key(AirportType), key(State)
);