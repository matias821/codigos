<?php

class Coin
{
    public $last_close;
    public $id = 0;
    public $config_decimales_cot = 0;
    public $config_decimales_compra = 0;
    public $config_unidad_nim = '0';
    public $online = 0;
    public $simbolo = '';
    public $simbolo2 = '';
    public $simbolos;
    public $permiso_comprar = 0;
    public $permiso_vender = 0;
    public $ahora_comprar;
    public $velocidad;
    public $limites = 0;
    public $arr_limites;
    public $sql_or;
    public $emulacion = 0;
    public $last_buy_orden = 0;
    public $vender_orden_id = 0;

    public $modo = 1;
    public $modobot = 0;
    public $bot_activo = 0;
    public $simulacion = 0;


    public $operando = 0;
    public $monto = 0;
    public $sembrar = 0;
    public $sembrar_todas = 0;
    public $sembrar_inter = 0;
    public $sembrar_grandes = 0;

    public $profit_simu_24h;
    public $profit_simu_7d;
    public $profit_simu_30d;

    public $profit_real_24h;
    public $profit_real_7d;
    public $profit_real_30d;

    public $mostrar_real = 0;
    public $mostrar_simu = 0;
    public $mostrar_emu = 0;

    public $cant_ordenes;

    public $comprar = 0;
    public $estrategia_base = 0;
    public $estrategia = 0;
    public $precio_comprar;
    public $estrategia_sembrado;
    //public $sembrar_multi=0;  // 0 todas - 1 intermedias - 2 grandes caidas
    public $salida;

    public $cont_sembrar = 0;

    public function __construct($rs_moneda, $last_close)
    {
        $this->config_decimales_cot = $rs_moneda->cant_decimales;
        $this->config_decimales_compra = $rs_moneda->decimales_compra;

        $this->last_close = number_format($last_close, $this->config_decimales_cot);
        $this->id = $rs_moneda->id_mon;
        $this->config_unidad_nim = $rs_moneda->unidad_minima;
        $this->online = $rs_moneda->online;
        $this->modo = $rs_moneda->modo;
        $this->simbolo = $rs_moneda->simbolo;
        $this->simbolo2 = $rs_moneda->simbolo2;
        $this->simbolos = $this->simbolo . $this->simbolo2;
        $this->permiso_comprar = $rs_moneda->comprar;
        $this->permiso_vender = $rs_moneda->vender;
        $this->modobot = $rs_moneda->modobot;
        $this->bot_activo = $rs_moneda->bot_activo;
        $this->simulacion = $rs_moneda->simulacion;
        $this->operando = $rs_moneda->operando;
        $this->monto = $rs_moneda->cant;

        $this->sembrar_todas = $rs_moneda->sembrar;
        $this->sembrar_inter = $rs_moneda->sembrar_inter;
        $this->sembrar_grandes = $rs_moneda->sembrar_grandes;


        $this->ahora_comprar = $rs_moneda->ahora_comprar;
        $this->velocidad = $rs_moneda->velocidad;
        $this->limites = $rs_moneda->limites;

        $this->profit_real_24h = $rs_moneda->profit_24h;
        $this->profit_real_7d = $rs_moneda->profit_7d;
        $this->profit_real_30d = $rs_moneda->profit_30d;

        $this->profit_simu_24h = $rs_moneda->profit_24h_simu;
        $this->profit_simu_7d = $rs_moneda->profit_7d_simu;
        $this->profit_simu_30d = $rs_moneda->profit_30d_simu;

        $this->mostrar_real = $rs_moneda->mostrar_real;
        $this->mostrar_simu = $rs_moneda->mostrar_simu;
        $this->mostrar_emu = $rs_moneda->mostrar_emu;
        $this->sql_or = $this->sqlEstrategias();
        if ($this->limites >= 1) {
            $this->crearLimites();
        }

        $this->estrategia_base = 0;
        $this->estrategia = 0;
    }

    //seccion de compras
    public function crearLimites()
    {
        global $db;
        $id_limite = $this->limites;
        $sql = "SELECT * FROM limites_opciones2 WHERE id_limite=" . $id_limite;
        $db->cargar_sql($sql);
        $rs_limites = $db->cargar_avisos();

        $arr_limites = array();
        foreach ($rs_limites as $limite) {
            for ($i = $limite->valor1; $i < $limite->valor2; $i++) {
                $arr_limites[$i] = $limite->limit_orders;
            }

        }
        $this->arr_limites = $arr_limites;
    }


    public function Comprar($arr_compra)
    {
        global $moneda;
        global $rs_estrategias;
        global $sistema;
        if ($this->emulacion == 1) {
            $rs_posicion = analizarPosicion_emulador(1, $moneda->last_close);
        } else {
            $rs_posicion = analizarPosicion(1, $cotizacion);
        }

        if ($rs_posicion[15] == 0) {
            $sistema->grabarLog(5, 'Compra - Obtener posicion', 0);
            $this->suspenderCompras();
            return false;
        }

        $sembrar = 0;
        if ($arr_compra["sembrar"] >= 2 and ($this->estrategia_sembrado or $this->ahora_comprar == 1 or $rs_estrategias->arr_instrumentacion[$arr_compra["estrategia"]]["instrumentacion"] == 1)) {
            $sembrar = 1;
        }
        $arr_posc[1] = 1;
        $arr_posc[2] = 3;
        $arr_posc[3] = 4;
        $arr_posc[4] = 5;
        $arr_posc[5] = 6;
        $arr_posc[6] = 7;
        $arr_posc[7] = 8;
        $arr_posc[8] = 9;
        if ($rs_estrategias->arr_instrumentacion[$arr_compra["estrategia"]]["instrumentacion"] == 1) {
            $arr_posc[1] = $rs_estrategias->arr_instrumentacion[$arr_compra["estrategia"]]["s1"];
            $arr_posc[2] = $rs_estrategias->arr_instrumentacion[$arr_compra["estrategia"]]["s2"];
            $arr_posc[3] = $rs_estrategias->arr_instrumentacion[$arr_compra["estrategia"]]["s3"];
            $arr_posc[4] = $rs_estrategias->arr_instrumentacion[$arr_compra["estrategia"]]["s4"];
            $arr_posc[5] = $rs_estrategias->arr_instrumentacion[$arr_compra["estrategia"]]["s5"];
            $arr_posc[6] = $rs_estrategias->arr_instrumentacion[$arr_compra["estrategia"]]["s6"];
            $arr_posc[7] = $rs_estrategias->arr_instrumentacion[$arr_compra["estrategia"]]["s7"];
            $arr_posc[8] = $rs_estrategias->arr_instrumentacion[$arr_compra["estrategia"]]["s8"];
        }


        if ($sembrar == 1) {
            $owner = 0;
            $this->cont_sembrar = 0;
            for ($i = 1; $i <= $arr_compra["sembrar"]; $i++) {
                $this->cont_sembrar = $this->cont_sembrar + 1;
                $posc = $arr_posc[$i];
                if ($posc == 0) {
                    $posc = $i;                                                               // Si s1,sx es 0 utiliza la posicion standard.
                }

                if ($posc < 0) {                                                               // Cuando es negativo se debe restar el porcentaje al primer valor de compra
                    $posc = -($posc);                                                         // Convierto el valor a positivo para restarlo luego
                    $restar_porcentaje = ($rs_posicion[3] / 100) * $posc;
                    $tmp_compra = $rs_posicion[3] - $restar_porcentaje;
                    $this->precio_comprar = number_format($tmp_compra, $this->config_decimales_cot, '.', '');
                } else {
                    if (!is_int($posc)) {
                        $posc = $i;
                    }
                    $this->precio_comprar = number_format($rs_posicion[15][$posc], $this->config_decimales_cot, '.', '');
                }

                if ($this->precio_comprar <= $rs_posicion[3]) {
                    echo 'entra ok pero owner: ' . $owner;
                    $this->crearOrden(0, 1, 0, $owner, $arr_compra);    //rs_orden es 0 porque solo se utilizara para recompras
                    if ($i == 1) {
                        $owner = $this->last_buy_orden;
                    }
                } else {
                    $sistema->grabarLog(5, 'Precio de compra inferior al mas bajo', 0);
                }
            }
        } else {
            $this->precio_comprar = number_format($rs_posicion[3], $this->config_decimales_cot, '.', '');
            echo '<br><hr>Precio comprar: ' . $this->precio_comprar . ' | Posc 3: ' . $rs_posicion[3] . ' | Cant decimales: ' . $this->config_decimales_cot;
            //$this->precio_comprar=$this->precio_comprar-($this->precio_comprar/100 * 0.7);
            //$this->precio_comprar=number_format($this->precio_comprar,$this->config_decimales_cot,'.','');
            $this->crearOrden(0, 1, 0, 0, $arr_compra);    //rs_orden es 0 porque solo se utilizara para recompras
        }
        //echo 'llegaaaa';
    }


    private function crearOrden($id_orden, $mercado, $rs_orden, $owner, $arr_compra)
    { //Si es 0 crea orden mysql si es mayor actualiza la orden mysql -
        global $db;
        global $api;
        global $log;
        global $sistema;
        global $log_limit;
        global $rs_estrategias;
        global $sub_entrada;
        global $cl_soportes;

        if (!is_numeric($owner)) {
            $owner = 0;
        }
        if ($id_orden >= 1) {
            $cantidad = $rs_orden->comprar_cantidad - $rs_orden->executedQty;
        } else {
            $cantidad = $this->obtenerCantidad($this->monto, $this->precio_comprar);
        }
        $log["log"] .= $cantidad . ' ' . $this->precio_comprar;

        if ($id_orden == 0) {
            $arr_info_macd = $this->obtenerInfoMacd();
            if ($this->emulacion == 1) {
                global $camino;
                $fecha_creacion = "'" . $camino->fecha_vela . "'";
            } else {
                $fecha_creacion = "now()";
            }

            $venta_inmediata = 0;
            if ($rs_estrategias->arr_instrumentacion[$arr_compra["estrategia"]]["instrumentacion"] == 1) {  // si instrumentacion activa
                $sx = $this->cont_sembrar;
                if ($sx == 0 or !is_numeric($sx)) {
                    $sx = 1;
                }
                if ($rs_estrategias->arr_instrumentacion[$arr_compra["estrategia"]]["s" . $sx . "v"] > 0) {
                    $venta_inmediata = $rs_estrategias->arr_instrumentacion[$arr_compra["estrategia"]]["s" . $sx . "v"];
                }
            }

            if (!is_numeric($arr_compra["sub_entrada"])) {
                $sub_entrada = 0;
            } else {
                $sub_entrada = $arr_compra["sub_entrada"];
            }
            $orderid = 0;//
            $clientOrderId = '';//
            $status = 'buyaunnogenerada';

            //logs
            $buy_soportes = $cl_soportes->arr_soportes_inf[0]["nivel"] . '-' . $cl_soportes->arr_soportes_inf[1]["nivel"] . '-' . $cl_soportes->arr_soportes_inf[2]["nivel"] . '|' . $cl_soportes->arr_soportes_sup[0]["nivel"] . '-' . $cl_soportes->arr_soportes_sup[1]["nivel"] . '-' . $cl_soportes->arr_soportes_sup[2]["nivel"];            //


            $sql = "INSERT INTO inver_ordenes (sembrada, bot_creador,emulacion,simulacion,btc_actual, inversion_usd,esbot,simbolo2, simbolo, simbolo_id, comprar_cantidad, comprar_cantidad_orig,vel_compra,vel_venta, comprar_precio,estrategia_base, estrategia_compra, comprar_tipo, comprar_intentos, fee, estado,orderid,clientOrderId,price,status,manual,fecha_creacion,fecha_creacion_orig,fecha_finalizada,entrada,macdh,macdm,macdd,log_limit,no_limita,venta_inmediata,sub_entrada,sembrar_owner, log_buy_soportes) 
			VALUES 
			($this->cont_sembrar, " . $this->bot_activo . "," . $this->emulacion . "," . $this->simulacion . ",'0','" . $this->monto . "'," . $this->modobot . ",'" . $this->simbolo2 . "','" . $this->simbolos . "', $this->id,'$cantidad', '$cantidad',$this->velocidad,1, '$this->precio_comprar'," . $arr_compra["estrategia"] . "," . $arr_compra["entrada"] . ",1,1, '1', -1,$orderid,'$clientOrderId','$this->precio_comprar','$status', $this->ahora_comprar,$fecha_creacion, $fecha_creacion,now()," . $arr_compra["entrada"] . ",'" . $arr_info_macd["macdh"] . "', '" . $arr_info_macd["macdm"] . "', '" . $arr_info_macd["macdd"] . "','" . $log_limit . "'," . $arr_compra["no_limita"] . ",'" . $venta_inmediata . "'," . $sub_entrada . "," . $owner . ",'" . $buy_soportes . "')";
            echo 'sqlCompra: <br>' . $sql . '<br>';
            $db->cargar_sql($sql);
            if ($db->ejecutar_sql()) {
                $last_id = $db->getLastID();
                $this->last_buy_orden = $last_id;


                $sistema->grabarLog(8, 'Compra cumplidos: <strong>' . $arr_compra["comprar_obj"] . '</strong>', $last_id);
                $sistema->grabarLog(8, 'Cot compra: <strong>' . $this->last_close . '</strong>', $last_id);


                $sistema->grabarLog(8, 'Orden de compra generada', $last_id);
                if ($this->simulacion == 0) {
                    $order = $api->buy($this->simbolos, $cantidad, $this->precio_comprar);
                    $orderid = $order["orderId"];
                    $clientOrderId = $order["clientOrderId"];
                    $price = $order["price"];
                    $status = $order["status"];
                }
                if ($this->simulacion == 1) {
                    $orderid = 1;
                    $clientOrderId = '000000000';
                    $price = $this->precio_comprar;
                    $status = $order["status"];
                    $order["orderId"] = 1;
                }
                if ($order["orderId"] >= 1) {
                    $sql = "UPDATE inver_ordenes SET estado=1,orderid=" . $orderid . ",clientOrderId='" . $clientOrderId . "',price='" . $price . "' WHERE id_orden=" . $last_id . " LIMIT 1";
                    $db->cargar_sql($sql);
                    if ($db->ejecutar_sql()) {
                        $sistema->grabarLog(8, 'Añadida buy order correctamente ' . $orderid, $last_id);
                    } else {
                        $sistema->grabarLog(8, 'Fallo al actualizar buy orden creada', $last_id);
                    }
                } else {
                    print_r($order);
                    $sistema->grabarLog(5, 'Compra - Error Generar orden ' . $this->simbolos . ' Cant: ' . $cantidad . ' Precio: ' . $this->precio_comprar, 0);
                    $log["mensajes"] .= '- ERROR Generar orden Cant: ' . $cantidad . ' Precio: ' . $this->precio_comprar;
                    //$this->suspenderCompras();
                    //$log["mensajes"].=' - Error al crear orden';
                    if ($order["code"] == '-2010') {
                        $log["mensajes"] .= '  - fondos insuficientes';
                        $sistema->grabarLog(4, 'Compra', 0);
                    } else {
                        $log["mensajes"] .= '  - otros: Cant: ' . $cantidad . ' Precio: ' . $this->precio_comprar;
                        $sistema->grabarLog(4, 'Compra - otros: Cant: ' . $cantidad . ' Precio: ' . $this->precio_comprar, 0);
                    }
                }


            } else {
                echo '<br>' . $sql . '<br>';
                $sistema->grabarLog(8, 'error al crear orden', 0);
                // Mensaje de error al añadir orden de compra a mysql
            }
        } else {
            // Actualiza orden mysql con nuevo montos de la orden real
            //$sql="UPDATE inver_ordenes SET comprar_cantidad='$cantidad', comprar_precio='$this->precio_comprar', comprar_intentos=comprar_intentos +1,price='$price',orderId=$orderid WHERE id_orden=" . $id_orden . " LIMIT 1";
            $sql = "UPDATE inver_ordenes SET comprar_precio='$this->precio_comprar', comprar_intentos=comprar_intentos +1,orderId=$orderid WHERE id_orden=" . $id_orden . " LIMIT 1";
            $db->cargar_sql($sql);
            $log["log"] .= '<br>--------------------crear orden act:  ' . $sql;
            if ($db->ejecutar_sql()) {

            } else {

            }
        }
        $this->resetAhora();
    }


    private function obtenerInfoMacd()
    {
        global $rs_minutos;
        global $rs_horas;
        global $rs_dias;

        $arr = array();
        $tipo_operacion2 = " Vender";
        if ($rs_horas->arr_macds[0]["secuencia_tipo"] == 1) {
            $tipo_operacion2 = " Comprar";
        }
        $arr["macdh"] = $rs_horas->arr_macds[0]["lado"] . ' ' . $rs_horas->arr_macds[0]["secuencia_cant"] . $tipo_operacion2 . ' | Vcolor ' . $rs_horas->arr_macds[0]["velas_color"];

        $tipo_operacion2 = " Vender";
        if ($rs_minutos->arr_macds[0]["secuencia_tipo"] == 1) {
            $tipo_operacion2 = " Comprar";
        }
        $arr["macdm"] = $rs_minutos->arr_macds[0]["lado"] . ' ' . $rs_minutos->arr_macds[0]["secuencia_cant"] . $tipo_operacion2 . ' | Vcolor ' . $rs_minutos->arr_macds[0]["velas_color"];

        $tipo_operacion2 = " Vender";
        if ($rs_dias->arr_macds[0]["secuencia_tipo"] == 1) {
            $tipo_operacion2 = " Comprar";
        }
        $arr["macdd"] = $rs_dias->arr_macds[0]["lado"] . ' ' . $rs_dias->arr_macds[0]["secuencia_cant"] . $tipo_operacion2 . ' | Vcolor ' . $rs_dias->arr_macds[0]["velas_color"];

        return $arr;
    }


    private function obtenerCantidad($monto, $cot)
    { // monto en dolares / cotizacion
        global $sistema;
        global $arr_moneda;

        if ($arr_moneda["conv_btc"] != '') {
            $cantidad = $this->cantidadConBtc($monto, $cot);
        } else {
            $cant_decimales_compra = $this->config_decimales_compra;
            $cant_decimales = $this->config_decimales_cot;
            $cot2 = $cot;
            $monto = number_format($monto, $cant_decimales, '.', '');
            $cot = number_format($cot, $cant_decimales, '.', '');
            $cantidad = ($monto / $cot);


            $cantidad = number_format($cantidad, $cant_decimales_compra, '.', '');
            echo '<br>Cantdad: ' . $cantidad . ' - Cant decimales: ' . $cant_decimales . '- Monto: ' . $monto . ' Cotizacion: ' . $cot;


            if ($cantidad == 0 or !is_numeric($cantidad)) {
                $sistema->grabarLog(8, 'ERROR cantidad: Cantidad: ' . $cantidad . ' - Cant decimales: ' . $cant_decimales . '- Monto: ' . $monto . ' Cotizacion: ' . $cot . ' cot2: ' . $cot2, 0);
            }
        }
        return $cantidad;
    }


    private function cantidadConBtc($monto, $cot)
    { // monto en dolares / cotizacion
        global $sistema;
        global $arr_moneda;

        $cot = $arr_moneda["conv_btc"];             // convierto la cotizacion a dolares para comprar.
        $cant_decimales_compra = $this->config_decimales_compra;
        $cant_decimales = $this->config_decimales_cot;
        $cot2 = $cot;
        $monto = number_format($monto, $cant_decimales, '.', '');
        $cot = number_format($cot, $cant_decimales, '.', '');
        $cantidad = ($monto / $cot);

        $cantidad = number_format($cantidad, $cant_decimales_compra, '.', '');
        echo '<br>Cantdad: ' . $cantidad . ' - Cant decimales: ' . $cant_decimales . '- Monto: ' . $monto . ' Cotizacion: ' . $cot;

        if ($cantidad == 0 or !is_numeric($cantidad)) {
            $sistema->grabarLog(8, 'BTC ERROR cantidad: Cantidad: ' . $cantidad . ' - Cant decimales: ' . $cant_decimales . '- Monto: ' . $monto . ' Cotizacion: ' . $cot . ' cot2: ' . $cot2 . ' conv_btc: ' . $arr_moneda["conv_btc"], 0);
        } else {
            $sistema->grabarLog(8, 'OK BTC ERROR cantidad: Cantidad: ' . $cantidad . ' - Cant decimales: ' . $cant_decimales . '- Monto: ' . $monto . ' Cotizacion: ' . $cot . ' cot2: ' . $cot2 . ' conv_btc: ' . $arr_moneda["conv_btc"], 0);
        }

        return $cantidad;
    }


    public function resetAhora()
    {
        echo 'llegaaaa' . $this->id;
        if ($this->ahora_comprar == 1) {
            global $db;
            $sql = "UPDATE inver_cartera SET ahora_comprar=0 WHERE id_mon=" . $this->id . " LIMIT 1";
            $db->cargar_sql($sql);
            $db->ejecutar_sql();
            $this->estrategia_base = 16;
            $this->estrategia = 52;
        }
    }

    private function suspenderCompras()
    {
        global $db;
        $sql = "UPDATE inver_cartera SET comprar=0 WHERE id_mon=" . $this->id . " LIMIT 1";
        $db->cargar_sql($sql);
        $db->ejecutar_sql();
    }

// fin compras


// Monitorear


    public function monitorear($rs_ordenes)
    {
        global $arr_operacion;
        global $arr_moneda;
        global $arr_comprar;
        global $arr_vender;
        global $api;
        global $db;
        global $sistema;
        global $_GET;
        global $emulando;
        global $rs_minutos;
        global $modo_filled;
        $permiso_cron = 0;
        //$permiso_cron=1;
        //$permiso_cron=1;
        if ($sistema->temporizador == 20 or $sistema->temporizador == 50 or $arr_moneda["cotizacion"] < $arr_comprar["precio_comprar"] or $this->velocidad == 2) {
            $permiso_cron = 1;
        } else {
            echo 'Tareas pendientes...Esperando cron 20 o 50';
        }
        if ($_GET["permisos"] == 1 or $emulando == 1) {
            $permiso_cron = 1;
            echo '<br>Permiso brindado';
        }

        foreach ($rs_ordenes as $orden) {
            $orderstatus["status"] = '';
            $orderstatus["executedQty"] = 0;
            $orderstatus["price"] = '';
            $ejecutado = 0;


//orden de compra		------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------		
//              		------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            if ($orden->estado == 1 and ($permiso_cron == 1 or $this->simulacion == 1)) {
                $orderid = $orden->orderId;
                if ($this->simulacion == 0) {
                    $orderstatus = $api->orderStatus($this->simbolos, $orderid);
                }
                if ($this->simulacion == 1 and ($this->last_close <= $orden->comprar_precio or $modo_filled == 1)) {
                    $orderstatus["status"] = 'FILLED';
                    $orderstatus["executedQty"] = $orden->comprar_cantidad;
                }

                $porc_tmp = porc2Cotiz($orden->comprar_precio, $this->last_close, 0);
                if ($rs_minutos->arr_macds[0]["lado"] == 'Pos' and $rs_minutos->arr_macds[0]["velas_color"] >= 3 and $porc_tmp >= 2) {
                    if (@$this->cancelarOrden($orden->orderId, $orden->id_orden)) {
                        $sistema->grabarLog(2, 'Alejamiento de compra1 porc: ' . $porc_tmp, $orden->id_orden);
                        $this->ordenActEstado($orden->id_orden, 0); // Estado 0 es borrar
                    }
                }

                switch ($orderstatus["status"]) {
                    case 'FILLED':

                        break;
                    case 'PARTIALLY_FILLED':

                        break;
                    case 'CANCELED':
                        if (@$this->cancelarOrden($orderid, $orden->id_orden)) {
                            $this->ordenActEstado($orden->id_orden, 0);
                            $sistema->grabarLog(3, 'Orden de compra cancelada', $orden->id_orden);
                        } else {
                            $sistema->grabarLog(3, 'error cancelando orden al comprar: OrderId: ' . $orderid . ' Orden id: ' . $orden->id_orden, $orden->id_orden);
                        }
                        break;
                }

                if ($orderstatus["status"] == 'FILLED' or $orderstatus["status"] == 'PARTIALLY_FILLED') {
                    $cant_total = $orden->comprar_cantidad;
                    $ejecutado = $orderstatus["executedQty"];
                    if ($this->simulacion == 1) {
                        $ejecutado = $cant_total;
                    }
                    $order_ejecutado = $orden->executedQty;

                    $paso1 = 100 * $order_ejecutado / $cant_total;
                    $dat_filled = number_format($paso1, 2);

                    $filled = $ejecutado - $order_ejecutado;
                    $total_ejecutado = $orderstatus["executedQty"] + $ejecutado;
                    if ($filled > 0) {
                        $sql = "INSERT INTO inver_ordenes_compras (id_orden, order_id, cotizacion, cantidad, status) VALUES (" . $orden->id_orden . "," . $orderid . ", '" . $orderstatus["price"] . "','" . $orderstatus["executedQty"] . "', '" . $orderstatus["status"] . "')";
                        $db->cargar_sql($sql);
                        if ($db->ejecutar_sql()) {
                            $sql = "UPDATE inver_ordenes SET executedQty='" . $total_ejecutado . "' WHERE orderId=" . $orderid . " LIMIT 1";
                            $sistema->grabarLog(8, 'Compra Filled: ' . $filled . ' de ' . $cant_total, $orden->id_orden);
                            $db->cargar_sql($sql);
                            $db->ejecutar_sql();
                        }
                    }
                }
                if ($ejecutado >= $orden->comprar_cantidad and $ejecutado > 0 and $orderstatus["status"] == 'FILLED' or ($orderstatus["status"] == 'PARTIALLY_FILLED' and porc2Cotiz($rs_orden->comprar_precio, $this->last_close, 0) >= 2)) {       // SE LLEGO A COMPRAR TODO
                    if ($this->emulacion == 1) {
                        global $camino;
                        $fecha_creacion = "'" . $camino->fecha_vela . "'";
                    } else {
                        if ($orden->simulacion == 1) {
                            $fecha_creacion = "now()";
                        } else {
                            $unix_timestamp = $orderstatus["updateTime"];
                            $unix_timestamp = number_format($unix_timestamp / 1000, 0, "", "");
                            $datetime = new DateTime("@$unix_timestamp");
                            $date_time_format = $datetime->format('Y-m-d H:i:s');
                            $time_zone_from = "UTC";
                            $time_zone_to = 'America/Argentina/Buenos_Aires';
                            $display_date = new DateTime($date_time_format, new DateTimeZone($time_zone_from));
                            // Date time with specific timezone
                            $display_date->setTimezone(new DateTimeZone($time_zone_to));
                            $fecha_creacion = "'" . $display_date->format('Y-m-d H:i:s') . "'";
                        }

                    }
                    $sql = "UPDATE inver_ordenes SET estado=2, fecha_creacion=" . $fecha_creacion . ",executedQty='" . $orderstatus["executedQty"] . "' WHERE orderId=" . $orderid . " and estado=1 LIMIT 1";
                    $db->cargar_sql($sql);
                    $db->ejecutar_sql();
                    $sistema->grabarLog(8, 'Compra Filled COMPLETA', $orden->id_orden);
                }
            }


            $orderstatus["status"] = '';
            $orderstatus["executedQty"] = 0;
            $orderstatus["price"] = '';
            $ejecutado = 0;


//MONITOREAR VENDIENDO  ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------		
//              		------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            if ($orden->estado == 3 and ($permiso_cron == 1 or $orden->simulacion == 1)) {
                $orderstatus["status"] = '';
                if ($orden->simulacion == 0) {
                    $orderid = $orden->venderOrderid;
                    $orderstatus = $api->orderStatus($this->simbolos, $orderid);
                    echo '<br>arr_orderstatus --------------------------------------<br>';
                    print_r($orderstatus);

                }
                if ($orden->simulacion == 1 and ($this->last_close >= $orden->vender_precio or $modo_filled == 1)) {
                    $orderid = $orden->venderOrderid;
                    $orderstatus["status"] = 'FILLED';
                    $orderstatus["executedQty"] = $orden->comprar_cantidad;
                    $orderstatus["price"] = $this->last_close;
                }


                if ($orden->venta_inmediata == 0 and porc2Cotiz($orden->vender_precio, $this->last_close, 0) <= -2) {
                    $sistema->grabarLog(3, 'cancel orden: ' . porc2Cotiz($orden->vender_precio, $this->last_close, 0) . ' VenderPrecion: ' . $orden->vender_precio . ' Last close: ' . $this->last_close, $orden->id_orden);
                    if ($this->cancelarOrden($orden->venderOrderid, $orden->id_orden)) {
                        $this->ordenActEstado($orden->id_orden, 2); // Estado 0 es borrar
                        $sistema->grabarLog(3, 'Alejamiento Venta1 Porc neg: ' . porc2Cotiz($orden->vender_precio, $this->last_close, 0) . ' VenderPrecion: ' . $orden->vender_precio . ' Last close: ' . $this->last_close, $orden->id_orden);
                    }
                }


                if ($orden->venta_inmediata > 0 and porc2Cotiz($orden->vender_precio, $this->last_close, 0) <= -1) {
                    if ($this->cancelarOrden($orden->venderOrderid, $orden->id_orden)) {
                        $sistema->grabarLog(3, 'Alejamiento Venta2 - Conversion de inmediata a normal', $orden->id_orden);
                        $this->inmediataDesactiva($rs_orden->id_orden);
                        $this->ordenActEstado($rs_orden->id_orden, 2); // Estado 0 es borrar

                    }
                }


                switch ($orderstatus["status"]) {
                    case 'FILLED':

                        break;
                    case 'PARTIALLY_FILLED':

                        break;
                    case 'CANCELED':
                        if (@$this->cancelarOrden($orden->venderOrderid, $orden->id_orden)) {
                            $this->ordenActEstado($orden->id_orden, 2);
                            $sistema->grabarLog(3, 'Estado 3 pero la orden estaba cancelada', $orden->id_orden);
                        } else {
                            $sistema->grabarLog(3, 'error cancelando orden: vOrderId: ' . $orden->venderOrderid . ' Orden id: ' . $orden->id_orden, $orden->id_orden);
                        }
                        break;
                }


                if ($orderstatus["status"] == 'FILLED' or $orderstatus["status"] == 'PARTIALLY_FILLED') {
                    $cant_total = $orden->comprar_cantidad;
                    $ejecutado = $orderstatus["executedQty"];
                    $order_ejecutado = $orden->venderexecutedQty;
                    $filled = $ejecutado - $order_ejecutado;
                    $total_ejecutado = $order_ejecutado + $orderstatus["executedQty"];

                    $rs_saldo_final = $this->saldoFinal($orden);
                    if ($filled > 0) {
                        $sql = "INSERT INTO inver_ordenes_ventas (id_orden,order_id, cotizacion, cantidad, status) VALUES (" . $orden->id_orden . "," . $orderid . ", '" . $orderstatus["price"] . "','" . $orderstatus["executedQty"] . "', '" . $orderstatus["status"] . "')";
                        $db->cargar_sql($sql);
                        if ($db->ejecutar_sql()) {
                            $sql = "UPDATE inver_ordenes SET venderexecutedQty='" . $total_ejecutado . "' WHERE id_orden=" . $orden->id_orden . " LIMIT 1";
                            $db->cargar_sql($sql);
                            $db->ejecutar_sql();
                            $sistema->grabarLog(8, 'Venta filled ' . $ejecutado . ' de ' . $orden->comprar_cantidad, $orden->id_orden);
                        }
                    }
                }

                if ($orderstatus["status"] == 'FILLED' or ($orderstatus["status"] == 'PARTIALLY_FILLED' and porc2Cotiz($rs_orden->vender_precio, $this->last_close, 0) <= -2)) {       // SE LLEGO A VENDER TODO
                    if ($this->emulacion == 1) {
                        global $camino;
                        $fecha_finalizada = "'" . $camino->fecha_vela . "'";
                    } else {

                        if ($orden->simulacion == 1) {
                            $fecha_finalizada = "now()";
                        } else {
                            $unix_timestamp = $orderstatus["updateTime"];
                            $unix_timestamp = number_format($unix_timestamp / 1000, 0, "", "");
                            $datetime = new DateTime("@$unix_timestamp");
                            // Display GMT datetime
                            $date_time_format = $datetime->format('Y-m-d H:i:s');
                            $time_zone_from = "UTC";
                            $time_zone_to = 'America/Argentina/Buenos_Aires';
                            $display_date = new DateTime($date_time_format, new DateTimeZone($time_zone_from));
                            // Date time with specific timezone
                            $display_date->setTimezone(new DateTimeZone($time_zone_to));
                            $fecha_finalizada = "'" . $display_date->format('Y-m-d H:i:s') . "'";
                        }


                        //echo '<br>Fecha finalizada: ' . $fecha_finalizada . ' ----';

                    }

                    if ($arr_moneda["conv_btc"] > 0) {
                        $sql_cot_btc = ", cot_btc='" . $arr_moneda["conv_btc"] . "'";
                    }
                    $sql = "UPDATE inver_ordenes SET estado=4,fecha_finalizada=" . $fecha_finalizada . ", saldo='" . $rs_saldo_final["ganancia"] . "', fee='" . $rs_saldo_final["fee"] . "',venderexecutedQty='" . $orderstatus["executedQty"] . "'" . $sql_cot_btc . " WHERE id_orden=" . $orden->id_orden . " and estado=3 LIMIT 1";

                    echo 'ssql: ' . $sql;
                    $db->cargar_sql($sql);
                    $db->ejecutar_sql();
                    $sistema->grabarLog(8, 'Venta filled COMPLETA, ejecutado: ' . $ejecutado . ' comprar cant: ' . $orden->comprar_cantidad, $orden->id_orden);
                    $sql = "UPDATE inver_cartera SET seg_hora=now() WHERE id_mon=" . $this->id . " LIMIT 1";
                    $db->cargar_sql($sql);
                    $db->ejecutar_sql();
                }


            }


//MONITOREAR VENTA INMEDIATA--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------		
//              		------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------								
            if ($orden->estado == 2 and $orden->venta_inmediata > 0) {
                $sistema->grabarLog(8, 'Pasa a estado 12 - Venta Inmediata', $orden->id_orden);
                $sql = "UPDATE inver_ordenes SET estado=12, salida=106 WHERE id_orden=" . $orden->id_orden . " LIMIT 1";  // 106 es la salida de Venta Inmediata, esta en estrategias manuales
                $db->cargar_sql($sql);
                $db->ejecutar_sql();
                $orden->estado = 12;
            }


//VENDER ORDEN 		------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------		
//              		------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------				
            if ($orden->suspendida == 0 and $orden->estado == 12 or ($orden->suspendida == 0 and $orden->estado == 2 and $this->vender_orden_id == $orden->id_orden and 1 == 2)) { // Crear orden de venta
                global $moneda;
                if ($this->emulacion == 1) {
                    $rs_posicion = analizarPosicion_emulador(1, $moneda->last_close);
                } else {
                    $rs_posicion = analizarPosicion(1, $cotizacion);
                }
                //$rs_posicion=analizarPosicion(1,$cotizacion);
                if ($orden->venta_inmediata == 0) {
                    $precio_vender = number_format($rs_posicion[12], $this->config_decimales_cot, '.', '');; // Es el valor del primer lugar de venta
                } else {
                    $precio_vender = $orden->comprar_precio + ($orden->comprar_precio / 100) * $orden->venta_inmediata;
                    $precio_vender = number_format($precio_vender, $this->config_decimales_cot, '.', '');;
                }

                $no_valida = 0;
                $sistema->grabarLog(8, 'Precio vender: <strong>' . $precio_vender . '</strong>', $orden->id_orden);
                //	echo '<br> -- Vendiendo, precio: ' . $precio_vender;
                //$precio_vender=$precio_vender+($precio_vender/100 * 1);
                if ($precio_vender > 0 and $precio_vender >= $rs_posicion[12]) {

                } else {
                    if ($rs_posicion[12] > 0) {
                        $precio_vender = $rs_posicion[12];
                        echo 'error, precio de venta inferior al primer lugar del libro de ventas, se actualizo el precio de venta';
                        $sistema->grabarLog(8, 'Aumento de precio venta a primer lugar de libro ventas porque el valor era inferior', $orden->id_orden);
                    } else {
                        $no_valida = 1;
                    }


                }
                if ($no_valida == 0) {
                    $this->venderOrden($orden->comprar_cantidad, $precio_vender, $orden->id_orden, 1, $orden);         //El primer valor de las venta
                } else {
                    $sistema->grabarLog(8, 'No validan datos de venta', $orden->id_orden);
                }


            }
        }

    }


    private function recomprar($recomprar, $orderid, $rs_orden)
    {              //Recomprar 0-1 si es 0 no hace nada.
        global $arr_comprar;
        global $rs_minutos;
        global $log;
        global $api;
        global $LIMIT_ORDER;
        global $posicion;
        global $sistema;
        $resultado = 1;

        $cotizacion = $rs_orden->comprar_precio;


        if ($this->emulacion == 1) {
            $rs_posicion = analizarPosicion_emulador(1, $this->last_close);
        } else {
            $rs_posicion = analizarPosicion(1, $cotizacion);
        }
        //	$rs_posicion=analizarPosicion(1,$cotizacion);
        $arr_comprar["precio_comprar"] = $rs_posicion[3]; // Es el valor del primer lugar de venta

        //$recomprar=2;//$ORDEN_CASCADA;
        if ($recomprar == 2) {
            if ($rs_posicion[1] >= 2 or !is_numeric($rs_posicion[1])) {
                // Cancelar y crear nueva orden, actualizar datos en la db.
                $sistema->grabarLog(2, 'Alejamiento de compra2', $rs_orden->id_orden);
                if (@$this->cancelarOrden($rs_orden->orderId, $rs_orden->id_orden)) {
                    //if (crearOrden($arr_comprar["monto_usd"], $arr_comprar["precio_comprar"], $rs_orden->id_orden,$LIMIT_ORDER,$btn_comprar,$rs_orden)){
                    if ($this->crearOrden(0, 1, 0, 0)) {

                    } else {
                        $resultado = 0;
                    }
                }
            }
        } else {

            $posicion = $rs_posicion[1];                                                                                                 // Si se alejo mas de 10 lugares cancelar orden
            //$log["mensajes"]='llega' . $posicion;
            //echo '<br>La posicion es: ' . $rs_posicion[1];

            $limite = 6;
            if ($rs_orden->sembrada >= 2) {
                $limite = $rs_orden->sembrada + 6;
            }

            if ($rs_minutos->arr_macds[0]["lado"] == 'Pos' and $rs_minutos->arr_macds[0]["velas_color"] >= 4 and porc2Cotiz($orden->comprar_precio, $this->last_close, 0) >= 1) {
                if ($rs_posicion[1] > $limite or !is_numeric($rs_posicion[1])) {
                    if (@$this->cancelarOrden($rs_orden->orderId, $rs_orden->id_orden)) {
                        $sistema->grabarLog(2, 'Alejamiento de compra3', $rs_orden->id_orden);
                        $this->ordenActEstado($rs_orden->id_orden, 0); // Estado 0 es borrar
                    } else {
                        //No se encontro orden y se cancela la operacion
                        //$sistema->grabarLog(8, 'Orden a 0 Alejamiento compra', $rs_orden->id_orden);
                        //$this->ordenActEstado($rs_orden->id_orden,0); // Estado 0 es borrar
                    }
                }
            }
        }
        if ($resultado == 1) {
            return true;
        } else {
            return false;
        }
    }

    private function revender($revender, $orderid, $rs_orden)
    {              //revender 0-1 si es 0 no hace nada.
        global $log;
        global $api;
        global $posicion;
        global $sistema;
        $resultado = 1;
        $cotizacion = $rs_orden->vender_precio;

        if ($this->emulacion == 1) {
            $rs_posicion = analizarPosicion_emulador(2, $this->last_close);
        } else {
            $rs_posicion = analizarPosicion(2, $cotizacion);
        }

        //$rs_posicion=analizarPosicion(2,$cotizacion);
        $vender_precio = $rs_posicion[12]; // Es el valor del primer lugar de venta
        //$revender=2;
        if ($revender == 2) {
            if ($rs_posicion[1] >= 2 or !is_numeric($rs_posicion[1])) {
                $log["mensajes"] = 'Re vendiendo moneda';
                $sistema->grabarLog(2, 'Cancelacion para reventa', $rs_orden->id_orden);
                if ($this->cancelarOrden($rs_orden->venderOrderid, $rs_orden->id_orden)) {
                    //$vender_precio=$vender_precio+($vender_precio/100 * 0.6);
                    if ($this->venderOrden($rs_orden->comprar_cantidad, $vender_precio, $rs_orden->id_orden, 1, $rs_orden)) {

                    } else {
                        $resultado = 0;
                    }
                }
            }
        } else {
            $posicion = $rs_posicion[1];
            //echo '<br>La posicion es:' . $posicion;                                                                           // Si se alejo mas de 10 lugares cancelar orden
            if ($rs_posicion[1] >= 10 or !is_numeric($rs_posicion[1]) and $rs_orden->venta_inmediata == 0 and porc2Cotiz($rs_orden->vender_precio, $this->last_close, 0) <= -2) {
                $sistema->grabarLog(3, 'cancel orden: ' . porc2Cotiz($rs_orden->vender_precio, $this->last_close, 0) . ' VenderPrecion: ' . $rs_orden->vender_precio . ' Last close: ' . $this->last_close, $rs_orden->id_orden);
                if ($this->cancelarOrden($rs_orden->venderOrderid, $rs_orden->id_orden)) {
                    $this->ordenActEstado($rs_orden->id_orden, 2); // Estado 0 es borrar
                    if (!is_numeric($rs_posicion[1])) {
                        $detalle = 'rs_posicion sin valor';
                    }
                    $sistema->grabarLog(3, 'Alejamiento Venta3 Porc neg: ' . porc2Cotiz($rs_orden->vender_precio, $this->last_close, 0) . ' VenderPrecion: ' . $rs_orden->vender_precio . ' Last close: ' . $this->last_close, $rs_orden->id_orden);
                }
            }


            if ($rs_orden->venta_inmediata > 0 and porc2Cotiz($rs_orden->vender_precio, $this->last_close, 0) <= -3) {
                // Se alejo el precio de una orden inmediata. Quitare el modo inmediato para evitar vender en grandes subidas
                if ($this->cancelarOrden($rs_orden->venderOrderid, $rs_orden->id_orden)) {
                    $sistema->grabarLog(3, 'Alejamiento Venta4 - Conversion de inmediata a normal', $rs_orden->id_orden);
                    $this->inmediataDesactiva($rs_orden->id_orden);
                    $this->ordenActEstado($rs_orden->id_orden, 2); // Estado 0 es borrar

                }
            }

        }

        if ($resultado == 0) {
            return false;
        } else {
            return true;
        }
    }

    private function inmediataDesactiva($id_orden)
    {
        global $sistema;
        global $db;

        $sql = "UPDATE inver_ordenes SET venta_inmediata=0 WHERE id_orden=" . $id_orden . " LIMIT 1";
        $sistema->grabarLog(3, 'Ingresa a desactivar ' . $sql, $id_orden);
        $db->cargar_sql($sql);
        if ($db->ejecutar_sql($sql)) {
            $sistema->grabarLog(3, 'Orden inmediata desactivada', $id_orden);
        }
    }

    private function cancelarOrden($id, $id_orden)
    {
        global $api;
        global $log;
        global $sistema;

        $sistema->grabarLog(8, 'Ingresa a cancelar orden id: ' . $id . ' orden: ' . $id_orden, $id_orden);
        if ($id == 1) {
            $sistema->grabarLog(8, 'Orden cancelada SIMU', $id_orden);
            return true;
        }
        try {
            $response = $api->cancel($this->simbolos, $id);
            if ($response["code"] == '-2011') {
                throw new errorCancel("Error al cancelar orden no encontrada.");
            }
        } catch (Exception $e) {
            $sistema->grabarLog(8, $e->getMessage(), $id_orden);
        }
        $orderstatus = @$api->orderStatus($this->simbolos, $id);
        if ($orderstatus["status"] == 'CANCELED' or $orderstatus["status"] == 'NEW') {
            $sistema->grabarLog(8, 'Orden cancelada', $id_orden);
            return true;
        } else {
            $sistema->grabarLog(8, 'Orden cancelada con error status: ' . $orderstatus["status"], $id_orden);
            return false;
        }
    }

    private function ordenActEstado($id_orden, $estado)
    {
        global $db;
        global $sistema;
        echo '<br>llega, id: ' . $id_orden . ' estado: ' . $estado;
        //if ($estado==0){
        $sql_cancel = '';
        if ($estado == 0) {
            $sql_cancel = ", fecha_finalizada=now()";
            if ($this->emulacion == 1) {
                global $camino;
                $sql_cancel = ", fecha_finalizada='" . $camino->fecha_vela . "'";
            }
        }


        $sql = "UPDATE inver_ordenes SET venta_inmediata=0, estado=" . $estado . $sql_cancel . " where id_orden=" . $id_orden . " LIMIT 1";
        echo '<br>' . $sql;
        $sistema->grabarLog(8, 'Orden actualizada a estado ' . $estado, $id_orden);
        $db->cargar_sql($sql);
        if ($db->ejecutar_sql()) {

            return true;
        } else {
            return false;
        }
        //}
    }

    private function saldoFinal($orden)
    {

        $valor_compra = $orden->comprar_precio * $orden->comprar_cantidad;
        $valor_venta = $orden->vender_precio * $orden->comprar_cantidad;
        $cant_decimales = 2;
        $simbolo_moneda = '$';
        //if ($orden->simbolo2=='BTC'){
        //	$cant_decimales=8;
        //	$simbolo_moneda='₿ ';
        //	$valor_compra=($orden->comprar_precio * $arr_moneda["conv_btc"]) * $orden->comprar_cantidad;
        //	$valor_venta=($orden->vender_precio * $arr_moneda["conv_btc"]) * $orden->comprar_cantidad;
        //}
        $fees = number_format((($valor_compra / 100) * 0.075) + (($valor_venta / 100) * 0.075), 3);
        $tot = $valor_venta - $valor_compra;
        $ganancia = number_format($tot, 3);
        $rs["fee"] = $fees;
        $rs["ganancia"] = $ganancia;
        return $rs;
    }

// fin monitorear


// Vender
    public function suspenderOrden($id, $motivo)
    {
        global $db;
        global $sistema;
        $sql = "UPDATE inver_ordenes SET suspendida=1, estado=2 WHERE id_orden=" . $id . " LIMIT 1";
        $sistema->grabarLog(8, 'Orden suspendida: ' . $motivo, $id);
        $db->cargar_sql($sql);
        $db->ejecutar_sql();
    }

    public function venderOrden($cantidad, $valor_venta, $id_orden, $mercado, $rs_orden)
    {
        global $db;
        global $activa;
        global $api;
        global $arr_moneda;
        global $cant_total;
        global $order_ejecutado;
        global $arr_vender;
        global $arr_operacion;
        global $arr_comprar;
        global $sistema;
        global $rs_minutos;
        global $rs_horas;
        global $rs_dias;


        $cant_orig = $cantidad;
        $cantidad = $cantidad - $rs_orden->venderexecutedQty;

        if (($cantidad * $valor_venta) <= 10 and ($cant_orig * $valor_venta) >= 10) {
            $this->suspenderOrden($id_orden, 'El valor es menor a 10 por que algo se ejecuto 1 cant: ' . $cantidad . ' cant_orig: ' . $cant_orig . ' pventa: ' . $valor_venta);
            return false;
        }

        //echo '<br>Valor venta: ' . $valor_venta .'<br>';
        //$valor_venta=$valor_venta + ($valor_venta/100 *2);
        $valor_venta = number_format($valor_venta, $this->config_decimales_cot, '.', '');

        if ($mercado == 1) {
            if ($rs_orden->simulacion == 0) {
                //	echo '<br>Simbolos: ' . $this->simbolos . ' Cantidad: ' . $cantidad . ' Valor venta: ' . $valor_venta . ' Order Id: ' . $id_orden . '<br>';
                try {
                    $order = $api->sell($this->simbolos, $cantidad, $valor_venta);
                    if ($response["code"] == '-2010') {
                        throw new errorVender("Fondos Insuficienter Venta.");
                    }
                } catch (Exception $e) {
                    $sistema->grabarLog(8, $e->getMessage(), $id_orden);
                    $order["code"] = '-2010';
                }


            }
            if ($order["code"] == '-2010') {
                echo 'aca';
                $this->suspenderOrden($id_orden, 'El valor es menor a 10 por que algo se ejecuto 2 cant: ' . $cantidad . ' cant_orig: ' . $cant_orig . ' pventa: ' . $valor_venta);
                $log["mensajes"] .= 'Error al vender - fondos insuficientes';
                $sistema->grabarLog(4, 'Fondos Insuficientes', $rs_orden->id_orden);
                return false;
            }

        }
        if ($mercado == 2) {
            $order = $api->marketSell($this->simbolos, $cantidad);
        }
        if ($mercado == 3 and $this->simulacion == 0) {
            $type = "STOP_LOSS_LIMIT"; // Set the type STOP_LOSS (market) or STOP_LOSS_LIMIT, and TAKE_PROFIT (market) or TAKE_PROFIT_LIMIT
            $quantity = $cantidad;
            $price = $valor_venta + 0.01; // Try to sell it for 0.5 btc
            $valor_venta = $price;
            $stopPrice = $valor_venta - 0.01; // Sell immediately if price goes below 0.4 btc
            $order = $api->sell($this->simbolos, $quantity, $price, $type, ["stopPrice" => $stopPrice]);
        }
        if ($rs_orden->simulacion == 0) {
            $orderid = $order["orderId"];
            $status = $order["status"];
            $sistema->grabarLog(3, 'Orden de venta colocada Binance Id:' . $orderid, $rs_orden->id_orden);
        }
        if ($rs_orden->simulacion == 1) {
            $orderid = 1;
        }
        if ($orderid >= 1) {
            $vel_venta = $this->velocidad;
            if ($this->salida != 0) {
                //$salida=$this->salida;
            } else {
                if ($rs_orden->salida != 0) {
                    //	$salida=$rs_orden->salida;
                } else {
                    //	$salida=999;  salida=$salida,
                }

            }


            $tipo_operacion2 = " Vender";
            if ($rs_dias->arr_macds[0]["secuencia_tipo"] == 1) {
                $tipo_operacion2 = " Comprar";
            }
            $txtmacdd = $rs_dias->arr_macds[0]["lado"] . ' ' . $rs_dias->arr_macds[0]["secuencia_cant"] . $tipo_operacion2 . ' | Vcolor ' . $rs_dias->arr_macds[0]["velas_color"];


            $tipo_operacion2 = " Vender";
            if ($rs_horas->arr_macds[0]["secuencia_tipo"] == 1) {
                $tipo_operacion2 = " Comprar";
            }
            $txtmacdh = $rs_horas->arr_macds[0]["lado"] . ' ' . $rs_horas->arr_macds[0]["secuencia_cant"] . $tipo_operacion2 . ' | Vcolor ' . $rs_horas->arr_macds[0]["velas_color"];


            $tipo_operacion2 = " Vender";
            if ($rs_minutos->arr_macds[0]["secuencia_tipo"] == 1) {
                $tipo_operacion2 = " Comprar";
            }
            $txtmacdm = $rs_minutos->arr_macds[0]["lado"] . ' ' . $rs_minutos->arr_macds[0]["secuencia_cant"] . $tipo_operacion2 . ' | Vcolor ' . $rs_minutos->arr_macds[0]["velas_color"];
            $sql = "UPDATE inver_ordenes SET venderOrderid=$orderid, estado=3,vender_status='$status',vender_cantidad='$cantidad', vel_venta=$vel_venta, vender_precio='$valor_venta', vender_intentos=vender_intentos +1,
			macdh_salida='" . $txtmacdh . "', macdm_salida='" . $txtmacdm . "', macdd_salida='" . $txtmacdd . "'   
			WHERE id_orden=" . $id_orden . " LIMIT 1";
            echo '<br>saliendo: ' . $sql . '<br>';
            $db->cargar_sql($sql);
            if ($db->ejecutar_sql()) {
                $sistema->grabarLog(8, 'Paso a estado 3 iniciando venta', $id_orden);
            } else {
            }
            return true;
        } else {
            //if ($order["code"]=='-2010'){
            $log["mensajes"] .= '  - Error al vender: ' . $order["code"] . ' cant: ' . $cantidad . ' Precio: ' . $valor_venta;
            //grabarLog(4, 'Venta Varios code:' . $order["code"],0);
            $sistema->grabarLog(8, 'Error al crear venta a estado 3 en coin class', $id_orden);
            $this->suspenderOrden($id_orden, 'falla al generar ordern de venta');
            //}
            return false;
        }
    }


// fin vender


    public function actMoneda()
    {
        global $db;
        $sql = "UPDATE inver_cartera SET fact=now() WHERE id_mon=" . $this->id . " LIMIT 1";
        $db->cargar_sql($sql);
        $db->ejecutar_sql();
    }

    public function Vender($id_orden)
    {
        $abc = 3;
    }

    private function Config()
    {
        $this->conf->test = 123;
    }
    private function sqlEstrategias()
    {
        global $db;
        $sql = "SELECT * FROM inver_cartera_estrategia WHERE id_cartera=" . $this->id;
        $db->cargar_sql($sql);
        $rs_activas = $db->cargar_avisos();

        $cont = 0;
        if ($rs_activas) {
            foreach ($rs_activas as $estra) {
                if ($cont == 0) {
                    $txt_sql_estra .= 'id_estrategia=' . $estra->id_estrategia;
                } else {
                    $txt_sql_estra .= ' or id_estrategia=' . $estra->id_estrategia;
                }
                $cont = $cont + 1;
            }
        }
        return $txt_sql_estra;
    }
}
// Clases internas

class Config2
{
    public $Decimales = 0;
}

?>
