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


//Import Auth.net SDK
require_once TL_ROOT . '/system/modules/isotope_authorizedotnet/vendor/anet_php_sdk/AuthorizeNet.php';


class AuthNetAIM extends Payment implements IsotopePayment
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
	protected $strFormId = 'payment_authnet_aim';
	
	
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
		if ($this->override_formaction)
		{
			if (isset($_SESSION['CHECKOUT_DATA']['authNetAim']['payment_data']))
			{
				\Database::getInstance()->prepare("UPDATE tl_iso_product_collection %s WHERE id=?")
										->set(array('payment_data'=>serialize($_SESSION['CHECKOUT_DATA']['authNetAim']['payment_data'])))
										->executeUncached($objOrder->id);
				
				unset($_SESSION['CHECKOUT_DATA']['authNetAim']['payment_data']);
			}
			
			return false;
		}
		
		$this->setCart($objModule);
				
		// Get CC Form
		$strCCForm = $this->getCreditCardForm($objModule, $objOrder);
		
		// Set form action to the current URL
		$strAction = htmlentities($this->removeGetParams($this->Environment->base . $this->Environment->request));
		
		// todo: put this in a template
		$strFormStart = "\n" . 
			'<form action="'.$strAction.'" id="iso_mod_checkout_payment" method="post" enctype="application/x-www-form-urlencoded">' . "\n" . 
			'<input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">' . "\n" .
			'<input type="hidden" name="FORM_SUBMIT" value="'.$this->strFormId.'">' . "\n";
		
		$strBuffer = $strFormStart . $strCCForm;
		
		if (!$this->tableless)
        {
        	$strBuffer .= '<table class="ccform">' . "\n";
        }
		
		$objWidget = new \FormSubmit(array('slabel'=>'Order'));
		$objWidget->tableless = $this->tableless;
		$strBuffer .= "\n" . $objWidget->parse();
		
		if (!$this->tableless)
        {
        	$strBuffer .= '</table>' . "\n";
        }
		
		$strBuffer .= "\n" . '</form>' . "\n";
		
						
		// Check for response from Authorize.Net
		if (is_object($this->objResponse))
		{
			if ($this->objResponse->approved)
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
	
	
	
	public function setPaymentData(&$objOrder, $arrTokens)
	{
		if ($_SESSION['CHECKOUT_DATA']['authNetDpm']['payment_data'])
		{
			\System::log('Storing payment data for Order ID ' . $objOrder->id, __METHOD__, TL_GENERAL);
			
			\Database::getInstance()->prepare("UPDATE tl_iso_product_collection %s WHERE id=?")
									->set(array('payment_data'=>serialize($_SESSION['CHECKOUT_DATA']['authNetDpm']['payment_data'])))
									->executeUncached($objOrder->id);
		}
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
			
			if (!in_array($strStep, $arrSteps))
			{
				$objOrder = Order::findBy('source_collection_id', Isotope::getCart()->id);
				$strBuffer = $this->getCreditCardForm($objModule, $objOrder);
			}
			
			return '<h2>' . $this->label . '</h2>'. $strBuffer;
		}
		
		return '';
	}
    
    
    
    /**
     * Generate a form for use in a Direct Post implementation.
     *
	 * @access	protected
     * @param	boolean
     * @return	string
     */
    protected function getCreditCardForm($objModule, $objOrder)
    {
        $time = time();
		$this->strFormId = $this->override_formaction ? $objModule->getFormId() : $this->strFormId;

        $strBuffer = \Input::get('response_code') == '1' ? '<p class="error message">' . $GLOBALS['ISO_LANG']['MSC']['authnet_dpm_locked'] . '</p>' : '';
        
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

		//Build years array - Going forward 7 years
		for ($i = 0; $i <= 7; $i++)
		{
			$arrYears[] = (string)$intStartYear+$i;
		}
		
		// Get billing data to include on form
		$arrBillingInfo = $objOrder ? $objOrder->getBillingAddress()->row() : Isotope::getCart()->getBillingAddress()->row();
		
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
		
		// todo: clean this up!
		$blnSubmit = true;
		$intSelectedPayment = intval(\Input::post('PaymentMethod') ?: $this->objCart->getPaymentMethod());
		
		foreach ($arrFields as $field => $arrData )
		{
			$strClass = $GLOBALS['TL_FFL'][$arrData['inputType']];

			// Continue if the class is not defined
			if (!class_exists($strClass))
			{
				continue;
			}
			
			$objWidget = new $strClass($strClass::getAttributesFromDca($arrData, $field));
			if($arrData['value'])
			{
				$objWidget->value = $arrData['value'];
			}
			$objWidget->tableless = $this->tableless;
			
			//Handle form submit
			if( \Input::post('FORM_SUBMIT') == $this->strFormId && $intSelectedPayment == $this->id)
			{
				$objWidget->validate();
				if($objWidget->hasErrors())
				{
					$blnSubmit = false;
					$objModule->doNotSubmit = true;
				}
			}
			
			$strBuffer .= $objWidget->parse();
		}
		
		if (!$this->tableless)
        {
        	$strBuffer .= '</table>' . "\n";
        }
        
        //Process the data
        if($blnSubmit && \Input::post('FORM_SUBMIT') == $this->strFormId && $intSelectedPayment == $this->id)
        {
	        $this->sendAIMRequest($objModule, $objOrder);
        }
        
		return $strBuffer;

    }
    
    
    /**
     * Send request to Authorize.net
     *
	 * @access	protected
     * @return	void
     */
    protected function sendAIMRequest($objModule, $objOrder)
    {
    	// Get billing data to include on form
    	$objCollection = $objOrder ?: Isotope::getCart();
		$arrBillingInfo = $objCollection->getBillingAddress()->row();
		$arrSubdivision = explode('-', $arrBillingInfo['subdivision']);
		
        $sale = new \AuthorizeNetAIM($this->strApiLoginId, $this->strTransKey);
        $sale->card_num           = \Input::post('x_card_num');
        $sale->exp_date           = \Input::post('card_expirationMonth') . '/' . \Input::post('card_expirationYear');
        $sale->amount             = $objCollection->total;
        $sale->description        = $objOrder ? ("Order Number " . $objCollection->document_number) : ("Cart ID " . $objCollection->id);
        $sale->first_name         = $arrBillingInfo['firstname'];
        $sale->last_name          = $arrBillingInfo['lastname'];
        $sale->company            = $arrBillingInfo['company'];
        $sale->address            = $arrBillingInfo['street_1'];
        $sale->city               = $arrBillingInfo['city'];
        $sale->state              = $arrSubdivision[1];
        $sale->zip                = $arrBillingInfo['postal'];
        $sale->country            = $arrSubdivision[0];
        $sale->phone              = $arrBillingInfo['phone'];
        $sale->email              = $arrBillingInfo['email'];

		if ($this->requireCCV)
		{
	        $sale->card_code = \Input::post('x_card_code');
		}
        
		//Unset sandbox if live request
		if(!$this->debug)
		{
			$sale->setSandbox(false);
		}
        
        // TODO:  Separate Auth only and Auth Capture
        $this->objResponse = $sale->authorizeAndCapture();
        
        $arrPaymentData = deserialize($objCollection->payment_data, true);
        $arrNewData = array
        (
        	'original_auth_amt'				=> $objCollection->total,
        	'transaction_amount'			=> $objCollection->total,
        	'response_code'					=> $this->objResponse->response_code,
        	'response_reason_text'			=> $this->objResponse->response_reason_text,
        	'response_reason_code'			=> $this->objResponse->response_reason_code,
        	'transaction_id'				=> $this->objResponse->transaction_id,
        	'card_type'						=> $this->objResponse->card_type,
        	'account_number'				=> $this->objResponse->account_number,
        );
        
        $_SESSION['CHECKOUT_DATA']['authNetAim']['payment_data'] = array_merge($arrPaymentData, $arrNewData);
        $objCollection->payment_data = array_merge($arrPaymentData, $arrNewData);

        \System::log('Authorize.net Response: '. $this->objResponse->response_code . '-' . $this->objResponse->response_subcode . '; Reason code: ' . $this->objResponse->response_reason_code . '; Reason text: ' . $this->objResponse->response_reason_text, __METHOD__, TL_INFO);
    
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
				$this->import($callback[0]);
				list($this->objCart, $this->strCartField) = $this->{$callback[0]}->{$callback[1]}($objModule, $this->objCart, $this->strCartField);
			}
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
	
}