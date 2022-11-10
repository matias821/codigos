<?php
// Script llamado cada 5 minutos en cron para verificar si existe un proceso,
//en caso de no existir lo inicia. De esta forma por cualquier error o reinicio 
//del servidor el proceso es iniciado automaticamente.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 800); //300 seconds = 5 minutes
ini_set('memory_limit', '2048M');
include("class_mysql.php");
$db = DataBase::getInstance(); 

$rs_proceso=existeProceso('lanzar_cot.sh');
if ($rs_proceso["existe"]==0){
	echo 'no existe, inicia lanzador';
	exec("nohup /home/grabarcot/public_html/cron/lanzar_cot.sh > /dev/null &",$resultado);	
}else{
	echo 'si existe';
}
function existeProceso($proceso){
	exec("ps aux | grep " . $proceso . " | grep -v grep", $rs_result,$err);
	if (count($rs_result)>=1){
		//existe
		$rs["existe"]=1;	
	}else{
		//no existe	
		$rs["existe"]=0;
	}
	return $rs;
}


?>
