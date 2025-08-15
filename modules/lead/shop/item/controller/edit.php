<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead_Shop_Item_Controller_Edit
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Lead_Shop_Item_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Prepare backend item's edit form
	 *
	 * @return self
	 */
	protected function _prepareForm()
	{
		parent::_prepareForm();

		$oMainTab = $this->getTab('main');
		$oAdditionalTab = $this->getTab('additional');

		$oMainTab
			->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'));

		$iLeadId = intval(Core_Array::getGet('lead_id'));

		$oLead = Core_Entity::factory('Lead', $iLeadId);

		$oShop = $oLead->Shop;

		$oMainTab->move($this->getField('datetime')->divAttr(array('class' => 'form-group col-sm-3 col-xs-12')), $oMainRow1);
		$oMainTab->move($this->getField('quantity')->divAttr(array('class' => 'form-group col-sm-2 col-xs-12')), $oMainRow1);

		$oMainTab->delete($this->getField('price'));

		// Создаем поле валюты как выпадающий список
		$oShopCurrencySelect = Admin_Form_Entity::factory('Select')
			->divAttr(array('class' => 'form-group'))
			->options(
				Shop_Controller::fillCurrencies()
			)
			->id('shopCurrencyId')
			->name('shop_currency_id')
			->value($this->_object->shop_currency_id);

		$oDiv_Lead_Shop_Item_Price = Admin_Form_Entity::factory('Div')
			->class('form-group col-xs-12 col-sm-3 amount-currency')
			->add(Admin_Form_Entity::factory('Input')
				->id('itemPrice')
				->name('price')
				->value($this->_object->price)
				->caption(Core::_('Lead_Shop_Item.price'))
				->divAttr(array('class' => 'form-group'))
			)
			->add(
				$oShopCurrencySelect
			);

		$oMainRow1->add($oDiv_Lead_Shop_Item_Price);

		$oMainTab->move($this->getField('rate')
				->id('itemRate')
				->divAttr(array('class' => 'form-group col-xs-5 col-sm-1')),
			$oMainRow1
		);

		$oMainRow1->add(Admin_Form_Entity::factory('Span')
			->value('%')
			->style("font-size: 200%")
			->divAttr(array(
				'class' => 'form-group col-lg-3 col-md-3 col-sm-3 col-xs-2',
				'style' => 'padding-top: 20px'
			))
		);

		$this->getField('name')->id('itemInput')->format(array('minlen' => array('value' => 0)));

		$oMainTab->moveAfter($this->getField('rate'), $oDiv_Lead_Shop_Item_Price);

		$oAdditionalTab
			->delete($this->getField('lead_id'))
			->delete($this->getField('shop_currency_id'))
			->delete($this->getField('shop_warehouse_id'));

		$oMainRow2->add(
			Admin_Form_Entity::factory('Select')
				->caption(Core::_('Lead_Shop_Item.shop_warehouse_id'))
				->options(
					$this->_fillWarehousesList($oShop->id)
				)
				->name('shop_warehouse_id')
				->value($this->_object->shop_warehouse_id)
				->divAttr(array('class' => 'form-group col-xs-6'))
		);

		$oMainTab->delete($this->getField('type'));

		$oMainRow2->add(
			Admin_Form_Entity::factory('Select')
				->caption(Core::_('Lead_Shop_Item.type'))
				->options(
					array(
						Core::_('Shop_Order_Item.order_item_type_caption0'),
						Core::_('Shop_Order_Item.order_item_type_caption1'),
						Core::_('Shop_Order_Item.order_item_type_caption2'),
						Core::_('Shop_Order_Item.order_item_type_caption3'),
						Core::_('Shop_Order_Item.order_item_type_caption4'),
						Core::_('Shop_Order_Item.order_item_type_caption5'),
						Core::_('Shop_Order_Item.order_item_type_caption6')
					)
				)
				->name('type')
				->value($this->_object->type)
				->divAttr(array('class' => 'form-group col-xs-6'))
		);

		$oMainTab->move($this->getField('marking')->id('itemMarking')->divAttr(array('class' => 'form-group col-xs-6')), $oMainRow3);

		$oAdditionalTab->move($this->getField('shop_item_id'), $oMainTab);

		$oMainTab->move($this->getField('shop_item_id')->id('itemId')->divAttr(array('class' => 'form-group col-xs-6')), $oMainRow3);

		$windowId = $this->_Admin_Form_Controller->getWindowId();

		$oCore_Html_Entity_Script = Core_Html_Entity::factory('Script')
			->value("$('#{$windowId} #itemInput').autocompleteShopItem({ shop_id: '{$oShop->id}', shop_currency_id: '{$oShop->shop_currency_id}' }, function(event, ui) {
				$('#{$windowId} #itemId').val(typeof ui.item.id !== 'undefined' ? ui.item.id : 0);
				$('#{$windowId} #itemPrice').val(typeof ui.item.price !== 'undefined' ? ui.item.price : 0);
				$('#{$windowId} #shopCurrencyId').val(typeof ui.item.currency_id !== 'undefined' ? ui.item.currency_id : 0);
				$('#{$windowId} #itemRate').val(typeof ui.item.rate !== 'undefined' ? ui.item.rate : 0);
				$('#{$windowId} #itemMarking').val(typeof ui.item.marking !== 'undefined' ? ui.item.marking : 0);
			});");

		$oMainTab->add($oCore_Html_Entity_Script);

		$oMainTab->add(
			Admin_Form_Entity::factory('Input')
				->type('hidden')
				->name('lead_id')
				->value($iLeadId)
		);

		$this->title($this->_object->id
			? Core::_('Lead_Shop_Item.lead_shop_items_edit_form_title', $oLead->getFullName(), FALSE)
			: Core::_('Lead_Shop_Item.lead_shop_items_add_form_title', $oLead->getFullName(), FALSE)
		);

		return $this;
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @return self
	 * @hostcms-event Lead_Shop_Item_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		// New order item
		if (!$this->_object->id
			&& ($shop_item_id = Core_Array::get($this->_formValues, 'shop_item_id'))
			&& !is_null($oShop_Item = Core_Entity::factory('Shop_Item')->find($shop_item_id, FALSE)))
		{
				Core_Array::get($this->_formValues, 'name') == '' && $this->_formValues['name'] = $oShop_Item->name;
				floatval(Core_Array::get($this->_formValues, 'quantity')) == 0.0 && $this->_formValues['quantity'] = 1.0;
				floatval(Core_Array::get($this->_formValues, 'price')) == 0.0 && $this->_formValues['price'] = $oShop_Item->price;
				Core_Array::get($this->_formValues, 'marking') == '' && $this->_formValues['marking'] = $oShop_Item->marking;
		}

		$this->_formValues['lead_id'] = intval(Core_Array::getPost('lead_id'));
		$this->_formValues['shop_currency_id'] = intval(Core_Array::getPost('shop_currency_id'));

		parent::_applyObjectProperty();

		Core_Event::notify(get_class($this) . '.onAfterRedeclaredApplyObjectProperty', $this, array($this->_Admin_Form_Controller));

		return $this;
	}

	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return mixed
	 */
	public function execute($operation = NULL)
	{
		// $iLeadId = intval(Core_Array::getRequest('lead_id'));

		$sJsRefresh = '<script>
			$(function() {
				var jA = $("li[data-type=timeline] a");
				if (jA.length)
				{
					$.adminLoad({ path: jA.data("path"), additionalParams: jA.data("additional"), windowId: jA.data("window-id") });
				}
			});
		</script>';

		switch ($operation)
		{
			case 'save':
			case 'saveModal':
			case 'apply':
			case 'applyModal':

				$operation == 'saveModal' && $this->addMessage($sJsRefresh);
				$operation == 'applyModal' && $this->addContent($sJsRefresh);
			break;
			case 'markDeleted':
				$this->_object->markDeleted();
				$this->addContent($sJsRefresh);
			break;
		}

		return parent::execute($operation);
	}

	/**
	 * Fill warehouses list
	 * @param int $iShopId shop ID
	 * @return array
	 */
	protected function _fillWarehousesList($iShopId)
	{
		$aReturn = array(" … ");

		$oShop_Warehouses = Core_Entity::factory('Shop_Warehouse');
		$oShop_Warehouses->queryBuilder()
			->where('shop_warehouses.shop_id', '=', $iShopId)
			->clearOrderBy()
			->orderBy('shop_warehouses.sorting')
			->orderBy('shop_warehouses.id');

		$aShop_Warehouses = $oShop_Warehouses->findAll(FALSE);

		foreach ($aShop_Warehouses as $oShop_Warehouse)
		{
			$aReturn[$oShop_Warehouse->id] = '[' . $oShop_Warehouse->id . '] ' . $oShop_Warehouse->name;
		}

		return $aReturn;
	}
}