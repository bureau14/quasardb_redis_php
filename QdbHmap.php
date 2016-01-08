<?php

class QdbHmap
{
  public function __construct($db, $key) {
    $this->db = $db;
    $this->rootKey = $key;
  }

  public function remove() {
    $root = $this->db->tag($this->rootKey);
    foreach ($root->getEntries() as $leaf) {
      $leaf->remove();
    }
    $root->remove();
  }

  public function at($subkey) {
    return $this->leaf($subkey)->get();
  }

  public function insert($subkey, $value) {
    $leaf = $this->leaf($subkey);
    $leaf->update($value);
    return $leaf->addTag($this->rootKey);
  }

  public function erase($subkey) {
    $leaf = $this->leaf($subkey);
    $leaf->remove();
  }

  private function leaf($subkey) {
    $leafKey = $this->rootKey . '.' . $subkey;
    return $this->db->blob($leafKey);
  }
}

?>
