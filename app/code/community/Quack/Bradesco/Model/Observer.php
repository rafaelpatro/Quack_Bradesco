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
class Quack_Bradesco_Model_Observer {
	
	const PAYMENT_METHOD = 'bradesco_standard';
	
	/**
	 * Set additional payment information in frontend order view page.
	 * 
	 * @param Varien_Event_Observer $observer
	 */
	public function setFrontendPaymentInfo(Varien_Event_Observer $observer) {
		$block     = $observer->getEvent()->getBlock();     /* @var $block     Mage_Payment_Block_Info */
		$payment   = $observer->getEvent()->getPayment();   /* @var $payment   Mage_Payment_Model_Info */
		$transport = $observer->getEvent()->getTransport(); /* @var $transport Varien_Object */
		if ($block->getBlockAlias() == 'payment_info' && $payment->getMethodInstance()->getCode() == self::PAYMENT_METHOD) {
			$order    = $payment->getOrder();
			if ($order instanceof Mage_Sales_Model_Order) {
				$orderId = $order->getData('entity_id');
				$baseUrl = Mage::getBaseUrl();
				$url     = "{$baseUrl}bradesco/standard/redirect/order_id/{$orderId}";
				$state   = $order->getData('state');
                $isAllowBankTransfer = ($payment->getMethodInstance()->getConfigData('allowbanktransfer') == 1);
                $cmsBankTransferPage = $payment->getMethodInstance()->getConfigData('cms_banktransfer_page');
				$transport->setData(array(
					Mage::helper('bradesco')->__('Bank Method') => $this->getHelper()->getTypeMessage  ( $payment->getAdditionalInformation( 'paymentType'   ) ),
					Mage::helper('bradesco')->__('Bank Status') => $this->getHelper()->getStatusMessage( $payment->getAdditionalInformation( 'paymentStatus' ) ),
				));
				if ($state==Mage_Sales_Model_Order::STATE_NEW) {
					$childBlock = $block->getLayout()->createBlock('payment/info'); /* @var $childBlock Mage_Payment_Block_Info */
					$childBlock->setData('shopfacilurl', $url);
                    $childBlock->setData('allowbanktransfer', $isAllowBankTransfer);
                    $childBlock->setData('cmsbanktransferpage', $cmsBankTransferPage);
					$childBlock->setTemplate("bradesco/info/button_redirect.phtml");
					$block->setChild('payment_try_again', $childBlock);
				}
			}
		} elseif ($block->getBlockAlias() == 'payment.info.'.self::PAYMENT_METHOD) {
			// Checkout Payment Info Block
			$transport->setData( Mage::helper('bradesco')->__('Transação bancária via Bradesco') );
		}
		return;
	}
	
	/**
	 * Set additional payment information in backend order view page.
	 * 
	 * @param Varien_Event_Observer $observer
	 */
	public function setBackendPaymentInfo(Varien_Event_Observer $observer) {
		$payment  = $observer->getEvent()->getPayment(); /* @var $payment Mage_Payment_Model_Info */
		if ($payment->getMethodInstance()->getCode() == self::PAYMENT_METHOD) {
			$observer->getEvent()->getTransport()->setData(array(
					Mage::helper('bradesco')->__('Bank Method') => $this->getHelper()->getTypeMessage  ( $payment->getAdditionalInformation( 'paymentType'   ) ),
					Mage::helper('bradesco')->__('Bank Status') => $this->getHelper()->getStatusMessage( $payment->getAdditionalInformation( 'paymentStatus' ) ),
			));
		}
		return;
	}
	
	
	public function checkPayment() {
		$order = Mage::getModel('sales/order'); /* @var $order Mage_Sales_Model_Order */
		$capturedPaymentsCounter = 0;
		$collection = self::loadPending();
		foreach ($collection as $entity) {
			$order->load( $entity->getParentId() );
			if ($order->canInvoice()) {
				$invoice = $order->prepareInvoice();
				$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
				try {
					$invoice->register();
				} catch (Exception $e) {
					Mage::log("{$e->getMessage()}");
					unset($order);
					unset($invoice);
					$order = Mage::getModel('sales/order');
					continue;
				}
				$invoice->getOrder()->setIsInProcess(true);
				$order->addStatusHistoryComment( Mage::helper('bradesco')->__('Payment auto captured') );
				$transaction = Mage::getModel('core/resource_transaction')
					->addObject($invoice)
					->addObject($invoice->getOrder());
				$transaction->save();
				$invoice->sendEmail(true);
				$capturedPaymentsCounter++;
			}
		}
		$pendingPaymentsCounter = count($collection);
		return "{$capturedPaymentsCounter} payments captured of {$pendingPaymentsCounter} pending";
	}
	
	public function loadPending() {
		/* @var $order Mage_Sales_Model_Order */
		$order = Mage::getModel('sales/order');
		$table = $order->getResource()->getTable('sales/order');
		/* @var $collection Mage_Sales_Model_Mysql4_Order_Payment_Collection */
		$collection = Mage::getModel('sales/order_payment')->getCollection();
		$collection->getSelect()->join($table, "main_table.parent_id = {$table}.entity_id", array());
		$collection
			->addAttributeToFilter('method', self::PAYMENT_METHOD)
			->addAttributeToFilter('amount_paid', array('null' => true))
			->addAttributeToFilter('state', $order::STATE_NEW);
		return $collection;
	}
	
	/**
	 * @return Quack_Bradesco_Helper_Data
	 */
	public function getHelper() {
		return Mage::helper('bradesco/data');
	}
	
}
