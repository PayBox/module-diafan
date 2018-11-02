<?php
/**
 * Настройки платежной системы Paybox для административного интерфейса
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

class Payment_paybox_admin
{
	public $config;

	public function __construct()
	{
		$this->config = array(
			"name" => 'Paybox',
			"params" => array(
                'paybox_merchant_id' => 'Номер магазина',
                'paybox_secret_key' => 'Секретный ключ',
                'paybox_lifetime' => 'Время жизни счета в минутах. Максимально 7 дней',
				'paybox_test' => array('name' => 'Тестовый режим', 'type' => 'checkbox'),
				'paybox_payment_system' => 'Название платежной системы, на которую указывает метод',
			)
		);
	}
}
