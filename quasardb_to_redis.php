<?php

class QuasardbRedis {

  public function __construct($db) {
    $this->db = $db;
  }

  private function serialize($v) {
    return is_string($v) ? $v : "__64__" . base64_encode($v);
  }

  private function deserialize($v) {
    return substr($v, 0, 6) === "__64__" ? base64_decode(substr($v, 6)) : $v;
  }

  public function get($key) {
    try {
      $ret_val =  $this->db->blob($key)->get();
      return $this->deserialize($ret_val);
    }
    catch(QdbAliasNotFoundException $e ) {
      return false;
    }
  }

  public function ttl($key) {
    return $this->db->blob($key)->getExpiryTime() - time();
  }

  public function setTimeout($key, $ttl) {
    try {
      $this->db->blob($key)->expiresFromNow($ttl);
      return true;
    }
    catch(QdbAliasNotFoundException $e ) {
      return false;
    }
  }

  public function set($key, $value) {
    $this->db->blob($key)->update($this->serialize($value));
    return true;
  }

  public function del($key) {
    try {
      $this->db->blob($key)->remove();
      return 1;
    }
    catch(QdbAliasNotFoundException $e) {
      return 0;
    }
  }

  public function setex($key, $ttl, $value) {
    $this->db->blob($key)->update($value, time()+$ttl);
    return true;
  }

  public function rpush($key, $value) {
    $this->db->queue($key)->pushBack($this->serialize($value));
    return $this->change_queue_size($key, +1);
  }

  public function lpush($key, $value) {
    $this->db->queue($key)->pushFront($this->serialize($value));
    return $this->change_queue_size($key, +1);
  }

  public function rpop($key) {
    try {
      $ret_val = $this->db->queue($key)->popBack();
      $this->change_queue_size($key, -1);
      return $this->deserialize($ret_val);
    }
    catch(QdbAliasNotFoundException $e ) {
      return false;
    }
    catch(QdbEmptyContainerException $e ) {
      return false;
    }
  }

  public function lpop($key) {
    try {
      $ret_val = $this->db->queue($key)->popFront();
      $this->change_queue_size($key, -1);
      return $this->deserialize($ret_val);
    }
    catch(QdbAliasNotFoundException $e ) {
      return false;
    }
    catch(QdbEmptyContainerException $e ) {
      return false;
    }
  }

  public function lsize($key) {
    try {
      return $this->db->integer($key.'.length')->get();
    }
    catch(Exception $e ) {
      return 0;
    }
  }

  public function flushdb() {
    
  }

  public function connect($a1 = 1, $a2 = 1, $a3 = 1, $a4 = 1, $a5 = 1) {
  }

  public function pconnect($a1 = 1, $a2 = 1, $a3 = 1, $a4 = 1) {
  }

  public function close() {
  }

  // Queue size is not supported yet, so we emulate it with an atomic integer
  private function change_queue_size($key, $inc) {
    try {
      return $this->db->integer($key.'.length')->add($inc);
    }
    catch(QdbAliasNotFoundException $e) {
      try {
        $this->db->integer($key.'.length')->put($inc);
        return $inc;
      }
      catch(QdbAliasAlreadyExistsException $e) {
        return $this->db->integer($key.'.length')->add($inc);
      }
    }
  }
}


// Decorator that adds the "transaction" feature
class QuasardbRedisWithMulti extends QuasardbRedis {
  private $on_multi = false;

  private function out($v) {
    if ($this->on_multi === false) {
      return $v;
    }
    else {
      array_push($this->on_multi, $v);
      return $this;
    }
  }

  public function pipeline() {
    return $this->multi();
  }

  public function multi() {
    $this->on_multi = array();
    return $this;
  }

  public function exec() {
    $res = $this->on_multi;
    $this->on_multi = false;
    return $res;
  }

  public function get($key) {
    return $this->out(parent::get($key));
  }

  public function ttl($key) {
    return $this->out(parent::ttl($key));
  }

  public function setTimeout($key, $ttl) {
    return $this->out(parent::setTimeout($key, $ttl));
  }

  public function set($key, $value) {
    return $this->out(parent::set($key, $value));
  }

  public function del($key) {
    return $this->out(parent::del($key));
  }

  public function setex($key, $ttl, $value) {
    return $this->out(parent::setex($key, $ttl, $value));
  }

  public function rpush($key, $value) {
    return $this->out(parent::rpush($key, $value));
  }

  public function lpush($key, $value) {
    return $this->out(parent::lpush($key, $value));
  }

  public function rpop($key) {
    return $this->out(parent::rpop($key));
  }

  public function lpop($key) {
    return $this->out(parent::lpop($key));
  }

  public function lsize($key) {
    return $this->out(parent::lsize($key));
  }
}