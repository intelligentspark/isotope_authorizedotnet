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
$GLOBALS['TL_DCA']['tl_iso_payment']['palettes']['authnet_dpm'] = '{type_legend},name,label,type;{note_legend:hide},note;{config_legend},override_formaction,tableless,new_order_status,allowed_cc_types,requireCCV,minimum_total,maximum_total,countries,shipping_modules,product_types;{gateway_legend},authorize_login,authorize_trans_key,authorize_trans_type,authorize_md5_hash;{price_legend:hide},price,tax_class;{lockout_legend:hide},lockout_attempts,lockout_method,lockout_duration,lockout_message;{expert_legend:hide},guests,protected;{enabled_legend},debug,enabled';


$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['authorize_login'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment']['authorize_login'],
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('mandatory'=>true, 'encrypt'=>true, 'hideInput'=>true, 'maxlength'=>255),
	'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['authorize_trans_key'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment']['authorize_trans_key'],
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('mandatory'=>true, 'encrypt'=>true, 'hideInput'=>true, 'maxlength'=>255),
	'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['authorize_delimeter'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment']['authorize_delimeter'],
	'default'                 => ';',
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('mandatory'=>true, 'maxlength'=>4),
	'sql'                     => "varchar(4) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['authorize_trans_type'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment']['authorize_trans_type'],
	'exclude'                 => true,
	'inputType'               => 'select',
	'options'                 => array('AUTH_ONLY', 'AUTH_CAPTURE'),
	'reference'               => &$GLOBALS['TL_LANG']['tl_iso_payment'],
	'eval'                    => array('mandatory'=>true),
	'sql'                     => "varchar(32) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['authorize_md5_hash'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment']['authorize_md5_hash'],
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('mandatory'=>true, 'encrypt'=>true, 'hideInput'=>true, 'maxlength'=>255),
	'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['authorize_relay_response'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment']['authorize_relay_response'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['authorize_email_customer'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment']['authorize_email_customer'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['override_formaction'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment']['override_formaction'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['tableless'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment']['tableless'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['lockout_attempts'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment']['lockout_attempts'],
	'default'				  => '0',
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('maxlength'=>10, 'rgxp'=>'price', 'tl_class'=>'w50'),
	'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['lockout_method'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment']['lockout_method'],
	'default'                 => 'session',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('\HBAgency\Backend\Payment\CallbackAuthNetDPM', 'getLockoutMethodOptions'),
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['lockout_duration'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment']['lockout_duration'],
	'default'				  => '86400',
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('maxlength'=>255, 'rgxp'=>'price', 'tl_class'=>'clr'),
	'sql'                     => "varchar(255) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_iso_payment']['fields']['lockout_message'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment']['lockout_message'],
	'exclude'                 => true,
	'inputType'               => 'textarea',
	'eval'                    => array('rte'=>'tinyMCE', 'decodeEntities'=>true, 'tl_class'=>'clr'),
	'sql'                     => "text NULL",
	'load_callback'			  => array
	(
		array('\HBAgency\Backend\Payment\CallbackAuthNetDPM', 'setDefaultLockoutMessage'),
	),
);
