<?php

/**
 * Counter.
 *
 * @package HostCMS
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2017 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'counter');

// Код формы
$iAdmin_Form_Id = 100;
$sAdminFormAction = '/admin/counter/agent/index.php';

$sCounterPath = '/admin/counter/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$sTitle = Core::_('Counter_Session.visitor_agents');

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);
$oAdmin_Form_Controller
	->module(Core_Module::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title($sTitle)
	->pageTitle($sTitle);

$sFormPath = $oAdmin_Form_Controller->getPath();

// подключение верхнего меню
include CMS_FOLDER . '/admin/counter/menu.php';

// Добавляем все меню контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);

// Строка навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs->add(Admin_Form_Entity::factory('Breadcrumb')
	->name(Core::_('Counter.title'))
	->href($oAdmin_Form_Controller->getAdminLoadHref($sCounterPath, NULL, NULL, ''))
	->onclick($oAdmin_Form_Controller->getAdminLoadAjax($sCounterPath, NULL, NULL, ''))
)
->add(Admin_Form_Entity::factory('Breadcrumb')
	->name($sTitle)
	->href($oAdmin_Form_Controller->getAdminLoadHref($sFormPath, NULL, NULL, ''))
	->onclick($oAdmin_Form_Controller->getAdminLoadAjax($sFormPath, NULL, NULL, ''))
);

$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

// Действие "Применить"
$oAdminFormActionApply = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id)
	->Admin_Form_Actions
	->getByName('apply');

if ($oAdminFormActionApply && $oAdmin_Form_Controller->getAction() == 'apply')
{
	$oControllerApply = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Apply', $oAdminFormActionApply
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oControllerApply);
}

// Действие "Копировать"
$oAdminFormActionCopy = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id)
	->Admin_Form_Actions
	->getByName('copy');

if ($oAdminFormActionCopy && $oAdmin_Form_Controller->getAction() == 'copy')
{
	$oControllerCopy = Admin_Form_Action_Controller::factory(
		'Admin_Form_Action_Controller_Type_Copy', $oAdminFormActionCopy
	);

	// Добавляем типовой контроллер редактирования контроллеру формы
	$oAdmin_Form_Controller->addAction($oControllerCopy);
}

// Источник данных
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Counter_Session')
);

$aSetting = Core_Array::get(Core::$config->get('counter_setting'), 'setting', array());
$iFromTimestamp = strtotime("-{$aSetting['showDays']} day");

!isset($oAdmin_Form_Controller->request['admin_form_filter_from_421']) && $oAdmin_Form_Controller->request['admin_form_filter_from_421'] = Core_Date::timestamp2date($iFromTimestamp);
!isset($oAdmin_Form_Controller->request['admin_form_filter_to_421']) &&	$oAdmin_Form_Controller->request['admin_form_filter_to_421'] = Core_Date::timestamp2date(time());

// Ограничение по сайту
$oAdmin_Form_Dataset->addCondition(
	array('select' => array('counter_sessions.id',
		array('counter_pages.date', 'date'),
		'user_agent',
		array('COUNT(counter_sessions.id)', 'adminCount'))
	)
)
->addCondition(
		array('join' => array('counter_pages', 'counter_sessions.id', '=', 'counter_pages.counter_session_id')
	)
)
->addCondition(
	array('where' =>
		array('counter_pages.site_id', '=', CURRENT_SITE)
	)
)
->addCondition(
	array('where' =>
		array('counter_sessions.bot', '=', 0)
	)
)
->addCondition(
	array('groupBy' => array('user_agent')
	)
);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

$aObjects = $oAdmin_Form_Controller->setDatasetConditions()->getDataset(0)->load();

if (count($aObjects))
{
	count($aObjects) > 12 && $aObjects = array_slice($aObjects, 0, 12);
	
	ob_start();

	$sWindowId = $oAdmin_Form_Controller->getWindowId();
	$aColors = Core_Array::get(Core::$config->get('counter_color'), 'Pie3D', array());

	?><div class="col-xs-12 col-lg-8">
		<div class="widget counter">
			<div class="widget-body">
				<div class="row">
					<div class="col-xs-12">
						<div id="userAgentsDiagram" class="chart"></div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		$(function(){
			var diagramData = [];
			<?php
			$i = 0;
			foreach ($aObjects as $key => $oObject)
			{
				?>
				diagramData.push(
					{
						label:'<?php echo Core_Str::escapeJavascriptVariable($oObject->user_agent())?>',
						data:[[1, <?php echo $oObject->adminCount?>]],
						color: '#<?php echo $aColors[$i % count($aColors)]?>'

					}
				);
				<?php
				$i++;
			}
			?>
			var placeholderUserAgentsDiagram = $("#<?php echo $sWindowId?> #userAgentsDiagram");

			placeholderUserAgentsDiagram.unbind();

			$.plot(placeholderUserAgentsDiagram, diagramData, {
				series: {
					pie: {
						show: true,
						radius: 1,
						innerRadius: 0.5,
						 label: {
                                show: true,
								radius: 3 / 4,
                                formatter: function labelFormatter(label, series) {
												return "<div style='font-size:8pt; text-align:center; padding:2px; color:white;'>" + Math.round(series.percent) + "%</div>";
											},
                                threshold: 0.1
                            }
					}
				}
				,
				grid: {
					hoverable: true
				//	clickable: true
				}

			});

			placeholderUserAgentsDiagram.bind("plothover", function (event, pos, obj) {

				if (!obj) {
					return;
				}

				var percent = parseFloat(obj.series.percent).toFixed(2);
				$("#hover").html("<span style='font-weight:bold; color:" + obj.series.color + "'>" + obj.series.label + " (" + percent + "%)</span>");
			});

			placeholderUserAgentsDiagram.bind("plotclick", function (event, pos, obj) {

				if (!obj) {
					return;
				}

				percent = parseFloat(obj.series.percent).toFixed(2);
				alert("" + obj.series.label + ": " + percent + "%");
			});
		})
	</script>

	<?php

	$oAdmin_Form_Controller->addEntity(
		Admin_Form_Entity::factory('Code')->html(ob_get_clean())
	);
}

$oAdmin_Form_Controller->execute();