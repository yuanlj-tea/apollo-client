<?php

namespace ApolloClient;

use ApolloClient\Exceptions\FileNotExistsException;
use ApolloClient\Exceptions\InvalidArgumentException;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class ApolloClient
{
    /**
     * env文件保存路径
     * @var string
     */
    private $envSavedDir;

    /**
     * env模板文件&拉取配置数据所在目录
     * @var string
     */
    private $envTemplateDir;

    /**
     * env模板文件路径
     * @var
     */
    private $envTemplatePath;

    /**
     * env文件路径
     * @var
     */
    private $envPath;

    /**
     * apollo服务端地址
     * @var
     */
    protected $baseUrl;

    /**
     * apollo配置项目的appid
     * @var
     */
    protected $appId;

    /**
     * apollo默认集群名
     * @var string
     */
    protected $cluster = 'default';

    /**
     * 绑定IP做灰度发布用
     * @var string
     */
    protected $clientIp = '127.0.0.1';

    /**
     * 要拉取的apollo namespace
     * @var
     */
    protected $namespaceList;

    private $notifications = [];

    /**
     * 获取某个namespace配置的请求超时时间
     * @var int
     */
    private $pullTimeOut = 60;

    /**
     * 每次请求获取apollo配置变更时的超时时间(数据无变化会hold住请求60秒)
     * @var int
     */
    private $intervalTimeout = 70;

    private $notificationsPath = '/notifications/v2';

    private $configPath = '/configs';

    /**
     * 生成的env文件默认文件名
     * @var string
     */
    private $envFileName = 'env';

    /**
     * env模板文件默认文件名
     * @var string
     */
    private $envTplFileName = '.env_tpl.php';

    const DS = DIRECTORY_SEPARATOR;

    public function __construct($baseUrl, $appId, $namespaceList)
    {
        ini_set('date.timezone', 'Asia/Shanghai');

        if (empty($baseUrl)) {
            throw new InvalidArgumentException('无效的base url');
        }

        $this->baseUrl = $baseUrl;
        $this->appId = $appId;
        $this->namespaceList = $namespaceList;

        $this->envSavedDir = __DIR__;
        $this->envTemplateDir = __DIR__ . '/../env';

        $this->setEnvTplPath();
        $this->setEnvPath();

        $this->initNotifications();
    }

    private function initNotifications()
    {
        foreach ($this->namespaceList as $k => $namespace) {
            $this->notifications[$namespace] = [
                'namespaceName' => $namespace,
                'notificationId' => -1
            ];
        }
    }

    /**
     * 设置env文件名
     * @param string $envFileName
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setEnvFileName($envFileName = '')
    {
        if (empty($envFileName)) {
            throw new InvalidArgumentException('无效的env文件名');
        }
        $this->envFileName = $envFileName;
        return $this;
    }

    /**
     * 设置env模板文件名
     * @param string $tplFileName
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setTplFileName($tplFileName = '')
    {
        if (empty($tplFileName)) {
            throw new InvalidArgumentException('无效的模板文件名');
        }
        $this->envTplFileName = $tplFileName;
        return $this;
    }

    /**
     * 设置env tpl文件路径
     * @param string $fullPath
     * @return $this
     * @throws FileNotExistsException
     */
    public function setEnvTplPath($fullPath = '')
    {
        if (!empty($fullPath)) {
            if (!file_exists($fullPath)) {
                throw  new FileNotExistsException(sprintf("模版文件[%s]不存在", $fullPath));
            }
            $this->envTemplatePath = $fullPath;
        } else {
            $this->envTemplatePath = sprintf("%s%s%s", $this->envTemplateDir, self::DS, $this->envTplFileName);
        }
        return $this;
    }

    /**
     * 设置env保存的完整路径
     * @param $fullPath string env存储文件的完整路径(含env文件名)
     * @return $this
     */
    public function setEnvPath($fullPath = '')
    {
        if (!empty($fullPath)) {
            $this->envPath = $fullPath;
        } else {
            $this->envPath = sprintf("%s%s.%s", $this->envSavedDir, self::DS, $this->envFileName);
        }
        return $this;
    }

    /**
     * 设置env文件保存的文件夹路径
     * @param $envSavedDir
     * @return $this
     */
    public function setEnvSavedDir($envSavedDir)
    {
        $this->envSavedDir = $envSavedDir;
        $this->setEnvPath();
        return $this;
    }

    /**
     * 设置env模板文件&拉取数据保存路径
     * @param $envTemplateDir
     * @return $this
     * @throws FileNotExistsException
     */
    public function setEnvTemplateDir($envTemplateDir)
    {
        $this->envTemplateDir = $envTemplateDir;
        $this->setEnvTplPath();
        return $this;
    }

    public function setCluster($cluster)
    {
        $this->cluster = $cluster;
        return $this;
    }

    public function setClientIp($clientIp)
    {
        $this->clientIp = $clientIp;
        return $this;
    }

    public function setPullTimeout($pullTimeout)
    {
        $pullTimeout = intval($pullTimeout);
        if ($pullTimeout < 1 || $pullTimeout > 300) {
            throw new InvalidArgumentException('拉取超时时间需在1~300之间');
        }
        $this->pullTimeOut = $pullTimeout;
        return $this;
    }

    public function setIntervalTimeout($intervalTimeout)
    {
        $intervalTimeout = intval($intervalTimeout);
        if ($intervalTimeout < 1 || $intervalTimeout > 300) {
            return $this;
        }
        $this->intervalTimeout = $intervalTimeout;
        return $this;
    }

    private function checkTplFileExists()
    {
        if (!file_exists($this->envTemplatePath)) {
            throw new FileNotExistsException('模板文件' . $this->envTemplatePath . '不存在');
        }
    }

    private function getReleaseKey($config_file)
    {
        $releaseKey = '';
        if (file_exists($config_file)) {
            $last_config = require $config_file;
            is_array($last_config) && isset($last_config['releaseKey']) && $releaseKey = $last_config['releaseKey'];
        }
        return $releaseKey;
    }

    /**
     * 获取单个namespace的配置文件路径
     * @param $namespaceName
     * @return string
     */
    private function getConfigFile($namespaceName)
    {
        return $this->envTemplateDir . self::DS . 'apolloConfig.' . $namespaceName . '.php';
    }

    private function buildNotificationUrl()
    {
        $param = [];

        $param['appId'] = $this->appId;
        $param['cluster'] = $this->cluster;
        $param['notifications'] = json_encode(array_values($this->notifications));

        $url = sprintf("%s/%s?%s", trim($this->baseUrl, '/'), trim($this->notificationsPath, '/'), http_build_query($param));
        return $url;
    }

    public function buildHeader($url)
    {
        $microtime = intval(microtime(true) * 1000);
        $pathWithQuery = $this->url2PathWithQuery($url);
        $stringToSign = strval($microtime) . "\n" . $pathWithQuery;
        $sign = hash_hmac('sha1', $stringToSign, 'e1146b2777734fc3941dcad04dad7858');

        $header = [
            'headers' => [
                'Authorization' => sprintf("Apollo %s:%s", $this->appId, $sign),
                'Timestamp' => $microtime,
            ],
            // 'proxy' => 'http://127.0.0.1:8888',
        ];

        return $header;
    }

    public function url2PathWithQuery($url)
    {
        $data = parse_url($url);
        $pathWithQuery = $data['path'];
        if (!empty($data['query'])) {
            $pathWithQuery .= '?' . $data['query'];
        }

        return $pathWithQuery;
    }

    private function longPull($callback = null)
    {
        while (true) {
            try {
                $url = $this->buildNotificationUrl();

                //发起请求,配置若无变更,服务端会hold住请求60秒
                $res = (new Client(['timeout' => $this->intervalTimeout]))->request('GET', $url, $this->buildHeader($url));
                var_dump($res);
                die;
                $resStatusCode = $res->getStatusCode();
                $resContents = $res->getBody()->getContents();

                if ($resStatusCode == 200) {
                    $data = json_decode($resContents, true);

                    $changeList = [];
                    foreach ($data as $k => $v) {
                        if ($v['notificationId'] != $this->notifications[$v['namespaceName']]['notificationId']) {
                            $changeList[$v['namespaceName']] = $v['notificationId'];
                        }
                    }

                    list($responseList, $responseData) = $this->pullConfigBatch(array_keys($changeList));
                    foreach ($responseList as $namespaceName => $result) {
                        //$result && ($this->notifications[$namespaceName]['notificationId'] = $changeList[$namespaceName]);
                        ($this->notifications[$namespaceName]['notificationId'] = $changeList[$namespaceName]);
                    }
                    $callback instanceof \Closure && call_user_func_array($callback, [$responseData]);
                } elseif ($resStatusCode == 304) {
                    $this->info(__LINE__, '所有namespace均无变更');
                }
            } catch (\Exception $e) {
                $this->info(__LINE__, sprintf("[FILE--1] %s || [LINE] %s || [MSG] %s", $e->getFile(), $e->getLine(), $e->getMessage()));
                throw $e;
            }
        }
    }

    /**
     * 批量拉取配置
     * @param $namespaceNames
     * @return array
     */
    private function pullConfigBatch($namespaceNames)
    {
        if (!$namespaceNames) {
            return [];
        }
        $p = [];
        foreach ($namespaceNames as $k => $v) {
            $configFile = $this->getConfigFile($v);
            $param['releaseKey'] = $this->getReleaseKey($configFile);
            $url = sprintf(
                "%s/%s/%s/%s/%s?%s",
                trim($this->baseUrl, '/'),
                trim($this->configPath, '/'),
                $this->appId,
                $this->cluster,
                $v,
                http_build_query($param)
            );

            $p[$k]['method'] = 'get';
            $p[$k]['uri'] = $url;
        }
        $res = $this->parallelRequest($p, ['timeout' => $this->pullTimeOut, 'Authorization' => 'e1146b2777734fc3941dcad04dad7858']);

        $responseList = [];
        $responseData = [];
        foreach ($res as $rk => $rv) {
            $resCode = $rv->getStatusCode();
            $resContents = $rv->getBody()->getContents();

            $configFile = $this->getConfigFile($namespaceNames[$rk]);
            $responseList[$namespaceNames[$rk]] = true;
            if ($resCode == 200) {
                $resContents = json_decode($resContents, true);
                $responseData[$rk] = $resContents;

                $content = sprintf("<?php return %s;", var_export($resContents, true));
                file_put_contents($configFile, $content);
            } elseif ($resCode == 304) {
                $this->info(__LINE__, $namespaceNames[$rk] . ":无变更");
                $responseList[$namespaceNames[$rk]] = false;
            } else {
                $responseList[$namespaceNames[$rk]] = false;
            }
        }
        return [$responseList, $responseData];
    }

    private function parallelRequest($data, $options = [])
    {
        $promise = [];
        $client = new Client($options);
        foreach ($data as $k => $v) {
            $promise[] = $client->requestAsync(strtoupper($v['method']), $v['uri'], $v['data'] ?? []);
        }
        $results = Promise\unwrap($promise);
        return $results;
    }

    private function info($line, $data)
    {
        $data = sprintf("[%s] [line:%s] : %s\n", date('c'), $line, print_r($data, true));
        print_r($data);
    }

    /**
     * 删除临时namespace文件
     */
    private function clearNamespaceFile()
    {
        $fileList = glob(sprintf("%s%sapolloConfig.*", $this->envTemplateDir, self::DS));
        foreach ($fileList as $k => $v) {
            $v = realpath($v);
            file_exists($v) && unlink($v);
        }
    }

    /**
     * 拉取配置到env
     * @param $type
     * @param \Closure|null $callback_param
     * @throws FileNotExistsException
     */
    public function startLongPull($type = 1, \Closure $callback_param = null)
    {
        $this->clearNamespaceFile();
        $this->checkTplFileExists();

        switch ($type) {
            case 1:
                //依赖模板文件
                $callback = function ($responseData) {
                    if (!empty($responseData)) {
                        $apollo = [];

                        //读取配置文件里的数据,写入env
                        $fileList = glob(sprintf("%s%sapolloConfig.*", $this->envTemplateDir, DIRECTORY_SEPARATOR));
                        foreach ($fileList as $k => $v) {
                            $config = require($v);
                            if (is_array($config) && isset($config['configurations'])) {
                                $apollo = array_merge($apollo, $config['configurations']);
                            }
                        }

                        ob_start();
                        @include $this->envTemplatePath;
                        $env_config = ob_get_contents();
                        ob_end_clean();
                        file_put_contents($this->envPath, $env_config);
                    }
                };
                break;
            case 2:
                //不依赖模板文件
                $callback = function ($responseData) {
                    if (!empty($responseData)) {
                        $apollo = [];

                        //读取配置文件里的数据,写入env
                        $fileList = glob(sprintf("%s%sapolloConfig.*", $this->envTemplateDir, DIRECTORY_SEPARATOR));
                        foreach ($fileList as $k => $v) {
                            $config = require($v);
                            if (is_array($config) && isset($config['configurations'])) {
                                $apollo = array_merge($apollo, $config['configurations']);
                            }
                        }

                        $data = '';
                        foreach ($apollo as $k => $v) {
                            $data .= sprintf("%s=%s\r\n", $k, $v);
                        }

                        file_put_contents($this->envPath, $data);
                    }
                };
                break;
            default:
                $callback = $callback_param;
                break;
        }

        $this->longPull($callback);
    }
}