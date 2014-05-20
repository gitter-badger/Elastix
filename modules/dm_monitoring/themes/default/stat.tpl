<table width="100%" class="inline">
    <thead>
    <tr>
        <th width="80%"><strong>Status</strong></th><th width="20%"><strong>Count</strong></th>
    </tr>
    </thead>

    {foreach from=$stat key=k item=v}
    <tr>
        <td align="center">{$k}</td>
        <td align="center">{$v}</td>
    </tr>
    {/foreach}
</table>