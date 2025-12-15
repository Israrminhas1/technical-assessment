<template>
    <div class="min-h-screen bg-gray-900 text-white">
        <router-view />
    </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useTradingStore } from '@/stores/trading';
import { createEcho } from '@/services/echo';

const authStore = useAuthStore();
const tradingStore = useTradingStore();

let echo = null;

function setupEcho() {
    if (!authStore.isAuthenticated) return;

    echo = createEcho();

    echo.private(`user.${authStore.user.id}`)
        .listen('.order.matched', (data) => {
            tradingStore.handleOrderMatched(data.trade);
        });
}

function cleanupEcho() {
    if (echo) {
        echo.disconnect();
        echo = null;
    }
}

watch(() => authStore.isAuthenticated, (isAuth) => {
    if (isAuth) {
        setupEcho();
    } else {
        cleanupEcho();
    }
});

onMounted(() => {
    if (authStore.isAuthenticated) {
        setupEcho();
    }
});
</script>
