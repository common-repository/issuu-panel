<?php

class IssuuPanelTinyMCEButton implements IssuuPanelService
{
	private $config;

	public function __construct()
	{
		add_action('init', array($this, 'init'));
		add_filter('tiny_mce_version', array($this, 'issuuPanelRefreshMCE'));
		add_action('wp_ajax_issuu_panel_tinymce_ajax', array($this, 'tinymceButtonPage'));
	}

	public function init()
	{
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages'))
			return;

		if (get_user_option('rich_editing') == 'true')
		{
			add_filter('mce_external_plugins', array($this, 'addIssuuPanelTinyMCEPlugin'));
			add_filter('mce_buttons', array($this, 'registerIssuuPanelButton'));
		}
		$this->getConfig()->getIssuuPanelDebug()->appendMessage("TinyMCE Button");
	}

	public function addIssuuPanelTinyMCEPlugin($plugin_array)
	{
		$plugin_array['issuupanel'] = ISSUU_PANEL_URL . 'assets/js/tinymce-button.js';
		return $plugin_array;
	}

	public function registerIssuuPanelButton($buttons)
	{
		array_push($buttons, "|", "issuupanel");
		return $buttons;
	}

	public function issuuPanelRefreshMCE($ver)
	{
		$ver += 3;
		return $ver;
	}

	public function setConfig(IssuuPanelConfig $config)
	{
		$this->config = $config;
	}

	public function getConfig()
	{
		return $this->config;
	}

	public function tinymceButtonPage()
	{
		try {
			$issuu_folder = $this->getConfig()->getIssuuServiceApi('IssuuFolder');
			$result = $issuu_folder->issuuList();
			$params = $issuu_folder->getParams();
			unset($params['apiBearerToken']);
			$this->getConfig()->getIssuuPanelDebug()->appendMessage("TinyMCE Modal");
			$this->getConfig()->getIssuuPanelDebug()->appendMessage("TinyMCE Modal params - " . json_encode($params));
		} catch (Exception $e) {
			$this->getConfig()->getIssuuPanelDebug()->appendMessage("TinyMCE Modal Exception - " . $e->getMessage());
			die($e->getMessage());
		}
		
		?>

		<!DOCTYPE html>
		<html>
		<head>
			<title>Issuu Panel Shortcode</title>
			<meta charset="utf-8">
			<?php
				wp_enqueue_script('jquery');
				wp_enqueue_script('tiny_mce_popup.js', includes_url('js/tinymce/tiny_mce_popup.js'));
				wp_enqueue_style('issuu-painel-tinymce-popup', ISSUU_PANEL_URL . 'assets/css/issuu-painel-tinymce-popup.css');
				// wp_print_styles('issuu-painel-tinymce-popup');
				// wp_print_scripts('jquery');
				// wp_print_scripts('tiny_mce_popup.js');
				do_action('admin_print_styles');
				do_action('admin_print_scripts');
			?>
		</head>
		<body>
			<form action="#" id="issuu-painel-form-popup">
				<div id="issuu-painel-table">
					<div class="issuu-painel-table-row">
						<div class="issuu-painel-table-cell"><?php the_issuu_message('Folder'); ?></div>
						<div class="issuu-painel-table-cell">
							<select name="folderId" id="folderId">
								<option value="none"><?php the_issuu_message('Select...'); ?></option>
								<?php if ($result['stat'] == 'ok' && (isset($result['folder']) && !empty($result['folder']))) : ?>
									<?php foreach ($result['folder'] as $folder) : ?>
										<option value="<?php echo $folder->folderId; ?>"><?php echo $folder->name; ?></option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
						</div>
					</div>
					<div class="issuu-painel-table-row">
						<div class="issuu-painel-table-cell"><?php the_issuu_message('Documents per page'); ?></div>
						<div class="issuu-painel-table-cell">
							<input type="text" name="size" id="size" maxlength="2" size="1">
						</div>
					</div>
					<div class="issuu-painel-table-row">
						<div class="issuu-painel-table-cell">
							<input type="submit" class="issuu-submit-button" value="<?php the_issuu_message('Insert'); ?>">
						</div>
						<div class="issuu-painel-table-cell">
							<input type="button" class="issuu-cancel-button" value="<?php the_issuu_message('Cancel'); ?>"
								onClick="tinyMCEPopup.close();">
						</div>
					</div>
				</div>
			</form>
			<script type="text/javascript">
				(function($){
					$('#size').keypress(function(e){
						if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
							return false;
						}
					});

					$('#issuu-painel-form-popup').submit(function(){
						var folder_id = $('#folderId').val();
						var size = $('#size').val();

						if (folder_id == 'none')
						{
							var shortcode = '[issuu-painel-document-list ';
						}
						else
						{
							var shortcode = '[issuu-painel-folder-list id="' + folder_id + '" ';
						}

						if (size.length > 0)
						{
							size = parseInt(size);

							if (size < 0 || size > 30)
							{
								size = 12;
							}

							shortcode += 'size="' + size + '" ';
						}

						tinyMCEPopup.execCommand('mceInsertContent', false, shortcode);
						tinyMCEPopup.close();
					});
				})(jQuery);
			</script>
		</body>
		</html>

		<?php
		die();
	}
}