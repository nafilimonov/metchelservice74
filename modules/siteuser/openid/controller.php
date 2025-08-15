<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Site users.
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2020 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Siteuser_OpenID_Controller
{
	/**
	 * The singleton instances.
	 * @var mixed
	 */
	static public $instance = NULL;

	/**
	 * Register an existing instance as a singleton.
	 * @return object
	 */
	static public function instance()
	{
		if (is_NULL(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * List of parameters
	 * @var array
	 */
	protected $_aURL = array();

	/**
	 * Set OpenID server
	 * @param string $openid_server
	 * @return self
	 */
	public function setOpenIDServer($openid_server)
	{
		$this->_aURL['openid_server'] = $openid_server;
		return $this;
	}

	/**
	 * Set trust_root option
	 * @param string $trust_root
	 * @return self
	 */
	public function setTrustRoot($trust_root)
	{
		$this->_aURL['trust_root'] = $trust_root;
		return $this;
	}

	/**
	 * Set return URL
	 * @param string $return
	 * @return self
	 */
	public function setReturnURL($return)
	{
		$this->_aURL['return'] = $return;
		return $this;
	}

	/**
	 * List of requered fields
	 * @var array
	 */
	protected $_aFields = array();

	/**
	 * Set requered fields
	 * @param mixed $required
	 * @return self
	 */
	public function setRequiredFields($required)
	{
		if (is_array($required))
		{
			$this->_aFields['required'] = $required;
		}
		else
		{
			$this->_aFields['required'][] = $required;
		}
		return $this;
	}

	/**
	 * Set optional fields
	 * @param mixed $optional
	 * @return self
	 */
	public function setOptionalFields($optional)
	{
		if (is_array($optional))
		{
			$this->_aFields['optional'] = $optional;
		}
		else
		{
			$this->_aFields['optional'][] = $optional;
		}
		return $this;
	}

	/**
	 * Identity URL
	 * @var string
	 */
	protected $_identityURL = NULL;

	/**
	 * Set Identity URL
	 * @param string $identityURL URL
	 * @return self
	 */
	public function setIdentityURL($identityURL)
	{
		if (strpos($identityURL, 'http://') === FALSE)
		{
			$identityURL = 'http://' . $identityURL;
		}
		$this->_identityURL = $identityURL;

		return $this;
	}

	/**
	 * Get URL identity
	 * @return string
	 */
	public function getIdentityUrl()
	{
		return $this->_identityURL;
	}

	/**
	 * List of errors
	 * @var array
	 */
	protected $_error = array();

	/**
	 * Get error
	 * @return array
	 */
	public function getError()
	{
		return $this->_error;
	}

	/**
	 * Set error
	 * @param int $code error code
	 * @param string $description error description
	 * @return self
	 */
	public function setError($code, $description = NULL)
	{
		//is_null($description) && $code == 'OPENID_NOSERVERSFOUND' && $description = 'Cannot find OpenID Server TAG on Identity page.';
		is_null($description) && $code == 'OPENID_NOSERVERSFOUND' && $description = 'OpenID Server не найден.';

		$this->_error = array(
			'code' => $code,
			'description' => $description
		);

		return $this;
	}

	/**
	 * Return TRUE if there are some errors
	 * @return boolean
	 */
	public function isError()
	{
		return count($this->_error) > 0;
	}

	/**
	 * Split response
	 * @param string $response response
	 * @return array
	 */
	public function splitResponse($response)
	{
		$return = array();
		$response = explode("\n", $response);

		foreach ($response as $line)
		{
			$line = trim($line);
			if ($line != '')
			{
				$aTmp = explode(':', $line, 2);

				if (count($aTmp) == 2)
				{
					list($key, $value) = $aTmp;
					$return[trim($key)] = trim($value);
				}
			}
		}
	 	return $return;
	}

	/**
	 * Сonverts associated array to URL Query String
	 * @param array $arr
	 * @return string
	 */
	protected function _array2url($arr)
	{
		if (!is_array($arr))
		{
			return FALSE;
		}
		$query = '';
		foreach ($arr as $key => $value)
		{
			$query .= $key . '=' . $value . '&';
		}

		return $query;
	}

	/**
	 * Get details of their OpenID server and (optional) delegate
	 * @param string $content content
	 * @return array
	 */
	protected function _html2OpenIDServer($content)
	{
		// Get details of their OpenID server and (optional) delegate
		preg_match_all('/<link[^>]*rel="openid.server"[^>]*href="([^"]+)"[^>]*\/?>/i', $content, $matches1);
		preg_match_all('/<link[^>]*href="([^"]+)"[^>]*rel="openid.server"[^>]*\/?>/i', $content, $matches2);
		$servers = array_merge($matches1[1], $matches2[1]);

		preg_match_all('/<link[^>]*rel="openid.delegate"[^>]*href="([^"]+)"[^>]*\/?>/i', $content, $matches1);
		preg_match_all('/<link[^>]*href="([^"]+)"[^>]*rel="openid.delegate"[^>]*\/?>/i', $content, $matches2);
		$delegates = array_merge($matches1[1], $matches2[1]);

		return array($servers, $delegates);
	}

	/**
	 * Get OpenID server
	 * @return array
	 */
	public function getOpenIDServer()
	{
		$Core_Http = Core_Http::instance('curl')
			->url($this->_identityURL)
			->execute();

		$response = $Core_Http->getDecompressedBody();

		list($servers, $delegates) = $this->_html2OpenIDServer($response);

		if (!count($servers))
		{
			$this->setError('OPENID_NOSERVERSFOUND');
			return FALSE;
		}

		isset($delegates[0]) && $delegates[0] != '' && $this->_identityURL = $delegates[0];

		$this->setOpenIDServer($servers[0]);

		return $servers[0];
	}

	/**
	 * Get redirect URL
	 * @return array
	 */
	public function getRedirectURL()
	{
		$params = array(
			'openid.return_to' => urlencode($this->_aURL['return']),
			'openid.mode' => 'checkid_setup',
			'openid.identity' => urlencode($this->_identityURL),
			'openid.trust_root' => urlencode($this->_aURL['trust_root']),
		);

		count($this->_aFields['required']) && $params['openid.sreg.required'] = implode(',', $this->_aFields['required']);

		count($this->_aFields['optional']) && $params['openid.sreg.optional'] = implode(',', $this->_aFields['optional']);

		return $this->_aURL['openid_server'] . '?' . $this->_array2url($params);
	}

	/**
	 * Execute redirection
	 */
	public function redirect()
	{
		$redirect_to = $this->getRedirectURL();

		if (headers_sent())
		{
			?><script>window.location='<?php echo htmlspecialchars($redirect_to)?>';</script><?php
		}
		else
		{
			header('Location: ' . $redirect_to);
			die();
		}
	}

	/**
	 * Checkout OpenID server
	 * @return array
	 */
	public function validateWithServer()
	{
		$params = array(
			'openid.assoc_handle' => urlencode(Core_Array::getGet('openid_assoc_handle')),
			'openid.signed' => urlencode(Core_Array::getGet('openid_signed')),
			'openid.sig' => urlencode(Core_Array::getGet('openid_sig'))
		);

		// Send only required parameters to confirm validity
		$arr_signed = explode(',', str_replace('sreg.','sreg_', Core_Array::getGet('openid_signed')));
		for ($i = 0; $i < count($arr_signed); $i++)
		{
			$params['openid.' . str_replace('sreg_','sreg.', $arr_signed[$i])] = urlencode(
				Core_Array::getGet('openid_' . $arr_signed[$i])
			);
		}
		$params['openid.mode'] = "check_authentication";

		$openid_server = $this->getOpenIDServer();

		if ($openid_server == FALSE)
		{
			return FALSE;
		}

		$Core_Http = Core_Http::instance('curl')
			->url($openid_server . '?' . $this->_array2url($params))
			->method('GET')
			->execute();
		$response = $Core_Http->getDecompressedBody();

		$data = $this->splitResponse($response);

		return isset($data['is_valid']) && $data['is_valid'] == 'true';
	}

	/**
	 * Get OpenID attribute
	 * @param string $name name
	 * @return mixed
	 */
	public function getAttribute($name)
	{
		return Core_Array::getGet('openid_sreg_' . $name);
	}
}