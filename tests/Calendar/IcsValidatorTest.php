<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Calendar;

use App\Calendar\IcsValidator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Calendar\IcsValidator
 */
class IcsValidatorTest extends TestCase
{
    private IcsValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new IcsValidator();
    }

    public function testIsValidIcsWithValidContent(): void
    {
        $validIcs = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\nUID:test123\r\nDTSTART:20231201T100000Z\r\nDTEND:20231201T110000Z\r\nSUMMARY:Test Event\r\nEND:VEVENT\r\nEND:VCALENDAR";
        
        $this->assertTrue($this->validator->isValidIcs($validIcs));
    }

    public function testIsValidIcsWithInvalidContent(): void
    {
        $invalidIcs = "This is not valid ICS content";
        
        $this->assertFalse($this->validator->isValidIcs($invalidIcs));
    }

    public function testIsValidIcsWithEmptyContent(): void
    {
        $this->assertFalse($this->validator->isValidIcs(''));
        $this->assertFalse($this->validator->isValidIcs('   '));
    }

    public function testIsValidIcsWithoutEvents(): void
    {
        $icsWithoutEvents = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nEND:VCALENDAR";
        
        $this->assertFalse($this->validator->isValidIcs($icsWithoutEvents));
    }

    public function testParseIcsEvents(): void
    {
        $validIcs = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\nUID:test123\r\nDTSTART:20231201T100000Z\r\nDTEND:20231201T110000Z\r\nSUMMARY:Test Event\r\nDESCRIPTION:Test Description\r\nLOCATION:Test Location\r\nEND:VEVENT\r\nEND:VCALENDAR";
        
        $events = $this->validator->parseIcsEvents($validIcs);
        
        $this->assertCount(1, $events);
        $this->assertEquals('Test Event', $events[0]['summary']);
        $this->assertEquals('Test Description', $events[0]['description']);
        $this->assertEquals('Test Location', $events[0]['location']);
        $this->assertEquals('test123', $events[0]['uid']);
        $this->assertInstanceOf(\DateTime::class, $events[0]['start']);
        $this->assertInstanceOf(\DateTime::class, $events[0]['end']);
    }

    public function testParseIcsEventsWithMultipleEvents(): void
    {
        $validIcs = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\nUID:test1\r\nDTSTART:20231201T100000Z\r\nDTEND:20231201T110000Z\r\nSUMMARY:Event 1\r\nEND:VEVENT\r\nBEGIN:VEVENT\r\nUID:test2\r\nDTSTART:20231202T100000Z\r\nDTEND:20231202T110000Z\r\nSUMMARY:Event 2\r\nEND:VEVENT\r\nEND:VCALENDAR";
        
        $events = $this->validator->parseIcsEvents($validIcs);
        
        $this->assertCount(2, $events);
        $this->assertEquals('Event 1', $events[0]['summary']);
        $this->assertEquals('Event 2', $events[1]['summary']);
    }

    public function testParseIcsEventsWithInvalidEvent(): void
    {
        $icsWithInvalidEvent = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\nSUMMARY:Invalid Event\r\nEND:VEVENT\r\nEND:VCALENDAR";
        
        $events = $this->validator->parseIcsEvents($icsWithInvalidEvent);
        
        $this->assertCount(0, $events);
    }

    public function testParseIcsEventsWithEscapedValues(): void
    {
        $validIcs = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\nUID:test123\r\nDTSTART:20231201T100000Z\r\nDTEND:20231201T110000Z\r\nSUMMARY:Test\\, Event with\\; semicolon\r\nDESCRIPTION:Line 1\\nLine 2\r\nEND:VEVENT\r\nEND:VCALENDAR";
        
        $events = $this->validator->parseIcsEvents($validIcs);
        
        $this->assertCount(1, $events);
        $this->assertEquals('Test, Event with; semicolon', $events[0]['summary']);
        $this->assertEquals("Line 1\nLine 2", $events[0]['description']);
    }
} 