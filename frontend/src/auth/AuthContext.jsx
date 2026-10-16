import { createContext, useContext, useEffect, useState } from 'react';
import api from '../api';

const AuthContext = createContext(null);

// Remembers who is logged in so every page can use it via useAuth().
export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    // When the app first loads, ask Laravel if we're already logged in.
    useEffect(() => {
        api.get('/user')
            .then((response) => setUser(response.data))
            .catch(() => setUser(null))
            .finally(() => setLoading(false));
    }, []);

    async function login(email, password) {
        const response = await api.post('/login', { email, password });
        setUser(response.data);
        return response.data;
    }

    async function logout() {
        await api.post('/logout');
        setUser(null);
    }

    return (
        <AuthContext.Provider value={{ user, loading, login, logout }}>
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    return useContext(AuthContext);
}
