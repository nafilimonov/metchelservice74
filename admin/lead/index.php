<?php
/**
 * Leads.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../bootstrap.php');

Core_Auth::authorization($sModule = 'lead');

// Код формы
$iAdmin_Form_Id = 268;
$sAdminFormAction = '/{admin}/lead/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Lead.title'))
	->pageTitle(Core::_('Lead.title'))
	->addView('kanban', 'Lead_Controller_Kanban')
	// ->view('kanban')
	;

$oAdmin_Form_Controller->showTopFilterTags = 'lead';

if (Core_Array::getPost('id') && (Core_Array::getPost('target_id') || Core_Array::getPost('sender_id')))
{
	$aJSON = array(
		'status' => '',
		'last_step' => 0,
		'type' => 0
	);

	$lead_id = Core_Array::getPost('lead_id', 0, 'int')
		? Core_Array::getPost('lead_id', 0, 'int')
		: Core_Array::getPost('id', 0, 'int');

	$iSenderStatusId = Core_Array::getPost('sender_id', 0, 'int');

	$oLeads = Core_Entity::factory('Lead');

	$iSenderStatusId == -1
		&& $oLeads->setMarksDeleted(NULL);

	$oLead = $oLeads->getById($lead_id);

	if (!is_null($oLead))
	{
		$lead_status_id = /*Core_Array::getPost('lead_status_id')
			? intval(Core_Array::getPost('lead_status_id'))
			: */Core_Array::getPost('target_id', 0, 'int');

		if ($lead_status_id > 0)
		{
			$oLead_Status = Core_Entity::factory('Lead_Status')->getById($lead_status_id);

			if (!is_null($oLead_Status))
			{
				if ($oLead_Status->type != 1)
				{
					$previousStatusId = $oLead->lead_status_id;

					$oLead->deleted = 0; // При отмене удаленного явно возвращаем в 0
					$oLead->lead_status_id = $lead_status_id;
					$oLead->save();

					if ($previousStatusId != $oLead->lead_status_id)
					{
						$oLead->notifyBotsChangeStatus();
					}

					$sNewLeadStepDatetime = Core_Date::timestamp2sql(time());

					$oCurrentUser = Core_Auth::getCurrentUser();

					Core_Entity::factory('Lead_Step')
						->lead_id($oLead->id)
						->lead_status_id($oLead->lead_status_id)
						->user_id($oCurrentUser->id)
						->datetime($sNewLeadStepDatetime)
						->save();

					$aJSON['type'] = $oLead_Status->type;
				}
				// Если тип "Успешный"
				else
				{
					$aJSON['last_step'] = 1;
					$aJSON['lead_status_id'] = $oLead_Status->id;
				}

				$aJSON['status'] = 'success';
				$aJSON['lead_id'] = $lead_id;
				$aJSON['window_id'] = $oAdmin_Form_Controller->getWindowId();

				if (intval(Core_Array::getPost('update_data')))
				{
					$aTargetData = $oLead->updateKanban($oLead_Status);

					$aJSON['update'][$oLead_Status->id] = $aTargetData;

					$oSender_Lead_Status = Core_Entity::factory('Lead_Status')->find($iSenderStatusId);
					if (!is_null($oSender_Lead_Status->id))
					{
						$aSenderData = $oLead->updateKanban($oSender_Lead_Status);

						$aJSON['update'][$oSender_Lead_Status->id] = $aSenderData;
					}
				}
			}
			else
			{
				$aJSON['status'] = 'errorLeadStatus';
			}
		}
		elseif ($lead_status_id == -1)
		{
			$oLead->markDeleted();
		}
		else
		{
			$aJSON['status'] = 'errorLeadStatusId';
		}
	}
	else
	{
		$aJSON['status'] = 'errorLead';
	}

	Core::showJson($aJSON);
}

if (!is_null(Core_Array::getRequest('loadLeadCard')) && !is_null(Core_Array::getRequest('phone')))
{
	$aCards = array();

	$phone = Core_Array::getRequest('phone', '', 'trim');

	$aLeads = Lead_Controller::getLeadsByPhone($phone, FALSE);
	$aLeads = array_slice($aLeads, 0, 4);
	foreach ($aLeads as $oLead)
	{
		$name = $oLead->getFullName();
		$name === '' && $name = Core::_('Lead.without_name');

		$aCards[] = array(
			'id' => $oLead->id,
			'name' => $name
		);
	}

	ob_start();

	if (count($aCards))
	{
		?><div class="siteuser-cards-wrapper"><?php
			foreach ($aCards as $aCard)
			{
				?><div class="siteuser-card" onclick="$.modalLoad({path: '<?php echo Admin_Form_Controller::correctBackendPath('/{admin}/lead/index.php')?>', action: 'edit', operation: 'modal', additionalParams: 'hostcms[checked][0][<?php echo $aCard['id']?>]=1', windowId: 'id_content', width: '90%'}); return false" title="<?php echo htmlspecialchars($aCard['name'])?>"><span><?php echo htmlspecialchars($aCard['name'])?></span></div><?php
			}
		?></div><?php
	}

	Core::showJson(
		array(
			'html' => ob_get_clean()
		)
	);
}

// Меню формы
$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus');

// Элементы меню
$oAdmin_Form_Entity_Menus->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Admin_Form.add'))
		->icon('fa fa-plus')
		->href(
			$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, 0)
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'edit', NULL, 0, 0)
		)
)->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Lead.menu_directory'))
		->icon('fa fa-book')
		->add(
			Admin_Form_Entity::factory('Menu')
				->name(Core::_('Lead.menu_need'))
				->icon('fa fa-puzzle-piece')
				->href(
					$oAdmin_Form_Controller->getAdminLoadHref($sNeedFormPath = '/{admin}/lead/need/index.php', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'list')
				)
				->onclick(
					$oAdmin_Form_Controller->getAdminLoadAjax($sNeedFormPath, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'list')
				)
		)
		->add(
			Admin_Form_Entity::factory('Menu')
				->name(Core::_('Lead.menu_maturity'))
				->icon('fa fa-circle')
				->href(
					$oAdmin_Form_Controller->getAdminLoadHref($sMaturityFormPath = '/{admin}/lead/maturity/index.php', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'list')
				)
				->onclick(
					$oAdmin_Form_Controller->getAdminLoadAjax($sMaturityFormPath, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'list')
				)
		)
		->add(
			Admin_Form_Entity::factory('Menu')
				->name(Core::_('Lead.menu_statuses'))
				->icon('fa fa-flag')
				->href(
					$oAdmin_Form_Controller->getAdminLoadHref($sStatusesFormPath = '/{admin}/lead/status/index.php', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'list')
				)
				->onclick(
					$oAdmin_Form_Controller->getAdminLoadAjax($sStatusesFormPath, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'list')
				)
		)
		->add(
			Admin_Form_Entity::factory('Menu')
				->name(Core::_('Crm_Source.siteuser_sources_title'))
				->icon('fa fa-user-plus')
				->href(
					$oAdmin_Form_Controller->getAdminLoadHref('/{admin}/crm/source/index.php', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'list')
				)
				->onclick(
					$oAdmin_Form_Controller->getAdminLoadAjax('/{admin}/crm/source/index.php', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'list')
				)
		)
)->add(
	Admin_Form_Entity::factory('Menu')
		->name(Core::_('Lead.menu_exchange'))
		->icon('fa fa-exchange')
		->add(
			Admin_Form_Entity::factory('Menu')
				->name(Core::_('Lead.import'))
				->icon('fa fa-download')
				->href(
					$oAdmin_Form_Controller->getAdminLoadHref('/{admin}/lead/import/index.php', NULL, NULL)
				)
				->onclick(
					$oAdmin_Form_Controller->getAdminLoadAjax('/{admin}/lead/import/index.php', NULL, NULL)
				)
		)
		->add(
			Admin_Form_Entity::factory('Menu')
				->name(Core::_('Lead.export'))
				->icon('fa fa-upload')
				->target('_blank')
				->href(
					$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'exportLeads', NULL, 0, 0)
				)
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

$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

// Добавляем крошку на текущую форму
$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Lead.title'))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, '')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, '')
		)
);

$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oLead_Controller_Edit = Admin_Form_Action_Controller::factory(
		'Lead_Controller_Edit', $oAdmin_Form_Action
	);

	// Хлебные крошки для контроллера редактирования
	$oLead_Controller_Edit->addEntity($oAdmin_Form_Entity_Breadcrumbs);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oLead_Controller_Edit);
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

// Действие "Изменить потребность"
$oAdminFormActionChangeNeed = $oAdmin_Form->Admin_Form_Actions->getByName('changeNeed');

if ($oAdminFormActionChangeNeed && $oAdmin_Form_Controller->getAction() == 'changeNeed')
{
	$oLeadControllerNeed = Admin_Form_Action_Controller::factory(
		'Lead_Controller_Need', $oAdminFormActionChangeNeed
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oLeadControllerNeed);
}

// Действие "Изменить источник"
$oAdminFormActionChangeCrmSource = $oAdmin_Form->Admin_Form_Actions->getByName('changeCrmSource');

if ($oAdminFormActionChangeCrmSource && $oAdmin_Form_Controller->getAction() == 'changeCrmSource')
{
	$oLeadControllerSource = Admin_Form_Action_Controller::factory(
		'Lead_Controller_Source', $oAdminFormActionChangeCrmSource
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oLeadControllerSource);
}

// Действие "Изменить зрелость"
$oAdminFormActionChangeMaturity = $oAdmin_Form->Admin_Form_Actions->getByName('changeMaturity');

if ($oAdminFormActionChangeMaturity && $oAdmin_Form_Controller->getAction() == 'changeMaturity')
{
	$oLeadControllerMaturity = Admin_Form_Action_Controller::factory(
		'Lead_Controller_Maturity', $oAdminFormActionChangeMaturity
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oLeadControllerMaturity);
}

// Действие экспорта
$oAdminFormActionExport = $oAdmin_Form->Admin_Form_Actions->getByName('exportLeads');

if ($oAdminFormActionExport && $oAdmin_Form_Controller->getAction() == 'exportLeads')
{
	$oSite = Core_Entity::factory('Site', CURRENT_SITE);
	$Lead_Exchange_Export_Controller = new Lead_Exchange_Export_Controller($oSite);
	$Lead_Exchange_Export_Controller->execute();
}

// Действие "Изменить потребность"
$oAdminFormActionMorphLead = $oAdmin_Form->Admin_Form_Actions->getByName('morphLead');

if ($oAdminFormActionMorphLead && $oAdmin_Form_Controller->getAction() == 'morphLead')
{
	$oLeadControllerMorph = Admin_Form_Action_Controller::factory(
		'Lead_Controller_Morph', $oAdminFormActionMorphLead
	);

	$oLeadControllerMorph
		->title(Core::_('Lead.morph_lead'))
		->buttonName(Core::_('Lead.morph'));

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oLeadControllerMorph);
}

// Действие "Объединить"
$oAdminFormActionMerge = $oAdmin_Form->Admin_Form_Actions->getByName('merge');

if ($oAdminFormActionMerge && $oAdmin_Form_Controller->getAction() == 'merge')
{
	$oAdmin_Form_Action_Controller_Type_Merge = new Admin_Form_Action_Controller_Type_Merge($oAdminFormActionMerge);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oAdmin_Form_Action_Controller_Type_Merge);
}

// Источник данных 0
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Lead')
);

// Доступ только к своим
$oUser = Core_Auth::getCurrentUser();
!$oUser->superuser && $oUser->only_access_my_own
	&& $oAdmin_Form_Dataset->addUserConditions();

// Ограничение источника 0 по родительской группе
$oAdmin_Form_Dataset
	->addCondition(
		array('select' => array('leads.*',
			array(Core_QueryBuilder::expression('CONCAT(COALESCE(leads.surname, \'\'), \' \', COALESCE(leads.name, \'\'), \' \', COALESCE(leads.patronymic, \'\'))'), 'contact')
			)
		)
	);

$oAdmin_Form_Dataset->addCondition(
	array('where' => array('site_id', '=', CURRENT_SITE))
);

if (strlen($sGlobalSearch))
{
	$sGlobalSearchEscaped = str_replace(' ', '%', Core_DataBase::instance()->escapeLike($sGlobalSearch));

	// Лиды
	$oUnionSelect = Core_QueryBuilder::select(array('id', 'lead_id'))
		->from('leads')
		->where('leads.site_id', '=', CURRENT_SITE)
		->open()
			->where('leads.name', 'LIKE', '%' . $sGlobalSearchEscaped . '%')
			->setOr()
			->where('leads.surname', 'LIKE', '%' . $sGlobalSearchEscaped . '%')
			->setOr()
			->where('leads.patronymic', 'LIKE', '%' . $sGlobalSearchEscaped . '%')
			->setOr()
			->where('leads.company', 'LIKE', '%' . $sGlobalSearchEscaped . '%')
			->setOr()
			->where('leads.comment', 'LIKE', '%' . $sGlobalSearchEscaped . '%')
		->close()
		// Лиды: почта
		->union(
			Core_QueryBuilder::select('lead_id')
				->distinct()
				->from('lead_directory_emails')
				->join('directory_emails', 'lead_directory_emails.directory_email_id', '=', 'directory_emails.id')
				->where('directory_emails.value', 'LIKE', '%' . $sGlobalSearchEscaped . '%')
		)
		// Лиды: телефон
		->union(
			Core_QueryBuilder::select('lead_id')
				->distinct()
				->from('lead_directory_phones')
				->join('directory_phones', 'lead_directory_phones.directory_phone_id', '=', 'directory_phones.id')
				->where('directory_phones.value', '=', Directory_Phone_Controller::format($sGlobalSearch))
		);

	$oAdmin_Form_Dataset
		->addCondition(
			array('select' => array('leads.*'))
		)
		->addCondition(
			array('join' => array(array($oUnionSelect, 'UNI'), 'leads.id', '=', 'UNI.lead_id'))
		);
}

if (isset($oAdmin_Form_Controller->request['topFilter_filter_tags'])
	&& is_array($oAdmin_Form_Controller->request['topFilter_filter_tags']))
{
	$aValues = $oAdmin_Form_Controller->request['topFilter_filter_tags'];
	$aValues = array_filter($aValues, 'strlen');

	if (count($aValues))
	{
		$oAdmin_Form_Dataset->addCondition(
			array('join' => array('tag_leads', 'leads.id', '=', 'tag_leads.lead_id'))
		)->addCondition(
			array('join' => array('tags', 'tags.id', '=', 'tag_leads.tag_id'))
		)->addCondition(
			array('where' => array('tags.name', 'IN', $aValues))
		);
	}
}

// Список значений для фильтра и поля
$aNeeds = array();
$aLead_Needs = Core_Entity::factory('Lead_Need')->getAllBySite_id(CURRENT_SITE);
foreach ($aLead_Needs as $oLead_Need)
{
	$aNeeds[$oLead_Need->id] = $oLead_Need->name;
}

$oAdmin_Form_Dataset
	->changeField('lead_need_id', 'list', $aNeeds);

// Список значений для фильтра и поля
$aMaturity = array();
$aLead_Maturities = Core_Entity::factory('Lead_Maturity')->getAllBySite_id(CURRENT_SITE);
foreach ($aLead_Maturities as $oLead_Maturity)
{
	$aMaturity[$oLead_Maturity->id] = $oLead_Maturity->name;
}

$oAdmin_Form_Dataset
	->changeField('lead_maturity_id', 'list', $aMaturity);

// Список значений для фильтра и поля
$aStatus = array();
$aLead_Statuses = Core_Entity::factory('Lead_Status')->getAllBySite_id(CURRENT_SITE);
foreach ($aLead_Statuses as $oLead_Status)
{
	$aStatus[$oLead_Status->id] = $oLead_Status->name;
}

$oAdmin_Form_Dataset
	->changeField('lead_status_id', 'list', $aStatus)
	->changeField('lead_status_id', 'type', 8);

// Список значений для фильтра и поля
$aSources = array();
$aCrm_Sources = Core_Entity::factory('Crm_Source')->findAll();
foreach ($aCrm_Sources as $oCrm_Source)
{
	$aSources[$oCrm_Source->id] = $oCrm_Source->name;
}

$oAdmin_Form_Dataset
	->changeField('crm_source_id', 'list', $aSources);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset($oAdmin_Form_Dataset);

// Показ формы
$oAdmin_Form_Controller->execute();