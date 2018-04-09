<?php
/**
 * Формирует данные для формы платежной системы Platron
 *
 * @package    DIAFAN.CMS
 * @author     diafan.ru
 * @version    5.4
 * @license    http://www.diafan.ru/license.html
 * @copyright  Copyright (c) 2003-2014 OOO «Диафан» (http://diafan.ru)
 */

if (! defined('DIAFAN'))
{
	include dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/includes/404.php';
}

class Payment_platron_model extends Diafan
{
	/**
     * Формирует данные для формы платежной системы "Platron"
     *
     * @param array $params настройки платежной системы
     * @param array $pay данные о платеже
     * @return void
     */
	public function get($params, $pay)
	{
		require_once 'PG_Signature.php';
		$strLang = 'en';
		if($this->diafan->_languages->site)
			$strLang = 'ru';

		$strDescription = '';
		foreach($pay['details']['goods'] as $arrProduct){
			$strDescription .= $arrProduct['name'];
			if($arrProduct['count'] > 1)
				$strDescription .= "*".$arrProduct['count'];
			$strDescription .= "; ";
		}

		foreach($pay['details']['additional'] as $arrProduct)
			$strDescription .= $arrProduct['name']."; ";

		if(!empty($pay['details']['delivery']))
			$strDescription .= $pay['details']['delivery']['name'];

		if(strlen($strDescription) > 250)
			$strDescription = substr($strDescription, 0, 250)."...";

		$arrFields = array(
			'pg_merchant_id'		=> $params['platron_merchant_id'],
			'pg_order_id'			=> $pay['id'],
			'pg_currency'			=> 'KZT',
			'pg_amount'				=> sprintf('%0.2f',$pay['summ']),
			'pg_lifetime'			=> isset($params['platron_lifetime'])?$params['platron_lifetime']*60:0,
			'pg_testing_mode'		=> ($params['platron_test'])?1:0,
			'pg_description'		=> $strDescription,
			'pg_user_ip'			=> $_SERVER['REMOTE_ADDR'],
			'pg_language'			=> $strLang,
			'pg_check_url'			=> BASE_PATH.'payment/get/platron/index.php?type=check',
			'pg_result_url'			=> BASE_PATH.'payment/get/platron/index.php?type=result',
			'pg_success_url'		=> BASE_PATH.'payment/get/platron/index.php?type=success',
			'pg_failure_url'		=> BASE_PATH.'payment/get/platron/index.php?type=fail',
			'pg_request_method'		=> 'GET',
			'pg_salt'				=> rand(21,43433), // Параметры безопасности сообщения. Необходима генерация pg_salt и подписи сообщения.
		);
		// а еще user_email и user_phone и payment_system
		if(!empty($pay['details']['phone'])){
			preg_match_all("/\d/", $pay['details']['phone'], $array);
			$strPhone = implode('',@$array[0]);
			$arrFields['pg_user_phone'] = $strPhone;
		}

		if(!empty($pay['details']['email'])){
			$arrFields['pg_user_email'] = $pay['details']['email'];
			$arrFields['pg_user_contact_email'] = $pay['details']['email'];
		}

		if(!empty($params['platron_payment_system']))
			$arrFields['pg_payment_system'] = $params['platron_payment_system'];

		$arrFields['pg_sig'] = PG_Signature::make('payment.php', $arrFields, $params['platron_secret_key']);

		echo "<form action='https://api.paybox.money/payment.php' method='POST' name='platronform' id='platronform'>";

		foreach ($arrFields as $name => $value)
		{
			echo "<input type='hidden' name='$name' value='$value' />";
		}
//		echo "<input type='submit' value='оплатить'></form>"; не знаю есть ли такие, у кого не работает javascript
		echo "<script type='text/javascript'>document.platronform.submit();</script>";

		exit;
	}
}
