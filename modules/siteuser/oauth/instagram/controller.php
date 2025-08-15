<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Oauth_Instagram_Controller
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */

class Siteuser_Oauth_Instagram_Controller extends Siteuser_Oauth_Controller
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		// данные клиента
		'CLIENT_ID',
		'CLIENT_SECRET',
		'REDIRECT_URI'
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

		$aConfig = Core_Config::instance()->get('siteuser_instagram', array());

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
	 * Execute the business logic
	 */
	public function execute()
	{
		if (is_null($this->code))
		{
			echo "<script>location.href = 'https://api.instagram.com/oauth/authorize/?client_id={$this->CLIENT_ID}&redirect_uri={$this->REDIRECT_URI}&response_type=code';</script>";
		}
		else
		{
			$Core_Http = Core_Http::instance('curl')
				->clear()
				->method('POST')
				->url('https://api.instagram.com/oauth/access_token')
				->data('client_id', $this->CLIENT_ID)
				->data('client_secret', $this->CLIENT_SECRET)
				->data('grant_type', 'authorization_code')
				->data('redirect_uri', $this->REDIRECT_URI)
				->data('code', $this->code)
				->execute();

			$aResponse = json_decode($Core_Http->getDecompressedBody(), TRUE);

			if (isset($aResponse['user']))
			{
				$aUserData = explode(' ', $aResponse['user']['full_name']);

				return array(
					'user_id' => $aResponse['user']['id'],
					'email' => '',
					'phone' => '',
					'name' => Core_Array::get($aUserData, 0, ''),
					'surname' => Core_Array::get($aUserData, 1, ''),
					'login' => $aResponse['user']['username'],
					'company' => '',
					'picture' => $aResponse['user']['profile_picture']
				);
			}

			return NULL;
		}
	}
}