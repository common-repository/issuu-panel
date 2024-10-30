<?php

/**
*   Classe IssuuServiceAPI
*
*   @author Pedro Marcelo de Sá Alves
*   @link https://github.com/pedromarcelojava/
*   @version 1.2
*/
abstract class IssuuServiceAPI
{

    /**
    *   Token Bearer da API
    *
    *   @access private
    *   @var string
    */
    private $api_bearer_token;

    /**
    *   URL da API do Issuu
    *
    *   @access private
    *   @var string
    */
    private $api_url = 'https://api.issuu.com/v2';

    /**
    *   URL de upload do Issuu
    *
    *   @access private
    *   @var string
    */
    private $upload_url = 'https://api.issuu.com/v2/drafts/{slug}/upload';

    /**
    *   Parâmetros da requisição em forma de array
    *
    *   @access protected
    *   @var array
    */
    protected $params;

    /**
     * Header da requisição
     * @var array
     */
    protected $headers = array();

    /**
    *   Parâmetros da requisição em forma de string
    *
    *   @access protected
    *   @var string
    */
    protected $params_str;

    /**
    *   Nome do método list
    *
    *   @access protected
    *   @var string
    */
    protected $list;

    /**
    *   Nome do método delete
    *
    *   @access protected
    *   @var string
    */
    protected $delete;

    /**
    *   Slug da seção
    *
    *   @access protected
    *   @var string
    */
    protected $slug_section;

    /**
    *   IssuuServiceAPI::__construct()
    *
    *   Construtor da classe
    *
    *   @access public
    *   @param string $api_bearer_token Correspondente ao token Bearer da API
    *   @throws Exception Lança uma exceção caso não seja informada o token Bearer da API
    */
    public function __construct($api_bearer_token)
    {
        if (is_string($api_bearer_token) && strlen($api_bearer_token) >= 1)
        {
            $this->api_bearer_token = $api_bearer_token;
        }
        else
        {
            throw new Exception('O token Bearer da API não foi informado');
        }
    }

    /**
    *   IssuuServiceAPI::__destruct()
    *
    *   Desconstrutor da classe
    *
    *   @access public
    */
    public function __destruct()
    {
        return false;
    }

    /**
    *   IssuuServiceAPI::buildUrl()
    *
    *   Monta a URL da requisição
    *
    *   @access protected
    *   @param boolean $regular_request
    *   @param string $slug
    *   @return string Retorna a URL da api ou upload junto com os parâmetros passados
    */
    protected function buildUrl($regular_request = true, $slug = null)
    {
        if ($regular_request == true)
        {
            return $this->api_url . '?' . $this->params_str;
        }
        else if ($regular_request == false)
        {
            // override upload_url {slug} with $slug
            return str_replace('{slug}', $slug, $this->upload_url) . '?' . $this->params_str;
        }
        else
        {
            return false;
        }
    }

    /**
    *   IssuuServiceAPI::setParams()
    *
    *   Seta os parâmetros da requisição
    *
    *   @access public
    *   @param array $params
    *   @throws Exception Lança um exceção caso não tenha parâmetros
    */
    public function setParams($params, $content_type = 'application/json')
    {
        if (is_array($params) && !empty($params))
        {
            $this->params = $params;
            $this->headers = array(
                'Content-Type: ' . $content_type,
                'Authorization: Bearer ' . $this->api_bearer_token
            );
        }
        else
        {
            throw new Exception('Os parâmetros não é um array ou está vazio');
        }
    }

    /**
    *   IssuuServiceAPI::getParams()
    *
    *   Retorna os parâmetros da requisição
    *
    *   @access public
    *   @return array
    */
    public function getParams()
    {
        return $this->params;
    }

    /**
    *   IssuuServiceAPI::curlRequest()
    *
    *   @access public
    *   @param string $url URL that will be sent in the request
    *   @param string|array $data Data that will be sent
    *   @param array $headers Additional request headers
    *   @param string $requestType Request type (GET or POST)
    *   @param array $additionalOptions Additional cURL options
    *   @return mixed Response of the request
    */
    public function curlRequest(
        $url,
        array $data,
        array $headers = array(),
        $requestType = 'GET',
        array $additionalOptions = array()
    ) {
        switch ($requestType) {
            case 'GET':
            case 'DELETE':
                $shouldQueryParameters = true;
                break;
            case 'PATCH_FILE':
                $shouldQueryParameters = false;
                $requestType = 'PATCH';
                break;
            default:
                $data = json_encode($data);
                break;
        }

        if ($shouldQueryParameters && !empty($data)) {
            $data = urldecode(http_build_query($data));
            $url = $url . '?' . $data;
        }
        
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $requestType,
            CURLOPT_POSTFIELDS => ($shouldQueryParameters && !empty($data)) ? null : $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        );
        
        foreach ($additionalOptions as $key => $value) {
            $options[$key] = $value;
        }
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    /**
    *   IssuuServiceAPI::validFieldJson()
    *
    *   Valida uma variável
    *
    *   @access public
    *   @param object $object
    *   @param string $field Nome da variável a ser validada
    *   @param int $type Corresponde ao tipo que a variável será convertida
    *   @return string Retorna a variável validada ou uma string vazia caso ela não exista
    */
    public function validFieldJson($object, $field, $type = 0)
    {
        if (isset($object->$field))
        {
            if ($type == 0)
            {
                return (string) $object->$field;
            }
            else if ($type == 1)
            {
                return intval($object->$field);
            }
            else if ($type == 2)
            {
                return (is_bool($object->$field))? $object->$field : (($object->$field == 'true')? true : false);
            }
            else if ($type == 3)
            {
                return floatval($object->$field);
            }
            else
            {
                return $object->$field;
            }
        }
        else
        {
            return '';
        }
    }

    /**
    *   IssuuServiceAPI::validFieldXML()
    *
    *   Valida uma variável
    *
    *   @access public
    *   @param array $object
    *   @param string $field Nome da variável a ser validada
    *   @param int $type Corresponde ao tipo que a variável será convertida
    *   @return string Retorna a variável validada ou uma string vazia caso ela não exista
    */
    public function validFieldXML($object, $field, $type = 0)
    {
        if (isset($object[$field]))
        {
            if ($type == 0)
            {
                return (string) $object[$field];
            }
            else if ($type == 1)
            {
                return intval($object[$field]);
            }
            else if ($type == 2)
            {
                return (is_bool($object[$field]))? $object[$field] : (($object[$field] == 'true')? true : false);
            }
            else if ($type == 3)
            {
                return floatval($object[$field]);
            }
            else
            {
                return $object[$field];
            }
        }
        else
        {
            return '';
        }
    }

    /**
    *   IssuuServiceAPI::returnErrorJson()
    *
    *   Lista registros da requisição
    *
    *   @access protected
    *   @param object $response Correspondente ao objeto de resposta da requisição
    *   @return array Array contendo o conteúdo do erro
    */
    protected function returnErrorJson($response)
    {
        return array(
            'stat' => 'fail',
            'code' => (string) $response->_content->error->code,
            'message' => (string) $response->_content->error->message,
            'field' => (string) $response->_content->error->field
        );
    }

    /**
    *   IssuuServiceAPI::returnErrorXML()
    *
    *   Lista registros da requisição
    *
    *   @access protected
    *   @param object $response Correspondente ao objeto de resposta da requisição
    *   @return array Array contendo o conteúdo do erro
    */
    protected function returnErrorXML($response)
    {
        return array(
            'stat' => 'fail',
            'code' => (string) $response->error['code'],
            'message' => (string) $response->error['message'],
            'field' => (string) $response->error['field']
        );
    }

    /**
    *   IssuuServiceAPI::returnSingleResult()
    *
    *   Faz a requisição de um único documento.
    *
    *   @access protected
    *   @param array $params Correspondente aos parâmetros da requisição
    *   @return array Retorna um array com a resposta da requisição
    */
    protected function returnSingleResult($params) {}

    /**
    *   IssuuServiceAPI::delete()
    *
    *   Exclui os registros da requisição
    *
    *   @access public
    *   @param array $params Correspondente aos parâmetros da requisição
    */
    public function delete($params = array()){}

    /**
    *   IssuuServiceAPI::issuuList()
    *
    *   Lista registros da requisição
    *
    *   @access public
    *   @param array $params Correspondente aos parâmetros da requisição
    */
    final public function issuuList($params = array())
    {
        $this->setParams($params);

        $response = $this->curlRequest(
            $this->getApiUrl('/publications'),
            $this->params,
            $this->headers,
        );

        $slug = $this->slug_section;

        $response = json_decode($response, true);

        if ($response['results'])
        {
            $result = array();
            $result['stat'] = 'ok';
            $result['totalCount'] = isset($response['count']) ? (int) $response['count'] : 0;
            $result['page'] = isset($params['page']) ? (int) $params['page'] : 0;
            $result['size'] = isset($response['pageSize']) ? (int) $response['pageSize'] : 0;
            $result['more'] = !!$response['links']['next'] ? true : false;

            if (!empty($response['results']))
            {
                foreach ($response['results'] as $item) {
                    $result[$slug][] = $this->clearObjectJson($item);
                }
            }
            return $result;
        }
        else
        {
            return $this->returnErrorJson($response);
        }
    }

    /**
    *   IssuuServiceAPI::getApiUrl()
    *
    *   @access public
    *   @param string $endpoint
    *   @return string URL da API de dados do Issuu
    */
    public function getApiUrl($endpoint = '')
    {
        return $this->api_url . $endpoint;
    }


    /**
    *   IssuuServiceAPI::getUploadUrl()
    *
    *   @access public
    *   @param string $slug Slug do documento
    *   @return string URL da API para upload do Issuu
    */
    public function getUploadUrl($slug)
    {
        return $this->buildUrl(false, $slug);
    }


    /**
    *   IssuuDocument::update()
    *
    *   Relacionado ao método issuu.document.update da API.
    *   Atualiza os dados de um determinado documento.
    *
    *   @access public
    *   @param array $params Correspondente aos parâmetros da requisição
    *   @return array Retorna um array com a resposta da requisição
    */
    public function update($params) {}

    /**
    *   IssuuServiceAPI::clearObjectXML()
    *
    *   Valida os atributos de um objeto XML
    *
    *   @access protected
    *   @param object $object Correspondente ao objeto XML a ser validado
    */
    abstract protected function clearObjectXML($object);

    /**
    *   IssuuServiceAPI::clearObjectJson()
    *
    *   Valida os atributos de um objeto Json
    *
    *   @access protected
    *   @param object $object Correspondente ao objeto Json a ser validado
    */
    abstract protected function clearObjectJson($object);
}