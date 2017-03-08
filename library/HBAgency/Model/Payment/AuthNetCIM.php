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

class AuthNetCIM extends Payment
{
	
	/**
	 * Cart object - Able to handle other cart types than just IsotopeCart
	 *
	 * @access protected
	 * @var IsotopeProductCollection
	 */
	protected $objCart;
	
	/**
	 * Order object
	 *
	 * @access protected
	 * @var IsotopeOrder
	 */
	protected $objOrder;
	
	/**
	 * Module object
	 *
	 * @access protected
	 * @var Module
	 */
	protected $objModule;
	
	/**
	 * Authorize.Net's duplicate transaction lockout time (must be > 2 minutes... account for lag time)
	 *
	 * @access protected
	 * @var integer
	 */
	protected $intAuthNetLockTime = 140;
	
	/**
	 * Is AJAX
	 *
	 * @access protected
	 * @var boolean
	 */
	protected $blnIsAjax = false;
	
	/**
	 * Cart field - could vary according to collection type
	 *
	 * @access protected
	 * @var string
	 */
	protected $strCartField = 'cart_id';
	
	
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
	 * Response object
	 *
	 * @access protected
	 * @var AuthorizeNetResponse
	 */
	protected $objResponse;

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
	 * Proceed to complete step?
	 *
	 * @access protected
	 * @var boolean
	 */
	protected $blnProceed = false;

	/**
	 * Form ID
	 *
	 * @access protected
	 * @var string
	 */
	protected $strFormId = 'payment_authnet_cim';
	
	
	/**
	 * Import libraries and initialize some variables
	 */
	public function __construct(\Database\Result $objResult = null)
	{
		parent::__construct($objResult);
		
		$this->objCart = TL_MODE == 'FE' ? Isotope::getCart() : null;
				
        //Decrypt the login/key values
        $this->strApiLoginId 	= \Encryption::decrypt($this->authorize_login);
        $this->strTransKey 		= \Encryption::decrypt($this->authorize_trans_key);
	}


	/**
	 * Process payment on confirmation page.
	 *
	 * @access public
	 * @return mixed
	 */
	public function processPayment(IsotopeProductCollection $objOrder, \Module $objModule)
	{
		$this->objModule = $objModule;
		
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
		$this->objModule = $objModule;
		
	    return false;
	}
	
	
	/**
	 * Generate payment authorization form and AUTH or CAPTURE
	 *
	 * @access 	public
	 * @param 	object
	 * @return	mixed
	 */
	public function paymentForm(\Module $objModule)
	{
		global $objPage;
		$this->objModule = $objModule;
		
		// See if we've already processed
		if ($_SESSION['AUTH_NET_CIM_PROCEED'][$objPage->id] === true)
		{
			$this->objModule->doNotSubmit = false;
			return false;
		}
		
		$this->setCart($objModule);

		$strBuffer = (!$this->tableless ? '<table class="ccform">' . "\n" : '');
        
        // Get CC Form
        $strBuffer .= $this->getCreditCardForm($objModule, $this->objCart);
        
        $strBuffer .= (!$this->tableless ? '</table>' . "\n" : '');
						
		// Check for response from Authorize.Net
		if (is_object($this->objResponse))
		{
			if(strtoupper($this->objResponse->xml->messages->resultCode) == 'OK')
    		{
    			return false;
			}
			else
			{
				$this->handleFailure();
			}
		}
		
		return $this->blnProceed ? false : '<h2>' . $this->label . '</h2>'. $strBuffer;
	}
    
    
    /**
     * Generate a form for use in a Direct Post implementation.
     *
	 * @access	protected
     * @param	boolean
     * @return	string
     */
    protected function getCreditCardForm($objModule, $objCollection)
    {
    	// Temporary
    	if(\Environment::get('isAjaxRequest'))
    	{
	    	return '';
    	}
    	
        $time = time();
		$this->objModule = $objModule;
        $strBuffer = \Input::get('response_code') == '1' ? '<p class="error message">' . $GLOBALS['ISO_LANG']['MSC']['authnet_dpm_locked'] . '</p>' : '';
        
        if (!$this->tableless)
        {
        	$strBuffer .= '<table class="ccform">' . "\n";
        }
        
        // Add script
        $GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/isotope_authorizedotnet/assets/authnet_formhandler.js';
        
        // Get credit card types
        $arrCCFinal = array();
       	$arrCCTypes = deserialize($this->allowed_cc_types);
       	
       	foreach ($arrCCTypes as $strCCType)
       	{
	       	$arrCCFinal[strtoupper(substr($strCCType, 0, 1))] = $GLOBALS['TL_LANG']['CCT'][$strCCType];
       	}

		$intStartYear = (integer)date('Y', time()); //2-digit year

		//Build years array - Going forward 7 years
		for ($i = 0; $i <= 7; $i++)
		{
			$arrYears[] = (string)$intStartYear+$i;
		}
		
		// Get billing data to include on form
		$arrBillingInfo = $objCollection->getBillingAddress()->row();
		
		$strValidate = strval(\Input::post('paymentProfile'));
		
		//Build form fields
		$arrFields = array
		(
			'paymentProfile'	=> array
			(
				'label'				=> &$GLOBALS['TL_LANG']['MSC']['paymentProfile'],
				'inputType'			=> 'radio',
				'options'           => array('cc', 'bank'),
				'reference'         => &$GLOBALS['TL_LANG']['MSC']['paymentProfileTypes'],
				'eval'				=> array('mandatory'=>true, 'tableless'=>true),
			),
			'x_card_num'	=> array
			(
				'label'				=> &$GLOBALS['TL_LANG']['MSC']['cc_num'],
				'inputType'			=> 'text',
				'eval'				=> array('mandatory'=>$strValidate=='cc', 'tableless'=>true, 'class'=>'ccField'),
			),
			'x_card_type' 		=> array
			(
				'label'				=> &$GLOBALS['TL_LANG']['MSC']['cc_type'],
				'inputType'			=> 'select',
				'options'			=> $arrCCFinal,
				'eval'				=> array('mandatory'=>$strValidate=='cc', 'tableless'=>true, 'class'=>'ccField'),
			),
			'card_expirationMonth' => array
			(
				'label'			=> &$GLOBALS['TL_LANG']['MSC']['cc_exp_month'],
				'inputType'		=> 'select',
				'options'		=> array('01','02','03','04','05','06','07','08','09','10','11','12'),
				'eval'			=> array('mandatory'=>$strValidate=='cc', 'tableless'=>true, 'includeBlankOption'=>true, 'class'=>'ccField')
			),
			'card_expirationYear'  => array
			(
				'label'			=> &$GLOBALS['TL_LANG']['MSC']['cc_exp_year'],
				'inputType'		=> 'select',
				'options'		=> $arrYears,
				'eval'			=> array('mandatory'=>$strValidate=='cc', 'includeBlankOption'=>true, 'class'=>'ccField')
			),
			'x_exp_date' => array
			(
				'inputType'		=> 'hidden',
				'value'			=> ''
			),
		);

		if ($this->requireCCV)
		{
			$arrFields['x_card_code'] = array
			(
				'label'			=> &$GLOBALS['TL_LANG']['MSC']['cc_ccv'],
				'inputType'		=> 'text',
				'eval'			=> array('mandatory'=>$strValidate=='cc', 'class'=>'ccv', 'class'=>'ccField')
			);
		}
		
		//Build bank account fields
		$arrBankFields = array
		(
			'bank_accountType'	=> array
			(
				'label'			=> &$GLOBALS['TL_LANG']['MSC']['bank_accountType'],
				'inputType'		=> 'select',
				'options'		=> array('checking', 'savings', 'businessChecking'),
				'reference'     => $GLOBALS['TL_LANG']['MSC']['accountTypes'],
				'eval'			=> array('tableless'=>true, 'includeBlankOption'=>true, 'class'=>'bankField')
			),
			'bank_routingNumber' 	=> array
			(
				'label'				=> &$GLOBALS['TL_LANG']['MSC']['bank_routingNumber'],
				'inputType'			=> 'text',
				'eval'				=> array('maxLength'=> 9, 'minLength' => 9, 'mandatory'=>$strValidate=='bank', 'tableless'=>true, 'class'=>'bankField'),
			),
			'bank_accountNumber' => array
			(
				'label'				=> &$GLOBALS['TL_LANG']['MSC']['bank_accountNumber'],
				'inputType'			=> 'text',
				'eval'				=> array('maxLength'=> 17, 'minLength' => 5, 'mandatory'=>$strValidate=='bank', 'tableless'=>true, 'class'=>'bankField'),
			),
			'bank_nameOnAccount'  => array
			(
				'label'				=> &$GLOBALS['TL_LANG']['MSC']['bank_nameOnAccount'],
				'inputType'			=> 'text',
				'eval'				=> array('maxLength'=> 22, 'mandatory'=>$strValidate=='bank', 'tableless'=>true, 'class'=>'bankField'),
			),
			'bank_echeckType'	=> array
			(
				'label'			=> &$GLOBALS['TL_LANG']['MSC']['bank_echeckType'],
				'inputType'		=> 'select',
				'options'		=> array('CCD', 'PPD', 'TEL', 'WEB'),
				'eval'			=> array('tableless'=>true, 'includeBlankOption'=>true, 'class'=>'bankField')
			),
			'bank_bankName'  => array
			(
				'label'				=> &$GLOBALS['TL_LANG']['MSC']['bank_bankName'],
				'inputType'			=> 'text',
				'eval'				=> array('maxLength'=> 50, 'tableless'=>true, 'class'=>'bankField'),
			),
		);
		
		$arrFields = array_merge($arrFields, $arrBankFields);
		
		$blnSubmit = true;
		
		foreach ($arrFields as $field => $arrData )
		{
			$strClass = $GLOBALS['TL_FFL'][$arrData['inputType']];

			// Continue if the class is not defined
			if (!class_exists($strClass))
			{
				continue;
			}
			
			$objWidget = new $strClass($strClass::getAttributesFromDca($arrData, $field));
			if($arrData['value'] || \Input::post($field))
			{
				$objWidget->value = $arrData['value'];
			}
			$objWidget->tableless = $this->tableless;
			
			//Handle form submit
			if( \Input::post('FORM_SUBMIT') == $objModule->getFormId() )
			{
				$objWidget->validate();
				if($objWidget->hasErrors())
				{
					if (empty($_SESSION['ISO_ERROR']))
					{
						$_SESSION['ISO_ERROR'][] = $objWidget->getErrorAsString();
					}
					$blnSubmit = false;
					$this->objModule->doNotSubmit = true;
				}
			}
			
			$strBuffer .= $objWidget->generateLabel() . ' ' . $objWidget->generateWithError();
		}

		
		if (!$this->tableless)
        {
        	$strBuffer .= '</table>' . "\n";
        }
        
        //Process the data
        if($blnSubmit && \Input::post('FORM_SUBMIT') == $objModule->getFormId())
        {
	        $this->sendCIMRequest($objModule, $objCollection);
	        
	        //Merge new payment data with old payment data
	        $arrNewPaymentData = $this->processCIMResponse();
	        $arrOldPaymentData = deserialize($objCollection->payment_data, true);
            $objCollection->payment_data = array_merge($arrOldPaymentData, $arrNewPaymentData);
        }
        
		return $strBuffer;

    }

    
    
    /**
     * Send request to Authorize.net
     *
	 * @access	protected
     * @return	void
     */
    protected function sendCIMRequest($objModule, $objCollection)
    {
		$this->objModule = $objModule;
		
    	// Get billing data to include on form
		$arrBillingInfo = $objCollection->getBillingAddress()->row();
		$arrSubdivision = explode('-', $arrBillingInfo['subdivision']);
        
        // Create new customer profile
        $customerProfile = new \AuthorizeNetCustomer();
        $customerProfile->description = $arrBillingInfo['firstname'] . ' ' . $arrBillingInfo['lastname'] . " Reference #" . $objCollection->id;
        $customerProfile->merchantCustomerId = $objCollection->id;
        $customerProfile->email = $arrBillingInfo['email'];
        
        //Setting Bill to Address
        $address = new \AuthorizeNetAddress();
        $address->firstName     = $arrBillingInfo['firstname'];
        $address->lastName      = $arrBillingInfo['lastname'];
        $address->company       = $arrBillingInfo['company'];
        $address->address       = $arrBillingInfo['street_1'];
        $address->city          = $arrBillingInfo['city'];
        $address->state         = $arrSubdivision[1];
        $address->zip           = $arrBillingInfo['postal'];
        $address->country       = "USA";
        $address->phoneNumber   = $arrBillingInfo['phone'];
    
        // Add payment profile.
        $paymentProfile = new \AuthorizeNetPaymentProfile();
        $paymentProfile->customerType          = "individual";
        $paymentProfile->billTo->firstName     = $arrBillingInfo['firstname'];
        $paymentProfile->billTo->lastName      = $arrBillingInfo['lastname'];
        $paymentProfile->billTo->company       = $arrBillingInfo['company'];
        $paymentProfile->billTo->address       = $arrBillingInfo['street_1'];
        $paymentProfile->billTo->city          = $arrBillingInfo['city'];
        $paymentProfile->billTo->state         = $arrSubdivision[1];
        $paymentProfile->billTo->zip           = $arrBillingInfo['postal'];
        $paymentProfile->billTo->country       = $arrSubdivision[0];
        $paymentProfile->billTo->phoneNumber   = $arrBillingInfo['phone'];
        
        if (\Input::post('paymentProfile') == 'cc')
        {
	        $paymentProfile->payment->creditCard->cardNumber = \Input::post('x_card_num');// ?: '4111111111111111';
	        $paymentProfile->payment->creditCard->expirationDate = (\Input::post('card_expirationYear') ?: ''/*'2021'*/) . '-' . (\Input::post('card_expirationMonth') ?: ''/*'04'*/);
        }
        else
        {
			$paymentProfile->payment->bankAccount->accountType = \Input::post('bank_accountType');
			$paymentProfile->payment->bankAccount->routingNumber = \Input::post('bank_routingNumber');
			$paymentProfile->payment->bankAccount->accountNumber = \Input::post('bank_accountNumber');
			$paymentProfile->payment->bankAccount->nameOnAccount = \Input::post('bank_nameOnAccount');
			$paymentProfile->payment->bankAccount->echeckType = \Input::post('bank_echeckType');
			$paymentProfile->payment->bankAccount->bankName = \Input::post('bank_bankName');
        }
        
        $customerProfile->paymentProfiles[] = $paymentProfile;
        
        //Create the request
        $request = new \AuthorizeNetCIM($this->strApiLoginId, $this->strTransKey);
        
		//Unset sandbox if live request
		if(!$this->debug)
		{
			$request->setSandbox(false);
		}
        
        //Create the profile
        $this->objResponse = $request->createCustomerProfile($customerProfile);

        \System::log('Authorize.net Response: '. $this->objResponse->xml->messages->resultCode->__toString() . '; Reason code: ' . $this->objResponse->xml->messages->message->code->__toString() . '; Reason text: ' . $this->objResponse->xml->messages->message->text->__toString(), __METHOD__, TL_INFO);
    
    }
    
    
    /**
     * Process an Authorize.net response to an array
     *
	 * @access	protected
     * @return	void
     */
    protected function processCIMResponse()
    {    
		global $objPage;
		
        $arrResponse = array
        (
            'resultCode'    =>  $this->objResponse->xml->messages->resultCode->__toString(),
            'messageCode'   =>  $this->objResponse->xml->messages->message->code->__toString(),
            'messageText'   =>  $this->objResponse->xml->messages->message->text->__toString(),
        );
        
        if(strtoupper($this->objResponse->xml->messages->resultCode) == 'OK')
        {
            $arrResponse['customerProfileId']           = $this->objResponse->xml->customerProfileId->__toString();
            $arrResponse['customerPaymentProfileId']    = $this->objResponse->xml->customerPaymentProfileIdList->numericString->__toString();
            $arrResponse['customerShippingAddressId']   = $this->objResponse->xml->customerPaymentProfileIdList->numericString->__toString();
            $arrResponse['validationDirectResponse']    = $this->objResponse->xml->validationDirectResponseList->string->__toString();
            
            $_SESSION['AUTH_NET_CIM_PROCEED'][$objPage->id] = true;
            $this->blnProceed = true;
        }
        else
        {
	        $_SESSION['ISO_ERROR'][] = $arrResponse['messageText'];
			if ($this->objModule) $this->objModule->doNotSubmit = true;
        }

        return $arrResponse;
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
    	if (!$this->objResponse->approved && strlen($this->objResponse->response_reason_text))
    	{
    		$_SESSION['ISO_ERROR'][] = $this->objResponse->response_reason_text;
    		
    		$strErrMsg = 'Authorize.Net error - Response code: ' . $this->objResponse->response_code;
    		$strErrMsg .= ' -- Response subcode: ' . $this->objResponse->response_subcode;
    		$strErrMsg .= ' -- Reason code: ' . $this->objResponse->response_reason_code;
    		$strErrMsg .= ' -- Message from Authorize.Net: ' . $this->objResponse->response_reason_text;
    		
    		\System::log($strErrMsg, __METHOD__, TL_ERROR);
    	}
    	else
    	{
    		$_SESSION['ISO_ERROR'][] = $GLOBALS['TL_LANG']['MSC']['authorizedotnet']['genericfail'];    		
    	}
    }
  
    
    /**
     * Allow other cart types/checkouts to use the payment module via a Hook
     *
	 * @access 	public
     * @param 	IsotopeModule
     * @return	void
     */
    public function setCart($objModule)
    {    
   		// Allow to customize attributes
		if (isset($GLOBALS['ISO_HOOKS']['setCart']) && is_array($GLOBALS['ISO_HOOKS']['setCart']))
		{
			foreach ($GLOBALS['ISO_HOOKS']['setCart'] as $callback)
			{			
				$objCallback = \System::importStatic($callback[0]);
				list($this->objCart, $this->strCartField) = $objCallback->{$callback[1]}($objModule, $this->objCart, $this->strCartField);
			}
		}
		
		if(null === $this->objCart)
		{
    		$this->objCart = Isotope::getCart();
		}
	}
	
	
	
	/**
	 * Return a list of order status options
	 * Allowed return values are ($GLOBALS['ISO_ORDER']):
	 * - pending
	 * - processing
	 * - complete
	 * - on_hold
	 * - cancelled
	 *
	 * @access 	public
	 * @return	array
	 */
	public function statusOptions()
	{
		return array('pending', 'processing', 'complete', 'on_hold', 'cancelled');
	}
	
	

	/**
	 * Generate the backend POS terminal
	 *
	 * @access 	public
	 * @param 	integer
	 * @return 	string
	 */
	public function backendInterface($intOrderId)
	{

	}
	
	/**
	 * For internal logging
	 *
	 * @access 	protected
	 * @param 	var
	 * @return 	string
	 */
	protected static function varDumpToString($var)
	{
		ob_start();
		var_dump($var);
		$result = ob_get_clean();
		return $result;
	}
	
}