<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Holidays {{ $year }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .holiday-table { width: 100%; border-collapse: collapse; }
        .holiday-table th, .holiday-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .holiday-table th { background-color: #f2f2f2; font-weight: bold; }
        .category { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .category-national { background-color: #e3f2fd; color: #1976d2; }
        .category-religious { background-color: #f3e5f5; color: #7b1fa2; }
        .category-company-specific { background-color: #e8f5e8; color: #388e3c; }
        .category-regional { background-color: #fff3e0; color: #f57c00; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Company Holidays - {{ $year }}</h1>
        <p>Generated on {{ date('F j, Y') }}</p>
    </div>

    <table class="holiday-table">
        <thead>
            <tr>
                <th>Holiday Name</th>
                <th>Date</th>
                <th>Category</th>
                <th>Type</th>
                <th>Branches</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($holidays as $holiday)
            <tr>
                <td>{{ $holiday->name }}</td>
                <td>
                    @if($holiday->end_date && $holiday->start_date !== $holiday->end_date)
                        {{ \Carbon\Carbon::parse($holiday->start_date)->format('M j, Y') }} - 
                        {{ \Carbon\Carbon::parse($holiday->end_date)->format('M j, Y') }}
                    @else
                        {{ \Carbon\Carbon::parse($holiday->start_date)->format('M j, Y') }}
                    @endif
                </td>
                <td>
                    <span class="category category-{{ $holiday->category }}">
                        {{ ucfirst(str_replace('-', ' ', $holiday->category)) }}
                    </span>
                </td>
                <td>
                    @if($holiday->is_half_day) Half Day, @endif
                    {{ $holiday->is_paid ? 'Paid' : 'Unpaid' }}
                    @if($holiday->is_recurring), Recurring @endif
                </td>
                <td>
                    @if($holiday->branches->count() > 0)
                        {{ $holiday->branches->pluck('name')->join(', ') }}
                    @else
                        All Branches
                    @endif
                </td>
                <td>{{ $holiday->description ?: '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($holidays->count() === 0)
        <p style="text-align: center; margin-top: 50px; color: #666;">No holidays found for {{ $year }}.</p>
    @endif
</body>
</html>