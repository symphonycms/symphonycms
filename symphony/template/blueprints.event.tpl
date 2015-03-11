<?php

class event<!-- CLASS NAME --> extends <!-- CLASS EXTENDS -->
{
    public $ROOTELEMENT = '<!-- ROOT ELEMENT -->';

    public $eParamFILTERS = array(
        <!-- FILTERS -->
    );

    public static function about()
    {
        return array(
            'name' => '<!-- NAME -->',
            'author' => array(
                'name' => '<!-- AUTHOR NAME -->',
                'website' => '<!-- AUTHOR WEBSITE -->',
                'email' => '<!-- AUTHOR EMAIL -->'),
            'version' => '<!-- VERSION -->',
            'release-date' => '<!-- RELEASE DATE -->',
            'trigger-condition' => 'action[<!-- TRIGGER CONDITION -->]'
        );
    }

    public static function getSource()
    {
        return '<!-- SOURCE -->';
    }

    public static function allowEditorToParse()
    {
        return true;
    }

    public static function documentation()
    {
        return '
<!-- DOCUMENTATION -->';
    }

    public function load()
    {
        if (isset($_POST['action']['<!-- TRIGGER CONDITION -->'])) {
            return $this->__trigger();
        }
    }

}
