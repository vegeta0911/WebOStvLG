<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

global $listCmdWebOStvLG;

$internalAddr=config::byKey('internalAddr');
$internalComplement=config::byKey('internalComplement');
$externalAddr=config::byKey('externalAddr');
$externalComplement=config::byKey('externalComplement');

$base_url='';
if($_SERVER['SERVER_NAME'] == $internalAddr){
    $base_url=$internalComplement;
}
if($_SERVER['SERVER_NAME'] == $externalAddr){
    $base_url=$externalComplement;
}
if($base_url != ''){
    if(substr($base_url,0,1) != '/'){
        $base_url='/' . $base_url;
    }
    if(substr($base_url,(strlen($base_url) -1),1) == '/'){
        $base_url=substr($base_url,0,(strlen($base_url) -1));
    }
}

sendVarToJs('base_url', $base_url);
sendVarToJS('eqType', 'WebOStvLG');
$eqLogics = eqLogic::byType('WebOStvLG');
$version_WebOStvLG=config::byKey('version_WebOStvLG', 'WebOStvLG');
sendVarToJS('version_WebOStvLG', $version_WebOStvLG);
?>

<div class="row row-overflow">
    <div class="col-lg-2">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter une TV}}</a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
                foreach ($eqLogics as $eqLogic) {
                    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
                }
                ?>
            </ul>
        </div>
    </div>
	<div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
        <legend>{{Mes TVs}}
        </legend>
        <div class="eqLogicThumbnailContainer">
                      <div class="cursor eqLogicAction" data-action="add" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
           <center>
            <i class="fa fa-plus-circle" style="font-size : 7em;color:#28a3d3;"></i>
        </center>
        <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;;color:#28a3d3"><center>Ajouter</center></span>
    </div>
         <?php
                foreach ($eqLogics as $eqLogic) {
                    echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >';
                    echo "<center>";
                    echo '<img src="plugins/WebOStvLG/plugin_info/WebOStvLG_icon.png"/>';
                    echo "</center>";
                    echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
                    echo '</div>';
                }
                ?>
            </div>
    </div>   
    <div class="col-lg-10 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
        <form class="form-horizontal">
            <fieldset>
                <legend><i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{Général}}<i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i></legend>
                <div class="alert alert-info">
                {{Comment Faire :<br/>
                    - Activez le LG CONNECT APPS dans le menu de la tv, Menu / Réseau / LG CONNECT APPS / ACTIVE<br/>
		    - Cliquez sur enregistrer, si il y a un message d'erreur merci de renseigner l'adresse IP, la TV vous demandera une confirmation de connexion<br/>
                    - Rajouter les blocs de commandes de votre choix en les choisissant au dessus<br/>
	        }}
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Nom de la TV}}</label>
                    <div class="col-sm-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                        <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de la TV}}"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label" >{{Objet parent}}</label>
                    <div class="col-sm-3">
                        <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                            <option value="">{{Aucun}}</option>
                            <?php
                            foreach (jeeObject::all() as $object) {
                                echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Catégorie}}</label>
                    <div class="col-sm-8">
                        <?php
                        foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                            echo '<label class="checkbox-inline">';
                            echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                            echo '</label>';
                        }
                        ?>

                    </div>
                </div>
                <div class="form-group">
                <label class="col-sm-3 control-label" ></label>
                <div class="col-sm-8">
                    <label class="checkbox-inline">
                        <input type="checkbox" class="eqLogicAttr" data-label-text="" data-l1key="isEnable" checked/>{{Activer}}
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" class="eqLogicAttr" data-label-text="" data-l1key="isVisible" checked/>{{Visible}}
                    </label>
                </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Adresse IP}}</label>
                    <div class="col-sm-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="addr" placeholder="{{Adresse IP}}"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Clé d'appairage Recue}}</label>
                    <div class="col-sm-4">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="key" placeholder="{{Clé d'appairage Recue, Laissez vide pour (ré)associer}}"/>
                    </div>
                    {{Laissez vide ou Supprimez la clé pour (re)faire l'association avec la TV.}}
                </div>

                <div class="form-group">
                <label class="col-sm-3 control-label">{{Model}}</label>
                    <div class="col-sm-3">
                        <span type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="model"></span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-3 control-label">{{Version OS}}</label>
                    <div class="col-sm-3">
                        <span type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="versionos"></span>
                    </div>
                </div>
            
            <div class="form-group">
              <label class="col-sm-3 control-label">{{Version Majeur}}</label>
                <div class="col-sm-3">
                  <span type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="majeur"></span>
                </div>
            </div>
            <div class="form-group">
              <label class="col-sm-3 control-label">{{Version mineur}}</label>
                <div class="col-sm-3">
                  <span type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="mineur"></span>
                </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">{{Mac Adresse}}</label>
                <div class="col-sm-3">
                  <span type="text" class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="mac"></span>
                </div>
            </div>
            
                <div class="form-group">
                    <label class="col-sm-2 control-label" >{{Groupe de commande}}</label>
                    <div class="col-sm-9">
                        <label class="checkbox-inline">
                            <input type="checkbox" class="eqLogicAttr" data-label-text="" data-l1key="configuration" data-l2key="has_base" checked/>{{Commandes de Base}}
                        </label>
                        <label class="checkbox-inline">
                            <input type="checkbox" class="eqLogicAttr" data-label-text="" data-l1key="configuration" data-l2key="has_inputs" checked/>{{Entrées}}
                        </label>
                        <label class="checkbox-inline">
                            <input type="checkbox" class="eqLogicAttr" data-label-text="" data-l1key="configuration" data-l2key="has_medias" checked/>{{Media}}
                        </label>
                        <label class="checkbox-inline">
                            <input type="checkbox" class="eqLogicAttr" data-label-text="" data-l1key="configuration" data-l2key="has_apps" checked/>{{Applications}}
                        </label>
                        <label class="checkbox-inline">
                            <input type="checkbox" class="eqLogicAttr" data-label-text="" data-l1key="configuration" data-l2key="has_channels" checked/>{{Chaines TNT}}
                        </label>
                         <label class="checkbox-inline">
                            <input type="checkbox" class="eqLogicAttr" data-label-text="" data-l1key="configuration" data-l2key="has_remote" checked/>{{Magic remote}}
                        </label>
                        <label class="checkbox-inline">
                            <input type="checkbox" class="eqLogicAttr" data-label-text="" data-l1key="configuration" data-l2key="statut"/>{{Statut TV}}
                            <label class="col-xs-6 control-label help" data-help="{{Cocher la case si la fonction TOUJOURS PRÊT est active}}"></label>
                        </label>

                    </div>
                </div>
            </fieldset>
        </form>
     <form class="form-horizontal">
         <fieldset>
             <div class="form-actions">
                 <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
                 <a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
             </div>
         </fieldset>
     </form>
        <legend>Commandes</legend>

    <div role="tabpanel" class="tab-pane" id="commandtab">
         
		<ul class="nav nav-tabs" id="tab_lg">
			<!--li class="active"><a href="#tab_custom"><i class="fas fa-list-alt"></i>  {{Custom}}</a></li-->
			<li><a href="#tab_base"><i class="fas fa-wrench"></i>  {{Base}}</a></li>
			<li><a href="#tab_inputs"><i class="fas fa-microphone"></i>  {{Inputs}}</a></li>
			<li><a href="#tab_apps"><i class="fas fa-random"></i>  {{Applications}}</a></li>
			<li><a href="#tab_channels"><i class="fas fa-tv"></i>  {{Chaînes}}</a></li>
            <li><a href="#tab_medias"><i class="fas fa-video"></i>  {{Medias}}</a></li> 
            <li><a href="#tab_remote"><i class="fas fa-magic"></i>  {{Magic remote}}</a></li>            
		</ul>
	<div class="tab-content">
			<div class="tab-pane active" id="tab_custom">            
         
        <!--a class="btn btn-success btn-sm cmdAction pull-left" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Commandes}}</a><br/><br/-->
        <table id="table_custom" class="table table-bordered table-condensed">
            <thead>
                <tr>
                    <th style="width: 300px;">{{Nom}}</th>
                    <th style="width: 100px;">{{Type}}</th>
                    <th>{{Parametre(s)}}</th>
                    <th style="width: 150px;">{{Options}}</th>
                    <th >{{Actions}}</th>
                </tr>
            </thead>
        <tbody>
        </tbody>
        </table>  
    </div>

    <div class="tab-pane" id="tab_base">
        <table id="table_base" class="table table-bordered table-condensed">
            <thead>
                <tr>
                    <th style="width: 300px;">{{Nom}}</th>
                    <th style="width: 100px;">{{Type}}</th>
                    <th>{{Parametre(s)}}</th>
                    <th style="width: 150px;">{{Options}}</th>
                    <th>{{Actions}}</th>
                </tr>
            </thead>
        <tbody>
        </tbody>
        </table>                    	
    </div>

    <div class="tab-pane" id="tab_inputs">
        <table id="table_inputs" class="table table-bordered table-condensed">
            <thead>
                <tr>
                    <th style="width: 300px;">{{Nom}}</th>
                    <th style="width: 100px;">{{Type}}</th>
                    <th>{{Parametre(s)}}</th>
                    <th style="width: 150px;">{{Options}}</th>
                    <th>{{Actions}}</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>             	
    </div>

    <div class="tab-pane" id="tab_apps">
        <table id="table_apps" class="table table-bordered table-condensed">
            <thead>
                <tr>
                    <th style="width: 300px;">{{Nom}}</th>
                    <th style="width: 100px;">{{Type}}</th>
                    <th>{{Parametre(s)}}</th>
                    <th style="width: 150px;">{{Options}}</th>
                    <th>{{Actions}}</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>            
    </div>

    <div class="tab-pane" id="tab_channels">
        <table id="table_channels" class="table table-bordered table-condensed">
            <thead>
                <tr>
                    <th style="width: 300px;">{{Nom}}</th>
                    <th style="width: 100px;">{{Type}}</th>
                    <th>{{Parametre(s)}}</th>
                    <th style="width: 150px;">{{Options}}</th>
                    <th>{{Actions}}</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>  
    </div>

    <div class="tab-pane" id="tab_medias">
        <table id="table_medias" class="table table-bordered table-condensed">
            <thead>
                <tr>
                    <th style="width: 300px;">{{Nom}}</th>
                    <th style="width: 100px;">{{Type}}</th>
                    <th>{{Parametre(s)}}</th>
                    <th style="width: 150px;">{{Options}}</th>
                    <th>{{Actions}}</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>            
    </div> 

    <div class="tab-pane" id="tab_remote">
        <table id="table_remote" class="table table-bordered table-condensed">
            <thead>
                <tr>
                    <th style="width: 300px;">{{Nom}}</th>
                    <th style="width: 100px;">{{Type}}</th>
                    <th>{{Parametre(s)}}</th>
                    <th style="width: 150px;">{{Options}}</th>
                    <th>{{Actions}}</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>            
    </div>  
                    </div>
        </div>
    
        <!--form class="form-horizontal">
            <fieldset>
                <div class="form-actions">
				    <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
                    <a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
                </div>
            </fieldset>
        </form-->

    </div>
</div>

<?php include_file('desktop', 'WebOStvLG', 'js', 'WebOStvLG'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
