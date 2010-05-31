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

		const FORCE_CURL = 'curl';
		const FORCE_SOCKET = 'socket';
		
		const CRLF = "\r\n";
		
        private $_host;
        private $_scheme;
        private $_port;
        private $_path;
        private $_url;

		private $_info_last = array();
        private $_method = 'GET';
        private $_agent = 'Symphony';
        private $_headers = NULL;
   		private $_content_type = 'application/x-www-form-urlencoded; charset=utf-8';
        private $_postfields = '';
        private $_http_version = '1.1';
   		private $_returnHeaders = 0;
		private $_timeout = 4;
    	private $_custom_opt = array();
    	
        public function init(){
        }
        
		public static function isCurlAvailable(){
			return function_exists('curl_init');
		}
       
        public function setopt($opt, $value){
        
            switch($opt){
            
                case 'URL':

                	$this->_url = $value;

                    $url_parsed = parse_url($value);
                    
                    $this->_host = $url_parsed['host'];
					
					$this->_scheme = 'http://';
					if(isset($url_parsed['scheme']) && strlen(trim($url_parsed['scheme'])) > 0){
						$this->_scheme = $url_parsed['scheme'];
					}
					
					$this->_port = NULL;
					if(isset($url_parsed['port'])){
                    	$this->_port = $url_parsed['port'];
					}
					
					if(isset($url_parsed['path'])){
                    	$this->_path = $url_parsed['path'];
					}
                     
                    if(isset($url_parsed['query'])){
						$this->_path .= '?' . $url_parsed['query'];
					}  
                    
					// Allow basic HTTP authentiction
					if(isset($url_parsed['user']) && isset($url_parsed['pass'])){
						$this->setopt(CURLOPT_USERPWD, sprintf('%s:%s', $url_parsed['user'], $url_parsed['pass']));
						$this->setopt(CURLOPT_HTTPAUTH, CURLAUTH_ANY);
					}
					
					// Better support for HTTPS requests
					if($url_parsed['scheme'] == 'https'){
						$this->setopt(CURLOPT_SSL_VERIFYPEER, false);
					}
              
                    break;
            
            
                case 'POST':
                    $this->_method = ($value == 1 ? 'POST' : 'GET');
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
            		break;

				default:
					$this->_custom_opt[$opt] = $value;
					break;
           
            
            }
            
        
        }
  	
		public function getInfoLast(){
			return $this->_info_last;
		}

       	public function exec($force_connection_method=NULL){

			if($force_connection_method != self::FORCE_SOCKET && self::isCurlAvailable()){
				$ch = curl_init();

				curl_setopt($ch, CURLOPT_URL, 
					sprintf("%s://%s%s%s", $this->_scheme, $this->_host, (!is_null($this->_port) ? ':' . $this->_port : NULL), $this->_path)
				);
				curl_setopt($ch, CURLOPT_HEADER, $this->_returnHeaders);
				curl_setopt($ch, CURLOPT_USERAGENT, $this->_agent);
				curl_setopt($ch, CURLOPT_PORT, $this->_port);
				@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				@curl_setopt($ch, CURLOPT_COOKIEJAR, TMP . '/cookie.txt');
				@curl_setopt($ch, CURLOPT_COOKIEFILE, TMP . '/cookie.txt');
				curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
				
                if(is_array($this->_headers) && !empty($this->_headers)) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_headers);
                }

				if($this->_method == 'POST') {
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $this->_postfields);
				}
				
				if(is_array($this->_custom_opt) && !empty($this->_custom_opt)){
					foreach($this->_custom_opt as $opt => $value){
						curl_setopt($ch, $opt, $value);
					}
				}
				
				##Grab the result
				$result = curl_exec($ch);

				$this->_info_last = curl_getinfo($ch);
				
				##Close the connection
				curl_close ($ch);

				return $result;
			}

			##No CURL is available, use attempt to use normal sockets
			if(!$handle = fsockopen($this->_host, $this->_port, $errno, $errstr, $this->_timeout)) return false;

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

			// Following code emulates part of the function curl_getinfo()
			preg_match('/Content-Type:\s*([^\r\n]+)/i', $header, $match);
			$content_type = $match[1];
			
			preg_match('/HTTP\/\d+.\d+\s+(\d+)/i', $header, $match);
			$status = $match[1];			
			
			$this->_info_last = array(
				'url' => $this->_url,
				'content_type' => $content_type,
				'http_code' => $status
			);
			
			return ($this->_returnHeaders ? $header : NULL) . $response;
		}

		public function flush(){
			$this->_postfields = NULL;
		}      
    
    }
    
