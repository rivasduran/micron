<?php
/*
Plugin Name: Funciones de CRON
Plugin URI: http://www.jj.com/
description: Plugin creado para consumir la API de Woocommerce y Invu Pos
Version: 1.0.0
Author: Joser
Author URI: http://www.jj.com/
License: GPL2
 */

//AQUI EN ESTA SECCION
require_once "class.relacion-admin.php";

//DEBEMOS INCLUIR EL MENU PARA ACTUALIZAR LOS DATOS
include "menu-actualizador-cron.php";

//ACTUALIZAR MANUAL
include "actualizar_manual_invu.php";

$mi_url_web = get_site_url();

function insertar_productos_web_cr()
{

}

/*
 **
CONSULTAS DE LOS PARAMETROS DE LA API
 **
 */

function consulta_parametros_a1($parametro = "")
{
    global $wpdb;

    $resultado = "";

    if ($parametro == "apikey" || $parametro == "") {
        //CONSULTAMOS EL API KEY DE ESTA CUENTA
        $mi_api  = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mi_api WHERE numero = 1 ");
        $api_key = "";
        foreach ($mi_api as $key) {
            $api_key = $key->nombre;
        }

        $resultado = $api_key;
    } else if ($parametro == "api") {
        //CONSULTAMOS LA VARIABLE DE API DE ESTA CUENTA
        $mi_api  = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mi_api WHERE numero = 2 ");
        $url_api = "";
        foreach ($mi_api as $key) {
            $url_api = $key->nombre;
        }

        $resultado = $url_api;
    }

    return $resultado;
}

add_filter('cron_schedules', 'cyb_cron_schedules');
function cyb_cron_schedules($schedules)
{

    $schedules['1min'] = array(
        //'interval' => 60, // ESTO ES 1 SEGUNDO
        'interval' => 3600, //Esto es una hora
        'display'  => __('Hourly', 'cyb-textdomain'), //nombre del intervalo
    );

    $schedules['30min'] = array(
        //'interval' => 1800, //Esto es media hora
        #600
        //'interval' => 600, //Esto es 10 minutos
        'interval' => 120, //Esto es 2 minutos
        'display'  => __('Hourly', 'cyb-textdomain'), //nombre del intervalo
    );

    $schedules['monthly'] = array(
        'interval' => 2592000, // segundos en 30 dias
        'display'  => __('Monthly', 'cyb-textdomain'), // nombre del intervalo
    );

    return $schedules;
}

register_activation_hook(__FILE__, 'cyb_plugin_activation');
function cyb_plugin_activation()
{

    /*
    if (!wp_next_scheduled('cyb_weekly_cron_job')) {
    wp_schedule_event(current_time('timestamp'), '1min', 'cyb_weekly_cron_job');
    }
     */

    #NUEVO METODO DE ACTUALIZACION
    if (!wp_next_scheduled('actualizar_solo_modificados')) {
        wp_schedule_event(current_time('timestamp'), '1min', 'actualizar_solo_modificados');
    }


    #ALL PRODUCTS
    if (!wp_next_scheduled('actualizar_todo_invu')) {
        //HERMANO QUE RECORRE TODO CADA 30 MINUTOS
        wp_schedule_event(current_time('timestamp'), '30min', 'actualizar_todo_invu');
    }

    #CATEGORIAS
    if (!wp_next_scheduled('crear_Categorias_invu')) {
        #LAS CATEGORIAS
        //wp_schedule_event(current_time('timestamp'), '1min', 'crear_Categorias_invu');
    }

    /*
    if( ! wp_next_scheduled( 'cyb_monthly_cron_job' ) ) {
    wp_schedule_event( current_time( 'timestamp' ), 'monthly', 'cyb_monthly_cron_job' );
    }
     */

    //DEBEMOS REGISTRAR EL ACTUALIZADOR DE IMAGENES DE PRODUCTOS
    if (!wp_next_scheduled('cyb_actualizar_imagenes')) {
        wp_schedule_event(current_time('timestamp'), '1min', 'cyb_actualizar_imagenes');
    }

    //FUNCTION PARA CREAR LOS PRODUCTOS QUE NO EXISTAN
    if (!wp_next_scheduled('cyb_weekly_cron_crear_p')) {
        wp_schedule_event(current_time('timestamp'), '1min', 'cyb_weekly_cron_crear_p');
    }

    //FUNCTION PARA BORRAR TODOS LOS PRODUCTOS QUE NO EXISTAN EN LA API

    if (!wp_next_scheduled('cyb_weekly_cron_borrar_p')) {
        wp_schedule_event(current_time('timestamp'), '1min', 'cyb_weekly_cron_borrar_p');
    }

}

//AGREGAMOS LA ACCION AL ACTUALIZADOR DE PRODUCTOS
add_action('cyb_weekly_cron_job', 'cyb_do_this_job_weekly');
function cyb_do_this_job_weekly()
{
    // Hacer algo cada semana
    // Hacer algo cada hora
    global $wpdb;

    //ANTES DE HACER NADA CON ESTA FUNCTION DEBEMOS CONSULTAR SI TENEMOS ENCENDIDA LA OPCION DE ACTUALIZAR PRODUCTOS
    $actualizar_p       = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tabla_cron WHERE nombre = 'actualizar_productos' ");
    $actualizar_p_valor = $actualizar_p[0]->numero;

    if ($actualizar_p_valor == 1) {
        $woocommerce = woocommerce_api();

        $data = ['update' => []];

        $curl = curl_init();

        //SETEAMOS LOS PARAMETROS DE LA API
        $api_key = consulta_parametros_a1("apikey");
        $mi_api  = consulta_parametros_a1("api");

        $token_api = "apikey: {$api_key}";

        if($mi_api != "api"){
            $token_api = "TOKEN: {$api_key}";
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL            => "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=menu/listarItems/agrupacion/true/checkStock/true",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "GET",
            CURLOPT_HTTPHEADER     => array(
                $token_api,
            ),
        ));

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $productos_invu = curl_exec($curl);

        /*
        $productos_wordpress = $woocommerce->get('products');

        $id_wordpress = [];
        foreach ($productos_wordpress as $key) {

        array_push($id_wordpress, $key->sku);

        }
         */

        /*
        $mi_producto2 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = '_sku' ");
        //print_r($mi_producto2);
        $id_wordpress = [];
        for ($i=0; $i < 50; $i++) {
        //echo "<h1>NN Estos son todos los productos -> {$mi_producto2[$i]->meta_value}</h1>";

        array_push($id_wordpress, $mi_producto2[$i]->meta_value);
        }
         */

        //TENEMOS EL MODULO EN EL QUE VEREMOS QUE ACTUALIZAREMOS
        //REVISAMOS CUALES SON LOS PARAMETROS QUE SE QUIEREN ACTUALIZAR
        $nombre_producto      = 1;
        $descripcion_producto = 1;
        $precio_producto      = 1;
        $inventario_producto  = 1;

        $que_act = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}variables_productos_a ");
        foreach ($que_act as $key) {
            if ($key->nombre == "nombre_producto") {
                $nombre_producto = $key->estado;
            }

            if ($key->nombre == "descripcion") {
                $descripcion_producto = $key->estado;
            }

            if ($key->nombre == "precio") {
                $precio_producto = $key->estado;
            }

            if ($key->nombre == "inventario") {
                $inventario_producto = $key->estado;
            }
        }

        $mi_producto2 = $wpdb->get_results("SELECT p.*, (SELECT m.meta_value FROM {$wpdb->prefix}postmeta AS m WHERE m.post_id = p.ID AND m.meta_key = '_sku' ) AS meta_value FROM {$wpdb->prefix}posts AS p WHERE p.post_type = 'product' AND p.post_status = 'publish' ");

        //print_r($mi_producto2);

        $cantidad_productos = count($mi_producto2);

        //CONSULTAREMOS LA SECUENCIA
        $secu = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}secuencia_act ORDER BY id DESC LIMIT 1");

        if (count($secu) == 1) {
            //
            if ($secu[0]->hasta <= count($mi_producto2)) {
                $desde = $secu[0]->hasta;
                $hasta = $desde + 50;
            } else {
                $desde = 0;
                $hasta = 50;
            }
        } else {
            $desde = 0;
            $hasta = 50;
        }

        //echo "<h1>Pasamos <--- </h1>";

        $id_wordpress = [];
        for ($i = 0; $i < $cantidad_productos; $i++) {

            if ($i >= $desde && $i <= $hasta) {
                //echo "<h1>NN Estos son todos los productos -> {$mi_producto2[$i]->meta_value}</h1>";

                array_push($id_wordpress, $mi_producto2[$i]->meta_value);
            }
        }

        //AL FINALIZAR INSERTAMOS QUE SE RECORRIO
        $wpdb->insert(
            "{$wpdb->prefix}secuencia_act",
            array(
                'desde' => $desde,
                'hasta' => $hasta,
            )
        );

        $manage = json_decode($productos_invu);
        foreach ($manage->data as $key) {
            //echo "<h1>{$key->idmenu} - {$key->nombre}</h1>";

            if (in_array($key->codigo, $id_wordpress)) {
                $mi_producto2 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = '_sku' AND meta_value = '{$key->codigo}' ");

                if (count($mi_producto2) > 0) {
                    $id_producc = end($mi_producto2); $id_producc = $id_producc->post_id;
                }

                $momentaneo = [];
                $momentaneo = [
                    'id'           => $id_producc,
                    "manage_stock" => true,
                ];

                //DATOS CUSTOM
                if ($nombre_producto == 1) {
                    $momentaneo['name'] = $key->nombre;
                }

                if ($descripcion_producto == 1) {
                    $momentaneo['descripcion']       = $key->descripcion;
                    $momentaneo['short_description'] = $key->descripcion;
                }

                if ($precio_producto == 1) {
                    $momentaneo['regular_price'] = $key->precioSugerido;
                }

                if ($inventario_producto == 1) {
                    $momentaneo['stock_quantity'] = $key->checkStock;
                }

                array_push($data['update'], $momentaneo);

                $momentaneo = [];
            }
        }

        $woocommerce->post('products/batch', $data);
    }

}

/*
 *
 *    NUEVA ACTIALIZACION DE PRODUCTOS
 *    solo los modificados
 *
 */
//AGREGAMOS LA ACCION AL ACTUALIZADOR DE PRODUCTOS
add_action('actualizar_solo_modificados', 'solo_modificados_invu');
//add_action('wp_footer', "solo_modificados_invu");
function solo_modificados_invu()
{
    // Hacer algo cada semana
    // Hacer algo cada hora
    global $wpdb;

    //VARIABLES DE FECHA
    $fecha_consulta = date("Y-m-d");
    //$fecha_consulta = "2021-02-22";
    $fecha_consulta = $fecha_consulta . " 00:01:00";
    //$fecha_consulta = "2020-12-13 00:01:00";

    //ANTES DE HACER NADA CON ESTA FUNCTION DEBEMOS CONSULTAR SI TENEMOS ENCENDIDA LA OPCION DE ACTUALIZAR PRODUCTOS
    $actualizar_p       = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tabla_cron WHERE nombre = 'actualizar_productos' ");
    $actualizar_p_valor = $actualizar_p[0]->numero;



    //CONSULTAMOS SI PODEMOS CREAR PRODUCTOS NUEVOS
    $servicios       = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tabla_cron  ");
    $crear_productos = 2;
    $actualizar_categorias  = 2;//LO DEFINIMOS EN 2 YA QUE NO QUEREMOS ACTUALIZARLA DE FABRICA
    $mantener_categorias  = 2;

    foreach ($servicios as $key) {
        if ($key->nombre == "crear_productos_nuevos") {
            $crear_productos = $key->numero;
        }

        //REVISAMOS SI PODEMOS ACTUALIZAR LA CATEGORIA 
        if($key->nombre == "actualizar_categorias"){
            $actualizar_categorias = $key->numero;
        }

        if($key->nombre == "mantener_categorias"){
            $mantener_categorias = $key->numero;
        }
    }

    $podemos_crear = 0;

    if ($crear_productos == 1) {

        $podemos_crear = 1;
    }

    if ($actualizar_p_valor == 1) {
        $woocommerce = woocommerce_api();

        $data = ['update' => [], 'create' => []];

        $curl = curl_init();


        //ANTES DE HACER NADA PRIMERO DEBEMOS SABER CUANTAS PAGINAS TENEMOS
        $productos_invu = productos_invu_paginar($fecha_consulta, "");
        $manage = json_decode($productos_invu);

        $paginas = $manage->cantidadPaginas;


        //echo "<h1>Cantidad de paginas {$paginas}</h1>";

        if($paginas == 0 && $manage->totalRegistros > 0){
            $paginas = 1;
        }


        //DEFINIMOS LA URL PARA LA CONSULTA
         //SETEAMOS LOS PARAMETROS DE LA API
         $api_key = consulta_parametros_a1("apikey");
         $mi_api  = consulta_parametros_a1("api");

        //$url_consulta_api = "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=menu/listarItems/agrupacion/true/checkStock/true";
        $url_consulta_api = "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=menu/listarItems/agrupacion/true/checkStock/true/limit/50/pagina/1";

        $posicion2 = 0;

        if($paginas != 1){
            //SI TENEMOS MAS DE 1 PAGINA DEBEMOS CONSULTAR BIEN QUE PAGINA QUEREMOS
            $hoy = date("Y-m-d")." 00:01:00";
            $posicion = $wpdb->get_results("SELECT pagina FROM {$wpdb->prefix}consultas_respuestas WHERE fecha = '{$fecha_consulta}' ");
            
            //echo "<h1>".count($posicion)."</h1>";
            //echo "<h1>".$hoy."</h1>";
            
            //HACEMOS UNA PEQUENA CONSULTA 
            if(count($posicion) == 0){
                //ESTO ES QUE ES LA PRIMERA TRANSACCION DEL DIA
                $posicion2 = 1;
                
            }else{
                $posicion_consulta = end($posicion);


                //echo "<h1>================> {$posicion_consulta->pagina}</h1>";

                if($posicion_consulta->pagina < $paginas){
                    $posicion2 = $posicion_consulta->pagina + 1;
                }else{
                    $posicion2 = 1;
                }
            }

            
        }

        $url_consulta_api = "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=menu/listarItems/agrupacion/true/checkStock/true/limit/50/pagina/{$posicion2}";

        $token_api = "apikey: {$api_key}";

        if($mi_api != "api"){
            $token_api = "TOKEN: {$api_key}";
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL            => $url_consulta_api,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "GET",
            CURLOPT_POSTFIELDS     => "{\n\t\"fecha_modificacion\": \"" . $fecha_consulta . "\"\n}",
            //CURLOPT_POSTFIELDS     => "{'fecha_modificacion':'{$fecha_consulta}'}",
            CURLOPT_HTTPHEADER     => array(
                $token_api,
            ),
        ));

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $productos_invu = curl_exec($curl);

        if ($productos_invu === false) {
            //die(curl_error($ch2));
            $productos_invu = curl_error($ch2);
        }

        //TENEMOS EL MODULO EN EL QUE VEREMOS QUE ACTUALIZAREMOS
        //REVISAMOS CUALES SON LOS PARAMETROS QUE SE QUIEREN ACTUALIZAR
        $nombre_producto      = 1;
        $descripcion_producto = 1;
        $precio_producto      = 1;
        $inventario_producto  = 1;

        $que_act = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}variables_productos_a ");
        foreach ($que_act as $key) {
            if ($key->nombre == "nombre_producto") {
                $nombre_producto = $key->estado;
            }

            if ($key->nombre == "descripcion") {
                $descripcion_producto = $key->estado;
            }

            if ($key->nombre == "precio") {
                $precio_producto = $key->estado;
            }

            if ($key->nombre == "inventario") {
                $inventario_producto = $key->estado;
            }
        }

        #COMENTADO SOLO PARA LA PRUEBA
        //print_r($productos_invu);

        $manage = json_decode($productos_invu);
        foreach ($manage->data as $key) {

            //VALIDAMOS QUE NO SEA UNA VARIACION
            if($key->codigo_item_principal == NULL){
                //echo "<h1>{$key->idmenu} - {$key->nombre}</h1>";

                //if (in_array($key->codigo, $id_wordpress)) {
                $mi_producto2 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = '_sku' AND meta_value = '{$key->codigo}' ");

                if (count($mi_producto2) > 0) {
                    $id_producc = end($mi_producto2); $id_producc = $id_producc->post_id;

                    $momentaneo = [];
                    $momentaneo = [
                        'id'           => $id_producc,
                        //"manage_stock" => true,
                    ];

                    //DATOS CUSTOM
                    if ($nombre_producto == 1) {
                        $momentaneo['name'] = $key->nombre;
                    }

                    if ($descripcion_producto == 1) {
                        $momentaneo['descripcion']       = $key->descripcion;
                        $momentaneo['short_description'] = $key->descripcion;
                    }

                    if ($precio_producto == 1) {
                        $momentaneo['regular_price'] = $key->precioSugerido;
                    }

                    if ($inventario_producto == 1) {
                        $momentaneo['stock_quantity'] = $key->checkStock;
                    }

                    //REVISAMOS LA CATEGORIA
                    if($actualizar_categorias == 1){
                        //ENVIAMOS A QUE SE ACTUALICE LA CATEGORIA
                        $categoria_producto = "";
                        $name_category = $key->nombre_categoriamenu;

                        if( $term = get_term_by( 'name', $name_category, 'product_cat' ) ){
                            $categoria_producto = $term->term_id;
                        }
                        /*
                        if($categoria_producto != ""){
                            $momentaneo['categories'] = [["id" => $categoria_producto]];
                        }else{
                            $momentaneo['categories'] = [["id" => categoria_hija_nueva($key->codigo_categoriamenu)]];
                        }
                        */
                        //$momentaneo['categories'] = [["id" => categoria_hija_nueva($key->codigo_categoriamenu)]];
                        $momentaneo['categories'] = categoria_hija_nueva($key->codigo_categoriamenu, $id_producc, $mantener_categorias);

                    }

                    array_push($data['update'], $momentaneo);

                    $momentaneo = [];
                    //}
                }else{
                    //AQUI ES SI EL PRODUCTO NO EXISTE, DEBEMOS VALIDAR SI LO PODEMOS CREAR O NO

                    if($podemos_crear == 1){
                        //SI NO EXISTE LO CREAMOS
                        $momentaneo = [];
                        $momentaneo = [
                            //'id'           => $id_producc,
                            "manage_stock" => true,
                        ];

                        //DATOS CUSTOM
                        if ($nombre_producto == 1) {
                            $momentaneo['name'] = $key->nombre;
                        }
                        $momentaneo['name'] = $key->nombre;

                        if ($descripcion_producto == 1) {
                            $momentaneo['descripcion']       = $key->descripcion;
                            $momentaneo['short_description'] = $key->descripcion;
                        }
                        $momentaneo['descripcion']       = $key->descripcion;
                        $momentaneo['short_description'] = $key->descripcion;

                        if ($precio_producto == 1) {
                            $momentaneo['regular_price'] = $key->precioSugerido;
                        }

                        if ($inventario_producto == 1) {
                            $momentaneo['stock_quantity'] = $key->checkStock;
                        }

                        //REVISAMOS LA CATEGORIA
                        if($actualizar_categorias == 1){
                            //ENVIAMOS A QUE SE ACTUALICE LA CATEGORIA
                            $categoria_producto = "";
                            $name_category = $key->nombre_categoriamenu;

                            if( $term = get_term_by( 'name', $name_category, 'product_cat' ) ){
                                $categoria_producto = $term->term_id;
                            }
                            /*
                            if($categoria_producto != ""){
                                $momentaneo['categories'] = [["id" => $categoria_producto]];
                            }else{
                                $momentaneo['categories'] = [["id" => categoria_hija_nueva($key->codigo_categoriamenu)]];
                            }
                            */

                            //$momentaneo['categories'] = [["id" => categoria_hija_nueva($key->codigo_categoriamenu)]];
                            $momentaneo['categories'] = categoria_hija_nueva($key->codigo_categoriamenu, $id_producc, $mantener_categorias);
                        }

                        //AGREGAMOS MUY IMPORTANTE EL SKU
                        $momentaneo['sku'] = $key->codigo;
                        //$momentaneo['']

                        array_push($data['create'], $momentaneo);

                        $momentaneo = [];
                        //}
                    }
                    
                }
            }else{
                //SI ES UNA VARIACION LO MANDAMOS TODO A LA FUNCION DE VARIACION
                variaciones_invu_pos( $nombre_producto, $descripcion_producto, $precio_producto, $inventario_producto, $key);
            }
        }

        $resultado = $woocommerce->post('products/batch', $data);
        $resultado = json_encode($resultado);

        $wpdb->insert(
            "{$wpdb->prefix}consultas_respuestas",
            array(
                'fecha'     => $fecha_consulta,
                'paginas'   => $paginas,
                'pagina'    => $posicion2,
                'consulta'  => $productos_invu,
                'respuesta' => $resultado,
            )
        );

    }

}


//add_action('wp_footer', "solo_modificados_invu");
function variaciones_invu_pos( $nombre_producto, $descripcion_producto, $precio_producto, $inventario_producto, $valor_key)
{
    global $wpdb;
    $woocommerce = woocommerce_api();

    $data = ['update' => [], 'create' => []];


    $key = $valor_key;

    $key_agrupacion = end($key->agrupacion);

    /*
    echo "<h1>ESTA ES LA AGRUPACION: </h1>";
    print_r($key_agrupacion);
    echo "<br>->";
    print_r($key->agrupacion);

    echo "<br><br><br>";
    echo "<h1>Este es el key: </h1>";
    print_r($key);
    echo json_encode($key);
    echo "<br><br><br>";
    echo "<h1>Fin de la aprupacion AGRUPACION: </h1>";
    */

    $mi_producto2 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = '_sku' AND meta_value = '{$key->codigo}' ");

    //BUSCAMOS EL ID DEL PADRE
    $producto_padre = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = '_sku' AND meta_value = '{$key->codigo_item_principal}' ");

    $id_padre = "";

    if(count($producto_padre) <= 0 ){
    	return;
    }

    //PRUEBA.

    if($key->codigo == "M2880"){
        return;
    }
    
    if (count($mi_producto2) > 0) {
        /*

        //echo "<h1>Si estamos en esta seccion del padre <---</h1>";
        $id_producc = end($mi_producto2); $id_producc = $id_producc->post_id;

        //ID DEL PADRE
        $id_padre = end($producto_padre); $id_padre = $id_padre->post_id;

        $datos_padre = $woocommerce->get('products/'.$id_padre);
        $tipo_padre = $datos_padre->type;
        $atributos_padre = $datos_padre->attributes;

        //echo "<h1>El tipo de padre es: {$tipo_padre}</h1>";

        $momentaneo = [];

        //REVICEMOS LOS ATRIBUTOS QUE TIENE EL PRODUCTO PARA NO PERDERLOS
        $t34 = $woocommerce->get('products/'.$id_padre.'/variations');

        //print_r($t34);

        $atributos_Viejos_color = [];
        $atributos_Viejos_talla = [];

		//VAMOS A RECORRER ESTO PARA VER
		foreach ($t34 as $k33) {

			//RECORREMOS PRIMERO EL COLOR
			foreach ($k33->attributes as $att) {
				if($att->name == "Color"){
					//echo "<h1>{$att->option}</h1>";
					array_push($atributos_Viejos_color, $att->option);
				}
			}

			//RECORREMOS LA TALLA
			foreach ($k33->attributes as $att) {
				if($att->name == "Talla"){
					//echo "<h1>{$att->option}</h1>";
					array_push($atributos_Viejos_talla, $att->option);
				}
			}
		}


		//PRIMERO ACTUALIZAMOS EL PRODUCTO
        $datap = [
			"type"          => "variable",
			"attributes"    => [],
            "manage_stock"  => false
		];

		$banderita = 0;
		
        //DEPENDERA EL TAMANO SI TENEMOS COLORES Y TALLAS
		//desc_coleccion 	|| TALLA
		//desc_autor		|| COLOR

		//VALIDAMOS QUE TENGAMOS COLOR
		if($key_agrupacion->groups->desc_autor){
            //echo "<h1>TRAEMOS UN COLOR</h1>";
			//if(!in_array($key_agrupacion->groups->desc_autor, $atributos_Viejos_color)){
				$banderita++;
				//GUARDAMOS EL COLOR DENTRO DE NUESTROS COLORES VIEJOS
				array_push($atributos_Viejos_color, $key_agrupacion->groups->desc_autor);


                //$momentaneoccl = array_unique($atributos_Viejos_color);
                //array_count_values
                $momentaneoccl = array_unique($atributos_Viejos_color);

                $atributos_Viejos_color = [];

                foreach($momentaneoccl as $keyss => $valor){
                    echo "<h1>EL color es: {$valor}</h1>";
                    array_push($atributos_Viejos_color, $valor);
                }

				//REVISAMOS EL COLOR
				$datos_color = [
			      "name"=> "Color",
			      "position"=> 0,
			      "visible" => true,
			      "variation"=> true,
			      "options"=> array_unique($atributos_Viejos_color)
				];

                print_r($atributos_Viejos_color);

                //echo "<h1>============> {$id_padre} ".json_encode(array_unique($atributos_Viejos_color))."</h1>";
				
				array_push($datap["attributes"], $datos_color);
			//}	
		}

		//VALIDAMOS QUE TENGAMOS LA TALLA
		if($key_agrupacion->groups->desc_coleccion){
			//if(!in_array($key_agrupacion->groups->desc_coleccion, $atributos_Viejos_talla)){

				$banderita++;

				//GUARDAMOS EL COLOR DENTRO DE NUESTROS COLORES VIEJOS
				array_push($atributos_Viejos_talla, $key_agrupacion->groups->desc_coleccion);

                $momentaneoccl = array_unique($atributos_Viejos_talla);
                
                $atributos_Viejos_talla = [];

                foreach($momentaneoccl as $keyss => $valor){
                    array_push($atributos_Viejos_talla, $valor);
                }


				//REVISAMOS EL COLOR
				$datos_talla = [
			      "name"=> "Talla",
			      "position"=> 0,
			      "visible" => true,
			      "variation"=> true,
			      "options"=> array_unique($atributos_Viejos_talla)
				];
				

				array_push($datap["attributes"], $datos_talla);
			//}	
		}
        
        //echo "<h1>Esto es lo que dice la banderita! {$banderita}</h1>";
		if($banderita > 0){
            //echo "<h1>Este si actualiza: {$id_padre}</h1>";
			$actualizamos = $woocommerce->put('products/'.$id_padre, $datap);
		}else if(count($atributos_padre) <= 0){

            //echo "<h1>UN SIMPLEEEEEEE</h1>";
            $actualizamos = $woocommerce->put('products/'.$id_padre, $datap);
        }

        //print_r($actualizamos);
        //echo "<h3>Tipo de producto ===============> {$t34->type}</h3>";

        //YA CON EL PADRE CONVERTIDO EN PRODUCTO VARIABLE PROCEDEMOS A ACTUALIZAR LAS VARIABLES RESPECTIVAS
        //$momentaneo['stock_quantity'] = $key->checkStock;
        $momentaneo = [
			'id' => $id_producc,//ID DEL HIJO
			'regular_price'     => $key->precioSugerido,
			'sku'			    => $key->codigo,
			//"attributes" => [],
            'stock_quantity'    => $key->checkStock,
            'manage_stock'      => true

		];

        array_push($data['update'], $momentaneo);

        $momentaneo = [];


        //echo "<h1>Pasamos por estos: </h1>";
        //print_r($momentaneo);//NOS QUEDAMOS EN ESTE PUNTO!**
        //}
        */
    }else{

        //ID DEL PADRE
        $id_padre = end($producto_padre); $id_padre = $id_padre->post_id;

        $datos_padre = $woocommerce->get('products/'.$id_padre);
        $tipo_padre = $datos_padre->type;

        //echo "<h1>El tipo de padre es: {$tipo_padre}</h1>";

        //AQUI ES SI EL PRODUCTO NO EXISTE, DEBEMOS VALIDAR SI LO PODEMOS CREAR O NO

        //if($podemos_crear == 1){
            //SI NO EXISTE LO CREAMOS
            $momentaneo = [];


            //REVICEMOS LOS ATRIBUTOS QUE TIENE EL PRODUCTO PARA NO PERDERLOS
            $t34 = $woocommerce->get('products/'.$id_padre.'/variations');


            echo "<h1>Consultando este padre: {$id_padre} SKU HIJO:{$key->codigo}</h1>";

            $atributos_Viejos_color = [];
            $atributos_Viejos_talla = [];

			//VAMOS A RECORRER ESTO PARA VER
			foreach ($t34 as $k33) {


				//RECORREMOS PRIMERO EL COLOR
				foreach ($k33->attributes as $att) {
					if($att->name == "Color"){
						array_push($atributos_Viejos_color, $att->option);
					}
				}

				//RECORREMOS LA TALLA
				foreach ($k33->attributes as $att) {
					if($att->name == "Talla"){
						array_push($atributos_Viejos_talla, $att->option);
					}
				}
			}


			$datap = [
				"type"          => "variable",
				"attributes"    => [],
                "manage_stock"  => false
			];

			if($key_agrupacion->groups->desc_autor){
				//if(!in_array($key_agrupacion->groups->desc_autor, $atributos_Viejos_color)){
					//GUARDAMOS EL COLOR DENTRO DE NUESTROS COLORES VIEJOS
					array_push($atributos_Viejos_color, $key_agrupacion->groups->desc_autor);

                    $momentaneoccl = array_unique($atributos_Viejos_color);

                    $atributos_Viejos_color = [];

                    foreach($momentaneoccl as $keyss => $valor){

                        echo "<h1>EL COLOR ES: {$valor} PADRE: {$id_padre}</h1>";
                        array_push($atributos_Viejos_color, $valor);
                    }
                    
					//REVISAMOS EL COLOR
					$datos_color = [
				      "name"=> "Color",
				      "position"=> 0,
				      "visible" => true,
				      "variation"=> true,
				      "options"=> $atributos_Viejos_color
					];
					

					array_push($datap["attributes"], $datos_color);
				//}	
			}

			//VALIDAMOS QUE TENGAMOS LA TALLA
			if($key_agrupacion->groups->desc_coleccion){
				//if(!in_array($key_agrupacion->groups->desc_coleccion, $atributos_Viejos_talla)){
					//GUARDAMOS EL COLOR DENTRO DE NUESTROS COLORES VIEJOS
					array_push($atributos_Viejos_talla, $key_agrupacion->groups->desc_coleccion);

                    $momentaneoccl = array_unique($atributos_Viejos_talla);
                
                    $atributos_Viejos_talla = [];

                    foreach($momentaneoccl as $keyss => $valor){
                        array_push($atributos_Viejos_talla, $valor);
                    }


					//REVISAMOS EL COLOR
					$datos_color = [
				      "name"=> "Talla",
				      "position"=> 0,
				      "visible" => true,
				      "variation"=> true,
				      "options"=> $atributos_Viejos_talla
					];
					

					array_push($datap["attributes"], $datos_color);
				//}	
			}

			//print_r($woocommerce->put('products/5067', $datap));

			$actualizamos = $woocommerce->put('products/'.$id_padre, $datap);
            
            //LUEGO DE ESTO PROCEDEMOS A CREAR LAS VARIABLES HIJAS

            //$key->agrupacion->groups->desc_autor;


			$imprimir_Esto = json_encode($key);
			//print_r($key);

			$momentaneo = [
			            'regular_price'     => $key->precioSugerido,
						'sku'			    => $key->codigo,
                        'stock_quantity'    => $key->checkStock,
                        'manage_stock'      => false,
			            'attributes'        => []
			        ];

			$momentaneo_color = [];
			if($key_agrupacion->groups->desc_autor){
				$momentaneo_color = [
			                    "name" => "Color",
			                    'option' => $key_agrupacion->groups->desc_autor
			                ];

                array_push($momentaneo['attributes'], $momentaneo_color);
			}


			$momentaneo_talla = [];
			if($key_agrupacion->groups->desc_coleccion){
				$momentaneo_talla = [
			                    "name" => "Talla",
			                    'option' => $key_agrupacion->groups->desc_coleccion
			                ];

                array_push($momentaneo['attributes'], $momentaneo_talla);
			}

			//print_r($woocommerce->post('products/5067/variations/batch', $data));

            array_push($data['create'], $momentaneo);

            $momentaneo = [];
            //}
        //}
        
    }

    if($id_padre != ""){
		//AHORA SI ENVIAMOS TODO
		$woocommerce->post('products/'.$id_padre.'/variations/batch', $data);	


        //LE QUITAMOS LO DEL INVENTARIO POR ULTIMA VEZ
        $datos_stock_padre = [
            "manage_stock"  => false
        ];

        $woocommerce->put('products/'.$id_padre, $datos_stock_padre);

        //echo "<h1>Si estamos en el padre {$id_padre}!</h1>";

        //echo "<h3>SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = '_sku' AND meta_value = '{$key->codigo_item_principal}'</h3>";
    }else{
        //ESTO ES SI NO PASA POR EL PADRE
        //echo "<h1>No paso por el padre!</h1>";
    }
}

/**
 * 
 * ESTA FUNCION ES IGUAL A LA ANTERIOR SOLO QUE SE EJECUTA CADA 30 MINUTOS 
 * Y ES CON TODA LA INFORMACION DE INVU
 */
add_action('actualizar_todo_invu', 'todo_invu_woocommerce');
//add_action("wp_footer", "todo_invu_woocommerce");
function todo_invu_woocommerce()
{
    global $wpdb;

    //VARIABLES DE FECHA
    $fecha_consulta = date("Y-m-d");
    $fecha_consulta = $fecha_consulta . " 00:01:00";
    //$fecha_consulta = "2020-12-13 00:01:00";

    //ESTA SECCION NO CONTARA CON FECHAS, YA QUE ES PARA ACTUALIZAR TODA LA INFORMACION
    $fecha_consulta = "";

    //ANTES DE HACER NADA CON ESTA FUNCTION DEBEMOS CONSULTAR SI TENEMOS ENCENDIDA LA OPCION DE ACTUALIZAR PRODUCTOS
    $actualizar_p       = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tabla_cron WHERE nombre = 'actualizar_productos' ");
    $actualizar_p_valor = $actualizar_p[0]->numero;

    //CONSULTAMOS SI PODEMOS CREAR PRODUCTOS NUEVOS
    $servicios              = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tabla_cron  ");
    $crear_productos        = 1;
    $actualizar_categorias  = 2;//LO DEFINIMOS EN 2 YA QUE NO QUEREMOS ACTUALIZARLA DE FABRICA
    $mantener_categorias    = 2;

    foreach ($servicios as $key) {
        if ($key->nombre == "crear_productos_nuevos") {
            $crear_productos = $key->numero;
        }

        //REVISAMOS SI PODEMOS ACTUALIZAR LA CATEGORIA 
        if($key->nombre == "actualizar_categorias"){
            $actualizar_categorias = $key->numero;
        }

        if($key->nombre == "mantener_categorias"){
            $mantener_categorias = $key->numero;
        }
    }

    $podemos_crear = 0;

    if ($crear_productos == 1) {

        $podemos_crear = 1;
    }

    if ($actualizar_p_valor == 1) {
        $woocommerce = woocommerce_api();

        $data = ['update' => [], 'create' => []];

        $curl = curl_init();


        //ANTES DE HACER NADA PRIMERO DEBEMOS SABER CUANTAS PAGINAS TENEMOS
        $productos_invu = productos_invu_paginar_20($fecha_consulta, "");
        $manage = json_decode($productos_invu);

        $paginas = $manage->cantidadPaginas;

        //echo "<h1>Cantidad de paginas {$paginas}</h1>";

        if($paginas == 0 && $manage->totalRegistros > 0){
            $paginas = 1;
        }

        //DEFINIMOS LA URL PARA LA CONSULTA
        //SETEAMOS LOS PARAMETROS DE LA API
        $api_key = consulta_parametros_a1("apikey");
        $mi_api  = consulta_parametros_a1("api");

        //$url_consulta_api = "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=menu/listarItems/agrupacion/true/checkStock/true";
        $url_consulta_api = "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=menu/listarItems/agrupacion/true/checkStock/true/limit/20/pagina/1";

        $posicion2 = 0;

        if($paginas != 1){
            //SI TENEMOS MAS DE 1 PAGINA DEBEMOS CONSULTAR BIEN QUE PAGINA QUEREMOS
            $hoy = date("Y-m-d")." 00:01:00";
            $posicion = $wpdb->get_results("SELECT pagina FROM {$wpdb->prefix}consultas_respuestas_todo WHERE fecha = '{$hoy}' ");
            
            //HACEMOS UNA PEQUENA CONSULTA 
            if(count($posicion) == 0){
                //ESTO ES QUE ES LA PRIMERA TRANSACCION DEL DIA
                $posicion2 = 1;
                
            }else{
                $posicion_consulta = end($posicion);

                if($posicion_consulta->pagina < $paginas){
                    $posicion2 = $posicion_consulta->pagina + 1;
                }else{
                    $posicion2 = 1;
                }
            }
        }

        $url_consulta_api = "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=menu/listarItems/agrupacion/true/checkStock/true/limit/20/pagina/{$posicion2}";

        $token_api = "apikey: {$api_key}";

        if($mi_api != "api"){
            $token_api = "TOKEN: {$api_key}";
        }
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $url_consulta_api,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "GET",
            CURLOPT_POSTFIELDS     => "{\n\t\"fecha_modificacion\": \"" . $fecha_consulta . "\"\n}",
            //CURLOPT_POSTFIELDS     => "{'fecha_modificacion':'{$fecha_consulta}'}",
            CURLOPT_HTTPHEADER     => array(
                $token_api,
            ),
        ));

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $productos_invu = curl_exec($curl);

        if ($productos_invu === false) {
            //die(curl_error($ch2));
            $productos_invu = curl_error($ch2);
        }

        //TENEMOS EL MODULO EN EL QUE VEREMOS QUE ACTUALIZAREMOS
        //REVISAMOS CUALES SON LOS PARAMETROS QUE SE QUIEREN ACTUALIZAR
        $nombre_producto      = 1;
        $descripcion_producto = 1;
        $precio_producto      = 1;
        $inventario_producto  = 1;

        $que_act = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}variables_productos_a ");
        foreach ($que_act as $key) {
            if ($key->nombre == "nombre_producto") {
                $nombre_producto = $key->estado;
            }

            if ($key->nombre == "descripcion") {
                $descripcion_producto = $key->estado;
            }

            if ($key->nombre == "precio") {
                $precio_producto = $key->estado;
            }

            if ($key->nombre == "inventario") {
                $inventario_producto = $key->estado;
            }
        }

        #COMENTADO SOLO PARA LA PRUEBA
        //print_r($productos_invu);
        $manage = json_decode($productos_invu);
        foreach ($manage->data as $key) {

            //VALIDAMOS QUE NO SEA UNA VARIACION
            if($key->codigo_item_principal == NULL){
                //echo "<h1>{$key->idmenu} - {$key->nombre}</h1>";

                //if (in_array($key->codigo, $id_wordpress)) {
                $mi_producto2 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = '_sku' AND meta_value = '{$key->codigo}' ");

                if (count($mi_producto2) > 0) {
                    $id_producc = end($mi_producto2); $id_producc = $id_producc->post_id;

                    $momentaneo = [];
                    $momentaneo = [
                        'id'           => $id_producc,
                        //"manage_stock" => true,
                    ];

                    //DATOS CUSTOM
                    if ($nombre_producto == 1) {
                        $momentaneo['name'] = $key->nombre;
                    }

                    if ($descripcion_producto == 1) {
                        $momentaneo['descripcion']       = $key->descripcion;
                        $momentaneo['short_description'] = $key->descripcion;
                    }

                    if ($precio_producto == 1) {
                        $momentaneo['regular_price'] = $key->precioSugerido;
                    }

                    if ($inventario_producto == 1) {
                        $momentaneo['stock_quantity'] = $key->checkStock;
                    }

                    //REVISAMOS LA CATEGORIA
                    if($actualizar_categorias == 1){
                        //ENVIAMOS A QUE SE ACTUALICE LA CATEGORIA
                        $categoria_producto = "";
                        $name_category = $key->nombre_categoriamenu;

                        //echo "<h3>es esta: {$name_category}</h3>";

                        if( $term = get_term_by( 'name', $name_category, 'product_cat' ) ){
                            //echo "<h1>hay concidencia! {$term->term_id}</h1>";

                            //print_r($term);

                            $categoria_producto = $term->term_id;
                        }

                        //echo "<h1>ENCUENTRAME {$categoria_producto}</h1>";
                        /*
                        if($categoria_producto != ""){
                            $momentaneo['categories'] = [["id" => $categoria_producto]];
                        }else{
                            $momentaneo['categories'] = [["id" => categoria_hija_nueva($key->codigo_categoriamenu)]];
                        }
                        */

                        //$momentaneo['categories'] = [["id" => categoria_hija_nueva($key->codigo_categoriamenu)]];
                        $momentaneo['categories'] = categoria_hija_nueva($key->codigo_categoriamenu, $id_producc, $mantener_categorias);
                    }

                    array_push($data['update'], $momentaneo);

                    $momentaneo = [];
                    //}
                }else{
                    //AQUI ES SI EL PRODUCTO NO EXISTE, DEBEMOS VALIDAR SI LO PODEMOS CREAR O NO

                    if($podemos_crear == 1){
                        //SI NO EXISTE LO CREAMOS
                        $momentaneo = [];
                        $momentaneo = [
                            //'id'           => $id_producc,
                            "manage_stock" => true,
                        ];

                        //DATOS CUSTOM
                        if ($nombre_producto == 1) {
                            $momentaneo['name'] = $key->nombre;
                        }
                        $momentaneo['name'] = $key->nombre;

                        if ($descripcion_producto == 1) {
                            $momentaneo['descripcion']       = $key->descripcion;
                            $momentaneo['short_description'] = $key->descripcion;
                        }
                        $momentaneo['descripcion']       = $key->descripcion;
                        $momentaneo['short_description'] = $key->descripcion;

                        if ($precio_producto == 1) {
                            $momentaneo['regular_price'] = $key->precioSugerido;
                        }

                        if ($inventario_producto == 1) {
                            $momentaneo['stock_quantity'] = $key->checkStock;
                        }


                        //REVISAMOS LA CATEGORIA
                        if($actualizar_categorias == 1){
                            //ENVIAMOS A QUE SE ACTUALICE LA CATEGORIA
                            $categoria_producto = "";
                            $name_category = $key->nombre_categoriamenu;

                            if( $term = get_term_by( 'name', $name_category, 'product_cat' ) ){
                                $categoria_producto = $term->term_id;
                            }
                            /*
                            if($categoria_producto != ""){
                                $momentaneo['categories'] = [["id" => $categoria_producto]];
                            }else{
                                $momentaneo['categories'] = [["id" => categoria_hija_nueva($key->codigo_categoriamenu)]];
                            }

                            */

                            //$momentaneo['categories'] = [["id" => categoria_hija_nueva($key->codigo_categoriamenu)]];
                            $momentaneo['categories'] = categoria_hija_nueva($key->codigo_categoriamenu, $id_producc, $mantener_categorias);
                        }

                        //AGREGAMOS MUY IMPORTANTE EL SKU
                        $momentaneo['sku'] = $key->codigo;
                        //$momentaneo['']

                        array_push($data['create'], $momentaneo);

                        $momentaneo = [];
                        //}
                    }
                    
                }
            }else{
                //SI ES UNA VARIACION LO MANDAMOS TODO A LA FUNCION DE VARIACION
                variaciones_invu_pos( $nombre_producto, $descripcion_producto, $precio_producto, $inventario_producto, $key);
            }
            
        }

        $resultado = $woocommerce->post('products/batch', $data);
        $resultado = json_encode($resultado);

        $wpdb->insert(
            "{$wpdb->prefix}consultas_respuestas_todo",
            array(
                //'fecha'     => $fecha_consulta,//IMPORTANTE DEBEMOS AGREGAR IGUAL LA FECHA
                'fecha'     => date("Y-m-d")." 00:01:00",
                'paginas'   => $paginas,
                'pagina'    => $posicion2,
                'consulta'  => $productos_invu,
                'respuesta' => $resultado,
            )
        );

    }

}

/**
 * NUEVA Y MEJORADA FUNCION DE CATEGORIAS
 */
function categoria_hija_nueva($codigo, $id_producc = "", $mantener_categorias){

    //return 16;
    /*
    $categoriademo = [["id" => 16]];
    return $categoriademo;*/
    global $wpdb;
    global $wp_query;

    $api_key = consulta_parametros_a1("apikey");
    $mi_api  = consulta_parametros_a1("api");

    $woocommerce = woocommerce_api();

    //$data = ['update' => [], 'create' => []];

    $curl = "";

    $curl = curl_init();

    //LA API ES LA 5 PARA ESTO
    $mi_api = "api5";

    $url_consulta_api = "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=categoriaMenu/view/id/{$codigo}";
    //echo $url_consulta_api."<br>";
    $token_api = "apikey: {$api_key}";

    if($mi_api != "api"){
        $token_api = "TOKEN: {$api_key}";
    }
    curl_setopt_array($curl, array(
        CURLOPT_URL            => $url_consulta_api,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => "",
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => "GET",
        //CURLOPT_POSTFIELDS     => "{\n\t\"fecha_modificacion\": \"" . $fecha_consulta . "\"\n}",
        //CURLOPT_POSTFIELDS     => "{'fecha_modificacion':'{$fecha_consulta}'}",
        CURLOPT_HTTPHEADER     => array(
            $token_api,
        ),
    ));

    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $productos_invu = curl_exec($curl);

    if ($productos_invu === false) {
        //die(curl_error($ch2));
        $productos_invu = curl_error($ch2);
    }
    
    
    $manage = json_decode($productos_invu);
    //print_r($manage);
    //foreach ($manage->data as $key) {
    $idCategoria = "";
    $name_category = $manage->data->nombremenu;

    $categoria_producto = 0;//ESTE YA ES EL ID FINAL DE WP
    $categoria_producto2 = 0;


    //BUSCAMOS LA CATEGORIA PRINCIPAL DE UNA VEZ
    $id_principal = $manage->data->idSubCategoriaMenu;
    $id_secundaria_invu = $manage->data->idcategoriamenu;


    $debemos_crear_sub_categoria = 2;

    //BUSCAMOS LA HIJA
    if( $term = get_term_by( 'name', $name_category, 'product_cat' ) ){
        $categoria_producto = $term->term_id;
    }else{
        $debemos_crear_sub_categoria = 1;
    }

    
    $curl = "";

    $curl = curl_init();
    $url_consulta_api = "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=menu/ListarSubcategorias";
    //echo $url_consulta_api."<br>";
    $token_api = "apikey: {$api_key}";

    if($mi_api != "api"){
        $token_api = "TOKEN: {$api_key}";
    }
    curl_setopt_array($curl, array(
        CURLOPT_URL            => $url_consulta_api,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => "",
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => "GET",
        //CURLOPT_POSTFIELDS     => "{\n\t\"fecha_modificacion\": \"" . $fecha_consulta . "\"\n}",
        //CURLOPT_POSTFIELDS     => "{'fecha_modificacion':'{$fecha_consulta}'}",
        CURLOPT_HTTPHEADER     => array(
            $token_api,
        ),
    ));

    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $productos_invu = curl_exec($curl);

    if ($productos_invu === false) {
        //die(curl_error($ch2));
        $productos_invu = curl_error($ch2);
    }
    
    
    $manage = json_decode($productos_invu);
    //print_r($manage);
    //foreach ($manage->data as $key) {

    foreach($manage->data as $datos){

        if($datos->id == $id_principal){


            if( $term = get_term_by( 'name', $datos->descripcion, 'product_cat' ) ){
                $categoria_producto2 = $term->term_id;
            }else{
                $data = [
                    "create"    => [
                        [
                            "name" => $datos->descripcion
                        ]
                    ]
                ];
    
                //print_r($woocommerce->post('products/categories', $data));
                $resultado = $woocommerce->post('products/categories/batch', $data);
    
                $categoria_producto2 = $resultado->create[0]->id;
            
    
                //LUEGO LA RELACIONAMOS ENTRE LOS PRODUCTOS
                $wpdb->insert(
                        "{$wpdb->prefix}mis_categorias",
                        array(
                            'invu'			=> $datos->id,
                            'wp'			=> $categoria_producto2,
                            'tipo'			=> '1'
                        )
                );

                if($debemos_crear_sub_categoria == 1){
                    $data = [
                        "create"    => [
                            [
                                "name" => $name_category,
                                "parent" => $datos->id
                            ]
                        ]
                    ];
        
                    //print_r($woocommerce->post('products/categories', $data));
                    $resultado = $woocommerce->post('products/categories/batch', $data);
        
                    $categoria_producto = $resultado->create[0]->id;
                
        
                    //LUEGO LA RELACIONAMOS ENTRE LOS PRODUCTOS
                    $wpdb->insert(
                            "{$wpdb->prefix}mis_categorias",
                            array(
                                'invu'			=> $id_secundaria_invu,
                                'wp'			=> $categoria_producto,
                                'tipo'			=> '2'
                            )
                    );
                }
            }
        }
    }

    if($categoria_producto == ""){
        $data = [
            "create"    => [
                [
                    "name" => $name_category,
                    "parent" => $categoria_producto2
                ]
            ]
        ];

        //print_r($woocommerce->post('products/categories', $data));
        $resultado = $woocommerce->post('products/categories/batch', $data);

        $categoria_producto = $resultado->create[0]->id;
    

        //LUEGO LA RELACIONAMOS ENTRE LOS PRODUCTOS
        $wpdb->insert(
                "{$wpdb->prefix}mis_categorias",
                array(
                    'invu'			=> $id_secundaria_invu,
                    'wp'			=> $categoria_producto,
                    'tipo'			=> '2'
                )
        );
    }

    if($mantener_categorias == 1){


        //CONSULTAMOS LAS CATEGORIAS QUE NO DEBEMOS TOCAR
        //CATEGORIAS QUE NO PODEMOS TOCAR
        $categorias = [];

        $cat        = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}no_editar ");
        foreach ($cat as $key) {
            array_push($categorias, $key->wp);
        }
        
        
        $terms_post = get_the_terms( $id_producc , 'product_cat' );

        $devolver = [];

        if($terms_post){
            foreach ($terms_post as $term_cat) { 
                $term_cat_id = $term_cat->term_id; 
                
                if (in_array($term_cat_id, $categorias)) {
                    array_push($devolver, $term_cat_id);
                }
            }
        }
        

        if(!in_array($categoria_producto2, $devolver)){
            array_push($devolver, $categoria_producto2);
        }

        if(!in_array($categoria_producto, $devolver)){
            array_push($devolver, $categoria_producto);
        }

        //DEVOLVER FINAL
        $devolver_final = [];

        for($i = 0; $i < count($devolver); $i++){

            //echo "<h1>RECORREMOS</h1>";
            $momentaneo = [
                "id"    => $devolver[$i]
            ];

            array_push($devolver_final, $momentaneo);
        }

        /*
        echo "<h1>Pausa</h1>";
        echo "llega->";
        print_r($devolver_final);
        echo "<- finaliza";
        */
        
        
        return $devolver_final;
    }else{
        return [["id" => $categoria_producto]];
    }    
}

/**
 * CREAMOS LA SUB CATEGORIA
*/
function categoria_hija_nueva2($codigo, $id_producc = "", $mantener_categorias){

    //return 16;
    /*
    $categoriademo = [["id" => 16]];
    return $categoriademo;*/
    global $wpdb;
    global $wp_query;

    $api_key = consulta_parametros_a1("apikey");
    $mi_api  = consulta_parametros_a1("api");

    $woocommerce = woocommerce_api();

    //$data = ['update' => [], 'create' => []];

    $curl = "";

    $curl = curl_init();

    //LA API ES LA 5 PARA ESTO
    $mi_api = "api5";

    $url_consulta_api = "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=categoriaMenu/view/id/{$codigo}";
    //echo $url_consulta_api."<br>";
    $token_api = "apikey: {$api_key}";

    if($mi_api != "api"){
        $token_api = "TOKEN: {$api_key}";
    }
    curl_setopt_array($curl, array(
        CURLOPT_URL            => $url_consulta_api,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => "",
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => "GET",
        //CURLOPT_POSTFIELDS     => "{\n\t\"fecha_modificacion\": \"" . $fecha_consulta . "\"\n}",
        //CURLOPT_POSTFIELDS     => "{'fecha_modificacion':'{$fecha_consulta}'}",
        CURLOPT_HTTPHEADER     => array(
            $token_api,
        ),
    ));

    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $productos_invu = curl_exec($curl);

    if ($productos_invu === false) {
        //die(curl_error($ch2));
        $productos_invu = curl_error($ch2);
    }
    
    
    $manage = json_decode($productos_invu);
    //print_r($manage);
    //foreach ($manage->data as $key) {
        $idCategoria = "";
        $name_category = $manage->data->nombremenu;
        $categoria_producto = 0;//ESTE YA ES EL ID FINAL DE WP


        //BUSCAMOS LA CATEGORIA PRINCIPAL DE UNA VEZ
        $id_principal = $manage->data->idSubCategoriaMenu;

        //echo "<h2>La categoria buscada es: {$id_principal}</h2>";
        $idPrincipal = "";

        $categoria_padre = $wpdb->get_results("SELECT wp FROM {$wpdb->prefix}mis_categorias WHERE  invu = '{$id_principal}' AND tipo = '1' ");
        if(count($categoria_padre) > 0){
            $idPrincipal = $categoria_padre[0]->wp;
        }


        //echo "<h1>EL PADRE ES: {$idPrincipal}</h1>";

        $padre = $wpdb->get_results("SELECT wp FROM {$wpdb->prefix}mis_categorias WHERE  invu = '{$manage->data->idcategoriamenu}' AND tipo = '2' ");
        if(count($padre) <= 0){//SI LA CATEGORIA NO EXISTE DEBEMOS CREARLA

            //echo "<h1>No existe</h1>";

            if( $term = get_term_by( 'name', $name_category, 'product_cat' ) ){
                $categoria_producto = $term->term_id;
            }

            //echo "<h1>==================> {$categoria_producto}</h1>";

            if($categoria_producto == 0){//SI LA CATEGORIA NO EXISTE DEBEMOS CREARLA


                //echo "<h1>** ==> (({$manage->data->nombremenu} ))</h1>";


                $data = [
                    "create"    => [
                        [
                            "name" => $manage->data->nombremenu,
                            "parent"    => $idPrincipal
                        ]
                    ]
                ];

                //print_r($woocommerce->post('products/categories', $data));
                $resultado = $woocommerce->post('products/categories/batch', $data);

                //$idCategoria = $resultado->id;
                $idCategoria = $resultado->create[0]->id;
                $categoria_producto = $idCategoria;
            }

            //LUEGO LA RELACIONAMOS ENTRE LOS PRODUCTOS
            $wpdb->insert(
                    "{$wpdb->prefix}mis_categorias",
                    array(
                        'invu'			=> $manage->data->idcategoriamenu,
                        'wp'			=> $idCategoria,
                        'tipo'			=> '2'
                    )
            );

            //
            //$idCategoria = $wpdb->insert_id;
        }else{

            //echo "<h1>EXISTE< ===================</h1>";
            /*
            if( $term = get_term_by( 'name', $name_category, 'product_cat' ) ){
                $categoria_producto = $term->term_id;
            }
            */

            //YA ESTA INFORMACION LA TENEMOS
            $categoria_producto = $padre[0]->wp;

            //ACTUALIZAMOS LA CATEGORIA
            $data = [
                "update"    => [
                    [
                        "id"    => $categoria_producto,
                        'name' => $manage->data->nombremenu,
                        "parent"    => $idPrincipal
                    ]
                ]
                
            ];            

            $resultado = $woocommerce->post('products/categories/batch', $data);

            //$idCategoria = $resultado->id;
            $idCategoria = $resultado->update[0]->id;
            //$categoria_producto = $idCategoria;
            /*
            $wpdb->update(
                        "{$wpdb->prefix}mis_categorias",
                        array(
                            'wp'			=> $idCategoria,
                            'tipo'			=> '2'
                        ),
                        array( 'invu'		=> $manage->data->idcategoriamenu )
                    );
                    */
        }
    //}

    //YA CON LA CATEGORIA BUSCARAMOS SU PADRE O SU RELACION
    //$idPrincipal //es el padre
    if($id_producc == ""){
        return [["id" => $categoria_producto]];
    }else{
        //AQUI SI DEVOLVEREMOS LA CATEGORIA PADRE Y LA HIJA
        //[["id" => categoria_hija_nueva($key->codigo_categoriamenu)]]
        if($mantener_categorias == 1){
            //SI QUEREMOS MANTENER LAS CATEGORIAS DEBEMOS VALIDAR CUALES TENEMOS YA*
            
            $terms_post = get_the_terms( $id_producc , 'product_cat' );


            echo "<h1>Si llegamos a este punto.</h1>";

            print_r($terms_post);
            $devolver = [];

            if($terms_post){
                foreach ($terms_post as $term_cat) { 
                    $term_cat_id = $term_cat->term_id; 
                    //if(!in_array($term_cat, $term_cat))
                    //echo $term_cat_id;
    
                    //array_push($devolver, $term_cat);
                    array_push($devolver, $term_cat);
                }
            }
            

            if(!in_array($idPrincipal, $devolver)){
                array_push($devolver, $idPrincipal);

                echo "<h1>No existe ({$idPrincipal})</h1>";
            }else{
                echo "<h1>Si existe</h1>";
            }

            if(!in_array($categoria_producto, $devolver)){
                array_push($devolver, $categoria_producto);

                echo "<h1>No existe ({$categoria_producto})</h1>";


                echo "(";print_r($devolver);echo ") devolvimos.";
            }

            //DEVOLVER FINAL
            $devolver_final = [];

            for($i = 0; $i < count($devolver); $i++){
                $momentaneo = [
                    "id"    => $devolver[$i]
                ];

                array_push($devolver_final, $momentaneo);
            }


            echo "<h2>El devolver final es: </h2>";
            print_r($devolver_final);
            echo "<h2>Fin del devolver final!</h2>";

            return $devolver_final;
        }else{
            //DE LO CONTRARIO SOLO DEVOLVEMOS PADRE HE HIJO
            $devolver = [
                            [
                                "id" => $idPrincipal
                            ],
                            [
                                "id"   => $categoria_producto
                            ]
                        ];

            //echo "Y dice que si!";
            return $devolver;
        }
    }
    
}

/**
 * 2021 CREAR TODAS LAS CATEGORIAS
 */
//add_action('crear_Categorias_invu', 'crear_todas_categorias_wp');
//add_action("wp_footer", "crear_todas_categorias_wp");
function crear_todas_categorias_wp(){
    if(isset($_POST['actualizar_todas_categorias'])){
        global $wpdb;

        $woocommerce = woocommerce_api();

        $data = ['update' => [], 'create' => []];

        $curl = curl_init();

        $api_key = consulta_parametros_a1("apikey");
        $mi_api  = consulta_parametros_a1("api");

        $url_consulta_api = "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=menu/ListarSubcategorias";
        $token_api = "apikey: {$api_key}";

        if($mi_api != "api"){
            $token_api = "TOKEN: {$api_key}";
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL            => $url_consulta_api,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "GET",
            //CURLOPT_POSTFIELDS     => "{\n\t\"fecha_modificacion\": \"" . $fecha_consulta . "\"\n}",
            //CURLOPT_POSTFIELDS     => "{'fecha_modificacion':'{$fecha_consulta}'}",
            CURLOPT_HTTPHEADER     => array(
                $token_api,
            ),
        ));

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $productos_invu = curl_exec($curl);

        if ($productos_invu === false) {
            //die(curl_error($ch2));
            $productos_invu = curl_error($ch2);
        }
        //EMPEZAMOS CON TODAS LAS PADRES

        $manage = json_decode($productos_invu);
        foreach ($manage->data as $key) {
            $idCategoria = "";
            $name_category = $key->descripcion;
            $categoria_producto = 0;//ESTE YA ES EL ID FINAL DE WP

            $padre = $wpdb->get_results("SELECT wp FROM {$wpdb->prefix}mis_categorias WHERE  invu = '{$key->id}' AND tipo = '1' ");
            if(count($padre) <= 0){//SI LA CATEGORIA NO EXISTE DEBEMOS CREARLA

                if( $term = get_term_by( 'name', $name_category, 'product_cat' ) ){
                    $categoria_producto = $term->term_id;
                }

                if($categoria_producto == 0){//SI LA CATEGORIA NO EXISTE DEBEMOS CREARLA


                    $data = [
                        "create"    => [
                            [
                                "name" => $key->descripcion
                            ]
                        ]
                    ];

                    //print_r($woocommerce->post('products/categories', $data));
                    $resultado = $woocommerce->post('products/categories/batch', $data);

                    $idCategoria = $resultado->create[0]->id;
                }

                //LUEGO LA RELACIONAMOS ENTRE LOS PRODUCTOS
                $wpdb->insert(
                        "{$wpdb->prefix}mis_categorias",
                        array(
                            'invu'			=> $key->id,
                            'wp'			=> $idCategoria,
                            'tipo'			=> '1'
                        )
                );

                //
                //$idCategoria = $wpdb->insert_id;
            }else{

                if( $term = get_term_by( 'name', $name_category, 'product_cat' ) ){
                    $categoria_producto = $term->term_id;
                }

                //ACTUALIZAMOS LA CATEGORIA
                $data = [
                    'update' => [
                        [
                            'id'    => $categoria_producto,
                            'name' => $key->descripcion
                        ]
                    ]
                ];

                

                $resultado = $woocommerce->post('products/categories/batch', $data);

                //$idCategoria = $resultado->id;
                $idCategoria = $resultado->create[0]->id;

                $wpdb->update(
                            "{$wpdb->prefix}mis_categorias",
                            array(
                                'wp'			=> $idCategoria,
                                'tipo'			=> '1'
                            ),
                            array( 'invu'		=> $key->id )
                        );
            }
        }


        /**
         * LUEGO VAMOS CON LAS HIJAS
        */
        /*
        $curl = "";

        $curl = curl_init();

        $url_consulta_api = "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=menu/listarCategorias";
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $url_consulta_api,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "GET",
            //CURLOPT_POSTFIELDS     => "{\n\t\"fecha_modificacion\": \"" . $fecha_consulta . "\"\n}",
            //CURLOPT_POSTFIELDS     => "{'fecha_modificacion':'{$fecha_consulta}'}",
            CURLOPT_HTTPHEADER     => array(
                "apikey: {$api_key}",
            ),
        ));

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $productos_invu = curl_exec($curl);

        if ($productos_invu === false) {
            //die(curl_error($ch2));
            $productos_invu = curl_error($ch2);
        }
        
        
        $manage = json_decode($productos_invu);
        foreach ($manage->data as $key) {
            $idCategoria = "";
            $name_category = $key->descripcion;
            $categoria_producto = 0;//ESTE YA ES EL ID FINAL DE WP

            $padre = $wpdb->get_results("SELECT wp FROM {$wpdb->prefix}mis_categorias WHERE  invu = '{$key->id}' AND tipo = '1' ");
            if(count($padre) <= 0){//SI LA CATEGORIA NO EXISTE DEBEMOS CREARLA

                if( $term = get_term_by( 'name', $name_category, 'product_cat' ) ){
                    $categoria_producto = $term->id;
                }

                if($categoria_producto == 0){//SI LA CATEGORIA NO EXISTE DEBEMOS CREARLA


                    $data = [
                        "update"    => [
                            "name" => $key->nombremenu
                        ]
                    ];

                    //print_r($woocommerce->post('products/categories', $data));
                    $resultado = $woocommerce->post('products/categories/batch', $data);

                    $idCategoria = $resultado->id;
                }

                //LUEGO LA RELACIONAMOS ENTRE LOS PRODUCTOS
                $wpdb->insert(
                        "{$wpdb->prefix}mis_categorias",
                        array(
                            'invu'			=> $key->id,
                            'wp'			=> $idCategoria,
                            'tipo'			=> '1'
                        )
                );

                //
                //$idCategoria = $wpdb->insert_id;
            }else{

                if( $term = get_term_by( 'name', $name_category, 'product_cat' ) ){
                    $categoria_producto = $term->id;
                }

                //ACTUALIZAMOS LA CATEGORIA
                $data = [
                    "create"    => [
                        "id"    => $categoria_producto,
                        'name' => $key->nombremenu
                    ]
                    
                ];

                

                $resultado = $woocommerce->post('products/categories/batch', $data);

                $idCategoria = $resultado->id;

                $wpdb->update(
                            "{$wpdb->prefix}mis_categorias",
                            array(
                                'wp'			=> $idCategoria,
                                'tipo'			=> '1'
                            ),
                            array( 'invu'		=> $key->id )
                        );
            }
        }
        */


        echo "<h1>Categorias actualizadas</h1>";
    }    
}

/*
 *
 *    PRUEBA DE TRANSACCIONES
 *
 */

add_action("wp_footer", "pruebas_acciones_SOLO_a");
function pruebas_acciones_SOLO_a()
{
    // Hacer algo cada semana
    // Hacer algo cada hora
    if (isset($_GET['prueba_solo_a'])) {
        global $wpdb;

        $prueba_solo_a = $_GET['prueba_solo_a'];

        //VARIABLES DE FECHA
        $fecha_consulta = date("Y-m-d");
        $fecha_consulta = $fecha_consulta . " 00:01:00";

        if ($prueba_solo_a == 1) {
            $fecha_consulta = "2020-12-13 00:01:00";
        } else {
            $fecha_consulta = $prueba_solo_a . " 00:01:00";
        }

        //QUITAMOS LA FECHA
        if(isset($_GET['nofecha']) && $_GET['nofecha'] == "no"){
            $fecha_consulta = "";
        }

        echo "<h1>Pasamos <--------------------</h1>";


        //CONSULTAMOS SI PODEMOS CREAR PRODUCTOS NUEVOS
        $servicios       = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}que_actualizar  ");
        $crear_productos = 1;
        $mantener_categorias = 2;

        foreach ($servicios as $key) {
            if ($key->tipo == "crear") {
                $crear_productos = $key->valor;
            }
        }

        $servicios       = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tabla_cron  ");
        foreach ($servicios as $key) {
                if ($key->nombre == "crear_productos_nuevos") {
                    $crear_productos = $key->numero;
                }

                //REVISAMOS SI PODEMOS ACTUALIZAR LA CATEGORIA 
                if($key->nombre == "actualizar_categorias"){
                    $actualizar_categorias = $key->numero;
                }

                if($key->nombre == "mantener_categorias"){
                    $mantener_categorias = $key->numero;
                }
            }
        echo "<h1>Mantener categorias: {$mantener_categorias}</h1>";

        $podemos_crear = 0;

        if ($crear_productos == 1) {

            $podemos_crear = 1;
        }

        //ANTES DE HACER NADA CON ESTA FUNCTION DEBEMOS CONSULTAR SI TENEMOS ENCENDIDA LA OPCION DE ACTUALIZAR PRODUCTOS
        $actualizar_p       = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tabla_cron WHERE nombre = 'actualizar_productos' ");
        $actualizar_p_valor = $actualizar_p[0]->numero;

        if ($actualizar_p_valor == 1) {
            
            $productos_invu = productos_invu_paginar($fecha_consulta, "");

            
            echo "<h1>VIENEN LOS RESULTAODS</h1>";
            echo $productos_invu;

            if ($productos_invu === false) {
                die(curl_error($ch2));
                $productos_invu = curl_error($ch2);
            }

            //TENEMOS EL MODULO EN EL QUE VEREMOS QUE ACTUALIZAREMOS
            //REVISAMOS CUALES SON LOS PARAMETROS QUE SE QUIEREN ACTUALIZAR
            $nombre_producto      = 1;
            $descripcion_producto = 1;
            $precio_producto      = 1;
            $inventario_producto  = 1;

            $que_act = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}variables_productos_a ");
            foreach ($que_act as $key) {
                if ($key->nombre == "nombre_producto") {
                    $nombre_producto = $key->estado;
                }

                if ($key->nombre == "descripcion") {
                    $descripcion_producto = $key->estado;
                }

                if ($key->nombre == "precio") {
                    $precio_producto = $key->estado;
                }

                if ($key->nombre == "inventario") {
                    $inventario_producto = $key->estado;
                }
            }

            #COMENTADO SOLO PARA LA PRUEBA
            $woocommerce = woocommerce_api();

            $manage = json_decode($productos_invu);

            //REVISAMOS CUANTAS PAGINAS HAY
            $paginas = $manage->cantidadPaginas;


            echo "<h1>Cantidad de paginas {$paginas}</h1>";

            if($paginas == 0 && $manage->totalRegistros > 0){
                $paginas = 1;
            }

            for($pa = 1; $pa <= $paginas; $pa++){
                #RECORREMOS Y ACTUALIZAMOS LOS PRODUCTOS DE INU

                //HACEMOS DE NUEVO LA CONSULTA PARA OPTENER NUEVAMENTE LOS DATOS
                $productos_invu = productos_invu_paginar($fecha_consulta, $pa);

                echo "<h1>Enviamos nuevamente <==================== <br><br></h1>";
                echo "<h1>Si estamos en este modulo!</h1>";

                $manage_paginar = json_decode($productos_invu);

                $data = "";
                $data = ['update' => [], 'create' => []];

                foreach ($manage_paginar->data as $key) {

                    
                    //echo "<h1>{$key->idmenu} - {$key->nombre}</h1>";
    
                    //if (in_array($key->codigo, $id_wordpress)) {
                    $mi_producto2 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = '_sku' AND meta_value = '{$key->codigo}' ");
    
                    if (count($mi_producto2) > 0) {
                        $id_producc = end($mi_producto2); $id_producc = $id_producc->post_id;
        
                        $momentaneo = [];
                        $momentaneo = [
                            'id'           => $id_producc,
                            "manage_stock" => true,
                        ];
        
                        //DATOS CUSTOM
                        if ($nombre_producto == 1) {
                            $momentaneo['name'] = $key->nombre;
                        }
        
                        if ($descripcion_producto == 1) {
                            $momentaneo['descripcion']       = $key->descripcion;
                            $momentaneo['short_description'] = $key->descripcion;
                        }
        
                        if ($precio_producto == 1) {
                            $momentaneo['regular_price'] = $key->precioSugerido;
                        }
        
                        if ($inventario_producto == 1) {
                            $momentaneo['stock_quantity'] = $key->checkStock;
                        }

                        if($mantener_categorias == 1){
                            $momentaneo['categories'] = categoria_hija_nueva($key->codigo_categoriamenu, $id_producc, $mantener_categorias);
                            
                            print_r(categoria_hija_nueva($key->codigo_categoriamenu, $id_producc, $mantener_categorias));
                            echo "<h1><================= Revisa este!</h1>";
                        }
        
                        array_push($data['update'], $momentaneo);
        
                        $momentaneo = [];
                        //}
                    }else{

                        //VALIDAMOS SI PODEMOS CREAR ALGO
                        if($podemos_crear == 1){
                            //SI NO EXISTE LO CREAMOS
                            $momentaneo = [];
                            $momentaneo = [
                                //'id'           => $id_producc,
                                "manage_stock" => true,
                            ];
            
                            //DATOS CUSTOM
                            if ($nombre_producto == 1) {
                                $momentaneo['name'] = $key->nombre;
                            }
            
                            if ($descripcion_producto == 1) {
                                $momentaneo['descripcion']       = $key->descripcion;
                                $momentaneo['short_description'] = $key->descripcion;
                            }
            
                            if ($precio_producto == 1) {
                                $momentaneo['regular_price'] = $key->precioSugerido;
                            }
            
                            if ($inventario_producto == 1) {
                                $momentaneo['stock_quantity'] = $key->checkStock;
                            }

                            if($mantener_categorias == 1){
                                $momentaneo['categories'] = categoria_hija_nueva($key->codigo_categoriamenu, $id_producc, $mantener_categorias);
                                
                                print_r(categoria_hija_nueva($key->codigo_categoriamenu, $id_producc, $mantener_categorias));
                                echo "<h1><================= Revisa este!</h1>";
                            }
            
                            //array_push($data['create'], $momentaneo);//COMENTADO PARA QUE NO SE CREE NADA
            
                            $momentaneo = [];
                        }
                        
                        //}
                    }
                    //}
                }
    
                $resultado = $woocommerce->post('products/batch', $data);
    
                echo "<h1>==========================================================================</h1>";
                print_r($resultado);
    
                $resultado = json_encode($resultado);
    
                $wpdb->insert(
                    "{$wpdb->prefix}consultas_respuestas",
                    array(
                        'fecha'     => $fecha_consulta,
                        'consulta'  => $productos_invu,
                        'respuesta' => $resultado,
                        'paginas'   => $manage_paginar->cantidadPaginas,
                        'pagina'    => $pa
                    )
                );
                
                #FIN DE RECORRIDO DE PRODUCTOS INVU
            }

        }
    }

}


function productos_invu_paginar($fecha_consulta = "", $posicion = ""){
    $curl = curl_init();

    //SETEAMOS LOS PARAMETROS DE LA API
    $api_key = consulta_parametros_a1("apikey");
    $mi_api  = consulta_parametros_a1("api");

    $paginas = "/limit/50/pagina/1";

    if($posicion != ""){
        $paginas = "/limit/50/pagina/".$posicion;
    }else{
        $paginas = "/limit/50/pagina/1";
    }

    $token_api = "apikey: {$api_key}";

    if($mi_api != "api"){
        $token_api = "TOKEN: {$api_key}";
    }

    curl_setopt_array($curl, array(
        CURLOPT_URL            => "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=menu/listarItems/agrupacion/true/checkStock/true".$paginas,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => "",
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => "GET",
        CURLOPT_POSTFIELDS     => "{\n\t\"fecha_modificacion\": \"" . $fecha_consulta . "\"\n}",
        //CURLOPT_POSTFIELDS     => "{'fecha_modificacion':'{$fecha_consulta}'}",
        CURLOPT_HTTPHEADER     => array(
            $token_api,
        ),
    ));

    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $productos_invu = curl_exec($curl);

    return $productos_invu;
}

function productos_invu_paginar_20($fecha_consulta = "", $posicion = ""){
    $curl = curl_init();

    //SETEAMOS LOS PARAMETROS DE LA API
    $api_key = consulta_parametros_a1("apikey");
    $mi_api  = consulta_parametros_a1("api");

    $paginas = "/limit/20/pagina/1";

    if($posicion != ""){
        $paginas = "/limit/20/pagina/".$posicion;
    }else{
        $paginas = "/limit/20/pagina/1";
    }

    $token_api = "apikey: {$api_key}";

    if($mi_api != "api"){
        $token_api = "TOKEN: {$api_key}";
    }

    curl_setopt_array($curl, array(
        CURLOPT_URL            => "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=menu/listarItems/agrupacion/true/checkStock/true".$paginas,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => "",
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => "GET",
        CURLOPT_POSTFIELDS     => "{\n\t\"fecha_modificacion\": \"" . $fecha_consulta . "\"\n}",
        //CURLOPT_POSTFIELDS     => "{'fecha_modificacion':'{$fecha_consulta}'}",
        CURLOPT_HTTPHEADER     => array(
            $token_api,
        ),
    ));

    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $productos_invu = curl_exec($curl);

    return $productos_invu;
}

/*
 **
CERADOR DE PRODUCTOS
 **
 */

//AGREGAMOS LA ACCION AL ACTUALIZADOR DE PRODUCTOS
add_action('cyb_weekly_cron_crear_p', 'cyb_do_this_crear_p');
function cyb_do_this_crear_p()
{
    // Hacer algo cada semana
    // Hacer algo cada hora
    global $wpdb;

    //ANTES QUE NADA DEBEMOS REVISAR SI TENEMOS ENCENDIDO EL CREADOR DE PRODUCTOS

    //ANTES DE HACER NADA CON ESTA FUNCTION DEBEMOS CONSULTAR SI TENEMOS ENCENDIDA LA OPCION DE ACTUALIZAR PRODUCTOS
    $actualizar_p       = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tabla_cron WHERE nombre = 'crear_productos_nuevos' ");
    $actualizar_p_valor = $actualizar_p[0]->numero;

    if ($actualizar_p_valor == 1) {
        //que_actualizar

        $servicios       = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}que_actualizar  ");
        $crear_productos = 1;

        foreach ($servicios as $key) {
            if ($key->tipo == "crear") {
                $crear_productos = $key->valor;
            }
        }

        if ($crear_productos == 1) {
            $woocommerce = woocommerce_api();

            $data = ['create' => []];

            $curl = curl_init();

            //SETEAMOS LOS PARAMETROS DE LA API
            $api_key = consulta_parametros_a1("apikey");
            $mi_api  = consulta_parametros_a1("api");

            $token_api = "apikey: {$api_key}";

            if($mi_api != "api"){
                $token_api = "TOKEN: {$api_key}";
            }

            curl_setopt_array($curl, array(
                CURLOPT_URL            => "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=menu/listarItems/agrupacion/true/checkStock/true",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => "",
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => "GET",
                CURLOPT_HTTPHEADER     => array(
                    $token_api,
                ),
            ));

            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            $productos_invu = curl_exec($curl);

            //TENEMOS EL MODULO EN EL QUE VEREMOS QUE ACTUALIZAREMOS
            //REVISAMOS CUALES SON LOS PARAMETROS QUE SE QUIEREN ACTUALIZAR
            $nombre_producto      = 1;
            $descripcion_producto = 1;
            $precio_producto      = 1;
            $inventario_producto  = 1;

            $que_act = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}variables_productos_a ");
            foreach ($que_act as $key) {
                if ($key->nombre == "nombre_producto") {
                    $nombre_producto = $key->estado;
                }

                //EL NOMBRE AL CREARCE SE DEBE VENIR
                $nombre_producto = $key->estado;

                if ($key->nombre == "descripcion") {
                    $descripcion_producto = $key->estado;
                }
                //LA DESCRIPCION TAMBIEN
                $descripcion_producto = $key->estado;

                if ($key->nombre == "precio") {
                    $precio_producto = $key->estado;
                }

                if ($key->nombre == "inventario") {
                    $inventario_producto = $key->estado;
                }
            }

            $mi_producto2 = $wpdb->get_results("SELECT p.*, (SELECT m.meta_value FROM {$wpdb->prefix}postmeta AS m WHERE m.post_id = p.ID AND m.meta_key = '_sku' ) AS meta_value FROM {$wpdb->prefix}posts AS p WHERE p.post_type = 'product' AND p.post_status = 'publish' ");

            //print_r($mi_producto2);

            $cantidad_productos = count($mi_producto2);

            //echo "<h1>Pasamos <--- </h1>";

            $cuentas_insert = 0;
            $manage         = json_decode($productos_invu);
            foreach ($manage->data as $key) {
                //echo "<h1>{$key->idmenu} - {$key->nombre}</h1>";

                if ($key->venta_online == true) {
                    $mi_producto2 = "";
                    $mi_producto2 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = '_sku' AND meta_value = '{$key->codigo}' ");

                    if (count($mi_producto2) <= 0) {
                        $cuentas_insert++;

                        //if($cuentas_insert <= 20){
                        //$id_producc = end($mi_producto2); $id_producc = $id_producc->post_id;

                        $momentaneo = [];
                        $momentaneo = [
                            //'id'                 => $id_producc,
                            "manage_stock" => true,
                        ];

                        //DATOS CUSTOM
                        if ($nombre_producto == 1) {
                            $momentaneo['name'] = $key->nombre;
                        }

                        $momentaneo['name'] = $key->nombre;

                        if ($descripcion_producto == 1) {
                            $momentaneo['descripcion']       = $key->descripcion;
                            $momentaneo['short_description'] = $key->descripcion;
                        }

                        $momentaneo['descripcion']       = $key->descripcion;
                        $momentaneo['short_description'] = $key->descripcion;

                        if ($precio_producto == 1) {
                            $momentaneo['regular_price'] = $key->precioSugerido;
                        }

                        if ($inventario_producto == 1) {
                            $momentaneo['stock_quantity'] = $key->checkStock;
                        }

                        $momentaneo['sku'] = $key->codigo;

                        //array_push($data['create'], $momentaneo);

                        $momentaneo = [];
                        //}

                    }
                }
            }

            $woocommerce->post('products/batch', $data);
        }
    }

}

add_action('cyb_monthly_cron_job', 'cyb_do_this_job_monthly');
function cyb_do_this_job_monthly()
{
    // Hacer algo cada mes
}

/*
 **
ANCLA PARA LAS FOTOS
 **
 */
//AGREGAMOS LA ACCION AL ACTUALIZADOR DE PRODUCTOS
//add_action('cyb_actualizar_imagenes', 'cyb_do_this_job_weekly_imagenes');
function cyb_do_this_job_weekly_imagenes()
{
    // Hacer algo cada semana
    // Hacer algo cada hora
    global $wpdb;

    //ANTES DE HACER NADA CON ESTA FUNCTION DEBEMOS CONSULTAR SI TENEMOS ENCENDIDA LA OPCION DE ACTUALIZAR PRODUCTOS
    $actualizar_p       = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tabla_cron WHERE nombre = 'actualizar_imagenes' ");
    $actualizar_p_valor = $actualizar_p[0]->numero;

    if ($actualizar_p_valor == 1) {

        $woocommerce = woocommerce_api();

        $data = ['update' => []];

        $curl = curl_init();

        //SETEAMOS LOS PARAMETROS DE LA API
        $api_key = consulta_parametros_a1("apikey");
        $mi_api  = consulta_parametros_a1("api");

        $token_api = "apikey: {$api_key}";

        if($mi_api != "api"){
            $token_api = "TOKEN: {$api_key}";
        }

        curl_setopt_array($curl, array(
            CURLOPT_URL            => "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=menu/listarItems/agrupacion/true/checkStock/true",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "GET",
            CURLOPT_HTTPHEADER     => array(
                $token_api,
            ),
        ));

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $productos_invu = curl_exec($curl);

        //TENEMOS EL MODULO EN EL QUE VEREMOS QUE ACTUALIZAREMOS
        //REVISAMOS CUALES SON LOS PARAMETROS QUE SE QUIEREN ACTUALIZAR
        $nombre_producto      = 1;
        $descripcion_producto = 1;
        $precio_producto      = 1;
        $inventario_producto  = 1;

        /* CONSULTAR QUE ACTIVAREMOS Y QUE NO YA NO ES NECESARIO
        $que_act = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}variables_productos_a ");
        foreach ($que_act as $key) {
        if($key->nombre == "nombre_producto"){
        $nombre_producto = $key->estado;
        }

        if($key->nombre == "descripcion"){
        $descripcion_producto = $key->estado;
        }

        if($key->nombre == "precio"){
        $precio_producto = $key->estado;
        }

        if($key->nombre == "inventario"){
        $inventario_producto = $key->estado;
        }
        }
         */

        $mi_producto2 = $wpdb->get_results("SELECT p.*, (SELECT m.meta_value FROM {$wpdb->prefix}postmeta AS m WHERE m.post_id = p.ID AND m.meta_key = '_sku' ) AS meta_value FROM {$wpdb->prefix}posts AS p WHERE p.post_type = 'product' AND p.post_status = 'publish' ");

        //print_r($mi_producto2);

        $cantidad_productos = count($mi_producto2);

        //CONSULTAREMOS LA SECUENCIA
        $secu = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}secuencia_act_img ORDER BY id DESC LIMIT 1");

        if (count($secu) == 1) {
            //
            if ($secu[0]->hasta <= count($mi_producto2)) {
                $desde = $secu[0]->hasta;
                $hasta = $desde + 5;
            } else {
                $desde = 0;
                $hasta = 5;
            }
        } else {
            $desde = 0;
            $hasta = 5;
        }

        //echo "<h1>Pasamos <--- </h1>";

        $id_wordpress = [];
        for ($i = 0; $i < $cantidad_productos; $i++) {

            if ($i >= $desde && $i <= $hasta) {
                //echo "<h1>NN Estos son todos los productos -> {$mi_producto2[$i]->meta_value}</h1>";

                array_push($id_wordpress, $mi_producto2[$i]->meta_value);
            }
        }

        //array_push($id_wordpress, "RGI-7612P");

        //AL FINALIZAR INSERTAMOS QUE SE RECORRIO
        $wpdb->insert(
            "{$wpdb->prefix}secuencia_act_img",
            array(
                'desde' => $desde,
                'hasta' => $hasta,
            )
        );

        $manage  = json_decode($productos_invu);
        $cuantos = 0;
        foreach ($manage->data as $key) {
            $cuantos++;
            //echo "<h1>{$key->idmenu} - {$key->nombre}</h1>";

            $mi_producto2 = "";

            if (in_array($key->codigo, $id_wordpress)) {
                //if ($cuantos <= 1) {

                $mi_producto2 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = '_sku' AND meta_value = '{$key->codigo}' ");

                if (count($mi_producto2) > 0) {
                    $id_producc = end($mi_producto2); $id_producc = $id_producc->post_id;
                }

                //
                //    NUEVO CODIGO PARA ACTUALIZAR LAS IMAGENES
                //
                $tipo_img = "admin";

                $imagen = "https://" . $tipo_img . ".invupos.com/invuPos/images/banner/" . $key->imagen;

                $imagen_espacios = $key->imagen;

                $imagen_espacios = str_replace(" ", "20", $imagen_espacios);
                $imagen_espacios = str_replace(array(".jpg", ".JPG", ".jpeg", ".png"), "", $imagen_espacios);

                $att_id = 0;

                $mi_imagen = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}posts WHERE post_title LIKE '%" . $imagen_espacios . "%'  AND post_type = 'attachment' ");

                if (count($mi_imagen) > 0) {
                    $att_id = $mi_imagen[0]->ID;

                    //echo "(La imagen si existe {$imagen_espacios} - {$att_id})";
                }

                if ($att_id != 0) {

                    //echo "(La imagen no existe {$imagen_espacios} - {$att_id})";

                    set_post_thumbnail($id_producc, $att_id);

                    $momentaneo = [];
                    $momentaneo = [
                        'id'         => $id_producc,
                        'attributes' => [
                            [
                                'name'      => 'Invu',
                                'position'  => 0,
                                'visible'   => true,
                                'variation' => true,
                                'options'   => [
                                    $id_producto,
                                ],
                            ],
                        ],
                    ];

                    array_push($data['update'], $momentaneo);
                    $momentaneo = [];
                } else {

                    $tamano       = getRemoteFileSize($imagen);
                    $tamano_final = convertToReadableSize2($tamano);

                    if ($tamano_final < 300) {
                        $momentaneo = [];
                        $momentaneo = [

                            'id'         => $id_producc,
                            'images'     => [
                                [
                                    'src' => $imagen,
                                ],
                            ],
                            'attributes' => [
                                [
                                    'name'      => 'Invu',
                                    'position'  => 0,
                                    'visible'   => true,
                                    'variation' => true,
                                    'options'   => [
                                        $id_producto,
                                    ],
                                ],
                            ],
                        ];

                        array_push($data['update'], $momentaneo);
                        $momentaneo = [];
                    }
                }

                //
                //    FIN DEL CODIGO PARA ACTUALIZAR LAS IMAGENES
                //
            }
        }

        $woocommerce->post('products/batch', $data);
    }

}

/*
 **
CERADOR DE PRODUCTOS
 **
 */

//AGREGAMOS LA ACCION AL ACTUALIZADOR DE PRODUCTOS
add_action('cyb_weekly_cron_borrar_p', 'cyb_do_this_borrar_productos');
function cyb_do_this_borrar_productos()
{
    // Hacer algo cada semana
    // Hacer algo cada hora
    global $wpdb;

    //ANTES DE HACER NADA CON ESTA FUNCTION DEBEMOS CONSULTAR SI TENEMOS ENCENDIDA LA OPCION DE ACTUALIZAR PRODUCTOS
    $actualizar_p       = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tabla_cron WHERE nombre = 'borrar_productos' ");
    $actualizar_p_valor = $actualizar_p[0]->numero;

    if ($actualizar_p_valor == 1) {
        //ANTES QUE NADA DEBEMOS REVISAR SI TENEMOS ENCENDIDO EL CREADOR DE PRODUCTOS
        //que_actualizar

        $servicios        = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}que_actualizar  ");
        $borrar_productos = 1;

        foreach ($servicios as $key) {
            if ($key->tipo == "borrar") {
                $borrar_productos = $key->valor;
            }
        }

        if ($borrar_productos == 1) {
            $woocommerce = woocommerce_api();

            $data = ['delete' => []];

            $curl = curl_init();

            //SETEAMOS LOS PARAMETROS DE LA API
            $api_key = consulta_parametros_a1("apikey");
            $mi_api  = consulta_parametros_a1("api");

            $token_api = "apikey: {$api_key}";

            if($mi_api != "api"){
                $token_api = "TOKEN: {$api_key}";
            }

            curl_setopt_array($curl, array(
                CURLOPT_URL            => "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=menu/listarItems/agrupacion/true/checkStock/true",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => "",
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => "GET",
                CURLOPT_HTTPHEADER     => array(
                    $token_api,
                ),
            ));

            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            $productos_invu = curl_exec($curl);

            //CATEGORIAS QUE NO PODEMOS TOCAR
            $categorias = [];
            $cat        = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}no_editar ");
            foreach ($cat as $key) {
                array_push($categorias, $key->wp);
            }

            $id_invu = [];
            $manage  = json_decode($productos_invu);
            foreach ($manage->data as $key) {
                if ($key->venta_online == true) {
                    array_push($id_invu, $key->codigo);
                }
            }

            //
            $mi_producto2   = $wpdb->get_results("SELECT s.* FROM {$wpdb->prefix}postmeta AS s WHERE s.meta_key = '_sku' ");
            $id_wordpress   = [];
            $id_wordpress_e = [];
            foreach ($mi_producto2 as $key) {
                //echo "<h1>{$key->id}</h1>";
                //echo "<h1>{$key->attributes[0]->options[0]}</h1>";

                //array_push($id_wordpress, $key->attributes[0]->options[0]);
                //if($key->wp == ""){
                array_push($id_wordpress, $key->meta_value);

                //REVISAMOS DE UNA SI ESTO EXISTE EN INVU
                if (in_array($key->meta_value, $id_invu)) {

                } else {
                    //array_push($id_wordpress_e, $key->post_id);

                    //CONSULTAMOS LA CATEGORIA A LA QUE PERTENECE
                    $la_categoria = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}term_relationships WHERE object_id = {$key->post_id} ");

                    $si_borra = 1;

                    foreach ($la_categoria as $key_la) {
                        if (in_array($key_la->term_taxonomy_id, $categorias)) {
                            $si_borra++;
                        }
                    }
                    if ($si_borra <= 1) {
                        array_push($data['delete'], $key->post_id);
                    }
                }
                //}
            }

            //RECORREMOS TODO EL ARREGLO PARA SABER QUE PRODUCTO NO ESTA EN INVU
            $woocommerce->post('products/batch', $data);

        }
    }
}

//
function cyb_do_this_job_weekly_imagenes_bb()
{
    // Hacer algo cada semana
    // Hacer algo cada hora
    global $wpdb;
    $woocommerce = woocommerce_api();

    $data = ['update' => []];

    $curl = curl_init();

    //SETEAMOS LOS PARAMETROS DE LA API
    $api_key = consulta_parametros_a1("apikey");
    $mi_api  = consulta_parametros_a1("api");

    $token_api = "apikey: {$api_key}";

    if($mi_api != "api"){
        $token_api = "TOKEN: {$api_key}";
    }

    curl_setopt_array($curl, array(
        CURLOPT_URL            => "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=menu/listarItems/agrupacion/true/checkStock/true",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => "",
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => "GET",
        CURLOPT_HTTPHEADER     => array(
            $token_api,
        ),
    ));

    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $productos_invu = curl_exec($curl);

    $manage  = json_decode($productos_invu);
    $cuantos = 0;
    foreach ($manage->data as $key) {
        $cuantos++;
        //echo "<h1>{$key->idmenu} - {$key->nombre}</h1>";

        $mi_producto2 = "";

        //if (in_array($key->codigo, $id_wordpress)) {
        if ($cuantos <= 1) {

            $mi_producto2 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}postmeta WHERE meta_key = '_sku' AND meta_value = '{$key->codigo}' ");

            if (count($mi_producto2) > 0) {
                $id_producc = end($mi_producto2); $id_producc = $id_producc->post_id;
            }

            //
            //    NUEVO CODIGO PARA ACTUALIZAR LAS IMAGENES
            //
            $tipo_img = "admin";

            $imagen = "https://" . $tipo_img . ".invupos.com/invuPos/images/banner/" . $key->imagen;

            $imagen_espacios = $key->imagen;

            $imagen_espacios = str_replace(" ", "20", $imagen_espacios);
            $imagen_espacios = str_replace(array(".jpg", ".JPG", ".jpeg", ".png"), "", $imagen_espacios);

            $att_id = 0;

            //$id_producc = "RGI-7612P";

            $wpdb->insert(
                "{$wpdb->prefix}secuencia_act_img",
                array(
                    'desde' => $desde,
                    'hasta' => $imagen,
                )
            );

            $momentaneo = [];
            $momentaneo = [

                'id'     => $id_producc,
                'images' => [
                    [
                        'src' => $imagen,
                    ],
                ],
            ];

            array_push($data['update'], $momentaneo);
            $momentaneo = [];
        }
    }

    $woocommerce->post('products/batch', $data);

}

register_deactivation_hook(__FILE__, 'cyb_plugin_deactivation');
function cyb_plugin_deactivation()
{
    wp_clear_scheduled_hook('cyb_weekly_cron_job');
    wp_clear_scheduled_hook('cyb_monthly_cron_job');

    //wp_clear_scheduled_hook('cyb_actualizar_imagenes');

    wp_clear_scheduled_hook('cyb_weekly_cron_crear_p');

    wp_clear_scheduled_hook('cyb_weekly_cron_borrar_p');

}
