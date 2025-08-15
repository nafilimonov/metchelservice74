<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Support_Model
 *
 * @package HostCMS
 * @subpackage Support
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2024 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Support_Model extends Core_Entity
{
	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'user' => array()
	);

	/**
	 * Constructor.
	 * @param int $id entity ID
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		if (is_null($id) && !$this->loaded())
		{
			$oUser = Core_Auth::getCurrentUser();
			$this->_preloadValues['user_id'] = is_null($oUser) ? 0 : $oUser->id;
			$this->_preloadValues['datetime'] = Core_Date::timestamp2sql(time());
		}
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function imgBackend()
	{
		return $this->user_id
			? $this->User->smallAvatar()
			: '';
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function textBackend()
	{
		return nl2br(htmlspecialchars((string) $this->text));
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function ticket_idBackend()
	{
		if ($this->ticket_id)
		{
			$color = '#a0d468';

			$number = ltrim($this->ticket_id, 0);

			?><span class="badge badge-round badge-max-width margin-left-5" title="<?php echo Core::_('Support.ticket_id')?>" style="border-color: <?php echo $color?>; color: <?php echo Core_Str::hex2darker($color, 0.2)?>; background-color:<?php echo Core_Str::hex2lighter($color, 0.88)?>"><a target="_blank" href="https://www.hostcms.ru/users/helpdesk/ticket-<?php echo htmlspecialchars($number)?>/" style="color: <?php echo Core_Str::hex2darker($color, 0.2)?>;"><?php echo htmlspecialchars($this->ticket_id)?></a></span><?php
		}
		else
		{
			$notSendColor = '#ed4e2a';

			?><span class="badge badge-round badge-max-width margin-left-5" title="<?php echo Core::_('Hostcms_Ozon_Shop.in_stock')?>" style="border-color: <?php echo $notSendColor?>; color: <?php echo Core_Str::hex2darker($notSendColor, 0.2)?>; background-color:<?php echo Core_Str::hex2lighter($notSendColor, 0.88)?>"><?php echo Core::_('Support.not_send')?></span><?php
		}
	}
}