<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Oauth_Mail_Controller
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */

class Siteuser_Oauth_Mail_Controller extends Siteuser_Oauth_Controller
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

		$aConfig = Core_Config::instance()->get('siteuser_mail', array());

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
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		defined('CURL_IPRESOLVE_V4') && curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($ch, CURLOPT_VERBOSE, FALSE); // Minimize logs
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // No certificate
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($aParams));
		curl_setopt($ch, 156, 5000);
		$oResponse = curl_exec($ch);

		if ($oResponse === FALSE)
		{
			throw new Core_Exception('Curl error: %error', array('%error' => curl_error($ch)), 0, FALSE);
		}
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
			$sRedirect_Url_Enc = urlencode($this->REDIRECT_URI);
			echo "<script>location.href = 'https://connect.mail.ru/oauth/authorize?client_id={$this->CLIENT_ID}&response_type=code&redirect_uri={$sRedirect_Url_Enc}';</script>";
		}
		else
		{
			$sAnswer = $this->_getData("https://connect.mail.ru/oauth/token", array(
				'client_id' => $this->CLIENT_ID,
				'client_secret' => $this->CLIENT_SECRET,
				'grant_type' => 'authorization_code',
				'code' => $this->code,
				'redirect_uri' => $this->REDIRECT_URI
			));

			if (strlen($sAnswer))
			{
				$oAccessToken = json_decode($sAnswer);

				if (isset($oAccessToken->access_token))
				{
					$sAnswer = @file_get_contents("https://www.appsmail.ru/platform/api/?" . http_build_query(array(
						'method' => 'users.getInfo',
						'secure' => '1',
						'app_id' => $this->CLIENT_ID,
						'session_key' => $oAccessToken->access_token,
						'sig' => md5("app_id={$this->CLIENT_ID}method=users.getInfosecure=1session_key={$oAccessToken->access_token}{$this->CLIENT_SECRET}")
					)), FALSE, stream_context_create(array(
						'http' => array(
								'method' => 'GET'
							)
						)
					));

					if (strlen($sAnswer))
					{
						$oUserData = json_decode($sAnswer);

						if (isset($oUserData[0]))
						{
							return array(
								'user_id' => $oAccessToken->x_mailru_vid,
								'email' => isset($oUserData[0]->email) ? $oUserData[0]->email : NULL,
								'phone' => '',
								'name' => $oUserData[0]->first_name,
								'surname' => $oUserData[0]->last_name,
								'login' => '',
								'company' => '',
								'picture' => $oUserData[0]->pic_180
							);
						}
					}
				}
			}

			return NULL;
		}
	}
}