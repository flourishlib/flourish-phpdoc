<h1>Class Tree</h1>
<h2>Classes</h2>
{if $classtrees}
<ul>
{section name=classtrees loop=$classtrees}
<li>{$classtrees[classtrees].class}
{$classtrees[classtrees].class_tree}
</li>
{/section}
</ul>
{/if}
{if $interfaces}
<h2>Interfaces</h2>
<ul>
{section name=classtrees loop=$interfaces}
{$interfaces[classtrees].class_tree}
{/section}
</ul>
{/if}