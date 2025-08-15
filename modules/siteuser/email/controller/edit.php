<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Email_Controller_Edit
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Siteuser_Email_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
		$object->subject == ''
			&& $object->subject = Core::_('Admin_Form.non_subject');

		if (!$object->id)
		{
			$object->siteuser_id = Core_Array::getGet('siteuser_id', 0);

			$oUser = Core_Auth::getCurrentUser();
			if (!is_null($oUser))
			{
				$oDirectory_Email = $oUser->Directory_Emails->getFirst();
				!is_null($oDirectory_Email)
					&& $object->from = $oDirectory_Email->value();
			}

			if (strlen($object->Siteuser->email))
			{
				$object->email = $object->Siteuser->email;
			}
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

		$oAdditionalTab
			->add($oAdditionalRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oAdditionalRow2 = Admin_Form_Entity::factory('Div')->class('row'));

		if ($this->_object->id)
		{
			$oAdditionalTab
				->move($this->getField('id')->divAttr(array('class' => 'form-group col-xs-12 col-sm-3')), $oAdditionalRow1);
		}

		$oAdditionalTab
			->move($this->getField('siteuser_id')->divAttr(array('class' => 'form-group col-xs-12 col-sm-3')), $oAdditionalRow1)
			->move($this->getField('user_id')->divAttr(array('class' => 'form-group col-xs-12')), $oAdditionalRow2);

		$oMainTab
			->move($this->getField('read')->divAttr(array('class' => 'form-group col-xs-12 col-sm-3')), $oAdditionalRow1)
			->move($this->getField('guid')->divAttr(array('class' => 'form-group col-xs-12 col-sm-3')), $oAdditionalRow1);

		$oMainTab
			->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow4 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow5 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow6 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow7 = Admin_Form_Entity::factory('Div')->class('row'))
			;

		$oMainTab
			->move($this->getField('from')->divAttr(array('class' => 'form-group col-xs-12'))->class('form-control'), $oMainRow1)
			->move($this->getField('email')->divAttr(array('class' => 'form-group col-xs-12'))->class('form-control')->format(array('lib' => array())), $oMainRow2)
			->move($this->getField('cc')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6')), $oMainRow3)
			->move($this->getField('bcc')->divAttr(array('class' => 'form-group col-xs-12 col-sm-6')), $oMainRow3)
			->move($this->getField('subject')->divAttr(array('class' => 'form-group col-xs-12'))->class('form-control input-lg'), $oMainRow3)
			;

		if ($this->_object->type)
		{
			$oMainPage = $this->_object->Siteuser->Site->Structures->getByPath('/');

			$template_id = !is_null($oMainPage)
				? $oMainPage->template_id
				: 0;

			$this->getField('text')
				->rows(10)
				->wysiwyg(Core::moduleIsActive('wysiwyg'))
				->template_id($template_id);

			$oMainTab->move($this->getField('text')->id('editor'), $oMainRow5);
		}
		else
		{
			$oMainTab->move($this->getField('text')->id('editor')->rows(10)->divAttr(array('class' => 'form-group col-xs-12')), $oMainRow5);
		}

		$oMainTab->move($this->getField('datetime')->divAttr(array('class' => 'form-group col-xs-12 col-sm-5 col-md-4 col-lg-3')), $oMainRow6);

		$oMainTab->delete($this->getField('type'));

		$sCurrentLng = Core_I18n::instance()->getLng();

		$oMainRow6->add(
			Admin_Form_Entity::factory('Select')
				->options(
					array(
						0 => Core::_('Siteuser_Email.text'),
						1 => Core::_('Siteuser_Email.html')
					)
				)
				->name('type')
				->value($this->_object->id
					? $this->_object->type
					: 0
				)
				->caption(Core::_('Siteuser_Email.type'))
				->divAttr(array('class' => 'form-group col-xs-12 col-sm-3'))
				->onchange('$.changeSiteuserEmailType(this, "' . $sCurrentLng . '")')
		);

		$oMainTab
			->move($this->getField('important')->divAttr(array('class' => 'form-group col-xs-12 col-sm-3 margin-top-21')), $oMainRow6);

		if (!is_null($this->_Admin_Form_Action) && $this->_Admin_Form_Action->name == 'edit')
		{
			$windowId = $this->_Admin_Form_Controller->getWindowId();

			$aSiteuser_Email_Attachments = $this->_object->Siteuser_Email_Attachments->findAll(FALSE);
			foreach ($aSiteuser_Email_Attachments as $oSiteuser_Email_Attachment)
			{
				$oFile = Admin_Form_Entity::factory('File')
					->controller($this->_Admin_Form_Controller)
					->type('file')
					->caption(Core::_('Siteuser_Email.attachment'))
					->name("file_{$oSiteuser_Email_Attachment->id}")
					->largeImage(
						array(
							'path' => Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/email/index.php?downloadFile=') . $oSiteuser_Email_Attachment->id . '&filename=' . $oSiteuser_Email_Attachment->name,
							'show_params' => FALSE,
							'originalName' => $oSiteuser_Email_Attachment->name,
							'delete_onclick' => "$.adminLoad({path: hostcmsBackend + '/siteuser/email/index.php', additionalParams: 'hostcms[checked][0][{$this->_object->id}]=1', operation: '{$oSiteuser_Email_Attachment->id}', action: 'deleteFile', windowId: '{$windowId}'}); return false",
							'delete_href' => '',
							'show_description' => FALSE
						)
					)
					->smallImage(
						array('show' => FALSE)
					)
					->divAttr(array('id' => "file_{$oSiteuser_Email_Attachment->id}", 'class' => 'input-group1 col-xs-12'));

				$oMainRow7->add($oFile);
			}

			$oAdmin_Form_Entity = Admin_Form_Entity::factory('File')
				->controller($this->_Admin_Form_Controller)
				->type('file')
				->name("file[]")
				->caption(Core::_('Siteuser_Email.attachment'))
				->largeImage(
					array(
						'show_params' => FALSE,
						'show_description' => FALSE
					)
				)
				->smallImage(
					array('show' => FALSE)
				)
				// ->divAttr(array('id' => 'file', 'class' => 'add-email-attachment1 form-group col-xs-12'))
				;

			$oMainRow7->add(
				Admin_Form_Entity::factory('Div')
					->class('input-group')
					->id('file')
					->add($oAdmin_Form_Entity)
					// ->add($oAdmin_Form_Entity_Code)
					->add(
						Admin_Form_Entity::factory('Code')->html('<div class="input-group-addon add-remove-property">
						<div class="no-padding-left col-lg-12">
						<div class="btn btn-palegreen" onclick="$.cloneFile(\'' . $windowId .'\'); event.stopPropagation();"><i class="fa fa-plus-circle close"></i></div>
						<div class="btn btn-darkorange" onclick="$(this).parents(\'#file\').remove(); event.stopPropagation();"><i class="fa fa-minus-circle close"></i></div>
						</div>
						</div>')
					)
			);
		}

		return $this;
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @hostcms-event Siteuser_Email_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		$bEdit = !is_null($this->_Admin_Form_Action) && $this->_Admin_Form_Action->name == 'edit';

		$aSiteuser_Email_Attachments = $this->_object->Siteuser_Email_Attachments->findAll(FALSE);

		if (!$bEdit)
		{
			$this
				->addSkipColumn('id')
				->addSkipColumn('read')
				->addSkipColumn('guid')
				->addSkipColumn('datetime');

			$this->_object = Core_Entity::factory('Siteuser_Email');
		}

		parent::_applyObjectProperty();

		if (!$bEdit)
		{
			foreach ($aSiteuser_Email_Attachments as $oSiteuser_Email_Attachment)
			{
				$oNew_Siteuser_Email_Attachment = clone($oSiteuser_Email_Attachment);
				$this->_object->add($oNew_Siteuser_Email_Attachment);

				$oNew_Siteuser_Email_Attachment->saveFile($oSiteuser_Email_Attachment->getFilePath(), $oSiteuser_Email_Attachment->name);
			}
		}
		else
		{
			// Замена загруженных ранее файлов на новые
			foreach ($aSiteuser_Email_Attachments as $oSiteuser_Email_Attachment)
			{
				$aExistFile = Core_Array::getFiles("file_{$oSiteuser_Email_Attachment->id}");

				if (!is_null($aExistFile))
				{
					if (Core_File::isValidExtension($aExistFile['name'], Core::$mainConfig['availableExtension']))
					{
						$oSiteuser_Email_Attachment->saveFile($aExistFile['tmp_name'], $aExistFile['name']);
					}
				}

				$oSiteuser_Email_Attachment->save();
			}

			$windowId = $this->_Admin_Form_Controller->getWindowId();

			// Новые значения
			$aNewFiles = Core_Array::getFiles("file", array());

			if (is_array($aNewFiles) && isset($aNewFiles['name']))
			{
				$iCount = count($aNewFiles['name']);

				for ($i = 0; $i < $iCount; $i++)
				{
					ob_start();

					$aFile = array(
						'name' => $aNewFiles['name'][$i],
						'tmp_name' => $aNewFiles['tmp_name'][$i],
						'size' => $aNewFiles['size'][$i]
					);

					$oCore_Html_Entity_Script = Core_Html_Entity::factory('Script')
						->value("$(\"#{$windowId} #file:has(input\\[name='file\\[\\]'\\])\").eq(0).remove();");

					if (intval($aFile['size']) > 0)
					{
						$oSiteuser_Email_Attachment = Core_Entity::factory('Siteuser_Email_Attachment');

						$oSiteuser_Email_Attachment->siteuser_email_id = $this->_object->id;
						$oSiteuser_Email_Attachment->save();

						$oSiteuser_Email_Attachment->saveFile($aFile['tmp_name'], $aFile['name']);

						if (!is_null($oSiteuser_Email_Attachment->id))
						{
							$oCore_Html_Entity_Script
								->value("$(\"#{$windowId} #file\").find(\"input[name='file\\[\\]']\").eq(0).attr('name', 'file_{$oSiteuser_Email_Attachment->id}');");
						}
					}

					$oCore_Html_Entity_Script->execute();

					$this->_Admin_Form_Controller->addMessage(ob_get_clean());
				}
			}
		}

		Core::moduleIsActive('wysiwyg') && Wysiwyg_Controller::uploadImages($this->_formValues, $this->_object, $this->_Admin_Form_Controller);

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
		$oUser = Core_Auth::getCurrentUser();

		//$parent_id = Core_Array::getGet('parent_id', 0);
		$siteuser_id = Core_Array::getGet('siteuser_id', 0);
		$bShow_subs = !is_null(Core_Array::getGet('show_subs'));

		// $windowId = $this->_Admin_Form_Controller->getWindowId();

		// Всегда id_content
		$sJsRefresh = '<script>

		var jA = $("li[data-type=timeline] a");
		if (jA.length)
		{
			$.adminLoad({ path: jA.eq(0).data("path"), additionalParams: jA.eq(0).data("additional"), windowId: jA.eq(0).data("window-id") });
		}';

		//if (!$parent_id && !$siteuser_id)
		if (!$bShow_subs && !$siteuser_id)
		{
			$sJsRefresh .= 'var jAEvents = $("li[data-type=email] a");
			if (jAEvents.length)
			{
				$.adminLoad({ path: jAEvents.eq(0).data("path"), additionalParams: jAEvents.eq(0).data("additional"), windowId: jAEvents.eq(0).data("window-id") });
			}';
		}

		$sJsRefresh .= '</script>';

		switch ($operation)
		{
			case 'save':
			case 'saveModal':
			case 'applyModal':
				$operation == 'saveModal' && $this->addMessage($sJsRefresh);
				$operation == 'applyModal' && $this->addContent($sJsRefresh);
			break;
			case 'markDeleted':
				$this->_object->markDeleted();
				$this->addMessage($sJsRefresh);
			break;
		}

		return parent::execute($operation);
	}
}