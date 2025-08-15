<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Eventlog.
 *
 * @package HostCMS
 * @subpackage Eventlog
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Eventlog_Event
{
	/**
	 * Backend property
	 * @var mixed
	 */
	public $id = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $datetime = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $login = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $event = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $status = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $site = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $page = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $fullData = NULL;

	/**
	 * Backend property
	 * @var mixed
	 */
	public $ip = NULL;

	/**
	 * List of images
	 * @var array
	 */
	static protected $_img = array(
		0 => 'bullet_black.gif',
		1 => 'bullet_green.gif',
		2 => 'bullet_orange.gif',
		3 => 'bullet_pink.gif',
		4 => 'bullet_red.gif',
	);

	/**
	 * Backend callback method
	 * @return string
	 */
	public function fullDataBackend()
	{
		ob_start();

		strpos($this->page, 'http') === 0
			? Core_Html_Entity::factory('A')
				->href($this->page)
				->target('_blank')
				->value(htmlspecialchars(Core_Str::cut($this->page, 50)))
				->execute()
			: Core_Html_Entity::factory('Span')
				->value(htmlspecialchars(Core_Str::cut($this->page, 50)))
				->execute();

		Core_Html_Entity::factory('Br')->execute();

		Core_Html_Entity::factory('Span')
			->value(htmlspecialchars(($this->login != '' ? $this->login . ' ' : '') . ' [' . $this->ip . ']'))
			->execute();

		return ob_get_clean();
	}

	static protected function _getStatuses()
	{
		return array(
			0 => Core::_('Eventlog.form_show_neutral'),
			1 => Core::_('Eventlog.form_show_successful'),
			2 => Core::_('Eventlog.form_show_low_criticality'),
			3 => Core::_('Eventlog.form_show_middle_criticality'),
			4 => Core::_('Eventlog.form_show_highes_criticality')
		);
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function statusBackend()
	{
		$img = isset(self::$_img[$this->status])
			? self::$_img[$this->status]
			: self::$_img[0];

		return Admin_Form_Controller::correctBackendPath("<img src=/{admin}/images/{$img}>");
	}

	/**
	 * Backend function
	 * @param mixed $value value
	 * @param Admin_Form_Field $oAdmin_Form_Field field
	 * @return string
	 */
	static public function eventFilter($value, $oAdmin_Form_Field, $filterPrefix)
	{
		ob_start();

		Core_Html_Entity::factory('Select')
			->options(
				array(
					-1 => Core::_('Eventlog.form_show_all_events'),
				) + self::_getStatuses()
			)
			->value($value == '' ? -1 : intval($value))
			->name($filterPrefix . $oAdmin_Form_Field->id)
			->execute();

		return ob_get_clean();
	}

	/**
	 * Backend function
	 * @param string $date_from
	 * @param string $date_to
	 * @param Admin_Form_Field $oAdmin_Form_Field field
	 * @return string
	 */
	static public function datetimeFilter($date_from, $date_to, $oAdmin_Form_Field, $filterPrefix)
	{
		ob_start();

		Core_Html_Entity::factory('Div')
			->class('input-group date')
			->add(
				Core_Html_Entity::factory('Input')
					->value(htmlspecialchars((string) $date_from))
					->name($filterPrefix . 'from_' . $oAdmin_Form_Field->id)
					->id($filterPrefix . 'from_' . $oAdmin_Form_Field->id)
					->size(8)
					->class('form-control input-sm')
			)
			->execute();

		$sCurrentLng = Core_I18n::instance()->getLng();

		Core_Html_Entity::factory('Script')
			->value("(function($) {
				$('#{$filterPrefix}from_{$oAdmin_Form_Field->id}')
					.datetimepicker({locale: '{$sCurrentLng}', format: '" . Core::$mainConfig['datePickerFormat'] . "'})
					.on('dp.show', datetimepickerOnShow);
			})(jQuery);")
			->execute();

		return ob_get_clean();
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function eventBackend()
	{
		$event = trim($this->event);

		if (strlen($event) != 0)
		{
			ob_start();

			Core_Html_Entity::factory('Div')
				->class('modalwindow')
				->value(nl2br(
					htmlspecialchars($event)
					. PHP_EOL . PHP_EOL
					. ($this->login != '' ? '<b>' . htmlspecialchars($this->login) . '</b>, ' : '')
					. htmlspecialchars(Core_Date::sql2datetime($this->datetime))
				))
				->execute();
			$content = ob_get_clean();

			ob_start();

			$text = htmlspecialchars(Core_Str::cut($event, 512));

			$height = floor(mb_strlen($text) * 0.6);
			$height < 150 && $height = 150;
			$height > 450 && $height = 450;

			Core_Html_Entity::factory('Div')
				->class('eventlog')
				->add(
					Core_Html_Entity::factory('Span')
						->class('pointer')
						->onclick("$.showWindow('eventLog{$this->id}', '" . Core_Str::escapeJavascriptVariable($content) . "', {width: 650, height: {$height}, title: '" . Core::_('Eventlog.detailed_event_info') . "', className: 'eventlog-item', Maximize: false})")
						->value(nl2br($text))
				)
				->execute();

			return ob_get_clean();
		}

		return '&nbsp;';
	}
}