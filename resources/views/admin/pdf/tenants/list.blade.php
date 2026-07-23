<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Total Tenants List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body { font-size: 12px; font-family: 'Arial', sans-serif; background-color: #f9f9f9; color: #333; }

        /* Header Styling */
        .header {
            background: linear-gradient(to right, #800000, #A52A2A); /* Mehroon Gradient */
            color: #fff; padding: 15px;
            border-bottom: 4px solid #650000;
            box-shadow: 0px 4px 6px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header img { height: 40px; width: auto; }
        .header .datetime { font-size: 14px; color: black; text-align: right; width: 100%; }

        /* Table Styling */
        .table { width: 100%; border-collapse: collapse; }
        .table th { background-color: #800000; color: white; padding: 10px; text-transform: uppercase; }
        .table tr:nth-child(even) { background-color: #f4f4f4; }
        .table tr:nth-child(odd) { background-color: #ffffff; }
        .table td { padding: 8px; border: 1px solid #ddd; }

        /* Footer Styling */
        .footer {
            background: #f1f1f1;
            color: #333;
            padding: 15px;
            text-align: center;
            font-size: 10px;
            margin-top: 20px;
            border-top: 4px solid #800000;
        }

        /* Responsive Table */
        .table-responsive { overflow-x: auto; }
    </style>
</head>
<body>

    <!-- Header Section -->
    <div class="header">
        <img src="{{ public_path('assets/images/logo-dark.png') }}" alt="Company Logo">
        <div class="datetime">
            <p><strong>Date & Time:</strong> {{ \Carbon\Carbon::now()->format('F j, Y, g:i a') }}</p>
        </div>
    </div>

    <!-- Title -->
    <div class="text-center my-3">
        <h4 style="color: #800000; font-weight: bold;">Total {{ $totalTenants }} Tenants in record</h4>
    </div>

    <!-- Data Table -->
    <div class="container-fluid">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tenants as $index => $tenant)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $tenant->name }}</td>
                        <td>{{ $tenant->email }}</td>
                        <td>{{ $tenant->phone }}</td>
                        <td>{{ $tenant->address }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>
            <i class="ri-map-pin-line"></i> 123 Street, City, Country |
            <i class="ri-mail-line"></i> info@gmail.com |
            <i class="ri-global-line"></i> www.company.com |
            &copy; {{ date('Y') }} Company Name. All rights reserved.
        </p>
    </div>

</body>
</html>
