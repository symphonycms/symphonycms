<?php

	Class migration_202 extends Migration{

		static function post_notes(){
			return array(
				__('Since %1$s, the built-in image manipulation features have been replaced with the %2$s extension. Should you have uploaded (or cloned) this to your Extensions folder, be sure to enable it', array('<code>2.0.2</code>', '<a href="http://github.com/pointybeard/jit_image_manipulation/tree/master">JIT Image Manipulation</a>'))
			);
		}

	}
