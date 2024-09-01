<?php
namespace Phpdocx\Utilities;

use Phpdocx\Resources\OOXMLResources;

/**
 * Merging of documents (Word and PDF)
 * 
 * @category   Phpdocx
 * @package    Batch Processing
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @link       https://www.phpdocx.com
 */

error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);

class MultiMerge
{
    /**
     *
     * @var string
     * @access private
     */
    private $_background;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_commentsDOM;

    /**
     *
     * @var string
     * @access private
     */
    private $_commentsXML;

    /**
     *
     * @var \DOMXPath
     * @access private
     */
    private $_commentsXPath;

    /**
     *
     * @var \DOMXPath
     * @access private
     */
    private $_contentTypesXPath;

    /**
     *
     * @var array
     * @access private
     */
    private $_coreFiles;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_documentDOM;

    /**
     *
     * @var string
     * @access private
     */
    private $_documentXML;

    /**
     *
     * @var \DOMXPath
     * @access private
     */
    private $_documentXPath;

    /**
     *
     * @var \ZipArchive
     * @access private
     */
    private $_docx;

    /**
     *
     * @var \DOMXPath
     * @access private
     */
    private $_docXPath;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_endnotesDOM;

    /**
     *
     * @var string
     * @access private
     */
    private $_endnotesXML;

    /**
     *
     * @var \DOMXPath
     * @access private
     */
    private $_endnotesXPath;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_firstCommentsDOM;

    /**
     *
     * @var DOMDocument
     * @access private
     */
    private $_firstCommentsExtendedDOM;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_firstContentTypesDOM;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_firstDocumentDOM;

    /**
     *
     * @var \ZipArchive
     * @access private
     */
    private $_firstDocx;

    /**
     *
     * @var string
     * @access private
     */
    private $_firstDocxCommentsXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_firstDocxCommentsExtendedXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_firstDocxContentTypesXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_firstDocxDocumentXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_firstDocxEndnotesXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_firstDocxFootnotesXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_firstDocxNumberingXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_firstDocxRelsXML;

    /**
     *
     * @var array
     * @access private
     */
    private $_firstDocxStructuralData;

    /**
     *
     * @var string
     * @access private
     */
    private $_firstDocxStylesXML;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_firstEndnotesDOM;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_firstFootnotesDOM;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_firstNumberingDOM;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_firstRelsDOM;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_firstStylesDOM;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_footnotesDOM;

    /**
     *
     * @var string
     * @access private
     */
    private $_footnotesXML;

    /**
     *
     * @var \DOMXPath
     * @access private
     */
    private $_footnotesXPath;

    /**
     *
     * @var array
     * @access private
     */
    private $_headersAndFootersXML;

    /**
     *
     * @var array
     * @access private
     */
    private $_headersAndFootersDOM;

    /**
     *
     * @var array
     * @access private
     */
    private $_headersAndFootersXPath;

    /**
     *
     * @var array
     * @access private
     */
    private $_implicitRelationships;

    /**
     *
     * @var boolean
     * @access private
     */
    private $_mergeComments;

    /**
     *
     * @var boolean
     * @access private
     */
    private $_mergeCommentsExtended;

    /**
     *
     * @var boolean
     * @access private
     */
    private $_mergeEndnotes;

    /**
     *
     * @var boolean
     * @access private
     */
    private $_mergeFootnotes;

    /**
     *
     * @var boolean
     * @access private
     */
    private $_mergeNumberings;

    /**
     *
     * @var string
     * @access private
     */
    private $_newCommentsXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_newCommentsExtendedXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_newContentTypesXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_newDocumentXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_newDocumentXMLContents;

    /**
     *
     * @var string
     * @access private
     */
    private $_newEndnotesXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_newFootnotesXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_newNumberingXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_newRelsXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_newStylesXML;

    /**
     *
     * @var boolean
     * @access private
     */
    private $_noComments;

    /**
     *
     * @var boolean
     * @access private
     */
    private $_noCommentsExtended;

    /**
     *
     * @var boolean
     * @access private
     */
    private $_noEndnotes;

    /**
     *
     * @var boolean
     * @access private
     */
    private $_noFootnotes;

    /**
     *
     * @var boolean
     * @access private
     */
    private $_noNumberings;

    /**
     *
     * @var boolean
     * @access private
     */
    private $_preserveDefaults;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_relsDOM;

    /**
     *
     * @var \DOMXPath
     * @access private
     */
    private $_relsXPath;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_secondCommentsDOM;

    /**
     *
     * @var DOMDocument
     * @access private
     */
    private $_secondCommentsExtendedDOM;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_secondContentTypesDOM;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_secondDocumentDOM;

    /**
     *
     * @var \ZipArchive
     * @access private
     */
    private $_secondDocx;

    /**
     *
     * @var string
     * @access private
     */
    private $_secondDocxCommentsXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_secondDocxCommentsExtendedXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_secondDocxContentTypesXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_secondDocxDocumentXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_secondDocxEndnotesXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_secondDocxFootnotesXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_secondDocxNumberingXML;

    /**
     *
     * @var string
     * @access private
     */
    private $_secondDocxRelsXML;

    /**
     *
     * @var array
     * @access private
     */
    private $_secondDocxStructuralData;

    /**
     *
     * @var string
     * @access private
     */
    private $_secondDocxStylesXML;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_secondEndnotesDOM;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_secondFootnotesDOM;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_secondNumberingDOM;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_secondRelsDOM;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_secondStylesDOM;

    /**
     *
     * @var array
     * @access private
     */
    private $_sectionHeaders;

    /**
     *
     * @var \DOMDocument
     * @access private
     */
    private $_stylesDOM;

    /**
     *
     * @var string
     * @access private
     */
    private $_stylesXML;

    /**
     *
     * @var \DOMXPath
     * @access private
     */
    private $_stylesXPath;

    /**
     *
     * @var array
     * @access private
     */
    private $_takenBookmarksIds;

    /**
     *
     * @var array
     * @access private
     */
    private $_takenNumberingsIds;

    /**
     *
     * @var array
     * @access private
     */
    private $_takenFootnotesIds;

    /**
     *
     * @var array
     * @access private
     */
    private $_takenEndnotesIds;

    /**
     *
     * @var array
     * @access private
     */
    private $_takenCommentsIds;

    /**
     *
     * @var string
     * @access private
     */
    private $_wordMLChunk;

    /**
     * Class constructor
     */
    public function __construct()
    {
        
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        
    }

    /**
     * This is the main class method that does all the needed manipulation to merge docx documents
     * 
     * @access public
     * @param string $firstDocument path to the first document
     * @param array $documentArray array of paths to the documents to be merged
     * @param string $finalDocument path to the final merged document
     * @param array $options, 
     * Values:
     * 'mergeType' (0,1) that correspond to preserving or not the sections of the merged document respectively
     * 'enforceSectionPageBreak' (bool) enforces a page section break between documents
     * 'numbering' (continue, restart) that allows to restart, for example, the page numbering in the merged document
     * 'lineBreaks' (int): insert the number of line breaks indicated between the contents of the merging files
     * 'preserveStyleDefaults' (boolean) if true (default) makes sure that the defaults of the first document are not overriden
     * 'forceLatestStyles' (boolean) if true (default is false) uses the latest document as the base styles
     * @return DOCXStructure
     */
    public function mergeDocx($firstDocument, $documentArray, $finalDocument, $options)
    {
        //We initialize the required variables    
        if (!isset($options['preserveStyleDefaults'])) {
            $this->_preserveDefaults = true;
        } else {
            $this->_preserveDefaults = $options['preserveStyleDefaults'];
        }     
        $this->_background = '';
        $this->_wordMLChunk = '';
        if (isset($options['lineBreaks']) && $options['lineBreaks'] > 0) {
            $this->_wordMLChunk = '<w:p xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">';
            for ($k = 0; $k < $options['lineBreaks'] - 1; $k++) {
                $this->_wordMLChunk .= '<w:r><w:br /></w:r>';
            }
            $this->_wordMLChunk .= '</w:p>';
        }
        $this->_implicitRelationships = array('numbering.xml',
            'footnotes.xml',
            'endnotes.xml',
            'comments.xml',
            'settings.xml',
            'webSettings.xml',
            'fontTable.xml',
            'theme/theme1.xml',
            'styles.xml',
            'stylesWithEffects.xml'
        );
        $this->_coreFiles = array('settings.xml',
            'webSettings.xml',
            'fontTable.xml',
            'theme/theme1.xml',
            'stylesWithEffects.xml'
        );
        //This is the required data regarding the documents to be merged
        $this->_firstDocxDocumentXML = '';
        $this->_firstDocxRelsXML = '';
        $this->_firstDocxStylesXML = '';
        $this->_firstDocxNumberingXML = '';
        $this->_firstDocxFootnotesXML = '';
        $this->_firstDocxEndnotesXML = '';
        $this->_firstDocxCommentsXML = '';
        $this->_firstDocxCommentsExtendedXML = '';
        $this->_firstDocxContentTypesXML = '';
        $this->_firstCommentsDOM = new \DOMDocument();
        $this->_firstCommentsExtendedDOM = new \DOMDocument();
        $this->_firstContentTypesDOM = new \DOMDocument();
        $this->_firstDocumentDOM = new \DOMDocument();
        $this->_firstEndnotesDOM = new \DOMDocument();
        $this->_firstFootnotesDOM = new \DOMDocument();
        $this->_firstNumberingDOM = new \DOMDocument();
        $this->_firstNumberingRelsDOM = new \DOMDocument();
        $this->_firstRelsDOM = new \DOMDocument();
        $this->_firstStylesDOM = new \DOMDocument();
        $this->_firstDocxStructuralData = array();

        $this->_secondDocxDocumentXML = '';
        $this->_secondDocxRelsXML = '';
        $this->_secondDocxStylesXML = '';
        $this->_secondDocxNumberingXML = '';
        $this->_secondDocxNumberingRelsXML = '';
        $this->_secondDocxFootnotesXML = '';
        $this->_secondDocxEndnotesXML = '';
        $this->_secondDocxCommentsXML = '';
        $this->_secondDocxCommentsExtendedXML = '';
        $this->_secondDocxContentTypesXML = '';
        $this->_secondCommentsDOM = new \DOMDocument();
        $this->_secondCommentsExtendedDOM = new \DOMDocument();
        $this->_secondContentTypesDOM = new \DOMDocument();
        $this->_secondDocumentDOM = new \DOMDocument();
        $this->_secondEndnotesDOM = new \DOMDocument();
        $this->_secondFootnotesDOM = new \DOMDocument();
        $this->_secondNumberingDOM = new \DOMDocument();
        $this->_secondNumberingRelsDOM = new \DOMDocument();
        $this->_secondRelsDOM = new \DOMDocument();
        $this->_secondStylesDOM = new \DOMDocument();
        $this->_secondDocxStructuralData = array();

        $this->_newCommentsXML = '';
        $this->_newCommentsExtendedXML = '';
        $this->_newContentTypesXML = '';
        $this->_newDocumentXML = '';
        $this->_newDocumentXMLContents = '';
        $this->_newEndnotesXML = '';
        $this->_newFootnotesXML = '';
        $this->_newNumberingXML = '';
        $this->_newRelsXML = '';
        $this->_newStylesXML = '';

        $this->_takenBookmarksIds = array();
        $this->_takenNumberingsIds = array();
        $this->_takenFootnotesIds = array();
        $this->_takenEndnotesIds = array();
        $this->_takenCommentsIds = array();

        $this->_mergeComments = false;
        $this->_mergeCommentsExtended = false;
        $this->_mergeEndnotes = false;
        $this->_mergeFootnotes = false;
        $this->_mergeNumberings = false;

        //we extract (some) of the relevant files of the copy of the first document for manipulation
        //WARNING: it seems that there is a known bug with certain versions of the zipArchive PHP module
        //and .odt (OpenOffice) files. For workarounds look at: https://bugs.php.net/bug.php?id=48763
        //if (file_exists(dirname(__FILE__) . '/DOCXStructureTemplate.php')) {
        // allow reading the template from memory
        if (file_exists(dirname(__FILE__) . '/DOCXStructureTemplate.php') && $firstDocument instanceof DOCXStructure) {
            $this->_firstDocx = $firstDocument;
        } else {
            $this->_firstDocx = new DOCXStructure();
            $this->_firstDocx->parseDocx($firstDocument);
        }
        //document
        $this->_firstDocxDocumentXML = $this->_firstDocx->getContent('word/document.xml');
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $this->_firstDocumentDOM->loadXML($this->_firstDocxDocumentXML);
        libxml_disable_entity_loader($optionEntityLoader);
        //rels
        $this->_firstDocxRelsXML = $this->_firstDocx->getContent('word/_rels/document.xml.rels');
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $this->_firstRelsDOM->loadXML($this->_firstDocxRelsXML);
        libxml_disable_entity_loader($optionEntityLoader);
        //styles
        $this->_firstDocxStylesXML = $this->_firstDocx->getContent('word/styles.xml');
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $this->_firstStylesDOM->loadXML($this->_firstDocxStylesXML);
        libxml_disable_entity_loader($optionEntityLoader);
        //contentTypes
        $this->_firstDocxContentTypesXML = $this->_firstDocx->getContent('[Content_Types].xml');
        $optionEntityLoader = libxml_disable_entity_loader(true);
        $this->_firstContentTypesDOM->loadXML($this->_firstDocxContentTypesXML);
        libxml_disable_entity_loader($optionEntityLoader);
        //numberings
        $this->_firstDocxNumberingXML = $this->_firstDocx->getContent('word/numbering.xml');
        if ($this->_firstDocxNumberingXML === false) {
            $this->_noNumberings = true;
        } else {
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $this->_firstNumberingDOM->loadXML($this->_firstDocxNumberingXML);
            libxml_disable_entity_loader($optionEntityLoader);
        }
        //rels numbering
        $this->_firstDocxNumberingRelsXML = $this->_firstDocx->getContent('word/_rels/numbering.xml.rels');

        //footnotes
        $this->_firstDocxFootnotesXML = $this->_firstDocx->getContent('word/footnotes.xml');
        if ($this->_firstDocxFootnotesXML === false) {
            $this->_noFootnotes = true;
        } else {
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $this->_firstFootnotesDOM->loadXML($this->_firstDocxFootnotesXML);
            libxml_disable_entity_loader($optionEntityLoader);
        }
        //endnotes
        $this->_firstDocxEndnotesXML = $this->_firstDocx->getContent('word/endnotes.xml');
        if ($this->_firstDocxEndnotesXML === false) {
            $this->_noEndnotes = true;
        } else {
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $this->_firstEndnotesDOM->loadXML($this->_firstDocxEndnotesXML);
            libxml_disable_entity_loader($optionEntityLoader);
        }
        //comments
        $this->_firstDocxCommentsXML = $this->_firstDocx->getContent('word/comments.xml');
        if ($this->_firstDocxCommentsXML === false) {
            $this->_noComments = true;
        } else {
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $this->_firstCommentsDOM->loadXML($this->_firstDocxCommentsXML);
            libxml_disable_entity_loader($optionEntityLoader);
        }
        //commentsExtended
        $this->_firstDocxCommentsExtendedXML = $this->_firstDocx->getContent('word/commentsExtended.xml');
        if ($this->_firstDocxCommentsExtendedXML === false) {
            $this->_noCommentsExtended = true;
        } else {
            $optionEntityLoader = libxml_disable_entity_loader(true);
            $this->_firstCommentsExtendedDOM->loadXML($this->_firstDocxCommentsExtendedXML);
            libxml_disable_entity_loader($optionEntityLoader);
        }

        //Let us now get all structural data associated with original file        
        $this->_firstDocxStructuralData = $this->getDocxStructuralData($this->_firstDocumentDOM, $this->_firstRelsDOM, $this->_firstContentTypesDOM, false);

        //we start the looping over the elements in the array
        foreach ($documentArray as $key => $secondDocument) {
            if (file_exists(dirname(__FILE__) . '/DOCXStructureTemplate.php') && $secondDocument instanceof DOCXStructure) {
                $this->_secondDocx = $secondDocument;
            } else {
                $this->_secondDocx = new DOCXStructure();
                $this->_secondDocx->parseDocx($secondDocument);
            }
            $this->_secondDocxDocumentXML = $this->_secondDocx->getContent('word/document.xml');
            $this->_secondDocxRelsXML = $this->_secondDocx->getContent('word/_rels/document.xml.rels');
            $this->_secondDocxStylesXML = $this->_secondDocx->getContent('word/styles.xml');
            $this->_secondDocxNumberingXML = $this->_secondDocx->getContent('word/numbering.xml');
            $this->_secondDocxFootnotesXML = $this->_secondDocx->getContent('word/footnotes.xml');
            $this->_secondDocxEndnotesXML = $this->_secondDocx->getContent('word/endnotes.xml');
            $this->_secondDocxCommentsXML = $this->_secondDocx->getContent('word/comments.xml');
            $this->_secondDocxContentTypesXML = $this->_secondDocx->getContent('[Content_Types].xml');
            $this->_secondDocxNumberingRelsXML = $this->_secondDocx->getContent('word/_rels/numbering.xml.rels');

            $optionEntityLoader = libxml_disable_entity_loader(true);
            $this->_secondContentTypesDOM->loadXML($this->_secondDocxContentTypesXML);
            $this->_secondDocumentDOM->loadXML($this->_secondDocxDocumentXML);
            $this->_secondRelsDOM->loadXML($this->_secondDocxRelsXML);
            $this->_secondStylesDOM->loadXML($this->_secondDocxStylesXML);
            libxml_disable_entity_loader($optionEntityLoader);

            //We prepare $this->_secondContentTypesDOM for XPath searches
            $this->_contentTypesXPath = new \DOMXPath($this->_secondContentTypesDOM);
            $this->_contentTypesXPath->registerNamespace('ct', 'http://schemas.openxmlformats.org/package/2006/content-types');
            //We prepare $this->_secondDocxDocumentXML for XPath searches
            $this->_docXPath = new \DOMXPath($this->_secondDocumentDOM);
            $this->_docXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $this->_docXPath->registerNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');
            $this->_docXPath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
            $this->_docXPath->registerNamespace('pic', 'http://schemas.openxmlformats.org/drawingml/2006/picture');
            $this->_docXPath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $this->_docXPath->registerNamespace('c', 'http://schemas.openxmlformats.org/drawingml/2006/chart');
            //We prepare $this->_secondDocxRelsXML for XPath searches
            $this->_relsXPath = new \DOMXPath($this->_secondRelsDOM);
            $this->_relsXPath->registerNamespace('rels', 'http://schemas.openxmlformats.org/package/2006/relationships');

            //Let us now get all structural data associated of the file to be merged        
            $this->_secondDocxStructuralData = $this->getDocxStructuralData($this->_secondDocumentDOM, $this->_secondRelsDOM, $this->_secondContentTypesDOM, true);
            $this->compoundDocuments($this->_firstDocxStructuralData, $this->_secondDocxStructuralData, $options);

            //Images
            //we should copy the images of the merged docx
            for ($j = 1; $j <= count($this->_secondDocxStructuralData['images']); $j++) {
                foreach ($this->_secondDocxStructuralData['images'][$j] as $key => $value) {
                    $tempImage = $this->_secondDocx->getContent('word/' . $value['path']);
                    $this->_firstDocx->addContent('word/' . $value['newPath'], $tempImage);
                }
            }

            //Charts
            for ($j = 1; $j <= count($this->_secondDocxStructuralData['charts']); $j++) {
                foreach ($this->_secondDocxStructuralData['charts'][$j] as $key => $value) {
                    //Now we should get and parse the corresponding charts rel files
                    $chartNameArray = explode('/', $value['path']);
                    $chartName = array_pop($chartNameArray);
                    $chartRels = $this->_secondDocx->getContent('word/charts/_rels/' . $chartName . '.rels');
                    $chartRelsDOM = new \DOMDocument();
                    $optionEntityLoader = libxml_disable_entity_loader(true);
                    $chartRelsDOM->loadXML($chartRels);
                    libxml_disable_entity_loader($optionEntityLoader);

                    $docXPathRels = new \DOMXPath($chartRelsDOM);
                    $docXPathRels->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
                    $queryXlsxRels = '//r:Relationship[@Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/package"]';
                    $queryXlsxRelsNode = $docXPathRels->query($queryXlsxRels);

                    $xlsxNode = $queryXlsxRelsNode->item(0);
                    $xlsxId = $xlsxNode->getAttribute('Id');
                    $xlsxTarget = $xlsxNode->getAttribute('Target');
                    //we get the original name of the xlsx file
                    $xlsxNameArray = explode('/', $xlsxTarget);
                    $xlsxName = array_pop($xlsxNameArray);
                    $xlsxNewName = 'spreadsheet' . $value['newId'];
                    $xlsxNode->setAttribute('Id', $value['newId']);
                    $xlsxNode->setAttribute('Target', '../embeddings/' . $xlsxNewName . '.xlsx');
                    //We also have to change the attribute r:id of the chart xml file
                    $chartXML = $this->_secondDocx->getContent('word/charts/' . $chartName);
                    $chartDOM = new \DOMDocument();
                    $optionEntityLoader = libxml_disable_entity_loader(true);
                    $chartDOM->loadXML($chartXML);
                    libxml_disable_entity_loader($optionEntityLoader);
                    $externalData = $chartDOM->getElementsByTagName('externalData')->item(0);
                    $externalData->setAttribute('r:id', $value['newId']);

                    // check if there're styles, colors or theme files
                    $queryRels = '//r:Relationship';
                    $queryRelsNodes = $docXPathRels->query($queryRels);
                    foreach ($queryRelsNodes as $docXPathRel) {
                        // avoid adding the XLSX file again
                        switch ($docXPathRel->getAttribute('Type')) {
                            case 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/package':
                                // avoid adding the XLSX file again
                                break;
                            case 'http://schemas.microsoft.com/office/2011/relationships/chartStyle':
                                $xmlStyleFile = $this->_secondDocx->getContent('word/charts/' . $docXPathRel->getAttribute('Target'));

                                $relationshipTag = $this->_contentTypesXPath->query('//ct:Override[@PartName="/word/charts/'.$docXPathRel->getAttribute('Target').'"]');
                                $relationshipTag->item(0)->setAttribute('PartName', '/word/charts/styles' . $value['newId'] . '.xml');

                                $docXPathRel->setAttribute('Id', 'styles' . $value['newId']);
                                $docXPathRel->setAttribute('Target', 'styles' . $value['newId'] . '.xml');

                                $this->_firstDocx->addContent('word/charts/' . 'styles' . $value['newId'] . '.xml', $xmlStyleFile);
                                break;
                            case 'http://schemas.microsoft.com/office/2011/relationships/chartColorStyle':
                                $xmlColorFile = $this->_secondDocx->getContent('word/charts/' . $docXPathRel->getAttribute('Target'));

                                $relationshipTag = $this->_contentTypesXPath->query('//ct:Override[@PartName="/word/charts/'.$docXPathRel->getAttribute('Target').'"]');
                                $relationshipTag->item(0)->setAttribute('PartName', '/word/charts/colors' . $value['newId'] . '.xml');

                                $docXPathRel->setAttribute('Id', 'colors' . $value['newId']);
                                $docXPathRel->setAttribute('Target', 'colors' . $value['newId'] . '.xml');

                                $this->_firstDocx->addContent('word/charts/' . 'colors' . $value['newId'] . '.xml', $xmlColorFile);
                                break;
                            case 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/themeOverride':
                                $xmlThemeOverrideFile = $this->_secondDocx->getContent('word/theme/' . $docXPathRel->getAttribute('Target'));
                                $relationshipTag = $this->_contentTypesXPath->query('//ct:Override[@PartName="/word'.str_replace('..', '', $docXPathRel->getAttribute('Target')).'"]');
                                $relationshipTag->item(0)->setAttribute('PartName', '/theme/themeOverride' . $value['newId'] . '.xml');

                                $docXPathRel->setAttribute('Id', 'themeOverride' . $value['newId']);
                                $docXPathRel->setAttribute('Target', 'themeOverride' . $value['newId'] . '.xml');

                                $this->_firstDocx->addContent('word/theme/' . 'themeOverride' . $value['newId'] . '.xml', $xmlThemeOverrideFile);
                                break;
                        }
                    }

                    //we start to insert the required files             
                    $this->_firstDocx->addContent('word/' . $value['newPath'], $chartDOM->saveXML());
                    //Now we add the corresponding rels file
                    $this->_firstDocx->addContent('word/charts/_rels/' . $value['newName'] . '.rels', $chartRelsDOM->saveXML());
                    //and the corresponding excel in the embeddings folder
                    $tempChart = $this->_secondDocx->getContent('word/embeddings/' . $xlsxName);
                    $this->_firstDocx->addContent('word/embeddings/' . $xlsxNewName . '.xlsx', $tempChart);
                }
            }

            //Numberings
            if ($this->checkData($this->_secondDocxStructuralData['numberings']) > 0) {
                $this->_mergeNumberings = true;
                if ($this->_noNumberings) {
                    $this->_firstDocxNumberingXML = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                                                    <w:numbering xmlns:ve="http://schemas.openxmlformats.org/markup-compatibility/2006" 
                                                        xmlns:o="urn:schemas-microsoft-com:office:office" 
                                                        xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" 
                                                        xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" 
                                                        xmlns:v="urn:schemas-microsoft-com:vml" 
                                                        xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" 
                                                        xmlns:w10="urn:schemas-microsoft-com:office:word" 
                                                        xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" 
                                                        xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml">
                                                    </w:numbering>';
                    $optionEntityLoader = libxml_disable_entity_loader(true);
                    $this->_firstNumberingDOM->loadXML($this->_firstDocxNumberingXML);
                    libxml_disable_entity_loader($optionEntityLoader);
                    $this->_noNumberings = false;
                }
                // load the numberings into the DOM
                $optionEntityLoader = libxml_disable_entity_loader(true);
                $this->_secondNumberingDOM->loadXML($this->_secondDocxNumberingXML);
                libxml_disable_entity_loader($optionEntityLoader);

                // add rels numbering if it exists
                if ($this->_secondDocxNumberingRelsXML !== false) {
                    if ($this->_firstDocxNumberingRelsXML === false) {
                        // there's no content, overwrite the XML content
                        $relsSecondDocxNumberingRelsXMLDOM = new \DOMDocument();
                        $optionEntityLoader = libxml_disable_entity_loader(true);
                        $relsSecondDocxNumberingRelsXMLDOM->loadXML($this->_secondDocxNumberingRelsXML);
                        libxml_disable_entity_loader($optionEntityLoader);

                        $relsNumberingSecondNodes = $relsSecondDocxNumberingRelsXMLDOM->firstChild->childNodes;
                        foreach ($relsNumberingSecondNodes as $relsNumberingSecondNode) {
                            if ($relsNumberingSecondNode->nodeName == 'Relationship') {
                                $newId = 'rId' . mt_rand(999, 9999);
                                $extArray = explode('.', $relsNumberingSecondNode->getAttribute('Target'));
                                $newExtension = array_pop($extArray);
                                $newTarget = 'media/image' . $newId . '.' . $newExtension;

                                $docXPath = new \DOMXPath($this->_secondNumberingDOM);
                                $docXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                                $docXPath->registerNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');
                                $docXPath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
                                $docXPath->registerNamespace('pic', 'http://schemas.openxmlformats.org/drawingml/2006/picture');
                                $docXPath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                                $docXPath->registerNamespace('c', 'http://schemas.openxmlformats.org/drawingml/2006/chart');
                                $docXPath->registerNamespace('v', 'urn:schemas-microsoft-com:vml');
                                $docXPath->registerNamespace('o', 'urn:schemas-microsoft-com:office:office');
                                $queryImage = '//v:imagedata[@r:id="'.$relsNumberingSecondNode->getAttribute('Id').'"]';
                                $imageNumberingNodes = $docXPath->query($queryImage);
                                $imageNumberingNodes->item(0)->setAttribute('r:id', $newId);

                                // copy the new image
                                $tempImage = $this->_secondDocx->getContent('word/' . $relsNumberingSecondNode->getAttribute('Target'));
                                $this->_firstDocx->addContent('word/' . $newTarget, $tempImage);

                                $relsNumberingSecondNode->setAttribute('Id', $newId);
                                $relsNumberingSecondNode->setAttribute('Target', $newTarget);
                            }
                        }

                        // overwrite the numbering rels content
                        $this->_firstDocxNumberingRelsXML = $relsSecondDocxNumberingRelsXMLDOM->saveXML();
                    } else {
                        // merge the rels
                        $relsFirstDocxNumberingRelsXMLDOM = new \DOMDocument();
                        $optionEntityLoader = libxml_disable_entity_loader(true);
                        $relsFirstDocxNumberingRelsXMLDOM->loadXML($this->_firstDocxNumberingRelsXML);
                        libxml_disable_entity_loader($optionEntityLoader);
                        $relsSecondDocxNumberingRelsXMLDOM = new \DOMDocument();
                        $optionEntityLoader = libxml_disable_entity_loader(true);
                        $relsSecondDocxNumberingRelsXMLDOM->loadXML($this->_secondDocxNumberingRelsXML);
                        libxml_disable_entity_loader($optionEntityLoader);

                        $relsNumberingSecondNodes = $relsSecondDocxNumberingRelsXMLDOM->firstChild->childNodes;
                        foreach ($relsNumberingSecondNodes as $relsNumberingSecondNode) {
                            if ($relsNumberingSecondNode->nodeName == 'Relationship') {
                                $newId = 'rId' . mt_rand(999, 9999);
                                $extArray = explode('.', $relsNumberingSecondNode->getAttribute('Target'));
                                $newExtension = array_pop($extArray);
                                $newTarget = 'media/image' . $newId . '.' . $newExtension;

                                $docXPath = new \DOMXPath($this->_secondNumberingDOM);
                                $docXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                                $docXPath->registerNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');
                                $docXPath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
                                $docXPath->registerNamespace('pic', 'http://schemas.openxmlformats.org/drawingml/2006/picture');
                                $docXPath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                                $docXPath->registerNamespace('c', 'http://schemas.openxmlformats.org/drawingml/2006/chart');
                                $docXPath->registerNamespace('v', 'urn:schemas-microsoft-com:vml');
                                $docXPath->registerNamespace('o', 'urn:schemas-microsoft-com:office:office');
                                $queryImage = '//v:imagedata[@r:id="'.$relsNumberingSecondNode->getAttribute('Id').'"]';
                                $imageNumberingNodes = $docXPath->query($queryImage);
                                $imageNumberingNodes->item(0)->setAttribute('r:id', $newId);

                                // copy the new image
                                $tempImage = $this->_secondDocx->getContent('word/' . $relsNumberingSecondNode->getAttribute('Target'));
                                $this->_firstDocx->addContent('word/' . $newTarget, $tempImage);

                                $relsNumberingSecondNode->setAttribute('Id', $newId);
                                $relsNumberingSecondNode->setAttribute('Target', $newTarget);
                                
                                $importedRelsNumbering = $relsFirstDocxNumberingRelsXMLDOM->importNode($relsNumberingSecondNode, true);
                                $relsFirstDocxNumberingRelsXMLDOM->firstChild->appendChild($importedRelsNumbering);
                            }
                        }

                        // overwrite the numbering rels content
                        $this->_firstDocxNumberingRelsXML = $relsFirstDocxNumberingRelsXMLDOM->saveXML();
                    }
                }

                $this->mergeNumberings($this->_firstNumberingDOM, $this->_secondNumberingDOM, $this->_secondDocxStructuralData['numberings']);
                //In case there is no numberings.xml file in the original document we should change the Id of the merged
                //rels file for numbering
                $query = '//rels:Relationship[@Target="numbering.xml"]';
                $affectedNodes = $this->_relsXPath->query($query);
                $nodeToBeChanged = $affectedNodes->item(0);
                $nodeToBeChanged->setAttribute('Id', uniqid('rId' . mt_rand(999, 9999)));
            }

            //footnotes and endnotes
            if ($this->checkData($this->_secondDocxStructuralData['footnotes']) > 0 ||
                    $this->checkData($this->_secondDocxStructuralData['endnotes']) > 0) {
                $this->_mergeEndnotes = true;
                $this->_mergeFootnotes = true;
                if ($this->_noFootnotes) {
                    $this->_firstDocxFootnotesXML = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                                                    <w:footnotes xmlns:ve="http://schemas.openxmlformats.org/markup-compatibility/2006" 
                                                        xmlns:o="urn:schemas-microsoft-com:office:office" 
                                                        xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" 
                                                        xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" 
                                                        xmlns:v="urn:schemas-microsoft-com:vml" 
                                                        xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" 
                                                        xmlns:w10="urn:schemas-microsoft-com:office:word" 
                                                        xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" 
                                                        xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml">
                                                    <w:footnote w:type="separator" w:id="-1">
                                                    <w:p w:rsidR="00B43F5E" w:rsidRDefault="00B43F5E" w:rsidP="00B43F5E">
                                                    <w:pPr>
                                                    <w:spacing w:after="0" w:line="240" w:lineRule="auto"/>
                                                    </w:pPr>
                                                    <w:r>
                                                    <w:separator/>
                                                    </w:r>
                                                    </w:p>
                                                    </w:footnote>
                                                    <w:footnote w:type="continuationSeparator" w:id="0">
                                                    <w:p w:rsidR="00B43F5E" w:rsidRDefault="00B43F5E" w:rsidP="00B43F5E">
                                                    <w:pPr>
                                                    <w:spacing w:after="0" w:line="240" w:lineRule="auto"/>
                                                    </w:pPr>
                                                    <w:r>
                                                    <w:continuationSeparator/>
                                                    </w:r>
                                                    </w:p>
                                                    </w:footnote>
                                                    </w:footnotes>';
                    $optionEntityLoader = libxml_disable_entity_loader(true);
                    $this->_firstFootnotesDOM->loadXML($this->_firstDocxFootnotesXML);
                    libxml_disable_entity_loader($optionEntityLoader);
                    $this->_noFootnotes = false;
                }
                if ($this->_noEndnotes) {
                    $this->_firstDocxEndnotesXML = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                                                    <w:endnotes xmlns:ve="http://schemas.openxmlformats.org/markup-compatibility/2006" 
                                                        xmlns:o="urn:schemas-microsoft-com:office:office" 
                                                        xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" 
                                                        xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" 
                                                        xmlns:v="urn:schemas-microsoft-com:vml" 
                                                        xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" 
                                                        xmlns:w10="urn:schemas-microsoft-com:office:word" 
                                                        xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" 
                                                        xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml">
                                                    <w:endnote w:type="separator" w:id="-1">
                                                    <w:p w:rsidR="00B43F5E" w:rsidRDefault="00B43F5E" w:rsidP="00B43F5E">
                                                    <w:pPr>
                                                    <w:spacing w:after="0" w:line="240" w:lineRule="auto"/>
                                                    </w:pPr>
                                                    <w:r>
                                                    <w:separator/>
                                                    </w:r>
                                                    </w:p>
                                                    </w:endnote>
                                                    <w:endnote w:type="continuationSeparator" w:id="0">
                                                    <w:p w:rsidR="00B43F5E" w:rsidRDefault="00B43F5E" w:rsidP="00B43F5E">
                                                    <w:pPr>
                                                    <w:spacing w:after="0" w:line="240" w:lineRule="auto"/>
                                                    </w:pPr>
                                                    <w:r>
                                                    <w:continuationSeparator/>
                                                    </w:r>
                                                    </w:p>
                                                    </w:endnote>
                                                    </w:endnotes>';
                    $optionEntityLoader = libxml_disable_entity_loader(true);
                    $this->_firstEndnotesDOM->loadXML($this->_firstDocxEndnotesXML);
                    libxml_disable_entity_loader($optionEntityLoader);
                    $this->_noEndnotes = false;
                }
                //We now load the footnotes and endnotes in the DOM
                $optionEntityLoader = libxml_disable_entity_loader(true);
                $this->_secondEndnotesDOM->loadXML($this->_secondDocxEndnotesXML);
                $this->_secondFootnotesDOM->loadXML($this->_secondDocxFootnotesXML);
                libxml_disable_entity_loader($optionEntityLoader);
                //We now deal with the footnotes
                $this->mergeFootnotes($this->_firstFootnotesDOM, $this->_secondFootnotesDOM, $this->_secondDocxStructuralData['footnotes']);
                //In case there is no footnotes.xml file in the original document we should change the Id of the merged
                //rels file for footnotes
                $query = '//rels:Relationship[@Target="footnotes.xml"]';
                $affectedNodes = $this->_relsXPath->query($query);
                $nodeToBeChanged = $affectedNodes->item(0);
                $nodeToBeChanged->setAttribute('Id', uniqid('rId' . mt_rand(999, 9999)));
                //We now deal with the endnotes
                $this->mergeEndnotes($this->_firstEndnotesDOM, $this->_secondEndnotesDOM, $this->_secondDocxStructuralData['endnotes']);
                //In case there is no endnotes.xml file in the original document we should change the Id of the merged
                //rels file for endnotes
                $query = '//rels:Relationship[@Target="endnotes.xml"]';
                $affectedNodes = $this->_relsXPath->query($query);
                $nodeToBeChanged = $affectedNodes->item(0);
                $nodeToBeChanged->setAttribute('Id', uniqid('rId' . mt_rand(999, 9999)));
            }

            //comments
            if ($this->checkData($this->_secondDocxStructuralData['comments']) > 0) {
                $this->_mergeComments = true;
                if ($this->_noComments) {
                    $this->_firstDocxCommentsXML = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                                                <w:comments xmlns:ve="http://schemas.openxmlformats.org/markup-compatibility/2006" 
                                                        xmlns:o="urn:schemas-microsoft-com:office:office" 
                                                        xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" 
                                                        xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" 
                                                        xmlns:v="urn:schemas-microsoft-com:vml" 
                                                        xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" 
                                                        xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" 
                                                        xmlns:w10="urn:schemas-microsoft-com:office:word" 
                                                        xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" 
                                                        xmlns:w15="http://schemas.microsoft.com/office/word/2012/wordml" 
                                                        xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" 
                                                        xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml">
                                                </w:comments>';
                    $optionEntityLoader = libxml_disable_entity_loader(true);
                    $this->_firstCommentsDOM->loadXML($this->_firstDocxCommentsXML);
                    libxml_disable_entity_loader($optionEntityLoader);
                    $this->_noComments = false;
                }
                //we now load the comments in the DOM
                $optionEntityLoader = libxml_disable_entity_loader(true);
                $this->_secondCommentsDOM->loadXML($this->_secondDocxCommentsXML);
                libxml_disable_entity_loader($optionEntityLoader);

                //commentsExtended
                $this->_secondDocxCommentsExtendedXML = $this->_secondDocx->getContent('word/commentsExtended.xml');
                if ($this->_secondDocxCommentsExtendedXML) {
                    $this->_mergeCommentsExtended = true;
                    if ($this->_noCommentsExtended) {
                        $this->_firstDocxCommentsExtendedXML = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w15:commentsEx mc:Ignorable="w14 w15 wp14" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:w10="urn:schemas-microsoft-com:office:word" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" xmlns:w15="http://schemas.microsoft.com/office/word/2012/wordml" xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape"></w15:commentsEx>';
                        $optionEntityLoader = libxml_disable_entity_loader(true);
                        $this->_firstCommentsExtendedDOM->loadXML($this->_firstDocxCommentsExtendedXML);
                        libxml_disable_entity_loader($optionEntityLoader);
                        $this->_noCommentsExtended = false;
                    }
                    $optionEntityLoader = libxml_disable_entity_loader(true);
                    $this->_secondCommentsExtendedDOM->loadXML($this->_secondDocxCommentsExtendedXML);
                    libxml_disable_entity_loader($optionEntityLoader);
                    $this->mergeCommentsExtended($this->_firstCommentsExtendedDOM, $this->_secondCommentsExtendedDOM, $this->_secondCommentsDOM);
                }

                $this->mergeComments($this->_firstCommentsDOM, $this->_secondCommentsDOM, $this->_secondDocxStructuralData['comments']);
                //In case there is no comments.xml file in the original document we should change the Id of the merged
                //rels file for comments
                $query = '//rels:Relationship[@Target="comments.xml"]';
                $affectedNodes = $this->_relsXPath->query($query);
                $nodeToBeChanged = $affectedNodes->item(0);
                $nodeToBeChanged->setAttribute('Id', uniqid('rId' . mt_rand(999, 9999)));
            }

            //afChunks
            for ($j = 1; $j <= count($this->_secondDocxStructuralData['afChunks']); $j++) {
                foreach ($this->_secondDocxStructuralData['afChunks'][$j] as $key => $value) {
                    $tempAfChunk = $this->_secondDocx->getContent('word/' . $value['fileName']);
                    $this->_firstDocx->addContent('word/' . $value['newName'], $tempAfChunk);
                }
            }
            //We now should check if we have to insert the new headers and footers
            if (!isset($options['mergeType']) || $options['mergeType'] == 0) {
                //headers
                for ($j = 1; $j <= count($this->_secondDocxStructuralData['headers']); $j++) {
                    foreach ($this->_secondDocxStructuralData['headers'][$j] as $key => $value) {
                        $tempHeader = $this->_secondDocx->getContent('word/' . $value['name']);
                        $tempDOM = new \DOMDocument();
                        $optionEntityLoader = libxml_disable_entity_loader(true);
                        $tempDOM->loadXML($tempHeader);
                        libxml_disable_entity_loader($optionEntityLoader);
                        $docPrNodes = $tempDOM->getElementsByTagName('docPr');
                        foreach ($docPrNodes as $node) {
                            $decimalNumber = $this->uniqueDecimal();
                            $node->setAttribute('id', $decimalNumber);
                            $node->setAttribute('name', uniqid(mt_rand(999, 9999)));
                        }
                        $picNodes = $tempDOM->getElementsByTagName('cNvPr');
                        foreach ($picNodes as $node) {
                            $decimalNumber = $this->uniqueDecimal();
                            $node->setAttribute('id', $decimalNumber);
                        }
                        $tempHeader = $tempDOM->saveXML();
                        $this->_firstDocx->addContent('word/' . $value['newName'], $tempHeader);
                        //we will check now if there is any rels file associated with that header		
                        $relsHeader = $this->_secondDocx->getContent('word/_rels/' . $value['name'] . '.rels');
                        if ($relsHeader !== false) {
                            $relsHeaderDOM = new \DOMDocument();
                            $optionEntityLoader = libxml_disable_entity_loader(true);
                            $relsHeaderDOM->loadXML($relsHeader);
                            libxml_disable_entity_loader($optionEntityLoader);
                            //Now we parse for photos
                            $relsHeaderXPath = new \DOMXPath($relsHeaderDOM);
                            $relsHeaderXPath->registerNamespace('rels', 'http://schemas.openxmlformats.org/package/2006/relationships');
                            $query = '//rels:Relationship[@Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image"]';
                            $affectedNodes = $relsHeaderXPath->query($query);
                            foreach ($affectedNodes as $node) {
                                $imageName = $node->getAttribute('Target');
                                $imageExtensionArray = explode('.', $imageName);
                                $extension = array_pop($imageExtensionArray);
                                $imageNewName = 'media/image' . uniqid(mt_rand(999, 9999)) . '.' . $extension;
                                $node->setAttribute('Target', $imageNewName);
                                //let us now copy the image in the final document
                                $tempImage = $this->_secondDocx->getContent('word/' . $imageName);
                                $this->_firstDocx->addContent('word/' . $imageNewName, $tempImage);
                            }
                            //let us insert now the rels part in the final document
                            $newRelsHeader = $relsHeaderDOM->saveXML();
                            $this->_firstDocx->addContent('word/_rels/' . $value['newName'] . '.rels', $newRelsHeader);
                        }
                    }
                }

                //footers
                for ($j = 1; $j <= count($this->_secondDocxStructuralData['footers']); $j++) {
                    foreach ($this->_secondDocxStructuralData['footers'][$j] as $key => $value) {
                        $tempFooter = $this->_secondDocx->getContent('word/' . $value['name']);
                        //Now we are going to regenerate all the "extra and otherwise useless ids" used in images and charts
                        $tempDOM = new \DOMDocument();
                        $optionEntityLoader = libxml_disable_entity_loader(true);
                        $tempDOM->loadXML($tempFooter);
                        libxml_disable_entity_loader($optionEntityLoader);
                        $docPrNodes = $tempDOM->getElementsByTagName('docPr');
                        foreach ($docPrNodes as $node) {
                            $decimalNumber = $this->uniqueDecimal();
                            $node->setAttribute('id', $decimalNumber);
                            $node->setAttribute('name', uniqid(mt_rand(999, 9999)));
                        }
                        $picNodes = $tempDOM->getElementsByTagName('cNvPr');
                        foreach ($picNodes as $node) {
                            $decimalNumber = $this->uniqueDecimal();
                            $node->setAttribute('id', $decimalNumber);
                        }
                        $tempFooter = $tempDOM->saveXML();
                        $this->_firstDocx->addContent('word/' . $value['newName'], $tempFooter);
                        //we will check now if there is any rels file associated with that footer		
                        $relsFooter = $this->_secondDocx->getContent('word/_rels/' . $value['name'] . '.rels');
                        if ($relsFooter !== false) {
                            $relsFooterDOM = new \DOMDocument();
                            $optionEntityLoader = libxml_disable_entity_loader(true);
                            $relsFooterDOM->loadXML($relsFooter);
                            libxml_disable_entity_loader($optionEntityLoader);
                            //Now we parse for photos
                            $relsFooterXPath = new \DOMXPath($relsFooterDOM);
                            $relsFooterXPath->registerNamespace('rels', 'http://schemas.openxmlformats.org/package/2006/relationships');
                            $query = '//rels:Relationship[@Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image"]';
                            $affectedNodes = $relsFooterXPath->query($query);
                            foreach ($affectedNodes as $node) {
                                $imageName = $node->getAttribute('Target');
                                $imageExtensionArray = explode('.', $imageName);
                                $extension = array_pop($imageExtensionArray);
                                $imageNewName = 'media/image' . uniqid(mt_rand(999, 9999)) . '.' . $extension;
                                $node->setAttribute('Target', $imageNewName);
                                //let us now copy the image in the final document
                                $tempImage = $this->_secondDocx->getContent('word/' . $imageName);
                                $this->_firstDocx->addContent('word/' . $imageNewName, $tempImage);
                            }
                            //let us insert now the rels part in the final document
                            $newRelsFooter = $relsFooterDOM->saveXML();
                            $this->_firstDocx->addContent('word/_rels/' . $value['newName'] . '.rels', $newRelsFooter);
                        }
                    }
                }
            }

            // add numberings in styles if they exist
            $this->mergeNumberingsStyles($this->_secondStylesDOM, $this->_firstNumberingDOM, $this->_secondNumberingDOM);

            // merge the styles files
            $this->mergeStyles($this->_firstStylesDOM, $this->_secondStylesDOM);

            // merge the contentTypes files
            $this->mergeContentTypes($this->_firstContentTypesDOM, $this->_secondContentTypesDOM);

            // merge the rels files
            $this->mergeRels($this->_firstRelsDOM, $this->_secondRelsDOM);
        }
        // close here the looping over merging documents.
        // Insert the required files into the open zip object document
        $this->_newDocumentXMLContents = $this->mergeDocuments($this->_firstDocxStructuralData, $options);
        // wrap the results within the <document><body> tags.
        // First check if there is any background image in the first document
        $backgroundNodes = $this->_firstDocumentDOM->getElementsByTagName('background');
        if ($backgroundNodes->length > 0) {
            $backgroundNode = $backgroundNodes->item(0);
            $this->_background = $backgroundNode->ownerDocument->saveXML($backgroundNode);
        }
        // finally build the complete new document.xml
        $this->_newDocumentXML = '<?xml version="1.0" encoding="UTF-8" standalone="yes" ?>
                                <w:document 
                                xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" 
                                xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" 
                                xmlns:o="urn:schemas-microsoft-com:office:office" 
                                xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" 
                                xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" 
                                xmlns:v="urn:schemas-microsoft-com:vml" 
                                xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" 
                                xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" 
                                xmlns:w10="urn:schemas-microsoft-com:office:word" 
                                xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" 
                                xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" 
                                xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" 
                                xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" 
                                xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" 
                                xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape"
                                mc:Ignorable="w14 wp14" >';


        $this->_newDocumentXML .= $this->_background . '<w:body>' . $this->_newDocumentXMLContents . '</w:body></w:document>';

        // remove extra XML headers
        $this->_newDocumentXML = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $this->_newDocumentXML);

        // insert the new document.xml in the merged docx
        $this->_firstDocx->addContent('word/document.xml', $this->_newDocumentXML);
        // styles
        if (isset($options['forceLatestStyles']) && $options['forceLatestStyles']) {
            $this->_newStylesXML = $this->_secondStylesDOM->saveXML();
            $this->_firstDocx->addContent('word/styles.xml', $this->_newStylesXML);
        } else {
            $this->_newStylesXML = $this->_firstStylesDOM->saveXML();
            $this->_firstDocx->addContent('word/styles.xml', $this->_newStylesXML);
        }

        //contentTypes
        $this->_newContentTypesXML = $this->_firstContentTypesDOM->saveXML();
        $this->_firstDocx->addContent('[Content_Types].xml', $this->_newContentTypesXML);

        //rels files
        $this->_newRelsXML = $this->_firstRelsDOM->saveXML();
        $this->_firstDocx->addContent('word/_rels/document.xml.rels', $this->_newRelsXML);

        //numberings
        if ($this->_mergeNumberings) {
            // handle numbering rels
            if ($this->_firstDocxNumberingRelsXML) {
                $this->_firstDocx->addContent('word/_rels/numbering.xml.rels', $this->_firstDocxNumberingRelsXML);
            }

            $this->newNumberingXML = $this->_firstNumberingDOM->saveXML();
            $this->_firstDocx->addContent('word/numbering.xml', $this->newNumberingXML);
        }

        //comments
        if ($this->_mergeComments) {
            $this->newCommentsXML = $this->_firstCommentsDOM->saveXML();
            $this->_firstDocx->addContent('word/comments.xml', $this->newCommentsXML);
        }

        //commentsExtended
        if ($this->_mergeCommentsExtended) {
            $this->newCommentsExtendedXML = $this->_firstCommentsExtendedDOM->saveXML();
            $this->_firstDocx->addContent('word/commentsExtended.xml', $this->newCommentsExtendedXML);
        }

        //endnotes
        if ($this->_mergeEndnotes) {
            $this->newEndnotesXML = $this->_firstEndnotesDOM->saveXML();
            $this->_firstDocx->addContent('word/endnotes.xml', $this->newEndnotesXML);
        }

        //footnotes
        if ($this->_mergeFootnotes) {
            $this->newFootnotesXML = $this->_firstFootnotesDOM->saveXML();
            $this->_firstDocx->addContent('word/footnotes.xml', $this->newFootnotesXML);
        }

        // save the new document
        return $this->_firstDocx->saveDocx($finalDocument);
    }

    /**
     * Merge docx documents after a specific position in the DOCX
     * 
     * @access public
     * @param string $firstDocument path to the first document
     * @param array $documentArray array of paths to the documents to be merged
     * @param string $finalDocument path to the final merged document
     * @param array $referenceNode
     * Keys and values:
     *     'type' (string) can be * (all, default value), bookmark, break, chart, endnote (content reference), footnote (content reference), image, list, paragraph (also for links and lists), section, shape, table
     *     'contains' (string) for bookmark, list, paragraph (text, link), shape
     *     'occurrence' (int) exact occurrence or (string) range of contents (e.g.: 2..9, 2.., ..9) or first() or last(), if empty iterate all elements
     *     'attributes' (array)
     *     'parent' (string) w:body (default) for document target, w:hdr for header target, w:ftr for footer target, '/' (any parent) or any other specific parent (/w:tbl/, /w:tc/, /w:r/...)
     *     'customQuery' (string) if set overwrites all previous references. It must be a valid XPath query
     * @param array $options, 
     * Values:
     * 'mergeType' (0,1) that correspond to preserving or not the sections of the merged document respectively
     * 'enforceSectionPageBreak' (bool) enforces a page section break between documents
     * 'numbering' (continue, restart) that allows to restart, for example, the page numbering in the merged document
     * 'lineBreaks' (int): insert the number of line breaks indicated between the contents of the merging files
     * 'preserveStyleDefaults' (boolean) if true (default) makes sure that the defaults of the first document are not overriden
     * 'forceLatestStyles' (boolean) if true (default is false) uses the latest document as the base styles
     * @return void
     */
    public function mergeDocxAt($firstDocument, $documentArray, $finalDocument, $referenceNode, $options)
    {
        // add a custom placeholder at the end of the first document
        $firstDocx = new \Phpdocx\Create\CreateDocxFromTemplate($firstDocument);

        $referenceNodeFirstDocxEnd = array(
            'occurrence' => -1,
        );
        $content = new \Phpdocx\Elements\WordFragment($firstDocx, 'document');
        $content->addText($firstDocx->getTemplateSymbol() . 'MERGED_DOCUMENT_PLACEHOLDER_END' . $firstDocx->getTemplateSymbol());
        $firstDocx->insertWordFragment($content, $referenceNodeFirstDocxEnd);

        // add a custom placeholder where the document will be merged
        $content = new \Phpdocx\Elements\WordFragment($firstDocx, 'document');
        $content->addText($firstDocx->getTemplateSymbol() . 'MERGED_DOCUMENT_PLACEHOLDER_TARGET' . $firstDocx->getTemplateSymbol());
        $firstDocx->insertWordFragment($content, $referenceNode, 'after', true);

        $randomPrefixFile = uniqid(mt_rand(999, 9999));

        if (file_exists(dirname(__FILE__) . '/DOCXStructureTemplate.php') && $firstDocument instanceof \Phpdocx\Utilities\DOCXStructure) {
            // use in-memory DOCX
            $firstDocxTarget = $firstDocx;
        } else {
            // use a file
            $firstDocxTarget = $finalDocument . $randomPrefixFile . '_first_docx_updated.docx';
            $firstDocx->createDocx($firstDocxTarget);
        }
        
        // use the current mergeDocx method to merge DOCX
        $this->mergeDocx($firstDocxTarget, $documentArray, $finalDocument . $randomPrefixFile . '_merged.docx', $options);

        $finalDocx = new \Phpdocx\Create\CreateDocxFromTemplate($finalDocument . $randomPrefixFile . '_merged.docx');

        // move the new content replacing the placeholder added at the specific position
        $referenceNodePlaceholderFollowingSiblings = array(
            'customQuery' => '//w:p[contains(., "'.$finalDocx->getTemplateSymbol().'MERGED_DOCUMENT_PLACEHOLDER_END'.$finalDocx->getTemplateSymbol().'")]/following-sibling::*',
        );
        $queryInfo = $finalDocx->getDocxPathQueryInfo($referenceNodePlaceholderFollowingSiblings);

        for ($i = $queryInfo['length']; $i >= 0; $i--) {
            $referenceNodeFrom = array(
                'customQuery' => '//w:p[contains(., "'.$finalDocx->getTemplateSymbol().'MERGED_DOCUMENT_PLACEHOLDER_END'.$finalDocx->getTemplateSymbol().'")]/following-sibling::*['.$i.']',
            );

            $referenceNodeTo = array(
                'customQuery' => '//w:p[contains(., "'.$finalDocx->getTemplateSymbol().'MERGED_DOCUMENT_PLACEHOLDER_TARGET'.$finalDocx->getTemplateSymbol().'")]',
            );
            $finalDocx->moveWordContent($referenceNodeFrom, $referenceNodeTo, 'after');
        }

        // remove the placeholders added to merge the contents
        $referenceNodePlaceholderEnd = array(
            'type' => 'paragraph',
            'contains' => $finalDocx->getTemplateSymbol().'MERGED_DOCUMENT_PLACEHOLDER_END'.$finalDocx->getTemplateSymbol(),
        );
        $finalDocx->removeWordContent($referenceNodePlaceholderEnd);
        $referenceNodePlaceholderTarget = array(
            'type' => 'paragraph',
            'contains' => $finalDocx->getTemplateSymbol().'MERGED_DOCUMENT_PLACEHOLDER_TARGET'.$finalDocx->getTemplateSymbol(),
        );
        $finalDocx->removeWordContent($referenceNodePlaceholderTarget);

        $finalDocx->createDocx($finalDocument);
        
        // remove temp files
        unlink($finalDocument . $randomPrefixFile . '_first_docx_updated.docx');
        unlink($finalDocument . $randomPrefixFile . '_merged.docx');
    }

    /**
     * Merge PDF documents
     * 
     * @access public
     * @param array $documents Ordered array of paths to the documents to be merged
     * @param string $finalDocument path to the final merged document
     * @param array $options
     *        'annotations' (bool) import annotations, false as default
     */
    public function mergePdf($documentArray, $finalDocument, $options = array())
    {
        require_once dirname(__FILE__) . '/../Libs/TCPDF_lib.php';

        if (!is_array($documentArray)) {
            throw new \Exception('You must set an array of document paths.');
        }

        if (!isset($options['annotations'])) {
            $options['annotations'] = false;
        }
        
        $pdf = new \Phpdocx\Libs\TCPDI();
        foreach ($documentArray as $path) {
            if (!file_exists($path)) {
                throw new \Exception('File does not exist');
            }

            $pageCount = $pdf->setSourceFile($path);
            
            if ($options['annotations']) {
                for ($i = 1; $i <= $pageCount; $i++) {
                    // avoid pages if requested
                    if (isset($options['pages']) && in_array($i, $options['pages'])) {
                        continue;
                    }
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                    $tplidx = $pdf->importPage($i, '/BleedBox');
                    $size = $pdf->getTemplatesize($tplidx);
                    $orientation = ($size['w'] > $size['h']) ? 'L' : 'P';
                    $pdf->addPage($orientation);
                    $pdf->setPageFormatFromTemplatePage(1, $orientation);
                    $pdf->useTemplate($tplidx, null, null, 0, 0, true);
                    $pdf->importAnnotations(1);
                }
            } else {
                for ($i = 1; $i <= $pageCount; $i++) {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                    $pdf->addPage();
                    $tplidx = $pdf->importPage($i);
                    $pdf->useTemplate($tplidx, null, null, 0, 0, true);
                }
            }
        }

        if (file_exists(dirname(__FILE__) . '/ZipStream.php') && \Phpdocx\Create\CreateDocx::$streamMode === true) {
            $pdf->Output($finalDocument, 'I');
        } else {
            $pdf->Output($finalDocument, 'F');
        }
    }

    /**
     * This is the method that extracts all the structural info of a given docx
     * @access public
     * @param \DOMDocument $docDOM
     * @param \DOMDocument $relsDOM
     * @param \DOMDocument $contentTypesDOM
     * @param string $relabel this variable controls if we have to reset the ids of the relevant \DOMDocuments
     * @return array
     */
    public function getDocxStructuralData($docDOM, $relsDOM, $contentTypesDOM, $relabel = false)
    {
        //Let us  now define some auxiliary variables
        $section = array();
        $sectionProperties = array();
        $images = array();
        $charts = array();
        $links = array();
        $bookmarks = array();
        $numberings = array();
        $headers = array();
        $footers = array();
        $footnotes = array();
        $endnotes = array();
        $comments = array();
        $afChunks = array();
        $parsedHeaders = array();
        $parsedFooters = array();

        $baseNode = $docDOM->getElementsByTagName('body')->item(0);
        $childNodes = $baseNode->childNodes;

        $j = 1;
        $section[$j] = new \DOMDocument('1.0', 'utf-8');
        $sectionProperties[$j] = new \DOMDocument();
        $images[$j] = array();
        $charts[$j] = array();
        $links[$j] = array();
        $bookmarks[$j] = array();
        $numberings[$j] = array();
        $headers[$j] = array();
        $footers[$j] = array();
        $footnotes[$j] = array();
        $endnotes[$j] = array();
        $comments[$j] = array();
        $afChunks[$j] = array();


        foreach ($childNodes as $node) {
            if ($node->nodeName == 'w:sectPr') {
                $importedNode = $sectionProperties[$j]->importNode($node, true);
                $sectionProperties[$j]->appendChild($importedNode);
            } else {
                $importedNode = $section[$j]->importNode($node, true);
                $section[$j]->appendChild($importedNode);
                $sectionNodes = $section[$j]->getElementsByTagName('sectPr');
                if ($sectionNodes->length == 0) {
                    continue;
                } else {
                    $sectionNode = $sectionNodes->item(0);
                    $importedNode = $sectionProperties[$j]->importNode($sectionNode, true);
                    $sectionProperties[$j]->appendChild($importedNode);
                    $sectionNode->parentNode->removeChild($sectionNode);
                    $j++;
                    $section[$j] = new \DOMDocument('1.0', 'utf-8');
                    $sectionProperties[$j] = new \DOMDocument();
                    //we now create the auxiliary arrays
                    $images[$j] = array();
                    $charts[$j] = array();
                    $links[$j] = array();
                    $bookmarks[$j] = array();
                    $numberings[$j] = array();
                    $headers[$j] = array();
                    $footers[$j] = array();
                    $footnotes[$j] = array();
                    $endnotes[$j] = array();
                    $comments[$j] = array();
                    $afChunks[$j] = array();
                }
            }
        }

        //We get the number of sections and we start the parsing

        $numSections = count($section);
        //We define an array to hold the main relationships
        $relsArray = array();
        $relationship = $relsDOM->documentElement;
        $relsNodes = $relationship->childNodes;
        //We feed the reslArray array
        foreach ($relsNodes as $node) {
            if ($node->nodeName == 'Relationship') {
                $relsArray[$node->getAttribute('Id')] = $node->getAttribute('Target');
            }
        }

        //Let us do the parsing by section
        //If the option relabel is set to true we will have to
        //regenerate all ids so there will be no clashes when performing the merging


        $contentTypesXPath = new \DOMXPath($contentTypesDOM);
        $contentTypesXPath->registerNamespace('ct', 'http://schemas.openxmlformats.org/package/2006/content-types');

        $relsXPath = new \DOMXPath($relsDOM);
        $relsXPath->registerNamespace('rels', 'http://schemas.openxmlformats.org/package/2006/relationships');


        // Main content
        // this two arrays keep trak of repeated images and shapes
        $imageShapeIds = array();
        $originalIds = array();
        // run over all the sections
        for ($k = 1; $k <= $numSections; $k++) {
            $docXPath = new \DOMXPath($section[$k]);
            $docXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $docXPath->registerNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');
            $docXPath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
            $docXPath->registerNamespace('pic', 'http://schemas.openxmlformats.org/drawingml/2006/picture');
            $docXPath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $docXPath->registerNamespace('c', 'http://schemas.openxmlformats.org/drawingml/2006/chart');
            $docXPath->registerNamespace('v', 'urn:schemas-microsoft-com:vml');
            $docXPath->registerNamespace('o', 'urn:schemas-microsoft-com:office:office');
            //we look for images
            //They can come in two flavors as a pic or as a shape
            //Let us first deal with the pic tag
            $queryImage = '//pic:blipFill/a:blip';
            $imageNodes = $docXPath->query($queryImage);
            //we have to take into account that images can be used more than once
            foreach ($imageNodes as $node) {
                $link = '';
                $attr = $node->getAttribute('r:embed');
                $link = $node->getAttribute('r:link');
                $extArray = explode('.', $relsArray[$attr]);
                $extension = array_pop($extArray);
                if (key_exists($attr, $originalIds)) {
                    $myId = $originalIds[$attr];
                    $newId = 'rId' . $myId;
                } else {
                    $myId = uniqid(mt_rand(999, 9999));
                    $newId = 'rId' . $myId;
                    $originalIds[$attr] = $myId;
                }
                if (key_exists($link, $originalIds)) {
                    $myIdLink = $originalIds[$link];
                    $newIdLink = 'rId' . $myIdLink;
                } else if (!empty($link)){
                    $myIdLink = uniqid(mt_rand(999, 9999));
                    $newIdLink = 'rId' . $myIdLink;
                    $originalIds[$link] = $myIdLink;
                }
                $newPath = 'media/image' . $myId . '.' . $extension;
                $images[$k][$attr] = array(
                    'path' => $relsArray[$attr],
                    'newPath' => $newPath,
                    'newId' => $newId
                );
                if ($relabel) {
                    $node->setAttribute('r:embed', $newId);
                    //we now update the document.xml.rels file
                    $query = '//rels:Relationship[@Id="' . $attr . '"]';
                    $affectedNodes = $relsXPath->query($query);
                    $nodeToBeChanged = $affectedNodes->item(0);
                    if (is_object($nodeToBeChanged)) {
                        $nodeToBeChanged->setAttribute('Target', $images[$k][$attr]['newPath']);
                        $nodeToBeChanged->setAttribute('Id', $images[$k][$attr]['newId']);
                    }
                    if (!empty($link)) {
                        $node->setAttribute('r:link', $newIdLink);
                        //we now update the document.xml.rels file
                        $query = '//rels:Relationship[@Id="' . $link . '"]';
                        $affectedNodes = $relsXPath->query($query);
                        $nodeToBeChanged = $affectedNodes->item(0);
                        if (is_object($nodeToBeChanged)) {
                            $nodeToBeChanged->setAttribute('Id', $newIdLink);
                        }
                    }
                }
            }
            //And now with shape tag
            $queryImage = '//v:imagedata';
            $imageNodes = $docXPath->query($queryImage);
            //we have to take into account that images can be used more than once
            foreach ($imageNodes as $node) {
                $attr = $node->getAttribute('r:id');
                $extArray = explode('.', $relsArray[$attr]);
                $extension = array_pop($extArray);
                if (key_exists($attr, $imageShapeIds)) {
                    $myId = $imageShapeIds[$attr];
                    $newId = 'rId' . $myId;
                } else {
                    $myId = uniqid(mt_rand(999, 9999));
                    $newId = 'rId' . $myId;
                    $imageShapeIds[$attr] = $myId;
                }
                $newPath = 'media/image' . $myId . '.' . $extension;
                $images[$k][$attr] = array(
                    'path' => $relsArray[$attr],
                    'newPath' => $newPath,
                    'newId' => $newId
                );
                if ($relabel) {
                    $node->setAttribute('r:id', $newId);
                    //we now update the document.xml.rels file
                    $query = '//rels:Relationship[@Id="' . $attr . '"]';
                    $affectedNodes = $relsXPath->query($query);
                    $nodeToBeChanged = $affectedNodes->item(0);
                    if (is_object($nodeToBeChanged)) {
                        $nodeToBeChanged->setAttribute('Target', $images[$k][$attr]['newPath']);
                        $nodeToBeChanged->setAttribute('Id', $images[$k][$attr]['newId']);
                    }
                    //Like by the time being we are not parsing de OLE objects we should remove the o:OLEObject tags that may go with the image
                    $siblings = $node->parentNode->parentNode->childNodes;
                    foreach ($siblings as $sibling) {
                        if ($sibling->nodeName == 'o:OLEObject') {
                            $sibling->parentNode->removeChild($sibling);
                        }
                    }
                }
            }
            //charts
            $queryChart = '//c:chart'; //We probably have to get some more things
            $chartNodes = $docXPath->query($queryChart);
            foreach ($chartNodes as $node) {
                $attr = $node->getAttribute('r:id');
                $myId = uniqid(mt_rand(999, 9999));
                $newId = 'rId' . $myId;
                $newPath = 'charts/chart' . $myId . '.xml';
                $charts[$k][$attr] = array(
                    'path' => $relsArray[$attr],
                    'newPath' => $newPath,
                    'newId' => $newId,
                    'newName' => 'chart' . $myId . '.xml'
                );
                if ($relabel) {
                    $node->setAttribute('r:id', $newId);
                    //we now update the Content_Types xml file
                    $query = '//ct:Override[@PartName="/word/' . $charts[$k][$attr]['path'] . '"]';
                    $affectedNodes = $contentTypesXPath->query($query);
                    $nodeToBeChanged = $affectedNodes->item(0);
                    $nodeToBeChanged->setAttribute('PartName', '/word/' . $charts[$k][$attr]['newPath']);
                    //and now the rels file
                    $query = '//rels:Relationship[@Id="' . $attr . '"]';
                    $affectedNodes = $relsXPath->query($query);
                    $nodeToBeChanged = $affectedNodes->item(0);
                    $nodeToBeChanged->setAttribute('Target', $charts[$k][$attr]['newPath']);
                    $nodeToBeChanged->setAttribute('Id', $charts[$k][$attr]['newId']);
                }
            }

            //links
            $queryLink = '//w:hyperlink[not(@w:anchor)]';
            $linkNodes = $docXPath->query($queryLink);
            foreach ($linkNodes as $node) {
                $attr = $node->getAttribute('r:id');
                $myId = uniqid(mt_rand(999, 9999));
                $newId = 'rId' . $myId;
                $links[$k][$attr] = array(
                    'path' => $relsArray[$attr],
                    'newId' => $newId
                );
                if ($relabel) {
                    $node->setAttribute('r:id', $newId);
                    //we update the rels file
                    $query = '//rels:Relationship[@Id="' . $attr . '"]';
                    $affectedNodes = $relsXPath->query($query);
                    $nodeToBeChanged = $affectedNodes->item(0);
                    $nodeToBeChanged->setAttribute('Id', $links[$k][$attr]['newId']);
                }
            }

            //bookmarks
            $queryBookmark = '//w:bookmarkStart';
            $bookmarkNodes = $docXPath->query($queryBookmark);
            foreach ($bookmarkNodes as $node) {
                $attr = $node->getAttribute('w:id');
                $this->_takenBookmarksIds[] = $attr;
                $newId = $this->uniqueDecimal($this->_takenBookmarksIds);
                $bookmarks[$k][$attr] = array(
                    'newId' => $newId
                );
                if ($relabel) {
                    $node->setAttribute('w:id', $newId);
                    //Now we have to set the w:id attribute of the corresponding bookmarkStop tag to the same new value
                    $queryBookmarkEnd = '//w:bookmarkEnd[@w:id = "' . $attr . '"]';
                    $affectedNodes = $docXPath->query($queryBookmarkEnd);
                    //sometimes bookmarks may start and dinish in different sections
                    //The standard allows that so we should make sure that if the bookmarkEnd tag is not found the script does not throw any error
                    if ($affectedNodes->length > 0) {
                        $nodeToBeChanged = $affectedNodes->item(0);
                        $nodeToBeChanged->setAttribute('w:id', $bookmarks[$k][$attr]['newId']);
                    }
                }
            }
            //numberings
            $queryNumbering = '//w:numId';
            $numberingNodes = $docXPath->query($queryNumbering);
            foreach ($numberingNodes as $node) {
                $attr = $node->getAttribute('w:val');
                if ($attr == '0') {
                    continue;
                }
                // ignore the section numbering to avoid redefining the same
                // numbering used in different sections
                if (!isset($numberings[1][$attr])) {
                    $numberings[1][$attr] = $this->uniqueDecimal($this->_takenNumberingsIds);
                    $this->_takenNumberingsIds[] = $numberings[1][$attr];
                }
                if ($relabel) {
                    $node->setAttribute('w:val', $numberings[1][$attr]);
                }
            }

            //footnotes
            $queryFootnote = '//w:footnoteReference';
            $footnoteNodes = $docXPath->query($queryFootnote);
            foreach ($footnoteNodes as $node) {
                $attr = $node->getAttribute('w:id');
                $this->_takenFootnotesIds[] = $attr;
                $footnotes[$k][$attr] = $this->uniqueDecimal($this->_takenFootnotesIds, 1000, 32761);
                if ($relabel) {
                    $node->setAttribute('w:id', $footnotes[$k][$attr]);
                }
            }

            //endnotes
            $queryEndnote = '//w:endnoteReference';
            $endnoteNodes = $docXPath->query($queryEndnote);
            foreach ($endnoteNodes as $node) {
                $attr = $node->getAttribute('w:id');
                $this->_takenEndnotesIds[] = $attr;
                $endnotes[$k][$attr] = $this->uniqueDecimal($this->_takenEndnotesIds, 1000, 32761);
                if ($relabel) {
                    $node->setAttribute('w:id', $endnotes[$k][$attr]);
                }
            }

            //comments
            $queryComment = '//w:commentReference';
            $commentNodes = $docXPath->query($queryComment);
            foreach ($commentNodes as $node) {
                $attr = $node->getAttribute('w:id');
                $this->_takenCommentsIds[] = $attr;
                $comments[$k][$attr] = $this->uniqueDecimal($this->_takenCommentsIds, 1000, 32761);
                if ($relabel) {
                    $node->setAttribute('w:id', $comments[$k][$attr]);
                    //Now we have to set the w:id attribute of the corresponding w:commentRangeStart and w:commentRangeEnd tag to the same new value
                    $queryCommentStart = '//w:commentRangeStart[@w:id = "' . $attr . '"]';
                    $affectedNodes = $docXPath->query($queryCommentStart);
                    $nodeToBeChanged = $affectedNodes->item(0);
                    if ($nodeToBeChanged) {
                        $nodeToBeChanged->setAttribute('w:id', $comments[$k][$attr]);
                    }
                    //and now the end of the comment
                    $queryCommentEnd = '//w:commentRangeEnd[@w:id = "' . $attr . '"]';
                    $affectedNodes = $docXPath->query($queryCommentEnd);
                    $nodeToBeChanged = $affectedNodes->item(0);
                    if ($nodeToBeChanged) {
                        $nodeToBeChanged->setAttribute('w:id', $comments[$k][$attr]);
                    }
                }
            }

            //afChunk
            $queryAfChunk = '//w:altChunk';
            $afChunkNodes = $docXPath->query($queryAfChunk);
            foreach ($afChunkNodes as $node) {
                $attr = $node->getAttribute('r:id');
                $myId = uniqid(mt_rand(999, 9999));
                $newId = 'altChunk' . $myId;
                $afChunks[$k][$attr] = array(
                    'newId' => $newId
                );
                if ($relabel) {
                    $node->setAttribute('r:id', $newId);
                    //we update the rels file
                    $query = '//rels:Relationship[@Id="' . $attr . '"]';
                    $affectedNodes = $relsXPath->query($query);
                    $nodeToBeChanged = $affectedNodes->item(0);
                    //we first get the name of the file that is being linked
                    $fileName = $nodeToBeChanged->getAttribute('Target');
                    $afChunks[$k][$attr]['fileName'] = $fileName;
                    //we create a new and unique name for the file
                    $fileNameArray = explode(".", $fileName);
                    $fileExtension = array_pop($fileNameArray);
                    $fileNewName = $newId . '.' . $fileExtension;
                    $afChunks[$k][$attr]['newName'] = $fileNewName;
                    //now we change the Id and Target attributes in the rels file
                    $nodeToBeChanged->setAttribute('Target', $afChunks[$k][$attr]['newName']);
                    $nodeToBeChanged->setAttribute('Id', $afChunks[$k][$attr]['newId']);
                }
            }

            //Now we are going to regenerate all the "extra and otherwise useless ids" used in images and charts
            if ($relabel) {
                $queryDocPr = '//wp:docPr';
                $docPrNodes = $docXPath->query($queryDocPr);
                foreach ($docPrNodes as $node) {
                    $decimalNumber = $this->uniqueDecimal();
                    $node->setAttribute('id', $decimalNumber);
                    $node->setAttribute('name', uniqid(mt_rand(999, 9999)));
                }
                $queryPic = '//pic:cNvPr';
                $picNodes = $docXPath->query($queryPic);
                foreach ($picNodes as $node) {
                    $decimalNumber = $this->uniqueDecimal();
                    $node->setAttribute('id', $decimalNumber);
                }
            }


            //Section properties

            $docXPath = new \DOMXPath($sectionProperties[$k]);
            $docXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $docXPath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            //headers
            $queryHeader = '//w:headerReference ';
            $headerNodes = $docXPath->query($queryHeader);
            foreach ($headerNodes as $node) {
                $attr = $node->getAttribute('r:id');
                //Check if that header has been already parsed before
                if (array_key_exists($attr, $parsedHeaders)) {
                    $myId = $parsedHeaders[$attr];
                } else {
                    $myId = uniqid(mt_rand(999, 9999));
                    $parsedHeaders[$attr] = $myId;
                }
                $newName = 'header' . $myId . '.xml';
                $newId = 'rId' . $myId;
                $headers[$k][$attr] = array(
                    'type' => $node->getAttribute('w:type'),
                    'name' => $relsArray[$attr],
                    'newName' => $newName,
                    'newId' => $newId
                );
                if ($relabel) {
                    $node->setAttribute('r:id', $newId);
                    //we now update the Content_Types xml file
                    $query = '//ct:Override[@PartName="/word/' . $headers[$k][$attr]['name'] . '"]';
                    $affectedNodes = $contentTypesXPath->query($query);
                    if ($affectedNodes->length > 0) {//This check is needed because this header can be called from more than one section
                        $nodeToBeChanged = $affectedNodes->item(0);
                        $nodeToBeChanged->setAttribute('PartName', '/word/' . $headers[$k][$attr]['newName']);
                        //we now update the document.xml.rels file
                        $query = '//rels:Relationship[@Id="' . $attr . '"]';
                        $affectedNodes = $relsXPath->query($query);
                        $nodeToBeChanged = $affectedNodes->item(0);
                        $nodeToBeChanged->setAttribute('Target', $headers[$k][$attr]['newName']);
                        $nodeToBeChanged->setAttribute('Id', $headers[$k][$attr]['newId']);
                    }
                }
            }
            //footers
            $queryFooter = '//w:footerReference ';
            $footerNodes = $docXPath->query($queryFooter);
            foreach ($footerNodes as $node) {
                $attr = $node->getAttribute('r:id');
                //Check if that footer has been already parsed before
                if (array_key_exists($attr, $parsedFooters)) {
                    $myId = $parsedFooters[$attr];
                } else {
                    $myId = uniqid(mt_rand(999, 9999));
                    $parsedFooters[$attr] = $myId;
                }
                $newName = 'footer' . $myId . '.xml';
                $newId = 'rId' . $myId;
                $footers[$k][$attr] = array(
                    'type' => $node->getAttribute('w:type'),
                    'name' => $relsArray[$attr],
                    'newName' => $newName,
                    'newId' => $newId
                );
                if ($relabel) {
                    $node->setAttribute('r:id', $newId);
                    //we now update the Content_Types xml file
                    $query = '//ct:Override[@PartName="/word/' . $footers[$k][$attr]['name'] . '"]';
                    $affectedNodes = $contentTypesXPath->query($query);
                    if ($affectedNodes->length > 0) {//This check is needed because this footer can be called from more than one section
                        $nodeToBeChanged = $affectedNodes->item(0);
                        $nodeToBeChanged->setAttribute('PartName', '/word/' . $footers[$k][$attr]['newName']);
                        //we now update the document.xml.rels file
                        $query = '//rels:Relationship[@Id="' . $attr . '"]';
                        $affectedNodes = $relsXPath->query($query);
                        $nodeToBeChanged = $affectedNodes->item(0);
                        $nodeToBeChanged->setAttribute('Target', $footers[$k][$attr]['newName']);
                        $nodeToBeChanged->setAttribute('Id', $footers[$k][$attr]['newId']);
                    }
                }
            }
        }
        $structure = array('section' => $section,
            'sectionProperties' => $sectionProperties,
            'images' => $images,
            'charts' => $charts,
            'links' => $links,
            'bookmarks' => $bookmarks,
            'numberings' => $numberings,
            'headers' => $headers,
            'footers' => $footers,
            'footnotes' => $footnotes,
            'endnotes' => $endnotes,
            'comments' => $comments,
            'afChunks' => $afChunks);
        return $structure;
    }

    /**
     * This method counts the number of w:p childs of a node
     * @access private
     * @param DOMNode $myNode 
     * @return int
     */
    private function getNumberPChilds($myNode)
    {
        $childs = $myNode->childNodes;
        $number = 0;
        foreach ($childs as $node) {
            if ($node->nodeName == 'w:p') {
                $number++;
            }
        }
        return $number;
    }

    /**
     * Checks if there are contents in a given array of data like images, bookmarks, ...
     * @access private
     * @param array $dataArray
     * @return int
     */
    private function checkData($dataArray)
    {
        $num = 0;
        for ($j = 0; $j <= count($dataArray); $j++) {
            if (isset($dataArray[$j])) {
                $num += count($dataArray[$j]);
            }
        }
        return $num;
    }

    /**
     * Modifies the first docx structural data to accomodate the merged docx
     * @access private
     * @param array $firstDocx
     * @param array $secondDocx
     * @param array $options
     * @return string
     */
    private function compoundDocuments(&$firstDocx, $secondDocx, $options)
    {
        $firstNumSections = count($firstDocx['section']);
        $secondNumSections = count($secondDocx['section']);
        //Before starting the merging we should take into account the numbering
        //option (restart or continue numbering in the merged document)
        if (isset($options['numbering']) && $options['numbering'] == 'restart') {
            $numberingNodes = $secondDocx['sectionProperties'][1]->documentElement->getElementsByTagName('pgNumType');
            if ($numberingNodes->length > 0) {
                $numberingNode = $numberingNodes->item(0);
                $numberingNode->setAttribute('w:start', 1);
            } else {
                $pgNumType = $secondDocx['sectionProperties'][1]->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'pgNumType');
                $pgNumType->setAttribute('w:start', 1);
                //insert the node
                $tagIndex = array_search('w:pgNumType', OOXMLResources::$sectionProperties);
                $childNodes = $secondDocx['sectionProperties'][1]->documentElement->childNodes;
                $index = false;
                foreach ($childNodes as $node) {
                    $name = $node->nodeName;
                    $index = array_search($node->nodeName, OOXMLResources::$sectionProperties);
                    if ($index > $tagIndex) {
                        $node->parentNode->insertBefore($pgNumType, $node);
                        break;
                    }
                }
                //in case no node was found (pretty unlikely)we should append the node
                if (!$index) {
                    $secondDocx['sectionProperties'][1]->documentElement->appendChild($pgNumType);
                }
            }
        } else if (isset($options['numbering']) && $options['numbering'] == 'continue') {
            $numberingNodes = $secondDocx['sectionProperties'][1]->documentElement->getElementsByTagName('pgNumType');
            if ($numberingNodes->length > 0) {
                $numberingNode = $numberingNodes->item(0);
                $numberingNode->removeAttribute('w:start');
            }
        }
        //Check if the option enforceSectionPageBreak is set to true
        if (isset($options['enforceSectionPageBreak']) && $options['enforceSectionPageBreak']) {
            $typeNodes = $secondDocx['sectionProperties'][1]->documentElement->getElementsByTagName('type');
            if ($typeNodes->length > 0) {
                $typeNodes->item(0)->setAttribute('w:val', 'nextPage');
            } else {
                $sectType = $secondDocx['sectionProperties'][1]->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'type');
                $sectType->setAttribute('w:val', 'nextPage');
                //insert the node
                $tagIndex = array_search('w:type', OOXMLResources::$sectionProperties);
                $childNodes = $secondDocx['sectionProperties'][1]->documentElement->childNodes;
                $index = false;
                foreach ($childNodes as $node) {
                    $name = $node->nodeName;
                    $index = array_search($node->nodeName, OOXMLResources::$sectionProperties);
                    if ($index > $tagIndex) {
                        $node->parentNode->insertBefore($sectType, $node);
                        break;
                    }
                }
                //in case no node was found (pretty unlikely)we should append the node
                if (!$index) {
                    $secondDocx['sectionProperties'][1]->documentElement->appendChild($sectType);
                }
            }
        }
        //Check if we need to insert line breaks between documents
        if ($this->_wordMLChunk != '') {
            $fragment = $firstDocx['section'][$firstNumSections]->createDocumentFragment();
            $fragment->appendXML($this->_wordMLChunk);
            $firstDocx['section'][$firstNumSections]->appendChild($fragment);
        }
        //Add the new entry arrays
        for ($k = 1; $k <= $secondNumSections; $k++) {
            $firstDocx['section'][] = $secondDocx['section'][$k];
            if (empty($options['mergeType']) || $options['mergeType'] != 1) {
                $firstDocx['sectionProperties'][] = $secondDocx['sectionProperties'][$k];
            }
        }
    }

    /**
     * It returns the indexes of all occurrences of a needdle in a string
     * @access private
     * @param string $myString the string to be searched
     * @param string $search the text to be searched
     * @return array
     */
    private function getIndexOf($myString, $search)
    {
        $initialChar = 0;
        $charIndexes = array();
        $lengthSearchTerm = strlen($search);
        while (($pos = strpos($myString, $search, $initialChar)) !== false) {
            $charIndexes[] = $pos;
            $initialChar = $pos + $lengthSearchTerm;
        }
        return $charIndexes;
    }

    /**
     * Merges the required sections into a single file
     * @access private
     * @param array $firstDocx
     * @param array $options
     * @return string
     */
    private function mergeDocuments(&$firstDocx, $options)
    {

        $numSections = count($firstDocx['section']);
        $numSectionProperties = count($firstDocx['sectionProperties']);

        //Now we can proceed to generate the new document.xml file contents
        $mergedDocumentAsString = '';
        for ($k = 1; $k < $numSectionProperties; $k++) {
            $sectNode = $firstDocx['section'][$k]->importNode($firstDocx['sectionProperties'][$k]->documentElement, true);
            $lastNode = $firstDocx['section'][$k]->lastChild;
            if ($lastNode->nodeName == 'w:p') {
                //check now if there is a pPr child
                if (is_object($lastNode->firstChild) && $lastNode->firstChild->nodeName == 'w:pPr') {
                    //check the name of the last child
                    if (is_object($lastNode->firstChild->lastChild) && $lastNode->firstChild->lastChild->nodeName == 'w:pPrChange') {
                        $lastNode->firstChild->lastChild->parentNode->insertBefore($sectNode, $lastNode->firstChild->lastChild);
                    } else {
                        $lastNode->firstChild->appendChild($sectNode);
                    }
                } else {
                    $sectFragment = $firstDocx['section'][$k]->createDocumentFragment();
                    $sectFragment->appendXML('<w:pPr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">' . $firstDocx['sectionProperties'][$k]->documentElement->ownerDocument->saveXML($firstDocx['sectionProperties'][$k]->documentElement) . '</w:pPr>');
                    if ($lastNode->hasChildNodes()) {
                        //if it has child nodes we insert it before the first one
                        $lastNode->insertBefore($sectFragment, $lastNode->firstChild);
                    } else {
                        //if it does not have child nodes we simply append it
                        $lastNode->appendChild($sectFragment);
                    }
                }
            } else {
                $sectFragment = $firstDocx['section'][$k]->createDocumentFragment();
                $sectFragment->appendXML('<w:p xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:pPr>' . $firstDocx['sectionProperties'][$k]->documentElement->ownerDocument->saveXML($firstDocx['sectionProperties'][$k]->documentElement) . '</w:pPr></w:p>');
                //we insert a p node just before it
                //$lastNode->parentNode->insertBefore($sectFragment,$node);
                $lastNode->parentNode->appendChild($sectFragment);
            }
            //we now concatenate the resulting document
            $mergedDocumentAsString .= $firstDocx['section'][$k]->saveXML();
        }
        //Concatenate the remaining sections
        $sectionOffset = $numSections - $numSectionProperties;
        for ($j = 0; $j <= $sectionOffset; $j++) {
            $mergedDocumentAsString .= $firstDocx['section'][$numSectionProperties + $j]->saveXML();
        }
        //Concatenate the last sectPr               
        $mergedDocumentAsString .= $firstDocx['sectionProperties'][$numSectionProperties]->saveXML();
        //we now remove the xml headers
        $mergedDocumentAsString = str_replace('<?xml version="1.0"?>', '', $mergedDocumentAsString);

        return $mergedDocumentAsString;
    }

    /**
     * Merges the required numbering styles into a single file
     * @access private
     * @param \DOMDocument $myOriginalNumbering
     * @param \DOMDocument $myMergedNumbering
     * @param array $numberings structural info about the lists
     * @return string
     */
    private function mergeNumberings($myOriginalNumbering, $myMergedNumbering, $numberings)
    {
        //Prepare $myMergedNumbering for xPath searches of the required nodes
        $mergedXPath = new \DOMXPath($myMergedNumbering);
        $mergedXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        //we have to remove the w:nsid and w:tmpl elements to avoid conflicts when merging twice the same template
        $nsidNumbering = '//w:nsid | //w:tmpl';
        $nsidNodes = $mergedXPath->query($nsidNumbering);
        foreach ($nsidNodes as $node) {
            $node->parentNode->removeChild($node);
        }
        // create an auxiliary array to avoid the relabeling of numberings that are used multiple times in different sections
        $refNumberings = array();
        for ($j = 1; $j <= count($numberings); $j++) {
            foreach ($numberings[$j] as $key => $value) {
                if (!in_array($key, $refNumberings)) {
                    $query = '//w:num[@w:numId="' . $key . '"]';
                    $numNodes = $mergedXPath->query($query);
                    //we now get the associated numbering style but we should first check that $numNodes is not empty
                    if ($numNodes->length > 0) {
                        $absNum = $numNodes->item(0)->getElementsByTagName('abstractNumId')->item(0)->getAttribute('w:val');
                        $query = '//w:abstractNum[@w:abstractNumId="' . $absNum . '"]';
                        $absNumNodes = $mergedXPath->query($query);
                        if ($absNumNodes->length > 0) {
                            //we have to check if there are results because we may have already
                            //redefined that numbering because it was used by other list
                            $absNumNode = $absNumNodes->item(0);
                            //we create a new abstractNumId (we use the same number to simplify debugging)
                            $newAbstractNumId = $value;
                            $absNumNode->setAttribute('w:abstractNumId', $newAbstractNumId);
                            $base = $myOriginalNumbering->documentElement->firstChild;
                            $newNumNode = $myOriginalNumbering->importNode($absNumNode, true);
                            $base->parentNode->insertBefore($newNumNode, $base);

                            // check if the numbering has a lvlPicBulletId tag and include it if true
                            $lvlPicBulletId = $newNumNode->getElementsByTagName('lvlPicBulletId');
                            if ($lvlPicBulletId->length > 0) {
                                // generate a new unique ID
                                $newIdLvlPicBulletId = mt_rand(999, 9999);

                                // get the w:numPicBullet content
                                $queryNumPicBullet = '//w:numPicBullet[@w:numPicBulletId="' . $lvlPicBulletId->item(0)->getAttribute('w:val') . '"]';
                                $absNumPicBullet = $mergedXPath->query($queryNumPicBullet);
                                if ($absNumPicBullet->length > 0) {
                                    // update the lvlPicBulletId in the main numbering file
                                    $lvlPicBulletId->item(0)->setAttribute('w:val', $newIdLvlPicBulletId);

                                    $absNumPicBullet->item(0)->setAttribute('w:numPicBulletId', $newIdLvlPicBulletId);
                                    $newNNumPicBullet = $myOriginalNumbering->importNode($absNumPicBullet->item(0), true);
                                    $newNumNode->parentNode->insertBefore($newNNumPicBullet, $newNumNode);
                                }
                            }

                            // include the relationship
                            $newNum = '<w:num w:numId="' . $value . '" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:abstractNumId w:val="' . $newAbstractNumId . '" /></w:num>';
                            $numFragment = $myOriginalNumbering->createDocumentFragment();
                            $numFragment->appendXML($newNum);
                            $myOriginalNumbering->documentElement->appendChild($numFragment);
                        }
                    }
                }

                $refNumberings[] = $key;
            }
        }
    }

    /**
     * Merges the required numbering in styles into a single file
     * 
     * @access private
     * @param DOMDocument $stylesDOM
     * @param DOMDocument $firstNumberingDOM
     * @param DOMDocument $secondNumberingDOM
     */
    private function mergeNumberingsStyles($stylesDOM, $firstNumberingDOM, $secondNumberingDOM)
    {
        $stylesXPath = new \DOMXPath($stylesDOM);
        $stylesXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $firstNumberingXPath = new \DOMXPath($firstNumberingDOM);
        $firstNumberingXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $secondNumberingXPath = new \DOMXPath($secondNumberingDOM);
        $secondNumberingXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $numberings = array();
        $numberings[1] = array();

        $queryNumbering = '//w:numPr/w:numId';
        $numberingNodes = $stylesXPath->query($queryNumbering);
        foreach ($numberingNodes as $node) {
            $attr = $node->getAttribute('w:val');
            if ($attr == '0') {
                continue;
            }
            // ignore the section numbering to avoid redefining the same
            // numbering used in different sections
            if (!isset($numberings[1][$attr])) {
                $numberings[1][$attr] = $this->uniqueDecimal($this->_takenNumberingsIds);
                $this->_takenNumberingsIds[] = $numberings[1][$attr];
            }
            $node->setAttribute('w:val', $numberings[1][$attr]);
        }

        if (count($numberings[1]) > 0) {
            $this->mergeNumberings($firstNumberingDOM, $secondNumberingDOM, $numberings);
        }
    }

    /**
     * Merges the required styles into a single file
     * @access private
     * @param \DOMDocument $myOriginalStyles
     * @param \DOMDocument $myMergedStyles
     * @return string
     */
    private function mergeStyles($myOriginalStyles, $myMergedStyles)
    {

        //Prepare $myMergedStyles and $myOriginalStyles for XPath searches of the required nodes
        $mergedXPath = new \DOMXPath($myMergedStyles);
        $mergedXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $originalXPath = new \DOMXPath($myOriginalStyles);
        $originalXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        //we now extract the style nodes from the file to be merged
        $query = '//w:style';
        $mergedStyleNodes = $mergedXPath->query($query);
        foreach ($mergedStyleNodes as $node) {
            $styleId = $node->getAttribute('w:styleId');
            //Let us check if that style already exists in the original file
            $query = '//w:style[@w:styleId="' . $styleId . '"]';
            $foundNodes = $originalXPath->query($query);
            if ($foundNodes->length == 0) {
                $newStyleNode = $myOriginalStyles->importNode($node, true);
                if ($this->_preserveDefaults) {
                    $newStyleNode->removeAttribute('w:default');
                }
                $myOriginalStyles->documentElement->appendChild($newStyleNode);
            }
        }
    }

    /**
     * Merges the required footnotes files into a single file
     * @access private
     * @param \DOMDocument $myOriginalFootnotes
     * @param \DOMDocument $myMergedFootnotes
     * @param array $footnotes structural info about the footnotes
     * @return string
     */
    private function mergeFootnotes(&$myOriginalFootnotes, $myMergedFootnotes, $footnotes)
    {
        //Prepare $myMergedFootnotes for xPath searches of the required nodes
        $mergedXPath = new \DOMXPath($myMergedFootnotes);
        $mergedXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        for ($j = 1; $j <= count($footnotes); $j++) {
            foreach ($footnotes[$j] as $key => $value) {
                $query = '//w:footnote[@w:id=' . $key . ']';
                $footnoteNodes = $mergedXPath->query($query);
                //we now get the associated footnote
                $nodeFootnote = $footnoteNodes->item(0);
                $nodeFootnote->setAttribute('w:id', $value);
                $newFootnoteNode = $myOriginalFootnotes->importNode($nodeFootnote, true);
                $base = $myOriginalFootnotes->documentElement;
                $base->appendChild($newFootnoteNode);
            }
        }
    }

    /**
     * Merges the required endnotes files into a single file
     * @access private
     * @param \DOMDocument $myOriginalEndnotes
     * @param \DOMDocument $myMergedEndnotes
     * @param array $endnotes structural info about the endnotes
     * @return string
     */
    private function mergeEndnotes($myOriginalEndnotes, $myMergedEndnotes, $endnotes)
    {
        //Prepare $myMergedEndnotes for xPath searches of the required nodes
        $mergedXPath = new \DOMXPath($myMergedEndnotes);
        $mergedXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        for ($j = 1; $j <= count($endnotes); $j++) {
            foreach ($endnotes[$j] as $key => $value) {
                $query = '//w:endnote[@w:id=' . $key . ']';
                $endnoteNodes = $mergedXPath->query($query);
                //we now get the associated endnote
                $nodeEndnote = $endnoteNodes->item(0);
                $nodeEndnote->setAttribute('w:id', $value);
                $newEndnoteNode = $myOriginalEndnotes->importNode($nodeEndnote, true);
                $base = $myOriginalEndnotes->documentElement;
                $base->appendChild($newEndnoteNode);
            }
        }
    }

    /**
     * Merges the required comments files into a single file
     * @access private
     * @param \DOMDocument $myOriginalComments
     * @param \DOMDocument $myMergedComments
     * @param array comments structural info about the comments
     * @return string
     */
    private function mergeComments($myOriginalComments, $myMergedComments, $comments)
    {
        //Prepare $myMergedComments for xPath searches of the required nodes
        $mergedXPath = new \DOMXPath($myMergedComments);
        $mergedXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        for ($j = 1; $j <= count($comments); $j++) {
            foreach ($comments[$j] as $key => $value) {
                $query = '//w:comment[@w:id=' . $key . ']';
                $commentNodes = $mergedXPath->query($query);
                //we now get the associated comment
                $nodeComment = $commentNodes->item(0);
                $nodeComment->setAttribute('w:id', $value);
                $newCommentNode = $myOriginalComments->importNode($nodeComment, true);
                $base = $myOriginalComments->documentElement;
                $base->appendChild($newCommentNode);
            }
        }
    }

    /**
     * Merges the required comments extended files into a single file
     * @access private
     * @param DOMDocument $myOriginalCommentsExtended
     * @param DOMDocument $myMergedCommentsExtended
     * @param DOMDocument $myOriginalComments
     * @param DOMDocument $myMergedComments
     * @param array comments structural info about the comments
     * @return string
     */
    private function mergeCommentsExtended($myOriginalCommentsExtended, $myMergedCommentsExtended, $myMergedComments)
    {
        //Prepare $myMergedComments for xPath searches of the required nodes
        $mergedCommentsExtendedXPath = new \DOMXPath($myMergedCommentsExtended);
        $mergedCommentsExtendedXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $mergedCommentsExtendedXPath->registerNamespace('w14', 'http://schemas.microsoft.com/office/word/2010/wordml');
        $mergedCommentsExtendedXPath->registerNamespace('w15', 'http://schemas.microsoft.com/office/word/2012/wordml');
        $mergedXPath = new \DOMXPath($myMergedComments);
        $mergedXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $mergedXPath->registerNamespace('w14', 'http://schemas.microsoft.com/office/word/2010/wordml');
        $mergedXPath->registerNamespace('w15', 'http://schemas.microsoft.com/office/word/2012/wordml');

        // iterate comments and get those that have a w14:paraId attribute
        $query = '//w:p';
        $commentNodes = $mergedXPath->query($query);
        // generate a new paraId for the comment and its extended tag
        if ($commentNodes->length > 0) {
            foreach ($commentNodes as $commentNode) {
                if ($commentNode->hasAttribute('w14:paraId')) {
                    $currentParaId = $commentNode->getAttribute('w14:paraId');
                    $paraId = dechex(mt_rand(9, 999999));
                    $commentNode->setAttribute('w14:paraId', $paraId);

                    $queryCommentEx = '//w15:commentEx[@w15:paraId="' . $currentParaId . '"]';
                    $commentExtNodes = $mergedCommentsExtendedXPath->query($queryCommentEx);
                    if ($commentExtNodes->length > 0) {
                        $newCommentExtendedNode = $myOriginalCommentsExtended->importNode($commentExtNodes->item(0), true);
                        $newCommentExtendedNode->setAttribute('w15:paraId', $paraId);
                        $baseCommentsExtended = $myOriginalCommentsExtended->documentElement;
                        $baseCommentsExtended->appendChild($newCommentExtendedNode);
                    }
                }
            }
        }
    }

    /**
     * Merges the required [Content_Types].xml into a single file
     * @access private
     * @param \DOMDocument $myOriginalContentTypes
     * @param \DOMDocument $myMergedContentTypes
     * @return string
     */
    private function mergeContentTypes($myOriginalContentTypes, $myMergedContentTypes)
    {
        //Prepare $myMergedContentTypes and $myOriginalContentTypes for xPath searches of the required nodes
        $mergedXPath = new \DOMXPath($myMergedContentTypes);
        $mergedXPath->registerNamespace('ct', 'http://schemas.openxmlformats.org/package/2006/content-types');
        $originalXPath = new \DOMXPath($myOriginalContentTypes);
        $originalXPath->registerNamespace('ct', 'http://schemas.openxmlformats.org/package/2006/content-types');
        //we now extract the Override nodes from the file to be merged
        $query = '//ct:Override';
        $mergedContentTypeNodes = $mergedXPath->query($query);
        foreach ($mergedContentTypeNodes as $node) {
            $partName = $node->getAttribute('PartName');
            //Let us check if that PartName already exists in the original file
            $query = '//ct:Override[@PartName="' . $partName . '"]';
            $foundNodes = $originalXPath->query($query);
            if ($foundNodes->length == 0) {
                $newOverrideNode = $myOriginalContentTypes->importNode($node, true);
                $myOriginalContentTypes->documentElement->appendChild($newOverrideNode);
            }
        }
        //we now extract the Default nodes from the file to be merged
        $query = '//ct:Default';
        $mergedContentTypeNodes = $mergedXPath->query($query);
        foreach ($mergedContentTypeNodes as $node) {
            $extension = $node->getAttribute('Extension');
            //Let us check if that Extension already exists in the original file
            $query = '//ct:Default[@Extension="' . strtolower($extension) . '"]';
            $foundNodes = $originalXPath->query($query);
            if ($foundNodes->length == 0) {
                $newDefaultNode = $myOriginalContentTypes->importNode($node, true);
                $myOriginalContentTypes->documentElement->appendChild($newDefaultNode);
            }
        }
    }

    /**
     * Merges the required document.xml.rels into a single file
     * @access private
     * @param \DOMDocument $myOriginalRels
     * @param \DOMDocument $myMergedrels
     * @return string
     */
    private function mergeRels($myOriginalRels, $myMergedRels)
    {
        //Prepare $myMergedRels and $myOriginalRels for xPath searches of the required nodes
        $mergedXPath = new \DOMXPath($myMergedRels);
        $mergedXPath->registerNamespace('rels', 'http://schemas.openxmlformats.org/package/2006/relationships');
        $originalXPath = new \DOMXPath($myOriginalRels);
        $originalXPath->registerNamespace('rels', 'http://schemas.openxmlformats.org/package/2006/relationships');
        //we now extract the Realtionship nodes from the file to be merged without TargetMode
        $query = '//rels:Relationship[not(@TargetMode)]';
        $mergedRelsNodes = $mergedXPath->query($query);
        foreach ($mergedRelsNodes as $node) {
            $target = $node->getAttribute('Target');
            $currentId = $node->getAttribute('Id');
            //We are going to filter the CustomXML, glossary and .bin files that we are not going to import by the time being
            if (strstr($target, 'customXml') === false &&
                    strstr($target, 'glossary') === false &&
                    strstr($target, '.bin') === false) {
                //$targetMode = $node->getAttribute('TargetMode');
                //Let us check if that Target already exists in the original file
                $query = '//rels:Relationship[@Target="' . $target . '"]';
                $foundNodes = $originalXPath->query($query);
                if ($foundNodes->length == 0) {
                    if (in_array($target, $this->_implicitRelationships)) {
                        $node->setAttribute('Id', 'Id' . uniqid(mt_rand(999, 9999)));
                        $newRelationshipNode = $myOriginalRels->importNode($node, true);
                        $myOriginalRels->documentElement->appendChild($newRelationshipNode);
                    } else {
                        //Check that the id does not conflict with an existing Id in the original rels file
                        $queryId = '//rels:Relationship[@Id="' . $currentId . '"]';
                        $currentIdNodes = $originalXPath->query($queryId);
                        if ($currentIdNodes->length == 0) {
                            $newRelationshipNode = $myOriginalRels->importNode($node, true);
                            $myOriginalRels->documentElement->appendChild($newRelationshipNode);
                        }
                    }
                }
            }
        }
        $query = '//rels:Relationship[@TargetMode]';
        $mergedRelsNodes = $mergedXPath->query($query);
        foreach ($mergedRelsNodes as $node) {
            $currentId = $node->getAttribute('Id');
            //Check that the id does not conflict with an existing Id in the original rels file
            $queryId = '//rels:Relationship[@Id="' . $currentId . '"]';
            $currentIdNodes = $originalXPath->query($queryId);
            if ($currentIdNodes->length == 0) {
                $queryId = '//rels:Relationship[@Id="' . $currentId . '"]';
                $newRelationshipNode = $myOriginalRels->importNode($node, true);
                $myOriginalRels->documentElement->appendChild($newRelationshipNode);
            }
        }
    }

    /**
     * Generates a unique Decimal number
     * @access public
     * @param int $min
     * @param int $max
     * @return int
     */
    public function uniqueDecimal(&$takenIds = array(), $min = 9999, $max = 0)
    {
        if ($max == 0) {
            $max = mt_getrandmax();
        }
        $proposedId = mt_rand($min, $max);
        if (in_array($proposedId, $takenIds)) {
            $proposedId = $this->uniqueDecimal($takenIds, $min, $max);
        }
        $takenIds[] = $proposedId;
        return $proposedId;
    }

    /**
     * To add support of sys_get_temp_dir for PHP versions under 5.2.1
     * 
     * @access private
     * @return string
     */
    public function getTempDir()
    {
        if (!function_exists('sys_get_temp_dir')) {

            function sys_get_temp_dir()
            {
                if ($temp = getenv('TMP')) {
                    return $temp;
                }
                if ($temp = getenv('TEMP')) {
                    return $temp;
                }
                if ($temp = getenv('TMPDIR')) {
                    return $temp;
                }
                $temp = tempnam(__FILE__, '');
                if (file_exists($temp)) {
                    unlink($temp);
                    return dirname($temp);
                }
                return null;
            }

        } else {
            return sys_get_temp_dir();
        }
    }

}
