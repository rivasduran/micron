<?php

//add_action("wp_footer", "traemos_pagos_invu");
function traemos_pagos_invu(){

    $woocommerce = woocommerce_api();

    //echo "<h1>TRAEMOS LOS METODOS DE PAGO DE INVU: </h1>";

    $api_key = consulta_parametros_a1("apikey");
    $mi_api  = consulta_parametros_a1("api");

    $token_api = "apikey: {$api_key}";

    if($mi_api != "api"){
        $token_api = '"TOKEN":'.'"'.$api_key.'"';
    }


    //echo "<h1> {$api_key} {$mi_api} ~ {$token_api}</h1>";

    $curl = curl_init();

    curl_setopt_array($curl, [
    CURLOPT_URL => "https://".$mi_api.".invupos.com/invuApiPos/index.php?r=metodosPago",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_POSTFIELDS => "",
    CURLOPT_COOKIE => "PHPSESSID=03fsbfl95pmmhtpvesp8lrtqsv",
    CURLOPT_HTTPHEADER => [
        "TOKEN: $api_key"
    ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        echo "cURL Error #:" . $err;
    } else {
        //echo $response;
    }

    $response = json_decode($response);

    return $response;


    // //RECORREMOS LOS METODOS DE PAGO
    // foreach($response->data as $resultados){
    //     echo "<h1>".$resultados->nombre."</h1>";
    // }

    // //TRAEMOS TODOS LOS METODOS DE PAGO DE WOOCOMMERCE
    // $metodos_pago_woocommerce = $woocommerce->get('payment_gateways');

    // //print_r($metodos_pago_woocommerce);

    // echo "<h1>Metodos de pago woo:</h1>";
    // foreach($metodos_pago_woocommerce as $pagos_woo){
    //     echo "<h2>{$pagos_woo->id}</h2>";
    // }

}

$tabla_metodos_pago = $wpdb->prefix."metodos_de_pago_invu";

function relacionador_metodos_pago_invu_woo($metodos_pago_invu){
    global $wpdb;
    global $tabla_metodos_pago;
    
    //GUARDAMOS TODAS LAS RELACIONES


    foreach($metodos_pago_invu->data as $metodos_pago){

        if(isset($_POST[$metodos_pago->id])){
            $wpdb->update( 
                    $tabla_metodos_pago,
                    array( 
                        'id_woo' 		=> $_POST[$metodos_pago->id]
                    ), 
                    array( 
                        'id_invu' 		=> $metodos_pago->id 
                    )
                );
        }
    }


    //VALIDAMOS SI EXISTE O NO EL METODO DE PAGO CREADO

    foreach($metodos_pago_invu->data as $metodos_pago){

        $activo = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_metodos_pago} WHERE id_invu = '".$metodos_pago->id."' ");

        if ($activo <= 0) {
            $wpdb->insert(
                $tabla_metodos_pago,
                array(
                    'id_invu'               => $metodos_pago->id,
                    'id_woo'                => '',
                    'nombre'                => $metodos_pago->nombre,
                    'id_tipo_pago'          => $metodos_pago->id_tipo_pago,
                    'desc_tipopago'         => $metodos_pago->desc_tipopago,
                    'id_fiscal'             => $metodos_pago->id_fiscal,
                    'emitir_fiscal'         => $metodos_pago->emitir_fiscal,
                    'pago_internacional'    => $metodos_pago->pago_internacional,
                    'id_tipo_pos'           => $metodos_pago->id_tipo_pos,
                    'tipo_pos_desc'         => $metodos_pago->tipo_pos_desc
                )
            );
        }

    }

    
}


function metodos_pago_woo(){
    global $wpdb;
    global $tabla_metodos_pago;

    $woocommerce = woocommerce_api();


    

    echo "<h1>Relacion de metodos de pago:</h1>";

    $metodos_pago_invu = traemos_pagos_invu();

    relacionador_metodos_pago_invu_woo($metodos_pago_invu);

    //TRAEMOS TODOS LOS METODOS DE PAGO DE WOOCOMMERCE
    $metodos_pago_woocommerce = $woocommerce->get('payment_gateways');
    

    //print_r($metodos_pago_woocommerce);

    //echo "<h1>Metodos de pago woo:</h1>";
    


    ?>
        <div class="formularios-approval quarterWidth formularioCertificados">
			<form method="post" action="">
			    <input type="text" name="data_metodos_pago" value="1" required style="display: none;" />

				<!-- SECCION DE LAS CATEGORIAS -->
				<div class="">

                <?php
                    foreach($metodos_pago_invu->data as $pagos_invu){

                        $activo = $wpdb->get_results("SELECT * FROM {$tabla_metodos_pago} WHERE id_invu = ".$pagos_invu->id." ");

                        

                        
                ?>

                        <div class="form-group">    
                            <label for="exampleInputEmail1"><?php echo $pagos_invu->nombre; ?></label>
                            <small class="form-text text-muted">Actualizamos las imagenes.</small>

                            <select name="<?php echo $pagos_invu->id; ?>"  class="form-control">
                                <option value="">Seleccione</option>

                <?php

                    foreach($metodos_pago_woocommerce as $pagos_woo){
                        $selected_selector = "";
                        //echo "<h2>{$pagos_woo->id}</h2>";
                        if(count($activo) > 0){
                            if($activo[0]->id_woo == $pagos_woo->id){
                                $selected_selector = "selected";
                            }
                        }
                ?>
                        <option value="<?php echo $pagos_woo->id; ?>" <?php echo $selected_selector; ?>><?php echo $pagos_woo->title;  ?></option>

                <?php
                    }
                ?>
                            </select>
                        </div>
                <?php
                    }
                ?>
					
					<!-- BOTON DE ENVIAR -->
					<?php submit_button("Guardar"); ?>
				</div>
			</form>
		</div>


		<br />
		<br />


	<?php

	wp_die();
}
