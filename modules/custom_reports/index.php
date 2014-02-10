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
  $Id: index.php,v 1.1 2014-01-31 12:01:59 supme supmea@gmail.com Exp $ */
//include elastix framework
include_once "libs/paloSantoGrid.class.php";
include_once "libs/paloSantoForm.class.php";
include_once "libs/paloSantoConfig.class.php";
include_once "libs/paloSantoQueue.class.php";

function _moduleContent(&$smarty, $module_name)
{
    //include module files
    include_once "modules/$module_name/configs/default.conf.php";
    include_once "modules/$module_name/libs/paloSantoCustom_Reports.class.php";

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
    //$pDB = "";

/*
 *  Это выбор очередей из базы астериска, она нам тут пока не нужна
 *
    //Получаем список очередей, для этого парсим конфиг
    //ToDo -------------- вот это перенести в файл конфига, это же конфиг -------------------------------
    $pConfig = new paloConfig("/etc", "amportal.conf", "=", "[[:space:]]*=[[:space:]]*");
    $ampconfig = $pConfig->leer_configuracion(false);
    $ampdsn = $ampconfig['AMPDBENGINE']['valor'] . "://" . $ampconfig['AMPDBUSER']['valor'] .
        ":" . $ampconfig['AMPDBPASS']['valor'] . "@" . $ampconfig['AMPDBHOST']['valor'] . "/asterisk";

    //Получаем список всех очередейд
    $oQueue = new paloQueue($ampdsn);
    $listQueue = $oQueue->getQueue();
    if (!is_array($listQueue)) {
        $smarty->assign("mb_title", _tr("Error when connecting to database"));
        $smarty->assign("mb_message", $oQueue->errMsg);
    }
*/

    //actions
    $action = getAction();
    $content = "";

    switch($action){
        default:
            $content = reportCustom_Reports($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }
    return $content;
}

function reportCustom_Reports($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pCustom_Reports = new paloSantoCustom_Reports($pDB);

    //Получаем присланные параметры нужные нам
    $campaign_in = getParameter("queue_in");
    $campaign_out = getParameter("queue_out");
    $date_start = getParameter("date_start");
    $date_end = getParameter("date_end");
    $span = getParameter("span");

    //Параметры для грида
    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->setTitle(_tr("Custom Reports"));
    $oGrid->pagingShow(false); // не показывать пагинатор.

//    $oGrid->enableExport();   // включить экспорт результатов.
    $oGrid->setNameFile_Export(_tr("Custom Reports"));

    //Добавляем в урл страницы дополнительные параметры
    $url = array(
        "menu"       =>  $module_name,
    );
    $oGrid->setURL($url);

    //Столбцы для отображения в гриде
    $arrColumns = array(_tr("detail"),_tr("total"),_tr("success"),_tr("unsuccessful"),_tr("dialing time"),_tr("connection time"),_tr("total time"),_tr("max time"),_tr("average time"),_tr("cancel call"),);
    $oGrid->setColumns($arrColumns);

    // Передаем параметры фильтра
    $pCustom_Reports->setParams($campaign_in, $campaign_out, $date_start, $date_end, $span);

    // Получаем данные
    $arrResult =$pCustom_Reports->getCustom_Reports();

    if(is_array($arrResult)){
        foreach($arrResult as $key => $value){ 
	        $arrTmp[0] = str_replace(" ","&nbsp;",$value['details']);
            $arrTmp[1] = $value['total'];
	        $arrTmp[2] = $value['success'];
	        $arrTmp[3] = $value['unsuccessful'];
	        $arrTmp[4] = $value['dialing_time'];
	        $arrTmp[5] = $value['connection_time'];
	        $arrTmp[6] = $value['total_time'];
	        $arrTmp[7] = $value['max_time'];
	        $arrTmp[8] = $value['average_time'];
	        $arrTmp[9] = $value['cancel_call'];
            $arrData[] = $arrTmp;
        }
    }
    $oGrid->setData($arrData);

    //begin section filter
    $oFilterForm = new paloForm($smarty, createFieldFilter($pCustom_Reports->getCampaignIn(), $pCustom_Reports->getCampaignOut()));
    $smarty->assign("show", _tr("Show"));
    $htmlFilter  = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
    //end section filter

    $oGrid->showFilter(trim($htmlFilter));
    $content = $oGrid->fetchGrid();
    //end grid parameters

    return $content;
}

function createFieldFilter($campaign_in, $campaign_out){

    $arrCampaign_in = array('' => '('._tr('All').')');
    foreach ($campaign_in as $oCampaign_in) {
        $arrCampaign_in[$oCampaign_in['id']] = $oCampaign_in['name'];
    }

    $arrCampaign_out = array('' => '('._tr('All').')');
    foreach ($campaign_out as $oCampaign_out) {
        $arrCampaign_out[$oCampaign_out['id']] = $oCampaign_out['name'];
    }

    $arrSpan = array(
        ''      =>  '('._tr('All').')',
        'day'   =>  _tr('Day'),
        'hour'    =>  _tr('Hour'),
        'ring'   =>  _tr('Ring'),
    );

    $arrFormElements = array(
        "date_start" => array(
            "LABEL"                  => _tr("Date start"),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "DATE",
            "INPUT_EXTRA_PARAM"      => array("TIME" => true, "FORMAT" => "%d %b %Y %H:%M","TIMEFORMAT" => "24"),
            "VALIDATION_TYPE"        => "",
            "EDITABLE"               => "si",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
        "date_end"   => array(
            "LABEL"                  => _tr("Date end"),
            "REQUIRED"               => "yes",
            "INPUT_TYPE"             => "DATE",
            "INPUT_EXTRA_PARAM"      => array("TIME" => true, "FORMAT" => "%d %b %Y %H:%M","TIMEFORMAT" => "24"),
            "VALIDATION_TYPE"        => "",
            "EDITABLE"               => "si",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
        "queue_in" => array(
            "LABEL"                  => _tr("Campaign_in"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrCampaign_in,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
        "queue_out" => array(
            "LABEL"                  => _tr("Campaign_out"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrCampaign_out,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
        "span" => array(
            "LABEL"                  => _tr("Span"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrSpan,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""
        ),

                    );
    return $arrFormElements;
}


function getAction()
{
    if(getParameter("save_new")) //Get parameter by POST (submit)
        return "save_new";
    else if(getParameter("save_edit"))
        return "save_edit";
    else if(getParameter("delete")) 
        return "delete";
    else if(getParameter("new_open")) 
        return "view_form";
    else if(getParameter("action")=="view")      //Get parameter by GET (command pattern, links)
        return "view_form";
    else if(getParameter("action")=="view_edit")
        return "view_form";
    else
        return "report"; //cancel
}
?>