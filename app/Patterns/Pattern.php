<?php

namespace App\Patterns;

use App\Patterns\Color;
use Intervention\Image\Image;

class Pattern
{
    /** @var Image */
    private $image;

    /** @var int */
    private $width;

    /** @var int */
    private $height;

    /** @var array|Color[][] */
    private $grid = [];

    /** @var array */
    private $usage = [];

    public function __construct(Image $image)
    {
        // Crop out any surrounding transparent areas
        $image->trim('transparent', null, 0, 0);
        $image->flip();

        $this->image = $image;
        $this->width = $image->width();
        $this->height = $image->height();

        foreach (range(0, $image->width() - 1) as $x) {
            foreach (range(0, $image->height() - 1) as $y) {
                $rgb = $image->pickColor($x, $y);
                if ($rgb[3] === 0.0) {
                    $this->grid[$x][$y] = Color::null();
                    continue;
                }
                $color = Color::from($rgb);
                $this->grid[$x][$y] = $color;

                if (!isset($this->usage[$color->code()])) {
                    $this->usage[$color->code()] = 0;
                }
                $this->usage[$color->code()]++;
            }
        }

        asort($this->usage);
    }

    public function image() : Image
    {
        return $this->image;
    }

    public function width() : int
    {
        return $this->width;
    }

    public function height() : int
    {
        return $this->height;
    }

    /**
     * @return array|Color[][]
     */
    public function grid() : array
    {
        return $this->grid;
    }

    public function usage() : array
    {
        return $this->usage;
    }
}
