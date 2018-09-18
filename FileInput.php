<?php

namespace bfyang5130\webuploader;

use Yii;
use yii\widgets\InputWidget;
use yii\helpers\Html;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
use yii\helpers\Json;

class FileInput extends InputWidget
{
    public $clientOptions = [];
    public $domain='';
    public $uploadUrl='';
    public $chooseButtonClass = ['class' => 'btn-default'];
    public $baseConfig=[];
    public $delimiter =',';
    public $defaultBaseConfig = [
        'defaultImage' => 'http://img1.imgtn.bdimg.com/it/u=2056478505,162569476&fm=26&gp=0.jpg',
        'disableGlobalDnd' => true,
        'accept' => [
            'title' => 'Images',
            'extensions' => 'gif,jpg,jpeg,bmp,png',
            'mimeTypes' => 'image/*',
        ],
        'pick' => [
            'multiple' => false,
        ],
    ];
    private $_view;
    private $_hashVar;
    private $_encOptions;
    private $_config;

    public function init ()
    {
        parent::init();
        $this->_view = $this->getView();
        $this->initOptions();
        $this->initConfig();
        $this->registerClientScript();
    }

    public function run ()
    {
        if ($this->hasModel()) {
            $model = $this->model;
            $attribute = $this->attribute;

            // 单图
            if (empty($this->_config['pick']['multiple'])) {
                $html = $this->renderInput($model, $attribute);
                $html .= $this->renderImage($model, $attribute);
            } 
            // 多图
            else {
                $html = $this->renderMultiInput($model, $attribute);
                $html .= $this->renderMultiImage($model, $attribute);
            }
            
            echo $html;
        }
    }

    /**
     * init options
     */
    public function initOptions ()
    {
        // to do.
        $id = md5($this->options['id']);
        $this->hashClientOptions("webupload_config_{$id}");
    }

    /**
     * register base js config
     */
    public function initConfig ()
    {
        if (empty($this->domain)) {
            throw new InvalidConfigException("domain` must set.", 1);
        }
        $this->_config = $this->mergeConfig();
        $config = Json::htmlEncode($this->_config);
        $js = <<<JS
            var {$this->_hashVar} = {$config};
            $('#{$this->_hashVar}').webupload_fileinput({$this->_hashVar});
JS;
        $this->_view->registerJs($js);
    }

    /**
     * Registers the needed client script and options.
     */
    public function registerClientScript ()
    {
        FileInputAsset::register($this->_view);
    }

    /**
     * generate hash var by plugin options
     */
    protected function hashClientOptions($name)
    {
        $this->_encOptions = empty($this->clientOptions) ? '' : Json::htmlEncode($this->clientOptions);
        $this->_hashVar = $name . '_' . hash('crc32', $this->_encOptions);
    }

    public function mergeConfig ()
    {
        // $config = $this->mergeArray($this->getDefaultClientOptions(), $this->clientOptions);
        $config = array_merge($this->getDefaultClientOptions(), $this->clientOptions);
        if (isset($this->clientOptions['csrf']) && $this->clientOptions['csrf'] === false) {
        } else {
            $config['formData'][Yii::$app->request->csrfParam] = Yii::$app->request->csrfToken;
        }

        $config['modal_id'] = $this->_hashVar;

        if (empty($config['server'])) {
            if(empty($this->uploadUrl)){
                throw new InvalidConfigException("后台处理理由必须进行设置.", 2);
            }
            $uploadUrl = $this->uploadUrl;
            $config['server'] = Url::to([$uploadUrl]);
        }

        return $config;
    }

    /**
     * array merge
     */
    public function mergeArray ($oriArr, $desArr)
    {
        foreach ($oriArr as $k => $v) {
            if (array_key_exists($k, $desArr)) {
                if (is_array($v) && $v) {
                    foreach ($v as $k2 => $v2) {
                        if (array_key_exists($k2, $desArr[$k])) {
                            $oriArr[$k][$k2] = $desArr[$k][$k2];
                        }
                    }
                } else {
                    $oriArr[$k] = $desArr[$k];
                }
            }
        }
        return $oriArr;
    }

    /**
     * register default config for js
     */
    public function getDefaultClientOptions ()
    {
        $this->baseConfig=array_merge($this->defaultBaseConfig,$this->baseConfig);
        return $this->baseConfig;
    }

    /**
     * render html body-input
     */
    public function renderInput ($model, $attribute)
    {
        Html::addCssClass($this->chooseButtonClass, "btn {$this->_hashVar}");
        $eles = [];
        $eles[] = Html::activeTextInput($model, $attribute, ['class' => 'form-control']);
        $eles[] = Html::tag('span', Html::button('选择图片', $this->chooseButtonClass), ['class' => 'input-group-btn']);

        return Html::tag('div', implode("\n", $eles), ['class' => 'input-group']);
    }

    /**
     * render html body-input-multi
     */
    public function renderMultiInput ($model, $attribute)
    {
        $inputName = Html::getInputName($model, $attribute);
        Html::addCssClass($this->chooseButtonClass, "btn {$this->_hashVar}");
        $eles = [];
        $eles[] = Html::textInput($attribute, null, ['class' => 'form-control', 'readonly' => 'readonly']);
        $eles[] = Html::hiddenInput($inputName, null);
        $eles[] = Html::tag('span', Html::button('选择图片', $this->chooseButtonClass), ['class' => 'input-group-btn']);

        return Html::tag('div', implode("\n", $eles), ['class' => 'input-group']);
    }

    /**
     * render html body-image
     */
    public function renderImage ($model, $attribute)
    {
        $src = $this->baseConfig['defaultImage'];
        $eles = [];
        if (($value = $model->$attribute)) {
            $src = $this->_validateUrl($value) ? $value :$this->domain . $value;
        }
        $eles[] = Html::img($src, ['class' => 'img-responsive img-thumbnail cus-img']);
        $eles[] = Html::tag('em', 'x', ['class' => 'close delImage', 'title' => '删除这张图片']);

        return Html::tag('div', implode("\n", $eles), ['class' => 'input-group', 'style' => 'margin-top:.5em;']);
    }

    /**
     * render html body-image-muitl
     */
    public function renderMultiImage ($model, $attribute)
    {
        /**
         * @var $srcTmp like this: src1,src2...srcxxx
         */
        $srcTmp = $model->$attribute;
        $items = [];
        if ($srcTmp) {
            is_string($srcTmp) && $srcTmp = explode($this->delimiter, $srcTmp);
            $inputName = Html::getInputName($model, $attribute);
            foreach ($srcTmp as $k => $v) {
                $dv = $this->_validateUrl($v) ? $v : $this->domain. $v;
                $src = $v ? $dv : $this->baseConfig['defaultImage'];
                $eles = [];
                $eles[] = Html::img($src, ['class' => 'img-responsive img-thumbnail cus-img']);
                $eles[] = Html::hiddenInput($inputName . "[]", $v);
                $eles[] = Html::tag('em', 'x', ['class' => 'close delMultiImage', 'title' => '删除这张图片']);
                $items[] = Html::tag('div', implode("\n", $eles), ['class' => 'multi-item']);
            }
        } 

        return Html::tag('div', implode("\n", $items), ['class' => 'input-group multi-img-details']);
    }

    /**
     * validate `$value` is url
     */
    private function _validateUrl ($value)
    {
        $pattern = '/^{schemes}:\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)(?::\d{1,5})?(?:$|[?\/#])/i';
        $validSchemes = ['http', 'https'];
        $pattern = str_replace('{schemes}', '(' . implode('|', $validSchemes) . ')', $pattern);
        if (!preg_match($pattern, $value)) {
            return false;
        }
        return true;
    }
}