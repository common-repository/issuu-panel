<?php

class IssuuPanelPageFolders extends IssuuPanelSubmenu
{
	protected $slug = 'issuu-folder-admin';

	protected $page_title = 'Folders';

	protected $menu_title = 'Folders';

	protected $priority = 2;

	public function page()
	{
		$this->getConfig()->getIssuuPanelDebug()->appendMessage("Issuu Panel Page (Folders)");
		$subpage = filter_input(INPUT_GET, 'issuu-panel-subpage');

		try {
			switch ($subpage) {
				case 'add':
					$this->addPage();
					break;
				case 'update':
					$this->updatePage();
					break;
				case null:
					$this->listPage();
					break;
				default:
					$this->getConfig()->getIssuuPanelDebug()->appendMessage("Page not found");
					$this->getErrorMessage(get_issuu_message('This page not exists'));
					break;
			}
		} catch (Exception $e) {
			$this->getConfig()->getIssuuPanelDebug()->appendMessage("Page Exception - " . $e->getMessage());
			$this->getErrorMessage(get_issuu_message('An error occurred while we try connect to Issuu'));
		}
	}

	private function addPage()
	{
		include(ISSUU_PANEL_DIR . 'menu/folder/forms/add.php');
	}

	private function updatePage()
	{
		$issuuFolder = $this->getConfig()->getIssuuServiceApi('IssuuFolder');
		$folderId = filter_input(INPUT_GET, 'folder');
		$folder = $issuuFolder->getUpdateData(array('folderId' => $folderId));
		$params = $issuuFolder->getParams();
		unset($params['apiBearerToken']);
		$this->getConfig()->getIssuuPanelDebug()->appendMessage(
			"Request Data - " . json_encode($params)
		);

		if ($folder['stat'] == 'ok')
		{
            $folders_documents = $folder['documents'];
			$folder = $folder['stack'];
			$image = 'https://image.issuu.com/%s/jpg/page_1_thumb_large.jpg';
            include(ISSUU_PANEL_DIR . 'menu/folder/forms/update.php');
		}
		else
		{
			$this->getErrorMessage(get_issuu_message('The folder does not exist'));
		}
	}

	private function listPage()
	{
		$issuuFolder = $this->getConfig()->getIssuuServiceApi('IssuuFolder');
		$issuuBookmark = $this->getConfig()->getIssuuServiceApi('IssuuBookmark');
		$image = 'https://image.issuu.com/%s/jpg/page_1_thumb_large.jpg';
		$page = (intval(filter_input(INPUT_GET, 'pn')))? : 1;
		$size = 10;
        $params =
        array(
            'size' => $size,
			'page' => $page
		);

		$folders = $issuuFolder->stackList($params);

		if (isset($folders['totalCount']) && $folders['totalCount'] > $folders['size'])
		{
            $number_pages = ceil($folders['totalCount'] / $size);
		}
		include(ISSUU_PANEL_DIR . 'menu/folder/folder-list.php');
	}
}