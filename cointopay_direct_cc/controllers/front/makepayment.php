<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @since 1.5.0
 */
 
require_once(_PS_MODULE_DIR_ . '/cointopay_direct_cc/vendor/cointopay/init.php');
require_once(_PS_MODULE_DIR_ . '/cointopay_direct_cc/vendor/version.php');

class Cointopay_Direct_CcMakepaymentModuleFrontController extends ModuleFrontController
{
	public function postProcess()
    {	
		$id_order = (int)$_GET['id_order'];
		if($id_order > 0)
		{
			$order = new Order($id_order);
			//echo '<pre>'; print_r($order); exit;
			if(!$order)
			{
				die('Invalid Order ID.');
			}
        	$this->generatePayment($order);
		} 
		else
		{
			die('Invalid Order ID.');
		}
    }
    public function generatePayment($order)
    {	
		$id_order = $order->id;
		$merchant_id = Configuration::get('COINTOPAY_DIRECT_CC_MERCHANT_ID');
        $security_code = Configuration::get('COINTOPAY_DIRECT_CC_SECURITY_CODE');
        $user_currency = Configuration::get('COINTOPAY_DIRECT_CC_CRYPTO_CURRENCY');
        $selected_currency = (isset($user_currency) && !empty($user_currency)) ? $user_currency : 1;
		$link = new Link();
		$currency = new CurrencyCore($order->id_currency);
		$total = (float)$order->total_paid;
		//$total= $total;
        $ctpConfig = array(
          'merchant_id' => $merchant_id,
          'security_code'=>$security_code,
          'selected_currency'=>$selected_currency,
          'user_agent' => 'Cointopay - Prestashop v'._PS_VERSION_.' Extension v'.COINTOPAY_DIRECT_CC_PRESTASHOP_EXTENSION_VERSION
        );

        \Cointopay_Direct_Cc\Cointopay_Direct_Cc::config($ctpConfig);
        $order = \Cointopay_Direct_Cc\Merchant\Order::createOrFail(array(
            'order_id'         => $id_order,
            'price'            => $total,
            'currency'         => $this->currencyCode($currency->iso_code),
            'cancel_url'       => $this->flashEncode($this->context->link->getModuleLink('cointopay_direct_cc', 'cancel')),
            'callback_url'     => $this->flashEncode($this->context->link->getModuleLink('cointopay_direct_cc', 'callback')),
            'title'            => Configuration::get('PS_SHOP_NAME') . ' Order #' . $id_order,
            'selected_currency'=> $selected_currency
        ));
         
        if (isset($order)) {

		preg_match_all($pattern, $order->PaymentDetailCConly, $matches); 
		$regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
	    if(preg_match_all("/$regexp/siU", $order->PaymentDetailCConly, $matches, PREG_SET_ORDER)) {
			foreach($matches as $match) {
			@header("Location: ".$match[2]);die;
			}
	    }
		}
		 die();
    }
	
	/**
     * URL encode to UTF-8
     *
     * @param $input
     * @return string
     */
    public function flashEncode($input)
    {
        return rawurlencode(utf8_encode($input));
    }

    /**
     * Currency code
     * @param $isoCode
     * @return string
     */
    public function currencyCode($isoCode)
    {
        $currencyCode='';

        if (isset($isoCode) && ($isoCode == 'RUB')) {
            $currencyCode='RUR';
        } else {
            $currencyCode= $isoCode;
        }
        
        return $currencyCode;
    }
}