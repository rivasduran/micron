<?php
/*
**
	MENU NUEVO PARA ACTUALIZAR EL CRON
**
*/


/** Step 2 (from text above). */
add_action( 'admin_menu', 'menu_cron_invu' );

/** Step 1. */
function menu_cron_invu() {
	add_menu_page( 'CRON', 'CRON', 'manage_options', 'cron_invu', 'cron_invu_2', 'dashicons-controls-repeat', '35' );
	//add_submenu_page( 'generar-liga', 'Categorias', 'Categorias', 'manage_options', 'mis-categorias', 'borrar_productos_web' );

	//SECCION DE CONFIGURACIONES
	//add_submenu_page( 'api_invu', 'Ajustes', 'Ajustes', 'manage_options', 'mis-ajustes-api', 'ajustes_api_invu' );
	add_submenu_page( 'cron_invu', 'Actualizar', 'Actualizar', 'manage_options', 'actualizar_manual_invu', 'actualizar_manual_invu' );

	//SECCION DE METODOS DE PAGO
	add_submenu_page( 'cron_invu', 'Metodos Pago', 'Metodos Pago', 'manage_options', 'metodos_pago_woo', 'metodos_pago_woo' );
}

function cron_invu_2(){
	global $wpdb;
	echo "<h1>Configuraciones de Cron: Importar elementos </h1>";

	//REVISAREMOS LOS CHECKBOX
	if(isset($_POST['datos_cron'])){
		
		//CREAMOS UN INSERT PARA TENER SOLO 
		if(isset($_POST['check_actualizar_imagenes'])){

			//echo "<h1>Llegamos a esto</h1>";

			$wpdb->update(
					"{$wpdb->prefix}tabla_cron",
					array( 
						'numero' 		=> "1" 
					), 
					array( 
						'nombre' 		=> "actualizar_imagenes" 
					)
				);
		}else{
			$wpdb->update(
					"{$wpdb->prefix}tabla_cron",
					array( 
						'numero' 		=> "2" 
					), 
					array( 
						'nombre' 		=> "actualizar_imagenes" 
					)
				);
		}

		if(isset($_POST['check_actualizar_productos'])){
			$wpdb->update( 
					"{$wpdb->prefix}tabla_cron",
					array( 
						'numero' 		=> "1" 
					), 
					array( 
						'nombre' 		=> "actualizar_productos" 
					)
				);
		}else{
			$wpdb->update(
					"{$wpdb->prefix}tabla_cron",
					array( 
						'numero' 		=> "2" 
					), 
					array( 
						'nombre' 		=> "actualizar_productos" 
					)
				);
		}

		if(isset($_POST['check_crear_productos_nuevos'])){
			$wpdb->update( 
					"{$wpdb->prefix}tabla_cron",
					array( 
						'numero' 		=> "1" 
					), 
					array( 
						'nombre' 		=> "crear_productos_nuevos" 
					)
				);
		}else{
			$wpdb->update(
					"{$wpdb->prefix}tabla_cron",
					array( 
						'numero' 		=> "2" 
					), 
					array( 
						'nombre' 		=> "crear_productos_nuevos" 
					)
				);
		}

		if(isset($_POST['check_borrar_productos'])){
			$wpdb->update( 
					"{$wpdb->prefix}tabla_cron",
					array( 
						'numero' 		=> "1" 
					), 
					array( 
						'nombre' 		=> "borrar_productos" 
					)
				);
		}else{
			$wpdb->update(
					"{$wpdb->prefix}tabla_cron",
					array( 
						'numero' 		=> "2" 
					), 
					array( 
						'nombre' 		=> "borrar_productos" 
					)
				);
		}

		//SECCION DE ACTUALIZAR CATEGORIA DE PRODUCTO
		//check_actualizar_categorias
		if(isset($_POST['check_actualizar_categorias'])){
			$wpdb->update( 
					"{$wpdb->prefix}tabla_cron",
					array( 
						'numero' 		=> "1" 
					), 
					array( 
						'nombre' 		=> "actualizar_categorias" 
					)
				);
		}else{
			$wpdb->update(
					"{$wpdb->prefix}tabla_cron",
					array( 
						'numero' 		=> "2" 
					), 
					array( 
						'nombre' 		=> "actualizar_categorias" 
					)
				);
		}

		//MANTENER LAS CATEGORIAS AGREGADAS MANUALMENTE EN INVU
		#check_mantener_categorias
		if(isset($_POST['check_mantener_categorias'])){
			$wpdb->update( 
					"{$wpdb->prefix}tabla_cron",
					array( 
						'numero' 		=> "1" 
					), 
					array( 
						'nombre' 		=> "mantener_categorias" 
					)
				);
		}else{
			$wpdb->update(
					"{$wpdb->prefix}tabla_cron",
					array( 
						'numero' 		=> "2" 
					), 
					array( 
						'nombre' 		=> "mantener_categorias" 
					)
				);
		}

	}
	

	//CONSULTAMOS EL API PARA MOSTRARLO AL USUARIO
	$mi_api2 = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tabla_cron WHERE numero = 1 ");

	//echo "<h1>SELECT * FROM {$wpdb->prefix}mi_api2 WHERE numero = 1 </h1>";

	$check_actualizar_imagenes 		= "";
	$check_actualizar_productos 	= "";
	$check_borrar_productos 		= "";
	$check_crear_productos_nuevos 	= "";
	$check_actualizar_categorias 	= "";
	$check_mantener_categorias 		= "";

	foreach ($mi_api2 as $key) {
		if($key->nombre == "actualizar_imagenes"){
			$check_actualizar_imagenes = 'checked="checked" ';
		}

		if($key->nombre == "actualizar_productos"){
			$check_actualizar_productos = 'checked="checked" ';
		}

		if($key->nombre == "crear_productos_nuevos"){
			$check_crear_productos_nuevos = 'checked="checked" ';
		}

		if($key->nombre == "borrar_productos"){
			$check_borrar_productos = 'checked="checked" ';
		}

		/**
		 * Agregamos el actualizador de categorias de productos
		 */
		if($key->nombre == "actualizar_categorias"){
			$check_actualizar_categorias = 'checked="checked" ';
		}

		if($key->nombre == "mantener_categorias"){
			$check_mantener_categorias = 'checked="checked" ';
		}
	}



	/**
	 * ACTUALIZAR CATEGORIAS
	 */
	
		if(isset($_POST['actualizar_todas_categorias'])){
			crear_todas_categorias_wp();
		}

	?>
		<div class="formularios-approval quarterWidth formularioCertificados">
			<form method="post" action="">
			    <input type="text" name="datos_cron" value="1" required style="display: none;" />

				<!-- SECCION DE LAS CATEGORIAS -->
				<div class="">
					<div class="form-group">
						<input type="checkbox" class="form-control" name="check_actualizar_imagenes" <?php  echo $check_actualizar_imagenes; ?>>
						<label for="exampleInputEmail1">Actualizar imagenes</label>
						<small class="form-text text-muted">Actualizamos las imagenes.</small>
					</div>

					<div class="form-group">
						<input type="checkbox" class="form-control" name="check_actualizar_productos" <?php  echo $check_actualizar_productos; ?>>
						<label for="exampleInputEmail1">Actualizar productos</label>
						<small class="form-text text-muted">Actualizamos los datos del producto.</small>
					</div>

					<div class="form-group">
						<input type="checkbox" class="form-control" name="check_crear_productos_nuevos" <?php  echo $check_crear_productos_nuevos; ?>>
						<label for="exampleInputEmail1">Crear productos nuevos</label>
						<small class="form-text text-muted">Creamos nuevos productos desde Invu.</small>
					</div>

					<div class="form-group">
						<input type="checkbox" class="form-control" name="check_borrar_productos" <?php  echo $check_borrar_productos; ?>>
						<label for="exampleInputEmail1">Borrar productos</label>
						<small class="form-text text-muted">Borramos los productos que no forman parte de Invu.</small>
					</div>

					<!-- AGREGAMOS PARA AGREGAR LAS CATEGORIAS -->
					<div class="form-group">
						<input type="checkbox" class="form-control" name="check_actualizar_categorias" <?php  echo $check_actualizar_categorias; ?>>
						<label for="exampleInputEmail1">Actualizar categorias</label>
						<small class="form-text text-muted">Actualizar las categorias del producto.</small>
					</div>

					<!-- HABILITAR SI QUEREMOS QUE SE MANTENGAN LAS CATEGORIAS ACTUALES -->
					<div class="form-group">
						<input type="checkbox" class="form-control" name="check_mantener_categorias" <?php  echo $check_mantener_categorias; ?>>
						<label for="exampleInputEmail1">Mantener catagorias</label>
						<small class="form-text text-muted">Mantener las categorias agregadas manualmente en Woocommerce</small>
					</div>
					
					<!-- BOTON DE ENVIAR -->
					<?php submit_button("Guardar"); ?>
				</div>
			</form>
		</div>


		<br />
		<br />
		<div>
			<form method="post" action="">
			    <input type="text" name="actualizar_todas_categorias" value="1" required style="display: none;" />
				<?php submit_button("Actualizar categorias"); ?>
			</form>
		</div>

	<?php

	wp_die();
}

?>