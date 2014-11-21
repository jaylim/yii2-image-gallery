<?php

namespace pentajeu\yii\widgets\gallery;

use Yii;
use yii\web\AssetBundle;

class GalleryAsset extends AssetBundle
{
    public $sourcePath = 'assets';
    public $depends = [
        'yii\web\JqueryAsset',
        'yii\jui\JuiAsset',
    ];

    public function init()
    {
        $this->css[] = 'galleryManager.css';
        $this->js[] = 'jquery.iframe-transport.min.js';
        $this->js[] = 'jquery.galleryManager.min.js';

        parent::init();
    }
}
