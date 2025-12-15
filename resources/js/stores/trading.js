import { defineStore } from 'pinia';
import api from '@/services/api';

export const useTradingStore = defineStore('trading', {
    state: () => ({
        balance: '0',
        assets: [],
        myOrders: [],
        myTrades: [],
        orderbook: {
            buy_orders: [],
            sell_orders: [],
        },
        selectedSymbol: 'BTC',
        loading: false,
        error: null,
    }),

    actions: {
        async fetchProfile() {
            try {
                const response = await api.get('/profile');
                this.balance = response.data.balance;
                this.assets = response.data.assets;
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to fetch profile';
                throw error;
            }
        },

        async fetchMyOrders() {
            try {
                const response = await api.get('/orders/my');
                this.myOrders = response.data.orders;
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to fetch orders';
                throw error;
            }
        },

        async fetchMyTrades() {
            try {
                const response = await api.get('/trades/my');
                this.myTrades = response.data.trades;
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to fetch trades';
                throw error;
            }
        },

        async fetchOrderbook(symbol = null) {
            try {
                const sym = symbol || this.selectedSymbol;
                const response = await api.get('/orders', { params: { symbol: sym } });
                this.orderbook = response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to fetch orderbook';
                throw error;
            }
        },

        async placeOrder(symbol, side, price, amount) {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.post('/orders', { symbol, side, price, amount });
                await this.fetchMyOrders();
                await this.fetchMyTrades();
                await this.fetchOrderbook(symbol);
                await this.fetchProfile();
                return response.data;
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to place order';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async cancelOrder(orderId) {
            this.loading = true;
            try {
                await api.post(`/orders/${orderId}/cancel`);
                await this.fetchMyOrders();
                await this.fetchOrderbook();
                await this.fetchProfile();
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to cancel order';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        handleOrderMatched(trade) {
            this.fetchProfile();
            this.fetchMyOrders();
            this.fetchMyTrades();
            this.fetchOrderbook();
        },

        setSymbol(symbol) {
            this.selectedSymbol = symbol;
            this.fetchOrderbook(symbol);
        },
    },
});
