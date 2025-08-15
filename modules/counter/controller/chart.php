<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Counter_Controller_Chart
 *
 * @package HostCMS
 * @subpackage Counter
 * @version 7.x
 * @copyright © 2005-2025 ООО "Хостмэйк"(Hostmake LLC), http://www.hostcms.ru
 */
class Counter_Controller_Chart
{
	/**
	 * Show chart widget
	 * @param int $month
	 * @param bool $bShowHeader
	 * @param string $path
	 * @return string
	 */
	static public function show($month = 6, $bShowHeader = FALSE, $path = '')
	{
		ob_start();
		?>
		<div class="widget counter">
			<?php
			if ($bShowHeader)
			{
				?><div class="widget-header bordered-bottom bordered-themeprimary">
					<i class="widget-icon fa fa-bar-chart-o themeprimary"></i>
					<span class="widget-caption themeprimary"><?php echo Core::_('Counter.index_all_stat')?></span>
					<div class="widget-buttons">
						<a data-toggle="maximize">
							<i class="fa fa-expand gray"></i>
						</a>
						<a data-toggle="refresh" onclick="$(this).find('i').addClass('fa-spin'); $.widgetLoad({ path: '<?php echo Core_Str::escapeJavascriptVariable($path)?>', context: $('#counterAdminPage'), 'button': $(this).find('i') });">
							<i class="fa-solid fa-rotate gray"></i>
						</a>
					</div>
				</div><?php
			}
			?>
			<div class="widget-body">
				<div class="tabbable">
					<ul id="counterTabs" class="nav nav-tabs tabs-flat nav-justified">
						<li class="active">
							<a href="#website_traffic" data-toggle="tab"><?php echo Core::_('Counter.website_traffic')?></a>
						</li>
						<li class="">
							<a href="#sessions" data-toggle="tab"><?php echo Core::_('Counter.website_sessions')?></a>
						</li>
						<li class="">
							<a href="#hits" data-toggle="tab"><?php echo Core::_('Counter.graph_hits')?></a>
						</li>
						<li class="">
							<a href="#hosts" data-toggle="tab"><?php echo Core::_('Counter.graph_hosts')?></a>
						</li>
						<li class="">
							<a href="#new_users" data-toggle="tab"><?php echo Core::_('Counter.graph_new_users')?></a>
						</li>
						<li class="">
							<a href="#search_bots" data-toggle="tab"><?php echo Core::_('Counter.crawlers')?></a>
						</li>
					</ul>

					<div class="tab-content tabs-flat no-padding">
						<div id="website_traffic" class="tab-pane animated fadeInUp active">
							<div class="row">
								<div class="col-xs-12">
									<div id="website-traffic-chart" class="chart chart-lg" style="width:100%"></div>
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
						<div id="sessions" class="tab-pane padding-left-5 padding-right-10 animated fadeInUp">
							<div class="row">
								<div class="col-xs-12">
									<div id="sessions-chart" class="chart chart-lg" style="width:100%"></div>
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
						<div id="hits" class="tab-pane padding-left-5 padding-right-10 animated fadeInUp">
							<div class="row">
								<div class="col-xs-12">
									<div id="hits-chart" class="chart chart-lg" style="width:100%"></div>
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
						<div id="hosts" class="tab-pane padding-left-5 padding-right-10 animated fadeInUp">
							<div class="row">
								<div class="col-xs-12">
									<div id="hosts-chart" class="chart chart-lg" style="width:100%"></div>
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
						<div id="new_users" class="tab-pane padding-left-5 padding-right-10 animated fadeInUp">
							<div class="row">
								<div class="col-xs-12">
									<div id="new-users-chart" class="chart chart-lg" style="width:100%"></div>
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
		$iBeginTimestamp = strtotime("-{$month} month");
		$iEndTimestamp = Core_Date::date2timestamp(date('Y-m-d 23:59:59'));

		$oCounters = Core_Entity::factory('Site', CURRENT_SITE)->Counters;
		$oCounters
			->queryBuilder()
			->where('date', '>=', date('Y-m-d 00:00:00', $iBeginTimestamp))
			->clearOrderBy()
			->orderBy('date', 'ASC');

		$aCounters = $oCounters->findAll(FALSE);

		// Началом периода считается первая найденная дата
		isset($aCounters[0])
			&& $iBeginTimestamp = Core_Date::date2timestamp($aCounters[0]->date);

		$aHits = array();
		for ($iTmp = $iBeginTimestamp; $iTmp <= $iEndTimestamp; $iTmp += 86400)
		{
			$aHits["'" . date('Y-m-d', $iTmp) . "'"] = 0;
		}

		$aBots = $aHosts = $aNewUsers = $aSessions = $aHits;

		foreach ($aCounters as $oCounter)
		{
			$index = "'" . $oCounter->date . "'";

			$aSessions[$index] = $oCounter->sessions;
			$aHits[$index] = $oCounter->hits;
			$aHosts[$index] = $oCounter->hosts;
			$aNewUsers[$index] = $oCounter->new_users;
			$aBots[$index] = $oCounter->bots;
		}

		$sTitles = implode(',', array_keys($aHits));
		$sHits = implode(',', array_values($aHits));
		$sHosts = implode(',', array_values($aHosts));
		$sBots = implode(',', array_values($aBots));
		$sSessions = implode(',', array_values($aSessions));
		$sNewUsers = implode(',', array_values($aNewUsers));

		?><script>
			$(function(){
			//$(window).bind("load", function () {
				var titles = [<?php echo $sTitles?>],
					sessions_values = [<?php echo $sSessions?>],
					hits_values = [<?php echo $sHits?>],
					hosts_values = [<?php echo $sHosts?>],
					new_users_values = [<?php echo $sNewUsers?>],
					bots_values = [<?php echo $sBots?>],
					valueTitlesSessions = new Array(),
					valueTitlesHits = new Array(),
					valueTitlesHosts = new Array(),
					valueTitlesNewUsers = new Array(),
					valueTitlesBots = new Array();

				for(var i = 0; i < sessions_values.length; i++) {
					valueTitlesSessions.push([new Date(titles[i]), sessions_values[i]]);
					valueTitlesHits.push([new Date(titles[i]), hits_values[i]]);
					valueTitlesHosts.push([new Date(titles[i]), hosts_values[i]]);
					valueTitlesNewUsers.push([new Date(titles[i]), new_users_values[i]]);
					valueTitlesBots.push([new Date(titles[i]), bots_values[i]]);
				}

				var themeprimary = getThemeColorFromCss('themeprimary'), gridbordercolor = "#eee", dataWebsiteTraffic = [
					{
						color: themeprimary,
						label: "<?php echo Core::_('Counter.graph_sessions')?>",
						data: valueTitlesSessions
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
				],
				dataSearchBots = [
					{
						color: themefifthcolor,
						label: "<?php echo Core::_('Counter.graph_bots')?>",
						data: valueTitlesBots
					}
				],
				dataSessions = [
					{
						color: themeprimary,
						label: "<?php echo Core::_('Counter.graph_sessions')?>",
						data: valueTitlesSessions
					}
				],
				dataHits = [
					{
						color: themesecondary,
						label: "<?php echo Core::_('Counter.graph_hits')?>",
						data: valueTitlesHits
					}
				],
				dataHosts = [
					{
						color: themethirdcolor,
						label: "<?php echo Core::_('Counter.graph_hosts')?>",
						data: valueTitlesHosts
					}
				],
				dataNewUsers = [
					{
						color: themefourthcolor,
						label: "<?php echo Core::_('Counter.graph_new_users')?>",
						data: valueTitlesNewUsers
					}
				];

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
						color: gridbordercolor,
						tickDecimals: 0
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

				$.getMultiContent([
						'jquery.flot.js',
						'jquery.flot.time.min.js',
						'jquery.flot.categories.min.js',
						'jquery.flot.tooltip.min.js',
						'jquery.flot.crosshair.min.js',
						'jquery.flot.selection.min.js',
						'jquery.flot.pie.min.js',
						'jquery.flot.resize.js'
					], '/modules/skin/bootstrap/js/charts/flot/').done(function() {
					// all scripts loaded
					var placeholderWebsiteTraffic = $("#website-traffic-chart"),
						placeholderSearchBots = $("#search-bots-chart"),
						placeholderSessions = $("#sessions-chart"),
						placeholderHits = $("#hits-chart"),
						placeholderHosts = $("#hosts-chart"),
						placeholderNewUsers = $("#new-users-chart");

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

					placeholderSessions.bind("plotselected", function (event, ranges) {
						//var zoom = $("#zoom").is(":checked");
						//if (zoom) {
							plotSessions = $.plot(placeholderSessions, dataSessions, $.extend(true, {}, options, {
								xaxis: {
									min: ranges.xaxis.from,
									max: ranges.xaxis.to
								}
							}));
						//}
					});

					placeholderHits.bind("plotselected", function (event, ranges) {
						//var zoom = $("#zoom").is(":checked");
						//if (zoom) {
							plotHits = $.plot(placeholderHits, dataHits, $.extend(true, {}, options, {
								xaxis: {
									min: ranges.xaxis.from,
									max: ranges.xaxis.to
								}
							}));
						//}
					});

					placeholderHosts.bind("plotselected", function (event, ranges) {
						//var zoom = $("#zoom").is(":checked");
						//if (zoom) {
							plotHosts = $.plot(placeholderHosts, dataHosts, $.extend(true, {}, options, {
								xaxis: {
									min: ranges.xaxis.from,
									max: ranges.xaxis.to
								}
							}));
						//}
					});

					placeholderNewUsers.bind("plotselected", function (event, ranges) {
						//var zoom = $("#zoom").is(":checked");
						//if (zoom) {
							plotNewUsers = $.plot(placeholderNewUsers, dataNewUsers, $.extend(true, {}, options, {
								xaxis: {
									min: ranges.xaxis.from,
									max: ranges.xaxis.to
								},
								yaxis: {
									// min: 0,
									// max: <?php echo max($aNewUsers)?>
									min: -5,
									max: 5
								}
							}));
						//}
					});

					/*
					$("#zoom").on('change', function(){
						$this = $(this);

						if (!$this.prop('checked'))
						{
							$('#setOriginalZoom').hide();
							plot = $.plot(placeholder, data, options);
						}
						else
						{
							$('#setOriginalZoom').show();
						}
					});
					*/

					$('#website_traffic #setOriginalZoom').on('click', function(){
						plotWebsiteTraffic = $.plot(placeholderWebsiteTraffic, dataWebsiteTraffic, options);
					});

					$('#search_bots #setOriginalZoom').on('click', function(){
						plotSearchBots = $.plot(placeholderSearchBots, dataSearchBots, options);
					});

					$('#sessions #setOriginalZoom').on('click', function(){
						plotSessions = $.plot(placeholderSessions, dataSessions, options);
					});

					$('#hits #setOriginalZoom').on('click', function(){
						plotHits = $.plot(placeholderHits, dataHits, options);
					});

					$('#hosts #setOriginalZoom').on('click', function(){
						plotHosts = $.plot(placeholderHosts, dataHosts, options);
					});

					$('#new_users #setOriginalZoom').on('click', function(){
						plotNewUsers = $.plot(placeholderNewUsers, dataNewUsers, options);
					});

					/*placeholderWebsiteTraffic.bind("plotunselected", function (event) {
						// Do Some Work
					});*/

					setTimeout(function() {
						var plotWebsiteTraffic = $.plot(placeholderWebsiteTraffic, dataWebsiteTraffic, options),
							plotSearchBots = $.plot(placeholderSearchBots, dataSearchBots, options),
							plotSessions = $.plot(placeholderSessions, dataSessions, options),
							plotHits = $.plot(placeholderHits, dataHits, options),
							plotHosts = $.plot(placeholderHosts, dataHosts, options),
							plotNewUsers = $.plot(placeholderNewUsers, dataNewUsers, options);

						$("#website_traffic #clearSelection").click(function () {
							plotWebsiteTraffic.clearSelection();
						});

						$("#search_bots #clearSelection").click(function () {
							plotSearchBots.clearSelection();
						});

						$("#sessions #clearSelection").click(function () {
							plotSessions.clearSelection();
						});

						$("#hits #clearSelection").click(function () {
							plotHits.clearSelection();
						});

						$("#hosts #clearSelection").click(function () {
							plotHosts.clearSelection();
						});

						$("#new_users #clearSelection").click(function () {
							plotNewUsers.clearSelection();
						});
					}, 200);
				});
			});
		</script>
		<?php
		return ob_get_clean();
	}
}