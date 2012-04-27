{section name=consts loop=$consts}
{if $show == 'summary'}
	<p>var {$consts[consts].const_name}, {$consts[consts].sdesc}</p>
{else}

{assign var="since" value=""}

{section name=tag loop=$consts[consts].tags}
  {if $consts[consts].tags[tag].keyword eq "since"}
	{assign var="since" value=$consts[consts].tags[tag].data}
  {/if}
{/section}
<!-- start {if $consts[consts].internal}protected {/if}constant -->
<h3 id="{$consts[consts].const_dest}" class="{if $consts[consts].internal}protected {/if}constant"><span class="resolution">::</span><span class="name">{$consts[consts].const_name}</span>{if $consts[consts].internal} <span class="visibility">internal</span>{/if}{*<a href="#top" class="top">Top</a>*}</h3>

<div class="{if $consts[consts].internal}protected {/if}constant">
	
	
	{if $consts[consts].internal}
	<div class="internal_notice">
		<p>
			<em>Please note: this constant is primarily intended for internal 
			use by Flourish and will normally not be useful in site/application
			code</em>
		</p>
	</div>
	{/if}
	
	{if $consts[consts].desc || $consts[consts].sdesc }
		<div class="constant_description">
		{if $consts[consts].sdesc}
			<p>{$consts[consts].sdesc}</p>
		{/if}
		{$consts[consts].desc}
		</div>
	{/if}
	
</div>
<!-- end constant -->
{/if}
{/section}
