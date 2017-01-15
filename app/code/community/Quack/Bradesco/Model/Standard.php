<?php
/**
 * Este arquivo é parte do programa Quack Bradesco
 *
 * Quack Bradesco é um software livre; você pode redistribuí-lo e/ou
 * modificá-lo dentro dos termos da Licença Pública Geral GNU como
 * publicada pela Fundação do Software Livre (FSF); na versão 3 da
 * Licença, ou (na sua opinião) qualquer versão.
 *
 * Este programa é distribuído na esperança de que possa ser  útil,
 * mas SEM NENHUMA GARANTIA; sem uma garantia implícita de ADEQUAÇÃO
 * a qualquer MERCADO ou APLICAÇÃO EM PARTICULAR. Veja a
 * Licença Pública Geral GNU para maiores detalhes.
 *
 * Você deve ter recebido uma cópia da Licença Pública Geral GNU junto
 * com este programa, Se não, veja <http://www.gnu.org/licenses/>.
 *
 * @category   Quack
 * @package    Quack_Bradesco
 * @author     Rafael Patro <rafaelpatro@gmail.com>
 * @copyright  Copyright (c) 2017 Rafael Patro (rafaelpatro@gmail.com)
 * @license    http://www.gnu.org/licenses/gpl.txt
 * @link       https://github.com/rafaelpatro/Quack_Bradesco
 */
?>
<?php
class Quack_Bradesco_Model_Standard extends Mage_Payment_Model_Method_Abstract {
	
	const PAYMENT_TYPE_AUTH = 'AUTHORIZATION';
	const PAYMENT_TYPE_SALE = 'SALE';

	protected $_code = 'bradesco_standard';
	protected $_allowCurrencyCode = array('BRL');
	protected $_formBlockType = 'bradesco/form';
  	protected $_canUseInternal = true;
  	protected $_canCapture = true;
  	protected $_canUseForMultishipping = true;
  	
  	protected $_order = null;
  	
  	public function setOrder($order) {
		$this->_order = $order;
  		return $this;
  	}
	
	public function getSession() {
		return Mage::getSingleton('bradesco/session');
	}

	/**
	 * Get checkout session namespace
	 * @return Mage_Checkout_Model_Session
	 */
	public function getCheckout() {
		return Mage::getSingleton('checkout/session');
	}

	/**
	 * Get current quote
	 *
	 * @return Mage_Sales_Model_Quote
	 */
	public function getQuote() {
		return $this->getCheckout()->getQuote();
	}

	public function createFormBlock($name) {
		$block = $this->getLayout()->createBlock('bradesco/form', $name)
			->setMethod ( 'bradesco_standard' )
			->setPayment( $this->getPayment() );
		return $block;
	}

	public function getTransactionId() {
		return $this->getSessionData('transaction_id');
	}

	public function setTransactionId($data) {
		return $this->setSessionData('transaction_id', $data);
	}

	public function validate() {
		parent::validate();
		$currency_code = $this->getQuote()->getBaseCurrencyCode();
		if ($currency_code == '') {
			$currency_code = Mage::getSingleton('adminhtml/session_quote')->getQuote()->getBaseCurrencyCode();
		}
		if (!in_array($currency_code,$this->_allowCurrencyCode)) {
			Mage::throwException(Mage::helper('bradesco')->__('A moeda selecionada ('.$currency_code.') não é compatível com o Itaú Shopline'));
		}
		return $this;
	}

	public function onOrderValidate(Mage_Sales_Model_Order_Payment $payment) {
	   return $this;
	}

	public function onInvoiceCreate(Mage_Sales_Model_Order_Payment $payment) {
		return $this;
	}

	public function getOrderPlaceRedirectUrl() {
	    if ($this->getConfigData('allowredirect') == 1) {
	        return Mage::getUrl('bradesco/standard/redirect');
	    }
	    return;
	}
	
	/**
	 * @return Mage_Sales_Model_Order
	 */
	public function getOrder() {
		if (!($this->_order instanceof Mage_Sales_Model_Order)) {
			$this->_order = Mage::getModel( 'sales/order' );
			$orderIncrementId = $this->getCheckout()->getLastRealOrderId();
			$this->_order->loadByIncrementId( $orderIncrementId );
		}
		return $this->_order;
	}
	
	/**
	 * @param Mage_Sales_Model_Order $order
	 * @return array
	 */
	public function getRedirectFields() {
		$this->log("bradesco.getRedirectFields");
		return array(
			'MerchantId' => $this->getConfigData('merchant_id'),
			'OrderId'    => $this->getOrder()->getEntityId(),
		);
	}
	
	public function getDescription($transaction) {
		$description = '';
		$method = "get{$this->_camelize($transaction)}Description";
		try {
			$description = $this->$method();
		} catch (Exception $e) {
			$this->log("Method {$transaction} does not exists");
		}
		return $description;
	}
	
	public function getOrderDescription() {
		$orderDescription = "";
		$order = $this->getOrder();
		foreach ($order->getAllItems() as $item) { /* @var $item Mage_Sales_Model_Order_Item */
			$elem = Mage::getModel('bradesco/orderDescription'); /* @var $elem Quack_Bradesco_Model_OrderDescription */
			$elem->orderid    = $order->getEntityId();
			$elem->descritivo = $item->getName();
			$elem->quantidade = (int)$item->getQtyOrdered();
			$elem->unidade    = 'un';
			$elem->valor      = number_format( ($item->getPrice() * $item->getQtyOrdered()) - $item->getBaseDiscountAmount(), 2, '', '');
			$orderDescription.= $this->getHelper()->encrypt( (array) $elem );
			unset($elem);
		}
		if ($order->getShippingInclTax() > 0) {
			$ship = Mage::getModel('bradesco/orderDescription'); /* @var $ship Quack_Bradesco_Model_OrderDescription */
			$ship->adicional      = $order->getShippingDescription();
			$ship->valorAdicional = number_format($order->getShippingInclTax(), 2, '', '');
			$orderDescription.= $this->getHelper()->encrypt( (array) $ship );
		}
		return $orderDescription;
	}
	
	public function getTransferDescription() {
		$transfer = Mage::getModel('bradesco/transferDescription'); /* @var $transfer Quack_Bradesco_Model_TransferDescription */
		$transfer->NUMEROAGENCIA = $this->getConfigData('agencia');
		$transfer->NUMEROCONTA   = $this->getConfigData('conta');
		$transfer->ASSINATURA    = $this->getConfigData('assinatura');
		return $this->getHelper()->encrypt( (array) $transfer );
	}

	public function getRequestUrl() {
		return $this->getConfigData('urlbradesco');
	}

	public function getSondaUrl() {
		return $this->getConfigData('urlsonda');
	}
	
	/**
	 * @return Quack_Bradesco_Helper_Data
	 */
	public function getHelper() {
		return Mage::helper('bradesco/data');
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Mage_Payment_Model_Method_Abstract::capture()
	 */
	public function capture(Varien_Object $payment, $amount) {
		parent::capture($payment, $amount);
		$this->log("bradesco.capture");
		try {
			/* @var $request Quack_Bradesco_Model_Sonda */
			$request = Mage::getModel('bradesco/sonda');
			$request
				->setMerchantId( $this->getConfigData('merchant_id') )
				->setData( Mage::getModel('core/date')->date('d/m/Y', $payment->getOrder()->getCreatedAt()) )
				->setManager( $this->getConfigData('manager') )
				->setPasswd( $this->getConfigData('passwd') )
				->setNumOrder( $payment->getParentId() );
			$sonda = $this->sonda( (array)$request );
			$this->getInfoInstance()
				->setAdditionalInformation( 'paymentType', 'Transferência entre Contas' )
				->setAdditionalInformation( 'paymentStatus', (string)$sonda->getStatus() )
				->save();
		} catch (Exception $e) {
			Mage::throwException($e->getMessage());
		}
		if ($sonda->getStatus() != '81') {
			$typeMsg = 'Transferência entre Contas';
			$statMsg = $this->getHelper()->getStatusMessage( $sonda->getStatus() );
			Mage::throwException("{$typeMsg}: {$statMsg}");
		}
		return $this;
	}
	
	/**
	 * @param array $params
	 * @return Quack_Bradesco_Model_Sonda
	 */
	public function sonda($params) {
		$this->log("bradesco.sonda");
		$this->log("params: ".print_r($params, true));
		$client = new Zend_Http_Client($this->getSondaUrl());
		$client->setParameterPost( $params );
		$result = $client->request('POST')->getBody();
		$this->log($result);
		$result = preg_replace('/<\![^>]*>/', '', $result); // remove doctype and cdata declarations
		$result = preg_replace('/<\?[^>]*>/', '', $result); // remove xml version and charset declaration
		$sonda = Mage::getModel('bradesco/sonda'); /* @var $sonda Quack_Bradesco_Model_Sonda */
		$xml = @simplexml_load_string($result);
		foreach ($xml->Bradesco->Pedido->attributes() as $attr => $value) {
			$sonda->setDataUsingMethod( $attr, (string) $value );
		}
		return $sonda;
	}
	
	/**
	 * @param string $message
	 * @return Quack_Bradesco_Model_Standard
	 */
	public function log($message) {
		Mage::log($message, null, 'bradesco.log');
		return $this;
	}
}
