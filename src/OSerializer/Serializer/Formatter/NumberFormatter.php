<?php

namespace OSerializer\Serializer\Formatter;

use Zend\I18n\Filter\NumberFormat;

/**
 * Format numbers
 *
 * @author vbarinov
 */
class NumberFormatter implements FormatterInterface
{
    /**
     * @var \Zend\I18n\Filter\NumberFormat|null
     */
    protected $numberFormatter = null;

    /**
     * @var string
     */
    protected $locale = 'ru_RU';

    /**
     *
     * @param mixed $value
     * @param $property
     * @param Entity $targetObject
     * @param $commonData
     * @return mixed
     */
    public function format($value, &$property, $targetObject, &$commonData)
    {
        if ($this->numberFormatter === null) {
            $this->numberFormatter = new NumberFormat($this->getLocale());
        }

        return $this->numberFormatter->filter($value);
    }

    public function isFormattable($targetEntity, $property, $value)
    {
        return is_scalar($value);
    }

    public function decode($value)
    {
        return $value;
    }

    public function isDecodeable($value)
    {
        return true;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }
}
