<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Oauth_Facebook_Controller
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */

class Siteuser_Oauth_Facebook_Controller extends Siteuser_Oauth_Controller
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		// данные клиента
		'APPLICATION_ID',
		'APPLICATION_SECRET',
		'CALLBACK_URL'
	);

	/**
	 * Code for exchange to access token
	 * @var string
	 */
	public $code = NULL;

	/**
	 * API version
	 * @var string
	 */
	protected $_version = 'v13.0';

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		$aConfig = Core_Config::instance()->get('siteuser_facebook', array());

		if (isset($aConfig[CURRENT_SITE]))
		{
			$this->_config = $aConfig[CURRENT_SITE];

			$this->APPLICATION_ID = strval(Core_Array::get($this->_config, 'APPLICATION_ID'));
			$this->APPLICATION_SECRET = strval(Core_Array::get($this->_config, 'APPLICATION_SECRET'));
			$this->CALLBACK_URL = urlencode(strval(Core_Array::get($this->_config, 'CALLBACK_URL')));
		}
		// Old notation
		elseif (isset($aConfig['APPLICATION_ID']))
		{
			$this->_config = $aConfig;

			$this->APPLICATION_ID = strval(Core_Array::get($this->_config, 'APPLICATION_ID'));
			$this->APPLICATION_SECRET = strval(Core_Array::get($this->_config, 'APPLICATION_SECRET'));
			$this->CALLBACK_URL = urlencode(strval(Core_Array::get($this->_config, 'CALLBACK_URL')));
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
			echo "<script>location.href = 'https://www.facebook.com/{$this->_version}/dialog/oauth?scope=email&client_id={$this->APPLICATION_ID}&redirect_uri={$this->CALLBACK_URL}';</script>";
			return NULL;
		}
		else
		{
			$sAnswer = @file_get_contents("https://graph.facebook.com/{$this->_version}/oauth/access_token?client_id={$this->APPLICATION_ID}&redirect_uri={$this->CALLBACK_URL}&client_secret={$this->APPLICATION_SECRET}&code={$this->code}", FALSE, stream_context_create(array(
				'http' => array(
						'method' => 'GET'
					)
				)
			));

			if (strlen($sAnswer))
			{
				// parse_str($sAnswer, $aReturnData);

				$aReturnData = json_decode($sAnswer, TRUE);

				$sAnswer = @file_get_contents("https://graph.facebook.com/{$this->_version}/me?access_token={$aReturnData['access_token']}&fields=email,first_name,last_name,picture", FALSE, stream_context_create(array(
					'http' => array(
							'method' => 'GET'
						)
					)
				));

				if (strlen($sAnswer))
				{
					$aUserInfo = json_decode($sAnswer, TRUE);

					$sWorkName = isset($aUserInfo['work'])
						? $aUserInfo['work'][0]['employer']['name']
						: '';

					return array(
						'user_id' => $aUserInfo['id'],
						'email' => Core_Array::get($aUserInfo, 'email'),
						'phone' => '',
						'name' => Core_Array::get($aUserInfo, 'first_name'),
						'surname' => Core_Array::get($aUserInfo, 'last_name'),
						'login' => Core_Array::get($aUserInfo, 'email', ''),
						'company' => $sWorkName,
						'picture' => "http://graph.facebook.com/{$aUserInfo['id']}/picture?type=normal"
					);
				}
			}

			return NULL;
		}
	}
}