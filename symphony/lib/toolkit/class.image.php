<?php

	Class Image{
		
		const DEFAULT_QUALITY = 80;
		const DEFAULT_INTERLACE = true;
		const DEFAULT_OUTPUT_TYPE = IMAGETYPE_JPEG;
		
		public static function height($res){
			return imagesy($res);
		}
		
		public static function width($res){
			return imagesx($res);
		}
				
		public static function loadExternal($uri, &$meta){
			
			if(function_exists('curl_init')){
				$ch = curl_init();
				
				curl_setopt($ch, CURLOPT_URL, $uri);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				
				$tmp = curl_exec($ch);
				curl_close($ch);			
			}
			
			else $tmp = file_get_contents($uri);
			
			if(!$tmp) trigger_error(__('Error reading external image <code>%s</code>. Please check the URI.', array($uri)), E_USER_ERROR);
			
			$dest = tempnam(sys_get_temp_dir(), 'IMAGE');
			
			if(!@file_put_contents($dest, $tmp)) trigger_error(__('Error writing to temporary file <code>%s</code>.', array($dest)), E_USER_ERROR);
			
			return self::load($dest, $meta);
			
		}
		
		public static function load($image, &$meta){
			
			if(!is_file($image) || !is_readable($image)) trigger_error(__('Error reading image <code>%s</code>. Check it exists and is readable.', array($image)), E_USER_ERROR);
			
			$meta = self::meta($image);
			
			switch($meta['type']){
					
				## GIF
				case IMAGETYPE_GIF:
					return imagecreatefromgif($image);
					break;
					
				## JPEG
				case IMAGETYPE_JPEG: 
				
					if($meta['channels'] <= 3) return imagecreatefromjpeg($image);
						
					## Cant handle CMYK JPEG files	
					else trigger_error(__('Cannot load CMYK JPG Images'), E_USER_ERROR);
						
					break;
				
				## PNG
				case IMAGETYPE_PNG: 
					return imagecreatefrompng($image);
					break;
					
				default: 
					trigger_error(__('Unsupported image type. Supported types: GIF, JPEG and PNG'), E_USER_ERROR);
					break;
			}			
			
			return true;
		}
	
		public static function display($res, $quality=NULL, $interlacing=NULL, $output=NULL, $flag=NULL){
						
			if(!$quality) $quality = self::DEFAULT_QUALITY;
			if(!$interlacing) $interlacing = self::DEFAULT_INTERLACE;
			if(!$output) $output = DEFAULT_OUTPUT_TYPE;
					
			self::renderOutputHeaders($output);
			return self::__render($res, NULL, $quality, $interlacing, $output);
		}
		
		public static function save($res, $dest, $quality=NULL, $interlacing=NULL, $output=NULL, $flag=NULL){
			
			if(!$quality) $quality = self::DEFAULT_QUALITY;
			if(!$interlacing) $interlacing = self::DEFAULT_INTERLACE;
			if(!$output) $output = DEFAULT_OUTPUT_TYPE;
			
			return self::__render($res, $dest, $quality, $interlacing, $output);
		}
		
		public static function meta($file){
			if(!$array = @getimagesize($file)) return false;

			$meta = array();

			$meta['width']    = $array[0];
			$meta['height']   = $array[1];
			$meta['type']     = $array[2];
			$meta['channels'] = $array['channels'];
			
			return $meta;
		}

		private static function __render($res, $dest, $quality, $interlacing, $output){
			
			if(!is_resource($res)) trigger_error(__('Invalid image resource supplied'), E_USER_ERROR);
			
			## Turn interlacing on for JPEG or PNG only
			if($interlacing && ($output == IMAGETYPE_JPEG || $output == IMAGETYPE_PNG)){		
				imageinterlace($res);	
			}
			
			switch($output){
				
				case IMAGETYPE_GIF:
					return imagegif($res, $dest);
					break;
									
				case IMAGETYPE_PNG:
					return imagepng($res, $dest, round(9 * ($quality * 0.01)));
					break;	
					
				case IMAGETYPE_JPEG:
				default:
					return imagejpeg($res, $dest, $quality);
					break;					
			}
			
			return false;					
		}

		public static function renderOutputHeaders($output, $dest=NULL){

			header('Content-Type: ' . image_type_to_mime_type($output));

			if(!$dest) return;
				
			## Try to remove old extension
			$ext = strrchr($dest, '.');
			if($ext !== false){  	         
				$dest = substr($dest, 0, -strlen($ext));  	     
			}
			
			header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header("Content-Disposition: inline; filename=$dest" . image_type_to_extension($output));
		    header('Pragma: no-cache');			

		}
	}	

?>