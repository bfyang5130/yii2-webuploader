<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/27
 * Time: 10:53
 */

namespace bfyang5130\webuploader;

use bfyang5130\AliyunOSS\AliyunOSS;
use Yii;

class OSS
{


    private $ossClient;

    public function __construct()
    {
        $serverAddress = (isset(Yii::$app->params['oss']['isInternal'])&&Yii::$app->params['oss']['isInternal']===true) ? Yii::$app->params['oss']['ossServerInternal'] : Yii::$app->params['oss']['ossServer'];
        $this->ossClient = AliyunOSS::boot(
            $serverAddress,
            Yii::$app->params['oss']['AccessKeyId'],
            Yii::$app->params['oss']['AccessKeySecret']
        );
    }

    public static function upload($ossKey, $filePath)
    {
        //$oss = new OSS(true); // 上传文件使用内网，免流量费
        $oss = new OSS();
        $oss->ossClient->setBucket(Yii::$app->params['oss']['Bucket']);
        $oss->ossClient->uploadFile($ossKey, $filePath);
    }

    public static function getUrl($ossKey)
    {
        $oss = new OSS();
        $oss->ossClient->setBucket(Yii::$app->params['oss']['Bucket']);
        return preg_replace('/(.*)\?OSSAccessKeyId=.*/', '$1', $oss->ossClient->getUrl($ossKey, new \DateTime("+1 day")));
    }

    public static function delFile($ossKey)
    {
        $oss = new OSS();
        $oss->ossClient->setBucket(Yii::$app->params['oss']['Bucket']);
        $oss->ossClient->delFile($ossKey);
    }

    public static function createBucket($bucketName)
    {
        $oss = new OSS();
        return $oss->ossClient->createBucket($bucketName);
    }

    public static function getAllObjectKey($bucketName)
    {
        $oss = new OSS();
        return $oss->ossClient->getAllObjectKey($bucketName);
    }
}