<?php
/**
 * ARCHIVO PARA ENVIAR LAS ORDENES
 * BUSCAR LA REFERENCIA EN ESTE ARCHIVO: C:\Users\joser\Documents\proyectos\Keylimetec\saks\final
 */
// define the woocommerce_thankyou callback
function sen_order_api_invu_pos($order_get_id)
{

    $woocommerce = woocommerce_api();

    

    //echo "<h1>TRAEMOS LOS METODOS DE PAGO DE INVU: </h1>";

    $api_key = consulta_parametros_a1("apikey");
    $mi_api  = consulta_parametros_a1("api");

    $token_api = "apikey: {$api_key}";

    if($mi_api != "api"){
        $token_api = '"TOKEN":'.'"'.$api_key.'"';
    }

    // make action magic happen here...
    //echo "<h1>--------------> llegamos a la orden, el id es: {$order_get_id}</h1>";

    //ANTES DE ENVIAR DEBEMOS VER EL ESTATUS DE LA ORDEN
    $order             = new WC_Order($order_get_id);
    $status            = $order->get_status();
    $send_api_erp_invu = $order->get_meta('send_api_erp_invu', true);


    if($status != "processing"){
        return false;
    }

    $enviar = "si";

    $enviado_manual = "";

    if(isset($_POST['enviado_manual'])){
        $enviado_manual = $_POST['enviado_manual'];
    }

    //if ($status == "on-hold" && $send_api_erp_invu != "") {
    //if ($status == "on-hold" && $send_api_erp_invu != "" || $status == "cancelled" && $send_api_erp_invu != "" || $enviado_manual == "si") {

        /*
        if ($status == "processing" && $send_api_erp_invu == "") {

        $enviar = "si";
        } else if ($status == "processing" && $send_api_erp_invu != "") {

        $enviar = "si";
        } else if ($send_api_erp_invu != "") {
        $enviar = "si";
        }

         */

        $transaccion_id = $order->get_transaction_id();

        // if ($transaccion_id == "") {
        //     $transaccion_id = $order->get_meta('transaccion_id', true);
        // }

        // if ($transaccion_id != "") {
        //     $enviar = "si";
        // }

        // $status_orden = $order->get_meta('status_orden', true);
        // //VALIDACION SOLO ENVIA 1 VEZ
        // if ($status == $status_orden && $send_api_erp_invu == "yes") {
        //     $enviar = "no";
        // }

        // //FORZAR EL ENVIO MANUAL
        // if (isset($_POST['enviado_manual']) && $_POST['enviado_manual'] == "si") {
        //     $enviar = "si";
        // }

        if ($enviar == "si") {
            $data = armando_mi_variables($order_get_id);

            //echo json_encode($data);//MUESTRA ELL ARREGLO

            //ENVIAMOS LA DATA
            // $ch2 = curl_init("https://api5.invupos.com/invuApiPos/index.php?r=citas/add");
            // curl_setopt_array($ch2, array(
            //     CURLOPT_POST           => true,
            //     CURLOPT_RETURNTRANSFER => true,
            //     CURLOPT_HTTPHEADER     => array(

            //         'Content-Type: application/json',
            //     ),
            //     CURLOPT_POSTFIELDS     => json_encode($data),
            // ));

            // // Send the request
            // $response2 = curl_exec($ch2);

            // //echo "<h2>Pasamos!</h2>";
            // // Check for errors
            // if ($response2 === false) {
            //     die(curl_error($ch2));
            // }

            //echo "<h1>----> LLEGA LA RESPUESTA!</h1>";

            //echo "<br><br><br>";

            //print_r($response2);

            //echo json_encode($data);

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => "https://".$mi_api.".invupos.com/invuApiPos/index.php?r=citas/add",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                //CURLOPT_POSTFIELDS => "",
                CURLOPT_COOKIE => "PHPSESSID=03fsbfl95pmmhtpvesp8lrtqsv",
                //CURLOPT_POSTFIELDS     => json_encode($data),
                CURLOPT_POSTFIELDS     => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    "TOKEN: $api_key"
            ],
            ]);

            $response = curl_exec($curl);

            $responseData2 = json_decode($response, true);

            $err = curl_error($curl);

            curl_close($curl);

            //print_r($responseData2);


            if(isset($_GET['orden_prueba'])){
                //print_r($responseData2);
            }



            // if ($responseData2 == 'Ok') {
            //     update_post_meta($order_get_id, 'send_api_erp_invu', esc_attr(htmlspecialchars("yes")));
            //     update_post_meta($order_get_id, 'status_orden', esc_attr(htmlspecialchars($status)));
            // } else {
            //     update_post_meta($order_get_id, 'send_api_erp_invu', esc_attr(htmlspecialchars("no")));
            //     update_post_meta($order_get_id, 'mensaje_error_erp', esc_attr(htmlspecialchars($responseData2)));
            //     update_post_meta($order_get_id, 'status_orden', esc_attr(htmlspecialchars($status)));
            // }

            //echo "Respuesta API SAKS: " . $responseData2;
        }

    //}

}

// add the action
add_action('woocommerce_thankyou', 'sen_order_api_invu_pos', 10, 1);//LA DESACTIVAMOS PORUQE NO REQUERIMOS QUE SE CREE CUANDO SE FINALIZA LA COMPRA
//DETECTAMOS CUANDO EL ESTATUS DE UNA ORDEN CAMBIA
//add_action('woocommerce_order_status_changed', 'sen_order_api_invu_pos', 10, 3);

add_action('woocommerce_update_order', 'sen_order_api_invu_pos', 10, 1);

//woocommerce_payment_complete
//add_action('woocommerce_payment_complete', 'sen_order_api_invu_pos', 10, 1);



/**
 * SECCION PARA ARMAR LA VARIABLES QUE SE ENVIAN
 */
//ARMANDO MI FORMATO JSON
//add_action("wp_footer", "armando_mi_variables");
function armando_mi_variables($order_get_id = "")
{
    global $wpdb;
    /*
    id_Ecommerce
    customer_Name
    customer_Last_Name
    total
    total_Tax
    status
     */

     if(isset($_GET['orden_prueba'])){
         //return "holas!";

     }

    $order_id = $order_get_id;

    //$order_id = 84;

    $order = new WC_Order($order_id);

    //print_r($order);

    // $la_orden = $order->get_line_tax();


    // echo count($order->tax_lines);
    // echo "<h1>Viene==></h1>";
    // print_r($order->get_line_tax());

    // echo "<br><br>";



    // echo "<br><br><br>";

    //$order->get_total();

    /**
     * SECCION DE CODIGO filthyfridaybocas tax_lines
     */
    $filthyfridaybocas = 1;
    $impuestosfilthyfridaybocas = 0;

    $handling_Fee = 0;

    if($filthyfridaybocas == 1){
        //AQUI ENTONCES DEBEMOS SACAR LOS DOS IMPUESTOS

        //print_r($order->get_line_tax());
        //$el_line_tax = json_encode($order->get_tax_lines());

        // Iterating through order fee items ONLY
        foreach( $order->get_items('fee') as $item_id => $item_fee ){

            // The fee name
            $fee_name = $item_fee->get_name();

            //echo "<h1>{$fee_name}</h1>";

            // The fee total amount
            $fee_total = $item_fee->get_total();

            //echo "<h1>{$fee_total}</h1>";

            // The fee total tax amount
            $fee_total_tax = $item_fee->get_total_tax();

            //echo "<h1>{$fee_total_tax}</h1>";


            if($fee_name == "Handling Fee"){
                $impuestosfilthyfridaybocas = (float)$impuestosfilthyfridaybocas + (float)$fee_total;

                $handling_Fee++;
            }
        }

        //TAX
        foreach( $order->get_items('tax') as $item_id => $item_fee ){

            // The fee name
            $rate_code = $item_fee->get_rate_code();

            //echo "<h1>{$rate_code}</h1>";

            // The fee total amount
            $tax_total = $item_fee->get_tax_total();

            //echo "<h1>{$tax_total}</h1>";

            // // The fee total tax amount
            // $fee_total_tax = $item_fee->get_total_tax();

            // echo "<h1>{$fee_total_tax}</h1>";

            if($rate_code == "SERVICE FEE-1"){
                $impuestosfilthyfridaybocas = (float)$impuestosfilthyfridaybocas + (float)$tax_total;
            }
        }

    //AHORA ASIGNAMOS LOS PRODUCTOS

    $array_productos = array();

    $datos_productos = "[";
    foreach ($order->get_items() as $item_id => $item) {

        //echo "<h1>-----------------------------------------</h1>";

        //print_r($item);

        //echo "<h1>-----------------------------------------</h1>";

        $product_id   = $item->get_product_id();
        $variation_id = $item->get_variation_id();
        $product      = $item->get_product();
        $name         = $item->get_name();
        $quantity     = $item->get_quantity();
        $subtotal     = $item->get_subtotal();
        $total        = $item->get_total();
        $tax          = $item->get_subtotal_tax();
        $taxclass     = $item->get_tax_class();
        $taxstat      = $item->get_tax_status();
        $allmeta      = $item->get_meta_data();
        $somemeta     = $item->get_meta('_whatever', true);
        $type         = $item->get_type();

        $product_t = wc_get_product($product_id);

        if ($variation_id != 0 && $variation_id != "") {
            $product_t = wc_get_product($variation_id);
        }

        $sku   = $product_t->get_sku();
        $price = $product_t->get_price();

        /*
        id
        name
        product_id
        variation_id
        quantity
        tax_class
        subtotal
        subtotal_tax
        total
        total_tax
        sku
        price
         */

        //$datos_productos .= "{oV: '".addslashes($key)."', oT: '".addslashes($value)."'},";

        $datos_productos .= '{';
        //$datos_producto  .= 'id: ' . $product_id . ', ';
        $datos_productos .= '"name": ' . $name . ', ';
        $datos_productos .= '"product_id": ' . $product_id . ', ';
        $datos_productos .= '"variation_id": ' . $variation_id . ', ';
        $datos_productos .= '"quantity": ' . $quantity . ', ';
        $datos_productos .= '"tax_class": ' . $tax_class . ', ';
        $datos_productos .= '"subtotal": ' . $subtotal . ', ';
        $datos_productos .= '"subtotal_tax": ' . $subtotal_tax . ', ';
        $datos_productos .= '"total": ' . $total . ', ';
        $datos_productos .= '"total_tax": ' . $total_tax . ', ';
        $datos_productos .= '"sku": ' . $sku . ', ';
        $datos_productos .= '"price": ' . $price . ', ';
        $datos_productos .= '"metaDataOrders": [] ';

        $datos_productos .= '},';

        //
        //ID_EC_PRODUCT
        // $variable_productos = array(
        //     'id_ec_product'  => $item_id,
        //     'name'           => $name,
        //     'product_id'     => $product_id,
        //     'variation_id'   => $variation_id,
        //     'quantity'       => $quantity,
        //     'tax_class'      => $tax_class,
        //     //'subtotal'       => $subtotal,
        //     'subtotal'       => $total,
        //     'subtotal_tax'   => $subtotal_tax,
        //     'total'          => $total,
        //     'total_tax'      => $total_tax,
        //     'sku'            => $sku,
        //     'price'          => $price,
        //     'metaDataOrders' => []
        // );
        

        $variable_productos = array(
            "item" => array(
                "precio"    => $price,
                "codigo"    => $sku,
                "nombre"    => $name,
                //"tax"       => $tax
                "tax"       => 7
            ),
            "gift"  => false,
            "cantidad" => $quantity,
        );

        array_push($array_productos, $variable_productos);

    }

    


        //echo "<h1>El total del producto TAX es: {$impuestosfilthyfridaybocas}</h1>";

        $tax_ser001 = null;

        if($handling_Fee == 0){
            $tax_ser001 =  number_format($impuestosfilthyfridaybocas*7/100 ,2);

            //SUMAMOS ESTE IMPUESTO
            $impuestosfilthyfridaybocas = (float)$impuestosfilthyfridaybocas + (float)$tax_ser001;
        }

        $variable_productos = array(
            "item" => array(
                "precio"    => $impuestosfilthyfridaybocas,
                "codigo"    => "SER001",
                "nombre"    => "Service Fee",
                "tax"       => null
            ),
            "gift"  => false,
            "cantidad" => 1,
        );

        array_push($array_productos, $variable_productos);
    }



    //$datos_productos = substr_replace($datos_productos, '', -1); // to get rid of extra comma
    $datos_productos .= "]";

    //SACAMOS EL ENVIO

    $order_item_name             = "";
    $order_item_type             = "";
    $shipping_method_title       = "";
    $shipping_method_id          = "";
    $shipping_method_instance_id = "";
    $shipping_method_total       = "";
    $shipping_method_total_tax   = "";
    $shipping_method_taxes       = "";
    //NOTAS
    $customer_note               = "";

    foreach ($order->get_items('shipping') as $item_id => $shipping_item_obj) {
        $order_item_name             = $shipping_item_obj->get_name();
        $order_item_type             = $shipping_item_obj->get_type();
        $shipping_method_title       = $shipping_item_obj->get_method_title();
        $shipping_method_id          = $shipping_item_obj->get_method_id(); // The method ID
        $shipping_method_instance_id = $shipping_item_obj->get_instance_id(); // The instance ID
        $shipping_method_total       = $shipping_item_obj->get_total();
        $shipping_method_total_tax   = $shipping_item_obj->get_total_tax();
        $shipping_method_taxes       = $shipping_item_obj->get_taxes();
    }

    $sucursal = $order->get_meta('_shipping_pickup_stores', true);

    //echo "<h1>=====================> la sucursal es: ({$sucursal})</h1>";

    if ($sucursal !== "") {
        $shipping_method_title = $sucursal;
    }

    //NUEVAS VARIABLES A ENVIAR
    //[ADDRESS],[TYPE_PAYMENT],[CODE_TRANSACTION],[DELIVERY_COST],[TYPE_DELIVERY],[PHONE]

    $country        = $order->get_billing_country();
    $state          = $order->get_billing_state();
    //NOTAS
    $customer_note  = $order->get_customer_note();

    $state_label = WC()->countries->get_states($country)[$state];

    $transaccion_id = $order->get_transaction_id();

    if ($transaccion_id == "") {
        $transaccion_id = $order->get_meta('transaccion_id', true);
    }

    //NUMERO DE AFILIADO
    $billing_wooccm13           = $order->get_meta('_billing_wooccm13');


    //echo "<h1>{$json}</h1>";

    //return $data;
    $data = array(
        "id_Ecommerce"         => $order_id,
        "customer_Name"        => $order->get_billing_first_name(),
        'customer_Last_Name'   => $order->get_billing_last_name(),
        'total'                => $order->get_total(),
        //'total_Tax'            => $order->get_total_tax(),
        'total_Tax'            => 0,//SIEMPRE CAE EN 0
        'status'               => $order->get_status(),
        'city'                 => $order->get_billing_city(),
        //'state'                => $order->get_billing_state(),
        'state'                => $state_label,
        'productList'          => $array_productos,
        'address'              => $order->get_billing_address_1() . " " . $order->get_billing_address_2(),
        'payment_method'       => $order->get_payment_method(),
        'payment_method_title' => $order->get_payment_method_title(),
        'code_transaction'     => $transaccion_id,
        'delivery_cost'        => $order->get_shipping_total(),
        'type_delivery'        => $shipping_method_title,
        'phone'                => $order->get_billing_phone(),
        'nota_cliente'         => $customer_note,
        'identification_card'      => $billing_wooccm13
    );

    $json = json_encode($data);
    

    //$order->get_payment_method()

    //echo "<h1>Tipo de pago: {$order->get_payment_method()}</h1>";

    //CONSULTAMOS EL METODO DE PAGO SEGUN INVU
    $mi_pago  = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}metodos_de_pago_invu WHERE id_woo = '".$order->get_payment_method()."' LIMIT 1");

    //print_r($mi_pago);

    $tipo_de_pago       = $mi_pago[0]->id_tipo_pago;
    $nombre_de_pago     = $mi_pago[0]->nombre;
    $id_tipo_de_pago    = $mi_pago[0]->id_invu;

    $data = array(
        "tipo_orden_obj"        => array(
                                        "id" => $order_id,
                                        "descripcion"   => "Pedido - ".$order_id
        ),
        "descripcion"           => "Pedido - ".$order_id,
        "comentario"            => $customer_note,
        "direccion"             => $order->get_billing_address_1() . " " . $order->get_billing_address_2(),
        "latitud"               => 0,
        "longitud"              => 0,
        "cerrar_orden"          => false,
        //"fecha_cierre"          => "",
        //"hora_entrega"          => "",
        //"fecha_entrega"         => "",
        "ecommerce"             => 1,
        "invitados"             => 1,
        // "descuento"             => array(
        //                             "id"            => 50,
        //                             "tipo"          => 5,
        //                             "monto"         => 3,
        //                             "isExento"      => false,
        //                             "nombre"        => "descuento prueba",
        //                             "isPorcentaje"  => false
        // ),
        //"propinas"              => array(),
        "pagos"                 => array(array(
                                    "tarjeta_digitos" => "",
                                    "monto"             => $order->get_total(),
                                    "fecha"             => modificar_fecha_orden($order->get_date_created()),
                                    "pago" => array(
                                                "tipo"      => $tipo_de_pago,
                                                "nombre"    => $nombre_de_pago,
                                                "id"        => $id_tipo_de_pago
                                    ))
        ),
        "empleado"              => array(
            "apellidos" => "Tech.",
            "nombres"   => "Pineapple",
            "id"        => "0"
        ),
        "cliente"               => array(
                                    "phone1"            => $order->get_billing_phone(),
                                    "ruc"               => "",
                                    "id"                => "nuevo",
                                    "address"           => "",
                                    "modificar"         => false,
                                    "email"             => "",
                                    "apellidos"         => $order->get_billing_last_name(),
                                    "nombres"           => $order->get_billing_first_name()
        ),
        "num_cita"              => $order_id,
        // "items"                 => array(array(
        //                             "items" => array(
        //                                 "precio" => 1,
        //                                 "codigo"    => "01.04.2022",
        //                                 "nombre"    => "Filthy Friday New Years Eve - December 30, 2021",
        //                                 "tax"       => 0
        //                             ),
        //                             "gift"  => false,
        //                             "cantidad" => 1,
        //                             )
        // )
        "items"                 => $array_productos

    );

    $json = json_encode($data);

    //VARIABLES DE INVU

    //echo "<h1>{$json}</h1>";
    if(isset($_GET['orden_prueba'])){
        //echo "<h1>{$json}</h1>";
        return $data;
        
        
    }else{
        return $data;
    }
    
    
}

/**
 * TRANSACCION DE PRUEBA
 */



add_action("wp_footer", "ordenes_de_prueba");
function ordenes_de_prueba(){
    //return "si pasamos!";
    if(isset($_GET['orden_prueba'])){

        //echo "<h1>ESTAMOS EN LA ORDEN DE PRUEBA!</h1>";
        $orden_prueba = $_GET['orden_prueba'];
        //armando_mi_variables($orden_prueba);


        //CON ESTE ENVIAMOS DE UNA A INVU
        sen_order_api_invu_pos($orden_prueba);

    }
    
}


function modificar_fecha_orden($fecha){
    $fecha = str_replace(".000000", "", $fecha);
    $fecha = str_replace("T", " ", $fecha);

    // 00:00
    $fecha = str_replace(" 00:00", "", $fecha);

    //+00:00
    $fecha = str_replace("+00:00", "", $fecha);
    return $fecha;
}

?>