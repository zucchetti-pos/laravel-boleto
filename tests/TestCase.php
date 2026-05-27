<?php

namespace Eduardokum\LaravelBoleto\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\Constraint\StringContains;

class TestCase extends BaseTestCase
{
    public static function assertIsArray($actual, string $message = ''): void
    {
        if (method_exists(BaseTestCase::class, 'assertIsArray')) {
            parent::assertIsArray($actual, $message);
        } else {
            static::assertThat(
                $actual,
                new IsType(IsType::TYPE_ARRAY),
                $message
            );
        }
    }

    public static function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
    {
        if (method_exists(BaseTestCase::class, 'assertStringContainsString')) {
            parent::assertStringContainsString($needle, $haystack, $message);
        } else {
            $constraint = new StringContains($needle, false);
            static::assertThat($haystack, $constraint, $message);
        }
    }
}
