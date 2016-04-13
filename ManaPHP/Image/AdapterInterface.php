<?php

namespace ManaPHP\Image {

    interface AdapterInterface
    {
        /**
         * Image width
         *
         * @return int
         */
        public function getWidth();

        /**
         * Image height
         *
         * @return int
         */
        public function getHeight();

        public function getInternalHandle();

        /**
         * @param int $width
         * @param int $height
         * @param int $offsetX
         * @param int $offsetY
         *
         * @return static
         */
        public function crop($width, $height, $offsetX = 0, $offsetY = 0);

        /**
         * @param int $width
         * @param int $height
         *
         * @return static
         */
        public function resize($width, $height);

        /**
         * Rotate the image by a given degrees
         *
         * @param int   $degrees
         * @param int   $background
         * @param float $alpha
         *
         * @return static
         */
        public function rotate($degrees, $background = 0xffffff, $alpha = 1.0);

        /**
         * @param string $text
         * @param int    $offsetX
         * @param int    $offsetY
         * @param float  $opacity
         * @param int    $color
         * @param int    $size
         * @param string $font_file
         *
         * @return static
         */
        public function text(
            $text,
            $offsetX = 0,
            $offsetY = 0,
            $opacity = 1.0,
            $color = 0x000000,
            $size = 12,
            $font_file = null
        );

        /**
         * @param string $file
         * @param int    $offsetX
         * @param int    $offsetY
         * @param float  $opacity
         *
         * @return static
         * @throws \ManaPHP\Image\Exception
         */
        public function watermark($file, $offsetX = 0, $offsetY = 0, $opacity = 1.0);

        /**
         * @param string $file
         * @param int    $quality
         *
         * @throws \ManaPHP\Image\Exception
         */
        public function save($file = null, $quality = 80);
    }
}

