<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Shop_Transaction Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Shop_Transaction_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
		$this->addSkipColumn('amount_base_currency');

		if (!$object->id)
		{
			$object->siteuser_id = intval(Core_Array::getGet('siteuser_id'));
			$object->shop_id = intval(Core_Array::getGet('shop_id'));
		}

		return parent::setObject($object);
	}

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
			->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow4 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow5 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow6 = Admin_Form_Entity::factory('Div')->class('row'));

		$oMainTab->move($this->getField('description'), $oMainRow1);
		$oMainTab->move($this->getField('active'), $oMainRow2);

		$this->getField('datetime')->divAttr(array('class' => 'form-group col-lg-3 col-md-4 col-sm-5'));
		$oMainTab->move($this->getField('datetime'), $oMainRow3);

		$this->getField('amount')->divAttr(array('class' => 'form-group col-lg-3 col-md-4 col-sm-5'));

		$oAdditionalTab->delete($this->getField('shop_currency_id'));

		// Список сайтов
		$oSelect_Currencies = Admin_Form_Entity::factory('Select');

		$oSelect_Currencies
			->options(
				Shop_Controller::fillCurrencies()
			)
			->name('shop_currency_id')
			->value($this->_object->shop_currency_id)
			->caption(Core::_('Shop_Siteuser_Transaction.currency_name'))
			->divAttr(array('class' => 'form-group col-lg-2 col-md-2 col-sm-3'));

		$oMainTab->move($this->getField('amount'), $oMainRow4);
		$oMainRow4->add($oSelect_Currencies);

		$this->getField('shop_order_id')->divAttr(array('class' => 'form-group col-lg-3 col-md-4 col-sm-5'));
		$oAdditionalTab->move($this->getField('shop_order_id'), $oMainRow5);

		$oMainTab->delete($this->getField('type'));

		// Список типов транзакций
		$oSelect_Types = Admin_Form_Entity::factory('Select');
		$oSelect_Types
			->options(array(Core::_('Shop_Siteuser_Transaction.type_typical'), Core::_('Shop_Siteuser_Transaction.type_affiliate_bonus'), Core::_('Shop_Siteuser_Transaction.type_bonus')))
			->name('type')
			->value($this->_object->type)
			->caption(Core::_('Shop_Siteuser_Transaction.type'))
			->divAttr(array('class' => 'form-group col-lg-3 col-md-4 col-sm-5'));

		$oMainRow6->add($oSelect_Types);

		$this->title($this->_object->id
			? Core::_('Shop_Siteuser_Transaction.transaction_edit_form')
			: Core::_('Shop_Siteuser_Transaction.transaction_add_form')
		);

		return $this;
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @return self
	 * @hostcms-event Siteuser_Shop_Transaction_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		parent::_applyObjectProperty();

		$oShop = $this->_object->Shop;

		// Определяем коэффициент пересчета
		$fCurrencyCoefficient = $this->_object->shop_currency_id > 0 && $oShop->shop_currency_id > 0
			? Shop_Controller::instance()->getCurrencyCoefficientInShopCurrency(
				$this->_object->Shop_Currency, $oShop->Shop_Currency
			)
			: 0;

		$this->_object->amount_base_currency = $this->_object->amount * $fCurrencyCoefficient;
		$this->_object->save();

		Core_Event::notify(get_class($this) . '.onAfterRedeclaredApplyObjectProperty', $this, array($this->_Admin_Form_Controller));

		return $this;
	}
}