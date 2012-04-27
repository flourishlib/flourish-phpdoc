{if $sdesc != ''}<p class="short_description">{$sdesc|default:''}</p>{/if}
{if $desc != ''}<div class="description">{$desc|default:''}</div>{/if}
{if count($tags) > 0}
<h4>Tags:</h4>
<table class="tags">
{section name=tag loop=$tags}
  <tr>
    <th>{$tags[tag].keyword}:</th><td>{$tags[tag].data}</td>
  </tr>
{/section}
</table>
{/if}
