<?php

if (!class_exists('IssuuServiceAPI'))
{
    require(dirname(__FILE__) . '/class.issuuserviceapi.php');
}

/**
*   Classe IssuuDocument
*
*   @author Pedro Marcelo de Sá Alves
*   @link https://github.com/pedromarcelojava/
*   @version 1.2
*/
class IssuuDocument extends IssuuServiceAPI
{
    /**
    *   Método de exclusão da seção Document
    *
    *   @access protected
    *   @var string
    */
    protected $delete = 'issuu.document.delete';

    /**
    *   Slug da seção
    *
    *   @access protected
    *   @var string
    */
    protected $slug_section = 'results';

    /**
    *   IssuuDocument::upload()
    *
    *   Relacionado ao método issuu.document.upload da API.
    *   Carrega um arquivo para a conta.
    *
    *   @access public
    *   @param array $params Correspondente aos parâmetros da requisição
    *   @return array Retorna um array com a resposta da requisição
    */
    public function upload($params = array(), $fileUrl = null)
    {
        if ((!isset($_FILES['file']) || empty($_FILES['file'])) && $fileUrl == null)
        {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error');
            header('Content-Type: text/plain');
            die('This form is not multipart/form-data');
        }

        foreach ($params as $key => $value) {
            if (isset($value) && ($value == '' || is_null($value)))
            {
                unset($params[$key]);
            }
        }
        $this->setParams($params);
        $friendlyUrl = str_replace(' ', '-', strtolower($params['desiredName']));
        unset($params['desiredName']);

        // create draft
        $create_params = array('info' => $params);
        if($fileUrl != null) {
            $create_params['fileUrl'] = $fileUrl;
            $create_params['confirmCopyright'] = 'true';
        }

        $create_response = $this->curlRequest(
            $this->getApiUrl('/drafts'),
            $create_params,
            $this->headers,
            'POST'
        );

        $create_response = json_decode($create_response);
        $slug = $create_response->slug;

        // upload file if not already uploaded
        if($fileUrl == null) {
            $upload_params = array(
                'file' => $this->setFile($_FILES['file']),
                'confirmCopyright' => 'true'
            );
            
            $this->setParams($params, 'multipart/form-data');
            $upload_response = $this->curlRequest(
                $this->getUploadUrl($slug),
                $upload_params,
                $this->headers,
                'PATCH_FILE'
            );
            $upload_response = json_decode($upload_response);
    
            // check if already processed
            $this->setParams($params);
            while($uploaded == false)
            {
                $check_response = $this->curlRequest(
                    $this->getApiUrl('/drafts/'.$slug),
                    array(),
                    $this->headers,
                );
    
                $check_response = json_decode($check_response);
    
                if($check_response->fileInfo->conversionStatus == 'DONE')
                {
                    $uploaded = true;
                }
            }
        }

        // publish
        $response = $this->curlRequest(
            $this->getApiUrl('/drafts/'.$slug.'/publish'),
            array("desiredName" => $friendlyUrl),
            $this->headers,
            'POST'
        );
        $response = json_decode($response);
        
        if(isset($response->publicLocation))
        {
            $result['stat'] = 'ok';
            $result[$slug] = $this->clearObjectJson($response);

            return $result;
        }
        else
        {
            return $this->returnErrorJson($response);
        }
    }

    /**
    *   IssuuDocument::urlUpload()
    *
    *   Relacionado ao método issuu.document.url_upload da API.
    *   Carrega um arquivo para a conta através de uma URL informada.
    *
    *   @access public
    *   @param array $params Correspondente aos parâmetros da requisição
    *   @return array Retorna um array com a resposta da requisição
    */
    public function urlUpload($params = array())
    {
        $fileUrl = $params['fileUrl'];
        unset($params['fileUrl']);
        return $this->upload($params, $fileUrl);
    }

    protected function returnSingleResult($params)
    {
        $slug = $params['slug'];
        $this->setParams($params);
        
        $response = $this->curlRequest(
            $this->getApiUrl('/publications/'.$slug),
            array(),
            $this->headers
        );

        $response = json_decode($response, true);
        
        if(isset($response['slug']))
        {
            $result['stat'] = 'ok';
            $result[$slug] = $this->clearObjectJson($response);

            return $result;
        }
        else
        {
            return $this->returnErrorJson($response);
        }
    }

    /**
     *  IssuuDocument::getUpdateData()
     *  
     */
    public function getUpdateData($params = array())
    {
        return $this->returnSingleResult($params);
    }

    public function update($params)
    {
        // clean and fix array for sending request
        $slug = $params['slug'];
        unset($params['slug']);
        unset($params['publishDate']);
        $params = array('info' => $params);

        $this->setParams($params);
        $response = $this->curlRequest(
            $this->getApiUrl('/drafts/'.$slug),
            $this->params,
            $this->headers,
            'PATCH'
        );

        $response = json_decode($response, true);
        if(isset($response['slug']))
        {
            $friendlyUrl = str_replace(' ', '-', strtolower($params['info']['title']));
            $response = $this->curlRequest(
                $this->getApiUrl('/drafts/'.$slug.'/publish'),
                array("desiredName" => $friendlyUrl),
                $this->headers,
                'POST'
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
        else
        {
            return $this->returnErrorJson($response);
        }
    }


    public function delete($params = array())
    {
        $this->setParams($params);
        foreach ($params['names'] as $slug) {
            $response = $this->curlRequest(
                $this->getApiUrl('/publications/'.$slug),
                array(),
                $this->headers,
                'DELETE'
            );
        }

        return array('stat' => 'ok');
    }
    
    /**
    *   IssuuDocument::clearObjectXML()
    *
    *   Valida e formata os atributos do objeto do documento.
    *
    *   @access protected
    *   @param object $document Correspondente ao objeto do documento
    *   @return object Retorna um novo objeto do documento devidamente validado
    */
    protected function clearObjectXML($document)
    {
        $doc = new stdClass();
        echo json_encode($document);

        $doc->username = $this->validFieldXML($document, 'username');
        $doc->name = $this->validFieldXML($document, 'name');
        $doc->documentId = $this->validFieldXML($document, 'documentId');
        $doc->title = $this->validFieldXML($document, 'title');
        $doc->access = $this->validFieldXML($document, 'access');
        $doc->state = $this->validFieldXML($document, 'state');
        $doc->errorCode = $this->validFieldXML($document, 'errorCode');
        $doc->preview = $this->validFieldXML($document, 'preview', 2);
        $doc->category = $this->validFieldXML($document, 'category');
        $doc->type = $this->validFieldXML($document, 'type');

        $doc->orgDocType = $this->validFieldXML($document, 'orgDocType');
        $doc->orgDocName = $this->validFieldXML($document, 'orgDocName');
        $doc->downloadable = $this->validFieldXML($document, 'downloadable', 2);
        $doc->origin = $this->validFieldXML($document, 'origin');
        $doc->language = $this->validFieldXML($document, 'language');
        $doc->rating = $this->validFieldXML($document, 'rating');
        $doc->ratingsAllowed = $this->validFieldXML($document, 'ratingsAllowed', 2);
        $doc->ratingDist = $this->validFieldXML($document, 'ratingDist');
        $doc->showDetectedLinks = $this->validFieldXML($document, 'showDetectedLinks', 2);

        $doc->pageCount = $this->validFieldXML($document, 'pageCount');
        $doc->dcla = $this->validFieldXML($document, 'dcla');
        $doc->ep = $this->validFieldXML($document, 'ep');
        $doc->publicationCreationTime = $this->validFieldXML($document, 'publicationCreationTime');
        $doc->publishDate = $this->validFieldXML($document, 'publishDate');
        $doc->publicOnIssuuTime = $this->validFieldXML($document, 'publicOnIssuuTime');
        $doc->description = $this->validFieldXML($document, 'description');
        $doc->coverWidth = $this->validFieldXML($document, 'coverWidth', 1);
        $doc->coverHeight = $this->validFieldXML($document, 'coverHeight', 1);

        if (isset($document->tags))
        {
            $doc->tags = array();

            foreach ($document->tags->tag as $tag) {
                $doc->tags[] = utf8_decode($tag['value']);
            }
        }

        if (isset($document->folders))
        {
            $doc->folders = array();

            foreach ($document->folders->folder as $folder) {
                $doc->folders[] = (string) $folder['id'];
            }
        }

        return $doc;
    }

    /**
    *   IssuuDocument::clearObjectJson()
    *
    *   Valida e formata os atributos do objeto do documento.
    *
    *   @access protected
    *   @param object $document Correspondente ao objeto do documento
    *   @return object Retorna um novo objeto do documento devidamente validado
    */
    protected function clearObjectJson($document)
    {
        $doc = (object) $document;

        if(isset($doc->cover['small'])) {
            $doc->coverImage = $doc->cover['small']['url'];
        }
        if(isset($doc->cover['medium'])) {
            $doc->coverImage = $doc->cover['medium']['url'];
        }
        if(isset($doc->cover['large'])) {
            $doc->coverImage = $doc->cover['large']['url'];
        }

        return $doc;
    }

    private function setFile($file)
    {
        if (version_compare(PHP_VERSION, '5.5', '>='))
        {
            $fileParams = new CURLFile(
                $file['tmp_name'],
                $file['type'],
                $file['name']
            );
        }
        else
        {
            $fileParams = '@' . $file['tmp_name'];
        }

        return $fileParams;
    }
 
}