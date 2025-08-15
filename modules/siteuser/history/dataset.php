<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser History Dataset.
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Siteuser_History_Dataset extends Admin_Form_Dataset
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
	 * @hostcms-event Siteuser_History_Dataset.onAfterConstruct
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
	 * Load items
	 */
	protected function _loadItems()
	{
		$oQB = Core_QueryBuilder::select(array(0, 'type'), 'id', 'datetime')
			->sqlCalcFoundRows()
			->from('comments')
			->where('comments.siteuser_id', '=', $this->_siteuser->id)
			->where('comments.deleted', '=', 0)
			->unionOrderBy('datetime', 'DESC')
			->unionLimit($this->_limit)
			->unionOffset($this->_offset);

		if (Core::moduleIsActive('shop'))
		{
			$shop_orders = Core_QueryBuilder::select(array(1, 'type'), 'id', 'datetime')
				->from('shop_orders')
				->where('shop_orders.siteuser_id', '=', $this->_siteuser->id)
				->where('shop_orders.deleted', '=', 0);

			$shop_items = Core_QueryBuilder::select(array(2, 'type'), 'id', 'datetime')
				->from('shop_items')
				->where('shop_items.siteuser_id', '=', $this->_siteuser->id)
				->where('shop_items.deleted', '=', 0);

			$oQB
				->union($shop_orders)
				->union($shop_items);
		}

		if (Core::moduleIsActive('informationsystem'))
		{
			$informationsystem_items = Core_QueryBuilder::select(array(3, 'type'), 'id', 'datetime')
				->from('informationsystem_items')
				->where('informationsystem_items.siteuser_id', '=', $this->_siteuser->id)
				->where('informationsystem_items.deleted', '=', 0);

			$oQB->union($informationsystem_items);
		}

		if (Core::moduleIsActive('helpdesk'))
		{
			$helpdesk_tickets = Core_QueryBuilder::select(array(4, 'type'), 'id', 'datetime')
				->from('helpdesk_tickets')
				->where('helpdesk_tickets.siteuser_id', '=', $this->_siteuser->id)
				->where('helpdesk_tickets.deleted', '=', 0);

			$oQB->union($helpdesk_tickets);
		}

		if (Core::moduleIsActive('event'))
		{
			$events1 = Core_QueryBuilder::select(array(5, 'type'), 'events.id', 'events.datetime')
				->from('events')
				->join('event_siteusers', 'event_siteusers.event_id', '=', 'events.id')
				// Companies
				->join('siteuser_companies', 'siteuser_companies.id', '=', 'event_siteusers.siteuser_company_id',
					array(
						array('AND' => array('siteuser_companies.siteuser_id', '=', $this->_siteuser->id))
					),
					array(
						array('AND' => array('siteuser_companies.deleted', '=', 0))
					)
				)
				->where('events.deleted', '=', 0);

			$events2 = Core_QueryBuilder::select(array(5, 'type'), 'events.id', 'events.datetime')
				->from('events')
				->join('event_siteusers', 'event_siteusers.event_id', '=', 'events.id')
				// People
				->join('siteuser_people', 'siteuser_people.id', '=', 'event_siteusers.siteuser_person_id',
					array(
						array('AND' => array('siteuser_people.siteuser_id', '=', $this->_siteuser->id))
					),
					array(
						array('AND' => array('siteuser_people.deleted', '=', 0))
					)
				)
				->where('events.deleted', '=', 0);

			$oQB->union($events1)->union($events2);
		}

		$queryBuilder = $oQB->execute();

		$this->_objects = $queryBuilder->asObject()->result();

		$this->_loaded = TRUE;
		$this->_count = $this->_getFoundRows();

		// Warning
		if (!is_null(Core_Array::getRequest('debug')))
		{
			echo '<p><b>Query</b>: sqlCalcFoundRows before FOUND_ROWS()</p>';
		}
	}

	/**
	 * Get count of finded objects
	 * @return int
	 */
	public function getCount()
	{
		if (!$this->_count)
		{
			$this->_loadItems();
		}

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
		return $this->_siteuser;
	}
}