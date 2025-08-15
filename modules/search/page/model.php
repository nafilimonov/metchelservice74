<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Search_Page_Model
 *
 * @package HostCMS
 * @subpackage Search
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Search_Page_Model extends Core_Entity
{
	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $text = NULL;

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'site' => array()
	);

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'search_word' => array(),
		'siteuser_group' => array('through' => 'search_page_siteuser_group'),
		'search_page_siteuser_group' => array(),
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'url' => '',
		'size' => 0,
		'inner' => 0,
		'module' => 0,
		'module_id' => 0,
		'module_value_type' => 0,
		'module_value_id' => 0,
		//'site_id' => 0,
	);

	/**
	 * Load columns list for model
	 * @return self
	 * @ignore
	 */
	protected function _loadColumns()
	{
		// Sphinx load columns
		if (empty($this->_tableColumns) && $this->_tableName == 'search_pages')
		{
			self::$_columnCache[$this->_modelName] = $this->_tableColumns = array(
				"id" => array(
					"datatype" => "int unsigned",
					"type" => "int",
					"max_length" => NULL,
					'default' => NULL,
					'min' => 0,
					'max' => 2147483647
				),
				// sphinx RT-index consist `site_id`
				"site_id" => array(
					"datatype" => "int unsigned",
					"type" => "int",
					"max_length" => NULL,
					'default' => NULL,
					'min' => 0,
					'max' => 2147483647
				),
				"url" => array(
					"datatype" => "varchar",
					"type" => "string",
					"max_length" => NULL,
					'default' => NULL,
					'null' => TRUE
				),
				"title" => array(
					"datatype" => "varchar",
					"type" => "string",
					"max_length" => NULL,
					'default' => NULL,
					'null' => TRUE
				),
				"datetime" => array(
					"datatype" => "datetime",
					"type" => "string",
					"max_length" => NULL,
					'default' => NULL,
				),
				"size" => array(
					"datatype" => "int",
					"type" => "int",
					"max_length" => NULL,
					'default' => NULL,
					'min' => 0,
					'max' => 2147483647
				),
				"inner" => array(
					"datatype" => "tinyint",
					"type" => "int",
					"max_length" => NULL,
					'default' => NULL,
					'min' => 0,
					'max' => 255
				),
				"module" => array(
					"datatype" => "tinyint",
					"type" => "int",
					"max_length" => NULL,
					'default' => NULL,
					'min' => 0,
					'max' => 255
				),
				"module_id" => array(
					"datatype" => "int",
					"type" => "int",
					"max_length" => NULL,
					'default' => NULL,
					'min' => 0,
					'max' => 2147483647
				),
				"module_value_type" => array(
					"datatype" => "int",
					"type" => "int",
					"max_length" => NULL,
					'default' => NULL,
					'min' => 0,
					'max' => 2147483647
				),
				"module_value_id" => array(
					"datatype" => "int",
					"type" => "int",
					"max_length" => NULL,
					'default' => NULL,
					'min' => 0,
					'max' => 2147483647
				)
			);

			$this->_loadColumnCacheDefaultValues();
		}

		return parent::_loadColumns();
	}

	/**
	 * Forbidden tags. If list of tags is empty, all tags will show.
	 * @var array
	 */
	protected $_forbiddenTags = array(
		'deleted',
		'user_id',
		'datetime',
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
			//$this->_preloadValues['site_id'] = defined('CURRENT_SITE') ? CURRENT_SITE : 0;
			$this->_preloadValues['datetime'] = Core_Date::timestamp2sql(time());
		}
	}

	/**
	 * Site Id
	 * @var int|NULL
	 */
	protected $_siteId = NULL;

	/**
	 * Set Site Id
	 * @param int $site_id
	 * @return self
	 */
	public function setSiteId($site_id)
	{
		$this->_siteId = intval($site_id);
		return $this;
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return self
	 * @hostcms-event search_page.onBeforeRedeclaredDelete
	 */
	/*public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}
		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		// see deleteSearchPage()
		//$this->Search_Words->deleteAll(FALSE);
		//$this->Search_Page_Siteuser_Groups->deleteAll(FALSE);

		return parent::delete($primaryKey);
	}*/

	/**
	 * Check if there another search page with this url is
	 * @return self
	 */
	protected function _checkDuplicate()
	{
		$oSearch_Pages = Core_Entity::factory('Search_Page')
			->setTableName($this->getTableName());

		$oSearch_Page = $oSearch_Pages->getByUrl($this->url, FALSE);

		if (!is_null($oSearch_Page) && $oSearch_Page->id != $this->id)
		{
			$this->id = $oSearch_Page->id;
		}

		return $this;
	}

	/**
	 * Save object.
	 *
	 * @return Core_Entity
	 */
	public function save()
	{
		$this->_checkDuplicate();
		return parent::save();
	}

	/**
	 * Get XML for entity and children entities
	 * @return string
	 * @hostcms-event search_page.onBeforeRedeclaredGetXml
	 */
	public function getXml()
	{
		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredGetXml', $this);

		$this->_prepareData();

		return parent::getXml();
	}

	/**
	 * Get stdObject for entity and children entities
	 * @return stdObject
	 * @hostcms-event search_page.onBeforeRedeclaredGetStdObject
	 */
	public function getStdObject($attributePrefix = '_')
	{
		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredGetStdObject', $this);

		$this->_prepareData();

		return parent::getStdObject($attributePrefix);
	}

	/**
	 * Prepare entity and children entities
	 * @return self
	 */
	protected function _prepareData()
	{
		$this->clearXmlTags()
			->addXmlTag('date', Core_Date::sql2date($this->datetime))
			->addXmlTag('datetime', Core_Date::sql2datetime($this->datetime))
			;

		return $this;
	}

	/**
	 * Forbid XML in added entities
	 * @var array
	 */
	protected $_itemsForbiddenTags = array();

	/**
	 * Add comments XML to item
	 * @param array $itemsForbiddenTags list of forbidden tags
	 * @return self
	 */
	public function itemsForbiddenTags(array $itemsForbiddenTags)
	{
		$this->_itemsForbiddenTags = $itemsForbiddenTags;
		return $this;
	}

	/**
	 * Add a children entity
	 *
	 * @param Core_Entity $oChildrenEntity
	 */
	public function addEntity($oChildrenEntity)
	{
		parent::addEntity($oChildrenEntity);

		// Устанавливаем запрещенные теги
		$this->applyItemsForbiddenTags($oChildrenEntity);

		return $this;
	}

	/**
	 * Apply forbidden xml tags for items
	 * @param Core_Entity $oChildrenEntity entity
	 * @return self
	 */
	public function applyItemsForbiddenTags($oChildrenEntity)
	{
		if (!is_null($this->_itemsForbiddenTags))
		{
			foreach ($this->_itemsForbiddenTags as $forbiddenTag)
			{
				$oChildrenEntity->addForbiddenTag($forbiddenTag);
			}
		}

		return $this;
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event search_page.onBeforeGetRelatedSite
	 * @hostcms-event search_page.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		return NULL;
	}
}