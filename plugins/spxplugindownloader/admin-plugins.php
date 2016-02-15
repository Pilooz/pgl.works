<?php if(!defined('PLX_ROOT')) exit; ?>

<?php

# dossier local pour le cache
$cache_dir = PLX_PLUGINS.'cache/';


# info url liste des dépots
$repositorystores_url = 'http://www.secretsitebox.fr/spx/repository/'; # avec un slash à la fin
$repositorystores_xmlfile = 'repository-stores.xml';
$repositorystores_version = 'repository-stores.version';

# infos sur le repository
//$repository_url = 'https://raw.githubusercontent.com/je-evrard/repository/master/'; # avec un slash à la fin
$repository_url = 'http://www.secretsitebox.fr/spx/repository/'; # avec un slash à la fin
$repository_xmlfile = 'repository.xml';
$repository_version = 'repository.version';

# Control du token du formulaire
plxToken::validateFormToken($_POST);

# vérification de la présence du dossier cache
if(!is_dir(PLX_PLUGINS.'/cache')) {
	mkdir(PLX_PLUGINS.'/cache',0755,true);
}



# 1) recupération du n° de version du repository-stores distant
if(!$remote_version_stores = spxplugindownloader::getRemoteFileContent($repositorystores_url.$repositorystores_version))
	echo $plxPlugin->getLang('L_ERR_REPOSITORYSTORES');
else { # traitement dépots stores */
	$remote_version_stores = intval (trim ($remote_version_stores));
	//echo ("remote store = ".$remote_version_stores."\n<br>");

	# 2) recupération du n° de version du repository-stores mis en cache
	$version_stores = '';
	if(is_file($cache_dir.$repositorystores_version))
		$version_stores = file_get_contents($cache_dir.$repositorystores_version);
	plxUtils::write($remote_version_stores, $cache_dir.$repositorystores_version);
	
	//echo ("version_stores = ".$version_stores."\n<br>");
	
	# 3) on récupère le fichier distant repository.xlm s'il n'existe pas en cache ou si nouveau n° de version
	if($version_stores=='' OR $version_stores!=$remote_version_stores or !is_file($cache_dir.$repositorystores_xmlfile)) {
		// echo ("chargement du magasin des depots \n<br>");
		spxplugindownloader::downloadRemoteFile($repositorystores_url.$repositorystores_xmlfile, $cache_dir.$repositorystores_xmlfile);
	}
	
	# 4) lecture des dépots
	# lecture du fichier xml contenant les infos sur les magasins de repository
	if (!$astores = spxplugindownloader::getStoresRepository($cache_dir.$repositorystores_xmlfile)){
		echo ("<tr><td>".$plxPlugin->getLang('L_ERR_REPOSITORYSTORES')."</td></tr>");
		exit;
	}
	
	//echo ("lecture du depot ".$cache_dir.$repositorystores_xmlfile." \n<br>");
	
	# 5) lecture des plugins des dépots
	foreach($astores as $storeName => $store) {
		
		$repositoryurl = $store["repositoryurl"];
		$repository_version = $store["repositoryversionurl"];
	
		# 5.1) recupération du n° de version du repository distant
		
		//$remote_version = spxplugindownloader::getRemoteFileContent($repository_version);
		$remote_version = spxplugindownloader::downloadRemoteFile2($repository_version);
		//echo ("remote_version2:: ".$remote_version." url".$repository_version."\n<br>");
		
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
				// spxplugindownloader::downloadRemoteFile($repositoryurl, $cache_dir."repository.".$storeName.".xml");
				// echo ("chargement des xml du depot ".$storeName);
				$checkcontent = spxplugindownloader::downloadRemoteFile2($repositoryurl);
				if(!$checkcontent) {
					plxMsg::Error($plxPlugin->getLang('L_ERR_DOWNLOAD'));
					header('Location: plugin.php?p=spxplugindownloader&groupe='.$groupe);
					exit;
				}else{
					file_put_contents($cache_dir."repository.".$storeName.".xml",$checkcontent);
				}
				
				
		}
		
		# 5.4) lecture des plugins du depot
		$repo = spxplugindownloader::getRepository($cache_dir."repository.".$storeName.".xml");
		
		
		if (intval($version)>0){
			// $storeName;
			foreach($repo as $pluginsName => $plugin) {
				$repo[$pluginsName]["repository"]=$storeName;;
			}
			# add to store
			$astores[$storeName]["plugins"] = $repo;
		}else{
			$astores[$storeName]["plugins"]= false;
		
		}
		
	
	}
	
	# 6) on récupère la liste des plugins dans le dossier plugins
	$aPlugins = array();
	$dirs = plxGlob::getInstance(PLX_PLUGINS, true);
	if(sizeof($dirs->aFiles)>0) {
		foreach($dirs->aFiles as $plugName) {
			if(!isset($aPlugins[$plugName]) AND $plugInstance=$plxAdmin->plxPlugins->getInstance($plugName)) {
				$plugInstance->getInfos();
				$aPlugins[$plugName] = $plugInstance;
			}
		}
	}
	
	# 7) on essaye d'associer les plugins du site a un depot existant via un nouveau tableau plugin 2
	$aPlugins2 =array();
	
	foreach($aPlugins as $pluginName => $plugin) {
		# info du plugin installé
		$aPlugins2[$pluginName]["name"]=$pluginName;
		$aPlugins2[$pluginName]["version"] = $plugin->getInfo('version');
		$aPlugins2[$pluginName]["title"] = $plugin->getInfo('title');
		$aPlugins2[$pluginName]["author"] = $plugin->getInfo('author');
		$aPlugins2[$pluginName]["date"] = $plugin->getInfo('date');
		$aPlugins2[$pluginName]["site"] = $plugin->getInfo('site');
		$aPlugins2[$pluginName]["description"] = $plugin->getInfo('description');
		$aPlugins2[$pluginName]["repository"] = false;
		//$aPlugins2[$pluginName]["title"]=$plugin["title"];
		foreach($astores as $storeName => $store) {
			if ($store["plugins"][$pluginName]["name"]==$pluginName){
				# info sup du store en surcharge
				$aPlugins2[$pluginName]["repository"]=$storeName;
				$aPlugins2[$pluginName]["repository_plugin"]=$store["plugins"][$pluginName];
				break;
			}
		}
	}
	
	
	$groupe = $_GET['groupe'];
	if ($groupe=='') $groupe='myplugin';
	//echo ("groupe=".$groupe);
	
	/*
	if(isset($aPlugins[$plugName]))
			$update = version_compare($aPlugins[$plugName]->getInfo('version'), $plug['version'], "<");

		$new = !is_dir(PLX_PLUGINS.$plugName);
	*/
	
	
if(!empty($_POST)) {
	
		
	foreach($_POST['button'] as $plugName => $dummy) {
		$depot = $_POST["buttonrepository"][$plugName];
		$file = $astores[$depot]["plugins"][$plugName]["file"];
		
		//echo ("plugin = ".$plugName." depot=".$depot);
		//echo ("file = ".$file."\n</br>");
	
	
		# on teste si le fichier distant est dispo
		if(!spxplugindownloader::is_RemoteFileExists($file)) {
			plxMsg::Error($plxPlugin->getLang('L_ERR_REMOTE_FILE'."remotefileexistpas"));
			header('Location: plugin.php?p=spxplugindownloader&groupe='.$groupe);
			exit;
		}
		
		# téléchargement du fichier distant with bronco method
		$zipfile = PLX_PLUGINS.$plugName.'.zip';
		$checkcontent = spxplugindownloader::downloadRemoteFile2($file);
		if(!$checkcontent) {
			plxMsg::Error($plxPlugin->getLang('L_ERR_DOWNLOAD'));
			header('Location: plugin.php?p=spxplugindownloader&groupe='.$groupe);
			exit;
		}else{
			file_put_contents($zipfile,$checkcontent);
		}
			
		/*
		if(!spxplugindownloader::downloadRemoteFile($file, $zipfile)) {
			plxMsg::Error($plxPlugin->getLang('L_ERR_DOWNLOAD'));
			header('Location: plugin.php?p=spxplugindownloader&groupe='.$groupe);
			exit;
		}
		*/
		

		# rename old plugin
		if (file_exists(realpath(PLX_PLUGINS.$plugName))) {
			rename(PLX_PLUGINS.$plugName , PLX_PLUGINS.$plugName.".tmp");
		}
		
		# dezippage de l'archive
		require_once(PLX_PLUGINS."spxplugindownloader/dUnzip2.inc.php");
		$zip = new dUnzip2($zipfile); // New Class : arg = fichier à dézipper
		$zip->unzipAll(PLX_PLUGINS, "", true, 0755); // Unzip All  : args = dossier de destination

		# on renomme le dossier extrait
		# does not work for xx.1.0.zip/xx/ structure
		rename(PLX_PLUGINS.$plugName.'-'.str_replace('.zip', '', basename($file)), PLX_PLUGINS.$plugName);

		# on supprimer le fichier .zip
		unlink($zipfile);

		# on teste si le dézippage semble ok par la présence du fichier infos.xml du plugin
		if(!is_file(PLX_PLUGINS.$plugName.'/infos.xml')){
			plxMsg::Error($plxPlugin->getLang('L_ERR_INSTALL'));
			rename(PLX_PLUGINS.$plugName.".tmp", PLX_PLUGINS.$plugName);
		}else{
			if (file_exists(realpath(PLX_PLUGINS.$plugName.".tmp"))) {
				echo ("file exist=".realpath(PLX_PLUGINS.$plugName.".tmp"));
				spxplugindownloader::deleteDir (realpath(PLX_PLUGINS.$plugName.".tmp"));
			}
			if ($depot=="spx"){
				$hitname = $_POST['buttonhit'][$plugName];
				file_get_contents('http://www.secretsitebox.fr/spx/hitcounter.php?file='.$hitname.'&url=true');
			}
			plxMsg::Info($plxPlugin->getLang('L_INSTALL_OK'));
		}
		
		# Redirection
		header('Location: plugin.php?p=spxplugindownloader&groupe='.$groupe);
		exit;
		
	}
}
	
	
?>

	<div id="contentplugindownloader"  >
		
		
		<div id="brdmenu" class="inbox">
			<ul>
				<li id="plugins" class="isactive"><a href="plugin.php?p=spxplugindownloader&amp;page=plugins">Plugins</a></li>
				<!--<li id="themes"><a href="plugin.php?p=spxplugindownloader&amp;page=themes">Themes</a></li>-->
				
			</ul>
		</div>
		
		
		<div class="separate2xxx"></div>
		<div id="incmenutop">
			<ul>
				
				<?php
				$selected=''; 
				if ($groupe=="myplugin"){
						$selected='class="selected"'; 
				}
				echo ('<li><a '.$selected.' href="plugin.php?p=spxplugindownloader&amp;groupe=myplugin" title="link" target="">Mes plugins</a></li>');
				?>
				
				
				
				<?php
				foreach($astores as $storeName => $store) {
					$selected=''; 
					if ($storeName == $groupe){
							$selected='class="selected"'; 
					}
					if ($store["plugins"]){
						$storeicon = '<img src="'.$store["icon"].'" width="20px" height="20px">'.$store["title"];
						echo '<li><a '.$selected.' href="plugin.php?p=spxplugindownloader&amp;groupe='.$storeName.'" title="'.$store["title"].'" target="">'.$storeicon.'</a></li>';
					}
				
				}
				?>
			  
			  
			</ul>
		</div>

		<div class="clear"></div>
		
		
		<form action="plugin.php?p=spxplugindownloader&groupe=<?php echo $groupe ?>" method="post" id="form_plugindownloader">
		<p><?php echo plxToken::getTokenPostMethod() ?></p>
			<table class="mypdler"  cellspacing="0">
		
		
		
		
				<?php
				# construction des plugins
				if ($groupe=='myplugin'){
					$repo=array();
					foreach($aPlugins2 as $plugName => $plug) {
						$update=false;
						
						
						
						echo '<tr>';
						echo '<td class="icon"><img src="'.PLX_PLUGINS.$plugName.'/icon.png" width="48px" height="auto" alt="" /></td>';
						echo '<td class="description'.$color.'">';
							echo '<strong>'.plxUtils::strCheck($plug['title']).'</strong>';
							echo ' - '.$plxPlugin->getLang('L_VERSION').' <strong>'.plxUtils::strCheck($plug['version']).'</strong>';
							if($plug['date']!='')
								echo ' ('.plxUtils::strCheck($plug['date']).')';
							echo '<br />';
							echo plxUtils::strCheck($plug['description']).'<br />';
							echo $plxPlugin->getLang('L_AUTHOR').' : '.plxUtils::strCheck($plug['author']);
							if($plug['site']!='')
								echo ' - <a href="'.plxUtils::strCheck($plug['site']).'">'.plxUtils::strCheck($plug['site']).'</a>';
						echo '</td>';
						if ($plug['repository']) {
							$update = version_compare($plug['version'], $plug["repository_plugin"]['version'], "<");
							if($update)
								echo '<td class="action"><input type="submit" class="medium red btsk update" name="button['.$plugName.']" value="'.$plxPlugin->getLang('L_UPDATE').'" /></td>';
							
							else
								echo '<td class="action"><input type="submit" class="medium green btsk" name="button['.$plugName.']" value="'.$plxPlugin->getLang('L_INSTALL').'" /></td>';

						}else{
							echo ('<td class="action">'.$plxPlugin->getLang('L_NOREPOSITORYEXIST').'</td>');
						}
						
						echo '<input type="hidden" name="buttonhit['.$plugName.']" value="'.$plugName.'.'.plxUtils::strCheck($plug['version']).'" />';
						echo '<input type="hidden" name="buttonrepository['.$plugName.']" value="'.plxUtils::strCheck($plug['repository']).'" />';
						echo '<input type="hidden" name="buttonrepositoryurl['.$plugName.']" value="'.plxUtils::strCheck($plug['repository']).'" />';
						echo "</tr>";
						
					}
					
					
					
					
				}else{
					//echo ("du store je prend ".$groupe);
					$repo= $astores[$groupe]["plugins"];
				
					foreach($repo as $plugName => $plug) {
						$update=false;
						if(isset($aPlugins[$plugName]))
							$update = version_compare($aPlugins[$plugName]->getInfo('version'), $plug['version'], "<");

						$new = !is_dir(PLX_PLUGINS.$plugName);

						$color='';
						if($update) $color = ' new-red';

						echo '<tr>';
						echo '<td class="icon"><img src="'.$plug['icon'].'" width="48px" height="auto" alt="" /></td>';
						echo '<td class="description'.$color.'">';
							echo '<strong>'.plxUtils::strCheck($plug['title']).'</strong>';
							echo ' - '.$plxPlugin->getLang('L_VERSION').' <strong>'.plxUtils::strCheck($plug['version']).'</strong>';
							if($plug['date']!='')
								echo ' ('.plxUtils::strCheck($plug['date']).')';
							echo '<br />';
							echo plxUtils::strCheck($plug['description']).'<br />';
							echo $plxPlugin->getLang('L_AUTHOR').' : '.plxUtils::strCheck($plug['author']);
							if($plug['site']!='')
								echo ' - <a href="'.plxUtils::strCheck($plug['site']).'">'.plxUtils::strCheck($plug['site']).'</a>';
						echo '</td>';

						if($update)
							echo '<td class="action"><input type="submit" class="medium red btsk update" name="button['.$plugName.']" value="'.$plxPlugin->getLang('L_UPDATE').'" /></td>';
						elseif($new)
							echo '<td class="action"><input type="submit" class="medium blue btsk add" name="button['.$plugName.']" value="'.$plxPlugin->getLang('L_DOWNLOAD').'" /></td>';
						else
							echo '<td class="action"><input type="submit" class="medium green btsk" name="button['.$plugName.']" value="'.$plxPlugin->getLang('L_INSTALL').'" /></td>';

						echo '<input type="hidden" name="buttonhit['.$plugName.']" value="'.$plugName.'.'.plxUtils::strCheck($plug['version']).'" />';
						echo '<input type="hidden" name="buttonrepository['.$plugName.']" value="'.$groupe.'" />';
						echo "</tr>";
					}
		
				}
		
		
		
		
		
		
		
		
		
		
		
		
		
		?>
	
			</table>
		</form>
	
	
	
	
	
	
	
	
	
	
	
	</div>



<?php } # fin traitement dépots stores ?>




<?php 
/*
echo ("<pre>");
		print_r($astores);
		echo ("</pre>");*/
		
		

	
/*	
echo ("<pre>");
		print_r($aPlugins);
		echo ("</pre>");
		

	
echo ("<pre>");
		print_r($repo);
		echo ("</pre>");
		
*/
/*
echo ("<pre>");
		print_r($aPlugins2);
		echo ("</pre>");*/

 ?>