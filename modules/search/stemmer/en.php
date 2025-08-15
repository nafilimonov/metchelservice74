<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Implementation of the Porter Stemmer algorithm.
 * http://tartarus.org/~martin/PorterStemmer/php.txt
 *
 * Copyright © 2005 Richard Heyes (http://www.phpguru.org/), Jon Abernathy, Hostmake LLC
 * @package HostCMS
 * @subpackage Search
 * @version 7.x
 */
class Search_Stemmer_En extends Search_Stemmer
{
	/**
	 * Регулярное выражение для совпадающих согласных
	 * @var string
	 */
	protected $_consonant = '(?:[bcdfghjklmnpqrstvwxz]|(?<=[aeiou])y|^y)';

	/**
	 * Регулярное выражение для совпадающих гласных
	 * @var string
	 */
	protected $_vowel = '(?:[aeiou]|(?<![aeiou])y)';

	/**
	 * Определение основы слова
	 *
	 * @param string $word слово
	 * @return string основа слова
	 */
	public function stem($word)
	{
		if (strlen($word) <= 2)
		{
			return $word;
		}

		$word = $this->step1ab($word);
		$word = $this->step1c($word);
		$word = $this->step2($word);
		$word = $this->step3($word);
		$word = $this->step4($word);
		$word = $this->step5($word);

		return $word;
	}

	/**
	 * Шаг 1.
	 * @param string $word word
	 * @return string
	 */
	protected function step1ab($word)
	{
		// Часть a)
		if (substr($word, -1) == 's')
		{
			$this->replace($word, 'sses', 'ss')
				|| $this->replace($word, 'ies', 'i')
				|| $this->replace($word, 'ss', 'ss')
				|| $this->replace($word, 's', '');
		}

		// Часть b)
		if (substr($word, -2, 1) != 'e' || !$this->replace($word, 'eed', 'ee', 0))
		{
			// Первое правило.
			$v = $this->_vowel;

			// ing and ed
			if (preg_match("/$v+/", substr($word, 0, -3)) && $this->replace($word, 'ing', '')
				|| preg_match("/$v+/", substr($word, 0, -2)) && $this->replace($word, 'ed', ''))
			{
				// Если 1 или больше двух, то истина.
				if (!$this->replace($word, 'at', 'ate')
				&& !$this->replace($word, 'bl', 'ble')
				&& !$this->replace($word, 'iz', 'ize'))
				{
					// Окончание с двумя согласными.
					if ($this->doubleConsonant($word)
					&& substr($word, -2) != 'll'
					&& substr($word, -2) != 'ss'
					&& substr($word, -2) != 'zz')
					{
						$word = substr($word, 0, -1);
					}
					elseif ($this->_getSyllables($word) == 1 && $this->cvc($word))
					{
						$word .= 'e';
					}
				}
			}
		}

		return $word;
	}

	/**
	 * Шаг 1 c)
	 *
	 * @param string $word
	 */
	protected function step1c($word)
	{
		$v = $this->_vowel;

		if (substr($word, -1) == 'y' && preg_match("/{$v}+/", substr($word, 0, -1)))
		{
			$this->replace($word, 'y', 'i');
		}

		return $word;
	}

	/**
	 * Шаг 2
	 *
	 * @param string $word
	 */
	protected function step2($word)
	{
		switch (substr($word, -2, 1))
		{
			case 'a':
				$this->replace($word, 'ational', 'ate', 0)
				|| $this->replace($word, 'tional', 'tion', 0);
				break;

			case 'c':
				$this->replace($word, 'enci', 'ence', 0)
				|| $this->replace($word, 'anci', 'ance', 0);
				break;

			case 'e':
				$this->replace($word, 'izer', 'ize', 0);
				break;

			case 'g':
				$this->replace($word, 'logi', 'log', 0);
				break;

			case 'l':
				$this->replace($word, 'entli', 'ent', 0)
				|| $this->replace($word, 'ousli', 'ous', 0)
				|| $this->replace($word, 'alli', 'al', 0)
				|| $this->replace($word, 'bli', 'ble', 0)
				|| $this->replace($word, 'eli', 'e', 0);
				break;

			case 'o':
				$this->replace($word, 'ization', 'ize', 0)
				|| $this->replace($word, 'ation', 'ate', 0)
				|| $this->replace($word, 'ator', 'ate', 0);
				break;

			case 's':
				$this->replace($word, 'iveness', 'ive', 0)
				|| $this->replace($word, 'fulness', 'ful', 0)
				|| $this->replace($word, 'ousness', 'ous', 0)
				|| $this->replace($word, 'alism', 'al', 0);
				break;

			case 't':
				$this->replace($word, 'biliti', 'ble', 0)
				|| $this->replace($word, 'aliti', 'al', 0)
				|| $this->replace($word, 'iviti', 'ive', 0);
				break;
		}

		return $word;
	}

	/**
	 * Шаг 3
	 * @param string $word
	 */
	protected function step3($word)
	{
		switch (substr($word, -2, 1))
		{
			case 'a':
				$this->replace($word, 'ical', 'ic', 0);
				break;

			case 's':
				$this->replace($word, 'ness', '', 0);
				break;

			case 't':
				$this->replace($word, 'icate', 'ic', 0)
				|| $this->replace($word, 'iciti', 'ic', 0);
				break;

			case 'u':
				$this->replace($word, 'ful', '', 0);
				break;

			case 'v':
				$this->replace($word, 'ative', '', 0);
				break;

			case 'z':
				$this->replace($word, 'alize', 'al', 0);
				break;
		}

		return $word;
	}

	/**
	 * Шаг 4
	 * @param string $word
	 */
	protected function step4($word)
	{
		switch (substr($word, -2, 1))
		{
			case 'a':
				$this->replace($word, 'al', '', 1);
				break;

			case 'c':
				$this->replace($word, 'ance', '', 1)
				OR $this->replace($word, 'ence', '', 1);
				break;

			case 'e':
				$this->replace($word, 'er', '', 1);
				break;

			case 'i':
				$this->replace($word, 'ic', '', 1);
				break;

			case 'l':
				$this->replace($word, 'able', '', 1)
				|| $this->replace($word, 'ible', '', 1);
				break;

			case 'n':
				$this->replace($word, 'ant', '', 1)
				|| $this->replace($word, 'ement', '', 1)
				|| $this->replace($word, 'ment', '', 1)
				|| $this->replace($word, 'ent', '', 1);
				break;

			case 'o':
				if (substr($word, -4) == 'tion' || substr($word, -4) == 'sion')
				{
					$this->replace($word, 'ion', '', 1);
				} else
				{
					$this->replace($word, 'ou', '', 1);
				}
				break;

			case 's':
				$this->replace($word, 'ism', '', 1);
				break;

			case 't':
				$this->replace($word, 'ate', '', 1)
				|| $this->replace($word, 'iti', '', 1);
				break;

			case 'u':
				$this->replace($word, 'ous', '', 1);
				break;

			case 'v':
				$this->replace($word, 'ive', '', 1);
				break;

			case 'z':
				$this->replace($word, 'ize', '', 1);
				break;
		}

		return $word;
	}

	/**
	 * Step 5
	 * @param string $word Word to stem
	 */
	protected function step5($word)
	{
		// Часть 1
		if (substr($word, -1) == 'e') {
			if ($this->_getSyllables(substr($word, 0, -1)) > 1) {
				$this->replace($word, 'e', '');

			} else if ($this->_getSyllables(substr($word, 0, -1)) == 1) {

				if (!$this->cvc(substr($word, 0, -1))) {
					$this->replace($word, 'e', '');
				}
			}
		}

		// Часть 2
		if ($this->_getSyllables($word) > 1 && $this->doubleConsonant($word) && substr($word, -1) == 'l')
		{
			$word = substr($word, 0, -1);
		}

		return $word;
	}

	/**
	 * Заменяет первую строку второй, начиная с конца строки. Если задан третий аргумент, то исходная строка должна совпадать по крайней мере с $m.
	 * @param string $str Строка для проверки
	 * @param string $check Что проверяется
	 * @param string $repl Строка замены
	 * @param int $m Необязательный. Минимальное число соответствий _getSyllables()
	 * @return bool Была ли строка $check в окончании строки $str. True не всегда означает, что строка была заменена.
	 */
	protected function replace(&$str, $check, $repl, $m = null)
	{
		$len = 0 - strlen($check);

		if (substr($str, $len) == $check)
		{
			$substr = substr($str, 0, $len);
			if (is_null($m) || $this->_getSyllables($substr) > $m)
			{
				$str = $substr . $repl;
			}

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Возвращает количество согласных последовательностей в $str.
	 * Если c это согласная последовательность, а v - гласная, то
	 * <c><v> возвращает 0
	 * <c>vc<v> возвращает 1
	 * <c>vcvc<v> возвращает 2
	 * <c>vcvcvc<v> возвращает 3
	 *
	 * @param string $str строка
	 * @return int
	 */
	protected function _getSyllables($str)
	{
		$str != ''
			&& $str = preg_replace("/^{$this->_consonant}+/u", '', $str);

		$str != ''
			&& 	$str = preg_replace("/{$this->_vowel}+$/u", '', $str);

		$str != ''
			? preg_match_all("/({$this->_vowel}+{$this->_consonant}+)/u", $str, $matches)
			: $matches[1] = array();

		return count($matches[1]);
	}

	/**
	 * Содержит ли строка две подряд одинаковые согласные буквы в конце строки.
	 *
	 * @param string $str
	 * @return bool
	 */
	protected function doubleConsonant($str)
	{
		return preg_match('/' . $this->_consonant . '{2}$/', $str, $matches) && $matches[0][0] == $matches[0][1];
	}

	/**
	 * Проверяем на окончание последовательностью CVC, где
	 * вторая C (т.е. согласная) не W, X или Y.
	 *
	 * @param string $str
	 * @return bool
	 */
	protected function cvc($str)
	{
		return preg_match('/(' . $this->_consonant . $this->_vowel . $this->_consonant . ')$/u', $str, $matches)
			&& strlen($matches[1]) == 3
				&& $matches[1][2] != 'w' && $matches[1][2] != 'x' && $matches[1][2] != 'y';
	}
}