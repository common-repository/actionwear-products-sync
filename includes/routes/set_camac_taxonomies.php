<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Attribute\Action_Wear_Attribute as Attribute;


$route = new Action_Wear_Api_Route("/set_camac_taxonomies");
$route->setMethods("POST");
$route->setCallback(function () {

    $response = new ApiResponse();
    $attribute = new Attribute();

    $selected_attributes = json_decode(file_get_contents("php://input"), true);
    $selected_attributes = $selected_attributes['body'];

    $selected_attributes = array_filter($selected_attributes, function ($attribute) {
        return $attribute['selected'];
    });

    foreach ($selected_attributes as $key => $_attribute) {
        wc_create_attribute([
            "name" => $_attribute["name"],
            "slug" => $key,
            "has_archives" => true
        ]);
    }

    $attr_keys = array_keys($selected_attributes);
    $attr_keys = json_encode($attr_keys);

    $attribute->setSelectedAttributes($attr_keys);

    $response->success([$selected_attributes], 'Attributi importati e creati con successo!');
    return $response->getResponse();
});
$route->registerRoute();
