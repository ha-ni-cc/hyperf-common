<?php


namespace App\Controller;


use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use App\Kernel\Validator\Validator;
use App\Util\IPUtil;
use Hyperf\HttpServer\Contract\ResponseInterface;

/**
 * Class ToolController
 * @package App\Controller
 * @Controller()
 */
class ToolController extends AbstractController
{
    /**
     * @GetMapping("/tool/ip")
     */
    public function getIP()
    {
        $body = $this->request->query();
        Validator::make($body, [
            'query' => 'ip'
        ]);
        $ip = empty($body['query']) ? IPUtil::getClientIP() : $body['query'];
        $data['ip'] = $ip;
        $data['location'] = IPUtil::getLocationAsStr($ip);
        return $this->response->json($data);
    }

    /**
     * @GetMapping("/tool/timestamp")
     */
    public function getTimestamp()
    {
        if ($this->request->query('type') == 'ms') {
            return intval(microtime(true) * 1000);
        }
        return time();
    }

    /**
     * @GetMapping("/tool/image_ping")
     */
    public function ping()
    {
        /** @var ResponseInterface $response */
        $response = di(ResponseInterface::class);
        $stream = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAIAAAACAQMAAABIeJ9nAAAAA1BMVEUODg7U3xBZAAAACklEQVQI12MAAgAABAABINItbwAAAABJRU5ErkJggg==');
        return $response->withHeader('Content-type', 'image/x-icon')->withBody(new SwooleStream($stream));
   }
}