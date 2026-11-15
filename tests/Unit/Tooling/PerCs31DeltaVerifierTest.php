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

            $script = dirname(__DIR__, 3) . '/scripts/ci/check-per-cs-31-delta.php';
            [$exitCode, $output] = self::runCommand([PHP_BINARY, $script, $fixture]);
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

    public function testCompositeStyleCommandChecksSwitchCases(): void
    {
        $root = dirname(__DIR__, 3);
        $scratch = tempnam(sys_get_temp_dir(), 'category-style-composite-');
        self::assertNotFalse($scratch);
        self::assertTrue(unlink($scratch));
        self::assertTrue(mkdir($scratch));

        $directories = ['src', 'tests', 'scripts', 'scripts/ci'];
        $configuration = ['composer.json', '.php-cs-fixer.dist.php', 'scripts/ci/check-per-cs-31-delta.php'];
        $fixture = 'src/Fallthrough.php';
        try {
            foreach ($directories as $directory) {
                self::assertTrue(mkdir($scratch . '/' . $directory));
            }
            foreach ($configuration as $file) {
                self::assertTrue(copy($root . '/' . $file, $scratch . '/' . $file));
            }
            self::assertTrue(symlink($root . '/vendor', $scratch . '/vendor'));

            $source = <<<'FIXTURE'
                <?php

                declare(strict_types=1);

                switch ($value) {
                    case 1:
                        record($value);
                        // Falls through intentionally.
                    case 2:
                        record($value);
                        // Fall through to share the next case handler.
                    case 3:
                        record($value);
                        // Intentionally continue into the next case.
                    case 4:
                        echo "First: {$name}";
                        break;
                    default:
                        echo "Following: {$name}";
                        break;
                }
                FIXTURE;
            self::assertNotFalse(file_put_contents($scratch . '/' . $fixture, $source . "\n"));
            [$exitCode, $output] = self::runCommand(['composer', 'check:style'], $scratch);
            self::assertSame(0, $exitCode, $output);
            self::assertStringContainsString('PER-CS 3.1 delta verification passed', $output);

            $invalid = str_replace('Falls through intentionally.', 'Check whether this should fall through.', $source);
            self::assertNotFalse(file_put_contents($scratch . '/' . $fixture, $invalid . "\n"));
            [$exitCode, $output] = self::runCommand(['composer', 'check:style'], $scratch);
            self::assertSame(1, $exitCode, $output);
            self::assertStringContainsString('[switch-case-termination]', $output);
            self::assertSame($invalid . "\n", file_get_contents($scratch . '/' . $fixture));
        } finally {
            foreach ([$fixture, ...$configuration, 'vendor'] as $file) {
                if (file_exists($scratch . '/' . $file) || is_link($scratch . '/' . $file)) {
                    unlink($scratch . '/' . $file);
                }
            }
            foreach (array_reverse($directories) as $directory) {
                if (is_dir($scratch . '/' . $directory)) {
                    rmdir($scratch . '/' . $directory);
                }
            }
            rmdir($scratch);
        }
    }

    /**
     * @param non-empty-list<string> $command
     * @return array{int, string}
     */
    private static function runCommand(array $command, ?string $workingDirectory = null): array
    {
        $log = tmpfile();
        self::assertIsResource($log);
        try {
            $process = proc_open($command, [1 => $log, 2 => $log], $pipes, $workingDirectory);
            self::assertIsResource($process);
            $exitCode = proc_close($process);
            rewind($log);
            $output = stream_get_contents($log);
            self::assertIsString($output);

            return [$exitCode, $output];
        } finally {
            fclose($log);
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
            'Fall through to share the next case handler.' => null,
            'Intentionally continue into the next case.' => null,
            'Fall through to the next case?' => 'switch-case-termination',
            'This is not an intentional fall-through.' => 'switch-case-termination',
            'Check whether this should fall through.' => 'switch-case-termination',
        ];
        foreach ($fallthrough as $comment => $rule) {
            $body = 'record($value);' . "\n        // " . $comment;
            yield $comment => [str_replace(['CONDITION', 'BODY'], ['1', $body], $switch), $rule];
        }

        yield 'alternative switch syntax requires braces' => [<<<'FIXTURE'
            switch (choose($value)):
                case 1:
                    if ($flag) {
                        record($value);
                    }
                default:
                    break;
            endswitch;
            FIXTURE, 'switch-body'];

        yield 'interpolation cannot hide a nonterminating following case' => [<<<'FIXTURE'
            switch ($value) {
                case 1:
                    echo "First: {$name}";
                    break;
                default:
                    echo 'Missing termination';
            }
            FIXTURE, 'switch-case-termination'];

        yield 'dollar-brace interpolation still needs termination' => [<<<'FIXTURE'
            switch ($value) {
                default:
                    echo "Last: ${name}";
            }
            FIXTURE, 'switch-case-termination'];

        yield 'literal delimiter in interpolated case condition' => [<<<'FIXTURE'
            switch ($value) {
                case "({$name}":
                    echo "{{$name}";
                    break;
                default:
                    break;
            }
            FIXTURE, null];

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

        yield 'unkeyed multiline elements follow the PER-CS nested-array example' => [<<<'FIXTURE'
            $values = [
                [
                    1,
                    2,
                ],
                [
                    3,
                    4,
                ],
            ];
            FIXTURE, null];

        yield 'keyed nested arrays still start after the arrow' => [<<<'FIXTURE'
            $values = [
                'key' =>
                    [
                        1,
                        2,
                    ],
            ];
            FIXTURE, 'multiline-array-opening'];

        yield 'a function argument after a comma is not an unkeyed array item' => [<<<'FIXTURE'
            send(
                1,
                [
                    2,
                    3,
                ],
            );
            FIXTURE, 'multiline-array-opening'];

        yield 'array literal starts a function statement' => [<<<'FIXTURE'
            function values(): array
            {
                [
                    1,
                    2,
                ];

                return [];
            }
            FIXTURE, 'multiline-array-opening'];

        yield 'array literal after a semicolon' => [<<<'FIXTURE'
            record(1);
            [
                1,
                2,
            ];
            FIXTURE, 'multiline-array-opening'];

        yield 'unpacked array literal starts on a separate line' => [<<<'FIXTURE'
            return array_merge(...
                [
                    [1],
                    [2],
                ]);
            FIXTURE, 'multiline-array-opening'];

        yield 'array offsets and assignment destructuring are not literals' => [<<<'FIXTURE'
            record(1);
            [
                $one,
                $two,
            ] = $values;
            return $object->{$property}
            [
                $one + $two
            ];
            FIXTURE, null];

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
