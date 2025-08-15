<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Lead_Controller.
 *
 * @package HostCMS
 * @subpackage Lead
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Lead_Controller
{
	/**
	 * Backend property
	 * @var string
	 */
	static public $allowTags = '<html><head><body><style><a><b><strong><img><em><i><span><small><div><p><br><br/><br /><li><ol><ul><pre><table><tr><td><tbody><thead><h1><h2><h3><h4><h5><h6>';

	/**
	 * Create lead from email
	 * @param array $aMessage message
	 * @param integer $crm_source_id
	 * @return self
	 */
	static public function createLeadFromEmail(array $aMessage, $crm_source_id = 0)
	{
		if (isset($aMessage['from']) && $aMessage['from'] != '')
		{
			$oLead = self::_findLead($aMessage['from']);

			if (is_null($oLead))
			{
				$datetime = isset($aMessage['date'])
					? Core_Date::datetime2sql($aMessage['date'])
					: Core_Date::timestamp2sql(time());

				$oLead = Core_Entity::factory('Lead');
				$oLead->datetime = $datetime;
				$oLead->crm_source_id = $crm_source_id;

				if (strstr(mb_strtolower($aMessage['type']), 'html') !== FALSE
					|| strstr(mb_strtolower($aMessage['subtype']), 'html') !== FALSE
					|| (isset($aMessage['structure_array']['parts'][0]['type']) && strstr(mb_strtolower($aMessage['structure_array']['parts'][0]['type']), 'html') !== FALSE)
					|| (isset($aMessage['structure_array']['parts'][0]['subtype']) && strstr(mb_strtolower($aMessage['structure_array']['parts'][0]['subtype']), 'html') !== FALSE)
				)
				{
					// тип - html
					$aMessage['body'] = preg_replace('/<!DOCTYPE[^>]*>/i', '', $aMessage['body']);

					$text = Core_Str::stripTags($aMessage['body'], self::$allowTags);
				}
				else
				{
					// тип - text
					$text = $aMessage['body'];
				}

				$text = Core_Str::deleteIllegalCharacters($text);
				$text = Core_Str::removeEmoji($text);
				$oLead->comment = Core_Str::convertHtmlToText($text);
				$oLead->save();

				// Add e-mail
				$oDirectory_Email_Type = Core_Entity::factory('Directory_Email_Type')->getFirst();

				$oDirectory_Email = Core_Entity::factory('Directory_Email')
					->directory_email_type_id($oDirectory_Email_Type->id)
					->public(0)
					->value($aMessage['from'])
					->save();

				$oLead->add($oDirectory_Email);
			}
		}
	}

	/**
	 * Find lead
	 * @param string $email
	 * @return object|NULL
	 */
	static protected function _findLead($email)
	{
		$oSite = Core_Entity::factory('Site', CURRENT_SITE);

		if (Core::moduleIsActive('siteuser'))
		{
			$oSiteuser = $oSite->Siteusers->getByEmail($email);

			if (!is_null($oSiteuser))
			{
				return $oSiteuser;
			}
		}

		$oLeads = Core_Entity::factory('Lead');
		$oLeads->queryBuilder()
			->select('leads.*')
			->join('lead_directory_emails', 'leads.id', '=', 'lead_directory_emails.lead_id')
			->join('directory_emails', 'lead_directory_emails.directory_email_id', '=', 'directory_emails.id')
			->where('leads.site_id', '=', $oSite->id)
			->where('directory_emails.value', '=', $email)
			->limit(1);

		$aLeads = $oLeads->findAll(FALSE);

		return isset($aLeads[0])
			? $aLeads[0]
			: NULL;
	}

	/**
	 * Get Lead by phone
	 * @param string $phone
	 * @param boolean $bCache
	 * @return array
	 */
	static public function getLeadsByPhone($phone, $bCache = TRUE)
	{
		$oLeads = Core_Entity::factory('Lead');
		$oLeads->queryBuilder()
			->join('lead_directory_phones', 'leads.id', '=', 'lead_directory_phones.lead_id')
			->join('directory_phones', 'lead_directory_phones.directory_phone_id', '=', 'directory_phones.id')
			->where('directory_phones.value', '=', Directory_Phone_Controller::format($phone))
			->groupBy('leads.id');

		return $oLeads->findAll($bCache);
	}
}