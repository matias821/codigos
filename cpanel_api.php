<?php
class usrCp{
 /**
Ejecutar acciones de usuarios cPanel, acceder al panel de control, generar nueva clave, 
ver cuentas de correo mostrar cuentas de revendedor
 */  
	public $server_id;
	private $user;
	private $token;
	private $hostname='';
	private $estado=0;
	private $estado_error='';
	private $server_estado=0;
	private $rs_orden;              // Para nuevas cuentas se envia 0

    public function __construct($server_id, $rs_orden){  // 0 es chat - 1 contacto - 2 soporte 
		$this->server_id=$server_id;
		$this->rs_orden=$rs_orden;
		$this->obtenerServer();
    }
	
	private function obtenerServer(){
		global $db;
		$sql="SELECT * FROM sist_servidores WHERE id_serv=" . $this->server_id . " LIMIT 1";
		$db->cargar_sql($sql);
		$rs_servidor=$db->cargar_aviso($sql);
		if ($rs_servidor->hostname!=''){
			$this->user = "root";
			$this->token = $this->tokenRecuperar($rs_servidor->apitoken);
			$this->hostname=$rs_servidor->hostname;
			$this->server_estado=1;
		}else{
			$this->server_estado=0;		
		}
	}

	private function tokenRecuperar($token){
		$token=substr($token, 5,strlen($token)-10);
		return $token;	
	}

	public function iniciarServicio($id_servicio){
		$result=array();
		if (strtolower($this->rs_orden->username)=='root'){
			exit;
		}
		if ($this->estadoOrden()){
			$arr_service=$this->obtenerServicios($id_servicio);
			if ($id_servicio==14 and $this->rs_orden->username!=''){
				// Para cambio de clave	
				if ($this->queryClave()){
					$result["result"]=1;
					$result["call_js"]='		
					parent.document.getElementById("msg_general").innerHTML="El password fue actualizado y enviado mediante correo";
					parent.document.getElementById("msg_general").style.color="#fff";	
					parent.document.getElementById("msg_general").style.background="#018c00";	
					parent.document.getElementById("msg_general").style.display="block";
					';
				}else{
					$result["result"]=0;
					$result["call_js"]='		
					parent.document.getElementById("msg_general").innerHTML="No fue posible enviar la clave, contacte soporte";
					parent.document.getElementById("msg_general").style.color="#fff";	
					parent.document.getElementById("msg_general").style.background="#018c00";	
					parent.document.getElementById("msg_general").style.display="block";
					';
				}
			}else{
				$query = "https://" . $this->hostname . ":2087/json-api/create_user_session?api.version=1&user=" . $this->rs_orden->username . "&service=" . $arr_service["service"] . "&locale=es&app=" . $arr_service["service_app"] ;
				$url=$this->ejecutarQueryServicios($query);
				if ($url["estado"]==1){
					$result["result"]=1;
					$result["url"]=$url["url"];
					$result["call_js"]='
					parent.document.getElementById("enlace_servicios").href="' . $url["url"] . '"; 
					parent.document.getElementById("enlace_servicios").click();
					parent.document.getElementById("msg_general").innerHTML="Acceso Correcto";
					parent.document.getElementById("msg_general").style.color="#fff";	
					parent.document.getElementById("msg_general").style.background="#018c00";	
					parent.document.getElementById("msg_general").style.display="block";
					';					
				}else{
					$result["result"]=0;
					$result["error_msg"]='Error al crear la consulta';
					$result["call_js"]='
					parent.document.getElementById("msg_general").innerHTML="No es posible Acceder";
					parent.document.getElementById("msg_general").style.color="#fff";	
					parent.document.getElementById("msg_general").style.background="#ff9b31";	
					parent.document.getElementById("msg_general").style.display="block";	
				';
				}
			}
		}else{
			$result["call_js"]='
			parent.document.getElementById("msg_general").innerHTML="El servicio no se encuentra activo";
			parent.document.getElementById("msg_general").style.color="#fff";	
			parent.document.getElementById("msg_general").style.background="#ff9b31";	
			parent.document.getElementById("msg_general").style.display="block";	
			';
			$result["result"]=0;
			$result["error_msg"]=$this->estado_error;
		}
		return $result;
	}
	private function queryClave(){
		global $rs_usuario;
		$pass_cpanel=passCpanel();
		$query = "https://" . $this->hostname . ":2087/json-api/passwd?api.version=1&user=" . $this->rs_orden->username . "&password=" . $pass_cpanel . "&enabledigest=1" ;
		$curl = curl_init();
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
			$header[0] = "Authorization: whm $this->user:$this->token";
			curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
			curl_setopt($curl, CURLOPT_URL, $query);
		$result = curl_exec($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($http_status != 200) {
			return false;
		}else{
		   global $rs_sitio;
		   $json = json_decode($result);
		   $email=$rs_usuario->usr_email;
		   $asunto="Su clave de acceso";
		   $mensaje="Estimado/a " . ucfirst($rs_usuario->usr_nombre) . " <br><hr> Clave de acceso:" . $pass_cpanel . " <br><strong>La nueva clave puede demorar hasta 10 minutos en funcionar<br>Reiterados intentos fallidos pueden bloquear su ip, pero puede desbloquearla desde el Panel de control!</strong><br> Por seguridad solo se envia el dato solicitado, sin usuarios y/o enlaces. Esta informacion esta disponible en su panel de control. " . mailPie($rs_sitio);
		   $usr=$rs_usuario->usr_id;
		  // echo '<br>mail: ' . $email . ' Asunto: ' . $asunto . ' msg: ' . $mensaje . ' usr: ' . $usr;
		   enviarEmail($email, $asunto, $mensaje,$usr);
		   return true;
		}
		curl_close($curl);
	}
	
	private function obtenerServicios($id_servicio){
		$service='cpaneld';
		$service_app='';
		switch ($id_servicio){
			case 1:
				$service_app='Email_Accounts';
			break;	
			case 2:
				$service_app='Email_Forwarders';
			break;	
			case 3:
				$service_app='Email_AutoResponders';
			break;	
			case 4:
				$service_app='FileManager_Home';
			break;	
			case 5:
				$service_app='Backups_Home';
			break;	
			case 6:
				$service_app='Domains_SubDomains';
			break;	
			case 7:
				$service_app='Domains_AddonDomains';
			break;	
			case 8:
				$service_app='Cron_Home';
			break;	
			case 9:
				$service_app='Database_MySQL';
			break;	
			case 10:
				$service_app='Database_phpMyAdmin';
			break;	
			case 11:
				$service_app='Stats_AWStats';
			break;	
			case 12:
				$service_app='';   //CPANEL COMPLETO
			break;	
			case 15:
				$service='whostmgrd';
				$service_app='';
				//$service_app='whostmgrd';   // WHM
			break;										
		}
		$result["service"]=$service;
		$result["service_app"]=$service_app;
		return $result;
	}

	private function estadoOrden(){
		$rs_orden=$this->rs_orden;
		if (!$rs_orden){
			$this->estado=0;
			$this->estado_error='Orden no encontrada';	
		}
		$msg_error='';
		switch ($rs_orden->estado){
			case 1:
				$msg_error='La cuenta esta en proceso de activacion';
				break;
			case 3:
				$msg_error='La cuenta esta suspendida';
				break;
			case 4:
				$msg_error='La cuenta fue terminada';
				break;
			case 6:
				$msg_error='Su cuenta fue suspendida';
				break;
			case 8:
				$msg_error='Su cuenta aun no esta activa';		
			default:
			
		}
		
		if ($msg_error==''){
			$this->estado=1;
			$this->estado_error='';
			return true;
		}else {
			$this->estado=0;
			$this->estado_error=$msg_error;
			return false;
		}
	}
    
	public function listReseller($reseller_user){
		$query = "https://" . $this->hostname . ":2087/json-api/listaccts?api.version=1";
		$rs_reseller=$this->ejecutarQuery($query,$reseller_user);
		print_r($rs_reseller);
	}
    
	public function ejecutarQuery($query,$reseller_user){			
		$curl = curl_init();
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);

			$header[0] = "Authorization: whm $this->user:$this->token";
			curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
			curl_setopt($curl, CURLOPT_URL, $query);

		$result = curl_exec($curl);		
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($http_status != 200) {
			echo "[!] Error: " . $http_status . " returned\n";
		} else {
			$json = json_decode($result);
			$cont=0;
			foreach ($json->{'data'}->{'acct'} as $userdetails) {	
			
				if ($userdetails->{'owner'}==$reseller_user and $userdetails->{'user'}!=$reseller_user){
					$cuentas.="<br>" . $cont . ' ' . $userdetails->{'user'};
					$rs_cuentas[$userdetails->{'user'}]["existe"]=1;
					$rs_cuentas[$userdetails->{'user'}]["user"]=$userdetails->{'user'};
					$rs_cuentas[$userdetails->{'user'}]["owner"]=$userdetails->{'owner'};
					$rs_cuentas[$userdetails->{'user'}]["espacio_usado"]=$userdetails->{'diskused'};
					$rs_cuentas[$userdetails->{'user'}]["suspendida"]=$userdetails->{'suspendtime'};
					$rs_cuentas[$userdetails->{'user'}]["suspendida_motivo"]=$userdetails->{'suspendreason'};
					$rs_cuentas[$userdetails->{'user'}]["dominio"]=$userdetails->{'domain'};
					$cont=$cont+1;
				}
			}
		}
		curl_close($curl);		
		return $rs_cuentas;
	}
    
	public function ejecutarQueryServicios($query){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
		$header[0] = "Authorization: whm $this->user:$this->token";
		curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
		curl_setopt($curl, CURLOPT_URL, $query);
	
		$result = curl_exec($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if ($http_status != 200) {
			$resultado["estado"]=0;
		}else{
			$json = json_decode($result);
			$resultado["estado"]=1;
			$resultado["url"]=$json->{'data'}->{'url'};
		}
		curl_close($curl);
		return $resultado;
	}
}
   
?>
