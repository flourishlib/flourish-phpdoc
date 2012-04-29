<?php
ini_set('date.timezone', 'America/New_York');

if (isset($argv[1])) {
	if (!file_exists($argv[1])) {
		echo "ERROR: Output folder {$argv[1]} not found\n";
		exit(1);
	}
	$tmp_dir = realpath($argv[1]) . '/';
} else {
	$tmp_dir = '/var/tmp/flourish-phpdoc/';
}
if (file_exists($tmp_dir)) {
	`rm -Rf $tmp_dir`;
}
mkdir($tmp_dir);

$current_dir = dirname(__file__);

$bin                = $current_dir . '/pear/PhpDocumentor/phpdoc';
$pdepend_bin        = $current_dir . '/pear/pdepend';
$source_dir         = $current_dir . '/../classes/';
$output_dir         = $tmp_dir . 'output/';
$depend_output_dir  = $output_dir . 'depend/';
$phpdoc_output_dir  = $output_dir . 'phpdoc';
$docs_dir           = $tmp_dir . 'docs/';
$export_dir         = $tmp_dir . 'export';
$template_base      = $current_dir . '/pear';

if (!file_exists($output_dir)) {
	mkdir($output_dir);
}

if (!file_exists($docs_dir)) {
	mkdir($docs_dir);
}

if (!file_exists($export_dir)) {
	mkdir($export_dir);
}

$start = microtime(TRUE);

`cp -R $current_dir/../classes/* $export_dir/`;

$phpdepend_start = microtime(TRUE);
echo "Running PHP_Depend...";
`php $pdepend_bin $export_dir $depend_output_dir`;
$phpdepend_time = microtime(TRUE) - $phpdepend_start;
echo "done (" . round($phpdepend_time, 2) . " seconds)\n";

$preprocessing_start = microtime(TRUE);
echo "Preprocessing source code...";

function remove_pre($content, &$pres, $pre_tag='(?<= \* )') {
	preg_match_all("#" . $pre_tag . "<pre>.*?</pre>#s", $content, $matches);
	
	// Replace pre tags with placeholders so other parsing can be done
	foreach ($matches[0] as $match) {
		$pres[] = $match;
		$content = preg_replace('#' . preg_quote($match, '#') . '#', ':pre_' . (sizeof($pres)-1), $content, 1);
	}
	return $content;	
}

function readd_pre($content, $pres) {
	$i = 0;
	foreach ($pres as $pre) {
		$content = preg_replace('#:pre_' . $i . '\b#', strtr($pre, array('\\' => '\\\\', '$' => '\\$')), $content);
		$i++;
	}	
	return $content;	
}

function pre_highlight($content, $type)
{
	$content = stripcslashes($content);
	
	// Remove automatic linking in this pre tag
	$content = preg_replace('#<a href="f[A-Z0-9][a-zA-Z0-9]+(?:\#[a-zA-Z0-9]+)?">(((?!</a>).)*)</a>#', '\1', $content);	
	$content = preg_replace('#^ #m', '', $content);
	
	if ($type == 'php') {
		$content = preg_replace('/#!php\s*/', '', $content);
	} elseif ($type == 'html') {
		$content = preg_replace('/#!(text\/)?html\s*/', '', $content);
	} elseif ($type == 'sql') {
		$content = preg_replace('/#!sql\s*/', '', $content);
	}
	return '<pre class="block ' . $type . '"><code>' . $content . '</code></pre>';
}

// Change simple lists into complex ones for the phpdoc parser
$files = array_diff(scandir($export_dir), array('.', '..', '.git'));
do {
	$files_to_fix = array();
	$found_dir = FALSE;
	foreach ($files as $file) {
		if (is_dir($export_dir . '/' . $file)) {
			$found_dir = TRUE;
			$files2 = array_diff(scandir($export_dir . '/' . $file), array('.', '..'));
			foreach ($files2 as $file2) {
				$files_to_fix[] = $file . '/' . $file2;	
			}
		} else {
			$files_to_fix[] = $file;
		}	
	}	
	$files = $files_to_fix;
} while ($found_dir);

foreach ($files as $file) {
	
	$contents = file_get_contents($export_dir . '/' . $file);
	
	$pres = array();
	$contents = str_replace('* {{{', '* <pre>',  $contents);
	$contents = str_replace('* }}}', '* </pre>', $contents);
	
	$pres = array();
	$contents = remove_pre($contents, $pres);
	
	//$contents = str_replace(' :$', ' |$',  $contents);
	
	do {
		$orig_contents = $contents;
		// Auto-link fClass::method references
		$contents = preg_replace('#^(\s*\*.*)(\b(?<!\{\@link )f[A-Z0-9][A-Za-z0-9]+::\w+\(\))(.*)$#m', '\1{@link \2}\3', $contents);
		// Auto-link fClass references
		$contents = preg_replace('#^(\s*\*.*)(\b(?<!\{\@link |@param |@param  |@throws |@throws  |docs/|\.com/|@return |@return  |\|)f[A-Z0-9][A-Za-z0-9]+\b)(.*)$#m', '\1{@link \2}\3', $contents);
		// Auto-link ::method() references
		$contents = preg_replace('#^(\s*\*.*)(?<![a-zA-Z0-9])::(\w+\(\))(.*)$#m', '\1{@link \2}\3', $contents);
		
		// Link http: links
		$contents = preg_replace('#^(\s*\*.*)\[(https?://[^ ]+ [^\]]+)\](.*)$#m', '\1{@link \2}\3', $contents);
		// Link fClass::method() and fClass references
		$contents = preg_replace('#^(\s*\*.*)\[(f[A-Z0-9][A-Za-z0-9]+(?:::\w+\(\))? [^\]]+)\](.*)$#m', '\1{@link \2}\3', $contents);
		// Link method() references
		$contents = preg_replace('#^(\s*\*.*)\[(\w+\(\) [^\]]+)\](.*)$#m', '\1{@link \2}\3', $contents);
		
		// Link http: links without []s
		$contents = preg_replace('#^(\s*\*.*)((?<!\{\@link |@link       |@license    |`|\[)https?://[^ ]+?[^ .!`])(([.!`]\s|\s)(.*))?$#m', '\1{@link \2}\3', $contents);
		
		$contents = preg_replace('#`<(/?\w+)>`#', '`<<\1>>`', $contents);

		$contents = str_replace('<{@link fContrib}', '<fContrib', $contents);
		
	} while($orig_contents != $contents);

	$contents = preg_replace('#^(\s+\* *(\w.*|[ \t]*))((?:\r)?\n\s*\* ) - #im', '\1<ul>\3 - ', $contents);
	$contents = preg_replace('#(\* )((?: )+)- (.*?)(?=\r|\n|$)#im', '\1\2<li>\3</li>', $contents);
	$contents = preg_replace('#^(\s+\* ((?: )+)<li>.*?)</li>((?:\r)?\n\s*\* \2 )<li>#im', '\1\3<ul>\3<li>', $contents);
	$contents = preg_replace('#(\* ((?: )+) (?:<ul>)?<li>.*?</li>)((?:\r)?\n\s*\* \2)<li>#im', '\1\3</ul>\3</li>\3<li>', $contents);
	$contents = preg_replace_callback('#^((\s*\* )((?: )+)<li>.*?</li>)((?:\r)?\n\s*\*\s*((\r)?\n))#im', 
									  create_function(
										  '$matches',
										  '
										  $output = $matches[1] . "\n" . $matches[2] . $matches[3] . "</ul>";
										  $repeat = strlen($matches[3])-2;
										  for ($i=0; $i < $repeat; $i++) {
											  $output .= "</li>\n" . $matches[2] . str_pad("", $repeat-$i) . "</ul>";
										  }
										  return $output . $matches[4];
										  '    
									  ),
									  $contents);
	$contents = preg_replace('#(\*   </ul>((?:\r)?\n\s*\*))(\s*((\r)?\n))#im', '\1  </li>\2  </ul>\2\3', $contents);
	
	$contents = readd_pre($contents, $pres);
	
	file_put_contents($export_dir . '/' . $file, $contents);
	unset($contents);
}
$preprocessing_time = microtime(TRUE) - $preprocessing_start;
echo "done (" . round($preprocessing_time, 2) . " seconds)\n";

$phpdoc_start = microtime(TRUE);
echo "Running PHPDoc...";
shell_exec("php -d memory_limit=384M $bin -d $export_dir/ -o HTML:Smarty:PHP -ct changes,throws -tb $template_base -t $phpdoc_output_dir");
$phpdoc_seconds = microtime(TRUE) - $phpdoc_start;
echo "done (" . round($phpdoc_seconds, 2) . " seconds)\n";

$postprocessing_start = microtime(TRUE);
echo "Postprocessing PHPDoc results...";
	  
$files = scandir($phpdoc_output_dir . '/Flourish');
foreach ($files as $file) {
	if ($file[0] != '_' && $file[0] != '.') {
		shell_exec("mv ${phpdoc_output_dir}/Flourish/$file ${docs_dir}/");
	}
}
//shell_exec("rm -Rf ${output_dir}*");

$docs = array_diff(scandir($docs_dir), array('.', '..'));

$classes = array();
$class_versions = array();
$protected_methods = array(); 
$protected_vars = array();
$protected_consts = array();

$files_touched = array();

function add_tt($matches){
	return "<tt>" . preg_replace(array("#&lt;a href=&quot;(?:[^\"]+)?(f\w+)&quot;&gt;\\1&lt;/a&gt;#", '#&amp;(\w+;)#'), array('<a href="\1">\1</a>', '&\1'), htmlspecialchars($matches[1], ENT_COMPAT, "UTF-8")) . "</tt>";
}

foreach ($docs as $doc) {
	if (is_dir($docs_dir . $doc) || $doc == 'handler.php' || $doc == 'not_found.php' || $doc == 'file_listings' || $doc == 'highlighted_files') {
		continue;	
	} 
	
	$content = file_get_contents($docs_dir . $doc);
	
	$content = str_replace('"../Flourish/', '"', $content);
	$content = str_replace('"{subdir}', '"', $content);
	$content = str_replace('"Flourish/', '"', $content);
	$content = str_replace('"' . $doc . '#', '"#', $content);
	$content = str_replace('.html', '', $content);
	$content = str_replace('"elementindex_Flourish', '"', $content);
	
	$content = str_replace('<br>', '<br />', $content); 
	
	$content = preg_replace('#name="var\$#', 'name="var', $content);
	
	$content = str_replace('<pre></pre>', '', $content);
	
	$content = str_replace('<p><pre>', '<pre>', $content);
	$content = str_replace('</pre></p>', '</pre>', $content);
	
	$pres = array();
	$content = remove_pre($content, $pres, '');
	
	$content = preg_replace('/(href="[a-z0-9_\-]*#)var\$/i', '\1var', $content);
	
	$content = preg_replace('#<dt>[a-z0-9_\-\.]+(?:\.class)?\.php</dt>\s*<dd>.*?</dd>#im', '', $content);
	$content = preg_replace('#<dd>in file [a-z0-9_\-\.]+(?:\.class)?\.php, #im', '<dd><em>', $content);
	$content = preg_replace('#<br />&nbsp;&nbsp;&nbsp;&nbsp;#im', '</em><br />', $content);
	
	$content = preg_replace('#(<p>(.(?!</p>))*?)<([uo]l)>#ims', '\1</p><\3>', $content);
	$content = preg_replace('#</([uo]l)>((.(?!</p>))*)</p>#ims', '</\1><p>\2</p>', $content);
	
	$content = preg_replace('#<p>\s*</p><([uo]l)>#im', '<\1>', $content);
	$content = preg_replace('#</([uo]l)><p>\s*</p>#im', '</\1>', $content);
	
	//$content = str_replace('<span class="name">$', '<span class="name">', $content);
	
	$content = preg_replace('#(<span class="implements"> implements (?:<a href="[^"]+">)?\w+)\:\:\w+((</a>)?</span>)#im', '\1\2', $content);
	
	// Fix the stupid duplicated inherited method stuff
	$content = preg_replace('#(<dt>.*?</dt>\s*<dd>.*?</dd>)\s*\1#ims', '\1', $content);
	
	preg_match('#<div class="description">(.*?)</div>#i', $content, $matches);
	if ($matches && strip_tags($matches[1], '<a>') == $matches[1]) {
		$matches[1] = '<p>' . $matches[1] . '</p>';
		$content = preg_replace('#<div class="description">(.*?)</div>#i', strtr($matches[1], array('\\' => '\\\\', '$' => '\\$')), $content);
	}
	
	preg_match('#(<div class="changes"><strong>Changes:</strong> )((;(?:(?:(?!&\w{2,7};)[^;])+|&\w{2,7};)+;)+)</div>#im', $content, $matches);
	if ($matches && $matches[2]) {
		$changes = explode(';;', substr($matches[2], 1, -1));
		$changes = array_reverse($changes);
		$replacement = $matches[1] . '<table cellspacing="0">';
		foreach ($changes as $change) {
			list($version, $changes_made) = array_map('trim', explode(' ', trim($change), 2));
			preg_match('#\[[\w-+]+\s*,\s+(\d{4}-\d{2}-\d{2})\s*\]\s*$#', $changes_made, $change_matches);
			if ($change_matches) {
				$changes_made = preg_replace('#\s*\[[\w-+]+\s*,\s+\d{4}-\d{2}-\d{2}\s*\]\s*$#', '', $changes_made) . ' <span class="date">' . date('n/j/y', strtotime($change_matches[1])) . '</span>';
			}
			$replacement .= '<tr><th>' . $version . '</th><td>' . $changes_made . '</td></tr>';
		}
		$replacement .= '</table></div>';
		$content = preg_replace('#<div class="changes"><strong>Changes:</strong> ((;(?:(?:(?!&\w{2,7};)[^;])+|&\w{2,7};)+;)+)</div>#im', strtr($replacement, array('\\' => '\\\\', '$' => '\\$')), $content);
	}
	
	preg_match('#(<div class="todo"><strong>Todo:</strong> )((;[^;]+;)+)</div>#im', $content, $matches);
	if ($matches && $matches[2]) {
		$todos = explode(';;', substr($matches[2], 1, -1));
		$replacement = $matches[1] . '<ul>';
		foreach ($todos as $todo) {
			$replacement .= '<li>' . $todo . '</li>';
		}
		$replacement .= '</ul></div>';
		$content = preg_replace('#<div class="todo"><strong>Todo:</strong> (;[^;]+;)+</div>#im', strtr($replacement, array('\\' => '\\\\', '$' => '\\$')), $content);
	}
	
	// Fixes ... params
	$content = preg_replace('#(<span class="php-code">,</span> <span class="php-vartype">[\w|]+</span> )<span class="php-var">...</span>#', ' [<span class="php-code">, ... </span>]', $content);
	$content = preg_replace('#(</td>\s*<td class="param_description">((?!</td>).)*</td>\s*</tr>)\s*<tr>\s*<td class="param_data_type">((?!</td>).)*</td>\s*<td class="param_name">\.\.\.</td>\s*<td class="param_description">((?!</td>).)*</td>\s*</tr>#', ' <em>[, ... ]</em>\1', $content);
	
	// Allows for monospaced content
	$content = preg_replace_callback('#`([^`]+)`#', 'add_tt', $content);
	
	// Allows for bolded content
	$content = preg_replace('#\*\*(((?!\*\*).)*)\*\*#', '<strong>\1</strong>', $content);
	
	// Allows for italic content
	$content = preg_replace('#(?<!:)//(((?!//).)*)//#', '<em>\1</em>', $content);
	
	// Fix escaping variables passed by reference
	$content = preg_replace('#&\$#', '&amp;$', $content);
	
	// Add special link formatting
	$content = preg_replace('#<a href="(http://php\.net[^"]+)">(\w+\(\))</a>#', '<a class="ext-link" href="\1"><span class="icon"><tt>\2</tt></span></a>', $content);
	$content = preg_replace('#<a href="(http://((?!flourishlib\.com)[^"])+)">(((?!</a>).)*)</a>#', '<a class="ext-link" href="\1"><span class="icon">\3</span></a>', $content);
	$content = preg_replace('#<a href="((f[0-9A-Z][a-zA-Z0-9]+)\#(\w+))">(\2::\3\(\))</a>#', '<a href="\1"><tt>\4</tt></a>', $content);
	//$content = preg_replace('#(?<!<li>)<a href="(f[0-9A-Z][a-zA-Z0-9]+)">(\1)</a>#', '<a href="\1"><tt>\1</tt></a>', $content);
	$content = preg_replace('#(?<!<li>)<a href="(\#(\w+))">(\2\(\))</a>#', '<a href="\1"><tt>\3</tt></a>', $content);
	
	// Fix documentation for Exception
	$exception_method_list = '<dt><a href="http://www.php.net/~helly/php/ext/spl/classException.html#a2547e89c725be1ba3edbb20846a93f2">Exception::__construct()</a></dt>
<dd>Creates the exception</dd>
<dt><a href="http://www.php.net/~helly/php/ext/spl/classException.html#84cb308cbcf072fe19e9e2b8ec83918e">Exception::__toString()</a></dt>
<dd>Converts the exception to a string</dd>
<dt><a href="http://www.php.net/~helly/php/ext/spl/classException.html#097d6b0b078e04a05f32be7fa04675f6">Exception::getCode()</a></dt>
<dd>Returns the exception code</dd>
<dt><a href="http://www.php.net/~helly/php/ext/spl/classException.html#c2bf492ed2491a3e4440c812d0fcfdd1">Exception::getFile()</a></dt>
<dd>Returns the file the exception was thrown in</dd>
<dt><a href="http://www.php.net/~helly/php/ext/spl/classException.html#c40ad0b2cbad5d821dd58aa6845fb0d8">Exception::getLine()</a></dt>
<dd>Returns the line the exception was thrown on</dd>
<dt><a href="http://www.php.net/~helly/php/ext/spl/classException.html#dea1821fcc9ae127f9f6a14d0f3440bc">Exception::getMessage()</a></dt>
<dd>Returns the exception message</dd>
<dt><a href="http://www.php.net/~helly/php/ext/spl/classException.html#8fe27b7c983c263148aa3b01b987a0ef">Exception::getTrace()</a></dt>
<dd>Returns the backtrace leading up to the exception</dd>
<dt><a href="http://www.php.net/~helly/php/ext/spl/classException.html#eeed2af7b93463a31a58d0df1c820017">Exception::getTraceAsString()</a></dt>
<dd>Returns the backtrace leading up to the exception as a string</dd>';
	$content = preg_replace('#<dt>\s*constructor __construct \( \$message, \$code \)\s*</dt>\s*<dd>\s*</dd>\s*<dt>\s*__clone \(  \)\s*</dt>\s*<dd>\s*</dd>\s*<dt>\s*__toString \(  \)\s*</dt>\s*<dd>\s*</dd>\s*<dt>\s*getCode \(  \)\s*</dt>\s*<dd>\s*</dd>\s*<dt>\s*getFile \(  \)\s*</dt>\s*<dd>\s*</dd>\s*<dt>\s*getLine \(  \)\s*</dt>\s*<dd>\s*</dd>\s*<dt>\s*getMessage \(  \)\s*</dt>\s*<dd>\s*</dd>\s*<dt>\s*getTrace \(  \)\s*</dt>\s*<dd>\s*</dd>\s*<dt>\s*getTraceAsString \(  \)\s*</dt>\s*<dd>\s*</dd>#ims', $exception_method_list, $content);
	
	$exception_var_list = '<dt class="protected"><a href="http://www.php.net/~helly/php/ext/spl/classException.html#de01c70bc19d688f45fbc5bbd5f93a3f">Exception::$code</a></dt>
<dd class="protected">The exception code</dd>
<dt class="protected"><a href="http://www.php.net/~helly/php/ext/spl/classException.html#ef6f37d2ac33c140c56a4e45f7c08f9a">Exception::$file</a></dt>
<dd class="protected">The file the exception was thrown in</dd>
<dt class="protected"><a href="http://www.php.net/~helly/php/ext/spl/classException.html#3e94580366b5f4496810e34370b6b597">Exception::$line</a></dt>
<dd class="protected">The line the exception was thrown on</dd>
<dt class="protected"><a href="http://www.php.net/~helly/php/ext/spl/classException.html#da284a4a4a0e914127ad41028524c62f">Exception::$message</a></dt>
<dd class="protected">The exception message</dd>';
	$content = preg_replace('#<dt>\s*\$code\s*</dt>\s*<dd>\s*</dd>\s*<dt>\s*\$file\s*</dt>\s*<dd>\s*</dd>\s*<dt>\s*\$line\s*</dt>\s*<dd>\s*</dd>\s*<dt>\s*\$message\s*</dt>\s*<dd>\s*</dd>\s*<dt>\s*\$string\s*</dt>\s*<dd>\s*</dd>\s*<dt>\s*\$trace\s*</dt>\s*<dd>\s*</dd>#ims', $exception_var_list, $content);
	
	$content = str_replace('Exception (Internal Class)', 'Exception', $content);
	$content = str_replace('(internal interface)', '', $content);
	
	
	// Fix visiblity issues
	$class_name = preg_replace('#\.html$#', '', $doc);
	
	preg_match_all('#<\!-- start protected method(?: static)? -->\s*<h3 id="(.*?)"#ims', $content, $matches, PREG_SET_ORDER);
	if (sizeof($matches)) {
		$protected_methods[$class_name] = array();		
	}
	foreach ($matches as $match) {
		$protected_methods[$class_name][] = $match[1];		
	}
	
	if (isset($protected_methods[$class_name])) {
		foreach ($protected_methods[$class_name] as $method) {
			$content = preg_replace('#<li>(<a href="\#' . preg_quote($method, '#') . '">' . preg_quote($method, '#') . '\(\)</a></li>)#ims', '<li class="protected">\1', $content);	
		}
	}
	
	preg_match_all('#<\!-- start protected variable(?: static)? -->\s*<h3 id="(.*?)"#ims', $content, $matches, PREG_SET_ORDER);
	if (sizeof($matches)) {
		$protected_vars[$class_name] = array();		
	}
	foreach ($matches as $match) {
		$protected_vars[$class_name][] = $match[1];		
	}
	
	if (isset($protected_vars[$class_name])) {
		foreach ($protected_vars[$class_name] as $var) {
			$content = preg_replace('#<li>(<a href="\#' . preg_quote($var, '#') . '">\$' . preg_quote($var, '#') . '</a></li>)#ims', '<li class="protected">\1', $content);	
		}
	}
	
	
	preg_match_all('#<\!-- start protected constant -->\s*<h3 id="(.*?)"#ims', $content, $matches, PREG_SET_ORDER);
	if (sizeof($matches)) {
		$protected_consts[$class_name] = array();		
	}
	foreach ($matches as $match) {
		$protected_consts[$class_name][] = $match[1];		
	}
	
	if (isset($protected_consts[$class_name])) {
		foreach ($protected_consts[$class_name] as $const) {
			$content = preg_replace('#<li>(<a href="\#' . preg_quote($const, '#') . '">' . preg_quote($const, '#') . '</a></li>)#ims', '<li class="protected">\1', $content);	
		}
	}
	
	
	$content = readd_pre($content, $pres);
	
	// Highlight php content
	$content = preg_replace('#<pre> \#\!php[ \t]*\r?\n(((?!</pre>).)*)</pre>#se', "pre_highlight('\\1', 'php')", $content);
	
	// Highlight html content
	$content = preg_replace('#<pre> \#!(?:text/)?html[ \t]*\r?\n(((?!</pre>).)*)</pre>#se', "pre_highlight('\\1', 'html')", $content);
	
	// Highlight sql content
	$content = preg_replace('#<pre> \#!sql[ \t]*\r?\n(((?!</pre>).)*)</pre>#se', "pre_highlight('\\1', 'sql')", $content);
	
	// Fix pre spaces
	$content = preg_replace('#<pre>(?!<code>)(((?!</pre>).)*)</pre>#se', "pre_highlight('\\1', '')", $content);
	
	$lines = explode("\n", $content);
	$new_lines = array();
	foreach ($lines as $line) {
		if (trim($line)) {
			$new_lines[] = $line;
		}   
	}
	$content = join("\n", $new_lines);
	
	if ($doc == 'dep_tree.json') {
		file_put_contents($docs_dir . $doc, $content);	
		$files_touched[] = $doc;
	} else {
		/*$class_dir = preg_replace('#\.html$#', '', $doc) . '/';
		preg_match('#<span class="version">v(.*?)(</span>)#i', $content, $matches); 
		$version_array = explode('.', $matches[1]);
		$version = $version_array[0] . '.' . $version_array[1];
		if (!file_exists($docs_dir . $class_dir)) {
			mkdir($docs_dir . $class_dir, 0775);
		}
		unlink($docs_dir . $doc);*/
		file_put_contents($docs_dir . $doc, $content);	
		
		$classes[] = str_replace('.html', '', $doc);
		/*
		$class_versions[substr($class_dir, 0, -1)] = $version;*/
		
		$files_touched[] = $doc;
	}
	
	unset($content);
}





// Do another pass to correct visibility
$docs = array_diff(scandir($docs_dir), array('.', '..'));
$new_docs = array();
$dependencies = array();

foreach ($docs as $doc) {
	if ($doc == 'handler.php' || $doc == 'not_found.php') {
		continue;	
	}
	
	if (is_dir($docs_dir . $doc)) {
		$temp_docs = array_diff(scandir($docs_dir . $doc), array('.', '..'));
		foreach ($temp_docs as $temp_doc) {
			$new_docs[] = $doc . '/' . $temp_doc;	
		}
	} else {
		$new_docs[] = $doc;	
	}
}

foreach ($new_docs as $doc) {	
	if ($doc == 'handler.php' || $doc == 'not_found.php' || !in_array($doc, $files_touched)) {
		continue;	
	}

	$content = file_get_contents($docs_dir . $doc);
	
	/*// Set individual methods to have the protected class
	foreach ($protected_methods as $class => $methods) {
		foreach ($methods as $method) {
			$content = preg_replace('#<dt>((?:(?!</dt>).)*<a href="' . $class . '\#' . preg_quote($method, '#') . '"(?:(?!</dt>).)*</dt>\s*)<dd>(((?!</dd>).)*</dd>)#ims', '<dt class="protected">\1<dd class="protected">\2', $content);
			$content = preg_replace('#<dt>((?>.*?</dt>)\s*)(?:<dd>((.(?!</dd>))*a href="' . $class . '\#' . preg_quote($method, '#') . '".*?</dd>))#ims', '<dt class="protected">\1<dd class="protected">\2', $content);
		}	
	}
	
	// Set individual vars to have the protected class
	foreach ($protected_vars as $class => $vars) {
		foreach ($vars as $var) {
			$content = preg_replace('#<dt>((?:(?!</dt>).)*<a href="' . $class . '\#' . preg_quote($var, '#') . '"(?:(?!</dt>).)*</dt>\s*)<dd>(((?!</dd>).)*</dd>)#ims', '<dt class="protected">\1<dd class="protected">\2', $content);
			$content = preg_replace('#<dt>((?>.*?</dt>)\s*)(?:<dd>((.(?!</dd>))*a href="' . $class . '\#' . preg_quote($var, '#') . '".*?</dd>))#ims', '<dt class="protected">\1<dd class="protected">\2', $content);
		}	
	}
	
	// Set individual consts to have the protected class
	foreach ($protected_consts as $class => $consts) {
		foreach ($consts as $const) {
			$content = preg_replace('#<dt>((?:(?!</dt>).)*<a href="' . $class . '\#' . preg_quote($const, '#') . '"(?:(?!</dt>).)*</dt>\s*)<dd>(((?!</dd>).)*</dd>)#ims', '<dt class="protected">\1<dd class="protected">\2', $content);
			$content = preg_replace('#<dt>((?>.*?</dt>)\s*)(?:<dd>((.(?!</dd>))*a href="' . $class . '\#' . preg_quote($const, '#') . '".*?</dd>))#ims', '<dt class="protected">\1<dd class="protected">\2', $content);
		}	
	}*/
	
	// Set whole sectiosn of class navigation to be protected if all of the elements are
	$content = preg_replace('#<div class="variables( static)?">(\s*<h2><a href="\#class_vars(_static)?">(Static )?Variables</a></h2>\s*<ul>\s*(<li class="protected">((?!</li>).)*</li>\s*)+\s*</ul>\s*</div>)#ims', '<div class="protected variables\1">\2', $content);
	$content = preg_replace('#<div class="methods( static)?">(\s*<h2><a href="\#class_methods(_static)?">(Static )?Methods</a></h2>\s*<ul>\s*(<li class="protected">((?!</li>).)*</li>\s*)+\s*</ul>\s*</div>)#ims', '<div class="protected methods\1">\2', $content);
	$content = preg_replace('#<div class="consts">(\s*<h2><a href="\#class_consts">Constants</a></h2>\s*<ul>\s*(<li class="protected">((?!</li>).)*</li>\s*)+\s*</ul>\s*</div>)#ims', '<div class="protected consts">\1', $content);
	
	// Set whole sections of the class info to be protected if all of the elements are
	$content = preg_replace('#<div class="class_variables( static)?">(\s*<h2>(Static )?Variables</h2>\s*(<\!-- start protected variable(?: static)? -->((?!<\!-- end ).)*<\!-- end variable(?: static)? -->\s*)+\s*</div>)#ims', '<div class="protected class_variables\1">\2', $content);
	$content = preg_replace('#<div class="class_methods( static)?">(\s*<h2>(Static )?Methods</h2>\s*(<\!-- start protected method(?: static)? -->((?!<\!-- end ).)*<\!-- end method(?: static)? -->\s*)+\s*</div>)#ims', '<div class="protected class_methods\1">\2', $content);
	$content = preg_replace('#<div class="class_consts">(\s*<h2>Constants</h2>\s*(<\!-- start protected constant -->((?!<\!-- end ).)*<\!-- end constant -->\s*)+\s*</div>)#ims', '<div class="protected class_constants">\1', $content);

	
	// Hide sectoins of the glossary if all elements are protected
	$content = preg_replace('#<div class="section">(\s*<h2>(?:(?!</h2>).)*</h2>\s*<dl>\s*(?:<dt class="protected">(?:(?!</dt>).)*</dt>\s*<dd class="protected">(?:(?!</dd>).)*</dd>\s*)+\s*</dl>\s*<a href="\#top" class="top">Top</a>\s*</div>)#ims', '<div class="protected section">\1', $content);
	
	preg_match_all('#<div class="protected section">\s*<h2>(.*?)</h2>#ims', $content, $matches, PREG_SET_ORDER);
	if (sizeof($matches)) {
		foreach ($matches as $match) {
			$content = preg_replace('#<a href="\#." class="section_link">(' . $match[1] . '</a>)#ims', '<a href="#' . $match[1] . '" class="protected section_link">\1', $content);
		}	
	}
	
	// Hide inherited methods and inheritied vars on class detail pages
	$content = preg_replace('#<div class="inherited_methods">(\s*<h3>(?:(?!</h3>).)*</h3>\s*(<dl>\s*(?:<dt class="protected">(?:(?!</dt>).)*</dt>\s*<dd class="protected">(?:(?!</dd>).)*</dd>\s*)+\s*</dl>\s*)+</div>)#ims', '<div class="protected inherited_methods">\1', $content);
	$content = preg_replace('#<div class="inherited_variables">(\s*<h3>(?:(?!</h3>).)*</h3>\s*(<dl>\s*(?:<dt class="protected">(?:(?!</dt>).)*</dt>\s*<dd class="protected">(?:(?!</dd>).)*</dd>\s*)+\s*</dl>\s*)+</div>)#ims', '<div class="protected inherited_variables">\1', $content);
	$content = preg_replace('#<div class="inherited_constants">(\s*<h3>(?:(?!</h3>).)*</h3>\s*(<dl>\s*(?:<dt class="protected">(?:(?!</dt>).)*</dt>\s*<dd class="protected">(?:(?!</dd>).)*</dd>\s*)+\s*</dl>\s*)+</div>)#ims', '<div class="protected inherited_constants">\1', $content);
	
	file_put_contents($docs_dir . $doc, $content);
	
	
	// Compile a list of dependencies
	preg_match('#^(f\w+)#', $doc, $filename_info);
	if (!empty($filename_info[1])) {
		$dependencies[$filename_info[1]] = array();
		
		preg_match('#<div class="uses">.*?<ul>(.*?)</ul>.*?</div>#is', $content, $list_items);
		if ($list_items) {
			preg_match_all('#>(f\w+)</a>#', $list_items[1], $uses, PREG_SET_ORDER);
			
			foreach ($uses as $use) {
				$dependencies[$filename_info[1]][] = $use[1];	 		
			}
		}
		
		preg_match('#<pre class="class_tree">(.*?)</pre>#is', $content, $tree_classes);
		if ($tree_classes) {
			preg_match_all('#>(f\w+)</a>#', $tree_classes[1], $inherits, PREG_SET_ORDER);
			foreach ($inherits as $inherit) {
				$dependencies[$filename_info[1]][] = $inherit[1];	 		
			}
		}		
	}
	
	unset($content);
}
$postprocessing_time = microtime(TRUE) - $postprocessing_start;
echo "done (" . round($postprocessing_time, 2) . " seconds)\n";
 
// Expand the dependencies for all files
$expanded_dependencies = array();
foreach ($dependencies as $class => $depend_classes) {
	$found_deps = array();
	$parsed_deps = array();
	find_dependencies($class, $found_deps, $parsed_deps, $dependencies);
	$expanded_dependencies[$class] = $found_deps; 	
	sort($expanded_dependencies[$class]);	
}


file_put_contents($docs_dir . 'dep_tree.json', json_encode($expanded_dependencies));

function find_dependencies($class, &$found_deps, &$parsed_deps, &$all_deps)
{
	$parsed_deps[$class] = TRUE;
	if (!in_array($class, $found_deps)) {
		$found_deps[] = $class;
	}
	
	if (isset($all_deps[$class])) {
		foreach ($all_deps[$class] as $dep) {
			if (isset($parsed_deps[$dep])) {
				continue;	
			}
			
			find_dependencies($dep, $found_deps, $parsed_deps, $all_deps);
		}
	}
}

`rm -Rf {$tmp_dir}export`;
`rm -Rf {$tmp_dir}output`;
`mv {$tmp_dir}docs/*.html $tmp_dir`;
`rm -Rf {$tmp_dir}docs`;

echo 'Generated docs in ' . round(microtime(TRUE) - $start, 2) . ' seconds' . "\n";
if (!isset($argv[1])) {
	echo "Output HTML in: $tmp_dir\n";
}