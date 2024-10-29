<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api_Route;
use AC_SYNC\Includes\Classes\Api\Action_Wear_API_Response as ApiResponse;
use AC_SYNC\Includes\Classes\Attribute\Action_Wear_Attribute as Attribute;

$route = new Action_Wear_Api_Route("/get_camac_attributes");
$route->setMethods("GET");
$route->setCallback(function () {

    $camac_attributes = Attribute::CAMAC_ATTRIBUTES;

    $response = new ApiResponse();
    $response->success([
        "camac_attributes" => $camac_attributes
    ]);
    return $response->getResponse();
});
$route->registerRoute();
