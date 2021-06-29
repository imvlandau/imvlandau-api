<?php

namespace App\DoctrineExtensions\DBAL\Types;

use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Platforms\AbstractPlatform;

use Doctrine\DBAL\Types\ConversionException;
use AppBundle\Doctrine\DBAL\DateTimeNotConvertable;

class PmbDateTimeType extends DateTimeType
{
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (null === $value) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format($platform->getDateTimeFormatString());
        }

        $converted = new \DateTime($value);
        return $converted->format($platform->getDateTimeFormatString());
    }
}
