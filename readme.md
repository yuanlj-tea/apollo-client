#### 																			 apollo-client

------

[协程apollo](https://github.com/ctripcorp/apollo) php client for laravel

#### 1、安装

```php
composer require yuanlj-tea/apollo-client
```

#### 2、使用

```php
require_once __DIR__ . '/../vendor/autoload.php';
use ApolloClient\ApolloClient;

$base_url = 'http://xxx';
$appid = 'xxx';
$namespace = ['xxx'];

$cluster = 'default';
$envDir = __DIR__ . '/../env';
$saveDir = __DIR__ . '/../src';

$client = new ApolloClient($base_url, $appid, $namespace);
//方式1：依赖模板文件
$client->setCluster($cluster)    		//设置集群名
  ->setEnvFileName('env-2')         //设置env文件名
  // ->setTplFileName('tpl.php')  	//设置env模板文件名
  ->setEnvTemplateDir($envDir)      //设置env模板文件所在路径
  ->setEnvSavedDir($saveDir);       //设置env文件的存储路径
$client->startLongPull(1);

//方式2：不依赖模板文件，把apollo的配置文件放入env
$envFullPath = __DIR__ . '/../src/.env-3';
$client->setCluster($cluster)       //设置集群名
	->setEnvPath($envFullPath);       //设置env文件存储的完整路径
$client->startLongPull(2);

//方式3：自行传入闭包自定义env生成方式
$callBack = function($data){
  // do sth
};
$client->startLongPull(3,$callBack);
```

#### 3、方法说明

```php
setEnvFileName($envFileName)//设置要生成的env模板文件名
  
setEnvSavedDir($envSavedDir)//设置env要保存到的文件夹
  
setEnvPath($fullPath)//设置env文件要保存的全路径

setTplFileName($tplFileName)//设置env模板文件名
  
setEnvTemplateDir($envTemplateDir)//设置env模板文件
  
setEnvTplPath($fullPath)//设置要依赖的env模板文件的全路径
    
setCluster($cluster)//设置集群名
    
setClientIp()//设置client ip
    
startLongPull($type = 1, \Closure $callback_param = null)//开始从apollo拉取配置
```

------

#### 4、在laravel中使用

##### 4.1、创建配置文件

```php
php artisan vendor:publish --tag=apollo --force(强制替换已有的配置文件)

会在config_path()下生成apollo.php
```

##### 4.2、在 `config/app.php`注册 ServiceProvider 和 Facade

```json
'providers' => [
    // ...
    ApolloClient\ApolloClientServiceProvider::class,
],
'aliases' => [
    // ...
    'Apollo' => ApolloClient\ApolloFacade::class,
],
```

##### 4.3、三种方式获取服务实例

方法参数注入

```php
public function handle(ApolloClient $apollo)
{
		$apollo->doSth();
}
```

通过服务名获取

```php
public function handle()
{
    app('apollo_client')->setEnvFileName('env.prod')
        ->setEnvSavedDir($saveDir)
        ->startLongPull(2);
}
```

通过门面类获取

```php
use Apollo;

public function handle()
{
    $envTplDir = app_path().'/env';
    $saveDir = realpath(app_path() . '/../');

  	Apollo::setEnvFileName('env.prod')
    		->setEnvSavedDir($saveDir)
    		->startLongPull(2);
}
```

