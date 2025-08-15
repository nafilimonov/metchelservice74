<?php
/**
 * Lists.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'list');

// Код формы
$iAdmin_Form_Id = 21;
$sAdminFormAction = '/{admin}/list/item/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$list_id = intval(Core_Array::getGet('list_id', 0));
$parent_id = intval(Core_Array::getGet('parent_id', 0));
$oList = Core_Entity::factory('List')->find($list_id);

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('List_Item.title', $oList->name))
	->pageTitle(Core::_('List_Item.title', $oList->name));

if (!is_null(Core_Array::getGet('autocomplete'))
	&& !is_null(Core_Array::getGet('show_parents'))
	&& !is_null(Core_Array::getGet('queryString'))
)
{
	$sQuery = trim(Core_Str::stripTags(strval(Core_Array::getGet('queryString'))));
	$iListId = intval(Core_Array::getGet('list_id'));
	$oList = Core_Entity::factory('List', $iListId);

	$mode = intval(Core_Array::getGet('mode'));

	$aJSON = array();

	if (strlen($sQuery))
	{
		$aJSON[0] = array(
			'id' => 0,
			'label' => Core::_('List_Item.root')
		);

		$oList_Items = $oList->List_Items;
		$oList_Items->queryBuilder()
			// ->where('list_items.value', 'LIKE', '%' . $sQuery . '%')
			->clearOrderBy()
			->orderBy('list_items.value', 'ASC')
			->limit(Core::$mainConfig['autocompleteItems']);

		switch ($mode)
		{
			// Вхождение
			case 0:
			default:
				$oList_Items->queryBuilder()->where('list_items.value', 'LIKE', '%' . str_replace(' ', '%', $sQuery) . '%');
			break;
			// Вхождение с начала
			case 1:
				$oList_Items->queryBuilder()->where('list_items.value', 'LIKE', $sQuery . '%');
			break;
			// Вхождение с конца
			case 2:
				$oList_Items->queryBuilder()->where('list_items.value', 'LIKE', '%' . $sQuery);
			break;
			// Точное вхождение
			case 3:
				$oList_Items->queryBuilder()->where('list_items.value', '=', $sQuery);
			break;
		}

		$aList_Items = $oList_Items->findAll(FALSE);

		foreach ($aList_Items as $oList_Item)
		{
			$aJSON[] = array(
				'id' => $oList_Item->id,
				'label' => $oList_Item->value
			);
		}
	}

	Core::showJson($aJSON);
}

if (Core_Array::getPost('showAddListItemModal'))
{
	$aJSON = array(
		'html' => ''
	);

	$list_id = Core_Array::getPost('list_id', 0, 'int');

	$oList = Core_Entity::factory('List')->getById($list_id);
	if (!is_null($oList))
	{
		ob_start();
		?>
		<div class="modal fade" id="listItemModal<?php echo $oList->id?>" tabindex="-1" role="dialog" aria-labelledby="listItemModalLabel">
			<div class="modal-dialog " role="document">
				<div class="modal-content no-padding-bottom">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title"><?php echo htmlspecialchars($oList->name)?></h4>
					</div>
					<div class="modal-body">
						<div class="row margin-bottom-10">
							<div class="col-xs-12">
								<input type="text" class="form-control" name="value" placeholder="<?php echo Core::_('Property.insert_value')?>"/>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-success" onclick="mainFormLocker.unlock(); $.savePropertyListItem('<?php echo $oAdmin_Form_Controller->getWindowId()?>', <?php echo $oList->id?>, $('#listItemModal<?php echo $oList->id?> input').val())"><?php echo Core::_('Admin_Form.add')?></button>
					</div>
				</div>
			</div>
		</div>
		<?php
		$aJSON['html'] = ob_get_clean();
	}

	Core::showJson($aJSON);
}

if (Core_Array::getPost('addListItem'))
{
	$aJSON = array(
		'status' => 'error'
	);

	$list_id = Core_Array::getPost('list_id', 0, 'int');
	$oList = Core_Entity::factory('List')->getById($list_id);

	if (!is_null($oList))
	{
		$value = Core_Array::getPost('value', '', 'trim');
		$oList_Item = $oList->List_Items->getByValue($value, FALSE);

		if (is_null($oList_Item))
		{
			$oList_Item = Core_Entity::factory('List_Item');
			$oList_Item->list_id = $oList->id;
			$oList_Item->value = $value;
			$oList_Item->save();

			$aJSON = array(
				'status' => 'success',
				'list_id' => $oList->id,
				'list_item_id' => $oList_Item->id,
				'value' => $value
			);
		}
		else
		{
			$aJSON['status'] = "List item {$value} already exist in list {$oList->name}";
		}
	}

	Core::showJson($aJSON);
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
		->name(Core::_('List_Item.mergeDuplicates'))
		->icon('fas fa-object-group')
		->href(
			$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'mergeDuplicates', $list_id, 0, 0)
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'mergeDuplicates', $list_id, 0, 0)
		)
);

// Добавляем все меню контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);

// Глобальный поиск
$additionalParams = "parent_id={$parent_id}&list_id={$list_id}";

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

$sGlobalSearch = str_replace(' ', '%', Core_DataBase::instance()->escapeLike($sGlobalSearch));

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

// Строка навигации
$sListDirPath = '/{admin}/list/index.php';

$sListPath = '/{admin}/list/item/index.php?list_id=' . $list_id;

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('List.title'))
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($sListDirPath, NULL, NULL, '')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($sListDirPath, NULL, NULL, '')
	)
);

if ($oList->list_dir_id && !is_null($oList->list_dir_id))
{
	$oList_Dir = Core_Entity::factory('List_Dir')->find($oList->list_dir_id);

	if (!is_null($oList_Dir->id))
	{
		$aBreadcrumbs = array();

		do
		{
			$additionalParams = 'list_dir_id=' . $oList_Dir->id;

			$aBreadcrumbs[] = Admin_Form_Entity::factory('Breadcrumb')
				->name($oList_Dir->name)
				->href(
					$oAdmin_Form_Controller->getAdminLoadHref($sListDirPath, NULL, NULL, $additionalParams)
				)
				->onclick(
					$oAdmin_Form_Controller->getAdminLoadAjax($sListDirPath, NULL, NULL, $additionalParams)
				);
		} while ($oList_Dir = $oList_Dir->getParent());

		$aBreadcrumbs = array_reverse($aBreadcrumbs);

		foreach ($aBreadcrumbs as $oAdmin_Form_Entity_Breadcrumb)
		{
			$oAdmin_Form_Entity_Breadcrumbs->add(
				$oAdmin_Form_Entity_Breadcrumb
			);
		}
	}
}

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name($oList->name)
		->href(
			$oAdmin_Form_Controller->getAdminLoadHref($sListPath, NULL, NULL, '')
		)
		->onclick(
			$oAdmin_Form_Controller->getAdminLoadAjax($sListPath, NULL, NULL, '')
	)
);

if ($parent_id)
{
	$oParentListItem = Core_Entity::factory('List_Item')->find($parent_id);

	if (!is_null($oParentListItem->id))
	{
		$aBreadcrumbs = array();

		do
		{
			$additionalParams = '&parent_id=' . $oParentListItem->id . '&list_id=' . $oList->id;

			$aBreadcrumbs[] = Admin_Form_Entity::factory('Breadcrumb')
				->name($oParentListItem->value)
				->href(
					$oAdmin_Form_Controller->getAdminLoadHref($oAdmin_Form_Controller->getPath(), NULL, NULL, $additionalParams)
				)
				->onclick(
					$oAdmin_Form_Controller->getAdminLoadAjax($oAdmin_Form_Controller->getPath(), NULL, NULL, $additionalParams)
				);
		} while ($oParentListItem = $oParentListItem->getParent());

		$aBreadcrumbs = array_reverse($aBreadcrumbs);

		foreach ($aBreadcrumbs as $oAdmin_Form_Entity_Breadcrumb)
		{
			$oAdmin_Form_Entity_Breadcrumbs->add(
				$oAdmin_Form_Entity_Breadcrumb
			);
		}
	}
}

// Добавляем все хлебные крошки контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Действие обновления
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('mergeDuplicates');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'mergeDuplicates')
{
	$oList_Item_Merge_Duplicate = Admin_Form_Action_Controller::factory(
		'List_Item_Merge_Duplicate', $oAdmin_Form_Action
	);

	$oAdmin_Form_Controller->addAction($oList_Item_Merge_Duplicate);
}


// Действие редактирования
$oAdmin_Form_Action = $oAdmin_Form->Admin_Form_Actions->getByName('edit');

if ($oAdmin_Form_Action && $oAdmin_Form_Controller->getAction() == 'edit')
{
	$oList_Item_Controller_Edit = Admin_Form_Action_Controller::factory(
		'List_Item_Controller_Edit', $oAdmin_Form_Action
	);

	$oList_Item_Controller_Edit
		->addEntity($oAdmin_Form_Entity_Breadcrumbs);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oList_Item_Controller_Edit);
}

// Действие "Применить"
$oAdminFormActionApply = $oAdmin_Form->Admin_Form_Actions->getByName('apply');

if ($oAdminFormActionApply && $oAdmin_Form_Controller->getAction() == 'apply')
{
	$oTagDirControllerApply = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Apply', $oAdminFormActionApply
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oTagDirControllerApply);
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

// Действие "Объединить"
$oAdminFormActionMerge = $oAdmin_Form->Admin_Form_Actions->getByName('merge');

if ($oAdminFormActionMerge && $oAdmin_Form_Controller->getAction() == 'merge')
{
	$oAdmin_Form_Action_Controller_Type_Merge = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Merge', $oAdminFormActionMerge
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oAdmin_Form_Action_Controller_Type_Merge);
}

// Действие "Перенести"
$oAdminFormActionMove = $oAdmin_Form->Admin_Form_Actions->getByName('move');

if ($oAdminFormActionMove && $oAdmin_Form_Controller->getAction() == 'move')
{
	$Admin_Form_Action_Controller_Type_Move = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Move', $oAdminFormActionMove
	);

	$Admin_Form_Action_Controller_Type_Move
		->title(Core::_('List_Item.move_title'))
		->selectCaption(Core::_('List_Item.move_list_id'))
		->value($list_id);

	$List_Controller_Edit = new List_Controller_Edit($oAdminFormActionMove);

	$Admin_Form_Action_Controller_Type_Move
		// Список директорий генерируется другим контроллером
		->selectOptions($List_Controller_Edit->fillLists(CURRENT_SITE));

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($Admin_Form_Action_Controller_Type_Move);
}

// Действие "Удаление файла большого изображения"
$oAction = $oAdmin_Form->Admin_Form_Actions->getByName('deleteLargeImage');

if ($oAction && $oAdmin_Form_Controller->getAction() == 'deleteLargeImage')
{
	$oDeleteLargeImageController = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Delete_File', $oAction
	);

	$oDeleteLargeImageController
		->methodName('deleteLargeImage')
		->divId(array('preview_large_image', 'delete_large_image'));

	// Добавляем контроллер удаления изображения к контроллеру формы
	$oAdmin_Form_Controller->addAction($oDeleteLargeImageController);
}

// Действие "Удаление файла малого изображения"
$oAction = $oAdmin_Form->Admin_Form_Actions->getByName('deleteSmallImage');

if ($oAction && $oAdmin_Form_Controller->getAction() == 'deleteSmallImage')
{
	$oDeleteSmallImageController = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Delete_File', $oAction
	);

	$oDeleteSmallImageController
		->methodName('deleteSmallImage')
		->divId(array('preview_small_image', 'delete_small_image'));

	$oAdmin_Form_Controller->addAction($oDeleteSmallImageController);
}

// Источник данных 0
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('List_Item')
);

// Доступ только к своим
$oUser = Core_Auth::getCurrentUser();
!$oUser->superuser && $oUser->only_access_my_own
	&& $oAdmin_Form_Dataset->addUserConditions();

// Ограничение источника 0 по родительской группе
$oAdmin_Form_Dataset->addCondition(
	array('where' =>
		array('list_id', '=', $list_id)
	)
);

if (strlen($sGlobalSearch))
{
	$oAdmin_Form_Dataset
		->addCondition(array('open' => array()))
			->addCondition(array('where' => array('list_items.id', '=', is_numeric($sGlobalSearch) ? intval($sGlobalSearch) : 0)))
			->addCondition(array('setOr' => array()))
			->addCondition(array('where' => array('list_items.value', 'LIKE', '%' . $sGlobalSearch . '%')))
		->addCondition(array('close' => array()));
}
else
{
	$oAdmin_Form_Dataset->addCondition(array('where' => array('parent_id', '=', $parent_id)));
}

$oAdmin_Form_Dataset->changeField('active', 'list', "1=" . Core::_('Admin_Form.yes') . "\n" . "0=" . Core::_('Admin_Form.no'));

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

// Показ формы
$oAdmin_Form_Controller->execute();