<?php

  class ListManager
  {

    private $lists = array();

    /**
     * @return array
     */
    public function getLists()
    {
      return $this->lists;
    }

    /**
     * @param array $lists
     *
     * @return $this
     */
    public function setLists($lists = array())
    {
      if (!is_array($lists)) {
        throw new InvalidArgumentException('Argument must be an array');
      }

      $this->lists = $lists;

      return $this;
    }

    /**
     * @param null  $name
     * @param array $default
     *
     * @return $this
     */
    public function getListByName($name = null, $default = [])
    {
      // TODO
      return $this;
    }

    /**
     * Note that this function doesn't read the contents of the file, it just adds the filepath to a collection for us
     * to use later
     *
     * @param null $filename
     *
     * @return $this
     */
    public function addListFromFile($filename = null)
    {
      // TODO - handle local filenames and urls
      // FileManger::readContents()

      return $this;
    }

    /**
     *
     */
    private function deduplicate()
    {

    }

    /**
     *
     */
    private function combineLists()
    {

    }

    // NOTE : Something needs to be done in order to make each list manageable
    //  and handle duplicate lists, and duplicate list names
  }
