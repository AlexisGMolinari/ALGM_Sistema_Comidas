import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import useAuth from '../hooks/useAuth';
import { Utensils, Loader2 } from 'lucide-react';

const LoginPage: React.FC = () => {
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const { login } = useAuth();
    const navigate = useNavigate();

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError('');

        if (!username || !password) {
            setError('Por favor ingrese usuario y contraseña');
            return;
        }

        setIsLoading(true);
        try {
            const success = await login(username, password);
            if (success) {
                navigate('/dashboard');
            } else {
                setError('Usuario o contraseña incorrectos');
            }
        } catch (err) {
            setError('Error al iniciar sesión. Intente nuevamente.');
            console.error(err);
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-900 via-blue-700 to-blue-600 relative overflow-hidden">
            {/* Fondo decorativo */}
            <div className="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/food.png')] opacity-10"></div>

            <div className="relative max-w-md w-full backdrop-blur-lg bg-white/10 p-8 rounded-2xl shadow-2xl border border-white/20 text-white">
                {/* Encabezado */}
                <div className="flex flex-col items-center mb-8">
                    <div className="bg-blue-500 p-4 rounded-full shadow-md mb-3">
                        <Utensils size={40} className="text-white" />
                    </div>
                    <h1 className="text-3xl font-bold tracking-wide">Sistema de Comidas</h1>
                    <p className="text-blue-100 mt-1 text-sm">Gestión rápida y eficiente</p>
                </div>

                {/* Formulario */}
                <form onSubmit={handleSubmit} className="space-y-5">
                    {error && (
                        <div className="p-3 bg-red-500/20 text-red-100 rounded-md text-sm text-center">
                            {error}
                        </div>
                    )}

                    <div>
                        <label htmlFor="username" className="block text-sm mb-1 text-blue-100">
                            Usuario
                        </label>
                        <input
                            id="username"
                            type="text"
                            value={username}
                            onChange={(e) => setUsername(e.target.value)}
                            placeholder="Ingrese su usuario"
                            className="w-full px-4 py-3 rounded-lg bg-white/20 border border-white/30 placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-blue-400 text-white"
                            autoComplete="username"
                        />
                    </div>

                    <div>
                        <label htmlFor="password" className="block text-sm mb-1 text-blue-100">
                            Contraseña
                        </label>
                        <input
                            id="password"
                            type="password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            placeholder="Ingrese su contraseña"
                            className="w-full px-4 py-3 rounded-lg bg-white/20 border border-white/30 placeholder-white/70 focus:outline-none focus:ring-2 focus:ring-blue-400 text-white"
                            autoComplete="current-password"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={isLoading}
                        className={`w-full py-3 font-semibold rounded-lg transition-all duration-200 flex items-center justify-center ${
                            isLoading
                                ? 'bg-blue-500/50 cursor-not-allowed'
                                : 'bg-blue-500 hover:bg-blue-600 shadow-lg shadow-black/30'
                        }`}
                    >
                        {isLoading ? (
                            <>
                                <Loader2 className="animate-spin mr-2" size={18} /> Iniciando...
                            </>
                        ) : (
                            'Iniciar Sesión'
                        )}
                    </button>
                </form>

                {/* Pie */}
                <div className="mt-6 text-center text-blue-200 text-xs">
                    © {new Date().getFullYear()} Sistema de Comidas. Desarrollado por:
                    <a
                        href="https://servicios.algm-webs.com/"
                        className="text-amber-400 hover:underline"
                        target="_blank"
                        rel="noopener noreferrer"
                    >   <b>  </b>ALGM
                    </a>.
                </div>
            </div>
        </div>
    );
};

export default LoginPage;
