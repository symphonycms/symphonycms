<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Giel
 * Date: 29-03-12
 * Time: 22:39
 * File: class.managerlookup.php
 */
 
class Lookup
{
	const LOOKUP_PAGES 		= 'pages';
	const LOOKUP_SECTIONS 	= 'sections';
	const LOOKUP_FIELDS 	= 'fields';

	// Instance is used for singleton:
	private static $_instances;

	// Index is used for index management:
	/* @var $_index SimpleXMLElement */
	private $_index;

	private $_type;
	private $_path;
	private $_element_name;

	/**
	 * Get the index
	 *
	 * @param $type
	 *  The type of the index
	 * @return Lookup
	 */
	public static function index($type)
	{
		if(!self::$_instances)
		{
			self::$_instances = array();
		}
		if(!self::$_instances[$type])
		{
			self::$_instances[$type] = new Lookup($type);
		}
		return self::$_instances[$type];
	}

	/**
	 * Private constructor, so this class can only be used as a singleton.
	 */
	private function __construct($type)
	{
		$this->_type = $type;
		// Create an index:
		switch($this->_type)
		{
			case self::LOOKUP_PAGES :
				{
					$this->_path = PAGES.'/*.xml';
					$this->_element_name = 'pages';
					$this->reIndex();
					break;
				}
		}
	}

	/**
	 * Save a lookup
	 *
	 * @param $hash
	 *  The unique hash of the item that is saved
	 * @return int
	 *  The ID internal used in the database.
	 */
	public function save($hash)
	{
		switch($this->_type)
		{
			case self::LOOKUP_PAGES :
				{
					return Symphony::Database()->insert(array('hash'=>$hash), 'tbl_lookup_pages');
					break;
				}
		}
		return false;
	}

	/**
	 * Delete a lookup
	 *
	 * @param $idOrHash
	 *  The ID of the item, or the hash value
	 * @return void
	 */
	public function delete($idOrHash)
	{
		switch($this->_type)
		{
			case self::LOOKUP_PAGES :
				{
					if(is_numeric($idOrHash) && strlen($idOrHash) != 32)
					{
						// Assume it's an ID
						Symphony::Database()->delete('tbl_lookup_pages', '`id` = '.$idOrHash);
					} else {
						// Assume it's a hash
						Symphony::Database()->delete('tbl_lookup_pages', '`hash` = \''.$idOrHash.'\'');
					}
					break;
				}
		}
	}

	/**
	 * Run an XPath expression on the index
	 *
	 * @param $path
	 *  The XPath expression
	 * @param bool $singleValue
	 *  Does this function return a single value?
	 * @return bool|SimpleXMLElement|SimpleXMLElement[]
	 */
	public function xpath($path, $singleValue = false)
	{
		if(!$singleValue)
		{
			return $this->_index->xpath('/'.$this->_type.'/'.$path);
		} else {
			$_result = $this->_index->xpath('/'.$this->_type.'/'.$path);
			if(count($_result) > 0)
			{
				return $_result[0];
			} else {
				return false;
			}
		}
	}

	/**
	 * Return the ID according to the hash
	 *
	 * @param $hash
	 *  The hash
	 * @return int
	 */
	public function getId($hash)
	{
		return Symphony::Database()->fetchVar('id', 0,
			sprintf('SELECT `id` FROM `tbl_lookup_pages` WHERE `hash` = \'%s\';', $hash));
	}

	/**
	 * Return the hash according to the ID
	 *
	 * @param $id
	 *  The id
	 * @return string
	 */
	public function getHash($id)
	{
		return Symphony::Database()->fetchVar('hash', 0,
			sprintf('SELECT `hash` FROM `tbl_lookup_pages` WHERE `id` = %d;', $id));
	}

	/**
	 * Fetch the index
	 *
	 * @param $orderBy
	 *  The key to order by
	 * @param string $orderDirection
	 *  The direction (asc or desc)
	 * @param bool $sortNumeric
	 *  Should sorting be numeric (default) or as a string?
	 * @param string $where
	 *  An xpath expression to filter on
	 * @return array
	 *  An array with SimpleXMLElements
	 */
	public function fetch($where = null, $orderBy = null, $orderDirection = 'asc', $sortNumeric = true)
	{
		// Build the new array:
		$array = array();

		// @todo: one day, this whole fetch-function is going to use a nice simple xpath expression to get them pages
		if($where != null && isset($where['xpath']))
		{
			$array = $this->_index->xpath($where['xpath']);
		} else {
			foreach($this->_index->children() as $_item)
			{
				if(!empty($where))
				{
					/* See if this item passes the filter:
					 * array(
					 *     'id'     => array('eq', 12),
					 *     'name'   => array('neq', 'tom'),
					 *     'nr'		=> array('gt', 10),
					 *     'nr'     => array('lt', 20),
					 *     'nr'     => array('gte', 10),
					 *     'nr'     => array('lte', 20)
					 * );
					 */
					$passes = false;
					foreach($where as $key => $expression)
					{
						switch($expression[0])
						{
							case 'eq' :
								{
									$passes = (string)$_item->$key == (string)$expression[1];
									break;
								}
							case 'neq' :
								{
									$passes = (string)$_item->$key != (string)$expression[1];
									break;
								}
							case 'gt' :
								{
									$passes = (float)$_item->$key > (float)$expression[1];
									break;
								}
							case 'lt' :
								{
									$passes = (float)$_item->$key < (float)$expression[1];
									break;
								}
							case 'gte' :
								{
									$passes = (float)$_item->$key >= (float)$expression[1];
									break;
								}
							case 'lte' :
								{
									$passes = (float)$_item->$key <= (float)$expression[1];
									break;
								}
						}
					}
					if($passes)
					{
						$array[] = $_item;
					}
				} else {
					// Just add it:
					$array[] = $_item;
				}
			}
		}
		// Order the array:
		if($orderBy != null)
		{
			$sorter = array();
			// Create an indexed array of items:
			foreach($array as $_item)
			{
				$sorter[(string)$_item->$orderBy] = $_item;
			}
			// Sort the array:
			if($sortNumeric) {
				ksort($sorter, SORT_NUMERIC);
			} else {
				ksort($sorter, SORT_STRING);
			}
			if($orderDirection == 'desc')
			{
				$sorter = array_reverse($sorter);
			}
			// Build the new array:
			$array = array();
			foreach($sorter as $_item)
			{
				$array[] = $_item;
			}
		}
		return $array;
	}

	/**
	 * Get the maximum value of an item
	 *
	 * @param $name
	 *  The name of the element
	 * @return int
	 */
	public function getMax($name)
	{
		$_max = 0;
		foreach($this->_index->children() as $_item)
		{
			if((int)$_item->$name > $_max) { $_max = (int)$_item->$name; }
		}
		return $_max;
	}

	/**
	 * Setup the index. The index is a SimpleXMLElement which stores all information about all
	 * items. Therefore, the setup only needs to be loaded once, and not for each request.
	 */
	public function reIndex()
	{
		$this->_index = new SimpleXMLElement('<'.$this->_element_name.'/>');
		$_pages = glob($this->_path);
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
	private function mergeXML($xml_element, $append)
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