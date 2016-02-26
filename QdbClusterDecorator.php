<?php

require_once 'QdbDequeWithExpiration.php';
require_once 'QdbHmap.php';

// Adds 3 features:
//  1. prefix
//  2. QdbDequeWithExpiration are returned instead of QdbDeque
//  3. QdbHmap are returned instead of QdbTag
class QdbClusterDecorator {

  private $cluster, $prefix;

  public function __construct($db, $prefix) {
    $this->cluster = $db;
    $this->prefix = $prefix;
  }

  // forward the call
  public function __call($name, $args) {
    $res = call_user_func_array(array($this->cluster, $name), $args);
    return $res === $this->cluster ? $this : $res;
  }

  private function makeAlias($alias) {
    return $prefix . $alias;
  }

  public function blob($key) {
    $alias = $this->makeAlias($key);
    return $this->cluster->blob($alias);
  }

  public function deque($key){
    $alias = $this->makeAlias($key);
    return new QdbDequeWithExpiration($this->cluster, $alias);
  }

  public function entry($key) {
    $alias = $this->makeAlias($key);
    $entry = $this->cluster->entry($alias);
    // tags are used to emulate redis' hash
    if ($entry instanceof QdbTag) {
      return new QdbHmap($this->cluster, $alias);
    }
    if ($entry instanceof QdbDeque) {
      return new QdbDequeWithExpiration($this->cluster, $alias);
    }
    return $entry;
  }

  public function hmap($key){
    $alias = $this->makeAlias($key);
    return new QdbHmap($this->cluster, $alias);
  }

  public function integer($key) {
    $alias = $this->makeAlias($key);
    return $this->cluster->integer($alias);
  }
}

?>