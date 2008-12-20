<?php

	Class ArchiveZip{
	
		const LOCAL_FILE_HEADER_SIGNATURE = "\x50\x4b\x03\x04";
		const CENTRAL_FILE_HEADER_SIGNATURE = "\x50\x4b\x01\x02";
		const END_OF_CENTRAL_DIRECTORY_SIGNATURE = "\x50\x4b\x05\x06";
		
		const IGNORE_HIDDEN = 1;

		private $_file_records = array();

		private function __traverse($path, $root=NULL, $flag=NULL, array $exclude=array()){
			$path = rtrim($path, '/');

			if(!$root) $root = $path;

			foreach(scandir($path) as $item){
				if(!strcmp($item, '.') || !strcmp($item, '..') || ($flag == self::IGNORE_HIDDEN && $item{0} == '.')) continue;

				$location = trim(str_replace($root, '', $path), '/');

				if(is_dir($path . '/' . $item)){
					
					if(in_array($item, $exclude)) continue;
					
					if(!$this->addFromString(NULL, trim($location . '/' . $item, '/') . '/'))
						trigger_error(__('Could not add directory "%s".', array(trim($location . '/' . $item, '/') . '/')), E_USER_ERROR);

					self::__traverse($path . '/' . $item, $root, $flag, $exclude);
				}

				else{
					if(!$this->addFromFile($path . '/' . $item, trim($location . '/' . $item, '/')))
						trigger_error(__('Could not add file "%s".', array($path . '/' . $item)), E_USER_ERROR);
				}
			}
		}

		public function addFromFile($src, $dest){

			if(!is_readable($src)) return false;
			
			return $this->addFromString(file_get_contents($src), $dest);
		}
		
		public function addFromString($string, $dest){
			$rec = new __ArchiveZipFile($string, $dest, false);
		
			if(!$rec->isValidFile()) return false;
		
			$this->_file_records[] = clone $rec;
		
			return true;			
		}
		
		public function addDirectory($src, $root=NULL, $flag=NULL, array $exclude=array()){
			$this->__traverse($src, $root, $flag, $exclude);
		}
		
		public function save($dest=NULL){

			$raw = NULL;
			$fr_raw = NULL;
			$cdr_raw = NULL;
			$offset = 0;

			for($ii = 0; $ii < count($this->_file_records); $ii++){
				$this->_file_records[$ii]->offset = $offset;			
				$chunk = self::LOCAL_FILE_HEADER_SIGNATURE . implode($this->_file_records[$ii]->renderFileRecord());
			
				$fr_raw .= $chunk;
			
				$cdr_raw .= self::CENTRAL_FILE_HEADER_SIGNATURE . implode($this->_file_records[$ii]->renderCentralDirectoryRecord($offset)); 
			
				$offset += strlen($chunk);
			}
		
			$offset = strlen($fr_raw);
			$raw = $fr_raw . $cdr_raw;	

			$raw .= self::END_OF_CENTRAL_DIRECTORY_SIGNATURE
				 .  pack('v', 0) // Number of this disk
				 .  pack('v', 0) // Number of the disk with the central directory
				 .  pack('v', count($this->_file_records)) // Total number of files in this archive
				 .  pack('v', count($this->_file_records)) // Total number of files across all archives
				 .  pack('V', strlen($cdr_raw)) // Central directory length
				 .  pack('V', $offset) // Offset to the Central Directory
				 .  pack('v', 0) // Comments length
				 .  ''; // Comments	

			if($dest){
				$handle = @fopen($dest, 'wb');
				fwrite($handle, $raw);
				fclose($handle);
				return true;
			}
		
			else return $raw;
		
		}

	}


	Class __ArchiveZipFile{
	
		const MIN_UNCOMPRESSED_SIZE = 256;
		const DEFAULT_VERSION_NEEDED = 10;
		const DEFAULT_COMPRESSION_METHOD = 8;
	
		const DIRECTORY = 16;
		const FILE = 32;		
		
		private $_properties = array(
			'version_needed' => '',
			'general_purpose_bit_flag' => '',
			'compression_method' => '',
			'modification_time' => '',
			'modification_date' => '',
			'crc_32' => '',
			'data_compressed_size' => '',
			'data_uncompressed_size' => '',
			'file_name_length' => '',
			'extra_field_length' => '',
			'file_name' => '',
			'data_compressed' => ''
		);
	
		private $_comments;
		private $_external_file_attributes;
		private $_offset;
	
		private $_valid = false;
	
		public function __set($n, $v){
			switch($n){
				case 'comments':
					$this->_comments = $v;
					break;

				case 'offset':
					$this->_offset = $v;
					break;
				
				case '_valid':
					$this->_valid = $v;
					break;
										
				case 'external_file_attributes':
					$this->_external_file_attributes = $v;
					break;
				
				default:
					$this->_properties[$n] = $v;
					break;
			}
		}
	
		public function __get($n){
			switch($n){
				case 'comments':
					return $this->_comments;
					break;

				case 'offset':
					return $this->_offset;
					break;
				
				case '_valid':
					return $this->_valid;
					break;					
				
				case 'external_file_attributes':
					return $this->_external_file_attributes;
					break;
				
				case 'properties':
					return $this->_properties;
					break;
				
				default:
					return $this->_properties[$n];
					break;
			}
		}

		private static function __compress($data){
			$data = gzcompress($data); 
	        return substr(substr($data, 0, strlen($data) - 4), 2);
		}
	
		public function isValidFile(){
			return $this->_valid;
		}
	
		public static function unixToDOSDateTime(){
		
			$now = getdate();

			$time = array(
				str_pad(decbin($now['hours']), 5, '0', STR_PAD_LEFT),
				str_pad(decbin($now['minutes']), 6, '0', STR_PAD_LEFT),
				str_pad(decbin(($now['seconds'] >= 32 ? $now['seconds'] - 32 : $now['seconds'])), 5, '0', STR_PAD_LEFT)
			);
		
			$date = array(
		        str_pad(decbin($now['year'] - 1980), 7, '0', STR_PAD_LEFT),
		 		str_pad(decbin($now['mon']), 4, '0', STR_PAD_LEFT),
			    str_pad(decbin($now['mday']), 5, '0', STR_PAD_LEFT)
			);
	
			return array(
						bindec(implode($time)),
						bindec(implode($date))
				   );
	
		}
	
		function __construct($src, $dest, $file=true){

			$data = NULL;
		
			if($src && $file){			
				if(($data = file_get_contents($src)) === false) return;
				$this->data_uncompressed_size = filesize($src);
				$this->external_file_attributes = self::FILE;
			}
			
			elseif(!$file && $src){
				$data = $src;
				$this->data_uncompressed_size = strlen($data);
				$this->external_file_attributes = self::FILE;
			}
		
			else{
				$this->data_uncompressed_size = 0;		
				$this->external_file_attributes = self::DIRECTORY;
			}
		
			$this->version_needed = self::DEFAULT_VERSION_NEEDED;
	        $this->general_purpose_bit_flag = 0; 
	        $this->crc_32 = crc32($data);
			$this->file_name_length = strlen($dest);
			$this->file_name = $dest;
		
			list($this->modification_time, $this->modification_date) = self::unixToDOSDateTime();

			if($this->data_uncompressed_size < self::MIN_UNCOMPRESSED_SIZE){
				$this->data_compressed_size = $this->data_uncompressed_size;
				$this->data_compressed = $data;
				$this->compression_method = 0;
			}
		
			else{
				$this->data_compressed = self::__compress($data);
				$this->data_compressed_size = strlen($this->data_compressed);
				$this->compression_method = self::DEFAULT_COMPRESSION_METHOD;
			}

			$this->extra_field_length = 0;
	
			$this->_valid = true;
		
		}
	
		public function renderFileRecord(){

			return array(
				pack('s', $this->version_needed),
				pack('s', $this->general_purpose_bit_flag),
				pack('s', $this->compression_method),
				pack('s', $this->modification_time),
				pack('s', $this->modification_date),
				pack('V', $this->crc_32),
				pack('I', $this->data_compressed_size),
				pack('I', $this->data_uncompressed_size),
				pack('s', $this->file_name_length),
				pack('s', $this->extra_field_length),
				$this->file_name,
				$this->data_compressed
			);
		
		}
	
		public function renderCentralDirectoryRecord($offset){
		
			return array(
				pack('v', 0),
				pack('v', $this->version_needed),
				pack('v', 0),
				pack('v', $this->compression_method),
				pack('v', $this->modification_time),
				pack('v', $this->modification_date),
				pack('V', $this->crc_32),
				pack('V', $this->data_compressed_size),
				pack('V', $this->data_uncompressed_size),
				pack('v', $this->file_name_length),
				pack('v', 0),
				pack('v', strlen($this->comments)),
				pack('v', 0),
				pack('v', 0),
				pack('V', $this->external_file_attributes),
				pack('V', $offset),
				$this->file_name,
				$this->comments
			);
				
		}		
	}

?>