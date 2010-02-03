<?php
class tree_parser_LocaleentityTreeParser extends tree_parser_LocalepackageTreeParser
{

    /**
     * Generic tree_parser_XmlListTreeParser constructor.
     *
     * A generic XmlListTreeParser (used by <wlist> elements) has the following properties :
     *
     *  - Depth limit : 1.
     *  - Length limit : 30.
     *  - Overwrite the already loaded data (on the client side) : yes (true).
     *  - Ignore children : yes (true).
     *  - Ignore length limit beyond depth : 1.
     *
     */
    public function initialize()
    {
        $this->setDepth(1)
            ->setLength(29)
            ->setOverwrite(true)
            ->setIgnoreChildren(true)
            ->setIgnoreLengthBeyondDepth(1);
    }
}

class uixul_locale_LocaleentityTreeParser extends tree_parser_LocaleentityTreeParser
{

}