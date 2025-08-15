<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead.
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2020 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Lead_Controller_Need extends Admin_Form_Action_Controller
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
			$leadNeedId = Core_Array::getRequest('leadNeedId');

			if (is_null($leadNeedId))
			{
				throw new Core_Exception("leadNeedId is NULL");
			}

			if ($leadNeedId)
			{
				$oLead_Need = Core_Entity::factory('Lead_Need')->find(intval($leadNeedId));

				if (!is_null($oLead_Need->id))
				{
					$leadNeedId = $oLead_Need->id;
				}
				else
				{
					throw new Core_Exception("leadNeedId is unknown");
				}
			}
			else
			{
				// Без статуса
				$leadNeedId = 0;
			}

			$oLead = $this->_object;
			$oLead->lead_need_id = intval($leadNeedId);
			$oLead->save();

			return TRUE;
		}
	}
}