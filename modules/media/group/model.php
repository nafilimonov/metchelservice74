<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Media_Group_Model
 *
 * @package HostCMS
 * @subpackage Media
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Media_Group_Model extends Core_Entity
{
	/**
	 * Model name
	 * @var mixed
	 */
	protected $_modelName = 'media_group';

	/**
	 * Backend property
	 * @var mixed
	 */
	public $img = 0;

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'media_item' => array(),
		'media_group' => array('foreign_key' => 'parent_id')
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'media_group' => array('foreign_key' => 'parent_id'),
		'site' => array(),
		'user' => array()
	);

	/**
	 * Forbidden tags. If list of tags is empty, all tags will be shown.
	 *
	 * @var array
	 */
	protected $_forbiddenTags = array(
		'deleted',
		'user_id'
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
	 * Get parent
	 * @return Media_Group_Model|NULL
	 */
	public function getParent()
	{
		return $this->parent_id
			? Core_Entity::factory('Media_Group', $this->parent_id)
			: NULL;
	}

	/**
	 * Get count of items all levels
	 * @return int
	 */
	public function getChildCount()
	{
		$count = $this->Media_Items->getCount();

		$aMedia_Groups = $this->Media_Groups->findAll(FALSE);
		foreach ($aMedia_Groups as $oMedia_Group)
		{
			$count += $oMedia_Group->getChildCount();
		}

		return $count;
	}

	/**
	 * Get count of items all levels
	 * @return int
	 */
	public function getGroupsChildCount()
	{
		$count = $this->Media_Groups->getCount();

		$aMedia_Groups = $this->Media_Groups->findAll(FALSE);
		foreach ($aMedia_Groups as $oMedia_Group)
		{
			$count += $oMedia_Group->getGroupsChildCount();
		}

		return $count;
	}

	/**
	 * Backend callback method
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function nameBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$object = $this;

		// $link = $oAdmin_Form_Field->link;
		// $onclick = $oAdmin_Form_Field->onclick;

		// $link = $oAdmin_Form_Controller->doReplaces($oAdmin_Form_Field, $object, $link);
		// $onclick = $oAdmin_Form_Controller->doReplaces($oAdmin_Form_Field, $object, $onclick);

		$parentWindowId = preg_replace('/[^A-Za-z0-9_-]/', '', Core_Array::getGet('parentWindowId', '', 'str'));
		$modalWindowId = preg_replace('/[^A-Za-z0-9_-]/', '', Core_Array::getGet('modalWindowId', '', 'str'));

		$entity_id = Core_Array::getGet('entity_id', 0, 'int');
		$entity_type = Core_Array::getGet('entity_type', '', 'trim');

		$onclick = $oAdmin_Form_Controller->getAdminLoadAjax(array(
			'path' => $oAdmin_Form_Controller->getPath(),
			'additionalParams' => 'media_group_id=' . $this->id . ($entity_id ? '&showMediaModal=1&entity_id=' . $entity_id . '&entity_type=' . $entity_type . '&modalWindowId=' . $modalWindowId . '&parentWindowId=' . $parentWindowId: '')
		));

		$oCore_Html_Entity_Div = Core_Html_Entity::factory('Div');

		$oCore_Html_Entity_Div->add(
			Core_Html_Entity::factory('A')
				//->href($link)
				->onclick($onclick)
				->value(htmlspecialchars($object->name !== '' ? $object->name : Core::_('Admin.no_title')))
		);

		$oCore_Html_Entity_Div->execute();
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return self
	 * @hostcms-event media_group.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$this->Media_Items->deleteAll(FALSE);
		$this->Media_Groups->deleteAll(FALSE);

		return parent::delete($primaryKey);
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event media_group.onBeforeGetRelatedSite
	 * @hostcms-event media_group.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}