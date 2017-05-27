<?php
namespace phpkit\config;
use Phalcon\Mvc\View\Simple as SimpleView;

class Config {
	function __construct($param = array()) {
		$frontCache = new \Phalcon\Cache\Frontend\Data(array(
			"lifetime" => 172800000,
		));

		$this->cache = new \Phalcon\Cache\Backend\File($frontCache, array(
			"cacheDir" => dirname(__FILE__) . '/data/',
		));
		//模板渲染
		$this->view = new SimpleView();
		$this->assets = new \Phalcon\Assets\Manager();
		$viewDir = dirname(__FILE__) . '/views/';
		$this->view->setViewsDir($viewDir);
	}

	function get($name, $test = '') {
		//强制填写字典
		if ($test == 'setIfNull' && !$this->exists($name)) {
			if ($_POST) {
				$value_str = str_replace("Array", "array", stripslashes(htmlspecialchars_decode(trim($_POST['cacheData']))));
				if (strpos($value_str, "array") !== 0) {
					$value_str = "'" . $value_str . "'";
				}
				eval("\$value = " . $value_str . "; ");
				$this->save($name, $value);
				echo '<script language="javascript" type="text/javascript">window.location.href=window.location.href</script>';
				exit();
			} else {
				$configDict = require dirname(__FILE__) . '/dict.php';
				//var_dump($configDict[$name]);
				$this->view->cacheName = $name;
				$this->view->title = $configDict[$name]['empty'];
				$this->view->placeholder = $configDict[$name]['placeholder'];
				echo $this->view->render("set-cache");
				exit();
			}
		}
		$value = $this->cache->get($name);
		return $value;
	}

	function save($name, $value) {
		return $this->cache->save($name, $value);
	}

	function exists($name) {
		return $this->cache->exists($name);
	}

	function delete($name) {
		$this->cache->delete($name);
	}
}
