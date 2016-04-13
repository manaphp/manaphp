<?php
namespace ManaPHP\Image\Adapter {

    use ManaPHP\Image\AdapterInterface;

    class Gd implements AdapterInterface
    {
        /**
         * @var string
         */
        protected $_file;
        /**
         * @var string
         */
        protected $_real_path;

        /**
         * @var resource
         */
        protected $_image;

        /**
         * @var
         */
        protected $_width;

        /**
         * @var
         */
        protected $_height;

        /**
         * @param string $file
         *
         * @throws \ManaPHP\Image\Adapter\Exception
         */
        public function __construct($file)
        {
            if (!extension_loaded('gd')) {
                throw new Exception('gd is not installed, or the extension is not loaded');
            }

            if (is_file($file)) {
                $this->_file = $file;
                $this->_real_path = realpath($this->_file);
                $imageInfo = getimagesize($this->_real_path);
                list($this->_width, $this->_height, $type) = $imageInfo;

                if ($type === IMAGETYPE_GIF) {
                    $this->_image = imagecreatefromgif($this->_real_path);
                } elseif ($type === IMAGETYPE_JPEG) {
                    $this->_image = imagecreatefromjpeg($this->_real_path);
                } elseif ($type === IMAGETYPE_PNG) {
                    $this->_image = imagecreatefrompng($this->_real_path);
                } else {
                    throw new Exception('Installed GD does not support such images');
                }
                imagesavealpha($this->_image, true);
            } else {
                throw new Exception('the file is not exist: ' . $file);
            }
        }

        /**
         * Image width
         *
         * @return int
         */
        public function getWidth()
        {
            return $this->_width;
        }

        /**
         * Image height
         *
         * @return int
         */
        public function getHeight()
        {
            return $this->_height;
        }

        public function getInternalHandle()
        {
            return $this->_image;
        }

        /**
         * @param int $width
         * @param int $height
         *
         * @return static
         */
        public function resize($width, $height)
        {
            if (version_compare(PHP_VERSION, '5.5.0') < 0) {
                $image = imagecreatetruecolor($width, $height);
                imagealphablending($image, false);
                imagesavealpha($image, true);

                imagecopyresampled($image, $this->_image, 0, 0, 0, 0, $width, $height, $this->_width, $this->_height);
            } else {
                $image = imagescale($this->_image, $width, $height);
            }

            imagedestroy($this->_image);
            $this->_image = $image;
            $this->_width = imagesx($image);
            $this->_height = imagesy($image);

            return $this;
        }

        /**
         * Rotate the image by a given degrees
         *
         * @param int   $degrees
         * @param int   $background
         * @param float $alpha
         *
         * @return static
         */
        public function rotate($degrees, $background = 0xffffff, $alpha = 1.0)
        {
            $transparent = imagecolorallocatealpha($this->_image, ($background >> 16) & 0xFF, ($background >> 8) & 0xFF,
                $background & 0xFF, $alpha * 127);
            $image = imagerotate($this->_image, 360 - $degrees, $transparent, true);
            imagealphablending($image, false);
            imagesavealpha($image, true);

            imagedestroy($this->_image);
            $this->_image = $image;
            $this->_width = imagesx($image);
            $this->_height = imagesy($image);

            return $this;
        }

        /**
         * @param int $width
         * @param int $height
         * @param int $offsetX
         * @param int $offsetY
         *
         * @return static
         */
        public function crop($width, $height, $offsetX = 0, $offsetY = 0)
        {
            if (version_compare(PHP_VERSION, '5.5.0') < 0) {
                $image = imagecreatetruecolor($width, $height);
                imagealphablending($image, false);
                imagesavealpha($image, true);
                imagecopy($image, $this->_image, 0, 0, $offsetX, $offsetY, $width, $height);
            } else {
                $image = imagecrop($this->_image,
                    ['x' => $offsetX, 'y' => $offsetY, 'width' => $width, 'height' => $height]);
            }

            imagedestroy($this->_image);
            $this->_image = $image;
            $this->_width = imagesx($image);
            $this->_height = imagesy($image);

            return $this;
        }

        /**
         * Execute a text
         *
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
        ) {
            $textColor = imagecolorallocatealpha($this->_image, ($color >> 16) & 0xFF, ($color >> 8) & 0xFF,
                $color & 0xFF, abs(1 - $opacity) * 127);
            if ($font_file !== null) {
                imagettftext($this->_image, $size, 0, $offsetX, $offsetY, $textColor, $font_file, $text);
            } else {
                imagestring($this->_image, $size, $offsetX, $offsetY, $text, $textColor);
            }

            return $this;
        }

        /**
         * @param string $file
         * @param int    $offsetX
         * @param int    $offsetY
         * @param float  $opacity
         *
         * @return static
         * @throws \ManaPHP\Image\Adapter\Exception
         */
        public function watermark($file, $offsetX = 0, $offsetY = 0, $opacity = 1.0)
        {
            $maskImageInfo = getimagesize($file);
            list($maskWidth, $maskHeight, $maskType) = $maskImageInfo;

            if ($maskType === IMAGETYPE_GIF) {
                $maskImage = imagecreatefromgif($file);
            } elseif ($maskType === IMAGETYPE_JPEG) {
                $maskImage = imagecreatefromjpeg($file);
            } elseif ($maskType === IMAGETYPE_PNG) {
                $maskImage = imagecreatefrompng($file);
            } else {
                throw new Exception('Installed GD does not support such images');
            }

            imagesavealpha($maskImage, true);

            $image = imagecreatetruecolor($this->_width, $this->_height);
            imagealphablending($image, false);
            imagesavealpha($image, true);
            if ($maskType !== IMAGETYPE_PNG) {
                $filedColor = imagecolorallocatealpha($image, 127, 127, 127, (1 - $opacity) * 127);
            } else {
                $filedColor = imagecolorallocate($image, 127, 127, 127);
            }

            imagelayereffect($maskImage, IMG_EFFECT_OVERLAY);
            imagefilledrectangle($maskImage, 0, 0, $maskWidth, $maskHeight, $filedColor);
            imagealphablending($this->_image, true);
            imagecopy($this->_image, $maskImage, $offsetX, $offsetY, 0, 0, $maskWidth, $maskHeight);

            return $this;
        }

        /**
         * @param string $file
         * @param int    $quality
         *
         * @throws \ManaPHP\Image\Adapter\Exception
         */
        public function save($file = null, $quality = 80)
        {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if ($ext === '') {
                $ext = 'jpg';
            }

            if ($ext === 'gif') {
                imagegif($this->_image, $file);
            } elseif ($ext === 'jpg' || $ext === 'jpeg') {
                imagejpeg($this->_image, $file, $quality);
            } elseif ($ext === 'png') {
                imagepng($this->_image, $file);
            } else {
                throw new Exception("Installed GD does not support ' $ext ' images");
            }
        }
    }
}