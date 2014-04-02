/**
 * Created by aagafonov on 20.03.14.
 */
var module_name = 'dm_outbound_manager';

$(document).ready(function() {

    $('#about_elastix2').click(function() { $('#acerca_de').dialog('open'); });
    $('#acerca_de').dialog({
        autoOpen: false,
        width: 500,
        height: 300,
        modal: true,
        buttons: [
            {
                text: "Close",
                click: function() { $(this).dialog('close'); }
            }
        ]
    });

    $('#check-all').click(function(){
        $("input:checkbox").attr('checked', true);
    });
    $('#uncheck-all').click(function(){
        $("input:checkbox").attr('checked', false);
    });

});

function show(element) {
    element = document.getElementById(element);
    element.style.display = "block";
    }

function hide(element) {
    element = document.getElementById(element);
    element.style.display = "none";
}

function closeForm() {
    hide('edit_form');
}

function loadForm(id)
{
    show('edit_form');
    $.post('index.php?menu=' + module_name + '&rawmode=yes', {
            menu:		module_name,
            rawmode:	'yes',
            action:		'loadform',
            id_call:    id
        },
        function (respuesta) {
            $(function(){$('#form_content').html((respuesta));});
        })
        .fail(function() {
            alert('Failed to connect to server to run request!');
        });
}

function deleteForm(id)
{
    if(confirm('You are sure?')){

        var checkbox = document.getElementsByName('id_call[]')
        var len = checkbox.length;
        var checked = [];
        for(var i = 0; i < len; i++) {
            if(checkbox[i].type == 'checkbox') {
                if(checkbox[i].checked || checkbox[i].value == id) checked.push(checkbox[i].value);
            }
        }

        $.post('index.php?menu=' + module_name + '&rawmode=yes', {
                menu:		module_name,
                rawmode:	'yes',
                action:		'deleteform',
                id_call:    checked
            },
            function () {
                var selectBox = document.getElementById("campaign");
                var selectedValue = selectBox.options[selectBox.selectedIndex].value;
                $(function(){window.location.href = 'index.php?menu=' + module_name + '&campaign=' + selectedValue;});
            })
            .fail(function() {
                alert('Failed to connect to server to run request!');
            });
    } else {

    }
}
