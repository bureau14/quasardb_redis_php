<?php

class QdbHmap
{
  public function __construct($db, $key) {
    $this->db = $db;
    $this->trunkKey = $key;
    $this->tag = $db->tag($key);
  }

  public function remove() { 
    $trunk = $this->db->tag($this->trunkKey);   
    foreach ($trunk->getEntries() as $leaf) {
      $leaf->remove();
    }
    $trunk->remove();
  }

  public function at($subkey) {
    return $this->leaf($subkey);
  }

  public function erase($subkey) {
    $this->leaf($subkey)->remove();
  }

  public function values() {
    $values = [];
    foreach ($this->tag->getEntries() as $leaf) {
      $values[$this->subkey($leaf)] = $leaf->get();
    }
    return $values;
  }

  public function put($subkey, $value) {
    $leafKey = $this->leafKey($subkey);
    if (is_int($value)) {
      $this->db->integer($leafKey)->put($value);
    } else {
      $this->db->blob($leafKey)->put($value);
    }
    $this->tag->addEntry($leafKey);
  }

  public function update($subkey, $value) {
    $leafKey = $this->leafKey($subkey);
    if (is_int($value)) {
      $this->db->integer($leafKey)->update($value);
    } else {
      $this->db->blob($leafKey)->update($value);
    }
    return $this->tag->addEntry($leafKey);
  }

  private function leaf($subkey) {
    return $this->db->entry($this->leafKey($subkey));
  }

  private function leafKey($subkey)
  {
    return $this->trunkKey . '.' . $subkey;
  }

  private function subkey($leaf) {
    return substr($leaf->alias(), strlen($this->trunkKey)+1);
  }
}

?>
