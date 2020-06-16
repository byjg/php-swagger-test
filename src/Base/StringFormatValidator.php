<?php

namespace ByJG\ApiTools\Base;

use DateTime;

/**
 * Adds ability to validate string format based on open api specification:
 *
 * @link https://swagger.io/docs/specification/data-models/data-types/
 */
class StringFormatValidator
{
    /**
     * Value must match the desired format (date, date-time), returns true if unknown format is provided.
     *
     * @param string $format
     * @param mixed $value
     * @return bool
     */
    public function validate($format, $value)
    {
        // full-date notation as defined by RFC 3339, section 5.6, for example, 2017-07-21
        if ($format === 'date' ) {
            $match = preg_match('/^[\d]{4}-[\d]{2}-[\d]{2}$/', $value);
            return $match !== false && $match !== 0;
        }

        // the date-time notation as defined by RFC 3339, section 5.6, for example, 2017-07-21T17:32:28Z
        if ($format === 'date-time') {
            return DateTime::createFromFormat(DateTime::ISO8601, $value) !== false;
        }

        return true;
    }
}
