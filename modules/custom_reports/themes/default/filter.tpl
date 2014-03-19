<table width="99%" border="0" cellspacing="0" cellpadding="0" align="center">
    <tr class="letra12">
        <td width="12%" align="right">
            {$report.LABEL}:
        </td>
        <td width="12%" align="left" nowrap="nowrap">
            {$report.INPUT}
        </td>
        <td width="12%" align="right">
            {$queue_in.LABEL}:
        </td>
        <td width="12%" align="left" nowrap="nowrap">
            {$queue_in.INPUT}
        </td>
        <td width="10%" align="right">
            {$ivr.LABEL}:
        </td>
        <td width="10%" align="left" nowrap="nowrap">
            {$ivr.INPUT}
        </td>
        <td width="10%" align="right">
            <span class="required">*</span>{$date_start.LABEL}:
        </td>
        <td width="10%" align="left" nowrap="nowrap">
            {$date_start.INPUT}
        </td>
    </tr>
    <tr class="letra12">
        <td width="10%" align="right">
            {$span.LABEL}:
        </td>
        <td width="10%" align="left" nowrap="nowrap">
            {$span.INPUT}
        </td>
        <td width="12%" align="right">
            {$queue_out.LABEL}:
        </td>
        <td width="12%" align="left" nowrap="nowrap">
            {$queue_out.INPUT}
        </td>
        <td width="12%" align="right">
            {$agent.LABEL}:
        </td>
        <td width="12%" align="left" nowrap="nowrap">
            {$agent.INPUT}
        </td>
        <td width="10%" align="right">
            <span class="required">*</span>{$date_end.LABEL}:
        </td>
        <td width="10%" align="left" nowrap="nowrap">
            {$date_end.INPUT}
        </td>
    </tr>
</table>
<center><input class="button" type="submit" name="show" value="{$show}" /></center>
