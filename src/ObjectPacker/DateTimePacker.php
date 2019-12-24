<?php

declare(strict_types=1);

namespace Brick\App\ObjectPacker;

use Brick\DateTime\DateTimeException;
use Brick\DateTime\Duration;
use Brick\DateTime\LocalDate;
use Brick\DateTime\LocalDateTime;
use Brick\DateTime\LocalTime;
use Brick\DateTime\TimeZoneOffset;
use Brick\DateTime\TimeZoneRegion;
use Brick\DateTime\YearMonth;
use Brick\DateTime\ZonedDateTime;

/**
 * Handles conversion between date-time objects and strings.
 */
class DateTimePacker implements ObjectPacker
{
    /**
     * {@inheritdoc}
     */
    public function pack(object $object) : ?PackedObject
    {
        if ($object instanceof Duration) {
            return new PackedObject(Duration::class, (string) $object);
        }

        if ($object instanceof LocalDate) {
            return new PackedObject(LocalDate::class, (string) $object);
        }

        if ($object instanceof LocalTime) {
            return new PackedObject(LocalTime::class, (string) $object);
        }

        if ($object instanceof LocalDateTime) {
            return new PackedObject(LocalDateTime::class, (string) $object);
        }

        if ($object instanceof TimeZoneOffset) {
            return new PackedObject(TimeZoneOffset::class, (string) $object);
        }

        if ($object instanceof TimeZoneRegion) {
            return new PackedObject(TimeZoneRegion::class, (string) $object);
        }

        if ($object instanceof YearMonth) {
            return new PackedObject(YearMonth::class, (string) $object);
        }

        if ($object instanceof ZonedDateTime) {
            return new PackedObject(ZonedDateTime::class, (string) $object);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function unpack(PackedObject $packedObject) : ?object
    {
        $class = $packedObject->getClass();
        $data  = $packedObject->getData();

        try {
            switch ($class) {
                case Duration::class:
                    return Duration::parse($data);

                case LocalDate::class:
                    return LocalDate::parse($data);

                case LocalTime::class:
                    return LocalTime::parse($data);

                case LocalDateTime::class:
                    return LocalDateTime::parse($data);

                case TimeZoneOffset::class:
                    return TimeZoneOffset::parse($data);

                case TimeZoneRegion::class:
                    return TimeZoneRegion::parse($data);

                case YearMonth::class:
                    return YearMonth::parse($data);

                case ZonedDateTime::class:
                    return ZonedDateTime::parse($data);
            }
        } catch (DateTimeException $e) {
            throw new Exception\ObjectNotConvertibleException($e->getMessage(), 0, $e);
        }

        return null;
    }
}
