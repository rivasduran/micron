<?php
/**
 * ACCIONES PARA ACTUALIZAR TODO MANUAL
 */
function actualizar_manual_invu(){
    //AQUI TENDREMOS LA SECCION PARA ACTUALIZAR DE MANERA MANUAL EL INVU

    /**
     * DEFINIMOS UN BOTON
    */
    echo '<button class="click_actualizar_manual">Actualizar Manual</button>';



    ?>


    <style>
        .click_actualizar_manual{
            display: inline-block;
            font-weight: 400;
            line-height: 1.5;
            color: #212529;
            text-align: center;
            text-decoration: none;
            vertical-align: middle;
            cursor: pointer;
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none;
            background-color: transparent;
            border: 1px solid transparent;
            padding: .375rem .75rem;
            font-size: 1rem;
            border-radius: .25rem;
            transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;

            color: #fff;
            background-color: #198754;
            border-color: #198754;

            margin-top: 50px;
            margin-bottom: 30px;
        }


        .agrega_respuestas{
            width: 100%!important;
            border-collapse: collapse;

        }

        .agrega_respuestas tr{
            border: solid 1px #e1e1e1!important;
        }

        .tabla_respuesta{
            max-height: 2500px!important;
            overflow-y: scroll;
        }
    </style>

    <div class="seccion_respuesta">
        <div class="texto_informativo"></div>


        <div class="tabla_respuesta">
            <table class="agrega_respuestas">
                <tr>
                    <td>Nombre</td>
                    <td>SKU</td>
                    <td>Stock</td>
                    <td>Accion</td>
                </tr>
            </table>
        </div>
        
    </div>

    <?php

    /**
     * AQUI DEBERIAMOS TENER LA BARRA DE PROGRESO
     */


    /**
     * Y AHORA EL SCRIP
     */
    $api_key = consulta_parametros_a1("apikey");
    $mi_api  = consulta_parametros_a1("api");

    $token_api = "apikey: {$api_key}";

    if($mi_api != "api"){
        $token_api = '"TOKEN":'.'"'.$api_key.'"';
    }

    ?>
        <script>
            jQuery(function(){
                //CLICK EN EL BOTON
                jQuery(".click_actualizar_manual").click(function(){
                    //PRIMERO DEBEMOS SABER CUANTAS PAGINAS SON, PARA ESO UTILIZAMOS AJAX
                    actualizar_todo_invu_a();

                    jQuery(".texto_informativo").text("Espere unos segundos...!");
                });
            });



            function actualizar_todo_invu_a(){
                const settings = {
                "async": true,
                "crossDomain": true,
                "url": "https://<?php  echo $mi_api; ?>.invupos.com/invuApiPos/index.php?r=menu/listarItems/online/1/checkStock/true/limit/10/pagina/1",
                "method": "GET",
                "headers": {
                    //"cookie": "PHPSESSID=d95po678gpf5jpkr3trpp28in5",
                    "Content-Type": "application/json",
                    <?php  echo $token_api; ?>
                },
                "processData": false,
                "data": ""
                };

                jQuery.ajax(settings).done(function (response) {
                    console.log("Llegamos y el resultado es: ");
                    console.log(response);
                    var totalRegistros = response.totalRegistros;
                    var totalFiltrado = response.totalFiltrado;
                    var cantidadPaginas = response.cantidadPaginas;

                    //AHORA SI PODEMOS PROCEDES A REALIZAR UN CICLO FOR CON LAS NUEVAS DIRECTROCES
                    console.log("Estamos enviando a al nueva pagina!");
                    actualizar_invu2(cantidadPaginas, 1);

                    
                });
            }


            function actualizar_invu2(cantidadPaginas, posicion){

                if(posicion <= cantidadPaginas){
                    var accion_realizar = "my_action_cron_actualizar_productos";
                    var datar = {
                        'action': accion_realizar,
                        //'tipo_img': Mi_api.url_img,
                        'posicion': posicion
                    };

                    //alert("---> "+datar.tipo_img);

                    var faltan = 1;

                    //alert("Llegamos "+data.action);

                    jQuery.post(ajaxurl, datar, function(response_final) {


                        //YA CON TODO LISTO ENVIAMOS AL PROCESO DE NUEVO
                        posicion++;

                        console.log("Volvemos a enviar!");
                        console.log(response_final);

                        //MOSTRAMOS POR DONDE VAMOS
                        jQuery(".texto_informativo").text("Total de paginas: "+cantidadPaginas+" | Pagina actual: "+posicion);


                        jQuery(".agrega_respuestas").append(response_final);

                        actualizar_invu2(cantidadPaginas, posicion);
                    });
                }
                
            }

            function actualizar_invu(cantidadPaginas, posicion){
                console.log("Llegamos a la nueva pagina cantidad: "+cantidadPaginas+" posicion"+posicion );
                if(posicion <= cantidadPaginas){
                    console.log("Entramos en el nuevo ciclo!");


                    const settings = {
                      "async": true,
                      "crossDomain": true,
                      "url": "https://<?php  echo $mi_api; ?>.invupos.com/invuApiPos/index.php?r=menu/listarItems/online/1/checkStock/true/limit/10/pagina/"+posicion,
                      "method": "GET",
                      "headers": {
                        //"cookie": "PHPSESSID=d95po678gpf5jpkr3trpp28in5",
                        "Content-Type": "application/json",
                        <?php  echo $token_api; ?>
                      },
                      "processData": false,
                      "data": ""
                    };

                    jQuery.ajax(settings).done(function (response) {
                        console.log("Llegamos y el resultado es: ");
                        console.log(response);
                        var totalRegistros = response.totalRegistros;
                        var totalFiltrado = response.totalFiltrado;
                        var cantidadPaginas = response.cantidadPaginas;

                        //AHORA SI PODEMOS PROCEDES A REALIZAR UN CICLO FOR CON LAS NUEVAS DIRECTROCES
                        console.log("Pasamos por la pagina =======> "+posicion);

                        //AQUI DEBEMOS ENVIAR TODO A WOOCOMMERCE
                        

                        
                    });


                    //

                    
                }
            }
        </script>

    <?php
}


/**
 * OPCION PARA ACTUALIZAR TODO MANUAL
 */

add_action( 'wp_ajax_my_action_cron_actualizar_productos', 'my_action_cron_actualizar_productos' );
function my_action_cron_actualizar_productos() {

    if(isset($_POST['posicion'])){

        //echo "entramos en la posicion!";
        $posicion = $_POST['posicion'];
        //$manage = json_decode($_POST['productos']);
        //echo "nombre del producto {$posicion}!";

        //YA CON LA POSICION AHORA SI PROCEDEMOS A ACTUALIZAR TODO
        global $wpdb;

        //VARIABLES DE FECHA
        $fecha_consulta = date("Y-m-d");
        $fecha_consulta = $fecha_consulta . " 00:01:00";
        //$fecha_consulta = "2020-12-13 00:01:00";

        //ANTES DE HACER NADA CON ESTA FUNCTION DEBEMOS CONSULTAR SI TENEMOS ENCENDIDA LA OPCION DE ACTUALIZAR PRODUCTOS
        $actualizar_p       = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tabla_cron WHERE nombre = 'actualizar_productos' ");
        $actualizar_p_valor = $actualizar_p[0]->numero;



        //CONSULTAMOS SI PODEMOS CREAR PRODUCTOS NUEVOS
        $servicios       = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tabla_cron  ");
        $crear_productos = 1;
        $actualizar_categorias  = 2;//LO DEFINIMOS EN 2 YA QUE NO QUEREMOS ACTUALIZARLA DE FABRICA
        $mantener_categorias        = 2;

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

        //echo "<h1>LAS CATEGORIAS SON: {$mantener_categorias}</h1>";

        //EN ESTE NO ACTUALIZAREMOS CATEGORIAS
        //$actualizar_categorias = 2;

        $podemos_crear = 0;

        if ($crear_productos == 1) {

            $podemos_crear = 1;
        }


        $respuesta = "";

        $api_key = consulta_parametros_a1("apikey");
        $mi_api  = consulta_parametros_a1("api");

        if ($actualizar_p_valor == 1) {

            //echo "actualizaremos!";
            $woocommerce = woocommerce_api();

            $data = ['update' => [], 'create' => []];

            $curl = curl_init();


            $url_consulta_api = "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=menu/listarItems/online/1/checkStock/true/limit/10/pagina/{$posicion}";
            $url_consulta_api = "https://{$mi_api}.invupos.com/invuApiPos/index.php?r=menu/listarItems/agrupacion/true/checkStock/true/limit/10/pagina/{$posicion}";


            //echo "<h1>{$url_consulta_api} </h1>";
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

            /*
            echo "<h1>Importante ver: </h1>";
            echo json_encode($productos_invu);


            echo "<br><br>";
            */

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


            //print_r( $manage);
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


                        //LLENAMOS LA RESPUESTA
                        //echo $key->nombre;
                        $respuesta .= "<tr>";
                        $respuesta .= "<td>{$key->nombre}</td>";
                        $respuesta .= "<td>{$key->codigo}</td>";
                        $respuesta .= "<td>{$key->checkStock}</td>";
                        $respuesta .= "<td>Actualizado</td>";
                        $respuesta .= "</tr>";
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


                            $respuesta .= "<tr>";
                            $respuesta .= "<td>{$key->nombre}</td>";
                            $respuesta .= "<td>{$key->codigo}</td>";
                            $respuesta .= "<td>{$key->checkStock}</td>";
                            $respuesta .= "<td>Actualizado</td>";
                            $respuesta .= "</tr>";
                            //}
                        }
                        
                    }
                }else{
                    //SI ES UNA VARIACION LO MANDAMOS TODO A LA FUNCION DE VARIACION
                    variaciones_invu_pos( $nombre_producto, $descripcion_producto, $precio_producto, $inventario_producto, $key);
                
                    $respuesta .= "<tr>";
                    $respuesta .= "<td>{$key->nombre}</td>";
                    $respuesta .= "<td>{$key->codigo}</td>";
                    $respuesta .= "<td>{$key->checkStock}</td>";
                    $respuesta .= "<td>Actualizado <--- COMO VARIABLE</td>";
                    $respuesta .= "</tr>";
                }
                
            }

            $resultado = $woocommerce->post('products/batch', $data);
            $resultado = json_encode($resultado);


            //echo $resultado;
            /* COMENTADO POR LOS MOMENTOS
            $wpdb->insert(
                "{$wpdb->prefix}consultas_respuestas",
                array(
                    'fecha'     => $fecha_consulta,
                    'paginas'   => $paginas,
                    'pagina'    => $posicion,
                    'consulta'  => $productos_invu,
                    'respuesta' => $resultado,
                )
            );
            */

        }

        
    }
    //echo "caramba! {$posicion}";

    echo $respuesta;
    wp_die();
}

?>