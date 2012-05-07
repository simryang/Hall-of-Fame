<?php

/**
 * @author bluelovers
 * @copyright 2012
 */

class HOF_Helper_Global
{

	/**
	 * お金の表示方式
	 */
	function MoneyFormat($number, $pre = '$&nbsp;')
	{
		return $pre . number_format($number);
	}

	/**
	 * 赤い警告文でエラー表示
	 */
	function ShowError($message, $add = false)
	{
		if ($add) $add = " " . $add;

		if (is_object($message) && method_exists($message, '__toString'))
		{
			$message = (string)$message;
		}
		elseif (is_array($message))
		{
			$message = implode('<p>', $message);
		}

		if ($message)
		{
			print ('<div class="error' . $add . '">' . $message . '</div>' . "\n");
		}
	}

}
