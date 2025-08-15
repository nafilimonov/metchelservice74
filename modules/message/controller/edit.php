<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Message Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Message
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Message_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
		if (!$object->id)
		{
			$object->message_topic_id = Core_Array::getGet('message_topic_id');
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

		$this->title($this->_object->id
			? Core::_('Message.edit_title')
			: Core::_('Message.add_title')
		);

		$oMainTab = $this->getTab('main');
		$oAdditionalTab = $this->getTab('additional');

		$oMainTab
			->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow4 = Admin_Form_Entity::factory('Div')->class('row'));

		$this->getField('datetime')->divAttr(array('class' => 'form-group col-lg-3 col-md-4 col-sm-5'));
		$oMainTab->move($this->getField('datetime'), $oMainRow1);

		$oAdditionalTab->delete($this->getField('siteuser_id'));

		$oMainRow2->add(
			Admin_Form_Entity::factory('Select')
				->options($this->fillSiteuser())
				->name('siteuser_id')
				->value($this->_object->siteuser_id)
				->caption(Core::_('Message_Topic.sender_siteuser_id'))
				->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'))
		);

		$oMainTab
			->move($this->getField('text'), $oMainRow3)
			->move($this->getField('read'), $oMainRow4);


		return $this;
	}

	/**
	 * Get participants of correspondence
	 * @return array
	 */
	public function fillSiteuser()
	{
		$oSiteuser = $this->_object->Message_Topic->Message_Topics_By_Sender;
		$oSiteuserConversationalPartner = $this->_object->Message_Topic->getConversationalPartner($oSiteuser);

		$aReturn = array();
		$aReturn[$oSiteuser->id] = $oSiteuser->login;

		!is_null($oSiteuserConversationalPartner) && $aReturn[$oSiteuserConversationalPartner->id] = $oSiteuserConversationalPartner->login;

		return $aReturn;
	}
}