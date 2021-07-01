<?php

namespace App\DoctrineExtensions\DBAL\Types;

use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
// use App\Exception\ApiProblem;
// use App\Exception\ApiProblemException;

class PmbDateTimeType extends DateTimeType
{
    /**
    * @var \DateTimezone
    */
    private static $utcDateTimezone;

    /**
    * @var \DateTimeZone
    */
    private static $serverDateTimezone;

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {

        // throw new ApiProblemException(new ApiProblem(400, "Test error"));

        if (null === $value) {
            return $value;
        }

        // // Wrong: this is 2021-07-01T21:00:06+02:00 (according to the time zone settings)
        // self::$serverDateTimezone = self::$serverDateTimezone ?: new \DateTimeZone(date_default_timezone_get());
        // if ($value instanceof \DateTime) {
        //     if (self::$serverDateTimezone->getName() !== $value->getTimezone()->getName()) {
        //         $value->setTimezone(self::$serverDateTimezone);
        //     }
        //     return $value;
        // }
        // // convert string to DateTime-Object
        // self::$utcDateTimezone = self::$utcDateTimezone ?: new \DateTimeZone('UTC');
        // $converted = \DateTime::createFromFormat(
        //     $platform->getDateTimeFormatString(),
        //     $value,
        //     self::$utcDateTimezone
        // );
        // if (!$converted) {
        //     throw ConversionException::conversionFailedFormat(
        //         $value,
        //         $this->getName(),
        //         $platform->getDateTimeFormatString()
        //     );
        // }
        // return $converted->setTimezone(self::$serverDateTimezone);

        // Correct: this is 2021-07-01T19:00:06+02:00 (like in the database table)
        // return new \DateTime($value);
        return new \DateTime($value);

        // by returning just a string there will be an error with serialization
        // return $value;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
      // throw new \Exception($value, 1);
      // throw new ApiProblemException(new ApiProblem(400, "sdfasdf asdfasdf"));
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
