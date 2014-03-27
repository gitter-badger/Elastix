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
    var $agent;
    var $CampaignWhere;
    var $AgentWhere;

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

    // Установка параметров фильтрации
    function setParams($campaign_in, $campaign_out, $date_start, $date_end, $span, $agent)
    {
        $this->campaign_in = $campaign_in;
        $this->campaign_out = $campaign_out;
        $this->date_start = date("Y-m-d H:i:s", strtotime($date_start));
        $this->date_end = date("Y-m-d H:i:s", strtotime($date_end));
        $this->span = $span;
        $this->agent = $agent;

        $this->CampaignWhere = $this->buildCampaignWhere();
        $this->AgentWhere = $this->buildAgentWhere();
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

            case "ring":

                break;

            default:
                $res = $this->getRowData();
                if($res){
                    $res['details'] = date("d.m.y H:i", strtotime($this->date_start))." - ".date("d.m.y H:i", strtotime($this->date_end));
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

        $sum = array();

        while($date_start <= $date_end){
            $this->date_start = date("Y-m-d H:i:s",$date_start);
            $this->date_end = date("Y-m-d H:i:s", ($date_start += $period)-1);
            $res = $this->getRowData();
            if ($res) {
                $res['details'] = date($format, strtotime($this->date_start));
                $result[] = $res;
            }
            // ToDo Тут, наверное, как то красивее можно плюсованием заниматься
            // ToDo а то и вообще из базы взять
            $sum['total'] += $res['total'];
            $sum['success'] += $res['success'];
            $sum['unsuccessful'] += $res['unsuccessful'];
            $sum['dialing_time'] += $res['dialing_time'];
            $sum['connection_time'] += $res['connection_time'];
            $sum['total_time'] += $res['total_time'];
            $sum['max_time'] += $res['max_time'];
            $sum['average_time'] += $res['average_time'];
            $sum['cancel_call'] += $res['cancel_call'];
        }
        $sum['total'] = $sum['total']==0?'':$sum['total'];
        $sum['success'] = $sum['success']==0?'':$sum['success'];
        $sum['unsuccessful'] = $sum['unsuccessful']==0?'':$sum['unsuccessful'];
        $sum['dialing_time'] = $sum['dialing_time']==0?'':$sum['dialing_time'];
        $sum['connection_time'] = $sum['connection_time']==0?'':$sum['connection_time'];
        $sum['total_time'] = $sum['total_time']==0?'':$sum['total_time'];
        $sum['max_time'] = $sum['max_time']==0?'':$sum['max_time'];
        $sum['average_time'] = $sum['average_time']==0?'':$sum['average_time'];
        $sum['cancel_call'] = $sum['cancel_call']==0?'':$sum['cancel_call'];
        $sum['details'] = '<strong>Total</strong>';
        $result[] = $sum;
        return $result;
    }

    function getRowData()
    {
        $res = false;
        $result = false;

        // Всего звонков по компаниям за период
        //$tmp = $this->getCountStatus('id_call_outgoing',true)+$this->getCountStatus('id_call_incoming',true);
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
        return $res[0];

    }

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

    //Список агентов
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
}

//echo $query.'<br/>'; print_r($params); echo '<hr/>';