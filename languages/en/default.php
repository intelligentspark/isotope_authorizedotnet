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
 * Payment modules
 */
$GLOBALS['TL_LANG']['MODEL']['tl_iso_payment.authnet_dpm']				= array('Authorize.Net - DPM', 'Authorize.net Direct POST Method - This method is a fully PCI compliant payment solution.');
$GLOBALS['TL_LANG']['MODEL']['tl_iso_payment.authnet_aim']				= array('Authorize.Net - AIM', 'Authorize.net Advanced Integration Method - This method is used to authorize and charge credit cards using the advanced integration method.');
$GLOBALS['TL_LANG']['MODEL']['tl_iso_payment.authnet_cim']				= array('Authorize.Net - CIM', 'Authorize.net Customer Information Manager - This method is used to remotely store customer CC data at Authorize.net for processing later on.');


/**
 * Misc
 */
$GLOBALS['ISO_LANG']['MSC']['authnet_dpm_msg']				= 'If you are not redirected in 5 seconds, please click here to complete your order';
$GLOBALS['ISO_LANG']['MSC']['authnet_dpm_locked']			= 'Something went wrong during this transaction.  For your safety, this transaction has been locked.  Please try again after two minutes.';
$GLOBALS['ISO_LANG']['MSC']['authnet_dpm_retry']			= 'Something went wrong during this transaction, please try again.  Note: your previous transaction will be voided so you will not be charged twice.';

/**
 * Fields
 */
$GLOBALS['TL_LANG']['MSC']['paymentProfile']		= array('Type', 'Select the type.');
$GLOBALS['TL_LANG']['MSC']['bank_accountType']      = array('Account type', 'Select your account type.');
$GLOBALS['TL_LANG']['MSC']['bank_routingNumber']    = array('Routing Number', 'Enter your bank\'s routing number.');
$GLOBALS['TL_LANG']['MSC']['bank_accountNumber']    = array('Account Number', 'Enter your account number');
$GLOBALS['TL_LANG']['MSC']['bank_nameOnAccount']    = array('Name on Account', 'Enter the full name on the account');
$GLOBALS['TL_LANG']['MSC']['bank_echeckType']       = array('eCheck Type', 'If this is an eCheck, please enter the type.');
$GLOBALS['TL_LANG']['MSC']['bank_bankName']         = array('Bank name', 'Enter the full name of your bank.');


/**
 * Reference
 */
$GLOBALS['TL_LANG']['MSC']['paymentProfileTypes']['cc']						= 'Credit Card';
$GLOBALS['TL_LANG']['MSC']['paymentProfileTypes']['bank']					= 'Bank account';
$GLOBALS['TL_LANG']['MSC']['accountTypes']['paymentProfileTypes']['cc']     = 'Credit Card';
$GLOBALS['TL_LANG']['MSC']['accountTypes']['paymentProfileTypes']['bank']   = 'EFT/Bank Account';
$GLOBALS['TL_LANG']['MSC']['accountTypes']['checking']              		= 'Checking Account';
$GLOBALS['TL_LANG']['MSC']['accountTypes']['savings']               		= 'Savings Account';
$GLOBALS['TL_LANG']['MSC']['accountTypes']['businessChecking']      		= 'Business Checking';