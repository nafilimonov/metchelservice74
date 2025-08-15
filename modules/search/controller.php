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
class Search_Controller
{
	/**
	 * The singleton instances.
	 * @var mixed
	 */
	static public $instance = array();

	/**
	 * Register an existing instance as a singleton.
	 * @param string $name driver's name
	 * @return object
	 */
	static public function instance($name = 'default')
	{
		if (!is_string($name))
		{
			throw new Core_Exception('Wrong argument type (expected String)');
		}

		if (!isset(self::$instance[$name]))
		{
			$aConfig = Core::$config->get('search_config', array()) + array(
				'default' => array(
					'driver' => 'hostcms'
				)
			);

			if (!isset($aConfig[$name]))
			{
				throw new Core_Exception("Search configuration '%driverName' doesn't defined.", array('%driverName' => $name));
			}

			$aConfigDriver = defined('CURRENT_SITE') && isset($aConfig[$name][CURRENT_SITE])
				? $aConfig[$name][CURRENT_SITE]
				: $aConfig[$name];

			if (!isset($aConfigDriver['driver']))
			{
				throw new Core_Exception("Driver configuration '%driverName' doesn't defined.", array('%driverName' => $name));
			}

			$driver = self::_getDriverName($aConfigDriver['driver']);

			self::$instance[$name] = new $driver($aConfigDriver);
		}

		return self::$instance[$name];
	}

	/**
	 * Get full driver name
	 * @param string $driver driver name
	 * @return srting
	 */
	static protected function _getDriverName($driver)
	{
		return __CLASS__ . '_' . ucfirst($driver);
	}

	/**
	 * Check is $word English
	 * @param string $word
	 */
	static public function isEnglish($word)
	{
		return preg_match('/[a-z]/u', $word);
	}

	/**
	 * Check is $word Russian
	 * @param string $word
	 */
	static public function isRussian($word)
	{
		return preg_match('/[а-яё]/u', $word);
	}

	/**
	 * Check is $word Ukrainian
	 * @param string $word
	 */
	static public function isUkrainian($word)
	{
		return preg_match('/[ґєї]/u', $word);
	}

	/**
	 * Get hash from $text
	 * @param string $text source text
	 * @param array $param list of hash params
	 * @return array
	 */
	static public function getHashes($text, $param = array())
	{
		if (!isset($param['hash_function']))
		{
			$param['hash_function'] = 'md5';
		}

		// Max string length for explode
		$iMaxLen = 5120;

		// Minimize text before Core_Str::getHashes
		$text = strip_tags($text, '<p><div><br>');

		$iTextLen = is_scalar($text) ? mb_strlen($text) : 0;

		$result = array();

		if ($iTextLen)
		{
			do {
				if ($iTextLen < $iMaxLen)
				{
					$iMaxLen = $iTextLen;
				}

				$iStrCut = mb_strpos($text, ' ', $iMaxLen);

				if ($iStrCut === FALSE)
				{
					$iStrCut = $iTextLen;
				}

				$subStr = mb_substr($text, 0, $iStrCut);
				$text = mb_substr($text, $iStrCut);

				$aText = Core_Str::getHashes($subStr, array('hash_function' => ''));

				$bUkrainian = self::isUkrainian($subStr);

				foreach ($aText as $res)
				{
					// ии => и не преобразовываем
					if (mb_strlen($res) > 2)
					{
						$word = self::isRussian($res)
							? ($bUkrainian
								? Search_Stemmer::instance('ua')->stem($res)
								: Search_Stemmer::instance('ru')->stem($res)
							)
							: Search_Stemmer::instance('en')->stem($res);
					}
					else
					{
						$word = $res;
					}

					switch ($param['hash_function'])
					{
						case '':
							$result[] = $word;
						break;
						default:
						case 'md5':
							$result[] = md5($word);
						break;
						case 'crc32':
							$result[] = Core::crc32($word);
						break;
					}
				}
			} while ($iTextLen = mb_strlen($text));
		}

		return $result;
	}

	/**
	 * Spell check for Russian and English
	 * @param string $word
	 * @return string
	 */
	static public function spellCheck($word)
	{
		$word = mb_strtolower($word);
		$len = mb_strlen($word);

		$newWord = NULL;

		if (self::isEnglish($word))
		{
			// https://github.com/elitejake/Moby-Project
			$model = 'Search_Dictionary_En';
		}
		elseif (self::isRussian($word) && !self::isUkrainian($word))
		{
			// https://github.com/danakt/russian-words
			$model = 'Search_Dictionary_Ru';
		}
		else
		{
			$model = NULL;
		}

		if (!is_null($model) && $len > 2)
		{
			$limitDic = 500;
			$offsetDic = 0;

			$maxPercent = 0;

			$expectedPercent = ($len - 2) / $len;

			do {
				$oSearch_Dictionaries = Core_Entity::factory($model);
				$oSearch_Dictionaries->queryBuilder()
					->where('first_char', '=', mb_substr($word, 0, 1))
					->where('len', '=', $len)
					->limit($limitDic)
					->offset($offsetDic);

				$aSearch_Dictionaries = $oSearch_Dictionaries->findAll(FALSE);
				
				foreach ($aSearch_Dictionaries as $oSearch_Dictionary)
				{
					similar_text($word, $oSearch_Dictionary->word, $percent);

					if ($percent > $expectedPercent && $percent > $maxPercent)
					{
						// Слово корректное
						if ($percent == 100)
						{
							$newWord = NULL;
							$maxPercent = 0;
							break;
						}
						$maxPercent = $percent;
						$newWord = $oSearch_Dictionary->word;
						//echo "<br>{$word} => {$oSearch_Dictionary->word}, {$percent}";
					}
				}
				$offsetDic += $limitDic;
			} while(count($aSearch_Dictionaries) === $limitDic);
		}

		return !is_null($newWord) ? $newWord : $word;
	}

	/**
	 * Indexing search pages
	 * @param array $aSearchPages list of search pages
	 * @return boolean
	 */
	static public function indexingSearchPages(array $aSearchPages)
	{
		return self::instance()->indexingSearchPages($aSearchPages);
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
	static public function deleteSearchPage($site_id, $module, $module_value_type, $module_value_id)
	{
		return self::instance()->deleteSearchPage($site_id, $module, $module_value_type, $module_value_id);
	}
}