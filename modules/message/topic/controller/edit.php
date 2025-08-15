<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Message_Topic Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Message
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Message_Topic_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Prepare backend item's edit form
	 *
	 * @return self
	 */
	protected function _prepareForm()
	{
		parent::_prepareForm();

		$this->title($this->_object->id
			? Core::_('Message_Topic.edit_title', $this->_object->subject, FALSE)
			: Core::_('Message_Topic.add_title')
		);

		$oMainTab = $this->getTab('main');
		$oAdditionalTab = $this->getTab('additional');

		$oMainTab
			->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow4 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow5 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow6 = Admin_Form_Entity::factory('Div')->class('row'));

		$oMainTab->move($this->getField('subject'), $oMainRow1);

		$this->getField('datetime')->divAttr(array('class' => 'form-group col-lg-3 col-md-4 col-sm-5'));
		$oMainTab->move($this->getField('datetime'), $oMainRow2);

		$oAdditionalTab->delete($this->getField('sender_siteuser_id'));

		$optionsSender = $this->_object->id && $this->_object->sender_siteuser_id
			? array($this->_object->Message_Topics_By_Sender->id => $this->_object->Message_Topics_By_Sender->login . ' [' . $this->_object->Message_Topics_By_Sender->id . ']')
			: array(0);

		$oSender_Form_Entity_Select = Admin_Form_Entity::factory('Select')
			->options($optionsSender)
			->name('sender_siteuser_id')
			->value($this->_object->sender_siteuser_id)
			->caption(Core::_('Message_Topic.sender_siteuser_id'))
			->divAttr(array('class' => 'col-xs-12'))
			->class('siteuser-tag')
			->style('width: 100%');

		$oMainRow3
			->add(
				Admin_Form_Entity::factory('Div')
					->class('form-group col-xs-12 col-sm-6 no-padding')
					->add($oSender_Form_Entity_Select)
			);

		// Show button
		Siteuser_Controller_Edit::addSiteuserSelect2($oSender_Form_Entity_Select, $this->_object->Message_Topics_By_Sender, $this->_Admin_Form_Controller);

		$oAdditionalTab->delete($this->getField('recipient_siteuser_id'));

		$optionsRecipient = $this->_object->id && $this->_object->recipient_siteuser_id
			? array($this->_object->Message_Topics_By_Recipient->id => $this->_object->Message_Topics_By_Recipient->login . ' [' . $this->_object->Message_Topics_By_Recipient->id . ']')
			: array(0);

		$oRecipient_Form_Entity_Select = Admin_Form_Entity::factory('Select')
			->options($optionsRecipient)
			->name('recipient_siteuser_id')
			->value($this->_object->recipient_siteuser_id)
			->caption(Core::_('Message_Topic.recipient_siteuser_id'))
			->divAttr(array('class' => 'col-xs-12'))
			->class('siteuser-tag')
			->style('width: 100%');

		$oMainRow3
			->add(
				Admin_Form_Entity::factory('Div')
					->class('form-group col-xs-12 col-sm-6 no-padding')
					->add($oRecipient_Form_Entity_Select)
			);

		// Show button
		Siteuser_Controller_Edit::addSiteuserSelect2($oRecipient_Form_Entity_Select, $this->_object->Message_Topics_By_Recipient, $this->_Admin_Form_Controller);

		$oMainTab
			->delete($this->getField('count_sender_unread'))
			->delete($this->getField('count_recipient_unread'));

		$oMainRow4
			->add(
				Admin_Form_Entity::factory('Input')
					->name('count_sender_unread')
					->value($this->_object->count_sender_unread)
					->caption(Core::_('Message_Topic.count_sender_unread'))
					->disabled('disabled')
					->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'))
			)
			->add(
				Admin_Form_Entity::factory('Input')
					->name('count_recipient_unread')
					->value($this->_object->count_recipient_unread)
					->caption(Core::_('Message_Topic.count_recipient_unread'))
					->disabled('disabled')
					->divAttr(array('class' => 'form-group col-xs-12 col-sm-6'))
			);

		$oMainTab
			->move($this->getField('deleted_by_sender'), $oMainRow5)
			->move($this->getField('deleted_by_recipient'), $oMainRow6);

		return $this;
	}

	/**
	 * Fill site users list
	 * @param int $site_id site ID
	 * @return array
	 */
	public function fillSiteuser($site_id = CURRENT_SITE)
	{
		$site_id = intval($site_id);

		$aSiteusers = Core_Entity::factory('Site', $site_id)->Siteusers->findAll();

		$aReturn = array();

		foreach ($aSiteusers as $oSiteuser)
		{
			$aReturn[$oSiteuser->id] = $oSiteuser->login;
		}

		return $aReturn;
	}
}