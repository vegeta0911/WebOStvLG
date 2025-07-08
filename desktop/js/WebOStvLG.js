
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
$('#tab_lg a').click(function (e) {
    e.preventDefault()
    $(this).tab('show')
})

$(function() {
    $("#table_cmd tbody").delegate(".listCmdWebOStvLG", 'click', function(event) {
        $('.description').hide();
        $('.version').hide();
        $('.required').hide();
        $('.description.' + $('#sel_addPreConfigCmdWebOStvLG').value()).show();
        $('.version.' + $('#sel_addPreConfigCmdWebOStvLG').value()).show();
        $('.required.' + $('#sel_addPreConfigCmdWebOStvLG').value()).show();
        $('#md_addPreConfigCmdWebOStvLG').modal('show');
        $('#bt_addPreConfigCmdWebOStvLGSave').undelegate().unbind();
        var tr = $(this).closest('tr');
        $("#div_mainContainer").delegate("#bt_addPreConfigCmdWebOStvLGSave", 'click', function(event) {
            var cmd_data = json_decode($('.json_cmd.' + $('#sel_addPreConfigCmdWebOStvLG').value()).value());
            tr.setValues(cmd_data, '.cmdAttr');
            $('#md_addPreConfigCmdWebOStvLG').modal('hide');
        });
    });

    $("#sel_addPreConfigCmdWebOStvLG").on('change', function(event) {
        $('.description').hide();
        $('.version').hide();
        $('.required').hide();
        $('.description.' + $(this).value()).show();
        $('.version.' + $(this).value()).show();
        $('.required.' + $(this).value()).show();
    });

    $("#table_custom").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
 	$("#table_base").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});	
    $("#table_input").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
 	$("#table_apps").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});	
    $("#table_channels").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
    $("#table_medias").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
    $("#table_remote").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
});



function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    
    if(!isset(_cmd.configuration.group)) {
		var value = "first",
			group = "custom";
	} else {
		var value = "last",
			group = _cmd.configuration.group;
	}

    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td class="name">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">   ';
    tr += '<span class="cmdAttr" data-l1key="id"></span>   </td>';
    tr += '<td class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType();
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span></td>';
    tr += '<td ><input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="parameters" style="margin-top : 5px;" />';
    tr += '<input type="hidden" class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="group">';
    tr += '</td>';
    tr += '<td>';
    tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/> {{Afficher}}<br/></span>';
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
	tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
    tr += '</tr>';
    if (value == "last") {
		$('#table_' + group + ' tbody').append(tr);
		$('#table_' + group + ' tbody tr:last').setValues(_cmd, '.cmdAttr');
		jeedom.cmd.changeType($('#table_' + group + ' tbody tr:last'), init(_cmd.subType));
	} else {
		$(tr).prependTo("#table_custom > tbody");
		jeedom.cmd.changeType($('#table_custom tbody tr:first'), init(_cmd.subType));
		$('#table_custom tbody tr:first .group').val('custom')
	}
	$("#tab_apps input.cmdAttr[data-l1key=name],#tab_inputs input.cmdAttr[data-l1key=name],#tab_channels input.cmdAttr[data-l1key=name],#tab_base input.cmdAttr[data-l1key=name],#tab_medias input.cmdAttr[data-l1key=name],#tab_remote input.cmdAttr[data-l1key=name]").attr("disabled", true);
}

if(version_WebOStvLG != '4'){
  bootbox.alert({
    message: "<h2>Bienvenue sur WebOStvLG !</h2> <br/><br/>Je me permets de soliciter <u>votre aide</u> !<br/><br/> Accepteriez vous de laisser un avis sur le Marquet de JEEDOM <a href='https://market.jeedom.com/index.php?v=d&p=market_display&id=4554'><u>CLIQUEZ ICI POUR LE FAIRE</u></a> ? <br/><br/>Et de plus : je cherche à <b>faire connaître</b> l'application WebOStvLG , et qui mieux que <b>VOUS</b> pour en faire la promotion ! Je compte sur vous pour en parler autour de vous !<br/><br/><i>Ce message ne s'affichera plus , soyez tranquille</i>",
  })
  $.ajax({
    type: 'POST',
    url: base_url + '/plugins/WebOStvLG/core/ajax/WebOStvLG.ajax.php',
    data: {
        action: 'set_version_WebOStvLG',
        version_WebOStvLG: init('4'),
    },
    dataType: 'json',
    error: function (request, status, error) {
        handleAjaxError(request, status, error);
    },
    success: function (data) {
        if (data.state != 'ok') {
            $('#div_alert').showAlert({message: 'Erreur message migration WebOStvLG', level: 'danger'});
            return;
        }
    }
  })
}
