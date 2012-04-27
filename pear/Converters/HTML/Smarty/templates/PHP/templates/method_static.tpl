{section name=methods loop=$methods}
	{if $methods[methods].static}
		{if $show == 'summary'}
			<p>static method {$methods[methods].function_call}, {$methods[methods].sdesc}</p>
		{else}
			{assign var="method_num" value=$smarty.section.methods.iteration}
			{assign var="return" value=""}
			{assign var="since" value=""}
			{assign var="access" value=""}
			{assign var="throws" value=""}
			{counter name="total_throws" start=0 assign="total_throws"}   

			{php}
				$methods     = $this->get_template_vars('methods');
				$method_num  = $this->get_template_vars('method_num');
				$params      = $methods[$method_num-1]['ifunction_call']['params'];
				$params_list = $methods[$method_num-1]['params'];
				
				if (!$params) { $params = array(); }
				if (!$params_list) { $params_list = array(); }
				
				$new_params      = array();
				$alt_params      = array();
				$new_params_list = array();
				$defaults        = array();
				$remove_default  = false;
				
				foreach ($params as $i => $param) {
					$param_name = $param['name'];
					if (is_numeric($param_name) ) {
						list ($param_name, $description) = preg_split('#\s+#', $param['description'], 2);
						$param['description'] = $description;
						$param['name']        = $param_name;
					}
					if (strpos($param['name'], '=') !== FALSE) {
						list ($param_name, $default) = explode('=', $param['name']);
						$param['name'] = $param_name;
						$param['hasdefault'] = TRUE;
						$param['default'] = $default;
					}
					if ($param_name[0] == '$' || $param_name[0] == '&' || $param_name[0] == '.') {
						$new_params[] = $param;
						if ($param['hasdefault']) {
							$defaults[$param['name']] = $param['default'];
						}
					} else {
						if ($param_name[0] == ':') {
							$remove_default = true;
						}
						$param['name'] = substr($param_name, 1);
						if ($param_name[0] != ':' && isset($defaults[$param['name']])) {
							$param['hasdefault'] = 1;
							$param['default'] = $defaults[$param['name']];
						}
						$alt_params[] = $param;
					}	
				}
				
				if ($alt_params && $remove_default) {
					$remove_defaults = array_slice(array_keys($new_params), sizeof($alt_params));
					foreach ($remove_defaults as $remove_default) {
						$new_params[$remove_default]['hasdefault'] = FALSE;
					}	
				}
				
				$set_params = array();
				foreach ($params_list as $param) {
					$param_name = $param['var'];
					if (is_numeric($param_name)) {
						list ($param_name, $description) = preg_split('#\s+#', $param['data'], 2);
						$param['data'] = $description;
						$param['var']  = $param_name;
					}
					if ($param_name[0] != '$' && $param_name[0] != '&' && $param_name[0] != '.') {
						$param_name = substr($param_name, 1);
						$param['var'] = $param_name;
						if (in_array($param_name, $set_params)) {
							continue;
						}
					}
					$set_params[] = $param_name;
					$new_params_list[] = $param;
				}	
				
				$this->assign('params',      $new_params);
				$this->assign('alt_params',  $alt_params);
				$this->assign('params_list', $new_params_list); 
			{/php}
			
			{section name=tag loop=$methods[methods].tags}
			  {if $methods[methods].tags[tag].keyword eq "return"}
				{assign var="return" value=$methods[methods].tags[tag].data}
			  {/if}
			  {if $methods[methods].tags[tag].keyword eq "since"}
				{assign var="since" value=$methods[methods].tags[tag].data}
			  {/if}
			  {if $methods[methods].tags[tag].keyword eq "access"}
				{assign var="access" value=$methods[methods].tags[tag].data}
			  {/if}
			  {if $methods[methods].tags[tag].keyword eq "throws"}
				{if $total_throws > 0}{assign var="throws" value="$throws"}{/if}
				{assign var="throws" value="$throws `$methods[methods].tags[tag].data`"}
				{counter name="total_throws"}
			  {/if}
			{/section}
			{if $throws ne ""}
				{assign var="throws" value="<dl class=\"throws\">$throws</dl>"}	
			{/if}
			<!-- start {if $access == 'protected' || $methods[methods].internal}protected {/if}method static -->
			
			<h3 id="{$methods[methods].method_dest}" class="{if $access == 'protected' || $methods[methods].internal}protected {/if}method static"><span class="resolution">::</span><span class="name">{$methods[methods].function_name}()</span> <span class="visibility">{if $methods[methods].internal}internal {/if}{$access}</span>{if $methods[methods].method_implements}<span class="implements"> implements {section name=imp loop=$methods[methods].method_implements}{$methods[methods].method_implements[imp].link}{/section}</span>{/if}{*<a href="#top" class="top">Top</a>*}</h3>
			
			<div class="{if $access == 'protected' || $methods[methods].internal}protected {/if}method static">
			
			{if $methods[methods].internal}
				<div class="internal_notice">
					<p>
						<em>Please note: this method is <strong>public</strong>, however it is
						primarily intended for internal use by Flourish and will normally not
						be useful in site/application code</em>
					</p>
				</div>
			{/if}
			
			{if $methods[methods].desc || $methods[methods].sdesc }
				<div class="method_description">
				{if $methods[methods].sdesc}
					<p>{$methods[methods].sdesc}</p>
				{/if}
				{$methods[methods].desc}
				</div>
			{/if}
			
			<h4>Signature{if count($alt_params)}s{/if}</h4>
			<p class="signature">
				<code><span class="php-vartype">{$methods[methods].function_return}</span> {if $methods[methods].ifunction_call.returnsref}&amp;{/if}<span class="php-identifier">{$methods[methods].function_name}</span><span class="php-brackets">(</span> {if count($params)}{section name=params loop=$params}{if $smarty.section.params.iteration != 1}<span class="php-code">,</span> {/if}<span class="php-vartype">{$params[params].type}</span> <span class="php-var">{$params[params].name}</span>{if $params[params].hasdefault}<span class="php-code">=</span>{$params[params].default}{/if}{/section}{/if} <span class="php-brackets">)</span></code>
			</p>
			{if count($alt_params)}
				<p class="signature">
					<code><span class="php-vartype">{$methods[methods].function_return}</span> {if $methods[methods].ifunction_call.returnsref}&amp;{/if}<span class="php-identifier">{$methods[methods].function_name}</span><span class="php-brackets">(</span> {if count($alt_params)}{section name=alt_params loop=$alt_params}{if $smarty.section.alt_params.iteration != 1}<span class="php-code">,</span> {/if}<span class="php-vartype">{$alt_params[alt_params].type}</span> <span class="php-var">{$alt_params[alt_params].name}</span>{if $alt_params[alt_params].hasdefault}<span class="php-code">=</span>{$alt_params[alt_params].default}{/if}{/section}{/if} <span class="php-brackets">)</span></code>
				</p>
			{/if}
			
			{if count($params_list) > 0}
			<h4>Parameters</h4>
			<div class="parameters">
			<table cellspacing="0">
			{section name=params_list loop=$params_list}
			  <tr>
				<td class="param_data_type">{$params_list[params_list].datatype}</td>
				<td class="param_name">{$params_list[params_list].var}</td>
				<td class="param_description">{$params_list[params_list].data}</td>
			  </tr>
			{/section}
			</table>
			</div>
			{/if}
			{if $return}
				<h4>Returns</h4>
				<p class="return">
					{$return}
				</p>
			{/if}
			{if $throws}
				<h4>Throws</h4>
				{$throws}
			{/if}

			{if $methods[methods].descmethod}
				<h4>Overridden By</h4>
				<div class="overridden">
					<dl>
						{section name=dm loop=$methods[methods].descmethod}
						<dt>{$methods[methods].descmethod[dm].link}</dt>
						{if $methods[methods].descmethod[dm].sdesc}
						<dd>{$methods[methods].descmethod[dm].sdesc}</dd>
						{/if}
						{/section}
					</dl>
				</div>
			{/if}
			
			{*
			{if $methods[methods].method_implements}
				<div class="implements">
					<strong>Implements:</strong>
					<dl>
						{section name=imp loop=$methods[methods].method_implements}
							<dt>{$methods[methods].method_implements[imp].link}</dt>
							{if $methods[methods].method_implements[imp].sdesc}
							<dd>{$methods[methods].method_implements[imp].sdesc}</dd>
							{/if}
						{/section}
					</dl>
				</div>
			{/if}
			*}
			
			{if $methods[methods].method_overrides}
				<h4>Overrides</h4>
				<div class="overrides">
					<dl>
						<dt>{$methods[methods].method_overrides.link}</dt>
						{if $methods[methods].method_overrides.sdesc}
						<dd>{$methods[methods].method_overrides.sdesc}</dd>
						{/if}
					</dl>
				</div>
			{/if}
			
			</div>
			<!-- end method static -->
		{/if}
		
	{/if}
{/section}