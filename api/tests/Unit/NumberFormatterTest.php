<?php

namespace Tests\Unit;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class NumberFormatterTest extends TestCase
{
    /**
     * @return void
     */
  public function testItCreatesAProperNumberAccordingToTheGivenFormat()
  {
      /** SETUP */
      $currentDate = Carbon::today();

      $format1 = 'MR[M]-[YYYY]XXXXX';
      $number1 = rand(10000, 99999);
      $expectedResult1 = 'MR' . $currentDate->month . '-' . $currentDate->year . $number1;

      $format2 = 'MR[M]-[YY]XXXXX';
      $number2 = rand(10000, 99999);
      $expectedResult2 = 'MR' . $currentDate->month . '-' . $currentDate->format('y') . $number2;

      $format3 = 'MR[M][YY]XXXXX';
      $number3 = rand(10000, 99999);
      $expectedResult3 = 'MR' . $currentDate->month . $currentDate->format('y') . $number3;

      $format4 = 'MR-XXXXX-[M][YY]';
      $number4 = rand(10000, 99999);
      $expectedResult4 = 'MR-' . $number4 . '-' . $currentDate->month . $currentDate->format('y');

      /** EXECUTE */
      $result1 = transformFormat($format1, $number1);
      $result2 = transformFormat($format2, $number2);
      $result3 = transformFormat($format3, $number3);
      $result4 = transformFormat($format4, $number4);

      /** ASSERT */
      $this->assertEquals($expectedResult1, $result1);
      $this->assertEquals($expectedResult2, $result2);
      $this->assertEquals($expectedResult3, $result3);
      $this->assertEquals($expectedResult4, $result4);
  }
}
