<?php 

try{
    // Bienvenue
    if (init('action') == 'set_version_WebOStvLG') {
      config::save('version_WebOStvLG', init('version_WebOStvLG'),'WebOStvLG');
      ajax::success();
    }

    ajax::init();

} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}