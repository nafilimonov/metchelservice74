<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Shortlink_Model
 *
 * @package HostCMS
 * @subpackage Shortlink
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Shortlink_Model extends Core_Entity
{
	/**
	 * Callback property_id
	 * @var int
	 */
	public $stats = 0;

	/**
	* Model name
	* @var mixed
	*/
	protected $_modelName = 'shortlink';

	/**
	* Column consist item's name
	* @var string
	*/
	protected $_nameColumn = 'shortlink';

	/**
	 * Backend property
	 * @var int
	 */
	public $img = 1;

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'shortlink_stat' => array()
	);

	/**
	* Belongs to relations
	* @var array
	*/
	protected $_belongsTo = array(
		'site' => array(),
		'shortlink_dir' => array(),
		'user' => array()
	);

	/**
	* List of preloaded values
	* @var array
	*/
	protected $_preloadValues = array(
		'active' => 1,
		'type' => 301,
		'log' => 1,
		'hits' => 0
	);

	/**
	* Constructor.
	* @param int $id entity ID
	*/
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		if (is_null($id))
		{
			$oUser = Core_Auth::getCurrentUser();
			$this->_preloadValues['user_id'] = is_null($oUser) ? 0 : $oUser->id;
			$this->_preloadValues['site_id'] = CURRENT_SITE;
			$this->_preloadValues['datetime'] = Core_Date::timestamp2sql(time());
		}
	}

	/**
	* Change item status
	* @return self
	* @hostcms-event shortlink.onBeforeChangeActive
	* @hostcms-event shortlink.onAfterChangeActive
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
	* Change log status
	* @return self
	* @hostcms-event shortlink.onBeforeChangeLog
	* @hostcms-event shortlink.onAfterChangeLog
	*/
	public function changeLog()
	{
		Core_Event::notify($this->_modelName . '.onBeforeChangeLog', $this);

		$this->log = 1 - $this->log;
		$this->save();

		Core_Event::notify($this->_modelName . '.onAfterChangeLog', $this);

		return $this;
	}

	/**
	* Backend callback method
	* @return string
	*/
	public function sourceBackend()
	{
		$oCore_Html_Entity_Div = Core_Html_Entity::factory('Div')
			->value(htmlspecialchars($this->source));

		$oCore_Html_Entity_Div
			->add(
				Core_Html_Entity::factory('A')
					->href($this->source)
					->target('_blank')
					->add(
						Core_Html_Entity::factory('I')
							->class('fa fa-external-link')
					)
			);

		$oCore_Html_Entity_Div->execute();
	}

	/**
	* Backend callback method
	* @return string
	*/
	public function shortlinkBackend()
	{
		$oCore_Html_Entity_Div = Core_Html_Entity::factory('Div')
			->value(htmlspecialchars($this->shortlink))
			->add(
				Core_Html_Entity::factory('A')
					->href('/' . urlencode($this->shortlink))
					->target('_blank')
					->add(
						Core_Html_Entity::factory('I')
							->class('fa fa-external-link')
					)
			);

		$oCore_Html_Entity_Div->execute();
	}

	/**
	 * Generate shortlink
	 * @return self
	 */
	public function generateShortlink()
	{
		$this->shortlink = Shortlink_Controller::encode($this->id);

		return $this->save();
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return Core_Entity
	 * @hostcms-event shortlink.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$this->Shortlink_Stats->deleteAll(FALSE);

		return parent::delete($primaryKey);
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event shortlink.onBeforeGetRelatedSite
	 * @hostcms-event shortlink.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}