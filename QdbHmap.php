<?php

class QdbHmap
{
  public function __construct($db, $key) {
    $this->db = $db;
    $this->trunkKey = $key;
  }

  public function remove() { 
    $trunk = $this->db->tag($this->trunkKey);   
    foreach ($trunk->getEntries() as $leaf) {
      $leaf->remove();
    }
    $trunk->remove();
  }

  public function at($subkey) {
    return $this->leaf($subkey)->get();
  }

  public function insert($subkey, $value) {
    $leaf = $this->leaf($subkey);
    $leaf->update($value);
    return $leaf->addTag($this->trunkKey);
  }

  public function erase($subkey) {
    $leaf = $this->leaf($subkey);
    $leaf->remove();
  }

  public function values() {
    $values = [];
    $trunk = $this->db->tag($this->trunkKey);
    foreach ($trunk->getEntries() as $leaf) {
      $values[$this->subkey($leaf)] = $leaf->get();
    }
    return $values;
  }

  private function leaf($subkey) {
    $leafKey = $this->trunkKey . '.' . $subkey;
    return $this->db->blob($leafKey);
  }

  private function subkey($leaf) {
    return substr($leaf->alias(), strlen($this->trunkKey)+1);
  }
}

?>
