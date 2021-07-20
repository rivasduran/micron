<?php

function total_categorias($categoria_id, $product_id = "")
{
    //global $post;
    global $wpdb;

//MANTENER CATEGORIAS
        if($key->nombre == "mantener_categorias"){
            $mantener_categorias = $key->numero;
        }

    

    if($mantener_categorias != 2){

        $categorias_final = [];
        //AQUI VAMOS A MANTENER LAS CATEGORIAS QUE YA TIENE EL PRODUCTO
        if($product_id != ""){

            //$product = wc_get_product( $product_id );
            
            $terms = get_the_terms( $product_id, 'product_cat' );
            foreach ($terms as $term) {
                $product_cat_id = $term->term_id;

                array_push($categorias_final, $product_cat_id);
                //break;
            }

        }
    }

}