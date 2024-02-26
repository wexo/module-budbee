<?php
namespace Wexo\Budbee\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;

class Request
{
    /**
     * @param CurlFactory $curlFactory
     * @param Config $config
     * @param Json $json
     */
    public function __construct(
        private readonly CurlFactory $curlFactory,
        private readonly Config $config,
        private readonly Json $json
    ) {
    }

    /**
     * @param string $url
     * @param string $header
     * @param string $method
     * @param array $postData
     * @return array
     * @throws LocalizedException
     */
    public function makeRequest(string $url, string $header = '', string $method = 'GET', array $postData = []): array
    {
        $curl = $this->curlFactory->create();

        $curl->setOption(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $curl->setOption(CURLOPT_USERPWD, $this->config->getApiKey() . ':' . $this->config->getApiSecret());

        $curl->addHeader('content-type', $header);

        if ($method == 'GET') {
            $curl->get($url);
        } elseif ($method == 'POST') {
            $curl->addHeader('content-length', strlen($this->json->serialize($postData)));
            $curl->post($url, $this->json->serialize($postData));
        } else {
            throw new \InvalidArgumentException('Unsupported HTTP method');
        }

        if ($curl->getStatus() === 202) {
            throw new LocalizedException(__('The processing was not finished'));
        }

        if (!in_array($curl->getStatus(), [200, 204])) {
            throw new LocalizedException(
                __(
                    'The request to Budbee did not return a valid status code: ' .
                    '['.$curl->getStatus().']' .
                    PHP_EOL .
                    $curl->getBody()
                )
            );
        }

        return [
            'status_code' => $curl->getStatus(),
            'body' => $curl->getBody()
        ];
    }
}
