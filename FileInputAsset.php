<?php

namespace bfyang5130\webuploader;

use yii\web\AssetBundle;

class FileInputAsset extends AssetBundle
{
    public $css = [
    	'webuploader/style.css',
        'webuploader/webuploader.css',
        'css/style.css',
    ];
    public $js = [
        'webuploader/webuploader.min.js',
        'webuploader/init.js'
    ];
    public $depends = [
        'yii\bootstrap\BootstrapPluginAsset',
		'yii\web\JqueryAsset',
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__;
        parent::init();
    }
}
