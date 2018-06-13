<tr class="klarna-creds_[{$sKey}] csc_first">
    <td colspan="3">
    <div class="rows-wrapper" style="display: block">
        <table>
            <tbody>
                <tr>
                    <td>
                        <div class="klarna-flag [{$sKey|lower}]"></div>
                        <div class="row-labels">
                            Merchant ID:
                        </div>
                    </td>

                    <td>
                        <div class="input">
                            <input type="text" class="" name="confaarrs[aKlarnaCreds_[{$sKey}]][mid]" value="[{$aValues.mid}]">
                        </div>
                    </td>
                    <td rowspan="2">
                        <a class="acc-remove" data-country="[{$sKey}]" href="#">
                            <i class="fa fa-window-close fa-2x" aria-hidden="true"></i>
                        </a>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="row-labels password">
                            Password:
                        </div>
                    </td>
                    <td class="center">
                        <div class="input" style="font-weight: normal;">
                            <input type="password" class="" name="confaarrs[aKlarnaCreds_[{$sKey}]][password]"
                                   value="[{$aValues.password}]">
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    </td>
</tr>