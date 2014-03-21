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
  $Id: Custom_Reports.class.php,v 1.1 2014-01-31 12:01:59 supme supmea@gmail.com Exp $ */
class Custom_Reports{
    var $_DB;
    var $errMsg;
    var $campaign_in;
    var $campaign_out;
    var $date_start;
    var $date_end;
    var $report;
    var $agent;
    var $ivr;
    var $CampaignWhere;
    var $AgentWhere;
    var $action;

    function Custom_Reports(&$pDB)
    {
        // Se recibe como parámetro una referencia a una conexión paloDB
        if (is_object($pDB)) {
            $this->_DB =& $pDB;
            $this->errMsg = $this->_DB->errMsg;
        } else {
            $dsn = (string)$pDB;
            $this->_DB = new paloDB($dsn);

            if (!$this->_DB->connStatus) {
                $this->errMsg = $this->_DB->errMsg;
                // debo llenar alguna variable de error
            } else {
                // debo llenar alguna variable de error
            }
        }
    }

    // Установка параметров фильтрации
    function setParams($campaign_in, $campaign_out, $date_start, $date_end, $report, $span, $agent, $ivr)
    {
        $this->campaign_in = $campaign_in;
        $this->campaign_out = $campaign_out;
        $this->date_start = date("Y-m-d H:i:s", strtotime($date_start));
        $this->date_end = date("Y-m-d H:i:s", strtotime($date_end));
        $this->report = $report;
        $this->span = $span;
        $this->agent = $agent;
        $this->ivr = $ivr;

        $this->CampaignWhere = $this->buildCampaignWhere();
        $this->AgentWhere = $this->buildAgentWhere();
    }

    function  getColumns_Reports()
    {
        $result = false;
        switch($this->report){
            case 'calls':
                switch($this->span)
                {
                    case "mon":
                    case "hour":
                    case "day":
                        $result = array("time","total","success","unsuccessful","dialing_time","connection_time","total_time","max_time","average_time","cancel_call");
                        $this->action = 'calls_span';
                        break;
                    case "ring":
                        $result = array("time","phone","status","duration_wait","duration","agent","campaign");
                        $this->action = 'calls_ring';
                        break;
                    default:
                        $result = array("time","total","success","unsuccessful","dialing_time","connection_time","total_time","max_time","average_time","cancel_call");
                        $this->action = 'calls_default';
                        break;
                }
                break;

            case 'oncalls':
                switch($this->span)
                {
                    case "mon":
                    case 'hour':
                    case 'day':
                        $result = array("time", "total","success","unsuccessful","unsuccess_more_5","success_less_20","sl","avg_wait_success","avg_wait_unsuccess");
                        $this->action = 'oncalls_span';
                        break;
                    default:
                        $result = array("time", "total","success","unsuccessful","unsuccess_more_5","success_less_20","sl","avg_wait_success","avg_wait_unsuccess");
                        $this->action = 'oncalls_default';
                        break;
                }
                break;

            case 'ivr':
                switch($this->span)
                {
                    case 'mon':
                    case 'hour':
                    case 'day':
                        $result = array("time", "'s'", "'i'", "'t'", "'*'", "'#'", "'0'", "'1'", "'2'", "'3'", "'4'", "'5'", "'6'", "'7'", "'8'", "'9'");
                        $this->action = 'ivr_span';
                        break;
                    case 'ring':
                        $result = array("ivr_name", "phone", "time", "ivr_time", "key");
                        $this->action = 'ivr_ring';
                        break;
                    default:
                        // Эта фишка с кавычками необходима для отображения символа "s", он режется транслэйтером эластикса
                        $result = array("time", "'s'", "'i'", "'t'", "'*'", "'#'", "'0'", "'1'", "'2'", "'3'", "'4'", "'5'", "'6'", "'7'", "'8'", "'9'");
                        $this->action = 'ivr_default';
                        break;
                }
                break;
        }

        return $result;
    }

    // Результаты фильтрации для отображения таблицы
    function getCustom_Reports()
    {
        $result = false;

        switch($this->action)
        {
            case "calls_span":
                $result = $this->getPeriodData();
                break;

            case "calls_ring":
                $this->date_start = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i",strtotime($this->date_start)).":00"));
                $this->date_end = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i",strtotime($this->date_end)).":00"));
                $result = $this->getRingData();
                break;

            case 'calls_default':
                $res = $this->getCallsData();
                if($res){
                    $res['time'] = str_replace(" ","&nbsp;",date("d.m.y H:i", strtotime($this->date_start))." - ".date("d.m.y H:i", strtotime($this->date_end)));
                    $result[0] = $res;
                }
                break;

            case 'oncalls_span':
                $result = $this->getPeriodData();
                break;

            case "oncalls_default":
                $res = $this->getOnCalls();
                $res['time'] = str_replace(" ","&nbsp;",date("d.m.y H:i", strtotime($this->date_start))." - ".date("d.m.y H:i", strtotime($this->date_end)));
                $result[0] = $res;
                break;

            case "ivr_span":
                $result = $this->getPeriodData();
                break;

            case "ivr_ring":
                $this->date_start = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i",strtotime($this->date_start)).":00"));
                $this->date_end = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i",strtotime($this->date_end)).":00"));
                $result = $this->getIvrDetail();
                break;

            case "ivr_default":
                $res = $this->getIvrData();
                $res['time'] = str_replace(" ","&nbsp;",date("d.m.y H:i", strtotime($this->date_start))." - ".date("d.m.y H:i", strtotime($this->date_end)));
                $result[0] = $res;
                break;
        }

        return $result;
    }

    // Детально по IVR
    function getIvrDetail(){
        $query = "SELECT ivr_name, phone, calldatetime, ivr_datetime, pressed_key FROM ivr_log WHERE (calldatetime BETWEEN ? AND ?)";
        $params = array($this->date_start, $this->date_end);
        if($this->ivr != ''){
            $query .= " AND ivr_id = ?";
            array_push($params, $this->ivr);
        }

        $res = $this->_DB->fetchTable($query,true, $params);

        $i = 0;
        foreach($res as $r){
            $result[$i]["ivr_name"] = $r['ivr_name'];
            $result[$i]["phone"] = $r['phone'];
            $result[$i]["time"] = str_replace(" ", "&nbsp;", date("d.m.y H:i:s",strtotime(date("Y-m-d H:i:s",strtotime($r['calldatetime'])))));
            $result[$i]["ivr_time"] = str_replace(" ", "&nbsp;", date("d.m.y H:i:s",strtotime(date("Y-m-d H:i:s",strtotime($r['ivr_datetime'])))));
            $result[$i]["key"] = $r['pressed_key'];
            $i++;
        }

        return $result;
    }

    // Отчет по звонкам с сервислевелом
    function getOnCalls()
    {
        if($this->campaign_in != 'all'){
            // Все звонки
            $query_in = "SELECT COUNT(*) FROM call_entry WHERE (datetime_entry_queue BETWEEN ? AND ?) AND id_campaign = ?";
            $params_in = array($this->date_start, $this->date_end, $this->campaign_in);
            // Среднее время ожидания удачных
            $query_in_success_avg = "SELECT AVG (duration_wait) FROM call_entry WHERE (datetime_entry_queue BETWEEN ? AND ?) AND id_campaign = ?  AND  status = 'terminada'";
            // Среднее время ожидания неудачных
            $query_in_unsuccess_avg = "SELECT AVG (duration_wait) FROM call_entry WHERE (datetime_entry_queue BETWEEN ? AND ?) AND id_campaign = ? AND  status <> 'terminada'";
        } else {
            $query_in = "SELECT COUNT(*) FROM call_entry  WHERE (datetime_entry_queue BETWEEN ? AND ?)";
            $params_in = array($this->date_start, $this->date_end);
            // Среднее время ожидания удачных
            $query_in_success_avg = "SELECT AVG (duration_wait) FROM call_entry WHERE (datetime_entry_queue BETWEEN ? AND ?) AND status = 'terminada'";
            // Среднее время ожидания неудачных
            $query_in_unsuccess_avg = "SELECT AVG (duration_wait) FROM call_entry WHERE (datetime_entry_queue BETWEEN ? AND ?) AND status <> 'terminada'";
        }
        // Удачные звонки
        $query_in_success = $query_in." AND status = 'terminada'";
        // Неудачные звонки
        $query_in_unsuccess = $query_in." AND status <> 'terminada'";
        // Неудачные с временем ожидания менее 5 сек
        $query_in_unsuccess_5 = $query_in_unsuccess." AND duration_wait < 5";
        // Удачные с временем ожидания более 20 сек
        $query_in_success_20 = $query_in_success." AND duration_wait > 20";


        if($this->campaign_out != 'all'){
            $query_out = "SELECT COUNT(*) FROM calls WHERE (fecha_llamada BETWEEN ? AND ?) AND id_campaign = ?";
            $params_out = array($this->date_start, $this->date_end, $this->campaign_out);
            // Среднее время ожидания удачных
            $query_out_success_avg = "SELECT AVG (duration_wait) FROM calls WHERE (fecha_llamada BETWEEN ? AND ?) AND id_campaign = ?  AND status IN ('Success', 'ShortCall')";
            // Среднее время ожидания неудачных
            $query_out_unsuccess_avg = "SELECT AVG (duration_wait) FROM calls WHERE (fecha_llamada BETWEEN ? AND ?) AND id_campaign = ? AND NOT status IN ('Success', 'ShortCall')";
        } else {
            $query_out = "SELECT COUNT(*) FROM calls  WHERE (fecha_llamada BETWEEN ? AND ?)";
            $params_out = array($this->date_start, $this->date_end);
            // Среднее время ожидания удачных
            $query_out_success_avg = "SELECT AVG (duration_wait) FROM calls WHERE (fecha_llamada BETWEEN ? AND ?) AND status IN ('Success', 'ShortCall')";
            // Среднее время ожидания неудачных
            $query_out_unsuccess_avg = "SELECT AVG (duration_wait) FROM calls WHERE (fecha_llamada BETWEEN ? AND ?) AND NOT status IN ('Success', 'ShortCall')";
        }
        // Удачные звонки
        $query_out_success = $query_out." AND status IN ('Success', 'ShortCall')";
        // Неудачные звонки
        $query_out_unsuccess = $query_out." AND NOT status IN ('Success', 'ShortCall')";
        // Неудачные с временем ожидания менее 5 сек
        $query_out_unsuccess_5 = $query_out_unsuccess." AND duration_wait < 5";
        // Удачные с временем ожидания более 20 сек
        $query_out_success_20 = $query_out_success." AND duration_wait > 20";

        $tmp_in = $this->_DB->getFirstRowQuery($query_in,false, $params_in);
        $tmp_out = $this->_DB->getFirstRowQuery($query_out,false, $params_out);
        $result["total"] = $tmp_in[0] + $tmp_out[0];

        $tmp_in = $this->_DB->getFirstRowQuery($query_in_success,false, $params_in);
        $tmp_out = $this->_DB->getFirstRowQuery($query_out_success,false, $params_out);
        $result["success"] = $tmp_in[0] + $tmp_out[0];

        $tmp_in = $this->_DB->getFirstRowQuery($query_in_unsuccess,false, $params_in);
        $tmp_out = $this->_DB->getFirstRowQuery($query_out_unsuccess,false, $params_out);
        $result["unsuccessful"] = $tmp_in[0] + $tmp_out[0];

        $tmp_in = $this->_DB->getFirstRowQuery($query_in_unsuccess_5,false, $params_in);
        $tmp_out = $this->_DB->getFirstRowQuery($query_out_unsuccess_5,false, $params_out);
        $result["unsuccess_more_5"] = $tmp_in[0] + $tmp_out[0];;

        $tmp_in = $this->_DB->getFirstRowQuery($query_in_success_20,false, $params_in);
        $tmp_out = $this->_DB->getFirstRowQuery($query_out_success_20,false, $params_out);
        $result["success_less_20"] = $tmp_in[0] + $tmp_out[0];;

        $result["sl"] = number_format(($result[0]["success_less_20"] / $result[0]["success"]) * 100, 2, '.', '');

        $tmp_in = $this->_DB->getFirstRowQuery($query_in_success_avg,false, $params_in);
        $tmp_out = $this->_DB->getFirstRowQuery($query_out_success_avg,false, $params_out);
        $result["avg_wait_success"] = $this->convertSec($tmp_in[0] + $tmp_out[0]);

        $tmp_in = $this->_DB->getFirstRowQuery($query_in_unsuccess_avg,false, $params_in);
        $tmp_out = $this->_DB->getFirstRowQuery($query_out_unsuccess_avg,false, $params_out);
        $result["avg_wait_unsuccess"] = $this->convertSec($tmp_in[0] + $tmp_out[0]);

        return $result;
    }

    // Отчет по звонкам с точностью до звонка
    function getRingData()
    {
        $query = "
        SELECT * FROM (
          SELECT
              callerid, datetime_entry_queue, duration_wait, duration, status, a.name as agentName, cp.name as campaignName
          FROM
              call_entry ce
            LEFT JOIN agent a ON ce.id_agent = a.id
            LEFT JOIN campaign_entry cp ON  ce.id_campaign = cp.id
          WHERE";
        $params = array();
        if ($this->agent or $this->campaign_in != 'all'){
            if ($this->agent){
                $query .= " id_agent = ? AND";
                array_push($params, $this->agent);
            }
            if ($this->campaign_in != 'all'){
                $query .= " id_campaign = ? AND";
                array_push($params, $this->campaign_in);
            }
        }

        $query .= " (datetime_entry_queue between ? AND ?) ";
        array_push($params, $this->date_start, $this->date_end);

        $query .= "

          UNION ALL

          SELECT phone, fecha_llamada, duration_wait, duration, status, a.name as agentName, cp.name as campaignName
          FROM
              calls cs
            LEFT JOIN agent a ON cs.id_agent = a.id
            LEFT JOIN campaign cp ON  cs.id_campaign = cp.id
          WHERE";

        if ($this->agent | $this->campaign_out != 'all'){
            if ($this->agent){
                $query .= " id_agent = ? AND";
                array_push($params, $this->agent);
            }

            if ($this->campaign_out != 'all'){
                $query .= " id_campaign = ? AND";
                array_push($params, $this->campaign_out);
            }
        }

        $query .= " (fecha_llamada between ? AND ?) ";
        array_push($params, $this->date_start, $this->date_end);

        $query .= "
        )q ORDER BY datetime_entry_queue ASC
        ";

        $res=$this->_DB->fetchTable($query, true, $params);

        $statuses = array(
            'abandonada'    =>  _tr('Abandoned'),
            'Abandoned'     =>  _tr('Abandoned'),
            'terminada'     =>  _tr('Success'),
            'Success'       =>  _tr('Success'),
            'fin-monitoreo' =>  _tr('End Monitor'),
            'Failure'       =>  _tr('Failure'),
            'NoAnswer'      =>  _tr('NoAnswer'),
            'OnQueue'       =>  _tr('OnQueue'),
            'Placing'       =>  _tr('Placing'),
            'Ringing'       =>  _tr('Ringing'),
            'ShortCall'     =>  _tr('ShortCall'),
        );
        $totalDurationWait = 0;
        $totalDuration = 0;
        foreach($res as $key=>$value){
            $arrTmp["time"] = str_replace(" ", "&nbsp;", date("d.m.y H:i:s",strtotime(date("Y-m-d H:i:s",strtotime($value["datetime_entry_queue"])))));
            $arrTmp["phone"] = $value["callerid"];
            $arrTmp["status"] = $statuses[$value["status"]]?$statuses[$value["status"]]:$value["status"];
            $totalDurationWait += $value["duration_wait"];
            $arrTmp["duration_wait"] = $this->convertSec($value["duration_wait"]);
            $totalDuration += $value["duration"];
            $arrTmp["duration"] = $this->convertSec($value["duration"]);
            $arrTmp["agent"] = $value["agentName"];
            $arrTmp["campaign"] = $value["campaignName"];
            $result[] = $arrTmp;
        }
        $result[] = array(
            "time" => "<b>"._tr("Total")."</b>",
            "duration_wait" => $this->convertSec($totalDurationWait),
            "duration" => $this->convertSec($totalDuration)
        );

        return $result;
    }

    // Отчет бьющий общий отчет на заданные периоды.Этакий хак с манипуляциями дат начала и конца периода.
    function getPeriodData()
    {
        switch($this->span)
        {
            case "hour":
                $this->date_start = date("Y-m-d H:i:s",strtotime(date("Y-m-d H",strtotime($this->date_start)).":00:00"));
                $this->date_end = date("Y-m-d H:i:s",strtotime(date("Y-m-d H",strtotime($this->date_end)).":00:00"));
                $format ="d.m.y H:i";
                break;

            case "day":
                $this->date_start = date("Y-m-d H:i:s",strtotime(date("Y-m-d",strtotime($this->date_start))." 00:00:00"));
                $this->date_end = date("Y-m-d H:i:s",strtotime(date("Y-m-d",strtotime($this->date_end))." 00:00:00"));
                $format = "d.m.y";
                break;

            case "mon":
                $this->date_start = date("Y-m-d H:i:s",strtotime(date("Y-m",strtotime($this->date_start))."-1 00:00:00"));
                $this->date_end = date("Y-m-d H:i:s",strtotime(date("Y-m",strtotime($this->date_end)).'-'.date("t",strtotime($this->date_end))." 23:59:59"));
                $format = "m.y";
                break;
        }

        $date_start = strtotime($this->date_start);
        $date_end = strtotime($this->date_end);

        $sum_start = $date_start;
        $sum_end = $date_end;

        $sum = array();

        while($date_start <= $date_end){
            $this->date_start = date("Y-m-d H:i:s",$date_start);

            switch($this->span)
            {
                case 'hour':
                    $this->date_end = date("Y-m-d H:i:s", ($date_start += 3600)-1);
                    break;
                case 'day':
                    $this->date_end = date("Y-m-d H:i:s", ($date_start += 86400)-1);
                    break;
                case 'mon';
                    $this->date_end = date("Y-m-d H:i:s", ($date_start += date('t',$date_start)*86400)-1);
                    break;
            }

            $this->date_end = date("Y-m-d H:i:s", $date_start-1);

            if ($this->report == 'calls') $res = $this->getCallsData();
            if ($this->report == 'oncalls') $res = $this->getOnCalls();
            if ($this->report == 'ivr') $res = $this->getIvrData();
            if ($res) {
                $res['time'] = str_replace(" ","&nbsp;",date($format, strtotime($this->date_start)));
                $result[] = $res;
            }
        }
        $this->date_start = date("Y-m-d H:i:s",$sum_start);
        $this->date_end = date("Y-m-d H:i:s", $sum_end);
//        if ($this->report == 'calls') $sum = $this->getCallsData();
//        if ($this->report == 'oncalls') $sum = $this->getOnCalls();
//        if ($this->report == 'ivr') $sum = $this->getIvrData();
        $sum['time'] = '<b>'._tr("Total").'</b>';
        $result[] = $sum;

        return $result;
    }

/*    function getPeriodData($period,$format)
    {
        $date_start = strtotime($this->date_start);
        $date_end = strtotime($this->date_end);

        $sum_start = $date_start;
        $sum_end = $date_end;

        $sum = array();

        while($date_start <= $date_end){
            $this->date_start = date("Y-m-d H:i:s",$date_start);
            $this->date_end = date("Y-m-d H:i:s", ($date_start += $period)-1);

            if ($this->report == 'calls') $res = $this->getCallsData();
            if ($this->report == 'oncalls') $res = $this->getOnCalls();
            if ($this->report == 'ivr') $res = $this->getIvrData();
            if ($res) {
                $res['time'] = str_replace(" ","&nbsp;",date($format, strtotime($this->date_start)));
                $result[] = $res;
            }
        }
        $this->date_start = date("Y-m-d H:i:s",$sum_start);
        $this->date_end = date("Y-m-d H:i:s", $sum_end);
        if ($this->report == 'calls') $sum = $this->getCallsData();
        if ($this->report == 'oncalls') $sum = $this->getOnCalls();
        if ($this->report == 'ivr') $sum = $this->getIvrData();
        $sum['time'] = '<b>'._tr("Total").'</b>';
        $result[] = $sum;

        return $result;
    }
*/
    // Отчет бьющий общий отчет на месяца
    function getMounthData(){
        date("t", strtotime("10 February 2004")); // количество дней в месяце
    }


    // Общий отчет по IVR за период
    function getIvrData(){
        $pads = array('s', 'i', 't', '*', '#', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        foreach($pads as $pad){
            $query = "SELECT COUNT(*) FROM ivr_log WHERE (calldatetime BETWEEN ? AND ?) AND pressed_key = ?";
            $params = array($this->date_start, $this->date_end, $pad);
            if($this->ivr != ''){
                $query .= " AND ivr_id = ?";
                array_push($params, $this->ivr);
            }
            $res = $this->_DB->getFirstRowQuery($query,false, $params);
            // Эта фишка с кавычками необходима для отображения символа "s", он режется транслэйтером эластикса
            $result["'$pad'"] = $res[0];
        }
        return $result;
    }

    // Общий отчет по звонкам за период
    function getCallsData()
    {
        $res = false;
        $result = false;

        // Всего звонков по компаниям за период
        $tmp = $this->getCountStatus('uniqueid',true);
        if($value['total'] = $tmp==0?false:$tmp) $res = true;

        // Удачных звонков по компаниям за период
        $tmp = $this->getCountStatus('Hangup')+$this->getCountStatus('ShortCall');
        if($value['success'] = $tmp==0?false:$tmp) $res = true;

        /*
         * ToDo Тут хрень, это может быть только при исходящей компании?
         * ToDo Но судя по имеющимся данным бывают исключения
         */
        // Неудачных звонков по компаниям за период
        $tmp = $this->getCountStatus('Failure');
        if($value['unsuccessful'] = $tmp==0?false:$tmp) $res = true;

        // Время набора номера по компаниям за период
        if($value['dialing_time'] = $this->getSumTime('Dialing','Success')) $res = true;

        // Время разговора по компаниям за период
        if($value['connection_time'] = $this->getSumTime('Success','Hangup')) $res = true;

        // Общее время по компаниям за период
        if($value['total_time'] = $this->getSumTime('OnQueue', 'Hangup')) $res = true;

        // Максимальное время по компаниям за период
        if($value['max_time'] = $this->getSumTime('Success','Hangup','MAX')) $res = true;

        // Среднее время по компаниям за период
        if($value['average_time'] = $this->getSumTime('Success','Hangup','AVG')) $res = true;

        // Сброшеных по компаниям за период
        if($value['cancel_call'] = $this->getCountStatus("Abandoned")) $res = true;

        if($res) $result = $value;

        return $result;
    }

    // Вспомогательная функция считающая статусы за период
    function getCountStatus($status, $unique=false)
    {
        if(isset($this->date_start) & $this->date_start != "" & isset($this->date_end) & $this->date_end != ""){


            $where = "";

            $where .= $this->CampaignWhere['where'];
            $where .= "AND datetime_entry between ? AND ?";

            if ($this->CampaignWhere['params'] != null) {
                $params = $this->CampaignWhere['params'];
                array_push($params, $this->date_start, $this->date_end);
            }
            else
                $params = array($this->date_start, $this->date_end);

        }

        if($this->AgentWhere['where'] != ''){
            $where .= $this->AgentWhere['where'];
            array_push($params, $this->AgentWhere['params']);
        }

        if($unique){
            $query = "SELECT COUNT(DISTINCT $status) FROM call_progress_log WHERE $where";
        } else {
            $where .= ' AND new_status = ?';
            array_push($params, $status);
            $query = "SELECT COUNT(*) FROM call_progress_log WHERE $where";
        }

        $res=$this->_DB->getFirstRowQuery($query, false, $params);
        return $res[0]!=0?$res[0]:"";

    }

    // Сумма колличества времени или максимальное (MAX) или среднее (AVG) между статусами за период
    function getSumTime($fromStatus, $toStatus, $action = 'SUM')
    {
        if($action != 'MAX' && $action != 'max' && $action != 'AVG' && $action != 'avg') $action = 'SUM';

        if(isset($this->date_start) & $this->date_start != "" & isset($this->date_end) & $this->date_end != ""){

            $dateWhere = "datetime_entry between ? AND ?";
            $params = array($this->date_start, $this->date_end, $this->date_start, $this->date_end);

            if($this->CampaignWhere['params'] != null){
                foreach($this->CampaignWhere['params'] as $value){
                    $params[] = $value;
                }
            }

            array_push($params, $this->date_start, $this->date_end);
        }

        if($this->AgentWhere['where'] != ''){
            array_push($params, $this->AgentWhere['params']);
        }

        $query = "
                        SELECT $action(timestampdiff(
                            SECOND , q.fromStatusTime, q.toStatusTime))
                        FROM (
                            SELECT q1.callId, (
                                SELECT datetime_entry
                                FROM call_progress_log pl1
                                WHERE coalesce(concat('in', cast(pl1.id_call_incoming AS char(10))), concat('out', cast(pl1.id_call_outgoing AS char(10)))) = q1.callId AND $dateWhere
                                AND pl1.new_status = '$fromStatus'
                            ) AS fromStatusTime, (
                                SELECT datetime_entry
                                FROM call_progress_log pl1
                                WHERE coalesce(concat('in', cast(pl1.id_call_incoming AS char(10))), concat('out', cast(pl1.id_call_outgoing AS char(10)))) = q1.callId  AND $dateWhere
                                AND pl1.new_status = '$toStatus'
                            ) AS toStatusTime
                            FROM (
                                SELECT DISTINCT coalesce(concat('in', cast(id_call_incoming AS char(10))) , concat('out', cast(id_call_outgoing AS char(10)))) AS callId
                                FROM call_progress_log
                                WHERE ".$this->CampaignWhere['where']." AND $dateWhere ".$this->AgentWhere['where']."
                            ) q1
                        ) q
                        WHERE NOT q.toStatusTime IS NULL
        ";

        $res=$this->_DB->getFirstRowQuery($query, false, $params);
        return $this->convertSec($res[0]);

    }

    // Билдер where по компаниям sql запросов для некоторых функций отчетов по звонкам, который я вынес, чтобы не повторять одно и тоже.
    function buildCampaignWhere()
    {
        $params = array();

        if($this->campaign_in != "all")
        {
            $where = '(id_campaign_incoming = ? ';
            $params[] = $this->campaign_in;
        }
        else
        {
            $where = '(NOT id_campaign_incoming IS NULL';
        }

        if($this->campaign_out != "all")
        {
            $where .= ' OR id_campaign_outgoing = ?) ';
            $params[] = $this->campaign_out;
        }
        else
        {
            $where .= ' OR NOT id_campaign_outgoing IS NULL) ';
        }

        return array('where' => $where, "params" => $params);

    }

    // Так же как по компаниям, билдер по агентам
    function buildAgentWhere()
    {
        if($this->agent){
            $where = " AND id_agent = ? ";
            $params = $this->agent;
            return array('where' => $where, "params" => $params);
        } else return array('where' => '');
    }


    // Список входящих компаний
    function getCampaignIn()
    {
        $query   = "SELECT id, name FROM campaign_entry";
        $result=$this->_DB->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }

        return $result;
    }

    // Список исходящих компаний
    function getCampaignOut()
    {
        $query   = "SELECT id, name FROM campaign";
        $result=$this->_DB->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }

        return $result;
    }

    // Список агентов
    function getAgents()
    {
        $query   = "SELECT id, name FROM agent";
        $result=$this->_DB->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }

        return $result;
    }

    // Список IVR с данными
    function getIvrs()
    {
        $query   = "SELECT DISTINCT ivr_id, ivr_name FROM ivr_log";
        $result=$this->_DB->fetchTable($query, true);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }

        return $result;
    }

    // Перевод секунд в человеческий вид
    function convertSec($sec)
    {
        if ($sec <= 0)
            return false;
        else
          return str_pad((int)($sec/3600),2,'0',STR_PAD_LEFT)._tr("h").str_pad(($sec/60 % 60),2,'0',STR_PAD_LEFT)._tr("m"). str_pad(($sec%60),2,'0',STR_PAD_LEFT)._tr("s");
    }
}
