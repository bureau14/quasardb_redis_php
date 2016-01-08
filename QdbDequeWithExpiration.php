<?php

// Current implementation of quasardb doesn't allow QdbDeque to have an expiration time.
// This decorator adds that functionality
class QdbDequeWithExpiration { 
  public function __construct($db, $key) {
    $this->deque = $db->deque($key);
    $this->marker = $db->blob("$key.expiration");
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

  public function expiresFromNow($seconds) {
    $this->marker->update("expires", time()+$seconds);
  }

  private function handleExpiration() {
    try {
      $this->marker->put("no expiry");      
      try {
        $this->deque->remove();
        // here = the deque expired, so we removed it
      }
      catch (QdbAliasNotFoundException $e) {
        // here = the deque didn't not exits at all
      }
    }
    catch (QdbAliasAlreadyExistsException $e) {
      // here = the deque exists and hasn't expired
    }
  }
}

?>