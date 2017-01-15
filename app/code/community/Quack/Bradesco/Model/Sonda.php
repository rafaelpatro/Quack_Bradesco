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
class Quack_Bradesco_Model_Sonda extends Varien_Object {
	/**
	 * @var int
	 */
	public $MerchantId;
	/**
	 * @var string
	 */
	public $Data;
	/**
	 * @var string
	 */
	public $Manager;
	/**
	 * @var string
	 */
	public $Passwd;
	/**
	 * @var string
	 */
	public $NumOrder;
	/**
	 * @var string
	 */
	protected $Numero;
	/**
	 * @var int
	 */
	protected $Valor;
	/**
	 * @var int
	 */
	protected $Status;
	/**
	 * @var int
	 */
	protected $Erro;
	
	/**
	 * @return int
	 */
	public function getMerchantId() {
		return $this->MerchantId;
	}
	
	/**
	 * @param $MerchantId int
	 */
	public function setMerchantId($MerchantId) {
		$this->MerchantId = $MerchantId;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getData($key='', $index=null) {
		return $this->Data;
	}
	
	/**
	 * @param $Data string
	 */
	public function setData($Data, $value=null) {
		$this->Data = $Data;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getManager() {
		return $this->Manager;
	}
	
	/**
	 * @param $Manager string
	 */
	public function setManager($Manager) {
		$this->Manager = $Manager;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getPasswd() {
		return $this->Passwd;
	}
	
	/**
	 * @param $Passwd string
	 */
	public function setPasswd($Passwd) {
		$this->Passwd = $Passwd;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getNumOrder() {
		return $this->NumOrder;
	}
	
	/**
	 * @param $NumOrder string
	 */
	public function setNumOrder($NumOrder) {
		$this->NumOrder = $NumOrder;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getNumero() {
		return $this->Numero;
	}
	
	/**
	 * @param $Numero string
	 */
	public function setNumero($Numero) {
		$this->Numero = $Numero;
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getValor() {
		return $this->Valor;
	}
	
	/**
	 * @param $Valor int
	 */
	public function setValor($Valor) {
		$this->Valor = $Valor;
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getStatus() {
		return $this->Status;
	}
	
	/**
	 * @param $Status int
	 */
	public function setStatus($Status) {
		$this->Status = $Status;
		return $this;
	}
	
	/**
	 * @return int
	 */
	public function getErro() {
		return $this->Erro;
	}
	
	/**
	 * @param $Erro int
	 */
	public function setErro($Erro) {
		$this->Erro = $Erro;
		return $this;
	}
	
	
}