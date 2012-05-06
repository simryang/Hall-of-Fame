<?php

/**
 * @author bluelovers
 * @copyright 2012
 */

class HOF_Class_Yaml extends Symfony_Component_Yaml_Yaml
{

	const INLINE = 6;

	public static function load($file, $enablePhpParsing = false)
	{
		HOF_Class_Yaml::$enablePhpParsing = $enablePhpParsing;

		if (file_exists($file))
		{
			$yaml = self::parse($file);
		}
		else
		{
			$yaml = false;
		}

		HOF_Class_Yaml::$enablePhpParsing = false;

		return $yaml;
	}

	public static function save($file, $data, $inline = HOF_Class_Yaml::INLINE)
	{
		return file_put_contents($file, self::dump($data, $inline), LOCK_EX);
	}

	public static function dump($array, $inline = HOF_Class_Yaml::INLINE)
	{
		return parent::dump($array, $inline);
	}

}

