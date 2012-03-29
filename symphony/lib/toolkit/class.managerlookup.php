<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Giel
 * Date: 29-03-12
 * Time: 22:39
 * File: class.managerlookup.php
 */
 
class ManagerLookup
{

	// Instance is used for singleton:
	private static $_instance;

	// Index is used for index management:
	/* @var $_index SimpleXMLElement */
	private $_index;

	/**
	 * Get a singleton instance of PageManager
	 *
	 * @return PageManager
	 */
	public static function instance()
	{
		if(!self::$_instance)
		{
			$_class = get_called_class();
			self::$_instance = new $_class;
		}
		return self::$_instance;
	}

	/**
	 * Private constructor, so this class can only be used as a singleton.
	 */
	private function __construct()
	{
		// Create an index of all pages:
		$this->_init();
	}

	protected function _init()
	{
		// This function needs to be overwritten by the corresponding managers
	}

	protected function _setIndex($path, $element_name)
	{
		$this->_index = new SimpleXMLElement('<'.$element_name.'/>');
		$_pages = glob($path);
		foreach($_pages as $_page)
		{
			$this->mergeXML($this->_index, simplexml_load_file($_page));
		}
	}

	/**
	 * @param $xml_element	SimpleXMLElement
	 * @param $append		SimpleXMLElement
	 * @return void
	 */
	protected function mergeXML($xml_element, $append)
    {
        if ($append) {
            if (strlen(trim((string) $append))==0) {
                $xml = $xml_element->addChild($append->getName());
                foreach($append->children() as $child) {
                    $this->mergeXML($xml, $child);
                }
            } else {
                $xml = $xml_element->addChild($append->getName(), (string) $append);
            }
            foreach($append->attributes() as $n => $v) {
                $xml->addAttribute($n, $v);
            }
        }
    }

}