<?php
use AC_SYNC\Includes\Classes\Api\Action_Wear_Api;

if (!defined('ABSPATH'))
  exit; // Exit if accessed directly
$nonce = json_encode(wp_create_nonce("wp_rest"));
echo '
<script type="text/javascript">
  window._ACTIONWEAR_BASE_URL = "' . get_site_url() . '";
  window._ACTIONWEAR_API_URL = "' . get_rest_url() . '";
  window._ACTIONWEAR_nonce = ' . $nonce . ';
</script>
';
Action_Wear_Api::pingInstance();
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<div id="actionwear-app">
</div>