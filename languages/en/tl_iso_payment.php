<?php

/**
 * Isotope eCommerce for Contao Open Source CMS
 *
 * Copyright (C) 2009-2014 terminal42 gmbh & Isotope eCommerce Workgroup
 *
 * @package    Isotope
 * @link       http://isotopeecommerce.org
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */
 

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_iso_payment']['authorize_login'] 		   = array('Authorize.net Login', 'Please enter your Authorize.net Login.');
$GLOBALS['TL_LANG']['tl_iso_payment']['authorize_trans_key'] 	   = array('Authorize.net API Transaction key', 'Please enter your Authorize.net API Transaction Key.');
$GLOBALS['TL_LANG']['tl_iso_payment']['authorize_md5_hash'] 	   = array('Authorize.net md5 Hash', 'Please enter your Authorize.net MD5 Hash to use for secure encryption of data.');
$GLOBALS['TL_LANG']['tl_iso_payment']['authorize_delimeter'] 	   = array('Authorize.net Delimeter', 'Please enter your Authorize.net Code Delimeter (if unsure use a semicolon -- ";").');
$GLOBALS['TL_LANG']['tl_iso_payment']['authorize_trans_type'] 	   = array('Authorize.net API Transaction Type', 'Please select the transaction type (Authorize Only or Authorize and Capture).');
$GLOBALS['TL_LANG']['tl_iso_payment']['authorize_relay_response']  = array('Relay Response?', 'Please check whether or not to use the relay response for Authorize.net.');
$GLOBALS['TL_LANG']['tl_iso_payment']['authorize_email_customer']  = array('Email Customer?', 'Please check whether or not to use the Authorize.net emails in addition to Isotope\'s email function.');
$GLOBALS['TL_LANG']['tl_iso_payment']['override_formaction'] 	   = array('Override Form Action', 'Check this option if this is your only payment method and you want to display the payment form on a review stage. Otherwise the payment form will appear after the final review stage.');
$GLOBALS['TL_LANG']['tl_iso_payment']['tableless'] 				   = array('Tableless form', 'Check this option if you want to use a tableless form.');
$GLOBALS['TL_LANG']['tl_iso_payment']['lockout_attempts']		   = array('Authorization attempts to lockout', 'This is the number of authorizations the user may attempt until they\'re locked out and need to speak to customer service or wait until they\'re unlocked (0 = unlimited).');
$GLOBALS['TL_LANG']['tl_iso_payment']['lockout_method']			   = array('Lockout method', 'This is the method that is used to determine whether a user is locked out or not (using IP address is normally used when there are some suspicious attempts being made often).');
$GLOBALS['TL_LANG']['tl_iso_payment']['lockout_duration']		   = array('Lockout duration', 'This is the amount of time in seconds that the user will be locked out (1 day = 86400 seconds).');
$GLOBALS['TL_LANG']['tl_iso_payment']['lockout_message']		   = array('Lockout message', 'This is the message that will be displayed when a user is locked out.');


/**
 * Misc
 */
$GLOBALS['TL_LANG']['tl_iso_payment']['session']					= 'Session';
$GLOBALS['TL_LANG']['tl_iso_payment']['ip']							= 'IP address';
$GLOBALS['TL_LANG']['tl_iso_payment']['lockout_default_message']	= '<p>We\'re sorry, your user account has been locked due to the number of authorizations you have requested. Please contact customer service or try again later. Thank you.</p>';



/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_iso_payment']['lockout_legend']				= 'Lockout settings';




