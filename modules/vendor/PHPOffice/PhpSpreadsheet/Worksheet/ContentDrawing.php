<?php
// HostCMS

namespace PhpOffice\PhpSpreadsheet\Worksheet;

class ContentDrawing extends MemoryDrawing
{
	public $imageResource;
    /**
     * Set image resource.
     *
     * @param resource $value
     *
     * @return MemoryDrawing
     */
    public function setImageResource($value)
    {
        $this->imageResource = $value;

        return $this;
    }

	/**
     * Get image resource.
     *
     * @return resource
     */
    public function getImageResource()
    {
        return $this->imageResource;
    }

    /**
     * Set width and height.
     *
     * @param int $width
     * @param int $height
     *
     * @return BaseDrawing
     */
    public function setWidthAndHeight(int $width, int $height): self
    {
		$this->width = $width;
		$this->height = $height;

        return $this;
    }
}
