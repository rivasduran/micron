<?php
/**
 * ARCHIVO PARA ENVIAR LAS ORDENES
 * BUSCAR LA REFERENCIA EN ESTE ARCHIVO: C:\Users\joser\Documents\proyectos\Keylimetec\saks\final
 */
// define the woocommerce_thankyou callback
function send_api_saks_orders($order_get_id)
{
    // make action magic happen here...
    //echo "<h1>--------------> llegamos a la orden, el id es: {$order_get_id}</h1>";

    //ANTES DE ENVIAR DEBEMOS VER EL ESTATUS DE LA ORDEN
    $order             = new WC_Order($order_get_id);
    $status            = $order->get_status();
    $send_api_erp_saks = $order->get_meta('send_api_erp_saks', true);

    $enviar = "no";

    $enviado_manual = "";

    if(isset($_POST['enviado_manual'])){
        $enviado_manual = $_POST['enviado_manual'];
    }

    //if ($status == "on-hold" && $send_api_erp_saks != "") {
    if ($status == "on-hold" && $send_api_erp_saks != "" || $status == "cancelled" && $send_api_erp_saks != "" || $enviado_manual == "si") {

        /*
        if ($status == "processing" && $send_api_erp_saks == "") {

        $enviar = "si";
        } else if ($status == "processing" && $send_api_erp_saks != "") {

        $enviar = "si";
        } else if ($send_api_erp_saks != "") {
        $enviar = "si";
        }

         */

        $transaccion_id = $order->get_transaction_id();

        if ($transaccion_id == "") {
            $transaccion_id = $order->get_meta('transaccion_id', true);
        }

        if ($transaccion_id != "") {
            $enviar = "si";
        }

        $status_orden = $order->get_meta('status_orden', true);
        //VALIDACION SOLO ENVIA 1 VEZ
        if ($status == $status_orden && $send_api_erp_saks == "yes") {
            $enviar = "no";
        }

        //FORZAR EL ENVIO MANUAL
        if (isset($_POST['enviado_manual']) && $_POST['enviado_manual'] == "si") {
            $enviar = "si";
        }

        if ($enviar == "si") {
            $data = armando_mi_variables($order_get_id);

            //echo json_encode($data);//MUESTRA ELL ARREGLO

            //ENVIAMOS LA DATA
            $ch2 = curl_init("http://api.saks.com.pa/ecommerce_service/api/Order");
            curl_setopt_array($ch2, array(
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => array(

                    'Content-Type: application/json',
                ),
                CURLOPT_POSTFIELDS     => json_encode($data),
            ));

            // Send the request
            $response2 = curl_exec($ch2);

            //echo "<h2>Pasamos!</h2>";
            // Check for errors
            if ($response2 === false) {
                die(curl_error($ch2));
            }

            //echo "<h1>----> LLEGA LA RESPUESTA!</h1>";

            //echo "<br><br><br>";

            //print_r($response2);

            $responseData2 = json_decode($response2, true);

            if ($responseData2 == 'Ok') {
                update_post_meta($order_get_id, 'send_api_erp_saks', esc_attr(htmlspecialchars("yes")));
                update_post_meta($order_get_id, 'status_orden', esc_attr(htmlspecialchars($status)));
            } else {
                update_post_meta($order_get_id, 'send_api_erp_saks', esc_attr(htmlspecialchars("no")));
                update_post_meta($order_get_id, 'mensaje_error_erp', esc_attr(htmlspecialchars($responseData2)));
                update_post_meta($order_get_id, 'status_orden', esc_attr(htmlspecialchars($status)));
            }

            //echo "Respuesta API SAKS: " . $responseData2;
        }

    }

}

// add the action
//add_action('woocommerce_thankyou', 'send_api_saks_orders', 10, 1);//LA DESACTIVAMOS PORUQE NO REQUERIMOS QUE SE CREE CUANDO SE FINALIZA LA COMPRA
//DETECTAMOS CUANDO EL ESTATUS DE UNA ORDEN CAMBIA
//add_action('woocommerce_order_status_changed', 'send_api_saks_orders', 10, 3);
add_action('woocommerce_update_order', 'send_api_saks_orders', 10, 1);

//woocommerce_payment_complete
//add_action('woocommerce_payment_complete', 'send_api_saks_orders', 10, 1);


?>