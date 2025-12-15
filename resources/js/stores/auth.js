import { defineStore } from 'pinia';
import api from '@/services/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: localStorage.getItem('token') || null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
    },

    actions: {
        async register(name, email, password, passwordConfirmation) {
            const response = await api.post('/register', {
                name,
                email,
                password,
                password_confirmation: passwordConfirmation,
            });
            this.setAuth(response.data.user, response.data.token);
            return response.data;
        },

        async login(email, password) {
            const response = await api.post('/login', { email, password });
            this.setAuth(response.data.user, response.data.token);
            return response.data;
        },

        async logout() {
            try {
                await api.post('/logout');
            } finally {
                this.clearAuth();
            }
        },

        setAuth(user, token) {
            this.user = user;
            this.token = token;
            localStorage.setItem('user', JSON.stringify(user));
            localStorage.setItem('token', token);
        },

        clearAuth() {
            this.user = null;
            this.token = null;
            localStorage.removeItem('user');
            localStorage.removeItem('token');
        },
    },
});
