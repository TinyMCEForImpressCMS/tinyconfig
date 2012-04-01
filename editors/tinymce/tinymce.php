<?php
/**
 * TinyMCE adapter
 *
 * @copyright	The XOOPS project http://www.xoops.org/
 * @license		http://www.fsf.org/copyleft/gpl.html GNU public license
 * @author		Taiwen Jiang (phppp or D.J.) <php_pp@hotmail.com>
 * @since		4.00
 * @version		$Id: tinymce.php 11324 2011-08-22 02:41:00Z skenow $
 * @package		xoopseditor
 */
class TinyMCE {
	var $rootpath;
	var $config = array();
	var $setting = array();


	/**
	 * PHP 5 Constructor
	 *
	 * @param    string    $config   The configuration
	 **/
	function __construct($config) {
		$this->setConfig($config);
		$this->rootpath = $this->config["rootpath"] . "/jscripts";
	}

	/**
	 * Creates one instance of the tinyMCE object
	 *
	 * @param    array     $config     The configuration
	 * @return   object    $instance   The instance of tinyMCE object
	 **/
	function &instance($config) {
		static $instance;
		if (!isset($instance)) {
			$instance = new TinyMCE($config);
		} else {
			$instance->setConfig($config);
		}

		return $instance;
	}

	/**
	 * Gets configuration Elements
	 *
	 * @param    string  $element    The configuration element
	 * @return   array   $elements   The array of configuration elements
	 **/
	function getElements($element = null) {
		static $elements = array();
		if (!empty($element)) {
			$elements[] = $element;
		}

		return $elements;
	}

	/**
	 * Gets configuration Elements
	 *
	 * @param    string  $element    The configuration element
	 * @return   array   $elements   The array of configuration elements
	 **/
	function setConfig($config) {
		$config["elements"] = implode(",", $this->getElements($config["elements"]));
		foreach ($config as $key => $val) {
			$this->config[$key] = $val;
		}
	}

	/**
	 * Initializes the tinyMCE
	 * @return   true
	 **/
	function init() {
		global $icmsConfigMultilang, $icmsConfigPersona;

		if ( is_object( icms::$user ) ) {
			$uid = icms::$user -> getVar( 'uid' );
			$getthegroupid = icms::$user -> getGroups( $uid );
			$thegroupid = array_slice( $getthegroupid, 0, 1 );
			$thegroupid = implode( ' ', $thegroupid );
			$thegroupid = trim( $thegroupid );
		} else {
			$thegroupid = 3;
		}

		$sql = 'SELECT * FROM ' . icms::$xoopsDB -> prefix( 'tinycfg_toolsets' ) . ' WHERE tinycfg_gid=' . $thegroupid;
		$tinycfg_tools = icms::$xoopsDB -> fetchArray( icms::$xoopsDB -> query( $sql ) );

		$configured = array();
		if (is_readable(ICMS_ROOT_PATH . $this->rootpath. '/langs/'.$this->config["language"].'.js')) {
			$this->setting["language"] = $this->config["language"];
		}

		if (empty($this->config["theme"]) || !is_dir(ICMS_ROOT_PATH . $this->rootpath."/themes/".$this->config["theme"])) {
			$this->setting["theme"] = "advanced";
		} else {
			$this->setting["theme"] = $this->config["theme"];
		}
		$this->setting["mode"] = @$this->config["mode"] ? $this->config["mode"] : "exact";
		$configured[] = "language";
		$configured[] = "theme";
		$configured[] = "mode";
		$this->setting["plugins"] = $tinycfg_tools['plugins'];
		$this->setting["plugins"] .= !empty($this->config["plugins"]) ? ",".$this->config["plugins"] : "";
		$configured[] = "plugins";

		$this->setting["content_css"] = @$this->config["content_css"] ? $this->config["content_css"] : "editor_xoops.css";
		if (!is_readable(ICMS_ROOT_PATH . $this->rootpath. '/themes/'.$this->setting["theme"].'/css/' .$this->setting["content_css"])) {
			unset( $this->setting["content_css"] );
		}
		$easiestml_exist = ($icmsConfigMultilang['ml_enable'] == true && defined('EASIESTML_LANGS') && defined('EASIESTML_LANGNAMES'));
		if ($this->setting["theme"] == "advanced") {
			$this->setting["theme_advanced_buttons1"] = $tinycfg_tools['buttons1'];
			$this->setting["theme_advanced_buttons2"] = $tinycfg_tools['buttons2'];
			$this->setting["theme_advanced_buttons3"] = $tinycfg_tools['buttons3'];
			$this->setting["theme_advanced_buttons4"] = $tinycfg_tools['buttons4'];
		}

		if ($this->setting["theme"] != "simple") {
			if (empty($this->config["buttons"])) {
				$this->config["buttons"][] = array(
					"before"	=> "",
					"add"		=> "",
				);
				$this->config["buttons"][] = array(
					"before"	=> "",
					"add"		=> "",
				);
				$this->config["buttons"][] = array(
					"before"	=> "",
					"add"		=> "",
				);
			}
			$i = 0;
			foreach ($this->config["buttons"] as $button) {
				$i++;
				if (isset($button["before"])) {
					$this->setting["theme_".$this->setting["theme"]."_buttons{$i}_add_before"] = $button["before"];
				}
				if (isset($button["add"])) {
					$this->setting["theme_".$this->setting["theme"]."_buttons{$i}_add"] = $button["add"];
				}
				if (isset($button[""])) {
					$this->setting["theme_".$this->setting["theme"]."_buttons{$i}"] = $button[""];
				}
			}
			$configured[] = "buttons";

			if (isset($this->config["toolbar_location"])) {
				$this->setting["theme_".$this->setting["theme"]."_toolbar_location"] = $this->config["toolbar_location"];
				$configured[] = "toolbar_location";
			} else {
				$this->setting["theme_".$this->setting["theme"]."_toolbar_location"] = $tinycfg_tools['toolbarloc'];
			}

			if (isset($this->config["toolbar_align"])) {
				$this->setting["theme_".$this->setting["theme"]."_toolbar_align"] = $this->config["toolbar_align"];
				$configured[] = "toolbar_align";
			} else {
				$this->setting["theme_".$this->setting["theme"]."_toolbar_align"] = _GLOBAL_LEFT;
			}

			if (isset($this->config["statusbar_location"])) {
				$this->setting["theme_".$this->setting["theme"]."_statusbar_location"] = $this->config["statusbar_location"];
				$configured[] = "statusbar_location";
			}

			if (isset($this->config["path_location"])) {
				$this->setting["theme_".$this->setting["theme"]."_path_location"] = $this->config["path_location"];
				$configured[] = "path_location";
			}

			if (isset($this->config["resize_horizontal"])) {
				$this->setting["theme_".$this->setting["theme"]."_resize_horizontal"] = $this->config["resize_horizontal"];
				$configured[] = "resize_horizontal";
			}

			if (isset($this->config["resizing"])) {
				$this->setting["theme_".$this->setting["theme"]."_resizing"] = $this->config["resizing"];
				$configured[] = "resizing";
			}

			if (!empty($this->config["fonts"])) {
				$this->setting["theme_".$this->setting["theme"]."_fonts"] = $this->config["fonts"];
				$configured[] = "fonts";
			}

			if (!empty($this->config["font_sizes"])) {
				$this->setting["theme_".$this->setting["theme"]."_font_sizes"] = $this->config["font_sizes"];
				$configured[] = "font_sizes";
			}
		}

		foreach ($this->config as $key => $val) {
			if (isset($this->setting[$key]) || in_array($key, $configured)) {
				continue;
			}
			$this->setting[$key] = $val;
		}

		if (!is_dir(ICMS_ROOT_PATH . $this->rootpath."/themes/".$this->setting["theme"]. '/docs/'.$this->config["language"].'/')) {
			$this->setting["docs_language"] = "en";
		}

		unset($this->config, $configured);

		return true;
	}

	/**
	 * Renders the tinyMCE
	 * @return   string  $ret      The rendered HTML string
	 **/
	function render() {
		static $rendered;
		if ($rendered) return null;

		$rendered = true;

		$this->init();

		if (!empty($this->setting["callback"])) {
			$callback = $this->setting["callback"];
			unset($this->setting["callback"]);
		} else {
			$callback = "";
		}

		$ret = '<script language="javascript" type="text/javascript" src="' . ICMS_URL . $this->rootpath . '/tiny_mce.js"></script>';
		$ret .= '
				<script language="javascript" type="text/javascript">
				tinyMCE.init({
			';
		foreach ($this->setting as $key => $val) {
			$ret .= $key . ' : ';
			if ($val === true || $val === false) {
				$ret .= $val.','."\r\n";
			} else {
				$ret .= '"'. $val . '",'."\r\n";
			}
		}

		$sql = 'SELECT * FROM ' . icms::$xoopsDB -> prefix( 'tinycfg_configs' );
		$tinycfg_configs = icms::$xoopsDB -> fetchArray( icms::$xoopsDB -> query( $sql ) );

		if ( $tinycfg_configs['skinvariant'] == 'none' ) { 
			$skinvariant = '';
		} else {
			$skinvariant = $tinycfg_configs['skinvariant'];
		}

		$ret .= '
		skin : "' . $tinycfg_configs['skin'] . '",
		skin_variant : "' . $skinvariant . '",
		verify_html : ' . $tinycfg_configs['verifyhtml'] . ',
		document_base_url : "' . ICMS_URL . '",
		convert_urls : ' . $tinycfg_configs['converturls'] . ',
		relative_urls : ' . $tinycfg_configs['relativeurls'] . ',
		remove_script_host : ' . $tinycfg_configs['relativeurls'] . ',
		force_br_newlines : ' . $tinycfg_configs['forcebr'] . ',
		force_p_newlines : ' . $tinycfg_configs['forcep'] . ',
		forced_root_block : "' . $tinycfg_configs['forcedrootblock'] . '",
		schema : "' . $tinycfg_configs['sschema'] . '",
		tinymceload : "1"});
		'.$callback.'
		function showMCE(id) {
			if (tinyMCE.getInstanceById(id) == null) {
				tinyMCE.execCommand(\'mceAddControl\', false, id);
			} else {
				tinyMCE.execCommand(\'mceRemoveControl\', false, id);
			}
		}
		</script>
		';
		return $ret ;
	}
}