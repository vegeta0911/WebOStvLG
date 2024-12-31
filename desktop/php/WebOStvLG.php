<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

global $listCmdWebOStvLG;

sendVarToJS('eqType', 'WebOStvLG');
$eqLogics = eqLogic::byType('WebOStvLG');
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
                    echo '<img src="plugins/WebOStvLG/doc/images/WebOStvLG_icon.png" height="105" width="95" />';
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
                <div class="form-group">
                    <label class="col-lg-2 control-label">{{Nom de la TV}}</label>
                    <div class="col-lg-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                        <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de la TV}}"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label" >{{Objet parent}}</label>
                    <div class="col-lg-3">
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
                    <label class="col-lg-2 control-label">{{Catégorie}}</label>
                    <div class="col-lg-8">
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
                <label class="col-sm-2 control-label" ></label>
                <div class="col-sm-9">
                    <label class="checkbox-inline">
                        <input type="checkbox" class="eqLogicAttr" data-label-text="" data-l1key="isEnable" checked/>{{Activer}}
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" class="eqLogicAttr" data-label-text="" data-l1key="isVisible" checked/>{{Visible}}
                    </label>
                </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">{{Adresse IP}}</label>
                    <div class="col-lg-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="addr" placeholder="{{Adresse IP}}"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">{{Adresse MAC Détectée}}</label>
                    <div class="col-lg-3">
                        <input disabled type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="mac" placeholder="{{Adresse MAC Détectée}}"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">{{Clé d'appairage Recue}}</label>
                    <div class="col-lg-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="key" placeholder="{{Clé d'appairage Recue, Laissez vide pour (ré)associer}}"/>
                    </div>
                    {{Laissez vide ou Supprimez la clé pour (re)faire l'association avec la TV.}}
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label" >{{Commandes à créer}}</label>
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
                        <!--label class="checkbox-inline">
                            <input type="checkbox" class="eqLogicAttr" data-label-text="" data-l1key="configuration" data-l2key="has_remote" checked/>{{Remote}}
                        </label-->

                    </div>
                </div>
            </fieldset>
        </form>

        <legend>Commandes</legend>
        <div class="alert alert-info">
         {{Comment Faire :<br/>
            - Activez le LG CONNECT APPS dans le menu de la tv, Menu / Réseau / LG CONNECT APPS / ACTIVE<br/>
			- Cliquez sur enregistrer apres  bien avoir mis l'adresse IP, la TV vous demandera une confirmation de connexion<br/>
            - Rajouter les blocs de commandes de votre choix en les choisissant au dessus<br/>
			}}
        </div>
        <form class="form-horizontal">
            <fieldset>
                <div class="form-actions">
                    <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
                    <a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
                </div>
            </fieldset>
        </form>
        <table id="table_cmd" class="table table-bordered table-condensed">
            <thead>
                <tr>
	  <th  >{{N°}}</th>
      <th >{{Nom}}</th>
	  <th >{{Parametres}}</th>
	  <th >{{Afficher}}</th>
	  <th ></th>
    </tr>
            </thead>
            <tbody>

            </tbody>
        </table>

        <form class="form-horizontal">
            <fieldset>
                <div class="form-actions">
				    <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
                    <a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
                </div>
            </fieldset>
        </form>

    </div>
</div>

<?php include_file('desktop', 'WebOStvLG', 'js', 'WebOStvLG'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>