<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Support_Controller_Edit
 *
 * @package HostCMS
 * @subpackage Support
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Support_Controller_Edit extends Admin_Form_Action_Controller_Type_Edit
{
	/**
	 * Set object
	 * @param object $object object
	 * @return self
	 */
	public function setObject($object)
	{
		$this
			->addSkipColumn('ticket_id')
			->addSkipColumn('datetime');

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

		$windowId = $this->_Admin_Form_Controller->getWindowId();

		$oMainTab = $this->getTab('main');
		// $oAdditionalTab = $this->getTab('additional');

		$oMainTab
			->add($oMainRow1 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow2 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow3 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow4 = Admin_Form_Entity::factory('Div')->class('row'))
			->add($oMainRow5 = Admin_Form_Entity::factory('Div')->class('row'))
			;

		$oMainTab
			->move($this->getField('subject')->class('form-control')->format(array('minlen' => array('value' => 10),'maxlen' => array('value' => 2000)))->divAttr(array('class' => 'form-group col-xs-12 col-sm-8')), $oMainRow1);

		$oMainRow1->add(
			Admin_Form_Entity::factory('Select')
				->name('department')
				->caption(Core::_('Support.department'))
				->options(
					array(
						Core::_('Support.support'),
						Core::_('Support.main')
					)
				)
				->divAttr(array('class' => 'form-group col-xs-12 col-sm-4'))
		);

		$oMainTab->move($this->getField('text')->rows(15), $oMainRow2);

		$oMainRow3->add(
			Admin_Form_Entity::factory('Input')
			->name('page')
			->caption(Core::_('Support.page'))
			->divAttr(array('class' => 'form-group col-xs-12 col-sm-8'))
		)->add(
			Admin_Form_Entity::factory('Select')
				->name('priority')
				->caption(Core::_('Support.priority'))
				->options(
					array(
						Core::_('Support.low'),
						Core::_('Support.middle'),
						Core::_('Support.high'),
						Core::_('Support.highest')
					)
				)
				->divAttr(array('class' => 'form-group col-xs-12 col-sm-4'))
		);

		$oMainRow4->add(
			Admin_Form_Entity::factory('Input')
				->name('name')
				->caption(Core::_('Support.name'))
				->format(
					array('minlen' => array('value' => 3)),
					array('maxlen' => array('value' => 255))
				)
				->divAttr(array('class' => 'form-group col-xs-12 col-sm-4'))
		)->add(
			Admin_Form_Entity::factory('Input')
				->name('email')
				->caption(Core::_('Support.email'))
				->format(
					array(
						'lib' => array('value' => 'email'),
						'minlen' => array('value' => 7),
						'maxlen' => array('value' => 255)
					)
				)
				->divAttr(array('class' => 'form-group col-xs-12 col-sm-4'))
		)->add(
			Admin_Form_Entity::factory('Input')
				->name('phone')
				->caption(Core::_('Support.phone'))
				->divAttr(array('class' => 'form-group col-xs-12 col-sm-4'))
		);

		$oMainRow5->add(
			Admin_Form_Entity::factory('Div')
			->class('input-group')
			->id('file')
			->add(
				Admin_Form_Entity::factory('File')
					->type('file')
					->name("file[]")
					->caption(Core::_('Support.file'))
					->largeImage(
						array(
							'show_params' => FALSE,
							'show_description' => FALSE
						)
					)
					->smallImage(array('show' => FALSE))
			)
			->add(
				Admin_Form_Entity::factory('Code')
					->html('<div class="input-group-addon add-remove-property">
					<div class="no-padding-left col-lg-12">
					<div class="btn btn-palegreen" onclick="$.cloneFile(\'' . $windowId .'\'); event.stopPropagation();"><i class="fa fa-plus-circle close"></i></div>
					<div class="btn btn-darkorange" onclick="$(this).parents(\'#file\').remove(); event.stopPropagation();"><i class="fa fa-minus-circle close"></i></div>
					</div>
					</div>')
			)
		);

		$this->title($this->_object->id
			? Core::_('Support.edit_title')
			: Core::_('Support.add_title')
		);

		return $this;
	}

	/**
	 * Processing of the form. Apply object fields.
	 * @return self
	 * @hostcms-event Support_Controller_Edit.onAfterRedeclaredApplyObjectProperty
	 */
	protected function _applyObjectProperty()
	{
		parent::_applyObjectProperty();

		switch (Core_Array::getPost('priority'))
		{
			default:
			case 0:
				$priority = Core::_('Support.low');
			break;
			case 1:
				$priority = Core::_('Support.middle');
			break;
			case 2:
				$priority = Core::_('Support.high');
			break;
			case 3:
				$priority = Core::_('Support.highest');
			break;
		}

		$message = Core::_('Support.mail_subject', Core_Array::getPost('subject')). "\n\n";
		$message .= Core_Array::getPost('text') . "\n";
		$message .= "_____________________________________\n";
		$message .= Core::_('Support.mail_page', Core_Array::getPost('page')) . "\n";
		$message .= "_____________________________________\n";
		$message .= Core::_('Support.mail_version', CURRENT_VERSION) . "\n";
		$message .= Core::_('Support.mail_update', HOSTCMS_UPDATE_NUMBER) . "\n";

		switch (Core_Array::get(Core::$config->get('core_hostcms'), 'integration'))
		{
			default:
			case 0:
				$redaction_name = 'HostCMS.Халява';
			break;
			case 1:
				$redaction_name = 'HostCMS.Мой сайт';
			break;
			case 3:
				$redaction_name = 'HostCMS.Малый бизнес';
			break;
			case 5:
				$redaction_name = 'HostCMS.Бизнес';
			break;
			case 7:
				$redaction_name = 'HostCMS.Корпорация';
			break;
		}

		$email = Core_Array::getPost('email');

		$message .= Core::_('Support.mail_redaction', $redaction_name) . "\n";
		$message .= "_____________________________________\n";
		$message .= Core::_('Support.mail_contact_information') . "\n";
		$message .= Core::_('Support.mail_name', Core_Array::getPost('name')) ."\n";
		$message .= Core::_('Support.mail_mail', $email) ."\n";
		$message .= Core::_('Support.mail_phone', Core_Array::getPost('phone')) . "\n";
		$message .= Core::_('Support.mail_contract', defined('HOSTCMS_CONTRACT_NUMBER') ? HOSTCMS_CONTRACT_NUMBER : '') . "\n";
		$message .= Core::_('Support.mail_pin', defined('HOSTCMS_PIN_CODE') ? HOSTCMS_PIN_CODE : '') . "\n";
		$message .= Core::_('Support.mail_priority', $priority) . "\n";

		$memoryLimit = ini_get('memory_limit')
			? ini_get('memory_limit')
			: 'undefined';

		$aDbDrivers = array();
		class_exists('PDO') && $aDbDrivers[] = 'PDO';
		function_exists('mysql_connect') && $aDbDrivers[] = 'mysql';

		$sDbDrivers = implode(', ', $aDbDrivers);

		$message .= "_____________________________________\n";
		$message .= Core::_('Support.mail_system_information') . "\n";
		$message .= Core::_('Support.mail_php_version', phpversion()) ."\n";
		$message .= Core::_('Support.mail_mysql_version', Core_DataBase::instance()->getVersion()) ."\n";
		$message .= Core::_('Support.mail_mysql_drivers', $sDbDrivers) ."\n";
		$message .= Core::_('Support.mail_gd_version', Core_Image::instance('gd')->getVersion()) ."\n";
		$message .= Core::_('Support.mail_pcre_version', Core::getPcreVersion()) ."\n";
		$message .= Core::_('Support.mail_max_execution_time', intval(ini_get('max_execution_time'))) ."\n";
		$message .= Core::_('Support.mail_memory_limit', $memoryLimit) ."\n";

		PHP_VERSION_ID < 80000
			&& $message .= Core::_('Support.mail_func_overload', function_exists('mb_get_info') ? mb_get_info('func_overload') : 'undefined') ."\n";

		$message .= "_____________________________________\n";

		$message .= Core::_('Support.mail_backend', Core::$mainConfig['backend']) . "\n";

		$aSite_Aliases = Core_Entity::factory('Site', CURRENT_SITE)->Site_Aliases->findAll();
		$site_alias = '';
		foreach ($aSite_Aliases as $oSite_Alias)
		{
			$site_alias .= $oSite_Alias->name . "\n";
		}

		$message .= Core::_('Support.mail_alias', $site_alias) . "\n";

		switch (Core_Array::getPost('department'))
		{
			default:
			case 0:
				$section = Core::_('Support.support');
			break;
			case 1:
				$section = Core::_('Support.main');
			break;
		}

		$subject = 'HostCMS:' . $section . ':' . Core_Array::getPost('subject');

		$this->_object->subject = $subject;
		$this->_object->save();

		$aFiles = Core_Array::getFiles('file', NULL);

		$oSupport_Controller = Support_Controller::instance();
		$oSupport_Controller->setSupportOptions();

		$aReturn = $oSupport_Controller->createTicket($subject, $message, $email, $aFiles);

		if ($aReturn['error'] == 99)
		{
			throw new Core_Exception(Core::_('Support.many_tickets'), array(), 0, FALSE, 0, FALSE);
		}

		if ($aReturn['ticket_id'] !== '')
		{
			$this->_object->ticket_id = $aReturn['ticket_id'];
			$this->_object->save();
		}

		Core_Event::notify(get_class($this) . '.onAfterRedeclaredApplyObjectProperty', $this, array($this->_Admin_Form_Controller));

		return $this;
	}

	/**
	 * Add save and apply buttons
	 * @return Admin_Form_Entity_Buttons
	 */
	protected function _addButtons()
	{
		// Кнопки
		$oAdmin_Form_Entity_Buttons = parent::_addButtons();

		// Удаляем "Сохранить"
		$aButtons = $oAdmin_Form_Entity_Buttons->getChildren();

		foreach ($aButtons as $key => $oButton)
		{
			if ($oButton->id == 'action-button-save')
			{
				$oAdmin_Form_Entity_Buttons->deleteChild($key);
			}

			if ($oButton->id == 'action-button-apply')
			{
				$oButton->value = Core::_('Support.button');
			}
		}

		return $oAdmin_Form_Entity_Buttons;
	}
}