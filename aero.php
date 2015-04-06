<?php

class AerospikeRedis {

  const BIN_NAME = "r";

  public function __construct($db, $db_name, $ns = "test") {
    $this->db = $db;
    $this->db_name = $db_name;
    $this->ns = $ns;
  }

  private function format_key($key) {
    return $this->db->initKey($this->ns, $this->db_name, $key);
  }

  private function check_result($status) {
    if ($status != Aerospike::OK) {
      throw new Exception("Aerospike error");
    }
  }

  private function assert_ok($ret_val) {
    if ($ret_val != "OK") {
      throw new Exception("Aerospike error, result not OK ".$ret_val);
    }
  }

  public function get($key) {
    $status = $this->db->apply($this->format_key($key), "redis", "GET", array(self::BIN_NAME), $ret_val);
    $this->check_result($status);
    return is_array($ret_val) ? NULL : $ret_val;
  }

  public function ttl($key) {
    $status = $this->db->apply($this->format_key($key), "redis", "TTL", array(self::BIN_NAME), $ret_val);
    $this->check_result($status);
    return is_array($ret_val) ? NULL : $ret_val;
  }

  public function set($key, $value) {
    $status = $this->db->apply($this->format_key($key), "redis", "SET", array(self::BIN_NAME, $value), $ret_val);
    $this->check_result($status);
    $this->assert_ok($ret_val);
  }

  public function del($key) {
    $status = $this->db->apply($this->format_key($key), "redis", "DEL", array(self::BIN_NAME), $ret_val);
    $this->check_result($status);
    $this->assert_ok($ret_val);
  }

  public function setex($key, $value, $ttl) {
    $status = $this->db->apply($this->format_key($key), "redis", "SETEX", array(self::BIN_NAME, $value, $ttl), $ret_val);
    $this->check_result($status);
    $this->assert_ok($ret_val);
  }

  public function rpush($key, $value) {
    $status = $this->db->apply($this->format_key($key), "redis", "RPUSH", array(self::BIN_NAME, $value), $ret_val);
    $this->check_result($status);
    return is_array($ret_val) ? NULL : $ret_val;
  }

  public function rpop($key) {
    $status = $this->db->apply($this->format_key($key), "redis", "RPOP", array(self::BIN_NAME, 1), $ret_val);
    $this->check_result($status);
    // var_dump($ret_val);
    return count($ret_val) == 0 ? NULL : $ret_val[0];
  }

  public function lsize($key) {
    $status = $this->db->apply($this->format_key($key), "redis", "LSIZE", array(self::BIN_NAME), $ret_val);
    $this->check_result($status);
    return is_array($ret_val) ? NULL : $ret_val;
  }

}
