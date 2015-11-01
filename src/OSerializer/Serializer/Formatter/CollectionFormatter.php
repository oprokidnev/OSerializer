<?php

namespace OSerializer\Serializer\Formatter;

/**
 * Description of DateTimeFormatter
 *
 * @author oprokidnev
 */
class CollectionFormatter implements FormatterInterface
{

    /**
     * 
     * @param \DateTime $value
     * @param Entity $targetObject
     */
    public function format($value, &$property, $targetObject, &$commonData)
    {
        return $value;
        return $value->format(\DateTime::ISO8601);
    }

    public function isFormattable($targetEntity, $property, $value)
    {
        return $value instanceof \Doctrine\Common\Collections\Collection;
    }

    public function decode($value)
    {
        return (boolean) \DateTime::createFromFormat(\DateTime::ISO8601, $value);
    }

    /**
     * 
     * @param boolean $value
     */
    public function isDecodeable($value)
    {
        return (boolean) \DateTime::createFromFormat(\DateTime::ISO8601, $value);
    }

}
