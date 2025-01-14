<?php
class WebOStvLG_Ping {
    
    public function ping($method = 'addr') {
        
        if($method != ''){
            $exec_string = 'sudo ping -c 2  ' . $method . ' 2> /dev/null';
			exec($exec_string, $output, $return);
            return $return;
        }
        return false;
       
    }
}
?>