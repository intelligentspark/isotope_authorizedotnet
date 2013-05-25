<?php

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
 * @copyright  Isotope eCommerce Workgroup 2009-2011
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @author     Fred Bliss <fred.bliss@intelligentspark.com>
 * @author	   Blair Winans <blair@winanscreative.com>
 * @author     Christian de la Haye <service@delahaye.de>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */


/**
 * Initialize the system
 */
define('TL_MODE', 'FE');
define('BYPASS_TOKEN_CHECK', true);
require('../../initialize.php');

//Import Auth.net SDK
require_once 'anet_php_sdk/AuthorizeNet.php';

/**
 * Class dpmHandler
 *
 * handles direct post method response for Authorize.net
 */
class dpmHandler extends Frontend
{

	/**
	 * Initialize the object (do not remove)
	 */
	public function __construct()
	{
		parent::__construct();
		$this->import('Encryption');
	}


	/**
	 * Run the controller
	 */
	public function run()
	{
		if( count($_POST) && $this->Input->post('iso_module_id') && $this->Input->post('iso_redirect_url'))
		{		
			$objModule = $this->Database->prepare("SELECT * FROM tl_iso_payment_modules WHERE id=?")->limit(1)->execute($this->Input->post('iso_module_id'));
						
			$strUrl = html_entity_decode($this->Input->post('iso_redirect_url'));
			$strMD5Hash = $this->Encryption->decrypt($objModule->authorize_md5_hash);
			$strLogin = $this->Encryption->decrypt($objModule->authorize_login);
			$response = new AuthorizeNetSIM( $strLogin, $strMD5Hash);
			$strTransHash = $response->generateHash();
			
			if ($response->isAuthorizeNet())
			{
				if ($response->approved)
	            {
	                // Do your processing here.
	                $redirect_url = $strUrl . '?response_code=1&transaction_id=' . $response->transaction_id . '&transaction_hash=' . urlencode($strTransHash);
	            }
	            else
	            {
	                // Redirect to error page.
	                $redirect_url = $strUrl . '?response_code='.$response->response_code . '&reason=' . $response->response_reason_text . '&reason_code=' . $response->response_reason_code;
	            }
			}
			else
			{
				 $redirect_url = $strUrl . '?response_code='.$response->response_code . '&reason=' . $response->response_reason_text . '&reason_code=' . $response->response_reason_code;
			}
			
			 // Send the Javascript back to AuthorizeNet, which will redirect user back to your site.
	         echo $this->getRelayResponseSnippet($redirect_url); 
		}
	}
	
	/**
     * A snippet to send to AuthorizeNet to redirect the user back to the
     * merchant's server. Use this on your relay response page.
     *
     * @param string $redirect_url Where to redirect the user.
     *
     * @return string
     */
    protected function getRelayResponseSnippet($redirect_url)
    {
    	$this->loadLanguageFile('default');
    	
        return "<html><head><script language=\"javascript\">
                <!--
                try
                {	
	                window.location=\"{$redirect_url}\";
                }
                catch (err)
                {
                	alert('An error has occurred! ' + err.message);
                }
                //-->
                </script>
                </head><body>
                <a href=\"{$redirect_url}\">" . $GLOBALS['ISO_LANG']['MSC']['authnet_dpm_msg'] . "</a>
                </body></html>";
    }
}


/**
 * Instantiate controller
 */
$objDPM = new dpmHandler();
$objDPM->run();

?>