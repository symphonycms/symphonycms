<?php

/**
 * @package core
 */
/**
 * The DateRangeParser provides functions to parse date fragments.
 * @since Symphony 3.0.0
 */
class DateRangeParser
{
    /**
     * The raw date to parse
     * @var string
     */
    private $date;

    /**
     * Ranges should include limits
     * @var boolean
     */
    private $includeLimits = false;

    /**
     * The range direction
     * @var string
     */
    private $direction;

    /**
     * The complete date format to use for returned dates
     * @var string
     */
    private $format = 'Y-m-d H:i:s';

    /**
     * Creates a new parser object with the raw date string to parse
     *
     * @param string $date
     */
    public function __construct($date)
    {
        General::ensureType([
            'date' => ['var' => $date, 'type' => 'string'],
        ]);
        $this->date = $date;
    }

    /**
     * Sets the include limits directive to true
     *
     * @return DateRangeParser
     *  The current instance
     */
    public function includeLimits()
    {
        $this->includeLimits = true;
        return $this;
    }

    /**
     * Sets open ranges direction.
     *
     * @param string $direction
     *  The direction, either 'later' or 'earlier'.
     * @return DateRangeParser
     *  The current instance
     * @throws Exception
     */
    public function direction($direction)
    {
        General::ensureType([
            'direction' => ['var' => $direction, 'type' => 'string'],
        ]);
        $this->direction = $direction;
        return $this;
    }

    /**
     * Given a string, this function builds the range of dates that match it, based
     * on the values set on the parser.
     * The dates in the string must be in ISO8601 style format, or a natural date, such
     * as 'last week', 'today', etc.
     * The string can also contain proper ranges closed range, like 'from 2017 to 2018',
     * and open ranges, like 'earlier than 2018-03'.
     *
     * @return array
     *  An associative array containing a date in ISO8601 format (or natural)
     *  with two keys, start and end.
     *  For opened ranges, either start or end will be null.
     */
    public function parse()
    {
        $matches = [];
        $parts = [
            'start' => null,
            'end' => null,
            'strict' => false,
        ];

        // Opened range
        if (preg_match('/^(equal to or )?(earlier|later) than (.*)$/i', $this->date, $matches)) {
            $this->includeLimits = !empty($matches[1]);
            $this->direction = $matches[2];
            $dp = (new DateRangeParser($matches[3]));
            if ($this->direction) {
                $dp->direction($this->direction);
            }
            if ($this->includeLimits) {
                $dp->includeLimits();
            }
            $date = $dp->parse();
            if ($this->direction === 'later') {
                $parts['start'] = $date['end'];
            } else {
                $parts['end'] = $date['start'];
            }
            $parts['strict'] = !$this->includeLimits;
        // Closed range
        } elseif (preg_match('/^(from )?(.+)\s+to\s+(.+)$/i', $this->date, $matches)) {
            $date1 = (new DateRangeParser($matches[2]))->includeLimits()->parse();
            $date2 = (new DateRangeParser($matches[3]))->includeLimits()->parse();
            $parts['start'] = $date1['start'];
            $parts['end'] = $date2['end'];
        // Year
        } elseif (preg_match('/^\d{1,4}$/', $this->date, $matches)) {
            $year = current($matches);

            $parts['start'] = "$year-01-01 00:00:00";
            $parts['end'] = "$year-12-31 23:59:59";

            $parts = $this->expandRange($parts);

        // Year/Month/Day/Time
        } elseif (preg_match('/^\d{1,4}[-\/]\d{1,2}[-\/]\d{1,2}\s\d{1,2}:\d{2}/', $this->date, $matches)) {
            // Handles the case of `to` filters
            if ($this->includeLimits || !$this->direction) {
                $parts['start'] = $parts['end'] = DateTimeObj::get($this->format, $this->date);
            } else {
                $parts['start'] = DateTimeObj::get($this->format, $this->date . ' - 1 second');
                $parts['end'] = DateTimeObj::get($this->format, $this->date . ' + 1 second');
            }

        // Year/Month/Day
        } elseif (preg_match('/^\d{1,4}[-\/]\d{1,2}[-\/]\d{1,2}$/', $this->date, $matches)) {
            $year_month_day = current($matches);

            $parts['start'] = "$year_month_day 00:00:00";
            $parts['end'] = "$year_month_day 23:59:59";

            $parts = $this->expandRange($parts);

        // Year/Month
        } elseif (preg_match('/^\d{1,4}[-\/]\d{1,2}$/', $this->date, $matches)) {
            $year_month = current($matches);

            $parts['start'] = "$year_month-01 00:00:00";
            $parts['end'] = DateTimeObj::get('Y-m-t', $parts['start']) . " 23:59:59";

            $parts = $this->expandRange($parts);

        // Natural date, aka '+ 3 weeks'
        } else {
            // Handles the case of `to` filters
            if ($this->includeLimits || !$this->direction) {
                $parts['start'] = $parts['end'] = DateTimeObj::get($this->format, $this->date);
            } else {
                $parts['start'] = DateTimeObj::get($this->format, $this->date . ' - 1 second');
                $parts['end'] = DateTimeObj::get($this->format, $this->date . ' + 1 second');
            }
        }

        return $parts;
    }

    /**
     * Expands the date array depending if the filter should include
     * the limits. 'later than 2011', is effectively the same as
     * 'equal to or later than 2012'.
     * It expands the end if the direction is later, the start if the direction is earlier.
     *
     * @see includeLimits()
     * @see direction()
     * @param array $parts
     *  An associative array containing a date in ISO8601 format (or natural)
     *  with two keys, start and end.
     * @return array
     *  The modified $parts array
     */
    public function expandRange(array $parts)
    {
        if (!$this->includeLimits) {
            return $parts;
        }

        if ($this->direction === 'later') {
            $parts['end'] = $parts['start'];
        } elseif ($this->direction === 'earlier') {
            $parts['start'] = $parts['end'];
        }

        return $parts;
    }
}
