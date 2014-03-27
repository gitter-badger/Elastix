<?php
  /* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 2.4.0-11                                               |
  | http://www.elastix.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  | http://www.palosanto.com                                             |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Original Code is: Elastix Open Source.                           |
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: index.php,v 1.1 2014-02-27 12:02:11 Supme supmea@gmail.com Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoConfig.class.php";
include_once "modules/agent_console/libs/ECCP.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/Operator_Break.class.php";

    //include file language agree to elastix configuration
    //if file language not exists, then include language by default (en)
    $lang=get_language();
    $base_dir=dirname($_SERVER['SCRIPT_FILENAME']);
    $lang_file="modules/$module_name/lang/$lang.lang";
    if (file_exists("$base_dir/$lang_file")) include_once "$lang_file";
    else include_once "modules/$module_name/lang/en.lang";

    //global variables
    global $arrConf;
    global $arrConfModule;
    global $arrLang;
    global $arrLangModule;
    $arrConf = array_merge($arrConf,$arrConfModule);
    $arrLang = array_merge($arrLang,$arrLangModule);

    //folder path for custom templates
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    //conexion resource
    $pDB = new paloDB($arrConf['dsn_conn_database']);

    //actions
    $action = getAction();
    $content = "";

    switch($action){
        case "set":
            $content = setOperator_Break($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
        default:
            $content = viewFormOperator_Break($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }
    return $content;
}

function viewFormOperator_Break($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pOperator_Break = new Operator_Break($pDB);
    $arrFormOperator_Break = createFieldForm($pOperator_Break->getAgents(), $pOperator_Break->getBreaks(), $arrConf);
    $oForm = new paloForm($smarty,$arrFormOperator_Break);

    //begin, Form data persistence to errors and other events.
    $_DATA  = $_POST;
    $action = getParameter("action");
    $id     = getParameter("id");
    $smarty->assign("ID", $id); //persistence id with input hidden in tpl

    $smarty->assign("SET", _tr("Set"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("icon", "images/list.png");

    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl",_tr("Operator Break"), $_DATA);
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}

function setOperator_Break($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pOperator_Break = new Operator_Break($pDB);
    $arrFormOperator_Break = createFieldForm($pOperator_Break->getAgents(), $pOperator_Break->getBreaks(), $arrConf);
    $oForm = new paloForm($smarty,$arrFormOperator_Break);

    if(!$oForm->validateForm($_POST)){
        // Validation basic, not empty and VALIDATION_TYPE 
        $smarty->assign("mb_title", _tr("Validation Error"));
        $arrErrores = $oForm->arrErroresValidacion;
        $strErrorMsg = "<b>"._tr("The following fields contain errors").":</b><br/>";
        if(is_array($arrErrores) && count($arrErrores) > 0){
            foreach($arrErrores as $k=>$v)
                $strErrorMsg .= "$k, ";
        }
        $smarty->assign("mb_message", $strErrorMsg);
        $content = viewFormOperator_Break($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
    }
    else{
        $error = "Ok";
        $eccp = new ECCP();
        $cr = $eccp->connect($arrConf["eccp_host"], $arrConf["eccp_login"], $arrConf["eccp_password"]);
        if (isset($cr->failure)) $error = "Error connect";
        $agents = getParameter('agents');
        $break =  getParameter('breaks');
        foreach($agents as $agent){
            $password = $pOperator_Break->getPassword($agent);
            $eccp->setAgentNumber($password['type']."/".$password['number']);
            $eccp->setAgentPass($password['password']);
            if($break != '0' and $break != 'logout'){
                $response = $eccp->pauseagent($break);
                if (isset($response->failure)) {
                    $eccp->unpauseagent();
                    $response = $eccp->pauseagent($break);
                    if (isset($response->failure)) $error = "Error";
                }
            }
            else{
                if($break == 'logout') $response = $eccp->logoutagent();
                else $response = $eccp->unpauseagent();
                if (isset($response->failure)) $error = "Error";
            }
        }

        $eccp->disconnect();
    }

    $smarty->assign("mb_title", _tr("Set status"));
    $smarty->assign("mb_message", _tr($error));

    return viewFormOperator_Break($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);;
}

function createFieldForm($agents, $breaks, $arrConf)
{
    $arrBreaks = array(0 => _tr("Working"));
    foreach ($breaks as $break) {
        $arrBreaks[$break['id']] = $break['name']."\n";
    }
    $arrBreaks['logout'] = _tr('Logout');

    foreach ($agents as $agent) {
        if(getStatus($agent['type'], $agent['number'], $arrConf) != 'offline'){
            $arrAgents[$agent['id']] = $agent['name'];
            if (!$agent['duration'] and $agent['id_break'])
                $arrAgents[$agent['id']] .= " (".$arrBreaks[($agent['id_break'])].")";
        }

    }

    $arrFields = array(
            "agents"   => array(            "LABEL"                  => _tr("Agents"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrAgents,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => "",
                                            "EDITABLE"               => "si",
                                            "SIZE"                  => "40",
                                            "MULTIPLE"              => true,
                                            ),
            "breaks"   => array(            "LABEL"                  => _tr("Breaks"),
                                            "REQUIRED"               => "no",
                                            "INPUT_TYPE"             => "SELECT",
                                            "INPUT_EXTRA_PARAM"      => $arrBreaks,
                                            "VALIDATION_TYPE"        => "text",
                                            "VALIDATION_EXTRA_PARAM" => ""
                                            ), 

            );
    return $arrFields;
}


function getStatus($type, $number, $arrConf){
    $eccp = new ECCP();
    $eccp->connect($arrConf["eccp_host"], $arrConf["eccp_login"], $arrConf["eccp_password"]);

    $eccp->setAgentNumber($type."/".$number);
    $res = $eccp->getagentstatus();

    $eccp->disconnect();

    return $res->status;
}


function getAction()
{
    if(getParameter("set")) //Get parameter by POST (submit)
        return "set";
}
?>