<?php if(!defined('PLX_ROOT')) exit; ?>
<?php

# Control du token du formulaire
plxToken::validateFormToken($_POST);

if(!empty($_POST)) {

	# sauvegarde des paramètres
	$plxPlugin->setParam('repo_url', $_POST['repo_url'], 'cdata');
	$plxPlugin->saveParams();

	# redirection sur la page de configuration du plugin
	header('Location: parametres_plugin.php?p=plxMyPluginDownloader');
	exit;
}

?>

<h2><?php echo $plxPlugin->getInfo('title') ?></h2>
<h3><?php echo $plxPlugin->getInfo('description') ?></h3>

<form action="parametres_plugin.php?p=plxMyPluginDownloader" method="post" id="form_plugindownloader">
	<fieldset>
		<p class="field"><label for="repo_url">Url du dépot</label></p>
		<?php plxUtils::printInput('repo_url', $plxPlugin->getParam('repo_url'), 'text', '80-255'); ?>
		<p>
			<?php echo plxToken::getTokenPostMethod() ?>
			<input type="submit" name="save" value="<?php $plxPlugin->lang('L_SAVE') ?>" />
		</p>
	</fieldset>
</form>
