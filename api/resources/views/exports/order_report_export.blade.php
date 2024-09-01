<html>
<body>
    <table>
        <tbody>
            <tr>
                <td colspan="12" class="center"><b>ORDER # {{ $VAR_NUMBER }}</b></td>
            </tr>
        </tbody>
    </table>
    <table>
        <thead>
            <tr>
                <th colspan="4"><b>DESCRIPTION</b></th>
                <th colspan="2"><b>QTY</b></th>
                <th colspan="3"><b>UNIT PRICE</b></th>
                <th colspan="3"><b>PRICE IN {{ $VAR_CURRENCY }}</b></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $key => $item)
                <tr>
                    <td colspan="4" class="long-word-container">
                        {{ $item['VAR_I_DESCRIPTION'] }}
                        {{ $item['VAR_I_M_DESCRIPTION'] ?? '' }}
                    </td>
                    <td colspan="2">
                        {{ $item['VAR_I_QUANTITY'] }} {{ $item['VAR_I_UNIT'] ?? '' }}
                    </td>
                    <td colspan="3">
                        {{ $item['VAR_I_PRICE'] }}
                    </td>
                    <td colspan="3">
                        {{ $item['VAR_I_TOTAL_PRICE'] }} {{ $item['VAR_I_M_QUANTITY'] ?? '' }}
                    </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="12">

                </td>
            </tr>
            <tr>
                <td colspan="9">SUBTOTAL</td>
                <td colspan="3">{{ $VAR_TOTAL_WITHOUT_MODS }}</td>
            </tr>
            <tr>9
                <td colspan="9">SUBTOTAL AFFECTED BY PRICE MODIFIERS</td>
                <td colspan="3">{{ $VAR_TOTAL_AFFECTED_BY_MODS }}</td>
            </tr>
            <tr>
                <td colspan="9">{{ $VAR_M_DESCRIPTION ?? '' }}</td>
                <td colspan="3">{{ $VAR_M_QUANTITY ?? '' }}</td>
            </tr>
            <tr>
                <td colspan="9"><b>SUBTOTAL</b></td>
                <td colspan="3"><b>{{ $VAR_WITHOUT_VAT }}</b></td>
            </tr>
            <tr>
                <td colspan="9"><b>{{ $VAR_TRANS_FEE_LABEL }}</b></td>
                <td colspan="3"><b>{{ $VAR_TRANS_FEE }}</b></td>
            </tr>
            <tr>
                <td colspan="9"><b>VAT</b></td>
                <td colspan="3"><b>{{ $VAR_TOTAL_VAT }}</b></td>
            </tr>
            <tr>
                <td colspan="9"><b>TOTAL</b></td>
                <td colspan="3"><b>{{ $VAR_TOTAL_PRICE }}</b></td>
            </tr>
            <tr>
                <td colspan="9"><b>GROSS MARGIN</b></td>
                <td colspan="3"><b>{{ $VAR_GROSS_MARGIN }}</b></td>
            </tr>
            <tr>
                <td colspan="9"><b>MARKUP</b></td>
                <td colspan="3"><b>{{ $VAR_MARKUP }}</b></td>
            </tr>
            <tr>
                <td colspan="9"><b>POTENTIAL GROSS MARGIN</b></td>
                <td colspan="3"><b>{{ $VAR_POTENTIAL_GM }}</b></td>
            </tr>
            <tr>
                <td colspan="9"><b>POTENTIAL MARKUP</b></td>
                <td colspan="3"><b>{{ $VAR_POTENTIAL_MARKUP }}</b></td>
            </tr>
            <tr>
                <td colspan="9"><b>COSTS</b></td>
                <td colspan="3"><b>{{ $VAR_COSTS }}</b></td>
            </tr>
            <tr>
                <td colspan="9"><b>POTENTIAL COSTS</b></td>
                <td colspan="3"><b>{{ $VAR_POTENTIAL_COSTS }}</b></td>
            </tr>
        </tbody>
    </table>
</body>

</html>
