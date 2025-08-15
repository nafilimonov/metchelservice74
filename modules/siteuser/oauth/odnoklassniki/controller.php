<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Oauth_Odnoklassniki_Controller
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */

class Siteuser_Oauth_Odnoklassniki_Controller extends Siteuser_Oauth_Controller
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'CLIENT_ID',
		'CLIENT_SECRET',
		'APPLICATION_KEY',
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

		$aConfig = Core_Config::instance()->get('siteuser_odnoklassniki', array());

		if (isset($aConfig[CURRENT_SITE]))
		{
			$this->_config = $aConfig[CURRENT_SITE];

			$this->CLIENT_ID = strval(Core_Array::get($this->_config, 'CLIENT_ID'));
			$this->CLIENT_SECRET = strval(Core_Array::get($this->_config, 'CLIENT_SECRET'));
			$this->APPLICATION_KEY = strval(Core_Array::get($this->_config, 'APPLICATION_KEY'));
			$this->REDIRECT_URI = strval(Core_Array::get($this->_config, 'REDIRECT_URI'));
		}
		// Old notation
		elseif (isset($aConfig['CLIENT_ID']))
		{
			$this->_config = $aConfig;

			$this->CLIENT_ID = strval(Core_Array::get($this->_config, 'CLIENT_ID'));
			$this->CLIENT_SECRET = strval(Core_Array::get($this->_config, 'CLIENT_SECRET'));
			$this->APPLICATION_KEY = strval(Core_Array::get($this->_config, 'APPLICATION_KEY'));
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
			$sRedirect = urlencode($this->REDIRECT_URI);
			echo "<script>location.href = 'https://www.odnoklassniki.ru/oauth/authorize?client_id={$this->CLIENT_ID}&scope=GET_EMAIL&response_type=code&redirect_uri={$sRedirect}';</script>";
		}
		else
		{
			$sAnswer = $this->_getData("https://api.odnoklassniki.ru/oauth/token.do", array(
				'code' => $this->code,
				'redirect_uri' => $this->REDIRECT_URI,
				'grant_type' => 'authorization_code',
				'client_id' => $this->CLIENT_ID,
				'client_secret' => $this->CLIENT_SECRET)
			);

			if (strlen($sAnswer))
			{
				$aData = json_decode($sAnswer, TRUE);

				if (isset($aData['access_token']))
				{
					$fields = 'uid,first_name,last_name,pic640x480,email';

					$sign = md5("application_key={$this->APPLICATION_KEY}fields={$fields}format=jsonmethod=users.getCurrentUser" . md5($aData['access_token'] . $this->CLIENT_SECRET));

					$sAnswer = $this->_getData("https://api.odnoklassniki.ru/fb.do", array(
							'method' => 'users.getCurrentUser',
							'access_token' => $aData['access_token'],
							'application_key' => $this->APPLICATION_KEY,
							'fields' => $fields,
							'format' => 'json',
							'sig' => $sign,
						)
					);

					if (strlen($sAnswer))
					{
						$aRetData = json_decode($sAnswer, TRUE);

						return array(
							'user_id' => $aRetData['uid'],
							'email' => '',
							'phone' => '',
							'name' => $aRetData['first_name'],
							'surname' => $aRetData['last_name'],
							'login' => '',
							'company' => '',
							'picture' => $aRetData['pic640x480']
						);
					}
				}
			}

			return NULL;
		}
	}
}