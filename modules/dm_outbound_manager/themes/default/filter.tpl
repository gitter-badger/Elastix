<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr class="letra12" id="r1">
        <td width="20%" align="right">
            {$campaign.LABEL}:
            {$campaign.INPUT}
        </td>
        <td width="10%" align="left">
            <input class="button" type="submit" name="show" value="{$Show}" />
        </td>
        {if $Selected neq "0"}
        <td width="10%" align="left">
            <input class="button" style="text-align: center" onclick="loadForm(0)" value="{$AddForm}"/>
        </td>

        <td width="20%" align="right">
            <small>
                <a id="check-all" href="javascript:void(0);">{$check_all}</a>
                <a id="uncheck-all" href="javascript:void(0);">{$uncheck_all}</a>
            </small>
        </td>
            <td width="10%" align="left">
                <a href="#" onclick="deleteForm()">{$Delete}</a>
            </td>
        {/if}
        <td>
        </td>
    </tr>
</table>

<div id="edit_form" style="display: none;">
    <div id="form_content" style="overflow-y: auto; overflow-x: visible; bottom: 20px; position: absolute; top: 40px; width: 93%;">
        Wait please...
    </div>
    <div id="close_form" style="text-align: right">
        <input class="button" style="width: 20px; height: 20px" onclick="closeForm()" value=" X "/>
    </div>
</div>