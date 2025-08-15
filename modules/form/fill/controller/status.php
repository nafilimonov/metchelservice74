<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Form_Fill_Controller_Status.
 *
 * @package HostCMS
 * @subpackage Form
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2020 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Form_Fill_Controller_Status extends Admin_Form_Action_Controller
{

	/**
	 * Executes the business logic.
	 * @param mixed $operation Operation name
	 * @return self
	 */

	public function execute($operation = NULL)
	{
		if (is_null($operation))
		{
			$formStatusId = Core_Array::getRequest('formStatusId');

			if (is_null($formStatusId))
			{
				throw new Core_Exception("formStatusId is NULL");
			}

			$oOriginalFormStatus = $this->_object->Form_Status;

			if ($formStatusId)
			{
				$oForm_Status = Core_Entity::factory('Form_Status')->find(intval($formStatusId));

				if (!is_null($oForm_Status->id))
				{
					$formStatusId = $oForm_Status->id;
				}
				else
				{
					throw new Core_Exception("formStatusId is unknown");
				}

				$sNewFormStatusName = $oForm_Status->name;
			}
			else
			{
				// Без статуса
				$formStatusId = 0;

				$sNewFromStatusName = Core::_('Admin.none');
			}

			$oForm_Fill = $this->_object;
			$oForm_Fill->form_status_id = $formStatusId;
			$oForm_Fill->save();

			return TRUE;
		}
	}
}