<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Data;

use function array_keys;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingFileAnalyser;
use SebastianBergmann\CodeCoverage\TestCase;

final class RawCodeCoverageDataTest extends TestCase
{
    /**
     * In the standard XDebug format, there is only line data. Therefore output should match input.
     */
    public function testLineDataFromStandardXDebugFormat(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromXdebugWithoutPathCoverage($lineDataFromDriver);

        $this->assertEquals($lineDataFromDriver, $dataObject->lineCoverage());
    }

    /**
     * In the path-coverage XDebug format, the line data exists inside a "lines" array key.
     */
    public function testLineDataFromPathCoverageXDebugFormat(): void
    {
        $rawDataFromDriver = [
            '/some/path/SomeClass.php' => [
                'lines' => [
                    8  => 1,
                    9  => -2,
                    13 => -1,
                ],
                'functions' => [

                ],
            ],
            '/some/path/justAScript.php' => [
                'lines' => [
                    18  => 1,
                    19  => -2,
                    113 => -1,
                ],
                'functions' => [

                ],
            ],
        ];

        $lineData = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
            '/some/path/justAScript.php' => [
                18  => 1,
                19  => -2,
                113 => -1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromXdebugWithPathCoverage($rawDataFromDriver);

        $this->assertEquals($lineData, $dataObject->lineCoverage());
    }

    /**
     * In the path-coverage XDebug format for Xdebug < 2.9.6, the line data exists inside a "lines" array key where the
     * file has classes or functions. For files without them, the data is stored in the line-only format.
     */
    public function testLineDataFromMixedCoverageXDebugFormat(): void
    {
        $rawDataFromDriver = [
            '/some/path/SomeClass.php' => [
                'lines' => [
                    8  => 1,
                    9  => -2,
                    13 => -1,
                ],
                'functions' => [

                ],
            ],
            '/some/path/justAScript.php' => [
                18  => 1,
                19  => -2,
                113 => -1,
            ],
        ];

        $lineData = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
            '/some/path/justAScript.php' => [
                18  => 1,
                19  => -2,
                113 => -1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromXdebugWithMixedCoverage($rawDataFromDriver);

        $this->assertEquals($lineData, $dataObject->lineCoverage());
    }

    public function testClear(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromXdebugWithoutPathCoverage($lineDataFromDriver);

        $dataObject->clear();

        $this->assertEmpty($dataObject->lineCoverage());
    }

    public function testRemoveCoverageDataForFile(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
            '/some/path/SomeOtherClass.php' => [
                18  => 1,
                19  => -2,
                113 => -1,
            ],
            '/some/path/AnotherClass.php' => [
                28  => 1,
                29  => -2,
                213 => -1,
            ],
        ];

        $expectedFilterResult = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
            '/some/path/AnotherClass.php' => [
                28  => 1,
                29  => -2,
                213 => -1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromXdebugWithoutPathCoverage($lineDataFromDriver);

        $dataObject->removeCoverageDataForFile('/some/path/SomeOtherClass.php');

        $this->assertEquals($expectedFilterResult, $dataObject->lineCoverage());
    }

    public function testKeepCoverageDataOnlyForLines(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
            '/some/path/SomeOtherClass.php' => [
                18  => 1,
                19  => -2,
                113 => -1,
            ],
            '/some/path/AnotherClass.php' => [
                28  => 1,
                29  => -2,
                213 => -1,
            ],
        ];

        $expectedFilterResult = [
            '/some/path/SomeClass.php' => [
                9  => -2,
                13 => -1,
            ],
            '/some/path/SomeOtherClass.php' => [
            ],
            '/some/path/AnotherClass.php' => [
                28 => 1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromXdebugWithoutPathCoverage($lineDataFromDriver);

        $dataObject->keepLineCoverageDataOnlyForLines('/some/path/SomeClass.php', [9, 13]);
        $dataObject->keepLineCoverageDataOnlyForLines('/some/path/SomeOtherClass.php', [999]);
        $dataObject->keepLineCoverageDataOnlyForLines('/some/path/AnotherClass.php', [28]);

        $this->assertEquals($expectedFilterResult, $dataObject->lineCoverage());
    }

    public function testRemoveCoverageDataForLines(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
            '/some/path/SomeOtherClass.php' => [
                18  => 1,
                19  => -2,
                113 => -1,
            ],
            '/some/path/AnotherClass.php' => [
                28  => 1,
                29  => -2,
                213 => -1,
            ],
        ];

        $expectedFilterResult = [
            '/some/path/SomeClass.php' => [
                8 => 1,
            ],
            '/some/path/SomeOtherClass.php' => [
                18  => 1,
                19  => -2,
                113 => -1,
            ],
            '/some/path/AnotherClass.php' => [
                29  => -2,
                213 => -1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromXdebugWithoutPathCoverage($lineDataFromDriver);

        $dataObject->removeCoverageDataForLines('/some/path/SomeClass.php', [9, 13]);
        $dataObject->removeCoverageDataForLines('/some/path/SomeOtherClass.php', [999]);
        $dataObject->removeCoverageDataForLines('/some/path/AnotherClass.php', [28]);

        $this->assertEquals($expectedFilterResult, $dataObject->lineCoverage());
    }

    public function testUseStatementsAreUncovered(): void
    {
        $file = TEST_FILES_PATH . 'source_with_use_statements.php';

        $this->assertEquals(
            [
                12,
                14,
                16,
                18,
            ],
            array_keys(RawCodeCoverageData::fromUncoveredFile($file, new ParsingFileAnalyser(true, true))->lineCoverage()[$file])
        );
    }

    public function testEmptyClassesAreUncovered(): void
    {
        $file = TEST_FILES_PATH . 'source_with_empty_class.php';

        $this->assertEquals(
            [
                12,
            ],
            array_keys(RawCodeCoverageData::fromUncoveredFile($file, new ParsingFileAnalyser(true, true))->lineCoverage()[$file])
        );
    }

    public function testInterfacesAreUncovered(): void
    {
        $file = TEST_FILES_PATH . 'source_with_interface.php';

        $this->assertEquals(
            [
                7,
                9,
                11,
                13,
            ],
            array_keys(RawCodeCoverageData::fromUncoveredFile($file, new ParsingFileAnalyser(true, true))->lineCoverage()[$file])
        );
    }

    public function testInlineCommentsKeepTheLine(): void
    {
        $file = TEST_FILES_PATH . 'source_with_oneline_annotations.php';

        $this->assertEquals(
            [
                19,
                22,
                26,
                29,
                31,
                32,
                33,
                35,
            ],
            array_keys(RawCodeCoverageData::fromUncoveredFile($file, new ParsingFileAnalyser(true, true))->lineCoverage()[$file])
        );
    }

    public function testHeavyIndentationIsHandledCorrectly(): void
    {
        $file = TEST_FILES_PATH . 'source_with_heavy_indentation.php';

        $this->assertEquals(
            [
                9,
                12,
                16,
                18,
                19,
                24,
                25,
                28,
                31,
                40,
                46,
                48,
                54,
                60,
                71,
                79,
                80,
                81,
                83,
                85,
                88,
                // function argument, must be present 89,
                // function argument, must be present 92,
                // function argument, must be present 93,
                97,
                // array element, must be present 98,
                101,
                // function argument, must be present 102,
                // function argument, must be present 106,
                // function argument, must be present 108,
                111,
                120,
                122,
                124,
                126,
                128,
                130,
                132,
                134,
                136,
                138,
                160,
                165,
                169,
                172,
                176,
                /* assign->expr with start line != executable line, must NOT be present */178,
                180,
                /* expr? with start line != executable line, must NOT be present */184,
                186,
                190,
                196,
            ],
            array_keys(RawCodeCoverageData::fromUncoveredFile($file, new ParsingFileAnalyser(true, true))->lineCoverage()[$file])
        );
    }

    public function testEmtpyConstructorIsMarkedAsExecutable(): void
    {
        $file = TEST_FILES_PATH . 'source_with_empty_constructor.php';

        $this->assertEquals(
            [
                5,
                6,
                7,
                30,
            ],
            array_keys(RawCodeCoverageData::fromUncoveredFile($file, new ParsingFileAnalyser(true, true))->lineCoverage()[$file])
        );
    }

    /**
     * @requires PHP 8.0
     */
    public function testEachCaseInMatchExpressionIsMarkedAsExecutable(): void
    {
        $file = TEST_FILES_PATH . 'source_with_match_expression.php';

        $this->assertEquals(
            [
                14,
                20,
                25,
            ],
            array_keys(RawCodeCoverageData::fromUncoveredFile($file, new ParsingFileAnalyser(true, true))->lineCoverage()[$file])
        );
    }

    public function testReturnStatementWithOnlyAnArrayWithScalarReturnsTheFirstElementLine(): void
    {
        $file = TEST_FILES_PATH . 'source_with_return_and_array_with_scalars.php';

        $this->assertEquals(
            [
                8,
                15,
                24,
                30,
                40,
                47,
                54,
                63,
            ],
            array_keys(RawCodeCoverageData::fromUncoveredFile($file, new ParsingFileAnalyser(true, true))->lineCoverage()[$file])
        );
    }

    public function testReturnStatementWithConstantExprOnlyReturnTheLineOfLast(): void
    {
        $file = TEST_FILES_PATH . 'source_with_multiline_constant_return.php';

        $this->assertEquals(
            [
                10,
                19,
                28,
                37,
                46,
                55,
                64,
                73,
                82,
                91,
                100,
                109,
                118,
                127,
                136,
                145,
                154,
                163,
                172,
                181,
                190,
                199,
                208,
                217,
                226,
                235,
                244,
                252,
                261,
                269,
                278,
                293,
                304,
                314,
                321,
                323,
                324,
                325,
                327,
                340,
                351,
                370,
                377,
                390,
                402,
                414,
                425,
                434,
                439,
                441,
                456,
                466,
                478,
                489,
            ],
            array_keys(RawCodeCoverageData::fromUncoveredFile($file, new ParsingFileAnalyser(true, true))->lineCoverage()[$file])
        );
    }

    public function testCoverageForFileWithInlineAnnotations(): void
    {
        $filename = TEST_FILES_PATH . 'source_with_oneline_annotations.php';
        $coverage = RawCodeCoverageData::fromXdebugWithPathCoverage(
            [
                $filename => [
                    'lines' => [
                        13 => -1,
                        19 => -1,
                        22 => -1,
                        26 => -1,
                        29 => -1,
                        31 => -1,
                        32 => -1,
                        33 => -1,
                        35 => -1,
                        36 => -1,
                        37 => -1,
                    ],
                    'functions' => [
                        '{main}' => [
                            'branches' => [
                                0 => [
                                    'op_start'   => 0,
                                    'op_end'     => 0,
                                    'line_start' => 37,
                                    'line_end'   => 37,
                                    'hit'        => 0,
                                    'out'        => [
                                        0 => 2147483645,
                                    ],
                                    'out_hit' => [
                                        0 => 0,
                                    ],
                                ],
                            ],
                            'paths' => [
                                0 => [
                                    'path' => [
                                        0 => 0,
                                    ],
                                    'hit' => 0,
                                ],
                            ],
                        ],
                        'Foo->bar' => [
                            'branches' => [
                                0 => [
                                    'op_start'   => 0,
                                    'op_end'     => 2,
                                    'line_start' => 11,
                                    'line_end'   => 13,
                                    'hit'        => 0,
                                    'out'        => [
                                        0 => 2147483645,
                                    ],
                                    'out_hit' => [
                                        0 => 0,
                                    ],
                                ],
                            ],
                            'paths' => [
                                0 => [
                                    'path' => [
                                        0 => 0,
                                    ],
                                    'hit' => 0,
                                ],
                            ],
                        ],
                        'baz' => [
                            'branches' => [
                                0 => [
                                    'op_start'   => 0,
                                    'op_end'     => 18,
                                    'line_start' => 16,
                                    'line_end'   => 36,
                                    'hit'        => 0,
                                    'out'        => [
                                        0 => 2147483645,
                                    ],
                                    'out_hit' => [
                                        0 => 0,
                                    ],
                                ],
                            ],
                            'paths' => [
                                0 => [
                                    'path' => [
                                        0 => 0,
                                    ],
                                    'hit' => 0,
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $coverage->removeCoverageDataForLines(
            $filename,
            [
                29,
                31,
                32,
                33,
            ]
        );

        $this->assertEquals(
            [
                13 => -1,
                19 => -1,
                22 => -1,
                26 => -1,
                35 => -1,
                36 => -1,
            ],
            $coverage->lineCoverage()[$filename]
        );

        $this->assertEquals(
            [
                '{main}' => [
                    'branches' => [
                        0 => [
                            'op_start'   => 0,
                            'op_end'     => 0,
                            'line_start' => 37,
                            'line_end'   => 37,
                            'hit'        => 0,
                            'out'        => [
                                0 => 2147483645,
                            ],
                            'out_hit' => [
                                0 => 0,
                            ],
                        ],
                    ],
                    'paths' => [
                        0 => [
                            'path' => [
                                0 => 0,
                            ],
                            'hit' => 0,
                        ],
                    ],
                ],
                'Foo->bar' => [
                    'branches' => [
                        0 => [
                            'op_start'   => 0,
                            'op_end'     => 2,
                            'line_start' => 11,
                            'line_end'   => 13,
                            'hit'        => 0,
                            'out'        => [
                                0 => 2147483645,
                            ],
                            'out_hit' => [
                                0 => 0,
                            ],
                        ],
                    ],
                    'paths' => [
                        0 => [
                            'path' => [
                                0 => 0,
                            ],
                            'hit' => 0,
                        ],
                    ],
                ],
                'baz' => [
                    'branches' => [
                    ],
                    'paths' => [
                    ],
                ],
            ],
            $coverage->functionCoverage()[$filename]
        );
    }
}
