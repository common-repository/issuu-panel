<?php

if (!class_exists('IssuuServiceAPI'))
{
    require(dirname(__FILE__) . '/class.issuuserviceapi.php');
}

/**
*   Classe IssuuFolder
*
*   @author Pedro Marcelo de Sá Alves
*   @link https://github.com/pedromarcelojava/
*   @version 1.2
*/
class IssuuFolder extends IssuuServiceAPI
{

    /**
    *   Método de listagem da seção Folder
    *
    *   @access protected
    *   @var string
    */
    protected $list = 'issuu.folders.list';

    /**
    *   Método de exclusão da seção Folder
    *
    *   @access protected
    *   @var string
    */
    protected $delete = 'issuu.folder.delete';

    /**
    *   Slug da seção
    *
    *   @access protected
    *   @var string
    */
    protected $slug_section = 'folder';

    /**
    *   IssuuFolder::add()
    *
    *   Relacionado ao método issuu.folder.add da API.
    *   Cria uma pasta vazia na conta. Documentos e marcadores podem
    *   ser adicionados as pastas.
    *
    *   @access public
    *   @param array $params Correspondente aos parâmetros da requisição
    *   @return array Retorna um array com a resposta da requisição
    */
    public function add($params)
    {
        $this->setParams($params);
        $response = $this->curlRequest(
            $this->getApiUrl('/stacks'),
            $params,
            $this->headers,
            'POST'
        );

        if (isset($response))
        {
            $result['stat'] = 'ok';
            $result['stackId'] = $response;
            return $result;
        }
        else
        {
            return $this->returnErrorJson($response);
        }
    }

    protected function returnSingleResult($params)
    {
        $stackId = $params['folderId'];
        $this->setParams($params);
        
        $response_stack = $this->curlRequest(
            $this->getApiUrl('/stacks/'.$stackId),
            array(),
            $this->headers
        );
        $response_stack = json_decode($response_stack, true);
        
        if(isset($response_stack['id']))
        {
            $result['stat'] = 'ok';
            $result['stack'] = $this->clearObjectJson($response_stack);
            $has_next = true;
            $page = 1;

            while($has_next) {
                // get stack items
                $response_stack_items = $this->curlRequest(
                    $this->getApiUrl('/stacks/'.$stackId.'/items'),
                    array(
                        'includeUnlisted' => 'true',
                        'page' => $page,
                    ),
                    $this->headers
                );
                $response_stack_items = json_decode($response_stack_items, true);
                $cleared_object = $this->clearObjectJson($response_stack_items);

                if(isset($cleared_object->links['next']))
                {
                    $page++;
                }
                else
                {
                    $has_next = false;
                }


                foreach($cleared_object->results as $item_id)
                {
                    $stack_items[] = $item_id;
                }
            }            
            
            // get data from each document
            foreach($stack_items as $item)
            {
                $result_document = $this->curlRequest(
                    $this->getApiUrl('/publications/'.$item),
                    array(),
                    $this->headers
                );

                $document = json_decode($result_document, true);
                $document = $this->clearObjectJson($document);
                $documents[] = $document;
            }
            
            $result['documents'] = $documents;

            return $result;
        }
        else
        {
            return $this->returnErrorJson($response_stack);
        }
    }

    public function getUpdateData($params = array())
    {
        return $this->returnSingleResult($params);
    }

    /**
    *   IssuuBookmark::stackList()
    *
    *   Lista stacks
    *
    *   @access public
    *   @param array $params Correspondente aos parâmetros da requisição
    */
    public function stackList($params = array())
    {
        $this->setParams($params);

        $response = $this->curlRequest(
            $this->getApiUrl('/stacks'),
            $this->params,
            $this->headers,
        );

        $slug = $this->slug_section;
        $response = json_decode($response, true);
        if (isset($response['results']) && !empty($response['results']))
        {
            $result['stat'] = 'ok';
            $result['totalCount'] = isset($response['count']) ? (int) $response['count'] : 0;
            $result['page'] = isset($params['page']) ? (int) $params['page'] : 0;
            $result['size'] = isset($response['pageSize']) ? (int) $response['pageSize'] : 0;
            $result['more'] = isset($response['links']['next']) ? true : false;

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
     *  IssuuFolder::delete()
     * 
     * Deleta uma ou mais stacks.
     */
    public function delete($params = array())
    {
        $this->setParams($params);
        foreach ($params['stackIds'] as $slug) {
            $response = $this->curlRequest(
                $this->getApiUrl('/stacks/'.$slug),
                array(),
                $this->headers,
                'DELETE'
            );
        }

        return array('stat' => 'ok');
    }

    /**
    *   IssuuFolder::update()
    *
    *   Relacionado ao método issuu.folder.update da API.
    *   Atualiza os dados de uma determinada pasta.
    *
    *   @access public
    *   @param array $params Correspondente aos parâmetros da requisição
    *   @return array Retorna um array com a resposta da requisição
    */
    public function update($params)
    {
        $slug = $params['id'];
        unset($params['id']);
        $this->setParams($params);
        $response = $this->curlRequest(
            $this->getApiUrl('/stacks/'.$slug),
            $this->params,
            $this->headers,
            'PATCH'
        );

        if(isset($response))
        {
            $result['stat'] = 'ok';
            $result[$params['slug']] = $this->clearObjectJson($response);

            return $result;
        }
        else
        {
            return $this->returnErrorJson($response);
        }
    }

    /**
    *   IssuuFolder::clearObjectJson()
    *
    *   Valida e formata os atributos do objeto da pasta.
    *
    *   @access protected
    *   @param object $folder Correspondente ao objeto da pasta
    *   @return object Retorna um novo objeto da pasta devidamente validado
    */
    protected function clearObjectJson($folder)
    {
        $fold = (object) $folder;

        if(isset($doc->cover['small'])) {
            $fold->coverImage = $fold->cover['small']['url'];
        }
        if(isset($fold->cover['medium'])) {
            $fold->coverImage = $fold->cover['medium']['url'];
        }
        if(isset($fold->cover['large'])) {
            $fold->coverImage = $fold->cover['large']['url'];
        }

        return $fold;
    }

    /**
    *   IssuuFolder::clearObjectXML()
    *
    *   Valida e formata os atributos do objeto da pasta.
    *
    *   @access protected
    *   @param object $folder Correspondente ao objeto da pasta
    *   @return object Retorna um novo objeto da pasta devidamente validado
    */
    protected function clearObjectXML($folder)
    {
        $fold = new stdClass();

        $fold->folderId = $this->validFieldXML($folder, 'folderId');
        $fold->username = $this->validFieldXML($folder, 'username');
        $fold->name = $this->validFieldXML($folder, 'name');
        $fold->description = $this->validFieldXML($folder, 'description');
        $fold->items = $this->validFieldXML($folder, 'items', 1);
        $fold->itemCount = $this->validFieldXML($folder, 'itemCount', 1);
        $fold->ep = $this->validFieldXML($folder, 'ep', 1);
        $fold->created = $this->validFieldXML($folder, 'created');

        return $fold;
    }
}