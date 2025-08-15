<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser Timeline Dataset.
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Siteuser_Timeline_Dataset extends Admin_Form_Dataset
{
	/**
	 * Items count
	 * @var int
	 */
	protected $_count = NULL;

	/**
	 * Siteuser_Model object
	 * @var object
	 */
	protected $_siteuser = NULL;

	/**
	 * Constructor.
	 * @param Siteuser_Model $oSiteuser entity
	 * @hostcms-event Siteuser_Timeline_Dataset.onAfterConstruct
	 */
	public function __construct(Siteuser_Model $oSiteuser)
	{
		$this->_siteuser = $oSiteuser;

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
	 * Get siteuser email
	 * @param int $id
	 * @return object
	 */
	protected function _getQb0($id = NULL)
	{
		// Load model columns BEFORE FOUND_ROWS()
		Core_Entity::factory('Siteuser_Email')->getTableColumns();

		$oQb = Core_QueryBuilder::select(array(0, 'type'), 'id', 'datetime')
			->from('siteuser_emails')
			->where('siteuser_emails.siteuser_id', '=', $this->_siteuser->id)
			->where('siteuser_emails.deleted', '=', 0);

		$id && $oQb->where('siteuser_emails.id', '=', $id);

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
			->leftJoin('siteuser_crm_notes', 'crm_notes.id', '=', 'siteuser_crm_notes.crm_note_id')
			->where('siteuser_crm_notes.siteuser_id', '=', $this->_siteuser->id)
			->where('crm_notes.deleted', '=', 0);

		$id && $oQb->where('crm_notes.id', '=', $id);

		return $oQb;
	}

	/**
	 * Get siteuser email
	 * @param int $id
	 * @return object
	 */
	protected function _getQb2($id = NULL)
	{
		// Load model columns BEFORE FOUND_ROWS()
		Core_Entity::factory('Shop_Order')->getTableColumns();

		$oQb = Core_QueryBuilder::select(array(2, 'type'), 'id', 'datetime')
			->from('shop_orders')
			->where('shop_orders.siteuser_id', '=', $this->_siteuser->id)
			->where('shop_orders.deleted', '=', 0);

		$id && $oQb->where('shop_orders.id', '=', $id);

		return $oQb;
	}

	/**
	 * Get event
	 * @param int $id
	 * @return object
	 */
	protected function _getQb3($id = NULL)
	{
		// Load model columns BEFORE FOUND_ROWS()
		Core_Entity::factory('Event')->getTableColumns();

		$oUser = Core_Auth::getCurrentUser();

		$oQb = Core_QueryBuilder::select(array(3, 'type'), 'events.id', 'datetime')
			->from('events')
			->join('event_siteusers', 'events.id', '=', 'event_siteusers.event_id')
			->join('event_users', 'events.id', '=', 'event_users.event_id')
			->leftJoin('siteuser_companies', 'event_siteusers.siteuser_company_id', '=', 'siteuser_companies.id')
			->leftJoin('siteuser_people', 'event_siteusers.siteuser_person_id', '=', 'siteuser_people.id')
			->where('event_users.user_id', '=', $oUser->id)
			->open()
				->where('siteuser_companies.siteuser_id', '=', $this->_siteuser->id)
				->setOr()
				->where('siteuser_people.siteuser_id', '=', $this->_siteuser->id)
			->close()
			->where('events.deleted', '=', 0);

		$id && $oQb->where('events.id', '=', $id);

		return $oQb;
	}

	/**
	 * Get event
	 * @param int $id
	 * @return object
	 */
	protected function _getQb4($id = NULL)
	{
		// Load model columns BEFORE FOUND_ROWS()
		Core_Entity::factory('Deal')->getTableColumns();

		$oQb = Core_QueryBuilder::select(array(4, 'type'), 'deals.id', array('start_datetime', 'datetime'))
			->from('deals')
			->join('deal_siteusers', 'deals.id', '=', 'deal_siteusers.deal_id')
			->leftJoin('siteuser_companies', 'deal_siteusers.siteuser_company_id', '=', 'siteuser_companies.id')
			->leftJoin('siteuser_people', 'deal_siteusers.siteuser_person_id', '=', 'siteuser_people.id')
			->open()
				->where('siteuser_companies.siteuser_id', '=', $this->_siteuser->id)
				->setOr()
				->where('siteuser_people.siteuser_id', '=', $this->_siteuser->id)
			->close()
			->where('deals.deleted', '=', 0);

		$id && $oQb->where('deals.id', '=', $id);

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
				// ->union($this->_getQb2())
				;

			Core::moduleIsActive('shop')
				&& $oQB->union($this->_getQb2());

			Core::moduleIsActive('event')
				&& $oQB->union($this->_getQb3());

			Core::moduleIsActive('deal')
				&& $oQB->union($this->_getQb4());

			$oQB
				->unionOrderBy('datetime', 'DESC')
				->unionLimit($this->_limit)
				->unionOffset($this->_offset);
				;

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
				return Core_Entity::factory('Siteuser_Email', $object->id)->dataDatetime($object->datetime);
			break;
			case 1:
				return Core_Entity::factory('Crm_Note', $object->id)->dataDatetime($object->datetime);
			break;
			case 2:
				return Core_Entity::factory('Shop_Order', $object->id)->dataDatetime($object->datetime);
			break;
			case 3:
				return Core_Entity::factory('Event', $object->id)->dataDatetime($object->datetime);
			break;
			case 4:
				return Core_Entity::factory('Deal', $object->id)->dataDatetime($object->datetime);
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