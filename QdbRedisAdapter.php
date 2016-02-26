<?php

require_once 'QdbClusterDecorator.php';

class QdbRedisAdapter {

  private $cluster;

  public function __construct($cluster, $prefix="redis.") {
    $this->cluster = new QdbClusterDecorator($cluster, $prefix);
    $this->prefix = $prefix;
  }

  private function serialize($v) {
    return is_string($v) ? $v : "__64__" . base64_encode($v);
  }

  private function deserialize($v) {
    return substr($v, 0, 6) === "__64__" ? base64_decode(substr($v, 6)) : $v;
  }

  public function get($key) {
    try {
      $ret_val = $this->cluster->blob($key)->get();
      return $this->deserialize($ret_val);
    }
    catch(QdbIncompatibleTypeException $e) {
      return "" . $this->cluster->integer($key)->get();
    }
    catch(QdbAliasNotFoundException $e) {
      return false;
    }
  }

  public function ttl($key) {
    try {
      return $this->cluster->entry($key)->getExpiryTime() - time();
    }
    catch(QdbAliasNotFoundException $e) {
      return -2;
    }
  }

  public function setTimeout($key, $ttl) {
    try {
      $this->cluster->entry($key)->expiresFromNow($ttl);
      return true;
    }
    catch(QdbAliasNotFoundException $e ) {
      return false;
    }
  }

  public function set($key, $value) {
    $this->cluster->blob($key)->update($this->serialize($value));
    return true;
  }

  public function del($key) {
    try {
      $this->cluster->entry($key)->remove();
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
    $this->cluster->blob($key)->update($value, time()+$ttl);
    return true;
  }

  public function rpush($key, $value) {
    $deque = $this->cluster->deque($key);
    $deque->pushBack($this->serialize($value));
    return $deque->size();
  }

  public function rpushex($key, $value, $ttl)  {
    $deque = $this->cluster->deque($key);
    $deque->expiresFromNow($ttl);
    $deque->pushBack($this->serialize($value));
    return $deque->size();
  }

  public function lpush($key, $value) {
    $deque = $this->cluster->deque($key);
    $deque->pushFront($this->serialize($value));
    return $deque->size();
  }

  public function lpushex($key, $value, $ttl = -1) {
    $deque = $this->cluster->deque($key);
    $deque->expiresFromNow($ttl);
    $deque->pushFront($this->serialize($value));
    return $deque->size();
  }

  public function rpop($key) {
    $deque = $this->cluster->deque($key);
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
    $deque = $this->cluster->deque($key);
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
    $deque = $this->cluster->deque($key);
    try {
      return $deque->size();
    }
    catch(QdbAliasNotFoundException $e) {
      return 0;
    }
  }

  public function incrby($key, $increment) {
    try {
      $this->cluster->integer($key)->put($increment);
      return $increment;
    }
    catch(QdbAliasAlreadyExistsException $e) {
      return $this->cluster->integer($key)->add($increment);
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
      $this->cluster->purgeAll();
      return true;
    }
    catch (QdbOperationDisabled $e) {
      return false;
    }
  }

  public function hget($key, $field) {
    $map = $this->cluster->hmap($key);
    try {
      return $this->deserialize(strval($map->at($field)->get()));
    }
    catch (QdbAliasNotFoundException $e) {
      return false;
    }
  }

  public function hmget($key, $fields) {
    $result = [];
    $map = $this->cluster->hmap($key);
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
    $map = $this->cluster->hmap($key);
    return $map->update($field, $this->serialize($value)) ? 1 : 0;
  }

  public function hmset($key, $fields) {
    $map = $this->cluster->hmap($key);
    foreach ($fields as $field => $value) {
      $map->update($field, $this->serialize($value));
    }
    return true;
  }

  public function hgetall($key) {
    $result = [];
    $map = $this->cluster->hmap($key);
    foreach ($map->values() as $key => $value) {
      $result[$key] = $this->deserialize(strval($value));
    }
    return $result;
  }

  public function hdel($key, $field) {
    $map = $this->cluster->hmap($key);
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
    $map = $this->cluster->hmap($key);
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

?>