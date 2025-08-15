<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Bot_Send_Email
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2020 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_Bot_Send_Email extends Bot_Controller
{
	/**
	 * Bot module color
	 * @var string
	 */
	protected $_color = '#e75b8d';

	/**
	 * Get bot module fields
	 * @return array
	 */
	public function getFields()
	{
		$this->_fields = array(
			'from' => array(
				'caption' => Core::_('Siteuser.from'),
				'type' => 'input',
				'value' => FALSE,
				'obligatory' => TRUE
			),
			'sender-name' => array(
				'caption' => Core::_('Siteuser.sender_name'),
				'type' => 'input',
				'value' => FALSE,
				'obligatory' => TRUE
			),
			'theme' => array(
				'caption' => Core::_('Siteuser.theme'),
				'type' => 'input',
				'value' => FALSE,
				'obligatory' => TRUE
			),
			'text' => array(
				'caption' => Core::_('Siteuser.text'),
				'type' => 'wysiwyg',
				'value' => FALSE,
				'obligatory' => TRUE
			)
		);

		return parent::getFields();
	}

	/**
	 * Check available
	 */
	public function available()
	{
		return Core::moduleIsActive('siteuser');
	}

	/**
	 * Execute business logic
	 */
	public function execute()
	{
		$aSettings = json_decode($this->_oBot_Module->json, TRUE);

		$sFrom = isset($aSettings['from']) && strlen(trim($aSettings['from']))
			? $aSettings['from']
			: NULL;

		$sSenderName = isset($aSettings['sender-name']) && strlen(trim($aSettings['sender-name']))
			? $aSettings['sender-name']
			: NULL;

		$oSiteuser = $this->_oObject->siteuser_id
			? $this->_oObject->Siteuser
			: NULL;

		if (!is_null($oSiteuser))
		{
			$oCore_Meta = new Core_Meta();
			$oCore_Meta
				->addObject('siteuser', $oSiteuser)
				->addObject('object', $this->_oObject)
				->addObject('settings', $aSettings);

			$sSubject = isset($aSettings['theme']) && strlen(trim($aSettings['theme']))
				? $oCore_Meta->apply($aSettings['theme'])
				: Core::_('Admin_Form.non_subject');

			$sMessage = isset($aSettings['text'])
				? $oCore_Meta->apply($aSettings['text'])
				: NULL;

			$email = strlen($oSiteuser->email)
				? htmlspecialchars($oSiteuser->email)
				: NULL;

			if (!is_null($email))
			{
				Core_Mail::instance()
					->clear()
					->to($email)
					->from($sFrom)
					->senderName($sSenderName)
					->subject($sSubject)
					->message($sMessage)
					->contentType('text/html')
					->header('X-HostCMS-Reason', 'Siteuser Bot Send Email')
					->header('Precedence', 'bulk')
					->send();

				Core_Log::instance()->clear()
					->status(Core_Log::$SUCCESS)
					->write("Siteuser_Bot_Send_Email: mail sent to siteuser id: {$oSiteuser->id}, email: {$email}");
			}
		}
	}
}