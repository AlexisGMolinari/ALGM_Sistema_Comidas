import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import useAuth from '../hooks/useAuth';
import { Loader2, Eye, EyeOff } from 'lucide-react';

const LoginPage: React.FC = () => {
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [loginSuccess, setLoginSuccess] = useState(false);
    const [errorShake, setErrorShake] = useState(false);

    const [isLoading, setIsLoading] = useState(false);

    const { login } = useAuth();
    const navigate = useNavigate();

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        setError('');
        setErrorShake(false);

        if (!username || !password) {
            setError('Por favor ingrese usuario y contraseña');
            setErrorShake(true);

            setTimeout(() => {
                setErrorShake(false);
            }, 500);

            return;
        }

        setIsLoading(true);

        try {
            const success = await login(username, password);

            if (success) {
                setLoginSuccess(true);

                setTimeout(() => {
                    navigate('/dashboard');
                }, 900);

            } else {
                setError('Usuario o contraseña incorrectos');
                setErrorShake(true);

                setTimeout(() => {
                    setErrorShake(false);
                }, 500);
            }

        } catch (err) {
            setError('Error al iniciar sesión. Intente nuevamente.');
            setErrorShake(true);

            setTimeout(() => {
                setErrorShake(false);
            }, 500);

            console.error(err);
        } finally {
            setIsLoading(false);
        }
    };


    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-950 via-blue-800 to-blue-700 relative overflow-hidden">

            {/* Fondo textura */}
            <div className="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/food.png')] opacity-10"></div>

            {/* Glow decorativo */}
            <div className="absolute w-[500px] h-[500px] bg-blue-500/20 rounded-full blur-3xl -top-40 -left-40"></div>
            <div className="absolute w-[400px] h-[400px] bg-indigo-500/20 rounded-full blur-3xl bottom-0 right-0"></div>

            {/* CARD */}
            <div className={`relative max-w-md w-full backdrop-blur-xl bg-white/10 p-10 rounded-3xl shadow-2xl border border-white/20 text-white
            transition-all duration-700 ease-out animate-loginEntry
            ${errorShake ? 'animate-shake border-red-400/40' : ''}`}>
                {/* OVERLAY DE ÉXITO */}
                {loginSuccess && (
                    <div className="absolute inset-0 rounded-3xl overflow-hidden z-50">

                        {/* Fondo con transición suave */}
                        <div className="absolute inset-0 bg-gradient-to-br from-emerald-500 to-green-600 opacity-95 animate-fadeIn"></div>

                        {/* Contenido */}
                        <div className="relative flex items-center justify-center h-full flex-col text-white animate-successFade">
                            <div className="w-16 h-16 border-4 border-white/30 border-t-white rounded-full animate-spin mb-6"></div>

                            <div className="text-lg tracking-wider font-semibold uppercase">
                                Bienvenido de nuevo!
                            </div>

                        </div>
                    </div>
                )}


                {/* LOGO */}
                <div className="flex flex-col items-center mb-8">
                    <img
                        src="/LOGO-ALGM.png"
                        alt="ALGM Logo"
                        className="w-20 mb-4 drop-shadow-xl animate-float"
                    />
                    <h1 className="text-3xl font-semibold tracking-widest">
                        Sistema de Comidas
                    </h1>
                    <p className="text-blue-200 mt-2 text-sm opacity-80">
                        Gestión rápida y eficiente
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">

                    {error && (
                        <div className="p-3 bg-red-500/20 text-red-100 rounded-lg text-sm text-center border border-red-400/30">
                            {error}
                        </div>
                    )}

                    {/* USERNAME */}
                    <div>
                        <label className="block text-sm mb-2 text-blue-200">
                            Correo de usuario
                        </label>
                        <input
                            id="username"
                            type="text"
                            value={username}
                            onChange={(e) => setUsername(e.target.value)}
                            placeholder="Ingrese su correo"
                            className={`w-full px-4 py-3 rounded-xl bg-white/15 border
                            ${error ? 'border-red-400 focus:ring-red-400 focus:border-red-400' : 'border-white/20 focus:ring-blue-400 focus:border-blue-400'}
                            placeholder-white/60 focus:outline-none focus:ring-2
                            transition-all duration-300 text-white`}

                            autoComplete="username"
                        />
                    </div>

                    {/* PASSWORD */}
                    <div className="relative">
                        <label className="block text-sm mb-2 text-blue-200">
                            Contraseña
                        </label>

                        <input
                            id="password"
                            type={showPassword ? "text" : "password"}
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            placeholder="Ingrese su contraseña"
                            className={`w-full px-4 py-3 pr-12 rounded-xl bg-white/15 border
                            ${error ? 'border-red-400 focus:ring-red-400 focus:border-red-400' : 'border-white/20 focus:ring-blue-400 focus:border-blue-400'}
                            placeholder-white/60 focus:outline-none focus:ring-2
                            transition-all duration-300 text-white`}
                            autoComplete="current-password"
                        />

                        <button
                            type="button"
                            onClick={() => setShowPassword(!showPassword)}
                            className="absolute right-4 top-[42px] text-blue-200 hover:text-white transition"
                        >
                            {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                        </button>
                    </div>

                    {/* BOTÓN MODERNO RESTAURADO */}
                    <button
                        type="submit"
                        disabled={isLoading || loginSuccess}
                        className={`w-full py-3 font-semibold rounded-xl transition-all duration-300 flex items-center justify-center
                    bg-gradient-to-r from-blue-500 to-blue-600
                    hover:from-blue-600 hover:to-blue-700
                    shadow-lg shadow-blue-900/40
                    ${isLoading ? 'opacity-70 cursor-not-allowed' : ''}
                    `}
                    >
                        {isLoading ? (
                            <>
                                <Loader2 className="animate-spin mr-2" size={18} />
                                Iniciando...
                            </>
                        ) : (
                            'Iniciar Sesión'
                        )}
                    </button>

                </form>

                <div className="mt-8 text-center text-blue-200 text-xs opacity-70">
                    © {new Date().getFullYear()} Sistema de Comidas. Desarrollado por
                    <a
                        href="https://servicios.algm-webs.com/"
                        className="text-amber-400 hover:underline ml-1"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        ALGM
                    </a>
                </div>
            </div>
        </div>
    );

};

export default LoginPage;
