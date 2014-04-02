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
class OutboundManager{
    var $_DB;
    var $errMsg;
    var $campaign = false;
    var $action;
    var $module_name;

    function OutboundManager(&$pDB)
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

    // Принимаем параметры
    function setParams($campaign, $module_name){
        $this->campaign = $campaign;
        $this->module_name = $module_name;
    }

    // Список компаний
    function getCampaign(){

        $query   = "SELECT id, name FROM campaign";
        $result=$this->_DB->fetchTable($query);

        if($result==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }

        return $result;
    }

    // Список колонок для вывода
    function getColumns(){

        $result = false;

        if($this->campaign) {
            $query = "SELECT DISTINCT columna FROM call_attribute";
            $result=$this->_DB->fetchTable($query);

            if($result==FALSE){
                $this->errMsg = $this->_DB->errMsg;
                return array();
            }
            $result[] = array('Action');
        }



        return $result;
    }

    // Список для вывода в грид
    function getList(){

        $query = 'SELECT
                          ca.id_call
                          ,ca.columna
                          ,ca.value
                  FROM calls c
                  INNER JOIN call_attribute ca on ca.id_call = c.id
                  WHERE c.status is NULL and c.id_campaign = ?';

        $calls = $this->_DB->fetchTable($query,true,array($this->campaign));

        if($calls==FALSE){
            $this->errMsg = $this->_DB->errMsg;
            return array();
        }

        $action =  "<img src='modules/$this->module_name/images/edit.png' alt='"._tr('edit')."' onclick='loadForm(?)'/>
                    <img src='modules/$this->module_name/images/delete.png' alt='"._tr('delete')."'onclick='deleteForm(?)'/>
                    <input id='id_call' type='checkbox' name='id_call[]' value='?'/>";
        /*$action =  "<input class='button' style='width: 20px; height: 20px' onclick='loadForm(?)' value=' E '/>
                    <input class='button' style='width: 20px; height: 20px' onclick='deleteForm(?)' value=' X '/>
                    <input id='id_call' type='checkbox' name='id_call[]' value='?'/>";*/
        foreach($calls as $key=>$call){
            $result[$call['id_call']]['Action'] = str_replace('?', $call['id_call'], $action);
            $result[$call['id_call']][$call['columna']] = $call['value'];
        }

        return $result;
    }

    function getData($id)
    {
        if($id == 0){
            $query = "SELECT DISTINCT columna FROM call_attribute";
            $data=$this->_DB->fetchTable($query, true);

            if(count($data) == 0){
                $result[0]['columna'] = 'Number';
                $result[0]['value'] = '';
                $result[1]['columna'] = 'Name';
                $result[1]['value'] = '';
            } else {
                $i = 0;
                foreach($data as $dat){
                    $result[$i]['columna'] = $dat['columna'];
                    $result[$i]['value'] = '';
                    $i++;
                }
            }

        } else {
            $query = "SELECT columna, value FROM call_attribute WHERE id_call = ?";
            $result=$this->_DB->fetchTable($query, true, array($id));
        }

        return $result;
    }

    function setData($id_call, $data){
        foreach($data as $key => $value){
            $query = "SELECT COUNT(*) FROM call_attribute WHERE  id_call = ? AND columna = ?";
            $count = $this->_DB->getFirstRowQuery($query,false,array($id_call, $key));

            if($count[0] <> 0){
                $query = "UPDATE call_attribute SET value = ? WHERE id_call = ? AND columna = ?";
                $this->_DB->getFirstRowQuery($query,false, array($value, $id_call, $key));
                if($key == 'Number') {
                    $query = 'UPDATE calls SET phone = ? WHERE id = ?';
                    $this->_DB->getFirstRowQuery($query,false, array($value, $id_call));
                }

            } else {

                /* Нужен ли где этот column_number ?
                $query = "SELECT COUNT(*) FROM call_attribute WHERE  id_call = ?";
                $number = $this->_DB->getFirstRowQuery($query,false,array($id_call));
                */
                if($key == 'Number') {
                    $query = 'SELECT COUNT(*) FROM dont_call WHERE caller_id = ? AND status = ?';
                    $result = $this->_DB->getFirstRowQuery($query, false, array($id_call, 'A'));
                    $dnc = ($result[0] != 0) ? 1 : 0;

                    $query = 'INSERT INTO calls (id_campaign, phone, status, dnc) VALUES (?, ?, NULL, ?)';
                    $this->_DB->genQuery($query, array($this->campaign, $value, $dnc));

                    if($id_call == 0) $id_call = $this->_DB->getLastInsertId();
                }
                $query = "INSERT INTO call_attribute (id_call, columna, value) VALUES (?, ?, ?)";
                $this->_DB->getFirstRowQuery($query, false, array($id_call, $key, $value));

            }
        }
    }

    function deleteData($id){
        $string = implode("','", $id);

        $query = "DELETE FROM call_attribute WHERE id_call IN ('".$string."')";
        $this->_DB->fetchTable($query);

        $query = "DELETE FROM calls WHERE id IN ('".$string."')";
        $this->_DB->fetchTable($query);

        return true;
    }

}
