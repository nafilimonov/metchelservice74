<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Oauth_Yandex_Controller
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */

class Siteuser_Oauth_Yandex_Controller extends Siteuser_Oauth_Controller
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		// данные клиента
		'CLIENT_ID',
		'CLIENT_SECRET'
	);

	/**
	 * Code for exchange to access token
	 */
	public $code = NULL;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		$aConfig = Core_Config::instance()->get('siteuser_yandex', array());

		if (isset($aConfig[CURRENT_SITE]))
		{
			$this->_config = $aConfig[CURRENT_SITE];

			$this->CLIENT_ID = strval(Core_Array::get($this->_config, 'CLIENT_ID'));
			$this->CLIENT_SECRET = strval(Core_Array::get($this->_config, 'CLIENT_SECRET'));
		}
		// Old notation
		elseif (isset($aConfig['CLIENT_ID']))
		{
			$this->_config = $aConfig;

			$this->CLIENT_ID = strval(Core_Array::get($this->_config, 'CLIENT_ID'));
			$this->CLIENT_SECRET = strval(Core_Array::get($this->_config, 'CLIENT_SECRET'));
		}
		else
		{
			throw new Core_Exception("Current site config doesn`t exist!");
		}
	}

	/**
	 * Execute the business logic
	 */
	public function execute()
	{
		if (is_null($this->code))
		{
			echo "<script>location.href = 'https://oauth.yandex.ru/authorize?response_type=code&client_id={$this->CLIENT_ID}';</script>";
		}
		else
		{
			$sAnswer = @file_get_contents("https://oauth.yandex.ru/token", FALSE, stream_context_create(array(
					'http' => array(
							'method' => 'POST',
							'header' => 'Content-Type: text/plain',
							'content' => http_build_query(
								array(
									'grant_type' => 'authorization_code',
									'code' => $this->code,
									'client_id' => $this->CLIENT_ID,
									'client_secret' => $this->CLIENT_SECRET
								)
						)
					)
			)));

			if (strlen($sAnswer))
			{
				$oAccessToken = json_decode($sAnswer);

				if (isset($oAccessToken->access_token))
				{
					$sAnswer = @file_get_contents("https://login.yandex.ru/info?format=json&oauth_token={$oAccessToken->access_token}", FALSE, stream_context_create(array(
							'http' => array(
									'method' => 'GET'
								)
							)
						));

					if (strlen($sAnswer))
					{
						$oUserInfo = json_decode($sAnswer);

						return array(
							'user_id' => $oUserInfo->id,
							'email' => $oUserInfo->default_email,
							'phone' => '',
							'name' => property_exists($oUserInfo,'first_name') ? $oUserInfo->first_name : '',
							'surname' => property_exists($oUserInfo,'last_name') ? $oUserInfo->last_name : '',
							'login' => $oUserInfo->login,
							'company' => '',
							'picture' => ''
						);
					}
				}
			}

			return NULL;
		}
	}
}