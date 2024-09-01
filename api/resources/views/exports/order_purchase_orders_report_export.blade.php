<html>

<body>
    <table>
        <thead>
            <tr>
                <th><b>NUMBERS</b></th>
                <th><b>RESOURCE</b></th>
                <th><b>DATE</b></th>
                <th><b>DELIVERY DATE</b></th>
                <th><b>STATUS</b></th>
                <th><b>PRICE</b></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchase_orders as $key => $po)
                <tr>
                    <td>
                        {{ $po['VAR_PO_NUMBER'] }}
                    </td>
                    <td>
                        {{ $po['VAR_PO_RESOURCE'] }}
                    </td>
                    <td>
                        {{ $po['VAR_PO_DATE'] }}
                    </td>
                    <td>
                        {{ $po['VAR_PO_DELIVERY_DATE'] }}
                    </td>
                    <td>
                        {{ $po['VAR_PO_STATUS'] }}
                    </td>
                    <td>
                        {{ $po['VAR_PO_PRICE'] }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
