import axios from 'axios';
import { CashRegister, ExpenseAPIResponse, Category, Expense, CierreCajaPayload, Order,
    Product, OrderItem, DailyReport, MonthlyReport, DailyReportItem, Comprobantes, DashboardResumen } from '../types';

const API_URL = import.meta.env.VITE_API_URL || 'https://api-elarco.algm-webs.com/api';
const BASE_URL = API_URL.replace(/\/api\/?$/, '');
//console.log("API_URL en este entorno:", API_URL);


// Axios instancia
const api = axios.create({
    baseURL: API_URL,
    headers: {
        'Content-Type': 'application/json',
    },
});

// Cliente sin token (para rutas públicas)
const publicApi = axios.create({
    baseURL: API_URL.replace(/\/api\/?$/, ''),  // Sin '/api' para acceder a /reportes/ directo
    headers: { 'Content-Type': 'application/json' },
});

// Interceptor para incluir token en cada request
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('authToken');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

api.interceptors.response.use(
    (response) => {
        // Si la respuesta contiene status 'error' en el cuerpo
        if (response.data?.status === 'error') {
            const msg = response.data?.message || 'Ocurrió un error inesperado.';
            return Promise.reject(new Error(msg));
        }
        return response;
    },
    (error) => {
        // Si el backend devolvió un error HTTP (ej. 400, 500)
        if (error.response?.data) {
            const msg =
                error.response.data.message ||
                error.response.data.error ||
                'Error en la comunicación con el servidor.';
            return Promise.reject(new Error(msg));
        }

        // Si fue un error de red o timeout
        if (error.request) {
            return Promise.reject(
                new Error('No se pudo conectar con el servidor.')
            );
        }

        // Cualquier otro error desconocido
        return Promise.reject(new Error(error.message || 'Error desconocido.'));
    }
);

export { API_URL, BASE_URL, api, publicApi };

// Funciones de autenticación
export const auth = {
    login: async (username: string, password: string) => {
        const response = await api.post('/login_check', { username, password });
        const { token } = response.data;

        // Guardar token y decodificarlo
        localStorage.setItem('authToken', token);
        const payload = JSON.parse(atob(token.split('.')[1]));
        localStorage.setItem('user', JSON.stringify(payload));

        return payload;
    },

    getProfile: async () => {
      return await api.get('/usuarios/usuario-actual').then((res) => res.data);
    },

    logout: () => {
        localStorage.removeItem('authToken');
        localStorage.removeItem('user');
    },

    getUsuarios: async() => {
      return await api.get('/usuarios/').then((res) => res.data);
    },
    // 🚀 NUEVAS RUTAS
    createUsuario: async (usuario: any) => {
        return await api.post('/usuarios/', { usuario }).then(res => res.data);
    },

    updateUsuario: async (id: number, usuario: any) => {
        return await api.put(`/usuarios/${id}`, { usuario }).then(res => res.data);
    },
};

export const getDashboardResumen = async (): Promise<DashboardResumen> => {
    try {
        const response = await api.get<DashboardResumen>('/dashboard/resumen');
        return response.data;
    } catch (error) {
        console.error('Error al obtener el resumen del dashboard:', error);
        return {
            salesTotal: 0,
            pendingOrders: 0,
            completedOrdersToday: 0,
            cashRegisterStatus: 'closed',
            dailyExpenses: 0,
            dailyBalance: 0,
        };
    }
};


export const getComprobantes = async (): Promise<{ registros: Comprobantes[]; totalRegistros: number }> => {
    try {
        const response = await api.get('/reportes/comprobantes/');
        return response.data;
    } catch (error) {
        console.error('Error al obtener los comprobantes:', error);
        return { registros: [], totalRegistros: 0 };
    }
};

export const getComprobantesPorFechas = async (
    desde: string,
    hasta: string
): Promise<{ registros: Comprobantes[]; totalRegistros: number } | { errores: string[] }> => {
    try {
        // Accedemos a /reportes/comprobantes/:desde/:hasta sin token
        const response = await api.get(`/reportes/comprobantes/${desde}/${hasta}`);
        const data = response.data;

        if (Array.isArray(data)) {
            return { registros: data, totalRegistros: data.length };
        }

        return data ?? { registros: [], totalRegistros: 0 };
    } catch (error) {
        console.error('Error al obtener comprobantes por fechas públicas:', error);
        return { errores: ['Error inesperado al conectar con el servidor'] };
    }
};


export const products = {
    getAll: async (
        queryParams?: Record<string, string | number | boolean | undefined>
    ): Promise<{ data: Product[]; total?: number }> => {
        const params = new URLSearchParams();

        if (queryParams) {
            Object.entries(queryParams).forEach(([key, val]) =>
                params.append(key, String(val))
            );
        }

        const response = await api.get(`/productos/?${params.toString()}`);
        const json = response.data;

        return {
            data: json.data ?? json.registros ?? json,
            total: json.recordsTotal ?? undefined,
        };
    },
    getById: async (id: number): Promise<Product> => {
        const response = await api.get(`/productos/${id}`);
        return response.data;
    },
    getByCategoria: async (categoriaId: number): Promise<Product[]> => {
        const response = await api.get(`/productos/categoria/${categoriaId}`);
        return response.data;
    },
    create: async (producto: Omit<Product, 'id'>): Promise<void> => {
        await api.post('/productos/', producto);
    },
    update: async (id: number, producto: Partial<Product>): Promise<void> => {
        await api.put(`/productos/${id}`, producto);
    },
    updateStock: async (productoId: number, tipo_movimiento: 1 | 2, cantidad: number): Promise<void> => {
        await api.post(`/productos/${productoId}/stock`, {
            id: productoId, // opcional si el backend no lo requiere en body, pero por seguridad podés enviarlo
            tipo_movimiento,
            cantidad,
        });
    },
    disable: async (id: number): Promise<void> => {
        await api.put(`/productos/desactivar/${id}`, { id: id, activo: 0 });
    },
};

export const combos = {
    getAll: async (): Promise<Product[]> => {
        const response = await api.get('/productos/combos');
        return response.data;
    },

    create: async (combo: Omit<Product, 'id'> & { componentes: { producto_id: number; cantidad: number }[] }): Promise<{ id: number }> => {
        const response = await api.post('/productos/combos', combo);
        return response.data; // Devuelve { id: comboId }
    },

    descontar: async (comboId: number, cantidad: number): Promise<void> => {
        await api.post('/productos/combos/descontar', {
            combo_id: comboId,
            cantidad,
        });
    },
};


export const getCajaActual = async (): Promise<CashRegister | null> => {
    try {
        const response = await api.get('/caja');
        const data = response.data;

        if (!data || data.status === 'error') return null;

        return {
            ...data,
            openedAt: new Date(data.openedAt),           // convertir a objeto Date
            closedAt: data.closedAt ? new Date(data.closedAt) : null,
        };
    } catch (error) {
        console.error('Error al obtener la caja actual:', error);
        return null;
    }
};

// --- Crear caja (POST "/") ---
export const createCaja = async (initialAmount: number): Promise<void> => {
    await api.post('/caja/', {
        id: 0,
        monto_inicial: initialAmount,
    });
};

export const cierreCaja = async (id: number, data: CierreCajaPayload): Promise<void> => {
    await api.put(`/caja/${id}`, data);
};

// ----------------------------------------- Pedidos -------------------------
interface PedidoResponse {
    id: number;
    nombre_cliente: string;
    estadoPedido: 'pendiente' | 'completado' | 'eliminado';
    total: string;
    metodoPago: 'cash' | 'transfer' | 'mixed';
    fecha_creado: string;
    comprobante_img: string;
    nombreUsuario: string;
}

interface PedidoItemResponse {
    id: number;
    pedido_id: number;
    producto_id: number;
    precio: string;
    cantidad: number;
    nombre_producto: string;
}

export const createPedido = async (
    customerName: string,
    paymentMethod: 'cash' | 'transfer' | 'mixed',
    total: number,
    items: OrderItem[],
    receiptImage?: string,
    totalCash: number = 0,
    totalTransfer: number = 0
): Promise<{ id: number; message: string }> => {

    const metodoPagoMap: Record<string, number> = {
        cash: 1,
        transfer: 2,
        mixed: 3, // si lo usás
    };

    const payload = {
        id: 0,
        estado_id: 1,
        nombre_cliente: customerName,
        total,
        total_efectivo: totalCash,
        total_transferencia: totalTransfer,
        metodo_pago_id: metodoPagoMap[paymentMethod] ?? 1,
        comprobante_img: receiptImage ?? '',
        items: items.map((item) => ({
            producto_id: Number(item.id),
            cantidad: item.quantity,
            precio: item.price,
        })),
    };

    const { data } = await api.post('/admin/pedidos/', payload);
    return data; // { id, message }
};

export const updatePedido = async (
    pedidoId: number,
    customerName: string,
    paymentMethod: 'cash' | 'transfer' | 'mixed',
    items: OrderItem[],
    receiptImage?: string
): Promise<void> => {
    const metodoPagoMap: Record<string, number> = {
        cash: 1,
        transfer: 2,
        mixed: 3,
    };

    const payload = {
        id: pedidoId,
        estado_id: 1, // pendiente por defecto, según tu lógica backend
        nombre_cliente: customerName,
        metodo_pago_id: metodoPagoMap[paymentMethod],
        comprobante_img: receiptImage ?? '',
        items: items.map((item) => ({
            producto_id: Number(item.id),
            cantidad: item.quantity,
            precio: item.price,
        })),
    };

    await api.put(`/admin/pedidos/${pedidoId}`, payload);
};



export const cancelPedido = async (pedidoId: number): Promise<void> => {
    await api.put(`/admin/pedidos/anulo-pedido/${pedidoId}`);
};


export const fetchPedidos = async (): Promise<Order[]> => {
    try {
        const response = await api.get('/admin/pedidos/');
        const { registros } = response.data;
        if (!Array.isArray(registros)) return [];

        return registros.map((item: PedidoResponse): Order => ({
            id: String(item.id),
            customerName: item.nombre_cliente,
            status: item.estadoPedido,
            total: parseFloat(item.total),
            paymentMethod: normalizePaymentMethod(item.metodoPago),
            createdAt: new Date(item.fecha_creado),
            createdBy: item.nombreUsuario,
            receiptImage: item.comprobante_img,
        }));
    } catch (error) {
        console.error('Error al obtener pedidos:', error);
        return [];
    }
};

export const fetchPedidoById = async (
    pedidoId: number
): Promise<{ order: Order; items: OrderItem[] } | null> => {
    try {
        // 1) Datos generales del pedido
        const response = await api.get(`/admin/pedidos/${pedidoId}`);
        const item: PedidoResponse = response.data;

        const order: Order = {
            id: String(item.id),
            customerName: item.nombre_cliente,
            status: item.estadoPedido,
            total: parseFloat(item.total),
            paymentMethod: normalizePaymentMethod(item.metodoPago),
            createdAt: new Date(item.fecha_creado),
            createdBy: item.nombreUsuario,
            receiptImage: item.comprobante_img,
        };

        // 2) Productos del pedido
        const detailResponse = await api.get(`/admin/pedidos/detalle/${pedidoId}`);
        const detalle: PedidoItemResponse[] = detailResponse.data;

        const items: OrderItem[] = detalle.map((i) => ({
            id: String(i.producto_id),
            name: i.nombre_producto,
            price: parseFloat(i.precio),
            quantity: i.cantidad,
        }));

        return { order, items };
    } catch (error) {
        console.error(`Error al obtener pedido con ID ${pedidoId}:`, error);
        return null;
    }
};

export const completeOrder = async (orderId: string): Promise<void> => {
    try {
        await api.put(`/admin/pedidos/${orderId}/completar`);
    } catch (error) {
        console.error(`Error al completar el pedido con ID ${orderId}:`, error);
        throw error;
    }
};


export const uploadComprobanteImage = async (
    pedidoId: number,
    comprobanteFile: File
): Promise<void> => {
    const formData = new FormData();
    formData.append('id', String(pedidoId));
    formData.append('comprobante_img', comprobanteFile);

    await api.post(`/admin/pedidos/comprobante/${pedidoId}`, formData, {
        withCredentials: true,
        headers: {
            // IMPORTANTE: axios detecta 'multipart/form-data' con FormData automáticamente,
            // pero lo ponemos explícito para claridad
            'Content-Type': 'multipart/form-data',
        },
    });
};

const normalizePaymentMethod = (input: string): 'cash' | 'transfer' | 'mixed' => {
    switch (input.toLowerCase()) {
        case 'transferencia':
        case 'transfer':
            return 'transfer';
        case 'efectivo':
        case 'cash':
            return 'cash';
        case 'mixto':
        case 'mixed':
            return 'mixed';
        default:
            return 'cash'; // Valor por defecto
    }
};

// ------------------------------------------ Categorias de Productos
export const fetchProductosByCategoria = async (categoriaId: number): Promise<Product[]> => {
    try {
        const response = await api.get(`/productos/categoria/${categoriaId}`);

        // Tipado temporal explícito
        const productos: {
            id: number;
            nombre: string;
            precio: string;
            categoria_prod_id: number;
            stock_actual: number;
            activo: number;
            nombreCategoria: 'food' | 'drinks' | 'combos'; // Validado contra tu interfaz
        }[] = response.data;

        return productos.map((p) => ({
            id: Number(p.id),
            nombre: p.nombre,
            precio: parseFloat(p.precio),
            categoria_prod_id: p.categoria_prod_id,
            stock_actual: p.stock_actual,
            category: p.nombreCategoria as Product['category'],
            activo: p.activo === 1,
        }));
    } catch (error) {
        console.error(`Error al obtener productos de categoría ${categoriaId}:`, error);
        return [];
    }
};

// ------------------------------------------ Reportes de Pedidos

// month: número o string del mes seleccionado (ej: "10" para octubre)
export const fetchMonthlyReport = async (month: string): Promise<MonthlyReport | null> => {
    try {
        // Enviamos el mes como query param
        const response = await api.get('/admin/reportes/mensual', {
            params: { month }
        });
        return response.data;
    } catch (error) {
        console.error('Error al obtener el reporte mensual:', error);
        return null;
    }
};

// Obtener reporte diario
export const fetchDailyReport = async (): Promise<DailyReport[] | null> => {
    try {
        const response = await api.get<DailyReportItem[]>('/admin/reportes/semanal');

        return response.data.map((item) => ({
            date: new Date(item.date),
            totalSales: Number(item.totalSales),
            totalExpenses: Number(item.totalExpenses),
            ordersCount: Number(item.ordersCount),
            balance: Number(item.totalSales) - Number(item.totalExpenses),
        }));
    } catch (error) {
        console.error('Error al obtener el reporte diario:', error);
        return null;
    }
};

// ------------------------------------------ Egresos
export const fetchExpenses = async (): Promise<Expense[]> => {
    try {
        const resp = await api.get<{ registros: Expense[] }>('/egreso/');
        return resp.data.registros.map(item => ({
            ...item,
            fecha: item.fecha, // ya es string
            monto: Number(item.monto),
        }));
    } catch (e) {
        console.error('Error al cargar egresos', e);
        return [];
    }
};
export const fetchExpenseCategories = async (): Promise<Category[]> => {
    try {
        const resp = await api.get<Category[]>('/egreso/categorias-egreso');
        return resp.data;
    } catch (e) {
        console.error('Error al cargar categorías de egreso', e);
        return [];
    }
};


export const addExpense = async (payload: Omit<ExpenseAPIResponse, 'id' | 'createdBy'>): Promise<void> =>
    await api.post('/egreso/', {id: 0, ...payload});

export const updateExpense = async (id: number, payload: Partial<ExpenseAPIResponse>): Promise<void> =>
    await api.put(`/egreso/${id}`, {id: id, ...payload});

export const deleteExpense = async (id: number): Promise<void> =>
    await api.delete(`/egreso/${id}`);

