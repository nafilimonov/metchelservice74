<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Search.
 *
 * @package HostCMS
 * @subpackage Search
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
class Search_Controller_Sphinx extends Search_Controller_Driver
{
	/**
	 * Sphinx Max Matches
	 * @var int
	 */
	protected $_max_matches = 1000;

	/**
	 * Remove all indexed data
	 * @param int $site_id Site ID
	 * @return self
	 */
	public function truncate($site_id)
	{
		$oCore_Database = Core_Database::instance($this->_config['database']);

		/*$oCore_Database
			->setQueryType(5)
			->query("TRUNCATE RTINDEX " . $oCore_Database->quoteTableName($this->_config['index']));*/

		Core_QueryBuilder::delete($this->_config['index'])
			->setDataBase($oCore_Database)
			->where('site_id', '=', intval($site_id))
			->execute();

		$oCore_Database
			->setQueryType(5)
			->query("ALTER RTINDEX " . $oCore_Database->quoteTableName($this->_config['index']) . " RECONFIGURE");

		return $this;
	}

	/**
	 * Optimize indexed data
	 * @param int $site_id Site ID
	 * @return self
	 */
	public function optimize($site_id)
	{
		if ($site_id == 0)
		{
			$oCore_Database = Core_Database::instance($this->_config['database']);

			$oCore_Database
				->setQueryType(8)
				->query("OPTIMIZE INDEX " . $oCore_Database->quoteTableName($this->_config['index']));
		}
	}

	/**
	 * Create ID by $module, $module_value_type and $module_value_id
	 * @param int $module module's number, 0-15
	 * @param int $module_value_type value type, 0-15
	 * @param int $module_value_id entity id, 0-16777216
	 * @return int
	 */
	protected function _getId($module, $module_value_type, $module_value_id)
	{
		return $module | ($module_value_type << 4) | ($module_value_id << 8);
	}

	/**
	 * Quote string
	 * @param string $string
	 * @return string
	 */
	protected function _quote($string)
	{
		/*def EscapeString(self, string):
		return re.sub(r"([=\(\)|\-!@~\"&/\\\^\$\=])", r"\\\1", string)*/

		// escape individual keywords and then escape by driver
		$from = array('\\', '(', ')', '|', '-', '!', '@', '~', '<<',
		'"', '&', '/', '^', '$', '=', "'", "\x00", "\n", "\r", "\x1a");

		$to = array('\\\\ ', '\(', '\)', '\|', '\-', '\!', '\@', '\~', '\<<',
		'\"', '\&', '\/', '\^', '\$', '\=', "\'", ' ', ' ', ' ', ' ');

		return Core_Database::instance()->escape(
			str_replace($from, $to, $string)
		);
	}

	/**
	 * Get QueryBuilder
	 * @return Core_QueryBuilder_Replace
	 * @hostcms-event Search_Controller_Sphinx.onAfterGetReplaceQueryBuilder
	 */
	protected function _getReplaceQueryBuilder()
	{
		$oCore_Database = Core_Database::instance($this->_config['database']);

		$queryBuilder = Core_QueryBuilder::replace();
		$queryBuilder->setDataBase($oCore_Database);
		$queryBuilder
			->into($this->_config['index'])
			->columns(
				'id',
				'title', 'text', 'datetime', 'url', 'size', 'inner',
				'module', 'module_id', 'module_value_type', 'module_value_id', 'site_id', 'siteuser_group_id'
			);

		Core_Event::notify(get_class($this) . '.onAfterGetReplaceQueryBuilder', $this, array($queryBuilder));

		return $queryBuilder;
	}

	/**
	 * Indexing search pages
	 * @param array $aPages list of search pages
	 * @return boolean
	 * @hostcms-event Search_Controller_Sphinx.onAfterPrepareQueryBuilder
	 * @hostcms-event Search_Controller_Sphinx.onAfterPrepareValues
	 */
	public function indexingSearchPages(array $aPages)
	{
		$queryBuilder = $this->_getReplaceQueryBuilder();

		Core_Event::notify(get_class($this) . '.onAfterPrepareQueryBuilder', $this, array($queryBuilder, $aPages));

		if (count($aPages))
		{
			$count = 0;
			foreach ($aPages as $oPage)
			{
				if (!is_null($oPage) && is_array($oPage->siteuser_groups))
				{
					$aValues = array(
						$this->_getId($oPage->module, $oPage->module_value_type, $oPage->module_value_id),
						$this->_prepareText($oPage->title),
						$this->_prepareText($oPage->text),
						Core_Date::sql2timestamp($oPage->datetime),
						$oPage->url,
						$oPage->size,
						$oPage->inner,
						$oPage->module,
						$oPage->module_id,
						$oPage->module_value_type,
						$oPage->module_value_id,
						$oPage->site_id,
						array_map('intval', $oPage->siteuser_groups)
					);

					Core_Event::notify(get_class($this) . '.onAfterPrepareValues', $this, array($aValues, $oPage));

					$eventResult = Core_Event::getLastReturn();

					!is_null($eventResult)
						&& $aValues = $eventResult;

					$queryBuilder->values($aValues);

					$count++;

					if ($count >= 10)
					{
						$queryBuilder->execute();
						$queryBuilder->clearValues();

						$count = 0;

						$queryBuilder = $this->_getReplaceQueryBuilder();
					}
				}
			}

			if ($count)
			{
				$queryBuilder->execute();
				$queryBuilder->clearValues();
			}

			return TRUE;
		}
	}

	/**
	 * Get pages count
	 * @param int $site_id site ID
	 * @return string count of pages
	 */
	public function getPageCount($site_id)
	{
		$oCore_Database = Core_Database::instance($this->_config['database']);

		$oCore_QueryBuilder_Select = Core_QueryBuilder::select(array('COUNT(*)', 'count'))
			->setDataBase($oCore_Database)
			->from($this->_config['index']);

		$site_id && $oCore_QueryBuilder_Select->where('site_id', '=', intval($site_id));

		$row = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();

		return $row['count'];
	}

	/**
	 * Prepare text
	 * @param string $text
	 * @return string
	 */
	protected function _prepareText($text)
	{
		$aConfig = Core::$config->get('core_str');

		if (isset($aConfig['separators']))
		{
			$text = str_replace($aConfig['separators'], ' ', $text);
		}

		$text = str_replace("\t", ' ', $text);

		// Убираем двойные пробелы
		while (strstr($text, '  '))
		{
			$text = str_replace('  ', ' ', $text);
		}

		return $text;
	}

	/**
	 * Find
	 * @param string $query Search query
	 * @return array Array of Search_Page_Model
	 * @hostcms-event Search_Controller_Sphinx.onBeforeExecuteFind
	 */
	public function find($query)
	{
		$aSearch_Pages = array();

		if (strlen($query) && $this->offset < $this->_max_matches)
		{
			if (!isset($this->_config['database']))
			{
				throw new Core_Exception("Sphinx Search Configuration: database doesn't defined.");
			}

			$oCore_Database = Core_Database::instance($this->_config['database']);

			$aSiteuserGroups = array(0, -1);
			if (Core::moduleIsActive('siteuser'))
			{
				$oSiteuser = Core_Entity::factory('Siteuser')->getCurrent();

				if ($oSiteuser)
				{
					$aSiteuser_Groups = $oSiteuser->Siteuser_Groups->findAll();
					foreach ($aSiteuser_Groups as $oSiteuser_Group)
					{
						$aSiteuserGroups[] = intval($oSiteuser_Group->id);
					}
				}
			}

			$oSearch_Sphinx_QueryBuilder_Select = new Search_Sphinx_QueryBuilder_Select();

			$oSearch_Sphinx_QueryBuilder_Select
				->max_matches($this->_max_matches)
				->setDataBase($oCore_Database)
				->select('id', 'url', 'title', 'datetime', 'size', 'inner', 'module', 'module_id', 'module_value_type',
					'module_value_id', 'site_id')
				->from($this->_config['index'])
				->offsetPostgreSQLSyntax(FALSE)
				->where('site_id', '=', intval($this->site_id))
				->where('siteuser_group_id', 'IN', $aSiteuserGroups);

			if (is_array($this->modules) && count($this->modules))
			{
				$oSearch_Sphinx_QueryBuilder_Select->setAnd()->open();

				foreach ($this->modules as $module => $module_entity_array)
				{
					$module = intval($module);

					$oSearch_Sphinx_QueryBuilder_Select->open();

					if (is_array($module_entity_array))
					{
						$entity_array = array();

						foreach ($module_entity_array as $key => $value)
						{
							if (is_array($value))
							{
								// при передаче массива module_id обязателен
								if (isset($value['module_id']))
								{
									$module_id = intval($value['module_id']);

									if (isset($value['module_value_type']))
									{
										if (is_array($value['module_value_type']) && count($value['module_value_type']))
										{
											$aValueType = array();

											foreach ($value['module_value_type'] as $value_type)
											{
												$aValueType[] = intval($value);
											}

											$oSearch_Sphinx_QueryBuilder_Select->where('module_value_type', 'IN', $aValueType);
										}
										else
										{
											$value['module_value_type'] = intval($value['module_value_type']);

											$oSearch_Sphinx_QueryBuilder_Select->where('module_value_type', '=', $value['module_value_type']);
										}
									}

									if (isset($value['module_value_id']))
									{
										if (is_array($value['module_value_id']) && count($value['module_value_id']))
										{
											$aValueId = array();

											foreach ($value['module_value_id'] as $value_id)
											{
												$aValueId[] = intval($value_id);
											}

											$oSearch_Sphinx_QueryBuilder_Select->where('module_value_id', 'IN', $aValueId);
										}
										else
										{
											$value['module_value_id'] = intval($value['module_value_id']);
											$oSearch_Sphinx_QueryBuilder_Select->where('module_value_id', '=', $value['module_value_id']);
										}
									}

									$oSearch_Sphinx_QueryBuilder_Select
										->setAnd()
										->where('module', '=', $module)
										->where('module_id', '=', $module_id)
										->setOr();
								}
							}
							else
							{
								$entity_array[$key] = intval($value);
							}
						}

						if (count($entity_array))
						{
							$oSearch_Sphinx_QueryBuilder_Select
								->where('module', '=', $module)
								->setAnd()
								->where('module_id', 'IN', $entity_array)
								->setOr();
						}
					}
					else // Если не массив, то ограничиваем только по модулю
					{
						$oSearch_Sphinx_QueryBuilder_Select
							->where('module', '=', $module)
							->setOr();
					}

					$oSearch_Sphinx_QueryBuilder_Select->close()->setOr();
				}
				$oSearch_Sphinx_QueryBuilder_Select->close()->setAnd();
			}

			$oSearch_Sphinx_QueryBuilder_Select
				->where(Core_QueryBuilder::expression("MATCH(" . $this->_quote($this->_prepareText($query)) . ")"))
				->limit($this->limit)
				->offset($this->offset);

			if ($this->inner !== 'all')
			{
				$oSearch_Sphinx_QueryBuilder_Select->where('inner', '=', $this->inner);
			}

			switch ($this->orderField)
			{
				case 'weight':
					$oSearch_Sphinx_QueryBuilder_Select->orderBy('WEIGHT()', $this->orderDirection);
				break;
				default:
					$oSearch_Sphinx_QueryBuilder_Select->orderBy($this->orderField, $this->orderDirection);
			}

			Core_Event::notify(get_class($this) . '.onBeforeExecuteFind', $this, array($query, $oSearch_Sphinx_QueryBuilder_Select));

			// Load model columns BEFORE FOUND_ROWS()
			// SHOW FULL COLUMNS FROM
			Core_Entity::factory('Search_Page')->getTableColumns();

			// Load user BEFORE FOUND_ROWS()
			$oUserCurrent = Core_Auth::getCurrentUser();

			$bLogged = Core_Auth::logged();

			$bLogged && $fBeginTime = Core::getmicrotime();

			$aSearch_Pages = $oSearch_Sphinx_QueryBuilder_Select
				->execute()
				->asObject($this->_asObject)
				->result();

			$bLogged && Core_Page::instance()->addFrontendExecutionTimes(
				Core::_('Search.sphinxTime', Core::getmicrotime() - $fBeginTime)
			);

			// No way to convert timestamp => sql-datetime in select
			foreach ($aSearch_Pages as $key => $oSearch_Page)
			{
				is_numeric($oSearch_Page->datetime)
					&& $aSearch_Pages[$key]->datetime = Core_Date::timestamp2sql($oSearch_Page->datetime);
 			}

			// Определим количество элементов
			$this->_foundRows();
		}

		return $aSearch_Pages;
	}

	/**
	 * Set found rows
	 * @return self
	 */
	protected function _foundRows()
	{
		$oCore_Database = Core_Database::instance($this->_config['database']);

		$row = $oCore_Database
			->setQueryType(5)
			->asAssoc()
			->query("SHOW META LIKE 'total_found'")
			->current(FALSE);

		$this->total = $row['Value'] < $this->_max_matches
			? $row['Value']
			: $this->_max_matches;

		return $this;
	}

	/**
	 * Delete search page
	 *
	 * @param int $site_id
	 * @param int $module module's number, 0-15
	 * @param int $module_value_type value type, 0-15
	 * @param int $module_value_id entity id, 0-16777216
	 * @return self
	 */
	public function deleteSearchPage($site_id, $module, $module_value_type, $module_value_id)
	{
		$oCore_Database = Core_Database::instance($this->_config['database']);

		$queryBuilder = Core_QueryBuilder::delete($this->_config['index']);
		$queryBuilder->setDataBase($oCore_Database);
		$queryBuilder
			->where('id', '=', $this->_getId($module, $module_value_type, $module_value_id))
			->execute();

		return $this;
	}
}