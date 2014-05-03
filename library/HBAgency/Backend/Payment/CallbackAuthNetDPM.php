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

namespace HBAgency\Backend\Payment;


class CallbackAuthNetDPM extends \Backend
{

	protected function __construct()
	{
		$this->loadLanguageFile('tl_iso_payment');
		
		parent::__construct();
	}
	
	
	public function getLockoutMethodOptions()
	{
		$arrOptions = array('session'=>$GLOBALS['TL_LANG']['tl_iso_payment']['session'], 'ip'=>$GLOBALS['TL_LANG']['tl_iso_payment']['ip']);
		
		return $arrOptions;
	}
	
	
	public function setDefaultLockoutMessage($varValue, \DataContainer $dc)
	{
		if ($varValue == '')
			return $GLOBALS['TL_LANG']['tl_iso_payment']['lockout_default_message'];
		else
			return $varValue;
	}
	
}
