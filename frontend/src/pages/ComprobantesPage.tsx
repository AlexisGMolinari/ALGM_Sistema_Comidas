import React, { useEffect, useState } from 'react';
import {View, Search, Receipt, Check, X, Clock } from 'lucide-react';
import { Comprobantes } from '../types';
import { getComprobantes, getComprobantesPorFechas, BASE_URL } from '../contexts/api';

// Utility function to format currency
const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
    }).format(amount);
};

const ComprobantesPage: React.FC = () => {
    const [comprobantes, setComprobantes] = useState<Comprobantes[]>([]);
    const [searchTerm, setSearchTerm] = useState('');
    const [desde, setDesde] = useState('');
    const [hasta, setHasta] = useState('');
    const [errores, setErrores] = useState<string[]>([]);
    const [modalVisible, setModalVisible] = useState(false);
    const [selectedImageUrl, setSelectedImageUrl] = useState('');


    useEffect(() => {
        const fetchComprobantes = async () => {
            const data = await getComprobantes();
            setComprobantes(data.registros ?? []); // fallback por si no viene registros
        };
        fetchComprobantes();
    }, []);

    const handleFiltrar = async () => {
        setErrores([]);
        if (!desde || !hasta) {
            setErrores(['Debes completar ambas fechas']);
            return;
        }
        if (desde > hasta) {
            setErrores(['La fecha "Desde" no puede ser mayor que "Hasta"']);
            return;
        }
        const resultado = await getComprobantesPorFechas(desde, hasta);
        if ('errores' in resultado) {
            setErrores(resultado.errores);
            setComprobantes([]); // limpiar resultados anteriores
        } else {
            setComprobantes(resultado.registros ?? []);
        }
    };

    const handleLimpiar = async () => {
        setDesde('');
        setHasta('');
        setSearchTerm('');
        setErrores([]);
        const data = await getComprobantes();
        setComprobantes(data.registros ?? []);
    };


    const filtered = (comprobantes ?? []).filter((item) =>
        item.nombre_cliente.toLowerCase().includes(searchTerm.toLowerCase())
    );

    return (
        <div className="space-y-6">
            <div className="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-800">Gestión de Comprobantes</h1>
                    <p className="mt-1 text-gray-600">
                        Total: <span className="font-medium">{comprobantes.length} comprobantes</span>
                    </p>
                </div>
            </div>

            <div className="bg-white rounded-xl shadow-md overflow-visible">
                <div className="p-5 border-b border-gray-200 space-y-4 md:space-y-0 md:flex md:items-end md:justify-between">
                    <div className="flex flex-col md:flex-row gap-4 items-center">
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Desde</label>
                            <div className="relative z-10">
                                <input
                                    type="date"
                                    inputMode="text"
                                    value={desde}
                                    onChange={(e) => setDesde(e.target.value)}
                                    className="form-input mt-1"
                                />
                            </div>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700">Hasta</label>
                            <div className="relative z-10">
                                <input
                                    type="date"
                                    inputMode="text"
                                    value={hasta}
                                    onChange={(e) => setHasta(e.target.value)}
                                    className="form-input mt-1"
                                />
                            </div>
                        </div>
                        <button
                            onClick={handleFiltrar}
                            className="bg-[#FF6B35] text-white px-4 py-2 rounded-lg hover:bg-[#e65c28] transition"
                        >
                            Filtrar por fechas
                        </button>
                        {/* Botón Limpiar */}
                        <button
                            onClick={handleLimpiar}
                            className="bg-gray-300 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-400 transition"
                        >
                            Limpiar filtro
                        </button>
                    </div>

                    <div className="relative max-w-md mt-4 md:mt-0">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Search size={18} className="text-gray-400" />
                        </div>
                        <input
                            type="text"
                            className="pl-10 form-input w-full max-w-xs"
                            placeholder="Buscar cliente"
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                        />
                    </div>
                </div>

                {errores.length > 0 && (
                    <div className="bg-red-100 text-red-700 p-3 border-l-4 border-red-500">
                        {errores.map((err, i) => (
                            <div key={i}>{err}</div>
                        ))}
                    </div>
                )}

                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                        {(filtered ?? []).map((comp) => (
                            <tr key={comp.id} className="hover:bg-gray-50">
                                <td className="px-6 py-4">
                                    <div className="flex items-center">
                                        <div
                                            className="h-10 w-10 flex-shrink-0 bg-gray-100 rounded-lg flex items-center justify-center">
                                            <Receipt size={18} className="text-gray-500"/>
                                        </div>
                                        <div className="ml-4">
                                            <div
                                                className="text-sm font-medium text-gray-900">{comp.nombre_cliente}</div>
                                        </div>
                                    </div>
                                </td>

                                <td className="px-6 py-4 whitespace-nowrap">
                                    <div className="text-sm text-gray-900">{formatCurrency(Number(comp.total))}</div>
                                </td>

                                <td className="px-6 py-4 whitespace-nowrap">
                                    <span
                                        className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                            comp.nombre === 'completado'
                                                ? 'bg-green-100 text-green-800'
                                                : comp.nombre === 'anulado'
                                                    ? 'bg-red-100 text-red-800'
                                                    : comp.nombre === 'eliminado'
                                                        ? 'bg-gray-200 text-gray-600 line-through'
                                                        : 'bg-yellow-100 text-yellow-800'
                                        }`}
                                    >
                                        {comp.nombre === 'completado' && <Check size={12} className="mr-1"/>}
                                        {comp.nombre === 'anulado' && <X size={12} className="mr-1"/>}
                                        {comp.nombre === 'eliminado' && <X size={12} className="mr-1"/>}
                                        {comp.nombre === 'pendiente' && <Clock size={12} className="mr-1"/>}
                                        {comp.nombre.charAt(0).toUpperCase() + comp.nombre.slice(1)}
                                    </span>
                                </td>

                                <td className="px-6 py-4 text-center">
                                    <button
                                        className="text-indigo-600 hover:text-indigo-900"
                                        onClick={() => {
                                            setSelectedImageUrl(`${BASE_URL}/imagenes/comprobantes/${comp.comprobante_img}`);
                                            setModalVisible(true);
                                        }}
                                    >
                                        <View size={16}/>
                                    </button>
                                </td>

                            </tr>
                        ))}
                        </tbody>
                    </table>
                    {modalVisible && (
                        <div
                            className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
                            onClick={() => setModalVisible(false)}
                        >
                            <div
                                className="bg-white p-4 rounded-lg shadow-lg max-w-md w-full"
                                onClick={(e) => e.stopPropagation()} // evita que el click en la imagen cierre el modal
                            >
                                <div className="flex justify-end mb-2">
                                    <button
                                        className="text-gray-600 hover:text-gray-900"
                                        onClick={() => setModalVisible(false)}
                                    >
                                        ✕
                                    </button>
                                </div>
                                <img
                                    src={selectedImageUrl}
                                    alt="Comprobante"
                                    className="w-full h-auto object-contain rounded-md"
                                />
                            </div>
                        </div>
                    )}

                </div>
            </div>
        </div>
    );
};

export default ComprobantesPage;