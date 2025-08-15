<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Affiliate_Model
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Affiliate_Model extends Core_Entity
{
	/**
	 * Backend property
	 * @var string
	 */
	public $img_list_affiliates = NULL;

	/**
	 * Backend property
	 * @var string
	 */
	public $login = NULL;

	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'siteuser' => array(),
		'referral' => array('model' => 'Siteuser', 'foreign_key' => 'referral_siteuser_id'),
		'user' => array()
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'active' => 1
	);

	/**
	 * Column consist item's name
	 * @var string
	 */
	protected $_nameColumn = 'id';

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
			$this->_preloadValues['date'] = Core_Date::timestamp2sql(time());
		}
	}

	/**
	 * Get affiliate by referral ID
	 * @param int $referral_siteuser_id referral ID
	 * @return Siteuser_Affiliate_Model|NULL
	 */
	public function getByReferralId($referral_siteuser_id)
	{
		$this->queryBuilder()
		//->clear()
		->where('referral_siteuser_id', '=', $referral_siteuser_id)
		->limit(1);

		$aSiteuser_Affiliats = $this->findAll();

		return isset($aSiteuser_Affiliats[0]) ? $aSiteuser_Affiliats[0] : NULL;
	}

	/**
	 * Backend badge
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function img_list_affiliatesBadge($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$count = $this->getCountBySiteuser_id($this->referral_siteuser_id);
		$count && Core_Html_Entity::factory('Span')
			->class('badge badge-ico badge-azure white')
			->value($count)
			->execute();
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event siteuser.onBeforeGetRelatedSite
	 * @hostcms-event siteuser.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Siteuser->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}