<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Winans Creative 2009-2011
 * @author     Blair Winans <blair@winanscreative.com>
 * @author     Adam Fisher <adam@winanscreative.com>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */

/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_iso_payment_modules']['palettes']['authnet_dpm'] = '{type_legend},name,label,type;{note_legend:hide},note;{config_legend},override_formaction,tableless,new_order_status,allowed_cc_types,requireCCV,minimum_total,maximum_total,countries,shipping_modules,product_types;{gateway_legend},authorize_login,authorize_trans_key,authorize_trans_type,authorize_md5_hash;{price_legend:hide},price,tax_class;{lockout_legend:hide},lockout_attempts,lockout_method,lockout_duration,lockout_message;{expert_legend:hide},guests,protected;{enabled_legend},debug,enabled';

/**
 * Avoid saving/displaying cleartext API Keys
 */
$GLOBALS['TL_DCA']['tl_iso_payment_modules']['fields']['authorize_login']['eval']['encrypt'] = true;
$GLOBALS['TL_DCA']['tl_iso_payment_modules']['fields']['authorize_login']['eval']['hideInput'] = true;
$GLOBALS['TL_DCA']['tl_iso_payment_modules']['fields']['authorize_trans_key']['eval']['encrypt'] = true;
$GLOBALS['TL_DCA']['tl_iso_payment_modules']['fields']['authorize_trans_key']['eval']['hideInput'] = true;

$GLOBALS['TL_DCA']['tl_iso_payment_modules']['fields']['authorize_md5_hash'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment_modules']['authorize_md5_hash'],
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('mandatory'=>true, 'encrypt'=>true, 'hideInput'=>true, 'maxlength'=>255)
);

$GLOBALS['TL_DCA']['tl_iso_payment_modules']['fields']['override_formaction'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment_modules']['override_formaction'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_iso_payment_modules']['fields']['tableless'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment_modules']['tableless'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
);

$GLOBALS['TL_DCA']['tl_iso_payment_modules']['fields']['lockout_attempts'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment_modules']['lockout_attempts'],
	'default'				  => '0',
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('maxlength'=>10, 'rgxp'=>'price', 'tl_class'=>'w50'),
);

$GLOBALS['TL_DCA']['tl_iso_payment_modules']['fields']['lockout_method'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment_modules']['lockout_method'],
	'default'                 => 'session',
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('tl_iso_payment_modules_authnetdpm', 'getLockoutMethodOptions'),
	'eval'                    => array('tl_class'=>'w50')
);

$GLOBALS['TL_DCA']['tl_iso_payment_modules']['fields']['lockout_duration'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment_modules']['lockout_duration'],
	'default'				  => '86400',
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('maxlength'=>255, 'rgxp'=>'price', 'tl_class'=>'clr'),
);

$GLOBALS['TL_DCA']['tl_iso_payment_modules']['fields']['lockout_message'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_iso_payment_modules']['lockout_message'],
	'exclude'                 => true,
	'inputType'               => 'textarea',
	'eval'                    => array('rte'=>'tinyMCE', 'decodeEntities'=>true, 'tl_class'=>'clr'),
	'load_callback'			  => array
	(
		array('tl_iso_payment_modules_authnetdpm', 'setDefaultLockoutMessage'),
	),
);


class tl_iso_payment_modules_authnetdpm extends Controller
{

	protected function __construct()
	{
		$this->loadLanguageFile('tl_iso_payment_modules');
		
		parent::__construct();
	}
	
	
	public function getLockoutMethodOptions()
	{
		$arrOptions = array('session'=>$GLOBALS['TL_LANG']['tl_iso_payment_modules']['session'], 'ip'=>$GLOBALS['TL_LANG']['tl_iso_payment_modules']['ip']);
		
		return $arrOptions;
	}
	
	
	public function setDefaultLockoutMessage($varValue, DataContainer $dc)
	{
		if ($varValue == '')
			return $GLOBALS['TL_LANG']['tl_iso_payment_modules']['lockout_default_message'];
		else
			return $varValue;
	}
}


?>
