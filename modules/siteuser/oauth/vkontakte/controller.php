<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Oauth_Vkontakte_Controller
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2024 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */

class Siteuser_Oauth_Vkontakte_Controller extends Siteuser_Oauth_Controller
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

		$aConfig = Core_Config::instance()->get('siteuser_vkontakte', array());

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
		$url = $sUrl . '?' . http_build_query($aParams);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		defined('CURL_IPRESOLVE_V4') && curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		curl_setopt($ch, CURLOPT_VERBOSE, FALSE); // Minimize logs
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // No certificate
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
			$sParams = http_build_query(array(
				'client_id' => $this->CLIENT_ID,
				'redirect_uri' => $this->REDIRECT_URI,
				'display' => 'page',
				'scope' => 'notify,email',
				'response_type' => 'code'
			));

			echo "<script>location.href = 'https://oauth.vk.com/authorize?{$sParams}';</script>";

			return NULL;
		}
		else
		{
			$aReturn = json_decode(
				$this->_getData('https://oauth.vk.com/access_token', array(
					'client_id' => $this->CLIENT_ID,
					'client_secret' => $this->CLIENT_SECRET,
					'code' => $this->code,
					'redirect_uri' => $this->REDIRECT_URI)
				), TRUE
			);

			if (isset($aReturn['user_id']))
			{
				$oReturn = json_decode(
					$this->_getData('https://api.vk.com/method/users.get', array(
						'fields' => 'id, first_name, last_name, nickname, screen_name, photo_big, contacts',
						'user_ids' => $aReturn['user_id'],
						'v' => '5.131',
						'access_token' => $aReturn['access_token'],
					)
				));

				if (isset($oReturn->response))
				{
					$phone = isset($oReturn->response[0]->mobile_phone)
						? strval($oReturn->response[0]->mobile_phone)
						: (isset($oReturn->response[0]->home_phone)
							? strval($oReturn->response[0]->home_phone)
							: ''
						);

					return array(
						'user_id' => $oReturn->response[0]->id,
						'email' => Core_Array::get($aReturn, 'email'),
						'phone' => $phone,
						'name' => $oReturn->response[0]->first_name,
						'surname' => $oReturn->response[0]->last_name,
						'login' => $oReturn->response[0]->nickname,
						'company' => '',
						'picture' => $oReturn->response[0]->photo_big
					);
				}
				else
				{
					return NULL;
				}
			}
			else
			{
				//return NULL;
			}
		}
	}
}