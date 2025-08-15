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
$iAdmin_Form_Id = 121;
$sAdminFormAction = '/admin/counter/summary/index.php';

$sCounterPath = '/admin/counter/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$sTitle = Core::_('Counter_Session.summary_statistics');

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

!isset($oAdmin_Form_Controller->request['admin_form_filter_from_541']) && $oAdmin_Form_Controller->request['admin_form_filter_from_541'] = Core_Date::timestamp2date($iFromTimestamp);
!isset($oAdmin_Form_Controller->request['admin_form_filter_to_541']) &&	$oAdmin_Form_Controller->request['admin_form_filter_to_541'] = Core_Date::timestamp2date(time());

// Ограничение по сайту
$oAdmin_Form_Dataset->addCondition(
	array('select' => array('counter_sessions.*',
		array('counter_pages.date', 'date'),
		array('COUNT(counter_pages.id)', 'adminCount'))
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
		array('counter_sessions.bot', '=', 1)
	)
)
->addCondition(
	array('groupBy' => array('user_agent'))
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

	$aColors = Core_Array::get(Core::$config->get('counter_color'), 'Pie3D', array());
	$iCountColors = count($aColors);
	$sWindowId = $oAdmin_Form_Controller->getWindowId();
	?>
	<div class="col-xs-12 col-lg-8">
		<div class="widget counter">
			<div class="widget-body">
				<div class="row">
					<div class="col-xs-12">
						<div id="summaryDiagram" class="chart"></div>
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
						label:'<?php echo Core_Str::escapeJavascriptVariable(htmlspecialchars(Core_Str::cut($oObject->user_agent, $aSetting['nameLen'])))?>',
						data:[[1, <?php echo $oObject->adminCount?>]],
						color: '#<?php echo $aColors[$key % $iCountColors]?>'
					}
				);
				<?php
				$i++;
			}
			?>
			var placeholderSummaryDiagram = $("#<?php echo $sWindowId?> #summaryDiagram");

			$.plot(placeholderSummaryDiagram, diagramData, {
				series: {
					pie: {
						show: true,
						radius: 1,
						//radius: 120,
						innerRadius: 0.7,

						label: {
                                show: true,
								radius: 0,
								formatter: function(label, series) {
												return "<div style='font-size:8pt;'>" + label + "</div>";
								}
						}
					}
				},

				legend: {
					labelFormatter: function (label, series) {
						//return "<div style='font-size:8pt; text-align:center; padding:2px;'>" + label + "<br/>" + Math.round(series.percent) + "%</div>";
						return label + ", " + Math.round(series.percent  * 100) / 100 + "%";
					}
				}
				,
				grid: {
					hoverable: true,
					//clickable: true
				}

			});

			placeholderSummaryDiagram.bind("plothover", function (event, pos, obj) {
				if (!obj) {
					return;
				}

				$("#<?php echo $sWindowId?> #summaryDiagram span[id ^= 'pieLabel']").hide();
				$("#<?php echo $sWindowId?> #summaryDiagram span[id ^= 'pieLabel" + obj.seriesIndex + "']").show();
			});

			placeholderSummaryDiagram.resize(function(){$("#<?php echo $sWindowId?> #summaryDiagram span[id ^= 'pieLabel']").hide();});

			$("#<?php echo $sWindowId?> #summaryDiagram span[id ^= 'pieLabel']").hide();
		})
	</script>
	<?php
	$oAdmin_Form_Controller->addEntity(
		Admin_Form_Entity::factory('Code')->html(ob_get_clean())
	);
}

$oAdmin_Form_Controller->execute();