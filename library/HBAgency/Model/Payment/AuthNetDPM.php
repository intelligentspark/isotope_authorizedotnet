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
 
namespace HBAgency\Model\Payment;

use Isotope\Model\Payment;
use Haste\Http\Response\Response;
use Isotope\Interfaces\IsotopePayment;
use Isotope\Interfaces\IsotopeProductCollection;
use Isotope\Isotope;
use Isotope\Model\Product;
use Isotope\Model\OrderStatus;
use Isotope\Model\ProductCollection\Order;
use Isotope\Module\OrderDetails as ModuleIsotopeOrderDetails;


class AuthNetDPM extends Payment
{

	const LIVE_URL = 'https://secure.authorize.net/gateway/transact.dll';
    const SANDBOX_URL = 'https://test.authorize.net/gateway/transact.dll';
	
	/**
	 * Authorize.Net's duplicate transaction lockout time (must be > 2 minutes... account for lag time)
	 *
	 * @access protected
	 * @var integer
	 */
	protected $intAuthNetLockTime = 140;
	
	/**
	 * Cart field - could vary according to collection type
	 *
	 * @access protected
	 * @var string
	 */
	protected $strCartField = 'cart_id';
	
	/**
	 * DPM handler file path (can be overridden in child classes)
	 *
	 * @access protected
	 * @var string
	 */
	protected $strDpmHandlerPath = 'system/modules/isotope_authorizedotnet/dpmHandler.php';
	
	/**
	 * API Login ID
	 *
	 * @access protected
	 * @var string
	 */
	protected $strApiLoginId = '';
	
	/**
	 * Transaction Key
	 *
	 * @access protected
	 * @var string
	 */
	protected $strTransKey = '';
	
	/**
	 * Status
	 *
	 * @access protected
	 * @var string
	 */
	protected $strStatus;

	/**
	 * Reason
	 *
	 * @access protected
	 * @var string
	 */
	protected $strReason;

	/**
	 * Transaction ID
	 *
	 * @access protected
	 * @var string
	 */
	protected $strTransactionID;
	
	
	/**
	 * Import libraries and initialize some variables
	 */
	public function __construct(\Database\Result $objResult = null)
	{
		parent::__construct($objResult);
				
        //Decrypt the login/key values
        $this->strApiLoginId 	= \Encryption::decrypt($this->authorize_login);
        $this->strTransKey 		= \Encryption::decrypt($this->authorize_trans_key);
        
    	if (!$this->cartUpdated() && \Input::get('response_code') == '3' && \Input::get('reason_code') == '11')
    	{
    		// We have a "duplicate transaction" response from Authorize.Net, save the timestamp so we know when we got locked out
    		$_SESSION['ISOTOPE']['LAST_ATTEMPT_TSTAMP'] = time();
    	}
		
	}


	/**
	 * Process payment on confirmation page.
	 *
	 * @access public
	 * @return mixed
	 */
	public function processPayment(IsotopeProductCollection $objOrder, \Module $objModule)
	{
		//We have already done the Authorization - go to Complete step
		return true;
	}
	
	
	/**
	 * Generate payment authorization form and AUTH or CAPTURE
	 *
	 * @access 	public
	 * @param 	object
	 * @return	mixed
	 */
	public function checkoutForm(IsotopeProductCollection $objOrder, \Module $objModule)
	{
		if ($this->override_formaction) {	
			return false;	
		}
				
		$blnCapture = ($this->authorize_trans_type == 'AUTH_CAPTURE' ? true : false);
		
		// Get CC Form
		$strCCForm = $this->getCreditCardForm($blnCapture);
		
		// Set form action to the current URL if the user is locked out
		$strCurrent = htmlentities($this->removeGetParams(\Environment::get('base') . \Environment::get('request')));
		$strAction = $this->debug ? self::SANDBOX_URL : (($this->isUserLockedOut() || $this->isAuthNetLocked()) ? $strCurrent : self::LIVE_URL);
		$strFormStart = "\n" . '<form action="'.$strAction.'" id="iso_mod_checkout_payment" method="post" enctype="application/x-www-form-urlencoded">' . "\n";
		
		$strBuffer = $strFormStart . $strCCForm;
		
		if (!$this->tableless) {
        	$strBuffer .= '<table class="ccform">' . "\n";
        }
		
		$objWidget = new FormSubmit(array('slabel'=>'Order'));
		$objWidget->tableless = $this->tableless;
		$strBuffer .= "\n" . $objWidget->parse();
		
		if (!$this->tableless) {
        	$strBuffer .= '</table>' . "\n";
        }
		
		$strBuffer .= "\n" . '</form>' . "\n";
		
						
		// Check for response from Authorize.Net
		if (strlen(\Input::get('response_code'))) {
		
			$this->setOrderPaymentData($objOrder);
			
			// FORM_SUBMIT will be empty if it's coming from Authorize.Net
	    	if (!strlen(\Input::post('FORM_SUBMIT'))) {
	    	
	    		if (\Input::get('response_code') == '1') {
	    		
	    			if ($this->isHashValid()) {
						return false;	// Valid submission
	    			}
	    			else {
	    				$this->handleFailure();
	    			}
				}
    			else {
    				$this->handleFailure();
    			}
	    	}
			
		}
		
		return '<h2>' . $this->label . '</h2>'. $strBuffer;
	}
	
	
	/**
	 * Generate payment authorization form
	 * NOTE:  Will always AUTH_ONLY at this step for PCI Compliance. Capture will take place at process step.
	 *
	 * @access	public
	 * @param	object
	 * @return	string
	 */
	public function paymentForm($objModule)
	{
		if ($this->override_formaction)
		{		
			$strStep = \Haste\Input\Input::getAutoItem('step');
			$arrSteps = is_array($GLOBALS['ISO_CHECKOUT_STEPS_PASS']) ? $GLOBALS['ISO_CHECKOUT_STEPS_PASS'] : array();
						
			$objModule->doNotSubmit = (!$this->cartUpdated() && !$this->isUserLockedOut() && !$this->isAuthNetLocked() && in_array($strStep, $arrSteps)) ? false : true;
										
			// Check for response from Authorize.Net
			if (strlen(\Input::get('response_code')))
			{			
				// FORM_SUBMIT will be empty if it's coming from Authorize.Net
		    	if (!isset($_POST['FORM_SUBMIT']))
		    	{
		    		if (\Input::get('response_code') == '1')
		    		{
		    			if ($this->isHashValid())
		    			{
							$objModule->doNotSubmit = false;
							\Input::setPost('FORM_SUBMIT', $objModule->getFormId());
							
					   		// Provide method for custom actions on successful response from Auth.Net
							if (isset($GLOBALS['ISO_HOOKS']['authnetdpm_success']) && is_array($GLOBALS['ISO_HOOKS']['authnetdpm_success']))
							{
								foreach ($GLOBALS['ISO_HOOKS']['authnetdpm_success'] as $callback)
								{			
		                            $objCallback = \System::importStatic($callback[0]);
		                            $objCallback->$callback[1]($objModule, $this->objCart, $this);
								}
							}
		    			}
		    			else
		    			{
		    				$this->handleFailure();
		    			}
					}
	    			else
	    			{
	    				$this->handleFailure();
	    			}
		    	}
			}

			$blnCapture = $this->authorize_trans_type == 'AUTH_CAPTURE' ? true : false;
			
			// Get CC Form
			$strBuffer = $this->getCreditCardForm($blnCapture);
	    	
	    	// Set any payment data we might have received
			$this->setOrderPaymentData();
			
			// Set new checkout form action
			$strCurrent = htmlentities($this->removeGetParams(\Environment::get('base') . \Environment::get('request')));
			$objModule->Template->action = $this->debug ? self::SANDBOX_URL : (($this->isUserLockedOut() || $this->isAuthNetLocked()) ? $strCurrent : self::LIVE_URL);
			
			return '<h2>' . $this->label . '</h2>'. $strBuffer;
		}
		
		return '';
	}


	/**
	 * Store response data to the Order
	 *
	 * @access public
	 * @return void
	 */
	public function setOrderPaymentData($objOrder=null)
	{
		if (strlen(\Input::get('response_code')) == 0)
			return;
		
		$blnOrderArg = $objOrder ? true : false;
		$objOrder = $objOrder ?: Isotope::getCart();
		
		$arrResponses = array();
		$arrPaymentInfo = array();

		//grab any existing payment data.  If this is an order where a prior auth was made and a new total greater than the original exists,
		//we need to re-auth.  Otherwise we simply use the old auth with the <= order total.
		$arrOrderPaymentData = deserialize($objOrder->payment_data, true);
		
		// Get response data
		$blnResponse = strlen(\Input::get('response_code')) ? true : false;
		
		// Build the payment data array
		if ($blnResponse)
		{
			$strCode			= \Input::get('response_code');
			$strTranId			= \Input::get('transaction_id');
			$strReason			= htmlentities(\Input::get('reason'));
			$strReasonCode 		= \Input::get('reason_code');
			$strAmount			= sprintf("%1\$.2f", $objOrder->getTotal() );
			
			list($arrResponses['response_code'], $this->strStatus) = array($strCode, $strCode);
			
			$arrResponses['transaction_amount'] = $strAmount;
			
			if (strlen($strReason))
			{
				list($arrResponses['reason'], $this->strReason) = array($strReason, $strReason);
			}
			
			if (strlen($strTranId))
			{
				$arrResponses['transaction_id'] = $strTranId;
			}
			
			if (!isset($arrOrderPaymentData['original_auth_amt']))
			{
				$arrOrderPaymentData['original_auth_amt'] = Isotope::roundPrice( $objOrder->getTotal() );
			}
		}
		
		//Update payment data AKA Response Data.
		$arrPaymentInfo = (count($arrOrderPaymentData)) ? array_merge($arrOrderPaymentData, $arrResponses) : $arrResponses;

		$objOrder->payment_data = serialize($arrPaymentInfo);
		$objOrder->save();
		
		if ($blnOrderArg)
		{
			\Database::getInstance()->prepare("UPDATE tl_iso_product_collection %s WHERE id=?")
									->set(array('payment_data'=>serialize($arrPaymentInfo)))
									->executeUncached($objOrder->id);
		}
		
		$_SESSION['CHECKOUT_DATA']['authNetDpm']['payment_data'] = $arrPaymentInfo;

		//unlock the payment submit
		$_SESSION['CHECKOUT_DATA']['payment']['request_lockout'] = false;
	}
	
	
	
	public function setPaymentData(&$objOrder, $arrTokens)
	{
		if ($_SESSION['CHECKOUT_DATA']['authNetDpm']['payment_data'])
		{
			\System::log('Storing payment data for Order ID ' . $objOrder->id, __METHOD__, TL_GENERAL);
			
			\Database::getInstance()->prepare("UPDATE tl_iso_product_collection %s WHERE id=?")
									->set(array('payment_data'=>serialize($_SESSION['CHECKOUT_DATA']['authNetDpm']['payment_data'])))
									->executeUncached($objOrder->id);
			
			unset($_SESSION['CHECKOUT_DATA']['authNetDpm']['payment_data']);
		}
	}
    
    
    
    /**
     * Generate a form for use in a Direct Post implementation.
     *
	 * @access	protected
     * @param	boolean
     * @return	string
     */
    protected function getCreditCardForm($blnCapture=false)
    {
    	if ($this->isUserLockedOut())
        {
			$objTemplate = new \FrontendTemplate('authnet_dpm_lockoutmessage');
			$objTemplate->message = strlen(strval($this->lockout_message)) ? $this->lockout_message : $GLOBALS['TL_LANG']['tl_iso_payment_modules']['lockout_default_message'];
			return $objTemplate->parse();
        }
        
    	// Check Authorize.Net's prior transaction lockout period, also display this 
    	// instead of the form if the submission was successful, this won't display 
    	// unless there was an issue continuing to the order details screen
        if ($this->isAuthNetLocked())
        {
	        // Check to see if we need to void the previous transaction
	        if ($this->needsVoid())
	        {
	        	$this->voidLastTransaction();
	        }
	        
			$objTemplate = new \FrontendTemplate('mod_message');
			$objTemplate->type = 'error';
			$objTemplate->message = $GLOBALS['ISO_LANG']['MSC']['authnet_dpm_locked'];
			return $objTemplate->parse();
        }
        
        $time = time();
        $objCart = Isotope::getCart();
        
        $strBuffer = (\Input::get('response_code') == '1' ? 
        				'<p class="error message">' . $GLOBALS['TL_LANG']['MSC']['authnet_dpm_locked'] . '</p>' :
        				 '');
        
        // Add script
        $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/isotope_authorizedotnet/assets/authnet_formhandler.js';
        
        // Check to see if we need to void the previous transaction
        if ($this->needsVoid())
        {
        	$this->voidLastTransaction();
        }
        
        $relay_response_url = \Environment::get('base') . $this->strDpmHandlerPath;
		$amount = $objCart->getTotal();
		
        $fp_sequence = $objCart->id;
        $fp = \AuthorizeNetSIM_Form::getFingerprint( $this->strApiLoginId, 
        											 $this->strTransKey, 
        											 $amount, 
        											 $fp_sequence, 
        											 $time);
        $sim = new \AuthorizeNetSIM_Form(
            array(
            'x_amount'        => $amount,
            'x_fp_sequence'   => $fp_sequence,
            'x_fp_hash'       => $fp,
            'x_fp_timestamp'  => $time,
            'x_relay_response'=> "TRUE",
            'x_relay_url'     => $relay_response_url,
            'x_login'         => $this->strApiLoginId,
            'x_type'		  => $blnCapture ? 'AUTH_CAPTURE' : 'AUTH_ONLY'
            )
        );
        $strBuffer .= $sim->getHiddenFieldString();
        
        if (!$this->tableless)
        {
        	$strBuffer .= '<table class="ccform">' . "\n";
        }
        
        // Get credit card types
        $arrCCFinal = array();
       	$arrCCTypes = deserialize($this->allowed_cc_types);
       	
       	foreach ($arrCCTypes as $strCCType)
       	{
	       	$arrCCFinal[strtoupper(substr($strCCType, 0, 1))] = $GLOBALS['TL_LANG']['CCT'][$strCCType];
       	}

		$intStartYear = (integer)date('Y', time()); //2-digit year

		//Build years array - Going forward 14 years
		for ($i = 0; $i <= 14; $i++)
		{
			$arrYears[] = (string)$intStartYear+$i;
		}
		
		// Build the redirect URL and remove the GET params if this has already been submitted.
		global $objPage;
		$strIsoRedirectUrl = \Environment::get('base') . (\Environment::get('isAjaxRequest') ? $objPage->alias.($GLOBALS['TL_CONFIG']['useAutoItem'] ? '/' : '/step/').\Input::get('step').'.html' : \Environment::get('request'));
		$strIsoRedirectUrl = htmlentities($this->removeGetParams($strIsoRedirectUrl));
		
		// Get billing data to include on form
		$objBillingAddress =  $objCart->getBillingAddress();

		//Build form fields
		$arrFields = array
		(
			'x_card_num'	=> array
			(
				'label'				=> &$GLOBALS['TL_LANG']['MSC']['cc_num'],
				'inputType'			=> 'text',
				'eval'				=> array('mandatory'=>true, 'tableless'=>true),
			),
			'x_card_type' 		=> array
			(
				'label'				=> &$GLOBALS['TL_LANG']['MSC']['cc_type'],
				'inputType'			=> 'select',
				'options'			=> $arrCCFinal,
				'eval'				=> array('mandatory'=>true, 'tableless'=>true),
			),
			'card_expirationMonth' => array
			(
				'label'			=> &$GLOBALS['TL_LANG']['MSC']['cc_exp_month'],
				'inputType'		=> 'select',
				'options'		=> array('01','02','03','04','05','06','07','08','09','10','11','12'),
				'eval'			=> array('mandatory'=>true, 'tableless'=>true, 'includeBlankOption'=>true)
			),
			'card_expirationYear'  => array
			(
				'label'			=> &$GLOBALS['TL_LANG']['MSC']['cc_exp_year'],
				'inputType'		=> 'select',
				'options'		=> $arrYears,
				'eval'			=> array('mandatory'=>true, 'includeBlankOption'=>true)
			),
			'x_exp_date' => array
			(
				'inputType'		=> 'hidden',
				'value'			=> ''
			),
			'iso_redirect_url' => array
			(
				'inputType'		=> 'hidden',
				'value'			=> $strIsoRedirectUrl 
			),
			'iso_module_id' => array
			(
				'inputType'		=> 'hidden',
				'value'			=> $this->id,
			),
			'x_first_name' => array
			(
				'inputType'		=> 'hidden',
				'value'			=> $objBillingAddress->firstname,
			),
			'x_last_name' => array
			(
				'inputType'		=> 'hidden',
				'value'			=> $objBillingAddress->lastname,
			),
			'x_address' => array
			(
				'inputType'		=> 'hidden',
				'value'			=> $objBillingAddress->street_1,
			),
			'x_city' => array
			(
				'inputType'		=> 'hidden',
				'value'			=> $objBillingAddress->city,
			),
			'x_state' => array
			(
				'inputType'		=> 'hidden',
				'value'			=> $objBillingAddress->subdivision,
			),
			'x_zip' => array
			(
				'inputType'		=> 'hidden',
				'value'			=> $objBillingAddress->postal,
			),
			'x_country' => array
			(
				'inputType'		=> 'hidden',
				'value'			=> $objBillingAddress->country,
			),
			'x_phone' => array
			(
				'inputType'		=> 'hidden',
				'value'			=> $objBillingAddress->phone,
			),
			'x_email' => array
			(
				'inputType'		=> 'hidden',
				'value'			=> $objBillingAddress->email,
			),
			'x_description' => array
			(
				'inputType'		=> 'hidden',
				'value'			=> "Cart ID " . $objCart->id,
			),
			'shipping_id' => array
			(
				'inputType'		=> 'hidden',
				'value'			=> "Shipping: " . $objCart->getShippingMethod()->label,
			),
		);

		if ($this->requireCCV)
		{
			$arrFields['x_card_code'] = array
			(
				'label'			=> &$GLOBALS['TL_LANG']['MSC']['cc_ccv'],
				'inputType'		=> 'text',
				'eval'			=> array('mandatory'=>true, 'class'=>'ccv')
			);
		}
		
		foreach ($arrFields as $field => $arrData )
		{
			$strClass = $GLOBALS['TL_FFL'][$arrData['inputType']];

			// Continue if the class is not defined
			if (!class_exists($strClass))
			{
				continue;
			}
			
			$objWidget = new $strClass(\Widget::getAttributesFromDca($arrData, $field));
			if($arrData['value'])
			{
				$objWidget->value = $arrData['value'];
			}
			$objWidget->tableless = $this->tableless;
			
			$strBuffer .= $objWidget->parse();
		}
		
		if (!$this->tableless)
        {
        	$strBuffer .= '</table>' . "\n";
        }
		
		return $strBuffer;

    }
    
    
    /**
     * Check for Authorize.Net's duplicate transaction lockout time if this isn't the first submission
     *
	 * @access	protected
     * @return	boolean
     */
    protected function isAuthNetLocked()
    {
    	// Check for a prior Authorize.Net request attempt that could have failed to create an order
    	if ($_SESSION['ISOTOPE']['LAST_ATTEMPT_TSTAMP'] && (time() < (intval($_SESSION['ISOTOPE']['LAST_ATTEMPT_TSTAMP']) + $this->intAuthNetLockTime)))
    	{
    		$_SESSION['ISO_ERROR'][] = $GLOBALS['ISO_LANG']['MSC']['authnet_dpm_locked'];
    		$_SESSION['ISO_ERROR'] = array_unique($_SESSION['ISO_ERROR']);
	    		
			return true;
    	}
		
		return false;
    }
    
    
     /**
     * Remove any GET params that might still be in the URL string.
     *
	 * @access	protected
     * @param	string
     * @return	string
     */
    protected function removeGetParams($strURL)
    {
		$getPos = strpos($strURL, '?');	
				
		if ($getPos !== false)
		{
			$strGet = substr($strURL, $getPos);
			$strURL = str_replace($strGet, '', $strURL);
		}
		
    	return $strURL;
    }
    
    
    /**
     * Generates an Md5 hash to compare against Authorize.Net's.
     *
	 * @access	public
     * @param	string
     * @param	string
     * @param	string
     * @param	string
     * @return	string Hash
     */
    public function generateHash($strMD5Hash, $strLoginId, $strTransId, $strAmount)
    {
        return strtoupper(md5($strMD5Hash . $strLoginId . $strTransId . sprintf("%1\$.2f", $strAmount)));
    }


	/**
	 * Return allowed CC types
	 *
	 * @access public
	 * @return array
	 */
	public static function getAllowedCCTypes()
	{
		return array('mc', 'visa', 'amex', 'discover', 'jcb', 'diners', 'enroute');
	}
    
    
    /**
     * Handle a failure response from Authorize.Net.
     *
	 * @access	protected
     * @return	void
     */
	protected function handleFailure()
    {
    	if (strlen(\Input::get('response_code')) && \Input::get('response_code') != '1' && strlen(\Input::get('reason_code')))
    	{
    		$_SESSION['ISO_ERROR'][] = $GLOBALS['TL_LANG']['MSG']['authorizedotnet'][\Input::get('response_code')][\Input::get('reason_code')];
    		
    		$strErrMsg = 'Authorize.Net error: ' . $GLOBALS['TL_LANG']['MSG']['authorizedotnet'][\Input::get('response_code')][\Input::get('reason_code')];
    		$strErrMsg .= ' -- Message from Authorize.Net: ' . \Input::get('reason');
    		
    		\System::log($strErrMsg, __METHOD__, TL_ERROR);
    	}
    	else
    	{
    		$_SESSION['ISO_ERROR'][] = $GLOBALS['TL_LANG']['MSC']['authorizedotnet']['genericfail'];    		
    	}
    }
    
    
    /**
     * Check to see if the cart has been updated and we need to void the prior authorization.
     *
	 * @access	protected
     * @return	boolean
     */
	protected function cartUpdated()
    {
		// Don't need to void if this is the first time this loads or we're in the back end
    	if (!isset($_SESSION['ISOTOPE']['CART_HASH']))
    	{
    		return false;
    	}
    	
    	// Check to see if the cart has been updated
    	$strCartHash = $this->generateCartHash();
    	
    	if ($strCartHash == $_SESSION['ISOTOPE']['CART_HASH'])
    	{
    		return false;
    	}
    	
    	// Save the cart hash string to the session
    	$_SESSION['ISOTOPE']['CART_HASH'] = $strCartHash;
    	
    	return true;
    }
	
    
    /**
     * Create a hash out of the cart info
     *
	 * @access	protected
     * @return	string
     */
    protected function generateCartHash()
    {
    	$strCartInfo = strval($this->objCart->getTotal());
    	$arrProducts = $this->objCart->getProducts();
    	
    	// Use the product ID and quantity to build a cart hash
    	foreach ($arrProducts as $objProduct)
    	{
    		$strCartInfo .= $objProduct->id;
    		$strCartInfo .= $objProduct->quantity_requested;
    	}
    	
    	return md5($strCartInfo);
    }
    
    
    /**
     * Check to see if the transaction needs to be voided.
     *
	 * @access	protected
     * @return	boolean
     */
    protected function needsVoid()
    {
    	if ($this->debug || \Environment::get('isAjaxRequest'))
    		return false;
    	
    	$objOrder = Order::findOneBy('source_collection_id', Isotope::getCart()->id);
    	
    	if ($objOrder === null)
    	{
	    	return false;
    	}

		$arrOrderPaymentData = deserialize($objOrder->payment_data, true);
		
		// If a transaction has already been requested and we're not moving to the order detail page then we need to void it
		if( strlen($arrOrderPaymentData['transaction_id']) && 
			strlen(\Input::get('transaction_id')) && 
			$arrOrderPaymentData['transaction_id'] != \Input::get('transaction_id'))
		{
			
			$this->strTransactionID = $arrOrderPaymentData['transaction_id'];
			
			// Remove transaction data from the order
			$objOrder->payment_data = serialize(array('transaction_voided'=>'1'));
			$objOrder->save();
			
			\Database::getInstance()->prepare("UPDATE tl_iso_product_collection %s WHERE id=?")
									->set(array('payment_data'=>serialize(array('transaction_voided'=>'1'))))
									->executeUncached($objOrder->id);
		
			
			return true;
		}
		
		return false;
    }
    
    
    /**
     * Try to void the last transaction.
     *
	 * @access	protected
     * @return	object
     */
    protected function voidLastTransaction()
    {
    	$obj = new stdClass();
    	
		// Check for debug mode and whether or not we've already done a void this time around
		if ($this->debug)
			return $obj;
		
		// Check for previous lockout
    	if ($this->isUserLockedOut())
    	{
    		$obj->response_reason_text = $this->lockout_message;
    		$_SESSION['ISO_ERROR'][] = $this->lockout_message;
    		
    		return $obj;
    	}
    	
    	// Void the last transaction
		$sale = new \AuthorizeNetAIM($this->strApiLoginId, $this->strTransKey);
		$sale->setSandbox(false);
		$response = $sale->void($this->strTransactionID);
		
		// Increment the number of attempts for this session
		$_SESSION['ISOTOPE']['AUTH_ATTEMPTS'] = intval($_SESSION['ISOTOPE']['AUTH_ATTEMPTS']) + 1;
		
		// If the number of attempts has reached the limit, lock the user out
		if ($_SESSION['ISOTOPE']['AUTH_ATTEMPTS'] >= $this->lockout_attempts)
		{
			$this->lockUser();
		}
		
		return $response;
    }
    
    
    /**
     * Check to see if the front end user is locked out.
     *
	 * @access	protected
     * @return	boolean
     */
    protected function isUserLockedOut()
    {
		if (TL_MODE == 'BE' || !$this->lockout_attempts)
    		return false;
    	
    	switch ($this->lockout_method)
		{
			case 'ip':
				return $this->checkSavedLockouts();
				break;
				
			case 'session':
				return $this->checkSessionLockout();
				break;
				
			default:
				
				// Provide a hook for other lockout methods
				if (isset($GLOBALS['ISO_HOOKS']['authnetdpm_isUserLockedOut']) && is_array($GLOBALS['ISO_HOOKS']['authnetdpm_isUserLockedOut']))
				{
					foreach ($GLOBALS['ISO_HOOKS']['authnetdpm_isUserLockedOut'] as $callback)
					{
						$this->import($callback[0]);
						$varValue = $this->{$callback[0]}->{$callback[1]}($this->objCart, $this->strTransactionID, $this);
						
						if ($varValue === true)
						{
							return true;
						}
					}
				}
		}
		
		return false;
    }
    
    
    
    
    /**
     * Check lockouts that have been saved in the database
     *
	 * @access	protected
     * @return	boolean
     */
    protected function checkSavedLockouts()
    {
    	$objLockouts = \Database::getInstance()->prepare("SELECT * FROM tl_iso_lockout")->executeUncached();
    	
    	if ($objLockouts->numRows)
    	{
			foreach ($objLockouts->fetchAllAssoc() as $arrLockout)
    		{
    			// Found the IP address
    			if (\Environment::getInstance()->ip == $arrLockout['ip'])
    			{
					if ($arrLockout['unlock_tstamp'] <= time())
    				{
						// The lockout time has passed, remove the record from the table
    					\Database::getInstance()->prepare("DELETE FROM tl_iso_lockout WHERE id=?")->executeUncached($arrLockout['id']);
    					return false;
    				}
    				else
    				{
						// This IP address needs to be blocked
						$_SESSION['ISO_ERROR'][] = $this->lockout_message;
						$_SESSION['ISO_ERROR'] = array_unique($_SESSION['ISO_ERROR']);
    					return true;
    				}
    			}
    		}
    	}
    	
    	return false;
    }
    
    
    
    
    /**
     * Check session lockout
     *
	 * @access	protected
     * @return	boolean
     */
    protected function checkSessionLockout()
    {
		// Check to see if the maximum number of attempts has been exceeded
		if ($_SESSION['ISOTOPE']['AUTH_ATTEMPTS'] >= $this->lockout_attempts)
		{
			// Max attempts exceeded, check to see if the lockout time has been exceeded
			if ($_SESSION['ISOTOPE']['AUTH_UNLOCK_TSTAMP'] && $_SESSION['ISOTOPE']['AUTH_UNLOCK_TSTAMP'] <= time())
			{
				// Lockout time has been exceeded, unlock the user
				$_SESSION['ISOTOPE']['AUTH_ATTEMPTS'] = 0;
				$_SESSION['ISOTOPE']['AUTH_UNLOCK_TSTAMP'] = 0;
				return false;
			}
			else
			{
				// Lockout time hasn't been exceeded, the user needs to be blocked.
				if (intval($_SESSION['ISOTOPE']['AUTH_UNLOCK_TSTAMP']) === 0)
				{
					// Set the timestamp to unlock the user if it hasn't been set
					$_SESSION['ISOTOPE']['AUTH_UNLOCK_TSTAMP'] = time() + intval($this->lockout_duration);
				}
				
				$_SESSION['ISO_ERROR'][] = $this->lockout_message;
				$_SESSION['ISO_ERROR'] = array_unique($_SESSION['ISO_ERROR']);
				
				return true;
			}
		}
		
		return false;
    }
    
    
    /**
     * Lock a user out so they can't attempt any more authorizations.
     *
	 * @access	protected
     * @return	void
     */
    protected function lockUser()
    {
		switch ($this->lockout_method)
		{
			case 'ip':
		
				$intTime = time();
				
				if (FE_USER_LOGGED_IN)
				{
					$this->import('FrontendUser', 'User');
				}
				
				$arrInsert = array
				(
					'pid'					=> (FE_USER_LOGGED_IN ? $this->User->id : 0),
					'tstamp'				=> $intTime,
					'lockout_method'		=> 'ip',
					'ip'					=> \Environment::getInstance()->ip,
					'unlock_tstamp'			=> $intTime + intval($this->lockout_duration),
				);
				
				\Database::getInstance()->prepare("INSERT INTO tl_iso_lockout %s")->set($arrInsert)->executeUncached();
				break;
				
			default:
				
				// Provide a hook for other lockout methods
				if (isset($GLOBALS['ISO_HOOKS']['authnetdpm_lockUser']) && is_array($GLOBALS['ISO_HOOKS']['authnetdpm_lockUser']))
				{
					foreach ($GLOBALS['ISO_HOOKS']['authnetdpm_lockUser'] as $callback)
					{
						$this->import($callback[0]);
						$this->{$callback[0]}->{$callback[1]}(Isotope::getCart(), $this->strTransactionID, $this);
					}
				}
		}
    }
    
    
    
    /**
     * Validate the hash that is coming from the GET matches the one sent to Auth.net
     *
	 * @access 	protected
     * @param	IsotopeOrder
     * @return	bool
     */
    protected function isHashValid($objOrder=null)
    {
		if (strlen(\Input::get('response_code')) && \Input::get('response_code') == '1' && strlen(\Input::get('transaction_id')) && strlen(\Input::get('transaction_hash')))
    	{
			// Get the hash sent from the dpmHandler
			$strDpmHash = \Input::get('transaction_hash');
			
			// Get the hash using the same method as the AuthorizeNetSIM to verify that it came from Authorize.net						
			$strMD5Hash = \Encryption::decrypt($this->authorize_md5_hash);
			
			$intTotal = (is_null($objOrder) ? Isotope::getCart()->getTotal() : $objOrder->getTotal());
			
			$strHash = $this->generateHash($strMD5Hash, $this->strApiLoginId, \Input::get('transaction_id'), $intTotal);
									
			// Compare the two hash values.
			if ($strDpmHash == $strHash)
			{
				return true; 
			}
    	}
	
		return false;
    }


	/**
	 * Capture a previously authorized sale.
	 *
	 * @access protected
	 * @return boolean
	 */
	protected function doPriorAuthCapture(&$objOrder)
	{
		$blnReturn = false;
		
		// Get transaction data
		$arrPaymentData = deserialize($objOrder->payment_data, true);
        
        // Try the capture
		$sale = new \AuthorizeNetAIM($this->strApiLoginId, $this->strTransKey);
		$sale->setSandbox($this->debug ? true : false);
		$response = $sale->priorAuthCapture($arrPaymentData['transaction_id'] ?: $arrPaymentData['transaction-id'], $objOrder->getTotal());
		
		if ($response->approved)
		{
			$objPaidStatus = OrderStatus::findOneByPaid('1');
			$objOrder->updateOrderStatus($objPaidStatus->id);
			
			$this->strStatus = '1';
			$this->strReason = 'Success!';
			$blnReturn = true;
			
			if (isset($arrPaymentData['reason']))
				unset($arrPaymentData['reason']);
		}
		else
		{
			$objOrder->updateOrderStatus(Isotope::getConfig()->orderstatus_error);
			
			$this->strStatus = $response->response_code;
			$this->strReason = $response->response_reason_text;
			
			// Store the reason in the Order's payment data
			$arrPaymentData['reason'] = $response->response_reason_text;
		}
			
		$objOrder->payment_data = serialize($arrPaymentData);
		$objOrder->save();
		
		return $blnReturn;
	}
	

	/**
	 * Generate the backend POS terminal
	 *
	 * @access 	public
	 * @param 	integer
	 * @return 	string
	 */
	public function backendInterface($orderId)
	{
		if (($objOrder = Order::findByPk($orderId)) === null) {
            return parent::backendInterface($orderId);
        }

		\Input::setGet('uid', $objOrder->uniqid);
		
		$objModule = new ModuleIsotopeOrderDetails(\Database::getInstance()->execute("SELECT * FROM tl_module WHERE type='iso_orderdetails'"));

		$strOrderDetails = $objModule->generate(true);

		$arrPaymentData = deserialize($objOrder->payment_data, true);


		//Get the authorize.net configuration data
		$objAIMConfig = \Database::getInstance()->prepare("SELECT * FROM tl_iso_payment WHERE type=?")
														->execute('authnet_dpm');
		if($objAIMConfig->numRows < 1)
		{
			return '<i>' . $GLOBALS['TL_LANG']['MSC']['noPaymentModules'] . '</i>';
		}
		

		// Form was submitted
		if (\Input::post('FORM_SUBMIT') == 'be_pos_terminal' && $arrPaymentData['transaction-id'] !== '0')
		{
			$blnAuthCapture = $this->doPriorAuthCapture($objOrder);

			$strResponse = '<p class="tl_info">' . sprintf("Transaction Status: %s, Reason: %s", $this->strStatus, $this->strReason) . '</p>';
		}

		// Build an array of HTML lines to pass to unknown callbacks so they can insert lines where needed. This really needs to be done in a template...
		$arrLines = array('<div id="tl_buttons">');
		$arrLines[] = '
<input type="hidden" name="FORM_SUBMIT" value="be_pos_terminal">';
		$arrLines[] = '
<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">';
		$arrLines[] = '
<a href="'.ampersand(str_replace('&key=payment', '', \Environment::get('request'))).'" class="header_back" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBT']).'">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>';
		$arrLines[] = '
</div>';
		$arrLines[] = '
<h2 class="sub_headline">' . $GLOBALS['ISO_LANG']['PAY']['authorizedotnet'][0] . '</h2>';
		$arrLines[] = '
<div class="tl_formbody_edit">';
		$arrLines[] = '
<div class="tl_tbox block">';

		$arrLines[] = ($strResponse ? $strResponse : '');
		$arrLines[] = $strOrderDetails;
		$arrLines[] = '</div></div>';
		
		$objPaidStatus = OrderStatus::findOneByPaid('1');
		$arrExcludes = array('complete', 'cancelled', 'processing', $objPaidStatus, '3');
		
		if (!in_array($objOrder->order_status, $arrExcludes))
		{
			$arrLines[] = '<div class="tl_formbody_submit"><div class="tl_submit_container">';
			$arrLines[] = '<input type="submit" class="submit" value="' . specialchars($GLOBALS['TL_LANG']['MSC']['confirmOrder']) . '"></div></div>';
		}
		
		
		// HOOK: Provide a way for custom modules to manipulate the backend interface HTML
		// should be done through a template but this works for now
		if (isset($GLOBALS['ISO_HOOKS']['authnetdpm_be_interface']) && is_array($GLOBALS['ISO_HOOKS']['authnetdpm_be_interface']))
		{
			foreach ($GLOBALS['ISO_HOOKS']['authnetdpm_be_interface'] as $callback)
			{
				$this->import($callback[0]);
				$arrLines = $this->{$callback[0]}->{$callback[1]}($arrLines, $arrOrderInfo, $this);
			}
		}

		// Code specific to Authorize.net!
		$objTemplate = new \BackendTemplate('be_pos_terminal');
		$objTemplate->orderReview = implode('', $arrLines);
		$objTemplate->action = ampersand(\Environment::get('request'), ENCODE_AMPERSANDS);

		return $objTemplate->parse();
	}
	
	
	
	protected static function varDumpToString($var)
	{
		ob_start();
		var_dump($var);
		$result = ob_get_clean();
		return $result;
	}

	
	
}
