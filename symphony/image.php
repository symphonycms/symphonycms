<?php

	function expandCSSColourString($colour){
		return (strlen($colour) == 3 ? $colour{0}.$colour{0}.$colour{1}.$colour{1}.$colour{2}.$colour{2} : $colour);
	}
	
	##Include some parts of the engine
	require_once('../manifest/config.php');
	
	include(TOOLKIT . '/class.image.php');
	include(TOOLKIT . '/class.imagefilters.php');
	
	define_safe('MODE_NONE', 0);
	define_safe('MODE_RESIZE', 1);
	define_safe('MODE_RESIZE_CROP', 2);
	define_safe('MODE_CROP', 3);
	
	define_safe('CACHING', ($settings['image']['cache'] == 1 ? true : false));
	
	$param = array();
	
	list(
		$param['mode'],
		$param['width'],
		$param['height'],
		$param['position'],
		$param['background'],
		$param['external'],		
		$param['file']												
	) = preg_split('/:/i', $_REQUEST['param'], 7);
	
	$param['background'] = expandCSSColourString($param['background']);
	
	$meta = $cache_file = NULL;

	$image_path = ($param['external'] == '1' ? 'http://' . $param['file'] : WORKSPACE . '/' . $param['file']);
	
	## Do cache checking stuff here
	
	if($param['external'] != '1' && CACHING){
		
	    $cache_file = CACHE . '/' . md5($_REQUEST['param'] . $quality) . "_" . basename($image_path);	

		if(@is_file($cache_file) && (@filemtime($cache_file) < @filemtime($image_path))){ 
			unlink($cache_file);
		}		
		
		elseif(is_file($cache_file)){
			$image_path = $cache_file;
			@touch($cache_file);
			$param['mode'] = MODE_NONE;
		}
	}
	
	####
	
	if($param['mode'] == MODE_NONE && $param['external'] != 1){
		
		if(!$contents = @file_get_contents($image_path)){
			header('HTTP/1.0 404 Not Found');
			trigger_error('Image <code>'.$image_path.'</code> could not be found.', E_USER_ERROR);
		}
		
		$meta = Image::meta($image_path);
		Image::renderOutputHeaders($meta['type']);		
		print $contents;
		@imagedestroy($image);
		exit();
	} 
	
	$method = ($param['external'] == 1 ? 'loadExternal' : 'load');
	
	if(!$image = call_user_func_array(array('Image', $method), array($image_path, &$meta))){
		header('HTTP/1.0 404 Not Found');
		trigger_error('Error loading image', E_USER_ERROR);
	}
	
	switch($param['mode']){
		
		case MODE_RESIZE:
			ImageFilters::resize($image, $param['width'], $param['height']);
			break;
			
		case MODE_RESIZE_CROP:
			$src_w = Image::width($image);
			$src_h = Image::height($image);
			
			
			$dst_w = $param['width'];
			$dst_h = $param['height'];
			
      $src_r = ($src_w / $src_h);
      $dst_r = ($dst_w / $dst_h);

      if($src_r < $dst_r) ImageFilters::resize($image, $dst_w, NULL);
      else ImageFilters::resize($image, NULL, $dst_h);			
			
			/*
				if($src_h < $param['height'] || $src_h > $param['height']) ImageFilters::resize($image, NULL, $param['height']);
				if($src_w < $param['width']) ImageFilters::resize($image, $param['width'], NULL);			
					
			*/

		case MODE_CROP:
			ImageFilters::crop($image, $param['width'], $param['height'], $param['position'], $param['background']);
			break;
	}

	if(!Image::display($image, intval($settings['image']['quality']), true, $meta['type'])) trigger_error('Error generating image', E_USER_ERROR);
	
	if(CACHING && !is_file($cache_file)) Image::save($image, $cache_file, intval($settings['image']['quality']), true, $meta['type']);
	
	@imagedestroy($image);
	exit();
