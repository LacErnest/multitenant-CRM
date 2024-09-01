<html>

<body>
    <table>
        <thead>
            <tr>
                <th><b>FIRST NAME</b></th>
                <th><b>LAST NAME</b></th>
                <th><b>EMAIL</b></th>
                <th><b>PHONE NUMBER</b></th>
                <th><b>TYPE</b></th>
                <th><b>MONTH</b></th>
                <th><b>YEARS</b></th>
                <th><b>HOURS</b></th>
                <th><b>COST</b></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($project_employees as $key => $employee)
                <tr>
                    <td>
                        {{ $employee['VAR_E_FIRST_NAME'] }}
                    </td>
                    <td>
                        {{ $employee['VAR_E_LAST_NAME'] }}
                    </td>
                    <td>
                        {{ $employee['VAR_E_EMAIL'] }}
                    </td>
                    <td>
                        {{ $employee['VAR_E_PHONE_NUMBER'] }}
                    </td>
                    <td>
                        {{ $employee['VAR_E_TYPE'] }}
                    </td>
                    <td>
                        {{ $employee['VAR_E_MONTH'] }}
                    </td>
                    <td>
                        {{ $employee['VAR_E_YEAR'] }}
                    </td>
                    <td>
                        {{ $employee['VAR_E_HOURS'] }}
                    </td>
                    <td>
                        {{ $employee['VAR_E_COST'] }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
