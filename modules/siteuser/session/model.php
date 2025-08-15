<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Session_Model
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Siteuser_Session_Model extends Core_Entity
{
	/**
	 * Model name
	 * @var mixed
	 */
	protected $_modelName = 'siteuser_session';

	/**
	 * Disable markDeleted()
	 * @var mixed
	 */
	protected $_marksDeleted = NULL;

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'siteuser' => array()
	);

	/**
	 * Default sorting for models
	 * @var array
	 */
	protected $_sorting = array(
		'siteuser_sessions.id' => 'ASC',
	);

	/**
	 * Constructor.
	 * @param int $id entity ID
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		if (is_null($id) && !$this->loaded())
		{
			$this->_preloadValues['time'] = time();
		}
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function idBackend()
	{
		return Core_Str::stringToSecret($this->id, 14);
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function imgBackend()
	{
		!is_null($this->dataSession) && Core_Html_Entity::factory('Span')
			->value('<i class="fa fa-check-circle green" title="Session exists"></i>')
			->execute();
	}

	/**
	 * Backend field
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function siteuser_idBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		if (Core::moduleIsActive('siteuser') && $this->siteuser_id)
		{
			$oSiteuser = $this->Siteuser;

			$href = $oAdmin_Form_Controller->getAdminActionLoadHref(array('path' => '/{admin}/siteuser/index.php', 'action' => 'edit', 'datasetKey' => 0, 'datasetValue' => $this->siteuser_id));

			return '<a target="_blank" href="' . $href . '">' . $oSiteuser->login . '</a>';
		}
	}

	/**
	 * Backend callback method
	 * @return string
	 */
 	public function timeBackend()
	{
		return Core_Date::timestamp2string($this->time);
	}

	/**
	 * Backend callback method
	 * @return string
	 */
 	public function osBackend()
	{
		return !is_null($this->user_agent)
			? Core_Browser::getOs($this->user_agent)
			: '—';
	}

	/**
	 * Backend callback method
	 * @return string
	 */
 	public function deviceBackend()
	{
		$return = '—';

		if (!is_null($this->user_agent))
		{
			$device = Core_Browser::getDevice($this->user_agent);

			switch ($device)
			{
				case 0:
					$icon = 'fa-desktop';
				break;
				case 1:
					$icon = 'fa-tablet';
				break;
				case 2:
					$icon = 'fa-mobile-phone';
				break;
				case 3:
					$icon = 'fa-tv';
				break;
				case 3:
					$icon = 'fa-clock-0';
				break;
			}

			$return = '<i class="fa ' . $icon . '" title="' . Core::_('Siteuser_Session.device' . $device) . '"></i>';
		}

		return $return;
	}

	/**
	 * Backend callback method
	 * @return string
	 */
 	public function browserBackend()
	{
		$browser = !is_null($this->user_agent)
			? Core_Browser::getBrowser($this->user_agent)
			: '—';

		if (!is_null($browser))
		{
			$ico = Core_Browser::getBrowserIco($browser);

			!is_null($ico)
				&& $browser = '<i class="' . $ico . '"></i> ' . $browser;
		}

		return $browser;
	}

	/**
	 * Destroy user session
	 */
 	public function destroy()
	{
		Core_Session::destroy($this->id);
		$this->delete();

		return $this;
	}
}