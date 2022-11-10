#!/usr/local/bin/php -q
<?php
// Este script se ejecuta cada vez que llega un correo a soporte@tophosting.net
// Convierte mails en Tickets de soporte
// Detecta el asunto y lo asigna como respuesta a un ticket de soporte, creacion de un ticket de soporte, etc
error_reporting(E_ALL);
ini_set('display_errors', '1');

$madmin="soporte_pipe@tophosting.net";
include('/home/tophost/public_html/clases/class_mysql.php');
include("pipe_func.php");
include("/home/tophost/public_html/funciones/comunes.php");
include("/home/tophost/public_html/funciones/correos.php");
include("/home/tophost/public_html/funciones/tickets.php");
require_once('parse/class_parse.php');
$db = DataBase::getInstance();

$Parser = new MimeMailParser();
$Parser->setStream(fopen("php://stdin", "r"));
$from = $Parser->getHeader('from');
$subject = $Parser->getHeader('subject');
$text_plane = $Parser->getMessageBody('text');
$html = $Parser->getMessageBody('html');

$rs_from=explode("<", $from);
if (count($rs_from)>=2){
	$rs_from=explode(">", $rs_from[1]);			
	$from=$rs_from[0];
}

$cuerpo='';
$cuerpo_temp= explode ("<body", $html);
if (count($cuerpo_temp)>=2){
	$posicion_coincidencia = strpos($cuerpo_temp[1], ">");
	if ($posicion_coincidencia) {
		$cuerpo_temp[1]=substr($cuerpo_temp[1], $posicion_coincidencia + 1);
	}
	$cuerpo=$cuerpo_temp[0] . '<body>';
	$cuerpo.=$cuerpo_temp[1];
}else{
	$cuerpo='' . $html;
}

// Final pipes - Comienza verificacion y creacion de tickets
$rs_text_plane=explode("_________________________", $text_plane);
$text_plane=$rs_text_plane[0];
$ticket_usr_id=0;
$rs_ticket=explode("ID:", $subject);	
if (count($rs_ticket)>=2){
	$rs_ticket=explode("]", $rs_ticket[1]);			
	$id_ticket=$rs_ticket[0];
	$ticket_usr_id=0;
}else{
	$rs_ticket=explode("(", $subject);	
	if (count($rs_ticket)>=2){
		$rs_ticket_tmp=explode(")", $rs_ticket[1]);	
		$rs_ticket=explode("-", $rs_ticket_tmp[0]);		
		$id_ticket=$rs_ticket[0];
		$ticket_usr_id=$rs_ticket[1];
	}
}
$existe='no';
if ($from=='soporte@tophosting.net' or $from=='soporte_pipe@tophosting.net' or $from==$madmin){
	exit; // Evita duplicar tickets	
}

$rs_datos=ticketAbierto($id_ticket, $ticket_usr_id);
$rs_datos["mensaje"]=$text_plane;
if ($rs_datos["existe"]==1){
	$existe='si';
	$notificar_admin=0;
	$rs_ticket=msgCrear($rs_datos,$notificar_admin);
	// Enviar mensaje de ticket a soporte
}else{
	$rs_datos=existeCliente($from);
	if ($rs_datos["existe"]==1){
		// Crear nuevo Ticket
		$rs_datos["email"]=$from;
		$rs_datos["asunto"]=$subject;
		$rs_datos["categoria"]=7; // Creado por email
		$notificar_admin=0;
		$notificar_usr=1;
		$rs_ticket=ticketCrear($rs_datos,$notificar_admin, $notificar_usr);

		// creo mensaje/body		
		$rs_datos["ticket_id"]=$rs_ticket["id"];
		$rs_datos["ticket_token"]=$rs_ticket["token"];
		$rs_datos["mensaje"]=$text_plane;
		$notificar_admin=0;
		$rs_ticket=msgCrear($rs_datos,$notificar_admin);
	}else{
		// no existe ticket ni el cliente, debe llegar solo el email a soporte sin ticket, de esta forma evito spam ademas nadie deberia escribir directamete a soporte sin ser cliente. invitados enviaran desde formulario.
			
	}	
}

?>
