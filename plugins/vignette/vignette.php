<?php
/**
 * Plugin Vignette
 *
 * @package	PLX
 * @version	1.0
 * @date	29/11/2015
 * @author	Rockyhorror
 **/
class vignette extends plxPlugin {

	/**
	 * Constructeur de la classe vignette
	 *
	 * @param	default_lang	langue par défaut utilisée par PluXml
	 * @return	null
	 * @author	Rockyhorror
	 **/
	public function __construct($default_lang) {

		# Appel du constructeur de la classe plxPlugin (obligatoire)
		parent::__construct($default_lang);

		# Déclarations des hooks
		$this->addHook('AdminArticlePostData', 'AdminArticlePostData');
		$this->addHook('AdminArticleParseData', 'AdminArticleParseData');
		$this->addHook('AdminArticleInitData', 'AdminArticleInitData');
		$this->addHook('AdminArticlePreview', 'AdminArticlePreview');
		$this->addHook('AdminTopEndHead', 'AdminTopEndHead');
		$this->addHook('AdminArticleSidebar', 'AdminArticleSidebar');
		$this->addHook('plxAdminEditArticleXml', 'plxAdminEditArticleXml');
		$this->addHook('plxMotorParseArticle', 'plxMotorParseArticle');
		$this->addHook('showVignette', 'showVignette');
		$this->addHook('vignetteArtList', 'vignetteArtList');
		$this->addHook('FeedBegin', 'FeedBegin');
		
		# Autorisation d'acces à la configuration du plugins
		$this-> setConfigProfil(PROFIL_ADMIN, PROFIL_MANAGER);
	}

	public function FeedBegin() {
		define('PLX_FEED', true);
	}

	public function AdminTopEndHead() {
		echo "\n\t".'<script type="text/javascript" src="'.PLX_PLUGINS.'vignette/vignette.js"></script>';
	}

	public function AdminFootEndBody() {
		echo "\n\t".'<script type="text/javascript">vignette.init(\''.PLX_PLUGINS.'vignette/'.'\');</script>'."\n";
	}

	public function AdminArticlePostData () {
		echo '<?php $vignette = $_POST["vignette"]; ?>';
	}
	
	public function AdminArticleParseData () {
		echo '<?php $vignette = $result["vignette"]; ?>';
	}
	
	public function AdminArticleInitData () {
		echo '<?php $vignette = ""; ?>';
	}

	/**
	 * Méthode qui permet la prévisualisation de l'article avec la vignette
	 *
	 * @return	stdio
	 * @author	Rockyhorror
	 **/
	public function AdminArticlePreview () {
		$plxMotor = plxMotor::getInstance();
		
		echo "<?php \$art[\"vignette\"] = (empty(\$_POST[\"vignette\"]))?'':\$_POST[\"vignette\"]; ?>";
		if($this->getParam('disable_auto')) { return; }
		$root_dir = $plxMotor->aConf['medias'];
		$pattern = '/([\w\-\.]+)\.\w+$/';
		echo <<<END
<?php  
	if(!empty(\$art["vignette"])) {
		\$img = "$root_dir".\$art["vignette"];
		\$num = preg_match("$pattern", \$art['vignette'], \$match);
		if(\$num > 0) { 
			\$alt = str_replace(array('-', '_'), ' ', \$match[1]); 
		}
		else {
			\$alt = 'vignette';
		}
		\$srcCode = '<div class="vignette"><img src="'.\$img.'" alt="'.\$alt.'" /></div>';
		if(!empty(\$art['chapo'])){
			\$art['chapo'] = \$srcCode.\$art['chapo'];
		}
		else {
			\$art['content'] = \$srcCode.\$art['content'];
		}
	 }
?>
END;
		
	}


	/**
	 * Méthode qui ajoute le champs 'Vignette' dans l'edition de l'article
	 *
	 * @return	stdio
	 * @author	Rockyhorror
	 **/
	public function AdminArticleSidebar(){
		echo <<<END
			<div class="grid">
				<div class="col sml-12">
					<label for="id_vignette">
						{$this->getlang('L_PATH')}
						<a class="hint"><span>{$this->getlang('L_VIGNETTE_FIELD_TITLE')}</span></a>
					</label>
					<input id="id_vignette" name="vignette" type="text" value="<?php echo plxUtils::strCheck(\$vignette); ?>" size="27" maxlength="255" />
					<a title="{$this->getlang('L_VIGNETTE_TOGGLER_TITLE')}" id="toggler" href="javascript:void(0)" onclick="myVignette.openPopup('../../plugins/vignette/medias.php?id=vignette','undefined','750','580');return false;" style="outline:none">+</a>
				</div>
			</div>
END;

        }

	public function plxAdminEditArticleXml(){
		echo "<?php \$xml .= '\t'.'<vignette><![CDATA['.plxUtils::cdataCheck(trim(\$content['vignette'])).']]></vignette>'.'\n'; ?>";
	}


	/**
	 * Méthode qui affiche la vignette en mode intégration automatique
	 *
	 * @return	stdio
	 * @author	Rockyhorror
	 **/
	public function plxMotorParseArticle(){
		if(defined('PLX_FEED')) {
			$plxMotor = plxFeed::getInstance();
		}
		else {
			$plxMotor = plxMotor::getInstance();
		}
		
		echo "<?php \$art['vignette'] = (isset(\$iTags['vignette']))?trim(\$values[ \$iTags['vignette'][0] ]['value']):''; ?>";
		if($this->getParam('disable_auto')) { return; }
		$root_dir = $plxMotor->aConf['medias'];
		$pattern = '/([\w\-\.]+)\.\w+$/';
		echo <<<END
<?php 
	if(!empty(\$art['vignette']) && \$this->mode != '') {
		\$img = "$root_dir".\$art['vignette'];
		\$num = preg_match("$pattern", \$art['vignette'], \$match);
		if(\$num > 0) { 
			\$alt = str_replace(array('-', '_'), ' ', \$match[1]); 
		}
		else {
			\$alt = 'vignette';
		}
		\$srcCode = '<div class="vignette"><img src="'.\$img.'" alt="'.\$alt.'" /></div>';
		if(!empty(\$art['chapo'])){
			\$art['chapo'] = \$srcCode.\$art['chapo'];
		}
		else {
			\$art['content'] = \$srcCode.\$art['content'];
		}
	}; 
?>
END;
	}
	
	
	/**
	 * Méthode qui affiche la vignette en mode manuel
	 *
	 * @return	stdio
	 * @author	Rockyhorror
	 **/
	public function showVignette($params) {
		$plxMotor = plxMotor::getInstance();
		
		$vignette = $plxMotor->plxRecord_arts->f('vignette');
		if(empty($vignette)) { return; }
		
		if(isset($params)) {
			if(is_array($params)) {
				$pathOnly = !empty($params[0])?$params[0]:false;
				$format = !empty($params[1])?$params[1]:NULL;
			}
			else {
				$pathOnly = !empty($params)?$params:false;
			}
		}
		else {
			$pathOnly = false;
		}
		
		$root_dir = $plxMotor->aConf['medias'];
		$img = $root_dir.$vignette;
		if($pathOnly) {
			echo $img;
		}
		else {
			$pattern = '/([\w\-\.]+)\.\w+$/';
			$num = preg_match($pattern, $vignette, $match);
			if($num > 0) { 
				$alt = str_replace(array('-', '_'), ' ', $match[1]); 
			}
			else {
				$alt = 'vignette';
			}
			if(!isset($format)) { $format = '<div class="vignette"><img src="#url" alt="'.$alt.'" /></div>'; }
			$row = str_replace('#url',$img,$format);
			$row = str_replace('#alt',$alt,$row);
			echo $row;
		}
		
	}
	
	public function vignetteArtList($params) {
		$plxShow = plxShow::getInstance();
		
		if(isset($params)) {
			if(is_array($params)) {
				$format = empty($params[0])?'<li><a href="#art_url" title="#art_title"><img src="#art_vignette" />#art_title</a></li>':$params[0];
				$max = isset($params[1])?$params[1]:5;
				$cat_id = empty($params[2])?'':$params[2];
				$ending = empty($params[3])?'':$params[3];
				$sort = empty($params[4])?'rsort':$params[4];
			}
			else {
				$format = empty($params)?'<li><a href="#art_url" title="#art_title"><img src="#art_vignette" />#art_title</a></li>':$params;
			}
		}
		else {
			$format='<li><a href="#art_url" title="#art_title"><img src="#art_vignette" />#art_title</a></li>';
			$max=5;
			$cat_id='';
			$ending='';
			$sort='rsort';
		}
	
		# Génération de notre motif
		if(empty($cat_id))
			$motif = '/^[0-9]{4}.(?:[0-9]|home|,)*(?:'.$plxShow->plxMotor->activeCats.'|home)(?:[0-9]|home|,)*.[0-9]{3}.[0-9]{12}.[a-z0-9-]+.xml$/';
		else
			$motif = '/^[0-9]{4}.((?:[0-9]|home|,)*(?:'.str_pad($cat_id,3,'0',STR_PAD_LEFT).')(?:[0-9]|home|,)*).[0-9]{3}.[0-9]{12}.[a-z0-9-]+.xml$/';

		# Nouvel objet plxGlob et récupération des fichiers
		$plxGlob_arts = clone $plxShow->plxMotor->plxGlob_arts;
		if($aFiles = $plxGlob_arts->query($motif,'art',$sort,0,$max,'before')) {
			foreach($aFiles as $v) { # On parcourt tous les fichiers
				$art = $plxShow->plxMotor->parseArticle(PLX_ROOT.$plxShow->plxMotor->aConf['racine_articles'].$v);
				
				# recupere la vignette
				$vignette = plxUtils::strCheck($art['vignette']);
				$medias_path = $plxShow->plxMotor->aConf['medias'];
				$vignette_path = empty($vignette)?'':$medias_path.$vignette;
				
				$num = intval($art['numero']);
				$date = $art['date'];
				if(($plxShow->plxMotor->mode == 'article') AND ($art['numero'] == $plxShow->plxMotor->cible))
					$status = 'active';
				else
					$status = 'noactive';
				# Mise en forme de la liste des catégories
				$catList = array();
				$catIds = explode(',', $art['categorie']);
				foreach ($catIds as $idx => $catId) {
					if(isset($plxShow->plxMotor->aCats[$catId])) { # La catégorie existe
						$catName = plxUtils::strCheck($plxShow->plxMotor->aCats[$catId]['name']);
						$catUrl = $plxShow->plxMotor->aCats[$catId]['url'];
						$catList[] = '<a title="'.$catName.'" href="'.$plxShow->plxMotor->urlRewrite('?categorie'.intval($catId).'/'.$catUrl).'">'.$catName.'</a>';
					} else {
						$catList[] = L_UNCLASSIFIED;
					}
				}
				# On modifie nos motifs
				$row = str_replace('#art_id',$num,$format);
				$row = str_replace('#cat_list', implode(', ',$catList), $row);
				$row = str_replace('#art_url',$plxShow->plxMotor->urlRewrite('?article'.$num.'/'.$art['url']),$row);
				$row = str_replace('#art_status',$status,$row);
				$author = plxUtils::getValue($plxShow->plxMotor->aUsers[$art['author']]['name']);
				$row = str_replace('#art_author',plxUtils::strCheck($author),$row);
				$row = str_replace('#art_title',plxUtils::strCheck($art['title']),$row);
				$strlength = preg_match('/#art_chapo\(([0-9]+)\)/',$row,$capture) ? $capture[1] : '100';
				$chapo = plxUtils::truncate($art['chapo'],$strlength,$ending,true,true);
				$row = str_replace('#art_chapo('.$strlength.')','#art_chapo', $row);
				$row = str_replace('#art_chapo',$chapo,$row);
				$strlength = preg_match('/#art_content\(([0-9]+)\)/',$row,$capture) ? $capture[1] : '100';
				$content = plxUtils::truncate($art['content'],$strlength,$ending,true,true);
				$row = str_replace('#art_content('.$strlength.')','#art_content', $row);
				$row = str_replace('#art_content',$content, $row);
				$row = str_replace('#art_date',plxDate::formatDate($date,'#num_day/#num_month/#num_year(4)'),$row);
				$row = str_replace('#art_hour',plxDate::formatDate($date,'#hour:#minute'),$row);
				$row = plxDate::formatDate($date,$row);
				$row = str_replace('#art_nbcoms',$art['nb_com'], $row);
				# On ajoute la vignette
				$row = str_replace('#art_vignette', $vignette_path, $row);
				# On genère notre ligne
				echo $row;
			}
		}
	
	}

}
?>
