<?php
/**
 * Search.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
require_once('../../bootstrap.php');

Core_Auth::authorization($sModule = 'search');

$sAdminFormAction = '/{admin}/search/index.php';

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create();
$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title(Core::_('Search.title'))
	//->pageTitle(Core::_('Search.title'))
	;

ob_start();

$Search_Controller = Search_Controller::instance();

$oAdmin_View = Admin_View::create();
$oAdmin_View
	->module(Core_Module_Abstract::factory($sModule))
	->pageTitle(Core::_('Search.title'))
	->addMessage(
		Core_Message::get(
			Core::_('Search.search_index_items', $Search_Controller->getPageCount(CURRENT_SITE))
		)
	);

$aDictionaries = array();

$aTables = Core_DataBase::instance()->asAssoc()->getTablesSchema('search_dictionary_%');
foreach ($aTables as $aTable)
{
	if (isset($aTable['table_rows']) && $aTable['table_rows'])
	{
		switch ($aTable['name'])
		{
			case 'search_dictionary_rus':
				$aDictionaries[] = Core::_('Search.search_dictionary_ru');
			break;
			case 'search_dictionary_ens':
				$aDictionaries[] = Core::_('Search.search_dictionary_en');
			break;
		}
	}
}

$aDictionaries = array_reverse($aDictionaries);

if (count($aDictionaries))
{
	$oAdmin_View->addMessage(
		Core_Message::get(
			Core::_('Search.search_dictionary', implode(', ', $aDictionaries)), 'info'
		)
	);
}
else
{
	$oAdmin_View->addMessage(
		Core_Message::get(
			Core::_('Search.search_dictionary_not_installed', Admin_Form_Controller::correctBackendPath('/{admin}/market/index.php') . '?hostcms[action]=sendSearchQuery&search_query=Словари для поиска', FALSE), 'warning'
		)
	);
}

// Меню формы
$sSearchLogPath = '/{admin}/search/log/index.php';

$oAdmin_Form_Entity_Menus = Admin_Form_Entity::factory('Menus')
	->add(
		Admin_Form_Entity::factory('Menu')
			->name(Core::_('Search_Log.title'))
			->icon('fa fa-list-ul')
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref($sSearchLogPath, NULL, NULL, '')
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax($sSearchLogPath, NULL, NULL, '')
			)
	)
	->add(
		Admin_Form_Entity::factory('Menu')
			->name(Core::_('Search_Link.title'))
			->icon('fa fa-file-lines')
			->href(
				$oAdmin_Form_Controller->getAdminLoadHref('/{admin}/search/link/index.php', NULL, NULL, '')
			)
			->onclick(
				$oAdmin_Form_Controller->getAdminLoadAjax('/{admin}/search/link/index.php', NULL, NULL, '')
			)
	)
	;

?>
<div class="table-toolbar">
	<?php $oAdmin_Form_Entity_Menus->execute()?>
	<div class="clear"></div>
</div>
<?php

$bIndexingCompleted = TRUE;

if ($oAdmin_Form_Controller->getAction() == 'process')
{
	Core_Session::start();

	try
	{
		// Текущий пользователь
		$oUser = Core_Auth::getCurrentUser();

		// Read Only режим
		if (defined('READ_ONLY') && READ_ONLY || $oUser->read_only)
		{
			throw new Core_Exception(
				Core::_('User.demo_mode'), array(), 0, FALSE
			);
		}

		$siteId = CURRENT_SITE;

		$topic = Core_Array::getRequest('topic', 0, 'int');
		$timeout = Core_Array::getRequest('timeout', 0, 'int');
		$limit = Core_Array::getRequest('limit', 0, 'int');
		$step = Core_Array::getRequest('step', 100, 'int');

		$bIndexingCompleted = FALSE;

		if ($topic == 0 && $limit == 0)
		{
			// Remove all indexed data
			$Search_Controller->truncate($siteId);

			//$_SESSION['search_block'] = 0;
			$_SESSION['previous_step'] = 0;
			$_SESSION['last_limit'] = 0;
		}

		// Цикл по модулям
		$oModules = Core_Entity::factory('Module');
		$oModules->queryBuilder()
			->where('modules.active', '=', 1)
			->where('modules.indexing', '=', 1);

		$aModules = $oModules->findAll();

		if ($topic < count($aModules))
		{
			$aPages = NULL;
			$finished = TRUE;
			$indexed = 0;

			$previousSearchBlock = Core_Array::get($_SESSION, 'search_block');

			$oModule = $aModules[$topic]->loadModule();
			if (!is_null($oModule->Core_Module))
			{
				if (method_exists($oModule->Core_Module, 'indexing'))
				{
					$mTmp = $oModule->Core_Module->indexing($siteId, $limit, $step);

					if (isset($mTmp['pages']) && isset($mTmp['finished']))
					{
						// Проиндексированные страницы
						$aPages = $mTmp['pages'];

						// Модуль завершил индексацию
						$finished = $mTmp['finished'];

						// Проиндексировано последним блоком, может быть меньше количества $aPages, т.к. $aPages содержит результат нескольких блоков
						$indexed = $mTmp['indexed'];
					}
					else
					{
						$aPages = $mTmp;

						$indexed = $_SESSION['last_limit'] > 0
							? $_SESSION['last_limit']
							: $step;

						// Больше, т.к. некоторые модули могут возвращать больше проиндексированных элементов, чем запрошено, например, форумы
						$finished = empty($aPages) || count($aPages) < $step;
					}
				}
			}

			is_array($aPages)
				&& $Search_Controller->indexingSearchPages($aPages);

			if (!$finished)
			{
				// Если предыдущая индексация шла в несколько этапов, лимит сбрасывается для нового шага
				if (Core_Array::get($_SESSION, 'search_block') != $previousSearchBlock)
				{
					$limit = 0;
				}

				$limit += $indexed;
			}
			else
			{
				$topic++;
				$limit = 0;
				$_SESSION['search_block'] = $_SESSION['previous_step'] = $_SESSION['last_limit'] = 0;
			}

			// Организуем редиректы для перехода от блока к блоку
			?>
			<p><?php echo Core::_('Search.search_indexed_all_sites', $oModule->name, is_array($aPages) ? count($aPages) : 0, $timeout)?>
			<br />
			<?php
			$sAdditionalParams = "indexation=1&topic={$topic}&limit={$limit}&step={$step}&timeout={$timeout}";

			echo Core::_('Search.search_indexed_automatic_redirection_message',
				$oAdmin_Form_Controller->getAdminActionLoadHref($oAdmin_Form_Controller->getPath(), 'process', NULL, 0, 0, $sAdditionalParams),
				$oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'process', NULL, 0, 0, $sAdditionalParams)
			)?>
			</p>

			<script type="text/javascript">
			setTimeout(function(){
				<?php echo $oAdmin_Form_Controller->getAdminActionLoadAjax($oAdmin_Form_Controller->getPath(), 'process', NULL, 0, 0, $sAdditionalParams)?>
			}, <?php echo $timeout * 1000?>);
			</script>
			<?php
		}
		else
		{
			$bIndexingCompleted = TRUE;
		}
	}
	catch (Exception $e)
	{
		$oAdmin_View->addMessage(
			Core_Message::get($e->getMessage(), 'error')
		);
	}

	$bIndexingCompleted && $_SESSION['search_block'] = $_SESSION['previous_step'] = 0;
}

if ($bIndexingCompleted)
{
	$Search_Controller->optimize(CURRENT_SITE);
	$Search_Controller->optimize(0);

	// Clear Cache
	if (Core::moduleIsActive('cache'))
	{
		Core_Cache::instance(Core::$mainConfig['defaultCache'])->deleteAll('search');
	}

	Admin_Form_Entity::factory('Form')
		->controller($oAdmin_Form_Controller)
		->action($sAdminFormAction)
		->add(
			Admin_Form_Entity::factory('Select')
				->name('step')
				->caption(Core::_('Search.step'))
				->options(array(
					10 => 10,
					30 => 30,
					50 => 50,
					100 => 100,
					500 => 500,
					1000 => 1000
				))
				->style('width: 160px')
				->value(Core_Array::getPost('step', 100))
		)->add(
			Admin_Form_Entity::factory('Input')
				->name('timeout')
				->caption(Core::_('Search.timeout'))
				->style('width: 160px')
				->value(Core_Array::getPost('timeout', 0))
		)->add(
			Admin_Form_Entity::factory('Button')
				->name('process')
				->type('submit')
				->value(Core::_('Search.button'))
				->class('applyButton btn btn-blue')
				->onclick(
					$oAdmin_Form_Controller->getAdminSendForm('process', NULL, '')
				)
		)
		->execute();
}

$content = ob_get_clean();

ob_start();
$oAdmin_View
	->content($content)
	->show();

Core_Skin::instance()->answer()
	->ajax(Core_Array::getRequest('_', FALSE))
	->content(ob_get_clean())
	->message($oAdmin_View->message)
	->title(Core::_('Search.title'))
	->module($sModule)
	->execute();