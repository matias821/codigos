<?php
class sitio{
 /**
   Obtener dominio, segun el dominio se mostrara idioma, secciones, planes y precios en moneda local. 
 */
	public $planes_de=0;
	public $dominio;
	public $nombre_completo;
	public $pais_id;
	public $pais_nombre;
	public $pais_nombre2;
	public $pais_codigo;
	public $pais_descripcion;
	public $analytics_id;
	public $url='';
	public $ano;
	public $mes;
	public $moneda_id;
	public $moneda_nombre;
	public $moneda_simbolo;
	public $moneda_simbolo2;
	
	public $blog_activo=0;
	public $blog_directorio='';
	public $ciudades_activas=0;
	public $hosting_vende=0;
	public $sitio_activo=1;
	public $header_tipo=0;
	public $ranking_mostrar=0;
	public $ranking_directorio='';
    public $tipo_logo;
	public $logo1;
	public $logo2;
	public $logo3;
	public $eslogan;
	public $cdn_ruta;
	
    public function __construct(){
       $this->inicio($this->obtenerDominio());
	   $arr_mes=array('Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
	   $this->mes=$arr_mes[date("n")-1];
    }
	
	private function inicio($dominio){
		$this->planes_de=0;
		$dominio=trim(strtolower($dominio));
		switch ($dominio) {
			case 'tophostingargentina.com' :
				require($_SERVER['DOCUMENT_ROOT'] . "/modulos/arrays/info1.php");                             //este dato hay que cargarlo manual para cada sitio  ATENCION info ID_SITIO
				break;	
			case 'matsysweb.com' :
				require($_SERVER['DOCUMENT_ROOT'] . "/modulos/arrays/info20.php");                             
				break;	
			case 'tophosting.ar' :
				require($_SERVER['DOCUMENT_ROOT'] . "/modulos/arrays/info20.php");                             
				break;					
			case 'tophosting.net' :
				require($_SERVER['DOCUMENT_ROOT'] . "/modulos/arrays/info18.php");
				//include("modulos/arrays/info18.php"); 
				$idioma=1;                            
				break;	
			case 'tophosting.es' :
				include($_SERVER['DOCUMENT_ROOT'] . "/modulos/arrays/info12.php");                             
				break;			
			case 'tophostingchile.com' :
				include($_SERVER['DOCUMENT_ROOT'] . "/modulos/arrays/info4.php");                             //este dato hay que cargarlo manual para cada sitio  ATENCION info ID_SITIO
				$this->planes_de=18;
				break;	
			case 'tophostingcolombia.com' :
				include($_SERVER['DOCUMENT_ROOT'] . "/modulos/arrays/info6.php");  
				$this->planes_de=18;                           
				break;				
			case 'tophostingperu.com' :
				include($_SERVER['DOCUMENT_ROOT'] . "/modulos/arrays/info3.php");   
				$this->planes_de=18;                          
				break;	
			case 'tophostingamerica.com' :
				require($_SERVER['DOCUMENT_ROOT'] . "/modulos/arrays/info20.php");                             
				break;											
			case 'elmejorhosting.net' :
				$this->planes_de=18;

				break;	
			case 'hostingpremium.com.ar' :
				require($_SERVER['DOCUMENT_ROOT'] . "/modulos/arrays/info22.php");   
				$this->planes_de=20;                          
				break;	
			case 'tophosting' :
				require($_SERVER['DOCUMENT_ROOT'] . "/modulos/arrays/info20.php"); 
				//$this->planes_de=18;                          
				break;	
			case 'matsysweb' :
				require($_SERVER['DOCUMENT_ROOT'] . "/modulos/arrays/info20.php");                            
				break;	
			case 'tophostingus' :
				require($_SERVER['DOCUMENT_ROOT'] . "/modulos/arrays/info18.php");                            
				break;	
			default:
				$this->notificarAdmin('Pais no detectado', 'Pais no detectado');
				require($_SERVER['DOCUMENT_ROOT'] . "/modulos/arrays/info1.php");                             			
		}

		$this->dominio=$sitio["dominio"];
		$this->nombre_completo=$sitio["nombre_completo"];
		$this->pais_id=$sitio["id_pais"];
		if ($this->planes_de==0){
			$this->planes_de=$this->pais_id;
		}
		$this->pais_nombre=$sitio["nombre"];
		$this->pais_nombre2=$sitio["nombre2"];
		$this->pais_codigo=$sitio["codigo"];
		$this->pais_descripcion=$sitio["descripcion"];
		$this->analytics_id=$sitio["analytics"];
		$this->moneda_id=$sitio["id_moneda"];
		$this->moneda_nombre=$sitio["nmoneda"];
		$this->moneda_simbolo=$sitio["simbolo"];
		$this->moneda_simbolo2=$sitio["simbolo2"];
		$this->blog_activo=$sitio["mostrar_blog"];
		$this->blog_directorio=$sitio["base_blog"];
		$this->ranking_directorio=$sitio["base_empresas"];

		$this->ciudades_activas=$sitio["ciudades"];
		$this->hosting_vende=$sitio["vende"];
		
		$this->sitio_activo=$sitio["activo"];
		$this->header_tipo=$sitio["tipo_header"];
		
		$this->logo1=$sitio["logo1"];
		$this->logo2=$sitio["logo2"];
		$this->logo3=$sitio["logo3"];
		
		$this->eslogan=$sitio["eslogan"];
		$this->cdn_ruta='/statics/';//'https://statics.' . $this->dominio;
		$this->ano=date("Y");
	}
	
	public function notificarAdmin($titulo, $msg){
		echo 'Notificado';	
	}
	
	public function enviarEmail(){

	}
	
	public function fLog($cliente_ip,$id_cli,$tipo,$valor){
		global $db;
		$sql="INSERT INTO sist_log (ip, cliente, tipo, valor) VALUES ('$cliente_ip', $id_cli, '$tipo', '$valor')";
		$db->cargar_sql($sql);
		$db->ejecutar_sql();
	}
	
	private function obtenerDominio(){
		$dominio='';
		$datos_dominio=explode(".",$_SERVER['SERVER_NAME']);
		$pagina_url=$_SERVER['REQUEST_URI'];
		$this->url=$pagina_url;
		if ($_SERVER['SERVER_NAME']=='localhost'){
			$dominio='localhost';	
		}
		if (isset($datos_dominio[2]) and $datos_dominio[2]!='ar'){
			$subdominio=$datos_dominio[0];
			if ($subdominio=='www' or $subdominio==$_SERVER['HTTP_HOST']){
				$subdominio='';
			}	
			if (count($datos_dominio)==2){
				$subdominio='';
			}
			if (count($datos_dominio)==2){
				$dominio=$datos_dominio[0] . '.' . $datos_dominio[1];
			}
			if (count($datos_dominio)==3){
				$dominio=$datos_dominio[1] . '.' . $datos_dominio[2];
			}
		}
		if (isset($datos_dominio[3]) and $datos_dominio[3]=='ar'){
				$subdominio=$datos_dominio[0];
				if ($subdominio=='www' or $subdominio==$_SERVER['HTTP_HOST']){
					$subdominio='';
				}	
				$dominio=$datos_dominio[1] . '.' . $datos_dominio[2] . '.' . $datos_dominio[3];	
		}
		if (isset($datos_dominio[2]) and $datos_dominio[2]=='ar'){
			$dominio=$datos_dominio[0] . '.' . $datos_dominio[1] . '.' . $datos_dominio[2];
		}
		//para localhost
		if ($dominio==''){
			$dominio=$datos_dominio[0];	
		}
		return $dominio;		
	}

}
   
?>
