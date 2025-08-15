<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * REST API command controller.
 *
 * @package HostCMS
 * @subpackage Restapi
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Restapi_Command_Controller extends Core_Command_Controller
{
	/**
	 * Selected API version
	 * @var string
	 */
	public $version = NULL;

	/**
	 * Request path
	 * @var string
	 */
	public $path = NULL;

	/**
	 * GET limit
	 * @var int
	 */
	protected $_limit = 25;

	/**
	 * GET offset
	 * @var int
	 */
	protected $_offset = 0;

	/**
	 * GET expand
	 * @var array
	 */
	protected $_expand = array();

	/**
	 * Answer mode, e.g. JSON, XML
	 * @var string|NULL
	 */
	protected $_mode = NULL;

	/**
	 * Content-type, e.g. 'application/json', 'multipart/form-data'
	 * @var string|NULL
	 */
	protected $_contentType = NULL;

	/**
	 * Multipart Boundary
	 * @var string|NULL
	 */
	protected $_boundary = NULL;

	/**
	 * Error Message
	 * @var string|NULL
	 */
	protected $_error = NULL;

	/**
	 * HTTP code, default 200
	 * @var int
	 */
	protected $_statusCode = 200;

	/**
	 * REST API answer
	 * @var mixed
	 */
	protected $_answer = NULL;

	/**
	 * @var User_Model|NULL
	 */
	protected $_user = NULL;

	/**
	 * Module Config
	 * @var array
	 */
	protected $_config = array();

	/**
	 * Available operators in the filter fields
	 * ne - "!=" not equal to
	 * lt - "<" is less than
	 * gt - ">" is greater than
	 * lte - "≤" is less than or equal to
	 * gte - "≥" is greater than or equal to
	 * @var array
	 */
	protected $_availableOperators = array('ne', 'lt',  'gt',  'lte',  'gte');

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->_config = Core_Config::instance()->get('restapi_config', array()) + array(
			'url' => '/api',
			'xmlRootNode' => 'request',
			'log' => TRUE,
		);
	}

	/**
	 * Core_Response
	 * @var Core_Response|NULL
	 */
	protected $_oCore_Response = NULL;

	/**
	 * Default controller action
	 * @return Core_Response
	 * @hostcms-event Restapi_Command_Controller.onBeforeShowAction
	 * @hostcms-event Restapi_Command_Controller.onAfterShowAction
	 */
	public function showAction()
	{
		Core_Event::notify(get_class($this) . '.onBeforeShowAction', $this);

		$this->_oCore_Response = new Core_Response();

		ob_start();

		switch ($this->version)
		{
			case '1':
			case '1.0':
				$this->_version1(0);
			break;
			case '1.1':
				$this->_version1(1);
			break;
			default:
				$this->_error = 'Wrong version';
				$this->_statusCode = 400;
			break;
		}

		if (!is_null($this->_statusCode))
		{
			$this->_oCore_Response->status($this->_statusCode);
		}

		$messageContent = ob_get_clean();

		$this->_oCore_Response
			->header('Pragma', 'no-cache')
			->header('Cache-Control', 'private, no-cache')
			->header('Vary', 'Accept');

		$return = $this->_answer;

		if ($this->_statusCode >= 300)
		{
			$return['error']['code'] = $this->_statusCode;

			if ($this->_config['log'])
			{
				Core_Log::instance()->clear()
					->notify(FALSE)
					->status(Core_Log::$MESSAGE)
					->write(sprintf('REST API. CODE: %d, ERROR: %s', $this->_statusCode, $this->_error));
			}
		}

		if (!is_null($this->_error))
		{
			is_array($return)
				&& $return['error']['message'] = $this->_error;
		}

		if ($messageContent != '')
		{
			is_array($return)
				&& $return['error']['extraMessage'] = $messageContent;
		}

		switch ($this->_mode)
		{
			case 'json':
			default:
				$this->_oCore_Response
					->header('Content-Disposition', 'inline; filename="files.json"')
					->header('Content-Type', 'application/json; charset=utf-8');

				$aJson = NULL;
				if (is_array($return))
				{
					foreach ($return as $key => $tmp)
					{
						$aJson[$key] = $this->_entity2array($tmp, $this->_expand);
					}
				}
				else
				{
					$aJson = $this->_entity2array($return, $this->_expand);
				}

				$this->_oCore_Response->body(json_encode($aJson, defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 0));
			break;
			case 'xml':
				$this->_oCore_Response->header('Content-Type', 'application/xml; charset=utf-8');

				$this->_oCore_Response->body(
					'<?xml version="1.0" encoding="UTF-8"?>' . "\r\n" .
					'<' . Core_Str::xml($this->_config['xmlRootNode']) . '>' . "\r\n"
				);

				$this->_oCore_Response->body($this->_entity2XML($return));

				$this->_oCore_Response->body(
					'</' . Core_Str::xml($this->_config['xmlRootNode']) . '>'
				);
			break;
		}

		Core_Event::notify(get_class($this) . '.onAfterShowAction', $this, array($this->_oCore_Response));

		return $this->_oCore_Response;
	}

	/**
	 * Convert $entity to array
	 * @var mixed $entity
	 * @return array
	 */
	protected function _entity2array($entity, $expand = array())
	{
		if (is_object($entity))
		{
			$aReturn = $entity instanceof Core_ORM
				? $entity->toArray()
				: $entity;

			foreach ($expand as $fieldName => $subFields)
			{
				$idField = $fieldName . '_id';
				if (isset($aReturn[$idField]))
				{
					if (class_exists(Core_Entity::getClassName($fieldName)))
					{
						$oSubEntity = $aReturn[$idField]
							? Core_Entity::factory($fieldName, $aReturn[$idField])->load()
							: array();

						$aReturn = Core_Array::changeKey($aReturn, $idField, $fieldName, $this->_entity2array($oSubEntity, $subFields));
					}
					else
					{
						$this->_error = sprintf('Wrong Expand Entity "%s"', $fieldName);
					}
				}
			}

			return $aReturn;
		}

		return $entity;
	}

	/**
	 * Convert $entity to XML
	 * @var mixed $entity
	 * @return string|NULL
	 */
	protected function _entity2XML($entity)
	{
		if (is_object($entity) && $entity instanceof Core_ORM)
		{
			return $entity->getXml();
		}

		return is_array($entity)
			? Core_Xml::array2xml($entity)
			: NULL;
	}

	/**
	 * Parse $aPath
	 * @param array $aPath
	 * @return array|bool
	 */
	protected function _parseByPathV1(array $aPath)
	{
		$oPreviosEntity = NULL;

		foreach ($aPath as $key => $path)
		{
			if ($key == 0)
			{
				$sSingularName = $this->_getClassName($path);

				if ($sSingularName === FALSE)
				{
					$this->_error = 'Wrong entity name';
					$this->_statusCode = 400;

					return FALSE;
				}

				$oPreviosEntity = Core_Entity::factory($sSingularName);

				$bFound = FALSE;
			}
			elseif (is_numeric($path) && $oPreviosEntity instanceof Core_ORM)
			{
				$oPreviosEntity = $oPreviosEntity->getById($path);

				// Entity exists
				if (is_null($oPreviosEntity))
				{
					$this->_error = 'Entity Not Found By PK';
					$this->_statusCode = 403;

					return FALSE;
				}

				// Check access
				if (!$this->_user->checkObjectAccess($oPreviosEntity))
				{
					$this->_error = sprintf('Entity %d. Access Forbidden.', $path);
					$this->_statusCode = 403;

					return FALSE;
				}

				$bFound = TRUE;
			}
			else
			{
				if (is_object($oPreviosEntity) && $oPreviosEntity instanceof Core_ORM && $oPreviosEntity->id)
				{
					$bFound = FALSE;

					try {
						if (isset($oPreviosEntity->$path))
						{
							$oPreviosEntity = $oPreviosEntity->$path;
						}
						elseif (method_exists($oPreviosEntity, $path) || method_exists($oPreviosEntity, 'isCallable') && $oPreviosEntity->isCallable($path))
						{
							$oPreviosEntity = $oPreviosEntity->$path();

							$bFound = TRUE;
						}
					}
					catch (Exception $e)
					{
						$this->_error = $e->getMessage();
						$this->_statusCode = 422;

						return FALSE;
					}
				}
				else
				{
					$this->_error = 'Parent Entity Not Found';
					$this->_statusCode = 403;

					return FALSE;
				}
			}
		}

		return array($oPreviosEntity, $bFound);
	}

	/**
	 * Parse path. Version 1.1 ($limit, $offset, $orderBy, $count)
	 * @param array $aPath
	 * @return mixed
	 */
	protected function _selectByPathV11($aPath)
	{
		// Expand
		$expand = Core_Array::getGet('$expand');
		if (is_scalar($expand))
		{
			// Fields separated by comma
			$aExpand = array_map('trim', explode(',', $expand));
			foreach ($aExpand as $sExpand)
			{
				// Sub-fields separated by dot
				$previousExpand = &$this->_expand;

				$aFieldExplode = array_map('trim', explode('.', $sExpand));
				foreach ($aFieldExplode as $sFieldExplode)
				{
					$previousExpand[$sFieldExplode] = array();
					$previousExpand = &$previousExpand[$sFieldExplode];
				}
			}
		}
			
		$mResult = $this->_parseByPathV1($aPath);

		if (is_bool($mResult))
		{
			return $mResult;
		}

		list($oPreviosEntity, $bFound) = $mResult;

		$mAnswer = NULL;

		if ($bFound)
		{
			$mAnswer = $oPreviosEntity;
		}
		else
		{
			// LIMIT
			$tmpLimit = Core_Array::getGet('$limit', 0, 'int');
			if (is_numeric($tmpLimit) && $tmpLimit > 0)
			{
				$this->_limit = intval($tmpLimit);
			}

			// OFFSET
			$tmpOffset = Core_Array::getGet('$offset', 0, 'int');
			if (is_numeric($tmpOffset) && $tmpOffset >= 0)
			{
				$this->_offset = intval($tmpOffset);
			}

			$oPreviosEntity->queryBuilder()
				->limit($this->_limit)
				->offset($this->_offset);

			// ORDER BY
			$aTmpOrderBy = Core_Array::getGet('$orderBy');
			if (!is_null($aTmpOrderBy) && !is_array($aTmpOrderBy))
			{
				$aTmpOrderBy = array($aTmpOrderBy);
			}

			is_array($aTmpOrderBy)
				&& $this->_applyOrderByV1($aTmpOrderBy, $oPreviosEntity);

			// Count
			$count = Core_Array::getGet('$count');
			$bCount = !is_null($count) && $count !== 'false';
			if ($bCount)
			{
				// FOUND_ROWS()
				$oPreviosEntity
					->queryBuilder()
					->sqlCalcFoundRows();
			}

			$aPredefinedFields = array(
				'$limit',
				'$offset',
				'$orderBy',
				'$count',
				'$expand'
			);

			// OTHER OPTIONS
			$this->_applyFilterConditionsV1($aPredefinedFields, $oPreviosEntity);

			try {
				// Load model columns BEFORE FOUND_ROWS()
				$oPreviosEntity->getTableColumns();

				$aResult = $oPreviosEntity->findAll(FALSE);

				if ($bCount)
				{
					$total = Core_QueryBuilder::select()->getFoundRows();
					$this->_oCore_Response->header('X-Total-Count', $total);
				}
			}
			catch (Exception $e)
			{
				$this->_error = $e->getMessage();
				$this->_statusCode = 422;

				return FALSE;
			}

			$mAnswer = array();
			foreach ($aResult as $oEntity)
			{
				if ($this->_user->checkObjectAccess($oEntity))
				{
					$mAnswer[] = $oEntity;
				}
			}
		}

		return $mAnswer;
	}

	/**
	 * Parse path. Version 1.0 (limit, offset, orderBy)
	 * @param array $aPath
	 * @return mixed
	 */
	protected function _selectByPathV10($aPath)
	{
		$mResult = $this->_parseByPathV1($aPath);

		if (is_bool($mResult))
		{
			return $mResult;
		}

		list($oPreviosEntity, $bFound) = $mResult;

		$mAnswer = NULL;

		if ($bFound)
		{
			$mAnswer = $oPreviosEntity;
		}
		else
		{
			// LIMIT
			$tmpLimit = Core_Array::getGet('limit', 0, 'int');
			if (is_numeric($tmpLimit) && $tmpLimit > 0)
			{
				$this->_limit = intval($tmpLimit);
			}

			// OFFSET
			$tmpOffset = Core_Array::getGet('offset', 0, 'int');
			if (is_numeric($tmpOffset) && $tmpOffset >= 0)
			{
				$this->_offset = intval($tmpOffset);
			}

			$oPreviosEntity->queryBuilder()
				->limit($this->_limit)
				->offset($this->_offset);

			// ORDER BY
			$aTmpOrderBy = Core_Array::getGet('orderBy');
			if (!is_null($aTmpOrderBy) && !is_array($aTmpOrderBy))
			{
				$aTmpOrderBy = array($aTmpOrderBy);
			}

			is_array($aTmpOrderBy)
				&& $this->_applyOrderByV1($aTmpOrderBy, $oPreviosEntity);

			$aPredefinedFields = array(
				'limit',
				'offset',
				'orderBy',
			);

			// OTHER OPTIONS
			$this->_applyFilterConditionsV1($aPredefinedFields, $oPreviosEntity);

			try {
				$aResult = $oPreviosEntity->findAll(FALSE);
			}
			catch (Exception $e)
			{
				$this->_error = $e->getMessage();
				$this->_statusCode = 422;

				return FALSE;
			}

			$mAnswer = array();
			foreach ($aResult as $oEntity)
			{
				if ($this->_user->checkObjectAccess($oEntity))
				{
					$mAnswer[] = $oEntity;
				}
			}
		}

		return $mAnswer;
	}

	/**
	 * Apply Order By to the $oEntity, v. 1.x
	 * @param array $aPredefinedFields
	 * @param Core_Entity $oEntity
	 * @return self
	 */
	protected function _applyOrderByV1(array $OrderBy, $oEntity)
	{
		foreach ($OrderBy as $tmpOrderBy)
		{
			$aTmpExplodeOrderBy = explode(' ', $tmpOrderBy);
			if (strlen($aTmpExplodeOrderBy[0]))
			{
				$orderBy = $aTmpExplodeOrderBy[0];

				$orderByDirection = isset($aTmpExplodeOrderBy[1])
					? $aTmpExplodeOrderBy[1]
					: 'ASC';

				$oEntity->queryBuilder()
					->orderBy($orderBy, $orderByDirection);
			}
		}

		return $this;
	}

	/**
	 * Apply Filter Conditions to the $oEntity, v. 1.x
	 * @param array $aPredefinedFields
	 * @param Core_Entity $oEntity
	 * @return self
	 */
	protected function _applyFilterConditionsV1(array $aPredefinedFields, $oEntity)
	{
		// OTHER OPTIONS
		foreach ($_GET as $key => $value)
		{
			if (!in_array($key, $aPredefinedFields))
			{
				$expression = '=';

				if (is_array($value) && count($value))
				{
					$expression = 'IN';
				}
				elseif (strpos($key, '|') !== FALSE)
				{
					$aTmp = explode('|', $key);
					$last = array_pop($aTmp);

					if (in_array($last, $this->_availableOperators))
					{
						// change $key
						$key = implode('|', $aTmp);

						switch ($last)
						{
							case 'ne': // not equal to
								$expression = '!=';
							break;
							case 'lt': // is less than
								$expression = '<';
							break;
							case 'gt': // is greater than
								$expression = '>';
							break;
							case 'lte': // is less than or equal to
								$expression = '<=';
							break;
							case 'gte': // is greater than or equal to
								$expression = '>=';
							break;
						}
					}
				}

				$oEntity->queryBuilder()->where($key, $expression, $value);
			}
		}

		return $this;
	}

	/**
	 * Verson 1.x
	 * @param int $minorVersion minor version, e.g. 2 for 1.2
	 * @hostcms-event Restapi_Command_Controller.onBeforeAddNewEntity
	 * @hostcms-event Restapi_Command_Controller.onBeforeUpdateEntity
	 */
	protected function _version1($minorVersion)
	{
		$ip = Core::getClientIp();

		if (Core::moduleIsActive('ipaddress'))
		{
			$aIp = array($ip);
			$HTTP_X_FORWARDED_FOR = Core_Array::get($_SERVER, 'HTTP_X_FORWARDED_FOR');
			if (!is_null($HTTP_X_FORWARDED_FOR) && $ip != $HTTP_X_FORWARDED_FOR)
			{
				$aIp[] = $HTTP_X_FORWARDED_FOR;
			}

			$oIpaddress_Controller = new Ipaddress_Controller();

			// для RestAPI доступ подсети для клиентского раздела не блокируем
			if ($oIpaddress_Controller->isBackendBlocked($aIp))
			{
				$this->_error = 'Access Unavailable!';
				$this->_statusCode = 401;

				return FALSE;
			}
		}

		// Получаем количество неудачных попыток
		$iCountAccessdenied = Core_Entity::factory('Restapi_Accessdenied')->getCountByIp($ip);

		// Были ли у данного пользователя неудачные попытки входа в систему администрирования за последние 24 часа?
		if ($iCountAccessdenied)
		{
			// Last Restapi_Accessdenied by IP
			$oRestapi_Accessdenied = Core_Entity::factory('Restapi_Accessdenied')->getLastByIp($ip);

			if (!is_null($oRestapi_Accessdenied))
			{
				// Определяем интервал времени между последней неудачной попыткой входа в систему
				// и текущим временем входа в систему
				$delta = time() - Core_Date::sql2timestamp($oRestapi_Accessdenied->datetime);

				// Определяем период времени, в течении которого пользователю, имевшему неудачные
				// попытки доступа в систему запрещен вход в систему
				$delta_access_denied = $iCountAccessdenied > 2
					? 5 * exp(2.2 * log($iCountAccessdenied - 1))
					: 5 * $iCountAccessdenied;

				// Если период запрета доступа в систему не истек
				if ($delta_access_denied > $delta)
				{
					$iCountAccessdenied++;

					// Проверяем количество доступных ошибочных попыток
					if (Core::$mainConfig['banAfterFailedAccessAttempts']
						&& $iCountAccessdenied > Core::$mainConfig['banAfterFailedAccessAttempts']
						&& Core::moduleIsActive('ipaddress')
					)
					{
						$banIp = strpos($ip, ':') === FALSE
							// IPv4
							? substr($ip, 0, strrpos($ip, '.')) . '.0/24'
							// IPv6
							: $ip;

						$oIpaddress = Core_Entity::factory('Ipaddress')->getByIp($banIp, FALSE);

						if (!$oIpaddress)
						{
							$oIpaddress = Core_Entity::factory('Ipaddress');
							$oIpaddress->ip = $banIp;
							$oIpaddress->deny_access = 0;
							$oIpaddress->deny_backend = 1;
							$oIpaddress->comment = sprintf('IP %s blocked after %d failed RestAPI attempts', $ip, $iCountAccessdenied);
							$oIpaddress->save();
						}
					}

					$this->_error = 'Access Temporarily Unavailable. Please wait ' . round($delta_access_denied - $delta) . ' sec.';
					$this->_statusCode = 401;

					return FALSE;
				}
			}
		}

		// Check Authorization
		$this->_checkAuthorization();

		if (is_null($this->_user))
		{
			// Save attempt
			$oRestapi_Accessdenied = Core_Entity::factory('Restapi_Accessdenied');
			$oRestapi_Accessdenied->datetime = Core_Date::timestamp2sql(time());
			$oRestapi_Accessdenied->ip = $ip;
			$oRestapi_Accessdenied->save();

			$this->_error = 'Authentication Required';
			$this->_statusCode = 401;

			return FALSE;
		}
		else
		{
			// Удаление всех неудачных попыток входа систему за период ранее 24 часов с момента успешного входа
			$oRestapi_Accessdenieds = Core_Entity::factory('Restapi_Accessdenied');
			$oRestapi_Accessdenieds->queryBuilder()
				->clear()
				->where('datetime', '<', Core_Date::timestamp2sql(time() - 86400))
				// Удаляем все попытки доступа с текущего IP
				->setOr()
				->where('ip', '=', $ip);

			$aRestapi_Accessdenieds = $oRestapi_Accessdenieds->findAll(FALSE);
			foreach ($aRestapi_Accessdenieds as $oRestapi_Accessdenied)
			{
				$oRestapi_Accessdenied->delete();
			}
		}

		$this->_checkMode();

		if (is_null($this->_mode))
		{
			$this->_error = 'Wrong Content-Type';
			$this->_statusCode = 400;

			return FALSE;
		}

		// Check main entity
		if (is_null($this->path))
		{
			$this->_error = 'Empty Request';
			$this->_statusCode = 400;

			return FALSE;
		}

		$oPreviosEntity = NULL;
		$bFound = FALSE;

		$aPath = explode('/', $this->path);

		$sMethod = Core_Array::get($_SERVER, 'REQUEST_METHOD');

		if ($this->_config['log'])
		{
			Core_Log::instance()->clear()
				->notify(FALSE)
				->status(Core_Log::$MESSAGE)
				->write(sprintf('REST API: method "%s", path: "%s"', $sMethod, $this->path));
		}

		switch ($sMethod)
		{
			// SELECT ITEMS
			case 'GET':
				switch ($minorVersion)
				{
					case 0:
						$mAnswer = $this->_selectByPathV10($aPath);
					break;
					case 1:
						$mAnswer = $this->_selectByPathV11($aPath);
					break;
					default:
						$this->_error = 'Wrong version!';
						$this->_statusCode = 400;
						return FALSE;
				}

				if ($this->_statusCode == 200)
				{
					$this->_answer = $mAnswer;
				}
			break;
			// CREATE NEW ITEM
			case 'POST':
				$iCount = count($aPath);
				$newEntity = NULL;

				foreach ($aPath as $key => $path)
				{
					// Last Item
					if ($key == $iCount - 1)
					{
						$sSingularName = $this->_getClassName($path);

						if ($sSingularName === FALSE)
						{
							return FALSE;
						}

						$newEntity = Core_Entity::factory($sSingularName);
					}
					elseif (is_numeric($path))
					{
						if (is_null($oPreviosEntity))
						{
							$this->_error = 'Entity Not Found';
							$this->_statusCode = 403;

							return FALSE;
						}

						$oPreviosEntity = $oPreviosEntity->getById($path);

						if (is_null($oPreviosEntity))
						{
							$this->_error = 'Entity Not Found By PK';
							$this->_statusCode = 403;

							return FALSE;
						}

						// Check access
						if (!$this->_user->checkObjectAccess($oPreviosEntity))
						{
							$this->_error = sprintf('Entity %d. Access Forbidden.', $path);
							$this->_statusCode = 403;

							return FALSE;
						}
					}
					else
					{
						$sSingularName = $this->_getClassName($path);

						if ($sSingularName === FALSE)
						{
							return FALSE;
						}

						$oPreviosEntity = Core_Entity::factory($sSingularName);
					}
				}

				if (is_null($newEntity))
				{
					$this->_error = 'Wrong New Entity Name';
					$this->_statusCode = 422;

					return FALSE;
				}

				if ($this->_contentType == 'application/json')
				{
					$rawData = @file_get_contents("php://input");

					if (strlen($rawData) > 2)
					{
						try {
							$aJson = json_decode($rawData, TRUE);
							foreach ($aJson as $key => $value)
							{
								if (!is_array($value))
								{
									$newEntity->$key = $value;
								}
								else
								{
									$this->_error = sprintf('Wrong JSON data, field %s', htmlspecialchars($key));
									$this->_statusCode = 422;

									return FALSE;
								}
							}

							Core_Event::notify(get_class($this) . '.onBeforeAddNewEntity', $this, array($newEntity, $aJson));

							is_null($oPreviosEntity)
								? $newEntity->save()
								: $oPreviosEntity->add($newEntity);

							$this->_answer = $newEntity->getPrimaryKey();
						}
						catch (Exception $e)
						{
							$this->_error = $e->getMessage();
							$this->_statusCode = 422;

							return FALSE;
						}
					}
					else
					{
						$this->_error = 'Wrong POST data';
						$this->_statusCode = 422;

						return FALSE;
					}
				}
				else
				{
					$this->_error = sprintf('Wrong Content-Type "%s", expected "application/json"', htmlspecialchars($this->_contentType));
					$this->_statusCode = 422;

					return FALSE;
				}
			break;
			// EDIT ITEM
			case 'PUT':
				$sMethodName = NULL;
				foreach ($aPath as $key => $path)
				{
					if ($key == 0)
					{
						$sSingularName = $this->_getClassName($path);

						if ($sSingularName === FALSE)
						{
							return FALSE;
						}

						$oPreviosEntity = Core_Entity::factory($sSingularName);
					}
					elseif ($key == 1 && is_numeric($path))
					{
						$oPreviosEntity = $oPreviosEntity->getById($path);

						// Entity exists
						if (is_null($oPreviosEntity))
						{
							$this->_error = 'Entity Not Found By PK';
							$this->_statusCode = 403;

							return FALSE;
						}

						// Check access
						if (!$this->_user->checkObjectAccess($oPreviosEntity))
						{
							$this->_error = sprintf('Entity %d. Access Forbidden.', $path);
							$this->_statusCode = 403;

							return FALSE;
						}

						$bFound = TRUE;
					}
					elseif ($key == 2)
					{
						$sMethodName = $path;
					}
					else
					{
						$this->_error = sprintf('Unexpected %s', $path);
						$this->_statusCode = 422;

						return FALSE;
					}
				}

				if ($bFound)
				{
					$rawData = $this->_contentType != 'multipart/form-data'
						? @file_get_contents("php://input")
						: NULL;

					// Call method
					if ($sMethodName)
					{
						if (method_exists($oPreviosEntity, $sMethodName))
						{
							$args = array();

							try {
								// PHP 8.4
								// Вызов функции request_parse_body поглощает поток php://input, после этого поток php://input будет пуст.
								// Если php://input поток был прочитан ранее (например file_get_contents('php://input'), функция request_parse_body возвратит пустой результат ([0 => [], 1 => []]).
								// [$_POST, $_FILES]  = request_parse_body();

								if ($this->_contentType == 'multipart/form-data')
								{
									list($_POST, $_FILES) = Core_Http::requestParseBody($this->_boundary);
								}
								elseif (strlen($rawData))
								{
									$aJson = @json_decode($rawData, TRUE);
									is_array($aJson) && $args = $aJson;
								}

								$this->_answer = call_user_func_array(array($oPreviosEntity, $sMethodName), $args);
							}
							catch (Exception $e)
							{
								$this->_error = $e->getMessage();
								$this->_statusCode = 422;

								return FALSE;
							}

							$this->_statusCode = 201;
							is_null($this->_answer) && $this->_answer = 'OK';
						}
						else
						{
							$this->_error = sprintf('Udefined method %s()', htmlspecialchars($sMethodName));
							$this->_statusCode = 422;

							return FALSE;
						}
					}
					elseif (strlen($rawData) > 2)
					{
						if ($this->_contentType == 'application/json')
						{
							try {
								$aJson = json_decode($rawData, TRUE);
								foreach ($aJson as $key => $value)
								{
									if (!is_array($value))
									{
										$oPreviosEntity->$key = $value;
									}
									else
									{
										$this->_error = sprintf('Wrong JSON data, field %s', htmlspecialchars($key));
										$this->_statusCode = 422;

										return FALSE;
									}
								}

								Core_Event::notify(get_class($this) . '.onBeforeUpdateEntity', $this, array($oPreviosEntity, $aJson));

								$oPreviosEntity->save();

								// Created
								$this->_statusCode = 201;
								$this->_answer = 'OK';
							}
							catch (Exception $e)
							{
								$this->_error = $e->getMessage();
								$this->_statusCode = 422;

								return FALSE;
							}
						}
						else
						{
							$this->_error = sprintf('Wrong Content-Type "%s", expected "application/json"', htmlspecialchars($this->_contentType));
							$this->_statusCode = 422;

							return FALSE;
						}
					}
					else
					{
						$this->_error = 'Wrong POST data';
						$this->_statusCode = 422;

						return FALSE;
					}
				}
			break;
			// DELETE ITEM
			case 'DELETE':
				switch ($minorVersion)
				{
					case 0:
						$mAnswer = $this->_selectByPathV10($aPath);
					break;
					case 1:
						$mAnswer = $this->_selectByPathV11($aPath);
					break;
					default:
						$this->_error = 'Wrong version!';
						$this->_statusCode = 400;
						return FALSE;
				}

				if ($this->_statusCode == 200)
				{
					!is_array($mAnswer)
						&& $mAnswer = array($mAnswer);

					$i = 0;

					foreach ($mAnswer as $oEntity)
					{
						try {
							is_null($oEntity->getMarksDeleted())
								? $oEntity->delete()
								: $oEntity->markDeleted();
							$i++;
						}
						catch (Exception $e)
						{
							$this->_error = $e->getMessage();
							$this->_statusCode = 422;

							return FALSE;
						}
					}

					//$this->_statusCode = 204; // No Content
					$this->_answer = sprintf('Deleted %d item(s)', $i);
				}
				else
				{
					return $mAnswer;
				}
			break;
			case 'OPTIONS':
				$this->_oCore_Response->header('Allow', 'GET,POST,PUT,DELETE,OPTIONS');

				foreach ($aPath as $key => $path)
				{
					if ($key == 0)
					{
						$sSingularName = $this->_getClassName($path);

						if ($sSingularName === FALSE)
						{
							return FALSE;
						}

						$oPreviosEntity = Core_Entity::factory($sSingularName);
					}
					else
					{
						$this->_error = sprintf('Unexpected %s', $path);
						$this->_statusCode = 422;

						return FALSE;
					}
				}

				// Fields
				$this->_answer['fields'] = $oPreviosEntity->getTableColumns();

				// Methods
				//$this->_answer['methods'] = get_class_methods($oPreviosEntity);
				$oReflectionClass = new ReflectionClass($oPreviosEntity);

				$aMethods = $oReflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
				foreach ($aMethods as $oMethod)
				{
					$this->_answer['methods'][] = $oMethod->name;
				}

				// Relations
				if ($oPreviosEntity instanceof Core_ORM)
				{
					$aRelations = $oPreviosEntity->getRelations();

					foreach ($aRelations as $tmpKey => $aRelation)
					{
						$this->_answer['relations'][] = $tmpKey;
					}
				}

			break;
			default:
				$this->_error = sprintf('Method "%s" Not Allowed', htmlspecialchars($sMethod));
				$this->_statusCode = 405;
			break;
		}
	}

	/**
	 * Get class name by $pluralName
	 * @param string $pluralName
	 * @return string|FALSE
	 */
	protected function _getClassName($pluralName)
	{
		//$oCore_Inflection_En = new Core_Inflection_En();
		//$sSingularName = $oCore_Inflection_En->singular($pluralName);
		$sSingularName = Core_Inflection::getSingular($pluralName);

		if (/*$sSingularName == $pluralName && !$oCore_Inflection_En->isPluralIrrigular($pluralName)
			|| */!class_exists(Core_Entity::getClassName($sSingularName)))
		{
			$this->_error = sprintf('Wrong Entity "%s"', $pluralName);
			$this->_statusCode = 422;

			return FALSE;
		}

		return $sSingularName;
	}

	/**
	 * Сheck Content Type by $_SERVER['HTTP_ACCEPT'] and $_SERVER['CONTENT_TYPE']
	 * @return boolean
	 */
	protected function _checkMode()
	{
		$this->_mode = NULL;

		// HTTP: Accept
		$httpAccept = Core_Array::get($_SERVER, 'HTTP_ACCEPT', 'application/json', 'str');
		$aAcccept = array_map('trim', explode(',', $httpAccept));
		foreach ($aAcccept as $accept)
		{
			$aTmp = explode(';', $accept);
			switch ($aTmp[0])
			{
				case 'application/json':
					$this->_mode = 'json';
				break 2;
				case 'application/xml':
					$this->_mode = 'xml';
				break 2;
			}
		}

		// HTTP: Content-Type
		$contentType = Core_Array::get($_SERVER, 'CONTENT_TYPE', 'application/json; charset=utf-8', 'str');
		$aContentTypes = array_map('trim', explode(',', $contentType));
		foreach ($aContentTypes as $sContentType)
		{
			$aTmp = array_map('trim', explode(';', $sContentType));

			switch ($aTmp[0])
			{
				case 'application/json':
				case 'multipart/form-data':
					$this->_contentType = $aTmp[0];
					is_null($this->_mode) && $this->_mode = 'json';

					if ($this->_contentType == 'multipart/form-data' && isset($aTmp[1]))
					{
						preg_match('/boundary=(.*)$/', $aTmp[1], $matches);
						isset($matches[1]) && $this->_boundary = $matches[1];
					}
				break 2;
				case 'application/xml':
					$this->_contentType = $aTmp[0];
					is_null($this->_mode) && $this->_mode = 'xml';
				break 2;
			}
		}

		return !is_null($this->_mode);
	}

	/**
	 * Get Request Headers. Use apache_request_headers() or $_SERVER
	 * @return array
	 */
	protected function _getRequestHeaders()
	{
		// PHP 5.4.0: This function became available under FastCGI. Previously, it was supported when PHP was installed as an Apache module or by the NSAPI server module in Netscape/iPlanet/SunONE webservers.
		// PHP 7.3.0: This function became available in the FPM SAPI.
		// FPM available just from 7.3
		if (function_exists('apache_request_headers'))
		{
			$aHeaders = apache_request_headers();
		}
		else
		{
			$aHeaders = array();
			foreach($_SERVER as $key => $val)
			{
				if (substr($key, 0, 5) == 'HTTP_')
				{
					$aKey = array_map('ucfirst',
						explode('_', strtolower(substr($key, 5)))
					);
					$key = implode('-', $aKey);

					$aHeaders[$key] = $val;
				}
			}
		}

		// fix bug with adding REDIRECT_ while using "RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]"
		if (!isset($aHeaders['Authorization']))
		{
			foreach ($_SERVER as $key => $value)
			{
				if (preg_replace('/^(REDIRECT_)*/', '', $key) == 'HTTP_AUTHORIZATION')
				{
					$aHeaders['Authorization'] = $_SERVER[$key];
					break;
				}
			}
		}

		return $aHeaders;
	}

	/**
	 * Chech Authorization
	 */
	protected function _checkAuthorization()
	{
		$this->_user = NULL;

		$aHeaders = $this->_getRequestHeaders();
		if (isset($aHeaders['Authorization']))
		{
			if (preg_match('/Bearer ([0-9a-zA-Z]+)/', $aHeaders['Authorization'], $matches))
			{
				if (isset($matches[1]))
				{
					$sCurrentDate = Core_Date::timestamp2sql(time());

					$oRestapi_Tokens = Core_Entity::factory('Restapi_Token');
					$oRestapi_Tokens->queryBuilder()
						->where('token', '=', $matches[1])
						->where('active', '=', 1)
						->where('datetime', '<=', $sCurrentDate)
						->open()
							->where('expire', '=', '0000-00-00 00:00:00')
							->setOr()
							->where('expire', '>', $sCurrentDate)
						->close()
						->limit(1);

					$aRestapi_Tokens = $oRestapi_Tokens->findAll(FALSE);
					if (isset($aRestapi_Tokens[0]))
					{
						$oRestapi_Token = $aRestapi_Tokens[0];

						if (!$oRestapi_Token->https || Core::httpsUses())
						{
							if ($oRestapi_Token->user_id)
							{
								$oUser = $oRestapi_Token->User;
								if ($oUser->active && !$oUser->read_only && !$oUser->dismissed)
								{
									$this->_user = $oUser;
								}
							}
						}
					}
				}
			}
		}

		return !is_null($this->_user);
	}
}