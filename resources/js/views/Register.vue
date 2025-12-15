<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-900 px-4">
        <div class="max-w-md w-full bg-gray-800 rounded-lg p-8">
            <h2 class="text-2xl font-bold text-center mb-8">Register</h2>

            <form @submit.prevent="handleRegister" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium mb-2">Name</label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-blue-500"
                    />
                </div>

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
                        minlength="8"
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:border-blue-500"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Confirm Password</label>
                    <input
                        v-model="form.passwordConfirmation"
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
                    {{ loading ? 'Creating account...' : 'Register' }}
                </button>
            </form>

            <p class="mt-6 text-center text-gray-400">
                Already have an account?
                <router-link to="/login" class="text-blue-500 hover:underline">Login</router-link>
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
    name: '',
    email: '',
    password: '',
    passwordConfirmation: '',
});
const loading = ref(false);
const error = ref('');

async function handleRegister() {
    loading.value = true;
    error.value = '';
    try {
        await authStore.register(form.name, form.email, form.password, form.passwordConfirmation);
        router.push('/');
    } catch (e) {
        error.value = e.response?.data?.message || 'Registration failed';
    } finally {
        loading.value = false;
    }
}
</script>
