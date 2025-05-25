<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../../3rdparty/WebOStvLG_Ping.class.php';

class WebOStvLG extends eqLogic {
    const PYTHON_PATH = __DIR__ . '/../../resources/venv/bin/python3';
    const EXEC_LG = self::PYTHON_PATH .' /var/www/html/plugins/WebOStvLG/resources/venv/bin/lgtv';
    const LG_PATH = __DIR__ . '/../..';
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */
    public static function cron() {
        WebOStvLG::etattv();
    }
    public static function dependancy_info() {

        $return = array();
        $return['log'] = log::getPathToLog(__CLASS__ . '_update');
        $return['progress_file'] = jeedom::getTmpFolder(__CLASS__) . '/dependance';
        $return['state'] = 'ok';
      
        if (file_exists(jeedom::getTmpFolder(__CLASS__) . '/dependance')) {
            $return['state'] = 'in_progress';
        } elseif (!file_exists(self::PYTHON_PATH)) {
            $return['state'] = 'nok';
        } elseif (!self::pythonRequirementsInstalled(self::PYTHON_PATH, __DIR__ . '/../../resources/requirements.txt')) {
            $return['state'] = 'nok';
        }
        
        return $return;
    }

    public static function dependancy_install()
    {
        log::remove(__CLASS__ . '_update');
        return array('script' => dirname(__FILE__) . '/../../resources/install_apt.sh ' . jeedom::getTmpFolder('WebOStvLG') . '/dependance',
                     'log' => log::getPathToLog(__CLASS__ . '_update'));
    }

    private static function pythonRequirementsInstalled(string $pythonPath, string $requirementsPath) {
		if (!file_exists($pythonPath) || !file_exists($requirementsPath)) {
			return false;
		}
		exec("{$pythonPath} -m pip freeze", $packages_installed);
		$packages = join("||", $packages_installed);
		exec("cat {$requirementsPath}", $packages_needed);
		foreach ($packages_needed as $line) {
			if (preg_match('/([^\s]+)[\s]*([>=~]=)[\s]*([\d+\.?]+)$/', $line, $need) === 1) {
				if (preg_match('/' . $need[1] . '==([\d+\.?]+)/i', $packages, $install) === 1) {
					if ($need[2] == '==' && $need[3] != $install[1]) {
						return false;
					} elseif (version_compare($need[3], $install[1], '>')) {
						return false;
					}
				} else {
					return false;
				}
			}
		}
       
		return true;
	}

    /*     * *********************Methode d'instance************************* */

    public function preUpdate() {
        if($this->getConfiguration('key') == ''){ 
        $execpython = self::PYTHON_PATH .' /var/www/html/plugins/WebOStvLG/resources/venv/bin/lgtv';
        $lgtvscan = exec(system::getCmdSudo().' '.$execpython .' scan');
        $datascan = json_decode($lgtvscan,true);
        
        log::add('WebOStvLG','debug','scan3 : ' .print_r($lgtvscan,true));

        $tvetat = $this->getCmd(null, 'etat');
        if (isset($tvetat)) {
        $value = $tvetat->execCmd();
        }

           
            if($datascan['result'] == 'ok'){
                $tv_info = $datascan['list'][0];
                log::add('WebOStvLG','info','lgtvinfo: ' . json_encode($lgtvjsoninInfo["payload"],true));
                $lgtvauth = shell_exec(system::getCmdSudo().' '.$execpython .' '.$versionLG.' --ssl auth '. $tv_info["address"] .' '.json_encode($tv_info['tv_name'],true)); 
                log::add('WebOStvLG','debug','auth : ' . $execpython .' auth '. $this->getConfiguration('addr') .' '.json_encode($tv_info['tv_name'],true));
	        }
	        else
	        {
                if($this->getConfiguration('addr') == ''){
                    throw new Exception(__('Merci de renseigner IP de la TV',__FILE__));
                }
                $tv_info['tv_name'] = "TV_LG";
                $lgtvauth = shell_exec(system::getCmdSudo().' '.$execpython .' --ssl auth '. $this->getConfiguration('addr') .' '.json_encode($tv_info['tv_name'],true));
                //throw new Exception(__('Je ne trouve pas de TV LG',__FILE__));
            }
        
        if($this->getConfiguration('addr') == ''){
          $tv_info = $datascan['list'][0];
          $this->setConfiguration('addr', $tv_info["address"]);
          $this->save(true);
        }

        if(file_exists(self::LG_PATH.'/3rdparty/config.json')){
            $remove = shell_exec(system::getCmdSudo().' rm -R '.self::LG_PATH.'/3rdparty/config.json');
            sleep(3);
        }

        $lgtvcopy = shell_exec(system::getCmdSudo().' cp -R /root/.lgtv/config.json '.self::LG_PATH.'/3rdparty');
        sleep(2);
	    $json_data = file_put_contents(self::LG_PATH.'/3rdparty/scan.json', json_encode($datascan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        //log::add('WebOStvLG','debug','scan 2 : ' .  print_r($tv_info,true));
        
    
        $lgtvjson = file_get_contents(self::LG_PATH.'/3rdparty/config.json');
        $lgtvjsonin = json_decode($lgtvjson, true);
    
        //log::add('WebOStvLG','debug','scan1 : ' . json_encode($lgtvjsonin[$tv_info['tv_name']],true));
        $tv_info = $datascan['list'][0];
        log::add('WebOStvLG','debug','tv info : ' .  print_r($lgtvjsonin[$tv_info['tv_name']]["key"],true));
        if ($lgtvjsonin[$tv_info['tv_name']]["key"] != "") {
                $this->setConfiguration('key', $lgtvjsonin[$tv_info['tv_name']]["key"]);
                $this->setConfiguration('mac', $lgtvjsonin[$tv_info['tv_name']]["mac"]);

                //print("OK, la clé est " . $lgtvjsonin[$tv_info['tv_name']]["key"]);
            log::add('WebOStvLG','debug','lgtvauth: ' . print_r($lgtvjson,true));
            $tv_info = $datascan['list'][0];
        }
        else
        {
            foreach ($lgtvjsonin as $device_name => $device_info) {
                if($device_name == "TV_LG"){
                $this->setConfiguration('key', $device_info['key']);
                $this->setConfiguration('mac', ($device_info['mac'] ?? 'Non défini'));
               // log::add('WebOStvLG','debug','tv info : ' .  print_r($device_info,true));
                /*$device_info['key'];
                ($device_info['mac']; ?? 'Non défini') . "<br>";  // Si "mac" est null, afficher "Non défini"
                $device_info['ip'];
                $device_info['hostname'];*/
                $tv_info['tv_name'] = $device_name;

                }
            }
        }
        $lgtvinfo = shell_exec(system::getCmdSudo().' '.$execpython .' --name "'.$tv_info['tv_name'].'" --ssl swInfo');
                $jsonInfo = str_replace('{"closing": {"code": 1000, "reason": ""}}', '', $lgtvinfo);
                $datainfo = json_decode($jsonInfo,true);
                $json_data = file_put_contents(self::LG_PATH.'/3rdparty/info.json', json_encode($datainfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                $lgtvjsonInfo = file_get_contents(self::LG_PATH.'/3rdparty/info.json');
                $lgtvjsoninInfo = json_decode($lgtvjsonInfo, true);
                $this->setConfiguration('versionos', $lgtvjsoninInfo["payload"]["product_name"]);
                $this->setConfiguration('model', $lgtvjsoninInfo["payload"]["model_name"]);
                $this->setConfiguration('majeur', $lgtvjsoninInfo["payload"]["major_ver"]);
                $this->setConfiguration('mineur', $lgtvjsoninInfo["payload"]["minor_ver"]);
                $this->setConfiguration('mac', $lgtvjsoninInfo["payload"]["device_id"]);
                $this->save(true);
		
        log::add('WebOStvLG','debug','tv info : ' .  print_r($tv_info['tv_name'].' '.$device_info['hostname'],true));
         
       }
    }
	
	public function getGroups() {
       return array('base', 'inputs', 'apps', 'channels','medias','remote');
    }
	
    public function loadCmdFromConf($type,$data = false) {
		log::add('WebOStvLG', 'debug','loadCmdFromConf ' . $type);
		if (!is_file(__DIR__ . '/../config/commands/' . $type . '.json')) {
			log::add('WebOStvLG','debug', 'no file' . $type);
			return;
		}
		log::add('WebOStvLG', 'debug','loadCmdFromConf 2');
		$content = file_get_contents(__DIR__ . '/../config/commands/' . $type . '.json');
		
		if (!is_json($content)) {
			log::add('WebOStvLG','debug', 'no json content');
			return;
		}
		log::add('WebOStvLG', 'debug','loadCmdFromConf 4 ');
		$device = json_decode($content, true);
		log::add('WebOStvLG', 'debug','loadCmdFromConf 5 ');
		//log::add('WebOStvLG', 'debug',print_r($device,true));
		if (!is_array($device) || !isset($device['commands'])) {
			log::add('WebOStvLG','debug', 'no array');
			return true;
		}
       
        $lgtvscan = file_get_contents(self::LG_PATH.'/3rdparty/scan.json');
        $lgtvscanin = json_decode($lgtvscan, true);
        
        
        if($lgtvscanin == ''){
            $lgtvjson = file_get_contents(self::LG_PATH.'/3rdparty/config.json');
            $lgtvjsonin = json_decode($lgtvjson, true);
            
            foreach ($lgtvjsonin as $device_name => $device_info) {
                if($device_name == "TV_LG"){
                    $lgtvscanin["list"][0]["tv_name"] = $device_name;
                    log::add('WebOStvLG','debug', 'loadCmdFromConf: '.$lgtvscanin["list"][0]["tv_name"]);
                }
            }
            
        }

        foreach($device['commands'] as $key => &$modif){

            if (isset($modif['configuration']['request'])) {
                $lgtvjsonInfo = file_get_contents(self::LG_PATH.'/3rdparty/info.json');
                $lgtvjsoninInfo = json_decode($lgtvjsonInfo, true);
                
                if($lgtvjsoninInfo["payload"]["major_ver"] >= "04"){
                  $versionLG = '--ssl';
                  $modif['configuration']['request'] = $modif['configuration']['modif'].'"'.$lgtvscanin["list"][0]["tv_name"].'" '.$versionLG.''.$modif['configuration']['modif1'];
                }
              else
              {
                 $modif['configuration']['request'] = $modif['configuration']['modif'].'"'.$lgtvscanin["list"][0]["tv_name"].'"'.$modif['configuration']['modif1'];
              }

               // log::add('WebOStvLG','debug', 'loadCmdFromConf1: '. $versionLG);
                // Modifier la valeur de 'request' pour chaque commande
                 // Remplacez par la valeur souhaitée
                //log::add('WebOStvLG', 'debug','modification jsom type :'. $modif['configuration']['request']);
            }
        }
        // Sauvegarder les modifications dans le fichier JSON
        file_put_contents(__DIR__ . '/../config/commands/' . $type . '.json', json_encode($device, JSON_PRETTY_PRINT));
        log::add('WebOStvLG', 'debug','modification jsom type 1:'. $modif['configuration']['request']);

		foreach ($device['commands'] as $command) { 

			$webosTvCmd = $this->getCmd(null, $command['name']);
			if ( !is_object($webosTvCmd) ) {
				log::add('WebOStvLG', 'debug','no exist');
				$webosTvCmd = new WebOStvLGCmd();
				$webosTvCmd->setName(__($command['name'], __FILE__));
				$webosTvCmd->setEqLogic_id($this->getId());
				$webosTvCmd->setLogicalId($command['name']);	
				$webosTvCmd->setType($command['type']);
				$webosTvCmd->setSubType($command['subtype']);				
			}
			
            $webosTvCmd->setConfiguration('dashicon', $command['configuration']['dashicon']);
			$webosTvCmd->setConfiguration('request', $command['configuration']['request']);
			$webosTvCmd->setConfiguration('parameters', $command['configuration']['parameters']);
			$webosTvCmd->setConfiguration('group', $command['configuration']['group']);
			$webosTvCmd->save();
		}
        log::add('WebOStvLG', 'debug','exist:'. $command['configuration']['group']);
	}	

    public function addApps() {
        $lgtvscan = file_get_contents(self::LG_PATH.'/3rdparty/scan.json');
        $lgtvscanin = json_decode($lgtvscan, true);
      
        if($lgtvscanin == ''){
            $lgtvjson = file_get_contents(self::LG_PATH.'/3rdparty/config.json');
            $lgtvjsonin = json_decode($lgtvjson, true);
            
            foreach ($lgtvjsonin as $device_name => $device_info) {
                if($device_name == "TV_LG"){
                    $lgtvscanin["list"][0]["tv_name"] = $device_name;
                    //log::add('WebOStvLG','debug', 'addApps: '.$lgtvscanin["list"][0]["tv_name"]);
                }
            }
            
        }
        $lgtvjsonInfo = file_get_contents(self::LG_PATH.'/3rdparty/info.json');
        $lgtvjsoninInfo = json_decode($lgtvjsonInfo, true);
                
                if($lgtvjsoninInfo["payload"]["major_ver"] >= "04"){
                    $versionLG = "--ssl";
                     $lgcommand = '--name "'.$lgtvscanin["list"][0]["tv_name"].'" '.$versionLG.' listApps';
                }
                else
                {
                  $lgcommand = '--name "'.$lgtvscanin["list"][0]["tv_name"].'" listApps';
                }
      
        
        $json_in = shell_exec(system::getCmdSudo() . self::EXEC_LG .' '. $lgcommand );    
        $json = str_replace('{"closing": {"code": 1000, "reason": ""}}', '', $json_in);
        //log::add('WebOStvLG', 'debug', json_decode($json,true));
        /*if($json == ''){
        
            throw new Exception(__('La TV LG est éteinte',__FILE__));
        }*/
         log::add('WebOStvLG','debug', 'addApps: '.  $lgcommand);
        if (is_json($json)) {
            $ret = json_decode($json, true);
        
            foreach ($ret["payload"]["apps"] as $inputs) {
                
                $name = str_replace("LG ", "LG", $inputs["title"]);
                $name1 = str_replace(" ", "_", $name);
                $name2 = str_replace("'", "_", $name1);
                $name3 = str_replace("&", " ", $name2);
                $name4 = str_replace("\xc2\xa0", "_", $name3);
                if($name != ""){
                
                log::add('WebOStvLG', 'debug', '|  json: listApps '.print_r($inputs["title"],true) );
                if(strlen($inputs["title"]) >= 30) {
                    $name4 =  substr($inputs["title"],0,30);
                    $nameicone =  substr($inputs["title"],0,30);
                }	
                log::add('WebOStvLG', 'debug', '| NEW APP FOUND:' . $name);
                
                if($name == "Live TV" || $name != "Mode Expo." && $name != "InputCommon" && $name != "DvrPopup" && substr($name,0,4) != "Live" && $name != "Local Control Panel" && $name != "User Agreement" && $name != "QML Factorywin" && $name != "Publicité" && $name != "Thirdparty Login" && $name != "Viewer" && $name != "Service clientèle"  && $name != "Connected Red Button"){
                    //log::add('WebOStvLG', 'debug', '| NEW APP :' . substr($name,0,4));
                $webosTvCmd = $this->getCmd(null, $name);
                if ( !is_object($webosTvCmd) ) {
                    $webosTvCmd = new WebOStvLGCmd();
                    $webosTvCmd->setName(__($name, __FILE__));
                    $webosTvCmd->setEqLogic_id($this->getId());
                    $webosTvCmd->setLogicalId($name);	
                    $webosTvCmd->setType('action');
                    $webosTvCmd->setSubType('other');				
                } else {
                    if($webosTvCmd->getConfiguration('group') != "" && $webosTvCmd->getConfiguration('group') != 'apps')
                        continue;
                }
                $webosTvCmd->setConfiguration('dashicon', $name4);
                if($lgtvjsoninInfo["payload"]["major_ver"] >= "04"){
                    $versionLG = "--ssl";
                    $webosTvCmd->setConfiguration('request', '--name "'.$lgtvscanin["list"][0]["tv_name"].'" '.$versionLG.' startApp '.$inputs["id"]);
                }
                else
                {
                    $webosTvCmd->setConfiguration('request', '--name "'.$lgtvscanin["list"][0]["tv_name"].'" startApp '.$inputs["id"]);
                }
                $webosTvCmd->setConfiguration('parameters', 'startApp ' . $inputs["id"]);
                $webosTvCmd->setConfiguration('group', 'apps');
                $webosTvCmd->save();	
                }
               }		
               log::add('WebOStvLG', 'debug', '|  json: listApps '.print_r($inputs["title"],true) );
            }	
            
            }
        
    }

    public function addInputs() {
        $lgtvscan = file_get_contents(self::LG_PATH.'/3rdparty/scan.json');
        $lgtvscanin = json_decode($lgtvscan, true);

	if(!file_exists(self::LG_PATH.'/core/template/images/icons_inputs')){
	    $creatInputs = shell_exec('mkdir '.self::LG_PATH.'/core/template/images/icons_inputs');
	}

    if($lgtvscanin == ''){
        $lgtvjson = file_get_contents(self::LG_PATH.'/3rdparty/config.json');
        $lgtvjsonin = json_decode($lgtvjson, true);
        
        foreach ($lgtvjsonin as $device_name => $device_info) {
            if($device_name == "TV_LG"){
                $lgtvscanin["list"][0]["tv_name"] = $device_name;
                //log::add('WebOStvLG','debug', 'addApps: '.$lgtvscanin["list"][0]["tv_name"]);
            }
        }
        
    }
        $lgtvjsonInfo = file_get_contents(self::LG_PATH.'/3rdparty/info.json');
        $lgtvjsoninInfo = json_decode($lgtvjsonInfo, true);
                
        if($lgtvjsoninInfo["payload"]["major_ver"] >= "04"){
            $versionLG = '--ssl';
            $lgcommand = '--name "'.$lgtvscanin["list"][0]["tv_name"].'" '.$versionLG.' listInputs';
        }
        else
        {
            $lgcommand = '--name "'.$lgtvscanin["list"][0]["tv_name"].'" listInputs';
        }
		$json_in = shell_exec(system::getCmdSudo() . self::EXEC_LG .' '. $lgcommand );
		$json = str_replace('{"closing": {"code": 1000, "reason": ""}}', '', $json_in);		
		if (!is_json($json)) {
			log::add('WebOStvLG', 'debug', '| Impossible de continuer la récupération ' );
			return;
		}
		log::add('WebOStvLG', 'debug', '|  json: listInputs' );	
		$ret = json_decode($json, true);
        $json_data = file_put_contents(self::LG_PATH.'/3rdparty/inputs.json', json_encode($ret, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $lgtvjsonInput = file_get_contents(self::LG_PATH.'/3rdparty/inputs.json');
        $lgtvjsoninInputs = json_decode($lgtvjsonInput, true);

		foreach ($lgtvjsoninInputs["payload"]["devices"] as $inputs) {
            
            $imageUrl = $inputs['icon'];
            $imageName = basename($imageUrl);
            $imageData = file_get_contents($imageUrl);
            $resultimage = file_put_contents(self::LG_PATH.'/core/template/images/icons_inputs/'.$imageName, $imageData);
            
			if (array_key_exists('label', $inputs)) {
				//$inputs["label"] = str_replace("'", " ", $inputs["label"]);
				//$inputs["label"] = str_replace("&", " ", $inputs["label"]);
				log::add('webosTv', 'debug', '| NEW INPUT FOUND:' . $inputs["label"]);
				
				$webosTvCmd = $this->getCmd(null, $inputs["label"]);
				if ( !is_object($webosTvCmd) ) {
					log::add('WebOStvLG', 'debug','no exist');
					$webosTvCmd = new WebOStvLGCmd();
					$webosTvCmd->setName(__($inputs["label"], __FILE__));
					$webosTvCmd->setEqLogic_id($this->getId());
					$webosTvCmd->setLogicalId($inputs["label"]);	
					$webosTvCmd->setType('action');
					$webosTvCmd->setSubType('other');				
				}
				log::add('WebOStvLG', 'debug','exist');
				$webosTvCmd->setConfiguration('dashicon', $imageName);
                if($lgtvjsoninInfo["payload"]["major_ver"] >= "04"){
                    $versionLG = '--ssl';
				    $webosTvCmd->setConfiguration('request', '--name "'.$lgtvscanin["list"][0]["tv_name"].'" '.$versionLG.' setInput '.$inputs["id"]);
                }
                else
                {
                    $webosTvCmd->setConfiguration('request', '--name "'.$lgtvscanin["list"][0]["tv_name"].'" setInput '.$inputs["id"]);
                }
				$webosTvCmd->setConfiguration('parameters', 'Passer sur l entree ' . $inputs["label"]);
				$webosTvCmd->setConfiguration('group', 'inputs');
				$webosTvCmd->save();				
			}
		}
	}

    public function addChannels(){
        $lgtvscan = file_get_contents(self::LG_PATH.'/3rdparty/scan.json');
        $lgtvscanin = json_decode($lgtvscan, true);

        if($lgtvscanin == ''){
            $lgtvjson = file_get_contents(self::LG_PATH.'/3rdparty/config.json');
            $lgtvjsonin = json_decode($lgtvjson, true);
            
            foreach ($lgtvjsonin as $device_name => $device_info) {
                if($device_name == "TV_LG"){
                    $lgtvscanin["list"][0]["tv_name"] = $device_name;
                    //log::add('WebOStvLG','debug', 'addApps: '.$lgtvscanin["list"][0]["tv_name"]);
                }
            }
            
        }
        $lgtvjsonInfo = file_get_contents(self::LG_PATH.'/3rdparty/info.json');
                $lgtvjsoninInfo = json_decode($lgtvjsonInfo, true);
                
                if($lgtvjsoninInfo["payload"]["major_ver"] >= "04"){
                    $versionLG = '--ssl';
                    $lgcommand = '--name "'.$lgtvscanin["list"][0]["tv_name"].'" '.$versionLG.' listChannels';
                }
                else
                {
                    $lgcommand = '--name "'.$lgtvscanin["list"][0]["tv_name"].'" listChannels';
                }
        $json_in = shell_exec(system::getCmdSudo() . self::EXEC_LG .' '. $lgcommand );
        $json = str_replace('{"closing": {"code": 1000, "reason": ""}}', '', $json_in);
        /*if($json_in == ''){
        
            throw new Exception(__('La TV LG est éteinte',__FILE__));
        }*/
        if (is_json($json)) {
            log::add('WebOStvLG', 'debug', '|  json: listChannels '.$json_in );
            $ret = json_decode($json, true);

            if ($ret["payload"]["channelList"] != "") {
                foreach ($ret["payload"]["channelList"] as $inputs) {
                    //log::add('WebOStvLG','debug','Channel : ' . print_r($inputs, true));
                    if ($inputs["channelName"] != "") {
                        $WebOStvLGCmd = $this->getCmd(null, $inputs["channelName"]);
                        if ( !is_object($WebOStvLGCmd) ) {
                            $WebOStvLGCmd = new WebOStvLGCmd();
                            $WebOStvLGCmd->setName(__($inputs["channelName"], __FILE__));
                            $WebOStvLGCmd->setEqLogic_id($this->getId());
                            $WebOStvLGCmd->setLogicalId($inputs["channelName"]);	
                            $WebOStvLGCmd->setType('action');
                            $WebOStvLGCmd->setSubType('other');				
                        } else {
                            if($WebOStvLGCmd->getConfiguration('group') != "" && $WebOStvLGCmd->getConfiguration('group') != 'channels')
                                continue;
                        }
                        $chaine = str_replace("'", " ", $inputs["channelName"]);
                        $chaines = str_replace(" ", "_", $chaine);
                        $WebOStvLGCmd->setConfiguration('dashicon',  $chaines);
                        if($lgtvjsoninInfo["payload"]["major_ver"] >= "04"){
                            $versionLG = '--ssl';
                            $WebOStvLGCmd->setConfiguration('request', '--name "'.$lgtvscanin["list"][0]["tv_name"].'" '.$versionLG.' setTVChannel '.$inputs["channelId"]);
                        }
                        else
                        {
                            $WebOStvLGCmd->setConfiguration('request', '--name "'.$lgtvscanin["list"][0]["tv_name"].'" setTVChannel '.$inputs["channelId"]);
                        }
                        $WebOStvLGCmd->setConfiguration('parameters', 'Mettre la chaine ' . $inputs["channelNumber"]);
                        $WebOStvLGCmd->setConfiguration('group', 'channels');
                        $WebOStvLGCmd->save();							
                    }
                }
            }
        }		
    }
	
    public function postAjax() {
        if (!$this->getId())
          return;
		
          if ($this->getConfiguration('has_remote') == 1) {
			$this->loadCmdFromConf('remote');
        } else {
			foreach (cmd::searchConfigurationEqLogic($this->getId(),'remote') as $cmd) {
				if (is_object($cmd)) {
					$cmd->remove();
				}
			}            
        }		

        if ($this->getConfiguration('has_medias') == 1) {
		    $this->loadCmdFromConf('medias');
        } else {
			foreach (cmd::searchConfigurationEqLogic($this->getId(),'medias') as $cmd) {
				if (is_object($cmd)) {
					$cmd->remove();
				}
			}
        }
		if ($this->getConfiguration('has_base') == 1) {
		    $this->loadCmdFromConf('base');
        } else {
			foreach (cmd::searchConfigurationEqLogic($this->getId(),'base') as $cmd) {
				if (is_object($cmd)) {
					$cmd->remove();
				}
			}
        }
		if ($this->getConfiguration('has_apps') == 1) {
			$this->addApps();
        } else {
			foreach (cmd::searchConfigurationEqLogic($this->getId(),'apps') as $cmd) {
				if (is_object($cmd)) {
					$cmd->remove();
				}
			}             
        }
		 if ($this->getConfiguration('has_inputs') == 1) {
			$this->addInputs();
        } else {
            foreach (cmd::searchConfigurationEqLogic($this->getId(),'inputs') as $cmd) {
				if (is_object($cmd)) {
					$cmd->remove();
				}
			} 	
        }
		
        if ($this->getConfiguration('has_channels') == 1) {
	            $this->addChannels();
        } else {
            foreach (cmd::searchConfigurationEqLogic($this->getId(),'channels') as $cmd) {
				if (is_object($cmd)) {
					$cmd->remove();
				}
			} 			
        }

        $state = $this->getCmd(null, 'etat');
		if (!is_object($state)) {
			$state = new WebOStvLGCmd();
			$state->setLogicalId('etat');
			$state->setIsVisible(1);
			$state->setName(__('Etat', __FILE__));
		}
		//$state->setConfiguration('request', '/site/#siteId#/security');
		$state->setConfiguration('response', 'statusLabel');
		//$state->setEventOnly(1);
		$state->setConfiguration('onlyChangeEvent',1);
		$state->setType('info');
		$state->setSubType('binary');
		$state->setIsHistorized(1);
		//$state->setDisplay('generic_type','ALARM_MODE');
		$state->setTemplate('dashboard','defaut');
		$state->setTemplate('mobile','defaut');
		$state->setEqLogic_id($this->getId());
		$state->save();
		
    }
  
    public function preSave() {
		

    }
	
    public function postSave() {}
    

	public function postInsert() {}
	
    public function toHtml($_version = 'dashboard') {
		if ($this->getIsEnable() != 1) {
            return '';
        }
		if (!$this->hasRight('r')) {
			return '';
		}
		
		
		$replace = $this->preToHtml($_version);
		if (!is_array($replace)) {
			return $replace;
		}
		$_version = jeedom::versionAlias($_version);
		
        $groups_template = array();
        $group_names = $this->getGroups();
		foreach ($group_names as $group) {
            
            $groups_template[$group] = getTemplate('core', $_version, $group, 'WebOStvLG');
            $replace['#group_'.$group.'#'] = '';
        }
        $html_groups = array();
        if ($this->getIsEnable()) {
            foreach ($this->getCmd() as $cmd) {
                $cmd_html = ' ';
                $group = $cmd->getConfiguration('group');
               
                if ($cmd->getIsVisible()) {
					if ($cmd->getType() == 'info') {
						continue;
					} else {
						$cmd_template = getTemplate('core', $_version, $group.'_cmd', 'WebOStvLG');
                        
						($cmd->getDisplay('icon') != '') ? $icon = $cmd->getDisplay('icon') : $icon = $cmd->getConfiguration('dashicon');
                       
						$cmd_replace = array(
							'#id#' => $cmd->getId(),
							'#name#' => $cmd->getName(), //($cmd->getDisplay('icon') != '') ? $cmd->getDisplay('icon') : $cmd->getName(),
                            '#dashicon#' => $icon, //getName(),
                            //'#eqLink#' => $this->getLinkToConfiguration('action'), //($cmd->getDisplay('icon') != '') ? $cmd->getDisplay('icon') : $cmd->getName(),
                            //'#action#' => (isset($action)) ? $action : '',
                            
						);
                        
						$cmd_html = template_replace($cmd_replace, $cmd_template);
                        //log::add('WebOStvLG','debug','template: '.print_r($cmd_html,true));
						if($cmd->getSubType() == "slider") {
							$audioStatus = $this->getCmd(null, 'audioStatus');
							if(is_object($audioStatus)) {
								$cmd->setConfiguration('lastCmdValue', $audioStatus->execCmd());
								$cmd->save();
							}
							$cmd_html = $cmd->toHtml();
						}						
					}
                    if (isset($html_groups[$group]))
					{
						$html_groups[$group]++;
						$html_groups[$group] .= $cmd_html;
					} else {
						$html_groups[$group] = $cmd_html; 
					}    
                } 
                $cmd_replace = array(
                    '#'.strtolower($cmd->getName()).'#' => $cmd_html,
                    );
                $groups_template[$group] = template_replace($cmd_replace, $groups_template[$group]);
               
            }
        }
        $replace['#cmd#'] = "";
        $keys = array_keys($html_groups);
		foreach ($html_groups as $group => $html_cmd) {      
            $group_template =  $groups_template[$group]; 
            $group_replace = array(
                '#cmd#' => $html_cmd,
            );
            $replace['#group_'.$group.'#'] .= template_replace($group_replace, $group_template);
            
        }
		$parameters = $this->getDisplay('parameters');
        
        if (is_array($parameters)) {
            foreach ($parameters as $key => $value) {
                $replace['#' . $key . '#'] = $value;
            }
        }
		$state = $this->getCmd(null, 'etat');
		if(is_object($state)) {
			$replace['#state#'] = $state->execCmd();
		}

        return template_replace($replace, getTemplate('core', $_version, 'eqLogic', 'WebOStvLG'));
    }
	public static function ping($state) {
        foreach (eqLogic::byType('WebOStvLG', true) as $eqLogic) {
        if ($eqLogic->getConfiguration('addr') == '') {
            return;
        }
        $changed = false;
        $ping = new WebOStvLG_Ping($eqLogic->getConfiguration('addr'));
        $state = $ping->ping($eqLogic->getConfiguration('addr'));
        return $state;
        }
    }
	
	public static function event() {
		$cmd =  WebOStvLGCmd::byId(init('id'));
	   
		if (!is_object($cmd)) {
			throw new Exception('Commande ID virtuel inconnu : ' . init('id'));
		}
	   
		$value = init('value');
       
		if ($cmd->getEqLogic()->getEqType_name() != 'WebOStvLG') {
			throw new Exception(__('La cible de la commande WebOStvLG n\'est pas un équipement de type WebOStvLG', __FILE__));
		}
		   
		$cmd->event($value);
	   
		$cmd->setConfiguration('valeur', $value);
		log::add('WebOStvLG','debug','set:'.$cmd->getName().' to '. $value);
		$cmd->save();
		
   }
    public static function etattv() {
        foreach (eqLogic::byType('WebOStvLG', true) as $eqLogic) {

        $etat = $eqLogic->ping($eqLogic->getConfiguration('addr'));
        if($etat == 1){
            $etat = 0;
        }
        else
        {
            $etat = 1;
        }
        $eqLogic->checkAndUpdateCmd('etat', $etat);
        $eqLogic->refreshWidget();
        
        log::add('WebOStvLG','info','Etat TV: ' .print_r($etat,true));

        }
    }
}

class WebOStvLGCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    public function preSave() {
        /*if ($this->getConfiguration('request') == '') {
            throw new Exception(__('La requete ne peut etre vide',__FILE__));
		}*/	
    }

    public function execute($_options = null) {
    	$WebOStvLG = $this->getEqLogic();
        $lg_path = realpath(dirname(__FILE__) . '/../../3rdparty');
		$tvip = $WebOStvLG->getConfiguration('addr');
    	$key = $WebOStvLG->getConfiguration('key');
		$volnum = $WebOStvLG->getConfiguration('volnum');
		if ($this->type == 'action') {
				$command=$this->getConfiguration('request');
                
                
                if ($this->getSubType() == 'message') {
                    if ($_options['message'] != null) {
                        $message = '"' . $_options['message'] . '"';
                    } else {
                        $message = '"Message TEST"';
                    }
                    $command = str_replace("#message#", $message, $command);
                }
                $commande= $command;
                
                if ($this->getSubType() == 'message') {
		$ret = shell_exec(system::getCmdSudo().' '.__DIR__ . '/../../resources/venv/bin/python3 /var/www/html/plugins/WebOStvLG/resources/venv/bin/lgtv ' .$command .' '.$message);
                log::add('WebOStvLG','debug','$$$ EXEC: '.__DIR__ . '/../../resources/venv/bin/python3 /var/www/html/plugins/WebOStvLG/resources/venv/bin/lgtv ' .$command .' > ' . $message . ' > ' .$ret );
                }
                else
                {
                $ret = shell_exec(system::getCmdSudo().' '.__DIR__ . '/../../resources/venv/bin/python3 /var/www/html/plugins/WebOStvLG/resources/venv/bin/lgtv ' .$command);
                log::add('WebOStvLG','debug','$$$ EXEC: '.__DIR__ . '/../../resources/venv/bin/python3 /var/www/html/plugins/WebOStvLG/resources/venv/bin/lgtv ' .$command .' > ' .$ret );
                }/*if ($command=='volumeDown' or $command=='volumeUp') {
					for ($i = 1; $i <= $volnum-1; $i++) {
						shell_exec('/usr/bin/python ' . $lg_path . '/lgtv.py ' .$commande);
					}
				}*/
		}
    }
		


    /*     * **********************Getteur Setteur*************************** */
}
?>
