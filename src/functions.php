<?php
/**
 * PHP implementation of RFC 6570 (URI Template)
 *
 * @package seebz\uri-template
 * @author  Sébastien Corne <sebastien@seebz.net>
 * @license http://www.wtfpl.net WTFPL
 */


/**
 * Transforms a template string into a URI
 * 
 * @param  string  $template   URI template
 * @param  array   $variables  Array of variables (optionnal)
 * @return string  Complete URI
 */
function uri_template($template, array $variables = array())
{
	// Expression replacement
	$expr_callback = function ($match) use ($variables)
	{
		list(, $operator, $variable_list) = $match;

		$separators = array(
			''  => ',',
			'+' => ',',
			'#' => ',',
			'.' => '.',
			'/' => '/',
			';' => ';',
			'?' => '&',
			'&' => '&',
		);
		$separator = $separators[$operator];

		$prefixes = array(
			''  => '',
			'+' => '',
			'#' => '#',
			'.' => '.',
			'/' => '/',
			';' => ';',
			'?' => '?',
			'&' => '&',
		);
		$prefix = $prefixes[$operator];


		// Callbacks
		$encode = function($value) use ($operator)
		{
			$value = rawurlencode($value);
			$value = str_replace('+', '%20', $value);

			if ($operator == '+' or $operator == '#')
			{
				// Reserved chars are now allowed
				$reserved = array(
					':' => '%3A',
					'/' => '%2F',
					'?' => '%3F',
					'#' => '%23',
					'[' => '%5B',
					']' => '%5D',
					'@' => '%40',
					'!' => '%21',
					'$' => '%24',
					'&' => '%26',
					"'" => '%27',
					'(' => '%28',
					')' => '%29',
					'*' => '%2A',
					'+' => '%2B',
					',' => '%2C',
					';' => '%3B',
					'=' => '%3D',
				);
				$value = str_replace(
					$reserved,
					array_keys($reserved),
					$value
				);

				// pct-encoded chars are allowed
				$value = preg_replace('`%25([0-9]{2})`', '%\\1', $value);
			}

			return $value;
		};

		$add_key = function ($key, $value) use ($operator)
		{
			if (empty($value) and $operator == ';')
			{
				$value = $key;
			}
			elseif ($operator == ';' or $operator == '?' or $operator == '&')
			{
				$value = $key . '=' . $value;
			}

			return $value;
		};

		// Scalar values
		$format_scalars = function ($key, $value, $modifier = null, $modifier_option = null)
			use ($encode, $add_key)
		{
			if ($modifier == ':' and $modifier_option)
			{
				$value = substr($value, 0, $modifier_option);
			}

			$value = $encode($value);
			$value = $add_key($key, $value);

			return $value;
		};

		// List-type array
		$format_lists = function ($key, $value, $modifier = null)
			use ($separator, $encode, $add_key)
		{
			if ($modifier == '*')
			{
				foreach($value as $k => $v)
				{
					$v = $encode($v);
					$v = $add_key($key, $v);
					$value[$k] = $v;
				}
				$value = implode($separator, $value);
			}
			else
			{
				$value = array_map($encode, $value);
				$value = implode(',', $value);
				$value = $add_key($key, $value);
			}

			return $value;
		};

		// Key-type array
		$format_keys = function ($key, $value, $modifier = null, $modifier_option = null)
			use ($operator, $separator, $encode, $add_key)
		{
			if ($modifier == '*')
			{
				foreach($value as $k => $v)
				{
					$v = $k . '=' . $encode($v);
					$value[$k] = $v;
				}
				$value = implode($separator, $value);
			}
			else
			{
				foreach($value as $k => $v)
				{
					$v = $k . ',' . $encode($v);
					$value[$k] = $v;
				}
				$value = implode(',', $value);
				$value = $add_key($key, $value);
			}

			return $value;
		};


		// The loop
		foreach(explode(',', $variable_list) as $variable_key)
		{
			preg_match('`^([^:\*]+)(:([1-9][0-9]*)|\*)?$`', $variable_key, $m);
			$key = $m[1];
			$modifier        = count($m) > 2 ? $m[2]{0} : null;
			$modifier_option = count($m) > 3 ? $m[3] : null;

			if (isset($variables[$key]))
			{
				$value = $variables[$key];

				if (is_scalar($value))
				{
					$format_func = $format_scalars;
				}
				elseif (empty($value))
				{
					continue;
				}
				elseif (array_values($value) === $value)
				{
					$format_func = $format_lists;
				}
				else
				{
					$format_func = $format_keys;
				}
				$founds[] = $format_func($key, $value, $modifier, $modifier_option);
			}
		}

		return empty($founds) ? '' : $prefix . implode($separator, $founds);
	};

	$expr_pattern = '`\{'
	              . '(&|\?|;|/|\.|#|\+|)' // operator
	              . '([^\}]+)'            // variable_list
	              . '\}`';

	return preg_replace_callback($expr_pattern, $expr_callback, $template);
}
