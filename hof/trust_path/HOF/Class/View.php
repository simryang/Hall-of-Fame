<?php

/**
 * @author bluelovers
 * @copyright 2012
 */

class HOF_Class_View
{
	var $output = array();
	var $template = null;
	var $template_file = null;
	var $content = null;
	var $extend = null;
	var $body = null;

	var $controller;

	protected static $_suppressNotFoundWarnings = true;

	function __construct(&$controller, &$output, $template = null)
	{
		$this->controller = &$controller;

		$this->output = &$output;
		$this->template = $template;

		$this->template_file = self::_getTplFile($this->template);
	}

	function __toString()
	{
		return (string)$this->body;
	}

	static function render(&$controller, $output, $template = null, $content = null)
	{
		$_this = new self(&$controller, &$output, $template);

		$_this->controller->view['render'][$_this->template][] = $_this;

		$_this->content = $content;

		$content = $_this->_view();

		if ($_this->extend)
		{
			$content = self::render($_this->controller, &$_this->output, $_this->extend, $content);
		}

		$_this->body = $content;

		return $_this;
	}

	function output()
	{
		$output = $this->__toString();

		$output = preg_replace('/^[\s\n]*|[\s\n]*$/i', '', $output);
		$output = preg_replace('/[\s\r\n]*(\n)[\s\r\n]*/i', '\\1', $output);

		echo $output;

		return $this;
	}

	function slot($name, $content = null)
	{
		$view = self::render($this->controller, &$this->output, $name, $content);

		$this->controller->view['slot'][$name][] = $view;

		return $view;
	}

	function _getTplFile($name)
	{
		$template = BASE_PATH_TPL . '/'.$name.'.php';

		return $template;
	}

	function exists($name)
	{
		$template = self::_getTplFile($name);

		return file_exists($template);
	}

	function extend($name)
	{
		$this->extend = $name;

		$this->controller->view['extend'][$this->extend][] = $this->extend;
	}

	protected function _view()
	{
		ob_start();
		$this->_display($this->output);
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	public static function suppressNotFoundWarnings($flag = null)
    {
        if (null !== $flag) {
        	self::$_suppressNotFoundWarnings = (bool) $flag;
       	}

        return self::$_suppressNotFoundWarnings;
    }

	protected function _display($tplcache)
	{
		if (self::$_suppressNotFoundWarnings && !file_exists($this->template_file))
		{

		}
		else
		{
			require($this->template_file);
		}
	}

	function set($k, $v)
	{
		$this->output[$k] = $v;

		return $this;
	}

	function get($k, $default = null)
	{
		return (!isset($default) || isset($this->output[$k])) ? $this->output[$k] : $default;
	}
}