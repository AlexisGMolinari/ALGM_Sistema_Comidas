export interface Product {
  id: number;
  nombre: string;
  precio: number;
  categoria_prod_id: number;
  nombreCategoria?: string;
  stock_actual: number;
  activo: boolean | number;
  category?: 'food' | 'drinks' | 'combos';
}

export interface OrderItem {
  id: string;
  name: string;
  price: number;
  quantity: number;
}

export interface Order {
  id: string;
  customerName: string;
  status: 'pendiente' | 'completado' | 'anulado' | 'eliminado';
  total: number;
  totalCash?: number;
  totalTransfer?: number;
  paymentMethod: 'cash' | 'transfer' | 'mixed';
  createdAt: Date;
  createdBy?: string;
  receiptImage?: string;
}

export interface Category {
  id: number;
  nombre: string;
  activo: number;
}


export interface Expense {
  id: number;
  monto: number;
  categoria_id: number;
  descripcion: string;
  fecha: string;
  createdBy: string;
}

export interface ExpenseAPIResponse {
  id: string;
  monto: number;
  categoria_id: number;
  descripcion: string;
  createdBy: string;
}

export interface CashRegister {
  id: number;
  isOpen: boolean;
  openedAt: Date; // ISO fecha
  openedByUserId: number;
  openedBy: string;
  closedAt: Date | null;
  closedByUserId: number | null;
  initialAmount: number;
  finalAmount: number | null;
  sales: number;
  expenses: number;
  currentAmount: number;
  notes?: string;
  salesCount: number;
  salesBreakdown: {
    efectivo: number;
    transferencia: number;
  };
}

export interface CierreCajaPayload {
  id: number;
  monto_final: number;
  total_ventas: number;
  total_gastos: number;
  observaciones?: string;
}

export interface User {
  id: string;
  name: string;
  username: string;
  role: 'admin' | 'employee';
}

export interface DailyReportItem {
  date: string; // viene como string del backend
  totalSales: number;
  totalExpenses: number;
  ordersCount: number;
}

export interface DailyReport {
  date: Date;
  totalSales: number;
  totalExpenses: number;
  ordersCount: number;
  balance: number;
}

export interface CategoryReport {
  name: string;
  value: number;
}

export interface MonthlyReport {
  month: string;
  totalSales: number;
  totalExpenses: number;
  balance: number;
  ordersCount: number;
  salesByCategory: CategoryReport[];
  expensesByCategory: CategoryReport[];
}

export interface Comprobantes {
  id: number;
  nombre_cliente: string;
  total: string;
  estado_id: number;
  nombre: string;
  comprobante_img: string;
  fecha_creado: string;
}

export interface DashboardResumen {
  salesTotal: number;
  pendingOrders: number;
  completedOrdersToday: number;
  cashRegisterStatus: 'open' | 'closed';
  dailyExpenses: number;
  dailyBalance: number;
}