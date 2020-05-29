<?php namespace Waka\Utils\Classes;

use Mexitek\PHPColors\Color;
use October\Rain\Support\Collection;

class PhpColors
{

    public $mainColor;
    public $secondaryColor;
    public $complementaryColor;
    public $colorArray;

    public function __construct($mainColor, $secondaryColor = null)
    {
        $this->mainColor = $mainColor;

        $color1 = new Color($mainColor);
        $this->complementaryColor = '#' . $color1->complementary();
        $complementaryColor = new Color($this->complementaryColor);
        $red = $color1->mix('#ff0000', 60);
        $blue = $color1->mix('#0000ff', 60);
        $yellow = $color1->mix('#0000FF', 60);
        $orange = $color1->mix('#FF6600', 60);
        $green = $color1->mix('#00FF00', 60);
        $purple = $color1->mix('#6600FF', 60);

        $this->colorArray = new Collection();

        $this->colorArray->push(['color' => $this->mainColor]);
        $this->colorArray->push(['color' => $this->complementaryColor]);
        $this->colorArray->push(['color' => '#' . $red]);
        $this->colorArray->push(['color' => '#' . $blue]);
        $this->colorArray->push(['color' => '#' . $yellow]);
        $this->colorArray->push(['color' => '#' . $orange]);
        $this->colorArray->push(['color' => '#' . $green]);
        $this->colorArray->push(['color' => '#' . $purple]);
        $this->colorArray->push(['color' => '#' . $color1->lighten(10)]);
        $this->colorArray->push(['color' => '#' . $color1->darken(10), 'darken' => true]);
        $this->colorArray->push(['color' => '#' . $complementaryColor->darken(10), 'darken' => true]);
        $this->colorArray->push(['color' => '#' . $complementaryColor->lighten(10)]);
        $this->colorArray->push(['color' => '#' . $color1->lighten(20), 'darken' => true]);
        $this->colorArray->push(['color' => '#' . $color1->darken(20), 'darken' => true]);
        $this->colorArray->push(['color' => '#' . $complementaryColor->darken(20), 'darken' => true]);
        $this->colorArray->push(['color' => '#' . $complementaryColor->lighten(20)]);
        $this->colorArray->push(['color' => '#' . $color1->lighten(30)]);
        $this->colorArray->push(['color' => '#' . $color1->darken(30), 'darken' => true]);
        $this->colorArray->push(['color' => '#' . $complementaryColor->darken(30), 'darken' => true]);
        $this->colorArray->push(['color' => '#' . $complementaryColor->lighten(30)]);

    }

    public function getColors($level = 0)
    {
        $array = $this->colorArray->all();
        return $array[$level];

    }

    public function getColorsArray($num)
    {
        $chunk = $this->colorArray->where('darken', '<>', true)->take($num);
        return $chunk->pluck('color');

    }
    public function getRandomColorsArray($num)
    {
        $random = $this->colorArray->random(3);
        return $random->all();

    }

}
