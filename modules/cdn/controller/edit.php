<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Cdn Editing Controller.
 *
 * @package HostCMS
 * @subpackage Cdn
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Cdn_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
		$this
			->addSkipColumn('balance');

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

		$this->title(
			$this->_object->id
				? Core::_('Cdn.edit_title', $this->_object->name, FALSE)
				: Core::_('Cdn.add_title')
		);

		$oMainTab = $this->getTab('main');

		$oMainTab
			->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'));

		$oMainTab
			->move($this->getField('name')->divAttr(array('class' => 'form-group col-xs-12')), $oMainRow1)
			->move($this->getField('driver')->divAttr(array('class' => 'form-group col-xs-12 col-md-6')), $oMainRow2)
			->move($this->getField('login')->divAttr(array('class' => 'form-group col-xs-12 col-md-6')), $oMainRow2)
			->move($this->getField('key')->divAttr(array('class' => 'form-group col-xs-12 col-md-6')), $oMainRow3);

		$balance = 0;

		if ($this->_object->id)
		{
			try {
				$Cdn_Controller = Cdn_Controller::instance($this->_object->driver);
				$Cdn_Controller->setCdn($this->_object);
				$balance = $Cdn_Controller->getBalance();
			}
			catch (Exception $e){
				Core_Message::show($e->getMessage(), 'error');
			}
		}

		$oBalanceInput = Admin_Form_Entity::factory('Input')
			->name('balance')
			->caption(Core::_('Cdn.balance'))
			->divAttr(array('class' => 'form-group col-xs-12 col-md-2'))
			->disabled('disabled')
			->value($balance);

		$oMainRow3->add($oBalanceInput);

		$oMainTab
			->move($this->getField('balance_datetime')->divAttr(array('class' => 'form-group col-xs-12 col-md-4')), $oMainRow3);

		$oSite = Core_Entity::factory('Site', CURRENT_SITE);

		$oCdnSiteTab = Admin_Form_Entity::factory('Tab')
			->caption(Core::_('Cdn.tab_cdn_site'))
			->name('Cdn_Site');

		$oCdnSiteBlock = Admin_Form_Entity::factory('Div')->class('well with-header');

		$oCdnSiteTab->add($oCdnSiteBlock);

		$oCdnSiteBlock
			->add(
				Admin_Form_Entity::factory('Div')
					->class('header bordered-palegreen')
					->value(Core::_("Cdn.cdn_site_header", $oSite->name))
			);

		$oCdn_Site_Controller_Tab = new Cdn_Site_Controller_Tab($this->_Admin_Form_Controller);

		$oCdnOption = $oCdn_Site_Controller_Tab
			->site_id($oSite->id)
			->cdn_id(intval($this->_object->id))
			->execute();

		$oCdnSiteBlock->add($oCdnOption);

		$this
			->addTabAfter($oCdnSiteTab, $oMainTab);

		return $this;
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @hostcms-event Cdn_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 * @return self
	 */
	protected function _applyObjectProperty()
	{
		parent::_applyObjectProperty();

		$oSite = Core_Entity::factory('Site', CURRENT_SITE);

		// Apply CDN options
		$oCdn_Site_Controller_Tab = new Cdn_Site_Controller_Tab($this->_Admin_Form_Controller);
		$oCdn_Site_Controller_Tab
			->site_id($oSite->id)
			->cdn_id(intval($this->_object->id))
			->applyObjectProperty();

		try {
			$Cdn_Controller = Cdn_Controller::instance($this->_object->driver);
			$Cdn_Controller->setCdn($this->_object);
			$Cdn_Controller->onSave();
			$Cdn_Controller->getBalance();
		}
		catch (Exception $e){
			Core_Message::show($e->getMessage(), 'error');
		}

		Core_Event::notify(get_class($this) . '.onAfterRedeclaredApplyObjectProperty', $this, array($this->_Admin_Form_Controller));

		return $this;
	}
}