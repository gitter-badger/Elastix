/**
 * Created by aagafonov on 20.03.14.
 */

$( document ).ready(function() {
    report = document.getElementById("report");
    report.setAttribute("onchange", "changeReport();");
    report = document.getElementById("span");
    report.setAttribute("onchange", "changeSpan();");
    changeReport();
    changeSpan();
});

function show(element) {
    element = document.getElementById(element);
    element.style.display = "block";
    }

function hide(element) {
    element = document.getElementById(element);
    element.style.display = "none";
}

function changeReport() {
    var selectBox = document.getElementById("report");
    var selectedValue = selectBox.options[selectBox.selectedIndex].value;
    if(selectedValue == 'calls') {
        hide('ivr');
        show('agent');
        show('queue_in');
        show('queue_out');
    }
    if(selectedValue == 'oncalls') {
        hide('ivr');
        hide('agent');
        show('queue_in');
        show('queue_out');
    }
    if(selectedValue == 'ivr') {
        show('ivr');
        hide('agent');
        hide('queue_in');
        hide('queue_out');
    }
}

function changeSpan() {
    var selectBox = document.getElementById("span");
    var selectedValue = selectBox.options[selectBox.selectedIndex].value;
}