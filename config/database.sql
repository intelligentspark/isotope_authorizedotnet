-- **********************************************************
-- *                                                        *
-- * IMPORTANT NOTE                                         *
-- *                                                        *
-- * Do not import this file manually but use the Contao    *
-- * install tool to create and maintain database tables!   *
-- *                                                        *
-- **********************************************************


-- --------------------------------------------------------

--
-- Table `tl_iso_payment_modules`
--

CREATE TABLE `tl_iso_payment_modules` (
  `authorize_md5_hash` varchar(255) NOT NULL default '',
  `override_formaction` char(1) NOT NULL default '',
  `tableless` char(1) NOT NULL default '',
  `lockout_attempts` int(10) unsigned NOT NULL default '0',
  `lockout_method` varchar(255) NOT NULL default '',
  `lockout_duration` varchar(255) NOT NULL default '',
  `lockout_message` text NULL,
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


-- --------------------------------------------------------


--
-- Table `tl_iso_lockouts`
--

CREATE TABLE `tl_iso_lockouts` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `pid` int(10) unsigned NOT NULL default '0',
  `tstamp` int(10) unsigned NOT NULL default '0',
  `lockout_method` varchar(255) NOT NULL default '',
  `ip` varchar(255) NOT NULL default '',
  `unlock_tstamp` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;