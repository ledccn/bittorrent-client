<?php

namespace Iyuu\BittorrentClient\Clients\transmission;

use Iyuu\BittorrentClient\Clients\Clients;
use Iyuu\BittorrentClient\Exception\UnauthorizedException;
use Iyuu\BittorrentClient\Utils;

/**
 * transmission
 * @link https://github.com/transmission/transmission
 */
class Client extends Clients
{
    /**
     * @var string
     */
    protected string $session_id = '';

    /**
     * 添加种子到下载器
     * @param string $data
     * @param string $savePath
     * @param array $extra
     * @return string|bool|null
     * @throws UnauthorizedException
     */
    public function addTorrent(string $data = '', string $savePath = '', array $extra = []): string|bool|null
    {
        if (Utils::isTorrentUrl($data)) {
            return $this->addTorrentByUrl($data, $savePath, $extra);
        }
        return $this->addTorrentByMetadata($data, $savePath, $extra);
    }

    /**
     * 添加种子到下载器
     * @param string $metadata
     * @param string $savePath
     * @param array $extra
     * @return string|bool|null
     * @throws UnauthorizedException
     */
    public function addTorrentByMetadata(string $metadata = '', string $savePath = '', array $extra = []): string|bool|null
    {
        if (!empty($savePath)) {
            $extra['download-dir'] = $savePath;
        }
        $extra['metainfo'] = base64_encode($metadata);

        return $this->request("torrent-add", $extra);
    }

    /**
     * 添加种子到下载器
     * @param string $filename
     * @param string $savePath
     * @param array $extra
     * @return string|bool|null
     * @throws UnauthorizedException
     */
    public function addTorrentByUrl(string $filename = '', string $savePath = '', array $extra = []): string|bool|null
    {
        if (!empty($savePath)) {
            $extra['download-dir'] = $savePath;
        }
        $extra['filename'] = $filename;
        return $this->request("torrent-add", $extra);
    }

    public function getTorrentList(): array
    {
        // TODO: Implement getTorrentList() method.
    }

    /**
     * @return string
     */
    public function login(): string
    {
        $curl = $this->curl;
        $config = $this->getConfig();
        $curl->setBasicAuthentication($config->username ?? '', $config->password ?? '');
        $curl->get($config->getClientUrl());
        if ($response = $curl->response) {
            if (preg_match("#<code>X-Transmission-Session-Id: (.*?)</code>#i", $response, $matches)) {
                $this->session_id = $matches[1] ?? '';
            }
        } else {
            if ($config->debug) {
                var_dump($curl);
            }
        }
        return $this->session_id;
    }

    /**
     * @return bool
     */
    public function logout(): bool
    {
        return true;
    }

    /**
     * 删除一个或多个种子
     * @param int|array $ids
     * @param bool $delete_local_data 是否删除数据
     * @return bool|string|null
     * @throws UnauthorizedException
     */
    public function delete(int|array $ids = [], bool $delete_local_data = false): bool|string|null
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $request = array(
            'ids' => $ids,
            'delete-local-data' => $delete_local_data
        );
        return $this->request("torrent-remove", $request);
    }

    /**
     * 开始一个或多个种子
     * @param int|array $ids
     * @return bool|string|null
     * @throws UnauthorizedException
     */
    public function start(int|array $ids = []): bool|string|null
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $params = ['ids' => $ids];
        return $this->request('torrent-start', $params);
    }

    /**
     * @return mixed|string
     * @throws UnauthorizedException
     */
    public function status(): mixed
    {
        $response = $this->request('session-stats');
        if ($response) {
            $resp = json_decode($response, true);
            return $resp['result'] ?? 'error';
        }
        return 'null';
    }

    /**
     * 停止一个或多个种子
     * @param int|array $ids
     * @return bool|string|null
     * @throws UnauthorizedException
     */
    public function stop(int|array $ids = []): bool|string|null
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $params = ['ids' => $ids];
        return $this->request('torrent-stop', $params);
    }

    /**
     * 校验一个或多个种子
     * @param int|array $ids
     * @return bool|string|null
     * @throws UnauthorizedException
     */
    public function verify(int|array $ids): bool|string|null
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $params = ['ids' => $ids];
        return $this->request('torrent-verify', $params);
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return false|string|null
     * @throws UnauthorizedException
     */
    protected function request(string $method, array $arguments = []): bool|string|null
    {
        $arguments = $this->cleanRequestData($arguments);
        if (!$this->session_id) {
            if (!$this->login()) {
                throw new UnauthorizedException('无法获得 X-Transmission-Session-Id');
            }
        }

        $curl = $this->curl;
        $config = $this->getConfig();
        $data = [
            'method' => $method,
            'arguments' => $arguments
        ];
        $header = array(
            'Content-Type' => 'application/json',
            'X-Transmission-Session-Id' => $this->session_id
        );
        foreach ($header as $key => $value) {
            $curl->setHeader($key, $value);
        }
        $curl->post($config->getClientUrl(), $data, true);
        if ($curl->isSuccess()) {
            return $curl->response;
        } else {
            if ($config->debug) {
                var_dump($curl);
            }
        }

        return null;
    }

    /**
     * 预处理请求报文
     * @param array $array
     * @return array|null
     */
    protected function cleanRequestData(array $array): ?array
    {
        if (count($array) == 0) {
            return null;
        }
        setlocale(LC_NUMERIC, 'en_US.utf8');    // Override the locale - if the system locale is wrong, then 12.34 will encode as 12,34 which is invalid JSON
        foreach ($array as $index => $value) {
            if (is_array($value)) {
                $array[$index] = $this->cleanRequestData($value);
            }    // Recursion
            if (empty($value) && ($value !== 0 || $value !== false)) {    // Remove empty members
                unset($array[$index]);
                continue; // Skip the rest of the tests - they may re-add the element.
            }
            if (is_numeric($value)) {
                $array[$index] = $value + 0;
            }    // Force type-casting for proper JSON encoding (+0 is a cheap way to maintain int/float/etc)
            if (is_bool($value)) {
                $array[$index] = ($value ? 1 : 0);
            }    // Store boolean values as 0 or 1
            if (is_string($value)) {
                $type = mb_detect_encoding($value, 'auto');
                if ($type !== 'UTF-8') {
                    $array[$index] = mb_convert_encoding($value, 'UTF-8');
                }
            }
        }
        return $array;
    }
}
