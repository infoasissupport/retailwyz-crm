<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case HalfDay = 'half_day';
    case Absent = 'absent';
    case Leave = 'leave';
    case Holiday = 'holiday';

    public function label(): string
    {
        return $this === self::HalfDay ? 'Half day' : ucfirst($this->value);
    }

    /** One-letter code for the monthly register. */
    public function code(): string
    {
        return match ($this) {
            self::Present => 'P',
            self::HalfDay => 'H',
            self::Absent => 'A',
            self::Leave => 'L',
            self::Holiday => 'O',
        };
    }

    /** Working-day credit: present 1, half day 0.5. */
    public function credit(): float
    {
        return match ($this) {
            self::Present => 1.0,
            self::HalfDay => 0.5,
            default => 0.0,
        };
    }
}
