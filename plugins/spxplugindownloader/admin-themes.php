<?php if(!defined('PLX_ROOT')) exit; ?>

<?php

# dossier local pour le cache
$cache_dir = PLX_PLUGINS.'cache/';


# info url liste des dépots
$repositorystores_url = 'http://www.secretsitebox.fr/spx/repository/'; # avec un slash à la fin
$repositorystores_xmlfile = 'repository-themesstores.xml';
$repositorystores_version = 'repository-themesstores.version';

# Control du token du formulaire
plxToken::validateFormToken($_POST);

# vérification de la présence du dossier cache
if(!is_dir(PLX_PLUGINS.'/cache')) {
	mkdir(PLX_PLUGINS.'/cache',0755,true);
}
?>

<div id="contentplugindownloader"  >
		
		
		<div id="brdmenu" class="inbox">
			<ul>
				<li id="plugins" ><a href="plugin.php?p=spxplugindownloader&amp;page=plugins">Plugins</a></li>
				<li id="themes" class="isactive"><a href="plugin.php?p=spxplugindownloader&amp;page=themes">Themes</a></li>
				
			</ul>
		</div>
		

<?php
# 1) recupération du n° de version du repository-stores distant
if(!$remote_version_stores = spxplugindownloader::getRemoteFileContent($repositorystores_url.$repositorystores_version)) {
	
	
	echo "<div class='separate2'></div><p>".$plxPlugin->getLang('L_ERR_REPOSITORYSTORES')."</p>";
} else { # traitement dépots stores */
	$remote_version_stores = intval (trim ($remote_version_stores));
	
	# 2) recupération du n° de version du repository-stores mis en cache
	$version_stores = '';
	if(is_file($cache_dir.$repositorystores_version))
		$version_stores = file_get_contents($cache_dir.$repositorystores_version);
	plxUtils::write($remote_version_stores, $cache_dir.$repositorystores_version);
	
	# 3) on récupère le fichier distant repository.xlm s'il n'existe pas en cache ou si nouveau n° de version
	if($version_stores=='' OR $version_stores!=$remote_version_stores or !is_file($cache_dir.$repositorystores_xmlfile)) {
		//echo ("chargement du magasin des depots \n<br>");
		spxplugindownloader::downloadRemoteFile($repositorystores_url.$repositorystores_xmlfile, $cache_dir.$repositorystores_xmlfile);
	}
	
	# 4) lecture des dépots
	# lecture du fichier xml contenant les infos sur les magasins de repository
	if (!$astores = spxplugindownloader::getStoresRepository($cache_dir.$repositorystores_xmlfile)){
		echo ("<div class='separate2'></div><p>".$plxPlugin->getLang('L_ERR_REPOSITORYSTORES')."</p>");
		exit;
	}
	
	# 5) lecture des themes des dépots
	foreach($astores as $storeName => $store) {
		$repositoryurl = $store["repositoryurl"];
		$repository_version = $store["repositoryversionurl"];
	
		# 5.1) recupération du n° de version du repository distant
		
		$remote_version = spxplugindownloader::getRemoteFileContent($repository_version);
		$remote_version = intval (trim ($remote_version));
		
		$astores[$storeName]["remote_version"] = $remote_version;
		
		//echo ("remote version = ".$astores[$storeName]["remote_version"]."\n<br>");	
		# 5.2) recupération du n° de version du repository mis en cache
		$version = '';
		if(is_file($cache_dir."repository.".$storeName.".version")){
			$version = file_get_contents($cache_dir."repository.".$storeName.".version");
			
		}
		plxUtils::write($remote_version, $cache_dir."repository.".$storeName.".version");
	
		//echo ("version = ".$version."\n<br>");	
		$astores[$storeName]["version"] = $version;
		//echo ("version-".$version."-\n<br>");
		//echo ("remote_version-".$remote_version."-\n<br>");
		# 5.3) on récupère le fichier distant repository.xml s'il n'existe pas en cache ou si nouveau n° de version
		
		if($version=='' OR $version!=$remote_version or !is_file($cache_dir."repository.".$storeName.".version")) {
				//echo ("loading remote".$storeName."\n<br>");
				spxplugindownloader::downloadRemoteFile($repositoryurl, $cache_dir."repository.".$storeName.".xml");
		}
		
		# 5.4) lecture des themes du depot
		$repo = spxplugindownloader::getRepository($cache_dir."repository.".$storeName.".xml");
		
		// $storeName;
		foreach($repo as $pluginsName => $plugin) {
			$repo[$pluginsName]["repository"]=$storeName;;
		}
		$astores[$storeName]["themes"] = $repo;
		
	}
	
	# 6) on récupère la liste des themes dans le dossier theme
	$aThemes = array();
	$dirs = spxplugindownloader::getlistthemes();
	if(sizeof($dirs->aFiles)>0) {
		foreach($dirs->aFiles as $themeName) {
			if(!isset($aThemes[$themeName])) {
				$infos = spxplugindownloader::theme_getInfos($themeName, 'all');
				$aThemes[$themeName] = $infos;
			}
		}
	}
	
	
	/*
	
	if(sizeof($dirs->aFiles)>0) {
		foreach($dirs->aFiles as $themeName) {
			if(!isset($aThemes[$plugName]) AND $plugInstance=$plxAdmin->plxPlugins->getInstance($plugName)) {
				$plugInstance->getInfos();
				$aPlugins[$plugName] = $plugInstance;
			}
		}
	}
	*/

 ?>
 
	
		
		
		
		<div id="incmenutop">
			<ul>
			</ul>
		</div>

		<div class="clear"></div>
		
	
		
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	<?php } # fin traitement dépots stores ?>	
		
	</div>	

<?php 

echo ("<pre>");
		print_r($astores);
		echo ("</pre>");
		
		

	

echo ("<pre>");
		print_r($aThemes);
		echo ("</pre>");
		

	
echo ("<pre>");
		print_r($repo);
		echo ("</pre>");
		

echo ("<pre>");
		print_r($aThemes2);
		echo ("</pre>");

 ?>	
		
		
		