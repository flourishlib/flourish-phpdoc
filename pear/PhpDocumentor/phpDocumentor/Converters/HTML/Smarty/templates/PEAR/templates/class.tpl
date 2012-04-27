{include file="header.tpl" eltype="class" hasel=true contents=$classcontents}

{if $conflicts.conflict_type}<p class="warning">Conflicts with classes:<br />
	{section name=me loop=$conflicts.conflicts}
	{$conflicts.conflicts[me]}<br />
	{/section}
<p>
	{/if}

<div class="leftcol">
	<h3><a href="#class_details">Class Overview</a> <span class="smalllinenumber">[line {if $class_slink}{$class_slink}{else}{$line_number}{/if}]</span></h3>
	<div id="classTree"><pre>{section name=tree loop=$class_tree.classes}{$class_tree.classes[tree]}{$class_tree.distance[tree]}{/section}</pre>
</div>
	<div class="small">
	<p>{$sdesc|default:''}</p>
	{if $tutorial}
	<h4 class="classtutorial">Class Tutorial:</h4>
	<ul>
		<li>{$tutorial}</li>
	</ul>
	{/if}
	<h4>Author(s):</h4>
	<ul>
		{section name=tag loop=$tags}
			{if $tags[tag].keyword eq "author"}
			<li>{$tags[tag].data}</li>
			{/if}
		{/section}
	</ul>
	<h4>Version:</h4>
	<ul>
		{section name=tag loop=$tags}
			{if $tags[tag].keyword eq "version"}
			<li>{$tags[tag].data}</li>
			{/if}
		{/section}
	</ul>

	<h4>Copyright:</h4>
	<ul>
		{section name=tag loop=$tags}
			{if $tags[tag].keyword eq "copyright"}
			<li>{$tags[tag].data}</li>
			{/if}
		{/section}
	</li>
	</div>
</div>

<div class="middlecol">
	<h3><a href="#class_vars">Variables</a></h3>
	<ul class="small">
		{section name=contents loop=$contents.var}
		<li>{$contents.var[contents]}</li>
		{/section}
	</ul>
</div>
<div class="rightcol">
	<h3><a href="#class_methods">Methods</a></h3>
	<ul class="small">
		{section name=contents loop=$contents.method}
		<li>{$contents.method[contents]}</li>
		{/section}
	</ul>
</div>

<div id="content">
<hr>
	<div class="contents">
{if $children}
	<h2>Child classes:</h2>
	{section name=kids loop=$children}
	<dl>
	<dt>{$children[kids].link}</dt>
		<dd>{$children[kids].sdesc}</dd>
	</dl>
	{/section}</p>
{/if}
	</div>

	<div class="leftCol">
	<h2>Inherited Variables</h2>
	{section name=ivars loop=$ivars}
		<div class="indent">
		<h3>Class: {$ivars[ivars].parent_class}</h3>
		<div class="small">
			<dl>
			{section name=ivars2 loop=$ivars[ivars].ivars}
			<dt>
				{$ivars[ivars].ivars[ivars2].link}
			</dt>
			<dd>
				{$ivars[ivars].ivars[ivars2].ivars_sdesc} 
			</dd>
			{/section}
			</dl>
		</div>
		</div>
	{/section}
	</div>

	<div class="rightCol">
	<h2>Inherited Methods</h2>
	{section name=imethods loop=$imethods}
		<div class="indent">
		<h3>Class: {$imethods[imethods].parent_class}</h3>
		<dl class="small">
			{section name=im2 loop=$imethods[imethods].imethods}
			<dt>
				{$imethods[imethods].imethods[im2].link}
			</dt>
			<dd>
				{$imethods[imethods].imethods[im2].sdesc}
			</dd>
		{/section}
		</dl>
		</div>
	{/section}
	</div>
	<br clear="all">
	<hr>

	<a name="class_details"></a>
	<div class="sectionHeader">
		<h2>Class Details</h2>
	</div>
	<div class="sectionNav"><a href="class_overview">Class Overview</a> | <a href="#class_vars">Class Variables"</a> <a href="#class_methods">Class Methods</a> | <a href="#top">Top</a></div>
	<div class="sectionBody">
	{include file="docblock.tpl" type="class" sdesc=$sdesc desc=$desc}
	</div>

	<a name="class_vars"></a>
	<div class="sectionHeader">
		<h2>Class Variables</h2>
	</div>
	<div class="sectionNav"><a href="class_overview">Class Overview</a> | <a href="#class_vars">Class Variables"</a> <a href="#class_methods">Class Methods</a> | <a href="#top">Top</a></div>
	<div class="sectionBody">
	{include file="var.tpl"}
	</div>

	<a name="class_methods"></a>
	<div class="sectionHeader">
		<h2>Class Methods</h2>
	</div>
	<div class="sectionNav"><a href="class_overview">Class Overview</a> | <a href="#class_vars">Class Variables"</a> <a href="#class_methods">Class Methods</a> | <a href="#top">Top</a></div>
	<div class="sectionBody">
	{include file="method.tpl"}
	</div>
</div>
{include file="footer.tpl"}
