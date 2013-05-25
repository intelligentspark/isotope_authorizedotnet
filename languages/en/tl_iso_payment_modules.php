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
$GLOBALS['TL_LANG']['tl_iso_payment_modules']['authorize_md5_hash'] 		= array('Authorize.net md5 Hash', 'Please enter your Authorize.net MD5 Hash to use for secure encryption of data.');

$GLOBALS['TL_LANG']['tl_iso_payment_modules']['override_formaction'] 		= array('Override Form Action', 'Check this option if this is your only payment method and you want to display the payment form on a review stage. Otherwise the payment form will appear after the final review stage.');

$GLOBALS['TL_LANG']['tl_iso_payment_modules']['tableless'] 					= array('Tableless form', 'Check this option if you want to use a tableless form.');

$GLOBALS['TL_LANG']['tl_iso_payment_modules']['lockout_attempts']			= array('Authorization attempts to lockout', 'This is the number of authorizations the user may attempt until they\'re locked out and need to speak to customer service or wait until they\'re unlocked (0 = unlimited).');

$GLOBALS['TL_LANG']['tl_iso_payment_modules']['lockout_method']				= array('Lockout method', 'This is the method that is used to determine whether a user is locked out or not (using IP address is normally used when there are some suspicious attempts being made often).');

$GLOBALS['TL_LANG']['tl_iso_payment_modules']['lockout_duration']			= array('Lockout duration', 'This is the amount of time in seconds that the user will be locked out (1 day = 86400 seconds).');

$GLOBALS['TL_LANG']['tl_iso_payment_modules']['lockout_message']			= array('Lockout message', 'This is the message that will be displayed when a user is locked out.');



/**
 * Misc
 */
$GLOBALS['TL_LANG']['tl_iso_payment_modules']['session']					= 'Session';
$GLOBALS['TL_LANG']['tl_iso_payment_modules']['ip']							= 'IP address';
$GLOBALS['TL_LANG']['tl_iso_payment_modules']['lockout_default_message']	= '<p>We\'re sorry, your user account has been locked due to the number of authorizations you have requested. Please contact customer service or try again later. Thank you.</p>';



/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_iso_payment_modules']['lockout_legend']				= 'Lockout settings';




