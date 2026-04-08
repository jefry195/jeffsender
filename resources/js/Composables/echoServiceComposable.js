import { usePage } from '@inertiajs/vue3'
import Pusher from 'pusher-js'
import Echo from 'laravel-echo'

window.Pusher = Pusher
window.Echo = Echo

export default {
  connect: () => {
    try {
      return new Echo(usePage().props.broadcast_config ?? null)
    } catch (error) {
      console.error('Unable to connect websocket server :', error)
    }
  }
}
