<?php

namespace Gregwar\Image\Adapter;

abstract class Common extends Adapter
{
    /**
     * Perform a zoom crop of the image to desired width and height
     *
     * @param integer $width  Desired width
     * @param integer $height Desired height
     * @param int $bg
     * @return void
     */
    public function zoomCrop($width, $height, $bg = 0xffffff)
    {
        // Calculate the different ratios
        $originalRatio = $this->width() / $this->height();
        $newRatio = $width / $height;

        // Compare ratios
        if ($originalRatio > $newRatio) {
            // Original image is wider
            $newHeight = $height;
            $newWidth = (int) $height * $originalRatio;
        } else {
            // Equal width or smaller
            $newHeight = (int) $width / $originalRatio;
            $newWidth = $width;
        }

        // Perform resize
        $this->resize($newWidth, $newHeight, $bg, true);

        // Calculate cropping area
        $xPos = (int) ($newWidth - $width) / 2;
        $yPos = (int) ($newHeight - $height) / 2;

        // Crop image to reach desired size
        $this->crop($xPos, $yPos, $width, $height);
    }

    /**
     * Resizes the image forcing the destination to have exactly the
     * given width and the height
     *
     * @param int $w the width
     * @param int $h the height
     * @param int $bg the background
     */
    public function forceResize($width = null, $height = null, $background = 0xffffff)
    {
        $this->resize($width, $height, $background, true);
    }

    /**
     * Resizes the image preserving scale. Can enlarge it.
     *
     * @param int $w the width
     * @param int $h the height
     * @param int $bg the background
     */
    public function scaleResize($width = null, $height = null, $background=0xffffff, $crop = false)
    {
        $this->resize($width, $height, $background, false, true, $crop);
    }

    /**
     * Works as resize() excepts that the layout will be cropped
     *
     * @param int $w the width
     * @param int $h the height
     * @param int $bg the background
     */
    public function cropResize($width = null, $height = null, $background=0xffffff)
    {
        $this->resize($width, $height, $background, false, false, true);
    }
    
    abstract protected function openGif();
    abstract protected function openJpeg();
    abstract protected function openPng();
    abstract protected function createImage($width, $height);
    abstract protected function createImageFromData($data);
    
    /**
     * Try to open the file
     *
     * XXX: this logic should maybe be factorized
     */
    public function init()
    {
        if (null === $this->file) {
            if (null === $this->data) {
                if (null === $this->resource) {
                    $this->createImage($this->width, $this->height);
                }
            } else {
                $this->createImageFromData($this->data);

                if (false === $this->resource) {
                    throw new \UnexpectedValueException('Unable to create file from string.');
                }
            }
        } else {
            if (null === $this->resource) {
                if (!$this->supports($this->type)) {
                    throw new \RuntimeException('Type '.$this->type.' is not supported by GD');
                }

                if ($this->type == 'jpeg') {
                    $this->openJpeg();
                }

                if ($this->type == 'gif') {
                    $this->openGif();
                }

                if ($this->type == 'png') {
                    $this->openPng();
                }

                if (false === $this->resource) {
                    throw new \UnexpectedValueException('Unable to open file ('.$this->file.')');
                } else {
                    $this->convertToTrueColor();
                }
            }
        }

        if ($this->resource) {
            imagesavealpha($this->resource, true);
        }

        return $this;
    }
 
    /**
     * Resizes the image. It will never be enlarged.
     *
     * @param int $w the width
     * @param int $h the height
     * @param int $bg the background
     */
    public function resize($w = null, $h = null, $bg = 0xffffff, $force = false, $rescale = false, $crop = false)
    {
        $width = $this->width();
        $height = $this->height();
        $scale = 1.0;

        if ($h === null && preg_match('#^(.+)%$#mUsi', $w, $matches)) {
            $w = round($width * ((float)$matches[1]/100.0));
            $h = round($height * ((float)$matches[1]/100.0));
        }

        if (!$rescale && (!$force || $crop)) {
            if ($w!=null && $width>$w) {
                $scale = $width/$w;
            }

            if ($h!=null && $height>$h) {
                if ($height/$h > $scale)
                    $scale = $height/$h;
            }
        } else {
            if ($w!=null) {
                $scale = $width/$w;
                $new_width = $w;
            }

            if ($h!=null) {
                if ($w!=null && $rescale) {
                    $scale = max($scale,$height/$h);
                } else {
                    $scale = $height/$h;
                }
                $new_height = $h;
            }
        }

        if (!$force || $w==null || $rescale) {
            $new_width = round($width/$scale);
        }

        if (!$force || $h==null || $rescale) {
            $new_height = round($height/$scale);
        }

        if ($w == null || $crop) {
            $w = $new_width;
        }

        if ($h == null || $crop) {
            $h = $new_height;
        }

        $this->doResize($bg, $w, $h, $new_width, $new_height);
    }
    
    abstract protected function doResize($bg, $target_width, $target_height, $new_width, $new_height);
}
