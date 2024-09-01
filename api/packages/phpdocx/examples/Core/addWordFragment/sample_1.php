<?php

require_once '../../../Classes/Phpdocx/Create/CreateDocx.php';

$docx = new Phpdocx\Create\CreateDocx();

$wordFragment = new Phpdocx\Elements\WordFragment($docx);

$imageOptions = array(
    'src' => '../../img/image.png',
    'scaling' => 50, 
    'float' => 'right',
    'textWrap' => 1,
);
$wordFragment->addImage($imageOptions);

$linkOptions = array(
    'url' => 'http://www.google.es', 
    'color' => '0000FF', 
    'underline' => 'single',
);
$wordFragment->addLink('link to Google', $linkOptions);

$docx->addWordFragment($wordFragment);

$docx->createDocx('example_addWordFragment_1');