<?php

class Db_class {

    var $query_num = 0;
    var $link = false;

    function Db_class($dbhost, $dbport, $dbuser, $dbpw, $dbname) {
        $this->connect($dbhost, $dbport, $dbuser, $dbpw, $dbname);
    }

    function connect($dbhost, $dbport, $dbuser, $dbpw, $dbname) {
        global $dbcharset;
        $dbcharset = "utf8";
        $this->link = @mysqli_connect($dbhost, $dbuser, $dbpw, $dbname, $dbport);
        if (!$this->link) {
            return;
        }
        if ($this->server_info() > '4.1' && $dbcharset)
            mysqli_query($this->link, "SET NAMES '" . $dbcharset . "'");
        if ($this->server_info() > '5.0')
            mysqli_query($this->link, "SET sql_mode=''");
        return $this->link;
    }

    function get_link() {
        return $this->link;
    }

    function server_info() {
        return mysqli_get_server_info($this->link);
    }

    function query($SQL, $method = '') {
        if ($method == 'unbuffer' && function_exists('mysqli_unbuffered_query'))
            $query = mysqli_unbuffered_query($SQL, $this->link);
        else
            $query = mysqli_query($this->link, $SQL);
        if (!$query && $method != 'SILENT')
            $this->halt('MySQL Query Error: ' . $SQL);
        $this->query_num++;
        return $query;
    }

    function update($SQL) {
        return $this->query($SQL, 'unbuffer');
    }

    function get_value($SQL, $result_type = MYSQL_NUM) {
        $query = $this->query($SQL, 'unbuffer');
        $rs = mysqli_fetch_array($query, MYSQL_NUM);
        return $rs[0];
    }

    function get_one($SQL, $method = 'unbuffer') {
        $query = $this->query($SQL, $method);
        $rs = mysqli_fetch_array($query, MYSQL_ASSOC);
        return $rs;
    }

    function get_all($SQL, $result_type = MYSQL_ASSOC) {
        $query = $this->query($SQL);
        $result = array();
        while ($row = mysqli_fetch_array($query, $result_type))
            $result[] = $row;
        return $result;
    }

    function fetch_array($query, $result_type = MYSQL_ASSOC) {
        return mysqli_fetch_array($query, $result_type);
    }

    function affected_rows() {
        return mysqli_affected_rows($this->link);
    }

    function fetch_row($query) {
        return mysqli_fetch_row($query);
    }

    function num_rows($query) {
        return mysqli_num_rows($query);
    }

    function num_fields($query) {
        return mysqli_num_fields($query);
    }

    function result($query, $row) {
        $query = mysqli_result($query, $row);
        return $query;
    }

    function free_result($query) {
        return mysqli_free_result($query);
    }

    function insert_id() {
        return ($id = mysqli_insert_id($this->link)) >= 0 ? $id : $this->result($this->query("SELECT last_insert_id()"), 0);
    }

    function close() {
        return mysqli_close($this->link);
    }

    function error() {
        return (($this->link) ? mysqli_error($this->link) : mysqli_error());
    }

    function errno() {
        return intval(($this->link) ? mysqli_errno($this->link) : mysqli_errno());
    }

    function select_query($fields, $table, $where) {
        if (!$fields)
            return;
        if (!$table)
            return;
        $where = $where ? "WHERE $where" : '';
        return $this->query("SELECT $fields FROM $table $where");
    }

    function select_one($fields, $table, $where) {
        if (!$fields)
            return;
        if (!$table)
            return;
        $where = $where ? "WHERE $where" : '';
        return $this->get_one("SELECT $fields FROM $table $where");
    }

    function select_all($fields, $table, $where) {
        if (!$fields)
            return;
        if (!$table)
            return;
        $where = $where ? "WHERE $where" : '';
        return $this->get_all("SELECT $fields FROM $table $where");
    }

    function select_value($field, $table, $where) {
        if (!$field)
            return;
        if (!$table)
            return;
        $where = $where ? "WHERE $where" : '';
        return $this->get_value("SELECT $field FROM $table $where");
    }

    function select_count($table, $where) {
        return $this->select_value("COUNT(*)", $table, $where);
    }

    function delete_new($table, $where) {
        if (!$table)
            return;
        $where = $where ? "WHERE $where" : '';
        return $this->query("DELETE FROM $table $where");
    }

    function insert_new($table, $inlist) {
        if (!$table)
            return;
        if (!is_array($inlist) || count($inlist) == 0)
            return;
        foreach ($inlist as $key => $val) {
            $set[] = "$key='$val'";
        }
        $SQL = "INSERT $table SET " . implode(", ", $set);
        return $this->query($SQL);
    }

    function update_new($table, $where, $uplist) {
        if (!$table)
            return;
        if (!is_array($uplist) || count($uplist) == 0)
            return;
        $where = $where ? "WHERE $where" : '';
        foreach ($uplist as $key => $val) {
            $set[] = "$key='$val'";
        }
        $SQL = "UPDATE $table SET " . implode(", ", $set) . " $where";
        return $this->update($SQL);
    }

    function halt($msg = '') {
        $message .= "<p>Msg:</p><pre><b>" . ($msg) . "</b>";
        $message .= "<b>Mysql error description</b>: " . ($this->error()) . "\n<br />";
        $message .= "<b>Mysql error number</b>: " . $this->errno() . "\n<br />";
        echo $message;
        //exit;
    }

}
