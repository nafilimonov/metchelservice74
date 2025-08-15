<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead.
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Lead_Controller_Source extends Admin_Form_Action_Controller
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
			$crmSourceId = Core_Array::getRequest('crmSourceId');

			if (is_null($crmSourceId))
			{
				throw new Core_Exception("crmSourceId is NULL");
			}

			if ($crmSourceId)
			{
				$oCrm_Source = Core_Entity::factory('Crm_Source')->find(intval($crmSourceId));

				if (!is_null($oCrm_Source->id))
				{
					$crmSourceId = $oCrm_Source->id;
				}
				else
				{
					throw new Core_Exception("crmSourceId is unknown");
				}
			}
			else
			{
				// Без статуса
				$crmSourceId = 0;
			}

			$oLead = $this->_object;
			$oLead->crm_source_id = intval($crmSourceId);
			$oLead->save();

			return TRUE;
		}
	}
}