<h1>Todo List</h1>
{foreach from=$todos key=todopackage item=todo}
	{section name=todo loop=$todo}
		<h2>{$todo[todo].link}</h2>
		<ul>
		{section name=t loop=$todo[todo].todos}
			<li>{$todo[todo].todos[t]}</li>
		{/section}
		</ul>
	{/section}
{/foreach}