<?php


namespace App\Controller;


use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;
use App\Kernel\Validator\Validator;
use App\Util\JwtUtil;
use Psr\Http\Message\ResponseInterface;

/**
 * Class JwtController
 * @package App\Controller
 * @Controller()
 */
class JwtController extends AbstractController
{
    /**
     * 刷新Token
     * @PostMapping("/jwt/{scope}/refreshToken")
     * @param string $scope
     * @return ResponseInterface
     */
    public function refreshToken(string $scope)
    {
        $body = $this->request->all();
        $body['scope'] = $scope;
        Validator::make($body, [
            'scope' => 'required|string',
            'id' => 'required|int',
            'key' => 'required|md5'
        ]);
        $data['tokenInfo'] = JwtUtil::refreshToken($body['scope'], $body['id'], $body['key']);
        return $this->success($data);
    }
}