<?php

namespace bfyang5130\webuploader;

use Yii;
use yii\base\Action;
use yii\helpers\Html;
use yii\base\InvalidConfigException;
use yii\helpers\Url;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use bfyang5130\webuploader\OSS;

class FileUploadAction extends Action
{
    /**
     * @var array
     */
    public $config = [];


    /**
     * 配置初始参数
     */
    public function init()
    {
        //close csrf
        Yii::$app->request->enableCsrfValidation = false;

        //默认设置
        $_config = require(__DIR__ . '/config.php');
        //添加图片默认root路径；
        $_config['imageRoot'] = Yii::getAlias('@webroot');

        //load config file
        $this->config = ArrayHelper::merge($_config, $this->config);
        parent::init();
    }

    public function run()
    {
        /**
         * 设置返回数据形式
         */
        if (Yii::$app->request->get('callback',false)) {
            Yii::$app->response->format = Response::FORMAT_JSONP;
        } else {
            Yii::$app->response->format = Response::FORMAT_JSON;
        }
        return $this->handleAction();
    }

    /**
     * 处理action
     */
    protected function handleAction()
    {
       $result = $this->actionUpload();
       if(is_array($result)&&isset($result['state'])&&$result['state']=='SUCCESS'){
           if(isset($this->config['aliyunoss'])&&$this->config['aliyunoss']===true){
               if($this->ossALiyun($result)){
                   $rw_result=[
                       'code'=>0,
                       "url"=>isset(Yii::$app->params['oss']['ossOutLink'])?Yii::$app->params['oss']['ossOutLink'].$result['url']:$result['url'],
                       "attachment"=> isset(Yii::$app->params['oss']['ossOutLink'])?Yii::$app->params['oss']['ossOutLink'].$result['url']:$result['url']
                   ];
               }else{
                   $rw_result=[
                       'code'=>1,
                       "msg"=>'上传失败'
                   ];
               }

               @unlink($this->config['imageRoot'].$result['url']);
           }else{
               return $rw_result=[
                   'code'=>0,
                   "url"=>isset($this->config['imageUrlPrefix'])?$this->config['imageUrlPrefix'].$result['url']:$result['url'],
                   "attachment"=> isset($this->config['imageUrlPrefix'])?$this->config['imageUrlPrefix'].$result['url']:$result['url']
               ];
           }

       }else{
           $rw_result=[
               'code'=>1,
               "msg"=>'上传失败'
           ];
       }
        return $rw_result;
    }

    /**
     * 上传
     * @return array
     */
    protected function actionUpload()
    {
        $base64 = "upload";
        $config = array(
            "pathRoot" => ArrayHelper::getValue($this->config, "imageRoot", $_SERVER['DOCUMENT_ROOT']),
            "pathFormat" => $this->config['imagePathFormat'],
            "maxSize" => $this->config['imageMaxSize'],
            "allowFiles" => $this->config['imageAllowFiles']
        );
        $fieldName = $this->config['imageFieldName'];
        /* 生成上传实例对象并完成上传 */

        $up = new Uploader($fieldName, $config, $base64);
        /**
         * 得到上传文件所对应的各个参数,数组结构
         * array(
         *     "state" => "",          //上传状态，上传成功时必须返回"SUCCESS"
         *     "url" => "",            //返回的地址
         *     "title" => "",          //新文件名
         *     "original" => "",       //原始文件名
         *     "type" => ""            //文件类型
         *     "size" => "",           //文件大小
         * )
         */

        /* 返回数据 */
        return $up->getFileInfo();
    }

    protected function ossALiyun($result){
          try{
              if(stripos($result['url'],'/')==0){
                  $newUrl=substr($result['url'],'1');
              }
              $result=OSS::upload($newUrl, $this->config['imageRoot'].$result['url']);
              return true;
          }catch (\Exception $e){
              \Yii::error($e);
              return false;
          }
    }
}