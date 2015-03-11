<?php
/**
 * @package content
 */
/**
 * The AjaxParameters returns an JSON array of all available parameters.
 */

class contentAjaxParameters extends JSONPage
{
    private $template = '$%s';

    public function view()
    {
        $params = array();
        $filter = $_GET['query'];

        if ($_GET['template']) {
            $this->template = General::sanitize($_GET['template']);
        }

        // Environment parameters
        if ($filter == 'env') {
            $params = array_merge($params, $this->__getEnvParams());

            // Page parameters
        } elseif ($filter == 'page') {
            $params = array_merge($params, $this->__getPageParams());

            // Data source parameters
        } elseif ($filter == 'ds') {
            $params = array_merge($params, $this->__getDSParams());

            // All parameters
        } else {
            $params = array_merge($params, $this->__getEnvParams());
            $params = array_merge($params, $this->__getPageParams());
            $params = array_merge($params, $this->__getDSParams());
        }

        foreach ($params as $param) { 
            if (empty($filter) || strripos($param, $filter) !== false) {
                $this->_Result[] = $param;
            }
        }

        sort($this->_Result);
    }

    /**
     * Utilities
     */
    private function __getEnvParams()
    {
        $params = array();
        $env = array('today', 'current-time', 'this-year', 'this-month', 'this-day', 'timezone', 'website-name', 'page-title', 'root', 'workspace', 'root-page', 'current-page', 'current-page-id', 'current-path', 'current-query-string', 'current-url', 'cookie-username', 'cookie-pass', 'page-types', 'upload-limit');

        foreach ($env as $param) {
            $params[] = sprintf($this->template, $param);
        }

        return $params;
    }

    private function __getPageParams()
    {
        $params = array();
        $pages = PageManager::fetch(true, array('params'));

        foreach ($pages as $key => $pageparams) {
            if (empty($pageparams['params'])) {
                continue;
            }

            $pageparams = explode('/', $pageparams['params']);

            foreach ($pageparams as $pageparam) {
                $param = sprintf($this->template, $pageparam);

                if (!in_array($param, $params)) {
                    $params[] = $param;
                }
            }
        }

        return $params;
    }

    private function __getDSParams()
    {
        $params = array();
        $datasources = DatasourceManager::listAll();

        foreach ($datasources as $datasource) {
            $current = DatasourceManager::create($datasource['handle'], array(), false);

            // Get parameters
            if (is_array($current->dsParamPARAMOUTPUT)) {
                foreach ($current->dsParamPARAMOUTPUT as $id => $param) {
                    $params[] = sprintf($this->template, 'ds-' . Lang::createHandle($datasource['name']) . '.' . Lang::createHandle($param));
                }
            }
        }

        return $params;
    }
}
