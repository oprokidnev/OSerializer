<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace OSerializer\Iterator;

/**
 * Description of HydrationResultIterator
 *
 * @author oprokidnev
 */
class SerializerIterator extends \IteratorIterator implements \Countable
{

    /** 
     * @var \OSerializer\Serializer\DoctrineObjectSerializer Serializer
     */
    protected $Serializer;

    /**
     * @param \Traversable|array   $arrayOrIterator Traversable iterator or array
     * @param \OSerializer\Serializer\DoctrineObject $Serializer
     *
     * @throws InvalidArgumentException if the callback if not callable
     */
    public function __construct($arrayOrIterator, $Serializer)
    {
        if (is_array($arrayOrIterator)) {
            $arrayOrIterator = new \ArrayIterator($arrayOrIterator);
        }
        parent::__construct($arrayOrIterator);

        $this->Serializer = $Serializer;
    }
    /**
     * Provides items count
     * 
     * @return int
     */
    public function count()
    {
        if($this->getInnerIterator() instanceof \Countable) {
            return $this->getInnerIterator()->count();
        }else{
            return count(iterator_to_array($this->getInnerIterator()));
        }
    }

    protected $extractionCache = [];

    public function current()
    {
        if (!isset($this->extractionCache[$this->key()])) {
            $this->extractionCache[$this->key()] = $this->Serializer->extract(parent::current());
        }
        return $this->extractionCache[$this->key()];
    }

}
