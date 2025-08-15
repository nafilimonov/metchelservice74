<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Oauth_Google_Controller
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */

class Siteuser_Oauth_Google_Controller extends Siteuser_Oauth_Controller
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'CLIENT_ID',
		'CLIENT_SECRET',
		'REDIRECT_URI'
	);

	/**
	 * Code for exchange to access token
	 * @var string
	 */
	public $code = NULL;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		$aConfig = Core_Config::instance()->get('siteuser_google', array());

		if (isset($aConfig[CURRENT_SITE]))
		{
			$this->_config = $aConfig[CURRENT_SITE];

			$this->CLIENT_ID = strval(Core_Array::get($this->_config, 'CLIENT_ID'));
			$this->CLIENT_SECRET = strval(Core_Array::get($this->_config, 'CLIENT_SECRET'));
			$this->REDIRECT_URI = strval(Core_Array::get($this->_config, 'REDIRECT_URI'));
		}
		// Old notation
		elseif (isset($aConfig['CLIENT_ID']))
		{
			$this->_config = $aConfig;

			$this->CLIENT_ID = strval(Core_Array::get($this->_config, 'CLIENT_ID'));
			$this->CLIENT_SECRET = strval(Core_Array::get($this->_config, 'CLIENT_SECRET'));
			$this->REDIRECT_URI = strval(Core_Array::get($this->_config, 'REDIRECT_URI'));
		}
		else
		{
			throw new Core_Exception("Current site config doesn`t exist!");
		}
	}

	/**
	 * cURL request sender
	 * @param string $sUrl URL
	 * @param array $aParams parameters
	 * @return mixed
	 */
	private function _getData($sUrl, $aParams)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $sUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($aParams));
		curl_setopt($ch, 156, 5000);
		$oResponse = curl_exec($ch);
		curl_close($ch);
		return $oResponse;
	}

	/**
	 * Execute the business logic
	 */
	public function execute()
	{
		if (is_null($this->code))
		{
			$sRedirect = urlencode($this->REDIRECT_URI);
			echo "<script>location.href = 'https://accounts.google.com/o/oauth2/auth?redirect_uri={$sRedirect}&response_type=code&client_id={$this->CLIENT_ID}&scope=https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fuserinfo.profile%20https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fuserinfo.email&approval_prompt=force&access_type=offline';</script>";
		}
		else
		{
			$sAnswer = $this->_getData("https://accounts.google.com/o/oauth2/token", array(
				'code' => $this->code,
				'redirect_uri' => $this->REDIRECT_URI,
				'client_id' => $this->CLIENT_ID,
				'scope' => '',
				'client_secret' => $this->CLIENT_SECRET,
				'grant_type' => 'authorization_code'
			));

			if (strlen($sAnswer))
			{
				$aData = json_decode($sAnswer, TRUE);

				if (isset($aData['token_type']) && isset($aData['access_token']))
				{
					$stream_context = stream_context_create(array(
						'http' => array(
								'method' => 'GET',
								'header' => "Authorization: {$aData['token_type']} {$aData['access_token']}"
						)));

					$sAnswer = @file_get_contents("https://www.googleapis.com/oauth2/v2/userinfo", FALSE, $stream_context);

					if (strlen($sAnswer))
					{
						$aReturnData = json_decode($sAnswer, TRUE);

						return array(
							'user_id' => Core_Array::get($aReturnData, 'id'),
							'email' => Core_Array::get($aReturnData, 'email'),
							'phone' => '',
							'name' => Core_Array::get($aReturnData, 'given_name', ''),
							'surname' => Core_Array::get($aReturnData, 'family_name', ''),
							'login' => '',
							'company' => '',
							'picture' => Core_Array::get($aReturnData, 'picture', ''),
						);
					}
				}
			}

			return NULL;
		}
	}
}