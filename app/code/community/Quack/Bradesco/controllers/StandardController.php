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
class Quack_Bradesco_StandardController extends Mage_Core_Controller_Front_Action
{
	public $data=array();

	/**
	 * Order instance
	 */
	protected $_order;

	/**
	 *  Get order
	 *
	 *  @return   Mage_Sales_Model_Order
	 */
	public function getOrder() {
		if ($this->_order == null) {
		}
		return $this->_order;
	}

	protected function _expireAjax()
	{
		if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
			$this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
			exit;
		}
	}

	/**
	 * @return Quack_Bradesco_Model_Standard
	 */
	public function getStandard() {
		return Mage::getSingleton('bradesco/standard');
	}
	
	public function getHelper() {
		return $this->getStandard()->getHelper();
	}
	
	public function getConfig($field) {
		return $this->getStandard()->getConfigData($field);
	}

	public function redirectAction() {
		Mage::log('bradesco.redirectAction');
		$session = Mage::getSingleton('checkout/session');
		$session->setBradescoStandardQuoteId($session->getQuoteId());
		if ( $this->getStandard()->getOrder()->getPayment() ) {
			$this->getStandard()->getOrder()->sendNewOrderEmail();
		}
		$this->getResponse()->setBody($this->getLayout()->createBlock('bradesco/redirect')->toHtml());
		$session->unsQuoteId();
		$session->unsRedirectUrl();
	}

	public function cancelAction() {
		Mage::log('bradesco.cancelAction');
		$session = Mage::getSingleton('checkout/session');
		$session->setQuoteId($session->getBradescoQuoteId(true));
		if ($session->getLastRealOrderId()) {
			$order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
			if ($order->getId()) {
				$order->cancel()->save();
			}
		}
		$this->_redirect('checkout/cart');
	}

	public function successAction() {
		Mage::log('bradesco.successAction');
		$session = Mage::getSingleton('checkout/session');
		$session->setQuoteId($session->getBradescoStandardQuoteId(true));
		$orderId    = $this->getRequest()->getParam('numOrder');
		$tipopagto  = $this->getRequest()->getParam('tipopagto');
		$merchantid = $this->getRequest()->getParam('merchantid');
		if ($merchantid == $this->getConfig('merchant_id')) {
			try {
				$order = Mage::getModel("sales/order")->load((int)$orderId); /* @var $order Mage_Sales_Model_Order */
				if ($order->canInvoice()) {
					$invoice = $order->prepareInvoice();
					$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
					$invoice->register();
					$invoice->getOrder()->setIsInProcess(true);
					$order->addStatusHistoryComment($this->getHelper()->getTypeMessage($tipopagto));
					$transaction = Mage::getModel('core/resource_transaction')
						->addObject($invoice)
						->addObject($invoice->getOrder());
					$transaction->save();
				}
				Mage::getSingleton('core/session')->addSuccess("Pedido efetuado com sucesso!");
			} catch (Exception $e) {
				Mage::getSingleton('core/session')->addError($e->getMessage());
				Mage::getSingleton('core/session')->addNotice("Se já efetuou o pagamento, aguarde alguns instantes, a confirmação aparecerá em breve.");
				Mage::getSingleton('core/session')->addNotice("Caso não tenha conseguido realizar o pagamento, acesse seu pedido, e clique em Efetuar Pagamento.");
			}
		}
		Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
		$this->_redirect('sales/order/history');
	}
	
	public function returnAction() {
		Mage::log('bradesco.returnAction');
		Mage::log(print_r($this->getRequest()->getParams(), true));
		$transId    = $this->getRequest()->getParam('transId');
		$orderId    = $this->getRequest()->getParam('numOrder');
		$cod        = $this->getRequest()->getParam('cod');
		$merchantid = $this->getRequest()->getParam('merchantid');
		if ($merchantid == $this->getConfig('merchant_id')) {
			$order       = Mage::getModel("sales/order")->load((int)$orderId); /* @var $order Mage_Sales_Model_Order */
			$standard    = $this->getStandard()->setOrder($order);
			$standard->log("standard loaded");
			$action      = strtolower(substr($transId, 3));
			$block       = $this->getLayout()->createBlock( "core/template" ); /* @var $block Mage_Core_Block_Template */
			$standard->log("block created");
			if ($action == 'auth') {
				$block->setData( "cod", "{$cod}");
			} else {
				$block->setData( "order_description", $standard->getDescription("order") );
				$block->setData( "{$action}_description", $standard->getDescription($action) );
				$standard->log("description loaded");
			}
			$block->setTemplate( "bradesco/{$action}.phtml" );
			$standard->log("template created: {$block->getTemplate()}");
			$html = $block->toHtml();
			$standard->log("html loaded: {$html}");
		} else {
			$html = "Erro: Pagina nao encontrada";
		}
		$this->getResponse()->setBody( trim($html) );
	}
	
}
?>
