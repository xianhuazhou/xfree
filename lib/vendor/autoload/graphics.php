<?php

/**
	Graphics plugin for the PHP Fat-Free Framework

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2009-2010 F3 Factory
	Bong Cosca <bong.cosca@yahoo.com>

		@package Graphics
		@version 1.4.0
**/

//! Graphics plugin
class Graphics extends Core {

	//! Minimum framework version required to run
	const F3_Minimum='1.4.0';

	//@{
	//! Locale-specific error/exception messages
	const
		TEXT_Color='Invalid color specified';
	//@}

	const
		// Background color
		GFX_BGColor=0xFFF,
		// Foreground transparency
		GFX_Transparency=0x020,
		// Identicon horizontal/vertical blocks
		GFX_IdBlocks=4,
		// Identicon pixels per block
		GFX_IdPixels=64,
		//! PNG compression level
		PNG_Compress=1;

	/**
		Convert RGB hex triad to array
			@return mixed
			@param $int integer
			@public
	**/
	public static function rgb($int) {
		$hex=str_pad(dechex($int),$int<4096?3:6,'0',STR_PAD_LEFT);
		$len=strlen($hex);
		if ($len>6) {
			trigger_error(self::TEXT_Color);
			return FALSE;
		}
		$color=str_split($hex,$len/3);
		foreach ($color as &$hue)
			$hue=hexdec(str_repeat($hue,6/$len));
		return $color;
	}

	/**
		Generate CAPTCHA image
			@param $dimx integer
			@param $dimy integer
			@param $len integer
			@param $ttfs string
			@public
	**/
	public static function captcha($dimx,$dimy,$len,$ttfs='cube') {
		$base=self::rgb(self::$global['BGCOLOR']);
		$trans=self::$global['FGTRANS'];
		// Specify Captcha seed
		if (!strlen(session_id()))
			session_start();
		$_SESSION['captcha']=substr(md5(uniqid()),0,$len);
		self::$global['SESSION']=&$_SESSION;
		// Font size
		$size=min($dimx/$len,.6*$dimy);
		// Load TrueType font file
		$fonts=explode('|',$ttfs);
		$file=self::$global['FONTS'].
			F3::fixSlashes($fonts[mt_rand(0,count($fonts)-1)]).'.ttf';
		if (!isset(self::$stats['FILES']))
			self::$stats['FILES']=array('fonts'=>array());
		self::$stats['FILES']['fonts'][basename($file)]=filesize($file);
		$maxdeg=15;
		// Compute bounding box metrics
		$bbox=imagettfbbox($size,0,$file,$_SESSION['captcha']);
		$wimage=.9*(max($bbox[2],$bbox[4])-max($bbox[0],$bbox[6]));
		$himage=max($bbox[1],$bbox[3])-max($bbox[5],$bbox[7]);
		// Create blank image
		$captcha=imagecreatetruecolor($dimx,$dimy);
		list($r,$g,$b)=$base;
		$bg=imagecolorallocate($captcha,$r,$g,$b);
		imagefill($captcha,0,0,$bg);
		$width=0;
		// Insert each Captcha character
		for ($i=0;$i<$len;$i++) {
			// Random angle
			$angle=$maxdeg-mt_rand(0,$maxdeg*2);
			// Get CAPTCHA character from session cookie
			$char=$_SESSION['captcha'][$i];
			$fg=imagecolorallocatealpha(
				$captcha,
				mt_rand(0,255-$trans),
				mt_rand(0,255-$trans),
				mt_rand(0,255-$trans),
				$trans
			);
			imagettftext(
				$captcha,$size,$angle,
				($dimx-$wimage)/2+$i*$wimage/$len,
				($dimy-$himage)/2+.9*$himage,
				$fg,$file,$char
			);
			imagecolordeallocate($captcha,$fg);
		}
		// Make the background transparent
		imagecolortransparent($captcha,$bg);
		// Send output as PNG image
		if (PHP_SAPI!='cli')
			header(F3::HTTP_Content.': image/png');
		imagepng($captcha,NULL,self::PNG_Compress,PNG_NO_FILTER);
	}

	/**
		Invert colors of specified image
			@param $file
			@public
	**/
	public static function invert($file) {
		preg_match('/\.(gif|jp[e]*g|png)$/',$file,$ext);
		$ext[1]=str_replace('jpg','jpeg',$ext[1]);
		$file=self::$global['GUI'].$file;
		$img=imagecreatefromstring(file_get_contents($file));
		imagefilter($img,IMG_FILTER_NEGATE);
		if (PHP_SAPI!='cli')
			header(F3::HTTP_Content.': image/'.$ext[1]);
		// Send output in same graphics format as original
		eval('image'.$ext[1].'($img);');
	}

	/**
		Apply grayscale filter on specified image
			@param $file
			@public
	**/
	public static function grayscale($file) {
		preg_match('/\.(gif|jp[e]*g|png)$/',$file,$ext);
		$ext[1]=str_replace('jpg','jpeg',$ext[1]);
		$file=self::$global['GUI'].$file;
		$img=imagecreatefromstring(file_get_contents($file));
		imagefilter($img,IMG_FILTER_GRAYSCALE);
		if (PHP_SAPI!='cli')
			header(F3::HTTP_Content.': image/'.$ext[1]);
		// Send output in same graphics format as original
		eval('image'.$ext[1].'($img);');
	}

	/**
		Generate thumbnail image
			@param $file string
			@param $dimx integer
			@param $dimy integer
			@public
	**/
	public static function thumb($file,$dimx,$dimy) {
		preg_match('/\.(gif|jp[e]*g|png)$/',$file,$ext);
		$ext[1]=str_replace('jpg','jpeg',$ext[1]);
		$file=self::$global['GUI'].$file;
		$img=imagecreatefromstring(file_get_contents($file));
		// Get image dimensions
		$oldx=imagesx($img);
		$oldy=imagesy($img);
		// Adjust dimensions; retain aspect ratio
		$ratio=$oldx/$oldy;
		if ($dimx<=$oldx && $dimx/$ratio<=$dimy)
			// Adjust height
			$dimy=$dimx/$ratio;
		elseif ($dimy<=$oldy && $dimy*$ratio<=$dimx)
			// Adjust width
			$dimx=$dimy*$ratio;
		else {
			// Retain size if dimensions exceed original image
			$dimx=$oldx;
			$dimy=$oldy;
		}
		// Create blank image
		$tmp=imagecreatetruecolor($dimx,$dimy);
		list($r,$g,$b)=self::rgb(self::$global['BGCOLOR']);
		$bg=imagecolorallocate($tmp,$r,$g,$b);
		imagefill($tmp,0,0,$bg);
		// Resize
		imagecopyresampled($tmp,$img,0,0,0,0,$dimx,$dimy,$oldx,$oldy);
		// Make the background transparent
		imagecolortransparent($tmp,$bg);
		if (PHP_SAPI!='cli')
			header(F3::HTTP_Content.': image/'.$ext[1]);
		// Send output in same graphics format as original
		eval('image'.$ext[1].'($tmp);');
	}

	/**
		Generate identicon from an MD5 hash value
			@param $hash string
			@param $size integer
			@public
	**/
	public static function identicon($hash,$size=NULL) {
		$blox=self::$global['IBLOCKS'];
		if (is_null($size))
			$size=self::$global['IPIXELS'];
		// Rotatable shapes
		$dynamic=array(
			array(.5,1,1,0,1,1),
			array(.5,0,1,0,.5,1,0,1),
			array(.5,0,1,0,1,1,.5,1,1,.5),
			array(0,.5,.5,0,1,.5,.5,1,.5,.5),
			array(0,.5,1,0,1,1,0,1,1,.5),
			array(1,0,1,1,.5,1,1,.5,.5,.5),
			array(0,0,1,0,1,.5,0,0,.5,1,0,1),
			array(0,0,.5,0,1,.5,.5,1,0,1,.5,.5),
			array(.5,0,.5,.5,1,.5,1,1,.5,1,.5,.5,0,.5),
			array(0,0,1,0,.5,.5,1,.5,.5,1,.5,.5,0,1),
			array(0,.5,.5,1,1,.5,.5,0,1,0,1,1,0,1),
			array(.5,0,1,0,1,1,.5,1,1,.75,.5,.5,1,.25),
			array(0,.5,.5,0,.5,.5,1,0,1,.5,.5,1,.5,.5,0,1),
			array(0,0,1,0,1,1,0,1,1,.5,.5,.25,.5,.75,0,.5,.5,.25),
			array(0,.5,.5,.5,.5,0,1,0,.5,.5,1,.5,.5,1,.5,.5,0,1),
			array(0,0,1,0,.5,.5,.5,0,0,.5,1,.5,.5,1,.5,.5,0,1)
		);
		// Fixed shapes (for center sprite)
		$static=array(
			array(),
			array(0,0,1,0,1,1,0,1),
			array(.5,0,1,.5,.5,1,0,.5),
			array(0,0,1,0,1,1,0,1,0,.5,.5,1,1,.5,.5,0,0,.5),
			array(.25,0,.75,0,.5,.5,1,.25,1,.75,.5,.5,
				.75,1,.25,1,.5,.5,0,.75,0,.25,.5,.5),
			array(0,0,.5,.25,1,0,.75,.5,1,1,.5,.75,0,1,.25,.5),
			array(.33,.33,.67,.33,.67,.67,.33,.67),
			array(0,0,.33,0,.33,.33,.67,.33,.67,0,1,0,1,.33,.67,.33,
				.67,.67,1,.67,1,1,.67,1,.67,.67,.33,.67,.33,1,0,1,
				0,.67,.33,.67,.33,.33,0,.33)
		);
		// Parse MD5 hash
		$hash=F3::resolve($hash);
		list($bgR,$bgG,$bgB)=self::rgb(self::$global['BGCOLOR']);
		list($fgR,$fgG,$fgB)=self::rgb(hexdec(substr($hash,0,6)));
		$shapeC=hexdec($hash[6]);
		$angleC=hexdec($hash[7]%4);
		$shapeX=hexdec($hash[8]);
		for ($i=0;$i<$blox-2;$i++) {
			$shapeS[$i]=hexdec($hash[9+$i*2]);
			$angleS[$i]=hexdec($hash[10+$i*2]%4);
		}
		// Start with NxN blank slate
		$identicon=imagecreatetruecolor($size*$blox,$size*$blox);
		imageantialias($identicon,TRUE);
		$bg=imagecolorallocate($identicon,$bgR,$bgG,$bgB);
		$fg=imagecolorallocate($identicon,$fgR,$fgG,$fgB);
		// Generate corner sprites
		$corner=imagecreatetruecolor($size,$size);
		imagefill($corner,0,0,$bg);
		$sprite=$dynamic[$shapeC];
		for ($i=0,$len=count($sprite);$i<$len;$i++)
			$sprite[$i]=$sprite[$i]*$size;
		imagefilledpolygon($corner,$sprite,$len/2,$fg);
		for ($i=0;$i<$angleC;$i++)
			$corner=imagerotate($corner,90,$bg);
		// Generate side sprites
		for ($i=0;$i<$blox-2;$i++) {
			$side[$i]=imagecreatetruecolor($size,$size);
			imagefill($side[$i],0,0,$bg);
			$sprite=$dynamic[$shapeS[$i]];
			for ($j=0,$len=count($sprite);$j<$len;$j++)
				$sprite[$j]=$sprite[$j]*$size;
			imagefilledpolygon($side[$i],$sprite,$len/2,$fg);
			for ($j=0;$j<$angleS[$i];$j++)
				$side[$i]=imagerotate($side[$i],90,$bg);
		}
		// Generate center sprites
		for ($i=0;$i<$blox-2;$i++) {
			$center[$i]=imagecreatetruecolor($size,$size);
			imagefill($center[$i],0,0,$bg);
			$sprite=$dynamic[$shapeX];
			if ($blox%2>0 && $i==$blox-3)
				// Odd center sprites
				$sprite=$static[$shapeX%8];
			$len=count($sprite);
			if ($len) {
				for ($j=0;$j<$len;$j++)
					$sprite[$j]=$sprite[$j]*$size;
				imagefilledpolygon($center[$i],$sprite,$len/2,$fg);
			}
			if ($i<($blox-3))
				for ($j=0;$j<$angleS[$i];$j++)
					$center[$i]=imagerotate($center[$i],90,$bg);
		}
		// Paste sprites
		for ($i=0;$i<4;$i++) {
			imagecopy($identicon,$corner,0,0,0,0,$size,$size);
			for ($j=0;$j<$blox-2;$j++) {
				imagecopy($identicon,$side[$j],
					$size*($j+1),0,0,0,$size,$size);
				for ($k=$j;$k<$blox-3-$j;$k++)
					imagecopy($identicon,$center[$k],
						$size*($k+1),$size*($j+1),0,0,$size,$size);
			}
			$identicon=imagerotate($identicon,90,$bg);
		}
		if ($blox%2>0)
			// Paste odd center sprite
			imagecopy($identicon,$center[$blox-3],
				$size*(floor($blox/2)),$size*(floor($blox/2)),0,0,
				$size,$size);
		// Resize
		$resized=imagecreatetruecolor($size,$size);
		imagecopyresampled($resized,$identicon,0,0,0,0,$size,$size,
			$size*$blox,$size*$blox);
		// Make the background transparent
		imagecolortransparent($resized,$bg);
		if (PHP_SAPI!='cli')
			header(F3::HTTP_Content.': image/png');
		imagepng($resized,NULL,self::PNG_Compress,PNG_NO_FILTER);
	}

	/**
		Generate a blank image for use as a placeholder
			@param $dimx integer
			@param $dimy integer
			@param $bg string
			@public
	**/
	public static function fakeImage($dimx,$dimy,$bg=0xEEE) {
		// GD extension required
		if (!extension_loaded('gd')) {
			self::$global['CONTEXT']='gd';
			trigger_error(self::TEXT_PHPExt);
			return;
		}
		list($r,$g,$b)=self::rgb($bg);
		$img=imagecreatetruecolor($dimx,$dimy);
		$bg=imagecolorallocate($img,$r,$g,$b);
		imagefill($img,0,0,$bg);
		if (PHP_SAPI!='cli')
			header(F3::HTTP_Content.': image/png');
		imagepng($img,NULL,self::PNG_Compress,PNG_NO_FILTER);
	}

	/**
		Bootstrap code
			@public
	**/
	public static function onLoad() {
		// GD extension required
		if (!extension_loaded('gd')) {
			// Unable to continue
			self::$global['CONTEXT']='gd';
			trigger_error(self::TEXT_PHPExt);
			return;
		}
		self::$global['HEADERS']=array();
		self::$global['SITEMAP']=array();
		if (!isset(self::$global['FONTS']))
			self::$global['FONTS']=self::$global['BASE'];
		if (!isset(self::$global['BGCOLOR']))
			self::$global['BGCOLOR']=self::GFX_BGColor;
		if (!isset(self::$global['FGTRANS']))
			self::$global['FGTRANS']=self::GFX_Transparency;
		if (!isset(self::$global['IBLOCKS']))
			self::$global['IBLOCKS']=self::GFX_IdBlocks;
		if (!isset(self::$global['IPIXELS']))
			self::$global['IPIXELS']=self::GFX_IdPixels;
	}

}
