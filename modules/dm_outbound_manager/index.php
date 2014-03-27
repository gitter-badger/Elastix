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
    include_once "modules/$module_name/libs/Outbound_Manager.class.php";

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

    // путь к шаблону
    $templates_dir=(isset($arrConf['templates_dir']))?$arrConf['templates_dir']:'themes';
    $local_templates_dir="$base_dir/modules/$module_name/".$templates_dir.'/'.$arrConf['theme'];

    // конект к базе
    $pDB = new paloDB($arrConf['dsn_conn_database']);

    return reportOutbound_Manager($smarty, $module_name, $local_templates_dir, $pDB);
}

function reportOutbound_Manager($smarty, $module_name, $local_templates_dir, &$pDB)
{
    $OutboundManager = new OutboundManager($pDB);

    //Параметры для грида
    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->pagingShow(false); // не показывать пагинатор.

    //begin данные для фильтра
    $oFilterForm = new paloForm($smarty, createFieldFilter($OutboundManager->getCampaign()));
    $smarty->assign('show', _tr('Show'));
    $htmlFilter  = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
    //end данные для фильтра

    $oGrid->showFilter(trim($htmlFilter));

    //Добавляем в урл страницы дополнительные параметры
    $params = array(
        "menu"      => $module_name,
        "campaign"  => getParameter("campaign"),
    );

    // Передаем параметры фильтру
    $OutboundManager->setParams($params["campaign"]);

    //Столбцы для отображения в гриде
    $Columns = $OutboundManager->getColumns();
    foreach($Columns as $column){
        $arrColumns[] = _tr($column);
    }

    // Получаем данные
    $arrResult =$OutboundManager->getList();
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

    $oGrid->setURL($params);
    $oGrid->setData($arrData);
    $oGrid->setColumns($arrColumns);
    $oGrid->setTitle(_tr("Outbound Manager"));

    return $oGrid->fetchGrid();
}

function createFieldFilter($campaigns){

    foreach ($campaigns as $campaign) {
        $arrCampaign[$campaign['campaign_id']] = $campaign['campaign_name'];
    }

    $arrFormElements = array(
        "report" => array(
            "LABEL"                  => _tr("Campaign"),
            "REQUIRED"               => "no",
            "INPUT_TYPE"             => "SELECT",
            "INPUT_EXTRA_PARAM"      => $arrCampaign,
            "VALIDATION_TYPE"        => "text",
            "VALIDATION_EXTRA_PARAM" => ""
        ),
    );
    return $arrFormElements;
}

?>