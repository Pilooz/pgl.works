<?php
/**
 * Plugin spxplugindownloader
 *
 * @author	Stephane F modified by Je-evrard 
 **/

class spxplugindownloader extends plxPlugin {

	/**
	 * Constructeur de la classe
	 *
	 * @param	default_lang	langue par défaut
	 * @return	stdio
	 * @author	Stephane F
	 **/
	public function __construct($default_lang) {

		# appel du constructeur de la classe plxPlugin (obligatoire)
		parent::__construct($default_lang);

		# droits pour accèder à la page config.php
		$this->setConfigProfil(PROFIL_ADMIN);

		# droits pour accèder à la page admin.php
		$this->setAdminProfil(PROFIL_ADMIN);
		
		$this->setAdminMenu($this->getLang('L_SPXPLUGINDOWNLOADER'), false, $this->getLang('L_SPXPLUGINDOWNLOADERTITLE'));
		
		# déclaration des hooks
		$this->addHook('AdminTopEndHead', 'AdminTopEndHead');
		$this->addHook('AdminTopBottom', 'AdminTopBottom');

	}

	/**
	 * Méthode appelée à l'activation du plugin pour créer le répertoire cache
	 *
	 * @author	Stephane F
	 **/
	public function onActivate() {
		if(!is_dir(PLX_PLUGINS.'/cache')) {
			mkdir(PLX_PLUGINS.'/cache',0755,true);
		}
	}

	/**
	 * Méthode qui ajoute la déclaration de la feuille de style pour l'écran d'admin du plugin
	 *
	 * @return	stdio
	 * @author	Stephane F
	 **/
	public function AdminTopEndHead() {
		echo '<link rel="stylesheet" type="text/css" href="'.PLX_PLUGINS.'spxplugindownloader/css/style.css" />'."\n";
	}

	/**
	 * Méthode qui effectue les contrôles pour le fonctionnement du plugin
	 *
	 * @return	stdio
	 * @author	Stephane F
	 **/
	public function AdminTopBottom() {

		$string = '

			$test1 = spxplugindownloader::is_cURL();
			$test2 = is_dir(PLX_PLUGINS);
			$test3 = is_writable(PLX_PLUGINS);

			if(!$test1 OR !$test2 OR !$test3) {

				echo "<p class=\"warning\">Plugin MyPluginDownloader<br />";
				if(!$test1) echo "<br />'.$this->getLang('L_ERR_CURL').'";
				if(!$test2) echo "<br />'.$this->getLang('L_ERR_PLX_PLUGINS').'";
				if($test2 AND !$test3) echo "<br />'.$this->getLang('L_ERR_WRITE_ACCESS').'";
				echo "</p>";
				plxMsg::Display();

			}
		';
		echo '<?php '.$string.' ?>';

	}

	/***************************************************/
	/* méthodes publiques pour télécharger des plugins */
	/***************************************************/

	public static function is_cURL() {
		return in_array("curl", get_loaded_extensions());
	}

	public static function getRemoteFileContent($remotefile){

		$curl = curl_init($remotefile);
		curl_setopt($curl, CURLOPT_FAILONERROR, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($curl);
		curl_close($curl);
		if ($result !== false){
			return $result;
		}
		return false;
	}
	
	
	public static function is_RemoteFileExists($remotefile) {
		return true;
		// Version 4.x supported
		$curl   = curl_init($remotefile);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_FAILONERROR, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, Array("User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.15) Gecko/20080623 Firefox/2.0.0.15") ); // request as if Firefox
		curl_setopt($curl, CURLOPT_NOBODY, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
		$connectable = curl_exec($curl);
		curl_close($curl);
		return $connectable;

	}

	# bronco function
	public static function downloadRemoteFile2($url,$pretend=true){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept-Charset: UTF-8'));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,  FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        if (!ini_get("safe_mode") && !ini_get('open_basedir') ) {curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);}
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        if ($pretend){curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:19.0) Gecko/20100101 Firefox/19.0');}
    
        curl_setopt($ch, CURLOPT_REFERER, 'http://mon-cul-sur-la-commode.com');// notez le referer "custom"

        $data = curl_exec($ch);
        $response_headers = curl_getinfo($ch);

        // Google seems to be sending ISO encoded page + htmlentities, why??
        if($response_headers['content_type'] == 'text/html; charset=ISO-8859-1') $data = html_entity_decode(iconv('ISO-8859-1', 'UTF-8//TRANSLIT', $data)); 


        curl_close($ch);
		
		

        return $data;
    }
	
	# stef function
	public static function downloadRemoteFile($remotefile, $destination,$VerifyPeer=false,$VerifyHost=true) {
		if($fp = fopen($destination, 'w')) {
			$curl = curl_init($remotefile);
			curl_setopt($curl, CURLOPT_FILE, $fp);
			curl_setopt($curl, CURLOPT_HEADER, 0); # we are not sending any headers
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			spxplugindownloader::curl_redir_exec($curl);
			curl_close($curl);
			fclose($fp);
		}
		else return false;

		return (is_file($destination) AND filesize($destination)>0);

	}

	# get de la liste des magasins de dépots
	public static function getStoresRepository($filename) {
		$array=array();
		# Mise en place du parseur XML
		$data = implode('',file($filename));
		$parser = xml_parser_create(PLX_CHARSET);
		xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
		xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,0);
		xml_parse_into_struct($parser,$data,$values,$iTags);
		xml_parser_free($parser);
		# Récupération des données xml
		
		if(isset($iTags['repository'])) {
			$nb = sizeof($iTags['name']);
			for($i = 0; $i < $nb; $i++) {
				$name = plxUtils::getValue($values[$iTags['name'][$i]]['value']);
				if($name!='') {
					$array[$name]['name'] = $name;
					$array[$name]['title'] = plxUtils::getValue($values[$iTags['title'][$i]]['value']);
					$array[$name]['author'] = plxUtils::getValue($values[$iTags['author'][$i]]['value']);
					$array[$name]['repositoryurl'] = plxUtils::getValue($values[$iTags['repositoryurl'][$i]]['value']);
					$array[$name]['repositoryversionurl'] = plxUtils::getValue($values[$iTags['repositoryversionurl'][$i]]['value']);
					$array[$name]['site'] = plxUtils::getValue($values[$iTags['site'][$i]]['value']);
					$array[$name]['description'] = plxUtils::getValue($values[$iTags['description'][$i]]['value']);
					$array[$name]['icon'] = plxUtils::getValue($values[$iTags['icon'][$i]]['value']);
				}

			}
		}
		return $array;
	}
	
	
	public static function getRepository($filename) {
		$array=array();
		# Mise en place du parseur XML
		$data = implode('',file($filename));
		$parser = xml_parser_create(PLX_CHARSET);
		xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
		xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,0);
		xml_parse_into_struct($parser,$data,$values,$iTags);
		xml_parser_free($parser);
		# Récupération des données xml
		
		if(isset($iTags['plugin'])) {
			$nb = sizeof($iTags['name']);
			for($i = 0; $i < $nb; $i++) {
				$name = plxUtils::getValue($values[$iTags['name'][$i]]['value']);
				if($name!='') {
					$array[$name]['name'] = $name;
					$array[$name]['title'] = plxUtils::getValue($values[$iTags['title'][$i]]['value']);
					$array[$name]['author'] = plxUtils::getValue($values[$iTags['author'][$i]]['value']);
					$array[$name]['version'] = plxUtils::getValue($values[$iTags['version'][$i]]['value']);
					$array[$name]['date'] = plxUtils::getValue($values[$iTags['date'][$i]]['value']);
					$array[$name]['site'] = plxUtils::getValue($values[$iTags['site'][$i]]['value']);
					$array[$name]['description'] = plxUtils::getValue($values[$iTags['description'][$i]]['value']);
					$array[$name]['file'] = plxUtils::getValue($values[$iTags['file'][$i]]['value']);
					
					$array[$name]['icon'] = plxUtils::getValue($values[$iTags['icon'][$i]]['value']);
				}

			}
		}
		return $array;
	}

	public static function ini_get_boolean($setting) {

		$my_boolean = ini_get($setting);
		if ((int) $my_boolean > 0)
			$my_boolean = true;
		else {
			$my_lowered_boolean = strtolower($my_boolean);
			if ($my_lowered_boolean === "true" || $my_lowered_boolean === "on" || $my_lowered_boolean === "yes")
				$my_boolean = true;
			else
				$my_boolean = false;
		}
		return $my_boolean;
	}

	public static function curl_redir_exec(/*resource*/ $ch, /*int*/ &$maxredirect = null) {
		$mr = $maxredirect === null ? 5 : intval($maxredirect);
		if (ini_get('open_basedir') == '' && !spxplugindownloader::ini_get_boolean('safe_mode')) {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $mr > 0);
			curl_setopt($ch, CURLOPT_MAXREDIRS, $mr);
		} else {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
			if ($mr > 0) {
				$newurl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
				$rch = curl_copy_handle($ch);
				curl_setopt($rch, CURLOPT_HEADER, true);
				curl_setopt($rch, CURLOPT_NOBODY, true);
				curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
				curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
				do {
					curl_setopt($rch, CURLOPT_URL, $newurl);
					$header = curl_exec($rch);
					if (curl_errno($rch)) {
						$code = 0;
					} else {
						$code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
						if ($code == 301 || $code == 302) {
							preg_match('/Location:(.*?)\n/', $header, $matches);
							$newurl = trim(array_pop($matches));
						} else {
							$code = 0;
						}
					}
				} while ($code && --$mr);
				
				curl_close($rch);
				if (!$mr) {
					if ($maxredirect === null) {
						trigger_error('Too many redirects. When following redirects, libcurl hit the maximum amount.', E_USER_WARNING);
					} else {
						$maxredirect = 0;
					}
					return false;
				}
				curl_setopt($ch, CURLOPT_URL, $newurl);
			}
		}
		return curl_exec($ch);
	}
	
	public static function getlistthemes() {
		$plxMotor = plxMotor::getInstance();
		return plxGlob::getInstance(PLX_ROOT.$plxMotor->aConf['racine_themes'], true);
	}
	
	# theme status
	public static function getthemestatus($themeName) {
		$bneedupdate=false;
		$bneedactive=false;
		$themeVersion = self::$aPackage["themesneeded"][$themeName]["version"];
		
		# check actual situation
		$b1 = self::theme_check_exist($themeName);
		if ($b1){
			self::set_log ("getthemestatus theme exist", $themeName);
			$b2 = self::theme_check_active($themeName);
	
			if ($b2==false){
				$bneedactive=true;
			}
			$version=self::theme_getInfos($themeName);
			self::set_log ("theme version", $version);
			if (floatval($themeVersion)>floatval($version)){
				$bneedupdate=true;
			}
			
		}else{
			self::set_log ("getthemestatus theme not exist", $themeName);
			$bneedupdate = true;
			$bneedactive=true;
		}
		
		self::set_log ($themeName, "bneedupdate=".$bneedupdate." bneedactive=".$bneedactive);
		
		if ($bneedupdate == true && $bneedactive==true){
			return "need_update_and_active";
		}else if ($bneedupdate == true){
			return "need_update";
		}else if ($bneedactive==true){
			return "need_activation";
		}
		return "ok";
		
		
	}
	
	# check if a theme exist
	public static function theme_check_exist($themename) {
		$plxMotor = plxMotor::getInstance();
		if(!is_file(PLX_ROOT.$plxMotor->aConf['racine_themes'].$themename.'/infos.xml'))
				return false;
			else
				return true;
	}
	
	public static function theme_check_active($themename) {
		$plxMotor = plxMotor::getInstance();
		if ($plxMotor->aConf["style"]==$themename){
			return true;
		} else{
			return false;
		}
	}
	
	public static function theme_getInfos($themename,$info='version') {
		$plxMotor = plxMotor::getInstance();
		$file = PLX_ROOT.$plxMotor->aConf['racine_themes'].$themename.'/infos.xml';
		if(!is_file($file)) return;

		# Mise en place du parseur XML
		$data = implode('',file($file));
		$parser = xml_parser_create(PLX_CHARSET);
		xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
		xml_parser_set_option($parser,XML_OPTION_SKIP_WHITE,0);
		xml_parse_into_struct($parser,$data,$values,$iTags);
		xml_parser_free($parser);
		$aInfos = array(
			'title'			=> (isset($iTags['title']) AND isset($values[$iTags['title'][0]]['value']))?$values[$iTags['title'][0]]['value']:'',
			'author'		=> (isset($iTags['author']) AND isset($values[$iTags['author'][0]]['value']))?$values[$iTags['author'][0]]['value']:'',
			'version'		=> (isset($iTags['version']) AND isset($values[$iTags['version'][0]]['value']))?$values[$iTags['version'][0]]['value']:'',
			'date'			=> (isset($iTags['date']) AND isset($values[$iTags['date'][0]]['value']))?$values[$iTags['date'][0]]['value']:'',
			'site'			=> (isset($iTags['site']) AND isset($values[$iTags['site'][0]]['value']))?$values[$iTags['site'][0]]['value']:'',
			'description'	=> (isset($iTags['description']) AND isset($values[$iTags['description'][0]]['value']))?$values[$iTags['description'][0]]['value']:'',
			);
		
		if ($infos == 'all') return $aInfos;
		return $aInfos[$info];
	}
	
	/**
	 * Méthode récursive qui supprime tous les dossiers et les fichiers d'un répertoire
	 *
	 * @param	deldir	répertoire de suppression
	 * @return	boolean	résultat de la suppression
	 * @author	Stephane F
	 **/
	public static function deleteDir($deldir) { #fonction récursive

		if(is_dir($deldir) AND !is_link($deldir)) {
			if($dh = opendir($deldir)) {
				while(FALSE !== ($file = readdir($dh))) {
					if($file != '.' AND $file != '..') {
						spxplugindownloader::deleteDir(($deldir!='' ? $deldir.'/' : '').$file);
					}
				}
				closedir($dh);
			}
			return rmdir($deldir);
		}
		return unlink($deldir);
	}
	
	
	
	
	
	
	
	
	
	
}
?>