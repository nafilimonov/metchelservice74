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
class Search_Controller_Hostcms extends Search_Controller_Driver
{
	/**
	 * Get search_pages Table Name
	 * @param int $site_id Site ID
	 * @return string
	 */
	public function getSearchPageTableName($site_id)
	{
		return 'search_pages' . $site_id;
	}

	/**
	 * Get search_words Table Name
	 * @param int $site_id Site ID
	 * @return string
	 */
	public function getSearchWordTableName($site_id)
	{
		return 'search_words' . $site_id;
	}

	/**
	 * Get search_page_siteuser_groups Table Name
	 * @param int $site_id Site ID
	 * @return string
	 */
	public function getSearchPageSiteuserGroupTableName($site_id)
	{
		return 'search_page_siteuser_groups' . $site_id;
	}

	/**
	 * Remove all indexed data
	 * @param int $site_id Site ID
	 * @return self
	 */
	public function truncate($site_id)
	{
		$aTables = $this->getTables();

		$searchPageTableName = $this->getSearchPageTableName($site_id);
		$searchWordTableName = $this->getSearchWordTableName($site_id);
		$searchPageSiteuserGroupTableName = $this->getSearchPageSiteuserGroupTableName($site_id);

		//in_array($searchPageTableName, $aTables) && Core_QueryBuilder::truncate($searchPageTableName)->execute();
		//in_array($searchWordTableName, $aTables) && Core_QueryBuilder::truncate($searchWordTableName)->execute();
		//in_array($searchPageSiteuserGroupTableName, $aTables) && Core_QueryBuilder::truncate($searchPageSiteuserGroupTableName)->execute();

		Core_QueryBuilder::drop($searchPageTableName)
			->table($searchWordTableName)
			->table($searchPageSiteuserGroupTableName)
			->ifExists()
			->execute();

		if (isset($this->_aCreated[$site_id]))
		{
			unset($this->_aCreated[$site_id]);
		}

		$this->_tables = NULL;

		return $this;
	}

	/**
	 * Optimize indexed data
	 * @param int $site_id Site ID
	 * @return self
	 */
	public function optimize($site_id)
	{
		// nothing to do
	}

	/**
	 * Array of tables
	 * @var array|NULL
	 */
	protected $_tables = NULL;

	/**
	 * Get list of tables
	 * @return array
	 */
	public function getTables()
	{
		if (is_null($this->_tables))
		{
			$this->_tables = Core_DataBase::instance()->getTables();
		}

		return $this->_tables;
	}

	protected $_aCreated = array();

	public function create($siteId)
	{
		if (!isset($this->_aCreated[$siteId]))
		{
			$siteId = intval($siteId);

			$oCore_DataBase = Core_DataBase::instance();

			$aConfig = $oCore_DataBase->getConfig();

			$sEngine = isset($aConfig['storageEngine'])
				? $aConfig['storageEngine']
				: 'MyISAM';

			$aTables = $this->getTables();

			$tableName = $this->getSearchPageTableName($siteId);
			if (!in_array($tableName, $aTables))
			{
				$query = "CREATE TABLE IF NOT EXISTS `" . $tableName . "` (" .
					"\n`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT," .
					"\n`url` varchar(2000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL," .
					"\n`title` varchar(255) DEFAULT NULL," .
					"\n`datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'," .
					"\n`size` int(11) NOT NULL DEFAULT '0'," .
					"\n`inner` tinyint(1) DEFAULT '0'," .
					"\n`module` tinyint(4) NOT NULL DEFAULT '0'," .
					"\n`module_id` int(11) NOT NULL DEFAULT '0'," .
					"\n`module_value_type` int(11) NOT NULL DEFAULT '0'," .
					"\n`module_value_id` int(11) NOT NULL DEFAULT '0'," .
					//"\n`site_id` int(11) NOT NULL DEFAULT '0'," .
					"\nPRIMARY KEY (`id`)," .
					"\nKEY `size` (`size`,`inner`)," .
					"\nKEY `url` (`url`(60))," .
					//"\nKEY `site_id` (`site_id`)," .
					"\nKEY `module` (`module`,`module_id`)," .
					"\nKEY `module_value_type_and_id` (`module`,`module_value_type`,`module_value_id`)" .
					"\n) ENGINE={$sEngine} DEFAULT CHARSET=utf8";

				$oCore_DataBase->query($query);
			}

			$tableName = $this->getSearchWordTableName($siteId);
			if (!in_array($tableName, $aTables))
			{
				$query = "CREATE TABLE IF NOT EXISTS `" . $tableName . "` (" .
					"\n`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT," .
					"\n`hash` int(11) NOT NULL DEFAULT '0'," .
					"\n`search_page_id` int(11) NOT NULL DEFAULT '0'," .
					"\n`weight` float NOT NULL DEFAULT '0'," .
					"\nPRIMARY KEY (`id`)," .
					"\nKEY `hash` (`hash`)," .
					"\nKEY `search_page_id` (`search_page_id`)" .
					"\n) ENGINE={$sEngine} DEFAULT CHARSET=utf8";

				$oCore_DataBase->query($query);
			}

			$tableName = $this->getSearchPageSiteuserGroupTableName($siteId);
			if (!in_array($tableName, $aTables))
			{
				$query = "CREATE TABLE IF NOT EXISTS `" . $tableName . "` (" .
					"\n`id` int(11) NOT NULL AUTO_INCREMENT," .
					"\n`search_page_id` int(11) NOT NULL DEFAULT '0'," .
					"\n`siteuser_group_id` int(11) NOT NULL DEFAULT '0'," .
					"\nPRIMARY KEY (`id`)," .
					"\nKEY `search_page_id` (`search_page_id`)," .
					"\nKEY `site_users_group_id` (`siteuser_group_id`)" .
					"\n) ENGINE={$sEngine} DEFAULT CHARSET=utf8";

				$oCore_DataBase->query($query);
			}

			$this->_aCreated[$siteId] = TRUE;
		}

		return $this;
	}

	/**
	 * Indexing search pages
	 * @param array $aPages list of search pages
	 * @return boolean
	 */
	public function indexingSearchPages(array $aPages)
	{
		if (count($aPages))
		{
			$aCounts = array();
			$iLastInsertTime = time();

			$aQB = array();

			foreach ($aPages as $oPage)
			{
				if (!is_null($oPage))
				{
					$this->create($oPage->site_id);

					// Create Search_Page
					$oSearch_Page = Core_Entity::factory('Search_Page')
						->setTableName($this->getSearchPageTableName($oPage->site_id));
					$oSearch_Page->url = $oPage->url;
					$oSearch_Page->title = $oPage->title;
					$oSearch_Page->size = $oPage->size;
					//$oSearch_Page->site_id = $oPage->site_id;
					$oSearch_Page->datetime = $oPage->datetime;
					$oSearch_Page->module = $oPage->module;
					$oSearch_Page->module_id = $oPage->module_id;
					$oSearch_Page->inner = $oPage->inner;
					$oSearch_Page->module_value_type = $oPage->module_value_type;
					$oSearch_Page->module_value_id = $oPage->module_value_id;
					$oSearch_Page->save();

					// Delete previous search_page_siteuser_groups
					$searchPageSiteuserGroupTableName = $this->getSearchPageSiteuserGroupTableName($oPage->site_id);
					Core_QueryBuilder::delete($searchPageSiteuserGroupTableName)
						->where('search_page_id', '=', $oSearch_Page->id)
						->execute();

					if (is_array($oPage->siteuser_groups))
					{
						foreach ($oPage->siteuser_groups as $siteuser_group_id)
						{
							$oSearch_Page_Siteuser_Group = Core_Entity::factory('Search_Page_Siteuser_Group')
								->setTableName($searchPageSiteuserGroupTableName);
							$oSearch_Page_Siteuser_Group->siteuser_group_id = $siteuser_group_id;
							$oSearch_Page->add($oSearch_Page_Siteuser_Group);
						}
					}

					Core_QueryBuilder::delete($this->getSearchWordTableName($oPage->site_id))
						->where('search_page_id', '=', $oSearch_Page->id)
						->execute();

					$aWords = array_merge(
						Search_Controller::getHashes($oPage->text, array('hash_function' => 'crc32')),
						Search_Controller::getHashes($oPage->title, array('hash_function' => 'crc32'))
					);

					$oPage->text = $oPage->title = '';

					$iCountArray = count($aWords);
					$aWeights = array();
					foreach ($aWords as $word)
					{
						if (!isset($aWeights[$word]))
						{
							$aWeights[$word] = 1;
						}
						else
						{
							$aWeights[$word] += 1 / $iCountArray;
						}
					}

					if (!isset($aQB[$oPage->site_id]))
					{
						$this->create($oPage->site_id);

						$aQB[$oPage->site_id] = Core_QueryBuilder::insert($this->getSearchWordTableName($oPage->site_id))
							->columns('hash', 'search_page_id', 'weight');
					}

					// Insert words for page
					$aWords = array_unique($aWords);
					foreach ($aWords as $word)
					{
						$aQB[$oPage->site_id]->values($word, $oSearch_Page->id, $aWeights[$word]);

						!isset($aCounts[$oPage->site_id]) && $aCounts[$oPage->site_id] = 0;

						if (time() - $iLastInsertTime > 5 || $aCounts[$oPage->site_id] * 30 / 1024 > 1)
						{
							$aQB[$oPage->site_id]->execute();
							$aQB[$oPage->site_id]->clearValues();
							$aCounts[$oPage->site_id] = 0;
							$iLastInsertTime = time();
						}
						else
						{
							$aCounts[$oPage->site_id]++;
						}
					}
				}
			}

			foreach ($aCounts as $siteId => $count)
			{
				if ($count)
				{
					$aQB[$siteId]->execute();
					$aQB[$siteId]->clearValues();
				}
			}

			return TRUE;
		}
	}

	/**
	 * Get pages count
	 * @param int $siteId site ID
	 * @return string count of pages
	 */
	public function getPageCount($siteId)
	{
		$this->create($siteId);

		$oCore_QueryBuilder_Select = Core_QueryBuilder::select(array('COUNT(*)', 'count'))
			->from($this->getSearchPageTableName($siteId));

		//$siteId && $oCore_QueryBuilder_Select->where('site_id', '=', intval($siteId));

		$row = $oCore_QueryBuilder_Select->execute()->asAssoc()->current();

		return $row['count'];
	}

	/**
	 * Get subquery QueryBuilder
	 * @param array $words Word's hashes
	 * @return Core_QueryBuilder_Select
	 */
	protected function _getWordSelectorQB(array $aWords)
	{
		$searchWordTableName = $this->getSearchWordTableName($this->site_id);
		$subQuery = Core_QueryBuilder::select('search_page_id', array('SUM(weight)', 'sum_weight'))
			->from($searchWordTableName);

		switch ($this->orderField)
		{
			case 'weight':
				$subQuery->orderBy('sum_weight', $this->orderDirection);
			break;
			default:
				$subQuery->orderBy($this->orderField, $this->orderDirection);
		}

		$subQuery
			->where('hash', 'IN', $aWords)
			->groupBy('search_page_id')
			->having('COUNT(id)', '=', count($aWords));

		return $subQuery;
	}

	/**
	 * Find
	 * @param string $query Search query
	 * @return array Array of Search_Page_Model
	 * @hostcms-event Search_Controller_Hostcms.onBeforeExecuteFind
	 */
	public function find($query)
	{
		$this->create($this->site_id);

		$aSearch_Pages = array();

		if (strlen($query))
		{
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

			$searchPageTableName = $this->getSearchPageTableName($this->site_id);
			$searchPageSiteuserGroupTableName = $this->getSearchPageSiteuserGroupTableName($this->site_id);

			$oSearch_Pages = Core_QueryBuilder::select($searchPageTableName . '.*')
				->straightJoin()
				->sqlCalcFoundRows()
				// Нулевые элементы - XSL-шаблоны, ТДС
				//->where('search_pages.site_id', '=', $this->site_id) // Таблицы разделены по сайтам
				->where($searchPageSiteuserGroupTableName . '.siteuser_group_id', 'IN', $aSiteuserGroups)
				// По умолчанию сортировка по весу слов
				//->orderBy('datetime', 'DESC')
				;

			$aWords = array_unique(Search_Controller::getHashes($query, array('hash_function' => 'crc32')));

			if (count($aWords))
			{
				$subQuery = $this->_getWordSelectorQB($aWords);

				// Spell checking
				$aQuery = explode(' ', $query);
				foreach ($aQuery as $key => $sTmp)
				{
					$aQuery[$key] = Search_Controller::spellCheck($sTmp);
				}
				$spellCheckedQuery = implode(' ', $aQuery);

				// Откорректированный запрос отличается от исходного
				if ($query !== $spellCheckedQuery)
				{
					$aSpellCheckedWords = array_unique(Search_Controller::getHashes($spellCheckedQuery, array('hash_function' => 'crc32')));

					$subQuerySpellChecked = $this->_getWordSelectorQB($aSpellCheckedWords);

					$subQuery
						->clearOrderBy()
						->union(
							$subQuerySpellChecked->clearOrderBy()
						);

					// отдельная сортировка для общего результата, т.к. используется UNION
					switch ($this->orderField)
					{
						case 'weight':
							$oSearch_Pages->orderBy('tmp.sum_weight', $this->orderDirection);
						break;
						default:
							$oSearch_Pages->orderBy($this->orderField, $this->orderDirection);
					}
				}

				if (is_array(($this->modules)) && count($this->modules))
				{
					$oSearch_Pages->setAnd()->open();

					foreach ($this->modules as $module => $module_entity_array)
					{
						$module = intval($module);

						$oSearch_Pages->open();

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

												$oSearch_Pages->where('module_value_type', 'IN', $aValueType);
											}
											else
											{
												$value['module_value_type'] = intval($value['module_value_type']);

												$oSearch_Pages->where('module_value_type', '=', $value['module_value_type']);
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

												$oSearch_Pages->where('module_value_id', 'IN', $aValueId);
											}
											else
											{
												$value['module_value_id'] = intval($value['module_value_id']);
												$oSearch_Pages->where('module_value_id', '=', $value['module_value_id']);
											}
										}

										$oSearch_Pages
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
								$oSearch_Pages
									->where('module', '=', $module)
									->setAnd()
									->where('module_id', 'IN', $entity_array)
									->setOr();
							}
						}
						else // Если не массив, то ограничиваем только по модулю
						{
							$oSearch_Pages
								->where('module', '=', $module)
								->setOr();
						}

						$oSearch_Pages->close()->setOr();
					}
					$oSearch_Pages->close()->setAnd();
				}

				$oSearch_Pages
					->from(array($subQuery, 'tmp'))
					->join($searchPageTableName, $searchPageTableName . '.id', '=', 'tmp.search_page_id')
					->join($searchPageSiteuserGroupTableName, $searchPageTableName . '.id', '=', $searchPageSiteuserGroupTableName . '.search_page_id')
					//->groupBy('search_pages.id') // есть в верхнем запросе
					->limit($this->limit)
					->offset($this->offset);

				if ($this->inner !== 'all')
				{
					$oSearch_Pages->where($searchPageTableName . '.inner', '=', $this->inner);
				}

				Core_Event::notify(get_class($this) . '.onBeforeExecuteFind', $this, array($query, $oSearch_Pages, $subQuery));

				// Load model columns BEFORE FOUND_ROWS()
				// SHOW FULL COLUMNS FROM
				Core_Entity::factory('Search_Page')->setTableName($searchPageTableName)->getTableColumns();

				// Load user BEFORE FOUND_ROWS()
				$oUserCurrent = Core_Auth::getCurrentUser();

				$aSearch_Pages = $oSearch_Pages
					->execute()
					->asObject($this->_asObject)
					->result();

				// Определим количество элементов
				$this->_foundRows();
			}
		}

		return $aSearch_Pages;
	}

	/**
	 * Set found rows
	 * @return self
	 */
	protected function _foundRows()
	{
		$this->total = Core_QueryBuilder::select()->getFoundRows();

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
		$this->create($site_id);

		$searchPageTableName = $this->getSearchPageTableName($site_id);

		// Load model columns BEFORE FOUND_ROWS()
		// SHOW FULL COLUMNS FROM
		Core_Entity::factory('Search_Page')->setTableName($searchPageTableName)->getTableColumns();

		$oSearch_Pages = Core_Entity::factory('Search_Page')
			->setTableName($searchPageTableName);
		$oSearch_Pages->queryBuilder()
			->where('module', '=', $module)
			->where('module_value_type', '=', $module_value_type)
			->where('module_value_id', '=', $module_value_id);

		$oSearch_Page = $oSearch_Pages->find();

		if (!is_null($oSearch_Page->id))
		{
			$searchWordTableName = $this->getSearchWordTableName($site_id);
			 Core_QueryBuilder::delete($searchWordTableName)
				->where('search_page_id', '=', $oSearch_Page->id)
				->execute();

			$searchPageSiteuserGroupTableName = $this->getSearchPageSiteuserGroupTableName($site_id);
			Core_QueryBuilder::delete($searchPageSiteuserGroupTableName)
				->where('search_page_id', '=', $oSearch_Page->id)
				->execute();
		}

		return $this;
	}
}