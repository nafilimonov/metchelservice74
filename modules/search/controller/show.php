 <?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Поиск по сайту.
 *
 * Доступные методы:
 *
 * - query($query) поисковый запрос
 * - inner($inner) поиск по внутренним данным, например, Helpdesk. По умолчанию 0 - внешние данные, 1 - внутренние данные
 * - modules($modules) массив условий поиска по модулям
 * - itemsForbiddenTags(array('description')) массив тегов связанных элементов, запрещенных к передаче в генерируемый XML
 * - offset($offset) смещение, с которого выводить информационные элементы, по умолчанию 0
 * - limit($limit) количество выводимых элементов
 * - orderField($orderField) поле сортировки, по умолчанию 'weight'
 * - orderDirection($orderDirection) направление сортировки, по умолчанию 'DESC'
 * - log(TRUE|FALSE) активность журнала поисковых запросов. По умолчанию TRUE
 * - getSearchPageCallback($callback) callback-функция для обработки массива найденных страниц для метода getData()/getJson(), по умолчанию _getSearchPageCallback() текущего контроллера
 *
 * <code>
 * $oSite = Core_Entity::factory('Site', CURRENT_SITE);
 *
 * $Search_Controller_Show = new Search_Controller_Show($oSite);
 *
 * $Search_Controller_Show
 * 	->limit(Core_Page::instance()->libParams['result_on_page'])
 * 	->parseUrl()
 * 	->len(Core_Page::instance()->libParams['maxlen'])
 * 	->query(Core_Array::getGet('text'));
 *
 * $Search_Controller_Show
 * ->xsl(
 * 	Core_Entity::factory('Xsl')->getByName(Core_Page::instance()->libParams['xsl'])
 * )
 * ->show();
 * </code>
 *
 * Массив условий поиска по модулям позволяет ограничить область поиска по модулям и типам индексируемого контента. Ключами массива являются номера модулей, а значениями — массив идентификаторов элементов.
 * Номера модулей:
 *
 * - 0 – Структура сайта;
 * - 1 – Информационные системы;
 * - 2 – Форум;
 * - 3 – Интернет-магазин;
 * - 4 – HelpDesk.
 * - 5 – Пользователи сайта.
 * - 6 – Страницы и документы.
 * - 7 – XSL-шаблоны.
 * - 8 – Tpl-шаблоны.
 * - 9 – Типовые динамические страницы.
 * - 10 – Поиск по сайту.
 *
 * Пример поиска по информационной системе с номером 5 и 7, а также по магазину с номером 17.
 * <code>
 * $Search_Controller_Show->modules(
 *		array(
 *			1 => array (5, 7),
 *			3 => array (17)
 *		)
 *	);
 * </code>
 *
 * Пример поиска по информационной системе с номером 5 и 7 (с дополнительным условием поиска только по информационным элементам), а также по магазину с номером 17.
 * <code>
 * $Search_Controller_Show->modules(
 *		array(
 *		1 => array (5,
 *			 array('module_id' => 7, 'module_value_type' => 2)),
 *		3 => array (17))
 *	);
 * </code>
 *
 * При указании массива с дополнительными условиями он может принимать следующие аргументы:
 *
 * - module_id — целое число, ID сущности, например, магазин с кодом 7
 * - module_value_type — целое число или массив, ID типа, например, 1 - группа, 2 - элемент (или товар)
 * - module_value_id — целое число или массив, ID сущности указанного типа (например, ID товара или группы) при поиске только по ним.
 *
 * @package HostCMS
 * @subpackage Search
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Search_Controller_Show extends Core_Controller
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'query',
		'inner',
		'len',
		'offset',
		'limit',
		'page',
		'total',
		'modules',
		'structure',
		'pattern',
		'patternExpressions',
		'patternParams',
		'cache',
		'itemsForbiddenTags',
		'orderField',
		'orderDirection',
		'log',
		'url',
		'getSearchPageCallback'
	);

	/**
	 * Search config
	 * @var array
	 */
	protected $_config = NULL;

	/**
	 * Error code
	 * @var int|NULL
	 */
	protected $_errorCode = NULL;

	/**
	 * Constructor.
	 * @param Site_Model $oSite site
	 */
	public function __construct(Site_Model $oSite)
	{
		parent::__construct($oSite->clearEntities());

		$this->_setSearchPages();

		$this->len = 200;
		$this->offset = $this->page = 0;
		$this->inner = 0; // Поиск по умолчанию ведем по внешним страницам
		$this->modules = array(); // Ограничение поиска по модулям и сущностям
		$this->structure = Core_Page::instance()->structure;

		$this->cache = $this->log = TRUE;
		$this->itemsForbiddenTags = array();

		$this->_config = Core::$config->get('search_config', array()) + array(
			'modules' => array(
				0 => 'Structure',
				1 => 'Informationsystem',
				2 => 'Forum',
				3 => 'Shop',
				4 => 'Helpdesk',
				5 => 'Siteuser',
				6 => 'Document',
				7 => 'Xsl',
				8 => 'Tpl',
				9 => 'Lib',
				10 => 'Search',
			)
		);

		$this->orderField = 'weight';
		$this->orderDirection = 'DESC';

		// Named subpatterns {name} can consist of up to 32 alphanumeric characters and underscores, but must start with a non-digit.
		$this->structure
			&& $this->pattern = rawurldecode($this->structure->getPath()) . '({path})(page-{page}/)';

		$this->patternExpressions = array(
			'page' => '\d+',
		);

		$this->url = Core::$url['path'];

		$this->getSearchPageCallback = array($this, '_getSearchPageCallback');
	}

	/**
	 * Set search pages
	 * @return self
	 */
	protected function _setSearchPages()
	{
		$siteuser_id = 0;

		if (Core::moduleIsActive('siteuser'))
		{
			$oSiteuser = Core_Entity::factory('Siteuser')->getCurrent();

			$oSiteuser && $siteuser_id = $oSiteuser->id;
		}

		$this->addCacheSignature('siteuser_id=' . $siteuser_id);

		$this->addEntity(
			Core::factory('Core_Xml_Entity')
				->name('siteuser_id')
				->value($siteuser_id)
		);

		return $this;
	}

	/**
	 * Log search query
	 * @return self
	 */
	protected function _searchLog()
	{
		$oSite = $this->getEntity();

		// Search log
		$ip = Core::getClientIp();

		$hash = Core::crc32(
			strval($this->query)
		);
		$oSearch_Log = Core_Entity::factory('Search_Log')
			->getByHashAndIp($ip, $hash, date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59'));

		if (is_null($oSearch_Log))
		{
			$oSearch_Log = Core_Entity::factory('Search_Log');

			if (Core::moduleIsActive('siteuser'))
			{
				$oSiteuser = Core_Entity::factory('Siteuser')->getCurrent();
				if ($oSiteuser)
				{
					$oSearch_Log->siteuser_id = $oSiteuser->id;
				}
			}

			$oSearch_Log->query = strval($this->query);
			$oSearch_Log->ip = $ip;
			$oSearch_Log->hash = $hash;

			$oSearch_Log->save();
			$oSite->add($oSearch_Log);
		}

		return $this;
	}

	/**
	 * Get JSON
	 * @return string
	 */
	public function getJson()
	{
		return json_encode(
			$this->getData()
		);
	}

	/**
	 * Typical conversion of Search_Pages to an array
	 * @param array $aSearch_Pages
	 * @return array
	 */
	protected function _getSearchPageCallback(array $aSearch_Pages)
	{
		$aReturn = array();

		foreach ($aSearch_Pages as $oSearch_Page)
		{
			if (isset($this->_config['modules'][$oSearch_Page->module]))
			{
				$oCore_Module = Core_Module_Abstract::factory($this->_config['modules'][$oSearch_Page->module]);

				if ($oCore_Module && method_exists($oCore_Module, 'searchCallback'))
				{
					$oCore_Module->searchCallback($oSearch_Page);
				}
			}

			$aReturn[] = $oSearch_Page->toArray();
		}

		return $aReturn;
	}

	/**
	 * Prepare date
	 * @return array
	 */
	public function getData()
	{
		$this->total = 0;

		if (!is_null($this->query) && !is_scalar($this->query))
		{
			$this->_errorCode = 404;
		}
		else
		{
			$query = trim((string) $this->query);

			$this->len
				&& $query = mb_substr($query, 0, $this->len);

			$this->log
				&& $query != ''
				&& $this->_searchLog();

			$found = $this->_find($query);

			if ($this->page && $found == 0)
			{
				$this->_errorCode = 410;
			}
		}

		if ($this->_errorCode)
		{
			$aReturn = array(
				'error' => array(
					'code' => $this->_errorCode
				)
			);

			switch ($this->_errorCode)
			{
				case 403:
					$aReturn['error']['message'] = "Access forbidden, url '{$this->url}'";
				break;
				case 410:
				case 404:
					$aReturn['error']['message'] = "Path '{$this->url}' Not Found";
				break;
				default:
					$aReturn['error']['message'] = "Unknown Error";
				break;
			}

			return $aReturn;
		}

		$url = $this->structure
			? $this->structure->getPath()
			: '';

		$aSearch_Pages = $this->getSearchPages($query);

		$aReturn = array(
			'pagination' => array(
				'total' => intval($this->total),
				'pages' => $this->limit > 0 ? ceil($this->total / $this->limit) : 0,
				'current' => intval($this->page) + 1,
				'limit' => $this->limit
			),
			'url' => $url,
			'query' => $query,
			'queryencode' => rawurlencode(strtoupper(SITE_CODING) != 'UTF-8'
					? @iconv('UTF-8', SITE_CODING . "//IGNORE//TRANSLIT", $query)
					: $query
				),
			'items' => call_user_func($this->getSearchPageCallback, $aSearch_Pages)
		);

		return $aReturn;
	}

	/**
	 * Show built data
	 * @return self
	 * @hostcms-event Search_Controller_Show.onBeforeRedeclaredShow
	 */
	public function show()
	{
		Core_Event::notify(get_class($this) . '.onBeforeRedeclaredShow', $this);

		if (!is_null($this->query) && !is_scalar($this->query))
		{
			return $this->error404();
		}

		$bCache = $this->cache && Core::moduleIsActive('cache');
		if ($bCache)
		{
			$oCore_Cache = Core_Cache::instance(Core::$mainConfig['defaultCache']);
			$inCache = $oCore_Cache->get($cacheKey = strval($this), $cacheName = 'search');

			if (is_array($inCache))
			{
				$this->_shownIDs = $inCache['shown'];
				echo $inCache['content'];
				return $this;
			}
		}

		$this->total = 0;

		$query = trim((string) $this->query);

		$this->len
			&& $query = mb_substr($query, 0, $this->len);

		$this->log
			&& $query != ''
			&& $this->_searchLog();

		$url = $this->structure
			? $this->structure->getPath()
			: '';

		$this->addEntity(
			Core::factory('Core_Xml_Entity')
				->name('url')
				->value($url)
		)->addEntity(
			Core::factory('Core_Xml_Entity')
				->name('page')
				->value(intval($this->page))
		)->addEntity(
			Core::factory('Core_Xml_Entity')
				->name('limit')
				->value(intval($this->limit))
		)->addEntity(
			Core::factory('Core_Xml_Entity')
				->name('query')
				->value($query)
		)->addEntity(
			Core::factory('Core_Xml_Entity')
				->name('queryencode')
				// Перед преобразованием rawurlencode запрос нужно привести к клиентской кодировке
				->value(rawurlencode(strtoupper(SITE_CODING) != 'UTF-8'
					? @iconv('UTF-8', SITE_CODING . "//IGNORE//TRANSLIT", $query)
					: $query
				))
		);

		$found = $this->_find($query);

		if ($this->page && $found == 0)
		{
			return $this->error410();
		}

		$this->addEntity(
			Core::factory('Core_Xml_Entity')
				->name('total')
				->value(intval($this->total))
		);

		echo $content = $this->get();

		$bCache && $oCore_Cache->set(
			$cacheKey,
			array('content' => $content, 'shown' => $this->_shownIDs),
			$cacheName
		);

		return $this;
	}

	/**
	 * Find
	 * @param string $query query
	 * @return int The number of items currently matched
	 */
	protected function _find($query)
	{
		$oSite = $this->getEntity();

		$bTpl = $this->_mode == 'tpl';

		$this->_shownIDs = array();

		$aSearch_Pages = $this->getSearchPages($query);

		if ($bTpl)
		{
			$this->assign('controller', $this);
			$this->assign('aSearch_Pages', $aSearch_Pages);
		}

		$bCount = count($this->itemsForbiddenTags);

		foreach ($aSearch_Pages as $oSearch_Page)
		{
			if (!$bTpl)
			{
				if (isset($this->_config['modules'][$oSearch_Page->module]))
				{
					$oCore_Module = Core_Module_Abstract::factory($this->_config['modules'][$oSearch_Page->module]);

					if ($oCore_Module && method_exists($oCore_Module, 'searchCallback'))
					{
						$bCount && $oSearch_Page->itemsForbiddenTags($this->itemsForbiddenTags);

						$oCore_Module->searchCallback($oSearch_Page);
					}
				}

				$this->addEntity($oSearch_Page);
			}

			$this->_shownIDs[] = $oSearch_Page->id;
		}

		return count($aSearch_Pages);
	}

	/**
	 * Get Search_Pages
	 * @return array
	 */
	public function getSearchPages($query)
	{
		$oSite = $this->getEntity();
		
		$Search_Controller = Search_Controller::instance();
		$Search_Controller
			->orderField($this->orderField)
			->orderDirection($this->orderDirection)
			->site($oSite)
			->modules($this->modules)
			->offset($this->offset)
			->page($this->page)
			->limit($this->limit)
			->inner($this->inner);

		$aSearch_Pages = $Search_Controller->find($query);

		$this->total = $Search_Controller->total;

		return $aSearch_Pages;
	}

	/**
	 * Parse URL and set controller properties
	 * @return Search_Controller_Show
	 * @hostcms-event Search_Controller_Show.onBeforeParseUrl
	 * @hostcms-event Search_Controller_Show.onAfterParseUrl
	 */
	public function parseUrl()
	{
		Core_Event::notify(get_class($this) . '.onBeforeParseUrl', $this);

		$Core_Router_Route = new Core_Router_Route($this->pattern, $this->patternExpressions);
		$this->patternParams = $matches = $Core_Router_Route->applyPattern($this->url);

		if (!isset($matches['path']) || strlen($matches['path']))
		{
			return $this->error404();
		}

		if (isset($matches['page']) && $matches['page'] > 1)
		{
			$this->page($matches['page'] - 1);
			$this->offset($this->limit * $this->page);
		}

		Core_Event::notify(get_class($this) . '.onAfterParseUrl', $this);

		return $this;
	}

/**
	 * Define handler for 410 error
	 * @return self
	 */
	public function error410()
	{
		$this->_errorCode = 410;

		!is_null(Core_Page::instance()->response)
			&& Core_Page::instance()->error410();

		return $this;
	}

	/**
	 * Define handler for 404 error
	 * @return self
	 */
	public function error404()
	{
		$this->_errorCode = 404;

		!is_null(Core_Page::instance()->response)
			&& Core_Page::instance()->error404();

		return $this;
	}
}
