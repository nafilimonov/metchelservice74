<?php
/**
 * Forms.
 *
 * @package HostCMS
 * @version 7.x
 * @copyright © 2005-2024, https://www.hostcms.ru
 */
require_once('../../../../bootstrap.php');

Core_Auth::authorization('form');

$form_fill_id = intval(Core_Array::getGet('form_fill_id', 0));
$oForm_Fill = Core_Entity::factory('Form_Fill', $form_fill_id);
$oForm = $oForm_Fill->Form;

?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title><?php echo htmlspecialchars($oForm->name . ', № ' . $form_fill_id)?></title>
		<meta http-equiv="Content-Language" content="ru">
		<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
		<style type="text/css">html, body, td {
			font-family: Arial, Verdana, Tahoma, sans-serif;
			font-size: 9pt;
			background-color: #FFFFFF;
			color: #000000;
			vertical-align: top
		}
		</style>
	</head>

	<body style="margin: 3.5em">
		<?php
		if (defined('FORM_PRINT_CARD_XSL'))
		{
			$oXsl = Core_Entity::factory('Xsl')->getByName(FORM_PRINT_CARD_XSL);

			if (!is_null($oXsl))
			{
				$oForm_Fill_Fields = $oForm_Fill->Form_Fill_Fields;
				$oForm_Fill_Fields
					->queryBuilder()
					->select('form_fill_fields.*')
					->join('form_fields', 'form_fill_fields.form_field_id', '=', 'form_fields.id')
					->clearOrderBy()
					->orderBy('form_fields.sorting', 'ASC');

				$aForm_Fill_Fields = $oForm_Fill_Fields->findAll();

				$aForm_Field_Dirs = $oForm->Form_Field_Dirs->findAll();

				foreach ($aForm_Fill_Fields as $oForm_Fill_Field)
				{
					$oForm_Fill_Field->addEntity(
						Core::factory('Core_Xml_Entity')
							->name('form_field_dir_id')
							->value(intval($oForm_Fill_Field->Form_Field->Form_Field_Dir->id))
					);

					$oForm
						->addEntity($oForm_Fill_Field)
						->addEntity($oForm_Fill_Field->Form_Field);
				}

				$oForm
					->addEntity($oForm_Fill->clearEntities())
					->addEntities($aForm_Field_Dirs);

				$sXml = $oForm->getXml();

				Core::setLng(Core_I18n::instance()->getLng());

				$return = Xsl_Processor::instance()
						->xml($sXml)
						->xsl($oXsl)
						->process();

				echo $return;
			}
			else
			{
				throw new Core_Exception('XSL template %name does not exist.', array(
					'%name' => FORM_PRINT_CARD_XSL
				));
			}
		}
		else
		{
		?>
			<h1><?php  echo htmlspecialchars($oForm->name) . ', № ' . $form_fill_id?></h1>

			<?php
			setFormFieldDirs($oForm_Fill, 0);
			?>

			<table cellspacing="2" cellpadding="5" border="0">
				<tr>
					<td><?php echo Core::_('Form_Fill.print_datetime')?></td>
					<td><strong><?php echo Core_Date::sql2datetime($oForm_Fill->datetime)?></strong></td>
				</tr>
				<tr>
					<td><?php echo Core::_('Form_Fill.print_ip')?></td>
					<td><strong><?php echo htmlspecialchars($oForm_Fill->ip)?></strong></td>
				</tr>

				<?php
				if ($oForm_Fill->source_id)
				{
					$oSource = $oForm_Fill->Source;

					$aSourceFields = array('service', 'campaign', 'ad', 'source', 'medium', 'content', 'term');

					foreach ($aSourceFields as $sFieldName)
					{
						if ($oSource->$sFieldName != '')
						{
							?><tr>
								<td><?php echo Core::_('Source.' . $sFieldName)?>:</td>
								<td><strong><?php echo htmlspecialchars($oSource->$sFieldName)?></strong></td>
							</tr><?php
						}
					}
				}
				?>
			</table>
		<?php
		}
		?>
	</body>
</html>

<?php
function setFormFieldDirs($oForm_Fill, $parent_id)
{
	$oForm_Fill_Fields = $oForm_Fill->Form_Fill_Fields;
	$oForm_Fill_Fields
		->queryBuilder()
		->select('form_fill_fields.*')
		->join('form_fields', 'form_fill_fields.form_field_id', '=', 'form_fields.id')
		->where('form_fields.form_field_dir_id', '=', $parent_id)
		->clearOrderBy()
		->orderBy('form_fields.sorting', 'ASC');

	$aForm_Fill_Fields = $oForm_Fill_Fields->findAll(FALSE);

	$name = $parent_id == 0
		? Core::_('Form_Field_Dir.main_section')
		: Core_Entity::factory('Form_Field_Dir', $parent_id)->name;

	//$iCount = getCount($oForm_Fill, $oForm_Field_Dir->id);

	if (count($aForm_Fill_Fields))
	{
	?>
	<h2><?php echo htmlspecialchars($name)?></h2>
	<table cellspacing="2" cellpadding="5" border="0">
		<?php
		foreach ($aForm_Fill_Fields as $oForm_Fill_Field)
		{
			?><tr>
				<td><?php echo htmlspecialchars($oForm_Fill_Field->Form_Field->caption)?>:</td>
				<td><strong><?php echo nl2br(htmlspecialchars($oForm_Fill_Field->value))?></strong></td>
			</tr><?php
		}
		?>
	</table>
	<?php
	}

	// Form Field Dirs
	$aForm_Field_Dirs = $oForm_Fill->Form->Form_Field_Dirs->getAllByParent_id($parent_id);

	foreach ($aForm_Field_Dirs as $oForm_Field_Dir)
	{
		$aObject_Form_Field_Dirs[] = $oForm_Field_Dir;

		setFormFieldDirs($oForm_Fill, $oForm_Field_Dir->id);
	}
}