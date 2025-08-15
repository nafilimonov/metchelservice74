<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Cloud_Model
 *
 * @package HostCMS
 * @subpackage Cloud
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Cloud_Model extends Core_Entity
{
	/**
	 * Backend property
	 * @var string
	 */
	public $hash = '';

	/**
	 * Backend property
	 * @var int
	 */
	public $img = 0;

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'user' => array(),
		'site' => array()
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'active' => 1,
		'sorting' => 0
	);

	/**
	 * Default sorting for models
	 * @var array
	 */
	protected $_sorting = array(
		'clouds.sorting' => 'ASC',
		'clouds.name' => 'ASC'
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
	 * Change cloud status
	 * @return self
	 * @hostcms-event cloud.onBeforeChangeActive
	 * @hostcms-event cloud.onAfterChangeActive
	 */
	public function changeActive()
	{
		Core_Event::notify($this->_modelName . '.onBeforeChangeActive', $this);

		$this->active = 1 - $this->active;
		$this->save();

		Core_Event::notify($this->_modelName . '.onAfterChangeActive', $this);

		return $this;
	}

	/**
	 * Backend callback method
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function typeBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		return $this->type
			? htmlspecialchars($this->type)
			: '—';
	}
	
	/**
	 * Backend callback method
	 * @return string
	 */
	public function image()
	{
		return '<div class="fm_preview"><i class="fa-solid fa-cloud azure"></i></div>';
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event cloud.onBeforeGetRelatedSite
	 * @hostcms-event cloud.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}