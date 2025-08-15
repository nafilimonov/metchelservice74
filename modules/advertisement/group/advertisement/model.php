<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Advertisement_Group_Advertisement_Model
 *
 * @package HostCMS
 * @subpackage Advertisement
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2017 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Advertisement_Group_Advertisement_Model extends Advertisement_Model
{
	/**
	 * Name of the table
	 * @var string
	 */
	protected $_tableName = 'advertisements';

	/**
	 * Name of the model
	 * @var string
	 */
	protected $_modelName = 'advertisement_group_advertisement';

	/**
	 * Backend property
	 * @var int
	 */
	public $in_group = NULL;

	/**
	 * Backend property
	 * @var int
	 */
	public $ctr = NULL;

	/**
	 * Backend property
	 * @var int
	 */
	public $probability = NULL;

	/**
	 * Save object. Use self::update() or self::create()
	 * @return self
	 */
	public function save()
	{
		// Идентификатор почтовой рассылки
		$advertisement_group_id = intval(Core_Array::getGet('advertisement_group_id'));
		$oAdvertisement_Group = Core_Entity::factory('Advertisement_Group')->find($advertisement_group_id);

		if (!is_null($oAdvertisement_Group->id))
		{
			$oAdvertisement_Group_List = $oAdvertisement_Group->Advertisement_Group_Lists->getByAdvertisementId($this->id);

			if ($this->in_group)
			{
				$this->probability = !$this->probability || $this->probability < 1 || $this->probability > 100
					? 100
					: intval($this->probability);

				if (is_null($oAdvertisement_Group_List))
				{
					$oAdvertisement_Group_List = Core_Entity::factory('Advertisement_Group_List');
					$oAdvertisement_Group_List->advertisement_id = $this->id;
					$oAdvertisement_Group_List->probability = $this->probability;
					$oAdvertisement_Group->add($oAdvertisement_Group_List);
				}
				else
				{
					$oAdvertisement_Group_List->probability = $this->probability;
					$oAdvertisement_Group_List->save();
				}
			}
			elseif (!is_null($oAdvertisement_Group_List))
			{
				$oAdvertisement_Group_List->delete();
			}
		}

		return $this;
	}
}