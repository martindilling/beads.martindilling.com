<?php

namespace App\Patterns;

use Illuminate\Contracts\Support\Arrayable;

class Color implements Arrayable
{
    /** @var string */
    private $code;

    /** @var array */
    private $original;

    public function __construct(string $code, array $original = [])
    {
        $this->code = $code;
        $this->original = $original;
    }

    /**
     * @return \App\Patterns\Color
     */
    public static function null(): Color
    {
        return new static('', []);
    }

    /**
     * @param array $rgb
     * @return \App\Patterns\Color
     */
    public static function from(array $rgb): Color
    {
        return new static(self::nearest($rgb), $rgb);
    }

    public function isEmpty() : bool
    {
        return $this->code === '';
    }

    public function code(): string
    {
        return $this->code;
    }

    public function color(): array
    {
        return Palette::get($this->code);
    }

    public function __toString()
    {
        return $this->code();
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->color();
    }

    public static function nearest($givenColor)
    {
        // split into RGB, if not already done
        $givenColorRGB = is_array($givenColor) ? $givenColor : self::str2rgb($givenColor);
        $min = 0xffff;
        $return = null;

        foreach (Palette::all() as $key => $color) {
            // split into RGB
            $color = is_array($color) ? $color : self::str2rgb($color);
            // deltaE
            #if($min >= ($deltaE = self::deltaE(self::rgb2lab($color),self::rgb2lab($givenColorRGB))))
            // euclidean distance
            if ($min >= ($deltaE = self::colorDistance(self::rgb2lab($color), self::rgb2lab($givenColorRGB)))) {
                $min = $deltaE;
                $return = $key;
            }
        }

        return $return;
    }

    private static function rgb2lab($rgb)
    {
        $eps = 216 / 24389;
        $k = 24389 / 27;
        // reference white D50
        $xr = 0.964221;
        $yr = 1.0;
        $zr = 0.825211;
        // reference white D65
        #$xr = 0.95047; $yr = 1.0; $zr = 1.08883;

        // RGB to XYZ
        $rgb[0] = $rgb[0] / 255; //R 0..1
        $rgb[1] = $rgb[1] / 255; //G 0..1
        $rgb[2] = $rgb[2] / 255; //B 0..1

        // assuming sRGB (D65)
        $rgb[0] = ($rgb[0] <= 0.04045) ? ($rgb[0] / 12.92) : pow(($rgb[0] + 0.055) / 1.055, 2.4);
        $rgb[1] = ($rgb[1] <= 0.04045) ? ($rgb[1] / 12.92) : pow(($rgb[1] + 0.055) / 1.055, 2.4);
        $rgb[2] = ($rgb[2] <= 0.04045) ? ($rgb[2] / 12.92) : pow(($rgb[2] + 0.055) / 1.055, 2.4);

        // sRGB D50
        $x = 0.4360747 * $rgb[0] + 0.3850649 * $rgb[1] + 0.1430804 * $rgb[2];
        $y = 0.2225045 * $rgb[0] + 0.7168786 * $rgb[1] + 0.0606169 * $rgb[2];
        $z = 0.0139322 * $rgb[0] + 0.0971045 * $rgb[1] + 0.7141733 * $rgb[2];
        // sRGB D65
        /*$x =  0.412453*$rgb[0] + 0.357580*$rgb[1] + 0.180423*$rgb[2];
        $y =  0.212671*$rgb[0] + 0.715160*$rgb[1] + 0.072169*$rgb[2];
        $z =  0.019334*$rgb[0] + 0.119193*$rgb[1] + 0.950227*$rgb[2];*/

        // XYZ to Lab
        $xr = $x / $xr;
        $yr = $y / $yr;
        $zr = $z / $zr;

        $fx = ($xr > $eps) ? pow($xr, 1 / 3) : ($fx = ($k * $xr + 16) / 116);
        $fy = ($yr > $eps) ? pow($yr, 1 / 3) : ($fy = ($k * $yr + 16) / 116);
        $fz = ($zr > $eps) ? pow($zr, 1 / 3) : ($fz = ($k * $zr + 16) / 116);

        $lab = [];
        $lab[] = round((116 * $fy) - 16);
        $lab[] = round(500 * ($fx - $fy));
        $lab[] = round(200 * ($fy - $fz));

        return $lab;
    }

    private static function deltaE($lab1, $lab2)
    {
        // CMC 1:1
        $l = 1;
        $c = 1;

        $c1 = sqrt($lab1[1] * $lab1[1] + $lab1[2] * $lab1[2]);
        $c2 = sqrt($lab2[1] * $lab2[1] + $lab2[2] * $lab2[2]);

        $h1 = (((180000000 / M_PI) * atan2($lab1[1], $lab1[2]) + 360000000) % 360000000) / 1000000;

        $t = (164 <= $h1 AND $h1 <= 345) ? (0.56 + abs(0.2 * cos($h1 + 168))) : (0.36 + abs(0.4 * cos($h1 + 35)));
        $f = sqrt(pow($c1, 4) / (pow($c1, 4) + 1900));

        $sl = ($lab1[0] < 16) ? (0.511) : ((0.040975 * $lab1[0]) / (1 + 0.01765 * $lab1[0]));
        $sc = (0.0638 * $c1) / (1 + 0.0131 * $c1) + 0.638;
        $sh = $sc * ($f * $t + 1 - $f);

        return sqrt(pow(($lab1[0] - $lab2[0]) / ($l * $sl), 2) + pow(($c1 - $c2) / ($c * $sc),
                2) + pow(sqrt(($lab1[1] - $lab2[1]) * ($lab1[1] - $lab2[1]) + ($lab1[2] - $lab2[2]) * ($lab1[2] - $lab2[2]) + ($c1 - $c2) * ($c1 - $c2)) / $sh,
                2));
    }

    private static function colorDistance($lab1, $lab2)
    {
        return sqrt(($lab1[0] - $lab2[0]) * ($lab1[0] - $lab2[0]) + ($lab1[1] - $lab2[1]) * ($lab1[1] - $lab2[1]) + ($lab1[2] - $lab2[2]) * ($lab1[2] - $lab2[2]));
    }

    private static function str2rgb($str)
    {
        $str = preg_replace('~[^0-9a-f]~', '', $str);
        $rgb = str_split($str, 2);
        for ($i = 0; $i < 3; $i++) {
            $rgb[$i] = intval($rgb[$i], 16);
        }

        return $rgb;
    }
}
