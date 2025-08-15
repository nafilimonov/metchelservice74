<?php
/**
 * Site users.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../../../bootstrap.php');

Core_Auth::authorization($sModule = 'siteuser');

// Код формы
$iAdmin_Form_Id = 243;
$sAdminFormAction = '/{admin}/siteuser/shop/cart/item/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$siteuser_id = Core_Array::getGet('siteuser_id');
$oSiteuser = Core_Entity::factory('Siteuser', $siteuser_id);

$shop_id = Core_Array::getGet('shop_id');
$oShop = Core_Entity::factory('Shop', $shop_id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Siteuser.shop_siteuser_cart_item_title', $oSiteuser->login))
	->pageTitle(Core::_('Siteuser.shop_siteuser_cart_item_title', $oSiteuser->login));

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

$sSiteuserPath = '/{admin}/siteuser/index.php';

$additionalParams = 'siteuser_id=' . intval($siteuser_id);
$additionalParamItems = 'siteuser_id=' . intval($siteuser_id) . '&shop_id=' . intval($shop_id);

// Строка навигации для пользователей
$oAdmin_Form_Entity_Breadcrumbs
	->add(
		Admin_Form_Entity::factory('Breadcrumb')
			->name(Core::_('Siteuser.siteusers'))
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($sSiteuserPath, NULL, NULL, '')
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($sSiteuserPath, NULL, NULL, '')
			)
	)
	->add(
		Admin_Form_Entity::factory('Breadcrumb')
			->name(Core::_('Siteuser.shop_siteuser_cart_title', $oSiteuser->login, FALSE))
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref('/{admin}/siteuser/shop/cart/index.php', NULL, NULL, $additionalParams)
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax('/{admin}/siteuser/shop/cart/index.php', NULL, NULL, $additionalParams)
			)
	)->add(
		Admin_Form_Entity::factory('Breadcrumb')
			->name(Core::_('Siteuser.shop_siteuser_cart_item_title', $oSiteuser->login, FALSE))
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, $additionalParamItems)
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, $additionalParamItems)
			)
	);

$windowId = $oAdmin_Form_Controller->getWindowId();

$oAdmin_Form_Controller->addEntity(
	Admin_Form_Entity::factory('Code')
		->html('
			<div class="add-event margin-bottom-20">
				<form action="'. Admin_Form_Controller::correctBackendPath("/{admin}/siteuser/shop/cart/item/index.php") . '" method="POST">
					<div class="input-group">
						<input type="text" id="shop_item_name" name="shop_item_name" class="form-control" placeholder="' . Core::_('Siteuser.placeholderShopItemName') . '">
						<span class="input-group-btn">
							<button id="sendForm" class="btn btn-gray" type="submit" onclick="' . $oAdmin_Form_Controller->getAdminSendForm('addShopItem', NULL, 'siteuser_id=' . $siteuser_id . '&shop_id=' . $shop_id) . '">
								<i class="fa fa-check no-margin"></i>
							</button>
						</span>
						<input type="hidden" id="shop_item_id" name="shop_item_id" value="0"/>
						<input type="hidden" name="hostcms[checked][0][0]" value="1"/>
					</div>
				</form>
			</div>
			<script type="text/javascript">
				$(\'#' . $windowId . ' #shop_item_name\').autocompleteShopItem({ shop_id: ' . $oShop->id . ',  shop_currency_id: ' . $oShop->shop_currency_id . '}, function(event, ui) {
					$(\'#' . $windowId . ' #shop_item_id\').val(typeof ui.item.id !== \'undefined\' ? ui.item.id : 0);
				});
			</script>
		')
);

// Добавляем все хлебные крошки контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Действие "Применить"
$oAdminFormActionApply = $oAdmin_Form->Admin_Form_Actions->getByName('apply');

if ($oAdminFormActionApply && $oAdmin_Form_Controller->getAction() == 'apply')
{
	$oControllerApply = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Apply', $oAdminFormActionApply
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oControllerApply);
}

// Действие "Добавить товар"
$oAdminFormActionAddShopItem = $oAdmin_Form->Admin_Form_Actions->getByName('addShopItem');

if ($oAdminFormActionAddShopItem && $oAdmin_Form_Controller->getAction() == 'addShopItem')
{
	$oControllerAddShopItem = Admin_Form_Action_Controller::factory(
		'Siteuser_Shop_Cart_Controller_Add', $oAdminFormActionAddShopItem
	);

	$shop_item_id = intval(Core_Array::getRequest('shop_item_id'));
	$siteuser_id = intval(Core_Array::getGet('siteuser_id'));
	$sShopItemName = trim(Core_Array::getRequest('shop_item_name'));

	$oControllerAddShopItem
		->shop_item_id($shop_item_id)
		->siteuser_id($siteuser_id)
		->shop_item_name($sShopItemName);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oControllerAddShopItem);
}

// Источник данных 0
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Shop_Cart')
);

// Ограничение по сайту
$oAdmin_Form_Dataset->addCondition(
	array(
		'select' => array(
			'shop_carts.id',
			'shop_carts.shop_item_id',
			'shop_carts.postpone',
			'shop_carts.siteuser_id',
			'shop_carts.shop_id',
			'shop_items.name',
			'shop_items.marking',
			'shop_carts.quantity',
			'shop_items.price',
		)
	)
)
->addCondition(
	array('join' =>
		array('shop_items', 'shop_carts.shop_item_id', '=', 'shop_items.id')
	)
)
->addCondition(
	array('where' =>
		array('shop_carts.shop_id', '=', $shop_id)
	)
)
->addCondition(
	array('where' =>
		array('shop_items.deleted', '=', 0)
	)
)
->addCondition(
	array('where' =>
		array('shop_carts.siteuser_id', '=', $siteuser_id)
	)
);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

$oAdmin_Form_Controller->addExternalReplace('{siteuser_id}', $siteuser_id);

Core_Event::attach('Admin_Form_Controller.onBeforeShowFooter', array('Siteuser_Shop_Cart_Observer', 'onBeforeShowFooter'));

class Siteuser_Shop_Cart_Observer
{
	static public function onBeforeShowFooter($oAdmin_Form_Controller, $args)
	{
		$siteuser_id = Core_Array::getGet('siteuser_id', 0);
		$shop_id = Core_Array::getGet('shop_id', 0);

		if ($siteuser_id && $shop_id)
		{
			$oShop = Core_Entity::factory('Shop', $shop_id);

			$countShopCarts = 0;
			$amount = 0;

			$oShop_Carts = Core_Entity::factory('Shop_Cart');
			$oShop_Carts->queryBuilder()
				->join('shop_items', 'shop_carts.shop_item_id', '=', 'shop_items.id')
				->where('shop_carts.siteuser_id', '=', $siteuser_id)
				->where('shop_carts.shop_id', '=', $oShop->id)
				->where('shop_carts.postpone', '=', 0)
				->where('shop_items.deleted', '=', 0);

			$aShop_Carts = $oShop_Carts->findAll(FALSE);
			foreach ($aShop_Carts as $oShop_Cart)
			{
				$countShopCarts += $oShop_Cart->quantity;

				$amount += $oShop_Cart->getPrice() * $oShop_Cart->quantity;
			}

			$sAmount = htmlspecialchars(
				$oShop->Shop_Currency->formatWithCurrency($amount)
			);

			?>
			<div class="siteuser-shop-cart">
				<span><?php echo Core::_('Siteuser.total', $countShopCarts, $sAmount)?></span>
			</div>
			<?php
		}
	}
}

// Показ формы
$oAdmin_Form_Controller->execute();