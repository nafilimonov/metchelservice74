<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Restapi_Token_Model
 *
 * @package HostCMS
 * @subpackage Restapi
 * @version 7.x
 * @author Hostmake LLC
 * @copyright © 2005-2022 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Restapi_Token_Model extends Core_Entity
{
	/**
	 * Model name
	 * @var mixed
	 */
	protected $_modelName = 'restapi_token';

	/**
	 * Column consist item's name
	 * @var string
	 */
	protected $_nameColumn = 'token';

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'user' => array()
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
			$oUser = Core_Auth::getCurrentUser();
			$this->_preloadValues['user_id'] = is_null($oUser) ? 0 : $oUser->id;
			$this->_preloadValues['datetime'] = Core_Date::timestamp2sql(time());
		}
	}

	/**
	 * Change active
	 * @return self
	 * @hostcms-event restapi_token.onBeforeChangeActive
	 * @hostcms-event restapi_token.onAfterChangeActive
	 */
	public function changeActive()
	{
		Core_Event::notify($this->_modelName . '.onBeforeChangeActive', $this);

		$this->active = 1 - $this->active;
		$this->save();

		Core_Event::notify($this->_modelName . '.onAfterChangeActive', $this);

		return $this;
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function user_nameBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$oUser = $this->User;
		return $oUser->id
			? $oUser->showAvatarWithName()
			: '';
	}

	/**
	 * Backend callback method
	 * @return string
	 */
	public function tokenStatusBackend()
	{
		ob_start();

		if (!$this->active || $this->expire != '0000-00-00 00:00:00' && time() > Core_Date::sql2timestamp($this->expire))
		{
			$expire = 1;
			$class = 'fa fa-ban darkorange';
		}
		else
		{
			$expire = 0;
			$class = 'fa fa-check palegreen';
		}

		?><span class="<?php echo $class?>" title="<?php echo Core::_('Restapi_Token.expire_' . $expire)?>"></span><?php

		return ob_get_clean();
	}
}