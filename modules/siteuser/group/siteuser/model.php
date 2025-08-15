<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Group_Siteuser_Model
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Group_Siteuser_Model extends Siteuser_Model
{
	/**
	 * Name of the table
	 * @var string
	 */
	protected $_tableName = 'siteusers';

	/**
	 * Name of the model
	 * @var string
	 */
	protected $_modelName = 'siteuser_group_siteuser';

	/**
	 * Backend property
	 * @var string
	 */
	public $in_group = NULL;

	/**
	 * Backend property
	 * @var string
	 */
	public $counterparty = NULL;

	/**
	 * Save object. Use self::update() or self::create()
	 * @return Siteuser_Group_Siteuser_Model
	 */
	public function save()
	{
		// Идентификатор группы пользователей
		$siteuser_group_id = intval(Core_Array::getGet('siteuser_group_id'));
		$oSiteuser_Group = Core_Entity::factory('Siteuser_Group')->find($siteuser_group_id);

		if (!is_null($oSiteuser_Group->id))
		{
			$oSiteuser_Group_List = $oSiteuser_Group->Siteuser_Group_Lists->getBySiteuserId($this->id, FALSE);

			if ($this->in_group)
			{
				if (is_null($oSiteuser_Group_List))
				{
					$oSiteuser_Group_List = Core_Entity::factory('Siteuser_Group_List');
					$oSiteuser_Group_List->siteuser_id = $this->id;
					$oSiteuser_Group->add($oSiteuser_Group_List);
				}
			}
			else
			{
				if (!is_null($oSiteuser_Group_List))
				{
					$oSiteuser_Group_List->delete();
				}
			}
		}

		return $this;
	}

	/**
	 * Insert user into group
	 */
	public function includeSiteuser()
	{
		$this->in_group = 1;
		$this->save();
	}

	/**
	 * Remove user from group
	 */
	public function excludeSiteuser()
	{
		$this->in_group = 0;
		$this->save();
	}

	/**
	 * Insert/remove user into/from group
	 * @param int $value operation
	 * @return int
	 */
	public function in_group($value = NULL)
	{
		if (is_numeric($value))
		{
			$value
				? $this->in_group = 1
				: $this->in_group = 0;
			return $this;
		}
		return $this->in_group;
	}

	/**
	 * Backend callback method
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function counterpartyBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$sResult = '';

		$aSiteuserCompanies = Core_Entity::factory('Siteuser_Company')->getAllBySiteuser_id($this->id);
		$aSiteuserPersons = Core_Entity::factory('Siteuser_Person')->getAllBySiteuser_id($this->id);

		if (count($aSiteuserCompanies) || count($aSiteuserPersons))
		{
			$sResult .= '<div class="profile-container tickets-container"><ul class="tickets-list">';

			foreach ($aSiteuserCompanies as $oSiteuserCompany)
			{
				$oSiteuserCompany->id
					&& $sResult .= $oSiteuserCompany->getProfileBlock();
			}

			foreach ($aSiteuserPersons as $oSiteuserPerson)
			{
				$oSiteuserPerson->id
					&& $sResult .= $oSiteuserPerson->getProfileBlock();
			}

			$sResult .= '</ul></div>';
		}

		return $sResult;
	}
}