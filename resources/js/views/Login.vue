<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-900 px-4">
        <div class="max-w-md w-full bg-gray-800 rounded-lg p-8">
            <h2 class="text-2xl font-bold text-center mb-8">Login</h2>

            <form @submit.prevent="handleLogin" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium mb-2">Email</label>
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-blue-500"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Password</label>
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-blue-500"
                    />
                </div>

                <div v-if="error" class="text-red-500 text-sm">{{ error }}</div>

                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full py-2 bg-blue-600 hover:bg-blue-700 rounded-lg font-medium transition disabled:opacity-50"
                >
                    {{ loading ? 'Logging in...' : 'Login' }}
                </button>
            </form>

            <p class="mt-6 text-center text-gray-400">
                Don't have an account?
                <router-link to="/register" class="text-blue-500 hover:underline">Register</router-link>
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const form = reactive({
    email: '',
    password: '',
});
const loading = ref(false);
const error = ref('');

async function handleLogin() {
    loading.value = true;
    error.value = '';
    try {
        await authStore.login(form.email, form.password);
        router.push('/');
    } catch (e) {
        error.value = e.response?.data?.message || 'Login failed';
    } finally {
        loading.value = false;
    }
}
</script>
