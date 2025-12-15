<template>
    <div class="min-h-screen bg-gray-900">
        <!-- Toast Notifications -->
        <div class="fixed top-4 right-4 z-50 space-y-2">
            <transition-group name="toast">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    :class="[
                        'px-4 py-3 rounded-lg shadow-lg max-w-sm',
                        toast.type === 'success' ? 'bg-green-600' : toast.type === 'error' ? 'bg-red-600' : 'bg-blue-600'
                    ]"
                >
                    {{ toast.message }}
                </div>
            </transition-group>
        </div>

        <!-- Header -->
        <header class="bg-gray-800 border-b border-gray-700">
            <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
                <h1 class="text-xl font-bold">Trading Platform</h1>
                <div class="flex items-center gap-4">
                    <span class="text-gray-400">{{ authStore.user?.name }}</span>
                    <button @click="handleLogout" class="text-red-400 hover:text-red-300">Logout</button>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 py-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Order Form + Wallet -->
                <div class="space-y-6">
                    <!-- Order Form -->
                    <div class="bg-gray-800 rounded-lg p-6">
                        <h2 class="text-lg font-semibold mb-4">Place Order</h2>

                        <form @submit.prevent="handlePlaceOrder" class="space-y-4">
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Symbol</label>
                                <select
                                    v-model="orderForm.symbol"
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-blue-500"
                                >
                                    <option value="BTC">BTC</option>
                                    <option value="ETH">ETH</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Side</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        @click="orderForm.side = 'buy'"
                                        :class="[
                                            'py-2 rounded-lg font-medium transition',
                                            orderForm.side === 'buy'
                                                ? 'bg-green-600 text-white'
                                                : 'bg-gray-700 text-gray-400 hover:bg-gray-600'
                                        ]"
                                    >
                                        Buy
                                    </button>
                                    <button
                                        type="button"
                                        @click="orderForm.side = 'sell'"
                                        :class="[
                                            'py-2 rounded-lg font-medium transition',
                                            orderForm.side === 'sell'
                                                ? 'bg-red-600 text-white'
                                                : 'bg-gray-700 text-gray-400 hover:bg-gray-600'
                                        ]"
                                    >
                                        Sell
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Price (USD)</label>
                                <input
                                    v-model="orderForm.price"
                                    type="number"
                                    step="0.00000001"
                                    min="0"
                                    required
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-blue-500"
                                />
                            </div>

                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Amount</label>
                                <input
                                    v-model="orderForm.amount"
                                    type="number"
                                    step="0.00000001"
                                    min="0"
                                    required
                                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-blue-500"
                                />
                            </div>

                            <div class="text-sm text-gray-400">
                                Total: <span class="text-white">{{ orderTotal }} USD</span>
                            </div>

                            <div v-if="orderError" class="text-red-500 text-sm">{{ orderError }}</div>
                            <div v-if="orderSuccess" class="text-green-500 text-sm">{{ orderSuccess }}</div>

                            <button
                                type="submit"
                                :disabled="tradingStore.loading"
                                :class="[
                                    'w-full py-2 rounded-lg font-medium transition disabled:opacity-50',
                                    orderForm.side === 'buy' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'
                                ]"
                            >
                                {{ tradingStore.loading ? 'Processing...' : `Place ${orderForm.side} Order` }}
                            </button>
                        </form>
                    </div>

                    <!-- Wallet -->
                    <div class="bg-gray-800 rounded-lg p-6">
                        <h2 class="text-lg font-semibold mb-4">Wallet</h2>

                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-3 bg-gray-700 rounded-lg">
                                <span class="text-gray-400">USD Balance</span>
                                <span class="font-mono font-medium">{{ formatNumber(tradingStore.balance) }}</span>
                            </div>

                            <div
                                v-for="asset in tradingStore.assets"
                                :key="asset.symbol"
                                class="p-3 bg-gray-700 rounded-lg"
                            >
                                <div class="flex justify-between items-center">
                                    <span class="font-medium">{{ asset.symbol }}</span>
                                    <span class="font-mono">{{ formatNumber(asset.amount) }}</span>
                                </div>
                                <div class="flex justify-between text-sm text-gray-400 mt-1">
                                    <span>Available</span>
                                    <span class="font-mono">{{ formatNumber(asset.available) }}</span>
                                </div>
                                <div v-if="parseFloat(asset.locked_amount) > 0" class="flex justify-between text-sm text-yellow-500 mt-1">
                                    <span>Locked</span>
                                    <span class="font-mono">{{ formatNumber(asset.locked_amount) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Middle Column: Orderbook -->
                <div class="bg-gray-800 rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold">Orderbook</h2>
                        <select
                            v-model="tradingStore.selectedSymbol"
                            @change="tradingStore.fetchOrderbook()"
                            class="px-3 py-1 bg-gray-700 border border-gray-600 rounded-lg text-sm"
                        >
                            <option value="BTC">BTC</option>
                            <option value="ETH">ETH</option>
                        </select>
                    </div>

                    <!-- Sell Orders -->
                    <div class="mb-4">
                        <h3 class="text-sm text-red-400 mb-2">Sell Orders</h3>
                        <div class="space-y-1 max-h-48 overflow-y-auto">
                            <div
                                v-for="order in tradingStore.orderbook.sell_orders"
                                :key="order.id"
                                class="flex justify-between text-sm py-1 px-2 bg-red-900/20 rounded"
                            >
                                <span class="text-red-400 font-mono">{{ formatNumber(order.price) }}</span>
                                <span class="font-mono">{{ formatNumber(order.amount) }}</span>
                            </div>
                            <div v-if="!tradingStore.orderbook.sell_orders?.length" class="text-gray-500 text-sm text-center py-4">
                                No sell orders
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-700 my-4"></div>

                    <!-- Buy Orders -->
                    <div>
                        <h3 class="text-sm text-green-400 mb-2">Buy Orders</h3>
                        <div class="space-y-1 max-h-48 overflow-y-auto">
                            <div
                                v-for="order in tradingStore.orderbook.buy_orders"
                                :key="order.id"
                                class="flex justify-between text-sm py-1 px-2 bg-green-900/20 rounded"
                            >
                                <span class="text-green-400 font-mono">{{ formatNumber(order.price) }}</span>
                                <span class="font-mono">{{ formatNumber(order.amount) }}</span>
                            </div>
                            <div v-if="!tradingStore.orderbook.buy_orders?.length" class="text-gray-500 text-sm text-center py-4">
                                No buy orders
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: My Orders -->
                <div class="bg-gray-800 rounded-lg p-6">
                    <h2 class="text-lg font-semibold mb-4">My Orders</h2>

                    <!-- Filters -->
                    <div class="flex gap-2 mb-4">
                        <select
                            v-model="orderFilter.symbol"
                            class="px-2 py-1 bg-gray-700 border border-gray-600 rounded text-sm"
                        >
                            <option value="">All Symbols</option>
                            <option value="BTC">BTC</option>
                            <option value="ETH">ETH</option>
                        </select>
                        <select
                            v-model="orderFilter.side"
                            class="px-2 py-1 bg-gray-700 border border-gray-600 rounded text-sm"
                        >
                            <option value="">All Sides</option>
                            <option value="buy">Buy</option>
                            <option value="sell">Sell</option>
                        </select>
                        <select
                            v-model="orderFilter.status"
                            class="px-2 py-1 bg-gray-700 border border-gray-600 rounded text-sm"
                        >
                            <option value="">All Status</option>
                            <option value="1">Open</option>
                            <option value="2">Filled</option>
                            <option value="3">Cancelled</option>
                        </select>
                    </div>

                    <div class="space-y-2 max-h-[300px] overflow-y-auto">
                        <div
                            v-for="order in filteredOrders"
                            :key="order.id"
                            class="p-3 bg-gray-700 rounded-lg"
                        >
                            <div class="flex justify-between items-start">
                                <div>
                                    <span :class="order.side === 'buy' ? 'text-green-400' : 'text-red-400'" class="font-medium uppercase">
                                        {{ order.side }}
                                    </span>
                                    <span class="ml-2">{{ order.symbol }}</span>
                                </div>
                                <span :class="statusClass(order.status)" class="text-xs px-2 py-1 rounded">
                                    {{ statusText(order.status) }}
                                </span>
                            </div>
                            <div class="mt-2 text-sm text-gray-400 space-y-1">
                                <div class="flex justify-between">
                                    <span>Price:</span>
                                    <span class="font-mono">{{ formatNumber(order.price) }} USD</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Amount:</span>
                                    <span class="font-mono">{{ formatNumber(order.amount) }}</span>
                                </div>
                            </div>
                            <button
                                v-if="order.status === 1"
                                @click="handleCancelOrder(order.id)"
                                class="mt-2 w-full py-1 text-sm bg-gray-600 hover:bg-gray-500 rounded transition"
                            >
                                Cancel
                            </button>
                        </div>

                        <div v-if="!filteredOrders?.length" class="text-gray-500 text-center py-8">
                            {{ tradingStore.myOrders?.length ? 'No orders match filters' : 'No orders yet' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trade History -->
            <div class="mt-6 bg-gray-800 rounded-lg p-6">
                <h2 class="text-lg font-semibold mb-4">Trade History</h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-gray-400 border-b border-gray-700">
                                <th class="text-left py-2 px-3">Time</th>
                                <th class="text-left py-2 px-3">Symbol</th>
                                <th class="text-left py-2 px-3">Side</th>
                                <th class="text-right py-2 px-3">Price</th>
                                <th class="text-right py-2 px-3">Amount</th>
                                <th class="text-right py-2 px-3">Total</th>
                                <th class="text-right py-2 px-3">Fee</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="trade in tradingStore.myTrades"
                                :key="trade.id"
                                class="border-b border-gray-700/50 hover:bg-gray-700/30"
                            >
                                <td class="py-2 px-3 text-gray-400">{{ formatDate(trade.created_at) }}</td>
                                <td class="py-2 px-3">{{ trade.symbol }}</td>
                                <td class="py-2 px-3">
                                    <span :class="trade.side === 'buy' ? 'text-green-400' : 'text-red-400'" class="uppercase">
                                        {{ trade.side }}
                                    </span>
                                </td>
                                <td class="py-2 px-3 text-right font-mono">{{ formatNumber(trade.price) }}</td>
                                <td class="py-2 px-3 text-right font-mono">{{ formatNumber(trade.amount) }}</td>
                                <td class="py-2 px-3 text-right font-mono">{{ formatNumber(trade.total) }}</td>
                                <td class="py-2 px-3 text-right font-mono text-yellow-500">
                                    {{ parseFloat(trade.commission) > 0 ? formatNumber(trade.commission) : '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="!tradingStore.myTrades?.length" class="text-gray-500 text-center py-8">
                        No trades yet
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useTradingStore } from '@/stores/trading';
import { createEcho } from '@/services/echo';

const router = useRouter();
const authStore = useAuthStore();
const tradingStore = useTradingStore();

let echoInstance = null;

const orderForm = reactive({
    symbol: 'BTC',
    side: 'buy',
    price: '',
    amount: '',
});

const orderFilter = reactive({
    symbol: '',
    side: '',
    status: '',
});

const orderError = ref('');
const orderSuccess = ref('');
const toasts = ref([]);

function showToast(message, type = 'info') {
    const id = Date.now();
    toasts.value.push({ id, message, type });
    setTimeout(() => {
        toasts.value = toasts.value.filter(t => t.id !== id);
    }, 3000);
}

const orderTotal = computed(() => {
    const total = parseFloat(orderForm.price || 0) * parseFloat(orderForm.amount || 0);
    return isNaN(total) ? '0.00' : total.toFixed(2);
});

const filteredOrders = computed(() => {
    return tradingStore.myOrders.filter(order => {
        if (orderFilter.symbol && order.symbol !== orderFilter.symbol) return false;
        if (orderFilter.side && order.side !== orderFilter.side) return false;
        if (orderFilter.status && order.status !== parseInt(orderFilter.status)) return false;
        return true;
    });
});

function formatNumber(value) {
    const num = parseFloat(value);
    if (isNaN(num)) return '0.00000000';
    return num.toFixed(8).replace(/\.?0+$/, '') || '0';
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString();
}

function statusText(status) {
    const map = { 1: 'Open', 2: 'Filled', 3: 'Cancelled' };
    return map[status] || 'Unknown';
}

function statusClass(status) {
    const map = {
        1: 'bg-blue-600',
        2: 'bg-green-600',
        3: 'bg-gray-600',
    };
    return map[status] || 'bg-gray-600';
}

async function handlePlaceOrder() {
    orderError.value = '';
    orderSuccess.value = '';

    try {
        const result = await tradingStore.placeOrder(
            orderForm.symbol,
            orderForm.side,
            orderForm.price,
            orderForm.amount
        );

        if (result.matched) {
            orderSuccess.value = 'Order matched successfully!';
            showToast('Order matched successfully!', 'success');
        } else {
            orderSuccess.value = 'Order placed successfully!';
            showToast('Order placed successfully!', 'success');
        }

        orderForm.price = '';
        orderForm.amount = '';
    } catch (e) {
        orderError.value = e.response?.data?.message || 'Failed to place order';
        showToast(orderError.value, 'error');
    }
}

async function handleCancelOrder(orderId) {
    try {
        await tradingStore.cancelOrder(orderId);
        showToast('Order cancelled successfully!', 'success');
    } catch (e) {
        orderError.value = e.response?.data?.message || 'Failed to cancel order';
        showToast(orderError.value, 'error');
    }
}

async function handleLogout() {
    if (echoInstance) {
        echoInstance.disconnect();
    }
    await authStore.logout();
    router.push('/login');
}

function setupWebSocket() {
    if (!authStore.user?.id) return;

    echoInstance = createEcho();

    // Listen for personal order matches
    echoInstance.private(`user.${authStore.user.id}`)
        .listen('.order.matched', (event) => {
            console.log('Order matched event received:', event);
            tradingStore.handleOrderMatched(event);
            showToast(`Trade executed: ${event.trade.amount} ${event.trade.symbol} @ ${event.trade.price}`, 'success');
        });

    // Listen for orderbook updates (public channel)
    echoInstance.channel(`orderbook.${tradingStore.selectedSymbol}`)
        .listen('.order.placed', () => {
            console.log('Orderbook updated');
            tradingStore.fetchOrderbook();
        });
}

onMounted(async () => {
    await Promise.all([
        tradingStore.fetchProfile(),
        tradingStore.fetchMyOrders(),
        tradingStore.fetchMyTrades(),
        tradingStore.fetchOrderbook(),
    ]);

    setupWebSocket();
});

onUnmounted(() => {
    if (echoInstance) {
        echoInstance.disconnect();
    }
});
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from {
    opacity: 0;
    transform: translateX(100%);
}
.toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
</style>
