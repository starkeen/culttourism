<?php

class RSSElement extends SimpleXMLElement
{
    public function addChildWithCData($name , $value) {
        $new = parent::addChild($name);
        $base = dom_import_simplexml($new);
        $docOwner = $base->ownerDocument;
        $base->appendChild($docOwner->createCDATASection($value));
    }
}