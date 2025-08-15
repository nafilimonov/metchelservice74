<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Показ списка клиентов сайта
 *
 * Доступные методы:
 *
 * - showProperties Показывать дополнительные свойства
 * - showMaillists Показывать подписку на почтовые рассылку
 * - addFilter() добавить условие отобра клиентов, например ->addFilter('property', 17, '=', 1)
 * - filterStrictMode(TRUE|FALSE) фильтровать только по существующим значениям, отсутствие значения считать неверным значением, по умолчанию FALSE
 * - addAllowedTags('/node/path', array('description')) массив тегов для элементов, указанных в первом аргументе, разрешенных к передаче в генерируемый XML
 * - addForbiddenTags('/node/path', array('description')) массив тегов для элементов, указанных в первом аргументе, запрещенных к передаче в генерируемый XML
 *
 * Доступные свойства:
 *
 * - total общее количество доступных для отображения элементов
 * - patternParams массив данных, извелеченных из URI при применении pattern
 *
 * Доступные пути для методов addAllowedTags/addForbiddenTags:
 *
 * - '/' или '/site' Сайт
 * - '/site/siteuser' Клиент
 *
 * <code>
 * $oSite = Core_Entity::factory('Site', 1);
 *
 * $Siteuser_List_Controller_Show = new Siteuser_List_Controller_Show(
 * 	$oSite
 * );
 *
 * $Siteuser_List_Controller_Show
 * 	->xsl(
 * 		Core_Entity::factory('Xsl')->getByName('СписокПользователейСайта')
 * 	)
 * 	->show();
 * </code>
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2023 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_List_Controller_Show extends Core_Controller
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'offset',
		'limit',
		'page',
		'total',
		'pattern',
		'patternParams',
		'showProperties',
		'filterStrictMode',
		'showMaillists',
		'url'
	);

	/**
	 * Siteusers
	 * @var Siteuser_Model
	 */
	protected $_Siteusers = NULL;

	/**
	 * Get Siteusers
	 * @return Siteuser_Model
	 */
	public function siteusers()
	{
		return $this->_Siteusers;
	}

	/**
	 * Constructor.
	 * @param Site_Model $oSite
	 */
	public function __construct(Site_Model $oSite)
	{
		parent::__construct($oSite->clearEntities());

		$this->showProperties = $this->showMaillists = $this->filterStrictMode = FALSE;

		// Named subpatterns {name} can consist of up to 32 alphanumeric characters and underscores, but must start with a non-digit.
		$this->pattern = rawurldecode(Core_Page::instance()->structure->getPath()) . '(page-{page}/)';

		$this->_Siteusers = $oSite->Siteusers;

		$this->_Siteusers
			->queryBuilder()
			->select('siteusers.*');
			
		$this->url = Core::$url['path'];
	}

	/**
	 * Show built data
	 * @return self
	 * @hostcms-event Siteuser_List_Controller_Show.onBeforeRedeclaredShow
	 */
	public function show()
	{
		Core_Event::notify(get_class($this) . '.onBeforeRedeclaredShow', $this);

		$oSite = $this->getEntity();

		$this->addEntity(
			Core::factory('Core_Xml_Entity')
				->name('page')
				->value(intval($this->page))
		)->addEntity(
			Core::factory('Core_Xml_Entity')
				->name('limit')
				->value(intval($this->limit))
		);

		$this->applyFilter();

		if ($this->limit > 0)
		{
			// Load model columns BEFORE FOUND_ROWS()
			Core_Entity::factory('Siteuser')->getTableColumns();

			// Load user BEFORE FOUND_ROWS()
			$oUserCurrent = Core_Auth::getCurrentUser();

			$this->_Siteusers
				->queryBuilder()
				->sqlCalcFoundRows()
				->offset(intval($this->offset))
				->limit(intval($this->limit));

			$aSiteusers = $this->_Siteusers->findAll(FALSE);

			$this->total = Core_QueryBuilder::select()->getFoundRows();

			$this->addEntity(
				Core::factory('Core_Xml_Entity')
					->name('total')
					->value(intval($this->total))
			);

			foreach ($aSiteusers as $oSiteuser)
			{
				$oSiteuser->clearEntities();

				$this->applyForbiddenAllowedTags('/site/siteuser', $oSiteuser);

				$this->addEntity(
					$oSiteuser
						->showXmlProperties($this->showProperties)
						->showXmlMaillists($this->showMaillists)
				);
			}
		}

		return parent::show();
	}

	/**
	 * Parse URL and set controller properties
	 * @return self
	 * @hostcms-event Siteuser_List_Controller_Show.onBeforeParseUrl
	 * @hostcms-event Siteuser_List_Controller_Show.onAfterParseUrl
	 */
	public function parseUrl()
	{
		Core_Event::notify(get_class($this) . '.onBeforeParseUrl', $this);

		$Core_Router_Route = new Core_Router_Route($this->pattern);
		$this->patternParams = $matches = $Core_Router_Route->applyPattern($this->url);

		if (isset($matches['page']) && $matches['page'] > 1)
		{
			$this->page($matches['page'] - 1)
				->offset($this->limit * $this->page);
		}

		Core_Event::notify(get_class($this) . '.onAfterParseUrl', $this);

		return $this;
	}

	/**
	 * Set goods sorting
	 * @param $column Column name
	 * @return self
	 */
	public function orderBy($column, $direction = 'ASC')
	{
		$this->siteusers()
			->queryBuilder()
			->clearOrderBy()
			->orderBy($column, $direction);

		$this->addCacheSignature('orderBy=' . $column . $direction);

		return $this;
	}

	/**
	 * Array of Properties conditions, see addFilter()
	 * @var array
	 */
	protected $_aFilterProperties = array();

	/**
	 * Add filter condition
	 * ->addFilter('property', 17, '=', 33)
	 */
	public function addFilter()
	{
		$args = func_get_args();

		$iCountArgs = count($args);

		if ($iCountArgs < 4)
		{
			throw new Core_Exception("addFilter() expected at least 4 arguments");
		}

		switch ($args[0])
		{
			case 'property':
				/*if ($iCountArgs < 4)
				{
					throw new Core_Exception("addFilter('property') expected 4 arguments");
				}*/

				$oProperty = Core_Entity::factory('Property', $args[1]);

				$aPropertiesValue = $args[3];

				!is_array($aPropertiesValue) && $aPropertiesValue = array($aPropertiesValue);

				switch ($oProperty->type)
				{
					case 3:
					case 5:
					case 12:
					case 7:
						$map = 'intval';
					break;
					case 11:
						$map = 'floatval';
					break;
					default:
						$map = 'strval';
				}

				$aPropertiesValue = array_map($map, $aPropertiesValue);

				$this->_aFilterProperties[$oProperty->id][] = array($oProperty, $args[2], $aPropertiesValue);
			break;
			default:
				throw new Core_Exception("The option '%option' doesn't allow",
					array('%option' => $args[0])
				);
		}

		return $this;
	}

	/**
	 * Remove filter condition
	 * ->removeFilter('property', 17)
	 */
	public function removeFilter()
	{
		$args = func_get_args();

		$iCountArgs = count($args);

		if ($iCountArgs < 2)
		{
			throw new Core_Exception("removeFilter() expected at least 2 arguments");
		}

		switch ($args[0])
		{
			case 'property':
				/*if ($iCountArgs < 2)
				{
					throw new Core_Exception("removeFilter('property') expected 2 arguments");
				}*/

				$property_id = $args[1];

				if (isset($this->_aFilterProperties[$property_id]))
				{
					unset($this->_aFilterProperties[$property_id]);
				}
			break;
			default:
				throw new Core_Exception("The option '%option' doesn't allow",
					array('%option' => $args[0])
				);
		}

		return $this;
	}

	/**
	 * Apply Filter
	 * @return self
	 */
	public function applyFilter()
	{
		$this->_basicFilter();

		return $this;
	}

	/**
	 * Apply Basic Filter
	 * @return self
	 */
	protected function _basicFilter()
	{
		// Filter by properties
		if (count($this->_aFilterProperties))
		{
			$aTableNames = array();

			$this->siteusers()->queryBuilder()
				->leftJoin('siteuser_properties', 'siteusers.site_id', '=', 'siteuser_properties.site_id')
				->setAnd()
				->open();

			foreach ($this->_aFilterProperties as $iPropertyId => $aTmpProperties)
			{
				foreach ($aTmpProperties as $aTmpProperty)
				{
					list($oProperty, $condition, $aPropertyValues) = $aTmpProperty;
					$tableName = $oProperty->createNewValue(0)->getTableName();

					!in_array($tableName, $aTableNames) && $aTableNames[] = $tableName;

					$this->siteusers()->queryBuilder()
						->where('siteuser_properties.property_id', '=', $oProperty->id);

					// Для строк фильтр LIKE %...%
					if ($oProperty->type == 1)
					{
						foreach ($aPropertyValues as $propertyValue)
						{
							$this->siteusers()->queryBuilder()
								->where($tableName . '.value', 'LIKE', "%{$propertyValue}%");
						}
					}
					else
					{
						// 7 - Checkbox
						$oProperty->type == 7 && $aPropertyValues[0] != '' && $aPropertyValues = array(1);

						// Not strict mode and Type is '7 - Checkbox' or '3 - List'
						$bCheckUnset = !$this->filterStrictMode
							&& $oProperty->type != 7
							&& $oProperty->type != 3;

						$bCheckUnset && $this->siteusers()->queryBuilder()->open();

						$this->siteusers()->queryBuilder()
							->where(
								$tableName . '.value',
								count($aPropertyValues) == 1 ? $condition : 'IN',
								count($aPropertyValues) == 1 ? $aPropertyValues[0] : $aPropertyValues
							);

						$bCheckUnset && $this->siteusers()->queryBuilder()
							->setOr()
							->where($tableName . '.value', 'IS', NULL)
							->close();
					}

					// Между значениями значение по AND (например, значение => 10 и значение <= 99)
					$this->siteusers()->queryBuilder()->setAnd();

					$this->_addFilterPropertyToXml($oProperty, $condition, $aPropertyValues);
				}

				// при смене свойства сравнение через OR
				$this->siteusers()->queryBuilder()->setOr();
			}

			$this->siteusers()->queryBuilder()
				->close()
				->groupBy('siteusers.id');

			foreach ($aTableNames as $tableName)
			{
				$this->siteusers()->queryBuilder()
					->leftJoin($tableName, 'siteusers.id', '=', $tableName . '.entity_id',
						array(
							array('AND' => array('siteuser_properties.property_id', '=', Core_QueryBuilder::expression($tableName . '.property_id')))
						)
					);
			}

			$havingCount = count($this->_aFilterProperties);

			$havingCount > 1
				&& $this->siteusers()->queryBuilder()
						->having(Core_Querybuilder::expression('COUNT(DISTINCT `siteuser_properties`.`property_id`)'), '=', $havingCount);
		}

		return $this;
	}

	/**
	 * Add Filter Property to the XML
	 * @param Property_Model $oProperty
	 * @param string $condition
	 * @param array $aPropertyValues
	 * @return self
	 */
	protected function _addFilterPropertyToXml($oProperty, $condition, $aPropertyValues)
	{
		switch ($condition)
		{
			case '>=':
				$xmlName = 'property_' . $oProperty->id . '_from';
			break;
			case '<=':
				$xmlName = 'property_' . $oProperty->id . '_to';
			break;
			default:
				$xmlName = 'property_' . $oProperty->id;
		}

		foreach ($aPropertyValues as $propertyValue)
		{
			switch ($oProperty->type)
			{
				case 8: // date
					$propertyValue = $propertyValue == '0000-00-00 00:00:00'
						? ''
						: Core_Date::sql2date($propertyValue);
				break;
				case 9: // datetime
					$propertyValue = $propertyValue == '0000-00-00 00:00:00'
						? ''
						: Core_Date::sql2datetime($propertyValue);
				break;
			}

			$this->addEntity(
				Core::factory('Core_Xml_Entity')
					->name($xmlName)
					->value($propertyValue)
					->addAttribute('condition', $condition)
			);
		}

		return $this;
	}

	/**
	 * Convert property value, e.g. '23.11.2020' => '2020-11-23 00:00:00'
	 * @param Property_Model $oProperty
	 * @param mixed $value
	 * @return string
	 */
	protected function _convertReceivedPropertyValue(Property_Model $oProperty, $value)
	{
		switch ($oProperty->type)
		{
			case 8: // date
				$value != ''
					&& $value = Core_Date::date2sql($value);
			break;
			case 9: // datetime
				$value != ''
					&& $value = Core_Date::datetime2sql($value);
			break;
		}

		return $value;
	}

	/**
	 * Get Filter Properties
	 * @return array
	 */
	public function getFilterProperties()
	{
		return $this->_aFilterProperties;
	}

	/**
	 * Set Filter Properties
	 * @param array $array
	 * @return self
	 */
	public function setFilterProperties(array $array)
	{
		$this->_aFilterProperties = $array;
		return $this;
	}
}