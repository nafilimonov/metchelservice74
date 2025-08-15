<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Advertisement_Model
 *
 * @package HostCMS
 * @subpackage Advertisement
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Advertisement_Model extends Core_Entity
{
	/**
	 * Backend property
	 * @var string
	 */
	public $img_contextualwords = 0;

	/**
	 * Backend property
	 * @var string
	 */
	public $statistic = 0;

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'advertisement_contextualword' => array(),
		'advertisement_statistic' => array(),
		'advertisement_show' => array(),
		'advertisement_group_list' => array(),
		'advertisement_group' => array('through' => 'advertisement_group_list')
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'site' => array(),
		'user' => array(),
		'structure' => array(),
		'popup_structure' => array('foreign_key' => 'popup_structure_id', 'model' => 'structure')
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'last_date' => '0000-00-00',
		'source' => '',
		'show_per_day' => 99999999,
		'show_total' => 99999999,
		'showed_today' => 0
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
	 * Clone entity
	 * @return void
	 */
	public function __clone()
	{
		parent::__clone();
		$this->showed_today = 0;
		$this->showed = 0;
		$this->source = '';
	}

	/**
	 * Copy object
	 * @return Core_Entity
	 * @hostcms-event advertisement.onAfterRedeclaredCopy
	 */
	public function copy()
	{
		$newObject = parent::copy();

		// Существует файл копируемого баннера
		if (Core_File::isFile($fileSourcePath = $this->getFilePath()))
		{
			$newObject->saveFile($fileSourcePath, $this->source);
		}

		$aAdvertisementContextualwords = $this->Advertisement_Contextualwords->findAll();

		foreach ($aAdvertisementContextualwords as $oAdvertisementContextualword)
		{
			$oNewAdvertisementContextualword = $oAdvertisementContextualword->copy();
			$newObject->add($oNewAdvertisementContextualword);
		}

		Core_Event::notify($this->_modelName . '.onAfterRedeclaredCopy', $newObject, array($this));

		return $newObject;
	}

	/**
	 * Get href for files
	 * @return string
	 */
	public function getHref()
	{
		return $this->Site->uploaddir . 'img/';
	}

	/**
	 * Get path for files
	 * @return string
	 */
	public function getPath()
	{
		return CMS_FOLDER . $this->getHref();
	}

	/**
	 * Get file path
	 * @return string
	 */
	public function getFilePath()
	{
		return $this->getPath() . $this->source;
	}

	/**
	 * Get file href
	 * @return string
	 */
	public function getFileHref()
	{
		return '/' . $this->getHref() . $this->source;
	}

	/**
	 * Save advertisement banner file
	 * @param string $fileSourcePath source file
	 * @param string $fileName file name
	 * @return self
	 */
	public function saveFile($fileSourcePath, $fileName)
	{
		$this->createDir();

		$filePath = $this->getFilePath();

		// Delete old file
		if ($this->source != '' && Core_File::isFile($filePath))
		{
			$this->deleteFile();
		}

		$this->source = $this->id . '.' . Core_File::getExtension(basename($fileName));
		$this->save();

		Core_File::upload($fileSourcePath, $this->getFilePath());

		return $this;
	}

	/**
	 * Delete advertisement banner file
	 * @return self
	 * @hostcms-event advertisement.onAfterDeleteFile
	 */
	public function deleteFile()
	{
		try
		{
			Core_File::delete($this->getFilePath());
		} catch (Exception $e) {}

		Core_Event::notify($this->_modelName . '.onAfterDeleteFile', $this);

		$this->source = '';
		$this->save();

		return $this;
	}

	/**
	 * Get advertisement by site id
	 * @param int $site_id site id
	 * @return array
	 */
	public function getBySiteId($site_id)
	{
		$this->queryBuilder()
			->clear()
			->where('site_id', '=', $site_id);

		return $this->findAll();
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event advertisement.onBeforeGetRelatedSite
	 * @hostcms-event advertisement.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return self
	 * @hostcms-event advertisement.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$this->Advertisement_Contextualwords->deleteAll(FALSE);
		$this->Advertisement_Group_Lists->deleteAll(FALSE);
		$this->Advertisement_Statistics->deleteAll(FALSE);
		$this->Advertisement_Shows->deleteAll(FALSE);

		$this->deleteFile();

		return parent::delete($primaryKey);
	}

	/**
	 * Get XML for entity and children entities
	 * @return string
	 * @hostcms-event advertisement.onBeforeRedeclaredGetXml
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
	 * @hostcms-event advertisement.onBeforeRedeclaredGetStdObject
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
			->addXmlTag('dir', '/' . $this->getHref());

		return $this;
	}

	/**
	 * Increment advertisement counter
	 * @return self
	 */
	public function incAdvertisementStatistic($showed = TRUE, $clicks = FALSE)
	{
		$date = date('Y-m-d');

		// LOCK TABLES
		Core_QueryBuilder::lock()
			->table('advertisement_statistics', 'WRITE')
			->table('advertisement_shows', 'WRITE')
			->execute();

		/* В подневной таблице показов либо наращиваем счётчик показов за день, либо,
		 если настал новый день добавляем новую запись */
		$oAdvertisement_Statistic = $this
			->Advertisement_Statistics
			->getByDate($date);

		if (is_null($oAdvertisement_Statistic))
		{
			$oAdvertisement_Statistic = Core_Entity::factory('Advertisement_Statistic');
			$oAdvertisement_Statistic->date = $date;
			$oAdvertisement_Statistic->showed = $showed ? 1 : 0;
			$oAdvertisement_Statistic->clicks = $clicks ? 1 : 0;

			// Если настал новый день, очищаем список показов
			Advertisement_Controller::instance()->clearAdvertisementShows();
		}
		else
		{
			$showed && $oAdvertisement_Statistic->showed++;
			$clicks && $oAdvertisement_Statistic->clicks++;
		}

		$this->add($oAdvertisement_Statistic);

		// UNLOCK TABLES
		Core_QueryBuilder::lock()->unlock();

		$showed && $this->showed++;
		$clicks && $this->clicks++;

		if ($this->last_date == $date)
		{
			$this->showed_today++;
		}
		else
		{
			$this->showed_today = 1;
			$this->last_date = $date;
		}

		return $this->save();
	}

	/**
	 * Add Advertisement_Show for Advertisement
	 * @return Advertisement_Show_Model
	 */
	public function addAdvertisementShow()
	{
		$oAdvertisement_Show = Core_Entity::factory('Advertisement_Show');
		$oAdvertisement_Show->date = date('Y-m-d');
		$oAdvertisement_Show->time = date('H:i:s');
		$this->add($oAdvertisement_Show);

		return $oAdvertisement_Show;
	}

	/**
	 * Create files directory
	 * @return self
	 */
	public function createDir()
	{
		clearstatcache();

		if (!Core_File::isDir($this->getPath()))
		{
			try
			{
				Core_File::mkdir($this->getPath(), CHMOD, TRUE);
			} catch (Exception $e) {}
		}

		return $this;
	}

	/**
	 * Backend badge
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function img_contextualwordsBadge($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$count = $this->Advertisement_Contextualwords->getCount();
		$count && Core_Html_Entity::factory('Span')
			->class('badge badge-ico badge-palegreen white')
			->value($count < 100 ? $count : '∞')
			->title($count)
			->execute();
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function nameBackend()
	{
		$oCore_Html_Entity_Div = Core_Html_Entity::factory('Div')
			->class('d-flex align-items-center');

		$oCore_Html_Entity_Div->value(
			htmlspecialchars((string) $this->name)
		);

		$bRightTime = ($this->start_datetime == '0000-00-00 00:00:00' || time() > Core_Date::sql2timestamp($this->start_datetime))
			&& ($this->end_datetime == '0000-00-00 00:00:00' || time() < Core_Date::sql2timestamp($this->end_datetime));

		!$bRightTime && $oCore_Html_Entity_Div->class('d-flex align-items-center wrongTime');

		if (!$bRightTime)
		{
			$oCore_Html_Entity_Div
				->add(
					Core_Html_Entity::factory('I')->class('fa fa-clock-o black margin-left-5')
				);
		}

		$this->structure_id && $oCore_Html_Entity_Div->add(
			Core_Html_Entity::factory('Span')
				->class('badge badge-yellow white margin-left-5')
				->value(htmlspecialchars($this->Structure->name))
		);

		$oCore_Html_Entity_Div->execute();
	}
}