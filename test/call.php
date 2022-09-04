<?php
require_once __DIR__ . '/../vendor/autoload.php';

use ApolloClient\ApolloClient;

function pullToEnv()
{
    $base_url = 'http://127.0.0.1:8080';
    $appid = '123456';
    $namespace = ['application'];

    $cluster = 'default';
    $envDir = __DIR__ . '/../env';
    $saveDir = __DIR__ . '/../src';

    $client = new ApolloClient($base_url, $appid, $namespace);

    //方式1
    /*$client->setCluster($cluster)    //设置集群名
        ->setEnvFileName('env-2')        //设置env文件名
        // ->setTplFileName('tpl.php')  //设置env模板文件名
        ->setEnvTemplateDir($envDir)         //设置env模板文件所在路径
        ->setEnvSavedDir($saveDir);      //设置env文件的存储路径
    $client->startLongPull(1);*/

    //方式2
    $envFullPath = __DIR__ . '/env-3';
    $client->setCluster($cluster)       //设置集群名
        ->setEnvPath($envFullPath)      //设置env文件存储的完整路径
        ->setsecret('e1146b2777734fc3941dcad04dad7858')
        ->startLongPull(2);
}

pullToEnv();