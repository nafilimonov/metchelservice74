<?php
/**
 * Lead.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../../bootstrap.php');

Core_Auth::authorization($sModule = 'lead');

// Код формы
$iAdmin_Form_Id = 273;
$sAdminFormAction = '/{admin}/lead/shop/item/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

//$oAdmin_Form->show_operations = 0;

$iLeadId = intval(Core_Array::getGet('lead_id', 0));
$oLead = Core_Entity::factory('Lead', $iLeadId);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);

$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Lead_Shop_Item.lead_shop_items_title'))
	->pageTitle(Core::_('Lead_Shop_Item.lead_shop_items_title'))
	->Admin_View(
		Admin_View::getClassName('Admin_Internal_View')
	)
	->addView('view', 'Lead_Shop_Item_Controller_View')
	->view('view');

$oAdmin_Form_Controller->addExternalReplace('{lead_id}', $oLead->id);

$oUser = Core_Auth::getCurrentUser();

if (is_null(Core_Array::getGet('hideMenu')))
{
	if ($oAdmin_Form->Admin_Form_Actions->checkAllowedActionForUser($oUser, 'edit'))
	{
		$windowId = $oAdmin_Form_Controller->getWindowId();

		if ($oLead->shop_id)
		{
			$oShop = $oLead->Shop;

			$oAdmin_Form_Controller->addEntity(
				Admin_Form_Entity::factory('Code')
					->html('
						<div class="lead-add-shop-item">
							<div><button type="button" class="btn btn-gray" onclick="' . $oAdmin_Form_Controller->getAdminActionModalLoad(array('path' => $oAdmin_Form_Controller->getPath(), 'action' => 'edit', 'operation' => 'modal', 'datasetKey' => 0, 'datasetValue' => 0, 'additionalParams' => "lead_id={$oLead->id}", 'width' => '90%')) . '">
								<i class="fa fa-plus no-margin"></i>
							</button></div>
							<div class="add-event">
								<form action="' . Admin_Form_Controller::correctBackendPath("/{admin}/lead/shop/item/index.php") . '" method="POST">
									<div class="input-group">
										<input type="text" id="shop_item_name" name="shop_item_name" class="form-control" placeholder="' . Core::_('Lead_Shop_Item.placeholderShopItemName') . '" />
										<span class="input-group-btn input-group-btn-inner">
											<button id="sendForm" class="btn btn-gray" type="submit" onclick="' . $oAdmin_Form_Controller->getAdminSendForm('addShopItem') . '">
												<i class="fa fa-check no-margin"></i>
											</button>
										</span>
										<input type="hidden" id="shop_item_id" name="shop_item_id" value="0"/>
										<input type="hidden" id="shop_item_rate" name="shop_item_rate" value="0"/>
										<input type="hidden" id="lead" name="lead" value="' . $oLead->id . '"/>
										<input type="hidden" name="hostcms[checked][0][0]" value="1"/>
									</div>
									<script type="text/javascript">
										$(\'#' . $windowId . ' #shop_item_name\').autocompleteShopItem({ shop_id: ' . $oShop->id . ', shop_currency_id: ' . $oShop->shop_currency_id . ' }, function(event, ui) {
											$(\'#' . $windowId . ' #shop_item_id\').val(typeof ui.item.id !== \'undefined\' ? ui.item.id : 0);
											$(\'#' . $windowId . ' #shop_item_rate\').val(typeof ui.item.rate !== \'undefined\' ? ui.item.rate : 0);
										});

										$(\'#' . $windowId . ' :input\').on(\'click\', function() { mainFormLocker.unlock() });
									</script>
								</form>
							</div>
						</div>
					')
			);
		}
		else
		{
			$oAdmin_Form_Controller->addEntity(
				Admin_Form_Entity::factory('Code')
					->html('<div class="alert alert-danger">' . Core::_('Lead_Shop_Item.shop_not_select') . '</div>')
			);
		}
	}
}
else
{
	$oAdmin_Form_Controller->showOperations(FALSE);
}

// Действие "Добавить товар"
$oAdminFormActionAddShopItem = $oAdmin_Form->Admin_Form_Actions->getByName('addShopItem');

if ($oAdminFormActionAddShopItem && $oAdmin_Form_Controller->getAction() == 'addShopItem')
{
	$oControllerAddShopItem = Admin_Form_Action_Controller::factory(
		'Lead_Shop_Item_Controller_Add', $oAdminFormActionAddShopItem
	);

	$sShopItemName = trim(Core_Array::getRequest('shop_item_name'));
	$iLeadId = intval(Core_Array::getRequest('lead_id'));
	$iShopItemId = intval(Core_Array::getRequest('shop_item_id'));
	$iShopItemRate = intval(Core_Array::getRequest('shop_item_rate'));

	$oControllerAddShopItem
		->shop_item_name($sShopItemName)
		->lead_id($iLeadId)
		->shop_item_id($iShopItemId)
		->shop_item_rate($iShopItemRate);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oControllerAddShopItem);
}

// Меню формы
/*$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

if (is_null(Core_Array::getGet('hideMenu')))
{
	// Элементы меню
	$oAdmin_Form_Entity_Menus
		->add(
			Admin_Form_Entity::factory('Menu')
				->name(Core::_('Lead_Shop_Item.lead_shop_item_menu_add'))
				->icon('fa fa-plus')
				->onclick(
					$oAdmin_Form_Controller->getAdminActionModalLoad($oAdmin_Form_Controller->getPath(), 'edit', 'modal', 0, 0, "lead_id={$oLead->id}")
				)
		);
}

// Добавляем все меню контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);*/

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oLead_Shop_Item_Controller_Edit = Admin_Form_Action_Controller::factory(
		'Lead_Shop_Item_Controller_Edit', $oAdmin_Form_Action
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oLead_Shop_Item_Controller_Edit);
}

// Действие удаления
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('markDeleted');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'markDeleted')
{
	$oLead_Controller_Markdeleted = Admin_Form_Action_Controller::factory(
		'Lead_Controller_Markdeleted', $oAdmin_Form_Action
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oLead_Controller_Markdeleted);
}

// Источник данных 0
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Lead_Shop_Item')
);

$oAdmin_Form_Dataset
	/*->addCondition(
		array('select' => array('lead_shop_items.*', array(Core_QueryBuilder::expression('ROUND((`price` +  ROUND(`price` * `rate` / 100, 2)) * `quantity`, 2)'), 'sum')))
	)*/
	->addCondition(
		array('where' => array('lead_shop_items.lead_id', '=', $oLead->id))
	);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

Core_Event::attach('Admin_Form_Controller.onAfterShowContent', array('User_Controller', 'onAfterShowContentPopover'), array($oAdmin_Form_Controller));
Core_Event::attach('Admin_Form_Action_Controller_Type_Edit.onAfterRedeclaredPrepareForm', array('User_Controller', 'onAfterRedeclaredPrepareForm'));

// Показ формы
$oAdmin_Form_Controller->execute();