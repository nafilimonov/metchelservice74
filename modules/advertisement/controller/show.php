<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Показ баннера
 *
 * <code>
 * $Advertisement_Controller_Show = new Advertisement_Controller_Show(
 * 		Core_Entity::factory('Advertisement', 1)
 * 	);
 *
 * 	$Advertisement_Controller_Show
 * 		->xsl(
 * 			Core_Entity::factory('Xsl')->getByName('ОтображениеБаннера')
 * 		)
 * 		->show();
 * </code>
 *
 * @package HostCMS
 * @subpackage Advertisement
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2020 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Advertisement_Controller_Show extends Core_Controller
{
	/**
	 * Constructor.
	 * @param Advertisement_Model $oAdvertisement advertisement
	 */
	public function __construct(Advertisement_Model $oAdvertisement)
	{
		parent::__construct($oAdvertisement);

		$date = date('Y-m-d');
		$datetime = date("Y-m-d H:i:s");

		$oAdvertisement->queryBuilder()
			->open()
				->where('show_per_day', '>', Core_QueryBuilder::expression('`showed_today`'))
				->where('last_date', '=', $date)
				->setOr()
				->where('last_date', '!=', $date)
			->close()
			->where('show_total', '>', 'showed')
			->where('show_total', '!=', 0)
			->where('show_per_day', '!=', 0)
			->open()
				->where('start_datetime', '=', '0000-00-00 00:00:00')
				->setOr()
				->where('start_datetime', '<=', $datetime)
			->close()
			->open()
				->where('end_datetime', '=', '0000-00-00 00:00:00')
				->setOr()
				->where('end_datetime', '>=', $datetime)
			->close();

		if ($oAdvertisement->getCount())
		{
			$oAdvertisement->incAdvertisementStatistic(TRUE, FALSE);
			// Идентификатор показа
			$oAdvertisement_Show = $oAdvertisement->addAdvertisementShow();

			$this->addEntity($oAdvertisement_Show);
		}
	}

	/**
	 * Show built data
	 * @return self
	 * @hostcms-event Advertisement_Controller_Show.onBeforeRedeclaredShow
	 */
	public function show()
	{
		Core_Event::notify(get_class($this) . '.onBeforeRedeclaredShow', $this);

		// Check crawlers
		if (Core::moduleIsActive('counter') && Counter_Controller::checkBot(Core_Array::get($_SERVER, 'HTTP_USER_AGENT')) && !defined('STATIC_CACHE'))
		{
			return FALSE;
		}

		return parent::show();
	}
}
