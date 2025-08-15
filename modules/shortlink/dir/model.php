<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Shortlink_Dir_Model
 *
 * @package HostCMS
 * @subpackage Shortlink
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Shortlink_Dir_Model extends Core_Entity
{
	/**
	 * Model name
	 * @var mixed
	 */
	protected $_modelName = 'shortlink_dir';

	/**
	 * Backend property
	 * @var int
	 */
	public $img = 0;

	/**
	 * Backend property
	 * @var int
	 */
	public $source = NULL;

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'shortlink_dir' => array('foreign_key' => 'parent_id'),
		'shortlink' => array(),
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'site' => array(),
		'shortlink_dir' => array('foreign_key' => 'parent_id'),
	);

	/**
	 * Default sorting for models
	 * @var array
	 */
	protected $_sorting = array(
		'shortlink_dirs.sorting' => 'ASC'
	);

	/**
	 * List of preloaded values
	 * @var array
	 */
	protected $_preloadValues = array(
		'sorting' => 0,
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
			$this->_preloadValues['site_id'] = CURRENT_SITE;
		}
	}

	/**
	 * Get parent
	 * @return Hostcms_Redirect_Group_Model|NULL
	 */
	public function getParent()
	{
		return $this->parent_id
			? Core_Entity::factory('Shortlink_Dir', $this->parent_id)
			: NULL;
	}

	/**
	 * Get count of items all levels
	 * @return int
	 */
	public function getChildCount()
	{
		$count = $this->Shortlinks->getCount();

		$aShortlink_Dirs = $this->Shortlink_Dirs->findAll(FALSE);
		foreach ($aShortlink_Dirs as $oShortlink_Dir)
		{
			$count += $oShortlink_Dir->getChildCount();
		}

		return $count;
	}

	/**
	 * Backend callback method
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function sourceBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$link = $oAdmin_Form_Field->link;
		$onclick = $oAdmin_Form_Field->onclick;

		$link = $oAdmin_Form_Controller->doReplaces($oAdmin_Form_Field, $this, $link);
		$onclick = $oAdmin_Form_Controller->doReplaces($oAdmin_Form_Field, $this, $onclick);

		$oCore_Html_Entity_Div = Core_Html_Entity::factory('Div');

		$oCore_Html_Entity_Div
			->add(
				Core_Html_Entity::factory('A')
					->href($link)
					->onclick($onclick)
					->value(htmlspecialchars($this->name))
			);

		$iCountShortlinks = $this->getChildCount();

		$iCountShortlinks > 0 && $oCore_Html_Entity_Div
			->add(
				Core_Html_Entity::factory('Span')
					->class('badge badge-hostcms badge-square')
					->value($iCountShortlinks)
			);

		$oCore_Html_Entity_Div->execute();
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return Core_Entity
	 * @hostcms-event shortlink_dir.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$this->Shortlink_Dirs->deleteAll(FALSE);
		$this->Shortlinks->deleteAll(FALSE);

		return parent::delete($primaryKey);
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event shortlink_dir.onBeforeGetRelatedSite
	 * @hostcms-event shortlink_dir.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}