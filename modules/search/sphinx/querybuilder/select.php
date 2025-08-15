<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Sphinx Query builder. SELECT Database Abstraction Layer (DBAL)
 *
 * @package HostCMS
 * @subpackage Search
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2020 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Search_Sphinx_QueryBuilder_Select extends Core_QueryBuilder_Select
{
	/**
	 * SphinxQL OPTIONs
	 */
	protected $_options = array(
		'max_matches' => 1000
	);

	/**
	 * Set option
	 * @param string $name
	 * @param string $value, should be escaped before set!
	 * @return self
	 */
	public function option($name, $value)
	{
		$this->_options[$name] = $value;
		return $this;
	}

	/**
	 * Set max_matches OPTION
	 * @param int $max_matches
	 * @return self
	 */
	public function max_matches($max_matches)
	{
		$max_matches > 0
			&& $this->_options['max_matches'] = intval($max_matches);

		return $this;
	}

	/**
	 * Build the SQL query
	 *
	 * @return string The SQL query
	 */
	public function build()
	{
		$sql = parent::build();

		if (count($this->_options))
		{
			$aOption = array();
			foreach ($this->_options as $optionName => $optionValue)
			{
				$aOption[] = $this->_dataBase->quoteColumnName($optionName) . ' = ' . $optionValue;
			}
			$sql .= " OPTION " . implode(', ', $aOption);
		}

		return $sql;
	}
}