<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Isotope_authorizedotnet
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

/**
 * Register PSR-0 namespace
 */
NamespaceClassLoader::add('HBAgency', 'system/modules/isotope_authorizedotnet/library');


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	'dpmHandler'              => 'system/modules/isotope_authorizedotnet/dpmHandler.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	//Lockout message
	'authnet_dpm_lockoutmessage' 	=> 'system/modules/isotope_authorizedotnet/templates/payment',
	
	// Backend interface
	'be_pos_terminal' 				=> 'system/modules/isotope_authorizedotnet/templates/payment',
));
