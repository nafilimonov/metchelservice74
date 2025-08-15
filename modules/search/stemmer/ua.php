<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Implementation of the Porter Stemmer algorithm.
 *
 * @package HostCMS
 * @subpackage Search
 * @version 7.x
 */
class Search_Stemmer_Ua extends Search_Stemmer
{
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
			'stemmer_ua' => array()
		);

		$this->_config = $aConfig['stemmer_ua'] += array(
			// Ударные гласные
			'stressedVowels' => array('а́' => 'а', 'е́' => 'е', 'є́' => 'є', 'и́' => 'и', 'і́' => 'і', 'ї́' => 'ї', 'о́' => 'о', 'у́' => 'у', 'ю́' => 'ю', 'я́' => 'я'),
			// Исключения из правил
			'exceptions' => array('баядер', 'беатріче', 'віче', 'наче', 'неначе', 'одначе', 'паче'),
			'irregular' => array('відер' => 'відр', 'був' => 'бува'),
			// Замена окончаний
			'changeEndings' => array('аче' => 'ак', 'іче' => 'ік', 'йовував' => 'йов', 'йовувала' => 'йов', 'йовувати' => 'йов', 'ьовував' => 'ьов', 'ьовувала' => 'ьов', 'ьовувати' => 'ьов', 'цьовував' => 'ц', 'цьовувала' => 'ц', 'цьовувати' => 'ц', 'ядер' => 'ядр'),
			// Пропускать с окончаниями
			'skipEnds' => array('ер', 'ск'),
			// Окончания
			'ends' => array('а', 'ам', 'ами', 'ах', 'та', 'в', 'вав', 'вавсь', 'вався', 'вала', 'валась', 'валася', 'вали', 'вались', 'валися', 'вало', 'валось', 'валося', 'вати', 'ватись', 'ватися', 'всь', 'вся', 'е', 'еві', 'ем', 'ею', 'є', 'ємо', 'ємось', 'ємося', 'ється', 'єте', 'єтесь', 'єтеся', 'єш', 'єшся', 'єю', 'и', 'ив', 'ий', 'ила', 'или', 'ило', 'илося', 'им', 'ими', 'имо', 'имось', 'имося', 'ите', 'итесь', 'итеся', 'ити', 'ить', 'иться', 'их', 'иш', 'ишся', 'й', 'ймо', 'ймось', 'ймося', 'йсь', 'йся', 'йте', 'йтесь', 'йтеся', 'і', 'ів', 'ій', 'ім', 'імо', 'ість', 'істю', 'іть', 'ї', 'ла', 'лась', 'лася', 'ло', 'лось', 'лося', 'ли', 'лись', 'лися', 'о', 'ові', 'овував', 'овувала', 'овувати', 'ого', 'ої', 'ок', 'ом', 'ому', 'осте', 'ості', 'очка', 'очкам', 'очками', 'очках', 'очки', 'очків', 'очкові', 'очком', 'очку', 'очок', 'ою', 'ти', 'тись', 'тися', 'у', 'ував', 'увала', 'увати', 'ь', 'ці', 'ю', 'юст', 'юсь', 'юся', 'ють', 'ються', 'я', 'ям', 'ями', 'ях')
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

		$return = $word;

		if (mb_strlen($return) > 2)
		{
			// Replace stressed vowels to unstressed ones
			$return = str_replace(array_keys($this->_config['stressedVowels']), array_values($this->_config['stressedVowels']), $return);
		
			if (!in_array($return, $this->_config['exceptions']))
			{
				if (isset($this->_config['irregular'][$return]))
				{
					return $this->_config['irregular'][$return];
				}

				foreach ($this->_config['changeEndings'] as $search => $replace)
				{
					if (Core_Str::endsWith($return, $search))
					{
						return mb_substr($return, 0, mb_strlen($return) - mb_strlen($search)) . $replace;
					}
				}

				foreach ($this->_config['skipEnds'] as $search)
				{
					if (Core_Str::endsWith($return, $search))
					{
						return $return;
					}
				}

				 // try simple trim
				 foreach ($this->_config['ends'] as $search)
				 {
					if (Core_Str::endsWith($return, $search))
					{
						$sTmp = mb_substr($return, 0, mb_strlen($return) - mb_strlen($search));

						if (mb_strlen($sTmp) > 2)
						{
							return $sTmp;
						}
					}
				 }
			}
		}

		return $return;
	}
}