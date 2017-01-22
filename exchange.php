<?php

abstract class Exchange
{
    protected $symbols = array();
    protected $prices = array();
    
    public function getSymbols()
    {
        return $this->symbols;
    }
    
    public function getPrices()
    {
        return $this->prices;
    }
}

?>
