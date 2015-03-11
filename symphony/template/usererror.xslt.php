<?php

include_once TOOLKIT . '/class.htmlpage.php';

$Page = new HTMLPage();

$Page->Html->setElementStyle('html');

$Page->Html->setDTD('<!DOCTYPE html>');
$Page->Html->setAttribute('xml:lang', 'en');
$Page->addElementToHead(new XMLElement('meta', null, array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset=UTF-8')), 0);
$Page->addStylesheetToHead(ASSETS_URL . '/css/symphony.min.css', 'screen', null, false);

$Page->setHttpStatus($e->getHttpStatusCode());
$Page->addHeaderToPage('Content-Type', 'text/html; charset=UTF-8');
$Page->addHeaderToPage('Symphony-Error-Type', 'xslt');

$Page->setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('XSLT Processing Error'))));
$Page->Body->setAttribute('id', 'error');

$div = new XMLElement('div', null, array('class' => 'frame'));
$ul = new XMLElement('ul');
$li = new XMLElement('li');
$li->appendChild(new XMLElement('h1', __('XSLT Processing Error')));
$li->appendChild(new XMLElement('p', __('This page could not be rendered due to the following XSLT processing errors:')));
$ul->appendChild($li);

$errors_grouped = array();

list($key, $val) = $e->getAdditional()->proc->getError(false, true);

do {
    if (preg_match('/^loadXML\(\)/i', $val['message']) && preg_match_all('/line:\s+(\d+)/i', $val['message'], $matches)) {
        $errors_grouped['xml'][] = array('line'=>$matches[1][0], 'raw'=>$val);
    } elseif (preg_match_all('/pages\/([^.\/]+\.xsl)\s+line\s+(\d+)/i', $val['message'], $matches) || preg_match_all('/pages\/([^.\/]+\.xsl):(\d+):/i', $val['message'], $matches)) {
        $errors_grouped['page'][$matches[1][0]][] = array('line'=>$matches[2][0], 'raw'=>$val);
    } elseif (preg_match_all('/utilities\/([^.\/]+\.xsl)\s+line\s+(\d+)/i', $val['message'], $matches)) {
        $errors_grouped['utility'][$matches[1][0]][] = array('line'=>$matches[2][0], 'raw'=>$val);
    } else {
        $val['parts'] = explode(' ', $val['message'], 3);
        $errors_grouped['general'][] = $val;
    }

} while (list($key, $val) = $e->getAdditional()->proc->getError());

$query_string = General::sanitize($Page->__buildQueryString());

if (strlen(trim($query_string)) > 0) {
    $query_string = "&amp;{$query_string}";
}

foreach ($errors_grouped as $group => $data) {
    switch ($group) {
        case 'general':
            $error = new XMLElement('li', '<header class="frame-header">' . __('General') . '<a class="debug" href="?debug' . $query_string .'" title="' . __('Show debug view') . '">' . __('Debug') . '</a></header>');
            $content = new XMLElement('div', null, array('class' => 'content'));
            $list = new XMLElement('ul');
            $file = null;
            $line = null;

            foreach ($data as $index => $e) {

                // Highlight error
                $class = array();
                if (strpos($data[$index + 1]['message'], '^') !== false) {
                    $class = array('class' => 'error');
                }

                // Don't show markers
                if (strpos($e['message'], '^') === false) {
                    $parts = explode('(): ', $e['message']);

                    // Function
                    preg_match('/(.*)\:(\d+)\:/', $e['parts'][1], $current);
                    if ($data[$index - 1]['parts'][0] != $e['parts'][0] || (strpos($data[$index - 1]['message'], '^') !== false && $data[$index - 2]['message'] != $data[$index + 1]['message'])) {
                        $list->appendChild(
                            new XMLElement(
                                'li',
                                '<code><em>' . $e['parts'][0] . ' ' . $current[1] . '</em></code>'
                            )
                        );
                    }

                    // Store current file and line
                    if (count($current) > 2) {
                        $file = $current[1];
                        $line = $current[2];
                    }

                    // Error
                    if (!empty($class)) {
                        if (isset($data[$index + 3]) && !empty($parts[1]) && strpos($data[$index + 3]['message'], $parts[1]) === false) {
                            $position = explode('(): ', $data[$index + 1]['message']);
                            $length = max(0, strlen($position[1]) - 1);
                            $list->appendChild(
                                new XMLElement(
                                    'li',
                                    '<code>&#160;&#160;&#160;&#160;' . str_replace(' ', '&#160;', trim(htmlspecialchars(substr($parts[1], 0, $length))) . '<b>' . htmlspecialchars(substr($parts[1], $length, 1)) . '</b>' . htmlspecialchars(substr($parts[1], $length + 1))) . '</code>',
                                    $class
                                )
                            );

                            if (isset($file, $line)) {
                                // Show in debug
                                $filename = explode(WORKSPACE . '/', $file);
                                $list->appendChild(
                                    new XMLElement(
                                        'li',
                                        '<code>&#160;&#160;&#160;&#160;<a href="?debug=/workspace/' . $filename[1] . '#line-' . $line .'" title="' . __('Show debug view for %s', array($filename[1])) . '">' . __('Show line %d in debug view', array($line)) . '</a></code>'
                                    )
                                );
                            }
                        }

                        // Message
                    } else {
                        $list->appendChild(
                            new XMLElement(
                                'li',
                                '<code>&#160;&#160;&#160;&#160;' . (strpos($e['parts'][1], '/') !== 0 ? $e['parts'][1] . ' ' : '') . str_replace(' ', '&#160;', $e['parts'][2]) . '</code>'
                            )
                        );
                    }
                }
            }

            $content->appendChild($list);
            $error->appendChild($content);
            $ul->appendChild($error);

            break;
        case 'page':
            foreach ($data as $filename => $errors) {
                $error = new XMLElement('li', '<header class="frame-header">' . $filename . '<a class="debug" href="?debug=/workspace/pages/' .  $filename . $query_string .'" title="' . __('Show debug view') . '">' . __('Debug') . '</a></header>');
                $content = new XMLElement('div', null, array('class' => 'content'));
                $list = new XMLElement('ul');

                foreach ($errors as $e) {
                    if (!is_array($e)) {
                        continue;
                    }

                    $parts = explode('(): ', $e['raw']['message']);

                    $list->appendChild(
                        new XMLElement(
                            'li',
                            '<code><em>' . $parts[0] . '():</em></code>'
                        )
                    );
                    $list->appendChild(
                        new XMLElement(
                            'li',
                            '<code>&#160;&#160;&#160;&#160;' . $parts[1] . '</code>'
                        )
                    );
                    $list->appendChild(
                        new XMLElement(
                            'li',
                            '<code>&#160;&#160;&#160;&#160;<a href="?debug=/workspace/pages/' . $filename . $query_string . '#line-' . $e['line'] .'" title="' . __('Show debug view for %s', array($filename)) . '">' . __('Show line %d in debug view', array($e['line'])) . '</a></code>'
                        )
                    );
                }

                $content->appendChild($list);
                $error->appendChild($content);
                $ul->appendChild($error);
            }

            break;
        case 'utility':
            foreach ($data as $filename => $errors) {
                $error = new XMLElement('li', '<header class="frame-header">' . $filename . '<a class="debug" href="?debug=/workspace/utilities/' .  $filename . $query_string .'" title="' . __('Show debug view') . '">' . __('Debug') . '</a></header>');
                $content = new XMLElement('div', null, array('class' => 'content'));
                $list = new XMLElement('ul');

                foreach ($errors as $e) {
                    if (!is_array($e)) {
                        continue;
                    }

                    $parts = explode('(): ', $e['raw']['message']);

                    $list->appendChild(
                        new XMLElement(
                            'li',
                            '<code><em>' . $parts[0] . '():</em></code>'
                        )
                    );
                    $list->appendChild(
                        new XMLElement(
                            'li',
                            '<code>&#160;&#160;&#160;&#160;' . $parts[1] . '</code>'
                        )
                    );
                    $list->appendChild(
                        new XMLElement(
                            'li',
                            '<code>&#160;&#160;&#160;&#160;<a href="?debug=/workspace/utilities/' .  $filename . $query_string . '#line-' . $e['line'] .'" title="' . __('Show debug view for %s', array($filename)) . '">' . __('Show line %d in debug view', array($e['line'])) . '</a></code>'
                        )
                    );
                }

                $content->appendChild($list);
                $error->appendChild($content);
                $ul->appendChild($error);
            }

            break;
        case 'xml':
            foreach ($data as $filename => $errors) {
                $error = new XMLElement('li', '<header class="frame-header">XML <a class="button" href="?debug=xml' . $query_string .'" title="' . __('Show debug view') . '">' . __('Debug') . '</a></header>');
                $content = new XMLElement('div', null, array('class' => 'content'));
                $list = new XMLElement('ul');

                foreach ($errors as $e) {
                    if (!is_array($e)) {
                        continue;
                    }

                    $parts = explode('(): ', $e['message']);

                    $list->appendChild(
                        new XMLElement(
                            'li',
                            '<code><em>' . $parts[0] . '():</em></code>'
                        )
                    );
                    $list->appendChild(
                        new XMLElement(
                            'li',
                            '<code>&#160;&#160;&#160;&#160;' . $parts[1] . '</code>'
                        )
                    );

                    if (strpos($e['file'], WORKSPACE) !== false) {
                        // The line in the exception is where it was thrown, it's
                        // useless for the ?debug view. This gets the line from
                        // the ?debug page.
                        preg_match('/:\s(\d+)$/', $parts[1], $line);

                        $list->appendChild(
                            new XMLElement(
                                'li',
                                '<code>&#160;&#160;&#160;&#160;<a href="?debug=xml' . $query_string . '#line-' . $line[1] .'" title="' . __('Show debug view for %s', array($filename)) . '">' . __('Show line %d in debug view', array($line[1])) . '</a></code>'
                            )
                        );
                    } else {
                        $list->appendChild(
                            new XMLElement(
                                'li',
                                '<code>&#160;&#160;&#160;&#160;' . $e['file'] . ':' . $e['line']. '</code>'
                            )
                        );
                    }
                }

                $content->appendChild($list);
                $error->appendChild($content);
                $ul->appendChild($error);
            }

            break;
    }
}

$div->appendChild($ul);
$Page->Body->appendChild($div);

print $Page->generate();

exit;
