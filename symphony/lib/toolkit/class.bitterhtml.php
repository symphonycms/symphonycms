<?php
/*------------------------------------------------------------------------------
	
	Copyright (c) 2008, Rowan Lewis, All rights reserved.
	
	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:
		* Redistributions of source code must retain the above copyright notice,
		  this list of conditions and the following disclaimer.
		* Redistributions in binary form must reproduce the above copyright
		  notice, this list of conditions and the following disclaimer in the
		  documentation and/or other materials provided with the distribution.
		* Neither the name of the PixelCarnage nor the names of its contributors
		  may be used to endorse or promote products derived from this software
		  without specific prior written permission.
	
	THIS SOFTWARE IS PROVIDED BY ROWAN LEWIS "AS IS" AND ANY EXPRESS OR IMPLIED
	WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
	MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
	EVENT SHALL ROWAN LEWIS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
	SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
	PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
	OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
	WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR
	OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
	ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
	
------------------------------------------------------------------------------*/


	class BitterLangHTML {
		
		public static function htmlentities($string) {
			return htmlentities($string, ENT_COMPAT, 'UTF-8');
		}
		
		public function process($source, $tabsize) {
			
			$tabsize = (integer)$tabsize;
			
			if ($tabsize < 1) $tabsize = 1;
			if ($tabsize > 8) $tabsize = 8;
			
			if (!function_exists('__expander')) eval("
				function __expander(\$matches) {
					return \$matches[1] . str_repeat(
						' ', strlen(\$matches[2]) * $tabsize - (strlen(\$matches[1]) % $tabsize)
					);
				}
			");
		
			while (strstr($source, "\t")) {
				$source = preg_replace_callback('%^([^\t\n]*)(\t+)%m', '__expander', $source);
			}
			
			return $this->contextMain($source);

		}
	
		protected function match($context, $regexp, $offset = 0) {
			$matches = array();
		
			if (preg_match($regexp, $context, $matches, 0, $offset)) {
				return $matches[0];
			}
		
			return '';
		}
	
		protected function position($context, $regexp, $offset = 0) {
			$matches = array();
		
			if (preg_match($regexp, $context, $matches, PREG_OFFSET_CAPTURE, $offset)) {
				return $matches[0][1];
			}
		
			return -1;
		}
	
		protected function slice($context, $start, $length = null) {
			if (!$length) return substr($context, $start);
			else return substr($context, $start, $length);
		}		
		
		protected function contextMain($source) {
			$context = $source;
			$output = '<span class="markup">';
			
			while (strlen($context)) {
				
				$regexp = '%<\?|<!(--)?|</?|&[^;\s]*;?%';
				$start = $this->position($context, $regexp);

				if ($start < 0) {
					$output .= self::htmlentities($context); break;
				}
				
				$subject = $this->match($context, $regexp); $before = '';
				
				if ($start > 0) {
					$before = $this->slice($context, 0, $start);
				}
				
				// Pre Processor:
				if ($subject == '<?') {
					$output .= $this->contextComment(
						$context, $start, $before, '%<\?.*?\?>%s'
					);
					
				// DTD:
				} else if ($subject == '<!') {
					$output .= $this->contextComment(
						$context, $start, $before, '%<!.*?>%s'
					);
					
				// Comment:
				} else if ($subject == '<!--') {
					$output .= $this->contextComment(
						$context, $start, $before, '%<!--.*?-->%s'
					);
					
				// Element:
				} else if ($subject == '<' or $subject == '</') {
					
					// Close
					if (preg_match('%^</[a-z0-9_\-:]+>%i', $this->slice($context, $start))) {
						$between = $this->match(
							$this->slice($context, $start), '%^</[a-z0-9_\-:]+>%i'
						);
						$after = $this->slice($context, $start + strlen($between));
						
						$output .= self::htmlentities($before);
						$output .= '<span class="element">';
						$output .= self::htmlentities($between);
						$output .= '</span>';
						
					// Open:
					} else if (preg_match('%^<[a-z0-9_\-:]+(\s|/?>)%i', $this->slice($context, $start))) {
						$open = $this->match($this->slice($context, $start), '%^<[a-z0-9_\-:]+%i');
						$close = $this->match($this->slice($context, $start), '%/?>%');
						$between = $this->match(
							$this->slice($context, $start + strlen($open)), '%[^>]*(?=/>)|[^>]*(?=>)%i'
						);
						$after = $this->slice($context, $start + strlen($open) + strlen($between) + strlen($close));
						
						$output .= self::htmlentities($before);
						$output .= '<span class="element">';
						$output .= self::htmlentities($open);
						$output .= $this->contextElement($between);
						$output .= self::htmlentities($close);
						$output .= '</span>';
						
					// Invalid:
					} else {
						$between = $this->match(
							$this->slice($context, $start), '%<([^<>]+>?)%i'
						);
						$after = $this->slice($context, $start + strlen($between));
						
						$output .= self::htmlentities($before);
						$output .= '<span class="error">';
						$output .= self::htmlentities($between);
						$output .= '</span>';
					}
					
					$context = $after;
					
				// Entity:
				} else if (preg_match('%&[^;\s]*;?%', $subject)) {
					$output .= $this->contextEntity($context, $start, $before, $subject);
					
				// Unknown:
				} else {
					$output .= self::htmlentities($context); break;
				}
			}
			
			$output .= '</span>';
			
			return $output;
		}
		
		protected function contextComment(&$context, $start, $before, $normal) {
			$output = '';
			$between = $this->match(
				$this->slice($context, $start), $normal
			);
			
			// Valid:
			if ($between) {
				$output .= self::htmlentities($before);
				$output .= '<span class="comment">';
				$output .= self::htmlentities($between);
				$output .= '</span>';
				
			// Invalid:
			} else {
				$between = $this->match(
					$this->slice($context, $start), '%<!([^<>]+>?)%s'
				);
				
				$output .= self::htmlentities($before);
				$output .= '<span class="error comment">';
				$output .= self::htmlentities($between);
				$output .= '</span>';
			}
			
			$context = $this->slice($context, $start + strlen($between));
			
			return $output;
		}
		
		protected function contextElement($source) {
			$context = $source; $output = '';
			
			while (strlen($context)) {
				$regexp = '%[a-z0-9_\-:]+\s*=\s*|[^\s]%';
				$start = $this->position($context, $regexp);
				
				if ($start < 0) {
					$output .= self::htmlentities($context); break;
				}
				
				$subject = $this->match($context, $regexp); $before = '';
				
				if ($start > 0) {
					$before = $this->slice($context, 0, $start);
				}
				
				// Attribute:
				if (preg_match('%[^\s]+\s*=\s*%', $subject)) {
					$after = $this->slice($context, $start + strlen($subject));
					$value = "";
					
					// String:
					if (!$value = $this->match($after, '%^"[^"\\\]*(?:\\\.[^"\\\]*)*"%')) {
						$value = $this->match($after, '%^\'[^\'\\\]*(?:\\\.[^\'\\\]*)*\'%');
					}
					
					// Is valid?
					if ($value) {
						$output .= self::htmlentities($before);
						$output .= '<span class="attribute">';
						$output .= self::htmlentities($subject);
						$output .= '</span>';
						$output .= '<span class="string">';
						$output .= $this->contextString($value);
						$output .= '</span>';
						
					// Invalid:
					} else {
						$output .= self::htmlentities($before);
						$output .= '<span class="error">';
						$output .= self::htmlentities($subject);
						$output .= '</span>';
					}
					
					$after = $this->slice($after, strlen($value));
					$context = $after;
					
				// Error:
				} else if (preg_match('%[^\s]%', $subject)) {
					$between = $this->match($context, '%[^\s]+%');
					$after = $this->slice($context, $start + strlen($between));
					
					$output .= self::htmlentities($before);
					$output .= '<span class="error">';
					$output .= self::htmlentities($between);
					$output .= '</span>';
					
					$context = $after;
					
				// Unknown:
				} else {
					$output .= self::htmlentities($context); break;
				}
			}
			
			return $output;
		}
		
		protected function contextEntity(&$context, $start, $before, $subject) {
			$after = $this->slice($context, $start + strlen($subject));
			$class = "error"; $output = '';
			
			// Is valid?
			if (preg_match('%&(#[0-9]{1,4}|#x[a-f0-9]{1,4}|[a-z\-]+);%i', $subject)) $class = "entity";
			
			$output .= self::htmlentities($before);
			$output .= '<span class="' . $class . '">';
			$output .= self::htmlentities($subject);
			$output .= '</span>';
			
			$context = $after;
			
			return $output;
		}
		
		protected function contextString($source) {
			$context = $source; $output = '';
		
			while (strlen($context)) {
				$regexp = '%&[^;\s]*;?|\{|\$%';
				$start = $this->position($context, $regexp);
				
				if ($start < 0) {
					$output .= self::htmlentities($context); break;
				}
				
				$subject = $this->match($context, $regexp); $before = '';
				
				if ($start > 0) {
					$before = $this->slice($context, 0, $start);
				}
				
				// Entity:
				if (preg_match('%&[^;\s]*;?%', $subject)) {
					$output .= $this->contextEntity($context, $start, $before, $subject);
					
				// XPath:
				} else if ($subject == '{') {
					$between = $this->match(
						$this->slice($context, $start), '%{.*?}%s'
					);
					
					// Valid:
					if ($between) {
						$output .= self::htmlentities($before);
						$output .= '<span class="xpath">';
						$output .= self::htmlentities($between);
						$output .= '</span>';
						
					// Invalid:
					} else {
						$between = $this->match(
							$this->slice($context, $start), '%.*%s'
						);
						
						$output .= self::htmlentities($before);
						$output .= '<span class="error">';
						$output .= self::htmlentities($between);
						$output .= '</span>';
					}
					
					$context = $this->slice($context, $start + strlen($between));
					
				// Variable:
				} else if ($subject == '$') {
					$between = $this->match(
						$this->slice($context, $start), '%\$[a-z0-9_\-:]+%s'
					);
					
					// Valid:
					if ($between) {
						$output .= self::htmlentities($before);
						$output .= '<span class="variable">';
						$output .= self::htmlentities($between);
						$output .= '</span>';
						
					// Invalid:
					} else {
						$between = $this->match(
							$this->slice($context, $start), '%\S*%s'
						);
						
						$output .= self::htmlentities($before);
						$output .= '<span class="error">';
						$output .= self::htmlentities($between);
						$output .= '</span>';
					}
					
					$context = $this->slice($context, $start + strlen($between));
					
				// Unknown:
				} else {
					$output .= self::htmlentities($context); break;
				}
			}
			
			return $output;
		}
	}