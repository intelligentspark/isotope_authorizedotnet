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

namespace HBAgency;

use Isotope\Model\Payment;
use Haste\Http\Response\Response;


/**
 * Initialize the system
 */
define('TL_MODE', 'FE');
define('BYPASS_TOKEN_CHECK', true);

require '../../initialize.php';

//Import Auth.net SDK
require_once TL_ROOT . '/system/modules/isotope_authorizedotnet/vendor/anet_php_sdk/AuthorizeNet.php';

/**
 * Class dpmHandler
 *
 * Handle Auth.net response
 * @copyright  HB Agency 2014
 * @author     Blair Winans <bwinans@hbagency.com>
 * @author     Adam Fisher <afisher@hbagency.com>
 */

class dpmHandler extends \Frontend
{

	/**
	 * Initialize the object (do not remove)
	 */
	public function __construct()
	{
		parent::__construct();
		
		// Contao Hooks are not save to be run on the postsale script (e.g. parseFrontendTemplate)
        unset($GLOBALS['TL_HOOKS']);
	}


	/**
	 * Run the controller
	 */
	public function run()
	{
        try 
        {
    		if( count($_POST) && \Input::post('iso_module_id') && \Input::post('iso_redirect_url'))
    		{	
    		
    			$objModule = Payment::findByPk(\Input::post('iso_module_id'));
    			$strUrl = html_entity_decode(\Input::post('iso_redirect_url'));
    			$strMD5Hash = \Encryption::decrypt($objModule->authorize_md5_hash);
    			$strLogin = \Encryption::decrypt($objModule->authorize_login);
    			$response = new \AuthorizeNetSIM( $strLogin, $strMD5Hash);
    			$strTransHash = $response->generateHash();
    			
    			if ($response->isAuthorizeNet())
    			{
    				if ($response->approved)
    	            {
            \System::log($response->account_number, __METHOD__, TL_ERROR);
    	                // Do your processing here.
    	                $redirect_url = $strUrl . '?response_code=1&transaction_id=' . $response->transaction_id . '&transaction_hash=' . urlencode($strTransHash) . '&lastfour=' . urlencode(\Encryption::encrypt($response->account_number));

    	                
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
		
		} catch (\Exception $e) {
            \System::log(
                sprintf('Exception in dpmHandler request in file "%s" on line "%s" with message "%s".',
                    $e->getFile(),
                    $e->getLine(),
                    $e->getMessage()
                ), __METHOD__, TL_ERROR);

            $objResponse = new Response('Internal Server Error', 500);
            $objResponse->send();
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
