<?php

/**
 * @package toolkit
 */
/**
 * @deprecated @since Symphony 2.6.0
 * The Gateway class has been renamed to HTTPGateway.
 * 
 * The Gateway class provides a standard way to interact with other pages.
 * By default it is essentially a wrapper for CURL, but if that is not available
 * it falls back to use sockets.
 * @example
 *  `
 * require_once(TOOLKIT . '/class.gateway.php');
 * $ch = new Gateway;
 * $ch->init('http://www.example.com/');
 * $ch->setopt('POST', 1);
 * $ch->setopt('POSTFIELDS', array('fred' => 1, 'happy' => 'yes'));
 * print $ch->exec();
 * `
 */

require_once(TOOLKIT . '/class.httpgateway.php');

class Gateway extends HTTPGateway
{

}
