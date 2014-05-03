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



/**
 * Misc
 */
$GLOBALS['ISO_LANG']['MSC']['authnet_dpm_msg']				= 'If you are not redirected in 5 seconds, please click here to complete your order';
$GLOBALS['ISO_LANG']['MSC']['authnet_dpm_locked']			= 'Something went wrong during this transaction.  For your safety, this transaction has been locked.  Please try again after two minutes.';
$GLOBALS['ISO_LANG']['MSC']['authnet_dpm_retry']			= 'Something went wrong during this transaction, please try again.  Note: your previous transaction will be voided so you will not be charged twice.';