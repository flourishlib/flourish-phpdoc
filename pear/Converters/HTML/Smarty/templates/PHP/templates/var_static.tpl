{section name=vars loop=$vars}
{if $vars[vars].static}
{if $show == 'summary'}
	<p>static var {$vars[vars].var_name}, {$vars[vars].sdesc}</p>
{else}
	{assign var="since" value=""}
	{assign var="access" value=""}

	{section name=tag loop=$vars[vars].tags}
	  {if $vars[vars].tags[tag].keyword eq "since"}
		{assign var="since" value=$vars[vars].tags[tag].data}
	  {/if}
	  {if $vars[vars].tags[tag].keyword eq "access"}
		{assign var="access" value=$vars[vars].tags[tag].data}
	  {/if}
	{/section}
	
	<!-- start {if $access == 'protected' || $vars[vars].internal}protected {/if}variable static -->
	<h3 id="{$vars[vars].var_dest}" class="{if $access == 'protected' || $vars[vars].internal}protected {/if}variable static"><span class="resolution">::</span><span class="name">{$vars[vars].var_name}</span> <span class="visibility">{if $vars[vars].internal}internal {/if}{$access}</span>{*<a href="#top" class="top">Top</a>*}</h3>
	<div class="{if $access == 'protected' || $vars[vars].internal}protected {/if}variable static">    


		{if $vars[vars].internal}
		<div class="internal_notice">
			<p>
				<em>Please note: this variable is <strong>public</strong>, however it is
				primarily intended for internal use by Flourish and will normally not
				be useful in site/application code</em>
			</p>
		</div>
		{/if}
		
		{if $vars[vars].desc || $vars[vars].sdesc }
			<div class="variable_description">
			{if $vars[vars].sdesc}
				<p>{$vars[vars].sdesc}</p>
			{/if}
			{$vars[vars].desc}
			</div>
		{/if}
		
		<h4>Type</h4>
		<p class="type">{$vars[vars].var_type}</p>

		{if $vars[vars].var_overrides != ""}
		<h4>Overrides</h4>
		<p class="overrides">{$vars[vars].var_overrides}</p>
		{/if}
		
		
	</div>
	<!-- end variable static -->
{/if}
{/if}
{/section}