<?php 
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');

if (!isConnect('admin')) {
    throw new Exception(__('401 - Accès non autorisé', __FILE__));
}
try{

     ajax::init(); 
     
    // Bienvenue
    if (init('action') == 'set_version_WebOStvLG') {
      config::save('version_WebOStvLG', init('version_WebOStvLG'),'WebOStvLG');
      ajax::success();
    }

   

} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
?>
