<?php
/*
 *
 * EN ESTE ARCHIVO TENDREMOS LA INSTALACION Y LA CREACION DE LAS TABLAS EN LA DB
 *
 */
//echo "<h1>JOSER</h1>";
//ESTO SE AGREGA SOLO CUANDO EL CLIENTE AGREGA POR PRIMERA VES EL PLUGIN, ASI PODEMOS CREAR LAS TABLAS ADECUADAMENTE
register_activation_hook(__FILE__, 'instalar_tabla_cron');
register_activation_hook(__FILE__, 'insertar_tabla_cron');

//FUNCTION PARA CREAR TABLA EN DB

global $varsion_tabla_cron;
$varsion_tabla_cron = '1.0.0';

global $wpdb;

//EN ESTE CASO VAMOS A NECESITAR LAS CATEGORIAS, LOS TERMINOS SEGUN CATEGORIA, LA RELACION ENTRE CATEGORIA Y TERMINOS

$tabla_cron                 = $wpdb->prefix . 'tabla_cron';
$consultas_respuestas       = $wpdb->prefix . 'consultas_respuestas';
//consultas_respuestas_todo
$consultas_respuestas_todo = $wpdb->prefix . 'consultas_respuestas_todo';

//productos_variables
$productos_variables        = $wpdb->prefix . 'productos_variables';

//TABLA PARA VER LOS METODOS DE PAGO
$metodos_de_pago_invu       = $wpdb->prefix . 'metodos_de_pago_invu';

function instalar_tabla_cron()
{
    ///echo "<h1>Pasa por el install</h1>";
    global $wpdb;
    global $varsion_tabla_cron;
    global $consultas_respuestas;
    global $consultas_respuestas_todo;
    global $productos_variables;
    global $metodos_de_pago_invu;

    //AQUI ESTAN LAS TABLAS DE LA DB
    global $tabla_cron;

    $charset_collate = $wpdb->get_charset_collate();

    //CREAMOS LA TABLA DE LA CATEGORIA
    $categoria = "CREATE TABLE $tabla_cron (
			id 		mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			nombre 	varchar(100),
			numero 	varchar(100)
		) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($categoria);

    //

    #AGREGAMOS EL REGISTRO DE INSERCIONES EN MI TABLA
    $consultas_respuestas = "CREATE TABLE $consultas_respuestas (
			id 		mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			fecha 		varchar(100),
            paginas     varchar(100),
            pagina      varchar(100),
			consulta 	longtext,
			respuesta 	longtext
		) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($consultas_respuestas);

    #LA SECUENCIA DE TODO
    $consultas_respuestas_todo = "CREATE TABLE $consultas_respuestas_todo (
        id 		mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        fecha 		varchar(100),
        paginas     varchar(100),
        pagina      varchar(100),
        consulta 	longtext,
        respuesta 	longtext
    ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($consultas_respuestas_todo);


    //CREAMOS LA BASE DE DASTOS DE VARIACIONES
    $productos_variables = "CREATE TABLE $productos_variables (
        id 		mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        fecha 		    varchar(100),
        padre_wp        varchar(100),
        hijo_wp         varchar(100),
        padre_invu      varchar(100),
        hijo_invu       varchar(100)
    ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($productos_variables);

    //METODOS DE PAGO
    // "id": "7",
    // "nombre": "Efectivo",
    // "id_tipo_pago": "1",
    // "desc_tipopago": "EFECTIVO",
    // "id_fiscal": null,
    // "emitir_fiscal": true,
    // "pago_internacional": false,
    // "id_tipo_pos": null,
    // "tipo_pos_desc": ""
    $metodos_de_pago_invu = "CREATE TABLE $metodos_de_pago_invu (
        id 		mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        id_invu 		        varchar(100),
        id_woo 		            varchar(100),
        nombre                  varchar(100),
        id_tipo_pago            varchar(100),
        desc_tipopago           varchar(100),
        id_fiscal               BOOLEAN,
        emitir_fiscal           BOOLEAN,
        pago_internacional      BOOLEAN,
        id_tipo_pos             BOOLEAN,
        tipo_pos_desc           varchar(100)
    ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($metodos_de_pago_invu);

    add_option('varsion_tabla_cron', $varsion_tabla_cron);

    update_option("varsion_tabla_cron", $varsion_tabla_cron);
}

function insertar_tabla_cron()
{
    global $wpdb;
    global $tabla_cron;

    //CREAMOS LOS DATOS PARA CREAR Y BORRAR tabla_cron
    $activo = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_cron} WHERE nombre = 'actualizar_imagenes' ");

    if ($activo <= 0) {
        $wpdb->insert(
            $tabla_cron,
            array(
                'nombre' => 'actualizar_imagenes',
                'numero' => '2',
            )
        );
    }

    //CREAMOS LOS DATOS PARA CREAR Y BORRAR tabla_cron
    $activo = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_cron} WHERE nombre = 'actualizar_productos' ");

    if ($activo <= 0) {
        $wpdb->insert(
            $tabla_cron,
            array(
                'nombre' => 'actualizar_productos',
                'numero' => '2',
            )
        );
    }

    //CREAMOS LOS DATOS PARA CREAR Y BORRAR tabla_cron
    $activo = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_cron} WHERE nombre = 'crear_productos_nuevos' ");

    if ($activo <= 0) {
        $wpdb->insert(
            $tabla_cron,
            array(
                'nombre' => 'crear_productos_nuevos',
                'numero' => '2',
            )
        );
    }

    //CREAMOS LOS DATOS PARA CREAR Y BORRAR tabla_cron
    $activo = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_cron} WHERE nombre = 'borrar_productos' ");

    if ($activo <= 0) {
        $wpdb->insert(
            $tabla_cron,
            array(
                'nombre' => 'borrar_productos',
                'numero' => '2',
            )
        );
    }

    //AGREGAMOS LA SECCION DE ACTUALIZAR CATEGORIAS DEL PRODUCTO
    //actualizar_categorias
    $activo = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_cron} WHERE nombre = 'actualizar_categorias' ");

    if ($activo <= 0) {
        $wpdb->insert(
            $tabla_cron,
            array(
                'nombre' => 'actualizar_categorias',
                'numero' => '2',
            )
        );
    }

    //AGREGAMOS LA SECCION DE MANTENER LAS CATEGORIAS DE WOOCOMMERCE
    $activo = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_cron} WHERE nombre = 'mantener_categorias' ");

    if ($activo <= 0) {
        $wpdb->insert(
            $tabla_cron,
            array(
                'nombre' => 'mantener_categorias',
                'numero' => '2',
            )
        );
    }


}

function cron2_update_db_check()
{
    global $varsion_tabla_cron;
    if (get_site_option('varsion_tabla_cron') != $varsion_tabla_cron) {
        instalar_tabla_cron();
        insertar_tabla_cron();
    }
}
add_action('plugins_loaded', 'cron2_update_db_check');

//instalar_tabla_cron();
//insertar_tabla_cron();
