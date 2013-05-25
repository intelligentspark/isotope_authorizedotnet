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
 * Payment modules
 */
$GLOBALS['ISO_LANG']['PAY']['authnet_dpm']				= array('Authorize.Net - DPM', 'Authorize.net Direct POST Method - This method is a fully PCI compliant payment solution.');



/**
 * Misc
 */
$GLOBALS['ISO_LANG']['MSC']['authnet_dpm_msg']				= 'If you are not redirected in 5 seconds, please click here to complete your order';
$GLOBALS['ISO_LANG']['MSC']['authnet_dpm_locked']			= 'Something went wrong during this transaction.  For your safety, this transaction has been locked.  Please try again after two minutes.';
$GLOBALS['ISO_LANG']['MSC']['authnet_dpm_retry']			= 'Something went wrong during this transaction, please try again.  Note: your previous transaction will be voided so you will not be charged twice.';




?>