<?php

// Base class for decorators that adds expiration to an entry that doesn't natively support it.
abstract class QdbEntryWithExpiration { 
  public function __construct($db, $key) {
    $this->marker = $db->blob("$key.expiration");
  }

  public function expiresFromNow($seconds) {
    $this->marker->update("expires", time()+$seconds);
  }

  public function getExpiryTime() {
    return $this->marker->getExpiryTime();
  }

  abstract protected function removeExpiredEntry();

  protected function handleExpiration() {
    try {
      $this->marker->put("no expiry");      
      try {
        $this->removeExpiredEntry();
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