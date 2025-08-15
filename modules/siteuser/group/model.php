<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Group_Model
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Group_Model extends Core_Entity
{
	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'siteuser_group_list' => array(),
		'siteuser' => array('through' => 'siteuser_group_list'),
		'maillist_siteuser_group' => array(),
		'forum_category_siteuser_group' => array(),
		'shop_discount_siteuser_group' => array(),
		'shop_payment_system_siteuser_group' => array(),
		'shop_delivery_siteuser_group' => array()
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'site' => array(),
		'user' => array()
	);

	/**
	 * Default sorting for models
	 * @var array
	 */
	protected $_sorting = array(
		'siteuser_groups.sorting' => 'ASC'
	);

	/**
	 * Constructor.
	 * @param int $id entity ID
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		if (is_null($id) && !$this->loaded())
		{
			$oUser = Core_Auth::getCurrentUser();
			$this->_preloadValues['user_id'] = is_null($oUser) ? 0 : $oUser->id;

			$this->_preloadValues['site_id'] = defined('CURRENT_SITE') ? CURRENT_SITE : 0;
		}
	}

	/**
	 * Get groups by site ID
	 * @param int $site_id site ID
	 * @return array
	 */
	public function getBySiteId($site_id)
	{
		$this->queryBuilder()
			//->clear()
			->where('site_id', '=', $site_id);

		return $this->findAll();
	}

	/**
	 * Get siteuser's groups for siteuser
	 * <code>
	 * Core_Entity::factory('Site', 1)->Siteuser_Groups->getForSiteuser($siteuser_id);
	 * </code>
	 * @param int $siteuser_id user ID
	 */
	public function getForSiteuser($siteuser_id)
	{
		$this->queryBuilder()
			//->clear()
			->select('siteuser_groups.*')
			->join('siteuser_group_lists', 'siteuser_groups.id', '=', 'siteuser_group_lists.siteuser_group_id')
			->where('siteuser_id', '=', $siteuser_id);

		return $this->findAll();
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return Core_Entity
	 * @hostcms-event siteuser_group.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$this->Siteuser_Group_Lists->deleteAll(FALSE);

		if (Core::moduleIsActive('maillist'))
		{
			$this->Maillist_Siteuser_Groups->deleteAll(FALSE);
		}

		if (Core::moduleIsActive('forum'))
		{
			$this->Forum_Category_Siteuser_Groups->deleteAll(FALSE);
		}

		if (Core::moduleIsActive('shop'))
		{
			$this->Shop_Discount_Siteuser_Groups->deleteAll(FALSE);
			$this->Shop_Payment_System_Siteuser_Groups->deleteAll(FALSE);
			$this->Shop_Delivery_Siteuser_Groups->deleteAll(FALSE);
		}

		return parent::delete($primaryKey);
	}

	/**
	 * Get default group
	 * @param boolean $bCache cache mode
	 * @return Siteuser_Group_Model|NULL
	 */
	public function getDefault($bCache = TRUE)
	{
		$this->queryBuilder()
			->where('siteuser_groups.default', '=', 1)
			->limit(1);

		$aSiteuser_Groups = $this->findAll($bCache);

		return isset($aSiteuser_Groups[0])
			? $aSiteuser_Groups[0]
			: NULL;
	}

	/**
	 * Set siteuser group as default
	 * @return self
	 */
	public function setDefault()
	{
		$this->save();

		$oSiteuser_Groups = $this->Site->Siteuser_Groups;
		$oSiteuser_Groups
			->queryBuilder()
			->where('siteuser_groups.default', '=', 1);

		$aSiteuser_Groups = $oSiteuser_Groups->findAll();

		foreach ($aSiteuser_Groups as $oSiteuser_Group)
		{
			$oSiteuser_Group->default = 0;
			$oSiteuser_Group->save();
		}

		$this->default = 1;
		$this->save();

		return $this;
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event siteuser_group.onBeforeGetRelatedSite
	 * @hostcms-event siteuser_group.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}