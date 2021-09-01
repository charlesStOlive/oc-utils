<?php namespace Waka\Utils\Classes;

use Mexitek\PHPColors\Color;
use Winter\Storm\Support\Collection;

class PhpColors
{

    public static $primary;
    public static $secondary;
    public static $complementary;
    public static $gd;
    public static $gl;

    public static function start()
    {
        //trace_log("start");
        $configColors = \Config::get('wcli.wconfig::brand_data');
        $primary = $configColors['primaryColor'];
        $color1 = new Color($primary);
        self::$primary = $primary;
        self::$complementary = "#" . $color1->complementary();
        self::$secondary = $configColors['secondaryColor'];
        self::$gd = $configColors['gd'];
        self::$gl = $configColors['gl'];
    }

    public static function startFromColor($newColor, $secondary = null, $gd = null, $gl = null)
    {
        $primary = $newColor;
        //trace_log($primary);
        $color1 = new Color($primary);
        self::$primary = $primary;
        self::$complementary = "#" . $color1->complementary();
        if ($secondary) {
            self::$secondary = $secondary;
        } else {
            self::$secondary = self::$complementary;
        }

        if ($gd) {
            self::$gd = $gd;
        } else {
            self::$gd = '#363636';
        }

        if ($gl) {
            self::$gl = $gl;
        } else {
            self::$gl = '#CDCDCD';
        }
    }

    public static function getDegrade($qty, $chosenColor = "primary", $newColor = null, $newSecondaryColor = null)
    {
        if (!$newColor) {
            self::start();
        } else {
            self::startFromColor($newColor, $newSecondaryColor);
        }
        $colorArray = new Collection();
        $color;
        if ($chosenColor == "primary") {
            $color = new Color(self::$primary);
            $colorArray->push(['color' => self::$primary]);
        }
        if ($chosenColor == "secondary") {
            $color = new Color(self::$secondary);
            $colorArray->push(['color' => self::$secondary]);
        }
        if ($chosenColor == "complementary") {
            $color = new Color(self::$complementary);
            $colorArray->push(['color' => self::$complementary]);
        }
        if ($chosenColor == "gd") {
            $color = new Color(self::$gd);
            $colorArray->push(['color' => self::$gd]);
        }
        if ($chosenColor == "gl") {
            $color = new Color(self::$gl);
            $colorArray->push(['color' => self::$gl]);
        }

        $lightPossibility = $color->getHsl();

        if ($color->isLight()) {
            //trace_log('light');
            $lightLevel = $lightPossibility['L'];
            //trace_log($lightLevel);
            $step = round(($lightLevel) / $qty * 100);
            //trace_log('step : ' . $step);
            for ($i = 1; $i <= $qty; $i++) {
                $var = $i * $step;
                $colorArray->push(['color' => '#' . $color->darken($var)]);
            }
        } else {
            //trace_log('dark');
            $lightLevel = $lightPossibility['L'];
            $step = round((0.9 - $lightLevel) / $qty * 100);
            //trace_log('step : ' . $step);
            for ($i = 1; $i <= $qty; $i++) {
                $var = $i * $step;
                $colorArray->push(['color' => '#' . $color->lighten($var)]);
                // $tempColor = new Color($color->lighten($var));
                // trace_log($tempColor->getHsl());
            }
        }
        return $colorArray->pluck('color')->toArray();
    }
    public static function getSeparate($qty, $newColor = null, $newSecondaryColor = null)
    {
        if (!$newColor) {
            self::start();
        } else {
            self::startFromColor($newColor, $newSecondaryColor);
        }

        $colorArray = new Collection();
        $primary = new Color(self::$primary);
        $colorArray->push(['color' => self::$primary]);

        if (self::$secondary != self::$complementary) {
            $secondary = new Color(self::$secondary);
            $colorArray->push(['color' => self::$secondary]);
            $secondaryComplementary = "#" . $secondary->complementary();
            $colorArray->push(['color' => $secondaryComplementary]);
        }

        $complementary = new Color(self::$complementary);
        $colorArray->push(['color' => self::$complementary]);

        $colorArray->push(['color' => self::$gd]);
        $colorArray->push(['color' => self::$gl]);

        $glColor = new Color(self::$gl);

        if ($qty > 3) {
            $red = "#" . $glColor->mix('#ff0000', 25);
            $blue = "#" . $glColor->mix('#0000ff', 25);
            $yellow = "#" . $glColor->mix('#FFFF00', 25);
            $orange = "#" . $glColor->mix('#FF6600', 25);
            $green = "#" . $glColor->mix('#00FF00', 25);
            $purple = "#" . $glColor->mix('#6600FF', 25);

            $colorArray->push(['color' => $red]);
            $colorArray->push(['color' => $blue]);
            $colorArray->push(['color' => $yellow]);
            $colorArray->push(['color' => $orange]);
            $colorArray->push(['color' => $green]);
            $colorArray->push(['color' => $purple]);
        }

        if ($qty > 9) {
            $ncolor = new Color($red);
            $c_red = "#" . $ncolor->darken(20);

            $ncolor = new Color($blue);
            $c_blue = "#" . $ncolor->darken(20);

            $ncolor = new Color($yellow);
            $c_yellow = "#" . $ncolor->darken(20);

            $ncolor = new Color($orange);
            $c_orange = "#" . $ncolor->darken(20);

            $ncolor = new Color($green);
            $c_green = "#" . $ncolor->darken(20);

            $ncolor = new Color($purple);
            $c_purple = "#" . $ncolor->darken(20);

            $colorArray->push(['color' => $c_red]);
            $colorArray->push(['color' => $c_blue]);
            $colorArray->push(['color' => $c_yellow]);
            $colorArray->push(['color' => $c_orange]);
            $colorArray->push(['color' => $c_green]);
            $colorArray->push(['color' => $c_purple]);
        }

        return $colorArray->pluck('color')->take($qty)->toArray();
    }
}
