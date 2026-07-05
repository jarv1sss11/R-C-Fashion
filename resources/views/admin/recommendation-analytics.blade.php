<x-layouts.app title="Recommendation Analytics — Admin">
    <x-navbar variant="full" />

    <main class="admin">
        <div class="container admin-inner">
            <x-admin-sidebar active="recommendation-analytics" />

            <div class="admin-content">
                <h1 class="admin-heading">Recommendation Analytics</h1>
                <p class="admin-subheading">Read-only reporting over the recommendation engine — no algorithm changes are made here.</p>

                <x-flash-status />

                <div class="admin-summary-grid">
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $overview['generated'] }}</span>
                        <span class="admin-summary-label">Recommendations Generated</span>
                    </div>
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $overview['clicks'] }}</span>
                        <span class="admin-summary-label">Recommendation Clicks</span>
                    </div>
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $overview['ctr'] }}%</span>
                        <span class="admin-summary-label">Overall CTR</span>
                    </div>
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $overview['generated_today'] }}</span>
                        <span class="admin-summary-label">Generated Today</span>
                    </div>
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $overview['cold_start_users'] }}</span>
                        <span class="admin-summary-label">Cold Start Users</span>
                    </div>
                    <div class="admin-summary-card">
                        <span class="admin-summary-value">{{ $overview['hybrid_usage'] }}</span>
                        <span class="admin-summary-label">Hybrid Usage</span>
                    </div>
                </div>

                <h2 class="admin-subheading--section">Algorithm Usage</h2>
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>Algorithm</th>
                            <th>Times Used</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($algorithmUsage as $algorithm => $count)
                            <tr>
                                <td>{{ ucfirst($algorithm) }}</td>
                                <td>{{ $count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2">No recommendations logged yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <h2 class="admin-subheading--section">Evaluation Metrics (Leave-One-Out)</h2>
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>Algorithm</th>
                            <th>Users Evaluated</th>
                            <th>Precision@K</th>
                            <th>Recall@K</th>
                            <th>MAP@K</th>
                            <th>NDCG@K</th>
                            <th>Coverage</th>
                            <th>Diversity</th>
                            <th>Novelty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($evaluation as $algorithm => $report)
                            <tr>
                                <td>{{ ucfirst($algorithm) }}</td>
                                <td>{{ $report['users_evaluated'] }}</td>
                                <td>{{ $report['precision_at_k'] }}</td>
                                <td>{{ $report['recall_at_k'] }}</td>
                                <td>{{ $report['map_at_k'] }}</td>
                                <td>{{ $report['ndcg_at_k'] }}</td>
                                <td>{{ $report['coverage'] }}</td>
                                <td>{{ $report['diversity'] }}</td>
                                <td>{{ $report['novelty'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="admin-chart-grid">
                    <div>
                        <h2 class="admin-subheading--section">Most Recommended Products</h2>
                        <table class="product-table">
                            <thead><tr><th>Product</th><th>Shown</th></tr></thead>
                            <tbody>
                                @forelse ($mostRecommended as $row)
                                    <tr><td>{{ $row['product'] }}</td><td>{{ $row['shown'] }}</td></tr>
                                @empty
                                    <tr><td colspan="2">No data yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <h2 class="admin-subheading--section">Most Clicked Products</h2>
                        <table class="product-table">
                            <thead><tr><th>Product</th><th>Clicks</th></tr></thead>
                            <tbody>
                                @forelse ($mostClicked as $row)
                                    <tr><td>{{ $row['product'] }}</td><td>{{ $row['clicks'] }}</td></tr>
                                @empty
                                    <tr><td colspan="2">No data yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <h2 class="admin-subheading--section">Highest CTR Products</h2>
                        <table class="product-table">
                            <thead><tr><th>Product</th><th>CTR</th></tr></thead>
                            <tbody>
                                @forelse ($highestCtr as $row)
                                    <tr><td>{{ $row['product'] }}</td><td>{{ $row['ctr'] }}%</td></tr>
                                @empty
                                    <tr><td colspan="2">No data yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <h2 class="admin-subheading--section">Lowest CTR Products</h2>
                        <table class="product-table">
                            <thead><tr><th>Product</th><th>CTR</th></tr></thead>
                            <tbody>
                                @forelse ($lowestCtr as $row)
                                    <tr><td>{{ $row['product'] }}</td><td>{{ $row['ctr'] }}%</td></tr>
                                @empty
                                    <tr><td colspan="2">No data yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</x-layouts.app>
