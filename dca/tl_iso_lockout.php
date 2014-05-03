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
 * Table tl_iso_lockout
 */
$GLOBALS['TL_DCA']['tl_iso_lockout'] = array
(
    'config' => array
    (
        'sql' => array
        (
            'keys' => array
            (
                'id'     => 'primary',
                'pid'    => 'index',
            )
        ),
    ),

    // Fields
    'fields' => array
    (

        'id' => array
        (
            'sql'                   =>  "int(10) unsigned NOT NULL auto_increment",
        ),
        'pid' => array
        (
            'foreignKey'            => 'tl_iso_payment.name',
            'sql'                   => "int(10) unsigned NOT NULL default '0'",
            'relation'              => array('type'=>'hasOne', 'load'=>'lazy'),
        ),
        'tstamp' => array
        (
            'sql'                   => "int(10) unsigned NOT NULL default '0'",
        ),
        'lockout_method' => array
        (
            'sql'                   => "varchar(255) NOT NULL default ''",
        ),
        'ip' => array
        (
            'sql'                   => "varchar(255) NOT NULL default ''",
        ),
        'unlock_tstamp' => array
        (
            'sql'                   => "int(10) unsigned NOT NULL default '0'",
        ),
    ),
);