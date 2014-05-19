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
include_once "modules/agent_console/libs/paloSantoConsola.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/Monitoring.class.php";

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

    $params=explode(':',getParameter('campaign'));
    $type = $params[0];
    $id = $params[1];

    switch($action){
        case "show":

            $content = viewStatMonitoring($smarty, $module_name, $local_templates_dir, $pDB, $arrConf, $type, $id);
            break;
        case "loadstat":
            $content = viewStatMonitoring($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $type, $id);
            break;
        case "loadoper":
            //$content = time();
            $content = viewOperMonitoring($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $type, $id);
            break;
        default:
            $content = viewFormMonitoring($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }
    return $content;
}

function viewFormMonitoring($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $Monitoring = new Monitoring($pDB);
    $arrFormMonitoring = createFieldForm($Monitoring->getCampaigns(), $arrConf);
    $oForm = new paloForm($smarty,$arrFormMonitoring);

    $smarty->assign("SET", _tr("Set"));
    $smarty->assign("REQUIRED_FIELD", _tr("Required field"));
    $smarty->assign("icon", "images/list.png");

    $htmlForm = $oForm->fetchForm("$local_templates_dir/form.tpl",_tr("Monitoring"));
    $content = "<form  method='POST' style='margin-bottom:0;' action='?menu=$module_name'>".$htmlForm."</form>";

    return $content;
}

function viewStatMonitoring($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $type, $id){

    $Monitoring = new Monitoring($pDB);
    $arrFormMonitoring = createFieldForm($Monitoring->getCampaigns(), $arrConf);
    $oForm = new paloForm($smarty,$arrFormMonitoring);

    //$content = viewFormMonitoring($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf);
    $stat = $Monitoring->statCampaign($type, $id);
    echo '<pre>';print_r($stat); echo '</pre>';

    //return $content;
}

function viewOperMonitoring($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf, $type, $id){
    echo 'Begin oper monitoring!';

    $oPaloConsola = new PaloSantoConsola();

    $result = $oPaloConsola->leerEstadoCampania($type, $id);

    echo ' = <pre>Agents: ';print_r($result); echo '</pre>';
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

function createFieldForm($campaigns, $arrConf)
{
    foreach ($campaigns as $key => $campaign) {
        $arrCampaigns[$campaign['type'].':'.$campaign['id']] = $campaign['name'];
    }

    $arrFields = array(
        "campaign" => array(
            "LABEL"                  => _tr("Campaign"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrCampaigns,
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
    if(isset($_POST['show']))
        return 'show';
    if(isset($_POST['action']))
        switch($_POST['action']){
            case 'loadstat':
                return "loadstat";
            break;

            case 'loadoper':
                return "loadoper";
            break;

            default:
                return '';
            break;
        }
    getParameter();
}
?>