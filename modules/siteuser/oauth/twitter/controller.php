<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Oauth_Twitter_Controller
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */

class Siteuser_Oauth_Twitter_Controller extends Siteuser_Oauth_Controller
{
	/**
	 * Allowed object properties
	 * @var array
	 */
	protected $_allowedProperties = array(
		'CONSUMER_KEY',
		'CONSUMER_SECRET',
		'REQUEST_TOKEN_URL',
		'AUTHORIZE_URL',
		'ACCESS_TOKEN_URL',
		'CALLBACK_URL',
		'ACCOUNT_DATA_URL',
		'sUnauthorizedToken',
		'sAccessToken',
		'sVerifierCode',
		'sAuthorizedRequestTokenSecret',
		'oauth_token',
		'oauth_verifier',
		'oauth_token_secret'
	);

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		$aConfig = Core_Config::instance()->get('siteuser_twitter', array());

		if (isset($aConfig[CURRENT_SITE]))
		{
			$this->_config = $aConfig[CURRENT_SITE];

			$this->CONSUMER_KEY = strval(Core_Array::get($this->_config, 'CONSUMER_KEY'));
			$this->CONSUMER_SECRET = strval(Core_Array::get($this->_config, 'CONSUMER_SECRET'));
			$this->REQUEST_TOKEN_URL = strval(Core_Array::get($this->_config, 'REQUEST_TOKEN_URL'));
			$this->AUTHORIZE_URL = strval(Core_Array::get($this->_config, 'AUTHORIZE_URL'));
			$this->ACCESS_TOKEN_URL = strval(Core_Array::get($this->_config, 'ACCESS_TOKEN_URL'));
			$this->CALLBACK_URL = strval(Core_Array::get($this->_config, 'CALLBACK_URL'));
		}
		// Old notation
		elseif (isset($aConfig['CONSUMER_KEY']))
		{
			$this->_config = $aConfig;

			$this->CONSUMER_KEY = strval(Core_Array::get($this->_config, 'CONSUMER_KEY'));
			$this->CONSUMER_SECRET = strval(Core_Array::get($this->_config, 'CONSUMER_SECRET'));
			$this->REQUEST_TOKEN_URL = strval(Core_Array::get($this->_config, 'REQUEST_TOKEN_URL'));
			$this->AUTHORIZE_URL = strval(Core_Array::get($this->_config, 'AUTHORIZE_URL'));
			$this->ACCESS_TOKEN_URL = strval(Core_Array::get($this->_config, 'ACCESS_TOKEN_URL'));
			$this->CALLBACK_URL = strval(Core_Array::get($this->_config, 'CALLBACK_URL'));
		}
		else
		{
			throw new Core_Exception("Current site config doesn`t exist!");
		}

		$this->ACCOUNT_DATA_URL = 'https://api.twitter.com/1.1/users/show.json';

		$this->oauth_token_secret = NULL;
		$this->oauth_token = NULL;
		$this->oauth_verifier = NULL;
	}

	/**
	 * Execute the business logic
	 */
	public function execute()
	{
		if (is_null($this->oauth_token_secret)
			&& is_null($this->oauth_token)
			&& is_null($this->oauth_verifier))
		{
			$sNonce = md5(rand());
			$iTimestamp = time();

			$sBasetext = "GET&";
			$sBasetext .= urlencode($this->REQUEST_TOKEN_URL) . "&";
			$sBasetext .= urlencode("oauth_callback=" . urlencode($this->CALLBACK_URL) . "&");
			$sBasetext .= urlencode("oauth_consumer_key=" . $this->CONSUMER_KEY."&");
			$sBasetext .= urlencode("oauth_nonce=" . $sNonce."&");
			$sBasetext .= urlencode("oauth_signature_method=HMAC-SHA1&");
			$sBasetext .= urlencode("oauth_timestamp=" . $iTimestamp . "&");
			$sBasetext .= urlencode("oauth_version=1.0");

			$sKey = $this->CONSUMER_SECRET . "&";
			$sSign = base64_encode(
				hash_hmac("sha1", $sBasetext, $sKey, TRUE)
			);

			$sUrl = $this->REQUEST_TOKEN_URL;
			$sUrl .= '?oauth_callback='.urlencode($this->CALLBACK_URL);
			$sUrl .= '&oauth_consumer_key='.$this->CONSUMER_KEY;
			$sUrl .= '&oauth_nonce='.$sNonce;
			$sUrl .= '&oauth_signature='.urlencode($sSign);
			$sUrl .= '&oauth_signature_method=HMAC-SHA1';
			$sUrl .= '&oauth_timestamp='.$iTimestamp;
			$sUrl .= '&oauth_version=1.0';

			$Core_Http = Core_Http::instance('curl')
				->clear()
				->method('GET')
				->url($sUrl)
				->execute();

			$oResponse = $Core_Http->getDecompressedBody();

			parse_str($oResponse, $aResult);

			$oauth_token = Core_Array::get($aResult, 'oauth_token');

			echo "<script>location.href = '" . $this->AUTHORIZE_URL . "?oauth_token={$oauth_token}';</script>";

			return Core_Array::get($aResult, 'oauth_token_secret');
		}
		elseif (!is_null($this->oauth_token_secret)
			&& !is_null($this->oauth_token)
			&& !is_null($this->oauth_verifier))
		{
			// создаем заново nonce и timestamp
			$sNonce = md5(rand());
			$iTimestamp = time();

			$sToken = $this->oauth_token;
			$sVerifier = $this->oauth_verifier;

			$sBasetext = "GET&";
			$sBasetext .= urlencode($this->ACCESS_TOKEN_URL) . "&";
			$sBasetext .= urlencode("oauth_consumer_key=" . $this->CONSUMER_KEY."&");
			$sBasetext .= urlencode("oauth_nonce=" . $sNonce."&");
			$sBasetext .= urlencode("oauth_signature_method=HMAC-SHA1&");
			$sBasetext .= urlencode("oauth_token=" . $sToken."&");
			$sBasetext .= urlencode("oauth_timestamp=" . $iTimestamp."&");
			$sBasetext .= urlencode("oauth_verifier=" . $sVerifier."&");
			$sBasetext .= urlencode("oauth_version=1.0");

			$sKey = $this->CONSUMER_SECRET . "&" . $this->oauth_token_secret;
			$sSignature = base64_encode(hash_hmac("sha1", $sBasetext, $sKey, TRUE));

			$sHeader = 'OAuth ' . implode(', ', array(
				"oauth_consumer_key=\"{$this->CONSUMER_KEY}\"",
				"oauth_nonce=\"{$sNonce}\"",
				"oauth_signature=\"{$sSignature}\"",
				"oauth_signature_method=\"HMAC-SHA1\"",
				"oauth_timestamp=\"{$iTimestamp}\"",
				"oauth_token=\"{$sToken}\"",
				"oauth_verifier=\"{$sVerifier}\"",
				"oauth_version=\"1.0\"",
			));

			$Core_Http = Core_Http::instance('curl')
				->clear()
				->method('GET')
				->url($this->ACCESS_TOKEN_URL)
				->additionalHeader('Authorization', $sHeader)
				->execute();

			$oResponse = $Core_Http->getDecompressedBody();

			if (strlen($oResponse))
			{
				parse_str($oResponse, $aResult);

				if (isset($aResult['oauth_token']))
				{
					$sToken = $aResult['oauth_token'];
					$sNonce = md5(rand());
					$iTimestamp = time();

					$sBasetext = "GET&";
					$sBasetext .= urlencode($this->ACCOUNT_DATA_URL) . "&";
					$sBasetext .= urlencode("oauth_consumer_key=" . $this->CONSUMER_KEY."&");
					$sBasetext .= urlencode("oauth_nonce=" . $sNonce."&");
					$sBasetext .= urlencode("oauth_signature_method=HMAC-SHA1&");
					$sBasetext .= urlencode("oauth_timestamp=" . $iTimestamp."&");
					$sBasetext .= urlencode("oauth_token=" . $sToken."&");
					$sBasetext .= urlencode("oauth_version=1.0&");
					$sBasetext .= urlencode("screen_name=" . $aResult['screen_name']);
					$sKey = $this->CONSUMER_SECRET . "&" . $aResult['oauth_token_secret'];
					$sSignature = urlencode(base64_encode(hash_hmac("sha1", $sBasetext, $sKey, TRUE)));

					$sHeader = 'OAuth ' . implode(', ', array(
						"oauth_consumer_key=\"{$this->CONSUMER_KEY}\"",
						"oauth_nonce=\"{$sNonce}\"",
						"oauth_signature=\"{$sSignature}\"",
						"oauth_signature_method=\"HMAC-SHA1\"",
						"oauth_timestamp=\"{$iTimestamp}\"",
						"oauth_token=\"{$sToken}\"",
						"oauth_version=\"1.0\"",
					));

					$sUrl = $this->ACCOUNT_DATA_URL . '?screen_name=' . $aResult['screen_name'];

					$Core_Http = Core_Http::instance('curl')
						->clear()
						->method('GET')
						->url($sUrl)
						->additionalHeader('Authorization', $sHeader)
						->execute();

					$oResponse = $Core_Http->getDecompressedBody();

					if (strlen($oResponse))
					{
						$oResponse = json_decode($oResponse);

						return array(
							'user_id' => $oResponse->id,
							'email' => '',
							'phone' => '',
							'name' => $oResponse->name,
							'surname' => '',
							'login' => $oResponse->screen_name,
							'company' => '',
							'picture' => str_replace("_normal", "", $oResponse->profile_image_url)
						);
					}
				}
			}

			return NULL;
		}
		else
		{
			throw new Core_Exception('Wrong parameters', array(), 0, FALSE);
		}
	}
}