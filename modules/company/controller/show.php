<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Company_Controller_Show
 *
 * @package HostCMS
 * @subpackage Company
 * @version 6.x
 * @author Hostmake LLC
 * @copyright © 2005-2019 ООО "Хостмэйк" (Hostmake LLC), http://www.hostcms.ru
 */
class Company_Controller_Show{
	/**
	 * Company_Model
	 * @var object
	 */
	protected $_oCompany = NULL;

	/**
	 * Window ID
	 * @var int
	 */
	protected $_windowId = NULL;

	/**
	 * Path
	 * @var string
	 */
	protected $_path = NULL;

	/**
	 * Set path
	 * @param string $path path
	 * @return self
	 */
	public function path($path)
	{
		$this->_path = $path;
		return $this;
	}

	/**
	 * Get path
	 * @return string
	 */
	public function getPath()
	{
		return $this->_path;
	}

	/**
	 * Constructor.
	 * @param string $primaryKey
	 */
	public function __construct(Company_Model $oCompany)
	{
		$this->_oCompany = $oCompany;
	}

	/**
	 * Set window id
	 * @param string $windowId window id
	 * @return self
	 */
	public function window($windowId)
	{
		$this->_windowId = $windowId;
		return $this;
	}

	public function show()
	{
		$aCompany_Departments = $this->_oCompany->Company_Departments->findAll();

		if (count($aCompany_Departments))
		{
			$oDivCompanyStructure = Admin_Form_Entity::factory('Div')
				->class('row')
				->add(
					Admin_Form_Entity::factory('Div')
						->class('col-xs-12')
						->add(
							//Admin_Form_Entity::factory('Div')
								//->class('well well-company')
								//->add(
									Admin_Form_Entity::factory('Div')
										->class('company dd bordered ')
										//->add(Admin_Form_Entity::factory('Code')
										//		->html("<ol class=\"dd-list\">" . $this->_showLevel(0) . "</ol>")
										->add(
											Admin_Form_Entity::factory('Code')
												->html($this->_showLevel(0))
										)
										->add(
											Admin_Form_Entity::factory('Code')
												->html("<script>
													   jQuery(function ($) {
															var aScripts = [
																'jquery.nestable.min.js'
															];

															$.getMultiContent(aScripts, '/modules/skin/bootstrap/js/nestable/').done(function() {
															// all scripts loaded

																var divCompanyStructure = $('.dd');
																divCompanyStructure.nestable({maxDepth: 30});

																divCompanyStructure.data('serializedCompanyStructure', divCompanyStructure.nestable('serialize'));

																/*
																$('.dd button').click();

																$('.department-users.widget.collapsed .widget-buttons *[data-toggle=\"collapse\"]').click();
																*/

																$('.dd-handle', divCompanyStructure).on('mousedown touchstart', '.dropdown-backdrop', function(e){
																	e.stopPropagation();
																});

																$('.control-buttons-wrapper', divCompanyStructure).on('mousedown touchstart', function(e){
																	e.stopPropagation();
																});

																//$('.dd-handle .btn', divCompanyStructure).on('mousedown  touchstart', function (e) {
																$('.dd-handle [data-action]', divCompanyStructure).on('mousedown  touchstart', function (e) {
																	e.stopPropagation();
																});

																$('.dd-handle .dropdown-menu', divCompanyStructure).on('mousedown  touchstart', function (e) {
																	e.stopPropagation();
																});

																$('.dd-handle .databox', divCompanyStructure).on('mousedown touchstart', function (e) {
																	e.stopPropagation();
																});

																$('.dd-handle .widget', divCompanyStructure).on('mousedown touchstart', function (e) {
																	e.stopPropagation();
																});

																// Функция получения идентификатора родительского отдела по идентификатору отдела
																function getParentIdByDepartmentId (serializedCompanyStructure, departmentId, currentParentDepartmentId)
																{
																	var currentParentDepartmentId = currentParentDepartmentId || 0;

																	for (var i = 0; i < serializedCompanyStructure.length; i++)
																	{
																		if (serializedCompanyStructure[i].id == departmentId)
																		{
																			return currentParentDepartmentId;
																		}
																		else if (serializedCompanyStructure[i].children)
																		{
																			var parentDepartmentId = getParentIdByDepartmentId(serializedCompanyStructure[i].children, departmentId, serializedCompanyStructure[i].id);

																			if (parentDepartmentId)
																			{
																				return parentDepartmentId;
																			}
																		}
																	}
																}

																function nestableOnStartEvent(event)
																{
																	var \$this = $(this),
																		departmentId = \$this.closest('li.dd-item').data('id'),
																		divCompanyStructure = $('.dd'),
																		parentDepartmentId = getParentIdByDepartmentId(divCompanyStructure.data('serializedCompanyStructure'), departmentId);

																	divCompanyStructure.data({'departmentId': departmentId, 'parentDepartmentId': parentDepartmentId});
																}

																function nestableOnEndEvent(event)
																{
																	var	divCompanyStructure = $('.dd'),
																		parentDepartmentId = divCompanyStructure.data('parentDepartmentId'),
																		departmentId = divCompanyStructure.data('departmentId'),
																		newParentDepartmentId = $('li[data-id = ' + departmentId + '] ', divCompanyStructure).closest('ol.dd-list').closest('li.dd-item').data('id') || 0;

																	if (newParentDepartmentId != parentDepartmentId)
																	{
																		bootbox.setLocale('ru');
																		bootbox.confirm({
																			title: '" . Core::_('Company_Department.edit_form_title') . "',
																			message: '" . Core::_('Company_Department.moveMessage') . "',
																			callback: function (result) {
																				var divCompanyStructure = $('.dd'),
																					paramAdminLoad = {
																					'path': '{$this->_path}',
																					'additionalParams': 'company_id=' + {$this->_oCompany->id},
																					'windowId' : '{$this->_windowId}'
																				}

																				if (result) {
																					paramAdminLoad.additionalParams += '&department_id=' + departmentId + '&new_parent_id=' + newParentDepartmentId;
																					paramAdminLoad.operation = 'changeParentDepartment';
																					paramAdminLoad.action = 'addEditDepartment';
																				}

																				$.adminLoad(paramAdminLoad);
																				divCompanyStructure.data('serializedCompanyStructure', divCompanyStructure.nestable('serialize'));
																			}
																		});
																	}
																}

																divCompanyStructure
																	.on('mousedown', '.dd-handle', nestableOnStartEvent)
																	.on('change', nestableOnEndEvent);

																$(document).on('touchstart', '.dd-handle', nestableOnStartEvent);
																	//.on('touchend', '.dd-handle', nestableOnEndEvent);


																// Действия с отделами
																$('.department [data-action]').on('click', function(){
																		var \$this = $(this),
																		actionName = \$this.data('action'),
																		paramAdminLoad = {
																			path: '{$this->_path}',
																			additionalParams: 'company_id={$this->_oCompany->id}&department_id=' + \$this.closest('li.dd-item').data('id'),
																			//action: actionName === 'edit' ? 'addEditDepartment' : 'deleteDepartment',
																			windowId:'{$this->_windowId}'
																		};

																		// console.log(actionName);
																		switch(actionName)
																		{
																			case 'delete':
																				bootbox.setLocale('ru');
																				bootbox.confirm({
																					title: '" . Core::_('Company_Department.delete_form_title') . "',
																					message: '" . Core::_('Company_Department.deleteMessage') . "',
																					callback: function (result) {
																						if (result) {
																							paramAdminLoad.operation = 'deleteDepartment';
																							paramAdminLoad.action = 'deleteDepartment';
																							$.adminLoad(paramAdminLoad);
																						}
																					}
																				});
																				break;
																			case 'edit':
																				paramAdminLoad.action = 'addEditDepartment';
																				$.adminLoad(paramAdminLoad);
																				break;
																			case 'add_user':
																				paramAdminLoad.action = 'addEditUserDepartment';
																				$.adminLoad(paramAdminLoad);
																				break;
																			case 'edit_user':
																				var userElement = \$this.closest('.user');
																				paramAdminLoad.action = 'addEditUserDepartment';
																				paramAdminLoad.additionalParams += '&user_id=' + userElement.data('user-id') + '&company_post_id=' + userElement.data('company-post-id');
																				$.adminLoad(paramAdminLoad);
																				break;
																			case 'delete_user':
																				bootbox.setLocale('ru');
																				bootbox.confirm({
																					title: '" . Core::_('Company_Department.delete_user_title') . "',
																					message: '" . Core::_('Company_Department.deleteUserMessage') . "',
																					callback: function (result) {
																						if (result) {
																							var userElement = \$this.closest('.user');
																							paramAdminLoad.operation = 'deleteUserFromDepartment';
																							paramAdminLoad.action = 'deleteUserFromDepartment';
																							paramAdminLoad.additionalParams += '&user_id=' + userElement.data('user-id') + '&company_post_id=' + userElement.data('company-post-id');

																							$.adminLoad(paramAdminLoad);
																						}
																					}
																				});
																				break;
																		}
																	}
																);


																$('.department-users .scroll-wrapper').each(function (){
																	var \$this = $(this);

																	if (\$this.find('tr.user').length > 3)
																	{
																		\$this.slimscroll({
																			// height: '215px',
																			alwaysVisible: true,
																			height: 'auto',
																			//color: 'rgba(0,0,0,0.3)',
																			color: themeprimary,
																			size: '5px'
																		});
																	}

																	//console.log($(this), $(this).outerHeight());
																});

																$('.dd button').click();

																$('.department-users.widget.collapsed .widget-buttons *[data-toggle=\"collapse\"]').click();

															});
														});
													</script>")
										)
						//		)
						)

				)
				->execute();
		}
	}

	protected function _showLevel($parent_id)
	{
		$aHeadIds = array();

		$oCompany_Departments = $this->_oCompany->Company_Departments;
		$oCompany_Departments->queryBuilder()
			->where('parent_id', '=', $parent_id);

		$aCompany_Departments = $oCompany_Departments->findAll();
		if (count($aCompany_Departments))
		{
			ob_start();
			?>
			<ol class="dd-list">
			<?php
			foreach ($aCompany_Departments as $oCompany_Department)
			{
				?><li class="dd-item" data-id="<?php echo $oCompany_Department->id?>">
					<div class="dd-handle department"><?php echo htmlspecialchars($oCompany_Department->name)?>
						<div class="control-buttons-wrapper department-top-actions">
							<div class="btn-group department-control-buttons">
								<a href="javascript:void(0);" class="bordered-palegreen" data-action="add_user" title="<?php echo Core::_('Company_Department.addUserTitleAction')?>" alt="<?php echo Core::_('Company_Department.addUserTitleAction');?>">
									<i class="fa fa-user-plus palegreen"></i>
								</a>
								<a href="javascript:void(0);" class="bordered-gray" data-action="edit" title="<?php echo Core::_('Company_Department.editTitleAction', htmlspecialchars($oCompany_Department->name))?>" alt="<?php echo Core::_('Company_Department.editTitleAction', htmlspecialchars($oCompany_Department->name))?>">
									<i class="fa fa-pencil darkgray"></i>
								</a>
								<!--<a href="javascript:void(0);" data-action="delete" style="min-width: 20px; display: inline-block; text-align: center;"> -->
								<a href="javascript:void(0);" class="bordered-darkorange" data-action="delete" title="<?php echo Core::_('Company_Department.deleteTitleAction')?>" alt="<?php echo Core::_('Company_Department.deleteTitleAction')?>">
									<i class="fa fa-trash-o darkorange "></i>
								</a>

								<a href="/admin/user/site/index.php?company_department_id=<?php echo $oCompany_Department->id?>" onclick="$.adminLoad({path: '/admin/user/site/index.php',action: '',operation: '',additionalParams: 'company_department_id=<?php echo $oCompany_Department->id?>',current: '1',windowId: 'id_content'}); return false" class="bordered-sky" data-action="module_access" title="<?php echo Core::_('Company_Department.moduleTitleAction')?>" alt="<?php echo Core::_('Company_Department.moduleTitleAction')?>">
									<i class="fa fa-cogs sky"></i>
								</a>
								<a href="/admin/user/site/index.php?company_department_id=<?php echo $oCompany_Department->id?>&mode=action" onclick="$.adminLoad({path: '/admin/user/site/index.php',action: '',operation: '',additionalParams: 'mode=action&company_department_id=<?php echo $oCompany_Department->id?>',current: '1',windowId: 'id_content'}); return false" class="bordered-yellow" data-action="action_access" title="<?php echo Core::_('Company_Department.actionTitleAction'); ?>" alt="<?php echo Core::_('Company_Department.actionTitleAction'); ?>">
									<i class="fa fa-bolt yellow"></i>
								</a>
							</div>
							<!-- <span class="ellipsis"><i class="fa fa-ellipsis-h" aria-hidden="true"></i><span> -->
						</div>

						<?php
						$aUsers = array();

						// Список руководителей
						$aCompany_Department_Heads = $oCompany_Department->getHeads();

						count($aCompany_Department_Heads) && $aUsers = array_merge($aUsers, $aCompany_Department_Heads);

						/*if (count($aUsers))
						{
							foreach ($aUsers as $oUser)
							{
								$aHeadIds[] = $oUser->id;

								// Руководящая должность сотрудника в отделе
								$aUser_Company_Posts = $oUser->getCompanyPostsByDepartment($oCompany_Department->id, TRUE);
								?>
								<!-- bordered-platinum -->
								<div class="department-head user databox margin-top-5 no-margin-bottom" data-user-id="<?php echo $oUser->id?>" data-company-post-id="<?php echo count($aUser_Company_Posts) ? $aUser_Company_Posts[0]->id : "0" ?>">
									<div class="databox-left">
										<?php
											echo "<img style=\"width:45px; height:45px;\" src=\"" . $oUser->getAvatar() . "\" class=\"image-circular\"/>";
										?>
									</div>
									<div class="databox-right padding-top-10 no-padding-left">
										<div class="h5 no-margin"><?php echo
											(!empty($oUser->surname) ? htmlspecialchars($oUser->surname) . ' ' : '' ) .
											(!empty($oUser->name) ? htmlspecialchars($oUser->name) . ' ' : '' ) .
											(!empty($oUser->patronymic) ? htmlspecialchars($oUser->patronymic) . ' ' : '' )?><div class="<?php echo $oUser->isOnline() ? 'online' : 'offline'; ?>"></div></div>
										<div class="databox-text darkgray"><?php echo count($aUser_Company_Posts) ? htmlspecialchars($aUser_Company_Posts[0]->name) : '' ?></div>
									</div>
									<div class="control-buttons-wrapper">
										<div class="control-buttons">
											<a href="javascript:void(0);" data-action="edit_user" title="<?php echo Core::_('Company_Department.editUserDepartmentPostTitleAction');?>" alt="<?php echo Core::_('Company_Department.editUserDepartmentPostTitleAction');?>" class="bordered-gray">
												<i class="fa fa-pencil darkgray"></i>
											</a>
											<a href="javascript:void(0);" data-action="delete_user" title="<?php echo Core::_('Company_Department.deleteUserDepartmentPostTitleAction');?>" alt="<?php echo Core::_('Company_Department.deleteUserDepartmentPostTitleAction');?>" class="bordered-darkorange">
												<i class="fa fa-user-times darkorange"></i>
											</a>
										</div>
										<span class="ellipsis"><i class="fa fa-ellipsis-h" aria-hidden="true"></i><span>
									</div>
								</div>
							<?php
							}
						}*/

						// Список сотрудников
						$aCompany_Department_Employees = $oCompany_Department->getEmployeesWithoutHeads();

						count($aCompany_Department_Employees) && $aUsers = array_merge($aUsers, $aCompany_Department_Employees);

						if (count($aUsers))
						{
							?>
							<!-- collapsed -->
							<div class="department-users widget flat collapsed bordered-platinum no-margin-bottom margin-top-10">
								<div class="widget-header">
									<span class="widget-caption"><?php echo Core::_('Company_Department.caption_block_users') ?></span>
									<div class="widget-buttons">
										<span class="count-users badge badge-sky"><?php echo count($aUsers); ?></span>
									</div>
									<div class="widget-buttons pull-left widget-button-chevron">
										<a href="#" data-toggle="collapse">
											<i class="fa fa-chevron-down sky"></i>
										</a>
									</div>
								</div>
								<div class="widget-body" style="display: none;">
									<div class="scroll-wrapper">
										<div class="table-scrollable border-transparent">
											<table class="table table-hover company-structure">
												<tbody>
												<?php
												foreach ($aUsers as $oUser)
												{
													$aUser_Company_Posts = $oUser->getCompanyPostsByDepartment($oCompany_Department->id);

													foreach ($aUser_Company_Posts as $oUser_Company_Post)
													{
													?>
													<tr class="user" data-user-id="<?php echo $oUser->id?>" data-company-post-id="<?php echo $oUser_Company_Post->id?>">
														<td>
														<?php
														echo "<img width=\"30\" height=\"30\" src=\"" . $oUser->getAvatar() . "\" class=\"img-circle user-avatar margin-right-10\" />";

														$bHead = $oUser->isHeadOfDepartment($oCompany_Department);

														$aName = array(
															$oUser->surname,
															$oUser->name,
															$oUser->patronymic
														);

														$aName = array_filter($aName, 'strlen');
														$sFullName = implode(' ', $aName);

														$name = htmlspecialchars(strlen($sFullName) ? $sFullName : $oUser->login);
														?>
														<span class="user-name"><a href="/admin/user/index.php?hostcms[action]=view&amp;hostcms[checked][0][<?php echo $oUser->id?>]=1" onclick="$.modalLoad({path: '/admin/user/index.php', action: 'view', operation: 'modal', additionalParams: 'hostcms[checked][0][<?php echo $oUser->id?>]=1', windowId: 'deal-notes'}); return false" title="<?php echo $name?>"><?php echo $name?></a></span>

														<div class="<?php echo $oUser->isOnline() ? 'online' : 'offline'; ?>"></div>

														<?php
														if ($bHead)
														{
															?>
															<i class="fa fa-star gold"></i>
															<?php
														}
														?>
														</td>
														<td>
															<span class="user-department-post"><?php echo htmlspecialchars($oUser_Company_Post->name);?></span>
														</td>
														<td>
															<div class="control-buttons">
																<a href="javascript:void(0);" data-action="edit_user" title="<?php echo Core::_('Company_Department.editUserDepartmentPostTitleAction'); ?>" alt="<?php echo Core::_('Company_Department.editUserDepartmentPostTitleAction'); ?>" class="bordered-darkgray">
																	<i class="fa fa-pencil darkgray"></i>
																</a>

																<a href="javascript:void(0);" data-action="delete_user" title="<?php echo Core::_('Company_Department.deleteUserDepartmentPostTitleAction'); ?>" alt="<?php echo Core::_('Company_Department.deleteUserDepartmentPostTitleAction'); ?>" class="bordered-darkorange">
																	<i class="fa fa-user-times darkorange"></i>
																</a>
															</div>
														</td>
													</tr>
													<?php
													}
												}
												?>
												</tbody>
											</table>
										</div>

									<?php
									if (1 == 0)
									{
									?>
									<div class="tickets-container no-padding">
										<ul class="tickets-list">
										<?php
										foreach ($aUsers as $oUser)
										{
											$aUser_Company_Posts = $oUser->getCompanyPostsByDepartment($oCompany_Department->id);

											foreach ($aUser_Company_Posts as $oUser_Company_Post)
											{
											?>
											<li class="ticket-item user" data-user-id="<?php echo $oUser->id?>" data-company-post-id="<?php echo $oUser_Company_Post->id?>">
												<div class="row">
													<div class="ticket-user col-lg-5 col-md-5 col-sm-5">
														<?php
														/*echo !empty($oUser->image)
															? "<img src=\"" . $oUser->getImageFileHref() . "\" class=\"user-avatar\" />"
															: '<span class="btn btn-sm btn-yellow icon-only white"><i class="fa fa-user"></i></span>';*/

														echo "<img src=\"" . $oUser->getAvatar() . "\" class=\"user-avatar\" />";

														$bHead = $oUser->isHeadOfDepartment($oCompany_Department);

														$aName = array(
															$oUser->surname,
															$oUser->name,
															$oUser->patronymic
														);

														$aName = array_filter($aName, 'strlen');
														$sFullName = implode(' ', $aName);
														?>
														<span class="user-name"><?php echo htmlspecialchars(strlen($sFullName) ? $sFullName : $oUser->login)?></span>
														<?php
														if (in_array($oUser->id, $aHeadIds))
														{
															?><i class="fa fa-star head-star" title="<?php echo Core::_('User.head_title');?>"></i><?php
														}
														?>

														<?php
														if ($bHead)
														{
															?>
															<i class="fa fa-star gold"></i>
															<?php
														}
														?>

														<div class="<?php echo $oUser->isOnline() ? 'online' : 'offline'; ?>"></div>
													</div>
													<div class="ticket-type col-lg-5 col-md-5 col-sm-5">
														<!--<div class="divider hidden-md hidden-sm hidden-xs"></div>-->
														<!-- <div class="divider"></div> -->
														<span class="user-department-post"><?php echo htmlspecialchars($oUser_Company_Post->name);?></span>
													</div>
													<!-- <div class="right-block">-->
														<!--<div class="divider hidden-xs"></div>-->
														<!-- <div class="divider"></div>
													</div> -->
													<div class="control-buttons-wrapper">
														<div class="control-buttons">
															<a href="javascript:void(0);" data-action="edit_user" title="<?php echo Core::_('Company_Department.editUserDepartmentPostTitleAction'); ?>" alt="<?php echo Core::_('Company_Department.editUserDepartmentPostTitleAction'); ?>" class="bordered-darkgray">
																<i class="fa fa-pencil darkgray"></i>
															</a>
															<a href="javascript:void(0);" data-action="delete_user" title="<?php echo Core::_('Company_Department.deleteUserDepartmentPostTitleAction'); ?>" alt="<?php echo Core::_('Company_Department.deleteUserDepartmentPostTitleAction'); ?>" class="bordered-darkorange">
																<i class="fa fa-user-times darkorange"></i>
															</a>
														</div>
														<span class="ellipsis"><i class="fa fa-ellipsis-h" aria-hidden="true"></i><span>
													</div>
												</div>
											</li>
										<?php
											}
										}
										?>
										</ul>
									</div>
									<?php
									}
									?>
									</div>
								</div>
							</div>
							<?php
						}
						?>
					</div>
				<?php

				echo $this->_showLevel($oCompany_Department->id);
				// конец вывода </div>
				?>
				</li>
				<?php
			}
			?>
			</ol>
			<?php
			return ob_get_clean();
		}
	}}
