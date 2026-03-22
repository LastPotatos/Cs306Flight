-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 21, 2026 at 09:19 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

-- CODE HERE WAS MODIFIED IN ORDER TO CLEAN UP AND SIMPLIFY THE DATABASE STRUCTURE FOR THIS PROJECT.

--
-- Database: `flight diary`
--

--
-- Table structure for table user
--

CREATE TABLE user (
  username varchar(50) NOT NULL, -- User's name
  email varchar(100) PRIMARY KEY NOT NULL, -- User's email address as identifier.
  passwd varchar(256) NOT NULL -- User's salted password
);


-- --------------------------------------------------------

--
-- Table structure for table airline
--

CREATE TABLE airline (
  aname text NOT NULL, -- Airline's name in English
  codeICAO varchar(3) PRIMARY KEY NOT NULL, -- Airline's 3 letter ICAO code, used for callsign identifiers. (Example: PGT for Pegasus Airlines)
  codeIATA varchar(2) NOT NULL, -- Airline's 2 letter IATA code, used for ticketing and flight numbers. (Example: PC for Pegasus Airlines)
  destinationSize int(11), -- Number of destinations served by the airline. Can be null as it may be unknown.
  hubAirports varchar(100), -- Comma separated list of the airline's hub airports as airport's ICAO codes (Example: LTFJ,LTAI).
);

-- --------------------------------------------------------

--
-- Table structure for table aircraft
--

CREATE TABLE aircraft (
  registration varchar(8) PRIMARY KEY NOT NULL, -- Aircraft's registration number, found on the tail of the aircraft. (Example: TC-JDM)
  airlineICAO varchar(3), -- ICAO code of the airline that owns the aircraft. This is a foreign key referencing the airline table.
  model varchar(50), -- Aircraft's model (Example: B738 for Boeing 737-800)
  seatConfig text, -- Comma seperated list of the number of seats seperated by aisle for each row for each class. (Example: 3-3,2-2)
  capacity int(11) NOT NULL, -- Total number of seats on the aircraft.
  CONSTRAINT airlineOwnsAircraft FOREIGN KEY (airlineICAO) REFERENCES airline (codeICAO) ON DELETE CASCADE ON UPDATE CASCADE
);

-- --------------------------------------------------------

--
-- Table structure for table airport
--

CREATE TABLE airport (
  latitude float(11) NOT NULL, -- Airport's latitude coordinate
  longitude float(11) NOT NULL, -- Airport's longitude coordinate
  codeICAO varchar(4) NOT NULL, -- Airport's 4 letter code, used for planning in flight computers. (Example: LTAF for Adana Şakirpaşa Airport)
  codeIATA varchar(3), -- Airport's 3 letter code found on tickets and baggage tags. (Example: UAB for Adana İncirlik Air Base)
  runways varchar(100) NOT NULL, -- Comma seperated list of runway numbers (Example: 36,35L,35R,34L,34R,18,17L,17R,16L,16R for Istanbul Airport)
  pname varchar(100) NOT NULL, -- Airport's name (Example: İstanbul Sabiha Gökçen)
  city varchar(100) NOT NULL, -- The city the airport is located in (Example: İstanbul)
  country varchar(2) NOT NULL, -- The country the airport is located in as a 2 letter ISO code (Example: TR for Turkey)
  avgAircraftToRunway int(11), -- Average time of taxiing in minutes
  avgGateToAircraftByBus int(11), -- Average time of passenger bus transfer in minutes
);

-- --------------------------------------------------------

--
-- Table structure for table flight
--

CREATE TABLE flight (
  email varchar(100) NOT NULL, -- Email of the user who took the flight.
  aircraftRegistration varchar(8) NOT NULL, -- Aircraft's registration number of this flight, found on the tail of the aircraft. (Example: TC-JDM)
  departedAirport varchar(6) NOT NULL, -- ICAO code of the airport where the flight departed.
  arrivedAirport varchar(6) NOT NULL, -- ICAO code of the airport where the flight arrived.
  flightDate datetime NOT NULL, -- Date of the flight.
  flightNumber varchar(6) NOT NULL, -- The number found on the ticket.
  scheduledDeparture datetime NOT NULL, -- Scheduled departure time according to the airline.
  scheduledArrival datetime NOT NULL, -- Scheduled arrival time according to the airline.
  comments text, -- User's comments about the flight, can include any details.
  actualDeparture datetime NOT NULL, -- Actual departure time according to the user/flight path.
  actualArrival datetime NOT NULL, -- Actual arrival time according to the user/flight path.
  PRIMARY KEY (flightNumber, flightDate),
  CONSTRAINT userLogsFlight FOREIGN KEY (email) REFERENCES user (email) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT flightUsesAircraft FOREIGN KEY (aircraftRegistration) REFERENCES aircraft (registration) ON DELETE CASCADE ON UPDATE CASCADE
);

-- --------------------------------------------------------

--
-- Table structure for table path
--

CREATE TABLE path (
  flightNumber varchar(6) NOT NULL, -- The flight number this path point belongs to.
  epochTimestamp timestamp NOT NULL, -- The timestamp of the path point.
  speed int(11) NOT NULL, -- The speed of the aircraft at this path point in knots (Nautical Miles per hour).
  latitude float(11) NOT NULL, -- The latitude coordinate of the path point.
  longitude float(11) NOT NULL, -- The longitude coordinate of the path point.
  altitude int(11) NOT NULL, -- The altitude of the aircraft at this path point in feet.
  heading float(11) NOT NULL, -- The heading of the aircraft at this path point in degrees.
  PRIMARY KEY (flightNumber,epochTimestamp),
  CONSTRAINT flightFliesOn FOREIGN KEY (flightNumber) REFERENCES flight (flightNumber) ON DELETE CASCADE ON UPDATE CASCADE
);

-- --------------------------------------------------------

--
-- Table structure for table ticket
--

CREATE TABLE ticket (
  email varchar(50) NOT NULL, -- Email of the user who purchased the ticket.
  flightNumber varchar(6) NOT NULL, -- The flight number this ticket is for.
  flightDate datetime NOT NULL, -- Date of the flight.
  seat varchar(3), -- The seat number assigned to the user.
  class varchar(50), -- The class of the ticket.
  addOns varchar(100), -- Comma seperated list of add-ons purchased with the ticket (Example: BDML,XBAG,SEAT,FLEX for a ticket with a pre-ordered meal, extra baggage and seat selection and flexible add-ons)
  price int(11), -- The price paid for the ticket in real form currency.
  currency varchar(50) -- The currency of the price paid for the ticket (Example: USD, EUR, TRY).
  ticketAirlineICAO varchar(3), -- This is the airline that issued the ticket, not necessarily the airline operating the flight
  pointsUsed int(11), -- The price paid for the ticket in the ticket issuer airline's frequent flier points.
  pointsReceived int(11), -- The ticket issuer airline's frequent flier points received from taking the flight for getting award tickets.
  pointsReceivedXP int(11), -- The ticket issuer airline's frequent flier experience received from taking the flight for upgrading in the program.
  PRIMARY KEY (flightNumber,flightDate),
  CONSTRAINT userBuysTicket FOREIGN KEY (email) REFERENCES user (email) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT ticketIsForFlight FOREIGN KEY (flightNumber,flightDate) REFERENCES flight (flightNumber, flightDate) ON DELETE CASCADE ON UPDATE CASCADE
);

-- --------------------------------------------------------
