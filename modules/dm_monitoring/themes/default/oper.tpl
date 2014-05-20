<table width="100%" class="tabForm">
    <tbody>
    <tr>
        <td><strong>Waiting responce</strong></td><td><strong>Agents Status</strong></td>
    </tr>
    </tbody>
    <tr>
        <td width="50%" valign="top" style="border: 2px solid black">
            <table width="100%" class="inline">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Number</th>
                        <th>Trunk</th>
                        <th>Start</th>
                    </tr>
                </thead>

                {foreach from=$activecalls key=k item=v}
                    <tr style="border: 1px solid grey">
                        <td >{$v.callstatus}</td>
                        <td style="border: 1px solid grey">{$v.callnumber}</td>
                        <td style="border: 1px solid grey">{$v.trunk}</td>
                        <td style="border: 1px solid grey">{$v.queuestart}{$v.dialend}</td>
                    </tr>
                {/foreach}
            </table>
        </td>
        <td width="50%" valign="top" style="border: 2px solid black">
            <table width="100%" class="inline">
                <thead>
                    <tr>
                        <th>Agent</th>
                        <th>Status</th>
                        <th>CallNumber</th>
                        <th>Trunk</th>
                        <th>Start</th>
                    </tr>
                </thead>
                {foreach from=$agents key=k item=v}
                    <tr>
                        <td style="border: 1px solid grey">{$k}</td>
                        <td style="border: 1px solid grey">{$v.status}</td>
                        <td style="border: 1px solid grey">{$v.callnumber}</td>
                        <td style="border: 1px solid grey">{$v.trunk}</td>
                        <td style="border: 1px solid grey">{$v.pausestart}{$v.linkstart}{$v.dialstart}</td>
                    </tr>
                {/foreach}
            </table>
        </td>
    </tr>
</table>