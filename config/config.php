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
 * Payment methods
 */
\Isotope\Model\Payment::registerModelType('authnet_dpm', 'HBAgency\Model\Payment\AuthNetDPM');
\Isotope\Model\Payment::registerModelType('authnet_aim', 'HBAgency\Model\Payment\AuthNetAIM');
\Isotope\Model\Payment::registerModelType('authnet_cim', 'HBAgency\Model\Payment\AuthNetCIM');

/**
 * Steps that will allow the payment method to continue
 */
$GLOBALS['ISO_CHECKOUT_STEPS_PASS'] = array
(
	'process',
	'complete',
	'review',
);


/**
 * Hooks
 */
$GLOBALS['ISO_HOOKS']['postCheckout'][]						= array('HBAgency\Model\Payment\AuthNetDPM', 'setPaymentData');
$GLOBALS['ISO_HOOKS']['postCheckout'][]						= array('HBAgency\Model\Payment\AuthNetAIM', 'setPaymentData');
