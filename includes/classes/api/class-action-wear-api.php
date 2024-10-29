<?php

namespace AC_SYNC\Includes\Classes\Api {
  if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

  use AC_SYNC\Includes\Classes\Utils\Action_Wear_Circuit_Breaker as CircuitBreaker;
  use AC_SYNC\Includes\Classes\Logger\Action_Wear_Log as Log;

  class Action_Wear_Api
  {

    // NOTE: This mode doesnt require any auth
    const DEBUG_MODE = false;
    const PROTECT_API = true;
    const EXTERNAL_CONFIGURATION = "https://www.crmcag.it/modules/data/wordpress_plugin_data.php";
    const ACTIONWEAR_HUB_API = "https://actionwearwebservices.it/api";
    const SECJWTKEY = "e98f1eec555e2acfd10bb4952eebf85a1365f7b5b67c5d72e10308a80bc3f180";
    const DONT_WAIT_FOR_RESPONSE = true;
    const WAIT_FOR_RESPONSE = false;

    public static function getBaseUrl()
    {
      $lang = get_option('_ACTIONWEAR_PRODUCTS_LANG');
      $base_url = "https://action-wear.com/rest/" . $lang . "/V1";
      return $base_url;
    }

    public static function init()
    {
      add_action('rest_api_init', [new self, "init_endpoints"]);
    }

    public static function init_endpoints()
    {
      $ds = DIRECTORY_SEPARATOR;
      require_once dirname(__FILE__) . $ds . ".." . $ds . ".." . $ds . "routes" . $ds . "all_routes.php";
    }

    public static function isValidApikey($apikey)
    {
      $body = [];
      $body["headers"] = [
        "Authorization" => "Bearer " . $apikey
      ];
      $response = wp_remote_get(self::getBaseUrl() . "/products/types", $body);
      $code = (int) wp_remote_retrieve_response_code($response);
      return $code === 200;
    }

    public static function hasJWT()
    {
      $jwt = get_option("_ACTIONWEAR_HUB_JWT");
      return $jwt !== false;
    }

    private static function encrypt($plaintext, $key)
    {
      $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
      $encrypted = openssl_encrypt($plaintext, 'aes-256-cbc', $key, 0, $iv);
      return base64_encode($iv . $encrypted);
    }

    private static function decrypt($ciphertext, $key)
    {
      $ciphertext = base64_decode($ciphertext);
      $iv_length = openssl_cipher_iv_length('aes-256-cbc');
      $iv = substr($ciphertext, 0, $iv_length);
      $ciphertext = substr($ciphertext, $iv_length);
      return openssl_decrypt($ciphertext, 'aes-256-cbc', $key, 0, $iv);
    }

    public static function createJWT()
    {
      $admin_email = get_option("admin_email");
      // get site domain without protocol
      $site_url = str_replace(["http://", "https://"], "", get_site_url());
      $apikey = get_option("_ACTIONWEAR_APIKEY");

      if (empty($apikey) || empty($admin_email) || empty($site_url)) {
        $jsonValues = json_encode([
          "apikey" => $apikey,
          "admin_email" => $admin_email,
          "site_url" => $site_url
        ]);
        Log::write("Failed to create JWT, some required values are empty: " . $jsonValues, Log::ERROR, Log::CONTEXT_CAMAC_API);
        return false;
      }

      $body = [
        "email" => $admin_email,
        "domain" => $site_url,
        "token" => $apikey
      ];

      $response = wp_remote_post(self::ACTIONWEAR_HUB_API . "/instance/authByToken", [
        "body" => json_encode($body),
        "headers" => [
          "Content-Type" => "application/json"
        ]
      ]);

      $code = (int) wp_remote_retrieve_response_code($response);

      if ($code !== 200) {
        Log::write("Failed to create JWT", Log::ERROR, Log::CONTEXT_CAMAC_API);
        return false;
      }

      $body = wp_remote_retrieve_body($response);
      $jwt = json_decode($body)->jwt;
      $jwt = self::encrypt($jwt, self::SECJWTKEY);

      update_option("_ACTIONWEAR_HUB_JWT", $jwt);
      return true;
    }

    public static function getJWT()
    {
      $jwt = get_option("_ACTIONWEAR_HUB_JWT");
      if ($jwt === false) {
        self::createJWT();
        $jwt = get_option("_ACTIONWEAR_HUB_JWT");
      }
      return self::decrypt($jwt, self::SECJWTKEY);
    }

    public static function pingInstance()
    {
      $jwt = self::getJWT();

      $site_url = str_replace(["http://", "https://"], "", get_site_url());

      $body = [
        "domain" => $site_url
      ];

      $response = wp_remote_post(self::ACTIONWEAR_HUB_API . "/instance/ping", [
        "body" => json_encode($body),
        "timeout" => 2,
        "headers" => [
          "Content-Type" => "application/json",
          "Authorization" => "Bearer " . $jwt
        ]
      ]);

      $code = (int) wp_remote_retrieve_response_code($response);

      if ($code !== 200) {
        Log::write("Failed to ping instance, code: " . $code, Log::ERROR, Log::CONTEXT_CAMAC_API);
        return false;
      }

      return true;
    }


    /**
     * Wrapper per chiamate esterne alle API di Actionwear
     *
     * Può includere parametri come timeout e body
     *
     * @param string $method GET|POST
     * @param string $endpoint Endpoint dell'API, deve cominciare con "/"
     * @param array $body Corpo della chiamata, include l'header authorizations di default
     * 
     * Parametri opzionali
     * 
     * timeout => 30
     * 
     **/
    public static function external($method, $endpoint, $body = [])
    {
      $circuitBreaker = new CircuitBreaker(
        get_option(CircuitBreaker::CACHE_KEY, 0),
        get_option(CircuitBreaker::RETRY_KEY, 0)
      );
      if (!$circuitBreaker->allowRequest()) {
        Log::write("Circuit breaker is open, not allowing request", Log::ERROR, Log::CONTEXT_CAMAC_API);
        return [
          "raw" => "",
          "code" => 500
        ];
      }
      $url = self::getBaseUrl() . $endpoint;
      $apikey = get_option("_ACTIONWEAR_APIKEY");
      if (!$apikey)
        throw new \Exception("Invalid external actionwear apikey");
      $body["headers"] = [
        "Authorization" => "Bearer " . $apikey
      ];
      if (!isset($body["timeout"]))
        $body["timeout"] = 30;
      if ($method == "GET")
        $response = wp_remote_get($url, $body);
      else
        $response = wp_remote_post($url, $body);
      $code = (int) wp_remote_retrieve_response_code($response);
      if ($code !== 200) {
        $circuitBreaker->handleFailure();
      } else {
        $circuitBreaker->handleSuccess();
      }
      return [
        "raw" => $response,
        "code" => $code
      ];
    }

    public static function buildMagentoQuery($filters, $sort, $pagination)
    {

      $query = "?";
      $i = 0;
      foreach ($filters as $filter) {

        [
          "field" => $field,
          "value" => $value,
          "conditionType" => $conditionType
        ] = $filter;

        $query .=
          urlencode("searchCriteria[filterGroups][0][filters][$i][field]")
          . "="
          . $field
          . "&"
          . urlencode("searchCriteria[filterGroups][0][filters][$i][value]")
          . "="
          . $value
          . "&"
          . urlencode("searchCriteria[filterGroups][0][filters][$i][conditionType]")
          . "="
          . $conditionType;
        $i++;
      }

      [
        "sortOrders" => $sortOrders,
        "sortDirection" => $sortDirection
      ] = $sort;

      [
        "pageSize" => $pageSize,
        "currentPage" => $currentPage
      ] = $pagination;

      $query .=
        "&"
        . urlencode("searchCriteria[sortOrders][0][field]")
        . "="
        . $sortOrders
        . "&"
        . urlencode("searchCriteria[sortOrders][0][direction]")
        . "="
        . $sortDirection
        . "&"
        . urlencode("searchCriteria[pageSize]")
        . "="
        . $pageSize
        . "&" .
        urlencode("searchCriteria[currentPage]")
        . "="
        . $currentPage;

      return $query;
    }


    public static function externalMagento($endpoint, $params, $customParams = "")
    {
      $circuitBreaker = new CircuitBreaker(
        get_option(CircuitBreaker::CACHE_KEY, 0),
        get_option(CircuitBreaker::RETRY_KEY, 0)
      );
      if (!$circuitBreaker->allowRequest()) {
        Log::write("Circuit breaker is open, not allowing request", Log::ERROR, Log::CONTEXT_CAMAC_API);
        return [
          "raw" => "",
          "code" => 500
        ];
      }
      $url = self::getBaseUrl() . $endpoint;
      $apikey = get_option("_ACTIONWEAR_APIKEY");
      if (!$apikey)
        throw new \Exception("Invalid external actionwear apikey");
      $body["headers"] = [
        "Authorization" => "Bearer " . $apikey
      ];
      $body["timeout"] = 30;

      if (empty($customParams))
        $url .= self::buildMagentoQuery($params["filters"], $params["sort"], $params["pagination"]);

      $url .= $customParams;

      $response = wp_remote_get($url, $body);
      $code = (int) wp_remote_retrieve_response_code($response);

      if ($code !== 200) {
        $circuitBreaker->handleFailure();
      } else {
        $circuitBreaker->handleSuccess();
      }

      return [
        "raw" => $response,
        "code" => $code
      ];
    }

  }
}
