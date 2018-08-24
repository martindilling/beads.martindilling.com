<?php

namespace App\Patterns;

use Intervention\Image\Image;
use Intervention\Image\AbstractFont;
use Intervention\Image\AbstractShape;

class PatternRenderer
{
    /** @var \App\Patterns\Pattern */
    private $pattern;

    /** @var int */
    private $multiplier;

    /** @var string */
    private $label;

    /** @var Image[] */
    private $cache = [];

    public function __construct(Pattern $pattern, int $multiplier, string $label = null)
    {
        $this->pattern = $pattern;
        $this->multiplier = $multiplier;
        $this->label = $label;
    }

    public function render() : Image
    {
        // Create new image
        $image = \Image::canvas($this->width(), $this->height(), '#fff');

        // Loop the entire grid, draw what needs to be drawn
        foreach (range(0, $this->pattern->width() - 1) as $x) {
            foreach (range(0, $this->pattern->height() - 1) as $y) {
                $this->drawReferenceNumbers($image, $x, $y);

                $color = $this->pattern->grid()[$x][$y] ?? Color::null();
                if ($color->isEmpty()) {
                    $this->drawBlank(
                        $image,
                        $this->calcX($x),
                        $this->calcY($y),
                        $this->referenceColor($x + 1, $y + 1)
                    );
                    continue;
                }

                $this->drawBead($image, $color, $this->calcX($x), $this->calcY($y));
            }
        }

        $this->drawInformationSide($image);

        $this->drawCreditLine($image, 'Generator made by Martin Dilling-Hansen');

        return $image;
    }

    public function pngResponse()
    {
        return $this->render()->response('png');
    }

    private function dotSize(): int
    {
        return $this->multiplier - 1;
    }

    private function numberRowSize(): int
    {
        return $this->multiplier - 1;
    }

    private function width(): int
    {
        return ($this->pattern->width() * $this->multiplier) + $this->dotSize() + $this->numberRowSize();
    }

    private function height(): int
    {
        return ($this->pattern->height() * $this->multiplier) + $this->dotSize() + $this->numberRowSize();
    }

    private function calcX(int $x): int
    {
        return ($x * $this->multiplier) + $this->dotSize() + $this->numberRowSize();
    }

    private function calcY(int $y): int
    {
        return ($y * $this->multiplier) + $this->dotSize() + $this->numberRowSize();
    }

    private function isGuide(int $value): bool
    {
        return $value % 50 == 0;
    }

    private function referenceColor(int $x, int $y): array
    {
        return $this->isGuide($x) || $this->isGuide($y)
            ? [210, 0, 0, 0.8]
            : [0, 0, 0, 0.4];
    }

    private function fontSize()
    {
        return $this->multiplier / 2;
    }

    private function drawBlank(Image $image, int $x, int $y, array $color)
    {
        $cacheName = 'blank_' . implode($color);
        $size = ($this->dotSize() / 4) * 2;

        // If missing from cache, generate it
        if (!isset($this->cache[$cacheName])) {
            $new = \Image::canvas($size, $size);

            $new->circle(
                $this->dotSize() / 4,
                $size / 2,
                $size / 2,
                function (AbstractShape $draw) use ($color) {
                    $draw->background($color);
                }
            );

            // Store in cache
            $this->cache[$cacheName] = $new;
        }

        $image->insert($this->cache[$cacheName], 'top-left', round($x - $size / 2), round($y - $size / 2));
    }

    private function drawBead(Image $image, Color $color, int $x, int $y)
    {
        $size = $this->dotSize() * 2;

        // If missing from cache, generate it
        if (!isset($this->cache[$color->code()])) {
            $new = \Image::canvas($size, $size);

            // Draw the circle with a border
            $new->circle(
                $this->dotSize(),
                $size / 2,
                $size / 2,
                $this->shapeBead($color)
            );

            // Draw the color code text on the circle
            $new->text(
                ltrim($color->code(), 'C'),
                $size / 2,
                $size / 2,
                $this->fontBead($color)
            );

            // Store in cache
            $this->cache[$color->code()] = $new;
        }

        $image->insert($this->cache[$color->code()], 'top-left', round($x - $size / 2), round($y - $size / 2));
    }

    private function drawReferenceNumbers(Image $image, int $x, int $y)
    {
        // Draw column numbers
        if ($y === 0 && ($x + 1) % 2 == 0) {
            $color = $this->referenceColor($x + 1, $y + 1);
            $image->text(
                $x + 1,
                $this->calcX($x),
                $this->calcY($y) - $this->numberRowSize(),
                $this->fontReference($color)
            );
        }

        // Draw row numbers
        if ($x === 0 && ($y + 1) % 2 == 0) {
            $color = $this->referenceColor($x + 1, $y + 1);
            $image->text(
                $y + 1,
                $this->calcX($x) - $this->numberRowSize(),
                $this->calcY($y),
                $this->fontReference($color)
            );
        }
    }

    private function drawCreditLine(Image $image, string $text)
    {
        $image->text(
            $text,
            $image->width() - 4,
            $image->height() - 4,
            $this->fontReference([0, 0, 0, 0.4], 'right', 'bottom')
        );
    }

    private function drawInformationSide(Image $image)
    {
        // Preview image
        $previewImage = $this->generatePreviewImage();

        // Usage image
        $usageImage = $this->generateUsageImage($image->height() - $previewImage->height());

        // Get information area width
        $width = max($previewImage->width(), $usageImage->width());

        // Make sure both sub images are wide enough
        $previewImage->resizeCanvas($width, 0, 'top', false, 'fff');
        $usageImage->resizeCanvas($width, 0, 'left', false, 'fff');

        // Add left information area
        $image->resizeCanvas($width, 0, 'right', true, 'fff');

        // Insert our generated sections
        $image->insert($previewImage, 'top-left', 0, 0);
        $image->insert($usageImage, 'top-left', 0, $previewImage->height());

        // Draw preview divider line
        $image->line(0, $previewImage->height(), $width, $previewImage->height(), function (AbstractShape $draw) {
            $draw->color('#aaa');
            $draw->width(2);
        });

        // Draw divider line
        $image->line($width, 0, $width, $image->height(), function (AbstractShape $draw) {
            $draw->color('#aaa');
            $draw->width(2);
        });
    }

    private function generatePreviewImage(): Image
    {
        $preview = $this->pattern->image();
        $preview->resize($preview->width() * 2, $preview->height() * 2);
        $preview->flip();
        $top = 30;
        $lines = 2 + ($this->label ? 1 : 0);

        $previewImage = \Image::canvas(
            $preview->width() * 2,
            ($top * 2) + $preview->height() + (($this->fontSize() + 6) * $lines),
            'fff'
        );

        // Draw preview
        $previewImage->insert(
            $preview,
            'top-left',
            round(($previewImage->width() / 2) - ($preview->width() / 2)),
            $top
        );
        $top += $preview->height() + 18;

        if ($this->label) {
            // Draw size text
            $previewImage->text(
                $this->label,
                $previewImage->width() / 2,
                $top,
                $this->fontNormal('center')
            );
            $top += 18;
        }

        // Draw size text
        $previewImage->text(
            $this->pattern->width() . ' x ' . $this->pattern->height(),
            $previewImage->width() / 2,
            $top,
            $this->fontNormal('center')
        );
        $top += 18;

        // Draw bead count
        $previewImage->text(
            array_sum($this->pattern->usage()) . ' beads',
            $previewImage->width() / 2,
            $top,
            $this->fontNormal('center')
        );

        return $previewImage;
    }

    private function generateUsageImage(int $height): Image
    {
        $usageImage = \Image::canvas(90, $height, 'fff');
        $top = $this->dotSize() / 2 + 15;
        $left = 15;

        // Draw usage information
        foreach ($this->pattern->usage() as $code => $count) {
            if ($top > $usageImage->height()) {
                $top = $this->dotSize() / 2 + 15;
                $left += 90;
                $usageImage->resizeCanvas(90, 0, 'left', true, 'fff');
            }

            $color = new Color($code);
            $this->drawBead($usageImage, $color, $left + ($this->dotSize() / 1.5), $top);

            $usageImage->text(
                'x ' . $count,
                $left + ($this->dotSize() * 1.5),
                $top + ($this->dotSize() / 6),
                $this->fontNormal()
            );

            $top += $this->dotSize() + 5;
        }

        return $usageImage;
    }

    private function shapeBead(Color $color)
    {
        return function (AbstractShape $draw) use ($color) {
            $draw->background($color->color());
            $draw->border(1, [0, 0, 0, 0.4]);
        };
    }

    private function fontReference(array $color, string $align = 'center', string $valign = 'middle')
    {
        return function (AbstractFont $font) use ($color, $align, $valign) {
            $font->file(resource_path('assets/fonts/open-sans/OpenSans-Regular.ttf'));
            $font->size($this->multiplier / 2.5);
            $font->color($color);
            $font->align($align);
            $font->valign($valign);
        };
    }

    private function fontBead(Color $color)
    {
        return function (AbstractFont $font) use ($color) {
            $font->file(resource_path('assets/fonts/open-sans/OpenSans-Regular.ttf'));
            $font->size($this->multiplier / 2.5);
            $font->color($this->getContrastColor($color->color()));
            $font->align('center');
            $font->valign('middle');
        };
    }

    private function fontNormal(string $align = 'left')
    {
        return function (AbstractFont $font) use ($align) {
            $font->file(resource_path('assets/fonts/open-sans/OpenSans-Regular.ttf'));
            $font->size($this->fontSize());
            $font->color('#000');
            $font->align($align);
        };
    }

    private function getContrastColor($color)
    {
        list($r, $g, $b) = $color;
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return ($yiq >= 128)
            ? [0, 0, 0, 0.75]
            : [255, 255, 255, 0.75];
    }

}
