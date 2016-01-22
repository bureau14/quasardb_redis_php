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
    $value = $this->deque->popBack();
    $this->removeIfEmpty();
    return $value;
  }

  public function popFront() {
    $this->handleExpiration();
    $value = $this->deque->popFront();
    $this->removeIfEmpty();
    return $value;
  }

  public function pushBack($value) {
    $this->handleExpiration();
    return $this->deque->pushBack($value);
  }

  public function pushFront($value) {
    $this->handleExpiration();
    return $this->deque->pushFront($value);
  }

  public function remove() {
    parent::remove();
    return $this->deque->remove();
  }
  
  protected function removeExpiredEntry() {
    $this->deque->remove();
  }

  private function removeIfEmpty() {
    if ($this->deque->size() == 0) {
      $this->remove();
    }
  }
}

?>