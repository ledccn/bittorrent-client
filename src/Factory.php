<?php

namespace Iyuu\BittorrentClient;

use Iyuu\BittorrentClient\Clients\Clients;
use Iyuu\BittorrentClient\Clients\Config;
use Iyuu\BittorrentClient\Clients\qBittorrent\Client as qBittorrent;
use Iyuu\BittorrentClient\Clients\transmission\Client as transmission;
use Iyuu\BittorrentClient\Exception\NotFoundException;

/**
 * 下载器工厂类
 */
class Factory
{
    /**
     * 默认类名
     */
    final const DEFAULT_CLASSNAME = 'Client';
    /**
     * 服务提供者
     * @var string[]
     */
    private static array $provider = [
        'qBittorrent' => qBittorrent::class,
        'transmission' => transmission::class,
    ];

    /**
     * @param Config $config
     * @return Clients
     * @throws NotFoundException
     */
    public static function create(Config $config): Clients
    {
        $type = $config->get('type', '');
        $provider = self::getProvider($type);
        if (!$provider) {
            $provider = Clients::getNamespace() . "\\{$type}\\" . self::DEFAULT_CLASSNAME;
        }
        self::checkProvider($provider);

        return new $provider($config);
    }

    /**
     * 获取服务提供者
     * @param string $site 站点标识
     * @return string|null
     */
    final public static function getProvider(string $site): ?string
    {
        return self::$provider[$site] ?? null;
    }

    /**
     * 注册服务提供者
     * @param string $site 站点标识
     * @param string $provider 服务提供者的完整类名
     * @throws NotFoundException
     */
    final public static function setProvider(string $site, string $provider): void
    {
        Factory::checkProvider($provider);
        Factory::$provider[$site] = $provider;
    }

    /**
     * 验证服务提供者类
     * @param string $provider 服务提供者的完整类名
     * @return void
     * @throws NotFoundException
     */
    final public static function checkProvider(string $provider): void
    {
        if (!class_exists($provider)) {
            throw new NotFoundException('服务提供者类不存在:' . $provider);
        }
        if (!is_a($provider, Clients::class, true)) {
            throw new NotFoundException($provider . '未继承：' . Clients::class);
        }
    }

    /**
     * 所有服务提供者
     * @return string[]
     */
    final public static function allProvider(): array
    {
        return self::$provider;
    }
}
