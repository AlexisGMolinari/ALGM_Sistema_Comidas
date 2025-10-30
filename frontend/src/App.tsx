import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import useAuth from './hooks/useAuth';
import LoginPage from './pages/LoginPage';
import Dashboard from './pages/Dashboard';
import OrdersPage from './pages/OrdersPage';
import ProductsPage from './pages/ProductsPage';
import CashRegisterPage from './pages/CashRegisterPage';
import ExpensesPage from './pages/ExpensesPage';
import ReportsPage from './pages/ReportsPage';
import ComprobantesPage from './pages/ComprobantesPage';
import AdminPage from './pages/Admin';
import Layout from './components/Layout';
import './App.css';

// Protected route component
const ProtectedRoute = ({ children }: { children: React.ReactNode }) => {
  const { user } = useAuth();
  if (!user) {
    return <Navigate to="/login" replace />;
  }
  return <>{children}</>;
};

// Admin route component
// const AdminRoute = ({ children }: { children: React.ReactNode }) => {
//   const { user } = useAuth();
//   if (!user || user.role !== 'admin') {
//     return <Navigate to="/dashboard" replace />;
//   }
//   return <>{children}</>;
// };

function App() {
  return (
    <AuthProvider>
      <Router>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="/" element={<Navigate to="/dashboard" replace />} />
          
          <Route
            path="/dashboard"
            element={
              <ProtectedRoute>
                <Layout>
                  <Dashboard />
                </Layout>
              </ProtectedRoute>
            }
          />
          
          <Route
            path="/orders"
            element={
              <ProtectedRoute>
                <Layout>
                  <OrdersPage />
                </Layout>
              </ProtectedRoute>
            }
          />
          
          <Route
            path="/cash-register"
            element={
              <ProtectedRoute>
                <Layout>
                  <CashRegisterPage />
                </Layout>
              </ProtectedRoute>
            }
          />
          
          <Route
            path="/expenses"
            element={
              <ProtectedRoute>
                <Layout>
                  <ExpensesPage />
                </Layout>
              </ProtectedRoute>
            }
          />
          
          <Route
            path="/reports"
            element={
              <ProtectedRoute>
                <Layout>
                  <ReportsPage />
                </Layout>
              </ProtectedRoute>
            }
          />
          
          <Route
            path="/products"
            element={
              <ProtectedRoute>
                <Layout>
                  <ProductsPage />
                </Layout>
              </ProtectedRoute>
            }
          />

            <Route
                path="/comprobantes"
                element={
                    <ProtectedRoute>
                        <Layout>
                            <ComprobantesPage />
                        </Layout>
                    </ProtectedRoute>
                }
            />
            <Route
                path="/admin"
                element={
                    <ProtectedRoute>
                        <Layout>
                            <AdminPage />
                        </Layout>
                    </ProtectedRoute>
                }
            />
        </Routes>
      </Router>
    </AuthProvider>
  );
}

export default App;