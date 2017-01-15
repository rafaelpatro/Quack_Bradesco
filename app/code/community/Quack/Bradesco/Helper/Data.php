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
class Quack_Bradesco_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function getStatusMessage($status) {
		$message = "situação desconhecida {$status}";
		switch ($status) {
			case '81': $message = "Transferência Sucesso"; break;
		}
		return $message;
	}
	
	public function getTypeMessage($type) {
		$message = is_numeric($type) ? "método de pagamento desconhecido {$type}" : $type;
		switch ($type) {
			case '101': $message = "Fácil - pagto em 1 vez"; break;
			case '102': $message = "Fácil - pagto parcelado adm pela loja"; break;
			case '103': $message = "Fácil - pagto parcelado adm pelo banco"; break;
			case '104': $message = "Fácil - pagto parcelado financiado pelo banco"; break;
		}
		return $message;
	}
	
	public function getTypeCc($type) {
		$message = "tipo de cartão desconhecido {$type}";
		switch ($type) {
			case 'BradescoVisa'                     : $message = "Bradesco Visa"; break;
			case 'BradescoPoupCard'                 : $message = "Bradesco Poup Card"; break;
			case 'BradescoMasterCard'               : $message = "Bradesco Mastercard"; break;
			case 'BradescoDebito'                   : $message = "Bradesco Débito"; break;
			case '102BradescoPoupCardCertless'      : $message = "Bradesco PoupCard Certless (Pagto Fácil)"; break;
			case '101BradescoDebitoCertless'        : $message = "Bradesco Debito Certless (Pagto Fácil)"; break;
			case '007CartaoPresenteCertless'        : $message = "Cartão Presente Certless (Pagto Fácil)"; break;
			case '006BradescoMasterCardCertless'    : $message = "Bradesco MasterCard Certless (Pagto Fácil)"; break;
			case '005BradescoVisaCertless Bradesco' : $message = "Visa Certless (Pagto Fácil)"; break;
		}
		return $message;
	}
	
	public function strtoascii($str) {
		setlocale(LC_ALL, 'pt_BR.utf8');
		return iconv('UTF-8', 'ASCII//TRANSLIT', $str);
	}
	
	public function getExpireDate($prazo) {
		$tmCompra		= time();
		$tmVencimento	= $tmCompra + ($prazo * 24 * 60 * 60);
		$diaSemanaCompra		= date('N', $tmCompra);
		$diaSemanaVencimento	= date('N', $tmVencimento);
		if ($diaSemanaVencimento < $diaSemanaCompra
				|| $diaSemanaVencimento == 6) {
			$tmVencimento+= 2 * 24 * 60 * 60;
		} elseif ($diaSemanaVencimento == 7) {
			$tmVencimento+= 1 * 24 * 60 * 60;
		}
		$dtVenc = date('dmY', $tmVencimento);
		return $dtVenc;
	}
	
	public function encrypt($orderDescList) {
		$transaction = '';
		foreach ($orderDescList as $key => $value) {
			$transaction.= is_null($value) ? $value : "<{$key}>=({$this->strtoascii($value)})";
		}
		return $transaction;
	}
}
?>
