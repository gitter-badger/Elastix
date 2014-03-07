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
    include_once "modules/$module_name/libs/Custom_Reports.class.php";
    include_once "modules/$module_name/libs/Excel_Xml.php";

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

/*    //actions
    $action = getAction();
    $content = "";

    switch($action){
        default:
            $content = reportCustom_Reports($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
            break;
    }
    return $content;
*/
    return reportCustom_Reports($smarty, $module_name, $local_templates_dir, $pDB, $arrConf);
}

function reportCustom_Reports($smarty, $module_name, $local_templates_dir, &$pDB, $arrConf)
{
    $pCustom_Reports = new Custom_Reports($pDB);

    //Получаем присланные параметры нужные нам
    $campaign_in = getParameter("queue_in");
    $campaign_out = getParameter("queue_out");
    $date_start = getParameter("date_start");
    $date_end = getParameter("date_end");
    $span = getParameter("span");
    $agent = getParameter("agent");

    //Параметры для грида
    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->pagingShow(false); // не показывать пагинатор.
    $oGrid->enableExport();    // включить экспорт результатов.

    //begin данные для фильтра
    $oFilterForm = new paloForm($smarty, createFieldFilter($pCustom_Reports->getCampaignIn(), $pCustom_Reports->getCampaignOut(), $pCustom_Reports->getAgents()));
    $smarty->assign("show", _tr("Show"));
    $htmlFilter  = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
    //end данные для фильтра

    $oGrid->showFilter(trim($htmlFilter));

    //    $isExport = $oGrid->isExportAction();

    //Добавляем в урл страницы дополнительные параметры
    $url = array(
        "menu"      => $module_name,
        "queue_in"  => $campaign_in,
        "queue_out" => $campaign_out,
        "date_start"=> $date_start,
        "date_end"  => $date_end,
        "span"      => $span,
        "agent"     => $agent
    );

    // Передаем параметры фильтра
    $pCustom_Reports->setParams($campaign_in, $campaign_out, $date_start, $date_end, $span, $agent);

    //Столбцы для отображения в гриде
    $Columns = $pCustom_Reports->getColumns_Reports();
    foreach($Columns as $column){
        $arrColumns[] = _tr($column);
    }

    // Получаем данные
    $arrResult =$pCustom_Reports->getCustom_Reports();
    if(is_array($arrResult)){
        foreach($arrResult as $key => $value){
            $i=0;
            foreach($Columns as $column){
                $arrTmp[$i] =  $value[$column];
                $i++;
            }
            $arrData[] = $arrTmp;
        }
    }

    $oGrid->setURL($url);
    $oGrid->setData($arrData);
    $oGrid->setColumns($arrColumns);
    $oGrid->setTitle(_tr("Custom Reports"));
    $oGrid->setNameFile_Export(_tr("report"));

    // Так как с штатным экспортом отчетов проблема, перехватываем стандартный экспорт и делаем свой...
    // ToDo Когда наладят можно убрать
    $file = _tr("report").'_'.date("d-m-Y_H:i:s", time());
    switch($oGrid->exportType()){
        case "csv":
            exportCSV($file, $arrColumns, $arrData);
            break;

        case "xls":
            exportXLS($file, $arrColumns, $arrData);
            break;

        default:
            return $oGrid->fetchGrid();
            break;
    }
}

function createFieldFilter($campaign_in, $campaign_out, $agents){

    $arrCampaign_in = array('0' => '('._tr('No').')', 'all' => '('._tr('All').')');
    foreach ($campaign_in as $oCampaign_in) {
        $arrCampaign_in[$oCampaign_in['id']] = $oCampaign_in['name'];
    }

    $arrCampaign_out = array('0' => '('._tr('No').')', 'all' => '('._tr('All').')');
    foreach ($campaign_out as $oCampaign_out) {
        $arrCampaign_out[$oCampaign_out['id']] = $oCampaign_out['name'];
    }

    $arrSpan = array(
        ''        =>  '('._tr('All').')',
        'day'     =>  _tr('Day'),
        'hour'    =>  _tr('Hour'),
        'ring'    =>  _tr('Ring'),
        'oncalls' =>  _tr('onCalls'),
    );

    $arrAgents = array('' => '('._tr('All').')');
    foreach ($agents as $agent) {
        $arrAgents[$agent['id']] = $agent['name'];
    }

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
        "agent" => array(
            "LABEL"                  => _tr("Agent"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrAgents,
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
// CSV экспорт --------------------------------------------------------
function exportCSV($file, $arrColumns, $arrData)
{
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=".$file.".csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    $outstream = fopen("php://output", "w");
    fputcsv($outstream, exportConvert($arrColumns), ';');
    foreach($arrData as $data){
        $data = exportConvert($data);
        fputcsv($outstream, $data,';');
    }
    fclose($outstream);
}

function exportConvert($data, $conv = false)
{
    foreach($data as $index => $val){
        $search  = array("<b>",  "</b>", "&nbsp;");
        $replace = array("",     "",      " ");
        $val = str_replace($search, $replace, $val);
        $data[$index] = $conv?$val:iconv("UTF8", "CP1251", $val);
    }
    return $data;
}

//XSL экспорт
function exportXLS($file, $arrColumns, $arrData)
{
    $phpexcel = new Excel_Xml;
    array_unshift($arrData, $arrColumns);
    $phpexcel->addWorksheet(_tr('report'), exportConvert($arrData, true));
    $phpexcel->sendWorkbook($file.'.xls');
}

?>