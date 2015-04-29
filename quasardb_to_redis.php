<?php

class QuasardbRedis {

  public function __construct($db, $ns, $set) {
    $this->db = $db;
    $this->ns = $ns;
    $this->set = $set;
    $this->on_multi = false;
  }

  private function out($v) {
    if ($this->on_multi === false) {
      return $v;
    }
    else {
      array_push($this->on_multi, $v);
      return $this;
    }
  }

  private function serialize($v) {
    return is_string($v) ? $v : "__64__" . base64_encode($v);
  }

  private function deserialize($v) {
    return substr($v, 0, 6) === "__64__" ? base64_decode(substr($v, 6)) : $v;
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
    try {
      $ret_val =  $this->db->blob('blob.'.$key)->get();
      return $this->out($this->deserialize($ret_val));
    }
    catch(QdbAliasNotFoundException $e ) {
      return $this->out(false);
    }
  }

  public function ttl($key) {
    $ttl = $this->db->blob('blob.'.$key)->getExpiryTime() - time();
    return $this->out($ttl);
  }

  public function setTimeout($key, $ttl) {
    try {
      $this->db->blob('blob.'.$key)->expiresFromNow($ttl);
      return $this->out(true);
    }
    catch(QdbAliasNotFoundException $e ) {
      return $this->out(false);
    }
  }

  public function set($key, $value) {
    $this->db->blob('blob.'.$key)->update($this->serialize($value));
    return $this->out(true);
  }

  public function del($key) {
    try {
      $this->db->blob('blob.'.$key)->remove();
      return $this->out(1);
    }
    catch(QdbAliasNotFoundException $e) {
      return $this->out(0);
    }
  }

  public function setex($key, $ttl, $value) {
    try {
      $this->db->blob('blob.'.$key)->update($value, time()+$ttl);
      return $this->out(true);
    }
    catch(Exception $e ) {
      return $this->out(false);
    }
  }

  public function rpush($key, $value) {
    $this->db->queue('queue.'.$key)->pushBack($this->serialize($value));
    return $this->out($this->change_queue_size($key, +1));
  }

  public function lpush($key, $value) {
    $this->db->queue('queue.'.$key)->pushFront($this->serialize($value));
    return $this->out($this->change_queue_size($key, +1));
  }

  public function rpop($key) {
    try {
      $ret_val = $this->db->queue('queue.'.$key)->popBack();
      $this->change_queue_size($key, -1);
      return $this->out($this->deserialize($ret_val));
    }
    catch(QdbAliasNotFoundException $e ) {
      return $this->out(false);
    }
    catch(QdbEmptyContainerException $e ) {
      return $this->out(false);
    }
  }

  public function lpop($key) {
    try {
      $ret_val = $this->db->queue('queue.'.$key)->popFront();
      $this->change_queue_size($key, -1);
      return $this->out($this->deserialize($ret_val));
    }
    catch(QdbAliasNotFoundException $e ) {
      return $this->out(false);
    }
    catch(QdbEmptyContainerException $e ) {
      return $this->out(false);
    }
  }

  public function lsize($key) {
    try {
      return $this->out($this->db->integer($key.'.length')->get());
    }
    catch(Exception $e ) {
      return $this->out(0);
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
