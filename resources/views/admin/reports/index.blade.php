<x-layouts.app title="Reports — Admin">
    <x-navbar variant="full" />

    <main class="admin">
        <div class="container admin-inner">
            <x-admin-sidebar active="reports" />

            <div class="admin-content">
                <h1 class="admin-heading">Reports</h1>

                <x-flash-status />

                <form method="GET" action="{{ route('admin.reports.index') }}" class="admin-filter-form">
                    <select name="type" class="input-field-input">
                        @foreach ($types as $key => $label)
                            <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <input type="date" name="from" value="{{ $from }}" class="input-field-input">
                    <input type="date" name="to" value="{{ $to }}" class="input-field-input">

                    <button type="submit" class="btn btn-primary">Run Report</button>
                    <a href="{{ route('admin.reports.export', request()->only(['type', 'from', 'to'])) }}" class="btn btn-outline">Export CSV</a>
                </form>

                @if ($rows->isEmpty())
                    <x-empty-state title="No data for this report" message="Try a different date range." />
                @else
                    <table class="product-table">
                        <thead>
                            <tr>
                                @foreach (array_keys($rows->first()) as $column)
                                    <th>{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr>
                                    @foreach ($row as $value)
                                        <td>{{ $value }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </main>
</x-layouts.app>
