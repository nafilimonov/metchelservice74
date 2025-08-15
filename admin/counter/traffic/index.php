<?php

/**
 * Counter.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2025, https://www.hostcms.ru
 */
require_once('../../../bootstrap.php');

Core_Auth::authorization($sModule = 'counter');

// Код формы
$iAdmin_Form_Id = 96;
$sAdminFormAction = '/{admin}/counter/traffic/index.php';

$sCounterPath = '/{admin}/counter/index.php';

$oAdmin_Form = Core_Entity::factory('Admin_Form', $iAdmin_Form_Id);

$sTitle = Core::_('Counter.traffic_title');

// Контроллер формы
$oAdmin_Form_Controller = Admin_Form_Controller::create($oAdmin_Form);

$oAdmin_Form_Controller
	->module(Core_Module_Abstract::factory($sModule))
	->setUp()
	->path($sAdminFormAction)
	->title($sTitle)
	->pageTitle($sTitle);

$sFormPath = $oAdmin_Form_Controller->getPath();

// подключение верхнего меню
include CMS_FOLDER . Admin_Form_Controller::correctBackendPath('/{admin}/counter/menu.php');

// Добавляем все меню контроллеру
$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Menus);

// Строка навигации
$oAdmin_Form_Entity_Breadcrumbs = Admin_Form_Entity::factory('Breadcrumbs');

// Элементы строки навигации
$oAdmin_Form_Entity_Breadcrumbs->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name(Core::_('Counter.title'))
		->href($oAdmin_Form_Controller->getAdminLoadHref($sCounterPath, NULL, NULL, ''))
		->onclick($oAdmin_Form_Controller->getAdminLoadAjax($sCounterPath, NULL, NULL, ''))
)
->add(
	Admin_Form_Entity::factory('Breadcrumb')
		->name($sTitle)
		->href($oAdmin_Form_Controller->getAdminLoadHref($sFormPath, NULL, NULL, ''))
		->onclick($oAdmin_Form_Controller->getAdminLoadAjax($sFormPath, NULL, NULL, ''))
);

$oAdmin_Form_Controller->addEntity($oAdmin_Form_Entity_Breadcrumbs);

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

// Источник данных
$oAdmin_Form_Dataset = new Admin_Form_Dataset_Entity(
	Core_Entity::factory('Counter')
);

$aSetting = Core_Array::get(Core::$config->get('counter_setting'), 'setting', array());

(!isset($oAdmin_Form_Controller->request['admin_form_filter_from_398'])
	|| !strlen($oAdmin_Form_Controller->request['admin_form_filter_from_398']))
		&& $oAdmin_Form_Controller->request['admin_form_filter_from_398'] = Core_Date::timestamp2date(strtotime('-12 month'));

(!isset($oAdmin_Form_Controller->request['admin_form_filter_to_398'])
	|| !strlen($oAdmin_Form_Controller->request['admin_form_filter_to_398']))
		&& $oAdmin_Form_Controller->request['admin_form_filter_to_398'] = Core_Date::timestamp2date(time());

// Ограничение по сайту
$oAdmin_Form_Dataset->addCondition(
	array('where' => array('site_id', '=', CURRENT_SITE))
);

// Добавляем источник данных контроллеру формы
$oAdmin_Form_Controller->addDataset(
	$oAdmin_Form_Dataset
);

class Counter_Traffic_Observer
{
	static public function onAfterSetConditionsAndLimits($controller)
	{
		$oAdmin_Form_Controller = $controller->getAdminFormController();

		$iBeginTimestamp = Core_Date::datetime2timestamp($oAdmin_Form_Controller->request['admin_form_filter_from_398'] . ' 12:00:00');
		$iEndTimestamp = Core_Date::datetime2timestamp($oAdmin_Form_Controller->request['admin_form_filter_to_398'] . ' 12:00:00');

		$oCounters = Core_Entity::factory('Site', CURRENT_SITE)->Counters;
		$oCounters
			->queryBuilder()
			->where('date', '>=', Core_Date::timestamp2sqldate($iBeginTimestamp));
		$aObjects = $oCounters->findAll(FALSE);

		if (count($aObjects))
		{
			$sWindowId = $oAdmin_Form_Controller->getWindowId();
			?>
			<div class="widget counter">
				<div class="widget-body">
					<div class="tabbable">
						<ul id="counterTabs" class="nav nav-tabs tabs-flat nav-justified">
							<li class="active">
								<a href="#website_traffic" data-toggle="tab"><?php echo Core::_('Counter.website_traffic')?></a>
							</li>
							<li class="">
								<a href="#search_bots" data-toggle="tab"><?php echo Core::_('Counter.crawlers')?></a>
							</li>
						</ul>

						<div class="tab-content tabs-flat no-padding">
							<div id="website_traffic" class="tab-pane animated fadeInUp active">
								<div class="row">
									<div class="col-xs-12">
										<div id="website-traffic-chart" class="chart chart-lg"></div>
									</div>
								</div>
								<div class="row">
									<div class="col-xs-12">
										<div class="col-sm-12 col-md-6">
											<button class="btn btn-palegreen" id="setOriginalZoom"><i class="fa fa-area-chart icon-separator"></i><?php echo Core::_('Counter.reset')?></button>
										</div>
									</div>
								</div>
							</div>
							<div id="search_bots" class="tab-pane padding-left-5 padding-right-10 animated fadeInUp">
								<div class="row">
									<div class="col-xs-12">
										<div id="search-bots-chart" class="chart chart-lg" style="width:100%"></div>
									</div>
								</div>
								<div class="row">
									<div class="col-xs-12">
										<div class="col-sm-12 col-md-6">
											<button class="btn btn-palegreen" id="setOriginalZoom"><i class="fa fa-area-chart icon-separator"></i><?php echo Core::_('Counter.reset')?></button>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<?php
			$aHits = array();
			for ($iTmp = $iBeginTimestamp; $iTmp <= $iEndTimestamp; $iTmp += 86400)
			{
				$aHits["'" . date('Y-m-d', $iTmp) . "'"] = 0;
			}

			$aBots = $aHosts = $aNewUsers = $aSessions = $aHits;

			foreach ($aObjects as $oCounter)
			{
				$index = "'" . $oCounter->date . "'";

				$aSessions[$index] = $oCounter->sessions;
				$aHits[$index] = $oCounter->hits;
				$aHosts[$index] = $oCounter->hosts;
				$aNewUsers[$index] = $oCounter->new_users;
				$aBots[$index] = $oCounter->bots;
			}

			$sTitles  = implode(',', array_keys($aHits));

			$sHits = implode(',', array_values($aHits));
			$sHosts = implode(',', array_values($aHosts));
			$sBots = implode(',', array_values($aBots));
			$sSessions = implode(',', array_values($aSessions));
			$sNewUsers = implode(',', array_values($aNewUsers));

			?>
			<script type="text/javascript">
			$(function(){
				var aScripts = [
					'jquery.flot.js',
					'jquery.flot.time.min.js',
					'jquery.flot.categories.min.js',
					'jquery.flot.tooltip.min.js',
					'jquery.flot.crosshair.min.js',
					'jquery.flot.selection.min.js',
					'jquery.flot.pie.min.js',
					'jquery.flot.resize.js'
				];

				$.getMultiContent(aScripts, '/modules/skin/bootstrap/js/charts/flot/').done(function() {

					var titles = [<?php echo $sTitles?>],
						sessions_values = [<?php echo $sSessions?>],
						hits_values = [<?php echo $sHits?>],
						hosts_values = [<?php echo $sHosts?>],
						new_users_values = [<?php echo $sNewUsers?>],
						bots_values = [<?php echo $sBots?>],
						valueTitlesSissions = new Array(),
						valueTitlesHits = new Array(),
						valueTitlesHosts = new Array(),
						valueTitlesNewUsers = new Array(),
						valueTitlesBots = new Array();

					for(var i = 0; i < sessions_values.length; i++) {
						valueTitlesSissions.push([new Date(titles[i]), sessions_values[i]]);
						valueTitlesHits.push([new Date(titles[i]), hits_values[i]]);
						valueTitlesHosts.push([new Date(titles[i]), hosts_values[i]]);
						valueTitlesNewUsers.push([new Date(titles[i]), new_users_values[i]]);
						valueTitlesBots.push([new Date(titles[i]), bots_values[i]]);
					}

					var themeprimary = getThemeColorFromCss('themeprimary'), gridbordercolor = "#eee",
					dataWebsiteTraffic = [{
							color: themeprimary,
							label: "<?php echo Core::_('Counter.graph_sessions')?>",
							data: valueTitlesSissions
						},
						{
							color: themesecondary,
							label: "<?php echo Core::_('Counter.graph_hits')?>",
							data: valueTitlesHits
						},
						{
							color: themethirdcolor,
							label: "<?php echo Core::_('Counter.graph_hosts')?>",
							data: valueTitlesHosts
						},
						{
							color: themefourthcolor,
							label: "<?php echo Core::_('Counter.graph_new_users')?>",
							data: valueTitlesNewUsers
						}
						/*,
						{
							color: themefifthcolor,
							label: "<?php echo Core::_('Counter.stat_bots')?>",
							data: valueTitlesBots
						}*/
					],
					dataSearchBots = [{
						color: themefifthcolor,
						label: "<?php echo Core::_('Counter.graph_bots')?>",
						data: valueTitlesBots
					}];

					var options = {
						series: {
							lines: {
								show: true
							},
							points: {
								show: true
							}
						},
						legend: {
							noColumns: 4,
							backgroundOpacity: 0.65
						},
						xaxis: {
							mode: "time",
							timeformat: "%d.%m.%Y",
							//tickDecimals: 0,
							color: gridbordercolor

						},
						yaxis: {
							min: 0,
							color: gridbordercolor
						},
						selection: {
							mode: "x"
						},
						grid: {
							hoverable: true,
							clickable: false,
							borderWidth: 0,
							aboveData: false
						},
						tooltip: true,
						tooltipOpts: {
							defaultTheme: false,
							dateFormat: "%d.%m.%Y",
							content: "<b>%s</b> : <span>%x</span> : <span>%y</span>",
						},
						crosshair: {
							mode: "x"
						}
					};

					// Traffic
					var placeholderWebsiteTraffic = $("#<?php echo $sWindowId?> #website-traffic-chart"),
						plotWebsiteTraffic = $.plot(placeholderWebsiteTraffic, dataWebsiteTraffic, options);

					placeholderWebsiteTraffic.bind("plotselected", function (event, ranges) {
						//var zoom = $("#zoom").is(":checked");
						//if (zoom) {
							plotWebsiteTraffic = $.plot(placeholderWebsiteTraffic, dataWebsiteTraffic, $.extend(true, {}, options, {
								xaxis: {
									min: ranges.xaxis.from,
									max: ranges.xaxis.to
								}
							}));
						//}
					});
					$('#<?php echo $sWindowId?> #website_traffic #setOriginalZoom').on('click', function(){
						plotWebsiteTraffic = $.plot(placeholderWebsiteTraffic, dataWebsiteTraffic, options);
					});
					$("#<?php echo $sWindowId?> #website_traffic #clearSelection").click(function () {
						plotWebsiteTraffic.clearSelection();
					});

					// Bots
					var placeholderSearchBots = $("#<?php echo $sWindowId?> #search-bots-chart"),
						plotSearchBots = $.plot(placeholderSearchBots, dataSearchBots, options);

					placeholderSearchBots.bind("plotselected", function (event, ranges) {
						//var zoom = $("#zoom").is(":checked");
						//if (zoom) {
							plotSearchBots = $.plot(placeholderSearchBots, dataSearchBots, $.extend(true, {}, options, {
								xaxis: {
									min: ranges.xaxis.from,
									max: ranges.xaxis.to
								}
							}));
						//}
					});
					$('#<?php echo $sWindowId?> #search_bots #setOriginalZoom').on('click', function(){
						plotSearchBots = $.plot(placeholderSearchBots, dataSearchBots, options);
					});
					$("#<?php echo $sWindowId?> #search_bots #clearSelection").click(function () {
						plotSearchBots.clearSelection();
					});

				});
			});
			</script>
			<?php
		}
	}
}

Core_Event::attach('Admin_Form_Controller.onAfterSetConditionsAndLimits', array('Counter_Traffic_Observer', 'onAfterSetConditionsAndLimits'));

$oAdmin_Form_Controller->execute();