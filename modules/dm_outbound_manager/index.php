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

function _moduleContent($smarty, $module_name)
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

function reportOutbound_Manager($smarty, $module_name, $local_templates_dir, $pDB)
{

    $action = getParameter('action');
    $id_call = (int)getParameter('id_call');
    if (!in_array($action, array('', 'loadform', 'saveform', 'deleteform')))
        $action = '';
    switch ($action) {
        case 'loadform':
            $content = loadForm($smarty, $id_call, $local_templates_dir, $pDB);
            break;

        case 'saveform':
            $content = saveForm($smarty, $module_name, $local_templates_dir, $pDB, $id_call);
            break;

        case 'deleteform':
            $content = deleteForm($smarty, $module_name, $local_templates_dir, $pDB);
            break;

        default:
            $content = listTasks($smarty, $module_name, $local_templates_dir, $pDB);
            break;
        }

    return $content;
}

function deleteForm($smarty, $module_name, $local_templates_dir, $pDB){
    $OutboundManager = new OutboundManager($pDB);
    $OutboundManager->setParams(getParameter("campaign"));

    $request = getParameter('id_call');
    if(!is_array($request)) $id_call[0] = $request;
    else $id_call = $request;

    $OutboundManager->deleteData($id_call);

    return listTasks($smarty, $module_name, $local_templates_dir, $pDB);
}

function saveForm($smarty, $module_name, $local_templates_dir, $pDB, $id_call)
{
    $OutboundManager = new OutboundManager($pDB);
    $OutboundManager->setParams(getParameter("campaign"));
    $fields = $OutboundManager->getData(0);
    $data = array();
    foreach($fields as $key => $field){
        $data[$field['columna']] = getParameter($field['columna']);
    }

    $OutboundManager->setData($id_call, $data);

    return listTasks($smarty, $module_name, $local_templates_dir, $pDB);
}

function loadForm($smarty, $id_call, $local_templates_dir, $pDB)
{
    $OutboundManager = new OutboundManager($pDB);
    $data = $OutboundManager->getData($id_call);

    $smarty->assign(array(
        'Id_call'         =>  $id_call,
        'FORMS'      =>  $data,
        'BTN_save'   =>  _tr('Save data'),
    ));


    return $smarty->fetch("$local_templates_dir/form.tpl");
}

// Основная функция (вывод фильтра, список звонков по фильтру)
function listTasks($smarty, $module_name, $local_templates_dir, $pDB)
{
    $OutboundManager = new OutboundManager($pDB);

    //Параметры для грида
    $oGrid  = new paloSantoGrid($smarty);
    $oGrid->pagingShow(false); // не показывать пагинатор.

    // Ловим переданные параметры
    $params = array(
        "menu"      => $module_name,
        "campaign"  => getParameter('campaign'),
    );

    if(isset($params['campaign'])){
        $smarty->assign('Selected', '1');
    } else {
        $smarty->assign('Selected', '0');
    }

    //begin данные для фильтра
    $oFilterForm = new paloForm($smarty, createFieldFilter($OutboundManager->getCampaign()));
    $smarty->assign('Show', _tr('Show'));
    $smarty->assign('AddForm', _tr('AddForm'));
    $smarty->assign('Delete', _tr('Delete'));
    $smarty->assign('check_all', _tr('check all'));
    $smarty->assign('uncheck_all', _tr('uncheck all'));
    $htmlFilter  = $oFilterForm->fetchForm("$local_templates_dir/filter.tpl","",$_POST);
    //end данные для фильтра

    $oGrid->showFilter(trim($htmlFilter));

    // Передаем параметры фильтру
    $OutboundManager->setParams($params['campaign'], $module_name);


    //Столбцы для отображения в гриде
    $Columns = $OutboundManager->getColumns();
    foreach($Columns as $column){
        $arrColumns[] = _tr($column[0]);
    }

    // Получаем данные
    $arrResult = $OutboundManager->getList();
    $arrData = false;
    if(is_array($arrResult)){
        foreach($arrResult as $key => $value){
            $i=0;
            foreach($Columns as $column){
                $arrTmp[$i] =  $value[$column[0]];
                $i++;
            }
            $arrData[] = $arrTmp;
        }
    }

    //$oGrid->setURL($params);
    $oGrid->setData($arrData);
    $oGrid->setColumns($arrColumns);
    $oGrid->setTitle(_tr("Outbound Manager"));

    return $oGrid->fetchGrid();
}

function createFieldFilter($campaigns){
echo '<pre>';
    foreach ($campaigns as $campaign) {
        $arrCampaign[$campaign[0]] = $campaign[1];
    }
echo '</pre>';
    $arrFormElements = array(
        "campaign" => array(
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