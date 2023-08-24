<?php

namespace Iyuu\BittorrentClient\Clients;

use Iyuu\BittorrentClient\Contracts\ClientsInterface;
use Ledc\Curl\Curl;

/**
 * 客户端抽象类
 */
abstract class Clients implements ClientsInterface
{
    /**
     * 配置
     * @var Config
     */
    private Config $config;
    /**
     * @var Curl
     */
    protected Curl $curl;

    /**
     * 构造函数
     * @param Config $config
     */
    final public function __construct(Config $config)
    {
        $this->config = $config;
        $this->initCurl();
        $this->initialize();
    }

    /**
     * 子类初始化
     * @return void
     */
    protected function initialize(): void
    {
    }

    /**
     * 初始化Curl
     * @return void
     */
    final protected function initCurl(): void
    {
        $this->curl = new Curl();
        $this->curl->setCommon(60, 600);
        $this->curl->setSslVerify(false, false);
        $this->curl->setUserAgent(Curl::USER_AGENT);
    }

    /**
     * 获取配置
     * @return Config
     */
    final public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * 设置配置
     * @param Config $config
     * @return Clients
     */
    final public function setConfig(Config $config): static
    {
        $this->config = $config;
        return $this;
    }

    /**
     * 获得当前命名空间
     * @return string
     */
    final public static function getNamespace(): string
    {
        return __NAMESPACE__;
    }

    /**
     * 获取当前文件路径
     * @return string
     */
    final public static function getFilepath(): string
    {
        return __FILE__;
    }

    /**
     * 获取当前目录名
     * @return string
     */
    final public static function getDirname(): string
    {
        return __DIR__;
    }
}
