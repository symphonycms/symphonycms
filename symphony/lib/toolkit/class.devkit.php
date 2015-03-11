<?php

/**
 * @package toolkit
 */
/**
 * Devkit extends the HTMLPage class to provide an object representation
 * of a Symphony Devkit page. Devkit's are used to aid in debugging by providing
 * raw XML representations of data sources and parameters and to help provide
 * profiling. There are two Symphony Devkit's currently, Debug and Profile. Devkit
 * pages are restricted to Symphony Author's and require them to be authenticated
 * to view them.
 */

class DevKit extends HTMLPage
{
    /**
     * The Devkit's `$_GET` query string
     * @var string
     */
    protected $_query_string = '';

    /**
     * An instance of the XSLTPage, usually FrontendPage
     * @var XSLTPage
     */
    protected $_page = null;

    /**
     * An associative array of the details of the Page that is being 'Devkitted'.
     * The majority of this information is from `tbl_pages` table.
     * @var array
     */
    protected $_pagedata = null;

    /**
     * The XML of the page that the XSLT will be applied to, this includes any
     * datasource results.
     * @var string
     */
    protected $_xml = null;

    /**
     * An array of the page parameters, including those provided by datasources.
     * @var array
     */
    protected $_param = array();

    /**
     * The resulting Page after it has been transformed, as a string. This is
     * similar to what you would see if you 'view-sourced' a page in a web browser
     * @var string
     */
    protected $_output = '';

    /**
     * Builds the Includes for a Devkit and sets the Content Type
     * to be text/html. The default Symphony devkit stylesheet
     * is the only include. The default doctype is enables HTML5
     */
    protected function buildIncludes()
    {
        $this->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');

        $this->Html->setElementStyle('html');
        $this->Html->setDTD('<!DOCTYPE html>');
        $this->Html->setAttribute('lang', Lang::get());
        $this->addElementToHead(new XMLElement(
            'meta',
            null,
            array(
                'http-equiv'    => 'Content-Type',
                'content'       => 'text/html; charset=UTF-8'
            )
        ));
        $this->addStylesheetToHead(ASSETS_URL . '/css/devkit.min.css', 'screen', null, false);
    }

    /**
     * This function will build the `<title>` element and create a default
     * `<h1>` with an anchor to this query string
     *
     * @param XMLElement $wrapper
     *    The parent `XMLElement` to add the header to
     * @throws InvalidArgumentException
     */
    protected function buildHeader(XMLElement $wrapper)
    {
        $this->setTitle(__(
            '%1$s &ndash; %2$s &ndash; %3$s',
            array(
                $this->_pagedata['title'],
                __($this->_title),
                __('Symphony')
            )
        ));

        $h1 = new XMLElement('h1');
        $h1->appendChild(Widget::Anchor($this->_pagedata['title'], ($this->_query_string ? '?' . trim(html_entity_decode($this->_query_string), '&') : '.')));

        $wrapper->appendChild($h1);
    }

    /**
     * Using DOMDocument, construct the Navigation list using the `devkit_navigation.xml`
     * file in the `ASSETS` folder. The default navigation file is an empty `<navigation>`
     * element. The `ManipulateDevKitNavigation` delegate allows extensions
     * to inject items into the navigation. The navigation is build by iterating over `<item>`
     * elements added. The idea is that all Devkit's can be accessed using the Navigation.
     *
     * @uses ManipulateDevKitNavigation
     * @param XMLElement $wrapper
     *    The parent XMLElement to add the navigation to
     * @throws InvalidArgumentException
     */
    protected function buildNavigation(XMLElement $wrapper)
    {
        $xml = new DOMDocument();
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput = true;
        $xml->load(ASSETS . '/xml/devkit_navigation.xml');
        $root = $xml->documentElement;
        $list = new XMLElement('ul');
        $list->setAttribute('id', 'navigation');

        // Add edit link:
        $item = new XMLElement('li');
        $item->appendChild(Widget::Anchor(
            __('Edit'),
            SYMPHONY_URL . '/blueprints/pages/edit/' . $this->_pagedata['id'] . '/'
        ));
        $list->appendChild($item);

        // Translate navigation names:
        if ($root->hasChildNodes()) {
            foreach ($root->childNodes as $item) {
                if ($item->tagName == 'item') {
                    $item->setAttribute('name', __($item->getAttribute('name')));
                }
            }
        }

        /**
         * Allow navigation XML to be manipulated before it is rendered.
         *
         * @delegate ManipulateDevKitNavigation
         * @param string $context
         *  '/frontend/'
         * @param DOMDocument $xml
         */
        Symphony::ExtensionManager()->notifyMembers(
            'ManipulateDevKitNavigation',
            '/frontend/',
            array(
                'xml'   => $xml
            )
        );

        if ($root->hasChildNodes()) {
            foreach ($root->childNodes as $node) {
                if ($node->getAttribute('active') === 'yes') {
                    $item = new XMLElement('li', $node->getAttribute('name'));

                } else {
                    $item = new XMLElement('li');
                    $item->appendChild(Widget::Anchor(
                        $node->getAttribute('name'),
                        '?' . $node->getAttribute('handle') . $this->_query_string
                    ));
                }

                $list->appendChild($item);
            }
        }

        $wrapper->appendChild($list);
    }

    /**
     * This function builds a Jump menu, which is what a Devkit uses as it's
     * internal navigation. Items are added to the Jump menu using the
     * buildJumpItem function
     *
     * @see buildJumpItem
     * @param XMLElement $wrapper
     *  The parent XMLElement that the jump menu will be appended
     *  to. By default this is `<div id='jump'>`
     */
    protected function buildJump(XMLElement $wrapper)
    {

    }

    /**
     *
     * @param string $name
     *    The name of the jump
     * @param string $link
     *    The link for this jump item
     * @param boolean $active
     *    Whether this is the active link, if true, this will add an
     *    active class to the link built. By default this is false
     * @throws InvalidArgumentException
     * @return XMLElement
     */
    protected function buildJumpItem($name, $link, $active = false)
    {
        $item = new XMLElement('li');
        $anchor = Widget::Anchor($name, $link);
        $anchor->setAttribute('class', 'inactive');

        if ($active == true) {
            $anchor->setAttribute('class', 'active');
        }

        $item->appendChild($anchor);

        return $item;
    }

    /**
     * The content of the Devkit, defaults to empty.
     *
     * @param XMLElement $wrapper
     *  The parent XMLElement that the content will be appended
     *  to. By default this is `<div id='content'>`
     */
    protected function buildContent(XMLElement $wrapper)
    {

    }

    /**
     * The prepare function acts a pseudo constructor for the Devkit,
     * setting some base variables with the given parameters
     *
     * @param XSLTPage $page
     *  An instance of the XSLTPage, usually FrontendPage
     * @param array $pagedata
     *  An associative array of the details of the Page that is
     *  being 'Devkitted'. The majority of this information is from
     *  tbl_pages table.
     * @param string $xml
     *  The XML of the page that the XSLT will be applied to, this includes
     *  any datasource results.
     * @param array $param
     *  An array of the page parameters, including those provided by
     *  datasources.
     * @param string $output
     *  The resulting Page after it has been transformed, as a string. This is
     *  similar to what you would see if you 'view-sourced' a page.
     */
    public function prepare(XSLTPage $page, Array $pagedata, $xml, Array $param, $output)
    {
        $this->_page = $page;
        $this->_pagedata = $pagedata;
        $this->_xml = $xml;
        $this->_param = $param;
        $this->_output = $output;

        if (is_null($this->_title)) {
            $this->_title = __('Utility');
        }
    }

    /**
     * Called when page is generated, this function calls each of the other
     * other functions in this page to build the Header, the Navigation,
     * the Jump menu and finally the content. This function calls it's parent
     * generate function
     *
     * @see toolkit.HTMLPage#generate()
     * @throws InvalidArgumentException
     * @return string
     */
    public function build()
    {
        $this->buildIncludes();
        $this->_view = General::sanitize($this->_view);

        $header = new XMLElement('div');
        $header->setAttribute('id', 'header');
        $jump = new XMLElement('div');
        $jump->setAttribute('id', 'jump');
        $content = new XMLElement('div');
        $content->setAttribute('id', 'content');

        $this->buildHeader($header);
        $this->buildNavigation($header);

        $this->buildJump($jump);
        $header->appendChild($jump);

        $this->Body->appendChild($header);

        $this->buildContent($content);
        $this->Body->appendChild($content);

        return parent::generate();
    }
}
