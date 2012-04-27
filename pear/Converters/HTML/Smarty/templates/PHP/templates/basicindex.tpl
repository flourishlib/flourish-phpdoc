<p class="section_links">
{section name=index loop=$index}
	<a href="#{$index[index].letter}" class="section_link">{$index[index].letter}</a>
{/section}
</p>

{section name=index loop=$index}
	<a name="{$index[index].letter}"></a>
	<div class="section">
		<h2>{$index[index].letter}</h2>
		<dl>
			{section name=contents loop=$index[index].index}
				<dt>{$index[index].index[contents].name}</dt>
				<dd>{$index[index].index[contents].listing}</dd>
			{/section}
		</dl>
		<a href="#top" class="top">Top</a>
	</div>
{/section}
