<?php if(!defined('PLX_ROOT')) exit; ?>

<?php
if(isset($_GET['page'])) {
	$page = $_GET['page'];
	if ($page=="themes"){
		include('admin-themes.php');
	}else{
		include('admin-plugins.php');
	}
	
}else{
	include('admin-plugins.php');
}



 ?>