<?php

    Class Gateway{
  
        /**
        
        Usage Example:
        
            require_once(TOOLKIT . '/class.gateway.php');
            $ch = new Gateway;
            
            $ch->init();
            $ch->setopt('URL', 'http://www.yoursite.com/');
            $ch->setopt('POST', 1);
            $ch->setopt('POSTFIELDS', array('fred' => 1, 'happy' => 'yes'));
            print $ch->exec(); 

        
        **/
  
		const GATEWAY_NO_FORCE = 0;
		const GATEWAY_FORCE_CURL = 1;
		const GATEWAY_FORCE_SOCKET = 2;
		
		const CRLF = "\r\n";
		
        private $_host;
        private $_port;
        private $_path;
        
        private $_method = 'GET';
        private $_agent = 'Symphony';
        private $_headers = NULL;
   		private $_content_type = 'application/x-www-form-urlencoded; charset=utf-8';
        private $_postfields = '';
        private $_http_version = '1.1';
   		private $_returnHeaders = 0;
		private $_timeout = 4;
    
        public function init(){
        }
        
		public static function isCurlAvailable(){
			return function_exists('curl_init');
		}
       
        public function setopt($opt, $value){
        
            switch($opt){
            
                case 'URL':
                
                    $url_parsed = parse_url($value);
                    
                    $this->_host = $url_parsed['host'];
                    $this->_port = $url_parsed['port'];
                    $this->_path = $url_parsed['path'];
                     
                    if(isset($url_parsed['query'])) $this->_path .= '?' . $url_parsed['query'];
		
                    if(!$this->_port) $this->_port = 80;  
                                  
                    break;
            
            
                case 'POST':
                    $this->_method = (strtoupper($value) == '1' ? 'POST' : 'GET');
                    break;

                case 'POSTFIELDS':
                
                    if(is_array($value) && !empty($value)){
	
                        foreach($value as $key => $val){
	
							if(is_array($val)){
								
								foreach($val as $k => $v){
									$d[] =  $key . "[$k]=" . urlencode($v);
								}
								
							}else
                            	$d[] = $key . '=' . urlencode($val);
						}
                          
                        $this->_postfields = implode('&', $d);

                    }else
                        $this->_postfields = $value;
                
                    break;
                    
                case 'USERAGENT':
                    $this->_agent = $value;
                    break;
                    
                case 'HTTPHEADER':
                    $this->_headers = $value;
                    break;
                    
                case 'RETURNHEADERS':
                    $this->_returnHeaders = (intval($value) == 1 ? true : false);
                    break;
                    
                case 'CONTENTTYPE':
                	$this->_content_type = $value;
                    break;
                    
                case 'HTTPVERSION':
                	$this->_http_version = $value;
                	break;

				case 'TIMEOUT':
					$this->_timeout = max(1, intval($value));
           
            
            }
            
        
        }
  
       	public function exec($force_connection_method=GATEWAY_NO_FORCE){

			if($force_connection_method != GATEWAY_FORCE_SOCKET && self::isCurlAvailable()){			

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $this->_host . ':' . $this->_port . $this->_path);
				curl_setopt($ch, CURLOPT_HEADER, $this->_returnHeaders);
				curl_setopt($ch, CURLOPT_USERAGENT, $this->_agent);
				curl_setopt($ch, CURLOPT_PORT, $this->_port);
				@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				@curl_setopt($ch, CURLOPT_COOKIEJAR, TMP . '/cookie.txt');
				@curl_setopt($ch, CURLOPT_COOKIEFILE, TMP . '/cookie.txt');
				curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
				
				if($this->_method == 'POST') {
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_postfields);
				}
				
				##Grab the result
				$result = curl_exec($ch);

				##Close the connection
				curl_close ($ch);

				return $result;
			}
			
			##No CURL is available, use attempt to use normal sockets		
			if(!$handle = fsockopen($this->_host, $this->_port, $errno, $errstr, 30)) return false;

			else{
				
				$query = $this->_method . ' ' . $this->_path . ' HTTP/' . $this->_http_version . self::CRLF;
				$query .= 'Host: '.$this->_host . self::CRLF;
				$query .= 'Content-type: '.$this->_content_type . self::CRLF;
				$query .= 'User-Agent: '.$this->_agent . self::CRLF;
				$query .= @implode(self::CRLF, $this->_headers);
				$query .= 'Content-length: ' . strlen($this->_postfields) . self::CRLF;
				$query .= 'Connection: close' . self::CRLF . self::CRLF;
				
				if($this->_method == 'POST') $query .= $this->_postfields;				

				// send request
				if(!@fwrite($handle, $query)) return false;
				
				stream_set_blocking($handle, false);
				stream_set_timeout($handle, $this->_timeout);

				$status = stream_get_meta_data($handle);

				// get header
				while (!preg_match('/\\r\\n\\r\\n$/', $header) && !$status['timed_out']) {
					$header .= @fread($handle, 1); 
					$status = stream_get_meta_data($handle);
				}
	
				$status = socket_get_status($handle);
	
				## Get rest of the page data
				while (!feof($handle) && !$status['timed_out']){
					$response .= fread($handle, 4096);
					$status = stream_get_meta_data($handle);
				}				
	
				@fclose($handle);
			
				if(preg_match('/Transfer\\-Encoding:\\s+chunked\\r\\n/', $header)){
					
					$fp = 0;
					
					do {
						$byte = '';
						$chunk_size = '';
						
						do {
							$chunk_size .= $byte;
							$byte = substr($response, $fp, 1); $fp++;
						} while ($byte != "\r" && $byte != "\\r"); 
						
						$chunk_size = hexdec($chunk_size); // convert to real number
						
						if($chunk_size == 0) break(1);
	
						$fp++;
	
						$dechunked .= substr($response, $fp, $chunk_size); $fp += $chunk_size;
	
						$fp += 2;
						
					} while(true);   			
					
					$response = $dechunked;
					
				}
			}

			return ($this->_returnHeaders ? $headers : NULL) . $response;
		}
				
		public function flush(){
	        $this->_postfields = NULL;			
		}      
    
    }
    
