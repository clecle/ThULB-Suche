<?php

namespace ThULB\ILS\Logic;

use VuFind\ILS\Logic\AvailabilityStatus as OriginalAvailabilityStatus;

class AvailabilityStatus extends OriginalAvailabilityStatus {

    /**
     * Status code for ordered items
     *
     * @var int
     */
    public const STATUS_ORDERED = 4;

    /**
     * Get status description.
     *
     * @return string
     */
    public function getStatusDescription(): string
    {
        if (!empty($this->status)) {
            return $this->status;
        }
        switch ($this->availability) {
            case self::STATUS_UNAVAILABLE:
                return 'Unavailable';
            case self::STATUS_AVAILABLE:
                return 'Available';
            case self::STATUS_ORDERED:
                return 'Ordered';
            case self::STATUS_UNKNOWN:
                return 'status_unknown_message';
            default:
                return 'Uncertain';
        }
    }

    /**
     * Get schema.org availability URI.
     *
     * @return ?string
     */
    public function getSchemaAvailabilityUri(): ?string
    {
        switch ($this->availability) {
            case self::STATUS_UNAVAILABLE:
                return 'http://schema.org/OutOfStock';
            case self::STATUS_AVAILABLE:
                return 'http://schema.org/InStock';
            case self::STATUS_ORDERED:
            case self::STATUS_UNKNOWN:
                return null;
            default:
                return 'http://schema.org/LimitedAvailability';
        }
    }

    /**
     * Convert availability to a string
     *
     * @return string
     */
    public function availabilityAsString(): string
    {
        switch ($this->availability) {
            case self::STATUS_UNAVAILABLE:
                return 'false';
            case self::STATUS_AVAILABLE:
                return 'true';
            case self::STATUS_ORDERED:
                return 'ordered';
            case self::STATUS_UNKNOWN:
                return 'unknown';
            default:
                return 'uncertain';
        }
    }

    /**
     * Get status priority.
     *
     * @return int
     */
    public function getPriority(): int
    {
        switch ($this->availability) {
            case self::STATUS_UNKNOWN:
                return 0;
            case self::STATUS_UNAVAILABLE:
                return 1;
            case self::STATUS_ORDERED:
                return 2;
            case self::STATUS_UNCERTAIN:
                return 3;
            default:
                return 4;
        }
    }
}
