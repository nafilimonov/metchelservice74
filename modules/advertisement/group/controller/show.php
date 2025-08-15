<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Показ баннера из группы баннеров.
 *
 * Доступные методы:
 *
 * - type($type) строка с типом отображаемых баннеров
 * <br />'all' для показа баннеров из списка баннеров группы (по умолчанию)
 * <br />'type' для показа только контекстных баннеров при переданном контексте и неконтекстных баннеров, если контекст не передан
 * - words($str) строка, содержащая контекстную информацию, на основании которой будет показан баннер. Это может быть поисковый запрос, по которому пришел пользователь или содержимое страницы. Если строка не передана - используется случайный выбор баннеров из группы.
 * - limit(1) количество отображаемых баннеров
 * - justContext(TRUE) разрешает показ только баннеров, найденых по переданному контексту. По умолчанию FALSE
 *
 * <code>
 * $Advertisement_Group_Controller_Show = new Advertisement_Group_Controller_Show(
 *		Core_Entity::factory('Advertisement_Group', 1)
 *	);
 *	$Advertisement_Group_Controller_Show
 *		->xsl(
 *			Core_Entity::factory('Xsl')->getByName('ОтображениеБаннера')
 *		)
 *		->show();
 * </code>
 *
 * @package HostCMS
 * @subpackage Advertisement
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2020 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Advertisement_Group_Controller_Show extends Core_Controller
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'type',
		'words',
		'justContext',
		'limit'
	);

	/**
	 * All children structure IDs
	 * @var array
	 */
	protected $_aStructuresId = array();

	/**
	 * Set array $this->_aStructuresId of all children structure IDs
	 * @param Structure_Model $oStructure structure
	 * @return self
	 */
	protected function _setStructureChildren(Structure_Model $oStructure)
	{
		$aStructure = $oStructure->Structures->findAll();

		// Добавляем к массиву дочерних узлов узлы, расположенные выше по ветке
		foreach ($aStructure as $oStructure)
		{
			$this->_aStructuresId[] = $oStructure->id;
			$this->_setStructureChildren($oStructure);
		}
		return $this;
	}

	/**
	 * Advertisement
	 * @var Advertisement_Model
	 */
	protected $_Advertisements = NULL;

	/**
	 * Get advertisements
	 * @return array
	 */
	public function advertisements()
	{
		return $this->_Advertisements;
	}

	/**
	 * Constructor.
	 * @param Advertisement_Group_Model $oAdvertisement_Group group
	 */
	public function __construct(Advertisement_Group_Model $oAdvertisement_Group)
	{
		parent::__construct($oAdvertisement_Group);

		$this->type = 'all';
		$this->justContext = FALSE;
		$this->limit = 1;

		$date = date('Y-m-d');
		$datetime = date("Y-m-d H:i:s");

		$this->_Advertisements = $oAdvertisement_Group->Advertisements;

		// Root and current
		$this->_aStructuresId = array(0, CURRENT_STRUCTURE_ID);

		// All children nodes
		$this->_setStructureChildren(
			Core_Entity::factory('Structure', CURRENT_STRUCTURE_ID)
		);

		$this->_Advertisements->queryBuilder()
			->open()
				->where('advertisements.last_date', '!=', $date)
				->setOr()
				->where('advertisements.show_per_day', '>', Core_QueryBuilder::expression('`advertisements`.`showed_today`'))
			->close()
			->where('advertisements.show_per_day', '>', 0)
			->where('advertisements.show_total', '>', 0)
			->where('advertisements.show_total', '>', Core_QueryBuilder::expression('`advertisements`.`showed`'))
			->where('advertisement_group_lists.probability', '>', 0)
			->open()
				->where('advertisements.start_datetime', '=', '0000-00-00 00:00:00')
				->setOr()
				->where('advertisements.start_datetime', '<=', $datetime)
			->close()
			->open()
				->where('advertisements.end_datetime', '=', '0000-00-00 00:00:00')
				->setOr()
				->where('advertisements.end_datetime', '>=', $datetime)
			->close()
			->where('advertisements.structure_id', 'IN', $this->_aStructuresId);
	}

	/**
	 * Get hashes
	 * @param string $text
	 * @return array
	 */
	protected function _getHashes($text)
	{
		return Core::moduleIsActive('search')
			? Search_Controller::getHashes($text, array('hash_function' => 'crc32'))
			: Core_Str::getHashes($text, array('hash_function' => 'crc32'));
	}

	/**
	 * Show built data
	 * @return self
	 * @hostcms-event Advertisement_Group_Controller_Show.onBeforeRedeclaredShow
	 */
	public function show()
	{
		Core_Event::notify(get_class($this) . '.onBeforeRedeclaredShow', $this);

		// Check crawlers
		if (Core::moduleIsActive('counter') && Counter_Controller::checkBot(Core_Array::get($_SERVER, 'HTTP_USER_AGENT')) && !defined('STATIC_CACHE'))
		{
			return FALSE;
		}

		$bTpl = $this->_mode == 'tpl';

		$oAdvertisement_Group = $this->getEntity();

		$bContext = !is_null($this->words) && strlen(trim($this->words));

		if ($bTpl)
		{
			$this->assign('controller', $this);
			$this->assign('aAdvertisements', array());
		}

		// Если указано использовать контекстные слова и переданы сами слова
		if ($bContext)
		{
			$aAdvContext = array();

			$this->_Advertisements
				->queryBuilder()
				->clearOrderBy()
				->orderBy('RAND()');

			$aSourceHash = $this->_getHashes($this->words, array('hash_function' => 'crc32'));
			$aSourceHash = array_unique($aSourceHash);
			// Сбрасываем индексы после array_unique
			$aSourceHash = array_values($aSourceHash);

			$aAdvertisements = $this->_Advertisements->findAll(FALSE);

			foreach ($aAdvertisements as $oAdvertisement)
			{
				$oAdvertisement_Group_List = $oAdvertisement
					->Advertisement_Group_Lists
					->getByAdvertisement_group_id($oAdvertisement_Group->id);

				if ($oAdvertisement_Group_List)
				{
					$probability = $oAdvertisement_Group_List->probability;

					// Получаем контекстные запросы
					$aAdvertisement_Contextualwords = $oAdvertisement->Advertisement_Contextualwords->findAll(FALSE);

					// Получаем все тексты для баннера
					foreach ($aAdvertisement_Contextualwords as $oAdvertisement_Contextualword)
					{
						// Строка с контекстными словами
						$sContextualWords = $oAdvertisement_Contextualword->value;

						// Массив из строки с контекстными словами
						$aContextualWords = explode(' ', $sContextualWords);

						$aContextualWordsAllow = $aContextualWordsDeny = array();

						// Делим массив на два - с разррешенными и запрещенными
						if (is_array($aContextualWords) && count($aContextualWords) > 0)
						{
							foreach ($aContextualWords as $value)
							{
								if (strlen($value) && substr($value, 0, 1) != '-')
								{
									$aContextualWordsAllow[] = $value;
								}
								else
								{
									$aContextualWordsDeny[] = $value;
								}
							}

							$sContextualWordsAllow = implode(' ', $aContextualWordsAllow);
							$sContextualWordsDeny = implode(' ', $aContextualWordsDeny);
						}
						else
						{
							$sContextualWordsAllow = $sContextualWords;
							$sContextualWordsDeny = '';
						}

						// Расчитываекм коэффициент для разрешенных фраз
						$aHashContextualWordsAllow = $this->_getHashes($sContextualWordsAllow);
						$coefficientAllow = $this->_getTextSimilarity($aHashContextualWordsAllow, $aSourceHash, 'min');

						// Расчитываекм коэффициент для запрещенных фраз
						$aHashContextualWordsDeny = $this->_getHashes($sContextualWordsDeny);
						$coefficientDeny = $this->_getTextSimilarity($aHashContextualWordsDeny, $aSourceHash, 'min');

						// Только при полном вхождении контекстной фразы в переданный текст отображаем баннер
						// Фраза может встречаться несколько раз, поэтому условие >= 1
						// и $coefficientDeny должен быть равен 0
						if ($coefficientAllow >= 1 && $coefficientDeny == 0)
						{
							if (!isset($aAdvContext[$oAdvertisement->id]))
							{
								$rand01 = (float) mt_rand() / (float) mt_getrandmax();
								$aAdvContext[$oAdvertisement->id] = -1 * log(1 - $rand01) / $probability;
							}
						}
					}
				}
			}

			$aAdvertisements = array();

			// Если были найдены контекстные баннеры - то ображаем их
			if (count($aAdvContext))
			{
				arsort($aAdvContext);

				$aAdvContext = array_slice($aAdvContext, 0, $this->limit, TRUE);

				foreach ($aAdvContext as $key => $probability)
				{
					$aAdvertisements[] = Core_Entity::factory('Advertisement', $key);
				}
			}
		}

		// Не контекстный показ или контекстный и только контекстные и контекстные найдены не были
		if (!$bContext || !$this->justContext && $this->type == 'all' && !count($aAdvertisements))
		{
			// Обычный показ N баннеров
			$this->_Advertisements
				->queryBuilder()
				->clearOrderBy()
				->orderBy(Core_QueryBuilder::expression('-LOG(1.0 - RAND()) / probability'), 'DESC')
				->limit($this->limit);

			$aAdvertisements = $this->_Advertisements->findAll(FALSE);
		}

		foreach ($aAdvertisements as $oAdvertisement)
		{
			$oAdvertisement->incAdvertisementStatistic(TRUE, FALSE);

			if (!$bTpl)
			{
				// Идентификатор показа
				$oAdvertisement_Show = $oAdvertisement->addAdvertisementShow();

				$this->addEntity($oAdvertisement->clearEntities());

				$oAdvertisement->addEntity(
					$oAdvertisement_Show->clearEntities()
				);
			}
			else
			{
				$this->append('aAdvertisements', $oAdvertisement);
			}
		}

		return parent::show();
	}

	/**
	 * Calculate the similarity between two arrays
	 *
	 * @param array $aArray1 The first array.
	 * @param array $aArray2 The second array.
	 * @param string $method Kind of similarity.
	 * @return int
	 */
	protected function _getTextSimilarity($aArray1, $aArray2, $method = 'max')
	{
		$iCount1 = count($aArray1);
		$iCount2 = count($aArray2);

		$intersect = $iCount2 > $iCount1
			? count(array_intersect($aArray2, $aArray1))
			: count(array_intersect($aArray1, $aArray2));

		$denominator = $method == 'max'
			? max($iCount1, $iCount2)
			: min($iCount1, $iCount2);

		if ($denominator != 0)
		{
			return $intersect / $denominator;
		}

		return 0;
	}
}