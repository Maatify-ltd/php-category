<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Unit\Tooling;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PerCs31DeltaVerifierTest extends TestCase
{
    #[DataProvider('fixtureProvider')]
    public function testVerifierAcceptsCompliantPhpAndReportsViolations(string $source, ?string $rule): void
    {
        $fixture = tempnam(sys_get_temp_dir(), 'category-style-');
        self::assertNotFalse($fixture);

        try {
            self::assertNotFalse(file_put_contents($fixture, "<?php\n\n" . $source . "\n"));

            $log = tmpfile();
            self::assertIsResource($log);
            try {
                $script = dirname(__DIR__, 3) . '/scripts/ci/check-per-cs-31-delta.php';
                $process = proc_open([PHP_BINARY, $script, $fixture], [1 => $log, 2 => $log], $pipes);
                self::assertIsResource($process);
                $exitCode = proc_close($process);
                rewind($log);
                $output = stream_get_contents($log);
            } finally {
                fclose($log);
            }

            self::assertIsString($output);
            if ($rule === null) {
                self::assertSame(0, $exitCode, $output);
                self::assertStringContainsString('verification passed', $output);
            } else {
                self::assertSame(1, $exitCode, $output);
                self::assertStringContainsString('[' . $rule . ']', $output);
            }
        } finally {
            unlink($fixture);
        }
    }

    /** @return iterable<string, array{string, ?string}> */
    public static function fixtureProvider(): iterable
    {
        $switch = <<<'FIXTURE'
            switch ($value) {
                case CONDITION:
                    BODY
                default:
                    break;
            }
            FIXTURE;

        $fallthrough = [
            'No break' => null,
            'Deliberate fall-through.' => null,
            'Falls through intentionally.' => null,
            'Intentional fallthrough: share the next case.' => null,
            'This is not an intentional fall-through.' => 'switch-case-termination',
            'Check whether this should fall through.' => 'switch-case-termination',
        ];
        foreach ($fallthrough as $comment => $rule) {
            $body = 'record($value);' . "\n        // " . $comment;
            yield $comment => [str_replace(['CONDITION', 'BODY'], ['1', $body], $switch), $rule];
        }

        yield 'a final case still needs termination' => [<<<'FIXTURE'
            switch ($value) {
                default:
                    record($value);
                    // Falls through intentionally.
            }
            FIXTURE, 'switch-case-termination'];

        $if = <<<'FIXTURE'
            if ($flag) {
                record($value);
            }
            FIXTURE;
        $if = str_replace("\n", "\n        ", $if);
        yield 'an earlier top-level comment is sufficient' => [
            str_replace(['CONDITION', 'BODY'], ['1', "/* Falls through intentionally. */\n        " . $if], $switch),
            null,
        ];
        yield 'a nested comment cannot describe the enclosing case' => [
            str_replace(
                ['CONDITION', 'BODY'],
                ['1', str_replace('record($value);', "record(\$value);\n            // No break", $if)],
                $switch,
            ),
            'switch-case-termination',
        ];
        yield 'outer break after if' => [str_replace(['CONDITION', 'BODY'], ['1', $if . "\n        break;"], $switch), null];
        yield 'nested-only break' => [
            str_replace(['CONDITION', 'BODY'], ['1', str_replace('record($value);', 'break;', $if)], $switch),
            'switch-case-termination',
        ];
        foreach (['$flag ? 1 : 2', '$value ?: 1', '$flag ? ($value ?: 1) : 2', '$object?->value ?? 2'] as $expression) {
            yield $expression => [str_replace(['CONDITION', 'BODY'], [$expression, 'break;'], $switch), null];
        }
        yield 'ternary case still needs termination' => [
            str_replace(['CONDITION', 'BODY'], ['$flag ? 1 : 2', 'record($value);'], $switch),
            'switch-case-termination',
        ];

        $generator = <<<'FIXTURE'
            function values(): Generator
            {
                yield from [
                    1,
                    2,
                ];
            }
            FIXTURE;
        yield 'yield from on the same line as its opening bracket' => [$generator, null];
        yield 'yield from with its opening bracket alone' => [
            str_replace('yield from [', "yield from\n        [", $generator),
            'multiline-array-opening',
        ];
    }
}
