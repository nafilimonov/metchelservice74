<?php
/**
 * PayPal
 */
class Shop_Payment_System_Handler10 extends Shop_Payment_System_Handler
{
	private $_SandboxFlag = TRUE;
	private $_Api_Username = "zzz-xxx_api1.hostcms.ru";
	private $_Api_Password = "00000000000000000000";
	private $_Api_Signature = "22222222222222222222222222222222222";
	private $_Api_Endpoint = '';
	private $_Paypal_Url = '';
	private $_Post_Data = '';
	private $_Subject = "sdk-three@sdk.com";
	private $_Default_Currency_Id = 1;

	public function __construct(Shop_Payment_System_Model $oShop_Payment_System_Model)
	{
		parent::__construct($oShop_Payment_System_Model);

		if ($this->_SandboxFlag == TRUE)
		{
			$this->_Api_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
			$this->_Paypal_Url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
		}
		else
		{
			$this->_Api_Endpoint = "https://api-3t.paypal.com/nvp";
			$this->_Paypal_Url = "https://www.paypal.com/cgi-bin/webscr";
		}
	}

	/* Вызывается на 4-ом шаге оформления заказа*/
	public function execute()
	{
		parent::execute();

		$this->printNotification();

		return $this;
	}

	protected function _processOrder()
	{
		parent::_processOrder();

		// Установка XSL-шаблонов в соответствии с настройками в узле структуры
		$this->setXSLs();

		// Отправка писем клиенту и пользователю
		$this->send();

		return $this;
	}

	/* вычисление суммы товаров заказа */
	public function getSumWithCoeff()
	{
		return Shop_Controller::instance()->round(($this->_Default_Currency_Id > 0
				&& $this->_shopOrder->shop_currency_id > 0
			? Shop_Controller::instance()->getCurrencyCoefficientInShopCurrency(
				$this->_shopOrder->Shop_Currency,
				Core_Entity::factory('Shop_Currency', $this->_Default_Currency_Id)
			)
			: 0) * $this->_shopOrder->getAmount() );
	}

	/* обработка ответа от платёжной системы */
	public function paymentProcessing()
	{
		foreach($_POST as $key=>$value)
		{
			$this->_Post_Data .= $key . "=" . urlencode($value) . "&";
		}

		$this->_Post_Data .= "cmd=_notify-validate";
		$curl = curl_init($this->_Paypal_Url);
		curl_setopt ($curl, CURLOPT_HEADER, 0);
		curl_setopt ($curl, CURLOPT_POST, 1);
		curl_setopt ($curl, CURLOPT_POSTFIELDS, $this->_Post_Data);
		curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, 2);
		$sResponse = curl_exec ($curl);
		curl_close ($curl);

		if ($sResponse == 'VERIFIED')
		{
			if (Core_Array::getRequest('receiver_email') == $this->_Subject
			&& Core_Array::getRequest('txn_type') == 'web_accept')
			{
				if($this->_shopOrder->paid)
				{
					return FALSE;
				}

				$sCurrentCurrencyCode = htmlspecialchars(Core_Entity::factory('Shop_Currency', $this->_Default_Currency_Id)->code);

				if(Core_Array::getRequest('mc_gross') == $this->_shopOrder->getAmount()
				&& Core_Array::getRequest('mc_currency') == $sCurrentCurrencyCode)
				{
					$this->_shopOrder->system_information = sprintf("Товар оплачен через PayPal.\nАтрибуты:\nTransaction ID: %s\nAmount: %s %s", Core_Array::getRequest('txn_id'), Core_Array::getRequest('mc_gross'), $sCurrentCurrencyCode);

					$this->_shopOrder->paid();
					$this->setXSLs();
					$this->send();
				}

				return TRUE;
			}

		}
	}

	/* печатает форму отправки запроса на сайт платёжной системы */
	public function getNotification()
	{
		$oSite_Alias = $this->_shopOrder->Shop->Site->getCurrentAlias();
		$site_alias = !is_null($oSite_Alias) ? $oSite_Alias->name : '';
		$shop_path = $this->_shopOrder->Shop->Structure->getPath();
		$handler_url = 'http://' . $site_alias . $shop_path . 'cart/';

		$default_sum = $this->getSumWithCoeff();

		$oShop_Currency = Core_Entity::factory('Shop_Currency', $this->_Default_Currency_Id);

		?>
		<h1>Оплата через систему PayPal</h1>

		<!-- Форма для оплаты -->
		<form id="pay" name="pay" method="post" action="<?php echo $this->_Paypal_Url?>">
			<input name="cmd" type="hidden" value="_xclick" /> <!-- Обязательный параметр. Должен иметь значение "_xclick" -->
			<input name="rm" type="hidden" value="2" />
			<input name="business" type="hidden" value="<?php echo $this->_Subject?>"> <!-- Обязательный параметр. E-mail продавца -->
			<input type="hidden" name="item_name" value="Order N <?php echo $this->_shopOrder->invoice?>"> <!-- Наименование товара, которое будет показано покупателю -->
			<input type="hidden" name="invoice" value="<?php echo $this->_shopOrder->guid?>">
			<input type="hidden" name="amount" value="<?php echo $default_sum?>"> <!-- Сумма к оплате -->
			<input type="hidden" name="item_number" value="1" /> <!-- Идентификатор товара -->
			<input type="hidden" name="currency_code" value="<?php echo htmlspecialchars($oShop_Currency->code)?>" /> <!-- Код валюты -->
			<input type="hidden" name="notify_url" value="<?php echo $handler_url . "?payment=success&order_id=" . $this->_shopOrder->invoice;?>" /> <!-- Ссылка возврата -->
			<input type="hidden" name="return" value="<?php echo $handler_url . "?payment=success"?>" /> <!-- Ссылка возврата -->
			<table>
			<tr>
			<td class="field"><strong><?php echo $default_sum?></strong></td>
			</tr>
			<tr>
			<td align="center"><input type="image" name="submit"
			src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif" /></td>
			</tr>
			<tr>
			<td align="center"><small>Save time. Pay securely without
			sharing your financial information.</small></td>
			</tr>
			</table>

			<!-- Для определения платежной системы на странице корзины --> <input
			type="hidden" name="order_id" value="<?php echo $this->_shopOrder->id?>">

			<div style="clear: both;"></div>
		</form>

		<?php
	}

	public function getInvoice()
	{
		return $this->getNotification();
	}
}
