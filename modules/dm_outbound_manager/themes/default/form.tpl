<table>
{foreach from=$FORMS key=ID_FORM item=FORM}
    <tr>
        <td>{$FORM.columna}:</td>
        <td>    <input  type="text"
                        name="{$FORM.columna}"
                        value="{$FORM.value|escape:"html"}" /></td>
    </tr>
{/foreach}
</table>
<input type="hidden" name="id_call" value="{$Id_call}"/>
<button id="btn_save" name="action" value="saveform">{$BTN_save}</button>