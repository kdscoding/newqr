CREATE TABLE IF NOT EXISTS `data_label_add` (
  `NO_URUT` int(11) NOT NULL,
  `UPLOAD_VERSION` varchar(50) NOT NULL,
  `ID` varchar(100) NOT NULL,
  `PO` varchar(100) NOT NULL,
  `ITEM` varchar(255) NOT NULL,
  `COUNTRY` varchar(100) NOT NULL,
  `BUILDING` varchar(50) NOT NULL,
  `CELL` varchar(50) NOT NULL,
  `QTY` varchar(50) NOT NULL,
  `PRIORITY` varchar(255) NOT NULL,
  `PACKING_LIST` varchar(255) NOT NULL,
  `TAKEN_BY` varchar(255) NOT NULL,
  `SAP` varchar(100) NOT NULL,
  PRIMARY KEY (`NO_URUT`,`UPLOAD_VERSION`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
