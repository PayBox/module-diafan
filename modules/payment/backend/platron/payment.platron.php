<?php
/**
 * Обработка данных, полученных от системы Platron
 * 
 * @package    DIAFAN.CMS
 * @author     diafan.ru
 * @version    5.4
 * @license    http://www.diafan.ru/license.html
 * @copyright  Copyright (c) 2003-2014 OOO «Диафан» (http://diafan.ru)
 */

include "PG_Signature.php";
if(!empty($_POST))
	$arrRequest = $_POST;
else
	$arrRequest = $_GET;

if (! defined('DIAFAN'))
{
	include dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/includes/404.php';
}

if (empty($arrRequest["pg_order_id"]))
{
	Custom::inc('includes/404.php');
}
unset($arrRequest['rewrite']);
$pay = $this->diafan->_payment->check_pay($_REQUEST["pg_order_id"], 'platron');

$thisScriptName = PG_Signature::getOurScriptName();
if (empty($arrRequest['pg_sig']) || !PG_Signature::check($arrRequest['pg_sig'], $thisScriptName, $arrRequest, $pay['params']['platron_secret_key']))
	die("Wrong signature");

if($arrRequest['type'] == 'check'){
	$bCheckResult = 0;
	if(sprintf('%0.2f',$arrRequest['pg_amount']) != sprintf('%0.2f',$pay['summ']))
		$error_desc = "Неверная сумма";
	else
		$bCheckResult = 1;

	$arrResponse['pg_salt']              = $arrRequest['pg_salt']; // в ответе необходимо указывать тот же pg_salt, что и в запросе
	$arrResponse['pg_status']            = $bCheckResult ? 'ok' : 'error';
	$arrResponse['pg_error_description'] = $bCheckResult ?  ""  : $error_desc;
	$arrResponse['pg_sig']				 = PG_Signature::make($thisScriptName, $arrResponse, $pay['params']['platron_secret_key']);

	$objResponse = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><response/>');
	$objResponse->addChild('pg_salt', $arrResponse['pg_salt']);
	$objResponse->addChild('pg_status', $arrResponse['pg_status']);
	$objResponse->addChild('pg_error_description', $arrResponse['pg_error_description']);
	$objResponse->addChild('pg_sig', $arrResponse['pg_sig']);
	
	header("Content-type: text/xml");
	echo $objResponse->asXML();
	die();
}
elseif($arrRequest['type'] == 'result'){
	$bResult = 0;
	if(sprintf('%0.2f',$arrRequest['pg_amount']) != sprintf('%0.2f',$pay['summ']))
		$strResponseDescription = "Неверная сумма";
	else {
		$bResult = 1;
		$strResponseStatus = 'ok';
		$strResponseDescription = "Оплата принята";
		if ($arrRequest['pg_result'] == 1) {
			// Установим статус оплачен
			$this->diafan->_payment->success($pay, 'pay');
		}
		else{
			// Не удачная оплата - статус не изменяется
			
		}
	}
	
	if(!$bResult)
		if($arrRequest['pg_can_reject'] == 1)
			$strResponseStatus = 'rejected';
		else
			$strResponseStatus = 'error';

	$objResponse = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><response/>');
	$objResponse->addChild('pg_salt', $arrRequest['pg_salt']); // в ответе необходимо указывать тот же pg_salt, что и в запросе
	$objResponse->addChild('pg_status', $strResponseStatus);
	$objResponse->addChild('pg_description', $strResponseDescription);
	$objResponse->addChild('pg_sig', PG_Signature::makeXML($thisScriptName, $objResponse, $pay['params']['platron_secret_key']));
	
	header("Content-type: text/xml");
	echo $objResponse->asXML();
	die();
}
elseif($arrRequest['type'] == 'success'){
	$this->diafan->_payment->success($pay, 'redirect');
}
else{
	$this->diafan->_payment->fail($pay);
}
