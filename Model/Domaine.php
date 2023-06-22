<?php

use autoload;

require 'vendor/autoload.php';

class Domaine
{
    private $id;
    private $name;
    private $date;

    // Constructor
    public function __construct($id, $name, $date)
    {
        $this->id = $id;
        $this->name = $name;
        $this->date = $date;
    }

    // Getter for name
    public function getName()
    {
        return $this->name;
    }

    // Setter for name
    public function setName($name)
    {
        $this->name = $name;
    }

    // Getter for date
    public function getDate()
    {
        return $this->date;
    }

    // Setter for date
    public function setDate($date)
    {
        $this->date = $date;
    }
}