<table width="30%" class="inline">
    <thead>
    <tr>
        <th width="80%"><strong>{$Status}</strong></th>
        <th width="20%"><strong>{$Count}</strong></th>
    </tr>
    </thead>

    {foreach from=$stat item=v}
    <tr>
        <td>{$v.status}</td>
        <td align="center">{$v.count}</td>
    </tr>
    {/foreach}
</table>