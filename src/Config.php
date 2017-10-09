<?php
namespace phpkit\config;
use Phalcon\Mvc\View\Simple as SimpleView;

class Config {
	function __construct($param = array()) {

		$param['configDir'] = $param['configDir'] ? $param['configDir'] : dirname(__FILE__) . '/data/';
		if (!$param['configDir']) {
			\phpkit\helper\mk_dir($cacheDir);
		}
		$this->params = $param;

		$frontCache = new \Phalcon\Cache\Frontend\Data(array(
			"lifetime" => 172800000,
		));

		$this->cache = new \Phalcon\Cache\Backend\File($frontCache, array(
			"cacheDir" => $cacheDir,
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
		if($GLOBALS['configData'][$name]){
			return $GLOBALS['configData'][$name];
		}
		if($this->exists($name)){
			$value = require $this->params['configDir'] . $name . ".php";
			//var_dump($this->params['configDir'] . $name . ".php");
			$dir = $this->params['configDir'] . $name . ".php";
			$GLOBALS['configData'][$name]=$value;
			//var_dump(require_once($dir));
			return $value;
		}else{
			return false;
		}
		
	}

	function save($name, $value) {
		$str_tmp = "<?php\r\n"; //得到php的起始符。$str_tmp将累加
		$str_tmp .= "return ";
		if (is_array($value)) {
			$str_tmp .= \phpkit\helper\arrayeval($value);
		} else if (is_string($value)) {
			$str_tmp .= "'" . $value . "'";
		} else {
			$str_tmp .= (string) $value;
		}

		$str_tmp = str_replace("Array", "array", $str_tmp);
		$str_tmp .= ";";
		$ret = \phpkit\helper\saveFile($this->params['configDir'] . $name . ".php", $str_tmp);
		if (!$ret) {
			throw new \Exception("配置生成失败！", 1);

		}
		//return $this->cache->save($name, $value);
	}

	function exists($name) {
		return is_file($this->params['configDir'] . $name . ".php");
	}

	function delete($name) {
		return unlink($this->params['configDir'] . $name . ".php");
	}
}
