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
class Lead_Controller_Maturity extends Admin_Form_Action_Controller
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
			$leadMaturityId = Core_Array::getRequest('leadMaturityId');

			if (is_null($leadMaturityId))
			{
				throw new Core_Exception("leadMaturityId is NULL");
			}

			if ($leadMaturityId)
			{
				$oLead_Maturity = Core_Entity::factory('Lead_Maturity')->find(intval($leadMaturityId));

				if (!is_null($oLead_Maturity->id))
				{
					$leadMaturityId = $oLead_Maturity->id;
				}
				else
				{
					throw new Core_Exception("leadMaturityId is unknown");
				}
			}
			else
			{
				// Без статуса
				$leadMaturityId = 0;
			}

			$oLead = $this->_object;
			$oLead->lead_maturity_id = intval($leadMaturityId);
			$oLead->save();

			return TRUE;
		}
	}
}