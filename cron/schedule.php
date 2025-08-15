<?php

/**
 * Запуск действий по расписанию, рекомендуется запускать раз в 1 минуту
 *
 * Пример вызова:
 * /usr/bin/php /var/www/site.ru/httpdocs/cron/schedule.php
 * Пример вызова с передачей php.ini
 * /usr/bin/php --php-ini /etc/php.ini /var/www/site.ru/httpdocs/cron/schedule.php
 * Реальный путь на сервере к корневой директории сайта уточните в службе поддержки хостинга.
 *
 * @package HostCMS 6\cron
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2016 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */

require_once(dirname(__FILE__) . '/../' . 'bootstrap.php');

if (Core::moduleIsActive('schedule'))
{
	$dateTime = Core_Date::timestamp2sql(time());

	$oSchedule_Controller = new Schedule_Controller();

	do {
		$oSchedules = Core_Entity::factory('Schedule');
		$oSchedules->queryBuilder()
			->where('schedules.completed', '=', 0)
			->where('schedules.start_datetime', '<', $dateTime)
			->limit(1)
			->clearOrderBy()
			->orderBy('schedules.id', 'ASC');

		$aSchedules = $oSchedules->findAll(FALSE);

		count($aSchedules)
			&& $oSchedule_Controller->execute($aSchedules[0]);
	}
	while (count($aSchedules));
}
else
{
	throw new Core_Exception("Module 'schedule' does not exist.");
}