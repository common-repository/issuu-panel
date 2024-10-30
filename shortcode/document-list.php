<?php

function issuu_painel_embed_documents_shortcode($atts)
{
	$post = get_post();
	$postID = (!is_null($post) && IssuuPanelConfig::inContent())? $post->ID : 0;
	$issuuPanelConfig = IssuuPanelConfig::getInstance();
	$issuu_panel_api_bearer_token = IssuuPanelConfig::getVariable('issuu_panel_api_bearer_token');
	$issuu_panel_reader = IssuuPanelConfig::getVariable('issuu_panel_reader');
	$issuu_shortcode_index = IssuuPanelConfig::getNextIteratorByTemplate();
	$inHook = IssuuPanelConfig::getIssuuPanelCatcher()->getCurrentHookIs();
	$page_query_name = 'ip_shortcode' . $issuu_shortcode_index . '_page';
	issuu_panel_debug("Shortcode [issuu-painel-document-list]: Init");
	issuu_panel_debug("Shortcode [issuu-painel-document-list]: Index " . $issuu_shortcode_index . ' in hook ' . $inHook);
	$shortcode = 'issuu-painel-document-list' . $issuu_shortcode_index . $inHook . $postID;

	$atts = shortcode_atts(
		array(
			'size' => '10'
		),
		$atts
	);

	$page = (isset($_GET[$page_query_name]) && is_numeric($_GET[$page_query_name]))?
		intval($_GET[$page_query_name]) : 1;

	if (IssuuPanelConfig::cacheIsActive() && !$issuuPanelConfig->isBot())
	{
		$cache = IssuuPanelConfig::getCache($shortcode, $atts, $page);
		issuu_panel_debug("Shortcode [issuu-painel-document-list]: Cache active");
		if (!empty($cache))
		{
			issuu_panel_debug("Shortcode [issuu-painel-document-list]: Cache used");
			return $cache;
		}
	}

	$params = array(
		'size' => $atts['size'],
		'page' => ($atts['size'] * ($page - 1)),
	);

	try {
		$issuu_document = new IssuuDocument($issuu_panel_api_bearer_token);
		$documents = $issuu_document->issuuList($params);
		issuu_panel_debug("Shortcode [issuu-painel-document-list]: URL - " . $issuu_document->buildUrl());
	} catch (Exception $e) {
		issuu_panel_debug("Shortcode [issuu-painel-document-list]: IssuuDocument->issuuList Exception - " .
			$e->getMessage());
		return "";
	}

	if (isset($documents['stat']) && $documents['stat'] == 'ok')
	{
		if (isset($documents['results']) && !empty($documents['results']))
		{
			$docs = array();
			$pagination = array(
				'size' => $documents['size'],
				'totalCount' => $documents['totalCount']
			);

			foreach ($documents['results'] as $doc) {
				$docs[] = array(
					'id' => $doc['slug'],
					'thumbnail' => $doc['cover']['large']['url'],
					'url' => 'https://issuu.com/' . $doc['owner'] . '/docs/' . str_replace(' ', '_', strtolower($doc['fileInfo']['name'])),
					'title' => $doc['fileInfo']['name'],
					'date' => date_i18n('d/F/Y', strtotime($doc['changes']['originalPublishDate'])),
				);
			}
			
			include(ISSUU_PANEL_DIR . 'shortcode/generator.php');

			issuu_panel_debug("Shortcode [issuu-painel-document-list]: List of documents successfully displayed");

			if (IssuuPanelConfig::cacheIsActive() && !$issuuPanelConfig->isBot())
			{
				IssuuPanelConfig::updateCache($shortcode, $content, $atts, $page);
				issuu_panel_debug("Shortcode [issuu-painel-document-list]: Cache updated");
			}
			return $content;
		}
		else
		{
			issuu_panel_debug("Shortcode [issuu-painel-document-list]: No documents in list");
			$content = '<h3>' . get_issuu_message('No documents in list') . '</h3>';
			if (IssuuPanelConfig::cacheIsActive() && !$issuuPanelConfig->isBot())
			{
				IssuuPanelConfig::updateCache($shortcode, $content, $atts, $page);
				issuu_panel_debug("Shortcode [issuu-painel-document-list]: Cache updated");
			}
			return $content;
		}
	}
	else
	{
		issuu_panel_debug("Shortcode [issuu-painel-document-list]: " . $documents['message']);
		$content = '<h3>' . get_issuu_message($documents['message']) . '</h3>';
		if (IssuuPanelConfig::cacheIsActive() && !$issuuPanelConfig->isBot())
		{
			IssuuPanelConfig::updateCache($shortcode, $content, $atts, $page);
			issuu_panel_debug("Shortcode [issuu-painel-document-list]: Cache updated");
		}
		return $content;
	}

}

add_shortcode('issuu-painel-document-list', 'issuu_painel_embed_documents_shortcode');