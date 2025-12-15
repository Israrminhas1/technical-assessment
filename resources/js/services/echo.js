import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

export function createEcho() {
  const token = localStorage.getItem('token')

  return new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
    forceTLS: false,
    enabledTransports: ['ws'],
    authEndpoint: '/api/broadcasting/auth',
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
      },
    },
  })
}

export default createEcho
