import { useEffect, useState } from 'react';
import api from '../../api';
import { useAuth } from '../../auth/AuthContext';
import { monthLabel, peso } from '../../format';

export default function DashboardPage() {
    const { user } = useAuth();
    const [data, setData] = useState(null); // { stats, top_selling, monthly_sales, low_stock }

    useEffect(() => {
        api.get('/admin/dashboard').then((response) => setData(response.data));
    }, []);

    if (!data) {
        return <p className="text-muted">Loading dashboard...</p>;
    }

    const { stats } = data;
    const statCards = [
        { label: 'Total Revenue', value: peso(stats.revenue) },
        { label: 'Purchases Recorded', value: stats.total_purchases },
        { label: 'Pending Reviews', value: stats.pending_reviews },
        { label: 'Total Gadgets', value: stats.total_gadgets },
        { label: 'Registered Customers', value: stats.total_customers },
        { label: 'Overall Avg. Rating', value: stats.average_rating > 0 ? `${stats.average_rating} / 5` : '—' },
    ];

    // The longest bar in "Monthly Sales" is the month with the most revenue.
    const bestMonthRevenue = Math.max(...data.monthly_sales.map((month) => month.revenue), 0);

    return (
        <>
            <div className="mb-4">
                <h2 className="fw-bold mb-1">Dashboard</h2>
                <p className="text-muted">Welcome back, <strong>{user.name}</strong></p>
            </div>

            <div className="row row-cols-1 row-cols-md-3 row-cols-xl-6 g-3">
                {statCards.map((card) => (
                    <div className="col" key={card.label}>
                        <div className="card dashboard-card p-3 text-center h-100">
                            <h6 className="text-muted mb-1">{card.label}</h6>
                            <h3 className="fw-bold mb-0">{card.value}</h3>
                        </div>
                    </div>
                ))}
            </div>

            <div className="row g-4 mt-1">
                <div className="col-md-6">
                    <div className="card dashboard-card h-100">
                        <div className="card-header bg-white"><h5 className="mb-0">Top Selling Products</h5></div>
                        <div className="card-body stat-list">
                            {data.top_selling.length === 0 && <p className="text-muted mb-0">No sales recorded yet.</p>}
                            {data.top_selling.map((gadget) => (
                                <div className="stat-row" key={gadget.id}>
                                    <div className="stat-main stat-label">{gadget.brand} {gadget.gadget_name}</div>
                                    <span className="badge bg-secondary">{gadget.total_sold} sold</span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                <div className="col-md-6">
                    <div className="card dashboard-card h-100">
                        <div className="card-header bg-white"><h5 className="mb-0">Low Stock Products</h5></div>
                        <div className="card-body stat-list">
                            {data.low_stock.length === 0 && <p className="text-muted mb-0">Nothing running low.</p>}
                            {data.low_stock.map((gadget) => (
                                <div className="stat-row" key={gadget.id}>
                                    <div className="stat-main stat-label">{gadget.brand} {gadget.gadget_name}</div>
                                    {gadget.stock === 0 ? (
                                        <span className="badge bg-danger">Out of Stock</span>
                                    ) : (
                                        <span className="badge bg-warning text-dark">{gadget.stock} left</span>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>

            <div className="card dashboard-card mt-4">
                <div className="card-header bg-white">
                    <h5 className="mb-0">Monthly Sales (last 6 months with activity)</h5>
                </div>
                <div className="card-body stat-list">
                    {data.monthly_sales.length === 0 && <p className="text-muted mb-0">No sales recorded yet.</p>}
                    {data.monthly_sales.map((month) => (
                        <div className="stat-row" key={month.month}>
                            <span className="stat-month-label">{monthLabel(month.month)}</span>
                            <div className="stat-main">
                                <div className="stat-bar-track">
                                    <div
                                        className="stat-bar-fill"
                                        style={{ width: `${bestMonthRevenue ? (month.revenue / bestMonthRevenue) * 100 : 0}%` }}
                                    ></div>
                                </div>
                                <div className="stat-sub">{month.total_qty} units sold</div>
                            </div>
                            <span className="stat-value">{peso(month.revenue)}</span>
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}
