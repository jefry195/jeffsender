import { usePage } from '@inertiajs/vue3'
import Pusher from 'pusher-js'
import Echo from 'laravel-echo'

window.Pusher = Pusher

export default {
  connect: () => {
    try {
      const config = usePage().props.broadcast_config;
      if (!config || Object.keys(config).length === 0 || !config.broadcaster) {
        return null;
      }
      if (!window.Echo) {
        window.Echo = new Echo(config);
      }
      return window.Echo;
    } catch (error) {
      console.error('Unable to connect websocket server :', error)
      return null;
    }
  }
}

