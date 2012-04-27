{assign var="version" value=""}
{assign var="copyright" value=""}
{assign var="is_abstract" value=""}
{assign var="authors" value=""}
{assign var="license" value=""}
{assign var="todo" value=""}
{assign var="changes" value=""}
{assign var="link" value=""} 
{assign var="static_class" value=""}

{counter name="total_authors" start=0 assign="total_authors"}
{counter name="total_uses" start=0 assign="total_uses"}
{counter name="total_usedby" start=0 assign="total_usedby"}

{counter name="total_methods" start=0 assign="total_methods"} 
{counter name="total_static_methods" start=0 assign="total_static_methods"} 

{section name=methods loop=$methods}
	{if $methods[methods].static}
		{counter name="total_static_methods"}
	{/if}
	{if !$methods[methods].static}
		{counter name="total_methods"}
	{/if}
{/section}

{counter name="total_vars" start=0 assign="total_vars"} 
{counter name="total_static_vars" start=0 assign="total_static_vars"}  

{section name=vars loop=$vars}
	{if $vars[vars].static}
		{counter name="total_static_vars"}
	{/if}
	{if !$vars[vars].static}
		{counter name="total_vars"}
	{/if}
{/section}

{if (!$iconsts || count($iconsts) == 0) && (!$ivars || count($ivars) == 0) && (!$imethods || count($imethods) == 0) && $total_vars == 0 && $total_methods == 0 && ($total_static_vars > 0 || $total_static_methods > 0)}
	{assign var="static_class" value="true"}
{/if}

{section name=tag loop=$tags}
  {if $tags[tag].keyword eq "version"}
	{assign var="version" value=$tags[tag].data}
  {/if}
  {if $tags[tag].keyword eq "copyright"}
	{assign var="copyright" value=$tags[tag].data}
  {/if}
  {if $tags[tag].keyword eq "abstract"}
	{assign var="is_abstract" value="true"}
  {/if}
  {if $tags[tag].keyword eq "license"}
	{assign var="license" value=$tags[tag].data}
  {/if}
  {if $tags[tag].keyword eq "link"}
	{assign var="link" value=$tags[tag].data}
  {/if}
  {if $tags[tag].keyword eq "changes"}
	{assign var="changes" value="$changes;`$tags[tag].data`;"}
  {/if}
  {if $tags[tag].keyword eq "todo"}
	{assign var="todo" value=";`$tags[tag].data`;$todo"}
  {/if} 
  {if $tags[tag].keyword eq "author"}
	{if $total_authors > 0}{assign var="authors" value="$authors,"}{/if}
	{assign var="authors" value="$authors `$tags[tag].data`"}
	{counter name="total_authors"}
  {/if}
  {if $tags[tag].keyword eq "uses"}
	{counter name="total_uses"}
  {/if}
  {if $tags[tag].keyword eq "usedby"}
	{counter name="total_usedby"}
  {/if}
{/section}

<h1>{$class_name}</h1>

<div class="sidebar">
<div class="resources">
	<h2>Class Resources</h2>
	<ul>
		<li><a href="/docs/{$class_name}">Class Documentation</a></li>
		<li><strong><a href="/api/{$class_name}">API Reference</a></strong></li>
		<li><a href="https://github.com/flourishlib/flourish-classes/blob/master{$source_location}">Source Code</a></li>
	</ul>
</div>
<!-- end resources -->

{if count($contents.const) > 0}
<div class="consts">
<h2><a href="#class_consts">Constants</a></h2>
<ul>
  {section name=contents loop=$contents.const}
  <li>{$contents.const[contents].link}</li>
  {/section}
</ul>
</div>
{/if}

{if $total_static_vars > 0}
<div class="variables static">
<h2><a href="#class_vars_static">Static Variables</a></h2>
<ul>
  {section name=contents loop=$contents.var}
	 {if $contents.var[contents].static}
		<li>{$contents.var[contents].link}</li>
	 {/if}
  {/section}
</ul>
</div>
{/if}

{if $total_vars > 0}
<div class="variables">
<h2><a href="#class_vars">Variables</a></h2>
<ul>
  {section name=contents loop=$contents.var}
	 {if !$contents.var[contents].static}
		<li>{$contents.var[contents].link}</li>
	 {/if}
  {/section}
</ul>
</div>
{/if}

{if $total_static_methods > 0}
<div class="methods static">
<h2><a href="#class_methods_static">Static Methods</a></h2>
<ul>
  {section name=contents loop=$contents.method}
	{if $contents.method[contents].static}
		<li>{$contents.method[contents].link}</li>
	{/if}
  {/section}
</ul>
</div>
{/if}

{if $total_methods > 0}
<div class="methods">
<h2><a href="#class_methods">Methods</a></h2>
<ul>
  {section name=contents loop=$contents.method}
	{if !$contents.method[contents].static}
		<li>{$contents.method[contents].link}</li>
	{/if}
  {/section}
</ul>
</div>
{/if}

{if $total_uses > 0}
<div class="uses">
<h2>Uses</h2>
<ul>
  {section name=tag loop=$tags} 
	{if $tags[tag].keyword eq "uses"}
	<li>{$tags[tag].data}</li>
	{/if}
  {/section}
</ul>
</div>
{/if}

{if $total_usedby > 0}
<div class="usedby">
<h2>Used By</h2>
<ul>
  {section name=tag loop=$tags} 
	{if $tags[tag].keyword eq "usedby"}
	<li>{$tags[tag].data}</li>
	{/if}
  {/section}
</ul>
</div>
{/if}
</div>

<div class="meta">
	<span class="type">{if $is_abstract}abstract {/if}{if $static_class == "true"}static {/if}{if $is_interface}interface{else}class{/if}</span>,
	{if $implements}<span class="implements"> implements {foreach item="int" from=$implements}{$int}{/foreach}</span>,{/if}
	{if $version}<span class="version">v{$version}</span>{/if}
</div>

{if $conflicts.conflict_type}<div class="warning">Conflicts with classes:<br />
	{section name=me loop=$conflicts.conflicts}
	{$conflicts.conflicts[me]}<br />
	{/section}
</div>
{/if}

{if $sdesc}
<p class="short_description">{$sdesc}</p>
{/if}

{if $desc}
<div class="description">{$desc}</div>
{/if}

{if $internal}
<div class="internal_notice">
	<p>
		<em>Please note: this class is primarily intended for internal use
		by Flourish and will normally not be useful in site/application code</em>
	</p>
</div>
{/if}

{if $tutorial}
<h4 class="classtutorial">{if $is_interface}Interface{else}Class{/if} Tutorial:</h4>
<ul>
	<li>{$tutorial}</li>
</ul>
{/if}
{*
<p class="source_file">
	<strong>Source file:</strong> <a href="https://github.com/flourishlib/flourish-classes/blob/master{$source_location}#L{if $class_slink}{$class_slink}{else}{$line_number}{/if}">{$source_location}</a> (line {if $class_slink}{$class_slink}{else}{$line_number}{/if})
</p>

{if $link}
<p class="wiki_docs"><strong>Class docs:</strong> {$link}</p>
{/if}
*}
{*
{if $copyright}
<p class="copyright"><strong>Copyright:</strong> {$copyright}</p>
{/if}

{if $license}
<p class="license"><strong>License:</strong> {$license}</p>
{/if}

{if $authors} 
<p class="author"><strong>Author{if $total_authors > 1}s{/if}:</strong> {$authors}</p>
{/if}

{if $version}
<p class="version"><strong>Version:</strong> {$version}</p>
{/if}

{if $implements}
<div class="implements">
	<strong>Implements:</strong>
	<ul>
		{foreach item="int" from=$implements}<li>{$int}</li>{/foreach}
	</ul>
</div>
{/if}

{if $uses}
<p class="uses"><strong>Uses:</strong> {$uses}</p>
{/if}

{if $usedby}
<p class="usedby"><strong>Used by:</strong> {$usedby}</p>
{/if}
*}


{if $changes}
<div class="changes"><strong>Changes:</strong> {$changes}</div>
{/if}

{if $todo}
<div class="todo"><strong>Todo:</strong> {$todo}</div>
{/if}


{if ($class_tree.classes && count($class_tree.classes) > 1) || ($children && count($children) > 0) || ($iconsts && count($iconsts) > 0) || ($ivars && count($ivars) > 0) || ($imethods && count($imethods) > 0)}
<div class="genealogy">	
	<h2>Genealogy</h2>
	
	{if $class_tree.classes && count($class_tree.classes) > 1}
	<div class="class_tree">
		<h3>Class Tree</h3>
		<pre class="class_tree">{section name=tree loop=$class_tree.classes}{$class_tree.classes[tree]}{$class_tree.distance[tree]}{/section}</pre>
	</div>
	{/if}
	
	{if $children}
	<div class="child_classes">
	<h3>Child Classes</h3>
	<dl>
	{section name=kids loop=$children}
	<dt>{$children[kids].link}</dt>
	<dd>{$children[kids].sdesc}</dd>
	{/section}
	</dl>
	</div>
	{/if}
	
	{if $iconsts && count($iconsts) > 0}
	<div class="inherited_constants">
	<h3>Inherited Constants</h3>
	{section name=iconsts loop=$iconsts}
	<dl>
	{section name=iconsts2 loop=$iconsts[iconsts].iconsts}
	<dt class="{$iconsts[iconsts].iconsts[iconsts2].access}">{$iconsts[iconsts].iconsts[iconsts2].link}</dt>
	<dd class="{$iconsts[iconsts].iconsts[iconsts2].access}">{$iconsts[iconsts].iconsts[iconsts2].sdesc} </dd>
	{/section}
	</dl>
	{/section}
	</div>
	{/if}
	
	{if $ivars && count($ivars) > 0}
	<div class="inherited_variables">
	<h3>Inherited Variables</h3>
	{section name=ivars loop=$ivars}
	<dl>
	{section name=ivars2 loop=$ivars[ivars].ivars}
	<dt class="{$ivars[ivars].ivars[ivars2].access}">{$ivars[ivars].ivars[ivars2].link}</dt>
	<dd class="{$ivars[ivars].ivars[ivars2].access}">{$ivars[ivars].ivars[ivars2].sdesc} </dd>
	{/section}
	</dl>
	{/section}
	</div>
	{/if}
	
	{if $imethods && count($imethods) > 0}
	<div class="inherited_methods">
	<h3>Inherited Methods</h3>
	{section name=imethods loop=$imethods}
	<dl>
	  {section name=im2 loop=$imethods[imethods].imethods}
	  <dt class="{$imethods[imethods].imethods[im2].access}">{$imethods[imethods].imethods[im2].link}</dt>
	  <dd class="{$imethods[imethods].imethods[im2].access}">{$imethods[imethods].imethods[im2].sdesc}</dd>
	  {/section}
	</dl>
	{/section}
	</div>
	{/if}
</div>	
{/if}

{if $consts && count($consts) > 0}
<div class="class_consts" id="class_consts">
<h2>Constants</h2>
{include file="const.tpl"}
</div>
{/if}

{if $total_static_vars > 0}
<div class="class_variables static" id="class_vars_static">
<h2>Static Variables</h2>
{include file="var_static.tpl"}
</div>
{/if}

{if $total_vars > 0}
<div class="class_variables" id="class_vars">
<h2>Variables</h2>
{include file="var.tpl"}
</div>
{/if}

{if $total_static_methods > 0}
<div class="class_methods static" id="class_methods_static">
<h2>Static Methods</h2>
{include file="method_static.tpl"}
</div>
{/if}

{if $total_methods > 0}
<div class="class_methods" id="class_methods">
<h2>Methods</h2>
{include file="method.tpl"}
</div>
{/if}
