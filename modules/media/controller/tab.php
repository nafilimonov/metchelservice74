<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Media.
 *
 * @package HostCMS
 * @subpackage Media
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Media_Controller_Tab extends Core_Servant_Properties
{
	/**
	* Form controller
	* @var Admin_Form_Controller
	*/
	protected $_Admin_Form_Controller = NULL;

	/**
	* Get _Admin_Form_Controller
	* @return Admin_Form_Controller
	*/
	public function getAdmin_Form_Controller()
	{
		return $this->_Admin_Form_Controller;
	}

	/**
	* Constructor.
	* @param Admin_Form_Controller $Admin_Form_Controller controller
	*/
	public function __construct(Admin_Form_Controller $Admin_Form_Controller)
	{
		$this->_Admin_Form_Controller = $Admin_Form_Controller;

		parent::__construct();
	}

	/**
	* Create and return an object of Property_Controller_Tab for current skin
	* @return object
	*/
	static public function factory(Admin_Form_Controller $Admin_Form_Controller)
	{
		$className = 'Skin_' . ucfirst(Core_Skin::instance()->getSkinName()) . '_' . __CLASS__;
		//die($className);

		if (!class_exists($className))
		{
			throw new Core_Exception("Class '%className' does not exist",
				array('%className' => $className));
		}

		return new $className($Admin_Form_Controller);
	}

	/**
	* Object
	* @var object
	*/
	protected $_object = NULL;

	/**
	* Set object
	* @param Core_Entity $object object
	* @return self
	*/
	public function setObject(Core_Entity $object)
	{
		$this->_object = $object;
		return $this;
	}

	/**
	* Get object
	* @return Core_Entity
	*/
	public function getObject()
	{
		return $this->_object;
	}

	/**
	* Dataset ID
	* @var int
	*/
	protected $_datasetId = NULL;

	/**
	* Set ID of dataset
	* @param int $datasetId ID of dataset
	* @return self
	*/
	public function setDatasetId($datasetId)
	{
		$this->_datasetId = $datasetId;
		return $this;
	}

	/**
	* Tab
	* @var Skin_Default_Admin_Form_Entity_Tab
	*/
	protected $_tab = NULL;

	/**
	* Set tab
	* @param Skin_Default_Admin_Form_Entity_Tab $tab tab
	* @return self
	*/
	public function setTab(Skin_Default_Admin_Form_Entity_Tab $tab)
	{
		$this->_tab = $tab;
		return $this;
	}

	/**
	* Show files on tab
	* @return self
	*/
	public function fillTab($ajax = FALSE)
	{
		$modalWindowId = preg_replace('/[^A-Za-z0-9_-]/', '', Core_Array::getGet('modalWindowId', '', 'str'));
		$windowId = $modalWindowId ? $modalWindowId : $this->_Admin_Form_Controller->getWindowId();

		$windowId = !$ajax ? "{$windowId}-media-items" : $windowId;

		$modelName = $this->_object->getModelName();

		$oWrapper = Admin_Form_Entity::factory('Div')
			->id($windowId);

		if (is_null($this->_object->id))
		{
			$this->_tab->add(
				$oWrapper->add(
					Admin_Form_Entity::factory('Code')->html(
						Core_Message::get(Core::_('Media.enable_after_save'), 'warning')
					)
				)
			);

			return $this;
		}

		$oWrapper
			->add(
				Admin_Form_Entity::factory('Button')
				->name('add')
				->class('btn btn-success')
				->value(Core::_('Admin_Form.add'))
				->onclick($this->_Admin_Form_Controller->getAdminActionModalLoad(array('path' => '/{admin}/media/index.php', 'action' => '', 'operation' => 'modal', 'datasetKey' => 0, 'datasetValue' => 0, 'additionalParams' => "showMediaModal=1&entity_id={$this->_object->id}&entity_type={$modelName}", 'window' => $windowId, 'width' => '90%')))
			)
			->add(
				$oMediaWrapper = Admin_Form_Entity::factory('Div')
					->class('media-wrapper')
			);

		$oMedia_Items = Core_Entity::factory('Media_Item');
		$oMedia_Items->queryBuilder()
			->select('media_items.*', array('media_' . $modelName . 's.id', 'dataEntityId'))
			->join('media_' . $modelName . 's', 'media_' . $modelName . 's.media_item_id', '=', 'media_items.id')
			->where('media_' . $modelName . 's.' . $modelName . '_id', '=', $this->_object->id)
			->clearOrderBy()
			->orderBy('sorting');

		$aMedia_Items = $oMedia_Items->findAll(FALSE);

		foreach ($aMedia_Items as $oMedia_Item)
		{
			$oEntity = Core_Entity::factory('media_' . $modelName, $oMedia_Item->dataEntityId);

			$oMediaWrapper->add(
				Media_Controller::getMediaItemBlock($windowId, $oMedia_Item, $this->_Admin_Form_Controller, $oEntity, $this->_object)
			);
		}

		$oWrapper->add(Core_Html_Entity::factory('Script')->value("
			$('.media-wrapper').sortable({
				connectWith: '.media-wrapper',
				items: '> div.media-item',
				scroll: false,
				placeholder: 'placeholder',
				cancel: '.media-item-file-delete',
				tolerance: 'pointer',
				// appendTo: 'body',
				// helper: 'clone',
				helper: function(event, ui) {
					var jUi = $(ui),
						clone = jUi.clone(true);
						// clone.css('border', '1px solid red');

					return clone.css('position','absolute').get(0);
				},

				start: function(event, ui) {
					// Ghost show
					$('.media-wrapper').find('div.media-item:hidden')
						.addClass('ghost-item')
						.css('opacity', .5)
						.show();
				},
				stop: function(event, ui) {
					// Ghost hide
					var ghostItem = $('.media-wrapper').find('div.ghost-item');

					ghostItem
						.removeClass('ghost-item')
						.css('opacity', 1);

					$.refreshMediaSorting('" . $windowId . "', '" . $modelName . "');
				}
			}).disableSelection();
		"));

		$this->_tab->add($oWrapper);

		$ajax && $this->_tab->execute();

		return $this;
	}

	/**
	 * Set sorting for media items
	 * @param string $modelName
	 * @param array $aData
	 * @return self
	 */
	public function setSorting($modelName, $aData = array())
	{
		// echo "<pre>";
		// var_dump($aData);
		// echo "</pre>";

		$sorting = 0;

		foreach ($aData as $key => $value)
		{
			if (strpos($key, 'media_') === 0)
			{
				$aTmp = explode('_', $key);
				if (count($aTmp) == 3)
				{
					$oEntities = Core_Entity::factory('Media_' . $modelName);
					$oEntities->queryBuilder()
						->where('media_' . $modelName . 's.' . $modelName . '_id', '=', $aTmp[1])
						->where('media_' . $modelName . 's.media_item_id', '=', $aTmp[2]);

					$oEntity = $oEntities->getLast(FALSE);

					if (!is_null($oEntity))
					{
						$oEntity->sorting = $sorting;
						$oEntity->save();

						$sorting++;
					}
				}
			}
		}

		return $this;
	}

	/**
	* Apply object property
	* @hostcms-event Media_Controller_Tab.onBeforeApplyObjectProperty
	* @hostcms-event Media_Controller_Tab.onAfterApplyObjectProperty
	*/
	public function applyObjectProperty()
	{
		Core_Event::notify('Media_Controller_Tab.onBeforeApplyObjectProperty', $this, array($this->_Admin_Form_Controller));

		$modelName = $this->_object->getModelName();

		$this->setSorting($modelName, $_POST);

		ob_start();
		Core_Html_Entity::factory('Script')
			->value("if($('div.ui-sortable').length) { $('div.ui-sortable').each(function(index, object) { $(object).sortable('refresh'); }); }")
			->execute();

		$this->_Admin_Form_Controller->addMessage(ob_get_clean());

		Core_Event::notify('Media_Controller_Tab.onAfterApplyObjectProperty', $this, array($this->_Admin_Form_Controller));
	}
}