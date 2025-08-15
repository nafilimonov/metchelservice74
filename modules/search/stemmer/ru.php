<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Implementation of the Porter Stemmer algorithm.
 *
 * http://snowball.tartarus.org/algorithms/russian/stemmer.html
 * @package HostCMS
 * @subpackage Search
 * @version 7.x
 */
class Search_Stemmer_Ru extends Search_Stemmer
{
	/**
	 * Разрешает кэширование
	 *
	 * @var boolean
	 * @access protected
	 */
	protected $_useCache = TRUE;

	/**
	 * Cache
	 *
	 * @var array
	 * @access protected
	 */
	protected $_cache = array();

	/**
	 * Config
	 *
	 * @var array
	 * @access protected
	 */
	protected $_config = array();

	public function __construct()
	{
		$aConfig = Core::$config->get('search_config', array()) + array(
			'stemmer_ru' => array()
		);

		$this->_config = $aConfig['stemmer_ru'] += array(
			// Регулярное выражение для гласных
			'vowels' => '/аеиоуыэюя/u',
			// Регулярное выражение для словообразующей части деепричастий
			'perfectiveGround' => '/((ив|ивши|ившись|ыв|ывши|ывшись)|((?<=[ая])(в|вши|вшись)))$/u',
			// Регулярное выражение для возвратных суффиксов
			'reflexive' => '/(с[яь])$/u',
			// Регулярное выражение для окончаний прилагательных
			'adjective' => '/(ее|ие|ые|ое|ими|ыми|ей|ий|ый|ой|ем|им|ым|ом|его|ого|ему|ому|их|ых|ую|юю|ая|яя|ою|ею)$/u',
			// Регулярное выражение для суффиксов прилагательных
			//'adjective_suffix' => '/(ал|ел|ан|ян|аст|ев|ов|еват|оват|ен|енн|енск|инск|лив|чив|ив|ин|овит|ит|шн|тельн|уч|чат|еньк|оньк|ехоньк|оханьк|ешеньк|ошеньк)$/u',
			// Регулярное выражение для словообразующей части причастий
			'participle' => '/((ивш|ывш|ующ)|((?<=[ая])(ем|нн|вш|ющ|щ)))$/u',
			// Регулярное выражение для окончаний глаголов
			'verb' => '/((ила|ыла|ена|ейте|уйте|ите|или|ыли|ей|уй|ил|ыл|им|ым|ен|ило|ыло|ено|ят|ует|уют|ит|ыт|ены|ить|ыть|ишь|ую|ю)|((?<=[ая])(ла|на|ете|йте|ли|й|л|ем|н|ло|но|ет|ют|ны|ть|ешь|нно)))$/u',
			// Регулярное выражение для суффиксов глаголов
			'verb_suffix' => '/(ка|ева|ова|ыва|ива|нича|ну|ствова|ть|ти|ирова)$/u',
			// Регулярное выражение для окончаний существительных
			'noun' => '/(а|ев|ов|ева|ова|ие|ье|е|иями|ями|ами|еи|ии|и|ией|ей|ой|ий|й|иям|ям|ием|ем|ам|ом|о|у|ах|иях|ях|ы|ь|ию|ью|ю|ия|ья|я)$/u',
			// Регулярное выражение для суффиксов существительных
			'noun_suffix' => '/(иров|я|ян|анин|янин|ач|ени|ет|еств|есть|ец|ца|изм|изн|ик|ник|ин|ист|тель|их|иц|ниц|льник|льщик|льщиц|ни|ун|чик|чиц|щик|еньк|оньк|енк|онк|ашк|ищ|ок|инк|очк|ушк|юшк|ышк|ишк|очек|ечк|ушек|ышек|ыш)$/u',
			// RV is the region after the first vowel, or the end of the word if it contains no vowel.
			'rv' => '/^(.*?[аеиоуыэюя])(.*)$/u',
			// Существительные, образованные от основ прилагательных, суфиксом -ость
			'derivational' => '/[^аеиоуыэюя][аеиоуыэюя]+[^аеиоуыэюя]+[аеиоуыэюя].*(?<=о)сть?$/u'
		);
	}

	/**
	 * Определение основы слова
	 *
	 * @param string $word слово
	 * @return string основа слова
	 */
	public function stem($word)
	{
		$word = mb_strtolower($word);

		// Проверка на наличие в кэше
		if ($this->_useCache && isset($this->_cache[$word]))
		{
			return $this->_cache[$word];
		}

		$return = $word;

		if (preg_match($this->_config['rv'], $word, $matches))
		{
			$start = $matches[1];
			$afterFirstVowel = $matches[2];

			if ($afterFirstVowel)
			{
				// Шаг 1
				if (!$this->_applyPattern($afterFirstVowel, $this->_config['perfectiveGround']))
				{
					$this->_applyPattern($afterFirstVowel, $this->_config['reflexive']);

					if ($this->_applyPattern($afterFirstVowel, $this->_config['adjective']))
					{
						//$this->_applyPattern($afterFirstVowel, $this->_config['adjective_suffix']);
						$this->_applyPattern($afterFirstVowel, $this->_config['participle']);
					}
					else
					{
						// Если $afterFirstVowel больше 3, только тогда работаем с окончаниями глаголов
						if (mb_strlen($afterFirstVowel) > 3 && $this->_applyPattern($afterFirstVowel, $this->_config['verb']))
						{
							$this->_applyPattern($afterFirstVowel, $this->_config['verb_suffix']);
						}
						else
						{
							$this->_applyPattern($afterFirstVowel, $this->_config['noun']);
							$this->_applyPattern($afterFirstVowel, $this->_config['noun_suffix']);
						}
					}
				}

				// Шаг 2
				$this->_applyPattern($afterFirstVowel, '/и$/u');

				// Шаг 3
				if (preg_match($this->_config['derivational'], $afterFirstVowel))
				{
					$this->_applyPattern($afterFirstVowel, '/ость?$/u');
				}

				// Шаг 4
				if (!$this->_applyPattern($afterFirstVowel, '/ь$/u'))
				{
					$this->_applyPattern($afterFirstVowel, '/ейше?/u');
					$this->_applyPattern($afterFirstVowel, '/нн$/u', 'н');
				}

				$return = $start . $afterFirstVowel;
			}
		}

		if ($this->_useCache)
		{
			$this->_cache[$word] = $return;
		}

		return $return;
	}

	/**
	 * Clear cache
	 * @return self
	 */
	public function clearCache()
	{
		$this->_cache = array();
		return $this;
	}

	/**
	 * Метод проверяет строку на наличие вхождений подстрок заданных регулярным выражением
	 *
	 * @param string $text обрабатываемая строка
	 * @param string $pattern регулярное выражение
	 * @param string $replace текст замены
	 * @return boolean была замена
	 * @access private
	 */
	protected function _applyPattern(&$text, $pattern, $replace = '')
	{
		$text = preg_replace($pattern, $replace, $text, -1 , $count);
		return $count > 0;
	}
}