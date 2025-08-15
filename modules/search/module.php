<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Search Module.
 *
 * @package HostCMS
 * @subpackage Search
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Search_Module extends Core_Module_Abstract
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
	protected $_moduleName = 'search';

	/**
	 * Get List of Schedule Actions
	 * @return array
	 */
	public function getScheduleActions()
	{
		return array(
			0 => array(
				'name' => 'reindex',
				'entityCaption' => Core::_('Search.reindex')
			)
		);
	}

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		if (-827242328 & (~Core::convert64b32(Core_Array::get(Core::$config->get('core_hostcms'), 'hostcms'))))
		{
			throw new Core_Exception(base64_decode('TW9kdWxlIFNlYXJjaCBpcyBmb3JiaWRkZW4u'), array(), 0, FALSE, 0, FALSE);
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
				'sorting' => 130,
				'block' => 1,
				'ico' => 'fa-solid fa-magnifying-glass',
				'name' => Core::_('Search.menu'),
				'href' => Admin_Form_Controller::correctBackendPath("/{admin}/search/index.php"),
				'onclick' => Admin_Form_Controller::correctBackendPath("$.adminLoad({path: '/{admin}/search/index.php'}); return false")
			)
		);

		return parent::getMenu();
	}

	/**
	 * Notify module on the action on schedule
	 * @param Schedule_Model $oSchedule
	 */
	public function callSchedule($oSchedule)
	{
		$action = $oSchedule->action;
		$entityId = $oSchedule->entity_id;

		$aSiteIDs = array();

		if (!$entityId)
		{
			$aSites = Core_Entity::factory('Site')->getAllByactive(1);

			foreach ($aSites as $oSite)
			{
				$aSiteIDs[] = $oSite->id;
			}
		}
		else
		{
			$aSiteIDs[] = $entityId;
		}

		switch ($action)
		{
			// Re-index sites
			case 0:
				@set_time_limit(9000);

				$Search_Controller = Search_Controller::instance();

				// Цикл по модулям
				$oModules = Core_Entity::factory('Module');
				$oModules->queryBuilder()
					->where('modules.active', '=', 1)
					->where('modules.indexing', '=', 1);

				$aModules = $oModules->findAll(FALSE);

				Core_Session::start();

				foreach ($aSiteIDs as $site_id)
				{
					$Search_Controller->truncate($site_id);

					$step = 500;
					foreach ($aModules as $oModule)
					{
						//echo "\nModule ", $oModule->path;

						$oModule->loadModule();

						if (!is_null($oModule->Core_Module))
						{
							if (method_exists($oModule->Core_Module, 'indexing'))
							{
								$offset
									= $_SESSION['search_block']
									= $_SESSION['previous_step']
									= $_SESSION['last_limit'] = 0;

								do {
									$previousSearchBlock = Core_Array::get($_SESSION, 'search_block');

									$mTmp = $oModule->Core_Module->indexing($site_id, $offset, $step);

									if (isset($mTmp['pages']) && isset($mTmp['finished']))
									{
										// Проиндексированные страницы
										$aPages = $mTmp['pages'];

										// Модуль завершил индексацию
										$finished = $mTmp['finished'];

										// Проиндексировано последним блоком, может быть меньше количества $aPages, т.к. $aPages содержит результат нескольких блоков
										$indexed = $mTmp['indexed'];
									}
									else
									{
										$aPages = $mTmp;

										$indexed = $_SESSION['last_limit'] > 0
											? $_SESSION['last_limit']
											: $step;

										// Больше, т.к. некоторые модули могут возвращать больше проиндексированных элементов, чем запрошено, например, форумы
										$finished = empty($aPages) || count($aPages) < $step;
									}

									$count = $aPages ? count($aPages) : 0;

									$count && $Search_Controller->indexingSearchPages($aPages);

									if (!$finished)
									{
										// Если предыдущая индексация шла в несколько этапов, лимит сбрасывается для нового шага
										if (Core_Array::get($_SESSION, 'search_block') != $previousSearchBlock)
										{
											$offset = 0;
										}

										$offset += $indexed;
									}

									Core_ObjectWatcher::clear();
									Search_Stemmer::instance('ru')->clearCache();

									//$offset += $step;
								} while ($aPages && $count >= $step);
							}
						}
					}
					$Search_Controller->optimize($site_id);
				}
				$Search_Controller->optimize(0);
			break;
		}
	}

	/**
	 * Функция обратного вызова для поисковой индексации
	 *
	 * @param int $site_id
	 * @param int $offset
	 * @param int $limit
	 * @return array
	 */
	public function indexing($site_id, $offset, $limit)
	{
		Core_Log::instance()->clear()
			->notify(FALSE)
			->status(Core_Log::$MESSAGE)
			->write("search indexingLinks({$offset}, {$limit})");

		$aTmp = $this->indexingLinks($site_id, $offset, $limit);

		$aTmp['indexed'] = $aTmp['resultLimit'];

		return $aTmp;
	}

	/**
	 * Индексация статичных страниц
	 *
	 * @param int $site_id
	 * @param int $offset
	 * @param int $limit
	 * @return array
	 * @hostcms-event Search_Module.indexingLinks
	 */
	public function indexingLinks($site_id, $offset, $limit)
	{
		$site_id = intval($site_id);
		$offset = intval($offset);
		$limit = intval($limit);

		$oSearch_Link = Core_Entity::factory('Search_Link');
		$oSearch_Link
			->queryBuilder()
			->where('site_id', '=', $site_id)
			->orderBy('search_links.id', 'DESC')
			->limit($offset, $limit);

		Core_Event::notify(get_class($this) . '.indexingLinks', $this, array($oSearch_Link));

		$aSearch_Link = $oSearch_Link->findAll(FALSE);

		// Ожидаемый лимит, используется ориентировочно при индексации вложенных директорий
		$expectedLimit = $limit;
		$resultLimit = 0;

		$finished = TRUE;

		$result = array();
		foreach ($aSearch_Link as $oSearch_Link)
		{
			$aTmp = $oSearch_Link->indexing($expectedLimit);

			count($aTmp['pages'])
				&& $result = array_merge($result, $aTmp['pages']);

			// Ссылка полностью проиндексирована, можно переходить к следующей
			if ($aTmp['finished'])
			{
				$resultLimit += 1;
			}
			else
			{
				$finished = FALSE;
				break;
			}
		}

		return array('pages' => $result, 'resultLimit' => $resultLimit, 'finished' => $finished);
	}
}