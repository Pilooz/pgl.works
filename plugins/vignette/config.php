<?php
/**
 * Plugin vignette
 *
 * @package     PLX
 * @version     1.0
 * @date        29/11/2015
 * @author      rockyhorror
 **/


	if(!defined('PLX_ROOT')) exit;
	# Control du token du formulaire
	plxToken::validateFormToken($_POST);

	if(!empty($_POST)) {
		$plxPlugin->setParam('disable_auto', isset($_POST['disable_auto'])?1:0, 'numeric');
		$plxPlugin->saveParams();
		header('Location: parametres_plugin.php?p=vignette');
		exit;
	}

?>
	<h2><?php $plxPlugin->lang('L_CONFIG_DESCRIPTION') ?></h2>
	
	<form action="parametres_plugin.php?p=vignette" method="post">
		<fieldset class="withlabel">
		
		<p><?php echo $plxPlugin->getLang('L_CONFIG_DISABLE_AUTO') ?>
		<input type="checkbox" name="disable_auto" value="True" <?php if($plxPlugin->getParam('disable_auto')) { echo 'checked="true"'; }?>/></p>
		
		</fieldset>
		
		<p class="in-action-bar">	
			<?php echo plxToken::getTokenPostMethod() ?>
			<input type="submit" name="submit" value="<?php echo $plxPlugin->getLang('L_SAVE') ?>" />
		</p>
	
	</form>
