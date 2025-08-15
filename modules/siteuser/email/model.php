<?php

defined('HOSTCMS') || exit('HostCMS: access denied.');

/**
 * Siteuser_Email_Model
 *
 * @package HostCMS
 * @subpackage Siteuser
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
class Siteuser_Email_Model extends Core_Entity
{
	/**
	 * Column consist item's name
	 * @var string
	 */
	protected $_nameColumn = 'subject';

	/**
	 * One-to-many or many-to-many relations
	 * @var array
	 */
	protected $_hasMany = array(
		'siteuser_email_attachment' => array()
	);

	/**
	 * Belongs to relations
	 * @var array
	 */
	protected $_belongsTo = array(
		'siteuser' => array(),
		'user' => array(),
	);

	/**
	 * Default sorting for models
	 * @var array
	 */
	protected $_sorting = array(
		'siteuser_emails.id' => 'DESC'
	);

	/**
	 * Constructor.
	 * @param int $id entity ID
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		if (is_null($id) && !$this->loaded())
		{
			$oUser = Core_Auth::getCurrentUser();
			$this->_preloadValues['user_id'] = is_null($oUser) ? 0 : $oUser->id;
			$this->_preloadValues['guid'] = Core_Guid::get();
			$this->_preloadValues['datetime'] = Core_Date::timestamp2sql(time());
		}
	}

	/**
	 * Delete object from database
	 * @param mixed $primaryKey primary key for deleting object
	 * @return Core_Entity
	 * @hostcms-event siteuser_email.onBeforeRedeclaredDelete
	 */
	public function delete($primaryKey = NULL)
	{
		if (is_null($primaryKey))
		{
			$primaryKey = $this->getPrimaryKey();
		}

		$this->id = $primaryKey;

		Core_Event::notify($this->_modelName . '.onBeforeRedeclaredDelete', $this, array($primaryKey));

		$this->Siteuser_Email_Attachments->deleteAll(FALSE);

		// Удаляем директорию письма
		$this->deleteDir();

		return parent::delete($primaryKey);
	}

	/**
	 * Get files href
	 */
	public function getHref()
	{
		 return $this->Siteuser->Site->uploaddir . 'private/email/' . Core_File::getNestingDirPath($this->id, 3) . '/' . $this->id . '/';
	}

	/**
	 * Get path for files
	 * @return string
	 */
	public function getPath()
	{
		return CMS_FOLDER . $this->getHref();
	}

	/**
	 * Create message files directory
	 * @return self
	 */
	public function createDir()
	{
		if (!Core_File::isDir($this->getPath()))
		{
			try
			{
				Core_File::mkdir($this->getPath(), CHMOD, TRUE);
			} catch (Exception $e) {}
		}

		return $this;
	}

	/**
	 * Delete files directory
	 * @return self
	 */
	public function deleteDir()
	{
		if (Core_File::isDir($this->getPath()))
		{
			try
			{
				Core_File::deleteDir($this->getPath());
			} catch (Exception $e) {}
		}

		return $this;
	}

	/**
	 * Delete attachment file
	 * @param $siteuser_email_attachment_id attachment id
	 * @return self
	 */
	public function deleteFile($siteuser_email_attachment_id)
	{
		$oSiteuser_Email_Attachment = $this->Siteuser_Email_Attachments->getById($siteuser_email_attachment_id);
		if ($oSiteuser_Email_Attachment)
		{
			$oSiteuser_Email_Attachment->delete();
		}

		return $this;
	}

	/**
	 * Backend callback method
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function dataUserNameBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		ob_start();

		if ($this->User->id)
		{
			?><div class="contracrot">
				<div class="user-image">
					<img class="contracrot-ico" src="<?php echo $this->User->getAvatar()?>" title="<?php echo htmlspecialchars($this->User->getFullName())?>"/>
				</div>
			</div><?php
		}

		return ob_get_clean();
	}

	/**
	 * Send mail
	 * @return self
	 */
	public function send()
	{
		if ($this->siteuser_id)
		{
			$oSiteuser = $this->Siteuser;
			$oSite = $oSiteuser->Site;

			$subject = strlen(trim($this->subject))
				? htmlspecialchars($this->subject)
				: Core::_('Admin_Form.non_subject');

			$sTracking = '';

			// tracking for html
			if ($this->type == 1)
			{
				$oSiteAlias = $oSite->getCurrentAlias();

				if ($oSiteAlias)
				{
					$oMainPage = $oSite->Structures->getByPath('/');

					if (!is_null($oMainPage))
					{
						$sUrl = ($oMainPage->https ? 'https' : 'http') . '://' . $oSiteAlias->name;

						$sTracking = '<img src="' . $sUrl . '/siteuser-email.php?guid=' . $this->guid . '" alt="" width="1px" height="1px" />';
					}
				}
			}

			try {
				$oCore_Mail = Core_Mail::instance()
					->clear()
					->to($this->email)
					->from($this->from)
					->subject($subject)
					->message($this->text . $sTracking)
					->contentType($this->type == 1 ? 'text/html' : 'text/plain')
					->header('Reply-To', $this->from)
					->header('Precedence', 'bulk');

				!is_null($this->cc)
					&& $oCore_Mail->header('Cc', $this->cc);

				!is_null($this->bcc)
					&& $oCore_Mail->header('Bcc', $this->bcc);

				$oSite->sender_name != ''
					&& $oCore_Mail->senderName($oSite->sender_name);

				$aSiteuser_Email_Attachments = $this->Siteuser_Email_Attachments->findAll(FALSE);
				foreach ($aSiteuser_Email_Attachments as $oSiteuser_Email_Attachment)
				{
					$oCore_Mail->attach(array(
						'filepath' => $oSiteuser_Email_Attachment->getFilePath(),
						'filename' => $oSiteuser_Email_Attachment->name
					));
				}

				$oCore_Mail->send();
			}
			catch (Exception $e) {
				Core_Message::show($e->getMessage(), 'error');
			}
		}

		return $this;
	}

	/**
	 * Backend callback method
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function subjectBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$countAttachments = $this->Siteuser_Email_Attachments->getCount();

		$windowId = $oAdmin_Form_Controller->getWindowId();

		if ($countAttachments)
		{
			?><i class="fa fa-paperclip margin-right-5"></i><?php
		}

		$subject = $this->subject != '' && strlen(trim($this->subject))
			? htmlspecialchars($this->subject)
			: Core::_('Admin_Form.non_subject');

		$oUser = Core_Auth::getCurrentUser();
		$oAdmin_Form = $oAdmin_Form_Controller->getAdminForm();

		$aAllowedActionsNames = array();

		$aAllowed_Admin_Form_Actions = $oAdmin_Form->Admin_Form_Actions->getAllowedActionsForUser($oUser);
		foreach ($aAllowed_Admin_Form_Actions as $oAdmin_Form_Action)
		{
			$aAllowedActionsNames[] = $oAdmin_Form_Action->name;
		}

		if (in_array('viewForm', $aAllowedActionsNames))
		{
			?>
			<script>
			$(function() {
				$('#<?php echo $windowId?> a#siteuser_email<?php echo $this->id?>').on('click', function (){
					$.ajax({
						url: hostcmsBackend + '/siteuser/email/index.php',
						type: "POST",
						data: { 'load_modal': 1, 'siteuser_email_id': <?php echo $this->id?> },
						dataType: 'json',
						error: function(){},
						success: function (result) {
							var dialog = bootbox.dialog({
								title: '<?php echo Core_Str::escapeJavascriptVariable($subject)?>',
								message: result.html,
								backdrop: true,
								size: 'large'
							});

							dialog.modal('show');

							// Resize iframe in modal
							dialog.on('shown.bs.modal', function (){
								var iframeHeight = $("#frame<?php echo $this->id?>").contents().height();
								$("#frame<?php echo $this->id?>").height(iframeHeight);
							});

							// Close modal
							$("#frame<?php echo $this->id?>").ready(function () {
								setTimeout(function () {
									dialog.on('keyup', function (e) {
										if (e.keyCode == 27) {
											dialog.modal("hide");
										}
									});
								}, 50);
							});
						}
					});
				});
			});
			</script>
			<?php
			return '<a id="siteuser_email' . $this->id . '" href="javascript:void(0);">' . $subject . '</a>';
		}

		return $subject;
	}

	/**
	 * Backend callback method
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function readBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		return !is_null($this->read) && $this->read != '0000-00-00 00:00:00'
			? '<i class="fa fa-check-circle palegreen" title="' . Core_Date::sql2datetime($this->read) . '"></i>'
			: '<i class="fa fa-times-circle darkorange"></i>';
	}

	/**
	 * Backend callback method
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function importantBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		return $this->important
			? '<i class="fa-solid fa-fire fire" title="' . Core::_('Siteuser_Email.important') . '"></i>'
			: '';
	}

	/**
	 * Backend callback method
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function fromBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		if (strlen(trim($this->from)))
		{
			?><span class="badge badge-square badge-max-width siteuser-email-from"><a href="mailto:<?php echo htmlspecialchars($this->from)?>"><?php echo htmlspecialchars($this->from)?></a></span><?php
		}
	}

	/**
	 * Backend callback method
	 * @param Admin_Form_Field $oAdmin_Form_Field
	 * @param Admin_Form_Controller $oAdmin_Form_Controller
	 * @return string
	 */
	public function emailBackend($oAdmin_Form_Field, $oAdmin_Form_Controller)
	{
		$aEmails = explode(',', $this->email);
		foreach ($aEmails as $sEmail)
		{
			$sEmail = trim($sEmail);

			if (strlen($sEmail))
			{
				?><span class="badge badge-square badge-max-width siteuser-email-to margin-right-5"><a href="mailto:<?php echo htmlspecialchars($sEmail)?>"><?php echo htmlspecialchars($sEmail)?></a></span><?php
			}
		}
	}

	/**
	 * Get Related Site
	 * @return Site_Model|NULL
	 * @hostcms-event siteuser_email.onBeforeGetRelatedSite
	 * @hostcms-event siteuser_email.onAfterGetRelatedSite
	 */
	public function getRelatedSite()
	{
		Core_Event::notify($this->_modelName . '.onBeforeGetRelatedSite', $this);

		$oSite = $this->Siteuser->Site;

		Core_Event::notify($this->_modelName . '.onAfterGetRelatedSite', $this, array($oSite));

		return $oSite;
	}
}