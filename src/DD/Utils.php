<?php

namespace DD;

class Utils {

	/**
	 * @param $stacktrace //debug_backtrace ()
	 */
	public static function PrintStack ($stacktrace) {

		echo str_repeat ("=", 50)."<br>";

		$i = 1;

		foreach ($stacktrace as $node) {

			$j        = $i < 10 ? "0".$i : $i;
			$file     = $node['file'] ?? "";
			$function = $node['function'] ?? "";
			$line     = $node['line'] ?? "";

			echo "$j ================================<br>";
			echo "File: ".basename ($file)."<br>";
			echo "Method: ".$function."(".$line.")<br>";
			echo "FilePath: ".$file."<br>";

			$i++;

		}

	}

}
