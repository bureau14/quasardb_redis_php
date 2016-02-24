<?php

require_once 'QdbDequeWithExpiration.php';
require_once 'QdbHmap.php';

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

  private function getEntry($key) {
    $entry = $this->db->entry($key);
    // tags are used to emulate redis' hash
    if ($entry instanceof QdbTag) {
      return new QdbHmap($this->db, $key);
    }
    if ($entry instanceof QdbDeque) {
      return new QdbDequeWithExpiration($this->db, $key);
    }
    return $entry;
  }

  public function get($key) {
    try {
      $ret_val =  $this->db->blob($key)->get();
      return $this->deserialize($ret_val);
    }
    catch(QdbIncompatibleTypeException $e) {
      return "" . $this->db->integer($key)->get();
    }
    catch(QdbAliasNotFoundException $e) {
      return false;
    }
  }

  public function ttl($key) {
    try {
      return $this->getEntry($key)->getExpiryTime() - time();
    }
    catch(QdbAliasNotFoundException $e) {
      return -2;
    }
  }

  public function setTimeout($key, $ttl) {
    try {
      $this->getEntry($key)->expiresFromNow($ttl);
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
      $this->getEntry($key)->remove();
      return 1;
    }
    catch(QdbAliasNotFoundException $e) {
      return 0;
    }
  }

  public function delete($key) {    
    return $this->del($key);
  }

  public function setex($key, $ttl, $value) {
    $this->db->blob($key)->update($value, time()+$ttl);
    return true;
  }

  public function rpush($key, $value) {
    $deque = new QdbDequeWithExpiration($this->db, $key);
    $deque->pushBack($this->serialize($value));
    return $deque->size();
  }

  public function rpushex($key, $value, $ttl)  {
    $deque = new QdbDequeWithExpiration($this->db, $key);
    $deque->expiresFromNow($ttl);
    $deque->pushBack($this->serialize($value));
    return $deque->size();
  }

  public function lpush($key, $value) {
    $deque = new QdbDequeWithExpiration($this->db, $key);
    $deque->pushFront($this->serialize($value));
    return $deque->size();
  }

  public function lpushex($key, $value, $ttl = -1) {
    $deque = new QdbDequeWithExpiration($this->db, $key);
    $deque->expiresFromNow($ttl);
    $deque->pushFront($this->serialize($value));
    return $deque->size();
  }

  public function rpop($key) {
    $deque = new QdbDequeWithExpiration($this->db, $key);
    try {
      return $this->deserialize($deque->popBack());
    }
    catch(QdbAliasNotFoundException $e ) {
      return false;
    }
    catch(QdbContainerEmptyException $e ) {
      return false;
    }
  }

  public function lpop($key) {
    $deque = new QdbDequeWithExpiration($this->db, $key);
    try {
      return $this->deserialize($deque->popFront());
    }
    catch(QdbAliasNotFoundException $e ) {
      return false;
    }
    catch(QdbContainerEmptyException $e ) {
      return false;
    }
  }

  public function lsize($key) {
    $deque = new QdbDequeWithExpiration($this->db, $key);
    try {
      return $deque->size();
    }
    catch(QdbAliasNotFoundException $e) {
      return 0;
    }
  }

  public function incrby($key, $increment) {
    try {
      $this->db->integer($key)->put($increment);      
      return $increment;
    }
    catch(QdbAliasAlreadyExistsException $e) {
      return $this->db->integer($key)->add($increment);
    }
    catch(QdbIncompatibleTypeException $e) {
      return false;
    }
  }

  public function decrby($key, $decrement) {
    return $this->incrby($key, -$decrement);
  }

  public function incr($key) {
    return $this->incrby($key, 1);
  }

  public function decr($key) {
    return $this->incrby($key, -1);
  }

  public function flushdb() {
    try {
      $this->db->purgeAll();
      return true;
    } 
    catch (QdbOperationDisabled $e) {
      return false;
    }
  }

  public function hget($key, $field) {
    $map = new QdbHmap($this->db, $key);
    try {
      return $this->deserialize(strval($map->at($field)->get()));
    }
    catch (QdbAliasNotFoundException $e) {
      return false;
    }
  }

  public function hmget($key, $fields) {
    $result = [];
    $map = new QdbHmap($this->db, $key);
    foreach ($fields as $field) {
      try {
        $result[$field] = $this->deserialize($map->at($field)->get());
      }
      catch (QdbAliasNotFoundException $e) {
        $result[$field] = false;
      }
    }
    return $result;
  }

  public function hset($key, $field, $value) {
    $map = new QdbHmap($this->db, $key);
    return $map->update($field, $this->serialize($value)) ? 1 : 0;
  }

  public function hmset($key, $fields) {
    $map = new QdbHmap($this->db, $key);
    foreach ($fields as $field => $value) {
      $map->update($field, $this->serialize($value));
    }
    return true;
  }

  public function hgetall($key) {
    $result = [];
    $map = new QdbHmap($this->db, $key);
    foreach ($map->values() as $key => $value) {
      $result[$key] = $this->deserialize(strval($value));
    }
    return $result;
  }

  public function hdel($key, $field) {
    $map = new QdbHmap($this->db, $key);
    try {
      $map->erase($field);
      return 1;
    }
    catch (QdbAliasNotFoundException $e) {
      return 0;
    }
  }

  public function hincrby($key, $field, $increment) {
    return $this->hincrbyex($key, $field, $increment, -1);
  }

  public function hincrbyex($key, $field, $increment, $ttl) {
    $map = new QdbHmap($this->db, $key);
    if ($ttl > 0 ) {
      $map->expiresFromNow($ttl);
    }
    try {
      $map->put($field, $increment);
      return $increment;
    }
    catch (QdbAliasAlreadyExistsException $e) {
      return $map->at($field)->add($increment);
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

  public function hset($key, $field, $value) {
    return $this->out(parent::hset($key, $field, $value));
  }

  public function hget($key, $field) {
    return $this->out(parent::hget($key, $field));
  }

  public function hmget($key, $fields) {
    return $this->out(parent::hmget($key, $fields));
  }

  public function hgetall($key) {
    return $this->out(parent::hgetall($key));
  }
}
