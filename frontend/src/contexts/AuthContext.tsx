import React, { createContext, useState, useEffect } from 'react';
import { auth } from './api.ts';
import {useToast} from "../components/common/SimpleToast.tsx";


// Define user types
interface User {
  id?: string;
  nombre?: string;
  email?: string;
  roles?: string;
  [key: string]: unknown;
}

// Define auth context type
interface AuthContextType {
  user: User | null;
  login: (username: string, password: string) => Promise<boolean>;
  logout: () => void;
  loading: boolean;
    getUsuarios: () => void;
    createUsuario: (usuario: any) => Promise<any>; // NUEVO
    updateUsuario: (id: number, usuario: any) => Promise<any>; // NUEVO
}

// Create context with default values
const AuthContext = createContext<AuthContextType>({
  user: null,
  login: async () => false,
  logout: () => {},
  loading: true,
    getUsuarios: async () => false,
    createUsuario: async () => { throw new Error('createUsuario no implementado'); },
    updateUsuario: async () => { throw new Error('updateUsuario no implementado'); },
});


// AuthProvider component
export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
    const { showToast } = useToast();

  useEffect(() => {
    const storedToken = localStorage.getItem('authToken');

    if (storedToken) {
      auth.getProfile()
          .then((profile) => setUser(profile))
          .catch(() => {
            localStorage.removeItem('authToken');
            localStorage.removeItem('user');
            setUser(null);
          })
          .finally(() => setLoading(false));
    } else {
      setLoading(false);
    }
  }, []);

  const login = async (username: string, password: string): Promise<boolean> => {
    try {
      await auth.login(username, password); // ← guarda token en localStorage

      const profile = await auth.getProfile(); // ← obtenés datos reales del usuario
      setUser(profile);

      return true;
    } catch (err) {
      console.error('Login error', err);
      showToast('Login Error', 'error' );
      return false;
    }
  };

  const logout = () => {
    auth.logout();
    setUser(null);
  };

  const getUsuarios = () => {
      auth.getUsuarios()
        .then((response) => {
          console.log(response);
        })
        .catch((error) => {
          console.error('Error al obtener usuarios:', error);
          showToast('Error al obtener usuarios', 'error' );
        });
  }
    const createUsuario = async (usuario: any) => {
        try {
            const res = await auth.createUsuario(usuario);
            return res;
        } catch (error) {
            console.error('Error al crear usuario:', error);
            showToast('Error al crear usuario', 'error' );
            throw error;
        }
    };

    const updateUsuario = async (id: number, usuario: any) => {
        try {
            const res = await auth.updateUsuario(id, usuario);
            return res;
        } catch (error) {
            console.error('Error al actualizar usuario:', error);
            showToast('Error al actualizar usuario', 'error' );
            throw error;
        }
    };


    return (
      <AuthContext.Provider value={{ user, login, logout, loading, getUsuarios, createUsuario, updateUsuario }}>
        {children}
      </AuthContext.Provider>
  );

};


export default AuthContext;
