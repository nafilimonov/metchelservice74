<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Eventlog Module.
 *
 * @package HostCMS
 * @subpackage Eventlog
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Eventlog_Module extends Core_Module_Abstract
{
	/**
	 * Module version
	 * @var string
	 */
	public $version = '7.1';

	/**
	 * Module date
	 * @var date
	 */
	public $date = '2025-04-04';

	/**
	 * Module name
	 * @var string
	 */
	protected $_moduleName = 'eventlog';

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		if (1283580064 & (~Core::convert64b32(Core_Array::get(Core::$config->get('core_hostcms'), 'hostcms'))))
		{
			throw new Core_Exception(base64_decode('TW9kdWxlIEV2ZW50bG9nIGlzIGZvcmJpZGRlbi4='), array(), 0, FALSE, 0, FALSE);
		}
	}

	/**
	 * Get Module's Menu
	 * @return array
	 */
	public function getMenu()
	{
		$this->menu = array(
			array(
				'sorting' => 190,
				'block' => 3,
				'ico' => 'fa fa-book',
				'name' => Core::_('Eventlog.menu'),
				'href' => Admin_Form_Controller::correctBackendPath("/{admin}/eventlog/index.php"),
				'onclick' => Admin_Form_Controller::correctBackendPath("$.adminLoad({path: '/{admin}/eventlog/index.php'}); return false")
			)
		);

		return parent::getMenu();
	}

	/**
	 * Send daily report
	 * @param date $date date
	 * @return boolean
	 */
	public function sendReport($date)
	{
		$oAdmin_Form_Dataset = new Eventlog_Dataset();

		$oAdmin_Form_Dataset
			->offset(0)
			->limit(9999)
			->addCondition(array(
				'and' => array('datetime', '=', $date)
			));

		$iCountEvents = $oAdmin_Form_Dataset->getCount();

		if ($iCountEvents)
		{
			$aEventlog_Events = $oAdmin_Form_Dataset->load();

			$iInfo = $iSuccessful = $iNotice = $iWarning = $iCritical = 0;

			$aCritical = array();

			foreach ($aEventlog_Events as $oEventlog_Event)
			{
				switch ($oEventlog_Event->status)
				{
					case 4:
						$iCritical++;
						$aCritical[] = $oEventlog_Event;
					break;
					case 3:
						$iWarning++;
					break;
					case 2:
						$iNotice++;
					break;
					case 1:
						$iSuccessful++;
					break;
					case 0:
						$iInfo++;
					break;
				}
			}

			unset($aEventlog_Events);

			$message='Здравствуйте!<br><br>';
			$message .= '<p>За прошедший день произошло <b>' . $iCountEvents . '</b> событий, из них: <br>';
			$message .= '<b>Успешных событий:</b> ' . $iSuccessful.'<br>';
			$message .= '<b>Нейтральных событий:</b> ' . $iInfo.'<br>';
			$message .= '<b>Замечаний:</b> ' . $iNotice.'<br>';
			$message .= '<b>Предупреждений:</b> ' . $iWarning.'<br>';
			$message .= '<b>Наивысшего уровня критичности:</b> ' . $iCritical.'</p>';

			if (count($aCritical))
			{
				$message .= '<p><b>Перечень событий наивысшего уровня критичности:</b><br>';

				foreach ($aCritical as $oEventlog_Event)
				{
						$message .= '--------------------------------------------------------------------------------------------------------<br>';
						$message .= '<b>Дата:</b> ' . Core_Date::sql2datetime($oEventlog_Event->datetime) . '<br>';
						$message .= '<b>Пользователь</b>: ' . $oEventlog_Event->login . '<br>';
						$message .= '<b>Событие:</b> ' . $oEventlog_Event->event . '<br>';
						$message .= '<b>Сайт:</b> ' . $oEventlog_Event->site . '<br>';
						$message .= '<b>Страница:</b> ' . $oEventlog_Event->page.'<br>';
						$message .= '<b>IP-адрес:</b> ' . $oEventlog_Event->ip . '<br>';
				}
				$message .= '--------------------------------------------------------------------------------------------------------';
			}
			$message .= '<p>Система управления сайтом HostCMS';
			$message .= '<br/><a href="http://www.hostcms.ru">http://www.hostcms.ru</a></p>';

			Core_Mail::instance()
				->to(SUPERUSER_EMAIL)
				->from(SUPERUSER_EMAIL)
				->subject('HostCMS: Отчет системы управления сайтом за ' . Core_Date::sql2date($date))
				->message($message)
				->contentType('text/html')
				->header('X-HostCMS-Reason', 'Eventlog Report')
				->header('Precedence', 'bulk')
				->messageId()
				->send();

			return TRUE;
		}

		return FALSE;
	}
}