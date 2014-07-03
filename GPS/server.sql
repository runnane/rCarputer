DROP TABLE IF EXISTS `gpslog`;
CREATE TABLE IF NOT EXISTS `gpslog` (
  `LogId` int(11) NOT NULL AUTO_INCREMENT,
  `Time` text NOT NULL,
  `Speed` text NOT NULL,
  `Lat` text NOT NULL,
  `Lon` text NOT NULL,
  `Alt` text NOT NULL,
  `Extra` text NOT NULL,
  `Time2` datetime NOT NULL,
  `EPV` text NOT NULL,
  `EPT` text NOT NULL,
  `Track` text NOT NULL,
  `Climb` text NOT NULL,
  `Distance` text NOT NULL,
  PRIMARY KEY (`LogId`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
