<?php

class ListManager {

  private $lists = array();

  public function getLists()
  {
    return $this-lists;
  }

  public function setLists($lists = array())
  {
    if (!is_array($lists)) {
      throw new InvalidArgumentException('Argument must be an array');
    }

    $this->lists = $lists;

    return $this;
  }


  public function getListByName($name = null, $default = [])
  {
    // TODO
    return $this;
  }

  public function addListFromFile($filename = null)
  {
    // TODO - handle local filenames and urls
    // FileManger::readContents()

    return $this;
  }

  public function deduplicate()
  {

  }

  public function combineLists()
  {

  }

  // NOTE : Something needs to be done in order to make each list manageable
  //  and handle duplicate lists, and duplicate list names
}
