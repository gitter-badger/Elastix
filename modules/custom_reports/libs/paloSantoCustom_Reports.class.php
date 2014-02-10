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
  $Id: paloSantoCustom_Reports.class.php,v 1.1 2014-01-31 12:01:59 supme supmea@gmail.com Exp $ */
class paloSantoCustom_Reports{
    var $_DB;
    var $errMsg;
    var $campaign_in;
    var $campaign_out;
    var $date_start;
    var $date_end;
    var $span;

    function paloSantoCustom_Reports(&$pDB)
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

    // Установка параметров фильтрации и билдер веры
    function setParams($campaign_in, $campaign_out, $date_start, $date_end, $span)
    {
        $this->campaign_in = $campaign_in;
        $this->campaign_out = $campaign_out;
        $this->date_start = date("Y-m-d H:i:s", strtotime($date_start));
        $this->date_end = date("Y-m-d H:i:s", strtotime($date_end));
        $this->span = $span;
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

    // Сумма колличества времени или максимальое (MAX) или среднее (AVG) между статусами за период
    function getSumTime($fromStatus, $toStatus, $action = 'SUM')
    {
        if($action != 'MAX' && $action != 'max' && $action != 'AVG' && $action != 'avg') $action = 'SUM';

        if(isset($this->date_start) & $this->date_start != "" & isset($this->date_end) & $this->date_end != ""){

            $dateWhere = "datetime_entry between ? AND ?";
            $params = array($this->date_start, $this->date_end, $this->date_start, $this->date_end);
            $campaignWhere = "";

            $campaignWhere_tmp = $this->buildCampaignWhere();
            $campaignWhere .= $campaignWhere_tmp['where'];
            foreach($campaignWhere_tmp['params'] as $value){
                $params[] = $value;
            }

            array_push($params, $this->date_start, $this->date_end);
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
                                WHERE $campaignWhere AND $dateWhere
                            ) q1
                        ) q
                        WHERE NOT q.toStatusTime IS NULL
";

        $res=$this->_DB->getFirstRowQuery($query, false, $params);
        return $res[0];

    }

    function getCountStatus($status)
    {
        if(isset($this->date_start) & $this->date_start != "" & isset($this->date_end) & $this->date_end != ""){


            $where = "";

            $campaignWhere_tmp = $this->buildCampaignWhere();
            $where .= $campaignWhere_tmp['where'];
            $where .= "AND datetime_entry between ? AND ?";

            if ($campaignWhere_tmp['params'] != null) {
                $params = $campaignWhere_tmp['params'];
                array_push($params, $this->date_start, $this->date_end, $status);
            }
            else
                $params = array($this->date_start, $this->date_end, $status);

        }

        $where .= ' AND new_status = ?';

        $query = "SELECT COUNT(*) FROM call_progress_log WHERE $where";
/*
        echo "<pre>$query";
        print_r($params);
        echo "</pre><hr/>";
*/
        $res=$this->_DB->getFirstRowQuery($query, false, $params);
        return $res[0];

    }

    // Результаты фильтрации для отображения таблицы
    function getCustom_Reports()
    {
        $result = array();

        switch($this->span)
        {
            case "hour":
                $this->date_start = date("Y-m-d H:i:s",strtotime(date("Y-m-d H",strtotime($this->date_start)).":00:00"));
                $this->date_end = date("Y-m-d H:i:s",strtotime(date("Y-m-d H",strtotime($this->date_end)).":00:00"));
                return $this->getPeriodData(3600,"d.m.y H:i");
                break;

            case "day":
                $this->date_start = date("Y-m-d H:i:s",strtotime(date("Y-m-d",strtotime($this->date_start))."00:00:00"));
                $this->date_end = date("Y-m-d H:i:s",strtotime(date("Y-m-d",strtotime($this->date_end))." 00:00:00"));
                return $this->getPeriodData(86400,"d.m.y");
                break;

            default:
                $res = $this->getRowData();
                if($res){
                    $res['details'] = date("d.m.y H:i", strtotime($this->date_start))." - ".date("d/m/y H:i", strtotime($this->date_end));
                    $result[0] = $res;
                }
                break;
        }

        return $result;
    }

    function getPeriodData($period,$format)
    {
        $date_start = strtotime($this->date_start);
        $date_end = strtotime($this->date_end);

        while($date_start <= $date_end){
            $this->date_start = date("Y-m-d H:i:s",$date_start);
            $this->date_end = date("Y-m-d H:i:s", ($date_start += $period)-1);
            $res = $this->getRowData();
            if ($res) {
                $res['details'] = date($format, strtotime($this->date_start));
                $result[] = $res;
            }
        }

        return $result;
    }

    function getRowData()
    {
        $res = false;
        $result = false;

        // Всего звонков по компаниям за период
        if($value['total'] = $this->getCountStatus('onQueue')) $res = true;

        // Удачных звонков по компаниям за период
        if($value['success'] = $this->getCountStatus('Hangup')) $res = true;

        /*
         * ToDo Тут хрень, это может быть только при исходящей компании?
         * ToDo Но судя по имеющимся данным бывают исключения
         */
        // Неудачных звонков по компаниям за период
        if($value['unsuccessful'] = $this->getCountStatus('Abandoned')) $res = true;

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
    function buildCampaignWhere()
    {
        $where ='';
        if((isset($this->campaign_in) & $this->campaign_in != "") || (isset($this->campaign_out) & $this->campaign_out != ""))
        {
            if(isset($this->campaign_in) & $this->campaign_in != ""){
                if(isset($this->campaign_out) & $this->campaign_out != ""){
                    $where .= "(id_campaign_incoming = ? ";
                } else {
                    $where .= "id_campaign_incoming = ? ";
                }
                $params[] = $this->campaign_in;
            }

            if(isset($this->campaign_out) & $this->campaign_out != ""){
                if(isset($this->campaign_in) & $this->campaign_in != ""){
                    $where .= "OR id_campaign_outgoing = ?) ";
                } else {
                    $where .= "id_campaign_outgoing = ? ";
                }
                $params[] = $this->campaign_out;
            }
        } else {
            $where .= " (NOT id_campaign_incoming IS NULL OR NOT id_campaign_outgoing IS NULL) ";
            $params = null;
        }

        return array('where' => $where, "params" => $params);

    }

/*
 * Удалить потом, это для шаблона
 *
    function getCustom_ReportsById($id)
    {
        $query = "SELECT * FROM table WHERE id=?";

        $result=$this->_DB->getFirstRowQuery($query, true, array("$id"));

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return null;
        }
        return $result;
    }
*/
}
