<?php

$params = array(
    'folderId' => $atts['id'],
    'size' => 1,
    'page' => 0,
    'bookmarkSortBy' => 'desc'
);

try {
    $bookmarks = $issuu_bookmark->issuuList($params);

    if ($bookmarks['stat'] == 'ok')
    {
        if (isset($bookmarks['bookmark']) && !empty($bookmarks['bookmark']))
        {
            $docs = array();

            foreach ($bookmarks['bookmark'] as $book) {
                $docs[] = array(
                    'id' => $book->documentId,
                    'thumbnail' => 'https://image.issuu.com/' . $book->documentId . '/jpg/page_1_thumb_large.jpg',
                    'url' => 'https://issuu.com/' . $book->username . '/docs/' . $book->name,
                    'title' => $book->title
                );
            }
        }
    
        $doc = $docs[0];
    }
} catch (Exception $e) {
    issuu_panel_debug("Shortcode [issuu-panel-last-document]: Exception - " . $e->getMessage());
}