<?php

require_once 'QdbEntryWithExpiration.php';

// Current implementation of quasardb doesn't allow QdbDeque to have an expiration time.
// This decorator adds that functionality
class QdbDequeWithExpiration extends QdbEntryWithExpiration { 
  public function __construct($db, $key) {
    parent::__construct($db, $key);
    $this->deque = $db->deque($key);
  }

  public function size() {
    $this->handleExpiration();
    return $this->deque->size();
  } 

  public function popBack() {
    $this->handleExpiration();
    return $this->deque->popBack();
  }

  public function popFront() {
    $this->handleExpiration();
    return $this->deque->popFront();
  }

  public function pushBack($value) {
    $this->handleExpiration();
    return $this->deque->pushBack($value);
  }

  public function pushFront($value) {
    $this->handleExpiration();
    return $this->deque->pushFront($value);
  }
  
  protected function removeExpiredEntry() {
    $this->deque->remove();
  }
}

?>