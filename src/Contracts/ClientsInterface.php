<?php

namespace Iyuu\BittorrentClient\Contracts;

/**
 * 客户端接口
 */
interface ClientsInterface
{
    /**
     * 构造函数
     * @param array $config 配置
     */
    public function __construct(array $config = []);

    /**
     * 登陆
     */
    public function login();

    /**
     * 退出登陆
     */
    public function logout();

    /**
     * 添加种子到下载器
     * @return mixed
     */
    public function addTorrent(): mixed;

    /**
     * 添加种子到下载器
     * @return mixed
     */
    public function addTorrentByUrl(): mixed;

    /**
     * 添加种子到下载器
     * @return mixed
     */
    public function addTorrentByMetadata(): mixed;

    /**
     * 获取种子列表
     * @return array
     */
    public function getTorrentList(): array;

    /**
     * 开始做种
     */
    public function start();

    /**
     * 停止做种
     */
    public function stop();

    /**
     * 删除做种
     */
    public function delete();

    /**
     * 获取下载器状态
     */
    public function status();
}