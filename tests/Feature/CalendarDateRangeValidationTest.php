<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarDateRangeValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_calendar_accepts_the_range_sent_by_the_front(): void
    {
        $this->getJson('/api/events/calendar?start=2026-09-20T03:00:00.000Z&end=2026-11-07T03:00:00.000Z')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_calendar_accepts_no_range(): void
    {
        $this->getJson('/api/events/calendar')->assertOk();
    }

    /**
     * @dataProvider invalidCalendarRanges
     */
    public function test_calendar_rejects_invalid_range(string $query, string $field): void
    {
        $this->getJson('/api/events/calendar?' . $query)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public static function invalidCalendarRanges(): array
    {
        return [
            'start invalido' => ['start=abc&end=2026-10-31', 'start'],
            'end invalido' => ['start=2026-10-01&end=2026-13-45', 'end'],
            'so start' => ['start=2026-10-01', 'end'],
            'so end' => ['end=2026-10-31', 'start'],
            'end antes do start' => ['start=2026-10-31&end=2026-10-01', 'end'],
            'periodo longo demais' => ['start=2026-01-01&end=2026-12-31', 'end'],
        ];
    }

    public function test_blocked_dates_accepts_valid_range(): void
    {
        $this->getJson('/api/blocked-dates?start=2026-09-20&end=2026-11-07&per_page=500')->assertOk();
    }

    /**
     * @dataProvider invalidBlockedDateRanges
     */
    public function test_blocked_dates_rejects_invalid_range(string $query, string $field): void
    {
        $this->getJson('/api/blocked-dates?' . $query)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);
    }

    public static function invalidBlockedDateRanges(): array
    {
        return [
            'start invalido' => ['start=abc', 'start'],
            'end invalido' => ['end=2026-02-30', 'end'],
            'end antes do start' => ['start=2026-10-31&end=2026-10-01', 'end'],
        ];
    }
}
