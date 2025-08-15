<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead Timeline Dataset.
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Lead_Timeline_Dataset extends Admin_Form_Dataset
{
	/**
	 * Items count
	 * @var int
	 */
	protected $_count = NULL;

	/**
	 * Lead_Model object
	 * @var object
	 */
	protected $_lead = NULL;

	/**
	 * Constructor.
	 * @param Lead_Model $oLead entity
	 * @hostcms-event Lead_Timeline_Dataset.onAfterConstruct
	 */
	public function __construct(Lead_Model $oLead)
	{
		$this->_lead = $oLead;

		Core_Event::notify(get_class($this) . '.onAfterConstruct', $this);
	}

	/**
	 * Get FOUND_ROWS
	 * @return int
	 */
	protected function _getFoundRows()
	{
		// Warning
		if (!is_null(Core_Array::getRequest('debug')))
		{
			echo '<p><b>Query FOUND_ROWS</b>.</p>';
		}

		return Core_QueryBuilder::select()->getFoundRows();
	}

	/**
	 * Get lead history
	 * @param int $id
	 * @return object
	 */
	protected function _getQb0($id = NULL)
	{
		// Load model columns BEFORE FOUND_ROWS()
		Core_Entity::factory('Lead_History')->getTableColumns();

		$oQb = Core_QueryBuilder::select(array(0, 'type'), 'id', 'datetime')
			->from('lead_histories')
			->where('lead_histories.lead_id', '=', $this->_lead->id);

		$id && $oQb->where('lead_histories.id', '=', $id);

		return $oQb;
	}

	/**
	 * Get crm note
	 * @param int $id
	 * @return object
	 */
	protected function _getQb1($id = NULL)
	{
		// Load model columns BEFORE FOUND_ROWS()
		Core_Entity::factory('Crm_Note')->getTableColumns();

		$oQb = Core_QueryBuilder::select(array(1, 'type'), 'crm_notes.id', 'datetime')
			->from('crm_notes')
			->leftJoin('lead_crm_notes', 'crm_notes.id', '=', 'lead_crm_notes.crm_note_id')
			->where('lead_crm_notes.lead_id', '=', $this->_lead->id)
			->where('crm_notes.deleted', '=', 0);

		$id && $oQb->where('crm_notes.id', '=', $id);

		return $oQb;
	}

	/**
	 * Get lead shop items
	 * @param int $id
	 * @return object
	 */
	protected function _getQb2($id = NULL)
	{
		// Load model columns BEFORE FOUND_ROWS()
		Core_Entity::factory('Lead_Shop_Item')->getTableColumns();

		$oQb = Core_QueryBuilder::select(array(2, 'type'), 'lead_shop_items.id', 'datetime')
			->from('lead_shop_items')
			->where('lead_shop_items.lead_id', '=', $this->_lead->id)
			->where('lead_shop_items.deleted', '=', 0);

		$id && $oQb->where('lead_shop_items.id', '=', $id);

		return $oQb;
	}

	/**
	 * Get events
	 * @param int $id
	 * @return object
	 */
	protected function _getQb3($id = NULL)
	{
		// Load model columns BEFORE FOUND_ROWS()
		Core_Entity::factory('Event')->getTableColumns();

		$oQb = Core_QueryBuilder::select(array(3, 'type'), 'events.id', 'datetime')
			->from('events')
			->leftJoin('lead_events', 'events.id', '=', 'lead_events.event_id')
			->where('lead_events.lead_id', '=', $this->_lead->id)
			->where('events.deleted', '=', 0);

		$id && $oQb->where('events.id', '=', $id);

		return $oQb;
	}

	/**
	 * Get lead steps
	 * @param int $id
	 * @return object
	 */
	protected function _getQb4($id = NULL)
	{
		// Load model columns BEFORE FOUND_ROWS()
		Core_Entity::factory('Lead_Step')->getTableColumns();

		$oQb = Core_QueryBuilder::select(array(4, 'type'), 'lead_steps.id', 'datetime')
			->from('lead_steps')
			->where('lead_steps.lead_id', '=', $this->_lead->id);

		$id && $oQb->where('lead_steps.id', '=', $id);

		return $oQb;
	}

	/**
	 * Get dms documents
	 * @param int $id
	 * @return object
	 */
	protected function _getQb5($id = NULL)
	{
		// Load model columns BEFORE FOUND_ROWS()
		Core_Entity::factory('Dms_Document')->getTableColumns();

		$oQb = Core_QueryBuilder::select(array(5, 'type'), 'dms_documents.id', array('created', 'datetime'))
			->from('dms_documents')
			->leftJoin('lead_dms_documents', 'dms_documents.id', '=', 'lead_dms_documents.dms_document_id')
			->where('lead_dms_documents.lead_id', '=', $this->_lead->id)
			->where('dms_documents.deleted', '=', 0);

		$id && $oQb->where('dms_documents.id', '=', $id);

		return $oQb;
	}

	/**
	 * Load items
	 */
	protected function _loadItems()
	{
		if ($this->_limit)
		{
			$oQB = $this->_getQb0()
				->sqlCalcFoundRows()
				->union($this->_getQb1())
				->union($this->_getQb2());

			Core::moduleIsActive('event')
				&& $oQB->union($this->_getQb3());

			$oQB
				->union($this->_getQb4())
				->unionOrderBy('datetime', 'DESC')
				->unionLimit($this->_limit)
				->unionOffset($this->_offset);

			Core::moduleIsActive('dms') && $oQB->union($this->_getQb5());

			$oDataBase = $oQB->execute();

			$aObjects = $oDataBase->asObject()->result();

			foreach ($aObjects as $oObject)
			{
				//$oObject->id = $oObject->type . '-' . $oObject->id;

				$this->_objects[$oObject->type . '-' . $oObject->id] = $this->_getObjectByType($oObject);
			}

			$this->_loaded = TRUE;
			$this->_count = $this->_getFoundRows();
		}

		// Warning
		if (!is_null(Core_Array::getRequest('debug')))
		{
			echo '<p><b>Query</b>: sqlCalcFoundRows before FOUND_ROWS()</p>';
		}
	}

	/**
	 * Get object by type
	 * @param object $object
	 * @return object
	 */
	protected function _getObjectByType($object)
	{
		switch ($object->type)
		{
			case 0:
				return Core_Entity::factory('Lead_History', $object->id)->dataDatetime($object->datetime);
			break;
			case 1:
				return Core_Entity::factory('Crm_Note', $object->id)->dataDatetime($object->datetime);
			break;
			case 2:
				return Core_Entity::factory('Lead_Shop_Item', $object->id)->dataDatetime($object->datetime);
			break;
			case 3:
				return Core_Entity::factory('Event', $object->id)->dataDatetime($object->datetime);
			break;
			case 4:
				return Core_Entity::factory('Lead_Step', $object->id)->dataDatetime($object->datetime);
			break;
			case 5:
				return Core_Entity::factory('Dms_Document', $object->id)->dataDatetime($object->datetime);
			break;
			default:
				throw new Core_Exception('_getObjectByType(): Wrong type', array(), 0, FALSE);
		}
	}

	/**
	 * Get count of finded objects
	 * @return int
	 */
	public function getCount()
	{
		is_null($this->_count) && $this->_loadItems();

		return $this->_count;
	}

	/**
	 * Load objects
	 * @return array
	 */
	public function load()
	{
		if (!$this->_loaded)
		{
			$this->_loadItems();

			$this->_loaded = TRUE;
		}

		return $this->_objects;
	}

	/**
	 * Get entity
	 * @return object
	 */
	public function getEntity()
	{
		return new stdClass();
	}

	/**
	 * Get object
	 * @param int $primaryKey ID
	 * @return object
	 */
	public function getObject($primaryKey)
	{
		$this->load();

		if (isset($this->_objects[$primaryKey]))
		{
			return $this->_objects[$primaryKey];
		}
		elseif (strpos($primaryKey, '-') !== FALSE)
		{
			list($type, $id) = explode('-', $primaryKey);

			$functionName = '_getQb' . intval($type);

			if (method_exists($this, $functionName))
			{
				if ($id)
				{
					$oQb = $this->$functionName($id);
					$oDataBase = $oQb->execute();

					$oObject = $oDataBase->asObject()->current();
				}
				else
				{
					$oObject = NULL;
				}

				if (!$oObject)
				{
					$oObject = new stdClass();
					$oObject->type = $type;
					$oObject->datetime = Core_Date::timestamp2sql(time());
					$oObject->id = NULL;
				}

				return $this->_getObjectByType($oObject);
			}
		}

		return NULL;
	}
}