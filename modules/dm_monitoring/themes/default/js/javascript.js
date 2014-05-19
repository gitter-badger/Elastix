/**
 * Created by aagafonov on 20.03.14.
 */
var module_name = 'dm_monitoring';

var i = 0;

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
//    setInterval(loadStat(1), 5000);
    setInterval(function(){loadOper()}, 1000);
    setInterval(function(){loadStat(1)}, 5000);
//    display();

});

function display()
{
    myTimer();
    //loadOper();
    //i = i + 1;
    //if(i = 5) loadStat(1);

    setTimeout(display(),1000);
}

function myStat() {
    var d = new Date();
    document.getElementById("stat").innerHTML = d.toLocaleTimeString();
}

function myOper() {
    var d = new Date();
    document.getElementById("oper").innerHTML = d.toLocaleTimeString();
}


function loadStat()
{
    $.post('index.php?menu=' + module_name + '&rawmode=yes', {
            menu:		module_name,
            rawmode:	'yes',
            action:		'loadstat',
            campaign:    document.getElementById("campaign").options[document.getElementById("campaign").selectedIndex].value
        },
        function (respuesta) {
            $(function(){$('#stat').html((respuesta));});
        })
        .fail(function() {
            alert('Failed to connect to server to run request!');
        });
}

function loadOper()
{
    $.post('index.php?menu=' + module_name + '&rawmode=yes', {
            menu:		module_name,
            rawmode:	'yes',
            action:		'loadoper',
            campaign:    document.getElementById("campaign").options[document.getElementById("campaign").selectedIndex].value
        },
        function (respuesta) {
            $(function(){$('#oper').html((respuesta));});
        })
        .fail(function() {
            alert('Failed to connect to server to run request!');
        });
}