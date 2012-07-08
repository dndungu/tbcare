<?php

namespace apps\content;

class ImageResize {
	
	private $sandbox = NULL;
	
	private $width = 100;
	
	private $height = 75;
	
	private $compression = 0;
	
	private $quality = 90;
	
	private $imagetypes = array('image/jpg','image/jpeg','image/gif','image/png');
	
	private $videotypes =  array(
			'application/annodex',
			'application/mp4',
			'application/ogg',
			'application/vnd.rn-realmedia',
			'application/x-matroska',
			'video/3gpp',
			'video/3gpp2',
			'video/annodex',
			'video/divx',
			'video/flv',
			'video/h264',
			'video/mp4',
			'video/mp4v-es',
			'video/mpeg',
			'video/mpeg-2',
			'video/mpeg4',
			'video/ogg',
			'video/ogm',
			'video/quicktime',
			'video/ty',	
			'video/vdo',
			'video/vivo',
			'video/vnd.rn-realvideo',	
			'video/vnd.vivo',
			'video/webm',
			'video/x-bin',
			'video/x-cdg',
			'video/x-divx',
			'video/x-dv',
			'video/x-flv',
			'video/x-la-asf',
			'video/x-m4v',
			'video/x-matroska',
			'video/x-motion-jpeg',
			'video/x-ms-asf',
			'video/x-ms-dvr',
			'video/x-ms-wm',
			'video/x-ms-wmv',
			'video/x-msvideo',
			'video/x-sgi-movie',
			'video/x-tivo',
			'video/avi',
			'video/x-ms-asx',
			'video/x-ms-wvx',
			'video/x-ms-wmx'
	);
	
	private $source = NULL;
	
	private $destination = NULL;
	
	private $mimetype = NULL;
	
	private $original = NULL;
	
	private $thumnbail = NULL;
	
	public function __construct($source){
		$this->source = $source;
		if(!$this->isImage() && !$this->isVideo()) {
			throw new \apps\ApplicationException("$source is not an image or video");
		}
	}
	
	public function isImage(){
		$finfo = new \finfo(FILEINFO_MIME_TYPE);
		$this->mimetype = strtolower($finfo->file($this->source));
		return in_array($this->mimetype, $this->imagetypes) ? true : false;
	}
	
	public function isVideo(){
		$finfo = new \finfo(FILEINFO_MIME_TYPE);
		$this->mimetype = strtolower($finfo->file($this->source));
		return in_array($this->mimetype, $this->videotypes) ? true : false;
	}
	
	public function doSave(){
		if($this->isImage()){
			$this->createOriginal();
			$this->generateImage();
			$this->saveImage();
		}
		if($this->isVideo()){
			//TODO
		}
	}
	
	public function doPrint(){
		if($this->isImage()){
			$this->createOriginal();
			$this->generateImage();
			$this->printImage();
		}
		if($this->isVideo()){
			try {
				$duration = $this->getVideoDuration();
				$this->generateVideoPoster($duration);
				$this->printImage();
			}catch(\apps\ApplicationException $e){
				throw new \apps\ApplicationException($e->getMessage());
			}
		}
	}
	
	private function getVideoDuration(){
		$source = $this->source;
		$output = shell_exec("ffmpeg -i $source 2>&1");
		if(!preg_match('/Duration: (.*?),/', $output, $duration)) {
			throw new \apps\ApplicationException($output);
		}
		$duration =  explode(":", substr($duration[1], 0, 8));
		$last = count($duration);
		if($last){
			$seconds = floor($duration[--$last]);
		}
		if($last){
			$seconds = $seconds + $duration[--$last] * 60;
		}
		if($last){
			$hours = $seconds + $duration[--$last] * 60 * 60;
		}
		return $seconds;
	}
	
	private function generateVideoPoster($duration){
		$position = rand(($duration / 4), $duration);
		$source = $this->source;
		$size = $this->width.'x'.$this->height;
		$destination = "$source-$size.png";
		$command = "avconv -ss $position -i $source -deinterlace -an -vcodec png -pix_fmt rgb24 -vframes 1 -an -f rawvideo -s $size -y $destination";
		$output = shell_exec($command);
		$this->thumnbail = imagecreatefrompng($destination);
		$finfo = new \finfo(FILEINFO_MIME_TYPE);
		$this->mimetype = strtolower($finfo->file($destination));
		if(!in_array($this->mimetype, $this->imagetypes)) {
			throw new \apps\ApplicationException($output);
		}
	}
		
	public function setDestination($destination){
		$this->destination = $destination;
	}
	
	public function createOriginal(){
		switch($this->mimetype){
			case "image/gif":
				$this->original = imagecreatefromgif($this->source);
				break;
			case "image/jpg":
			case "image/jpeg":
				$this->original = imagecreatefromjpeg($this->source);
				break;
			case "image/png":
				$this->original = imagecreatefrompng($this->source);
				break;	
		}
	}
	
	public function generateImage(){
		$size = getimagesize($this->source);
		$width = $size[0];
		$height = $size[1];
		$ratio = ($this->width / $width);
		if(($ratio * $height) > $this->height) {
			$ratio = $this->height / $height;
		}
		$this->width = ($width * $ratio);
		$this->height = ($height * $ratio);
		$thumbnail = imagecreatetruecolor($this->width, $this->height);
		$result = imagecopyresampled($thumbnail, $this->original, 0, 0, 0, 0, $this->width, $this->height, $width, $height);
		$this->thumnbail = $thumbnail;
	}
	
	public function saveImage(){
		switch($this->mimetype){
			case "image/gif":
				imagejpeg($this->thumnbail, $this->destination);
				break;
			case "image/jpg":
			case "image/jpeg":
				imagejpeg($this->thumnbail, $this->destination, $this->quality);
				break;
			case "image/png":
				imagepng($this->thumnbail, $this->destination, $this->compression);
				break;
		}
	}
	
	public function printImage(){
		switch($this->mimetype){
			case "image/gif":
				$this->printGif();
				break;
			case "image/jpg":
				$this->printJpg();
			case "image/jpeg":
				$this->printJpeg();
				break;
			case "image/png":
				$this->printPng();
				break;
		}
	}
	
	private function printGif(){
		ob_clean();
		header('Content-Type: image/gif');
		imagejpeg($this->thumnbail, NULL);
		exit;
	}

	private function printJpg(){
		ob_clean();
		header('Content-Type: image/jpg');
		imagejpeg($this->thumnbail, NULL, $this->quality);
		exit;
	}
	
	private function printJpeg(){
		ob_clean();
		header('Content-Type: image/jpeg');
		imagejpeg($this->thumnbail, NULL, $this->quality);
		exit;
	}
	
	private function printPng(){
		ob_clean();
		header('Content-Type: image/png');
		imagepng($this->thumnbail, NULL, $this->compression);
		exit;
	}
	
	public function setWidth($width){
		$this->width = $width;
	}
	
	public function getWidth(){
		return $this->width;
	}
	
	public function setHeight($height){
		$this->height = $height;
	}
	
	public function getHeight(){
		return $this->height;
	}
	
	public function getMimeType(){
		return $this->mimetype;
	}
}