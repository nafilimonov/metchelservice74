<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Counters.
 *
 * @package HostCMS
 * @subpackage Counter
 * @version 7.x
 * @copyright © 2005-2025 ООО "Хостмэйк"(Hostmake LLC), http://www.hostcms.ru
 */
class Counter_Controller extends Core_Servant_Properties
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'site',
		'referrer',
		'page',
		'cookies',
		'java',
		'colorDepth',
		'display',
		'js',
		'counterId',
		'updateCounter',
		'ip',
		'userAgent',
		'bBot',
		'bNewIp',
		'bNewUser',
		'bNewSession',
		'sessionId',
		'siteuserId',
		'cleaningFrequency',
		'sessionLifeTime',
	);

	/**
	 * Config value
	 * @var array
	 */
	protected $_config = array();

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->updateCounter = 1;
		$this->siteuserId = 0;
		$this->cleaningFrequency = defined('STAT_CLEARING_FREQUENCY') ? STAT_CLEARING_FREQUENCY : 1000;
		// Create new sessionId
		$this->bNewSession = FALSE;

		// Время, в течении которого сессия считается активной с момента последнего посещения, 7200 - 2 часа
		$this->sessionLifeTime = 7200;

		$this->_config = (array)Core::$config->get('counter_config', array()) + array(
			'gethostbyaddr' => FALSE,
			'counters' => array(
				0 => array(
					'color_red' => 0xff,
					'color_green' => 0xff,
					'color_blue' => 0xff,
					'show' => 0,
					'name' => 'Invisible',
					'image_name' => '0.gif',
					'x' => 1,
					'y' => 1
				)
			)
		);
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
	* Отображение кода счетчика
	*
	* @param int $counterId тип выводимого счетчика (файл счетчика должен быть расположен в директории /counter/ и представлять собой изображение с именем {НОМЕР}.gif)
	* @param string $alias_name наименование домена сайта, например www.site.ru
	* @param string $get_update_counter переменная с информацией о необходимости обновления счетчика, не обязательный параметр, по умолчанию равен пустоте
	* @return Counter_Controller
	*
	* <code>
	* <?php
	* ob_start();
	* Counter_Controller::instance()->showCounterCode(1, Core_Entity::factory('Site', CURRENT_SITE)->getCurrentAlias()->name);
	* $sCode = ob_get_clean();
	* ?>
	* </code>
	*/
	public function showCounterCode($counterId, $alias_name, $get_update_counter = '')
	{
		if (isset($this->_config['counters'][$counterId]) && is_array($this->_config['counters'][$counterId]))
		{
			?><!--HostCMS counter--><a href="https://www.hostcms.ru" target="_blank"><img id="hcntr<?php echo $counterId?>" width="<?php echo $this->_config['counters'][$counterId]['x']?>" height="<?php echo $this->_config['counters'][$counterId]['y']?>" style="border:0" title="HostCMS Counter" alt="" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"/></a><?php
			Core_Html_Entity::factory('Script')
				->value('(function(h,c,m,s){h.getElementById("hcntr' . $counterId .'").src="//' . $alias_name . '/counter/counter.php?r="+Math.random()+"&id=' . CURRENT_SITE . '&refer="+escape(h.referrer)+"&current_page="+escape(s.location.href)+"&cookie="+(h.cookie?"Y":"N")+"&java="+(m.javaEnabled()?"Y":"N")+"&screen="+c.width+\'x\'+c.height+"&counter=' . $counterId . $get_update_counter . '"})(document,screen,navigator,window)')
				->execute();

			Core_Html_Entity::factory('Noscript')
				->add(
					Core_Html_Entity::factory('A')
					->href('https://www.hostcms.ru')
					->add(Core_Html_Entity::factory('Img')
						->src("//{$alias_name}/counter/counter.php?id=" . CURRENT_SITE . "&counter={$counterId}{$get_update_counter}")
						->alt('HostCMS Counter')
						->width($this->_config['counters'][$counterId]['x'])
						->height($this->_config['counters'][$counterId]['y']))
				)
				->execute();
			?><!--/HostCMS--><?php
		}

		return $this;
	}

	static public function getPrimaryKeyByDate($date)
	{
		if ($date != '0000-00-00')
		{
			$timestamp = Core_Date::sql2timestamp($date);
			return date('Y', $timestamp) * 1000 + date('m', $timestamp) * 50 + date('d', $timestamp);
		}

		return 0;
	}

	/**
	 * Обновление расширенными данными по счетчику
	 * @return self
	 */
	public function updateData()
	{
		// Проверяем наличие записи о текущей сессии в куках пользователя
		$iSessionId = Core_Array::getCookie('_hc_session', 0, 'int');
		if ($iSessionId)
		{
			$oCounter_Session = Core_Entity::factory('Counter_Session')->find($iSessionId);

			// Передана сессия и с момента последней активности не прошло $this->sessionLifeTime
			if (!is_null($oCounter_Session->id)
				&& Core_Date::sql2timestamp($oCounter_Session->last_active) > time() - $this->sessionLifeTime)
			{
				if (!$oCounter_Session->counter_display_id && $this->display != '')
				{
					$date = date('Y-m-d');

					$oCounter_Session->counter_display_id = $this->update('counter_displays', array(
						'site_id' => $this->site->id,
						'date' => $date,
						'display' => mb_substr($this->display, 0, 11),
						'count' => 1
						));
				}

				if ($oCounter_Session->tag == '' && isset($_COOKIE['_h_tag']) && strlen($_COOKIE['_h_tag']) == 22)
				{
					$oCounter_Session->tag = $_COOKIE['_h_tag'];
				}

				$oCounter_Session->save();
			}

			// Проверяем наличие записи о текущей сессии в куках пользователя. Изначально сессия создается через index.php и затем уточняется данными из счетчика
			$iVisitId = Core_Array::getCookie('_hc_v', 0, 'int');
			if ($iVisitId)
			{
				$oCounter_Visit = Core_Entity::factory('Counter_Visit')->find($iVisitId);

				if (!is_null($oCounter_Visit->id) && $oCounter_Visit->ip == Core_Str::ip2hex($this->ip)
					&& !$oCounter_Visit->siteuser_id
				)
				{
					if (Core::moduleIsActive('siteuser'))
					{
						$oSiteuser = Core_Entity::factory('Siteuser')->getCurrent();
						$this->siteuserId = !is_null($oSiteuser) ? $oSiteuser->id : 0;
					}

					if ($this->siteuserId)
					{
						$oCounter_Visit->siteuser_id = $this->siteuserId;
						$oCounter_Visit->save();
					}
				}

				// Удаляем метку визита
				Core_Cookie::set('_hc_v', 0, array('expires' => time() - 86400, 'path' => '/'));
			}
		}

		// Периодически очищаем таблицы с подробными данными статистики
		if (rand(0, $this->cleaningFrequency / 4) == 0)
		{
			$this->_clearOldData($this->site->id);
		}

		return $this;
	}

	/**
	 * Сохранение подробных данных о посещении
	 * @return self
	 */
	public function applyData()
	{
		Core_Session::close();

		$site_id = intval($this->site->id);

		$sCurrentDate = date('Y-m-d');

		// Проверка бот или не бот
		$this->bBot = Core::checkBot($this->userAgent);

		if ($this->updateCounter == 1
			&& (!Core::moduleIsActive('ipaddress') || !Ipaddress_Controller::instance()->isNoStatistic($this->ip))
		)
		{
			$hexIp = Core_Str::ip2hex($this->ip);

			// Проверяем является ли данный ip уникальным для обновления данных счетчика
			$this->bNewIp = $this->bBot
				? FALSE
				: $this->ipIsNew($hexIp, $sCurrentDate, $site_id);

			// Проверяем наличие записи о текущей сессии в куках пользователя
			$iSessionId = Core_Array::getCookie('_hc_session', 0, 'int');
			if ($iSessionId)
			{
				$oCounter_Session = Core_Entity::factory('Counter_Session')->find($iSessionId);

				// Передана сессия и с момента последней активности не прошло $this->sessionLifeTime
				if (!is_null($oCounter_Session->id)
					&& Core_Date::sql2timestamp($oCounter_Session->last_active) > time() - $this->sessionLifeTime)
				{
					// есть текущая сессия
					$this->sessionId = $iSessionId;

					$oCounter_Session->last_active = Core_Date::timestamp2sql(time());
					$oCounter_Session->save();
				}
			}

			// Вставка новой сессии
			!$this->sessionId
				&& $this->sessionId = $this->insertSession();

			// Обновляем время сесии в куках
			Core_Cookie::set('_hc_session', $this->sessionId, array('expires' => time() + $this->sessionLifeTime, 'path' => '/'));

			// Проверяем наличие id-пользователя в куках для определения нового пользователя
			// Учитываются данные за 7 дней
			if (Core_Array::getCookie('_hc_nu'))
			{
				$this->bNewUser = FALSE;
			}
			else
			{
				// Проверяем является ли ip-адрес пользователя уникальным за текущий день
				// ip-адрес уникальный - пользователь новый
				$this->bNewUser = $this->bNewIp;
			}

			// 7-дневная метка нового пользователя
			/*!$this->bBot && */Core_Cookie::set('_hc_nu', 1, array('expires' => time() + 604800, 'path' => '/'));

			// Определение поисковик это или нет, получение поискового запроса, поисковой системы
			$aSearchSystem = self::isSearchSystem($this->referrer);

			if (is_array($aSearchSystem))
			{
				// Поисковая система
				$searchsystem = $aSearchSystem['search_system'];

				// Поисковая фраза
				$searchquery = $aSearchSystem['search_query'];
			}
			else
			{
				$searchquery = $searchsystem = NULL;
			}

			// Удалять Emoji
			$bRemoveEmoji = strtolower(Core_Array::get(Core_DataBase::instance()->getConfig(), 'charset')) != 'utf8mb4';

			$oCounter_Visit = Core_Entity::factory('Counter_Visit');
			$oCounter_Visit->site_id = $this->site->id;
			$oCounter_Visit->counter_session_id = $this->sessionId;
			$oCounter_Visit->lng = strtolower(substr(Core_Array::get($_SERVER, 'HTTP_ACCEPT_LANGUAGE', '', 'str'), 0, 2));

			if ($this->referrer == '')
			{
				$oCounter_Visit->counter_referrer_id = 0;
			}
			else
			{
				$inner = 0;

				$aSite_Aliases = $this->site->Site_Aliases->findAll();
				foreach ($aSite_Aliases as $oSite_Alias)
				{
					$sAlias = preg_quote($oSite_Alias->name, '#');
					$sAlias = str_replace('\*\.', '(?:[a-zA-Z0-9\.\-]*\.)?', $sAlias);
					if (preg_match('#^https?://' . $sAlias . '/#', $this->referrer, $matches))
					{
						$inner = 1;
						break;
					}
				}

				$oCounter_Visit->counter_referrer_id = $this->update('counter_referrers', array(
					'site_id' => $this->site->id,
					'date' => $sCurrentDate,
					'referrer' => $bRemoveEmoji ? Core_Str::removeEmoji($this->referrer) : $this->referrer,
					'inner' => $inner,
					'count' => !$this->bBot ? 1 : 0
				));
			}

			$oCounter_Visit->counter_page_id = $this->update('counter_pages', array(
				'site_id' => $this->site->id,
				'date' => $sCurrentDate,
				'page' => $bRemoveEmoji ? Core_Str::removeEmoji($this->page) : $this->page,
				'count' => !$this->bBot ? 1 : 0
			));

			$oCounter_Visit->counter_searchquery_id = is_null($searchsystem)
				? 0
				: $this->update('counter_searchqueries', array(
					'site_id' => $this->site->id,
					'date' => $sCurrentDate,
					'searchquery' => mb_substr($bRemoveEmoji ? Core_Str::removeEmoji($searchquery) : $searchquery, 0, 255),
					'searchsystem' => $searchsystem,
					'count' => !$this->bBot ? 1 : 0
				));

			$oCounter_Visit->new_user = intval($this->bNewUser);
			$oCounter_Visit->ip = $hexIp;
			$oCounter_Visit->host = $this->_config['gethostbyaddr']
				? @gethostbyaddr($this->ip)
				: NULL;
			// Здесь 0, значение устанавливается когда данные от счетчика поступают
			$oCounter_Visit->siteuser_id = $this->siteuserId;
			$oCounter_Visit->datetime = date('Y-m-d H:i:s');
			$oCounter_Visit->save();

			// Устанавливаем метку сессии для передачи доп. данным через counter.php
			//$aPageParse = @parse_url($this->page);
			//$pagePath = Core_Array::get($aPageParse, 'path', '/');
			Core_Cookie::set('_hc_v', $oCounter_Visit->id, array('expires' => time() + 300, 'path' => '/'));
		}

		// Периодически очищаем таблицы с подробными данными статистики
		if (rand(0, $this->cleaningFrequency / 4) == 0)
		{
			$this->_clearOldData($this->site->id);
		}

		return $this;
	}

	/**
	 * Сохранение обобщенных данных о посещении
	 * @return self
	 * @hostcms-event Counter_Controller.onAfterUpdateCounter
	 */
	public function applySummary()
	{
		$site_id = intval($this->site->id);

		$sCurrentDate = date('Y-m-d');

		$iPrimaryKey = self::getPrimaryKeyByDate($sCurrentDate) . $site_id;

		if (!$this->bBot)
		{
			$sUpdate = "`hits` = `hits` + 1, `hosts` = `hosts` + " . intval($this->bNewIp) . ", " .
				"`sessions` = `sessions` + " . intval($this->bNewSession) . ", " .
				"`new_users` = `new_users` + " . intval($this->bNewUser);

			$sValues = "('{$iPrimaryKey}', '{$site_id}', '{$sCurrentDate}', 1, " . intval($this->bNewIp) . ", " . intval($this->bNewSession) . ", 0, " . intval($this->bNewUser) . ", 0)";
		}
		else
		{
			$sUpdate = "`bots` = `bots` + 1";
			$sValues = "('{$iPrimaryKey}', '{$site_id}', '{$sCurrentDate}', 0, 0, 0, 1, 0, 0)";
		}

		$oCore_Database = Core_DataBase::instance();
		$oCore_Database
			->setQueryType(2)
			->query("UPDATE `counters` SET {$sUpdate} WHERE `id` = {$iPrimaryKey}");

		$iAffectedRows = $oCore_Database->getAffectedRows();

		if ($iAffectedRows == 0)
		{
			$oCore_Database
				->setQueryType(2)
				->query("INSERT INTO `counters` (`id`, `site_id`, `date`, `hits`, `hosts`, `sessions`, `bots`, `new_users`, `sent`) " .
					"VALUES {$sValues} " .
					"ON DUPLICATE KEY UPDATE {$sUpdate}");
		}

		Core_Event::notify(get_class($this) . '.onAfterUpdateCounter', $this);

		return $this;
	}

	/**
	 * Построение счетчика
	 * @return self
	 */
	public function buildCounter()
	{
		$site_id = intval($this->site->id);

		$sCurrentDate = date('Y-m-d');

		/* Получаем данные для сайта за указанный день*/
		$oCounter = $this->getDayInformation($sCurrentDate);

		/*
		 * Данные за предыдущий день не были отправлены, при этом данные отправляются до 5 утра максимум.
		 * Время отправки определяется как остаток деления ID сайта на 5
		 */
		if (!is_null($oCounter) && !$oCounter->sent && date('i') >= $site_id % 5)
		{
			$oCounter->sent = 1;
			$oCounter->save();

			if ($this->site->send_attendance_report)
			{
				Core_Log::instance()->clear()
					->notify(FALSE)
					->status(Core_Log::$MESSAGE)
					->write(sprintf('Counter: Daily report sent, site "%d"', $site_id));

				$this->mailReport(date('Y-m-d', strtotime('-1 day')));
			}
		}

		// Если не бот, выводим счетчик
		if (!$this->bBot)
		{
			$aCounterConfig = Core_Array::get($this->_config['counters'], $this->counterId, array()) + array(
				'show' => 1, 'image_name' => '', 'color_red' => 0, 'color_blue' => 0, 'color_green' => 0,
			);

			// выводим счетчик
			if (!is_null($oCounter))
			{
				// Хиты
				$str = (string) $oCounter->hits;
				$i1 = strlen($str);

				// Сессии
				$str2 = (string) $oCounter->sessions;
				$i2 = strlen($str2);

				/* Всего сессий для сайта*/
				$str3 = $this->getAllSession($site_id);
				$i3 = strlen($str3);

				$file_name = CMS_FOLDER . 'counter/' . $aCounterConfig['image_name'];

				// Проверяем наличие файла с изображением для счетчика
				if (!Core_File::isFile($file_name))
				{
					throw new Core_Exception('File %file does not exist', array(
						'%file' => $aCounterConfig['image_name']
					));
				}

				$im = imagecreatefromgif($file_name);

				// Цвет текста
				$red = Core_Array::get($aCounterConfig, 'color_red');
				$blue = Core_Array::get($aCounterConfig, 'color_blue');
				$green = Core_Array::get($aCounterConfig, 'color_green');
				$color_text = imagecolorallocate($im, $red, $blue, $green);

				if ($aCounterConfig['show'] == 1)
				{
					$padding = 5;
					$y1 = (88 - $padding) - $i1 * 5;
					$y2 = (88 - $padding) - $i2 * 5;
					$y3 = (88 - $padding) - $i3 * 5;
					imagestring($im, 1, $y2, 2, $str2, $color_text); // вывод сессий
					imagestring($im, 1, $y1, 11, $str, $color_text); // вывод хитов
					imagestring($im, 1, $y3, 20, $str3, $color_text); // всего
				}

				header("Content-type: image/gif");
				imagegif ($im);
				imagedestroy($im);
			}
			else // Не выбрано ни одной записи о данных счетчика за текущий день
			{
				// Обновление не производилось
				if ($this->updateCounter == 0)
				{
					/* Вызываем этот же метод только с обновлением данных для счетчика (если метод вызван из админки, а за текущий день не было ни одного посетителя)*/
					$this
						->updateCounter(1)
						->applyData()
						->applySummary()
						->buildCounter();
				}
			}
		}

		return $this;
	}

	/**
	 * Clear Old Data
	 * @param int $site_id
	 * @return self
	 */
	protected function _clearOldData($site_id)
	{
		$site_id = intval($site_id);

		$period_storage = defined('STAT_PERIOD_STORAGE') ? STAT_PERIOD_STORAGE : 30;

		// Получаем дату, начиная с которой необходимо хранить статистику (тек. дата-кол-во дней хранения в сек.)
		$cleaningDate = date('Y-m-d', strtotime("-{$period_storage} day"));

		$iLimit = intval($this->cleaningFrequency);

		$oCore_Database = Core_DataBase::instance();

		// counter_sessions
		$oCore_Database->setQueryType(3)
			->query("DELETE LOW_PRIORITY QUICK FROM `counter_sessions` WHERE `site_id` = '{$site_id}' AND `last_active` < '{$cleaningDate} 00:00:00' LIMIT {$iLimit}");

		// counter_pages
		$oCore_Database->setQueryType(3)
			->query("DELETE LOW_PRIORITY QUICK FROM `counter_pages` WHERE `site_id` = '{$site_id}' AND `date` < '{$cleaningDate}' LIMIT {$iLimit}");

		// counter_browsers
		$oCore_Database->setQueryType(3)
			->query("DELETE LOW_PRIORITY QUICK FROM `counter_browsers` WHERE `site_id` = '{$site_id}' AND `date` < '{$cleaningDate}' LIMIT {$iLimit}");

		// counter_devices
		$oCore_Database->setQueryType(3)
			->query("DELETE LOW_PRIORITY QUICK FROM `counter_devices` WHERE `site_id` = '{$site_id}' AND `date` < '{$cleaningDate}' LIMIT {$iLimit}");

		// counter_displays
		$oCore_Database->setQueryType(3)
			->query("DELETE LOW_PRIORITY QUICK FROM `counter_displays` WHERE `site_id` = '{$site_id}' AND `date` < '{$cleaningDate}' LIMIT {$iLimit}");

		// counter_oses
		$oCore_Database->setQueryType(3)
			->query("DELETE LOW_PRIORITY QUICK FROM `counter_oses` WHERE `site_id` = '{$site_id}' AND `date` < '{$cleaningDate}' LIMIT {$iLimit}");

		// counter_referrers
		$oCore_Database->setQueryType(3)
			->query("DELETE LOW_PRIORITY QUICK FROM `counter_referrers` WHERE `site_id` = '{$site_id}' AND `date` < '{$cleaningDate}' LIMIT {$iLimit}");

		// counter_searchqueries
		$oCore_Database->setQueryType(3)
			->query("DELETE LOW_PRIORITY QUICK FROM `counter_searchqueries` WHERE `site_id` = '{$site_id}' AND `date` < '{$cleaningDate}' LIMIT {$iLimit}");

		// counter_useragents
		$oCore_Database->setQueryType(3)
			->query("DELETE LOW_PRIORITY QUICK FROM `counter_useragents` WHERE `site_id` = '{$site_id}' AND `date` < '{$cleaningDate}' LIMIT {$iLimit}");

		// counter_visits
		$oCore_Database->setQueryType(3)
			->query("DELETE LOW_PRIORITY QUICK FROM `counter_visits` WHERE `site_id` = '{$site_id}' AND `datetime` < '{$cleaningDate} 00:00:00' LIMIT {$iLimit}");

		return $this;
	}

	/**
	* Определение наличия записи за сегодняшний день для переданного IP адреса для переданного сайта
	*
	* @param string $hexIp упакованный IP-адрес
	* @param string $sCurrentDate текущая дата
	* @param int $site_id идентификатор сайта
	* @return int 1 - новый, 0 - не новый
	* <code>
	* <?php
	* $hexIp = 'c0a80007';
	* $sCurrentDate = date('Y-m-d');
	* $site_id = 1;
	*
	* $result = Counter_Controller::instance()->ipIsNew($hexIp, $sCurrentDate, $site_id);
	*
	* // Распечатаем результат
	* echo $result;
	* ?>
	* </code>
	*/
	public function ipIsNew($hexIp, $sCurrentDate, $site_id)
	{
		$oCounter_Visit = Core_Entity::factory('Counter_Visit');
		$oCounter_Visit->queryBuilder()
			->clear()
			->where('site_id', '=', intval($site_id))
			->where('datetime', '>', $sCurrentDate . ' 00:00:00')
			->where('ip', '=', $hexIp)
			->limit(1);
		$oCounter_Visit = $oCounter_Visit->find();

		return intval(is_null($oCounter_Visit->id));
	}

	/**
	* Проверка user-agent на принадлежность к ботам
	*
	* @param string $agent user-agent
	* @return bool true, если это бот, false в противном случае
	*/
	static public function checkBot($agent)
	{
		return Core::checkBot($agent);
	}

	/**
	* Определение запроса из поисковой системы. Метод работает с поисковыми системами:
	* - Yandex
	* - Google
	* - Mail.Ru
	* - Yahoo.com
	* - Yahoo.com
	* - Metabot
	* - bing.com
	*
	* @param string $str адрес ссылающейся страницы
	* @return mixed поисковый запрос или FALSE
	* <code>
	* <?php
	* $str = 'https://yandex.ru/yandsearch?clid=13999&yasoft=barff&text=cms';
	*
	* $aSearchSystem = Counter_Controller::instance()->isSearchSystem($str);
	*
	* // Распечатаем результат
	* var_dump($aSearchSystem);
	* ?>
	* </code>
	*/
	static public function isSearchSystem($str)
	{
		if (!is_string($str))
		{
			return FALSE;
		}

		$uri = @parse_url($str);

		// Хост поисковой системы
		$host = Core_Array::get($uri, 'host', '', 'str');

		isset($uri['query'])
			? mb_parse_str($uri['query'], $query)
			: $query = array();

		$return = FALSE;

		if (preg_match("/ya.ru/", $host) || preg_match("/yandex.ru/", $host) || preg_match("/yandex.ua/", $host) || preg_match("/yandex.com/", $host)
		)
		{
			$return = array(
				'search_system' => $host,
				'search_query' => Core_Array::get($query, 'text', '', 'str')
			);
		}
		elseif (preg_match("/rambler.ru/", $host))
		{
			$return = array(
				'search_system' => $host,
				'search_query' => Core_Array::get($query, 'query', '', 'str')
			);
		}
		elseif (preg_match('/^www\.google\./u',$host)
			|| preg_match("/go.mail.ru/", $host)
			|| preg_match("/bing.com/", $host)
			|| preg_match("/metabot.ru/", $host)
		)
		{
			$return = array(
				'search_system' => $host,
				'search_query' => Core_Array::get($query, 'q', '', 'str')
			);
		}
		elseif (preg_match("/search.yahoo.com/", $host))
		{
			$return = array(
				'search_system' => $host,
				'search_query' => Core_Array::get($query, 'p', '', 'str')
			);
		}

		return $return;
	}

	/**
	 * Get OS name
	 * @param string $userAgent User agent
	 * @return string
	 */
	static public function getOs($userAgent)
	{
		return Core_Browser::getOs($userAgent);
	}

	/**
	 * Get browser name
	 * @param string $userAgent User agent
	 * @return string
	 */
	static public function getBrowser($userAgent)
	{
		return Core_Browser::getBrowser($userAgent);
	}

	/**
	 * Get device type by User Agent
	 * @param string $userAgent
	 * @return int 0 - desktop, 1 - tablet, 2 - phone, 3 - tv, 4 - watch
	 */
	static public function getDevice($userAgent)
	{
		return Core_Browser::getDevice($userAgent);
	}

	/**
	 * Get correspond Counter_Session or insert new one
	 * @return int sessionId
	 */
	public function insertSession()
	{
		$date = date('Y-m-d');

		$oCounter_Session = Core_Entity::factory('Counter_Session');
		$oCounter_Session->counter_useragent_id = $this->update('counter_useragents', array(
			'site_id' => $this->site->id,
			'date' => $date,
			'useragent' => mb_substr(Core_Str::removeEmoji($this->userAgent), 0, 255),
			'crawler' => intval($this->bBot),
			'count' => 1
		));

		$oCounter_Session->counter_os_id = !$this->bBot
			? $this->update('counter_oses', array(
				'site_id' => $this->site->id,
				'date' => $date,
				'os' => Core_Browser::getOs($this->userAgent),
				'count' => 1
			))
			: 0;

		$oCounter_Session->counter_browser_id = !$this->bBot
			? $this->update('counter_browsers', array(
				'site_id' => $this->site->id,
				'date' => $date,
				'browser' => Core_Browser::getBrowser($this->userAgent),
				'count' => 1
			))
			: 0;

		$oCounter_Session->counter_device_id = !$this->bBot
			? $this->update('counter_devices', array(
				'site_id' => $this->site->id,
				'date' => $date,
				'device' => Core_Browser::getDevice($this->userAgent),
				'count' => 1
			))
			: 0;

		$oCounter_Session->site_id = $this->site->id;
		$oCounter_Session->last_active = Core_Date::timestamp2sql(time());
		$oCounter_Session->counter_display_id = 0;
		$oCounter_Session->tag = isset($_COOKIE['_h_tag']) && strlen($_COOKIE['_h_tag']) == 22
			? $_COOKIE['_h_tag']
			: '';
		$oCounter_Session->save();

		// Флаг добавления новой сессии - истина
		$this->bNewSession = TRUE;

		return $oCounter_Session->id;
	}

	/**
	 * Update row. If row does not exit, call insertOnUpdate()
	 * @param string $tableName
	 * @param array $aValues
	 */
	public function update($tableName, array $aValues)
	{
		$iPrimaryKey = Core::crc32(implode('#', $aValues));

		$iCount = Core_Array::get($aValues, 'count', 1, 'int');

		$oCore_Database = Core_DataBase::instance();
		$quotedTableName = $oCore_Database->quoteColumnName($tableName);
		$sQuery = "UPDATE {$quotedTableName} SET `count` = `count` + {$iCount} WHERE `id` = {$iPrimaryKey}";

		$oCore_Database
			->setQueryType(2)
			->query($sQuery);

		$iAffectedRows = $oCore_Database->getAffectedRows();

		return $iAffectedRows == 0
			? $this->insertOnUpdate($tableName, $aValues)
			: $iPrimaryKey;
	}

	public function insertOnUpdate($tableName, array $aValues)
	{
		$iPrimaryKey = Core::crc32(implode('#', $aValues));

		$iCount = Core_Array::get($aValues, 'count', 1, 'int');

		$oCore_Database = Core_DataBase::instance();

		// Quote VALUES
		$aValues = array_map(array($oCore_Database, 'quote'), $aValues);

		// Quote Table Name
		$tableName = $oCore_Database->quoteColumnName($tableName);
		// Quote COLUMNS NAME
		$aTmpKeys = array_map(array($oCore_Database, 'quoteColumnName'), array_keys($aValues));

		/*$sQuery = "INSERT INTO {$tableName} (`id`, " . implode(', ', $aTmpKeys) . ", `count`) " .
			"VALUES ('{$iPrimaryKey}', " . implode(', ', $aValues) . ", 1) ON DUPLICATE KEY UPDATE `count` = `count` + 1";*/

		$sQuery = "INSERT INTO {$tableName} (`id`, " . implode(', ', $aTmpKeys) . ") " .
			"VALUES ('{$iPrimaryKey}', " . implode(', ', $aValues) . ") ON DUPLICATE KEY UPDATE `count` = `count` + {$iCount}";

		$oCore_Database
			->setQueryType(2)
			->query($sQuery);

		return $iPrimaryKey;
	}

	/**
	* Определение числа сессий для сайта за весь период подсчета статистики.
	*
	* @param int $site_id идентификатор сайта
	* @return int число сессий для сайта
	* <code>
	* <?php
	* $site_id = CURRENT_SITE;
	* $result = Counter_Controller::instance()->getAllSession($site_id);
	* // Распечатаем результат
	* echo $result;
	* ?>
	* </code>
	*/
	public function getAllSession($site_id)
	{
		$site_id = intval($site_id);

		$oCore_Cache = Core_Cache::instance(Core::$mainConfig['defaultCache']);
		$inCache = $oCore_Cache->get($cacheKey = $site_id, $cacheName = 'counter_allSession');

		if (!is_null($inCache))
		{
			return $inCache;
		}

		$oDataBase = Core_QueryBuilder::select(array('SUM(sessions)', 'count'))
			->from('counters')
			->where('site_id', '=', $site_id)
			->groupBy('site_id')
			->execute();

		$row = $oDataBase->asAssoc()->current();

		$oDataBase->free();

		$oCore_Cache->set($cacheKey, $row['count'], $cacheName);

		return $row['count'];
	}

	/**
	* Получение данных посещаемости за определенный день. Метод использует кэш "COUNTER_DAY_INFORMATION"
	*
	* @param string $date дата в формате ГГГГ-ММ-ДД
	* @return mixed array с данными или false, если данные отсутствуют
	* <code>
	* <?php
	* $date = date('Y-m-d');
	* $oCounter = Counter_Controller::instance()->getDayInformation($date);
	*
	* // Распечатаем результат
	* if (is_null($oCounter))
	* {
	* 	echo "Данные за указанный период не найдены";
	* }
	* ?>
	* </code>
	*/
	public function getDayInformation($date)
	{
		return $this->site->Counters->getByDate($date);
	}

	protected function _getColoredValue($value)
	{
		if ($value == 0)
		{
			return '–';
		}

		return '<span class="' . ($value < 0 ? 'red' : 'green') . '">' . ($value < 0 ? '↓' : '↑') . abs($value) . '%</span>';
	}

	/**
	* Отправка письма с отчетом администратору сайта
	*
	* @param string $date дата отчета
	* @return boolean false в случае ошибки, в случае успеной отправки метод не возвращет никаких значений
	* <code>
	* <?php
	* $site_id = 1;
	* $date = date('Y-m-d');
	* Counter_Controller::instance()->mailReport($date);
	* ?>
	* </code>
	* @hostcms-event Counter_Controller.onBeforeSendMailReport
	*/
	public function mailReport($date)
	{
		$oCounter = $this->getDayInformation($date);

		if (!is_null($oCounter))
		{
			$sessions = $oCounter->sessions;
			$hosts = $oCounter->hosts;
			$hits = $oCounter->hits;
			$new_users = $oCounter->new_users;
			$bots = $oCounter->bots;
		}
		else
		{
			$sessions = $hosts = $hits = $new_users = $bots = 0;
		}

		// Site lng exists
		$oAdmin_Language = $this->site->lng !== ''
			? Core_Entity::factory('Admin_Language')->getByShortname($this->site->lng)
			: NULL;

		Core_I18n::instance()->setLng($oAdmin_Language ? $oAdmin_Language->shortname : DEFAULT_LNG);

		$site_name = $this->site->name;

		$iTimestamp = Core_Date::sql2timestamp($date);

		ob_start();

		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
				<!--[if !mso]><!-->
				<meta http-equiv="X-UA-Compatible" content="IE=edge">
				<!--<![endif]-->
				<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
				<style type="text/css">
					html { width: 100% }
					body { font-size: 13px; font-family: Arial,Helvetica; -webkit-text-size-adjust: none; -ms-text-size-adjust: none; margin: 0; padding: 0 }
					td, th { padding: 5px; }
					td { text-align: left; /*border-collapse: collapse;*/ }
					th { text-align: center; border-bottom: 1px solid #333; }
					table.container > tbody > tr:nth-child(even) { background-color: #f5f5f5; }
					.title { font-size: 16px; margin-top: 20px; margin-bottom: 10px; }
					.red { color: red; }
					.green { color: green; }
				</style>
			</head>
			<body marginwidth="0" marginheight="0" offset="0" topmargin="0" leftmargin="0" style="word-wrap: break-word; -webkit-nbsp-mode: space;line-break: after-white-space; -webkit-line-break: after-white-space; margin-top: 0; margin-bottom: 0; padding-top: 0; padding-bottom: 0; width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; background-color: #efeeea">
				<div style="background-color: #fff; margin: 30px; padding: 30px;">
					<div class="title" style="margin-bottom: 20px;"><?php echo Core::_('Counter.counter_site', $site_name, Core_Date::timestamp2date($iTimestamp))?></div>
					<table class="container" border="0">
						<thead>
							<tr>
								<th><?php echo Core::_('Counter.graph_sessions')?></th>
								<th><?php echo Core::_('Counter.graph_hosts')?></th>
								<th><?php echo Core::_('Counter.graph_hits')?></th>
								<th><?php echo Core::_('Counter.graph_new_users')?></th>
								<th><?php echo Core::_('Counter.graph_bots')?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td style="text-align: center;"><?php echo $this->_formatNumber($sessions)?></td>
								<td style="text-align: center;"><?php echo $this->_formatNumber($hosts)?></td>
								<td style="text-align: center;"><?php echo $this->_formatNumber($hits)?></td>
								<td style="text-align: center;"><?php echo $this->_formatNumber($new_users)?></td>
								<td style="text-align: center;"><?php echo $this->_formatNumber($bots)?></td>
							</tr>
						</tbody>
					</table>

					<?php
					// Кол-во строк в таблицах ежедневного письма-отчета о посещаемости сайта
					$iLimit = defined('COUNTER_NUM_MAIL_TABLE_ROW') ? intval(COUNTER_NUM_MAIL_TABLE_ROW) : 20;

					// Получаем дату вчерашнего дня
					$sDaySql = Core_Date::timestamp2sql(strtotime("-1 day", $iTimestamp));

					// Получаем начальную дату предшествующей недели
					$sWeekSql = Core_Date::timestamp2sql(strtotime("-1 week", $iTimestamp));

					// Получаем данные для сайта за указанный день
					$oCounter = $this->getDayInformation($sDaySql);

					if (!is_null($oCounter))
					{
						$sessions_y = $oCounter->sessions;
						$hosts_y = $oCounter->hosts;
						$hits_y = $oCounter->hits;
						$new_users_y = $oCounter->new_users;
						$bots_y = $oCounter->bots;

						// Считаем динамику измнений по сравнению с вчерашним днем
						// Если значение за вчерашний день = 0, делим на 1, т.к. на 0 делить нельзя
						$sessions_dinamic = round((($sessions - $sessions_y) / ($sessions_y ? $sessions_y : 1)) * 100);
						$hosts_dinamic = round(($hosts - $hosts_y) / ($hosts_y ? $hosts_y : 1) * 100);
						$hits_dinamic = round(($hits - $hits_y) / ($hits_y ? $hits_y : 1) * 100);
						$new_users_dinamic = round(($new_users - $new_users_y) / ($new_users_y ? $new_users_y : 1) * 100);
						$bots_dinamic = round(($bots - $bots_y) / ($bots_y ? $bots_y : 1) * 100);
						?>
						<div class="title"><?php echo Core::_('Counter.counter_site_previous_day', Core_Date::timestamp2date(Core_Date::sql2timestamp($sDaySql)))?></div>
						<table class="container" border="0">
							<thead>
								<tr>
									<th><?php echo Core::_('Counter.graph_sessions')?></th>
									<th><?php echo Core::_('Counter.graph_hosts')?></th>
									<th><?php echo Core::_('Counter.graph_hits')?></th>
									<th><?php echo Core::_('Counter.graph_new_users')?></th>
									<th><?php echo Core::_('Counter.graph_bots')?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td style="text-align: center;"><?php echo $this->_getColoredValue($sessions_dinamic)?></td>
									<td style="text-align: center;"><?php echo $this->_getColoredValue($hosts_dinamic)?></td>
									<td style="text-align: center;"><?php echo $this->_getColoredValue($hits_dinamic)?></td>
									<td style="text-align: center;"><?php echo $this->_getColoredValue($new_users_dinamic)?></td>
									<td style="text-align: center;"><?php echo $this->_getColoredValue($bots_dinamic)?></td>
								</tr>
							</tbody>
						</table>
						<?php
					}

					// Получаем среднее значение данных для предыдущей недели
					$oDataBase = Core_QueryBuilder::select(
							array('avg(sessions)', 'avg_sessions'),
							array('avg(hosts)', 'avg_hosts'),
							array('avg(hits)', 'avg_hits'),
							array('avg(new_users)', 'avg_new_users'),
							array('avg(bots)', 'avg_bots'))
						->from('counters')
						->where('site_id', '=', $this->site->id)
						->where('date', 'BETWEEN', array($sWeekSql, $sDaySql))
						->groupBy('site_id')
						->execute();

					$row = $oDataBase->asAssoc()->current();

					$oDataBase->free();

					if ($row)
					{
						$sessions_avg = round($row['avg_sessions']);
						$hosts_avg = round($row['avg_hosts']);
						$hits_avg = round($row['avg_hits']);
						$new_users_avg = round($row['avg_new_users']);
						$bots_avg = round($row['avg_bots']);

						// Считаем динамику измнений по сравнению со средним значением за предыдущую неделю
						// Если среднее значение = 0, делим на 1, т.к. на 0 делить нельзя
						$sessions_dinamic_avg = round(($sessions - $sessions_avg) / ($sessions_avg ? $sessions_avg : 1) * 100);
						$hosts_dinamic_avg = round(($hosts - $hosts_avg) / ($hosts_avg ? $hosts_avg : 1) * 100);
						$hits_dinamic_avg = round(($hits - $hits_avg) / ($hits_avg ? $hits_avg : 1) * 100);
						$new_users_dinamic_avg = round(($new_users - $new_users_avg) / ($new_users_avg ? $new_users_avg : 1) * 100);
						$bots_dinamic_avg = round(($bots - $bots_avg) / ($bots_avg ? $bots_avg : 1) * 100);
						?>
						<div class="title"><?php echo Core::_('Counter.counter_site_previous_week')?></div>
						<table class="container" border="0">
							<thead>
								<tr>
									<th><?php echo Core::_('Counter.graph_sessions')?></th>
									<th><?php echo Core::_('Counter.graph_hosts')?></th>
									<th><?php echo Core::_('Counter.graph_hits')?></th>
									<th><?php echo Core::_('Counter.graph_new_users')?></th>
									<th><?php echo Core::_('Counter.graph_bots')?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td style="text-align: center;"><?php echo $this->_getColoredValue($sessions_dinamic_avg)?></td>
									<td style="text-align: center;"><?php echo $this->_getColoredValue($hosts_dinamic_avg)?></td>
									<td style="text-align: center;"><?php echo $this->_getColoredValue($hits_dinamic_avg)?></td>
									<td style="text-align: center;"><?php echo $this->_getColoredValue($new_users_dinamic_avg)?></td>
									<td style="text-align: center;"><?php echo $this->_getColoredValue($bots_dinamic_avg)?></td>
								</tr>
							</tbody>
						</table>
						<?php
					}

					// Получаем самые популярные страницы
					$oDataBase = Core_QueryBuilder::select()
						->from('counter_pages')
						->where('site_id', '=', $this->site->id)
						->where('date', '=', $date)
						->orderBy('count', 'DESC')
						->limit($iLimit)
						->execute();

					$aSelect = $oDataBase->asAssoc()->result();

					if (count($aSelect))
					{
						?>
						<div class="title"><?php echo Core::_('Counter.popular_pages')?></div>
						<table class="container" border="0">
							<thead>
								<tr>
									<th><?php echo Core::_('Counter.number')?></th>
									<th><?php echo Core::_('Counter.count')?></th>
									<th><?php echo Core::_('Counter.page_address')?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ($aSelect as $key => $aRow)
								{
									?>
									<tr>
										<td><?php echo $key + 1?></td>
										<td><?php echo $aRow['count']?></td>
										<td><a href="<?php echo htmlspecialchars($aRow['page'])?>" target="_blank"><?php echo htmlspecialchars($aRow['page'])?></a></td>
									</tr>
									<?php
								}
								?>
							</tbody>
						</table>
						<?php
					}

					// Получаем ссылающиеся страницы
					$oDataBase = Core_QueryBuilder::select()
						->from('counter_referrers')
						->where('site_id', '=', $this->site->id)
						->where('date', '=', $date)
						->orderBy('count', 'DESC')
						->limit($iLimit)
						->execute();

					$aSelect = $oDataBase->asAssoc()->result();

					if (count($aSelect))
					{
						?>
						<div class="title"><?php echo Core::_('Counter.link_pages')?></div>
						<table class="container" border="0">
							<thead>
								<tr>
									<th><?php echo Core::_('Counter.number')?></th>
									<th><?php echo Core::_('Counter.count')?></th>
									<th><?php echo Core::_('Counter.page_address')?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ($aSelect as $key => $aRow)
								{
									?>
									<tr>
										<td><?php echo $key + 1?></td>
										<td><?php echo $aRow['count']?></td>
										<td><a href="<?php echo htmlspecialchars($aRow['referrer'])?>" target="_blank"><?php echo htmlspecialchars($aRow['referrer'])?></a></td>
									</tr>
									<?php
								}
								?>
							</tbody>
						</table>
						<?php
					}

					// Получаем поисковые запросы за предыдущий день
					$oDataBase = Core_QueryBuilder::select()
						->from('counter_searchqueries')
						->where('site_id', '=', $this->site->id)
						->where('date', '=', $date)
						->where('searchquery', '!=', '')
						->orderBy('count', 'DESC')
						->limit($iLimit)
						->execute();

					$aSelect = $oDataBase->asAssoc()->result();

					if (count($aSelect))
					{
						?>
						<div class="title"><?php echo Core::_('Counter.search_query')?></div>
						<table class="container" border="0">
							<thead>
								<tr>
									<th><?php echo Core::_('Counter.number')?></th>
									<th><?php echo Core::_('Counter.count')?></th>
									<th><?php echo Core::_('Counter.source')?></th>
									<th><?php echo Core::_('Counter.query')?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ($aSelect as $key => $aRow)
								{
									?>
									<tr>
										<td><?php echo $key + 1?></td>
										<td><?php echo $aRow['count']?></td>
										<td><?php echo htmlspecialchars($aRow['searchsystem'])?></td>
										<td><?php echo htmlspecialchars($aRow['searchquery'])?></td>
									</tr>
									<?php
								}
								?>
							</tbody>
						</table>
						<?php
					}

					// Получаем User Agents ботов
					$oDataBase = Core_QueryBuilder::select()
						->from('counter_useragents')
						->where('site_id', '=', $this->site->id)
						->where('date', '=', $date)
						->where('crawler', '=', 1)
						->orderBy('count', 'DESC')
						->limit($iLimit)
						->execute();

					$aSelect = $oDataBase->asAssoc()->result();

					if (count($aSelect))
					{
						?>
						<div class="title"><?php echo Core::_('Counter.search_bots')?></div>
						<table class="container" border="0">
							<thead>
								<tr>
									<th><?php echo Core::_('Counter.number')?></th>
									<th><?php echo Core::_('Counter.count')?></th>
									<th><?php echo Core::_('Counter.crawler')?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ($aSelect as $key => $aRow)
								{
									?>
									<tr>
										<td><?php echo $key + 1?></td>
										<td><?php echo $aRow['count']?></td>
										<td><?php echo htmlspecialchars($aRow['useragent'])?></td>
									</tr>
									<?php
								}
								?>
							</tbody>
						</table>
						<?php
					}
					?>
				</div>

				<div style="margin-left: 30px; margin-top: 10px; margin-bottom: 20px;"><?php echo Core::_('Core.info_cms')?> <a href="http://www.hostcms.ru" target="_blank">HostCMS</a></div>
			</body>
		</html>
		<?php

		$mail_text = ob_get_clean();

		$oCore_Mail = Core_Mail::instance()
			->to($this->site->admin_email)
			->from($this->site->getFirstEmail())
			->subject(Core::_('Counter.subject', $site_name))
			->message($mail_text)
			->contentType('text/html')
			->header('X-HostCMS-Reason', 'Counter')
			->header('Precedence', 'bulk')
			->messageId();

		$this->site->sender_name != ''
			&& $oCore_Mail->senderName($this->site->sender_name);

		Core_Event::notify(get_class($this) . '.onBeforeSendMailReport', $this, array($oCore_Mail));

		$oCore_Mail->send();
	}

	/**
	 * Format number
	 * @param int $int
	 * @return string
	 */
	protected function _formatNumber($int)
	{
		return number_format(round($int), 0, '.', ' ');
	}

	/**
	* Выравнивание текста по центру с помощью пробелов
	*
	* @param int $count_all общее Кол-во символов
	* @param string $text текст
	* @return string строка с выровненным текстом
	* <code>
	* <?php
	* $count_all = 31;
	* $text = 'Тестовый текст для выравнивания';
	*
	* $result = Counter_Controller::instance()->alignTextSpace($count_all, $text);
	*
	* // Распечатаем результат
	* echo $result;
	* ?>
	* </code>
	*/
	public function alignTextSpace($count_all, $text)
	{
		// Получаем разницу между длиной текста и общим кол-ом символов
		$count = $count_all - mb_strlen($text);

		// Если общее кол-во больше - выравниваем текст
		if ($count > 0)
		{
			$text = str_pad($text, $count_all - round($count / 2), ' ', STR_PAD_LEFT);
			$text = str_pad($text, $count_all);
		}

		return $text;
	}
}