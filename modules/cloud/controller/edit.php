<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Cloud Backend Editing Controller.
 *
 * @package HostCMS
 * @subpackage Cloud
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Cloud_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Prepare backend item's edit form
	 *
	 * @return self
	 */
	protected function _prepareForm()
	{
		parent::_prepareForm();

		$this->title(is_null($this->_object->id)
			? Core::_('Cloud.add_title')
			: Core::_('Cloud.edit_title', $this->_object->name, FALSE)
		);

		$oMainTab = $this->getTab('main');

		$oMainTab
			->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow4 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow5 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow6 = Admin_Form_Entity::factory('Div')->class('row'));

		$windowId = $this->_Admin_Form_Controller->getWindowId();

		$oMainTab->delete($this->getField('type'));

		$oCloudTypeSelect = Admin_Form_Entity::factory('Select')
			->divAttr(array('class' => 'form-group col-xs-6'))
			->class('form-control input-lg')
			->caption(Core::_('Cloud.type'))
			->name('type')
			->options(
				array('...') + Cloud_Controller::getClouds()
			)
			->value($this->_object->type);

		$oMainTab
			->move($this->getField('name')->divAttr(array('class' => 'form-group col-xs-6')), $oMainRow1)
			->move($this->getField('key')->divAttr(array('class' => 'form-group col-xs-12 col-md-6')), $oMainRow2)
			->move($this->getField('secret')->divAttr(array('class' => 'form-group col-xs-12 col-md-6')), $oMainRow2);

		$oMainRow1->add($oCloudTypeSelect);

		$oCodeLink = Admin_Form_Entity::factory('Link');
		$oCodeLink
			->divAttr(array('class' => 'large-link margin-top-21 form-group col-xs-6 col-sm-5 col-md-4 col-lg-3'))
			->a
				->class('btn btn-labeled btn-sky')
				->href('#')
				->onclick("var id = $('#{$windowId} input[name=\'id\']');
					if (id.length==0)
					{
						alert('" . Core::_('Cloud.saveElement') . "');
					}
					else
					{
						$.ajaxRequest({path: hostcmsBackend + '/cloud/index.php', datasetId: 0, objectId: id.val(), context: 'document_id', callBack: function(data, status, jqXHR){
							$.loadingScreen('hide');
							if (data.status==false)
							{
								var messages = $('#{$windowId} div[id=\'id_message\']').find('#error');
								if (messages.length)
								{
									messages.get(0).val(data.url);
								}
								else
								{
									$('#{$windowId} div[id=\'id_message\']').append('<div id=\"error\">'+data.url+'</div>');
								}
							}
							else
							{
								window.open(data.url);
							}
						}, action: 'loadOAuthCode', windowId: '{$windowId}'});
					}
					return false;")
				->value(Core::_('Cloud.getNewCode'));
		$oCodeLink
			->icon
				->class('btn-label fa fa-code-fork');

		$oMainTab->move($this->getField('code')->divAttr(array('class' => 'form-group col-xs-6')), $oMainRow3);
		$oMainRow3->add($oCodeLink);

		$oAccessTokenLink = Admin_Form_Entity::factory('Link');
		$oAccessTokenLink
			->divAttr(array('class' => 'large-link margin-top-21 form-group col-xs-6 col-sm-5 col-md-4 col-lg-3'))
			->a
				->class('btn btn-labeled btn-success')
				->href('#')
				->onclick("var id = $('#{$windowId} input[name=\'id\']');
					if (id.length==0)
					{
						alert('" . Core::_('Cloud.saveElement') . "');
					}
					else
					{
						$.ajaxRequest({path: hostcmsBackend + '/cloud/index.php', datasetId: 0, objectId: id.val(), context: 'document_id', callBack: function(data, status, jqXHR){
							$.loadingScreen('hide');
							if (data.status==false)
							{
								var messages = $('#{$windowId} div[id=\'id_message\']').find('#error');
								if (messages.length)
								{
									messages.get(0).val(data.token);
								}
								else
								{
									$('#{$windowId} div[id=\'id_message\']').append('<div id=\"error\">'+data.token+'</div>');
								}
							}
							else
							{
								$('#{$windowId} textarea[name=\'access_token\']').val(data.token);
							}
						}, action: 'loadOAuthAccessToken', additionalParams: 'code='+$('#{$windowId} input[name=\'code\']').val(), windowId: '{$windowId}'});
					}
					return false;")
				->value(Core::_('Cloud.getNewToken'));
		$oAccessTokenLink
			->icon
				->class('btn-label fa fa-asterisk');

		$oMainTab->move($this->getField('access_token')->divAttr(array('class' => 'form-group col-xs-6')), $oMainRow4);
		$oMainRow4->add($oAccessTokenLink);

		$oMainTab
			->move($this->getField('root_folder')->divAttr(array('class' => 'form-group col-xs-12 col-md-6')), $oMainRow5)
			->move($this->getField('sorting')->divAttr(array('class' => 'form-group col-sm-6 col-md-4')), $oMainRow6)
			->move($this->getField('active')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6 margin-top-21')), $oMainRow6);

		return $this;
	}
}