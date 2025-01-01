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

class WebOStvLG extends eqLogic {
    const PYTHON_PATH = __DIR__ . '/../../resources/venv/bin/python3';
    const EXEC_LG = self::PYTHON_PATH .' /var/www/html/plugins/WebOStvLG/resources/venv/bin/lgtv';
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */
    
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
      
        $execpython = self::PYTHON_PATH .' /var/www/html/plugins/WebOStvLG/resources/venv/bin/lgtv';
        $lgtvscan = shell_exec($execpython .' scan');
        $datascan = json_decode($lgtvscan,true);
        $lg_path = realpath(dirname(__FILE__) . '/../../3rdparty');
        
        if($this->getConfiguration('key') == ''){
        if($datascan['result'] != 'ok'){
        
            throw new Exception(__('Je ne trouve pas de TV LG',__FILE__));
        }
        
        $tv_info = $datascan['list'][0];
         
        if($this->getConfiguration('addr') == ''){
          $this->setConfiguration('addr', $tv_info["address"]);
          $this->save(true);
        }
        $lgtvauth = shell_exec($execpython .' auth '. $this->getConfiguration('addr') .' MyTV'); //.'"'.$tv_info['tv_name'].'"');
        
        if($lg_path.'/lgtv' != ''){
            $remove = shell_exec('rm -R '.$lg_path.'/lgtv');
        }
        $lgtvcopy = exec('cp -R /var/www/.lgtv'.' '.$lg_path.'/lgtv');
        }
        $lgtvjson = file_get_contents($lg_path.'/lgtv/config.json');
        $lgtvjsonin = json_decode($lgtvjson, true);
        
        if ($lgtvjsonin["MyTV"]["key"] != "") {
                $this->setConfiguration('key', $lgtvjsonin["MyTV"]["key"]);
                $this->setConfiguration('mac', $lgtvjsonin["MyTV"]["mac"]);

                //print("OK, la clé est " . $ret["client-key"]);
            log::add('WebOStvLG','debug','lgtvauth: ' . print_r($lgtvjson,true));
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
		
		//log::add('WebOStvLG', 'debug','content' . print_r($content,true));
		
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
			if (array_key_exists('icon', $command)) {
				if ($command['icon'] != '')
					$webosTvCmd->setDisplay('icon', '<i  style="color:'.$command["color"].'" class="'.$command["icon"].'"></i>');
			}
			$webosTvCmd->save();
		}
        log::add('WebOStvLG', 'debug','exist:'. $command['configuration']['group']);
	}	

    public function addApps() {
        $lgcommand = '--name MyTV listApps';
        $json_in = shell_exec(system::getCmdSudo() . self::EXEC_LG .' '. $lgcommand );    
        $json = str_replace('{"closing": {"code": 1000, "reason": ""}}', '', $json_in);
        //log::add('WebOStvLG', 'debug', json_decode($json,true));
        if($json == ''){
        
            throw new Exception(__('La TV LG est éteinte',__FILE__));
        }
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
                
                if($name == "Live TV" || $name != "Mode Expo." && $name != "InputCommon" && $name != "DvrPopup" && substr($name,0,4) != "Live" && $name != "Local Control Panel" && $name != "User Agreement" && $name != "QML Factorywin" && $name != "Publicité" && $name != "Thirdparty Login" && $name != "Viewer" && $name != "Service clientèle"){
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
                $webosTvCmd->setConfiguration('request', '--name MyTV startApp ' . $inputs["id"]);
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
		$lgcommand = '--name MyTV listInputs';
		$json_in = shell_exec(system::getCmdSudo() . self::EXEC_LG .' '. $lgcommand );
		$json = str_replace('{"closing": {"code": 1000, "reason": ""}}', '', $json_in);		
		if (!is_json($json)) {
			log::add('WebOStvLG', 'debug', '| Impossible de continuer la récupération ' );
			return;
		}
		log::add('WebOStvLG', 'debug', '|  json: listInputs' );	
		$ret = json_decode($json, true);
		foreach ($ret["payload"]["devices"] as $inputs) {
		  //  //log::add('webosTv', 'debug', '$inputs:' . print_r($inputs, true));
			if (array_key_exists('label', $inputs)) {
				//$inputs["label"] = str_replace("'", " ", $inputs["label"]);
				//$inputs["label"] = str_replace("&", " ", $inputs["label"]);
				log::add('webosTv', 'debug', '| NEW INPUT FOUND:' . $inputs["label"]);
				/*if ($inputs["icon"] != "") {
					log::add('WebOStvLG', 'debug', '| Download icon of Input (' . $inputs["icon"] . ") : " );
					$downcheck = file_put_contents(realpath(dirname(__FILE__)) . "/../template/images/icons_inputs/" . $inputs["label"] . ".png", fopen($inputs["icon"], 'r'));

				}*/
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
				$webosTvCmd->setConfiguration('dashicon', $inputs["label"]);
				$webosTvCmd->setConfiguration('request', '--name MyTV setInput ' . $inputs["id"]);
				$webosTvCmd->setConfiguration('parameters', 'Passer sur l entree ' . $inputs["label"]);
				$webosTvCmd->setConfiguration('group', 'inputs');
				$webosTvCmd->save();				
			}
		}
	}

    public function addChannels(){
        $lgcommand = '--name MyTV listChannels';
        $json_in = shell_exec(system::getCmdSudo() . self::EXEC_LG .' '. $lgcommand );
        $json = str_replace('{"closing": {"code": 1000, "reason": ""}}', '', $json_in);
        if($json_in == ''){
        
            throw new Exception(__('La TV LG est éteinte',__FILE__));
        }
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
                        $WebOStvLGCmd->setConfiguration('request', '--name MyTV setTVChannel ' . $inputs["channelId"]);
                        $WebOStvLGCmd->setConfiguration('parameters', 'Mettre la chaine ' . $inputs["channelNumber"]);
                        $WebOStvLGCmd->setConfiguration('group', 'channels');
                        $WebOStvLGCmd->save();							
                    }
                }
            }
        }		
    }
	
    public function postUpdate() {}
  
    public function preSave() {
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
		$state = $this->getCmd(null, 'state');
		if(is_object($state)) {
			$replace['#state#'] = $state->execCmd();
		}
       
        return template_replace($replace, getTemplate('core', $_version, 'eqLogic', 'WebOStvLG'));
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
                    if ($_options['message'] != "") {
                        $message = '"' . $_options['message'] . '"';
                    } else {
                        $message = '"Message TEST"';
                    }
                    $command = str_replace("#message#", $message, $command);
                }
                $commande= $command;
                
				$ret = shell_exec(__DIR__ . '/../../resources/venv/bin/python3 /var/www/html/plugins/WebOStvLG/resources/venv/bin/lgtv ' .$command .' '.$message);
                log::add('WebOStvLG','debug','$$$ EXEC: python3 ' . $lg_path . '/lgtv ' .$command . " > " . $message . " > " .$ret );
                /*if ($command=='volumeDown' or $command=='volumeUp') {
					for ($i = 1; $i <= $volnum-1; $i++) {
						shell_exec('/usr/bin/python ' . $lg_path . '/lgtv.py ' .$commande);
					}
				}*/
		}
    }
		


    /*     * **********************Getteur Setteur*************************** */
}
?>
