<?php
/**
 * Site users.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
require_once('../../bootstrap.php');

// Код формы
$iAdmin_Form_Id = 30;
$sAdminFormAction = '/{admin}/siteuser/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

if (Core_Auth::logged())
{
	Core_Auth::checkBackendBlockedIp();

	// Контроллер формы
	$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);

	if (!is_null(Core_Array::getGet('loadPersonAvatar')) || !is_null(Core_Array::getGet('loadCompanyAvatar')))
	{
		Core_Session::close();

		$name = NULL;

		if (Core_Array::getGet('loadPersonAvatar'))
		{
			$id = Core_Array::getGet('loadPersonAvatar', 0, 'int');

			$oSiteuser_Person = $id
				? Core_Entity::factory('Siteuser_Person')->getById($id)
				: NULL;

			$oSiteuser_Person
				&& $name = trim(strval($oSiteuser_Person->name) . ' ' . strval($oSiteuser_Person->surname));
		}
		else
		{
			$id = Core_Array::getGet('loadCompanyAvatar', 0, 'int');

			$oSiteuser_Company = $id
				? Core_Entity::factory('Siteuser_Company')->getById($id)
				: NULL;

			$oSiteuser_Company
				&& $name = strval($oSiteuser_Company->name);
		}

		if (!is_null($name))
		{
			// Get initials
			$initials = Core_Str::getInitials($name);

			$bgColor = Core_Str::createColor($id);

			Core_Image::avatar($initials, $bgColor, $width = 130, $height = 130);
		}

		die();
	}

	if (!is_null(Core_Array::getGet('loadSiteusers')) && !is_null(Core_Array::getGet('term')))
	{
		Core_Auth::setCurrentSite();

		$aJSON = array();

		$aTypes = Core_Array::getGet('types', array('siteuser'));

		$sQuery = trim(Core_Str::stripTags(strval(Core_Array::getGet('term'))));

		if (strlen($sQuery))
		{
			if (in_array('siteuser', $aTypes))
			{
				$oSiteusers = Core_Entity::factory('Site', CURRENT_SITE)->Siteusers;
				$oSiteusers->queryBuilder()
					->open()
						->where('siteusers.login', 'LIKE', '%' . $sQuery . '%')
						->setOr()
						->where('siteusers.id', '=', $sQuery)
						->setOr()
						->where('siteusers.email', 'LIKE', '%' . $sQuery . '%')
					->close()
					->limit(Core::$mainConfig['autocompleteItems']);

				$aSiteusers = $oSiteusers->findAll(FALSE);

				foreach ($aSiteusers as $oSiteuser)
				{
					$aJSON[] = prepareSiteuserJSON($oSiteuser);
				}
				
				// При siteuser для компаний и представителей будет возвращаться ID от siteuser
				$mode = 'siteuser';
			}
			else
			{
				$mode = 'default';
			}

			if (in_array('siteuser', $aTypes) || in_array('person', $aTypes))
			{
				$oSiteuser_People = Core_Entity::factory('Siteuser_Person');
				$oSiteuser_People->queryBuilder()
					->join('siteusers', 'siteuser_people.siteuser_id', '=', 'siteusers.id')
					->open()
						->where('siteuser_people.name', 'LIKE', '%' . $sQuery . '%')
						->setOr()
						->where('siteuser_people.surname', 'LIKE', '%' . $sQuery . '%')
						->setOr()
						->where('siteuser_people.patronymic', 'LIKE', '%' . $sQuery . '%')
						->setOr()
						->where('siteusers.login', 'LIKE', '%' . $sQuery . '%')
					->close()
					->where('siteusers.site_id', '=', CURRENT_SITE)
					->where('siteusers.deleted', '=', 0)
					->limit(Core::$mainConfig['autocompleteItems']);

				$aSiteuser_People = $oSiteuser_People->findAll(FALSE);

				foreach ($aSiteuser_People as $oSiteuser_Person)
				{
					$aJSON[] = prepareSiteuserJSONPerson($oSiteuser_Person, $mode);
				}
			}

			if (in_array('siteuser', $aTypes) || in_array('company', $aTypes))
			{
				$oSiteuser_Companies = Core_Entity::factory('Siteuser_Company');
				$oSiteuser_Companies->queryBuilder()
					->join('siteusers', 'siteuser_companies.siteuser_id', '=', 'siteusers.id')
					->open()
						->where('siteuser_companies.name', 'LIKE', '%' . $sQuery . '%')
						->setOr()
						->where('siteusers.login', 'LIKE', '%' . $sQuery . '%')
					->close()
					->where('siteusers.site_id', '=', CURRENT_SITE)
					->where('siteusers.deleted', '=', 0)
					->limit(Core::$mainConfig['autocompleteItems']);

				$aSiteuser_Companies = $oSiteuser_Companies->findAll(FALSE);

				foreach ($aSiteuser_Companies as $oSiteuser_Company)
				{
					$aJSON[] = prepareSiteuserJSONCompany($oSiteuser_Company, $mode);
				}
			}
		}

		Core::showJson($aJSON);
	}

	if (!is_null(Core_Array::getPost('showPopover')))
	{
		$aJSON = array(
			'html' => ''
		);

		$oCurrentUser = Core_Auth::getCurrentUser();

		$company_id = Core_Array::getPost('company_id', 0, 'int');
		$person_id = Core_Array::getPost('person_id', 0, 'int');

		$oEntity = $company_id
			? Core_Entity::factory('Siteuser_Company')->getById($company_id)
			: Core_Entity::factory('Siteuser_Person')->getById($person_id);

		if (!is_null($oEntity) && $oCurrentUser->checkObjectAccess($oEntity))
		{
			$aJSON['html'] = $oEntity->getProfilePopupBlock();
		}

		Core::showJson($aJSON);
	}
}

Core_Auth::authorization($sModule = 'siteuser');

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Siteuser.siteusers'))
	->pageTitle(Core::_('Siteuser.siteusers'));

function prepareSiteuserJSON($object)
{
	switch (get_class($object))
	{
		case 'Siteuser_Model':
		default:
			$aReturn = array(
				'id' => $object->id,
				'text' => $object->login . ' [' . $object->id . ']',
				'login' => $object->login,
				'companies' => array(),
				'people' => array(),
				'type' => 'siteuser'
			);

			// Добавить всех представителей и компании клиента
			$aSiteuser_Companies = $object->Siteuser_Companies->findAll(FALSE);
			foreach ($aSiteuser_Companies as $oSiteuser_Company)
			{
				$aReturn['companies'][] = prepareSiteuserJSONCompany($oSiteuser_Company);
			}

			$aSiteuser_People = $object->Siteuser_People->findAll(FALSE);
			foreach ($aSiteuser_People as $oSiteuser_Person)
			{
				$aReturn['people'][] = prepareSiteuserJSONPerson($oSiteuser_Person);
			}
		break;
		case 'Siteuser_Company_Model':
			$aReturn = prepareSiteuserJSONCompany($object);
		break;
		case 'Siteuser_Person_Model':
			$aReturn = prepareSiteuserJSONPerson($object);
		break;
	}

	return $aReturn;
}

function prepareSiteuserJSONCompany($object, $mode = 'default')
{
	$avatar = $object->getAvatar();

	$aDirectory_Phones = $object->Directory_Phones->findAll(FALSE);
	$phone = isset($aDirectory_Phones[0])
		? $aDirectory_Phones[0]->value
		: '';

	$aDirectory_Emails = $object->Directory_Emails->findAll(FALSE);
	$email = isset($aDirectory_Emails[0])
		? $aDirectory_Emails[0]->value
		: '';

	$aAddresses = array();

	$aDirectory_Addresses = $object->Directory_Addresses->findAll(FALSE);
	foreach ($aDirectory_Addresses as $oDirectory_Address)
	{
		$shop_country_id = $shop_country_location_city_id = $shop_country_location_id = 0;

		if (trim((string) $oDirectory_Address->city) !== '')
		{
			$oShop_Country_Location_City = Core_Entity::factory('Shop_Country_Location_City')->getByName($oDirectory_Address->city);

			if (!is_null($oShop_Country_Location_City))
			{
				$shop_country_location_city_id = $oShop_Country_Location_City->id;
				$shop_country_location_id = $oShop_Country_Location_City->shop_country_location_id;
				$shop_country_id = $oShop_Country_Location_City->Shop_Country_Location->Shop_Country->id;
			}
		}
		elseif (trim((string) $oDirectory_Address->country) !== '')
		{
			$oShop_Country = Core_Entity::factory('Shop_Country')->getByName($oDirectory_Address->country);

			if (!is_null($oShop_Country))
			{
				$shop_country_id = $oShop_Country->id;
			}
		}

		$aAddresses[] = array(
			'country' => intval($shop_country_id),
			'location' => intval($shop_country_location_id),
			'city' => intval($shop_country_location_city_id),
			'postcode' => $oDirectory_Address->postcode,
			'address' => $oDirectory_Address->value
		);
	}

	$tin = !empty($object->tin)
		// ? ' ➤ ' . $object->tin
		? $object->tin
		: '';

	$aReturn = array(
		'id' => $mode == 'default' ? 'company_' . $object->id : $object->siteuser_id,
		// 'text' => $object->name . ' [' . $object->Siteuser->login . '] ' . '%%%' . $avatar . '%%%' . $object->tin,
		'text' => $object->name /*. $tin . ' 👤 ' . $object->Siteuser->login*/ . '%%%' . $avatar,
		'name' => $object->name,
		'avatar' => $avatar,
		'phone' => $phone,
		'email' => $email,
		'tin' => $tin,
		'addresses' => $aAddresses,
		'login' => ' 👤 ' . $object->Siteuser->login,
		'siteuser_id' => $object->siteuser_id,
		'type' => 'company',
	);

	return $aReturn;
}

function prepareSiteuserJSONPerson($object, $mode = 'default')
{
	$avatar = $object->getAvatar();
	$fullName = $object->getFullName();

	$aDirectory_Phones = $object->Directory_Phones->findAll(FALSE);
	$phone = isset($aDirectory_Phones[0])
		? $aDirectory_Phones[0]->value
		: '';

	$aDirectory_Emails = $object->Directory_Emails->findAll(FALSE);
	$email = isset($aDirectory_Emails[0])
		? $aDirectory_Emails[0]->value
		: '';

	$shop_country_id = $shop_country_location_city_id = $shop_country_location_id = 0;

	if (trim((string) $object->city) !== '')
	{
		$oShop_Country_Location_City = Core_Entity::factory('Shop_Country_Location_City')->getByName($object->city);

		if (!is_null($oShop_Country_Location_City))
		{
			$shop_country_location_city_id = $oShop_Country_Location_City->id;
			$shop_country_location_id = $oShop_Country_Location_City->shop_country_location_id;
			$shop_country_id = $oShop_Country_Location_City->Shop_Country_Location->Shop_Country->id;
		}
	}
	elseif (trim((string) $object->country) !== '')
	{
		$oShop_Country = Core_Entity::factory('Shop_Country')->getByName($object->country);

		if (!is_null($oShop_Country))
		{
			$shop_country_id = $oShop_Country->id;
		}
	}

	$aReturn = array(
		'id' => $mode == 'default' ? 'person_' . $object->id : $object->siteuser_id,
		// 'text' => $fullName . ' [' . $object->Siteuser->login . '] ' . '%%%' . $avatar,
		'text' => $fullName . /*' 👤 ' . $object->Siteuser->login .*/ '%%%' . $avatar,
		'name' => $object->name,
		'surname' => $object->surname,
		'patronymic' => $object->patronymic,
		'avatar' => $avatar,
		'phone' => $phone,
		'email' => $email,
		'country' => intval($shop_country_id),
		'location' => intval($shop_country_location_id),
		'city' => intval($shop_country_location_city_id),
		'postcode' => $object->postcode,
		'address' => $object->address,
		'login' => ' 👤 ' . $object->Siteuser->login,
		'siteuser_id' => $object->siteuser_id,
		'type' => 'person',
	);

	return $aReturn;
}

if (!is_null(Core_Array::getRequest('loadSiteuserCard')) && !is_null(Core_Array::getRequest('phone')))
{
	$aCards = array();

	$phone = Core_Array::getRequest('phone', '', 'trim');

	$aSiteuser_Companies = Siteuser_Controller::getCompaniesByPhone($phone, FALSE);
	$aSiteuser_Companies = array_slice($aSiteuser_Companies, 0, 2);
	foreach ($aSiteuser_Companies as $oSiteuser_Company)
	{
		$aCards[] = array(
			'id' => $oSiteuser_Company->id,
			'siteuser_id' => $oSiteuser_Company->siteuser_id,
			'type' => 'company',
			'name' => $oSiteuser_Company->name,
			'avatar' => $oSiteuser_Company->getAvatar(),
		);
	}

	$aSiteuser_People = Siteuser_Controller::getPeopleByPhone($phone, FALSE);
	$aSiteuser_People = array_slice($aSiteuser_People, 0, 2);
	foreach ($aSiteuser_People as $oSiteuser_Person)
	{
		$aCards[] = array(
			'id' => $oSiteuser_Person->id,
			'siteuser_id' => $oSiteuser_Person->siteuser_id,
			'type' => 'person',
			'name' => $oSiteuser_Person->getFullName(),
			'avatar' => $oSiteuser_Person->getAvatar(),
		);
	}

	ob_start();

	if (count($aCards))
	{
		?><div class="siteuser-cards-wrapper"><?php
			foreach ($aCards as $aCard)
			{
				$dataset = $aCard['type'] == 'company'
					? 0
					: 1;

				?><div class="siteuser-card" onclick="$.modalLoad({path: '<?php echo Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/representative/index.php')?>', action: 'view', operation: 'modal', additionalParams: 'hostcms[checked][<?php echo $dataset?>][<?php echo $aCard['id']?>]=1&siteuser_id=<?php echo $aCard['siteuser_id']?>', windowId: 'id_content', width: '90%'}); return false" data-type="<?php echo $aCard['type']?>" title="<?php echo htmlspecialchars($aCard['name'])?>"><img class="siteuser-avatar margin-right-5" src="<?php echo htmlspecialchars($aCard['avatar'])?>"/><span><?php echo htmlspecialchars($aCard['name'])?></span></div><?php
			}
		?></div><?php
	}

	Core::showJson(
		array(
			'html' => ob_get_clean()
		)
	);
}

// Загрузка данных о вновь созданном клиенте в select2
if (!is_null(Core_Array::getGet('loadSiteuserSelect2')))
{
	$iSiteuser_id = intval(Core_Array::getGet('loadSiteuserSelect2'));
	$oSiteuser = Core_Entity::factory('Siteuser')->find($iSiteuser_id);

	$aJSON = !is_null($oSiteuser->id)
		? prepareSiteuserJSON($oSiteuser)
		: array('error' => 'Siteuser not found');

	Core::showJson($aJSON);
}

if (!is_null(Core_Array::getPost('loadSelect2Avatars')))
{
	$aJSON = array(
		'result' => 'error',
		'html' => ''
	);

	$iSiteuser_id = intval(Core_Array::getPost('siteuser_id'));
	$oSiteuser = Core_Entity::factory('Siteuser')->find($iSiteuser_id);

	if (!is_null($oSiteuser))
	{
		$aJSON = array(
			'result' => 'success',
			'html' => Siteuser_Controller_Edit::addSiteuserRepresentativeAvatars($oSiteuser)
		);
	}

	Core::showJson($aJSON);
}

// Загрузка данных о вновь созданной компании в select2
if (!is_null(Core_Array::getGet('loadSiteuserCompanySelect2')))
{
	$iSiteuser_company_id = intval(Core_Array::getGet('loadSiteuserCompanySelect2'));
	$oSiteuser_Company = Core_Entity::factory('Siteuser_Company')->find($iSiteuser_company_id);

	$aJSON = !is_null($oSiteuser_Company->id)
		? prepareSiteuserJSON($oSiteuser_Company)
		: array('error' => 'Siteuser Company not found');

	Core::showJson($aJSON);
}

// Загрузка данных о вновь созданном представителе в select2
if (!is_null(Core_Array::getGet('loadSiteuserPersonSelect2')))
{
	$iSiteuser_person_id = intval(Core_Array::getGet('loadSiteuserPersonSelect2'));
	$oSiteuser_Person = Core_Entity::factory('Siteuser_Person')->find($iSiteuser_person_id);

	$aJSON = !is_null($oSiteuser_Person->id)
		? prepareSiteuserJSON($oSiteuser_Person)
		: array('error' => 'Siteuser Person not found');

	Core::showJson($aJSON);
}

$sSiteusers = Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/siteuser/index.php');
$sSiteuserProperties = Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/property/index.php');
$sSiteuserRepresentative = Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/representative/index.php');
$sSiteuserTypes = Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/type/index.php');
$sSiteuserStatuses = Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/status/index.php');
$sSiteuserSources = Admin_Form_Controller::correctBackendPath('/{admin}/crm/source/index.php');
$sSiteuserRepresentativeContracts = Admin_Form_Controller::correctBackendPath('/{admin}/siteuser/company/contract/index.php');

// Меню формы
$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

// Элементы меню
$oAdmin_Form_Entity_Menus->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Siteuser.siteuser'))
		->icon('fa fa-user')
		->add(
			Admin_Form_Entity::factory('Menu')
				->name(Core::_('Admin_Form.add'))
				->icon('fa fa-plus')
				->href(
					$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, 0)
				)
				->onclick(
					$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, 0)
				)
		)
		->add(
			Admin_Form_Entity::factory('Menu')
				->name(Core::_('Admin_Form.tabProperties'))
				->icon('fa fa-cogs')
				->href(
					$oAdmin_Form_Controller->getAdminLoadHref($sSiteuserProperties, NULL, NULL, '')
				)
				->onclick(
					$oAdmin_Form_Controller->getAdminLoadAjax($sSiteuserProperties, NULL, NULL, '')
				)
		)
		->add(
			Admin_Form_Entity::factory('Menu')
				->name(Core::_('Siteuser.add_list'))
				->icon('fa fa-list')
				->href(
					$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'addSiteusersList', NULL, 0, 0)
				)
				->onclick(
					$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'addSiteusersList', NULL, 0, 0)
				)
		)
		->add(
			Admin_Form_Entity::factory('Menu')
				->name(Core::_('Siteuser.export_siteusers'))
				->icon('fa fa-upload')
				->target('_blank')
				->href(
					$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'exportSiteusersList', NULL, 0, 0)
				)
		)
)
->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Siteuser.companies'))
		->icon('fa fa-building-o')
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($sSiteuserRepresentative, NULL, NULL, 'show=company')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($sSiteuserRepresentative, NULL, NULL, 'show=company')
		)
)
->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Siteuser.people'))
		->icon('fa fa-user')
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($sSiteuserRepresentative, NULL, NULL, 'show=person')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($sSiteuserRepresentative, NULL, NULL, 'show=person')
		)
)
->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Siteuser_Group.title'))
		->icon('fa fa-folder-o')
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($sSiteusers, NULL, NULL, '')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($sSiteusers, NULL, NULL, '')
		)
)
->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Siteuser.su_menu_directories'))
		->icon('fa fa-book')
		->add(
				Admin_Form_Entity::factory('Menu')
					->name(Core::_('Siteuser_Type.siteuser_types_title'))
					->icon('fa fa-bars')
					->href(
						$oAdmin_Form_Controller->getAdminLoadHref($sSiteuserTypes)
					)
					->onclick(
						$oAdmin_Form_Controller->getAdminLoadAjax($sSiteuserTypes)
					)
			)
		->add(
				Admin_Form_Entity::factory('Menu')
					->name(Core::_('Siteuser_Status.siteuser_statuses_title'))
					->icon('fa fa-flag-o')
					->href(
						$oAdmin_Form_Controller->getAdminLoadHref($sSiteuserStatuses)
					)
					->onclick(
						$oAdmin_Form_Controller->getAdminLoadAjax($sSiteuserStatuses)
					)
			)
		->add(
			Admin_Form_Entity::factory('Menu')
				->name(Core::_('Crm_Source.siteuser_sources_title'))
				->icon('fa fa-user-plus')
				->href(
					$oAdmin_Form_Controller->getAdminLoadHref($sSiteuserSources)
				)
				->onclick(
					$oAdmin_Form_Controller->getAdminLoadAjax($sSiteuserSources)
				)
		)
)
->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Siteuser.contracts'))
		->icon('fa fa-file-contract')
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($sSiteuserRepresentativeContracts, NULL, NULL, '')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($sSiteuserRepresentativeContracts, NULL, NULL, '')
		)
)->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Siteuser.session'))
		->icon('fa fa-history')
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref('/{admin}/siteuser/session/index.php', NULL, NULL, '')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax('/{admin}/siteuser/session/index.php', NULL, NULL, '')
		)
)->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Siteuser.accessdenied'))
		->icon('fa fa-ban')
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref('/{admin}/siteuser/accessdenied/index.php', NULL, NULL, '')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax('/{admin}/siteuser/accessdenied/index.php', NULL, NULL, '')
		)
);

// Добавляем все меню контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);

// Глобальный поиск
$additionalParams = '';

$sGlobalSearch = Core_Array::getGet('globalSearch', '', 'trim');

$oAdmin_Form_Controller->addEntity(
	Admin_Form_Entity::factory('Code')
		->html('
			<div class="row search-field margin-bottom-20">
				<div class="col-xs-12">
					<form action="' . $oAdmin_Form_Controller->getPath() . '" method="GET">
						<input type="text" name="globalSearch" class="form-control" placeholder="' . Core::_('Admin.placeholderGlobalSearch') . '" value="' . htmlspecialchars($sGlobalSearch) . '" />
						<i class="fa fa-times-circle no-margin" onclick="' . $oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), '', '', $additionalParams) . '"></i>
						<button type="submit" class="btn btn-default global-search-button" onclick="' . $oAdmin_Form_Controller->getAdminSendForm('', '', $additionalParams) . '"><i class="fa-solid fa-magnifying-glass fa-fw"></i></button>
					</form>
				</div>
			</div>
		')
);

// Построение хлебных крошек
$oAdminFormEntityBreadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

// Первая хлебная крошка будет всегда
$oAdminFormEntityBreadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Siteuser.siteusers'))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath())
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath())
		)
	);

// Хлебные крошки добавляем контроллеру
$oAdmin_Form_Controller->addEntity($oAdminFormEntityBreadcrumbs);

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oSiteuser_Group_Controller_Edit = Admin_Form_Action_Controller::factory(
		'Siteuser_Controller_Edit', $oAdmin_Form_Action
	);

	// Хлебные крошки доступны только форме редактирования
	$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

	$oAdmin_Form_Entity_Breadcrumbs->add(
		Admin_Form_Entity::factory('Breadcrumb')
			->name(Core::_('Siteuser.siteusers'))
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, '')
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, '')
			)
	);

	$oSiteuser_Group_Controller_Edit->addEntity($oAdmin_Form_Entity_Breadcrumbs);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oSiteuser_Group_Controller_Edit);
}

// Действие "Применить"
$oAdminFormActionApply = $oAdmin_Form->Admin_Form_Actions->getByName('apply');

if ($oAdminFormActionApply && $oAdmin_Form_Controller->getAction() == 'apply')
{
	$oControllerApply = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Apply', $oAdminFormActionApply
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oControllerApply);
}

// Действие "Копировать"
$oAdminFormActionCopy = $oAdmin_Form->Admin_Form_Actions->getByName('copy');

if ($oAdminFormActionCopy && $oAdmin_Form_Controller->getAction() == 'copy')
{
	$oControllerCopy = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Copy', $oAdminFormActionCopy
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oControllerCopy);
}

// Действие редактирования
$oAdminFormActionEdit = $oAdmin_Form->Admin_Form_Actions->getByName('addSiteusersList');

if ($oAdminFormActionEdit && $oAdmin_Form_Controller->getAction() == 'addSiteusersList')
{
	$oSiteuserListEdit = Admin_Form_Action_Controller::factory(
		'Siteuser_List_Controller_Edit', $oAdminFormActionEdit
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oSiteuserListEdit);

	// Крошки при редактировании
	$oSiteuserListEdit->addEntity($oAdminFormEntityBreadcrumbs);
}

// Действие экспорта
$oAdminFormActionExport = $oAdmin_Form->Admin_Form_Actions->getByName('exportSiteusersList');

if ($oAdminFormActionExport && $oAdmin_Form_Controller->getAction() == 'exportSiteusersList')
{
	$oSite = Core_Entity::factory('Site', CURRENT_SITE);
	$Siteuser_List_Export_Controller = new Siteuser_List_Export_Controller($oSite);
	$Siteuser_List_Export_Controller->execute();
}

// Действие "Удаление значения свойства"
$oAction = $oAdmin_Form->Admin_Form_Actions->getByName('deletePropertyValue');

if ($oAction && $oAdmin_Form_Controller->getAction() == 'deletePropertyValue')
{
	$oDeletePropertyValueController = Admin_Form_Action_Controller::factory(
		'Property_Controller_Delete_Value', $oAction
	);

	$oDeletePropertyValueController
		->linkedObject(array(
				Core_Entity::factory('Siteuser_Property_List', CURRENT_SITE)
			));

	$oAdmin_Form_Controller->addAction($oDeletePropertyValueController);
}

$oAdminFormActionMerge = $oAdmin_Form->Admin_Form_Actions->getByName('merge');

if ($oAdminFormActionMerge && $oAdmin_Form_Controller->getAction() == 'merge')
{
	/*$oAdmin_Form_Action_Controller_Type_Merge = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Merge', $oAdminFormActionMerge
	);

	$oAdmin_Form_Controller->addAction($oAdmin_Form_Action_Controller_Type_Merge);*/

	$oSiteuser_Controller_Merge = Admin_Form_Action_Controller::factory(
		'Siteuser_Controller_Merge', $oAdminFormActionMerge
	);

	$oSiteuser_Controller_Merge
		->title(Core::_('Siteuser.merge_title'))
		->selectCaption(Core::_('Siteuser.merge_siteuser'));

	$oAdmin_Form_Controller->addAction($oSiteuser_Controller_Merge);
}

// Источник данных 0
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Siteuser')
);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

// Доступ только к своим
$oUser = Core_Auth::getCurrentUser();
!$oUser->superuser && $oUser->only_access_my_own
	&& $oAdmin_Form_Dataset->addUserConditions();

$oAdmin_Form_Dataset->addCondition(
	array('where' => array('site_id', '=', CURRENT_SITE))
);

if (strlen($sGlobalSearch))
{
	$sGlobalSearchEscaped = str_replace(' ', '%', Core_DataBase::instance()->escapeLike($sGlobalSearch));

	// Клиенты
	$oUnionSelect = Core_QueryBuilder::select(array('id', 'siteuser_id'))
		->from('siteusers')
		->where('siteusers.site_id', '=', CURRENT_SITE)
		->open()
			->where('siteusers.login', 'LIKE', '%' . $sGlobalSearchEscaped . '%')
			->setOr()
			->where('siteusers.email', 'LIKE', '%' . $sGlobalSearchEscaped . '%')
		->close()
		// Компании
		->union(
			Core_QueryBuilder::select('siteuser_id')
				->from('siteuser_companies')
				->open()
					->where('siteuser_companies.name', 'LIKE', '%' . $sGlobalSearchEscaped . '%')
					->setOr()
					->where('siteuser_companies.tin', '=', $sGlobalSearchEscaped)
				->close()
		)
		// Представители
		->union(
			Core_QueryBuilder::select('siteuser_id')
				->from('siteuser_people')
				->open()
					->where('siteuser_people.surname', 'LIKE', '%' . $sGlobalSearchEscaped . '%')
					->setOr()
					->where('siteuser_people.name', 'LIKE', '%' . $sGlobalSearchEscaped . '%')
					->setOr()
					->where('siteuser_people.patronymic', 'LIKE', '%' . $sGlobalSearchEscaped . '%')
					->setOr()
					->where(Core_QueryBuilder::raw('CONCAT_WS(" ", `siteuser_people`.`surname`, `siteuser_people`.`name`, `siteuser_people`.`patronymic`)'), 'LIKE', '%' . $sGlobalSearchEscaped . '%')
				->close()
		)
		// Компании: почта
		->union(
			Core_QueryBuilder::select('siteuser_id')
				->distinct()
				->from('siteuser_companies')
				->join('siteuser_company_directory_emails', 'siteuser_companies.id', '=', 'siteuser_company_directory_emails.siteuser_company_id')
				->join('directory_emails', 'siteuser_company_directory_emails.directory_email_id', '=', 'directory_emails.id')
				->where('directory_emails.value', 'LIKE', '%' . $sGlobalSearchEscaped . '%')
		)
		// Компании: телефон
		->union(
			Core_QueryBuilder::select('siteuser_id')
				->distinct()
				->from('siteuser_companies')
				->join('siteuser_company_directory_phones', 'siteuser_companies.id', '=', 'siteuser_company_directory_phones.siteuser_company_id')
				->join('directory_phones', 'siteuser_company_directory_phones.directory_phone_id', '=', 'directory_phones.id')
				->where('directory_phones.value', '=', Directory_Phone_Controller::format($sGlobalSearch))
		)
		// Представители: почта
		->union(
			Core_QueryBuilder::select('siteuser_id')
				->distinct()
				->from('siteuser_people')
				->join('siteuser_people_directory_emails', 'siteuser_people.id', '=', 'siteuser_people_directory_emails.siteuser_person_id')
				->join('directory_emails', 'siteuser_people_directory_emails.directory_email_id', '=', 'directory_emails.id')
				->where('directory_emails.value', 'LIKE', '%' . $sGlobalSearchEscaped . '%')
		)
		// Представители: телефон
		->union(
			Core_QueryBuilder::select('siteuser_id')
				->distinct()
				->from('siteuser_people')
				->join('siteuser_people_directory_phones', 'siteuser_people.id', '=', 'siteuser_people_directory_phones.siteuser_person_id')
				->join('directory_phones', 'siteuser_people_directory_phones.directory_phone_id', '=', 'directory_phones.id')
				->where('directory_phones.value', '=', Directory_Phone_Controller::format($sGlobalSearch))
		);

	$oAdmin_Form_Dataset
		->addCondition(
			array('select' => array(
				'siteusers.*'
			))
		)
		->addCondition(
			array(
				'join' => array(
					array($oUnionSelect, 'UNI'), 'siteusers.id', '=', 'UNI.siteuser_id'
				)
			)
		);
}

if (isset($oAdmin_Form_Controller->request['admin_form_filter_1277'])
		&& $oAdmin_Form_Controller->request['admin_form_filter_1277'] != ''
	|| isset($oAdmin_Form_Controller->request['topFilter_1277'])
		&& $oAdmin_Form_Controller->request['topFilter_1277'] != ''
	|| $oAdmin_Form_Controller->sortingFieldId == 1277
)
{
	$oAdmin_Form_Dataset->addCondition(
		array(
			'select' => array(
				'siteusers.*', array(Core_QueryBuilder::expression('CONCAT_WS(" ",
					GROUP_CONCAT(CONCAT_WS(" ", `siteuser_companies`.`name`, `siteuser_companies`.`tin`)),
					GROUP_CONCAT(CONCAT_WS(" ", `siteuser_people`.`surname`, `siteuser_people`.`name`, `siteuser_people`.`patronymic`))
				)'), 'counterparty'),
			)
		)
	)
	->addCondition(
		array('leftJoin' => array('siteuser_companies', 'siteusers.id', '=', 'siteuser_companies.siteuser_id', array(
				array('AND' => array('siteuser_companies.deleted', '=', 0))
			))
		)
	)
	->addCondition(
		array('leftJoin' => array('siteuser_people', 'siteusers.id', '=', 'siteuser_people.siteuser_id',
			array(
				array('AND' => array('siteuser_people.deleted', '=', 0))
			))
		)
	)
	->addCondition(
		array('groupBy' => array('siteusers.id'))
	)
	;
}


// Список значений для фильтра и поля
$aTypes = array();
$aSiteuser_Types = Core_Entity::factory('Siteuser_Type')->findAll();
foreach ($aSiteuser_Types as $oSiteuser_Type)
{
	$aTypes[$oSiteuser_Type->id] = $oSiteuser_Type->name;
}

$oAdmin_Form_Dataset
	->changeField('siteuser_type_id', 'list', $aTypes);

$aStatuses = array();
$aSiteuser_Statuses = Core_Entity::factory('Siteuser_Status')->findAll();
foreach ($aSiteuser_Statuses as $oSiteuser_Status)
{
	$aStatuses[$oSiteuser_Status->id] = $oSiteuser_Status->name;
}

$oAdmin_Form_Dataset
	->changeField('siteuser_status_id', 'list', $aStatuses);

$aSources = array();
$aCrm_Sources = Core_Entity::factory('Crm_Source')->findAll();
foreach ($aCrm_Sources as $oCrm_Source)
{
	$aSources[$oCrm_Source->id] = $oCrm_Source->name;
}

$oAdmin_Form_Dataset
	->changeField('crm_source_id', 'list', $aSources);

// Показ формы
$oAdmin_Form_Controller->execute();