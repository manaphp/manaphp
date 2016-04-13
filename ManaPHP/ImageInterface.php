<?php

namespace ManaPHP {

    /**
     * Interface ImageInterface
     *
     * @package ManaPHP
     */
    interface ImageInterface
    {
        /**
         * ImageInterface constructor.
         *
         * @param string $file
         * @param string $adapter
         *
         * @throws \ManaPHP\Image\Exception
         */
        public function __construct($file, $adapter = null);

        /**
         * @return \ManaPHP\Image\AdapterInterface
         */
        public function getAdapter();

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

        /**
         * Resize the image by a given width and height
         *
         * @param int $width
         * @param int $height
         *
         * @return static
         */
        public function resize($width, $height);

        /**
         * Resize the image by a given width and height
         *
         * @param int $width
         * @param int $height
         *
         * @return static
         */
        public function resizeCropCenter($width, $height);

        /**
         * Scale the image by a given ratio
         *
         * @param float $ratio
         *
         * @return static
         */
        public function scale($ratio);

        /**
         * Scale the image by a given width
         *
         * @param $width
         *
         * @return static
         */
        public function scaleFixedWidth($width);

        /**
         * Scale the image by a given height
         *
         * @param $height
         *
         * @return static
         */
        public function scaleFixedHeight($height);

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
         * @param string $file
         * @param int    $offsetX
         * @param int    $offsetY
         * @param float  $opacity
         *
         * @return static
         */
        public function watermark($file, $offsetX = 0, $offsetY = 0, $opacity = 1.0);

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
         * @param int    $quality
         */
        public function save($file = null, $quality = 80);
    }
}
