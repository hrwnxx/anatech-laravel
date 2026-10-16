import { Route, Routes } from 'react-router-dom';
import RequireLogin from './auth/RequireLogin';
import AdminLayout from './layouts/AdminLayout';
import CustomerLayout from './layouts/CustomerLayout';

import CatalogPage from './pages/guest/CatalogPage';
import GadgetDetailsPage from './pages/guest/GadgetDetailsPage';
import LoginPage from './pages/auth/LoginPage';
import MyPurchasesPage from './pages/customer/MyPurchasesPage';
import MyReviewsPage from './pages/customer/MyReviewsPage';
import NotFoundPage from './pages/NotFoundPage';

import CustomersPage from './pages/admin/CustomersPage';
import DashboardPage from './pages/admin/DashboardPage';
import GadgetsPage from './pages/admin/GadgetsPage';
import PurchasesPage from './pages/admin/PurchasesPage';
import ReviewsPage from './pages/admin/ReviewsPage';
import UsersPage from './pages/admin/UsersPage';

// Every page of the app and the URL it lives at.
export default function App() {
    return (
        <Routes>
            <Route path="/login" element={<LoginPage />} />

            {/* Customer side: the top navigation bar */}
            <Route element={<CustomerLayout />}>
                <Route path="/" element={<CatalogPage />} />
                <Route path="/gadgets/:id" element={<GadgetDetailsPage />} />

                <Route element={<RequireLogin />}>
                    <Route path="/my/purchases" element={<MyPurchasesPage />} />
                    <Route path="/my/reviews" element={<MyReviewsPage />} />
                </Route>
            </Route>

            {/* Admin side: the sidebar. Admins only. */}
            <Route element={<RequireLogin role="admin" />}>
                <Route path="/admin" element={<AdminLayout />}>
                    <Route index element={<DashboardPage />} />
                    <Route path="gadgets" element={<GadgetsPage />} />
                    <Route path="reviews" element={<ReviewsPage />} />
                    <Route path="purchases" element={<PurchasesPage />} />
                    <Route path="customers" element={<CustomersPage />} />
                    <Route path="users" element={<UsersPage />} />
                </Route>
            </Route>

            <Route path="*" element={<NotFoundPage />} />
        </Routes>
    );
}
