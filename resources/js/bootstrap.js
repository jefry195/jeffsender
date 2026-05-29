import axios from 'axios'
import Pusher from 'pusher-js'

window.axios = axios
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// Enable sending cookies (session + XSRF-TOKEN) with every axios request
window.axios.defaults.withCredentials = true

// Helper to read a cookie value by name
function getCookie(name) {
  const match = document.cookie.match(new RegExp('(^|;\\s*)' + name + '=([^;]*)'))
  return match ? decodeURIComponent(match[2]) : null
}

// Attach the XSRF-TOKEN before every request so Sanctum accepts it
window.axios.interceptors.request.use((config) => {
  const token = getCookie('XSRF-TOKEN')
  if (token) {
    config.headers['X-XSRF-TOKEN'] = token
  }
  return config
})

window.Pusher = Pusher
