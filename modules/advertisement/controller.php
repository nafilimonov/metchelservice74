<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Advertisement.
 *
 * @package HostCMS
 * @subpackage Advertisement
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк"(Hostmake LLC), http://www.hostcms.ru
 */
class Advertisement_Controller extends Core_Servant_Properties
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'keep_days'
	);

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->keep_days = 1;
	}

	/**
	 * The singleton instances.
	 * @var mixed
	 */
	static public $instance = NULL;

	/**
	 * Register an existing instance as a singleton.
	 * @return object
	 */
	static public function instance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get advertisement href
	 * @param int $advertisement_show_id show ID
	 * @return string|NULL
	 */
	public function getLocation($advertisement_show_id)
	{
		$oAdvertisement_Show = Core_Entity::factory('Advertisement_Show')->find($advertisement_show_id);

		if (!is_null($oAdvertisement_Show->Advertisement->id))
		{
			$oAdvertisement = $oAdvertisement_Show->Advertisement;
			
			if ($oAdvertisement_Show->click != 1)
			{
				// Проверяем нажатие для защиты от ботов
				if (Core::moduleIsActive('counter') && Core_Array::get($_SERVER, 'HTTP_USER_AGENT'))
				{
					$bBot = Counter_Controller::instance()->checkBot(
						Core_Array::get($_SERVER, 'HTTP_USER_AGENT')
					);
				}
				else
				{
					$bBot = FALSE;
				}

				if (!$bBot)
				{
					$oAdvertisement_Show->click = 1;
					$oAdvertisement_Show->save();

					// inc clicks
					$oAdvertisement->incAdvertisementStatistic(FALSE, TRUE);
				}
			}

			return $oAdvertisement->href;
		}

		return FALSE;
	}

	/**
	 * Clear advertisement show info
	 * @return self
	 */
	public function clearAdvertisementShows()
	{
		if ($this->keep_days > 0)
		{
			Core_QueryBuilder::delete('advertisement_shows')
				->where('date', '<', Core_Date::timestamp2sql(time() - $this->keep_days * 86400))
				->execute();
		}

		return $this;
	}
}